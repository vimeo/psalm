<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'DateTime::createFromImmutable' => 
    array (
      0 => 'static',
      'object' => 'DateTimeImmutable',
    ),
    'JsonException::__clone' => 
    array (
      0 => 'void',
    ),
    'JsonException::__construct' => 
    array (
      0 => 'void',
      'message=' => 'string',
      'code=' => 'int',
      'previous=' => 'Throwable|null',
    ),
    'JsonException::__toString' => 
    array (
      0 => 'string',
    ),
    'JsonException::__wakeup' => 
    array (
      0 => 'void',
    ),
    'JsonException::getCode' => 
    array (
      0 => 'int',
    ),
    'JsonException::getFile' => 
    array (
      0 => 'string',
    ),
    'JsonException::getLine' => 
    array (
      0 => 'int',
    ),
    'JsonException::getMessage' => 
    array (
      0 => 'string',
    ),
    'JsonException::getPrevious' => 
    array (
      0 => 'Throwable|null',
    ),
    'JsonException::getTrace' => 
    array (
      0 => 'list<array{args?: array<array-key, mixed>, class?: class-string, file?: string, function: string, line?: int, type?: \'->\'|\'::\'}>',
    ),
    'JsonException::getTraceAsString' => 
    array (
      0 => 'string',
    ),
    'Normalizer::getRawDecomposition' => 
    array (
      0 => 'null|string',
      'string' => 'string',
      'form=' => 'int',
    ),
    'SplPriorityQueue::isCorrupted' => 
    array (
      0 => 'bool',
    ),
    'array_key_first' => 
    array (
      0 => 'int|null|string',
      'array' => 'array<array-key, mixed>',
    ),
    'array_key_last' => 
    array (
      0 => 'int|null|string',
      'array' => 'array<array-key, mixed>',
    ),
    'fpm_get_status' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'gc_status' => 
    array (
      0 => 'array{collected: int, roots: int, runs: int, threshold: int}',
    ),
    'gmp_binomial' => 
    array (
      0 => 'GMP|false',
      'n' => 'GMP|int|string',
      'k' => 'int',
    ),
    'gmp_kronecker' => 
    array (
      0 => 'int',
      'num1' => 'GMP|int|string',
      'num2' => 'GMP|int|string',
    ),
    'gmp_lcm' => 
    array (
      0 => 'GMP',
      'num1' => 'GMP|int|string',
      'num2' => 'GMP|int|string',
    ),
    'gmp_perfect_power' => 
    array (
      0 => 'bool',
      'num' => 'GMP|int|string',
    ),
    'hrtime' => 
    array (
      0 => 'array{0: int, 1: int}|false',
      'as_number=' => 'false',
    ),
    'hrtime\'1' => 
    array (
      0 => 'false|float|int',
      'as_number=' => 'true',
    ),
    'is_countable' => 
    array (
      0 => 'bool',
      'value' => 'mixed',
    ),
    'normalizer_get_raw_decomposition' => 
    array (
      0 => 'null|string',
      'string' => 'string',
      'form=' => 'int',
    ),
    'net_get_interfaces' => 
    array (
      0 => 'array<string, array<string, mixed>>|false',
    ),
    'openssl_pkey_derive' => 
    array (
      0 => 'false|string',
      'public_key' => 'mixed',
      'private_key' => 'mixed',
      'key_length=' => 'int|null',
    ),
    'session_set_cookie_params\'1' => 
    array (
      0 => 'bool',
      'options' => 'array{domain?: null|string, httponly?: bool|null, lifetime?: int|null, path?: null|string, samesite?: null|string, secure?: bool|null}',
    ),
    'setcookie\'1' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value=' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'setrawcookie\'1' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value=' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'socket_wsaprotocol_info_export' => 
    array (
      0 => 'false|string',
      'socket' => 'resource',
      'process_id' => 'int',
    ),
    'socket_wsaprotocol_info_import' => 
    array (
      0 => 'false|resource',
      'info_id' => 'string',
    ),
    'socket_wsaprotocol_info_release' => 
    array (
      0 => 'bool',
      'info_id' => 'string',
    ),
  ),
  'changed' => 
  array (
    'array_push' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&rw_array' => 'array<array-key, mixed>',
        '...values' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&rw_array' => 'array<array-key, mixed>',
        '...values=' => 'mixed',
      ),
    ),
    'array_unshift' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&rw_array' => 'array<array-key, mixed>',
        '...values' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&rw_array' => 'array<array-key, mixed>',
        '...values=' => 'mixed',
      ),
    ),
    'bcscale' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'scale' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'scale=' => 'int',
      ),
    ),
    'define' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'constant_name' => 'string',
        'value' => 'array<array-key, mixed>|null|scalar',
        'case_insensitive=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'constant_name' => 'string',
        'value' => 'array<array-key, mixed>|null|scalar',
        'case_insensitive=' => 'false',
      ),
    ),
    'ldap_compare' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_exop_passwd' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_list' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_mod_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_mod_del' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_mod_replace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_modify' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_modify_batch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_read' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_search' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, resource>|false|resource',
        'ldap' => 'array<array-key, resource>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, resource>|false|resource',
        'ldap' => 'array<array-key, resource>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'mkdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'permissions=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'permissions=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'session_get_cookie_params' => 
    array (
      'old' => 
      array (
        0 => 'array{domain: null|string, httponly: bool|null, lifetime: int|null, path: null|string, secure: bool|null}',
      ),
      'new' => 
      array (
        0 => 'array{domain: null|string, httponly: bool|null, lifetime: int|null, path: null|string, samesite: null|string, secure: bool|null}',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);