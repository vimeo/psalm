<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'sodium_crypto_aead_aes256gcm_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
  ),
  'changed' => 
  array (
    'collator::setstrength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'strength' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'strength' => 'int',
      ),
    ),
    'collator_set_strength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'strength' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'object' => 'collator',
        'strength' => 'int',
      ),
    ),
    'dateinterval::createfromdatestring' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval|false',
        'datetime' => 'string',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'datetime' => 'string',
      ),
    ),
    'domdocument::registernodeclass' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'baseClass' => 'string',
        'extendedClass' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'true',
        'baseClass' => 'string',
        'extendedClass' => 'null|string',
      ),
    ),
    'domimplementation::createdocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
      ),
      'new' => 
      array (
        0 => 'DOMDocument',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
      ),
    ),
    'finfo::set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags' => 'int',
      ),
    ),
    'finfo_set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'finfo',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'finfo' => 'finfo',
        'flags' => 'int',
      ),
    ),
    'hash_update' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'HashContext',
        'data' => 'string',
      ),
    ),
    'highlight_string' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'string' => 'string',
        'return=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string|true',
        'string' => 'string',
        'return=' => 'bool',
      ),
    ),
    'imagick::convolveimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'kernel' => 'array<array-key, mixed>',
        'channel=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'kernel' => 'ImagickKernel',
        'channel=' => 'int',
      ),
    ),
    'imagick::evaluateimages' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'evaluate' => 'int',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'evaluate' => 'int',
      ),
    ),
    'imagick::getimageblob' => 
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
    'imagick::getregistry' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
    ),
    'imagick::getresourcelimit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'type' => 'int',
      ),
      'new' => 
      array (
        0 => 'float',
        'type' => 'int',
      ),
    ),
    'imagick::localcontrastimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'radius' => 'float',
        'strength' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'radius' => 'float',
        'strength' => 'float',
      ),
    ),
    'imagick::newimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'background_color' => 'ImagickPixel|string',
        'format=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'background_color' => 'ImagickPixel|string',
        'format=' => 'null|string',
      ),
    ),
    'imagick::optimizeimagelayers' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'Imagick',
      ),
    ),
    'imagick::similarityimage' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&offset=' => 'array<array-key, mixed>|null',
        '&similarity=' => 'float|null',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&offset=' => 'array<array-key, mixed>',
        '&similarity=' => 'float',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
    ),
    'imagick::subimagematch' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w_offset=' => 'array<array-key, mixed>|null',
        '&w_similarity=' => 'float|null',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w_offset=' => 'array<array-key, mixed>',
        '&w_similarity=' => 'float',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
    ),
    'imagickdraw::getclippath' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
      ),
    ),
    'imagickkernel::frommatrix' => 
    array (
      'old' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin=' => 'array<array-key, mixed>|null',
      ),
    ),
    'imagickpixel::ispixelsimilar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
    ),
    'imagickpixel::ispixelsimilarquantum' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'string',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'string',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
      ),
    ),
    'imagickpixel::issimilar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'ImagickPixel',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'ImagickPixel',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
      ),
    ),
    'imagickpixeliterator::getcurrentiteratorrow' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
    ),
    'imagickpixeliterator::getnextiteratorrow' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
    ),
    'intlcalendar::clear' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => 'int|null',
      ),
    ),
    'intlcalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
    ),
    'intlcalendar::setfirstdayofweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dayOfWeek' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dayOfWeek' => 'int',
      ),
    ),
    'intlcalendar::setminimaldaysinfirstweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'days' => 'int',
      ),
    ),
    'intlgregoriancalendar::clear' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'field=' => 'int|null',
      ),
    ),
    'intlgregoriancalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
    ),
    'intlgregoriancalendar::setfirstdayofweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dayOfWeek' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dayOfWeek' => 'int',
      ),
    ),
    'intlgregoriancalendar::setminimaldaysinfirstweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'days' => 'int',
      ),
    ),
    'locale::setdefault' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'locale' => 'string',
      ),
    ),
    'locale_set_default' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'locale' => 'string',
      ),
    ),
    'openssl_csr_sign' => 
    array (
      'old' => 
      array (
        0 => 'OpenSSLCertificate|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'ca_certificate' => 'OpenSSLCertificate|null|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'days' => 'int',
        'options=' => 'array<array-key, mixed>|null',
        'serial=' => 'int',
      ),
      'new' => 
      array (
        0 => 'OpenSSLCertificate|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'ca_certificate' => 'OpenSSLCertificate|null|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'days' => 'int',
        'options=' => 'array<array-key, mixed>|null',
        'serial=' => 'int',
        'serial_hex=' => 'null|string',
      ),
    ),
    'pdostatement::setfetchmode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mode' => 'int',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'mode' => 'int',
        '...args=' => 'mixed',
      ),
    ),
    'pg_select' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'conditions=' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'phar::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'phar::decompressfiles' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'phar::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'localName' => 'string',
      ),
    ),
    'phar::delmetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'phar::setalias' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'alias' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'alias' => 'string',
      ),
    ),
    'phar::setdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'true',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
    ),
    'phar::setstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'phar::unlinkarchive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'filename' => 'string',
      ),
    ),
    'phardata::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'phardata::decompressfiles' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'phardata::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'localName' => 'string',
      ),
    ),
    'phardata::delmetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'phardata::setstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'pharfileinfo::compress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'compression' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'compression' => 'int',
      ),
    ),
    'pharfileinfo::decompress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'pharfileinfo::delmetadata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'resourcebundle::get' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle_get' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'bundle' => 'ResourceBundle',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'bundle' => 'ResourceBundle',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
    ),
    'splfixedarray::setsize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'size' => 'int',
      ),
    ),
    'splheap::insert' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'value' => 'mixed',
      ),
    ),
    'splpriorityqueue::insert' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
        'priority' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'value' => 'mixed',
        'priority' => 'mixed',
      ),
    ),
    'splpriorityqueue::recoverfromcorruption' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'sqlite3result::finalize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'sqlite3stmt::close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'stream_bucket_append' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'StreamBucket',
      ),
    ),
    'stream_bucket_make_writeable' => 
    array (
      'old' => 
      array (
        0 => 'null|object',
        'brigade' => 'resource',
      ),
      'new' => 
      array (
        0 => 'StreamBucket|null',
        'brigade' => 'resource',
      ),
    ),
    'stream_bucket_new' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'stream' => 'resource',
        'buffer' => 'string',
      ),
      'new' => 
      array (
        0 => 'StreamBucket',
        'stream' => 'resource',
        'buffer' => 'string',
      ),
    ),
    'stream_bucket_prepend' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'brigade' => 'resource',
        'bucket' => 'StreamBucket',
      ),
    ),
    'stream_context_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'mixed',
        'wrapper_or_options' => 'string',
        'option_name=' => 'null|string',
        'value=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'mixed',
        'wrapper_or_options' => 'string',
        'option_name=' => 'null|string',
        'value=' => 'mixed',
      ),
    ),
    'stream_context_set_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'params' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        'context' => 'resource',
        'params' => 'array<array-key, mixed>',
      ),
    ),
    'trigger_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => '256|512|1024|16384',
      ),
      'new' => 
      array (
        0 => 'true',
        'message' => 'string',
        'error_level=' => '256|512|1024|16384',
      ),
    ),
    'user_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'message' => 'string',
        'error_level=' => 'int',
      ),
    ),
    'xml_set_character_data_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_default_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_element_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable',
        'end_handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable|null',
        'end_handler' => 'callable|null',
      ),
    ),
    'xml_set_end_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_external_entity_ref_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_notation_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_processing_instruction_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_start_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xml_set_unparsed_entity_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable|null',
      ),
    ),
    'xmlreader::close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);