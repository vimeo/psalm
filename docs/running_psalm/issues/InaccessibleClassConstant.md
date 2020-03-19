# InaccessibleClassConstant

Emitted when a public/private class constant is not accessible from the calling context

```php
class A {
    protected const FOO = 'FOO';
}
echo A::FOO;
```
