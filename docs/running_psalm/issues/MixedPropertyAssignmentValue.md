# MixedPropertyAssignmentValue

Emitted when setting a property to a `mixed` type.

```php
<?php

class A {
    private string $mixed = '';

    public function setMixed(mixed $value): void
    {
        $this->mixed = $value;
    }
}
```