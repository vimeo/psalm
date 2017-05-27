<h1><a href="https://getpsalm.org"><img src="PsalmLogo.png" height="64" alt="logo" /></a></h1>

[![Packagist](https://img.shields.io/packagist/v/vimeo/psalm.svg)](https://packagist.org/packages/vimeo/psalm)
[![Travis CI](https://img.shields.io/travis/vimeo/psalm/master.svg)](https://travis-ci.org/vimeo/psalm/branches)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/vimeo/psalm/master.svg)](https://scrutinizer-ci.com/g/vimeo/psalm/?branch=master)

Psalm is a static analysis tool for finding errors in PHP applications.

 - **v0.3.x** supports checking PHP 5.4 - 7.1 code, and requires **PHP 5.6+** to run.
 - **v0.2.x** supports checking PHP 5.4 - 7.0 code and requires **PHP 5.4+** to run.

Check out the [wiki](https://github.com/vimeo/psalm/wiki) or [try a live demo](https://getpsalm.org/)!

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

You can also [learn how to suppress certain issues](https://github.com/vimeo/psalm/wiki/Dealing-with-code-issues).

## Acknowledgements

The engineering team [@vimeo](https://github.com/vimeo) for encouragement and patience, especially [@nbeliard](https://github.com/nbeliard), [@erunion](https://github.com/erunion) and [@nickyr](https://github.com/nickyr).

Thanks also to [@nikic](https://github.com/nikic) for creating the excellent [php-parser](https://github.com/nikic/php-parser), on top of which Psalm is built.
