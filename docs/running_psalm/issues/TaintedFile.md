# TaintedFile

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

This rule is emitted when user-controlled input can be passed into a sensitive file operation.

## Risk

The risk here depends on the actual operation that contains user-controlled input, and how it is later on processed.

It could range from:

- Creating files
    - Example: `file_put_contents`
    - Risk: Depending on the server configuration this may result in remote code execution. (e.g. writing a file in the web root)
- Modifying files
    - Example: `file_put_contents`
    - Risk: Depending on the server configuration this may result in remote code execution. (e.g. modifying a PHP file)
- Reading files
    - Example: `file_get_contents`
    - Risk: Sensitive data could be exposed from the filesystem. (e.g. config values, source code, user-submitted files)
- Deleting files
    - Example: `unlink`
    - Risk: Denial of Service or potentially RCE. (e.g. deleting application code, removing a .htaccess file)

curl opens the files some of its options name too. `CURLOPT_COOKIEJAR` writes the cookies the server sends into the file it names, so a server can put PHP code in a file of the attacker's choosing. A `CURLFile` uploads the file it names to the server.

## Example

```php
<?php

unlink($_GET['path']);
```

## Mitigations

Use an allowlist approach where possible to verify names on file operations.

Sanitize user-controlled filenames by stripping `..`, `\` and `/`.

## Further resources

- [File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [OWASP Wiki for Unrestricted FIle Upload](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload)
- [CWE-73](https://cwe.mitre.org/data/definitions/73.html)
