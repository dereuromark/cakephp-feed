# CakePHP Feed Plugin
[![CI](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml/badge.svg?branch=cake6)](https://github.com/dereuromark/cakephp-feed/actions/workflows/ci.yml?query=branch%3Acake6)
[![Coverage Status](https://codecov.io/gh/dereuromark/cakephp-feed/branch/cake6/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-feed)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.5-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-feed/license.svg)](LICENSE)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-feed/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-feed/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-feed)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin containing `RssView` and `AtomView` classes for generating RSS 2.0 and Atom 1.0 feeds.

This development branch targets CakePHP 6 and PHP 8.5. Read the
[branch installation guide](docs/guide/installation.md) and
[Getting started](docs/guide/getting-started.md). The
[published website](https://dereuromark.github.io/cakephp-feed/) covers CakePHP 5.

RSS provides channel metadata, items, and enclosures. Atom provides stable identifiers, publication and modification dates, structured authors, and multiple links. Choose the format your consumers support. Both view classes serialize arrays without template files.

The plugin writes feeds; it does not read or aggregate them. Try the [sandbox examples](https://sandbox.dereuromark.de/sandbox/feed-examples). The [version map](https://github.com/dereuromark/cakephp-feed/wiki#cakephp-version-map) covers older CakePHP branches.
