# TaintedExtract

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled array can be passed into an `extract` call.

```php
<?php

$array = $_GET;
extract($array);
```
