<?php
/**
 * PHP 5
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @author        Mark Scherer
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Feed\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Feed\View\RssView;

/**
 * RssViewTest
 */
class RssViewTest extends TestCase {

	public $Rss;

	public $baseUrl;

	/**
	 * RssViewTest::setUp()
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->Rss = new RssView();

		$this->baseUrl = trim(Router::url('/', true), '/');
	}

	/**
	 * TestTime method
	 *
	 * @return void
	 */
	public function testTime() {
		$now = time();
		$time = $this->Rss->time($now);
		$this->assertEquals(date('r', $now), $time);
	}

	/**
	 * RssViewTest::testSerialize()
	 *
	 * @return void
	 */
	public function testSerialize() {
		$Request = new Request();
		$Response = new Response();
		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'description' => 'Channel description'
			],
			'items' => [
				['title' => 'Title One', 'link' => 'http://example.org/one',
					'author' => 'one@example.org', 'description' => 'Content one',
					'source' => ['url' => 'http://foo.bar']],
				['title' => 'Title Two', 'link' => 'http://example.org/two',
					'author' => 'two@example.org', 'description' => 'Content two',
					'source' => ['url' => 'http://foo.bar', 'content' => 'Foo bar']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description>Channel description</description>
    <item>
      <title>Title One</title>
      <link>http://example.org/one</link>
      <author>one@example.org</author>
      <description>Content one</description>
      <source url="http://foo.bar">http://foo.bar</source>
    </item>
    <item>
      <title>Title Two</title>
      <link>http://example.org/two</link>
      <author>two@example.org</author>
      <description>Content two</description>
      <source url="http://foo.bar">Foo bar</source>
    </item>
  </channel>
</rss>

RSS;
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerialize()
	 *
	 * @return void
	 */
	public function testSerializeWithPrefixes() {
		$Request = new Request();
		$Response = new Response();

		$time = time();
		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'description' => 'Channel description',
				'sy:updatePeriod' => 'hourly',
				'sy:updateFrequency' => 1
			],
			'items' => [
				['title' => 'Title One', 'link' => 'http://example.org/one',
					'dc:creator' => 'Author One', 'pubDate' => $time],
				['title' => 'Title Two', 'link' => 'http://example.org/two',
					'dc:creator' => 'Author Two', 'pubDate' => $time,
					'source' => 'http://foo.bar'],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$time = date('r', $time);
		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description>Channel description</description>
    <sy:updatePeriod>hourly</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>
    <item>
      <title>Title One</title>
      <link>http://example.org/one</link>
      <dc:creator>Author One</dc:creator>
      <pubDate>$time</pubDate>
    </item>
    <item>
      <title>Title Two</title>
      <link>http://example.org/two</link>
      <dc:creator>Author Two</dc:creator>
      <pubDate>$time</pubDate>
      <source url="http://foo.bar">http://foo.bar</source>
    </item>
  </channel>
</rss>

RSS;
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithUnconfiguredPrefix()
	 *
	 * @expectedException RuntimeException
	 * @return void
	 */
	public function testSerializeWithUnconfiguredPrefix() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'foo:bar' => 'something',
			],
			'items' => [
				['title' => 'Title Two'],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);
	}

	/**
	 * RssViewTest::testSerializeWithArrayLinks()
	 *
	 * `'atom:link' => array('@href' => array(...)` becomes
	 * '@rel' => 'self', '@type' => 'application/rss+xml' automatically set for atom:link
	 *
	 * @return void
	 */
	public function testSerializeWithArrayLinks() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'atom:link' => ['@href' => ['controller' => 'foo', 'action' => 'bar']],
				'description' => 'Channel description',
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content one'],
				['title' => 'Title Two', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content two'],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <atom:link href="$this->baseUrl/foo/bar" rel="self" type="application/rss+xml"/>
    <description>Channel description</description>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
    </item>
    <item>
      <title>Title Two</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content two</description>
    </item>
  </channel>
</rss>

RSS;
		//debug($result);
		$this->assertSame('application/rss+xml', $Response->type());
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithContent()
	 *
	 * @return void
	 */
	public function testSerializeWithContent() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'guid' => ['url' => 'http://channel.example.org', '@isPermaLink' => 'true'],
				'atom:link' => ['@href' => ['controller' => 'foo', 'action' => 'bar']],
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content one',
					'content:encoded' => 'HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> one'],
				['title' => 'Title Two', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content two',
					'content:encoded' => 'HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> two'],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <guid isPermaLink="true">http://channel.example.org</guid>
    <atom:link href="$this->baseUrl/foo/bar" rel="self" type="application/rss+xml"/>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
      <content:encoded><![CDATA[HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> one]]></content:encoded>
    </item>
    <item>
      <title>Title Two</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content two</description>
      <content:encoded><![CDATA[HTML <img src="http://domain.com/some/link/to/image.jpg"/> <b>content</b> two]]></content:encoded>
    </item>
  </channel>
</rss>

RSS;
		//debug($output);
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithCustomNamespace()
	 *
	 * @return void
	 */
	public function testSerializeWithCustomNamespace() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'document' => [
				'namespace' => [
					'admin' => 'http://webns.net/mvcb/',
					'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#'
				]
			],
			'channel' => [
				'title' => 'Channel title',
				'admin:errorReportsTo' => ['@rdf:resource' => 'mailto:me@example.com']
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" version="2.0">
  <channel>
    <title>Channel title</title>
    <admin:errorReportsTo rdf:resource="mailto:me@example.com"/>
    <link>$this->baseUrl/</link>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
    </item>
  </channel>
</rss>

RSS;
		//debug($result);
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithImage()
	 *
	 * @return void
	 */
	public function testSerializeWithImage() {
		$Request = new Request();
		$Response = new Response();

		$url = ['controller' => 'topics', 'action' => 'feed', '_ext' => 'rss'];
		$data = [
			'channel' => [
				'title' => 'Channel title',
				'guid' => ['url' => $url, '@isPermaLink' => 'true'],
				'image' => [
					'url' => '/img/logo_rss.png',
					'link' => '/'
				]
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <guid isPermaLink="true">$this->baseUrl/topics/feed.rss</guid>
    <image>
      <url>$this->baseUrl/img/logo_rss.png</url>
      <link>$this->baseUrl/</link>
      <title>Channel title</title>
    </image>
    <link>$this->baseUrl/</link>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithCategories()
	 *
	 * @return void
	 */
	public function testSerializeWithCategories() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
				'category' => 'IT/Internet/Web development & more',
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content one',
					'category' => 'Internet'],
				['title' => 'Title Two', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content two',
					'category' => ['News', 'Tutorial'],
					'comments' => ['controller' => 'foo', 'action' => 'bar', '_ext' => 'rss']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <category>IT/Internet/Web development &amp; more</category>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
      <category>Internet</category>
    </item>
    <item>
      <title>Title Two</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content two</description>
      <category>News</category>
      <category>Tutorial</category>
      <comments>$this->baseUrl/foo/bar.rss</comments>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithEnclosure()
	 *
	 * @return void
	 */
	public function testSerializeWithEnclosure() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content one',
					'enclosure' => ['url' => 'http://www.example.com/media/3d.wmv', 'length' => 78645, 'type' => 'video/wmv']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
      <enclosure url="http://www.example.com/media/3d.wmv" length="78645" type="video/wmv"/>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithCustomTags()
	 *
	 * @return void
	 */
	public function testSerializeWithCustomTags() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
				'link' => 'http://channel.example.org',
			],
			'items' => [
				['title' => 'Title One', 'link' => ['controller' => 'foo', 'action' => 'bar'], 'description' => 'Content one',
					'foo' => ['@url' => 'http://www.example.com/media/3d.wmv', '@length' => 78645, '@type' => 'video/wmv']],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>http://channel.example.org</link>
    <description/>
    <item>
      <title>Title One</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>Content one</description>
      <foo url="http://www.example.com/media/3d.wmv" length="78645" type="video/wmv"/>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * RssViewTest::testSerializeWithSpecialChars()
	 *
	 * @return void
	 */
	public function testSerializeWithSpecialChars() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title with äöü umlauts and <!> special chars',
				'link' => 'http://channel.example.org',
			],
			'items' => [
				[
					'title' => 'A <unsafe title',
					'link' => ['controller' => 'foo', 'action' => 'bar'],
					'description' => 'My content "&" and <other> stuff here should also be escaped safely'],
			]
		];
		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title with äöü umlauts and &lt;!&gt; special chars</title>
    <link>http://channel.example.org</link>
    <description/>
    <item>
      <title>A &lt;unsafe title</title>
      <link>$this->baseUrl/foo/bar</link>
      <description>My content "&amp;" and &lt;other&gt; stuff here should also be escaped safely</description>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testMedia() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'document' => [
				'namespace' => [
					'media' => 'http://search.yahoo.com/mrss/'
				]
			],
			'channel' => [
				'title' => 'Channel title',
			],
			'items' => [
				[
					'media:restriction' => ['@type' => 'sharing', '@relationship' => 'deny'],
					'media:content' => [
						'@url' => 'http://some/img.ext',
						'@isPermaLink' => 'true',
						'@type' => 'video/quicktime'
					],
					'media:group' => [
						'media:content' => [
							'@url' => 'http://some/other-img.ext',
							'@isPermaLink' => 'true',
							'@type' => 'video/quicktime',
							'@fileSize' => 999
						],
					]
				],
			]
		];

		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:media="http://search.yahoo.com/mrss/" version="2.0">
  <channel>
    <title>Channel title</title>
    <link>/</link>
    <description/>
    <item>
      <media:restriction type="sharing" relationship="deny"/>
      <media:content url="http://some/img.ext" isPermaLink="true" type="video/quicktime"/>
      <media:group>
        <media:content url="http://some/other-img.ext" isPermaLink="true" type="video/quicktime" fileSize="999"/>
      </media:group>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

	public function testIsPermalink() {
		$Request = new Request();
		$Response = new Response();

		$data = [
			'channel' => [
				'title' => 'Channel title',
			],
			'items' => [
				[
					'guid' => ['url' => 'Testing', '@isPermalink' => 'false'],
				],
				[
					'guid' => ['url' => 'Testing', '@isPermalink' => 'true'],
				],
				[
					'guid' => ['url' => 'Testing'],
				],
			]
		];

		$viewVars = ['channel' => $data, '_serialize' => 'channel'];
		$View = new RssView($Request, $Response, null, ['viewVars' => $viewVars]);
		$result = $View->render(false);

		$expected = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Channel title</title>
    <link>/</link>
    <description/>
    <item>
      <guid isPermalink="false">Testing</guid>
    </item>
    <item>
      <guid isPermalink="true">/Testing</guid>
    </item>
    <item>
      <guid>/Testing</guid>
    </item>
  </channel>
</rss>

RSS;
		$this->assertTextEquals($expected, $result);
	}

}
