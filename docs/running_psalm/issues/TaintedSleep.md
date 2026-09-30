# TaintedSleep

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into a `sleep` call or similar.

```php
<?php

sleep($_GET["seconds"]);
```
