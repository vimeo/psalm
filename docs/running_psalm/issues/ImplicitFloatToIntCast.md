# ImplicitFloatToIntCast

Emitted when a float is an operand of a bitwise operation (`|`, `&`, `^`, `<<`, `>>`, `~`), which converts it to an int

```php
<?php

function setFlag(float $flags): int {
    return $flags | 1;
}
```

## Why this is bad

The fraction of the float is dropped. Since PHP 8.1 a float with a fraction (or one that is out of the range of an int) is deprecated as an operand: "Implicit conversion from float to int loses precision".

## How to fix

Convert the value explicitly, so the loss is intended:

```php
<?php

function setFlag(float $flags): int {
    return (int) $flags | 1;
}
```

A float literal without a fraction (`2.0 | 1`) is not reported.
