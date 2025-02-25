<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'datetimeinterface::__serialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'datetimeinterface::__unserialize' => 
    array (
      0 => 'void',
      'data' => 'array<array-key, mixed>',
    ),
    'imap_is_open' => 
    array (
      0 => 'bool',
      'imap' => 'IMAP\\Connection',
    ),
    'ini_parse_quantity' => 
    array (
      0 => 'int',
      'shorthand' => 'non-empty-string',
    ),
    'libxml_get_external_entity_loader' => 
    array (
      0 => 'callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
    ),
    'mysqli::execute_query' => 
    array (
      0 => 'bool|mysqli_result',
      'query' => 'non-empty-string',
      'params=' => 'list<mixed>|null',
    ),
    'mysqli_execute_query' => 
    array (
      0 => 'bool|mysqli_result',
      'mysql' => 'mysqli',
      'query' => 'non-empty-string',
      'params=' => 'list<mixed>|null',
    ),
    'openssl_cipher_key_length' => 
    array (
      0 => 'false|int<1, max>',
      'cipher_algo' => 'non-empty-string',
    ),
    'sodium_crypto_stream_xchacha20_xor_ic' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'non-empty-string',
      'counter' => 'int',
      'key' => 'non-empty-string',
    ),
    'ziparchive::getstreamindex' => 
    array (
      0 => 'false|resource',
      'index' => 'int',
      'flags=' => 'int',
    ),
    'ziparchive::getstreamname' => 
    array (
      0 => 'false|resource',
      'name' => 'string',
      'flags=' => 'int',
    ),
  ),
  'changed' => 
  array (
    'array_walk' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'array_walk_recursive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'dba_open' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'string',
        '...handler_params=' => 'string',
      ),
      'new' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'dba_popen' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'string',
        '...handler_params=' => 'string',
      ),
      'new' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'iterator_count' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable|array<array-key, mixed>',
      ),
    ),
    'iterator_to_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'preserve_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable|array<array-key, mixed>',
        'preserve_keys=' => 'bool',
      ),
    ),
    'mb_get_info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|int|string',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|int|null|string',
        'type=' => 'string',
      ),
    ),
    'passthru' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
    ),
    'register_shutdown_function' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'callback' => 'callable',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback' => 'callable',
        '...args=' => 'mixed',
      ),
    ),
    'str_split' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-list<string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
      ),
      'new' => 
      array (
        0 => 'list<non-empty-string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
      ),
    ),
    'strcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strnatcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strnatcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strncasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int<0, max>',
      ),
    ),
    'strncmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int<0, max>',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);