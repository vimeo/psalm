# RedundantCast

Emitted when a cast (string, int, float etc.) is redundant

```php
<?php
function foo(string $s) : string {
    return (string) $s;
}
```
