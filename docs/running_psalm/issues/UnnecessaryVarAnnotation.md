# UnnecessaryVarAnnotation

Emitted when `--find-dead-code` is turned on and you're using a `@var` annotation on an assignment that Psalm has already identified a type for.

```php
<?php // findUnusedCode="true"

function foo() : string {
    return "hello";
}

/** @var string */
$a = foo();
```
