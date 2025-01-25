<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'mb_str_split' => 
    array (
      0 => 'false|list<string>',
      'str' => 'string',
      'split_length=' => 'int<1, max>',
      'encoding=' => 'string',
    ),
    'openssl_x509_verify' => 
    array (
      0 => 'int',
      'cert' => 'resource|string',
      'key' => 'array<array-key, mixed>|resource|string',
    ),
    'reflectionproperty::gettype' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'reflectionproperty::isinitialized' => 
    array (
      0 => 'bool',
      'object' => 'object',
    ),
  ),
  'changed' => 
  array (
    'array_merge' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
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
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
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
        'fp' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length' => 'int',
      ),
    ),
    'imagecopymerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'src_im' => 'resource',
        'dst_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
    ),
    'imagecopymergegray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'src_im' => 'resource',
        'dst_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
    ),
    'locale::lookup' => 
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
    'locale_lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'def=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'def=' => 'null|string',
      ),
    ),
    'openssl_random_pseudo_bytes' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'length' => 'int',
        '&w_result_is_strong=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w_result_is_strong=' => 'bool',
      ),
    ),
    'pack' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        '...args' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        '...args=' => 'mixed',
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
        'regex' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'regex' => 'array<array-key, mixed>|string',
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
        'descriptorspec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env=' => 'array<array-key, mixed>|null',
        'other_options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'command' => 'array<array-key, mixed>|string',
        'descriptorspec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env=' => 'array<array-key, mixed>|null',
        'other_options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'splfileobject::fwrite' => 
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
    'spltempfileobject::fwrite' => 
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
    'stream_context_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream_or_context' => 'mixed',
        'wrappername' => 'string',
        'optionname' => 'string',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream_or_context' => 'mixed',
        'wrappername' => 'string',
        'optionname=' => 'string',
        'value=' => 'mixed',
      ),
    ),
    'strip_tags' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'allowable_tags=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
        'allowable_tags=' => 'list<non-empty-string>|string',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);