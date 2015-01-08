# CakePHP Feed Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-feed.png)](https://travis-ci.org/dereuromark/cakephp-feed)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-feed/badge.png)](https://coveralls.io/r/dereuromark/cakephp-feed)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.png)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x Plugin containing a RssView class to generate RSS feeds.

## Version notice

This branch only works for **CakePHP3.x** - please use the [Tools plugin version](https://github.com/dereuromark/cakephp-tools/blob/master/View/RssView.php) for CakePHP 2.x!
**It is still dev** (not even alpha), please be careful with using it.

### Planned Release Cycle:
Dev (currently), Alpha, Beta, RC, 1.0 stable (incl. tagged release then).

## What is this plugin for?
There is a core Rss helper, but it has several defencies.

### Goals of this view class

- Support view-less actions via serialize.
- Get rid of the ridiculously verbose “inline” namespace declarations.
- Simplify the use of namespaces and their prefixes (auto-add only those that are actually used).
- Support CDATA (unescaped content).

### Additional features
- View class mapping

See [my article](http://www.dereuromark.de/2013/10/03/rss-feeds-in-cakephp/) for detais on the history of this view class.

## Installation & Docs

- [Documentation](docs/README.md)

### Possible TODOs

* Maybe add Feed readers instead of just writers.
* Add AtomView ?
