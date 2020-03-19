# PossiblyUndefinedArrayOffset

Emitted when trying to access a possibly undefined array offset

```php
if (rand(0, 1)) {
    $arr = ["a" => 1, "b" => 2];
} else {
    $arr = ["a" => 3];
}

echo $arr["b"];

```
