# Adding a new issue type

To add a new issue type there are a number of required steps, listed below.

## Generating a new shortcode

Run `bin/max_used_shortcode.php` and note the value it printed (`$max_shortcode`)

## Create issue class

Create a class in `Psalm\Issue` namespace like this:

```php
<?php

namespace Psalm\Issue;

final class MyNewIssue extends CodeIssue 
{
    public const SHORTCODE = 123;
    public const ERROR_LEVEL = 2;
}
```

For `SHORTCODE` value use `$max_shortcode + 1`. To choose appropriate error level see [Error levels](../running_psalm/error_levels.md).

There a number of abstract classes you can extend:

* `CodeIssue` - non specific, default issue. It's a base class for all issues.
* `ClassIssue` - issue related to a specific class (also interface, trait, enum). These issues can be suppressed for specific classes in `psalm.xml` by using `referencedClass` attribute
* `PropertyIssue` - issue related to a specific property. Can be targeted by using `referencedProperty` in `psalm.xml`
* `FunctionIssue` - issue related to a specific function. Can be suppressed with `referencedFunction` attribute.
* `ArgumentIssue` - issue related to a specific argument. Can be targeted with `referencedFunction` attribute.
* `MethodIssue` - issue related to a specific method. Can be targeted with `referencedMethod` attribute.
* `ClassConstantIssue` - issue related ot a specific class constant. Can be targeted with `referencedConstant`.
* `VariableIssue` - issue for a specific variable. Targeted with `referencedVariable`

## Add a `config.xsd` entry

All issue types needs to be listed in `config.xsd`, which is used to validate `psalm.xml`. Choose appropriate `type` attribute. E.g. for issues extending `PropertyIssue` use `type="PropertyIssueHandlerType"`.

## Add a doc page for your new issue

Every issue needs to be documented. Create a markdown file in `docs/running_psalm/issues` folder. Make sure to include a snippet of code illustrating your issue. Important: snippets must use fenced php code block and must include opening PHP tag (`<?php`). The snippet must actually produce the issue you're documenting. It's checked by our test suite.

## Add links to the doc page

Add links to the doc page you created to `docs/running_psalm/error_levels.md` and `docs/running_psalm/issues.md`

## Run documentation tests

```
$ vendor/bin/phpunit tests/DocumentationTest.php
```

It will check whether you did all (or at least most) of the steps above.

## Use your new issue type in Psalm core

```php
IssueBuffer::maybeAdd(new MyNewIssue(...))
```
