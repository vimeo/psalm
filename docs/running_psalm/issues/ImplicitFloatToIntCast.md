# ImplicitFloatToIntCast

Emitted when a float whose value is known to lose something is an operand of a bitwise operation (`|`, `&`, `^`, `<<`, `>>`, `~`), which converts it to an int

```php
<?php

function setFlag(): int {
    return 1.5 | 1;
}
```

## Why this is bad

The fraction of the float is dropped. Since PHP 8.1 a float with a fraction (or one that is out of the range of an int) is deprecated as an operand: "Implicit conversion from float to int loses precision".

## How to fix

Convert the value explicitly, so the loss is intended:

```php
<?php

function setFlag(): int {
    return (int) 1.5 | 1;
}
```

A float of unknown value (`float $flags`, `round($x)`) and a float literal without a fraction (`2.0 | 1`) are not reported.
