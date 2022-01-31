# MethodSignatureMustProvideReturnType

In PHP 8.1+, [most non-final internal methods now require overriding methods to declare a compatible return type, otherwise a deprecated notice is emitted during inheritance validation](https://www.php.net/manual/en/migration81.incompatible.php#migration81.incompatible.core.type-compatibility-internal).  

This issue is emitted when a method overriding a native method is defined without a return type.  

**Only if** the return type cannot be declared to keep support for PHP 7, a `#[ReturnTypeWillChange]` attribute can be added to silence the PHP deprecation notice and Psalm issue.  

```php
<?php
class A implements JsonSerializable {
    public function jsonSerialize() {
        return ['type' => 'A'];
    }
}
```
