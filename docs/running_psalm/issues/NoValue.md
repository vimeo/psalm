# NoValue

Emitted when Psalm invalidated all possible types for a given expression. It is often an indication that Psalm found dead code or that documented types were not exhaustive

```php
<?php

/**
 * @return never
 */
function foo() : void {
    exit();
}

$a = foo(); // Psalm knows $a will never contain any type because foo() won't return
```

```php
<?php

function foo() : void {
    return throw new Exception(''); //Psalm detected the return expression is never used
}
```

```php
<?php
function shutdown(): never {die('Application closed unexpectedly');}
function foo(string $_a): void{}

foo(shutdown()); // foo() will never be called
```

```php
<?php
$a = [];
/** @psalm-suppress TypeDoesNotContainType */
assert(!empty($a));

count($a); // Assert above always fail. There is no possible type that $a can have here
```