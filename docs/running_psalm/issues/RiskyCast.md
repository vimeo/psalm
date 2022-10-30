# RiskyCast

Emitted when attempting to cast an array to int or float

```php
<?php

$foo = (int) array( 'hello' );
```

## Why this is bad

The value resulting from the cast depends on if the array is empty or not and can easily lead to off-by-one errors

## How to fix

Don't cast arrays to int or float.
