# PrivateFinalMethod

Emitted when a class defines private final method. PHP 8.0+ emits a warning when it sees private final method (except `__construct` where it's allowed), and allows redefinition in child classes (effectively ignoring `final` modifier). Before PHP 8.0, `final` was respected in this case.

```php
<?php
class Foo {
    final private function baz(): void {}
}
```

## Why this is bad

It causes a warning, and behavior differs between versions.
