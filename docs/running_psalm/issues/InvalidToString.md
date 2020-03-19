# InvalidToString

Emitted when a `__toString` method does not always return a `string`

```php
class A {
    public function __toString() {
        return true;
    }
}
```
