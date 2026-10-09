<?php

declare(strict_types=1);

/**
 * The builtin functions and methods only declared by the call map that store data under a key, or read it back: as
 * `@psalm-flow ($value) -> Store::$data[$key]` and `@psalm-flow Store::$data[$key] -> return` do for the builtins of
 * the stubs (see TaintStore). The store is named by a class and a property, which need not exist.
 *
 * @var array<lowercase-string, non-empty-list<non-empty-string>>
 */
return [
    'apcu_add' => ['($value) -> APCu::$data[$key]'],
    'apcu_entry' => ['APCu::$data[$key] -> return'],
    'apcu_fetch' => ['APCu::$data[$key] -> return'],
    'apcu_store' => ['($value) -> APCu::$data[$key]'],
];
