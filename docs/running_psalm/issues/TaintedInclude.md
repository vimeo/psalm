# TaintedInclude

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an `include` or `require` expression.

## Risk

`include` and `require` run the file they load, so user input in the path lets an attacker load a file of their choice (local file inclusion) or, if `allow_url_include` is on, a remote one (remote code execution).

Passing untrusted user input to `include` calls is dangerous, as it can allow an attacker to execute arbitrary scripts on your server.

```php
<?php

$name = $_GET["name"];

includeCode($name);

function includeCode(string $name) : void {
    include($name . '.php');
}
```

## Mitigations

- Don't build the path from user input: map known values to files with an allowlist.
- Keep `allow_url_include` off.

## Further resources

- [OWASP Wiki for Path Traversal](https://owasp.org/www-community/attacks/Path_Traversal)
- [CWE-98](https://cwe.mitre.org/data/definitions/98.html)
