# TaintedShell

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into an `exec` call or similar.

## Risk

User input that reaches a shell command lets an attacker run commands with the rights of the PHP process.

## Example

```php
<?php

$command = $_GET["command"];

runCode($command);

function runCode(string $command) {
    exec($command);
}
```

## Mitigations

- Avoid the shell: use the PHP function that does the job.
- Pass each argument through `escapeshellarg()` (and the command through `escapeshellcmd()`), or pass the arguments as an array to `proc_open()`.
- Check input against an allowlist.

## Further resources

- [OWASP Wiki for Command Injection](https://owasp.org/www-community/attacks/Command_Injection)
- [OS Command Injection Defense Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/OS_Command_Injection_Defense_Cheat_Sheet.html)
- [CWE-78](https://cwe.mitre.org/data/definitions/78.html)
