<?php

declare(strict_types=1);

/**
 * What the builtin functions and methods running SQL queries store in the database and read from it, as the
 * `@psalm-database-*` annotations say for the other function-likes (see DatabaseTaints): parameters are given by
 * offset. `optional_first` is the number of arguments below which the first parameter (a connection) is left out.
 *
 * @var array<lowercase-string, list<array<array-key, mixed>>>
 */
return [
    // PDO
    'pdo::query' => [['query', 'sql' => 0]],
    'pdo::exec' => [['query', 'sql' => 0, 'result' => 'none']],
    'pdo::prepare' => [['prepare', 'sql' => 0]],
    'pdostatement::execute' => [['execute', 'params' => 0]],
    'pdostatement::bindvalue' => [['bind', 'key' => 0, 'value' => 1, 'base' => 1]],
    'pdostatement::bindparam' => [['bind', 'key' => 0, 'value' => 1, 'base' => 1]],
    'pdostatement::fetch' => [['fetch']],
    'pdostatement::fetchall' => [['fetch', 'result' => 'rows']],
    'pdostatement::fetchcolumn' => [['fetch', 'result' => 'any_value']],
    'pdostatement::fetchobject' => [['fetch']],
    'pdostatement::getiterator' => [['fetch', 'result' => 'rows']],
    // mysqli
    'mysqli::query' => [['query', 'sql' => 0]],
    'mysqli::real_query' => [['query', 'sql' => 0, 'result' => 'none']],
    'mysqli::multi_query' => [['query', 'sql' => 0, 'result' => 'none']],
    'mysqli::execute_query' => [['query', 'sql' => 0, 'params' => 1]],
    'mysqli::prepare' => [['prepare', 'sql' => 0]],
    'mysqli_stmt::execute' => [['execute', 'params' => 0]],
    'mysqli_stmt::bind_param' => [['bind', 'values' => 1]],
    'mysqli_stmt::get_result' => [['fetch']],
    'mysqli_result::fetch_assoc' => [['fetch']],
    'mysqli_result::fetch_array' => [['fetch']],
    'mysqli_result::fetch_row' => [['fetch']],
    'mysqli_result::fetch_object' => [['fetch']],
    'mysqli_result::fetch_all' => [['fetch', 'result' => 'rows']],
    'mysqli_result::fetch_column' => [['fetch', 'result' => 'any_value']],
    'mysqli_result::getiterator' => [['fetch', 'result' => 'rows']],
    'mysqli_query' => [['query', 'sql' => 1]],
    'mysqli_real_query' => [['query', 'sql' => 1, 'result' => 'none']],
    'mysqli_multi_query' => [['query', 'sql' => 1, 'result' => 'none']],
    'mysqli_execute_query' => [['query', 'sql' => 1, 'params' => 2]],
    'mysqli_prepare' => [['prepare', 'sql' => 1]],
    'mysqli_stmt_execute' => [['execute', 'statement' => 0, 'params' => 1]],
    'mysqli_execute' => [['execute', 'statement' => 0, 'params' => 1]],
    'mysqli_stmt_bind_param' => [['bind', 'statement' => 0, 'values' => 2]],
    'mysqli_stmt_get_result' => [['fetch', 'from' => 0]],
    'mysqli_fetch_assoc' => [['fetch', 'from' => 0]],
    'mysqli_fetch_array' => [['fetch', 'from' => 0]],
    'mysqli_fetch_row' => [['fetch', 'from' => 0]],
    'mysqli_fetch_object' => [['fetch', 'from' => 0]],
    'mysqli_fetch_all' => [['fetch', 'from' => 0, 'result' => 'rows']],
    'mysqli_fetch_column' => [['fetch', 'from' => 0, 'result' => 'any_value']],
    // pgsql
    'pg_query' => [['query', 'sql' => 1, 'optional_first' => 2]],
    'pg_exec' => [['query', 'sql' => 1, 'optional_first' => 2]],
    'pg_query_params' => [['query', 'sql' => 1, 'params' => 2, 'optional_first' => 3]],
    'pg_send_query' => [['query', 'sql' => 1, 'result' => 'none']],
    'pg_send_query_params' => [['query', 'sql' => 1, 'params' => 2, 'result' => 'none']],
    'pg_prepare' => [['prepare', 'name' => 1, 'sql' => 2, 'optional_first' => 3]],
    'pg_send_prepare' => [['prepare', 'name' => 1, 'sql' => 2]],
    'pg_execute' => [['execute', 'name' => 1, 'params' => 2, 'optional_first' => 3]],
    'pg_send_execute' => [['execute', 'name' => 1, 'params' => 2, 'result' => 'none']],
    'pg_insert' => [['insert', 'table' => 1, 'data' => 2]],
    'pg_update' => [['update', 'table' => 1, 'data' => 2]],
    'pg_select' => [['select', 'table' => 1]],
    'pg_fetch_assoc' => [['fetch', 'from' => 0]],
    'pg_fetch_array' => [['fetch', 'from' => 0]],
    'pg_fetch_row' => [['fetch', 'from' => 0]],
    'pg_fetch_object' => [['fetch', 'from' => 0]],
    'pg_fetch_all' => [['fetch', 'from' => 0, 'result' => 'rows']],
    'pg_fetch_all_columns' => [['fetch', 'from' => 0, 'result' => 'any_column']],
    'pg_fetch_result' => [['fetch', 'from' => 0, 'result' => 'any_value']],
    // SQLite3
    'sqlite3::query' => [['query', 'sql' => 0]],
    'sqlite3::exec' => [['query', 'sql' => 0, 'result' => 'none']],
    'sqlite3::querysingle' => [['query', 'sql' => 0, 'result' => 'value_or_row']],
    'sqlite3::prepare' => [['prepare', 'sql' => 0]],
    'sqlite3stmt::bindvalue' => [['bind', 'key' => 0, 'value' => 1, 'base' => 1]],
    'sqlite3stmt::bindparam' => [['bind', 'key' => 0, 'value' => 1, 'base' => 1]],
    'sqlite3stmt::execute' => [['fetch']],
    'sqlite3result::fetcharray' => [['fetch']],
];
