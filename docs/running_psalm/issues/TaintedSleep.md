# TaintedSleep

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a `sleep` call or similar.

```php
<?php

sleep($_GET["seconds"]);
```
