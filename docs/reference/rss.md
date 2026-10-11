# RSS

`Feed.Rss` serializes `channel` metadata and an `items` list. The [RSS 2.0 specification](https://www.rssboard.org/rss-specification) requires channel title, link, and description. Supply meaningful values: the view's empty defaults do not make an incomplete channel valid.

An item needs a title or description. Use a stable `guid`, a canonical link, and a publication date when available. `pubDate` accepts `DateTimeInterface`, integer Unix timestamps, and parseable date strings, and renders in RSS date format. Format other date fields such as `lastBuildDate` yourself with `RssView::time()` before passing them into the feed.

## Fields and shorthands

- `link`, `comments`, and image `url` accept absolute strings, relative paths, or CakePHP URL arrays.
- `guid` accepts a URL or `['url' => $id, '@isPermalink' => 'false']` for an opaque identifier.
- `category` accepts a string, a list, or `['domain' => $url, 'content' => $name]`.
- `source` accepts `['url' => $url, 'content' => $name]`.
- `author` is an RSS email value. Use `dc:creator` for a display name.
- `content:encoded` contains CDATA-wrapped content, including HTML.

Ordinary values are XML-escaped. CDATA preserves markup, so sanitize HTML from untrusted sources before publishing it. XML escaping alone does not make embedded HTML safe in readers.

## Namespaces

Built-in prefixes are `atom`, `content`, `dc`, and `sy`. Used prefixes are declared on the root. Register additional prefixes in the input:

```php
$feed['document']['namespace'] = [
    'itunes' => 'http://www.itunes.com/dtds/podcast-1.0.dtd',
];
$feed['channel']['itunes:author'] = 'Example editor';
```

You can also call `setNamespace($prefix, $uri)` on the view. Unknown prefixes raise `RuntimeException`.

Add a feed self-link with:

```php
$feed['channel']['atom:link'] = ['@href' => 'https://example.org/feeds.rss'];
```

RSS Atom links default to `rel="self"` and `type="application/rss+xml"`; explicitly supplied attributes are preserved.

## Enclosures

```php
$feed['items'][0]['enclosure'] = [
    'url' => 'https://example.org/audio/episode.mp3',
    'length' => '12345',
    'type' => 'audio/mpeg',
];
```

RSS requires URL, byte length, and MIME type. Supply both metadata values for remote media. For relative files inside `WWW_ROOT`, the view can infer missing values from the file and memoizes its probe. Unresolved files leave missing metadata empty, so check the emitted enclosure before publication. The view does not fetch remote files.

For podcasts, RSS output and an iTunes namespace declaration are only part of the publisher requirements. Configure and validate the additional metadata required by your distribution service.
