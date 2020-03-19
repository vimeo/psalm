# DeprecatedConstant

Emitted when referring to a deprecated constant:

```php
class A {
    /** @deprecated */
    const FOO = 'foo';
}

echo A::FOO;
```
