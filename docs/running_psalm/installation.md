# Installation

Psalm Requires PHP >= 7.1 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Add a `psalm.xml` config:

```bash
./vendor/bin/psalm --init
```

Psalm will scan your project and figure out an appropriate [error level](error_levels.md) for your codebase.

Then run Psalm:

```bash
./vendor/bin/psalm
```

Psalm will probably find a number of issues - find out how to deal with them in [Dealing with code issues](dealing_with_code_issues.md).

## Using the Phar

Sometimes your project can conflict with one or more of Psalm’s dependencies.

In that case you may find the Phar (a self-contained PHP executable) useful.

Run `composer require --dev psalm/phar` to install it.
