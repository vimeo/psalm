# LessSpecificReturnType

Emitted when a return type covers more possibilities than the function itself

```php
function foo() : ?int {
    return 5;
}
```
