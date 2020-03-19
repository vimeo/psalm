# MixedFunctionCall

Emitted when calling a function on a value whose type Psalm cannot infer.

```php
/** @var mixed */
$a = $_GET['foo'];
$a();
```
