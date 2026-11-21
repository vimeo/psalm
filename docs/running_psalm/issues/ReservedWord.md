# ReservedWord

Emitted when using a reserved word as a class name

```php
<?php

function foo(resource $res) : void {}
```

`instanceof iterable` is also reported, as no object is an instance of it:

```php
<?php

function foo(mixed $a): bool {
    return $a instanceof iterable; // ReservedWord
}
```

Names PHP reserves in a later version are reported too, whatever PHP version you analyse:

```php
<?php

final class let {} // ReservedWord: let becomes a keyword in PHP 8.6
function readonly(): void {} // ReservedWord
const _ = 1; // ReservedWord
```
