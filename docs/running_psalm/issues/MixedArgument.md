# MixedArgument

Emitted when Psalm cannot determine the type of an argument

```php
function takesInt(int $i) : void {}
takesInt($_GET['foo']);
```
