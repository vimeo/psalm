# TaintedSql

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability rather than a type error or a code-quality problem.

Emitted when user-controlled input can be passed into a SQL command.

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
