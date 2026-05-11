<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Feed\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Exception\SerializationFailureException;
use DateTimeImmutable;
use Feed\View\AtomView;
use RuntimeException;

class AtomViewTest extends TestCase {

	protected AtomView $Atom;

	protected string $baseUrl;

	protected bool $debugWas;

	public function setUp(): void {
		parent::setUp();

		// Disable debug so Xml::fromArray doesn't pretty-print — the assertions
		// below compare flat strings. Pretty-printing only affects whitespace,
		// not semantics; toggling it for the test keeps the assertions tight.
		$this->debugWas = (bool)Configure::read('debug');
		Configure::write('debug', false);

		$this->Atom = new AtomView();
		$this->baseUrl = trim(Router::url('/', true), '/');

		Router::reload();
		$builder = Router::createRouteBuilder('/');
		$builder->fallbacks(DashedRoute::class);
		$builder->connect('/{controller}/{action}/*');
	}

	public function tearDown(): void {
		Configure::write('debug', $this->debugWas);
		parent::tearDown();
	}

	/**
	 * RFC 3339 dates: same format whether the input is DateTimeImmutable,
	 * an int unix timestamp, or a string the constructor can parse. Outputs
	 * are always in the form `Y-m-d\TH:i:sP`.
	 */
	public function testTimeAcceptsHeterogeneousInputs(): void {
		$expected = '2026-05-11T12:34:56+00:00';

		$dt = new DateTimeImmutable($expected);
		$this->assertSame($expected, $this->Atom->time($dt));

		// Int input is a unix timestamp at UTC.
		$ts = $dt->getTimestamp();
		$this->assertSame($expected, $this->Atom->time($ts));

		// String input parsed by Cake's DateTime. Timezone-aware string round-trips.
		$this->assertSame($expected, $this->Atom->time($expected));
	}

	/**
	 * Minimal feed: only the three RFC-required fields, no entries. Verifies
	 * the default Atom namespace binds correctly so any reader can parse it.
	 */
	public function testMinimalFeedShape(): void {
		$View = $this->buildView([
			'feed' => [
				'id' => 'urn:uuid:minimal',
				'title' => 'Minimal',
				'updated' => '2026-05-11T00:00:00Z',
			],
		]);

		$out = $View->render('');

		$this->assertStringContainsString('<?xml version="1.0"', $out);
		$this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom"', $out);
		$this->assertStringContainsString('<id>urn:uuid:minimal</id>', $out);
		$this->assertStringContainsString('<title>Minimal</title>', $out);
		$this->assertStringContainsString('<updated>2026-05-11T00:00:00+00:00</updated>', $out);
		$this->assertStringNotContainsString('<entry', $out);
	}

	/**
	 * Atom requires `id`, `title`, and `updated` at the feed level. Rendering
	 * a partial feed would otherwise silently emit invalid XML.
	 */
	public function testMissingRequiredFeedFieldsThrow(): void {
		$View = $this->buildView([
			'feed' => [
				'title' => 'Missing required fields',
			],
		]);

		$caught = null;
		try {
			$View->render('');
		} catch (SerializationFailureException $e) {
			$caught = $e->getPrevious();
		}

		$this->assertInstanceOf(SerializationFailureException::class, $caught);
		$this->assertStringContainsString('Atom feed is missing required field(s): id, updated', $caught->getMessage());
	}

	/**
	 * String shorthand for `link` becomes `<link rel="alternate" href="..."/>`
	 * — that's the Atom convention. Caller-supplied `@rel` survives.
	 */
	public function testLinkShorthandAndMultipleLinksPerEntry(): void {
		$View = $this->buildView([
			'feed' => [
				'id' => 'urn:test',
				'title' => 'Links',
				'updated' => '2026-05-11T00:00:00Z',
				'link' => [
					['@href' => 'http://example.org/', '@rel' => 'alternate'],
					['@href' => 'http://example.org/feed.atom', '@rel' => 'self', '@type' => 'application/atom+xml'],
				],
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'link' => 'http://example.org/posts/1',
					],
				],
			],
		]);

		$out = $View->render('');

		$this->assertStringContainsString('href="http://example.org/" rel="alternate"', $out);
		$this->assertStringContainsString('href="http://example.org/feed.atom" rel="self"', $out);
		$this->assertStringContainsString('type="application/atom+xml"', $out);
		$this->assertStringContainsString('href="http://example.org/posts/1" rel="alternate"', $out);
	}

	/**
	 * String shorthand for `author` produces a structured person with just
	 * `<name>` populated.
	 */
	public function testAuthorShorthandAndStructuredEqual(): void {
		$shorthandOut = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'author' => 'Jane Doe',
			],
		])->render('');

		$structuredOut = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'author' => ['name' => 'Jane Doe'],
			],
		])->render('');

		$this->assertStringContainsString('<author><name>Jane Doe</name></author>', $shorthandOut);
		$this->assertSame($shorthandOut, $structuredOut);
	}

	/**
	 * `author` accepts a list of person constructs and emits one `<author>`
	 * per element. RSS lacks this — it's an Atom advantage.
	 */
	public function testMultipleAuthorsAndContributors(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'author' => [
					['name' => 'Alice', 'email' => 'alice@example.org'],
					['name' => 'Bob', 'uri' => 'http://example.org/~bob'],
				],
				'contributor' => [
					['name' => 'Carol'],
				],
			],
		])->render('');

		$this->assertSame(2, substr_count($out, '<author>'));
		$this->assertStringContainsString('<name>Alice</name>', $out);
		$this->assertStringContainsString('<email>alice@example.org</email>', $out);
		$this->assertStringContainsString('<name>Bob</name>', $out);
		$this->assertStringContainsString('<uri>http://example.org/~bob</uri>', $out);
		$this->assertStringContainsString('<contributor><name>Carol</name></contributor>', $out);
	}

	/**
	 * HTML-typed content is CDATA-wrapped so reader markup survives. The
	 * placeholder-then-replace mechanism guarantees Xml::fromArray doesn't
	 * double-escape it.
	 */
	public function testHtmlContentIsCdataWrapped(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'content' => ['@type' => 'html', '@' => '<p>Hello <em>world</em></p>'],
					],
				],
			],
		])->render('');

		$this->assertStringContainsString('<content type="html"><![CDATA[<p>Hello <em>world</em></p>]]></content>', $out);
	}

	/**
	 * XHTML text constructs need nested namespaced markup, which this
	 * serializer does not currently support. Fail loudly instead of emitting
	 * invalid escaped text while claiming XHTML support.
	 */
	public function testXhtmlContentThrows(): void {
		$View = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'content' => [
							'@type' => 'xhtml',
							'@' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>X</p></div>',
						],
					],
				],
			],
		]);

		$caught = null;
		try {
			$View->render('');
		} catch (SerializationFailureException $e) {
			$caught = $e->getPrevious();
		}

		$this->assertInstanceOf(SerializationFailureException::class, $caught);
		$this->assertStringContainsString('Atom XHTML text constructs are not supported.', $caught->getMessage());
	}

	/**
	 * HTML content containing a literal `]]>` sequence must not break CDATA
	 * wrapping. The canonical workaround is to split the sequence as
	 * `]]]]><![CDATA[>`, which round-trips to the original `]]>` on parse but
	 * never closes the outer CDATA section prematurely. Without the split the
	 * emitted XML would be malformed and any reader would reject the feed.
	 */
	public function testHtmlContentSplitsLiteralCdataTerminator(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'content' => [
							'@type' => 'html',
							'@' => 'before]]>after',
						],
					],
				],
			],
		])->render('');

		// The exact escape sequence per the CDATA-injection workaround.
		$this->assertStringContainsString('<content type="html"><![CDATA[before]]]]><![CDATA[>after]]></content>', $out);

		// And — more importantly — the result must still round-trip through a
		// real XML parser. simplexml lets us verify both well-formedness and
		// that the inner text reads back as the original payload.
		$doc = simplexml_load_string($out);
		$this->assertNotFalse($doc, 'feed must remain well-formed XML after the CDATA split');
		$content = (string)$doc->entry->content;
		$this->assertSame('before]]>after', $content);
	}

	/**
	 * Cake URL arrays (e.g. `['controller' => 'Posts', 'action' => 'view', 1]`)
	 * are routed through `Router::url(..., true)` just like in RssView,
	 * instead of falling through and serializing as nested XML children of
	 * `<link>`. This is the shape most app code already uses with RssView.
	 */
	public function testLinkAcceptsCakeUrlArrayShorthand(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'link' => [
					'controller' => 'Posts',
					'action' => 'feed',
					'_ext' => 'atom',
				],
			],
		])->render('');

		$this->assertStringContainsString('href="' . $this->baseUrl . '/posts/feed.atom"', $out);
		$this->assertStringContainsString('rel="alternate"', $out);
		$this->assertStringNotContainsString('<controller>', $out);
		$this->assertStringNotContainsString('<action>', $out);
	}

	/**
	 * Text-typed content (the default) does NOT get CDATA-wrapped — the XML
	 * encoder takes care of escaping. Avoids needless `<![CDATA[plain text]]>`.
	 */
	public function testTextContentIsNotCdataWrapped(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'summary' => 'A teaser with <special> chars',
					],
				],
			],
		])->render('');

		$this->assertStringNotContainsString('<![CDATA[', $out);
		$this->assertStringContainsString('A teaser with &lt;special&gt; chars', $out);
	}

	/**
	 * Category accepts bare string and list-of-strings shorthand alongside
	 * full attribute arrays.
	 */
	public function testCategoryShorthandAndFullForm(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'category' => [
							'tech',
							['@term' => 'php', '@scheme' => 'http://example.org/tags', '@label' => 'PHP'],
						],
					],
				],
			],
		])->render('');

		$this->assertStringContainsString('<category term="tech"', $out);
		$this->assertStringContainsString('<category term="php" scheme="http://example.org/tags" label="PHP"', $out);
	}

	/**
	 * `published` and `updated` are distinct fields. Atom forced this split
	 * precisely to fix RSS's `pubDate`-ambiguity.
	 */
	public function testEntryHasBothPublishedAndUpdated(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'published' => '2026-05-10T08:00:00Z',
						'updated' => '2026-05-11T16:00:00Z',
					],
				],
			],
		])->render('');

		$this->assertStringContainsString('<published>2026-05-10T08:00:00+00:00</published>', $out);
		$this->assertStringContainsString('<updated>2026-05-11T16:00:00+00:00</updated>', $out);
	}

	/**
	 * Entries have the same required field set as the feed minus feed-only
	 * metadata. Missing them should fail instead of producing invalid Atom.
	 */
	public function testMissingRequiredEntryFieldsThrow(): void {
		$View = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'title' => 'Entry without required fields',
					],
				],
			],
		]);

		$caught = null;
		try {
			$View->render('');
		} catch (SerializationFailureException $e) {
			$caught = $e->getPrevious();
		}

		$this->assertInstanceOf(SerializationFailureException::class, $caught);
		$this->assertStringContainsString('Atom entry #1 is missing required field(s): id, updated', $caught->getMessage());
	}

	/**
	 * Custom namespaced keys (e.g. dc:creator) make the matching xmlns decl
	 * appear at the root, but only that one — unused declarations stay out
	 * of the output for cleanliness.
	 */
	public function testCustomNamespaceIsEmittedOnlyWhenUsed(): void {
		$out = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					[
						'id' => 'urn:e:1',
						'title' => 'Entry',
						'updated' => '2026-05-11T00:00:00Z',
						'dc:creator' => 'Jane',
					],
				],
			],
		])->render('');

		$this->assertStringContainsString('xmlns:dc="http://purl.org/dc/elements/1.1/"', $out);
		$this->assertStringContainsString('<dc:creator>Jane</dc:creator>', $out);
		$this->assertStringNotContainsString('xmlns:itunes=', $out);
		$this->assertStringNotContainsString('xmlns:content=', $out);
	}

	/**
	 * Using a `prefix:tag` key whose prefix was never registered must blow
	 * up loudly. Silently emitting a broken namespace is worse than failing.
	 */
	public function testUnregisteredNamespaceThrows(): void {
		$View = $this->buildView([
			'feed' => [
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'entries' => [
					['id' => 'urn:e:1', 'title' => 'E', 'updated' => '2026-05-11T00:00:00Z', 'foo:bar' => 'x'],
				],
			],
		]);

		// SerializedView wraps inner exceptions in SerializationFailureException;
		// unwrap once to assert on the actual cause from our serializer.
		$caught = null;
		try {
			$View->render('');
		} catch (SerializationFailureException $e) {
			$caught = $e->getPrevious();
		}

		$this->assertInstanceOf(RuntimeException::class, $caught);
		$this->assertStringContainsString('foo', $caught->getMessage());
	}

	/**
	 * Caller-passed namespaces via the top-level `namespace` key get
	 * registered before render — same shape RssView uses, just plumbed
	 * here.
	 */
	public function testInputCanRegisterCustomNamespaces(): void {
		$out = $this->buildView([
			'feed' => [
				'namespace' => ['ex' => 'http://example.org/ext'],
				'id' => 'a',
				'title' => 't',
				'updated' => '2026-05-11T00:00:00Z',
				'ex:owner' => 'someone',
			],
		])->render('');

		$this->assertStringContainsString('xmlns:ex="http://example.org/ext"', $out);
		$this->assertStringContainsString('<ex:owner>someone</ex:owner>', $out);
	}

	/**
	 * Content type the view advertises matches the Atom MIME spec. Loosely
	 * coupled to the response only — Cake's TypeMap is the authority for the
	 * "atom" extension binding.
	 */
	public function testContentTypeIsAtomXml(): void {
		$this->assertSame('application/atom+xml', AtomView::contentType());
	}

	/**
	 * Instantiating the view registers `atom => application/atom+xml` on the
	 * Response TypeMap idempotently and applies `withType('atom')` to the
	 * passed response — so a freshly constructed view emits the right
	 * Content-Type header without any extra wiring on the caller side.
	 */
	public function testResponseGetsAtomContentType(): void {
		$Request = new ServerRequest();
		$Response = new Response();
		new AtomView($Request, $Response, null, ['viewVars' => []]);

		$this->assertNotNull($Response);
		$this->assertStringContainsString('application/atom+xml', $Response->getHeaderLine('Content-Type'));
	}

	/**
	 * @param array<string, mixed> $viewVars
	 */
	protected function buildView(array $viewVars): AtomView {
		$Request = new ServerRequest();
		$Response = new Response();
		$View = new AtomView($Request, $Response, null, ['viewVars' => $viewVars]);
		$View->setConfig(['serialize' => array_key_first($viewVars)]);

		return $View;
	}

}
