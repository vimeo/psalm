# ImpureMethodCall

Emitted when calling an impure method from a function or method marked as pure.

```php
<?php

class A {
    public int $a = 5;

    public function foo() : void {
        $this->a++;
    }
}

/** @psalm-pure */
function filterOdd(int $i, A $a) : ?int {
    $a->foo();

    if ($i % 2 === 0 || $a->a === 2) {
        return $i;
    }

    return null;
}
```

What a method call costs depends on its receiver: see [how calls are charged](../../annotating_code/purity_model.md#how-calls-are-charged) in the purity model.
