<?php

namespace Feed\View;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Cake\Utility\Xml;
use Cake\View\View;

/**
 * A view class that is used for creating RSS feeds.
 *
 * By setting the '_serialize' key in your controller, you can specify a view variable
 * that should be serialized to XML and used as the response for the request.
 * This allows you to omit views + layouts, if your just need to emit a single view
 * variable as the XML response.
 *
 * In your controller, you could do the following:
 *
 * `$this->set(array('posts' => $posts, '_serialize' => 'posts'));`
 *
 * When the view is rendered, the `$posts` view variable will be serialized
 * into the RSS XML.
 *
 * **Note** The view variable you specify must be compatible with Xml::fromArray().
 *
 * If you don't use the `_serialize` key, you will need a view. You can use extended
 * views to provide layout like functionality. This is currently not yet tested/supported.
 *
 * Usage see docs.
 *
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @link http://www.dereuromark.de/2013/10/03/rss-feeds-in-cakephp
 */
class RssView extends View {

	/**
	 * Default spec version of generated RSS.
	 *
	 * @var string
	 */
	public $version = '2.0';

	/**
	 * The subdirectory. RSS views are always in rss. Currently not in use.
	 *
	 * @var string
	 */
	public $subDir = 'rss';

	/**
	 * Holds usable namespaces.
	 *
	 * @var array
	 * @link http://validator.w3.org/feed/docs/howto/declare_namespaces.html
	 */
	protected $_namespaces = [
		'atom' => 'http://www.w3.org/2005/Atom',
		'content' => 'http://purl.org/rss/1.0/modules/content/',
		'dc' => 'http://purl.org/dc/elements/1.1/',
		'sy' => 'http://purl.org/rss/1.0/modules/syndication/'
	];

	/**
	 * Holds the namespace keys in use.
	 *
	 * @var array
	 */
	protected $_usedNamespaces = [];

	/**
	 * Holds CDATA placeholders.
	 *
	 * @var array
	 */
	protected $_cdata = [];

	/**
	 * Constructor
	 *
	 * @param \Cake\Network\Request $request
	 * @param \Cake\Network\Response $response
	 * @param \Cake\Event\EventManager $eventManager
	 * @param array $viewOptions
	 */
	public function __construct(Request $request = null, Response $response = null,
		EventManager $eventManager = null, array $viewOptions = []) {
		parent::__construct($request, $response, $eventManager, $viewOptions);

		if ($response && $response instanceof Response) {
			$response->type('rss');
		}
	}

	/**
	 * If you are using namespaces that are not yet known to the class, you need to globablly
	 * add them with this method. Namespaces will only be added for actually used prefixes.
	 *
	 * @param string $prefix
	 * @param string $url
	 * @return void
	 */
	public function setNamespace($prefix, $url) {
		$this->_namespaces[$prefix] = $url;
	}

	/**
	 * Prepares the channel and sets default values.
	 *
	 * @param array $channel
	 * @return array Channel
	 */
	public function channel($channel) {
		if (!isset($channel['link'])) {
			$channel['link'] = '/';
		}
		if (!isset($channel['title'])) {
			$channel['title'] = '';
		}
		if (!isset($channel['description'])) {
			$channel['description'] = '';
		}

		$channel = $this->_prepareOutput($channel);
		return $channel;
	}

	/**
	 * Converts a time in any format to an RSS time
	 *
	 * @param int|string|\DateTime $time
	 * @return string An RSS-formatted timestamp
	 * @see \Cake\I18n\Time::toRssString()
	 */
	public function time($time) {
		$time = new Time($time);
		return $time->toRssString();
	}

	/**
	 * Skip loading helpers if this is a _serialize based view.
	 *
	 * @return void
	 */
	public function loadHelpers() {
		if (isset($this->viewVars['_serialize'])) {
			return;
		}
		parent::loadHelpers();
	}

	/**
	 * Render a RSS view.
	 *
	 * Uses the special '_serialize' parameter to convert a set of
	 * view variables into a XML response. Makes generating simple
	 * XML responses very easy. You can omit the '_serialize' parameter,
	 * and use a normal view + layout as well.
	 *
	 * @param string|null $view The view being rendered.
	 * @param string|null $layout The layout being rendered.
	 * @return string The rendered view.
	 */
	public function render($view = null, $layout = null) {
		if (isset($this->viewVars['_serialize'])) {
			return $this->_serialize($this->viewVars['_serialize']);
		}
		if ($view !== false && $this->_getViewFileName($view)) {
			return parent::render($view, false);
		}
	}

	/**
	 * Serialize view vars.
	 *
	 * @param string|array $serialize The viewVars that need to be serialized.
	 * @return string The serialized data
	 * @throws \RuntimeException When the prefix is not specified
	 */
	protected function _serialize($serialize) {
		$rootNode = isset($this->viewVars['_rootNode']) ? $this->viewVars['_rootNode'] : 'channel';

		if (is_array($serialize)) {
			$data = [$rootNode => []];
			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}
				$data[$rootNode][$alias] = $this->viewVars[$key];
			}
		} else {
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
			if (is_array($data) && Hash::numeric(array_keys($data))) {
				$data = [$rootNode => [$serialize => $data]];
			}
		}

		$defaults = ['document' => [], 'channel' => [], 'items' => []];
		$data += $defaults;
		if (!empty($data['document']['namespace'])) {
			foreach ($data['document']['namespace'] as $prefix => $url) {
				$this->setNamespace($prefix, $url);
			}
		}

		$channel = $this->channel($data['channel']);
		if (!empty($channel['image']) && empty($channel['image']['title'])) {
			$channel['image']['title'] = $channel['title'];
		}

		foreach ($data['items'] as $item) {
			$channel['item'][] = $this->_prepareOutput($item);
		}

		$array = [
			'rss' => [
				'@version' => $this->version,
				'channel' => $channel,
			]
		];
		$namespaces = [];
		foreach ($this->_usedNamespaces as $usedNamespacePrefix) {
			if (!isset($this->_namespaces[$usedNamespacePrefix])) {
				throw new \RuntimeException(sprintf('The prefix %s is not specified.', $usedNamespacePrefix));
			}
			$namespaces['xmlns:' . $usedNamespacePrefix] = $this->_namespaces[$usedNamespacePrefix];
		}
		$array['rss'] += $namespaces;

		$options = [];
		if (Configure::read('debug')) {
			$options['pretty'] = true;
		}

		$output = Xml::fromArray($array, $options)->asXML();
		$output = $this->_replaceCdata($output);

		return $output;
	}

	/**
	 * RssView::_prepareOutput()
	 *
	 * @param array $item
	 * @return array
	 */
	protected function _prepareOutput($item) {
		foreach ($item as $key => $val) {
			$prefix = null;
			// The cast prevents a PHP bug for switch case and false positives with integers
			$bareKey = (string)$key;

			// Detect namespaces
			if (strpos($key, ':') !== false) {
				list($prefix, $bareKey) = explode(':', $key, 2);
				if (strpos($prefix, '@') !== false) {
					$prefix = substr($prefix, 1);
				}
				if (!in_array($prefix, $this->_usedNamespaces)) {
					$this->_usedNamespaces[] = $prefix;
				}
			}

			$attrib = null;
			switch ($bareKey) {
				case 'encoded':
					$val = $this->_newCdata($val);
					break;

				case 'pubDate':
					$val = $this->time($val);
					break;

				case 'category':
					if (is_array($val) && isset($val['domain'])) {
						$attrib['@domain'] = $val['domain'];
						$attrib['@'] = isset($val['content']) ? $val['content'] : $attrib['@domain'];
						$val = $attrib;
					} elseif (is_array($val) && !empty($val[0])) {
						$categories = [];
						foreach ($val as $category) {
							$attrib = [];
							if (is_array($category) && isset($category['domain'])) {
								$attrib['@domain'] = $category['domain'];
								$attrib['@'] = isset($val['content']) ? $val['content'] : $attrib['@domain'];
								$category = $attrib;
							}
							$categories[] = $category;
						}
						$val = $categories;
					}
					break;

				case 'link':
				case 'url':
				case 'guid':
				case 'comments':
					if (is_array($val) && isset($val['@href'])) {
						$attrib = $val;
						$attrib['@href'] = Router::url($val['@href'], true);
						if ($prefix === 'atom') {
							$attrib['@rel'] = 'self';
							$attrib['@type'] = 'application/rss+xml';
						}
						$val = $attrib;
					} elseif (is_array($val) && isset($val['url'])) {
						if (!isset($val['@isPermalink']) || $val['@isPermalink'] !== 'false') {
							$val['url'] = Router::url($val['url'], true);
						}
						if ($bareKey === 'guid') {
							$val['@'] = $val['url'];
							unset($val['url']);
						}
					} else {
						$val = Router::url($val, true);
					}
					break;

				case 'source':
					if (is_array($val) && isset($val['url'])) {
						$attrib['@url'] = Router::url($val['url'], true);
						$attrib['@'] = isset($val['content']) ? $val['content'] : $attrib['@url'];
					} elseif (!is_array($val)) {
						$attrib['@url'] = Router::url($val, true);
						$attrib['@'] = $attrib['@url'];
					}
					$val = $attrib;
					break;

				case 'enclosure':
					if (isset($val['url']) && is_string($val['url']) && is_file(WWW_ROOT . $val['url']) && file_exists(WWW_ROOT . $val['url'])) {
						if (!isset($val['length']) && strpos($val['url'], '://') === false) {
							$val['length'] = sprintf('%u', filesize(WWW_ROOT . $val['url']));
						}
						if (!isset($val['type']) && function_exists('mime_content_type')) {
							$val['type'] = mime_content_type(WWW_ROOT . $val['url']);
						}
					}
					$attrib['@url'] = Router::url($val['url'], true);
					$attrib['@length'] = $val['length'];
					$attrib['@type'] = $val['type'];
					$val = $attrib;
					break;

				default:
					//nothing
			}

			if (is_array($val)) {
				$val = $this->_prepareOutput($val);
			}

			$item[$key] = $val;
		}

		return $item;
	}

	/**
	 * RssView::_newCdata()
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _newCdata($content) {
		$i = count($this->_cdata);
		$this->_cdata[$i] = $content;
		return '###CDATA-' . $i . '###';
	}

	/**
	 * RssView::_replaceCdata()
	 *
	 * @param string $content
	 * @return string
	 */
	protected function _replaceCdata($content) {
		foreach ($this->_cdata as $n => $data) {
			$data = '<![CDATA[' . $data . ']]>';
			$content = str_replace('###CDATA-' . $n . '###', $data, $content);
		}
		return $content;
	}

}
