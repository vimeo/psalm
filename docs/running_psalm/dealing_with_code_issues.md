# Dealing with code issues

Psalm has a large number of [code issues](issues.md). Each project can specify its own reporting level for a given issue.

Code issue levels in Psalm fall into three categories:

- `error`<br>
  This will cause Psalm to print a message, and to ultimately terminate with a non-zero exit status

- `info`<br>
  This will cause Psalm to print a message
  
- `suppress`<br>
  This will cause Psalm to ignore the code issue entirely

The third category, `suppress`, is the one you will probably be most interested in, especially when introducing Psalm to a large codebase.

## Suppressing issues

There are two ways to suppress an issue – via the Psalm config or via a function docblock.

### Config suppression

You can use the `<issueHandlers>` tag in the config file to influence how issues are treated.

Some issue types allow the use of `referencedMethod`, `referencedClass` or `referencedVariable` to isolate known trouble spots.

```xml
<issueHandlers>
  <MissingPropertyType errorLevel="suppress" />

  <InvalidReturnType>
    <errorLevel type="suppress">
      <directory name="some_bad_directory" /> <!-- all InvalidReturnType issues in this directory are suppressed -->
      <file name="some_bad_file.php" />  <!-- all InvalidReturnType issues in this file are suppressed -->
    </errorLevel>
  </InvalidReturnType>
  <UndefinedMethod>
    <errorLevel type="suppress">
      <referencedMethod name="Bar\Bat::bar" /> <!-- not supported for all types of errors -->
      <file name="some_bad_file.php" />
    </errorLevel>
  </UndefinedMethod>
  <UndefinedClass>
    <errorLevel type="suppress">
      <referencedClass name="Bar\Bat\Baz" />
    </errorLevel>
  </UndefinedClass>
  <PropertyNotSetInConstructor>
    <errorLevel type="suppress">
        <referencedProperty name="Symfony\Component\Validator\ConstraintValidator::$context" />
    </errorLevel>
  </PropertyNotSetInConstructor>
  <UndefinedGlobalVariable>
    <errorLevel type="suppress">
      <referencedVariable name="$fooBar" /> <!-- if your variable is "$fooBar" -->
    </errorLevel>
  </UndefinedGlobalVariable>
  <PluginIssue name="IssueNameEmittedByPlugin" errorLevel="info" /> <!-- this is a special case to handle issues emitted by plugins -->
</issueHandlers>
```

### Docblock suppression

You can also use `@psalm-suppress IssueName` on a function's docblock to suppress Psalm issues e.g.

```php
<?php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  return $a;
}
```

You can also suppress issues at the line level e.g.

```php
<?php
/**
 * @psalm-suppress InvalidReturnType
 */
function (int $a) : string {
  /**
   * @psalm-suppress InvalidReturnStatement
   */
  return $a;
}
```

If you wish to suppress all issues, you can use `@psalm-suppress all` instead of multiple annotations.

## Using a baseline file

If you have a bunch of errors and you don't want to fix them all at once, Psalm can grandfather-in errors in existing code, while ensuring that new code doesn't have those same sorts of errors.

```
vendor/bin/psalm --set-baseline=your-baseline.xml
```

will generate a file containing the current errors. You should commit that generated file so that Psalm can use it when running in other places (e.g. CI). It won't complain about those errors either.

You have two options to use the generated baseline when running psalm:

```
vendor/bin/psalm --use-baseline=your-baseline.xml
```

or using the configuration:

```xml
<?xml version="1.0"?>
<psalm
       ...
       errorBaseline="./path/to/your-baseline.xml"
>
   ...
</psalm>
```

To update that baseline file, use

```
vendor/bin/psalm --update-baseline
```

This will remove fixed issues, but will _not_ add new issues. To add new issues, use `--set-baseline=...`.

In case you want to run psalm without the baseline, run

```
vendor/bin/psalm --ignore-baseline
```

Baseline files are a great way to gradually improve a codebase.

## Using a plugin

If you want something more custom, like suppressing a certain type of error on classes that implement a particular interface, you can use a plugin that implements `AfterClassLikeVisitInterface`

```php
<?php
namespace Foo\Bar;

use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use ReflectionClass;

/**
 * Suppress issues dynamically based on interface implementation
 */
class DynamicallySuppressClassIssueBasedOnInterface implements AfterClassLikeVisitInterface
{
    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event)
    {
        $storage = $event->getStorage();
        if ($storage->user_defined
            && !$storage->is_interface
            && \class_exists($storage->name)
            && (new ReflectionClass($storage->name))->implementsInterface(\Your\Interface::class)
        ) {
            $storage->suppressed_issues[-1] = 'PropertyNotSetInConstructor';
        }
    }
}
```
