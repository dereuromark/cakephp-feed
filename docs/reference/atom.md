# Atom

`Feed.Atom` takes feed metadata and an `entries` list. It enforces `id`, `title`, and `updated` on the feed and each entry. Other validation rules in [RFC 4287](https://datatracker.ietf.org/doc/html/rfc4287) remain the application's responsibility, including author inheritance, person names, link attributes, valid identifiers, and required entry content or alternate links.

Use stable absolute identifiers. `updated` represents modification time; `published` is optional publication time. Dates accept `DateTimeInterface` and CakePHP date-only objects, integer timestamps, and parseable strings and render as RFC 3339 values.

## Text

Plain strings are XML-escaped text. HTML uses:

```php
$feed['entries'][0]['content'] = ['@type' => 'html', '@' => '<p>Published HTML</p>'];
```

HTML text constructs are CDATA-wrapped. Sanitize untrusted HTML before publishing it. XHTML text constructs are unsupported and raise `SerializationFailureException`; use text or HTML instead. The same text handling applies to title, subtitle, rights, summary, and content.

## Links

A string is an alternate link. Explicit links use XML attributes:

```php
$feed['link'] = [
    ['@href' => 'https://example.org/', '@rel' => 'alternate'],
    ['@href' => 'https://example.org/feeds.atom', '@rel' => 'self', '@type' => 'application/atom+xml'],
];
```

CakePHP URL arrays are also accepted and resolved to absolute URLs. Lists support several links. For media enclosures use `@rel => 'enclosure'` with URL, MIME type, and byte length; Atom does not use the RSS enclosure shorthand.

## People and categories

A bare author string becomes a person name. Structured people support name, email, and URI; lists represent multiple people. Contributors use the same shape:

```php
$feed['author'] = ['name' => 'Example editor', 'email' => 'editor@example.org'];
$feed['category'] = ['@term' => 'news', '@scheme' => 'https://example.org/tags', '@label' => 'News'];
```

Categories also accept strings and lists. Entry `source` can contain metadata for the originating feed.

## Namespaces

The Atom default namespace is automatic. Built-in extension prefixes are `dc`, `content`, and `itunes`. A top-level `namespace` map registers custom prefixes:

```php
$feed['namespace'] = ['ex' => 'https://example.org/feed-extension'];
$feed['ex:owner'] = 'Example publisher';
```

Use explicit HTML text constructs for markup; Atom extension values such as `content:encoded` are not automatically treated as HTML. Register every custom prefix used by your extension data.
