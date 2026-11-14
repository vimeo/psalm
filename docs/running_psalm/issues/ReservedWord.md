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
