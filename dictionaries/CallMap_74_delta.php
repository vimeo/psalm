<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'ReflectionProperty::getType' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'ReflectionProperty::isInitialized' => 
    array (
      0 => 'bool',
      'object' => 'object',
    ),
    'mb_str_split' => 
    array (
      0 => 'false|list<string>',
      'string' => 'string',
      'length=' => 'int<1, max>',
      'encoding=' => 'string',
    ),
    'openssl_x509_verify' => 
    array (
      0 => 'int',
      'certificate' => 'resource|string',
      'public_key' => 'array<array-key, mixed>|resource|string',
    ),
  ),
  'changed' => 
  array (
    'Locale::lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'languageTag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'defaultLocale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'languageTag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'defaultLocale=' => 'null|string',
      ),
    ),
    'SplFileObject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
    ),
    'SplTempFileObject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
    ),
    'array_merge' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_merge_recursive' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'gzread' => 
    array (
      'old' => 
      array (
        0 => '0|string',
        'stream' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length' => 'int',
      ),
    ),
    'locale_lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'languageTag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'defaultLocale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'languageTag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'defaultLocale=' => 'null|string',
      ),
    ),
    'openssl_random_pseudo_bytes' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'length' => 'int',
        '&w_strong_result=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w_strong_result=' => 'bool',
      ),
    ),
    'password_hash' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'algo' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'password_needs_rehash' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'hash' => 'string',
        'algo' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hash' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'preg_replace_callback' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'proc_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'command' => 'string',
        'descriptor_spec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env_vars=' => 'array<array-key, mixed>|null',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'command' => 'array<array-key, mixed>|string',
        'descriptor_spec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env_vars=' => 'array<array-key, mixed>|null',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'strip_tags' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'allowed_tags=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'allowed_tags=' => 'list<non-empty-string>|string',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);