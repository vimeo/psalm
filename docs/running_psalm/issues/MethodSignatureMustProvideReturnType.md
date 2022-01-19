# MethodSignatureMustProvideReturnType

In PHP 8.1+, [most non-final internal methods now require overriding methods to declare a compatible return type, otherwise a deprecated notice is emitted during inheritance validation](https://www.php.net/manual/en/migration81.incompatible.php#migration81.incompatible.core.type-compatibility-internal).  

This issue is emitted when a method overriding a native method is defined without a return type.  


```php
<?php

class A implements JsonSerializable {
    public function jsonSerialize() {
        return random_int(0, 1) ? 'test' : 123;
    }
}
```

Fix by specifying the correct typehint:  

```php
<?php

class A implements JsonSerializable {
    public function jsonSerialize(): string|int {
        return random_int(0, 1) ? 'test' : 123;
    }
}
```

In case the return type cannot be declared for an overriding method due to PHP cross-version compatibility concerns, a `#[ReturnTypeWillChange]` attribute can be added to silence the PHP deprecation notice and Psalm issue. 

```php
<?php

use ReturnTypeWillChange;

class A implements JsonSerializable {
    /**
     * TODO: Remove this attribute once PHP 7 support is dropped.
     * 
     * @return "test"|123
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize() {
        return random_int(0, 1) ? 'test' : 123;
    }
}
```
