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
    'apcu_add' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value=' => 'mixed',
      'ttl=' => 'int',
    ),
    'apcu_cache_info' => 
    array (
      0 => 'array<string, mixed>|false',
      'limited=' => 'bool',
    ),
    'apcu_cas' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'old' => 'int',
      'new' => 'int',
    ),
    'apcu_clear_cache' => 
    array (
      0 => 'bool',
    ),
    'apcu_dec' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'step=' => 'int',
      '&w success=' => 'bool',
      'ttl=' => 'int',
    ),
    'apcu_delete' => 
    array (
      0 => 'bool',
      'key' => 'APCuIterator|string',
    ),
    'apcu_enabled' => 
    array (
      0 => 'bool',
    ),
    'apcu_entry' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'callback' => 'callable[impure](string):mixed',
      'ttl=' => 'int',
    ),
    'apcu_exists' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'apcu_fetch' => 
    array (
      0 => 'false|mixed',
      'key' => 'string',
      '&w success=' => 'bool',
    ),
    'apcu_inc' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'step=' => 'int',
      '&w success=' => 'bool',
      'ttl=' => 'int',
    ),
    'apcu_key_info' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key' => 'string',
    ),
    'apcu_sma_info' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'limited=' => 'bool',
    ),
    'apcu_store' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value=' => 'mixed',
      'ttl=' => 'int',
    ),
    'apcuiterator::__construct' => 
    array (
      0 => 'void',
      'search=' => 'array<array-key, string>|null|string',
      'format=' => 'int',
      'chunk_size=' => 'int',
      'list=' => 'int',
    ),
    'apcuiterator::gettotalcount' => 
    array (
      0 => 'int',
    ),
    'apcuiterator::gettotalhits' => 
    array (
      0 => 'int',
    ),
    'apcuiterator::gettotalsize' => 
    array (
      0 => 'int',
    ),
    'apcuiterator::key' => 
    array (
      0 => 'string',
    ),
    'apcuiterator::next' => 
    array (
      0 => 'void',
    ),
    'apcuiterator::rewind' => 
    array (
      0 => 'void',
    ),
    'apcuiterator::valid' => 
    array (
      0 => 'bool',
    ),
    'ds\\map::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\map::apply' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'ds\\map::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\map::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\map::ksort' => 
    array (
      0 => 'void',
      'comparator=' => 'callable[impure]|null',
    ),
    'ds\\map::put' => 
    array (
      0 => 'void',
      'key' => 'mixed',
      'value' => 'mixed',
    ),
    'ds\\map::putall' => 
    array (
      0 => 'void',
      'values' => 'mixed',
    ),
    'ds\\map::reverse' => 
    array (
      0 => 'void',
    ),
    'ds\\map::sort' => 
    array (
      0 => 'void',
      'comparator=' => 'callable[impure]|null',
    ),
    'ds\\map::sum' => 
    array (
      0 => 'float|int',
    ),
    'ds\\map::union' => 
    array (
      0 => 'Ds\\Map',
      'map' => 'Ds\\Map',
    ),
    'ds\\pair::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\set::add' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\set::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\set::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\set::join' => 
    array (
      0 => 'string',
      'glue=' => 'string',
    ),
    'ds\\set::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\set::remove' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\set::reverse' => 
    array (
      0 => 'void',
    ),
    'ds\\set::sort' => 
    array (
      0 => 'void',
      'comparator=' => 'callable[impure]|null',
    ),
    'ds\\set::sum' => 
    array (
      0 => 'float|int',
    ),
    'ev::backend' => 
    array (
      0 => 'int',
    ),
    'ev::depth' => 
    array (
      0 => 'int',
    ),
    'ev::embeddablebackends' => 
    array (
      0 => 'int',
    ),
    'ev::feedsignal' => 
    array (
      0 => 'void',
      'signum' => 'int',
    ),
    'ev::feedsignalevent' => 
    array (
      0 => 'void',
      'signum' => 'int',
    ),
    'ev::iteration' => 
    array (
      0 => 'int',
    ),
    'ev::now' => 
    array (
      0 => 'float',
    ),
    'ev::nowupdate' => 
    array (
      0 => 'void',
    ),
    'ev::recommendedbackends' => 
    array (
      0 => 'int',
    ),
    'ev::resume' => 
    array (
      0 => 'void',
    ),
    'ev::run' => 
    array (
      0 => 'void',
      'flags=' => 'int',
    ),
    'ev::sleep' => 
    array (
      0 => 'void',
      'seconds' => 'float',
    ),
    'ev::stop' => 
    array (
      0 => 'void',
      'how=' => 'int',
    ),
    'ev::supportedbackends' => 
    array (
      0 => 'int',
    ),
    'ev::suspend' => 
    array (
      0 => 'void',
    ),
    'ev::time' => 
    array (
      0 => 'float',
    ),
    'ev::verify' => 
    array (
      0 => 'void',
    ),
    'evcheck::__construct' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evcheck::clear' => 
    array (
      0 => 'int',
    ),
    'evcheck::createstopped' => 
    array (
      0 => 'EvCheck',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evcheck::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evcheck::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evcheck::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evcheck::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evcheck::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evcheck::start' => 
    array (
      0 => 'void',
    ),
    'evcheck::stop' => 
    array (
      0 => 'void',
    ),
    'evchild::__construct' => 
    array (
      0 => 'void',
      'pid' => 'int',
      'trace' => 'bool',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evchild::clear' => 
    array (
      0 => 'int',
    ),
    'evchild::createstopped' => 
    array (
      0 => 'EvChild',
      'pid' => 'int',
      'trace' => 'bool',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evchild::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evchild::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evchild::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evchild::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evchild::set' => 
    array (
      0 => 'void',
      'pid' => 'int',
      'trace' => 'bool',
    ),
    'evchild::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evchild::start' => 
    array (
      0 => 'void',
    ),
    'evchild::stop' => 
    array (
      0 => 'void',
    ),
    'evembed::__construct' => 
    array (
      0 => 'void',
      'other' => 'EvLoop',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evembed::clear' => 
    array (
      0 => 'int',
    ),
    'evembed::createstopped' => 
    array (
      0 => 'EvEmbed',
      'other' => 'EvLoop',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evembed::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evembed::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evembed::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evembed::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evembed::set' => 
    array (
      0 => 'void',
      'other' => 'EvLoop',
    ),
    'evembed::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evembed::start' => 
    array (
      0 => 'void',
    ),
    'evembed::stop' => 
    array (
      0 => 'void',
    ),
    'evembed::sweep' => 
    array (
      0 => 'void',
    ),
    'evfork::__construct' => 
    array (
      0 => 'void',
      'loop' => 'EvLoop',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evfork::clear' => 
    array (
      0 => 'int',
    ),
    'evfork::createstopped' => 
    array (
      0 => 'EvFork',
      'loop' => 'EvLoop',
      'callback' => 'callable[impure]',
      'data=' => 'string',
      'priority=' => 'int',
    ),
    'evfork::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evfork::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evfork::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evfork::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evfork::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evfork::start' => 
    array (
      0 => 'void',
    ),
    'evfork::stop' => 
    array (
      0 => 'void',
    ),
    'evidle::__construct' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evidle::clear' => 
    array (
      0 => 'int',
    ),
    'evidle::createstopped' => 
    array (
      0 => 'EvIdle',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evidle::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evidle::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evidle::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evidle::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evidle::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evidle::start' => 
    array (
      0 => 'void',
    ),
    'evidle::stop' => 
    array (
      0 => 'void',
    ),
    'evio::__construct' => 
    array (
      0 => 'void',
      'fd' => 'mixed',
      'events' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evio::clear' => 
    array (
      0 => 'int',
    ),
    'evio::createstopped' => 
    array (
      0 => 'EvIo',
      'fd' => 'resource',
      'events' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evio::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evio::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evio::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evio::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evio::set' => 
    array (
      0 => 'void',
      'fd' => 'resource',
      'events' => 'int',
    ),
    'evio::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evio::start' => 
    array (
      0 => 'void',
    ),
    'evio::stop' => 
    array (
      0 => 'void',
    ),
    'evloop::__construct' => 
    array (
      0 => 'void',
      'flags=' => 'int',
      'data=' => 'mixed',
      'io_interval=' => 'float',
      'timeout_interval=' => 'float',
    ),
    'evloop::backend' => 
    array (
      0 => 'int',
    ),
    'evloop::child' => 
    array (
      0 => 'EvChild',
      'pid' => 'int',
      'trace' => 'bool',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::defaultloop' => 
    array (
      0 => 'EvLoop',
      'flags=' => 'int',
      'data=' => 'mixed',
      'io_interval=' => 'float',
      'timeout_interval=' => 'float',
    ),
    'evloop::embed' => 
    array (
      0 => 'EvEmbed',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::fork' => 
    array (
      0 => 'EvFork',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::idle' => 
    array (
      0 => 'EvIdle',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::invokepending' => 
    array (
      0 => 'void',
    ),
    'evloop::io' => 
    array (
      0 => 'EvIo',
      'fd' => 'resource',
      'events' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::loopfork' => 
    array (
      0 => 'void',
    ),
    'evloop::now' => 
    array (
      0 => 'float',
    ),
    'evloop::nowupdate' => 
    array (
      0 => 'void',
    ),
    'evloop::periodic' => 
    array (
      0 => 'EvPeriodic',
      'offset' => 'float',
      'interval' => 'float',
      'reschedule_cb' => 'callable[impure]',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::prepare' => 
    array (
      0 => 'EvPrepare',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::resume' => 
    array (
      0 => 'void',
    ),
    'evloop::run' => 
    array (
      0 => 'void',
      'flags=' => 'int',
    ),
    'evloop::signal' => 
    array (
      0 => 'EvSignal',
      'signum' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::stat' => 
    array (
      0 => 'EvStat',
      'path' => 'string',
      'interval' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::stop' => 
    array (
      0 => 'void',
      'how=' => 'int',
    ),
    'evloop::suspend' => 
    array (
      0 => 'void',
    ),
    'evloop::timer' => 
    array (
      0 => 'EvTimer',
      'after' => 'float',
      'repeat' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evloop::verify' => 
    array (
      0 => 'void',
    ),
    'evperiodic::__construct' => 
    array (
      0 => 'void',
      'offset' => 'float',
      'interval' => 'float',
      'reschedule_cb' => 'callable[impure]',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evperiodic::again' => 
    array (
      0 => 'void',
    ),
    'evperiodic::at' => 
    array (
      0 => 'float',
    ),
    'evperiodic::clear' => 
    array (
      0 => 'int',
    ),
    'evperiodic::createstopped' => 
    array (
      0 => 'EvPeriodic',
      'offset' => 'float',
      'interval' => 'float',
      'reschedule_cb' => 'callable[impure]',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evperiodic::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evperiodic::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evperiodic::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evperiodic::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evperiodic::set' => 
    array (
      0 => 'void',
      'offset' => 'float',
      'interval' => 'float',
      'reschedule_cb=' => 'mixed',
    ),
    'evperiodic::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evperiodic::start' => 
    array (
      0 => 'void',
    ),
    'evperiodic::stop' => 
    array (
      0 => 'void',
    ),
    'evprepare::__construct' => 
    array (
      0 => 'void',
      'callback' => 'string',
      'data=' => 'string',
      'priority=' => 'int',
    ),
    'evprepare::clear' => 
    array (
      0 => 'int',
    ),
    'evprepare::createstopped' => 
    array (
      0 => 'EvPrepare',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evprepare::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evprepare::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evprepare::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evprepare::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evprepare::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evprepare::start' => 
    array (
      0 => 'void',
    ),
    'evprepare::stop' => 
    array (
      0 => 'void',
    ),
    'evsignal::__construct' => 
    array (
      0 => 'void',
      'signum' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evsignal::clear' => 
    array (
      0 => 'int',
    ),
    'evsignal::createstopped' => 
    array (
      0 => 'EvSignal',
      'signum' => 'int',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evsignal::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evsignal::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evsignal::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evsignal::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evsignal::set' => 
    array (
      0 => 'void',
      'signum' => 'int',
    ),
    'evsignal::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evsignal::start' => 
    array (
      0 => 'void',
    ),
    'evsignal::stop' => 
    array (
      0 => 'void',
    ),
    'evstat::__construct' => 
    array (
      0 => 'void',
      'path' => 'string',
      'interval' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evstat::attr' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'evstat::clear' => 
    array (
      0 => 'int',
    ),
    'evstat::createstopped' => 
    array (
      0 => 'EvStat',
      'path' => 'string',
      'interval' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evstat::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evstat::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evstat::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evstat::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evstat::prev' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'evstat::set' => 
    array (
      0 => 'void',
      'path' => 'string',
      'interval' => 'float',
    ),
    'evstat::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evstat::start' => 
    array (
      0 => 'void',
    ),
    'evstat::stat' => 
    array (
      0 => 'bool',
    ),
    'evstat::stop' => 
    array (
      0 => 'void',
    ),
    'evtimer::__construct' => 
    array (
      0 => 'void',
      'after' => 'float',
      'repeat' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evtimer::again' => 
    array (
      0 => 'void',
    ),
    'evtimer::clear' => 
    array (
      0 => 'int',
    ),
    'evtimer::createstopped' => 
    array (
      0 => 'EvTimer',
      'after' => 'float',
      'repeat' => 'float',
      'callback' => 'callable[impure]',
      'data=' => 'mixed',
      'priority=' => 'int',
    ),
    'evtimer::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evtimer::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evtimer::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evtimer::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evtimer::set' => 
    array (
      0 => 'void',
      'after' => 'float',
      'repeat' => 'float',
    ),
    'evtimer::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evtimer::start' => 
    array (
      0 => 'void',
    ),
    'evtimer::stop' => 
    array (
      0 => 'void',
    ),
    'evwatcher::clear' => 
    array (
      0 => 'int',
    ),
    'evwatcher::feed' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evwatcher::getloop' => 
    array (
      0 => 'EvLoop|null',
    ),
    'evwatcher::invoke' => 
    array (
      0 => 'void',
      'revents' => 'int',
    ),
    'evwatcher::keepalive' => 
    array (
      0 => 'bool',
      'value=' => 'bool',
    ),
    'evwatcher::setcallback' => 
    array (
      0 => 'void',
      'callback' => 'callable[impure]',
    ),
    'evwatcher::start' => 
    array (
      0 => 'void',
    ),
    'evwatcher::stop' => 
    array (
      0 => 'void',
    ),
  ),
);