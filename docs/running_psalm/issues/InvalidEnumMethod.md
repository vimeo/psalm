# InvalidEnumMethod

Enums may not define most of the magic methods like `__get`, `__toString`, etc.

```php
<?php
enum Status: string {
    case Open = 'open';
    case Closed = 'closed';

    public function __toString(): string { // InvalidEnumMethod
        return "SomeStatus";
    }

    public function __debugInfo(): array { // InvalidEnumMethod before PHP 8.6, no issue from PHP 8.6
        return [];
    }
}
```
