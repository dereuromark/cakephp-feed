# Installation

This development branch requires CakePHP 6 and PHP 8.5. Run these commands
in your application directory:

```bash
composer config minimum-stability dev
composer config prefer-stable true
composer require cakephp/cakephp:^6.0@dev dereuromark/cakephp-feed:dev-cake6
bin/cake plugin load Feed
```

Composer checks the current PHP and CakePHP constraints. See [composer.json](https://github.com/dereuromark/cakephp-feed/blob/cake6/composer.json) for those constraints and the [version map](https://github.com/dereuromark/cakephp-feed/wiki#cakephp-version-map) for older framework branches.

The plugin has no database tables or migrations. Its bootstrap registers the `rss` and `atom` extensions and the Atom MIME mapping. Keep plugin bootstrap enabled if you rely on extension routing. The plugin does not create a controller or feed URL for your application.

Continue with [Getting started](./getting-started) to publish a feed from a controller action.
