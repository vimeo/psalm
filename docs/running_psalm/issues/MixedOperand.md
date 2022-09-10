# MixedOperand

Emitted when Psalm cannot infer a type for an operand in any calculated expression

```php
<?php

echo $GLOBALS['foo'] + "hello";
```

## Why it’s bad

Mixed operands can have fatal consequences, e.g. here:

```php
<?php

function foo(mixed $m) {
    echo $m . 'bar';
}

class A {}

foo(new A()); // triggers fatal error
```
