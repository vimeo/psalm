<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
  ),
  'changed' => 
  array (
    'dateinterval::__set_state' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval',
        'array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'static',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'datetimezone::__set_state' => 
    array (
      'old' => 
      array (
        0 => 'DateTimeZone',
        'array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'static',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'filter_var_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'array' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'array' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
    ),
    'header_register_callback' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'callback' => 'callable[impure]():void',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable[impure]():void',
      ),
    ),
    'intlbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'int',
      ),
    ),
    'intlcodepointbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'int',
      ),
    ),
    'intlrulebasedbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'int',
      ),
    ),
    'openssl_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        'tag=' => 'null|string',
        'aad=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        'tag=' => 'null|string',
        'aad=' => 'null|string',
      ),
    ),
    'openssl_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        '&w tag=' => 'string',
        'aad=' => 'string',
        'tag_length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        '&w tag=' => 'string',
        'aad=' => 'null|string',
        'tag_length=' => 'int',
      ),
    ),
    'openssl_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w signature' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w signature' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
        'salt_length=' => 'int',
      ),
    ),
    'openssl_verify' => 
    array (
      'old' => 
      array (
        0 => '-1|0|1|false',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => '-1|0|1|false',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
        'salt_length=' => 'int',
      ),
    ),
    'pcntl_signal' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'signal' => 'int',
        'handler' => 'callable[impure]():void|callable[impure](int):void|callable[impure](int, array<array-key, mixed>):void|int',
        'restart_syscalls=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'signal' => 'int',
        'handler' => 'callable[impure]():void|callable[impure](int):void|callable[impure](int, array<array-key, mixed>):void|int',
        'restart_syscalls=' => 'bool|null',
      ),
    ),
    'readline_completion_function' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'callback' => 'callable[impure](string):array<array-key, string>',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable[impure](string):array<array-key, string>',
      ),
    ),
    'register_tick_function' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'callback' => 'callable[impure]():void',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable[impure]():void',
        '...args=' => 'mixed',
      ),
    ),
    'stream_copy_to_stream' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'from' => 'resource',
        'to' => 'resource',
        'length=' => 'int|null',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'from' => 'resource',
        'to' => 'resource',
        'length=' => 'int|null',
        'offset=' => 'int',
        'context=' => 'mixed',
      ),
    ),
    'stream_is_local' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource|string',
        'context=' => 'mixed',
      ),
    ),
    'stream_select' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        '&read' => 'array<array-key, resource>|null',
        '&write' => 'array<array-key, resource>|null',
        '&except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'false|int',
        '&read' => 'array<array-key, resource>|null',
        '&write' => 'array<array-key, resource>|null',
        '&except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int|null',
        'context=' => 'mixed',
      ),
    ),
    'stream_socket_pair' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, resource>|false',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, resource>|false',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
        'context=' => 'mixed',
      ),
    ),
    'zip_entry_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'zip_entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'true',
        'zip_entry' => 'resource',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);