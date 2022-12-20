# OverriddenInterfaceConstant

Emitted when a constant declared on an interface is overridden by a child (illegal in PHP < 8.1).

```php
<?php

interface Foo
{
    public const BAR='baz';
}

interface Bar extends Foo
{
    public const BAR='foobar';
}
```
