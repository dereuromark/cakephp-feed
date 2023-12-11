# CakePHP Feed Plugin
[![CI](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-feed/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-feed)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.svg)](LICENSE)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-feed/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin containing a RssView class to generate RSS feeds.

## Version notice

This branch is for use with **CakePHP 5.0+**. See [version map](https://github.com/dereuromark/cakephp-feed/wiki#cakephp-version-map) for details.

## What is this plugin for?
There used to be a core helper for RSS generation, but it had several deficiencies. It also was removed in 4.0.
So this plugin aims to provide a better support for feed generation.

### Goals of this view class

- Support view-less actions via serialize.
- Get rid of the ridiculously verbose "inline" namespace declarations.
- Simplify the use of namespaces and their prefixes (auto-add only those that are actually used).
- Support CDATA (unescaped content).
- Allow mini-templating where necessary.

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
