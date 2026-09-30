# TaintedSystemSecret

This is a **security issue**, reported by [security analysis](../../security_analysis/index.md): it flags a potential vulnerability rather than a type error or a code-quality problem.

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
