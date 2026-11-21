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

This issue will always show up when overriding a constant that has neither a docblock type nor a native type. Psalm will infer the most specific type for the constant that it can, you have to add a type annotation to tell it what type constraint you wish to be applied. Otherwise Psalm has no way of telling if you mean for the constant to be a literal `1`, `int<1, max>`, `int`, `numeric`, etc.

A native type (PHP 8.3) is the constraint when the constant has no docblock type:

```php
<?php

class Limits
{
    public const int MAX = 10;
}

class LowerLimits extends Limits
{
    public const int MAX = 5; // no issue
}
```
