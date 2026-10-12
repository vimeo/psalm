# ImpureFunctionCall

Emitted when calling an impure function from a function or method marked as pure.

```php
<?php

function impure(array $a) : array {
    /** @var int */
    static $i = 0;

    ++$i;

    $a[$i] = 1;

    return $a;
}

/** @psalm-pure */
function filterOdd(array $a) : void {
    impure($a);
}
```

To make a function's purity depend on the closures it is given, see [purity templates](../../annotating_code/purity_model.md#purity-templates) in the purity model.
