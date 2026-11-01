# TaintedSql

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user-controlled input can be passed into a SQL command.

## Risk

User input in a SQL command lets an attacker change the query: read or change data they shouldn't, or, depending on the database, run commands.

## Example

```php
<?php

class A {
    public function deleteUser(PDO $pdo) : void {
        $userId = self::getUserId();
        $pdo->exec("delete from users where user_id = " . $userId);
    }

    public static function getUserId() : string {
        return (string) $_GET["user_id"];
    }
}
```

## Mitigations

- Use prepared statements with bound parameters (`$pdo->prepare('… where user_id = ?')`) instead of concatenating values.
- Allowlist anything that cannot be bound, such as table or column names.

## Further resources

- [OWASP Wiki for SQL Injection](https://owasp.org/www-community/attacks/SQL_Injection)
- [SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
- [CWE-89](https://cwe.mitre.org/data/definitions/89.html)
