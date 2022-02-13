# InvalidClassConstantType

Emitted when a constant type in a child does not satisfy the type in the parent.

```php
<?php

class Foo
{
    /** @var int<1,max> */
    public const CONSTANT = 3;

    public static function bar(): array
    {
        return str_split("foobar", static::CONSTANT);
    }
}

class Bar extends Foo
{
    /** @var int<min,-1> */
    public const CONSTANT = -1;
}

Bar::bar(); // Error: str_split argument 2 must be greater than 0
```

This issue will always show up when overriding a constant that doesn't have a docblock type. Psalm will infer the most specific type for the constant that it can, you have to add a type annotation to tell it what type constraint you wish to be applied. Otherwise Psalm has no way of telling if you mean for the constant to be a literal `1`, `int<1, max>`, `int`, `numeric`, etc.
