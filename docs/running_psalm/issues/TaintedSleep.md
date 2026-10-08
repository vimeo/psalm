# TaintedSleep

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a `sleep` call or similar.

```php
<?php

sleep($_GET["seconds"]);
```

The options making curl throttle a transfer (`CURLOPT_MAX_RECV_SPEED_LARGE`, ...) or wait longer for it (`CURLOPT_TIMEOUT`, ...) are reported too: a client choosing them can keep the request busy as long as it wants.
