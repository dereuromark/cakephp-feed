# Upgrade Guide

## Upgrading from 3.x

```php
$this->viewBuilder()->className('Feed.Rss');
```
is now

```php
$this->viewBuilder()->setClassName('Feed.Rss');
```
