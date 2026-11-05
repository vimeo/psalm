# ArgumentTypeCoercion

Emitted when calling a function with an argument which has a less specific type than the function expects

```php
<?php

class A {}
class B extends A {}

function takesA(A $a) : void {
    takesB($a);
}
function takesB(B $b) : void {}
```

## How to fix

You could add a typecheck before the call to `takesB`:

```php
<?php

function takesA(A $a) : void {
    if ($a instanceof B) {
        takesB($a);
    }
}
```

Or, if you have control over the function signature of `takesA` you can change it to expect `B`:

```php
<?php

function takesA(B $a) : void {
    takesB($a);
}
```

## Built-in functions

Some built-in functions accept a narrower type than their native signature says, e.g. `random_bytes()` only accepts a [`positive-int`](../../annotating_code/type_syntax/scalar_types.md):

```php
<?php

function bad(int $length): string {
    return random_bytes($length); // ArgumentTypeCoercion
}
```

Declare the narrower type of the parameter with [`@psalm-param`](../../annotating_code/supported_annotations.md):

```php
<?php

/** @psalm-param positive-int $length */
function good(int $length): string {
    return random_bytes($length); // no issue
}
```

Or check the value before the call, or assert its type with `@psalm-var`:

```php
<?php

function checked(int $length): string {
    if ($length < 1) {
        throw new InvalidArgumentException('The length must be positive');
    }

    return random_bytes($length); // no issue
}

function asserted(int $length): string {
    /** @psalm-var positive-int $length */
    return random_bytes($length); // no issue
}
```
