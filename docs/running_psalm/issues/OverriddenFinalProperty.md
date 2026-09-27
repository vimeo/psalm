# OverriddenFinalProperty

Emitted when a property declared as final is redeclared in a child class. Properties declared with `private(set)`
asymmetric visibility are implicitly final.

```php
<?php

class Foo
{
    public private(set) string $bar = 'baz';
}

class Bar extends Foo
{
    public string $bar = 'foobar';
}
```
