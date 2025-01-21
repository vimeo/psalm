<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
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
    'amqpbasicproperties::getappid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getclusterid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getcontentencoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getcontenttype' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getcorrelationid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getexpiration' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getmessageid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getreplyto' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::gettimestamp' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'amqpbasicproperties::gettype' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpbasicproperties::getuserid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpconnection::getcacert' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpconnection::getcert' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpconnection::getkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getappid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getclusterid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getconsumertag' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getcontentencoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getcontenttype' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getcorrelationid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getdeliverytag' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'amqpenvelope::getexchangename' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getexpiration' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getmessageid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getreplyto' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::gettimestamp' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'amqpenvelope::gettype' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpenvelope::getuserid' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpexchange::getname' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpexchange::gettype' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'amqpqueue::get' => 
    array (
      'old' => 
      array (
        0 => 'AMQPEnvelope|false',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'AMQPEnvelope|null',
        'flags=' => 'int',
      ),
    ),
    'amqpqueue::getname' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
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