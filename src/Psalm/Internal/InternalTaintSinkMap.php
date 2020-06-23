<?php

// This maps internal function names to sink types that we don’t want to end up there

return [
'exec' => [['shell']],
'file_get_contents' => [['text']],
'file_put_contents' => [['shell']],
'fopen' => [['shell']],
'header' => [['text']],
'ldap_search' => [['text']],
'mysqli_query' => [[], ['sql']],
'passthru' => [['shell']],
'pcntl_exec' => [['shell']],
'printr' => [['html', 'user_secret', 'system_secret']],
'PDO::prepare' => [['sql']],
'PDO::query' => [['sql']],
'PDO::exec' => [['sql']],
'setcookie' => [['text'], ['text']],
'shell_exec' => [['shell']],
'system' => [['shell']],
];
