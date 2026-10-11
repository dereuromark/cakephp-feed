# Getting started

Complete [Installation](./installation) first. This example publishes a small RSS feed without a database or template.

## Add an action

Create `src/Controller/FeedsController.php`:

```php
<?php
namespace App\Controller;

class FeedsController extends AppController {
    public function index(): void {
        $feed = [
            'channel' => [
                'title' => 'Example news',
                'link' => 'https://example.org/',
                'description' => 'Updates from Example',
            ],
            'items' => [
                [
                    'title' => 'First update',
                    'link' => 'https://example.org/posts/1',
                    'guid' => 'https://example.org/posts/1',
                    'description' => 'Our first update.',
                    'pubDate' => '2026-05-11T12:00:00Z',
                ],
            ],
        ];
        $this->set(compact('feed'));
        $this->viewBuilder()
            ->setClassName('Feed.Rss')
            ->setOption('serialize', 'feed');
    }
}
```

Replace the sample titles, links, and dates with your own published data. With the application's normal fallback routes, open `/feeds/index.rss`. If you use explicit routes, connect that path as described in [Routing](./routing).

The response should contain an XML `rss` root and use `application/rss+xml`. No `templates/Feeds/index.php` or RSS layout is needed when `serialize` is set.

## Make the URL public deliberately

The controller inherits your application's authentication and authorization rules. Allow public access only if the feed is intended to be public. Filter out drafts and records the requesting user may not read. A different response format does not change access control.

## Use Atom instead

For Atom, select `Feed.Atom` and provide its feed structure:

```php
$feed = [
    'id' => 'https://example.org/feeds/index.atom',
    'title' => 'Example news',
    'updated' => '2026-05-11T12:00:00Z',
    'author' => 'Example editor',
    'link' => 'https://example.org/',
    'entries' => [
        [
            'id' => 'https://example.org/posts/1',
            'title' => 'First update',
            'updated' => '2026-05-11T12:00:00Z',
            'link' => 'https://example.org/posts/1',
            'summary' => 'Our first update.',
        ],
    ],
];
$this->set(compact('feed'));
$this->viewBuilder()->setClassName('Feed.Atom')->setOption('serialize', 'feed');
```

The response uses `application/atom+xml`. Keep identifiers stable and use actual modification dates. RSS and Atom have different input structures; changing the view class alone does not convert an RSS array into Atom.

For production output, check the complete feed against its format specification and a feed validator. The serializers handle XML output but do not enforce every RSS or Atom publishing rule.
