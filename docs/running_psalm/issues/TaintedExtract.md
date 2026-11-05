# TaintedExtract

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled array can be passed into an `extract` call.

## Risk

`extract` creates variables from the keys of an array, so an attacker who controls the array can overwrite variables of the scope that calls it, such as flags that say whether the user is authenticated.

## Example

```php
<?php

$array = $_GET;
extract($array);
```

## Mitigations

- Don't call `extract` on user input; read the keys you need one by one.
- If you must, use the `EXTR_SKIP` flag so existing variables are not overwritten, and a `EXTR_PREFIX_ALL` prefix.

## Further resources

- [CWE-621](https://cwe.mitre.org/data/definitions/621.html)
