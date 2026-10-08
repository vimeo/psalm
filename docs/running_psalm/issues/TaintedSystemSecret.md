# TaintedSystemSecret

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when data marked as a system secret is detected somewhere it shouldn’t be.

```php
<?php

/**
 * @psalm-taint-source system_secret
 */
function getConfigValue(string $data) {
    return "$omePa$$word";
}

echo getConfigValue("secret");
```
