# Atom View

A CakePHP view class for generating Atom 1.0 feeds ([RFC 4287](https://datatracker.ietf.org/doc/html/rfc4287)).

- Template-less by default (same `serialize` flow as RssView)
- Friendly shorthands for the common case, full attribute control when you need it
- Mandatory fields enforced where the spec is strict (`id`, `title`, `updated`)
- Auto-emits only the namespace declarations actually used
- CDATA-wraps HTML content; leaves plain text un-CDATA'd for cleaner output

If you're new to Atom, the README has a [RSS vs Atom guidance section](../../README.md#rss-vs-atom-which-one) explaining when to pick which.

## Configs

`setNamespace()` registers a custom namespace prefix that can be used as a `prefix:tag` key in the input. The matching `xmlns:prefix="..."` decl is only emitted at the root if the prefix actually appears somewhere in the rendered tree.

The Atom default namespace (`http://www.w3.org/2005/Atom`) is bound automatically — you do not need to register it.

## Setup

Enable the Atom extension in your routes (or bootstrap):

``` php
Router::extensions(['atom']);
```

Activate the view class in your action:

``` php
$this->viewBuilder()->setClassName('Feed.Atom');
```

Or wire it once via view negotiation so every `.atom` request resolves to `AtomView`:

``` php
// AppController.php
public function viewClasses(): array {
    return [\Feed\View\AtomView::class];
}
```

Set the data and tell the builder which viewVar to serialize:

``` php
$this->set(['feed' => $feed]);
$this->viewBuilder()->setOption('serialize', 'feed');
```

## Input shape

All fields are optional unless noted. Bare strings and structured arrays are accepted interchangeably:

``` php
$feed = [
    'id'        => 'http://example.org/feed',   // REQUIRED — globally unique feed identifier (URI)
    'title'     => 'My blog',                    // REQUIRED
    'updated'   => '2026-05-11T12:00:00Z',       // REQUIRED — DateTime / DateTimeImmutable / int / string
    'subtitle'  => 'Notes from the field',
    'link'      => 'http://example.org/',        // shorthand: <link rel="alternate" href="..."/>
    'author'    => 'Jane Doe',                   // shorthand: <author><name>Jane Doe</name></author>
    'rights'    => 'Copyright 2026 Example, Inc.',
    'generator' => 'CakePHP Feed Plugin',
    'icon'      => '/favicon.ico',
    'logo'      => '/logo.png',
    'category'  => 'tech',
    'entries'   => [
        [
            'id'        => 'http://example.org/posts/1',  // REQUIRED — globally unique entry identifier
            'title'     => 'A post',                       // REQUIRED
            'updated'   => '2026-05-11',                   // REQUIRED
            'published' => '2026-05-10',                   // optional, distinct from `updated`
            'link'      => 'http://example.org/posts/1',
            'summary'   => 'A short teaser.',
            'content'   => ['@type' => 'html', '@' => '<p>Full HTML body.</p>'],
            'author'    => 'Jane',
            'category'  => 'tech',
        ],
    ],
];
```

## Field-by-field reference

### `id` — required (feed and per-entry)

Globally unique IRI. Atom readers use this to deduplicate, so it must be **stable across renders**. A common choice is the canonical permalink; for synthetic feeds a `urn:uuid:` is fine. The view does not generate one for you — pick one and stick with it.

### `title`, `subtitle`, `rights`, `summary`, `content` — text constructs

Atom distinguishes plain text, HTML, and XHTML. Plain string input becomes `type="text"` (escaped). For HTML, pass the full attribute shape:

``` php
'content' => ['@type' => 'html', '@' => '<p>This <em>is</em> HTML.</p>'],
```

The view CDATA-wraps `type="html"` bodies so the markup survives the XML encode.

XHTML text constructs are **not currently supported** by `AtomView`. The serializer only supports plain text and `type="html"` text constructs at the moment; passing `@type => 'xhtml'` raises a `SerializationFailureException` instead of emitting invalid escaped markup.

### `updated`, `published` — date constructs

Accepts `DateTimeInterface`, an int unix timestamp, or any string `DateTime` can parse. All inputs are normalized to RFC 3339 (`Y-m-d\TH:i:sP`), which is what Atom requires.

`updated` is the last modification time and is **required**. `published` is the original publication time and is optional but strongly recommended — distinguishing the two is one of Atom's main advantages over RSS. Missing required fields (`id`, `title`, `updated` on the feed; `id`, `title`, `updated` on each entry) raise a `SerializationFailureException`.

### `link` — single, multiple, or shorthand

``` php
// Shorthand — defaults to rel="alternate"
'link' => 'http://example.org/posts/1',

// Single, explicit attributes
'link' => ['@href' => '/feed.atom', '@rel' => 'self', '@type' => 'application/atom+xml'],

// Multiple links on one entry (only Atom supports this — RSS does not)
'link' => [
    ['@href' => 'http://example.org/posts/1',         '@rel' => 'alternate'],
    ['@href' => 'http://example.org/posts/1/comments','@rel' => 'replies'],
    ['@href' => 'http://example.org/audio/1.mp3',     '@rel' => 'enclosure',
     '@type' => 'audio/mpeg', '@length' => '12345'],
],
```

The default for a string-shorthand link is `rel="alternate"`. The default `xmlns` is the Atom namespace, so a self-link to the feed itself is `['@href' => '/feed.atom', '@rel' => 'self', '@type' => 'application/atom+xml']`.

### `author`, `contributor` — person constructs

Atom person constructs have a required `<name>` plus optional `<email>` and `<uri>`. A bare string becomes the `name`. Multiple people pass as a list:

``` php
// Shorthand
'author' => 'Jane Doe',

// Structured
'author' => ['name' => 'Jane Doe', 'email' => 'jane@example.org', 'uri' => 'http://example.org/~jane'],

// Multiple (Atom-only — RSS has one author per item)
'author' => [
    ['name' => 'Alice'],
    ['name' => 'Bob', 'email' => 'bob@example.org'],
],
```

`contributor` works identically.

### `category` — single, list, or shorthand

``` php
// Shorthand (term only)
'category' => 'tech',

// Single, with scheme + label
'category' => ['@term' => 'php', '@scheme' => 'http://example.org/tags', '@label' => 'PHP'],

// Multiple
'category' => [
    'tech',
    ['@term' => 'php', '@scheme' => 'http://example.org/tags'],
],
```

### Extension namespaces

Built in: `dc`, `content`, `itunes`. Use them as `prefix:tag` keys and the matching `xmlns:prefix` decl appears automatically:

``` php
$feed = [
    'id' => '...', 'title' => '...', 'updated' => '...',
    'entries' => [
        [
            'id' => '...', 'title' => '...', 'updated' => '...',
            'dc:creator' => 'Jane',
            'content:encoded' => '<p>HTML body</p>',  // not CDATA-wrapped automatically here; use the html-content form instead
        ],
    ],
];
```

For a custom namespace, register the prefix once (either via `setNamespace()` on the view, or by passing a top-level `namespace` key in the input):

``` php
$feed = [
    'namespace' => ['ex' => 'http://example.org/ext'],
    'id' => '...', 'title' => '...', 'updated' => '...',
    'ex:owner' => 'Mark',
];
```

Using a `prefix:tag` whose prefix has not been registered raises `RuntimeException` — silently emitting a broken namespace declaration is worse than failing loudly.

## Examples

### Minimal feed

``` php
$feed = [
    'id'      => 'http://example.org/feed',
    'title'   => 'My blog',
    'updated' => time(),
];
$this->set(['feed' => $feed]);
$this->viewBuilder()->setClassName('Feed.Atom');
$this->viewBuilder()->setOption('serialize', 'feed');
```

Renders:

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <id>http://example.org/feed</id>
  <title>My blog</title>
  <updated>2026-05-11T12:00:00+00:00</updated>
</feed>
```

### Self-link + alternate, multiple entries

``` php
$feed = [
    'id'      => 'http://example.org/feed',
    'title'   => 'My blog',
    'updated' => '2026-05-11T12:00:00Z',
    'link'    => [
        ['@href' => 'http://example.org/',          '@rel' => 'alternate'],
        ['@href' => 'http://example.org/feed.atom', '@rel' => 'self', '@type' => 'application/atom+xml'],
    ],
    'author'  => ['name' => 'Jane Doe', 'email' => 'jane@example.org'],
    'entries' => [
        [
            'id'        => 'http://example.org/posts/2',
            'title'     => 'Second post',
            'updated'   => '2026-05-11T10:00:00Z',
            'published' => '2026-05-11T08:00:00Z',
            'link'      => 'http://example.org/posts/2',
            'summary'   => 'About Atom.',
            'content'   => ['@type' => 'html', '@' => '<p>Atom is great.</p>'],
        ],
        [
            'id'        => 'http://example.org/posts/1',
            'title'     => 'First post',
            'updated'   => '2026-05-10T12:00:00Z',
            'link'      => 'http://example.org/posts/1',
        ],
    ],
];
```

### Podcast feed (RSS-only territory)

If you need a podcast feed, use `RssView` with the `itunes:` namespace — Apple's spec requires RSS. See [Rss.md](Rss.md).

## Spec references

- [RFC 4287 — The Atom Syndication Format](https://datatracker.ietf.org/doc/html/rfc4287)
- [Atom 1.0 vs RSS 2.0 comparison (W3C, archived)](https://web.archive.org/web/2022*/https://www.intertwingly.net/wiki/pie/Rss20AndAtom10Compared)
- [Atom auto-discovery](https://datatracker.ietf.org/doc/html/rfc4287#section-3.1.1)
