# DuplicateConstant

Emitted when a constant is defined twice in a single class or when there's a
clash between a constant and an enum case.

```php
<?php
class C {
    public const A = 1;
    public const A = 2;
}
```

## Why this is bad

The above code won’t compile.
