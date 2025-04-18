# DuplicateProperty

Emitted when a class property is defined twice

```php
<?php

class Foo
{
    public int $foo;
    public string $foo;
}

class Bar
{
    public int $bar;
    public static string $bar;
}
```
