# TaintedIntegerOverflow

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when an integer the user chooses, whose range the code doesn't bound, is packed into a fixed-size integer (`pack()` with `c`, `s`, `l`, `N`, ...), which it wraps around.

## Risk

A value past the size wraps to another one: a large count becomes negative, a length becomes small, and the checks made on the original value no longer hold for the bytes written.

## Example

```php
<?php

$count = (int) $_GET["count"];
echo pack("N", $count + 1);                           // TaintedIntegerOverflow
echo pack("N", max(0, min($count, 1000)));            // no issue: it fits
```

## Mitigations

- Bound the value (`max(0, min($value, 1000))`) to the range of the size before packing it.
- Use a 64-bit code (`J`, `q`, `Q`, `P`) when the whole range of an integer is meant.

## Further resources

- [CWE-190](https://cwe.mitre.org/data/definitions/190.html)
