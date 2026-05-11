<?php

namespace Feed\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime as CakeDateTime;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Xml;
use Cake\View\SerializedView;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

/**
 * A view class for generating Atom 1.0 feeds (RFC 4287).
 *
 * Atom is the stricter, better-specified successor to RSS 2.0 — preferred for
 * anything that isn't a podcast (which still requires RSS for tooling support).
 * Per-entry it supports multiple `<link>` with explicit `@rel`, structured
 * `<author>` with `<name>/<email>/<uri>`, typed `<content type="text|html|xhtml">`,
 * separate `<published>` vs `<updated>`, RFC-3339 dates, and mandatory `<id>`.
 *
 * Setup mirrors RssView. By setting the `serialize` view option to a viewVar
 * name, you can render an Atom feed without any template file:
 *
 * ```
 * $this->set(['feed' => $feed]);
 * $this->viewBuilder()->setOption('serialize', 'feed');
 * $this->viewBuilder()->setClassName('Feed.Atom');
 * ```
 *
 * Input shape (all keys are optional unless noted; bare strings and
 * structured arrays are accepted interchangeably for convenience):
 *
 * ```php
 * $feed = [
 *     'id' => 'http://example.org/feed', // REQUIRED per RFC 4287
 *     'title' => 'Channel title', // REQUIRED
 *     'updated' => '2026-05-11T12:00:00Z', // REQUIRED — DateTime, string, or int
 *     'subtitle' => 'A short description',
 *     'link' => 'http://example.org/', // or full @href array, or list of links
 *     'author' => 'Jane Doe', // or ['name' => ..., 'email' => ..., 'uri' => ...]
 *     'rights' => 'Copyright 2026',
 *     'generator' => 'CakePHP Feed Plugin',
 *     'entries' => [
 *         [
 *             'id' => 'http://example.org/posts/1', // REQUIRED
 *             'title' => 'A post', // REQUIRED
 *             'updated' => '2026-05-11', // REQUIRED
 *             'link' => 'http://example.org/posts/1',
 *             'summary' => 'A teaser',
 *             'content' => '<p>HTML body</p>', // shorthand: type=html, CDATA-wrapped
 *             'author' => 'Jane',
 *             'published' => '2026-05-10',
 *             'category' => 'tech',
 *         ],
 *     ],
 * ];
 * ```
 *
 * For full attribute control wrap any field in an array with `@`-prefixed keys
 * for attributes and `@` for the text content, exactly like RssView:
 *
 * - `link`: `['@href' => '/feed', '@rel' => 'self', '@type' => 'application/atom+xml']`
 * - `content`: `['@type' => 'xhtml', '@' => '<div xmlns="http://www.w3.org/1999/xhtml">…</div>']`
 * - `category`: `['@term' => 'php', '@scheme' => 'http://example.org/tags', '@label' => 'PHP']`
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @see https://datatracker.ietf.org/doc/html/rfc4287
 */
class AtomView extends SerializedView {

	/**
	 * The Atom 1.0 default namespace.
	 *
	 * @var string
	 */
	public const ATOM_NAMESPACE = 'http://www.w3.org/2005/Atom';

	/**
	 * Holds additional namespaces beyond the Atom default. Used the same way as
	 * RssView's namespace map — `xmlns:prefix="url"` is emitted only if the
	 * prefix actually appears as a key in the input (e.g. `dc:creator`).
	 *
	 * @var array<string, string>
	 */
	protected array $_namespaces = [
		'dc' => 'http://purl.org/dc/elements/1.1/',
		'content' => 'http://purl.org/rss/1.0/modules/content/',
		'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
	];

	/**
	 * Tracks namespace prefixes that actually appeared in the input, so
	 * unused namespace decls don't pollute the output.
	 *
	 * @var array<int, string>
	 */
	protected array $_usedNamespaces = [];

	/**
	 * CDATA placeholders. The Xml::fromArray() pipeline escapes everything by
	 * default, so HTML content is staged here under a sentinel string that we
	 * swap back in after rendering.
	 *
	 * @var array<int, string>
	 */
	protected array $_cdata = [];

	/**
	 * @param \Cake\Http\ServerRequest|null $request
	 * @param \Cake\Http\Response|null $response
	 * @param \Cake\Event\EventManager|null $eventManager
	 * @param array<string, mixed> $viewOptions
	 */
	public function __construct(
		?ServerRequest $request = null,
		?Response &$response = null,
		?EventManager $eventManager = null,
		array $viewOptions = [],
	) {
		// `atom` is not in Cake's built-in MIME map, so register it
		// idempotently here. We use the instance method on a throwaway
		// Response because the underlying type map lives in static state
		// shared across instances — this works on both Cake 5.1 (where
		// MimeType isn't a separate class yet) and 5.2+ (where it is).
		// The plugin's config/bootstrap.php registers the same binding
		// for route-extension dispatch; doing it here too keeps AtomView
		// usable when the plugin bootstrap hasn't been loaded (e.g.
		// ad-hoc instantiation in integration tests).
		(new Response())->setTypeMap('atom', 'application/atom+xml');

		parent::__construct($request, $response, $eventManager, $viewOptions);

		if ($response !== null) {
			$response = $response->withType('atom');
		}
	}

	/**
	 * @return string The Atom MIME type.
	 */
	public static function contentType(): string {
		return 'application/atom+xml';
	}

	/**
	 * Register a custom namespace usable as `prefix:tag` keys in the input.
	 *
	 * @param string $prefix
	 * @param string $url
	 * @return void
	 */
	public function setNamespace(string $prefix, string $url): void {
		$this->_namespaces[$prefix] = $url;
	}

	/**
	 * Normalize any date-ish input to an RFC 3339 string. Atom dates are
	 * RFC 3339 — never RFC 822 like RSS.
	 *
	 * @param \DateTimeInterface|string|int $time
	 * @return string
	 */
	public function time(DateTimeInterface|string|int $time): string {
		if ($time instanceof DateTimeInterface) {
			return $time->format(DateTimeInterface::ATOM);
		}
		if (is_int($time)) {
			return (new DateTimeImmutable('@' . $time))->format(DateTimeInterface::ATOM);
		}

		return (new CakeDateTime($time))->format(DateTimeInterface::ATOM);
	}

	/**
	 * Serialize view vars into Atom XML.
	 *
     * @param array<string>|string $serialize
     * @throws \RuntimeException When a custom namespace prefix was used without being registered.
     * @return string
	 */
	protected function _serialize(array|string $serialize): string {
		if (is_array($serialize)) {
			$data = [];
			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}
				$data[$alias] = $this->viewVars[$key];
			}
		} else {
			$data = $this->viewVars[$serialize] ?? null;
			if (is_array($data) && Hash::numeric(array_keys($data))) {
				$data = [$serialize => $data];
			}
		}
		$data = (array)$data;

		if (!empty($data['namespace'])) {
			foreach ((array)$data['namespace'] as $prefix => $url) {
				$this->setNamespace($prefix, $url);
			}
			unset($data['namespace']);
		}

		$entries = $data['entries'] ?? [];
		unset($data['entries']);

		$feed = $this->_prepareFeed($data);
		if ($entries) {
			$feed['entry'] = [];
			foreach ($entries as $entry) {
				$feed['entry'][] = $this->_prepareEntry($entry);
			}
		}

		// The default `xmlns` is emitted via Xml::fromArray's `@`-attribute
		// shape; prefixed `xmlns:foo` declarations use the *bare* key shape
		// because Cake's array-to-XML pipeline interprets `@xmlns:foo` as a
		// regular attribute and fails on `setAttributeNS` for the xmlns
		// namespace. Plain `xmlns:foo` keys are recognized via a dedicated
		// `str_contains($key, 'xmlns:')` branch in Xml::_createChild().
		$root = ['@xmlns' => static::ATOM_NAMESPACE] + $feed;
		foreach ($this->_usedNamespaces as $prefix) {
			if (!isset($this->_namespaces[$prefix])) {
				throw new RuntimeException(sprintf('The prefix %s is not specified.', $prefix));
			}
			$root['xmlns:' . $prefix] = $this->_namespaces[$prefix];
		}

		$options = [];
		if (Configure::read('debug')) {
			$options['pretty'] = true;
		}

		$output = Xml::fromArray(['feed' => $root], $options)->asXML();
		if ($output === false) {
			return '';
		}

		return $this->_replaceCdata($output);
	}

	/**
	 * Build the feed-level (channel-equivalent) element body.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	protected function _prepareFeed(array $data): array {
		$out = [];

		foreach (['id', 'title'] as $k) {
			if (isset($data[$k])) {
				$out[$k] = $data[$k];
				unset($data[$k]);
			}
		}
		if (isset($data['updated'])) {
			$out['updated'] = $this->time($data['updated']);
			unset($data['updated']);
		}
		if (isset($data['subtitle'])) {
			$out['subtitle'] = $this->_prepareText($data['subtitle']);
			unset($data['subtitle']);
		}
		if (isset($data['link'])) {
			$out['link'] = $this->_prepareLinks($data['link']);
			unset($data['link']);
		}
		if (isset($data['author'])) {
			$out['author'] = $this->_preparePersons($data['author']);
			unset($data['author']);
		}
		if (isset($data['contributor'])) {
			$out['contributor'] = $this->_preparePersons($data['contributor']);
			unset($data['contributor']);
		}
		foreach (['icon', 'logo', 'generator'] as $k) {
			if (isset($data[$k])) {
				$out[$k] = $data[$k];
				unset($data[$k]);
			}
		}
		if (isset($data['rights'])) {
			$out['rights'] = $this->_prepareText($data['rights']);
			unset($data['rights']);
		}
		if (isset($data['category'])) {
			$out['category'] = $this->_prepareCategories($data['category']);
			unset($data['category']);
		}

		// Anything left over (namespaced extensions like `itunes:owner`,
		// custom elements) is carried through verbatim. Tracks the prefix
		// so the matching `xmlns:foo` decl gets emitted at the root.
		foreach ($data as $k => $v) {
			$this->_trackNamespace($k);
			$out[$k] = $v;
		}

		return $out;
	}

	/**
	 * Build one `<entry>`.
	 *
	 * @param array<string, mixed> $entry
	 * @return array<string, mixed>
	 */
	protected function _prepareEntry(array $entry): array {
		$out = [];

		foreach (['id', 'title'] as $k) {
			if (isset($entry[$k])) {
				$out[$k] = $entry[$k];
				unset($entry[$k]);
			}
		}
		foreach (['updated', 'published'] as $k) {
			if (isset($entry[$k])) {
				$out[$k] = $this->time($entry[$k]);
				unset($entry[$k]);
			}
		}
		if (isset($entry['link'])) {
			$out['link'] = $this->_prepareLinks($entry['link']);
			unset($entry['link']);
		}
		if (isset($entry['author'])) {
			$out['author'] = $this->_preparePersons($entry['author']);
			unset($entry['author']);
		}
		if (isset($entry['contributor'])) {
			$out['contributor'] = $this->_preparePersons($entry['contributor']);
			unset($entry['contributor']);
		}
		if (isset($entry['summary'])) {
			$out['summary'] = $this->_prepareText($entry['summary']);
			unset($entry['summary']);
		}
		if (isset($entry['content'])) {
			$out['content'] = $this->_prepareText($entry['content']);
			unset($entry['content']);
		}
		if (isset($entry['rights'])) {
			$out['rights'] = $this->_prepareText($entry['rights']);
			unset($entry['rights']);
		}
		if (isset($entry['category'])) {
			$out['category'] = $this->_prepareCategories($entry['category']);
			unset($entry['category']);
		}
		if (isset($entry['source']) && is_array($entry['source'])) {
			// <source> carries the feed metadata an entry was originally
			// published in; structurally it's a stripped-down feed.
			$out['source'] = $this->_prepareFeed($entry['source']);
			unset($entry['source']);
		}

		foreach ($entry as $k => $v) {
			$this->_trackNamespace($k);
			$out[$k] = $v;
		}

		return $out;
	}

	/**
	 * Normalize link input to the Xml::fromArray attribute shape.
	 *
	 * Accepts a bare URL string, a single `@href` attribute array, or a list
	 * of either. Bare strings default to `rel="alternate"`. Caller-supplied
	 * `@rel` and `@type` are preserved verbatim.
	 *
	 * @param mixed $links
	 * @return array<int, array<string, mixed>>
	 */
	protected function _prepareLinks(mixed $links): array {
		if (is_string($links)) {
			return [$this->_oneLink($links)];
		}
		if (is_array($links) && isset($links['@href'])) {
			return [$this->_oneLink($links)];
		}
		if (is_array($links) && array_is_list($links)) {
			return array_map(fn ($l) => $this->_oneLink($l), $links);
		}

		return [$this->_oneLink($links)];
	}

	/**
	 * @param mixed $link
	 * @return array<string, mixed>
	 */
	protected function _oneLink(mixed $link): array {
		if (is_string($link)) {
			return ['@href' => Router::url($link, true), '@rel' => 'alternate'];
		}
		if (is_array($link) && isset($link['@href'])) {
			$link['@href'] = Router::url($link['@href'], true);
			$link['@rel'] ??= 'alternate';

			return $link;
		}

		/** @var array<string, mixed> $link */
		return $link;
	}

	/**
	 * Build a list of `<author>` or `<contributor>` elements.
	 *
	 * Atom person constructs have a required `<name>` plus optional `<email>`
	 * and `<uri>`. A bare string is treated as the name; an associative array
	 * with `name`/`email`/`uri` keys is used as-is; a list of either is
	 * rendered as multiple sibling elements.
	 *
	 * @param mixed $persons
	 * @return array<int, array<string, mixed>>
	 */
	protected function _preparePersons(mixed $persons): array {
		if (is_string($persons)) {
			return [['name' => $persons]];
		}
		if (
			is_array($persons)
			&& (isset($persons['name']) || isset($persons['email']) || isset($persons['uri']))
		) {
			return [$this->_onePerson($persons)];
		}
		if (is_array($persons) && array_is_list($persons)) {
			return array_map(fn ($p) => $this->_onePerson($p), $persons);
		}

		return [$this->_onePerson($persons)];
	}

	/**
	 * @param mixed $person
	 * @return array<string, mixed>
	 */
	protected function _onePerson(mixed $person): array {
		if (is_string($person)) {
			return ['name' => $person];
		}
		if (!is_array($person)) {
			return ['name' => (string)$person];
		}

		// Emit canonical order so output is deterministic.
		$out = [];
		foreach (['name', 'email', 'uri'] as $k) {
			if (isset($person[$k])) {
				$out[$k] = $person[$k];
			}
		}
		foreach ($person as $k => $v) {
			if (!isset($out[$k])) {
				$out[$k] = $v;
			}
		}

		return $out;
	}

	/**
	 * Atom text constructs (`title`, `summary`, `content`, `rights`,
	 * `subtitle`). Type defaults to `text`; `html` triggers CDATA wrapping.
	 *
	 * A plain string becomes `type="text"`. An array with `@type` and `@`
	 * (Xml::fromArray's attribute+content shape) is used verbatim.
	 *
	 * @param mixed $text
	 * @return array<string, mixed>|string
	 */
	protected function _prepareText(mixed $text): array|string {
		if (is_string($text)) {
			return $text;
		}
		if (!is_array($text)) {
			return (string)$text;
		}

		$type = $text['@type'] ?? null;
		if ($type === 'html' && isset($text['@']) && is_string($text['@']) && $text['@'] !== '') {
			$text['@'] = $this->_newCdata($text['@']);
		}

		return $text;
	}

	/**
	 * Categories accept a bare string (term only), a single `@term` attribute
	 * array, or a list of either.
	 *
	 * @param mixed $cats
	 * @return array<int, array<string, mixed>>
	 */
	protected function _prepareCategories(mixed $cats): array {
		if (is_string($cats)) {
			return [['@term' => $cats]];
		}
		if (is_array($cats) && isset($cats['@term'])) {
			return [$cats];
		}
		if (is_array($cats) && array_is_list($cats)) {
			return array_map(
				fn ($c) => is_string($c) ? ['@term' => $c] : $c,
				$cats,
			);
		}

		return [$cats];
	}

	/**
	 * Mark a `prefix:tag` key as having used a namespace so we emit its
	 * `xmlns:prefix` decl at the root. Mirrors RssView's tracking.
	 *
	 * @param string $key
	 * @return void
	 */
	protected function _trackNamespace(string $key): void {
		if (!str_contains($key, ':')) {
			return;
		}
		$prefix = explode(':', $key, 2)[0];
		if (str_starts_with($prefix, '@')) {
			$prefix = substr($prefix, 1);
		}
		if (!in_array($prefix, $this->_usedNamespaces, true)) {
			$this->_usedNamespaces[] = $prefix;
		}
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function _newCdata(string $content): string {
		$i = count($this->_cdata);
		$this->_cdata[$i] = $content;

		return '###CDATA-' . $i . '###';
	}

	/**
	 * @param string $content
	 * @return string
	 */
	protected function _replaceCdata(string $content): string {
		foreach ($this->_cdata as $n => $data) {
			$data = '<![CDATA[' . $data . ']]>';
			$content = str_replace('###CDATA-' . $n . '###', $data, $content);
		}

		return $content;
	}

}
