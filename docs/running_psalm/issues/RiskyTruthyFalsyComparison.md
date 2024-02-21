# RiskyTruthyFalsyComparison

Emitted when comparing a value with multiple types, where at least one type can be only truthy or falsy and other types can contain both truthy and falsy values.

```php
<?php

/**
 * @param array|null $arg
 * @return void
 */
function foo($arg) {
    if ($arg) {
        // this is risky, bc the empty array and null case are handled together
    }
    
    if (!$arg) {
        // this is risky, bc the empty array and null case are handled together  
    }
}
```

## Why this is bad

The truthy/falsy type of a variable is often forgotten and not handled explicitly causing hard to track down errors.

## How to fix

Explicitly validate the variable with strict comparison.
