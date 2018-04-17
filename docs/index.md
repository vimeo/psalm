# About Psalm

[Psalm](https://getpsalm.org) is a static analysis tool that attempts to dig into your program and find as many type-related bugs as possible.

- [Introduction](#introduction)
- [Installation](installation.md)
- [Configuration](configuration.md)
- [Running Psalm](running_psalm.md)
- [Supported Annotations](supported_annotations.md)
- [Dealing with code issues](dealing_with_code_issues.md)
- [Typing in Psalm](typing_in_psalm.md)
- [Plugins](plugins.md)
- [Checking non-PHP files](checking_non_php_files.md)

## Introduction

Psalm tries to understand your codebase as best as possible so it can find errors.

It has a few features that go further than other similar tools:

- **Mixed type warnings**<br />
  If Psalm cannot infer a type for an expression then it uses a `mixed` placeholder. Any `mixed` type is a sign of an insufficiently-documented codebase. You can configure Psalm warn when encountering `mixed` types by adding *`totallyTyped="true"`* attribute to your XML config file.

- **Logic checks**<br />
  Psalm keeps track of logical assertions made about your code, so `if ($a && $a) {}` and `if ($a && !$a) {}` are both treated as issues. Psalm also keeps track of logical assertions made in prior code paths, preventing issues like `if ($a) {} elseif ($a) {}`.

- **Property initialisation checks**<br />
  Psalm checks that all properties of a given object have values after the constructor is called.

- **Support for complicated array shapes**<br />
  Psalm has support for [object-like arrays](supported_annotations.md#object-like-arrays), allowing you to specify types for all keys of an array if you so wish.

Psalm also has a few features to make it perform as well as possible on large codebases:

- **Multi-threaded mode**<br />
  Using the `--threads=[X]` command line option will run Psalm's analysis stage on [X] threads. Useful for large codebases, it has a massive impact on performance.

- **Incremental checks**<br />
  When using the `--diff` command line option, Psalm will only analyse files that have changed *and* files that reference them.

## Example output

```php
// somefile.php
<?php
$a = ['foo', 'bar'];
echo implode($a, ' ');
```

```bash
> ./vendor/bin/psalm somefile.php
ERROR: InvalidArgument - somefile.php:3:14 - Argument 1 of implode expects `string`, `array` provided
```

## Inspirations

There are two main inspirations for Psalm:
 - Etsy's [Phan](https://github.com/etsy/phan), which uses nikic's [`php-ast`](https://github.com/nikic/php-ast) extension to create an abstract syntax tree
 - Facebook's [Hack](http://hacklang.org/), a PHP-like language that supports many advanced typing features natively, so docblocks aren't necessary.
