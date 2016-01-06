# Upgrade Guide

## Upgrading from 2.x or 3.0 to 3.1

```php
$this->viewClass = 'Feed.Rss';
```
is now

```php
$this->viewBuilder()->className('Feed.Rss');
```

Coming from 2.x the ext key in URLs is now _ext:
```php
[..., '_ext' => 'rss']
```
In case you want to build URLs linking to the feed from your templates.
