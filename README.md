# CakePHP Feed Plugin
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-feed.svg?branch=cake2)](https://travis-ci.com/dereuromark/cakephp-feed)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-feed/badge.png?branch=2.x)](https://coveralls.io/r/dereuromark/cakephp-feed)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-feed/v/stable.png)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.png)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-feed)

NOTE: With 4.x development already being started, **this 2.x branch is now in maintenance mode**. No active development is done anymore on it, mainly only necessary bugfixes.

## What is this plugin for?
There is a core Rss helper, but it has several deficiencies.

### Goals of this view class

- Support view-less actions via serialize.
- Get rid of the ridiculously verbose "inline" namespace declarations.
- Simplify the use of namespaces and their prefixes (auto-add only those that are actually used).
- Support CDATA (unescaped content).

### Additional features

- Automatic View class mapping via `rss` extension.

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

* Put the files in `APP/Plugin/Feed`.
* Make sure you have `CakePlugin::load('Feed')` or `CakePlugin::loadAll()` in your bootstrap.

You should use composer/packagist now @ https://packagist.org/packages/dereuromark/cakephp-feed

```
"require": {
	"dereuromark/cakephp-feed": "0.*"
}
```

That's it. It should be up and running.

Don't forget to add `public $components = array('RequestHandler');` in your controller for automatic extension routing.

## How to use
See [my article](http://www.dereuromark.de/2013/10/03/rss-feeds-in-cakephp/) for details on how to use this view class.

## History
This plugin is a split-off of the [Tools plugin](https://github.com/dereuromark/cakephp-tools/tree/2.x).
