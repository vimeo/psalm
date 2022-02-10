<?php

use Psalm\Type\TaintKind;

// This maps internal function names to sink types that we don’t want to end up there

/**
 * @var array<string, list<list<TaintKind::*>>>
 */
return [
'exec' => [['shell']],
'create_function' => [[], ['eval']],
'file_get_contents' => [['file']],
'file_put_contents' => [['file']],
'fopen' => [['file']],
'unlink' => [['file']],
'copy' => [['file'], ['file']],
'file' => [['file']],
'link' => [['file'], ['file']],
'mkdir' => [['file']],
'move_uploaded_file' => [['file'], ['file']],
'parse_ini_file' => [['file']],
'chown' => [['file']],
'lchown' => [['file']],
'readfile' => [['file']],
'rename' => [['file'], ['file']],
'rmdir' => [['file']],
'header' => [['header']],
'symlink' => [['file']],
'tempnam' => [['file']],
'igbinary_unserialize' => [['unserialize']],
'ldap_search' => [[], ['ldap'], ['ldap']],
'passthru' => [['shell']],
'pcntl_exec' => [['shell']],
'pg_exec' => [[], ['sql']],
'pg_prepare' => [[], [], ['sql']],
'pg_put_line' => [[], ['sql']],
'pg_query' => [[], ['sql']],
'pg_query_params' => [[], ['sql']],
'pg_send_prepare' => [[], [], ['sql']],
'pg_send_query' => [[], ['sql']],
'pg_send_query_params' => [[], ['sql'], []],
'setcookie' => [['cookie'], ['cookie']],
'shell_exec' => [['shell']],
'system' => [['shell']],
'unserialize' => [['unserialize']],
'popen' => [['shell']],
'proc_open' => [['shell']],
'curl_init' => [['ssrf']],
'curl_setopt' => [[], [], ['ssrf']],
'getimagesize' => [['ssrf']],
];
