# OverriddenFinalConstant

Emitted when a constant declared as final is overridden in a child class or interface.

```php
<?php

class Foo
{
    /** @var string */
    final public const BAR='baz';
}

class Bar extends Foo
{
    /** @var string */
    public const BAR='foobar';
}
```
