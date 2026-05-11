# CakePHP Feed Plugin
[![CI](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-feed/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-feed)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.svg)](LICENSE)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-feed/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin containing `RssView` and `AtomView` classes for generating RSS 2.0 and Atom 1.0 feeds.

## Version notice

This branch is for use with **CakePHP 5.1+**. See [version map](https://github.com/dereuromark/cakephp-feed/wiki#cakephp-version-map) for details.

## What is this plugin for?
There used to be a core helper for RSS generation, but it had several deficiencies. It also was removed in 4.0.
So this plugin aims to provide a better support for feed generation — both RSS 2.0 (the de-facto standard, still required for podcasting) and Atom 1.0 (the cleaner, stricter successor preferred for most modern feeds).

### Goals of these view classes

- Support view-less actions via serialize.
- Get rid of the ridiculously verbose "inline" namespace declarations.
- Simplify the use of namespaces and their prefixes (auto-add only those that are actually used).
- Support CDATA (unescaped content).
- Allow mini-templating where necessary.
- Accept friendly shorthands for the common case (bare strings for `link`, `author`, `category`) while leaving the full attribute shape available for power use.

### RSS vs Atom: which one?

- **RSS 2.0** if you publish a podcast. Apple Podcasts and the rest of that ecosystem require RSS plus the `itunes:` namespace. Also pick RSS if you have a specific reason to target the long tail of RSS-only readers.
- **Atom 1.0** for almost anything else. Unambiguous date semantics (separate `<published>` and `<updated>`), typed content (`text`/`html`/`xhtml`), structured `<author>` with `<name>/<email>/<uri>`, multiple `<link rel>` per entry, mandatory `<id>` for proper deduplication. Most modern feed readers parse both equally well, and Atom is strictly less ambiguous.
- **Both, if you're not sure.** Each view class is small; emitting `/feed.rss` and `/feed.atom` from the same controller action is a few extra lines.

### Additional features
- Automatic View class mapping via `rss` and `atom` extensions.

See [my article](https://www.dereuromark.de/2013/10/03/rss-feeds-in-cakephp/) for details on the history of this view class.

### Demo
https://sandbox.dereuromark.de/sandbox/feed-examples


## Installation & Docs

- [Documentation](docs/README.md)

### Possible TODOs

* Maybe add Feed readers instead of just writers.
