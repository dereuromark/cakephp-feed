# CakePHP Feed Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-feed.svg)](https://travis-ci.org/dereuromark/cakephp-feed)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-feed/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-feed)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x Plugin containing a RssView class to generate RSS feeds.

## Version notice

This branch only works for **CakePHP 3.5.5+** - please use the 2.x branch for CakePHP 2.x.

## What is this plugin for?
There is a core helper for RSS generation, but it has several deficiencies.
So this plugin aims to provide a better support for feed generation.

### Goals of this view class

- Support view-less actions via serialize.
- Get rid of the ridiculously verbose "inline" namespace declarations.
- Simplify the use of namespaces and their prefixes (auto-add only those that are actually used).
- Support CDATA (unescaped content).

### Additional features
- Automatic View class mapping via `rss` extension.

See [my article](https://www.dereuromark.de/2013/10/03/rss-feeds-in-cakephp/) for details on the history of this view class.

### Demo
https://sandbox.dereuromark.de/sandbox/feed-examples


## Installation & Docs

- [Documentation](docs/README.md)

### Possible TODOs

* Maybe add Feed readers instead of just writers.
* Add AtomView ?
