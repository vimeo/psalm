# UnresolvableConstant

Emitted when Psalm cannot resolve a constant used in `key-of` or `value-of`. Note that `static::CONST` is considered
unresolvable for `key-of` and `value-of`, since the literal keys and values can't be resolved due to the possibility
of being overridden by child classes.

```php
<?php

class Foo
{
    public const BAR = ['bar'];

    /**
     * @return value-of<self::BAT>
     */
    public function bar(): string
    {
        return self::BAR[0];
    }
}
```
