# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `ROOT/plugins/Feed`, using Packagist/Composer:
```
composer require dereuromark/cakephp-feed:dev-master
```

or manually via

```
"require": {
	"dereuromark/cakephp-feed": "dev-master"
}
```
and

	composer update

Details @ https://packagist.org/packages/dereuromark/cakephp-feed

This will load the plugin (within your boostrap file):
```php
Plugin::load('Feed');
```
or
```php
Plugin::loadAll(...);
```

In case you want the Feed bootstrap file included (recommended), you can do that in your `ROOT/config/bootstrap.php` with

```php
Plugin::load('Feed', array('bootstrap' => true));
```

or

```php
Plugin::loadAll(array(
		'Feed' => array('bootstrap' => true)
));
```

