<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'array_find_key' => 
    array (
      0 => 'array-key|null',
      'array' => 'array<array-key, mixed>',
      'callback' => 'impure-callable',
    ),
    'sodium_crypto_aead_aes256gcm_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
  ),
  'changed' => 
  array (
    'Collator::setStrength' => 
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
        'object' => 'Collator',
        'strength' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'object' => 'Collator',
        'strength' => 'int',
      ),
    ),
    'DateInterval::createFromDateString' => 
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
    'DOMDocument::registerNodeClass' => 
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
    'DOMImplementation::createDocument' => 
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
    'idn_to_ascii' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w idna_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'domain' => 'non-empty-string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w idna_info=' => 'array<array-key, mixed>',
      ),
    ),
    'idn_to_utf8' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w idna_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'domain' => 'non-empty-string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w idna_info=' => 'array<array-key, mixed>',
      ),
    ),
    'Imagick::getRegistry' => 
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
    'Imagick::similarityImage' => 
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
    'Imagick::subimageMatch' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w offset=' => 'array<array-key, mixed>|null',
        '&w similarity=' => 'float|null',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w offset=' => 'array<array-key, mixed>',
        '&w similarity=' => 'float',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
    ),
    'ImagickDraw::getClipPath' => 
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
    'IntlCalendar::clear' => 
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
    'IntlCalendar::set' => 
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
    'IntlCalendar::setFirstDayOfWeek' => 
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
    'IntlCalendar::setMinimalDaysInFirstWeek' => 
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
    'IntlGregorianCalendar::clear' => 
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
    'IntlGregorianCalendar::set' => 
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
    'IntlGregorianCalendar::setFirstDayOfWeek' => 
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
    'IntlGregorianCalendar::setMinimalDaysInFirstWeek' => 
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
    'Locale::setDefault' => 
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
    'PDOStatement::setFetchMode' => 
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
    'Phar::copy' => 
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
    'Phar::decompressFiles' => 
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
    'Phar::delete' => 
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
    'Phar::delMetadata' => 
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
    'Phar::setAlias' => 
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
    'Phar::setDefaultStub' => 
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
    'Phar::setStub' => 
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
    'Phar::unlinkArchive' => 
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
    'PharData::copy' => 
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
    'PharData::decompressFiles' => 
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
    'PharData::delete' => 
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
    'PharData::delMetadata' => 
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
    'PharData::setStub' => 
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
    'PharFileInfo::compress' => 
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
    'PharFileInfo::decompress' => 
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
    'PharFileInfo::delMetadata' => 
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
    'ResourceBundle::get' => 
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
    'round' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'num' => 'float|int',
        'precision=' => 'int',
        'mode=' => 'int<0, max>',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float|int',
        'precision=' => 'int',
        'mode=' => 'RoundingMode|int<0, max>',
      ),
    ),
    'SplFixedArray::setSize' => 
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
    'SplHeap::insert' => 
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
    'SplPriorityQueue::insert' => 
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
    'SplPriorityQueue::recoverFromCorruption' => 
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
    'SQLite3Result::finalize' => 
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
    'SQLite3Stmt::close' => 
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
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_default_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_element_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'impure-callable',
        'end_handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'impure-callable|null',
        'end_handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_end_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_external_entity_ref_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_notation_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_processing_instruction_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_start_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'xml_set_unparsed_entity_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'impure-callable|null',
      ),
    ),
    'XMLReader::close' => 
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