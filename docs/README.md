# About Psalm

Psalm is a static analysis tool that attempts to dig into your program and find as many type-related bugs as possible.

It has a few features that go further than other similar tools:

- **Mixed type warnings**<br />
  If Psalm cannot infer a type for an expression then it uses a `mixed` placeholder type. `mixed` types can sometimes mask bugs, so keeping track of them helps you avoid a number of common pitfalls.

- **Intelligent logic checks**<br />
  Psalm keeps track of logical assertions made about your code, so `if ($a && $a) {}` and `if ($a && !$a) {}` are both treated as issues. Psalm also keeps track of logical assertions made in prior code paths, preventing issues like `if ($a) {} elseif ($a) {}`.

- **Property initialisation checks**<br />
  Psalm checks that all properties of a given object have values after the constructor is called.

- **Taint analysis**<br />
  Psalm can [detect security vulnerabilities](https://psalm.dev/articles/detect-security-vulnerabilities-with-psalm) in your code.
  
- **Language Server**<br />
  Psalm has a Language Server that’s [compatible with a range of different IDEs](https://psalm.dev/docs/running_psalm/language_server/).

- **Automatic fixes**<br />
  Psalm can [fix many of the issues it finds automatically](https://psalm.dev/docs/manipulating_code/fixing/).
  
- **Automatic refactoring**<br />
  Psalm can also [perform simple refactors](https://psalm.dev/docs/manipulating_code/refactoring/) from the command line.

## Example output

Given a file `implode_strings.php`:

```php
<?php
$a = ['foo', 'bar'];
echo implode($a, ' ');
```

```bash
> ./vendor/bin/psalm implode_strings.php
ERROR: InvalidArgument - somefile.php:3:14 - Argument 1 of implode expects `string`, `array` provided (see https://psalm.dev/004)
```

## Inspirations

There are two main inspirations for Psalm:

- Etsy's [Phan](https://github.com/etsy/phan), which uses nikic's [php-ast](https://github.com/nikic/php-ast) extension to create an abstract syntax tree
- Facebook's [Hack](http://hacklang.org/), a PHP-like language that supports many advanced typing features natively, so docblocks aren't necessary.

## Index

- Running Psalm:
    - [Installation](running_psalm/installation.md)
    - [Configuration](running_psalm/configuration.md)
    - Plugins
        - [Using plugins](running_psalm/plugins/using_plugins.md)
        - [Authoring plugins](running_psalm/plugins/authoring_plugins.md)
        - [How Psalm represents types](running_psalm/plugins/plugins_type_system.md)
    - [Command line usage](running_psalm/command_line_usage.md)
    - [IDE support](running_psalm/language_server.md)
    - Handling errors:
        - [Dealing with code issues](running_psalm/dealing_with_code_issues.md)
        - [Issue Types](running_psalm/issues.md)
    - [Checking non-PHP files](running_psalm/checking_non_php_files.md)
- Annotating code:
    - [Typing in Psalm](annotating_code/typing_in_psalm.md)
    - [Supported Annotations](annotating_code/supported_annotations.md)
    - [Template Annotations](annotating_code/templated_annotations.md)
- Manipulating code:
    - [Fixing code](manipulating_code/fixing.md)
    - [Refactoring code](manipulating_code/refactoring.md)

