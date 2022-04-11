# DuplicateProperty

Emitted when a property is defined twice in a single class.

```php
<?php
class Foo {
    public int $bar = 1;
    public int $bar = 2;
}
```

## Why this is bad

The above code won’t compile.
