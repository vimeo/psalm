<h1><a href="https://getpsalm.org"><img src="PsalmLogo.png" height="64" alt="logo" /></a></h1>

[![Packagist](https://img.shields.io/packagist/v/vimeo/psalm.svg)](https://packagist.org/packages/vimeo/psalm)
[![Packagist](https://img.shields.io/packagist/dt/vimeo/psalm.svg)](https://packagist.org/packages/vimeo/psalm)
[![Travis CI](https://img.shields.io/travis/vimeo/psalm/master.svg)](https://travis-ci.org/vimeo/psalm/branches)
[![Coverage Status](https://coveralls.io/repos/github/vimeo/psalm/badge.svg)](https://coveralls.io/github/vimeo/psalm)

Psalm is a static analysis tool for finding errors in PHP applications, built on top of [PHP Parser](https://github.com/nikic/php-parser).

It's able to find a [large number issues](https://github.com/vimeo/psalm/blob/master/docs/issues.md), but it can also be configured to only care about a small subset of those.

[Read more about Psalm](https://github.com/vimeo/psalm/blob/master/docs/index.md), [try a live demo](https://getpsalm.org/), or install it in your project by following the guide below.

## Quickstart Guide

Install via [Composer](https://getcomposer.org/):

```bash
composer require --dev vimeo/psalm
```

Add a config:

```bash
./vendor/bin/psalm --init
```

Then run Psalm:

```bash
./vendor/bin/psalm
```

The config created above will show you all issues in your code, but will emit `INFO` issues (as opposed to `ERROR`) for certain common trivial code problems. If you want a more lenient config you can specify the level with

```bash
./vendor/bin/psalm --init [source_dir] [level]
```

You can also [learn how to suppress certain issues](https://github.com/vimeo/psalm/blob/master/docs/dealing_with_code_issues.md).

## How Psalm Works

A basic rundown of Psalm’s internals can be found in [docs/how_psalm_works.md](https://github.com/vimeo/psalm/blob/master/docs/how_psalm_works.md).

## Acknowledgements

The engineering team [@vimeo](https://github.com/vimeo) for encouragement and patience, especially [@nbeliard](https://github.com/nbeliard), [@erunion](https://github.com/erunion) and [@nickyr](https://github.com/nickyr).
