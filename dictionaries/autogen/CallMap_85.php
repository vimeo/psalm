<?php // phpcs:ignoreFile

return array (
  'clone' => 
  array (
    0 => 'object',
    'object' => 'object',
    'withProperties=' => 'array',
  ),
  'exit' => 
  array (
    0 => 'never',
    'status=' => 'string|int',
  ),
  'die' => 
  array (
    0 => 'never',
    'status=' => 'string|int',
  ),
  'zend_version' => 
  array (
    0 => 'string',
  ),
  'func_num_args' => 
  array (
    0 => 'int',
  ),
  'func_get_arg' => 
  array (
    0 => 'mixed',
    'position' => 'int',
  ),
  'func_get_args' => 
  array (
    0 => 'array',
  ),
  'strlen' => 
  array (
    0 => 'int',
    'string' => 'string',
  ),
  'strcmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strncmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    'length' => 'int',
  ),
  'strcasecmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strncasecmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    'length' => 'int',
  ),
  'error_reporting' => 
  array (
    0 => 'int',
    'error_level=' => 'int|null',
  ),
  'define' => 
  array (
    0 => 'bool',
    'constant_name' => 'string',
    'value' => 'mixed',
    'case_insensitive=' => 'bool',
  ),
  'defined' => 
  array (
    0 => 'bool',
    'constant_name' => 'string',
  ),
  'get_class' => 
  array (
    0 => 'string',
    'object=' => 'object',
  ),
  'get_called_class' => 
  array (
    0 => 'string',
  ),
  'get_parent_class' => 
  array (
    0 => 'string|false',
    'object_or_class=' => 'object|string',
  ),
  'is_subclass_of' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'class' => 'string',
    'allow_string=' => 'bool',
  ),
  'is_a' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'class' => 'string',
    'allow_string=' => 'bool',
  ),
  'get_class_vars' => 
  array (
    0 => 'array',
    'class' => 'string',
  ),
  'get_object_vars' => 
  array (
    0 => 'array',
    'object' => 'object',
  ),
  'get_mangled_object_vars' => 
  array (
    0 => 'array',
    'object' => 'object',
  ),
  'get_class_methods' => 
  array (
    0 => 'array',
    'object_or_class' => 'object|string',
  ),
  'method_exists' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'method' => 'string',
  ),
  'property_exists' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'property' => 'string',
  ),
  'class_exists' => 
  array (
    0 => 'bool',
    'class' => 'string',
    'autoload=' => 'bool',
  ),
  'interface_exists' => 
  array (
    0 => 'bool',
    'interface' => 'string',
    'autoload=' => 'bool',
  ),
  'trait_exists' => 
  array (
    0 => 'bool',
    'trait' => 'string',
    'autoload=' => 'bool',
  ),
  'enum_exists' => 
  array (
    0 => 'bool',
    'enum' => 'string',
    'autoload=' => 'bool',
  ),
  'function_exists' => 
  array (
    0 => 'bool',
    'function' => 'string',
  ),
  'class_alias' => 
  array (
    0 => 'bool',
    'class' => 'string',
    'alias' => 'string',
    'autoload=' => 'bool',
  ),
  'get_included_files' => 
  array (
    0 => 'array',
  ),
  'get_required_files' => 
  array (
    0 => 'array',
  ),
  'trigger_error' => 
  array (
    0 => 'true',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'user_error' => 
  array (
    0 => 'true',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'set_error_handler' => 
  array (
    0 => 'mixed',
    'callback' => 'callable|null',
    'error_levels=' => 'int',
  ),
  'restore_error_handler' => 
  array (
    0 => 'true',
  ),
  'get_error_handler' => 
  array (
    0 => 'callable|null',
  ),
  'set_exception_handler' => 
  array (
    0 => 'mixed',
    'callback' => 'callable|null',
  ),
  'restore_exception_handler' => 
  array (
    0 => 'true',
  ),
  'get_exception_handler' => 
  array (
    0 => 'callable|null',
  ),
  'get_declared_classes' => 
  array (
    0 => 'array',
  ),
  'get_declared_traits' => 
  array (
    0 => 'array',
  ),
  'get_declared_interfaces' => 
  array (
    0 => 'array',
  ),
  'get_defined_functions' => 
  array (
    0 => 'array',
    'exclude_disabled=' => 'bool',
  ),
  'get_defined_vars' => 
  array (
    0 => 'array',
  ),
  'get_resource_type' => 
  array (
    0 => 'string',
    'resource' => 'mixed',
  ),
  'get_resource_id' => 
  array (
    0 => 'int',
    'resource' => 'mixed',
  ),
  'get_resources' => 
  array (
    0 => 'array',
    'type=' => 'string|null',
  ),
  'get_loaded_extensions' => 
  array (
    0 => 'array',
    'zend_extensions=' => 'bool',
  ),
  'get_defined_constants' => 
  array (
    0 => 'array',
    'categorize=' => 'bool',
  ),
  'debug_backtrace' => 
  array (
    0 => 'array',
    'options=' => 'int',
    'limit=' => 'int',
  ),
  'debug_print_backtrace' => 
  array (
    0 => 'void',
    'options=' => 'int',
    'limit=' => 'int',
  ),
  'extension_loaded' => 
  array (
    0 => 'bool',
    'extension' => 'string',
  ),
  'get_extension_funcs' => 
  array (
    0 => 'array|false',
    'extension' => 'string',
  ),
  'gc_mem_caches' => 
  array (
    0 => 'int',
  ),
  'gc_collect_cycles' => 
  array (
    0 => 'int',
  ),
  'gc_enabled' => 
  array (
    0 => 'bool',
  ),
  'gc_enable' => 
  array (
    0 => 'void',
  ),
  'gc_disable' => 
  array (
    0 => 'void',
  ),
  'gc_status' => 
  array (
    0 => 'array',
  ),
  'strtotime' => 
  array (
    0 => 'int|false',
    'datetime' => 'string',
    'baseTimestamp=' => 'int|null',
  ),
  'date' => 
  array (
    0 => 'string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'idate' => 
  array (
    0 => 'int|false',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'gmdate' => 
  array (
    0 => 'string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'mktime' => 
  array (
    0 => 'int|false',
    'hour' => 'int',
    'minute=' => 'int|null',
    'second=' => 'int|null',
    'month=' => 'int|null',
    'day=' => 'int|null',
    'year=' => 'int|null',
  ),
  'gmmktime' => 
  array (
    0 => 'int|false',
    'hour' => 'int',
    'minute=' => 'int|null',
    'second=' => 'int|null',
    'month=' => 'int|null',
    'day=' => 'int|null',
    'year=' => 'int|null',
  ),
  'checkdate' => 
  array (
    0 => 'bool',
    'month' => 'int',
    'day' => 'int',
    'year' => 'int',
  ),
  'strftime' => 
  array (
    0 => 'string|false',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'gmstrftime' => 
  array (
    0 => 'string|false',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'time' => 
  array (
    0 => 'int',
  ),
  'localtime' => 
  array (
    0 => 'array',
    'timestamp=' => 'int|null',
    'associative=' => 'bool',
  ),
  'getdate' => 
  array (
    0 => 'array',
    'timestamp=' => 'int|null',
  ),
  'date_create' => 
  array (
    0 => 'DateTime|false',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_create_immutable' => 
  array (
    0 => 'DateTimeImmutable|false',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_create_from_format' => 
  array (
    0 => 'DateTime|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_create_immutable_from_format' => 
  array (
    0 => 'DateTimeImmutable|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_parse' => 
  array (
    0 => 'array',
    'datetime' => 'string',
  ),
  'date_parse_from_format' => 
  array (
    0 => 'array',
    'format' => 'string',
    'datetime' => 'string',
  ),
  'date_get_last_errors' => 
  array (
    0 => 'array|false',
  ),
  'date_format' => 
  array (
    0 => 'string',
    'object' => 'DateTimeInterface',
    'format' => 'string',
  ),
  'date_modify' => 
  array (
    0 => 'DateTime|false',
    'object' => 'DateTime',
    'modifier' => 'string',
  ),
  'date_add' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'date_sub' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'date_timezone_get' => 
  array (
    0 => 'DateTimeZone|false',
    'object' => 'DateTimeInterface',
  ),
  'date_timezone_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'timezone' => 'DateTimeZone',
  ),
  'date_offset_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeInterface',
  ),
  'date_diff' => 
  array (
    0 => 'DateInterval',
    'baseObject' => 'DateTimeInterface',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'date_time_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'date_date_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'date_isodate_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'date_timestamp_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'timestamp' => 'int',
  ),
  'date_timestamp_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeInterface',
  ),
  'timezone_open' => 
  array (
    0 => 'DateTimeZone|false',
    'timezone' => 'string',
  ),
  'timezone_name_get' => 
  array (
    0 => 'string',
    'object' => 'DateTimeZone',
  ),
  'timezone_name_from_abbr' => 
  array (
    0 => 'string|false',
    'abbr' => 'string',
    'utcOffset=' => 'int',
    'isDST=' => 'int',
  ),
  'timezone_offset_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeZone',
    'datetime' => 'DateTimeInterface',
  ),
  'timezone_transitions_get' => 
  array (
    0 => 'array|false',
    'object' => 'DateTimeZone',
    'timestampBegin=' => 'int',
    'timestampEnd=' => 'int',
  ),
  'timezone_location_get' => 
  array (
    0 => 'array|false',
    'object' => 'DateTimeZone',
  ),
  'timezone_identifiers_list' => 
  array (
    0 => 'array',
    'timezoneGroup=' => 'int',
    'countryCode=' => 'string|null',
  ),
  'timezone_abbreviations_list' => 
  array (
    0 => 'array',
  ),
  'timezone_version_get' => 
  array (
    0 => 'string',
  ),
  'date_interval_create_from_date_string' => 
  array (
    0 => 'DateInterval|false',
    'datetime' => 'string',
  ),
  'date_interval_format' => 
  array (
    0 => 'string',
    'object' => 'DateInterval',
    'format' => 'string',
  ),
  'date_default_timezone_set' => 
  array (
    0 => 'bool',
    'timezoneId' => 'string',
  ),
  'date_default_timezone_get' => 
  array (
    0 => 'string',
  ),
  'date_sunrise' => 
  array (
    0 => 'string|int|float|false',
    'timestamp' => 'int',
    'returnFormat=' => 'int',
    'latitude=' => 'float|null',
    'longitude=' => 'float|null',
    'zenith=' => 'float|null',
    'utcOffset=' => 'float|null',
  ),
  'date_sunset' => 
  array (
    0 => 'string|int|float|false',
    'timestamp' => 'int',
    'returnFormat=' => 'int',
    'latitude=' => 'float|null',
    'longitude=' => 'float|null',
    'zenith=' => 'float|null',
    'utcOffset=' => 'float|null',
  ),
  'date_sun_info' => 
  array (
    0 => 'array',
    'timestamp' => 'int',
    'latitude' => 'float',
    'longitude' => 'float',
  ),
  'libxml_set_streams_context' => 
  array (
    0 => 'void',
    'context' => 'mixed',
  ),
  'libxml_use_internal_errors' => 
  array (
    0 => 'bool',
    'use_errors=' => 'bool|null',
  ),
  'libxml_get_last_error' => 
  array (
    0 => 'LibXMLError|false',
  ),
  'libxml_get_errors' => 
  array (
    0 => 'array',
  ),
  'libxml_clear_errors' => 
  array (
    0 => 'void',
  ),
  'libxml_disable_entity_loader' => 
  array (
    0 => 'bool',
    'disable=' => 'bool',
  ),
  'libxml_set_external_entity_loader' => 
  array (
    0 => 'true',
    'resolver_function' => 'callable|null',
  ),
  'libxml_get_external_entity_loader' => 
  array (
    0 => 'callable|null',
  ),
  'openssl_x509_export_to_file' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'output_filename' => 'string',
    'no_text=' => 'bool',
  ),
  'openssl_x509_export' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    '&output' => 'mixed',
    'no_text=' => 'bool',
  ),
  'openssl_x509_fingerprint' => 
  array (
    0 => 'string|false',
    'certificate' => 'OpenSSLCertificate|string',
    'digest_algo=' => 'string',
    'binary=' => 'bool',
  ),
  'openssl_x509_check_private_key' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
  ),
  'openssl_x509_verify' => 
  array (
    0 => 'int',
    'certificate' => 'OpenSSLCertificate|string',
    'public_key' => 'mixed',
  ),
  'openssl_x509_parse' => 
  array (
    0 => 'array|false',
    'certificate' => 'OpenSSLCertificate|string',
    'short_names=' => 'bool',
  ),
  'openssl_x509_checkpurpose' => 
  array (
    0 => 'int|bool',
    'certificate' => 'OpenSSLCertificate|string',
    'purpose' => 'int',
    'ca_info=' => 'array',
    'untrusted_certificates_file=' => 'string|null',
  ),
  'openssl_x509_read' => 
  array (
    0 => 'OpenSSLCertificate|false',
    'certificate' => 'OpenSSLCertificate|string',
  ),
  'openssl_x509_free' => 
  array (
    0 => 'void',
    'certificate' => 'OpenSSLCertificate',
  ),
  'openssl_pkcs12_export_to_file' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'output_filename' => 'string',
    'private_key' => 'mixed',
    'passphrase' => 'string',
    'options=' => 'array',
  ),
  'openssl_pkcs12_export' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    '&output' => 'mixed',
    'private_key' => 'mixed',
    'passphrase' => 'string',
    'options=' => 'array',
  ),
  'openssl_pkcs12_read' => 
  array (
    0 => 'bool',
    'pkcs12' => 'string',
    '&certificates' => 'mixed',
    'passphrase' => 'string',
  ),
  'openssl_csr_export_to_file' => 
  array (
    0 => 'bool',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'output_filename' => 'string',
    'no_text=' => 'bool',
  ),
  'openssl_csr_export' => 
  array (
    0 => 'bool',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    '&output' => 'mixed',
    'no_text=' => 'bool',
  ),
  'openssl_csr_sign' => 
  array (
    0 => 'OpenSSLCertificate|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'ca_certificate' => 'OpenSSLCertificate|string|null|null',
    'private_key' => 'mixed',
    'days' => 'int',
    'options=' => 'array|null',
    'serial=' => 'int',
    'serial_hex=' => 'string|null',
  ),
  'openssl_csr_new' => 
  array (
    0 => 'OpenSSLCertificateSigningRequest|bool',
    'distinguished_names' => 'array',
    '&private_key' => 'mixed',
    'options=' => 'array|null',
    'extra_attributes=' => 'array|null',
  ),
  'openssl_csr_get_subject' => 
  array (
    0 => 'array|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'short_names=' => 'bool',
  ),
  'openssl_csr_get_public_key' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'short_names=' => 'bool',
  ),
  'openssl_pkey_new' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'options=' => 'array|null',
  ),
  'openssl_pkey_export_to_file' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'output_filename' => 'string',
    'passphrase=' => 'string|null',
    'options=' => 'array|null',
  ),
  'openssl_pkey_export' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    '&output' => 'mixed',
    'passphrase=' => 'string|null',
    'options=' => 'array|null',
  ),
  'openssl_pkey_get_public' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'mixed',
  ),
  'openssl_get_publickey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'mixed',
  ),
  'openssl_pkey_free' => 
  array (
    0 => 'void',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_free_key' => 
  array (
    0 => 'void',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_pkey_get_private' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'private_key' => 'mixed',
    'passphrase=' => 'string|null',
  ),
  'openssl_get_privatekey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'private_key' => 'mixed',
    'passphrase=' => 'string|null',
  ),
  'openssl_pkey_get_details' => 
  array (
    0 => 'array|false',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_pbkdf2' => 
  array (
    0 => 'string|false',
    'password' => 'string',
    'salt' => 'string',
    'key_length' => 'int',
    'iterations' => 'int',
    'digest_algo=' => 'string',
  ),
  'openssl_pkcs7_verify' => 
  array (
    0 => 'int|bool',
    'input_filename' => 'string',
    'flags' => 'int',
    'signers_certificates_filename=' => 'string|null',
    'ca_info=' => 'array',
    'untrusted_certificates_filename=' => 'string|null',
    'content=' => 'string|null',
    'output_filename=' => 'string|null',
  ),
  'openssl_pkcs7_encrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'headers' => 'array|null',
    'flags=' => 'int',
    'cipher_algo=' => 'int',
  ),
  'openssl_pkcs7_sign' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
    'headers' => 'array|null',
    'flags=' => 'int',
    'untrusted_certificates_filename=' => 'string|null',
  ),
  'openssl_pkcs7_decrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'private_key=' => 'mixed',
  ),
  'openssl_pkcs7_read' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&certificates' => 'mixed',
  ),
  'openssl_cms_verify' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'flags=' => 'int',
    'certificates=' => 'string|null',
    'ca_info=' => 'array',
    'untrusted_certificates_filename=' => 'string|null',
    'content=' => 'string|null',
    'pk7=' => 'string|null',
    'sigfile=' => 'string|null',
    'encoding=' => 'int',
  ),
  'openssl_cms_encrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'headers' => 'array|null',
    'flags=' => 'int',
    'encoding=' => 'int',
    'cipher_algo=' => 'string|int',
  ),
  'openssl_cms_sign' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
    'headers' => 'array|null',
    'flags=' => 'int',
    'encoding=' => 'int',
    'untrusted_certificates_filename=' => 'string|null',
  ),
  'openssl_cms_decrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'private_key=' => 'mixed',
    'encoding=' => 'int',
  ),
  'openssl_cms_read' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    '&certificates' => 'mixed',
  ),
  'openssl_private_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'mixed',
    'private_key' => 'mixed',
    'padding=' => 'int',
  ),
  'openssl_private_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'mixed',
    'private_key' => 'mixed',
    'padding=' => 'int',
    'digest_algo=' => 'string|null',
  ),
  'openssl_public_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'mixed',
    'public_key' => 'mixed',
    'padding=' => 'int',
    'digest_algo=' => 'string|null',
  ),
  'openssl_public_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'mixed',
    'public_key' => 'mixed',
    'padding=' => 'int',
  ),
  'openssl_error_string' => 
  array (
    0 => 'string|false',
  ),
  'openssl_sign' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&signature' => 'mixed',
    'private_key' => 'mixed',
    'algorithm=' => 'string|int',
    'padding=' => 'int',
  ),
  'openssl_verify' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    'signature' => 'string',
    'public_key' => 'mixed',
    'algorithm=' => 'string|int',
    'padding=' => 'int',
  ),
  'openssl_seal' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    '&sealed_data' => 'mixed',
    '&encrypted_keys' => 'mixed',
    'public_key' => 'array',
    'cipher_algo' => 'string',
    '&iv=' => 'mixed',
  ),
  'openssl_open' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&output' => 'mixed',
    'encrypted_key' => 'string',
    'private_key' => 'mixed',
    'cipher_algo' => 'string',
    'iv=' => 'string|null',
  ),
  'openssl_get_md_methods' => 
  array (
    0 => 'array',
    'aliases=' => 'bool',
  ),
  'openssl_get_cipher_methods' => 
  array (
    0 => 'array',
    'aliases=' => 'bool',
  ),
  'openssl_get_curve_names' => 
  array (
    0 => 'array|false',
  ),
  'openssl_digest' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'digest_algo' => 'string',
    'binary=' => 'bool',
  ),
  'openssl_encrypt' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'cipher_algo' => 'string',
    'passphrase' => 'string',
    'options=' => 'int',
    'iv=' => 'string',
    '&tag=' => 'mixed',
    'aad=' => 'string',
    'tag_length=' => 'int',
  ),
  'openssl_decrypt' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'cipher_algo' => 'string',
    'passphrase' => 'string',
    'options=' => 'int',
    'iv=' => 'string',
    'tag=' => 'string|null',
    'aad=' => 'string',
  ),
  'openssl_cipher_iv_length' => 
  array (
    0 => 'int|false',
    'cipher_algo' => 'string',
  ),
  'openssl_cipher_key_length' => 
  array (
    0 => 'int|false',
    'cipher_algo' => 'string',
  ),
  'openssl_dh_compute_key' => 
  array (
    0 => 'string|false',
    'public_key' => 'string',
    'private_key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_pkey_derive' => 
  array (
    0 => 'string|false',
    'public_key' => 'mixed',
    'private_key' => 'mixed',
    'key_length=' => 'int',
  ),
  'openssl_random_pseudo_bytes' => 
  array (
    0 => 'string',
    'length' => 'int',
    '&strong_result=' => 'mixed',
  ),
  'openssl_spki_new' => 
  array (
    0 => 'string|false',
    'private_key' => 'OpenSSLAsymmetricKey',
    'challenge' => 'string',
    'digest_algo=' => 'int',
  ),
  'openssl_spki_verify' => 
  array (
    0 => 'bool',
    'spki' => 'string',
  ),
  'openssl_spki_export' => 
  array (
    0 => 'string|false',
    'spki' => 'string',
  ),
  'openssl_spki_export_challenge' => 
  array (
    0 => 'string|false',
    'spki' => 'string',
  ),
  'openssl_get_cert_locations' => 
  array (
    0 => 'array',
  ),
  'preg_match' => 
  array (
    0 => 'int|false',
    'pattern' => 'string',
    'subject' => 'string',
    '&matches=' => 'mixed',
    'flags=' => 'int',
    'offset=' => 'int',
  ),
  'preg_match_all' => 
  array (
    0 => 'int|false',
    'pattern' => 'string',
    'subject' => 'string',
    '&matches=' => 'mixed',
    'flags=' => 'int',
    'offset=' => 'int',
  ),
  'preg_replace' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array|string',
    'replacement' => 'array|string',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
  ),
  'preg_filter' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array|string',
    'replacement' => 'array|string',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
  ),
  'preg_replace_callback' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array|string',
    'callback' => 'callable',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
    'flags=' => 'int',
  ),
  'preg_replace_callback_array' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
    'flags=' => 'int',
  ),
  'preg_split' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'subject' => 'string',
    'limit=' => 'int',
    'flags=' => 'int',
  ),
  'preg_quote' => 
  array (
    0 => 'string',
    'str' => 'string',
    'delimiter=' => 'string|null',
  ),
  'preg_grep' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'array' => 'array',
    'flags=' => 'int',
  ),
  'preg_last_error' => 
  array (
    0 => 'int',
  ),
  'preg_last_error_msg' => 
  array (
    0 => 'string',
  ),
  'ob_gzhandler' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'flags' => 'int',
  ),
  'zlib_get_coding_type' => 
  array (
    0 => 'string|false',
  ),
  'gzfile' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'gzopen' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'bool',
  ),
  'readgzfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'zlib_encode' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'encoding' => 'int',
    'level=' => 'int',
  ),
  'zlib_decode' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzdeflate' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzencode' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzcompress' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzinflate' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzdecode' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzuncompress' => 
  array (
    0 => 'string|false',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzwrite' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gzputs' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gzrewind' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'gzclose' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'gzeof' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'gzgetc' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
  ),
  'gzpassthru' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
  ),
  'gzseek' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'gztell' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
  ),
  'gzread' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length' => 'int',
  ),
  'gzgets' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length=' => 'int|null',
  ),
  'deflate_init' => 
  array (
    0 => 'DeflateContext|false',
    'encoding' => 'int',
    'options=' => 'object|array',
  ),
  'deflate_add' => 
  array (
    0 => 'string|false',
    'context' => 'DeflateContext',
    'data' => 'string',
    'flush_mode=' => 'int',
  ),
  'inflate_init' => 
  array (
    0 => 'InflateContext|false',
    'encoding' => 'int',
    'options=' => 'object|array',
  ),
  'inflate_add' => 
  array (
    0 => 'string|false',
    'context' => 'InflateContext',
    'data' => 'string',
    'flush_mode=' => 'int',
  ),
  'inflate_get_status' => 
  array (
    0 => 'int',
    'context' => 'InflateContext',
  ),
  'inflate_get_read_len' => 
  array (
    0 => 'int',
    'context' => 'InflateContext',
  ),
  'ctype_alnum' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_alpha' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_cntrl' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_digit' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_lower' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_graph' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_print' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_punct' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_space' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_upper' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_xdigit' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'curl_close' => 
  array (
    0 => 'void',
    'handle' => 'CurlHandle',
  ),
  'curl_copy_handle' => 
  array (
    0 => 'CurlHandle|false',
    'handle' => 'CurlHandle',
  ),
  'curl_errno' => 
  array (
    0 => 'int',
    'handle' => 'CurlHandle',
  ),
  'curl_error' => 
  array (
    0 => 'string',
    'handle' => 'CurlHandle',
  ),
  'curl_escape' => 
  array (
    0 => 'string|false',
    'handle' => 'CurlHandle',
    'string' => 'string',
  ),
  'curl_unescape' => 
  array (
    0 => 'string|false',
    'handle' => 'CurlHandle',
    'string' => 'string',
  ),
  'curl_multi_setopt' => 
  array (
    0 => 'bool',
    'multi_handle' => 'CurlMultiHandle',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'curl_exec' => 
  array (
    0 => 'string|bool',
    'handle' => 'CurlHandle',
  ),
  'curl_file_create' => 
  array (
    0 => 'CURLFile',
    'filename' => 'string',
    'mime_type=' => 'string|null',
    'posted_filename=' => 'string|null',
  ),
  'curl_getinfo' => 
  array (
    0 => 'mixed',
    'handle' => 'CurlHandle',
    'option=' => 'int|null',
  ),
  'curl_init' => 
  array (
    0 => 'CurlHandle|false',
    'url=' => 'string|null',
  ),
  'curl_upkeep' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
  ),
  'curl_multi_add_handle' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
    'handle' => 'CurlHandle',
  ),
  'curl_multi_get_handles' => 
  array (
    0 => 'array',
    'multi_handle' => 'CurlMultiHandle',
  ),
  'curl_multi_close' => 
  array (
    0 => 'void',
    'multi_handle' => 'CurlMultiHandle',
  ),
  'curl_multi_errno' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
  ),
  'curl_multi_exec' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
    '&still_running' => 'mixed',
  ),
  'curl_multi_getcontent' => 
  array (
    0 => 'string|null',
    'handle' => 'CurlHandle',
  ),
  'curl_multi_info_read' => 
  array (
    0 => 'array|false',
    'multi_handle' => 'CurlMultiHandle',
    '&queued_messages=' => 'mixed',
  ),
  'curl_multi_init' => 
  array (
    0 => 'CurlMultiHandle',
  ),
  'curl_multi_remove_handle' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
    'handle' => 'CurlHandle',
  ),
  'curl_multi_select' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
    'timeout=' => 'float',
  ),
  'curl_multi_strerror' => 
  array (
    0 => 'string|null',
    'error_code' => 'int',
  ),
  'curl_pause' => 
  array (
    0 => 'int',
    'handle' => 'CurlHandle',
    'flags' => 'int',
  ),
  'curl_reset' => 
  array (
    0 => 'void',
    'handle' => 'CurlHandle',
  ),
  'curl_setopt_array' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
    'options' => 'array',
  ),
  'curl_setopt' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'curl_share_close' => 
  array (
    0 => 'void',
    'share_handle' => 'CurlShareHandle',
  ),
  'curl_share_errno' => 
  array (
    0 => 'int',
    'share_handle' => 'CurlShareHandle',
  ),
  'curl_share_init' => 
  array (
    0 => 'CurlShareHandle',
  ),
  'curl_share_setopt' => 
  array (
    0 => 'bool',
    'share_handle' => 'CurlShareHandle',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'curl_share_strerror' => 
  array (
    0 => 'string|null',
    'error_code' => 'int',
  ),
  'curl_share_init_persistent' => 
  array (
    0 => 'CurlSharePersistentHandle',
    'share_options' => 'array',
  ),
  'curl_strerror' => 
  array (
    0 => 'string|null',
    'error_code' => 'int',
  ),
  'curl_version' => 
  array (
    0 => 'array|false',
  ),
  'dom_import_simplexml' => 
  array (
    0 => 'DOMAttr|DOMElement',
    'node' => 'object',
  ),
  'dom\\import_simplexml' => 
  array (
    0 => 'Dom\\Attr|Dom\\Element',
    'node' => 'object',
  ),
  'finfo_open' => 
  array (
    0 => 'finfo|false',
    'flags=' => 'int',
    'magic_database=' => 'string|null',
  ),
  'finfo_close' => 
  array (
    0 => 'true',
    'finfo' => 'finfo',
  ),
  'finfo_set_flags' => 
  array (
    0 => 'true',
    'finfo' => 'finfo',
    'flags' => 'int',
  ),
  'finfo_file' => 
  array (
    0 => 'string|false',
    'finfo' => 'finfo',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo_buffer' => 
  array (
    0 => 'string|false',
    'finfo' => 'finfo',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'mime_content_type' => 
  array (
    0 => 'string|false',
    'filename' => 'mixed',
  ),
  'filter_has_var' => 
  array (
    0 => 'bool',
    'input_type' => 'int',
    'var_name' => 'string',
  ),
  'filter_input' => 
  array (
    0 => 'mixed',
    'type' => 'int',
    'var_name' => 'string',
    'filter=' => 'int',
    'options=' => 'array|int',
  ),
  'filter_var' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    'filter=' => 'int',
    'options=' => 'array|int',
  ),
  'filter_input_array' => 
  array (
    0 => 'array|false|null|null',
    'type' => 'int',
    'options=' => 'array|int',
    'add_empty=' => 'bool',
  ),
  'filter_var_array' => 
  array (
    0 => 'array|false|null|null',
    'array' => 'array',
    'options=' => 'array|int',
    'add_empty=' => 'bool',
  ),
  'filter_list' => 
  array (
    0 => 'array',
  ),
  'filter_id' => 
  array (
    0 => 'int|false',
    'name' => 'string',
  ),
  'hash' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'data' => 'string',
    'binary=' => 'bool',
    'options=' => 'array',
  ),
  'hash_file' => 
  array (
    0 => 'string|false',
    'algo' => 'string',
    'filename' => 'string',
    'binary=' => 'bool',
    'options=' => 'array',
  ),
  'hash_hmac' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'data' => 'string',
    'key' => 'string',
    'binary=' => 'bool',
  ),
  'hash_hmac_file' => 
  array (
    0 => 'string|false',
    'algo' => 'string',
    'filename' => 'string',
    'key' => 'string',
    'binary=' => 'bool',
  ),
  'hash_init' => 
  array (
    0 => 'HashContext',
    'algo' => 'string',
    'flags=' => 'int',
    'key=' => 'string',
    'options=' => 'array',
  ),
  'hash_update' => 
  array (
    0 => 'true',
    'context' => 'HashContext',
    'data' => 'string',
  ),
  'hash_update_stream' => 
  array (
    0 => 'int',
    'context' => 'HashContext',
    'stream' => 'mixed',
    'length=' => 'int',
  ),
  'hash_update_file' => 
  array (
    0 => 'bool',
    'context' => 'HashContext',
    'filename' => 'string',
    'stream_context=' => 'mixed',
  ),
  'hash_final' => 
  array (
    0 => 'string',
    'context' => 'HashContext',
    'binary=' => 'bool',
  ),
  'hash_copy' => 
  array (
    0 => 'HashContext',
    'context' => 'HashContext',
  ),
  'hash_algos' => 
  array (
    0 => 'array',
  ),
  'hash_hmac_algos' => 
  array (
    0 => 'array',
  ),
  'hash_pbkdf2' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'password' => 'string',
    'salt' => 'string',
    'iterations' => 'int',
    'length=' => 'int',
    'binary=' => 'bool',
    'options=' => 'array',
  ),
  'hash_equals' => 
  array (
    0 => 'bool',
    'known_string' => 'string',
    'user_string' => 'string',
  ),
  'hash_hkdf' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'key' => 'string',
    'length=' => 'int',
    'info=' => 'string',
    'salt=' => 'string',
  ),
  'mhash_get_block_size' => 
  array (
    0 => 'int|false',
    'algo' => 'int',
  ),
  'mhash_get_hash_name' => 
  array (
    0 => 'string|false',
    'algo' => 'int',
  ),
  'mhash_keygen_s2k' => 
  array (
    0 => 'string|false',
    'algo' => 'int',
    'password' => 'string',
    'salt' => 'string',
    'length' => 'int',
  ),
  'mhash_count' => 
  array (
    0 => 'int',
  ),
  'mhash' => 
  array (
    0 => 'string|false',
    'algo' => 'int',
    'data' => 'string',
    'key=' => 'string|null',
  ),
  'iconv_strlen' => 
  array (
    0 => 'int|false',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'iconv_substr' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'string|null',
  ),
  'iconv_strpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'string|null',
  ),
  'iconv_strrpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'encoding=' => 'string|null',
  ),
  'iconv_mime_encode' => 
  array (
    0 => 'string|false',
    'field_name' => 'string',
    'field_value' => 'string',
    'options=' => 'array',
  ),
  'iconv_mime_decode' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'mode=' => 'int',
    'encoding=' => 'string|null',
  ),
  'iconv_mime_decode_headers' => 
  array (
    0 => 'array|false',
    'headers' => 'string',
    'mode=' => 'int',
    'encoding=' => 'string|null',
  ),
  'iconv' => 
  array (
    0 => 'string|false',
    'from_encoding' => 'string',
    'to_encoding' => 'string',
    'string' => 'string',
  ),
  'iconv_set_encoding' => 
  array (
    0 => 'bool',
    'type' => 'string',
    'encoding' => 'string',
  ),
  'iconv_get_encoding' => 
  array (
    0 => 'array|string|false',
    'type=' => 'string',
  ),
  'json_encode' => 
  array (
    0 => 'string|false',
    'value' => 'mixed',
    'flags=' => 'int',
    'depth=' => 'int',
  ),
  'json_decode' => 
  array (
    0 => 'mixed',
    'json' => 'string',
    'associative=' => 'bool|null',
    'depth=' => 'int',
    'flags=' => 'int',
  ),
  'json_validate' => 
  array (
    0 => 'bool',
    'json' => 'string',
    'depth=' => 'int',
    'flags=' => 'int',
  ),
  'json_last_error' => 
  array (
    0 => 'int',
  ),
  'json_last_error_msg' => 
  array (
    0 => 'string',
  ),
  'mb_language' => 
  array (
    0 => 'string|bool',
    'language=' => 'string|null',
  ),
  'mb_internal_encoding' => 
  array (
    0 => 'string|bool',
    'encoding=' => 'string|null',
  ),
  'mb_http_input' => 
  array (
    0 => 'array|string|false',
    'type=' => 'string|null',
  ),
  'mb_http_output' => 
  array (
    0 => 'string|bool',
    'encoding=' => 'string|null',
  ),
  'mb_detect_order' => 
  array (
    0 => 'array|bool',
    'encoding=' => 'array|string|null|null',
  ),
  'mb_substitute_character' => 
  array (
    0 => 'string|int|bool',
    'substitute_character=' => 'string|int|null|null',
  ),
  'mb_preferred_mime_name' => 
  array (
    0 => 'string|false',
    'encoding' => 'string',
  ),
  'mb_parse_str' => 
  array (
    0 => 'bool',
    'string' => 'string',
    '&result' => 'mixed',
  ),
  'mb_output_handler' => 
  array (
    0 => 'string',
    'string' => 'string',
    'status' => 'int',
  ),
  'mb_str_split' => 
  array (
    0 => 'array',
    'string' => 'string',
    'length=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_strlen' => 
  array (
    0 => 'int',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_strpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_strrpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_stripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_strripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_strstr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'string|null',
  ),
  'mb_strrchr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'string|null',
  ),
  'mb_stristr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'string|null',
  ),
  'mb_strrichr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'string|null',
  ),
  'mb_substr_count' => 
  array (
    0 => 'int',
    'haystack' => 'string',
    'needle' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_substr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'string|null',
  ),
  'mb_strcut' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'string|null',
  ),
  'mb_strwidth' => 
  array (
    0 => 'int',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_strimwidth' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'width' => 'int',
    'trim_marker=' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_convert_encoding' => 
  array (
    0 => 'array|string|false',
    'string' => 'array|string',
    'to_encoding' => 'string',
    'from_encoding=' => 'array|string|null|null',
  ),
  'mb_convert_case' => 
  array (
    0 => 'string',
    'string' => 'string',
    'mode' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_strtoupper' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_strtolower' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_ucfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_lcfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_trim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string|null',
    'encoding=' => 'string|null',
  ),
  'mb_ltrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string|null',
    'encoding=' => 'string|null',
  ),
  'mb_rtrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string|null',
    'encoding=' => 'string|null',
  ),
  'mb_detect_encoding' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'encodings=' => 'array|string|null|null',
    'strict=' => 'bool',
  ),
  'mb_list_encodings' => 
  array (
    0 => 'array',
  ),
  'mb_encoding_aliases' => 
  array (
    0 => 'array',
    'encoding' => 'string',
  ),
  'mb_encode_mimeheader' => 
  array (
    0 => 'string',
    'string' => 'string',
    'charset=' => 'string|null',
    'transfer_encoding=' => 'string|null',
    'newline=' => 'string',
    'indent=' => 'int',
  ),
  'mb_decode_mimeheader' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'mb_convert_kana' => 
  array (
    0 => 'string',
    'string' => 'string',
    'mode=' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_convert_variables' => 
  array (
    0 => 'string|false',
    'to_encoding' => 'string',
    'from_encoding' => 'array|string',
    '&var' => 'mixed',
    '&...vars=' => 'mixed',
  ),
  'mb_encode_numericentity' => 
  array (
    0 => 'string',
    'string' => 'string',
    'map' => 'array',
    'encoding=' => 'string|null',
    'hex=' => 'bool',
  ),
  'mb_decode_numericentity' => 
  array (
    0 => 'string',
    'string' => 'string',
    'map' => 'array',
    'encoding=' => 'string|null',
  ),
  'mb_send_mail' => 
  array (
    0 => 'bool',
    'to' => 'string',
    'subject' => 'string',
    'message' => 'string',
    'additional_headers=' => 'array|string',
    'additional_params=' => 'string|null',
  ),
  'mb_get_info' => 
  array (
    0 => 'array|string|int|false|null|null',
    'type=' => 'string',
  ),
  'mb_check_encoding' => 
  array (
    0 => 'bool',
    'value=' => 'array|string|null|null',
    'encoding=' => 'string|null',
  ),
  'mb_scrub' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_ord' => 
  array (
    0 => 'int|false',
    'string' => 'string',
    'encoding=' => 'string|null',
  ),
  'mb_chr' => 
  array (
    0 => 'string|false',
    'codepoint' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_str_pad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length' => 'int',
    'pad_string=' => 'string',
    'pad_type=' => 'int',
    'encoding=' => 'string|null',
  ),
  'mb_regex_encoding' => 
  array (
    0 => 'string|bool',
    'encoding=' => 'string|null',
  ),
  'mb_ereg' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    '&matches=' => 'mixed',
  ),
  'mb_eregi' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    '&matches=' => 'mixed',
  ),
  'mb_ereg_replace' => 
  array (
    0 => 'string|false|null|null',
    'pattern' => 'string',
    'replacement' => 'string',
    'string' => 'string',
    'options=' => 'string|null',
  ),
  'mb_eregi_replace' => 
  array (
    0 => 'string|false|null|null',
    'pattern' => 'string',
    'replacement' => 'string',
    'string' => 'string',
    'options=' => 'string|null',
  ),
  'mb_ereg_replace_callback' => 
  array (
    0 => 'string|false|null|null',
    'pattern' => 'string',
    'callback' => 'callable',
    'string' => 'string',
    'options=' => 'string|null',
  ),
  'mb_split' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'string' => 'string',
    'limit=' => 'int',
  ),
  'mb_ereg_match' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    'options=' => 'string|null',
  ),
  'mb_ereg_search' => 
  array (
    0 => 'bool',
    'pattern=' => 'string|null',
    'options=' => 'string|null',
  ),
  'mb_ereg_search_pos' => 
  array (
    0 => 'array|false',
    'pattern=' => 'string|null',
    'options=' => 'string|null',
  ),
  'mb_ereg_search_regs' => 
  array (
    0 => 'array|false',
    'pattern=' => 'string|null',
    'options=' => 'string|null',
  ),
  'mb_ereg_search_init' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'pattern=' => 'string|null',
    'options=' => 'string|null',
  ),
  'mb_ereg_search_getregs' => 
  array (
    0 => 'array|false',
  ),
  'mb_ereg_search_getpos' => 
  array (
    0 => 'int',
  ),
  'mb_ereg_search_setpos' => 
  array (
    0 => 'bool',
    'offset' => 'int',
  ),
  'mb_regex_set_options' => 
  array (
    0 => 'string',
    'options=' => 'string|null',
  ),
  'opcache_reset' => 
  array (
    0 => 'bool',
  ),
  'opcache_get_status' => 
  array (
    0 => 'array|false',
    'include_scripts=' => 'bool',
  ),
  'opcache_compile_file' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'opcache_invalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'force=' => 'bool',
  ),
  'opcache_jit_blacklist' => 
  array (
    0 => 'void',
    'closure' => 'Closure',
  ),
  'opcache_get_configuration' => 
  array (
    0 => 'array|false',
  ),
  'opcache_is_script_cached' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'opcache_is_script_cached_in_file_cache' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'set_time_limit' => 
  array (
    0 => 'bool',
    'seconds' => 'int',
  ),
  'header_register_callback' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'ob_start' => 
  array (
    0 => 'bool',
    'callback=' => 'mixed',
    'chunk_size=' => 'int',
    'flags=' => 'int',
  ),
  'ob_flush' => 
  array (
    0 => 'bool',
  ),
  'ob_clean' => 
  array (
    0 => 'bool',
  ),
  'ob_end_flush' => 
  array (
    0 => 'bool',
  ),
  'ob_end_clean' => 
  array (
    0 => 'bool',
  ),
  'ob_get_flush' => 
  array (
    0 => 'string|false',
  ),
  'ob_get_clean' => 
  array (
    0 => 'string|false',
  ),
  'ob_get_contents' => 
  array (
    0 => 'string|false',
  ),
  'ob_get_level' => 
  array (
    0 => 'int',
  ),
  'ob_get_length' => 
  array (
    0 => 'int|false',
  ),
  'ob_list_handlers' => 
  array (
    0 => 'array',
  ),
  'ob_get_status' => 
  array (
    0 => 'array',
    'full_status=' => 'bool',
  ),
  'ob_implicit_flush' => 
  array (
    0 => 'void',
    'enable=' => 'bool',
  ),
  'output_reset_rewrite_vars' => 
  array (
    0 => 'bool',
  ),
  'output_add_rewrite_var' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value' => 'string',
  ),
  'stream_wrapper_register' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
    'class' => 'string',
    'flags=' => 'int',
  ),
  'stream_register_wrapper' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
    'class' => 'string',
    'flags=' => 'int',
  ),
  'stream_wrapper_unregister' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
  ),
  'stream_wrapper_restore' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
  ),
  'array_push' => 
  array (
    0 => 'int',
    '&array' => 'array',
    '...values=' => 'mixed',
  ),
  'krsort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'ksort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'count' => 
  array (
    0 => 'int',
    'value' => 'Countable|array',
    'mode=' => 'int',
  ),
  'sizeof' => 
  array (
    0 => 'int',
    'value' => 'Countable|array',
    'mode=' => 'int',
  ),
  'natsort' => 
  array (
    0 => 'true',
    '&array' => 'array',
  ),
  'natcasesort' => 
  array (
    0 => 'true',
    '&array' => 'array',
  ),
  'asort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'arsort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'sort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'rsort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'usort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'uasort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'uksort' => 
  array (
    0 => 'true',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'end' => 
  array (
    0 => 'mixed',
    '&array' => 'object|array',
  ),
  'prev' => 
  array (
    0 => 'mixed',
    '&array' => 'object|array',
  ),
  'next' => 
  array (
    0 => 'mixed',
    '&array' => 'object|array',
  ),
  'reset' => 
  array (
    0 => 'mixed',
    '&array' => 'object|array',
  ),
  'current' => 
  array (
    0 => 'mixed',
    'array' => 'object|array',
  ),
  'pos' => 
  array (
    0 => 'mixed',
    'array' => 'object|array',
  ),
  'key' => 
  array (
    0 => 'string|int|null|null',
    'array' => 'object|array',
  ),
  'min' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'max' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'array_walk' => 
  array (
    0 => 'true',
    '&array' => 'object|array',
    'callback' => 'callable',
    'arg=' => 'mixed',
  ),
  'array_walk_recursive' => 
  array (
    0 => 'true',
    '&array' => 'object|array',
    'callback' => 'callable',
    'arg=' => 'mixed',
  ),
  'in_array' => 
  array (
    0 => 'bool',
    'needle' => 'mixed',
    'haystack' => 'array',
    'strict=' => 'bool',
  ),
  'array_search' => 
  array (
    0 => 'string|int|false',
    'needle' => 'mixed',
    'haystack' => 'array',
    'strict=' => 'bool',
  ),
  'extract' => 
  array (
    0 => 'int',
    '&array' => 'array',
    'flags=' => 'int',
    'prefix=' => 'string',
  ),
  'compact' => 
  array (
    0 => 'array',
    'var_name' => 'mixed',
    '...var_names=' => 'mixed',
  ),
  'array_fill' => 
  array (
    0 => 'array',
    'start_index' => 'int',
    'count' => 'int',
    'value' => 'mixed',
  ),
  'array_fill_keys' => 
  array (
    0 => 'array',
    'keys' => 'array',
    'value' => 'mixed',
  ),
  'range' => 
  array (
    0 => 'array',
    'start' => 'string|int|float',
    'end' => 'string|int|float',
    'step=' => 'int|float',
  ),
  'shuffle' => 
  array (
    0 => 'true',
    '&array' => 'array',
  ),
  'array_pop' => 
  array (
    0 => 'mixed',
    '&array' => 'array',
  ),
  'array_shift' => 
  array (
    0 => 'mixed',
    '&array' => 'array',
  ),
  'array_unshift' => 
  array (
    0 => 'int',
    '&array' => 'array',
    '...values=' => 'mixed',
  ),
  'array_splice' => 
  array (
    0 => 'array',
    '&array' => 'array',
    'offset' => 'int',
    'length=' => 'int|null',
    'replacement=' => 'mixed',
  ),
  'array_slice' => 
  array (
    0 => 'array',
    'array' => 'array',
    'offset' => 'int',
    'length=' => 'int|null',
    'preserve_keys=' => 'bool',
  ),
  'array_merge' => 
  array (
    0 => 'array',
    '...arrays=' => 'array',
  ),
  'array_merge_recursive' => 
  array (
    0 => 'array',
    '...arrays=' => 'array',
  ),
  'array_replace' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...replacements=' => 'array',
  ),
  'array_replace_recursive' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...replacements=' => 'array',
  ),
  'array_keys' => 
  array (
    0 => 'array',
    'array' => 'array',
    'filter_value=' => 'mixed',
    'strict=' => 'bool',
  ),
  'array_key_first' => 
  array (
    0 => 'string|int|null|null',
    'array' => 'array',
  ),
  'array_key_last' => 
  array (
    0 => 'string|int|null|null',
    'array' => 'array',
  ),
  'array_first' => 
  array (
    0 => 'mixed',
    'array' => 'array',
  ),
  'array_last' => 
  array (
    0 => 'mixed',
    'array' => 'array',
  ),
  'array_values' => 
  array (
    0 => 'array',
    'array' => 'array',
  ),
  'array_count_values' => 
  array (
    0 => 'array',
    'array' => 'array',
  ),
  'array_column' => 
  array (
    0 => 'array',
    'array' => 'array',
    'column_key' => 'string|int|null|null',
    'index_key=' => 'string|int|null|null',
  ),
  'array_reverse' => 
  array (
    0 => 'array',
    'array' => 'array',
    'preserve_keys=' => 'bool',
  ),
  'array_pad' => 
  array (
    0 => 'array',
    'array' => 'array',
    'length' => 'int',
    'value' => 'mixed',
  ),
  'array_flip' => 
  array (
    0 => 'array',
    'array' => 'array',
  ),
  'array_change_key_case' => 
  array (
    0 => 'array',
    'array' => 'array',
    'case=' => 'int',
  ),
  'array_unique' => 
  array (
    0 => 'array',
    'array' => 'array',
    'flags=' => 'int',
  ),
  'array_intersect_key' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_intersect_ukey' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_intersect' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_uintersect' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_intersect_assoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_uintersect_assoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_intersect_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_uintersect_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_diff_key' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_diff_ukey' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_diff' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_udiff' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_diff_assoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_diff_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_udiff_assoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_udiff_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'mixed',
  ),
  'array_multisort' => 
  array (
    0 => 'true',
    '&array' => 'mixed',
    '&...rest=' => 'mixed',
  ),
  'array_rand' => 
  array (
    0 => 'array|string|int',
    'array' => 'array',
    'num=' => 'int',
  ),
  'array_sum' => 
  array (
    0 => 'int|float',
    'array' => 'array',
  ),
  'array_product' => 
  array (
    0 => 'int|float',
    'array' => 'array',
  ),
  'array_reduce' => 
  array (
    0 => 'mixed',
    'array' => 'array',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'array_filter' => 
  array (
    0 => 'array',
    'array' => 'array',
    'callback=' => 'callable|null',
    'mode=' => 'int',
  ),
  'array_find' => 
  array (
    0 => 'mixed',
    'array' => 'array',
    'callback' => 'callable',
  ),
  'array_find_key' => 
  array (
    0 => 'mixed',
    'array' => 'array',
    'callback' => 'callable',
  ),
  'array_any' => 
  array (
    0 => 'bool',
    'array' => 'array',
    'callback' => 'callable',
  ),
  'array_all' => 
  array (
    0 => 'bool',
    'array' => 'array',
    'callback' => 'callable',
  ),
  'array_map' => 
  array (
    0 => 'array',
    'callback' => 'callable|null',
    'array' => 'array',
    '...arrays=' => 'array',
  ),
  'array_key_exists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'array' => 'array',
  ),
  'key_exists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'array' => 'array',
  ),
  'array_chunk' => 
  array (
    0 => 'array',
    'array' => 'array',
    'length' => 'int',
    'preserve_keys=' => 'bool',
  ),
  'array_combine' => 
  array (
    0 => 'array',
    'keys' => 'array',
    'values' => 'array',
  ),
  'array_is_list' => 
  array (
    0 => 'bool',
    'array' => 'array',
  ),
  'base64_encode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'base64_decode' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'strict=' => 'bool',
  ),
  'constant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'ip2long' => 
  array (
    0 => 'int|false',
    'ip' => 'string',
  ),
  'long2ip' => 
  array (
    0 => 'string',
    'ip' => 'int',
  ),
  'getenv' => 
  array (
    0 => 'array|string|false',
    'name=' => 'string|null',
    'local_only=' => 'bool',
  ),
  'putenv' => 
  array (
    0 => 'bool',
    'assignment' => 'string',
  ),
  'getopt' => 
  array (
    0 => 'array|false',
    'short_options' => 'string',
    'long_options=' => 'array',
    '&rest_index=' => 'mixed',
  ),
  'flush' => 
  array (
    0 => 'void',
  ),
  'sleep' => 
  array (
    0 => 'int',
    'seconds' => 'int',
  ),
  'usleep' => 
  array (
    0 => 'void',
    'microseconds' => 'int',
  ),
  'time_nanosleep' => 
  array (
    0 => 'array|bool',
    'seconds' => 'int',
    'nanoseconds' => 'int',
  ),
  'time_sleep_until' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'get_current_user' => 
  array (
    0 => 'string',
  ),
  'get_cfg_var' => 
  array (
    0 => 'array|string|false',
    'option' => 'string',
  ),
  'error_log' => 
  array (
    0 => 'bool',
    'message' => 'string',
    'message_type=' => 'int',
    'destination=' => 'string|null',
    'additional_headers=' => 'string|null',
  ),
  'error_get_last' => 
  array (
    0 => 'array|null',
  ),
  'error_clear_last' => 
  array (
    0 => 'void',
  ),
  'call_user_func' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'call_user_func_array' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'args' => 'array',
  ),
  'forward_static_call' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'forward_static_call_array' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'args' => 'array',
  ),
  'register_shutdown_function' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'highlight_file' => 
  array (
    0 => 'string|bool',
    'filename' => 'string',
    'return=' => 'bool',
  ),
  'show_source' => 
  array (
    0 => 'string|bool',
    'filename' => 'string',
    'return=' => 'bool',
  ),
  'php_strip_whitespace' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'highlight_string' => 
  array (
    0 => 'string|true',
    'string' => 'string',
    'return=' => 'bool',
  ),
  'ini_get' => 
  array (
    0 => 'string|false',
    'option' => 'string',
  ),
  'ini_get_all' => 
  array (
    0 => 'array|false',
    'extension=' => 'string|null',
    'details=' => 'bool',
  ),
  'ini_set' => 
  array (
    0 => 'string|false',
    'option' => 'string',
    'value' => 'string|int|float|bool|null|null',
  ),
  'ini_alter' => 
  array (
    0 => 'string|false',
    'option' => 'string',
    'value' => 'string|int|float|bool|null|null',
  ),
  'ini_restore' => 
  array (
    0 => 'void',
    'option' => 'string',
  ),
  'ini_parse_quantity' => 
  array (
    0 => 'int',
    'shorthand' => 'string',
  ),
  'set_include_path' => 
  array (
    0 => 'string|false',
    'include_path' => 'string',
  ),
  'get_include_path' => 
  array (
    0 => 'string|false',
  ),
  'print_r' => 
  array (
    0 => 'string|true',
    'value' => 'mixed',
    'return=' => 'bool',
  ),
  'connection_aborted' => 
  array (
    0 => 'int',
  ),
  'connection_status' => 
  array (
    0 => 'int',
  ),
  'ignore_user_abort' => 
  array (
    0 => 'int',
    'enable=' => 'bool|null',
  ),
  'getservbyname' => 
  array (
    0 => 'int|false',
    'service' => 'string',
    'protocol' => 'string',
  ),
  'getservbyport' => 
  array (
    0 => 'string|false',
    'port' => 'int',
    'protocol' => 'string',
  ),
  'getprotobyname' => 
  array (
    0 => 'int|false',
    'protocol' => 'string',
  ),
  'getprotobynumber' => 
  array (
    0 => 'string|false',
    'protocol' => 'int',
  ),
  'register_tick_function' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'unregister_tick_function' => 
  array (
    0 => 'void',
    'callback' => 'callable',
  ),
  'is_uploaded_file' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'move_uploaded_file' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
  ),
  'parse_ini_file' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    'process_sections=' => 'bool',
    'scanner_mode=' => 'int',
  ),
  'parse_ini_string' => 
  array (
    0 => 'array|false',
    'ini_string' => 'string',
    'process_sections=' => 'bool',
    'scanner_mode=' => 'int',
  ),
  'sys_getloadavg' => 
  array (
    0 => 'array|false',
  ),
  'get_browser' => 
  array (
    0 => 'object|array|false',
    'user_agent=' => 'string|null',
    'return_array=' => 'bool',
  ),
  'crc32' => 
  array (
    0 => 'int',
    'string' => 'string',
  ),
  'crypt' => 
  array (
    0 => 'string',
    'string' => 'string',
    'salt' => 'string',
  ),
  'strptime' => 
  array (
    0 => 'array|false',
    'timestamp' => 'string',
    'format' => 'string',
  ),
  'gethostname' => 
  array (
    0 => 'string|false',
  ),
  'gethostbyaddr' => 
  array (
    0 => 'string|false',
    'ip' => 'string',
  ),
  'gethostbyname' => 
  array (
    0 => 'string',
    'hostname' => 'string',
  ),
  'gethostbynamel' => 
  array (
    0 => 'array|false',
    'hostname' => 'string',
  ),
  'dns_check_record' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    'type=' => 'string',
  ),
  'checkdnsrr' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    'type=' => 'string',
  ),
  'dns_get_record' => 
  array (
    0 => 'array|false',
    'hostname' => 'string',
    'type=' => 'int',
    '&authoritative_name_servers=' => 'mixed',
    '&additional_records=' => 'mixed',
    'raw=' => 'bool',
  ),
  'dns_get_mx' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'mixed',
    '&weights=' => 'mixed',
  ),
  'getmxrr' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'mixed',
    '&weights=' => 'mixed',
  ),
  'net_get_interfaces' => 
  array (
    0 => 'array|false',
  ),
  'ftok' => 
  array (
    0 => 'int',
    'filename' => 'string',
    'project_id' => 'string',
  ),
  'hrtime' => 
  array (
    0 => 'array|int|float|false',
    'as_number=' => 'bool',
  ),
  'md5' => 
  array (
    0 => 'string',
    'string' => 'string',
    'binary=' => 'bool',
  ),
  'md5_file' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
    'binary=' => 'bool',
  ),
  'getmyuid' => 
  array (
    0 => 'int|false',
  ),
  'getmygid' => 
  array (
    0 => 'int|false',
  ),
  'getmypid' => 
  array (
    0 => 'int|false',
  ),
  'getmyinode' => 
  array (
    0 => 'int|false',
  ),
  'getlastmod' => 
  array (
    0 => 'int|false',
  ),
  'sha1' => 
  array (
    0 => 'string',
    'string' => 'string',
    'binary=' => 'bool',
  ),
  'sha1_file' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
    'binary=' => 'bool',
  ),
  'openlog' => 
  array (
    0 => 'true',
    'prefix' => 'string',
    'flags' => 'int',
    'facility' => 'int',
  ),
  'closelog' => 
  array (
    0 => 'true',
  ),
  'syslog' => 
  array (
    0 => 'true',
    'priority' => 'int',
    'message' => 'string',
  ),
  'inet_ntop' => 
  array (
    0 => 'string|false',
    'ip' => 'string',
  ),
  'inet_pton' => 
  array (
    0 => 'string|false',
    'ip' => 'string',
  ),
  'metaphone' => 
  array (
    0 => 'string',
    'string' => 'string',
    'max_phonemes=' => 'int',
  ),
  'header' => 
  array (
    0 => 'void',
    'header' => 'string',
    'replace=' => 'bool',
    'response_code=' => 'int',
  ),
  'header_remove' => 
  array (
    0 => 'void',
    'name=' => 'string|null',
  ),
  'setrawcookie' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value=' => 'string',
    'expires_or_options=' => 'array|int',
    'path=' => 'string',
    'domain=' => 'string',
    'secure=' => 'bool',
    'httponly=' => 'bool',
  ),
  'setcookie' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value=' => 'string',
    'expires_or_options=' => 'array|int',
    'path=' => 'string',
    'domain=' => 'string',
    'secure=' => 'bool',
    'httponly=' => 'bool',
  ),
  'http_response_code' => 
  array (
    0 => 'int|bool',
    'response_code=' => 'int',
  ),
  'headers_sent' => 
  array (
    0 => 'bool',
    '&filename=' => 'mixed',
    '&line=' => 'mixed',
  ),
  'headers_list' => 
  array (
    0 => 'array',
  ),
  'htmlspecialchars' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'string|null',
    'double_encode=' => 'bool',
  ),
  'htmlspecialchars_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
  ),
  'html_entity_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'string|null',
  ),
  'htmlentities' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'string|null',
    'double_encode=' => 'bool',
  ),
  'get_html_translation_table' => 
  array (
    0 => 'array',
    'table=' => 'int',
    'flags=' => 'int',
    'encoding=' => 'string',
  ),
  'assert' => 
  array (
    0 => 'bool',
    'assertion' => 'mixed',
    'description=' => 'Throwable|string|null|null',
  ),
  'assert_options' => 
  array (
    0 => 'mixed',
    'option' => 'int',
    'value=' => 'mixed',
  ),
  'bin2hex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'hex2bin' => 
  array (
    0 => 'string|false',
    'string' => 'string',
  ),
  'strspn' => 
  array (
    0 => 'int',
    'string' => 'string',
    'characters' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'strcspn' => 
  array (
    0 => 'int',
    'string' => 'string',
    'characters' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'nl_langinfo' => 
  array (
    0 => 'string|false',
    'item' => 'int',
  ),
  'strcoll' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'trim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'rtrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'chop' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'ltrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'wordwrap' => 
  array (
    0 => 'string',
    'string' => 'string',
    'width=' => 'int',
    'break=' => 'string',
    'cut_long_words=' => 'bool',
  ),
  'explode' => 
  array (
    0 => 'array',
    'separator' => 'string',
    'string' => 'string',
    'limit=' => 'int',
  ),
  'implode' => 
  array (
    0 => 'string',
    'separator' => 'array|string',
    'array=' => 'array|null',
  ),
  'join' => 
  array (
    0 => 'string',
    'separator' => 'array|string',
    'array=' => 'array|null',
  ),
  'strtok' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'token=' => 'string|null',
  ),
  'strtoupper' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'strtolower' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_increment' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_decrement' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'basename' => 
  array (
    0 => 'string',
    'path' => 'string',
    'suffix=' => 'string',
  ),
  'dirname' => 
  array (
    0 => 'string',
    'path' => 'string',
    'levels=' => 'int',
  ),
  'pathinfo' => 
  array (
    0 => 'array|string',
    'path' => 'string',
    'flags=' => 'int',
  ),
  'stristr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strstr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strchr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'stripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strrpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strrchr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'str_contains' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'str_starts_with' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'str_ends_with' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'chunk_split' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length=' => 'int',
    'separator=' => 'string',
  ),
  'substr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
  ),
  'substr_replace' => 
  array (
    0 => 'array|string',
    'string' => 'array|string',
    'replace' => 'array|string',
    'offset' => 'array|int',
    'length=' => 'array|int|null|null',
  ),
  'quotemeta' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'ord' => 
  array (
    0 => 'int',
    'character' => 'string',
  ),
  'chr' => 
  array (
    0 => 'string',
    'codepoint' => 'int',
  ),
  'ucfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'lcfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'ucwords' => 
  array (
    0 => 'string',
    'string' => 'string',
    'separators=' => 'string',
  ),
  'strtr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'from' => 'array|string',
    'to=' => 'string|null',
  ),
  'strrev' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'similar_text' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    '&percent=' => 'mixed',
  ),
  'addcslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters' => 'string',
  ),
  'addslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'stripcslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'stripslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_replace' => 
  array (
    0 => 'array|string',
    'search' => 'array|string',
    'replace' => 'array|string',
    'subject' => 'array|string',
    '&count=' => 'mixed',
  ),
  'str_ireplace' => 
  array (
    0 => 'array|string',
    'search' => 'array|string',
    'replace' => 'array|string',
    'subject' => 'array|string',
    '&count=' => 'mixed',
  ),
  'hebrev' => 
  array (
    0 => 'string',
    'string' => 'string',
    'max_chars_per_line=' => 'int',
  ),
  'nl2br' => 
  array (
    0 => 'string',
    'string' => 'string',
    'use_xhtml=' => 'bool',
  ),
  'strip_tags' => 
  array (
    0 => 'string',
    'string' => 'string',
    'allowed_tags=' => 'array|string|null|null',
  ),
  'setlocale' => 
  array (
    0 => 'string|false',
    'category' => 'int',
    'locales' => 'mixed',
    '...rest=' => 'mixed',
  ),
  'parse_str' => 
  array (
    0 => 'void',
    'string' => 'string',
    '&result' => 'mixed',
  ),
  'str_getcsv' => 
  array (
    0 => 'array',
    'string' => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'str_repeat' => 
  array (
    0 => 'string',
    'string' => 'string',
    'times' => 'int',
  ),
  'count_chars' => 
  array (
    0 => 'array|string',
    'string' => 'string',
    'mode=' => 'int',
  ),
  'strnatcmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'localeconv' => 
  array (
    0 => 'array',
  ),
  'strnatcasecmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'substr_count' => 
  array (
    0 => 'int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'str_pad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length' => 'int',
    'pad_string=' => 'string',
    'pad_type=' => 'int',
  ),
  'sscanf' => 
  array (
    0 => 'array|int|null|null',
    'string' => 'string',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'str_rot13' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_shuffle' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_word_count' => 
  array (
    0 => 'array|int',
    'string' => 'string',
    'format=' => 'int',
    'characters=' => 'string|null',
  ),
  'str_split' => 
  array (
    0 => 'array',
    'string' => 'string',
    'length=' => 'int',
  ),
  'strpbrk' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'characters' => 'string',
  ),
  'substr_compare' => 
  array (
    0 => 'int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
    'case_insensitive=' => 'bool',
  ),
  'utf8_encode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'utf8_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'opendir' => 
  array (
    0 => 'mixed',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'dir' => 
  array (
    0 => 'Directory|false',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'closedir' => 
  array (
    0 => 'void',
    'dir_handle=' => 'mixed',
  ),
  'chdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
  ),
  'chroot' => 
  array (
    0 => 'bool',
    'directory' => 'string',
  ),
  'getcwd' => 
  array (
    0 => 'string|false',
  ),
  'rewinddir' => 
  array (
    0 => 'void',
    'dir_handle=' => 'mixed',
  ),
  'readdir' => 
  array (
    0 => 'string|false',
    'dir_handle=' => 'mixed',
  ),
  'scandir' => 
  array (
    0 => 'array|false',
    'directory' => 'string',
    'sorting_order=' => 'int',
    'context=' => 'mixed',
  ),
  'glob' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'flags=' => 'int',
  ),
  'exec' => 
  array (
    0 => 'string|false',
    'command' => 'string',
    '&output=' => 'mixed',
    '&result_code=' => 'mixed',
  ),
  'system' => 
  array (
    0 => 'string|false',
    'command' => 'string',
    '&result_code=' => 'mixed',
  ),
  'passthru' => 
  array (
    0 => 'false|null',
    'command' => 'string',
    '&result_code=' => 'mixed',
  ),
  'escapeshellcmd' => 
  array (
    0 => 'string',
    'command' => 'string',
  ),
  'escapeshellarg' => 
  array (
    0 => 'string',
    'arg' => 'string',
  ),
  'shell_exec' => 
  array (
    0 => 'string|false|null|null',
    'command' => 'string',
  ),
  'proc_nice' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'flock' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'operation' => 'int',
    '&would_block=' => 'mixed',
  ),
  'get_meta_tags' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'pclose' => 
  array (
    0 => 'int',
    'handle' => 'mixed',
  ),
  'popen' => 
  array (
    0 => 'mixed',
    'command' => 'string',
    'mode' => 'string',
  ),
  'readfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
  ),
  'rewind' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'rmdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'umask' => 
  array (
    0 => 'int',
    'mask=' => 'int|null',
  ),
  'fclose' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'feof' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fgetc' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
  ),
  'fgets' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length=' => 'int|null',
  ),
  'fread' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length' => 'int',
  ),
  'fopen' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
  ),
  'fscanf' => 
  array (
    0 => 'array|int|false|null|null',
    'stream' => 'mixed',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'fpassthru' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
  ),
  'ftruncate' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'fstat' => 
  array (
    0 => 'array|false',
    'stream' => 'mixed',
  ),
  'fseek' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'ftell' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
  ),
  'fflush' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fsync' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fdatasync' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fwrite' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'fputs' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'mkdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'permissions=' => 'int',
    'recursive=' => 'bool',
    'context=' => 'mixed',
  ),
  'rename' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'mixed',
  ),
  'copy' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'mixed',
  ),
  'tempnam' => 
  array (
    0 => 'string|false',
    'directory' => 'string',
    'prefix' => 'string',
  ),
  'tmpfile' => 
  array (
    0 => 'mixed',
  ),
  'file' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'file_get_contents' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'unlink' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'context=' => 'mixed',
  ),
  'file_put_contents' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'data' => 'mixed',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'fputcsv' => 
  array (
    0 => 'int|false',
    'stream' => 'mixed',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'fgetcsv' => 
  array (
    0 => 'array|false',
    'stream' => 'mixed',
    'length=' => 'int|null',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'realpath' => 
  array (
    0 => 'string|false',
    'path' => 'string',
  ),
  'fnmatch' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'sys_get_temp_dir' => 
  array (
    0 => 'string',
  ),
  'fileatime' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'filectime' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'filegroup' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'fileinode' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'filemtime' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'fileowner' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'fileperms' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'filesize' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'filetype' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
  ),
  'file_exists' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_writable' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_writeable' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_readable' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_executable' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_file' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_dir' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_link' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'stat' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
  ),
  'lstat' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
  ),
  'chown' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'user' => 'string|int',
  ),
  'chgrp' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'group' => 'string|int',
  ),
  'lchown' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'user' => 'string|int',
  ),
  'lchgrp' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'group' => 'string|int',
  ),
  'chmod' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'permissions' => 'int',
  ),
  'touch' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'mtime=' => 'int|null',
    'atime=' => 'int|null',
  ),
  'clearstatcache' => 
  array (
    0 => 'void',
    'clear_realpath_cache=' => 'bool',
    'filename=' => 'string',
  ),
  'disk_total_space' => 
  array (
    0 => 'float|false',
    'directory' => 'string',
  ),
  'disk_free_space' => 
  array (
    0 => 'float|false',
    'directory' => 'string',
  ),
  'diskfreespace' => 
  array (
    0 => 'float|false',
    'directory' => 'string',
  ),
  'realpath_cache_get' => 
  array (
    0 => 'array',
  ),
  'realpath_cache_size' => 
  array (
    0 => 'int',
  ),
  'sprintf' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'printf' => 
  array (
    0 => 'int',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'vprintf' => 
  array (
    0 => 'int',
    'format' => 'string',
    'values' => 'array',
  ),
  'vsprintf' => 
  array (
    0 => 'string',
    'format' => 'string',
    'values' => 'array',
  ),
  'fprintf' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'vfprintf' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'format' => 'string',
    'values' => 'array',
  ),
  'fsockopen' => 
  array (
    0 => 'mixed',
    'hostname' => 'string',
    'port=' => 'int',
    '&error_code=' => 'mixed',
    '&error_message=' => 'mixed',
    'timeout=' => 'float|null',
  ),
  'pfsockopen' => 
  array (
    0 => 'mixed',
    'hostname' => 'string',
    'port=' => 'int',
    '&error_code=' => 'mixed',
    '&error_message=' => 'mixed',
    'timeout=' => 'float|null',
  ),
  'http_build_query' => 
  array (
    0 => 'string',
    'data' => 'object|array',
    'numeric_prefix=' => 'string',
    'arg_separator=' => 'string|null',
    'encoding_type=' => 'int',
  ),
  'http_get_last_response_headers' => 
  array (
    0 => 'array|null',
  ),
  'http_clear_last_response_headers' => 
  array (
    0 => 'void',
  ),
  'request_parse_body' => 
  array (
    0 => 'array',
    'options=' => 'array|null',
  ),
  'image_type_to_mime_type' => 
  array (
    0 => 'string',
    'image_type' => 'int',
  ),
  'image_type_to_extension' => 
  array (
    0 => 'string|false',
    'image_type' => 'int',
    'include_dot=' => 'bool',
  ),
  'getimagesize' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    '&image_info=' => 'mixed',
  ),
  'getimagesizefromstring' => 
  array (
    0 => 'array|false',
    'string' => 'string',
    '&image_info=' => 'mixed',
  ),
  'phpinfo' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'phpversion' => 
  array (
    0 => 'string|false',
    'extension=' => 'string|null',
  ),
  'phpcredits' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'php_sapi_name' => 
  array (
    0 => 'string|false',
  ),
  'php_uname' => 
  array (
    0 => 'string',
    'mode=' => 'string',
  ),
  'php_ini_scanned_files' => 
  array (
    0 => 'string|false',
  ),
  'php_ini_loaded_file' => 
  array (
    0 => 'string|false',
  ),
  'iptcembed' => 
  array (
    0 => 'string|bool',
    'iptc_data' => 'string',
    'filename' => 'string',
    'spool=' => 'int',
  ),
  'iptcparse' => 
  array (
    0 => 'array|false',
    'iptc_block' => 'string',
  ),
  'levenshtein' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    'insertion_cost=' => 'int',
    'replacement_cost=' => 'int',
    'deletion_cost=' => 'int',
  ),
  'readlink' => 
  array (
    0 => 'string|false',
    'path' => 'string',
  ),
  'linkinfo' => 
  array (
    0 => 'int|false',
    'path' => 'string',
  ),
  'symlink' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'link' => 'string',
  ),
  'link' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'link' => 'string',
  ),
  'mail' => 
  array (
    0 => 'bool',
    'to' => 'string',
    'subject' => 'string',
    'message' => 'string',
    'additional_headers=' => 'array|string',
    'additional_params=' => 'string',
  ),
  'abs' => 
  array (
    0 => 'int|float',
    'num' => 'int|float',
  ),
  'ceil' => 
  array (
    0 => 'float',
    'num' => 'int|float',
  ),
  'floor' => 
  array (
    0 => 'float',
    'num' => 'int|float',
  ),
  'round' => 
  array (
    0 => 'float',
    'num' => 'int|float',
    'precision=' => 'int',
    'mode=' => 'RoundingMode|int',
  ),
  'sin' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'cos' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'tan' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'asin' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'acos' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'atan' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'atanh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'atan2' => 
  array (
    0 => 'float',
    'y' => 'float',
    'x' => 'float',
  ),
  'sinh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'cosh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'tanh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'asinh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'acosh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'expm1' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'log1p' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'pi' => 
  array (
    0 => 'float',
  ),
  'is_finite' => 
  array (
    0 => 'bool',
    'num' => 'float',
  ),
  'is_nan' => 
  array (
    0 => 'bool',
    'num' => 'float',
  ),
  'intdiv' => 
  array (
    0 => 'int',
    'num1' => 'int',
    'num2' => 'int',
  ),
  'is_infinite' => 
  array (
    0 => 'bool',
    'num' => 'float',
  ),
  'pow' => 
  array (
    0 => 'object|int|float',
    'num' => 'mixed',
    'exponent' => 'mixed',
  ),
  'exp' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'log' => 
  array (
    0 => 'float',
    'num' => 'float',
    'base=' => 'float',
  ),
  'log10' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'sqrt' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'hypot' => 
  array (
    0 => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'deg2rad' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'rad2deg' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'bindec' => 
  array (
    0 => 'int|float',
    'binary_string' => 'string',
  ),
  'hexdec' => 
  array (
    0 => 'int|float',
    'hex_string' => 'string',
  ),
  'octdec' => 
  array (
    0 => 'int|float',
    'octal_string' => 'string',
  ),
  'decbin' => 
  array (
    0 => 'string',
    'num' => 'int',
  ),
  'decoct' => 
  array (
    0 => 'string',
    'num' => 'int',
  ),
  'dechex' => 
  array (
    0 => 'string',
    'num' => 'int',
  ),
  'base_convert' => 
  array (
    0 => 'string',
    'num' => 'string',
    'from_base' => 'int',
    'to_base' => 'int',
  ),
  'number_format' => 
  array (
    0 => 'string',
    'num' => 'float',
    'decimals=' => 'int',
    'decimal_separator=' => 'string|null',
    'thousands_separator=' => 'string|null',
  ),
  'fmod' => 
  array (
    0 => 'float',
    'num1' => 'float',
    'num2' => 'float',
  ),
  'fdiv' => 
  array (
    0 => 'float',
    'num1' => 'float',
    'num2' => 'float',
  ),
  'fpow' => 
  array (
    0 => 'float',
    'num' => 'float',
    'exponent' => 'float',
  ),
  'microtime' => 
  array (
    0 => 'string|float',
    'as_float=' => 'bool',
  ),
  'gettimeofday' => 
  array (
    0 => 'array|float',
    'as_float=' => 'bool',
  ),
  'getrusage' => 
  array (
    0 => 'array|false',
    'mode=' => 'int',
  ),
  'pack' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'unpack' => 
  array (
    0 => 'array|false',
    'format' => 'string',
    'string' => 'string',
    'offset=' => 'int',
  ),
  'password_get_info' => 
  array (
    0 => 'array',
    'hash' => 'string',
  ),
  'password_hash' => 
  array (
    0 => 'string',
    'password' => 'string',
    'algo' => 'string|int|null|null',
    'options=' => 'array',
  ),
  'password_needs_rehash' => 
  array (
    0 => 'bool',
    'hash' => 'string',
    'algo' => 'string|int|null|null',
    'options=' => 'array',
  ),
  'password_verify' => 
  array (
    0 => 'bool',
    'password' => 'string',
    'hash' => 'string',
  ),
  'password_algos' => 
  array (
    0 => 'array',
  ),
  'proc_open' => 
  array (
    0 => 'mixed',
    'command' => 'array|string',
    'descriptor_spec' => 'array',
    '&pipes' => 'mixed',
    'cwd=' => 'string|null',
    'env_vars=' => 'array|null',
    'options=' => 'array|null',
  ),
  'proc_close' => 
  array (
    0 => 'int',
    'process' => 'mixed',
  ),
  'proc_terminate' => 
  array (
    0 => 'bool',
    'process' => 'mixed',
    'signal=' => 'int',
  ),
  'proc_get_status' => 
  array (
    0 => 'array',
    'process' => 'mixed',
  ),
  'quoted_printable_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'quoted_printable_encode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'soundex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'stream_select' => 
  array (
    0 => 'int|false',
    '&read' => 'array|null',
    '&write' => 'array|null',
    '&except' => 'array|null',
    'seconds' => 'int|null',
    'microseconds=' => 'int|null',
  ),
  'stream_context_create' => 
  array (
    0 => 'mixed',
    'options=' => 'array|null',
    'params=' => 'array|null',
  ),
  'stream_context_set_params' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'params' => 'array',
  ),
  'stream_context_get_params' => 
  array (
    0 => 'array',
    'context' => 'mixed',
  ),
  'stream_context_set_option' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'wrapper_or_options' => 'array|string',
    'option_name=' => 'string|null',
    'value=' => 'mixed',
  ),
  'stream_context_set_options' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'options' => 'array',
  ),
  'stream_context_get_options' => 
  array (
    0 => 'array',
    'stream_or_context' => 'mixed',
  ),
  'stream_context_get_default' => 
  array (
    0 => 'mixed',
    'options=' => 'array|null',
  ),
  'stream_context_set_default' => 
  array (
    0 => 'mixed',
    'options' => 'array',
  ),
  'stream_filter_prepend' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed',
  ),
  'stream_filter_append' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed',
  ),
  'stream_filter_remove' => 
  array (
    0 => 'bool',
    'stream_filter' => 'mixed',
  ),
  'stream_socket_client' => 
  array (
    0 => 'mixed',
    'address' => 'string',
    '&error_code=' => 'mixed',
    '&error_message=' => 'mixed',
    'timeout=' => 'float|null',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'stream_socket_server' => 
  array (
    0 => 'mixed',
    'address' => 'string',
    '&error_code=' => 'mixed',
    '&error_message=' => 'mixed',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'stream_socket_accept' => 
  array (
    0 => 'mixed',
    'socket' => 'mixed',
    'timeout=' => 'float|null',
    '&peer_name=' => 'mixed',
  ),
  'stream_socket_get_name' => 
  array (
    0 => 'string|false',
    'socket' => 'mixed',
    'remote' => 'bool',
  ),
  'stream_socket_recvfrom' => 
  array (
    0 => 'string|false',
    'socket' => 'mixed',
    'length' => 'int',
    'flags=' => 'int',
    '&address=' => 'mixed',
  ),
  'stream_socket_sendto' => 
  array (
    0 => 'int|false',
    'socket' => 'mixed',
    'data' => 'string',
    'flags=' => 'int',
    'address=' => 'string',
  ),
  'stream_socket_enable_crypto' => 
  array (
    0 => 'int|bool',
    'stream' => 'mixed',
    'enable' => 'bool',
    'crypto_method=' => 'int|null',
    'session_stream=' => 'mixed',
  ),
  'stream_socket_shutdown' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'mode' => 'int',
  ),
  'stream_socket_pair' => 
  array (
    0 => 'array|false',
    'domain' => 'int',
    'type' => 'int',
    'protocol' => 'int',
  ),
  'stream_copy_to_stream' => 
  array (
    0 => 'int|false',
    'from' => 'mixed',
    'to' => 'mixed',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_get_contents' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_supports_lock' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'stream_set_write_buffer' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'set_file_buffer' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'stream_set_read_buffer' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'stream_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'enable' => 'bool',
  ),
  'socket_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'enable' => 'bool',
  ),
  'stream_get_meta_data' => 
  array (
    0 => 'array',
    'stream' => 'mixed',
  ),
  'socket_get_status' => 
  array (
    0 => 'array',
    'stream' => 'mixed',
  ),
  'stream_get_line' => 
  array (
    0 => 'string|false',
    'stream' => 'mixed',
    'length' => 'int',
    'ending=' => 'string',
  ),
  'stream_resolve_include_path' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
  ),
  'stream_get_wrappers' => 
  array (
    0 => 'array',
  ),
  'stream_get_transports' => 
  array (
    0 => 'array',
  ),
  'stream_is_local' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'stream_isatty' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'stream_set_chunk_size' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'stream_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'socket_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'gettype' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'get_debug_type' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'settype' => 
  array (
    0 => 'bool',
    '&var' => 'mixed',
    'type' => 'string',
  ),
  'intval' => 
  array (
    0 => 'int',
    'value' => 'mixed',
    'base=' => 'int',
  ),
  'floatval' => 
  array (
    0 => 'float',
    'value' => 'mixed',
  ),
  'doubleval' => 
  array (
    0 => 'float',
    'value' => 'mixed',
  ),
  'boolval' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'strval' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'is_null' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_resource' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_bool' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_int' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_integer' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_long' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_float' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_double' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_numeric' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_string' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_array' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_object' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_scalar' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_callable' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
    'syntax_only=' => 'bool',
    '&callable_name=' => 'mixed',
  ),
  'is_iterable' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_countable' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'uniqid' => 
  array (
    0 => 'string',
    'prefix=' => 'string',
    'more_entropy=' => 'bool',
  ),
  'parse_url' => 
  array (
    0 => 'array|string|int|false|null|null',
    'url' => 'string',
    'component=' => 'int',
  ),
  'urlencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'urldecode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'rawurlencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'rawurldecode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'get_headers' => 
  array (
    0 => 'array|false',
    'url' => 'string',
    'associative=' => 'bool',
    'context=' => 'mixed',
  ),
  'stream_bucket_make_writeable' => 
  array (
    0 => 'StreamBucket|null',
    'brigade' => 'mixed',
  ),
  'stream_bucket_prepend' => 
  array (
    0 => 'void',
    'brigade' => 'mixed',
    'bucket' => 'StreamBucket',
  ),
  'stream_bucket_append' => 
  array (
    0 => 'void',
    'brigade' => 'mixed',
    'bucket' => 'StreamBucket',
  ),
  'stream_bucket_new' => 
  array (
    0 => 'StreamBucket',
    'stream' => 'mixed',
    'buffer' => 'string',
  ),
  'stream_get_filters' => 
  array (
    0 => 'array',
  ),
  'stream_filter_register' => 
  array (
    0 => 'bool',
    'filter_name' => 'string',
    'class' => 'string',
  ),
  'convert_uuencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'convert_uudecode' => 
  array (
    0 => 'string|false',
    'string' => 'string',
  ),
  'var_dump' => 
  array (
    0 => 'void',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'var_export' => 
  array (
    0 => 'string|null',
    'value' => 'mixed',
    'return=' => 'bool',
  ),
  'debug_zval_dump' => 
  array (
    0 => 'void',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'serialize' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'unserialize' => 
  array (
    0 => 'mixed',
    'data' => 'string',
    'options=' => 'array',
  ),
  'memory_get_usage' => 
  array (
    0 => 'int',
    'real_usage=' => 'bool',
  ),
  'memory_get_peak_usage' => 
  array (
    0 => 'int',
    'real_usage=' => 'bool',
  ),
  'memory_reset_peak_usage' => 
  array (
    0 => 'void',
  ),
  'version_compare' => 
  array (
    0 => 'int|bool',
    'version1' => 'string',
    'version2' => 'string',
    'operator=' => 'string|null',
  ),
  'class_implements' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'class_parents' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'class_uses' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'spl_autoload' => 
  array (
    0 => 'void',
    'class' => 'string',
    'file_extensions=' => 'string|null',
  ),
  'spl_autoload_call' => 
  array (
    0 => 'void',
    'class' => 'string',
  ),
  'spl_autoload_extensions' => 
  array (
    0 => 'string',
    'file_extensions=' => 'string|null',
  ),
  'spl_autoload_functions' => 
  array (
    0 => 'array',
  ),
  'spl_autoload_register' => 
  array (
    0 => 'bool',
    'callback=' => 'callable|null',
    'throw=' => 'bool',
    'prepend=' => 'bool',
  ),
  'spl_autoload_unregister' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'spl_classes' => 
  array (
    0 => 'array',
  ),
  'spl_object_hash' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'spl_object_id' => 
  array (
    0 => 'int',
    'object' => 'object',
  ),
  'iterator_apply' => 
  array (
    0 => 'int',
    'iterator' => 'Traversable',
    'callback' => 'callable',
    'args=' => 'array|null',
  ),
  'iterator_count' => 
  array (
    0 => 'int',
    'iterator' => 'Traversable|array',
  ),
  'iterator_to_array' => 
  array (
    0 => 'array',
    'iterator' => 'Traversable|array',
    'preserve_keys=' => 'bool',
  ),
  'pdo_drivers' => 
  array (
    0 => 'array',
  ),
  'posix_kill' => 
  array (
    0 => 'bool',
    'process_id' => 'int',
    'signal' => 'int',
  ),
  'posix_getpid' => 
  array (
    0 => 'int',
  ),
  'posix_getppid' => 
  array (
    0 => 'int',
  ),
  'posix_getuid' => 
  array (
    0 => 'int',
  ),
  'posix_setuid' => 
  array (
    0 => 'bool',
    'user_id' => 'int',
  ),
  'posix_geteuid' => 
  array (
    0 => 'int',
  ),
  'posix_seteuid' => 
  array (
    0 => 'bool',
    'user_id' => 'int',
  ),
  'posix_getgid' => 
  array (
    0 => 'int',
  ),
  'posix_setgid' => 
  array (
    0 => 'bool',
    'group_id' => 'int',
  ),
  'posix_getegid' => 
  array (
    0 => 'int',
  ),
  'posix_setegid' => 
  array (
    0 => 'bool',
    'group_id' => 'int',
  ),
  'posix_getgroups' => 
  array (
    0 => 'array|false',
  ),
  'posix_getlogin' => 
  array (
    0 => 'string|false',
  ),
  'posix_getpgrp' => 
  array (
    0 => 'int',
  ),
  'posix_setsid' => 
  array (
    0 => 'int',
  ),
  'posix_setpgid' => 
  array (
    0 => 'bool',
    'process_id' => 'int',
    'process_group_id' => 'int',
  ),
  'posix_getpgid' => 
  array (
    0 => 'int|false',
    'process_id' => 'int',
  ),
  'posix_getsid' => 
  array (
    0 => 'int|false',
    'process_id' => 'int',
  ),
  'posix_uname' => 
  array (
    0 => 'array|false',
  ),
  'posix_times' => 
  array (
    0 => 'array|false',
  ),
  'posix_ctermid' => 
  array (
    0 => 'string|false',
  ),
  'posix_ttyname' => 
  array (
    0 => 'string|false',
    'file_descriptor' => 'mixed',
  ),
  'posix_isatty' => 
  array (
    0 => 'bool',
    'file_descriptor' => 'mixed',
  ),
  'posix_getcwd' => 
  array (
    0 => 'string|false',
  ),
  'posix_mkfifo' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'permissions' => 'int',
  ),
  'posix_mknod' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags' => 'int',
    'major=' => 'int',
    'minor=' => 'int',
  ),
  'posix_access' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'posix_eaccess' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'posix_getgrnam' => 
  array (
    0 => 'array|false',
    'name' => 'string',
  ),
  'posix_getgrgid' => 
  array (
    0 => 'array|false',
    'group_id' => 'int',
  ),
  'posix_getpwnam' => 
  array (
    0 => 'array|false',
    'username' => 'string',
  ),
  'posix_getpwuid' => 
  array (
    0 => 'array|false',
    'user_id' => 'int',
  ),
  'posix_getrlimit' => 
  array (
    0 => 'array|false',
    'resource=' => 'int|null',
  ),
  'posix_setrlimit' => 
  array (
    0 => 'bool',
    'resource' => 'int',
    'soft_limit' => 'int',
    'hard_limit' => 'int',
  ),
  'posix_get_last_error' => 
  array (
    0 => 'int',
  ),
  'posix_errno' => 
  array (
    0 => 'int',
  ),
  'posix_strerror' => 
  array (
    0 => 'string',
    'error_code' => 'int',
  ),
  'posix_initgroups' => 
  array (
    0 => 'bool',
    'username' => 'string',
    'group_id' => 'int',
  ),
  'posix_sysconf' => 
  array (
    0 => 'int',
    'conf_id' => 'int',
  ),
  'lcg_value' => 
  array (
    0 => 'float',
  ),
  'mt_srand' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'srand' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'rand' => 
  array (
    0 => 'int',
    'min=' => 'int',
    'max=' => 'int',
  ),
  'mt_rand' => 
  array (
    0 => 'int',
    'min=' => 'int',
    'max=' => 'int',
  ),
  'mt_getrandmax' => 
  array (
    0 => 'int',
  ),
  'getrandmax' => 
  array (
    0 => 'int',
  ),
  'random_bytes' => 
  array (
    0 => 'string',
    'length' => 'int',
  ),
  'random_int' => 
  array (
    0 => 'int',
    'min' => 'int',
    'max' => 'int',
  ),
  'readline' => 
  array (
    0 => 'string|false',
    'prompt=' => 'string|null',
  ),
  'readline_info' => 
  array (
    0 => 'mixed',
    'var_name=' => 'string|null',
    'value=' => 'mixed',
  ),
  'readline_add_history' => 
  array (
    0 => 'true',
    'prompt' => 'string',
  ),
  'readline_clear_history' => 
  array (
    0 => 'true',
  ),
  'readline_list_history' => 
  array (
    0 => 'array',
  ),
  'readline_read_history' => 
  array (
    0 => 'bool',
    'filename=' => 'string|null',
  ),
  'readline_write_history' => 
  array (
    0 => 'bool',
    'filename=' => 'string|null',
  ),
  'readline_completion_function' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'readline_callback_handler_install' => 
  array (
    0 => 'true',
    'prompt' => 'string',
    'callback' => 'callable',
  ),
  'readline_callback_read_char' => 
  array (
    0 => 'void',
  ),
  'readline_callback_handler_remove' => 
  array (
    0 => 'bool',
  ),
  'readline_redisplay' => 
  array (
    0 => 'void',
  ),
  'readline_on_new_line' => 
  array (
    0 => 'void',
  ),
  'session_name' => 
  array (
    0 => 'string|false',
    'name=' => 'string|null',
  ),
  'session_module_name' => 
  array (
    0 => 'string|false',
    'module=' => 'string|null',
  ),
  'session_save_path' => 
  array (
    0 => 'string|false',
    'path=' => 'string|null',
  ),
  'session_id' => 
  array (
    0 => 'string|false',
    'id=' => 'string|null',
  ),
  'session_create_id' => 
  array (
    0 => 'string|false',
    'prefix=' => 'string',
  ),
  'session_regenerate_id' => 
  array (
    0 => 'bool',
    'delete_old_session=' => 'bool',
  ),
  'session_decode' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'session_encode' => 
  array (
    0 => 'string|false',
  ),
  'session_destroy' => 
  array (
    0 => 'bool',
  ),
  'session_unset' => 
  array (
    0 => 'bool',
  ),
  'session_gc' => 
  array (
    0 => 'int|false',
  ),
  'session_get_cookie_params' => 
  array (
    0 => 'array',
  ),
  'session_write_close' => 
  array (
    0 => 'bool',
  ),
  'session_abort' => 
  array (
    0 => 'bool',
  ),
  'session_reset' => 
  array (
    0 => 'bool',
  ),
  'session_status' => 
  array (
    0 => 'int',
  ),
  'session_register_shutdown' => 
  array (
    0 => 'void',
  ),
  'session_commit' => 
  array (
    0 => 'bool',
  ),
  'session_set_save_handler' => 
  array (
    0 => 'bool',
    'open' => 'mixed',
    'close=' => 'mixed',
    'read=' => 'callable',
    'write=' => 'callable',
    'destroy=' => 'callable',
    'gc=' => 'callable',
    'create_sid=' => 'callable|null',
    'validate_sid=' => 'callable|null',
    'update_timestamp=' => 'callable|null',
  ),
  'session_cache_limiter' => 
  array (
    0 => 'string|false',
    'value=' => 'string|null',
  ),
  'session_cache_expire' => 
  array (
    0 => 'int|false',
    'value=' => 'int|null',
  ),
  'session_set_cookie_params' => 
  array (
    0 => 'bool',
    'lifetime_or_options' => 'array|int',
    'path=' => 'string|null',
    'domain=' => 'string|null',
    'secure=' => 'bool|null',
    'httponly=' => 'bool|null',
  ),
  'session_start' => 
  array (
    0 => 'bool',
    'options=' => 'array',
  ),
  'simplexml_load_file' => 
  array (
    0 => 'SimpleXMLElement|false',
    'filename' => 'string',
    'class_name=' => 'string|null',
    'options=' => 'int',
    'namespace_or_prefix=' => 'string',
    'is_prefix=' => 'bool',
  ),
  'simplexml_load_string' => 
  array (
    0 => 'SimpleXMLElement|false',
    'data' => 'string',
    'class_name=' => 'string|null',
    'options=' => 'int',
    'namespace_or_prefix=' => 'string',
    'is_prefix=' => 'bool',
  ),
  'simplexml_import_dom' => 
  array (
    0 => 'SimpleXMLElement|null',
    'node' => 'object',
    'class_name=' => 'string|null',
  ),
  'token_get_all' => 
  array (
    0 => 'array',
    'code' => 'string',
    'flags=' => 'int',
  ),
  'token_name' => 
  array (
    0 => 'string',
    'id' => 'int',
  ),
  'xml_parser_create' => 
  array (
    0 => 'XMLParser',
    'encoding=' => 'string|null',
  ),
  'xml_parser_create_ns' => 
  array (
    0 => 'XMLParser',
    'encoding=' => 'string|null',
    'separator=' => 'string',
  ),
  'xml_set_object' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'object' => 'object',
  ),
  'xml_set_element_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'start_handler' => 'callable|string|null|null',
    'end_handler' => 'callable|string|null|null',
  ),
  'xml_set_character_data_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_processing_instruction_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_default_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_unparsed_entity_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_notation_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_external_entity_ref_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_start_namespace_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_set_end_namespace_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|string|null|null',
  ),
  'xml_parse' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
    'data' => 'string',
    'is_final=' => 'bool',
  ),
  'xml_parse_into_struct' => 
  array (
    0 => 'int|false',
    'parser' => 'XMLParser',
    'data' => 'string',
    '&values' => 'mixed',
    '&index=' => 'mixed',
  ),
  'xml_get_error_code' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_error_string' => 
  array (
    0 => 'string|null',
    'error_code' => 'int',
  ),
  'xml_get_current_line_number' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_get_current_column_number' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_get_current_byte_index' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_parser_free' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
  ),
  'xml_parser_set_option' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'xml_parser_get_option' => 
  array (
    0 => 'string|int|bool',
    'parser' => 'XMLParser',
    'option' => 'int',
  ),
  'xmlwriter_open_uri' => 
  array (
    0 => 'XMLWriter|false',
    'uri' => 'string',
  ),
  'xmlwriter_open_memory' => 
  array (
    0 => 'XMLWriter|false',
  ),
  'xmlwriter_set_indent' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'enable' => 'bool',
  ),
  'xmlwriter_set_indent_string' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'indentation' => 'string',
  ),
  'xmlwriter_start_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_start_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_end_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'value' => 'string',
  ),
  'xmlwriter_start_attribute_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'xmlwriter_write_attribute_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'value' => 'string',
  ),
  'xmlwriter_start_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_end_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_full_end_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_start_element_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'xmlwriter_write_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content=' => 'string|null',
  ),
  'xmlwriter_write_element_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'content=' => 'string|null',
  ),
  'xmlwriter_start_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'target' => 'string',
  ),
  'xmlwriter_end_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'target' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_start_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_text' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_write_raw' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_start_document' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'version=' => 'string|null',
    'encoding=' => 'string|null',
    'standalone=' => 'string|null',
  ),
  'xmlwriter_end_document' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_start_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'qualifiedName' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
  ),
  'xmlwriter_end_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'content=' => 'string|null',
  ),
  'xmlwriter_start_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'qualifiedName' => 'string',
  ),
  'xmlwriter_end_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_start_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_end_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_start_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'isParam' => 'bool',
  ),
  'xmlwriter_end_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_write_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
    'isParam=' => 'bool',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'notationData=' => 'string|null',
  ),
  'xmlwriter_output_memory' => 
  array (
    0 => 'string',
    'writer' => 'XMLWriter',
    'flush=' => 'bool',
  ),
  'xmlwriter_flush' => 
  array (
    0 => 'string|int',
    'writer' => 'XMLWriter',
    'empty=' => 'bool',
  ),
  'apcu_clear_cache' => 
  array (
    0 => 'bool',
  ),
  'apcu_cache_info' => 
  array (
    0 => 'array|false',
    'limited=' => 'bool',
  ),
  'apcu_key_info' => 
  array (
    0 => 'array|null',
    'key' => 'string',
  ),
  'apcu_sma_info' => 
  array (
    0 => 'array|false',
    'limited=' => 'bool',
  ),
  'apcu_enabled' => 
  array (
    0 => 'bool',
  ),
  'apcu_store' => 
  array (
    0 => 'array|bool',
    'key' => 'mixed',
    'value=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_add' => 
  array (
    0 => 'array|bool',
    'key' => 'mixed',
    'value=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_inc' => 
  array (
    0 => 'int|false',
    'key' => 'string',
    'step=' => 'int',
    '&success=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_dec' => 
  array (
    0 => 'int|false',
    'key' => 'string',
    'step=' => 'int',
    '&success=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_cas' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'old' => 'int',
    'new' => 'int',
  ),
  'apcu_fetch' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
    '&success=' => 'mixed',
  ),
  'apcu_exists' => 
  array (
    0 => 'array|bool',
    'key' => 'mixed',
  ),
  'apcu_delete' => 
  array (
    0 => 'array|bool',
    'key' => 'mixed',
  ),
  'apcu_entry' => 
  array (
    0 => 'mixed',
    'key' => 'string',
    'callback' => 'callable',
    'ttl=' => 'int',
  ),
  'bcadd' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcsub' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcmul' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcdiv' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcmod' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcdivmod' => 
  array (
    0 => 'array',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcpowmod' => 
  array (
    0 => 'string',
    'num' => 'string',
    'exponent' => 'string',
    'modulus' => 'string',
    'scale=' => 'int|null',
  ),
  'bcpow' => 
  array (
    0 => 'string',
    'num' => 'string',
    'exponent' => 'string',
    'scale=' => 'int|null',
  ),
  'bcsqrt' => 
  array (
    0 => 'string',
    'num' => 'string',
    'scale=' => 'int|null',
  ),
  'bccomp' => 
  array (
    0 => 'int',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcscale' => 
  array (
    0 => 'int',
    'scale=' => 'int|null',
  ),
  'bcfloor' => 
  array (
    0 => 'string',
    'num' => 'string',
  ),
  'bcceil' => 
  array (
    0 => 'string',
    'num' => 'string',
  ),
  'bcround' => 
  array (
    0 => 'string',
    'num' => 'string',
    'precision=' => 'int',
    'mode=' => 'RoundingMode',
  ),
  'gd_info' => 
  array (
    0 => 'array',
  ),
  'imageloadfont' => 
  array (
    0 => 'GdFont|false',
    'filename' => 'string',
  ),
  'imagesetstyle' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'style' => 'array',
  ),
  'imagecreatetruecolor' => 
  array (
    0 => 'GdImage|false',
    'width' => 'int',
    'height' => 'int',
  ),
  'imageistruecolor' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
  ),
  'imagetruecolortopalette' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'dither' => 'bool',
    'num_colors' => 'int',
  ),
  'imagepalettetotruecolor' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
  ),
  'imagecolormatch' => 
  array (
    0 => 'true',
    'image1' => 'GdImage',
    'image2' => 'GdImage',
  ),
  'imagesetthickness' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'thickness' => 'int',
  ),
  'imagefilledellipse' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'color' => 'int',
  ),
  'imagefilledarc' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'start_angle' => 'int',
    'end_angle' => 'int',
    'color' => 'int',
    'style' => 'int',
  ),
  'imagealphablending' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagesavealpha' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagelayereffect' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'effect' => 'int',
  ),
  'imagecolorallocatealpha' => 
  array (
    0 => 'int|false',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
  ),
  'imagecolorresolvealpha' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
  ),
  'imagecolorclosestalpha' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
  ),
  'imagecolorexactalpha' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
  ),
  'imagecopyresampled' => 
  array (
    0 => 'true',
    'dst_image' => 'GdImage',
    'src_image' => 'GdImage',
    'dst_x' => 'int',
    'dst_y' => 'int',
    'src_x' => 'int',
    'src_y' => 'int',
    'dst_width' => 'int',
    'dst_height' => 'int',
    'src_width' => 'int',
    'src_height' => 'int',
  ),
  'imagerotate' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'angle' => 'float',
    'background_color' => 'int',
  ),
  'imagesettile' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'tile' => 'GdImage',
  ),
  'imagesetbrush' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'brush' => 'GdImage',
  ),
  'imagecreate' => 
  array (
    0 => 'GdImage|false',
    'width' => 'int',
    'height' => 'int',
  ),
  'imagetypes' => 
  array (
    0 => 'int',
  ),
  'imagecreatefromstring' => 
  array (
    0 => 'GdImage|false',
    'data' => 'string',
  ),
  'imagecreatefromavif' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromgif' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromjpeg' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefrompng' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromwebp' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromxbm' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromxpm' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromwbmp' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromgd' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromgd2' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromgd2part' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
    'x' => 'int',
    'y' => 'int',
    'width' => 'int',
    'height' => 'int',
  ),
  'imagecreatefrombmp' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromtga' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagexbm' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'filename' => 'string|null',
    'foreground_color=' => 'int|null',
  ),
  'imageavif' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
    'speed=' => 'int',
  ),
  'imagegif' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
  ),
  'imagepng' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
    'filters=' => 'int',
  ),
  'imagewebp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
  ),
  'imagejpeg' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
  ),
  'imagewbmp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'foreground_color=' => 'int|null',
  ),
  'imagegd' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string|null',
  ),
  'imagegd2' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string|null',
    'chunk_size=' => 'int',
    'mode=' => 'int',
  ),
  'imagebmp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'compressed=' => 'bool',
  ),
  'imagedestroy' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
  ),
  'imagecolorallocate' => 
  array (
    0 => 'int|false',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagepalettecopy' => 
  array (
    0 => 'void',
    'dst' => 'GdImage',
    'src' => 'GdImage',
  ),
  'imagecolorat' => 
  array (
    0 => 'int|false',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
  ),
  'imagecolorclosest' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagecolorclosesthwb' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagecolordeallocate' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'color' => 'int',
  ),
  'imagecolorresolve' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagecolorexact' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagecolorset' => 
  array (
    0 => 'false|null',
    'image' => 'GdImage',
    'color' => 'int',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha=' => 'int',
  ),
  'imagecolorsforindex' => 
  array (
    0 => 'array',
    'image' => 'GdImage',
    'color' => 'int',
  ),
  'imagegammacorrect' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'input_gamma' => 'float',
    'output_gamma' => 'float',
  ),
  'imagesetpixel' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
  ),
  'imageline' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagedashedline' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagerectangle' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagefilledrectangle' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagearc' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'start_angle' => 'int',
    'end_angle' => 'int',
    'color' => 'int',
  ),
  'imageellipse' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'color' => 'int',
  ),
  'imagefilltoborder' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'border_color' => 'int',
    'color' => 'int',
  ),
  'imagefill' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
  ),
  'imagecolorstotal' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
  ),
  'imagecolortransparent' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'color=' => 'int|null',
  ),
  'imageinterlace' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'enable=' => 'bool|null',
  ),
  'imagepolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
  ),
  'imageopenpolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
  ),
  'imagefilledpolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
  ),
  'imagefontwidth' => 
  array (
    0 => 'int',
    'font' => 'GdFont|int',
  ),
  'imagefontheight' => 
  array (
    0 => 'int',
    'font' => 'GdFont|int',
  ),
  'imagechar' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'font' => 'GdFont|int',
    'x' => 'int',
    'y' => 'int',
    'char' => 'string',
    'color' => 'int',
  ),
  'imagecharup' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'font' => 'GdFont|int',
    'x' => 'int',
    'y' => 'int',
    'char' => 'string',
    'color' => 'int',
  ),
  'imagestring' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'font' => 'GdFont|int',
    'x' => 'int',
    'y' => 'int',
    'string' => 'string',
    'color' => 'int',
  ),
  'imagestringup' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'font' => 'GdFont|int',
    'x' => 'int',
    'y' => 'int',
    'string' => 'string',
    'color' => 'int',
  ),
  'imagecopy' => 
  array (
    0 => 'true',
    'dst_image' => 'GdImage',
    'src_image' => 'GdImage',
    'dst_x' => 'int',
    'dst_y' => 'int',
    'src_x' => 'int',
    'src_y' => 'int',
    'src_width' => 'int',
    'src_height' => 'int',
  ),
  'imagecopymerge' => 
  array (
    0 => 'true',
    'dst_image' => 'GdImage',
    'src_image' => 'GdImage',
    'dst_x' => 'int',
    'dst_y' => 'int',
    'src_x' => 'int',
    'src_y' => 'int',
    'src_width' => 'int',
    'src_height' => 'int',
    'pct' => 'int',
  ),
  'imagecopymergegray' => 
  array (
    0 => 'true',
    'dst_image' => 'GdImage',
    'src_image' => 'GdImage',
    'dst_x' => 'int',
    'dst_y' => 'int',
    'src_x' => 'int',
    'src_y' => 'int',
    'src_width' => 'int',
    'src_height' => 'int',
    'pct' => 'int',
  ),
  'imagecopyresized' => 
  array (
    0 => 'true',
    'dst_image' => 'GdImage',
    'src_image' => 'GdImage',
    'dst_x' => 'int',
    'dst_y' => 'int',
    'src_x' => 'int',
    'src_y' => 'int',
    'dst_width' => 'int',
    'dst_height' => 'int',
    'src_width' => 'int',
    'src_height' => 'int',
  ),
  'imagesx' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
  ),
  'imagesy' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
  ),
  'imagesetclip' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
  ),
  'imagegetclip' => 
  array (
    0 => 'array',
    'image' => 'GdImage',
  ),
  'imageftbbox' => 
  array (
    0 => 'array|false',
    'size' => 'float',
    'angle' => 'float',
    'font_filename' => 'string',
    'string' => 'string',
    'options=' => 'array',
  ),
  'imagefttext' => 
  array (
    0 => 'array|false',
    'image' => 'GdImage',
    'size' => 'float',
    'angle' => 'float',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
    'font_filename' => 'string',
    'text' => 'string',
    'options=' => 'array',
  ),
  'imagettfbbox' => 
  array (
    0 => 'array|false',
    'size' => 'float',
    'angle' => 'float',
    'font_filename' => 'string',
    'string' => 'string',
    'options=' => 'array',
  ),
  'imagettftext' => 
  array (
    0 => 'array|false',
    'image' => 'GdImage',
    'size' => 'float',
    'angle' => 'float',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
    'font_filename' => 'string',
    'text' => 'string',
    'options=' => 'array',
  ),
  'imagefilter' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'filter' => 'int',
    '...args=' => 'mixed',
  ),
  'imageconvolution' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'matrix' => 'array',
    'divisor' => 'float',
    'offset' => 'float',
  ),
  'imageflip' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'mode' => 'int',
  ),
  'imageantialias' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagecrop' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'rectangle' => 'array',
  ),
  'imagecropauto' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'mode=' => 'int',
    'threshold=' => 'float',
    'color=' => 'int',
  ),
  'imagescale' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'width' => 'int',
    'height=' => 'int',
    'mode=' => 'int',
  ),
  'imageaffine' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'affine' => 'array',
    'clip=' => 'array|null',
  ),
  'imageaffinematrixget' => 
  array (
    0 => 'array|false',
    'type' => 'int',
    'options' => 'mixed',
  ),
  'imageaffinematrixconcat' => 
  array (
    0 => 'array|false',
    'matrix1' => 'array',
    'matrix2' => 'array',
  ),
  'imagegetinterpolation' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
  ),
  'imagesetinterpolation' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'method=' => 'int',
  ),
  'imageresolution' => 
  array (
    0 => 'array|true',
    'image' => 'GdImage',
    'resolution_x=' => 'int|null',
    'resolution_y=' => 'int|null',
  ),
  'gmp_init' => 
  array (
    0 => 'GMP',
    'num' => 'string|int',
    'base=' => 'int',
  ),
  'gmp_import' => 
  array (
    0 => 'GMP',
    'data' => 'string',
    'word_size=' => 'int',
    'flags=' => 'int',
  ),
  'gmp_export' => 
  array (
    0 => 'string',
    'num' => 'GMP|string|int',
    'word_size=' => 'int',
    'flags=' => 'int',
  ),
  'gmp_intval' => 
  array (
    0 => 'int',
    'num' => 'GMP|string|int',
  ),
  'gmp_strval' => 
  array (
    0 => 'string',
    'num' => 'GMP|string|int',
    'base=' => 'int',
  ),
  'gmp_add' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_sub' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_mul' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_div_qr' => 
  array (
    0 => 'array',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
    'rounding_mode=' => 'int',
  ),
  'gmp_div_q' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
    'rounding_mode=' => 'int',
  ),
  'gmp_div_r' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
    'rounding_mode=' => 'int',
  ),
  'gmp_div' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
    'rounding_mode=' => 'int',
  ),
  'gmp_mod' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_divexact' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_neg' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_abs' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_fact' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_sqrt' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_sqrtrem' => 
  array (
    0 => 'array',
    'num' => 'GMP|string|int',
  ),
  'gmp_root' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
    'nth' => 'int',
  ),
  'gmp_rootrem' => 
  array (
    0 => 'array',
    'num' => 'GMP|string|int',
    'nth' => 'int',
  ),
  'gmp_pow' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
    'exponent' => 'int',
  ),
  'gmp_powm' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
    'exponent' => 'GMP|string|int',
    'modulus' => 'GMP|string|int',
  ),
  'gmp_perfect_square' => 
  array (
    0 => 'bool',
    'num' => 'GMP|string|int',
  ),
  'gmp_perfect_power' => 
  array (
    0 => 'bool',
    'num' => 'GMP|string|int',
  ),
  'gmp_prob_prime' => 
  array (
    0 => 'int',
    'num' => 'GMP|string|int',
    'repetitions=' => 'int',
  ),
  'gmp_gcd' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_gcdext' => 
  array (
    0 => 'array',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_lcm' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_invert' => 
  array (
    0 => 'GMP|false',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_jacobi' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_legendre' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_kronecker' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_cmp' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_sign' => 
  array (
    0 => 'int',
    'num' => 'GMP|string|int',
  ),
  'gmp_random_seed' => 
  array (
    0 => 'void',
    'seed' => 'GMP|string|int',
  ),
  'gmp_random_bits' => 
  array (
    0 => 'GMP',
    'bits' => 'int',
  ),
  'gmp_random_range' => 
  array (
    0 => 'GMP',
    'min' => 'GMP|string|int',
    'max' => 'GMP|string|int',
  ),
  'gmp_and' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_or' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_com' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_xor' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_setbit' => 
  array (
    0 => 'void',
    'num' => 'GMP',
    'index' => 'int',
    'value=' => 'bool',
  ),
  'gmp_clrbit' => 
  array (
    0 => 'void',
    'num' => 'GMP',
    'index' => 'int',
  ),
  'gmp_testbit' => 
  array (
    0 => 'bool',
    'num' => 'GMP|string|int',
    'index' => 'int',
  ),
  'gmp_scan0' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'start' => 'int',
  ),
  'gmp_scan1' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'start' => 'int',
  ),
  'gmp_popcount' => 
  array (
    0 => 'int',
    'num' => 'GMP|string|int',
  ),
  'gmp_hamdist' => 
  array (
    0 => 'int',
    'num1' => 'GMP|string|int',
    'num2' => 'GMP|string|int',
  ),
  'gmp_nextprime' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|string|int',
  ),
  'gmp_binomial' => 
  array (
    0 => 'GMP',
    'n' => 'GMP|string|int',
    'k' => 'int',
  ),
  'intlcal_create_instance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'locale=' => 'string|null',
  ),
  'intlcal_get_keyword_values_for_locale' => 
  array (
    0 => 'IntlIterator|false',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'intlcal_get_now' => 
  array (
    0 => 'float',
  ),
  'intlcal_get_available_locales' => 
  array (
    0 => 'array',
  ),
  'intlcal_get' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_time' => 
  array (
    0 => 'float|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_set_time' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timestamp' => 'float',
  ),
  'intlcal_add' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
    'value' => 'int',
  ),
  'intlcal_set_time_zone' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timezone' => 'IntlTimeZone|DateTimeZone|string|null|null',
  ),
  'intlcal_after' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
  ),
  'intlcal_before' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
  ),
  'intlcal_set' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth=' => 'int',
    'hour=' => 'int',
    'minute=' => 'int',
    'second=' => 'int',
  ),
  'intlcal_roll' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
    'value' => 'mixed',
  ),
  'intlcal_clear' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'field=' => 'int|null',
  ),
  'intlcal_field_difference' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlcal_get_actual_maximum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_actual_minimum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_day_of_week_type' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_get_first_day_of_week' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_least_maximum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_greatest_minimum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_locale' => 
  array (
    0 => 'string|false',
    'calendar' => 'IntlCalendar',
    'type' => 'int',
  ),
  'intlcal_get_maximum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_minimal_days_in_first_week' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_set_minimal_days_in_first_week' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'days' => 'int',
  ),
  'intlcal_get_minimum' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_time_zone' => 
  array (
    0 => 'IntlTimeZone|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_type' => 
  array (
    0 => 'string',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_weekend_transition' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_in_daylight_time' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_is_lenient' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_is_set' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_is_equivalent_to' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
  ),
  'intlcal_is_weekend' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timestamp=' => 'float|null',
  ),
  'intlcal_set_first_day_of_week' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_set_lenient' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'lenient' => 'bool',
  ),
  'intlcal_get_repeated_wall_time_option' => 
  array (
    0 => 'int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_equals' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
  ),
  'intlcal_get_skipped_wall_time_option' => 
  array (
    0 => 'int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_set_repeated_wall_time_option' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'option' => 'int',
  ),
  'intlcal_set_skipped_wall_time_option' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'option' => 'int',
  ),
  'intlcal_from_date_time' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'string|null',
  ),
  'intlcal_to_date_time' => 
  array (
    0 => 'DateTime|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_error_code' => 
  array (
    0 => 'int|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_error_message' => 
  array (
    0 => 'string|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlgregcal_create_instance' => 
  array (
    0 => 'IntlGregorianCalendar|null',
    'timezoneOrYear=' => 'mixed',
    'localeOrMonth=' => 'mixed',
    'day=' => 'mixed',
    'hour=' => 'mixed',
    'minute=' => 'mixed',
    'second=' => 'mixed',
  ),
  'intlgregcal_set_gregorian_change' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlGregorianCalendar',
    'timestamp' => 'float',
  ),
  'intlgregcal_get_gregorian_change' => 
  array (
    0 => 'float',
    'calendar' => 'IntlGregorianCalendar',
  ),
  'intlgregcal_is_leap_year' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlGregorianCalendar',
    'year' => 'int',
  ),
  'collator_create' => 
  array (
    0 => 'Collator|null',
    'locale' => 'string',
  ),
  'collator_compare' => 
  array (
    0 => 'int|false',
    'object' => 'Collator',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'collator_get_attribute' => 
  array (
    0 => 'int|false',
    'object' => 'Collator',
    'attribute' => 'int',
  ),
  'collator_set_attribute' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    'attribute' => 'int',
    'value' => 'int',
  ),
  'collator_get_strength' => 
  array (
    0 => 'int',
    'object' => 'Collator',
  ),
  'collator_set_strength' => 
  array (
    0 => 'true',
    'object' => 'Collator',
    'strength' => 'int',
  ),
  'collator_sort' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'collator_sort_with_sort_keys' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    '&array' => 'array',
  ),
  'collator_asort' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'collator_get_locale' => 
  array (
    0 => 'string|false',
    'object' => 'Collator',
    'type' => 'int',
  ),
  'collator_get_error_code' => 
  array (
    0 => 'int|false',
    'object' => 'Collator',
  ),
  'collator_get_error_message' => 
  array (
    0 => 'string|false',
    'object' => 'Collator',
  ),
  'collator_get_sort_key' => 
  array (
    0 => 'string|false',
    'object' => 'Collator',
    'string' => 'string',
  ),
  'intl_get_error_code' => 
  array (
    0 => 'int',
  ),
  'intl_get_error_message' => 
  array (
    0 => 'string',
  ),
  'intl_is_failure' => 
  array (
    0 => 'bool',
    'errorCode' => 'int',
  ),
  'intl_error_name' => 
  array (
    0 => 'string',
    'errorCode' => 'int',
  ),
  'datefmt_create' => 
  array (
    0 => 'IntlDateFormatter|null',
    'locale' => 'string|null',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'calendar=' => 'IntlCalendar|int|null|null',
    'pattern=' => 'string|null',
  ),
  'datefmt_get_datetype' => 
  array (
    0 => 'int|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_timetype' => 
  array (
    0 => 'int|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_calendar' => 
  array (
    0 => 'int|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_set_calendar' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'calendar' => 'IntlCalendar|int|null|null',
  ),
  'datefmt_get_timezone_id' => 
  array (
    0 => 'string|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_calendar_object' => 
  array (
    0 => 'IntlCalendar|false|null|null',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_timezone' => 
  array (
    0 => 'IntlTimeZone|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_set_timezone' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'timezone' => 'IntlTimeZone|DateTimeZone|string|null|null',
  ),
  'datefmt_set_pattern' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'pattern' => 'string',
  ),
  'datefmt_get_pattern' => 
  array (
    0 => 'string|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_locale' => 
  array (
    0 => 'string|false',
    'formatter' => 'IntlDateFormatter',
    'type=' => 'int',
  ),
  'datefmt_set_lenient' => 
  array (
    0 => 'void',
    'formatter' => 'IntlDateFormatter',
    'lenient' => 'bool',
  ),
  'datefmt_is_lenient' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_format' => 
  array (
    0 => 'string|false',
    'formatter' => 'IntlDateFormatter',
    'datetime' => 'mixed',
  ),
  'datefmt_format_object' => 
  array (
    0 => 'string|false',
    'datetime' => 'mixed',
    'format=' => 'mixed',
    'locale=' => 'string|null',
  ),
  'datefmt_parse' => 
  array (
    0 => 'int|float|false',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'datefmt_localtime' => 
  array (
    0 => 'array|false',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'datefmt_get_error_code' => 
  array (
    0 => 'int',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_error_message' => 
  array (
    0 => 'string',
    'formatter' => 'IntlDateFormatter',
  ),
  'numfmt_create' => 
  array (
    0 => 'NumberFormatter|null',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'string|null',
  ),
  'numfmt_format' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
    'num' => 'int|float',
    'type=' => 'int',
  ),
  'numfmt_parse' => 
  array (
    0 => 'int|float|false',
    'formatter' => 'NumberFormatter',
    'string' => 'string',
    'type=' => 'int',
    '&offset=' => 'mixed',
  ),
  'numfmt_format_currency' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
    'amount' => 'float',
    'currency' => 'string',
  ),
  'numfmt_parse_currency' => 
  array (
    0 => 'float|false',
    'formatter' => 'NumberFormatter',
    'string' => 'string',
    '&currency' => 'mixed',
    '&offset=' => 'mixed',
  ),
  'numfmt_set_attribute' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
    'value' => 'int|float',
  ),
  'numfmt_get_attribute' => 
  array (
    0 => 'int|float|false',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
  ),
  'numfmt_set_text_attribute' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
    'value' => 'string',
  ),
  'numfmt_get_text_attribute' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
  ),
  'numfmt_set_symbol' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'symbol' => 'int',
    'value' => 'string',
  ),
  'numfmt_get_symbol' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
    'symbol' => 'int',
  ),
  'numfmt_set_pattern' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'pattern' => 'string',
  ),
  'numfmt_get_pattern' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
  ),
  'numfmt_get_locale' => 
  array (
    0 => 'string|false',
    'formatter' => 'NumberFormatter',
    'type=' => 'int',
  ),
  'numfmt_get_error_code' => 
  array (
    0 => 'int',
    'formatter' => 'NumberFormatter',
  ),
  'numfmt_get_error_message' => 
  array (
    0 => 'string',
    'formatter' => 'NumberFormatter',
  ),
  'grapheme_strlen' => 
  array (
    0 => 'int|false|null|null',
    'string' => 'string',
  ),
  'grapheme_strpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_stripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_strrpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_strripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_substr' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
    'locale=' => 'string',
  ),
  'grapheme_strstr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
    'locale=' => 'string',
  ),
  'grapheme_stristr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
    'locale=' => 'string',
  ),
  'grapheme_str_split' => 
  array (
    0 => 'array|false',
    'string' => 'string',
    'length=' => 'int',
  ),
  'grapheme_levenshtein' => 
  array (
    0 => 'int|false',
    'string1' => 'string',
    'string2' => 'string',
    'insertion_cost=' => 'int',
    'replacement_cost=' => 'int',
    'deletion_cost=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_extract' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'size' => 'int',
    'type=' => 'int',
    'offset=' => 'int',
    '&next=' => 'mixed',
  ),
  'idn_to_ascii' => 
  array (
    0 => 'string|false',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'mixed',
  ),
  'idn_to_utf8' => 
  array (
    0 => 'string|false',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'mixed',
  ),
  'locale_get_default' => 
  array (
    0 => 'string',
  ),
  'locale_set_default' => 
  array (
    0 => 'true',
    'locale' => 'string',
  ),
  'locale_get_primary_language' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale_get_script' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale_get_region' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale_get_keywords' => 
  array (
    0 => 'array|false|null|null',
    'locale' => 'string',
  ),
  'locale_get_display_script' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale_get_display_region' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale_get_display_name' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale_get_display_language' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale_get_display_variant' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale_compose' => 
  array (
    0 => 'string|false',
    'subtags' => 'array',
  ),
  'locale_parse' => 
  array (
    0 => 'array|null',
    'locale' => 'string',
  ),
  'locale_get_all_variants' => 
  array (
    0 => 'array|null',
    'locale' => 'string',
  ),
  'locale_filter_matches' => 
  array (
    0 => 'bool|null',
    'languageTag' => 'string',
    'locale' => 'string',
    'canonicalize=' => 'bool',
  ),
  'locale_canonicalize' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale_lookup' => 
  array (
    0 => 'string|null',
    'languageTag' => 'array',
    'locale' => 'string',
    'canonicalize=' => 'bool',
    'defaultLocale=' => 'string|null',
  ),
  'locale_accept_from_http' => 
  array (
    0 => 'string|false',
    'header' => 'string',
  ),
  'locale_is_right_to_left' => 
  array (
    0 => 'bool',
    'locale' => 'string',
  ),
  'locale_add_likely_subtags' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
  ),
  'locale_minimize_subtags' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
  ),
  'msgfmt_create' => 
  array (
    0 => 'MessageFormatter|null',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'msgfmt_format' => 
  array (
    0 => 'string|false',
    'formatter' => 'MessageFormatter',
    'values' => 'array',
  ),
  'msgfmt_format_message' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'pattern' => 'string',
    'values' => 'array',
  ),
  'msgfmt_parse' => 
  array (
    0 => 'array|false',
    'formatter' => 'MessageFormatter',
    'string' => 'string',
  ),
  'msgfmt_parse_message' => 
  array (
    0 => 'array|false',
    'locale' => 'string',
    'pattern' => 'string',
    'message' => 'string',
  ),
  'msgfmt_set_pattern' => 
  array (
    0 => 'bool',
    'formatter' => 'MessageFormatter',
    'pattern' => 'string',
  ),
  'msgfmt_get_pattern' => 
  array (
    0 => 'string|false',
    'formatter' => 'MessageFormatter',
  ),
  'msgfmt_get_locale' => 
  array (
    0 => 'string',
    'formatter' => 'MessageFormatter',
  ),
  'msgfmt_get_error_code' => 
  array (
    0 => 'int',
    'formatter' => 'MessageFormatter',
  ),
  'msgfmt_get_error_message' => 
  array (
    0 => 'string',
    'formatter' => 'MessageFormatter',
  ),
  'normalizer_normalize' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer_is_normalized' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer_get_raw_decomposition' => 
  array (
    0 => 'string|null',
    'string' => 'string',
    'form=' => 'int',
  ),
  'resourcebundle_create' => 
  array (
    0 => 'ResourceBundle|null',
    'locale' => 'string|null',
    'bundle' => 'string|null',
    'fallback=' => 'bool',
  ),
  'resourcebundle_get' => 
  array (
    0 => 'ResourceBundle|array|string|int|null|null',
    'bundle' => 'ResourceBundle',
    'index' => 'string|int',
    'fallback=' => 'bool',
  ),
  'resourcebundle_count' => 
  array (
    0 => 'int',
    'bundle' => 'ResourceBundle',
  ),
  'resourcebundle_locales' => 
  array (
    0 => 'array|false',
    'bundle' => 'string',
  ),
  'resourcebundle_get_error_code' => 
  array (
    0 => 'int',
    'bundle' => 'ResourceBundle',
  ),
  'resourcebundle_get_error_message' => 
  array (
    0 => 'string',
    'bundle' => 'ResourceBundle',
  ),
  'intltz_count_equivalent_ids' => 
  array (
    0 => 'int|false',
    'timezoneId' => 'string',
  ),
  'intltz_create_default' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_create_enumeration' => 
  array (
    0 => 'IntlIterator|false',
    'countryOrRawOffset=' => 'string|int|null|null',
  ),
  'intltz_create_time_zone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezoneId' => 'string',
  ),
  'intltz_create_time_zone_id_enumeration' => 
  array (
    0 => 'IntlIterator|false',
    'type' => 'int',
    'region=' => 'string|null',
    'rawOffset=' => 'int|null',
  ),
  'intltz_from_date_time_zone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezone' => 'DateTimeZone',
  ),
  'intltz_get_canonical_id' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    '&isSystemId=' => 'mixed',
  ),
  'intltz_get_display_name' => 
  array (
    0 => 'string|false',
    'timezone' => 'IntlTimeZone',
    'dst=' => 'bool',
    'style=' => 'int',
    'locale=' => 'string|null',
  ),
  'intltz_get_dst_savings' => 
  array (
    0 => 'int',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_equivalent_id' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    'offset' => 'int',
  ),
  'intltz_get_error_code' => 
  array (
    0 => 'int|false',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_error_message' => 
  array (
    0 => 'string|false',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_gmt' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_get_id' => 
  array (
    0 => 'string|false',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_offset' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone',
    'timestamp' => 'float',
    'local' => 'bool',
    '&rawOffset' => 'mixed',
    '&dstOffset' => 'mixed',
  ),
  'intltz_get_raw_offset' => 
  array (
    0 => 'int',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_region' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'intltz_get_tz_data_version' => 
  array (
    0 => 'string|false',
  ),
  'intltz_get_unknown' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_get_windows_id' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'intltz_get_id_for_windows_id' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    'region=' => 'string|null',
  ),
  'intltz_has_same_rules' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone',
    'other' => 'IntlTimeZone',
  ),
  'intltz_to_date_time_zone' => 
  array (
    0 => 'DateTimeZone|false',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_use_daylight_time' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_iana_id' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'transliterator_create' => 
  array (
    0 => 'Transliterator|null',
    'id' => 'string',
    'direction=' => 'int',
  ),
  'transliterator_create_from_rules' => 
  array (
    0 => 'Transliterator|null',
    'rules' => 'string',
    'direction=' => 'int',
  ),
  'transliterator_list_ids' => 
  array (
    0 => 'array|false',
  ),
  'transliterator_create_inverse' => 
  array (
    0 => 'Transliterator|null',
    'transliterator' => 'Transliterator',
  ),
  'transliterator_transliterate' => 
  array (
    0 => 'string|false',
    'transliterator' => 'Transliterator|string',
    'string' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'transliterator_get_error_code' => 
  array (
    0 => 'int',
    'transliterator' => 'Transliterator',
  ),
  'transliterator_get_error_message' => 
  array (
    0 => 'string',
    'transliterator' => 'Transliterator',
  ),
  'mongodb\\driver\\monitoring\\addsubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'mongodb\\driver\\monitoring\\mongoc_log' => 
  array (
    0 => 'void',
    'level' => 'int',
    'domain' => 'string',
    'message' => 'string',
  ),
  'mongodb\\driver\\monitoring\\removesubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'pcntl_fork' => 
  array (
    0 => 'int',
  ),
  'pcntl_waitpid' => 
  array (
    0 => 'int',
    'process_id' => 'int',
    '&status' => 'mixed',
    'flags=' => 'int',
    '&resource_usage=' => 'mixed',
  ),
  'pcntl_waitid' => 
  array (
    0 => 'bool',
    'idtype=' => 'int',
    'id=' => 'int|null',
    '&info=' => 'mixed',
    'flags=' => 'int',
    '&resource_usage=' => 'mixed',
  ),
  'pcntl_wait' => 
  array (
    0 => 'int',
    '&status' => 'mixed',
    'flags=' => 'int',
    '&resource_usage=' => 'mixed',
  ),
  'pcntl_signal' => 
  array (
    0 => 'bool',
    'signal' => 'int',
    'handler' => 'mixed',
    'restart_syscalls=' => 'bool',
  ),
  'pcntl_signal_get_handler' => 
  array (
    0 => 'mixed',
    'signal' => 'int',
  ),
  'pcntl_signal_dispatch' => 
  array (
    0 => 'bool',
  ),
  'pcntl_sigprocmask' => 
  array (
    0 => 'bool',
    'mode' => 'int',
    'signals' => 'array',
    '&old_signals=' => 'mixed',
  ),
  'pcntl_sigwaitinfo' => 
  array (
    0 => 'int|false',
    'signals' => 'array',
    '&info=' => 'mixed',
  ),
  'pcntl_sigtimedwait' => 
  array (
    0 => 'int|false',
    'signals' => 'array',
    '&info=' => 'mixed',
    'seconds=' => 'int',
    'nanoseconds=' => 'int',
  ),
  'pcntl_wifexited' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifstopped' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifcontinued' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifsignaled' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wexitstatus' => 
  array (
    0 => 'int|false',
    'status' => 'int',
  ),
  'pcntl_wtermsig' => 
  array (
    0 => 'int|false',
    'status' => 'int',
  ),
  'pcntl_wstopsig' => 
  array (
    0 => 'int|false',
    'status' => 'int',
  ),
  'pcntl_exec' => 
  array (
    0 => 'false',
    'path' => 'string',
    'args=' => 'array',
    'env_vars=' => 'array',
  ),
  'pcntl_alarm' => 
  array (
    0 => 'int',
    'seconds' => 'int',
  ),
  'pcntl_get_last_error' => 
  array (
    0 => 'int',
  ),
  'pcntl_errno' => 
  array (
    0 => 'int',
  ),
  'pcntl_getpriority' => 
  array (
    0 => 'int|false',
    'process_id=' => 'int|null',
    'mode=' => 'int',
  ),
  'pcntl_setpriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
    'process_id=' => 'int|null',
    'mode=' => 'int',
  ),
  'pcntl_strerror' => 
  array (
    0 => 'string',
    'error_code' => 'int',
  ),
  'pcntl_async_signals' => 
  array (
    0 => 'bool',
    'enable=' => 'bool|null',
  ),
  'pcntl_unshare' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'pcntl_getcpuaffinity' => 
  array (
    0 => 'array|false',
    'process_id=' => 'int|null',
  ),
  'pcntl_setcpuaffinity' => 
  array (
    0 => 'bool',
    'process_id=' => 'int|null',
    'cpu_ids=' => 'array',
  ),
  'pg_connect' => 
  array (
    0 => 'PgSql\\Connection|false',
    'connection_string' => 'string',
    'flags=' => 'int',
  ),
  'pg_pconnect' => 
  array (
    0 => 'PgSql\\Connection|false',
    'connection_string' => 'string',
    'flags=' => 'int',
  ),
  'pg_connect_poll' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_close' => 
  array (
    0 => 'true',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_dbname' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_last_error' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_errormessage' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_options' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_port' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_tty' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_host' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_version' => 
  array (
    0 => 'array',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_jit' => 
  array (
    0 => 'array',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_parameter_status' => 
  array (
    0 => 'string|false',
    'connection' => 'mixed',
    'name=' => 'string',
  ),
  'pg_ping' => 
  array (
    0 => 'bool',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_query' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'query=' => 'string',
  ),
  'pg_exec' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'query=' => 'string',
  ),
  'pg_query_params' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'query' => 'mixed',
    'params=' => 'array',
  ),
  'pg_prepare' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'statement_name' => 'string',
    'query=' => 'string',
  ),
  'pg_execute' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'statement_name' => 'mixed',
    'params=' => 'array',
  ),
  'pg_num_rows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_numrows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_num_fields' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_numfields' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_affected_rows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_cmdtuples' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_last_notice' => 
  array (
    0 => 'array|string|bool',
    'connection' => 'PgSql\\Connection',
    'mode=' => 'int',
  ),
  'pg_field_table' => 
  array (
    0 => 'string|int|false',
    'result' => 'PgSql\\Result',
    'field' => 'int',
    'oid_only=' => 'bool',
  ),
  'pg_field_name' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldname' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_size' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldsize' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_type' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldtype' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_type_oid' => 
  array (
    0 => 'string|int',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_num' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'string',
  ),
  'pg_fieldnum' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'string',
  ),
  'pg_fetch_result' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_result' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_fetch_row' => 
  array (
    0 => 'array|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_fetch_assoc' => 
  array (
    0 => 'array|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
  ),
  'pg_fetch_array' => 
  array (
    0 => 'array|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_fetch_object' => 
  array (
    0 => 'object|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'class=' => 'string',
    'constructor_args=' => 'array',
  ),
  'pg_fetch_all' => 
  array (
    0 => 'array',
    'result' => 'PgSql\\Result',
    'mode=' => 'int',
  ),
  'pg_fetch_all_columns' => 
  array (
    0 => 'array',
    'result' => 'PgSql\\Result',
    'field=' => 'int',
  ),
  'pg_result_seek' => 
  array (
    0 => 'bool',
    'result' => 'PgSql\\Result',
    'row' => 'int',
  ),
  'pg_field_prtlen' => 
  array (
    0 => 'int|false',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_fieldprtlen' => 
  array (
    0 => 'int|false',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_field_is_null' => 
  array (
    0 => 'int|false',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_fieldisnull' => 
  array (
    0 => 'int|false',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'string|int',
  ),
  'pg_free_result' => 
  array (
    0 => 'bool',
    'result' => 'PgSql\\Result',
  ),
  'pg_freeresult' => 
  array (
    0 => 'bool',
    'result' => 'PgSql\\Result',
  ),
  'pg_last_oid' => 
  array (
    0 => 'string|int|false',
    'result' => 'PgSql\\Result',
  ),
  'pg_getlastoid' => 
  array (
    0 => 'string|int|false',
    'result' => 'PgSql\\Result',
  ),
  'pg_trace' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'mode=' => 'string',
    'connection=' => 'PgSql\\Connection|null',
    'trace_mode=' => 'int',
  ),
  'pg_untrace' => 
  array (
    0 => 'true',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_lo_create' => 
  array (
    0 => 'string|int|false',
    'connection=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_locreate' => 
  array (
    0 => 'string|int|false',
    'connection=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_unlink' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lounlink' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_open' => 
  array (
    0 => 'PgSql\\Lob|false',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'mode=' => 'string',
  ),
  'pg_loopen' => 
  array (
    0 => 'PgSql\\Lob|false',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'mode=' => 'string',
  ),
  'pg_lo_close' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_loclose' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_lo_read' => 
  array (
    0 => 'string|false',
    'lob' => 'PgSql\\Lob',
    'length=' => 'int',
  ),
  'pg_loread' => 
  array (
    0 => 'string|false',
    'lob' => 'PgSql\\Lob',
    'length=' => 'int',
  ),
  'pg_lo_write' => 
  array (
    0 => 'int|false',
    'lob' => 'PgSql\\Lob',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_lowrite' => 
  array (
    0 => 'int|false',
    'lob' => 'PgSql\\Lob',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_lo_read_all' => 
  array (
    0 => 'int',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_loreadall' => 
  array (
    0 => 'int',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_lo_import' => 
  array (
    0 => 'string|int|false',
    'connection' => 'mixed',
    'filename=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_loimport' => 
  array (
    0 => 'string|int|false',
    'connection' => 'mixed',
    'filename=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_export' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'filename=' => 'mixed',
  ),
  'pg_loexport' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'filename=' => 'mixed',
  ),
  'pg_lo_seek' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'pg_lo_tell' => 
  array (
    0 => 'int',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_lo_truncate' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
    'size' => 'int',
  ),
  'pg_set_error_verbosity' => 
  array (
    0 => 'int|false',
    'connection' => 'mixed',
    'verbosity=' => 'int',
  ),
  'pg_set_client_encoding' => 
  array (
    0 => 'int',
    'connection' => 'mixed',
    'encoding=' => 'string',
  ),
  'pg_setclientencoding' => 
  array (
    0 => 'int',
    'connection' => 'mixed',
    'encoding=' => 'string',
  ),
  'pg_client_encoding' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_clientencoding' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_end_copy' => 
  array (
    0 => 'bool',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_put_line' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'query=' => 'string',
  ),
  'pg_copy_to' => 
  array (
    0 => 'array|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_copy_from' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'rows' => 'Traversable|array',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_escape_string' => 
  array (
    0 => 'string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_escape_bytea' => 
  array (
    0 => 'string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_unescape_bytea' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'pg_escape_literal' => 
  array (
    0 => 'string|false',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_escape_identifier' => 
  array (
    0 => 'string|false',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_result_error' => 
  array (
    0 => 'string|false',
    'result' => 'PgSql\\Result',
  ),
  'pg_result_error_field' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'PgSql\\Result',
    'field_code' => 'int',
  ),
  'pg_connection_status' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_transaction_status' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_connection_reset' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_cancel_query' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_connection_busy' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_send_query' => 
  array (
    0 => 'int|bool',
    'connection' => 'PgSql\\Connection',
    'query' => 'string',
  ),
  'pg_send_query_params' => 
  array (
    0 => 'int|bool',
    'connection' => 'PgSql\\Connection',
    'query' => 'string',
    'params' => 'array',
  ),
  'pg_send_prepare' => 
  array (
    0 => 'int|bool',
    'connection' => 'PgSql\\Connection',
    'statement_name' => 'string',
    'query' => 'string',
  ),
  'pg_send_execute' => 
  array (
    0 => 'int|bool',
    'connection' => 'PgSql\\Connection',
    'statement_name' => 'string',
    'params' => 'array',
  ),
  'pg_get_result' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_result_status' => 
  array (
    0 => 'string|int',
    'result' => 'PgSql\\Result',
    'mode=' => 'int',
  ),
  'pg_get_notify' => 
  array (
    0 => 'array|false',
    'connection' => 'PgSql\\Connection',
    'mode=' => 'int',
  ),
  'pg_get_pid' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_socket' => 
  array (
    0 => 'mixed',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_consume_input' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_flush' => 
  array (
    0 => 'int|bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_meta_data' => 
  array (
    0 => 'array|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'extended=' => 'bool',
  ),
  'pg_convert' => 
  array (
    0 => 'array|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array',
    'flags=' => 'int',
  ),
  'pg_insert' => 
  array (
    0 => 'PgSql\\Result|string|bool',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array',
    'flags=' => 'int',
  ),
  'pg_update' => 
  array (
    0 => 'string|bool',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array',
    'conditions' => 'array',
    'flags=' => 'int',
  ),
  'pg_delete' => 
  array (
    0 => 'string|bool',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'conditions' => 'array',
    'flags=' => 'int',
  ),
  'pg_select' => 
  array (
    0 => 'array|string|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'conditions=' => 'array',
    'flags=' => 'int',
    'mode=' => 'int',
  ),
  'pg_set_error_context_visibility' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
    'visibility' => 'int',
  ),
  'pg_result_memory_size' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_change_password' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'user' => 'string',
    'password' => 'string',
  ),
  'pg_put_copy_data' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
    'cmd' => 'string',
  ),
  'pg_put_copy_end' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
    'error=' => 'string|null',
  ),
  'pg_socket_poll' => 
  array (
    0 => 'int',
    'socket' => 'mixed',
    'read' => 'int',
    'write' => 'int',
    'timeout=' => 'int',
  ),
  'pg_set_chunked_rows_size' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'size' => 'int',
  ),
  'pg_close_stmt' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'Pgsql\\Connection',
    'statement_name' => 'string',
  ),
  'use_soap_error_handler' => 
  array (
    0 => 'bool',
    'enable=' => 'bool',
  ),
  'is_soap_fault' => 
  array (
    0 => 'bool',
    'object' => 'mixed',
  ),
  'sodium_crypto_aead_aes256gcm_is_available' => 
  array (
    0 => 'bool',
  ),
  'sodium_crypto_aead_aes256gcm_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aes256gcm_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aes256gcm_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_aegis128l_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aegis128l_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aegis128l_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_aegis256_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aegis256_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_aegis256_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_ietf_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_ietf_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_auth' => 
  array (
    0 => 'string',
    'message' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_auth_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_auth_verify' => 
  array (
    0 => 'bool',
    'mac' => 'string',
    'message' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_box' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_keypair' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_box_seed_keypair' => 
  array (
    0 => 'string',
    'seed' => 'string',
  ),
  'sodium_crypto_box_keypair_from_secretkey_and_publickey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_box_open' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'nonce' => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_publickey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_publickey_from_secretkey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_box_seal' => 
  array (
    0 => 'string',
    'message' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_box_seal_open' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_secretkey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_core_ristretto255_add' => 
  array (
    0 => 'string',
    'p' => 'string',
    'q' => 'string',
  ),
  'sodium_crypto_core_ristretto255_from_hash' => 
  array (
    0 => 'string',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_is_valid_point' => 
  array (
    0 => 'bool',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_random' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_add' => 
  array (
    0 => 'string',
    'x' => 'string',
    'y' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_complement' => 
  array (
    0 => 'string',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_invert' => 
  array (
    0 => 'string',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_mul' => 
  array (
    0 => 'string',
    'x' => 'string',
    'y' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_negate' => 
  array (
    0 => 'string',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_random' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_reduce' => 
  array (
    0 => 'string',
    's' => 'string',
  ),
  'sodium_crypto_core_ristretto255_scalar_sub' => 
  array (
    0 => 'string',
    'x' => 'string',
    'y' => 'string',
  ),
  'sodium_crypto_core_ristretto255_sub' => 
  array (
    0 => 'string',
    'p' => 'string',
    'q' => 'string',
  ),
  'sodium_crypto_kx_keypair' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_kx_publickey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_kx_secretkey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_kx_seed_keypair' => 
  array (
    0 => 'string',
    'seed' => 'string',
  ),
  'sodium_crypto_kx_client_session_keys' => 
  array (
    0 => 'array',
    'client_key_pair' => 'string',
    'server_key' => 'string',
  ),
  'sodium_crypto_kx_server_session_keys' => 
  array (
    0 => 'array',
    'server_key_pair' => 'string',
    'client_key' => 'string',
  ),
  'sodium_crypto_generichash' => 
  array (
    0 => 'string',
    'message' => 'string',
    'key=' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_generichash_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_generichash_init' => 
  array (
    0 => 'string',
    'key=' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_generichash_update' => 
  array (
    0 => 'true',
    '&state' => 'string',
    'message' => 'string',
  ),
  'sodium_crypto_generichash_final' => 
  array (
    0 => 'string',
    '&state' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_kdf_derive_from_key' => 
  array (
    0 => 'string',
    'subkey_length' => 'int',
    'subkey_id' => 'int',
    'context' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_kdf_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_pwhash' => 
  array (
    0 => 'string',
    'length' => 'int',
    'password' => 'string',
    'salt' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
    'algo=' => 'int',
  ),
  'sodium_crypto_pwhash_str' => 
  array (
    0 => 'string',
    'password' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
  ),
  'sodium_crypto_pwhash_str_verify' => 
  array (
    0 => 'bool',
    'hash' => 'string',
    'password' => 'string',
  ),
  'sodium_crypto_pwhash_str_needs_rehash' => 
  array (
    0 => 'bool',
    'password' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
  ),
  'sodium_crypto_pwhash_scryptsalsa208sha256' => 
  array (
    0 => 'string',
    'length' => 'int',
    'password' => 'string',
    'salt' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
  ),
  'sodium_crypto_pwhash_scryptsalsa208sha256_str' => 
  array (
    0 => 'string',
    'password' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
  ),
  'sodium_crypto_pwhash_scryptsalsa208sha256_str_verify' => 
  array (
    0 => 'bool',
    'hash' => 'string',
    'password' => 'string',
  ),
  'sodium_crypto_scalarmult' => 
  array (
    0 => 'string',
    'n' => 'string',
    'p' => 'string',
  ),
  'sodium_crypto_scalarmult_ristretto255' => 
  array (
    0 => 'string',
    'n' => 'string',
    'p' => 'string',
  ),
  'sodium_crypto_scalarmult_ristretto255_base' => 
  array (
    0 => 'string',
    'n' => 'string',
  ),
  'sodium_crypto_secretbox' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_secretbox_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_secretbox_open' => 
  array (
    0 => 'string|false',
    'ciphertext' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_init_push' => 
  array (
    0 => 'array',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_push' => 
  array (
    0 => 'string',
    '&state' => 'string',
    'message' => 'string',
    'additional_data=' => 'string',
    'tag=' => 'int',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => 
  array (
    0 => 'string',
    'header' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_pull' => 
  array (
    0 => 'array|false',
    '&state' => 'string',
    'ciphertext' => 'string',
    'additional_data=' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_rekey' => 
  array (
    0 => 'void',
    '&state' => 'string',
  ),
  'sodium_crypto_shorthash' => 
  array (
    0 => 'string',
    'message' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_shorthash_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_sign' => 
  array (
    0 => 'string',
    'message' => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_sign_detached' => 
  array (
    0 => 'string',
    'message' => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_sign_ed25519_pk_to_curve25519' => 
  array (
    0 => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_sign_ed25519_sk_to_curve25519' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_sign_keypair' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_sign_keypair_from_secretkey_and_publickey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_sign_open' => 
  array (
    0 => 'string|false',
    'signed_message' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_sign_publickey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_sign_secretkey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_sign_publickey_from_secretkey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_sign_seed_keypair' => 
  array (
    0 => 'string',
    'seed' => 'string',
  ),
  'sodium_crypto_sign_verify_detached' => 
  array (
    0 => 'bool',
    'signature' => 'string',
    'message' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_stream' => 
  array (
    0 => 'string',
    'length' => 'int',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_stream_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_stream_xor' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_stream_xchacha20' => 
  array (
    0 => 'string',
    'length' => 'int',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_stream_xchacha20_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_stream_xchacha20_xor' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_stream_xchacha20_xor_ic' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'counter' => 'int',
    'key' => 'string',
  ),
  'sodium_add' => 
  array (
    0 => 'void',
    '&string1' => 'string',
    'string2' => 'string',
  ),
  'sodium_compare' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'sodium_increment' => 
  array (
    0 => 'void',
    '&string' => 'string',
  ),
  'sodium_memcmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'sodium_memzero' => 
  array (
    0 => 'void',
    '&string' => 'string',
  ),
  'sodium_pad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'block_size' => 'int',
  ),
  'sodium_unpad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'block_size' => 'int',
  ),
  'sodium_bin2hex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'sodium_hex2bin' => 
  array (
    0 => 'string',
    'string' => 'string',
    'ignore=' => 'string',
  ),
  'sodium_bin2base64' => 
  array (
    0 => 'string',
    'string' => 'string',
    'id' => 'int',
  ),
  'sodium_base642bin' => 
  array (
    0 => 'string',
    'string' => 'string',
    'id' => 'int',
    'ignore=' => 'string',
  ),
  'sodium_crypto_scalarmult_base' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
  ),
  'uv_update_time' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_ref' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_unref' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_loop_new' => 
  array (
    0 => 'mixed',
  ),
  'uv_default_loop' => 
  array (
    0 => 'mixed',
  ),
  'uv_stop' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_run' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
    'run_mode=' => 'mixed',
  ),
  'uv_ip4_addr' => 
  array (
    0 => 'mixed',
    'address' => 'mixed',
    'port' => 'mixed',
  ),
  'uv_ip6_addr' => 
  array (
    0 => 'mixed',
    'address' => 'mixed',
    'port' => 'mixed',
  ),
  'uv_ip4_name' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_ip6_name' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_write' => 
  array (
    0 => 'mixed',
    'client' => 'mixed',
    'data' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_write2' => 
  array (
    0 => 'mixed',
    'client' => 'mixed',
    'data' => 'mixed',
    'send' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_shutdown' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_close' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_now' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_loop_delete' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_read_start' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_read_stop' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
  ),
  'uv_err_name' => 
  array (
    0 => 'mixed',
    'error' => 'mixed',
  ),
  'uv_strerror' => 
  array (
    0 => 'mixed',
    'error' => 'mixed',
  ),
  'uv_is_active' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_is_closing' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_is_readable' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_is_writable' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_walk' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'callback' => 'mixed',
    'opaque=' => 'mixed',
  ),
  'uv_guess_handle' => 
  array (
    0 => 'mixed',
    'fd' => 'mixed',
  ),
  'uv_idle_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_idle_start' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_idle_stop' => 
  array (
    0 => 'mixed',
    'idle' => 'mixed',
  ),
  'uv_timer_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_timer_start' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
    'timeout' => 'mixed',
    'repeat' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_timer_stop' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
  ),
  'uv_timer_again' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
  ),
  'uv_timer_set_repeat' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
    'timeout' => 'mixed',
  ),
  'uv_timer_get_repeat' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
  ),
  'uv_tcp_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_tcp_open' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'tcpfd' => 'mixed',
  ),
  'uv_tcp_nodelay' => 
  array (
    0 => 'mixed',
    'tcp' => 'mixed',
    'enabled' => 'mixed',
  ),
  'uv_tcp_bind' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'address' => 'mixed',
  ),
  'uv_tcp_bind6' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'address' => 'mixed',
  ),
  'uv_listen' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'backlog' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_accept' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'client' => 'mixed',
  ),
  'uv_tcp_connect' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'sock_addr' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_tcp_connect6' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'ipv6_addr' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_udp_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_udp_open' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'udpfd' => 'mixed',
  ),
  'uv_udp_bind' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'address' => 'mixed',
    'flags=' => 'mixed',
  ),
  'uv_udp_bind6' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'address' => 'mixed',
    'flags=' => 'mixed',
  ),
  'uv_udp_set_multicast_loop' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'enabled' => 'mixed',
  ),
  'uv_udp_set_multicast_ttl' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'ttl' => 'mixed',
  ),
  'uv_udp_send' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'buffer' => 'mixed',
    'address' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_udp_send6' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'buffer' => 'mixed',
    'address' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_udp_recv_start' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_udp_recv_stop' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
  ),
  'uv_udp_set_membership' => 
  array (
    0 => 'mixed',
    'client' => 'mixed',
    'multicast_addr' => 'mixed',
    'interface_addr' => 'mixed',
    'membership' => 'mixed',
  ),
  'uv_udp_set_broadcast' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'enabled' => 'mixed',
  ),
  'uv_poll_init' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
  ),
  'uv_poll_init_socket' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
  ),
  'uv_poll_start' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'events' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_poll_stop' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_fs_poll_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_fs_poll_start' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'callback' => 'mixed',
    'path' => 'mixed',
    'interval' => 'mixed',
  ),
  'uv_fs_poll_stop' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_tcp_getsockname' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_tcp_getpeername' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_udp_getsockname' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_tcp_simultaneous_accepts' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'enable' => 'mixed',
  ),
  'uv_pipe_init' => 
  array (
    0 => 'mixed',
    'file=' => 'mixed',
    'ipc=' => 'mixed',
  ),
  'uv_pipe_bind' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'name' => 'mixed',
  ),
  'uv_pipe_open' => 
  array (
    0 => 'mixed',
    'file' => 'mixed',
    'pipe' => 'mixed',
  ),
  'uv_pipe_connect' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'name' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_pipe_pending_instances' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'count' => 'mixed',
  ),
  'uv_pipe_pending_count' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_pipe_pending_type' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_stdio_new' => 
  array (
    0 => 'mixed',
  ),
  'uv_spawn' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'command' => 'mixed',
    'args' => 'mixed',
    'stdio' => 'mixed',
    'cwd' => 'mixed',
    'env' => 'mixed',
    'callback' => 'mixed',
    'flags=' => 'mixed',
    'options=' => 'mixed',
  ),
  'uv_process_kill' => 
  array (
    0 => 'mixed',
    'process' => 'mixed',
    'signal' => 'mixed',
  ),
  'uv_process_get_pid' => 
  array (
    0 => 'mixed',
    'process' => 'mixed',
  ),
  'uv_kill' => 
  array (
    0 => 'mixed',
    'pid' => 'mixed',
    'signal' => 'mixed',
  ),
  'uv_getaddrinfo' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'callback' => 'mixed',
    'node' => 'mixed',
    'service' => 'mixed',
    'hints=' => 'mixed',
  ),
  'uv_rwlock_init' => 
  array (
    0 => 'mixed',
  ),
  'uv_rwlock_rdlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_tryrdlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_rdunlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_wrlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_trywrlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_wrunlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_mutex_init' => 
  array (
    0 => 'mixed',
  ),
  'uv_mutex_lock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_mutex_trylock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_mutex_unlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_sem_init' => 
  array (
    0 => 'mixed',
    'val' => 'mixed',
  ),
  'uv_sem_post' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
  ),
  'uv_sem_wait' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
  ),
  'uv_sem_trywait' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
  ),
  'uv_prepare_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_prepare_start' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_prepare_stop' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_check_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_check_start' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_check_stop' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_async_init' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_async_send' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_fs_open' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'flag' => 'mixed',
    'mode' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_read' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'offset=' => 'mixed',
    'size=' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_write' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'buffer' => 'mixed',
    'offset' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_close' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fsync' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fdatasync' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_ftruncate' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'offset' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_mkdir' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'mode' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_rmdir' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_unlink' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_rename' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'from' => 'mixed',
    'to' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_utime' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'utime' => 'mixed',
    'atime' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_futime' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'utime' => 'mixed',
    'atime' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_chmod' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'mode' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fchmod' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'mode' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_chown' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'uid' => 'mixed',
    'gid' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fchown' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'uid' => 'mixed',
    'gid' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_link' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'from' => 'mixed',
    'to' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_symlink' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'from' => 'mixed',
    'to' => 'mixed',
    'callback' => 'mixed',
    'flags=' => 'mixed',
  ),
  'uv_fs_readlink' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_stat' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_lstat' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fstat' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_readdir' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'flags' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_scandir' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'flags' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_sendfile' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'in' => 'mixed',
    'out' => 'mixed',
    'offset' => 'mixed',
    'length' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_event_init' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'callback' => 'mixed',
    'flags=' => 'mixed',
  ),
  'uv_tty_init' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'readable' => 'mixed',
  ),
  'uv_tty_get_winsize' => 
  array (
    0 => 'mixed',
    'tty' => 'mixed',
    '&width' => 'mixed',
    '&height' => 'mixed',
  ),
  'uv_tty_set_mode' => 
  array (
    0 => 'mixed',
  ),
  'uv_tty_reset_mode' => 
  array (
    0 => 'mixed',
  ),
  'uv_loadavg' => 
  array (
    0 => 'mixed',
  ),
  'uv_uptime' => 
  array (
    0 => 'mixed',
  ),
  'uv_cpu_info' => 
  array (
    0 => 'mixed',
  ),
  'uv_interface_addresses' => 
  array (
    0 => 'mixed',
  ),
  'uv_get_free_memory' => 
  array (
    0 => 'mixed',
  ),
  'uv_get_total_memory' => 
  array (
    0 => 'mixed',
  ),
  'uv_hrtime' => 
  array (
    0 => 'mixed',
  ),
  'uv_exepath' => 
  array (
    0 => 'mixed',
  ),
  'uv_cwd' => 
  array (
    0 => 'mixed',
  ),
  'uv_chdir' => 
  array (
    0 => 'mixed',
    'dir' => 'mixed',
  ),
  'uv_resident_set_memory' => 
  array (
    0 => 'mixed',
  ),
  'uv_signal_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_signal_start' => 
  array (
    0 => 'mixed',
    'sig_handle' => 'mixed',
    'sig_callback' => 'mixed',
    'sig_num' => 'mixed',
  ),
  'uv_signal_stop' => 
  array (
    0 => 'mixed',
    'sig_handle' => 'mixed',
  ),
  'zip_open' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
  ),
  'zip_close' => 
  array (
    0 => 'void',
    'zip' => 'mixed',
  ),
  'zip_read' => 
  array (
    0 => 'mixed',
    'zip' => 'mixed',
  ),
  'zip_entry_open' => 
  array (
    0 => 'bool',
    'zip_dp' => 'mixed',
    'zip_entry' => 'mixed',
    'mode=' => 'string',
  ),
  'zip_entry_close' => 
  array (
    0 => 'bool',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_read' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'mixed',
    'len=' => 'int',
  ),
  'zip_entry_name' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_compressedsize' => 
  array (
    0 => 'int|false',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_filesize' => 
  array (
    0 => 'int|false',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_compressionmethod' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'mixed',
  ),
  'dl' => 
  array (
    0 => 'bool',
    'extension_filename' => 'string',
  ),
  'cli_set_process_title' => 
  array (
    0 => 'bool',
    'title' => 'string',
  ),
  'cli_get_process_title' => 
  array (
    0 => 'string|null',
  ),
  'db2_connect' => 
  array (
    0 => 'mixed',
    'database' => 'string',
    'username' => 'string|null',
    'password' => 'string|null',
    'options=' => 'array',
  ),
  'db2_commit' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_pconnect' => 
  array (
    0 => 'mixed',
    'database' => 'string',
    'username' => 'string|null',
    'password' => 'string|null',
    'options=' => 'array',
  ),
  'db2_pclose' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_autocommit' => 
  array (
    0 => 'int|bool',
    'connection' => 'mixed',
    'value=' => 'int|null',
  ),
  'db2_bind_param' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
    'parameter_number' => 'int',
    'variable_name' => 'string',
    'parameter_type=' => 'int',
    'data_type=' => 'int',
    'precision=' => 'int',
    'scale=' => 'int',
  ),
  'db2_close' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_column_privileges' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'string|null',
    'schema=' => 'string|null',
    'table_name=' => 'string|null',
    'column_name=' => 'string|null',
  ),
  'db2_columnprivileges' => 
  array (
    0 => 'mixed',
  ),
  'db2_columns' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'mixed',
    'schema=' => 'mixed',
    'table_name=' => 'mixed',
    'column_name=' => 'mixed',
  ),
  'db2_foreign_keys' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string|null',
    'table_name' => 'string',
  ),
  'db2_foreignkeys' => 
  array (
    0 => 'mixed',
  ),
  'db2_primary_keys' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string|null',
    'table_name' => 'string',
  ),
  'db2_primarykeys' => 
  array (
    0 => 'mixed',
  ),
  'db2_procedure_columns' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string',
    'procedure' => 'string',
    'parameter' => 'string|null',
  ),
  'db2_procedurecolumns' => 
  array (
    0 => 'mixed',
  ),
  'db2_procedures' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string',
    'procedure' => 'string',
  ),
  'db2_special_columns' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string',
    'table_name' => 'string',
    'scope' => 'int',
  ),
  'db2_specialcolumns' => 
  array (
    0 => 'mixed',
  ),
  'db2_statistics' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'string|null',
    'schema' => 'string|null',
    'table_name' => 'string',
    'unique' => 'bool',
  ),
  'db2_table_privileges' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'string|null',
    'schema=' => 'string|null',
    'table_name=' => 'string|null',
  ),
  'db2_tableprivileges' => 
  array (
    0 => 'mixed',
  ),
  'db2_tables' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'string|null',
    'schema=' => 'string|null',
    'table_name=' => 'string|null',
    'table_type=' => 'string|null',
  ),
  'db2_exec' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'statement' => 'string',
    'options=' => 'array',
  ),
  'db2_prepare' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'statement' => 'string',
    'options=' => 'array',
  ),
  'db2_execute' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
    'parameters=' => 'array',
  ),
  'db2_stmt_errormsg' => 
  array (
    0 => 'mixed',
    'stmt=' => 'mixed',
  ),
  'db2_conn_errormsg' => 
  array (
    0 => 'mixed',
    'connection=' => 'mixed',
  ),
  'db2_conn_error' => 
  array (
    0 => 'mixed',
    'connection=' => 'mixed',
  ),
  'db2_stmt_error' => 
  array (
    0 => 'mixed',
    'stmt=' => 'mixed',
  ),
  'db2_next_result' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
  ),
  'db2_num_fields' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
  ),
  'db2_num_rows' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
  ),
  'db2_field_name' => 
  array (
    0 => 'string|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_display_size' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_num' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_precision' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_scale' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_type' => 
  array (
    0 => 'string|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_field_width' => 
  array (
    0 => 'int|false',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_cursor_type' => 
  array (
    0 => 'int',
    'stmt' => 'mixed',
  ),
  'db2_rollback' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_free_stmt' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
  ),
  'db2_result' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
    'column' => 'string|int',
  ),
  'db2_fetch_row' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_assoc' => 
  array (
    0 => 'array|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_array' => 
  array (
    0 => 'array|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_both' => 
  array (
    0 => 'array|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_free_result' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
  ),
  'db2_set_option' => 
  array (
    0 => 'bool',
    'resource' => 'mixed',
    'options' => 'array',
    'type' => 'int',
  ),
  'db2_setoption' => 
  array (
    0 => 'bool',
  ),
  'db2_fetch_object' => 
  array (
    0 => 'stdClass|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_server_info' => 
  array (
    0 => 'stdClass|false',
    'connection' => 'mixed',
  ),
  'db2_client_info' => 
  array (
    0 => 'stdClass|false',
    'connection' => 'mixed',
  ),
  'db2_escape_string' => 
  array (
    0 => 'string',
    'string_literal' => 'string',
  ),
  'db2_lob_read' => 
  array (
    0 => 'string|false',
    'stmt' => 'mixed',
    'colnum' => 'int',
    'length' => 'int',
  ),
  'db2_get_option' => 
  array (
    0 => 'string|false',
    'resource' => 'mixed',
    'option' => 'string',
  ),
  'db2_last_insert_id' => 
  array (
    0 => 'string|null',
    'resource' => 'mixed',
  ),
  'internaliterator::__construct' => 
  array (
    0 => 'void',
  ),
  'internaliterator::current' => 
  array (
    0 => 'mixed',
  ),
  'internaliterator::key' => 
  array (
    0 => 'mixed',
  ),
  'internaliterator::next' => 
  array (
    0 => 'void',
  ),
  'internaliterator::valid' => 
  array (
    0 => 'bool',
  ),
  'internaliterator::rewind' => 
  array (
    0 => 'void',
  ),
  'exception::__clone' => 
  array (
    0 => 'void',
  ),
  'exception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'exception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'exception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'exception::getfile' => 
  array (
    0 => 'string',
  ),
  'exception::getline' => 
  array (
    0 => 'int',
  ),
  'exception::gettrace' => 
  array (
    0 => 'array',
  ),
  'exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'exception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'errorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'severity=' => 'int',
    'filename=' => 'string|null',
    'line=' => 'int|null',
    'previous=' => 'Throwable|null',
  ),
  'errorexception::getseverity' => 
  array (
    0 => 'int',
  ),
  'errorexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'errorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'errorexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'errorexception::getfile' => 
  array (
    0 => 'string',
  ),
  'errorexception::getline' => 
  array (
    0 => 'int',
  ),
  'errorexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'errorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'errorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'errorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'error::__clone' => 
  array (
    0 => 'void',
  ),
  'error::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'error::__wakeup' => 
  array (
    0 => 'void',
  ),
  'error::getmessage' => 
  array (
    0 => 'string',
  ),
  'error::getcode' => 
  array (
    0 => 'mixed',
  ),
  'error::getfile' => 
  array (
    0 => 'string',
  ),
  'error::getline' => 
  array (
    0 => 'int',
  ),
  'error::gettrace' => 
  array (
    0 => 'array',
  ),
  'error::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'error::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'error::__tostring' => 
  array (
    0 => 'string',
  ),
  'compileerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'compileerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'compileerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'compileerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'compileerror::getfile' => 
  array (
    0 => 'string',
  ),
  'compileerror::getline' => 
  array (
    0 => 'int',
  ),
  'compileerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'compileerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'compileerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'compileerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'parseerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'parseerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'parseerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'parseerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'parseerror::getfile' => 
  array (
    0 => 'string',
  ),
  'parseerror::getline' => 
  array (
    0 => 'int',
  ),
  'parseerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'parseerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'parseerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'parseerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'typeerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'typeerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'typeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'typeerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'typeerror::getfile' => 
  array (
    0 => 'string',
  ),
  'typeerror::getline' => 
  array (
    0 => 'int',
  ),
  'typeerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'typeerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'typeerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'typeerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'argumentcounterror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'argumentcounterror::getmessage' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'argumentcounterror::getfile' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::getline' => 
  array (
    0 => 'int',
  ),
  'argumentcounterror::gettrace' => 
  array (
    0 => 'array',
  ),
  'argumentcounterror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'argumentcounterror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::__tostring' => 
  array (
    0 => 'string',
  ),
  'valueerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'valueerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'valueerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'valueerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'valueerror::getfile' => 
  array (
    0 => 'string',
  ),
  'valueerror::getline' => 
  array (
    0 => 'int',
  ),
  'valueerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'valueerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'valueerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'valueerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'arithmeticerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'arithmeticerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'arithmeticerror::getfile' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::getline' => 
  array (
    0 => 'int',
  ),
  'arithmeticerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'arithmeticerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'arithmeticerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'divisionbyzeroerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'divisionbyzeroerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'divisionbyzeroerror::getfile' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::getline' => 
  array (
    0 => 'int',
  ),
  'divisionbyzeroerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'divisionbyzeroerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'divisionbyzeroerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'unhandledmatcherror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'unhandledmatcherror::getmessage' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'unhandledmatcherror::getfile' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::getline' => 
  array (
    0 => 'int',
  ),
  'unhandledmatcherror::gettrace' => 
  array (
    0 => 'array',
  ),
  'unhandledmatcherror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'unhandledmatcherror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::__tostring' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'requestparsebodyexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'requestparsebodyexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'requestparsebodyexception::getfile' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::getline' => 
  array (
    0 => 'int',
  ),
  'requestparsebodyexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'requestparsebodyexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'requestparsebodyexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'closure::__construct' => 
  array (
    0 => 'void',
  ),
  'closure::bind' => 
  array (
    0 => 'Closure|null',
    'closure' => 'Closure',
    'newThis' => 'object|null',
    'newScope=' => 'object|string|null|null',
  ),
  'closure::bindto' => 
  array (
    0 => 'Closure|null',
    'newThis' => 'object|null',
    'newScope=' => 'object|string|null|null',
  ),
  'closure::call' => 
  array (
    0 => 'mixed',
    'newThis' => 'object',
    '...args=' => 'mixed',
  ),
  'closure::fromcallable' => 
  array (
    0 => 'Closure',
    'callback' => 'callable',
  ),
  'closure::getcurrent' => 
  array (
    0 => 'Closure',
  ),
  'closure::__invoke' => 
  array (
    0 => 'mixed',
  ),
  'generator::rewind' => 
  array (
    0 => 'void',
  ),
  'generator::valid' => 
  array (
    0 => 'bool',
  ),
  'generator::current' => 
  array (
    0 => 'mixed',
  ),
  'generator::key' => 
  array (
    0 => 'mixed',
  ),
  'generator::next' => 
  array (
    0 => 'void',
  ),
  'generator::send' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
  ),
  'generator::throw' => 
  array (
    0 => 'mixed',
    'exception' => 'Throwable',
  ),
  'generator::getreturn' => 
  array (
    0 => 'mixed',
  ),
  'generator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'closedgeneratorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'closedgeneratorexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'closedgeneratorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'closedgeneratorexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'closedgeneratorexception::getfile' => 
  array (
    0 => 'string',
  ),
  'closedgeneratorexception::getline' => 
  array (
    0 => 'int',
  ),
  'closedgeneratorexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'closedgeneratorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'closedgeneratorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'closedgeneratorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'weakreference::__construct' => 
  array (
    0 => 'void',
  ),
  'weakreference::create' => 
  array (
    0 => 'WeakReference',
    'object' => 'object',
  ),
  'weakreference::get' => 
  array (
    0 => 'object|null',
  ),
  'weakmap::offsetget' => 
  array (
    0 => 'mixed',
    'object' => 'mixed',
  ),
  'weakmap::offsetset' => 
  array (
    0 => 'void',
    'object' => 'mixed',
    'value' => 'mixed',
  ),
  'weakmap::offsetexists' => 
  array (
    0 => 'bool',
    'object' => 'mixed',
  ),
  'weakmap::offsetunset' => 
  array (
    0 => 'void',
    'object' => 'mixed',
  ),
  'weakmap::count' => 
  array (
    0 => 'int',
  ),
  'weakmap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'attribute::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'returntypewillchange::__construct' => 
  array (
    0 => 'void',
  ),
  'allowdynamicproperties::__construct' => 
  array (
    0 => 'void',
  ),
  'sensitiveparameter::__construct' => 
  array (
    0 => 'void',
  ),
  'sensitiveparametervalue::__construct' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'sensitiveparametervalue::getvalue' => 
  array (
    0 => 'mixed',
  ),
  'sensitiveparametervalue::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'override::__construct' => 
  array (
    0 => 'void',
  ),
  'deprecated::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string|null',
    'since=' => 'string|null',
  ),
  'nodiscard::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string|null',
  ),
  'fiber::__construct' => 
  array (
    0 => 'void',
    'callback' => 'callable',
  ),
  'fiber::start' => 
  array (
    0 => 'mixed',
    '...args=' => 'mixed',
  ),
  'fiber::resume' => 
  array (
    0 => 'mixed',
    'value=' => 'mixed',
  ),
  'fiber::throw' => 
  array (
    0 => 'mixed',
    'exception' => 'Throwable',
  ),
  'fiber::isstarted' => 
  array (
    0 => 'bool',
  ),
  'fiber::issuspended' => 
  array (
    0 => 'bool',
  ),
  'fiber::isrunning' => 
  array (
    0 => 'bool',
  ),
  'fiber::isterminated' => 
  array (
    0 => 'bool',
  ),
  'fiber::getreturn' => 
  array (
    0 => 'mixed',
  ),
  'fiber::getcurrent' => 
  array (
    0 => 'Fiber|null',
  ),
  'fiber::suspend' => 
  array (
    0 => 'mixed',
    'value=' => 'mixed',
  ),
  'fibererror::__construct' => 
  array (
    0 => 'void',
  ),
  'fibererror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'fibererror::getmessage' => 
  array (
    0 => 'string',
  ),
  'fibererror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'fibererror::getfile' => 
  array (
    0 => 'string',
  ),
  'fibererror::getline' => 
  array (
    0 => 'int',
  ),
  'fibererror::gettrace' => 
  array (
    0 => 'array',
  ),
  'fibererror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'fibererror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'fibererror::__tostring' => 
  array (
    0 => 'string',
  ),
  'datetime::__construct' => 
  array (
    0 => 'void',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetime::__serialize' => 
  array (
    0 => 'array',
  ),
  'datetime::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'datetime::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetime::__set_state' => 
  array (
    0 => 'DateTime',
    'array' => 'array',
  ),
  'datetime::createfromimmutable' => 
  array (
    0 => 'static',
    'object' => 'DateTimeImmutable',
  ),
  'datetime::createfrominterface' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTimeInterface',
  ),
  'datetime::createfromformat' => 
  array (
    0 => 'DateTime|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetime::createfromtimestamp' => 
  array (
    0 => 'static',
    'timestamp' => 'int|float',
  ),
  'datetime::getlasterrors' => 
  array (
    0 => 'array|false',
  ),
  'datetime::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'datetime::modify' => 
  array (
    0 => 'DateTime',
    'modifier' => 'string',
  ),
  'datetime::add' => 
  array (
    0 => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'datetime::sub' => 
  array (
    0 => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'datetime::gettimezone' => 
  array (
    0 => 'DateTimeZone|false',
  ),
  'datetime::settimezone' => 
  array (
    0 => 'DateTime',
    'timezone' => 'DateTimeZone',
  ),
  'datetime::getoffset' => 
  array (
    0 => 'int',
  ),
  'datetime::getmicrosecond' => 
  array (
    0 => 'int',
  ),
  'datetime::settime' => 
  array (
    0 => 'DateTime',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'datetime::setdate' => 
  array (
    0 => 'DateTime',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'datetime::setisodate' => 
  array (
    0 => 'DateTime',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'datetime::settimestamp' => 
  array (
    0 => 'DateTime',
    'timestamp' => 'int',
  ),
  'datetime::setmicrosecond' => 
  array (
    0 => 'static',
    'microsecond' => 'int',
  ),
  'datetime::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'datetime::diff' => 
  array (
    0 => 'DateInterval',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'datetimeimmutable::__construct' => 
  array (
    0 => 'void',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetimeimmutable::__serialize' => 
  array (
    0 => 'array',
  ),
  'datetimeimmutable::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'datetimeimmutable::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetimeimmutable::__set_state' => 
  array (
    0 => 'DateTimeImmutable',
    'array' => 'array',
  ),
  'datetimeimmutable::createfromformat' => 
  array (
    0 => 'DateTimeImmutable|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetimeimmutable::createfromtimestamp' => 
  array (
    0 => 'static',
    'timestamp' => 'int|float',
  ),
  'datetimeimmutable::getlasterrors' => 
  array (
    0 => 'array|false',
  ),
  'datetimeimmutable::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'datetimeimmutable::gettimezone' => 
  array (
    0 => 'DateTimeZone|false',
  ),
  'datetimeimmutable::getoffset' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::getmicrosecond' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::diff' => 
  array (
    0 => 'DateInterval',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'datetimeimmutable::modify' => 
  array (
    0 => 'DateTimeImmutable',
    'modifier' => 'string',
  ),
  'datetimeimmutable::add' => 
  array (
    0 => 'DateTimeImmutable',
    'interval' => 'DateInterval',
  ),
  'datetimeimmutable::sub' => 
  array (
    0 => 'DateTimeImmutable',
    'interval' => 'DateInterval',
  ),
  'datetimeimmutable::settimezone' => 
  array (
    0 => 'DateTimeImmutable',
    'timezone' => 'DateTimeZone',
  ),
  'datetimeimmutable::settime' => 
  array (
    0 => 'DateTimeImmutable',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'datetimeimmutable::setdate' => 
  array (
    0 => 'DateTimeImmutable',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'datetimeimmutable::setisodate' => 
  array (
    0 => 'DateTimeImmutable',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'datetimeimmutable::settimestamp' => 
  array (
    0 => 'DateTimeImmutable',
    'timestamp' => 'int',
  ),
  'datetimeimmutable::setmicrosecond' => 
  array (
    0 => 'static',
    'microsecond' => 'int',
  ),
  'datetimeimmutable::createfrommutable' => 
  array (
    0 => 'static',
    'object' => 'DateTime',
  ),
  'datetimeimmutable::createfrominterface' => 
  array (
    0 => 'DateTimeImmutable',
    'object' => 'DateTimeInterface',
  ),
  'datetimezone::__construct' => 
  array (
    0 => 'void',
    'timezone' => 'string',
  ),
  'datetimezone::getname' => 
  array (
    0 => 'string',
  ),
  'datetimezone::getoffset' => 
  array (
    0 => 'int',
    'datetime' => 'DateTimeInterface',
  ),
  'datetimezone::gettransitions' => 
  array (
    0 => 'array|false',
    'timestampBegin=' => 'int',
    'timestampEnd=' => 'int',
  ),
  'datetimezone::getlocation' => 
  array (
    0 => 'array|false',
  ),
  'datetimezone::listabbreviations' => 
  array (
    0 => 'array',
  ),
  'datetimezone::listidentifiers' => 
  array (
    0 => 'array',
    'timezoneGroup=' => 'int',
    'countryCode=' => 'string|null',
  ),
  'datetimezone::__serialize' => 
  array (
    0 => 'array',
  ),
  'datetimezone::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'datetimezone::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetimezone::__set_state' => 
  array (
    0 => 'DateTimeZone',
    'array' => 'array',
  ),
  'dateinterval::__construct' => 
  array (
    0 => 'void',
    'duration' => 'string',
  ),
  'dateinterval::createfromdatestring' => 
  array (
    0 => 'DateInterval',
    'datetime' => 'string',
  ),
  'dateinterval::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'dateinterval::__serialize' => 
  array (
    0 => 'array',
  ),
  'dateinterval::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'dateinterval::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateinterval::__set_state' => 
  array (
    0 => 'DateInterval',
    'array' => 'array',
  ),
  'dateperiod::createfromiso8601string' => 
  array (
    0 => 'static',
    'specification' => 'string',
    'options=' => 'int',
  ),
  'dateperiod::__construct' => 
  array (
    0 => 'void',
    'start' => 'mixed',
    'interval=' => 'mixed',
    'end=' => 'mixed',
    'options=' => 'mixed',
  ),
  'dateperiod::getstartdate' => 
  array (
    0 => 'DateTimeInterface',
  ),
  'dateperiod::getenddate' => 
  array (
    0 => 'DateTimeInterface|null',
  ),
  'dateperiod::getdateinterval' => 
  array (
    0 => 'DateInterval',
  ),
  'dateperiod::getrecurrences' => 
  array (
    0 => 'int|null',
  ),
  'dateperiod::__serialize' => 
  array (
    0 => 'array',
  ),
  'dateperiod::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'dateperiod::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateperiod::__set_state' => 
  array (
    0 => 'DatePeriod',
    'array' => 'array',
  ),
  'dateperiod::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dateerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dateerror::getfile' => 
  array (
    0 => 'string',
  ),
  'dateerror::getline' => 
  array (
    0 => 'int',
  ),
  'dateerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'dateerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateobjecterror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateobjecterror::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dateobjecterror::getfile' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::getline' => 
  array (
    0 => 'int',
  ),
  'dateobjecterror::gettrace' => 
  array (
    0 => 'array',
  ),
  'dateobjecterror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateobjecterror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::__tostring' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'daterangeerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'daterangeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'daterangeerror::getfile' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::getline' => 
  array (
    0 => 'int',
  ),
  'daterangeerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'daterangeerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'daterangeerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dateexception::getfile' => 
  array (
    0 => 'string',
  ),
  'dateexception::getline' => 
  array (
    0 => 'int',
  ),
  'dateexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'dateexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateinvalidtimezoneexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateinvalidtimezoneexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dateinvalidtimezoneexception::getfile' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::getline' => 
  array (
    0 => 'int',
  ),
  'dateinvalidtimezoneexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'dateinvalidtimezoneexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateinvalidtimezoneexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateinvalidoperationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateinvalidoperationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dateinvalidoperationexception::getfile' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::getline' => 
  array (
    0 => 'int',
  ),
  'dateinvalidoperationexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'dateinvalidoperationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateinvalidoperationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'datemalformedstringexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datemalformedstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'datemalformedstringexception::getfile' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::getline' => 
  array (
    0 => 'int',
  ),
  'datemalformedstringexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'datemalformedstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedstringexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'datemalformedintervalstringexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datemalformedintervalstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'datemalformedintervalstringexception::getfile' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::getline' => 
  array (
    0 => 'int',
  ),
  'datemalformedintervalstringexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'datemalformedintervalstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedintervalstringexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'datemalformedperiodstringexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datemalformedperiodstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'datemalformedperiodstringexception::getfile' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::getline' => 
  array (
    0 => 'int',
  ),
  'datemalformedperiodstringexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'datemalformedperiodstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedperiodstringexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'sqlite3exception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'sqlite3exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'sqlite3exception::getfile' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::getline' => 
  array (
    0 => 'int',
  ),
  'sqlite3exception::gettrace' => 
  array (
    0 => 'array',
  ),
  'sqlite3exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'sqlite3exception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'sqlite3::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
  ),
  'sqlite3::open' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
  ),
  'sqlite3::close' => 
  array (
    0 => 'bool',
  ),
  'sqlite3::version' => 
  array (
    0 => 'array',
  ),
  'sqlite3::lastinsertrowid' => 
  array (
    0 => 'int',
  ),
  'sqlite3::lasterrorcode' => 
  array (
    0 => 'int',
  ),
  'sqlite3::lastextendederrorcode' => 
  array (
    0 => 'int',
  ),
  'sqlite3::lasterrormsg' => 
  array (
    0 => 'string',
  ),
  'sqlite3::changes' => 
  array (
    0 => 'int',
  ),
  'sqlite3::busytimeout' => 
  array (
    0 => 'bool',
    'milliseconds' => 'int',
  ),
  'sqlite3::loadextension' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'sqlite3::backup' => 
  array (
    0 => 'bool',
    'destination' => 'SQLite3',
    'sourceDatabase=' => 'string',
    'destinationDatabase=' => 'string',
  ),
  'sqlite3::escapestring' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'sqlite3::prepare' => 
  array (
    0 => 'SQLite3Stmt|false',
    'query' => 'string',
  ),
  'sqlite3::exec' => 
  array (
    0 => 'bool',
    'query' => 'string',
  ),
  'sqlite3::query' => 
  array (
    0 => 'SQLite3Result|false',
    'query' => 'string',
  ),
  'sqlite3::querysingle' => 
  array (
    0 => 'mixed',
    'query' => 'string',
    'entireRow=' => 'bool',
  ),
  'sqlite3::createfunction' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'callback' => 'callable',
    'argCount=' => 'int',
    'flags=' => 'int',
  ),
  'sqlite3::createaggregate' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'stepCallback' => 'callable',
    'finalCallback' => 'callable',
    'argCount=' => 'int',
  ),
  'sqlite3::createcollation' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'callback' => 'callable',
  ),
  'sqlite3::openblob' => 
  array (
    0 => 'mixed',
    'table' => 'string',
    'column' => 'string',
    'rowid' => 'int',
    'database=' => 'string',
    'flags=' => 'int',
  ),
  'sqlite3::enableexceptions' => 
  array (
    0 => 'bool',
    'enable=' => 'bool',
  ),
  'sqlite3::enableextendedresultcodes' => 
  array (
    0 => 'bool',
    'enable=' => 'bool',
  ),
  'sqlite3::setauthorizer' => 
  array (
    0 => 'bool',
    'callback' => 'callable|null',
  ),
  'sqlite3stmt::__construct' => 
  array (
    0 => 'void',
    'sqlite3' => 'SQLite3',
    'query' => 'string',
  ),
  'sqlite3stmt::bindparam' => 
  array (
    0 => 'bool',
    'param' => 'string|int',
    '&var' => 'mixed',
    'type=' => 'int',
  ),
  'sqlite3stmt::bindvalue' => 
  array (
    0 => 'bool',
    'param' => 'string|int',
    'value' => 'mixed',
    'type=' => 'int',
  ),
  'sqlite3stmt::clear' => 
  array (
    0 => 'bool',
  ),
  'sqlite3stmt::close' => 
  array (
    0 => 'true',
  ),
  'sqlite3stmt::execute' => 
  array (
    0 => 'SQLite3Result|false',
  ),
  'sqlite3stmt::getsql' => 
  array (
    0 => 'string|false',
    'expand=' => 'bool',
  ),
  'sqlite3stmt::paramcount' => 
  array (
    0 => 'int',
  ),
  'sqlite3stmt::readonly' => 
  array (
    0 => 'bool',
  ),
  'sqlite3stmt::reset' => 
  array (
    0 => 'bool',
  ),
  'sqlite3stmt::busy' => 
  array (
    0 => 'bool',
  ),
  'sqlite3stmt::explain' => 
  array (
    0 => 'int',
  ),
  'sqlite3stmt::setexplain' => 
  array (
    0 => 'bool',
    'mode' => 'int',
  ),
  'sqlite3result::__construct' => 
  array (
    0 => 'void',
  ),
  'sqlite3result::numcolumns' => 
  array (
    0 => 'int',
  ),
  'sqlite3result::columnname' => 
  array (
    0 => 'string|false',
    'column' => 'int',
  ),
  'sqlite3result::columntype' => 
  array (
    0 => 'int|false',
    'column' => 'int',
  ),
  'sqlite3result::fetcharray' => 
  array (
    0 => 'array|false',
    'mode=' => 'int',
  ),
  'sqlite3result::fetchall' => 
  array (
    0 => 'array|false',
    'mode=' => 'int',
  ),
  'sqlite3result::reset' => 
  array (
    0 => 'bool',
  ),
  'sqlite3result::finalize' => 
  array (
    0 => 'true',
  ),
  'curlfile::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'mime_type=' => 'string|null',
    'posted_filename=' => 'string|null',
  ),
  'curlfile::getfilename' => 
  array (
    0 => 'string',
  ),
  'curlfile::getmimetype' => 
  array (
    0 => 'string',
  ),
  'curlfile::getpostfilename' => 
  array (
    0 => 'string',
  ),
  'curlfile::setmimetype' => 
  array (
    0 => 'void',
    'mime_type' => 'string',
  ),
  'curlfile::setpostfilename' => 
  array (
    0 => 'void',
    'posted_filename' => 'string',
  ),
  'curlstringfile::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
    'postname' => 'string',
    'mime=' => 'string',
  ),
  'uri\\rfc3986\\uri::parse' => 
  array (
    0 => 'static|null',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\Rfc3986\\Uri|null',
  ),
  'uri\\rfc3986\\uri::__construct' => 
  array (
    0 => 'void',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\Rfc3986\\Uri|null',
  ),
  'uri\\rfc3986\\uri::getscheme' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawscheme' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::withscheme' => 
  array (
    0 => 'static',
    'scheme' => 'string|null',
  ),
  'uri\\rfc3986\\uri::getuserinfo' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawuserinfo' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::withuserinfo' => 
  array (
    0 => 'static',
    'userinfo' => 'string|null',
  ),
  'uri\\rfc3986\\uri::getusername' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawusername' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getpassword' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawpassword' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::gethost' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawhost' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::withhost' => 
  array (
    0 => 'static',
    'host' => 'string|null',
  ),
  'uri\\rfc3986\\uri::getport' => 
  array (
    0 => 'int|null',
  ),
  'uri\\rfc3986\\uri::withport' => 
  array (
    0 => 'static',
    'port' => 'int|null',
  ),
  'uri\\rfc3986\\uri::getpath' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::getrawpath' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::withpath' => 
  array (
    0 => 'static',
    'path' => 'string',
  ),
  'uri\\rfc3986\\uri::getquery' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawquery' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::withquery' => 
  array (
    0 => 'static',
    'query' => 'string|null',
  ),
  'uri\\rfc3986\\uri::getfragment' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::getrawfragment' => 
  array (
    0 => 'string|null',
  ),
  'uri\\rfc3986\\uri::withfragment' => 
  array (
    0 => 'static',
    'fragment' => 'string|null',
  ),
  'uri\\rfc3986\\uri::equals' => 
  array (
    0 => 'bool',
    'uri' => 'Uri\\Rfc3986\\Uri',
    'comparisonMode=' => 'Uri\\UriComparisonMode',
  ),
  'uri\\rfc3986\\uri::tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::torawstring' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::resolve' => 
  array (
    0 => 'static',
    'uri' => 'string',
  ),
  'uri\\rfc3986\\uri::__serialize' => 
  array (
    0 => 'array',
  ),
  'uri\\rfc3986\\uri::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'uri\\rfc3986\\uri::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'uri\\whatwg\\url::parse' => 
  array (
    0 => 'static|null',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\WhatWg\\Url|null',
    '&errors=' => 'mixed',
  ),
  'uri\\whatwg\\url::__construct' => 
  array (
    0 => 'void',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\WhatWg\\Url|null',
    '&softErrors=' => 'mixed',
  ),
  'uri\\whatwg\\url::getscheme' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::withscheme' => 
  array (
    0 => 'static',
    'scheme' => 'string',
  ),
  'uri\\whatwg\\url::getusername' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::withusername' => 
  array (
    0 => 'static',
    'username' => 'string|null',
  ),
  'uri\\whatwg\\url::getpassword' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::withpassword' => 
  array (
    0 => 'static',
    'password' => 'string|null',
  ),
  'uri\\whatwg\\url::getasciihost' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::getunicodehost' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::withhost' => 
  array (
    0 => 'static',
    'host' => 'string|null',
  ),
  'uri\\whatwg\\url::getport' => 
  array (
    0 => 'int|null',
  ),
  'uri\\whatwg\\url::withport' => 
  array (
    0 => 'static',
    'port' => 'int|null',
  ),
  'uri\\whatwg\\url::getpath' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::withpath' => 
  array (
    0 => 'static',
    'path' => 'string',
  ),
  'uri\\whatwg\\url::getquery' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::withquery' => 
  array (
    0 => 'static',
    'query' => 'string|null',
  ),
  'uri\\whatwg\\url::getfragment' => 
  array (
    0 => 'string|null',
  ),
  'uri\\whatwg\\url::withfragment' => 
  array (
    0 => 'static',
    'fragment' => 'string|null',
  ),
  'uri\\whatwg\\url::equals' => 
  array (
    0 => 'bool',
    'url' => 'Uri\\WhatWg\\Url',
    'comparisonMode=' => 'Uri\\UriComparisonMode',
  ),
  'uri\\whatwg\\url::toasciistring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::tounicodestring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::resolve' => 
  array (
    0 => 'static',
    'uri' => 'string',
    '&softErrors=' => 'mixed',
  ),
  'uri\\whatwg\\url::__serialize' => 
  array (
    0 => 'array',
  ),
  'uri\\whatwg\\url::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'uri\\whatwg\\url::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'uri\\uricomparisonmode::cases' => 
  array (
    0 => 'array',
  ),
  'uri\\uriexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\uriexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'uri\\uriexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'uri\\uriexception::getfile' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::getline' => 
  array (
    0 => 'int',
  ),
  'uri\\uriexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'uri\\uriexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\uriexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\urierror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'uri\\urierror::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'uri\\urierror::getfile' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::getline' => 
  array (
    0 => 'int',
  ),
  'uri\\urierror::gettrace' => 
  array (
    0 => 'array',
  ),
  'uri\\urierror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\urierror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\invaliduriexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'uri\\invaliduriexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'uri\\invaliduriexception::getfile' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::getline' => 
  array (
    0 => 'int',
  ),
  'uri\\invaliduriexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'uri\\invaliduriexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\invaliduriexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'errors=' => 'array',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\whatwg\\invalidurlexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'uri\\whatwg\\invalidurlexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'uri\\whatwg\\invalidurlexception::getfile' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::getline' => 
  array (
    0 => 'int',
  ),
  'uri\\whatwg\\invalidurlexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'uri\\whatwg\\invalidurlexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\whatwg\\invalidurlexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\urlvalidationerror::__construct' => 
  array (
    0 => 'void',
    'context' => 'string',
    'type' => 'Uri\\WhatWg\\UrlValidationErrorType',
    'failure' => 'bool',
  ),
  'uri\\whatwg\\urlvalidationerrortype::cases' => 
  array (
    0 => 'array',
  ),
  'jsonexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'jsonexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'jsonexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'jsonexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'jsonexception::getfile' => 
  array (
    0 => 'string',
  ),
  'jsonexception::getline' => 
  array (
    0 => 'int',
  ),
  'jsonexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'jsonexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'jsonexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'jsonexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'finfo::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
    'magic_database=' => 'string|null',
  ),
  'finfo::file' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo::buffer' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo::set_flags' => 
  array (
    0 => 'true',
    'flags' => 'int',
  ),
  'filter\\filterexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'filter\\filterexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'filter\\filterexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'filter\\filterexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'filter\\filterexception::getfile' => 
  array (
    0 => 'string',
  ),
  'filter\\filterexception::getline' => 
  array (
    0 => 'int',
  ),
  'filter\\filterexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'filter\\filterexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'filter\\filterexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'filter\\filterexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'filter\\filterfailedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'filter\\filterfailedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'filter\\filterfailedexception::getfile' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::getline' => 
  array (
    0 => 'int',
  ),
  'filter\\filterfailedexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'filter\\filterfailedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'filter\\filterfailedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'hashcontext::__construct' => 
  array (
    0 => 'void',
  ),
  'hashcontext::__serialize' => 
  array (
    0 => 'array',
  ),
  'hashcontext::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'hashcontext::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'logicexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'logicexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'logicexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'logicexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'logicexception::getfile' => 
  array (
    0 => 'string',
  ),
  'logicexception::getline' => 
  array (
    0 => 'int',
  ),
  'logicexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'logicexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'logicexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'logicexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'badfunctioncallexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'badfunctioncallexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'badfunctioncallexception::getfile' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::getline' => 
  array (
    0 => 'int',
  ),
  'badfunctioncallexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'badfunctioncallexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'badfunctioncallexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'badmethodcallexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'badmethodcallexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'badmethodcallexception::getfile' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::getline' => 
  array (
    0 => 'int',
  ),
  'badmethodcallexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'badmethodcallexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'badmethodcallexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'domainexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'domainexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domainexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'domainexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'domainexception::getfile' => 
  array (
    0 => 'string',
  ),
  'domainexception::getline' => 
  array (
    0 => 'int',
  ),
  'domainexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'domainexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'domainexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'domainexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'invalidargumentexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'invalidargumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'invalidargumentexception::getfile' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::getline' => 
  array (
    0 => 'int',
  ),
  'invalidargumentexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'invalidargumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'invalidargumentexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'lengthexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'lengthexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'lengthexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'lengthexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'lengthexception::getfile' => 
  array (
    0 => 'string',
  ),
  'lengthexception::getline' => 
  array (
    0 => 'int',
  ),
  'lengthexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'lengthexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'lengthexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'lengthexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'outofrangeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'outofrangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'outofrangeexception::getfile' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::getline' => 
  array (
    0 => 'int',
  ),
  'outofrangeexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'outofrangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'outofrangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'runtimeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'runtimeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'runtimeexception::getfile' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::getline' => 
  array (
    0 => 'int',
  ),
  'runtimeexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'runtimeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'runtimeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'outofboundsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'outofboundsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'outofboundsexception::getfile' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::getline' => 
  array (
    0 => 'int',
  ),
  'outofboundsexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'outofboundsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'outofboundsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'overflowexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'overflowexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'overflowexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'overflowexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'overflowexception::getfile' => 
  array (
    0 => 'string',
  ),
  'overflowexception::getline' => 
  array (
    0 => 'int',
  ),
  'overflowexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'overflowexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'overflowexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'overflowexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'rangeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'rangeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'rangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'rangeexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'rangeexception::getfile' => 
  array (
    0 => 'string',
  ),
  'rangeexception::getline' => 
  array (
    0 => 'int',
  ),
  'rangeexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'rangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'rangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'rangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'underflowexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'underflowexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'underflowexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'underflowexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'underflowexception::getfile' => 
  array (
    0 => 'string',
  ),
  'underflowexception::getline' => 
  array (
    0 => 'int',
  ),
  'underflowexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'underflowexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'underflowexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'underflowexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'unexpectedvalueexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'unexpectedvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'unexpectedvalueexception::getfile' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::getline' => 
  array (
    0 => 'int',
  ),
  'unexpectedvalueexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'unexpectedvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'unexpectedvalueexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'recursiveiteratoriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Traversable',
    'mode=' => 'int',
    'flags=' => 'int',
  ),
  'recursiveiteratoriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursiveiteratoriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursiveiteratoriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursiveiteratoriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::getdepth' => 
  array (
    0 => 'int',
  ),
  'recursiveiteratoriterator::getsubiterator' => 
  array (
    0 => 'RecursiveIterator|null',
    'level=' => 'int|null',
  ),
  'recursiveiteratoriterator::getinneriterator' => 
  array (
    0 => 'RecursiveIterator',
  ),
  'recursiveiteratoriterator::beginiteration' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::enditeration' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::callhaschildren' => 
  array (
    0 => 'bool',
  ),
  'recursiveiteratoriterator::callgetchildren' => 
  array (
    0 => 'RecursiveIterator|null',
  ),
  'recursiveiteratoriterator::beginchildren' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::endchildren' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::nextelement' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::setmaxdepth' => 
  array (
    0 => 'void',
    'maxDepth=' => 'int',
  ),
  'recursiveiteratoriterator::getmaxdepth' => 
  array (
    0 => 'int|false',
  ),
  'iteratoriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Traversable',
    'class=' => 'string|null',
  ),
  'iteratoriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'iteratoriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'iteratoriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'iteratoriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'iteratoriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'iteratoriterator::next' => 
  array (
    0 => 'void',
  ),
  'filteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'filteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'filteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'filteriterator::next' => 
  array (
    0 => 'void',
  ),
  'filteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'filteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'filteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'filteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivefilteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
  ),
  'recursivefilteriterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::getchildren' => 
  array (
    0 => 'RecursiveFilterIterator|null',
  ),
  'recursivefilteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivefilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivefilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivefilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivefilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'callbackfilteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'callback' => 'callable',
  ),
  'callbackfilteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'callbackfilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'callbackfilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'callbackfilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'callbackfilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'callbackfilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'callbackfilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivecallbackfilteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
    'callback' => 'callable',
  ),
  'recursivecallbackfilteriterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::getchildren' => 
  array (
    0 => 'RecursiveCallbackFilterIterator',
  ),
  'recursivecallbackfilteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivecallbackfilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivecallbackfilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivecallbackfilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivecallbackfilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'parentiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
  ),
  'parentiterator::accept' => 
  array (
    0 => 'bool',
  ),
  'parentiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'parentiterator::getchildren' => 
  array (
    0 => 'RecursiveFilterIterator|null',
  ),
  'parentiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'parentiterator::next' => 
  array (
    0 => 'void',
  ),
  'parentiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'parentiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'parentiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'parentiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'limititerator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'offset=' => 'int',
    'limit=' => 'int',
  ),
  'limititerator::rewind' => 
  array (
    0 => 'void',
  ),
  'limititerator::valid' => 
  array (
    0 => 'bool',
  ),
  'limititerator::next' => 
  array (
    0 => 'void',
  ),
  'limititerator::seek' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'limititerator::getposition' => 
  array (
    0 => 'int',
  ),
  'limititerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'limititerator::key' => 
  array (
    0 => 'mixed',
  ),
  'limititerator::current' => 
  array (
    0 => 'mixed',
  ),
  'cachingiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'cachingiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'cachingiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'cachingiterator::next' => 
  array (
    0 => 'void',
  ),
  'cachingiterator::hasnext' => 
  array (
    0 => 'bool',
  ),
  'cachingiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'cachingiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'cachingiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'cachingiterator::offsetget' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
  ),
  'cachingiterator::offsetset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'cachingiterator::offsetunset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
  ),
  'cachingiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'cachingiterator::getcache' => 
  array (
    0 => 'array',
  ),
  'cachingiterator::count' => 
  array (
    0 => 'int',
  ),
  'cachingiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'cachingiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'cachingiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivecachingiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'recursivecachingiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::getchildren' => 
  array (
    0 => 'RecursiveCachingIterator|null',
  ),
  'recursivecachingiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivecachingiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivecachingiterator::hasnext' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'recursivecachingiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivecachingiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursivecachingiterator::offsetget' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
  ),
  'recursivecachingiterator::offsetset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'recursivecachingiterator::offsetunset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
  ),
  'recursivecachingiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'recursivecachingiterator::getcache' => 
  array (
    0 => 'array',
  ),
  'recursivecachingiterator::count' => 
  array (
    0 => 'int',
  ),
  'recursivecachingiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivecachingiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivecachingiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'norewinditerator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'norewinditerator::rewind' => 
  array (
    0 => 'void',
  ),
  'norewinditerator::valid' => 
  array (
    0 => 'bool',
  ),
  'norewinditerator::key' => 
  array (
    0 => 'mixed',
  ),
  'norewinditerator::current' => 
  array (
    0 => 'mixed',
  ),
  'norewinditerator::next' => 
  array (
    0 => 'void',
  ),
  'norewinditerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'appenditerator::__construct' => 
  array (
    0 => 'void',
  ),
  'appenditerator::append' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'appenditerator::rewind' => 
  array (
    0 => 'void',
  ),
  'appenditerator::valid' => 
  array (
    0 => 'bool',
  ),
  'appenditerator::current' => 
  array (
    0 => 'mixed',
  ),
  'appenditerator::next' => 
  array (
    0 => 'void',
  ),
  'appenditerator::getiteratorindex' => 
  array (
    0 => 'int|null',
  ),
  'appenditerator::getarrayiterator' => 
  array (
    0 => 'ArrayIterator',
  ),
  'appenditerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'appenditerator::key' => 
  array (
    0 => 'mixed',
  ),
  'infiniteiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'infiniteiterator::next' => 
  array (
    0 => 'void',
  ),
  'infiniteiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'infiniteiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'infiniteiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'infiniteiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'infiniteiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'regexiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'pattern' => 'string',
    'mode=' => 'int',
    'flags=' => 'int',
    'pregFlags=' => 'int',
  ),
  'regexiterator::accept' => 
  array (
    0 => 'bool',
  ),
  'regexiterator::getmode' => 
  array (
    0 => 'int',
  ),
  'regexiterator::setmode' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'regexiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'regexiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'regexiterator::getregex' => 
  array (
    0 => 'string',
  ),
  'regexiterator::getpregflags' => 
  array (
    0 => 'int',
  ),
  'regexiterator::setpregflags' => 
  array (
    0 => 'void',
    'pregFlags' => 'int',
  ),
  'regexiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'regexiterator::next' => 
  array (
    0 => 'void',
  ),
  'regexiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'regexiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'regexiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'regexiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursiveregexiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
    'pattern' => 'string',
    'mode=' => 'int',
    'flags=' => 'int',
    'pregFlags=' => 'int',
  ),
  'recursiveregexiterator::accept' => 
  array (
    0 => 'bool',
  ),
  'recursiveregexiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursiveregexiterator::getchildren' => 
  array (
    0 => 'RecursiveRegexIterator',
  ),
  'recursiveregexiterator::getmode' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::setmode' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'recursiveregexiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursiveregexiterator::getregex' => 
  array (
    0 => 'string',
  ),
  'recursiveregexiterator::getpregflags' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::setpregflags' => 
  array (
    0 => 'void',
    'pregFlags' => 'int',
  ),
  'recursiveregexiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursiveregexiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursiveregexiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursiveregexiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursiveregexiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursiveregexiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'emptyiterator::current' => 
  array (
    0 => 'never',
  ),
  'emptyiterator::next' => 
  array (
    0 => 'void',
  ),
  'emptyiterator::key' => 
  array (
    0 => 'never',
  ),
  'emptyiterator::valid' => 
  array (
    0 => 'false',
  ),
  'emptyiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator|IteratorAggregate',
    'flags=' => 'int',
    'cachingIteratorFlags=' => 'int',
    'mode=' => 'int',
  ),
  'recursivetreeiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivetreeiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivetreeiterator::getprefix' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::setpostfix' => 
  array (
    0 => 'void',
    'postfix' => 'string',
  ),
  'recursivetreeiterator::setprefixpart' => 
  array (
    0 => 'void',
    'part' => 'int',
    'value' => 'string',
  ),
  'recursivetreeiterator::getentry' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::getpostfix' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivetreeiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::getdepth' => 
  array (
    0 => 'int',
  ),
  'recursivetreeiterator::getsubiterator' => 
  array (
    0 => 'RecursiveIterator|null',
    'level=' => 'int|null',
  ),
  'recursivetreeiterator::getinneriterator' => 
  array (
    0 => 'RecursiveIterator',
  ),
  'recursivetreeiterator::beginiteration' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::enditeration' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::callhaschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivetreeiterator::callgetchildren' => 
  array (
    0 => 'RecursiveIterator|null',
  ),
  'recursivetreeiterator::beginchildren' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::endchildren' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::nextelement' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::setmaxdepth' => 
  array (
    0 => 'void',
    'maxDepth=' => 'int',
  ),
  'recursivetreeiterator::getmaxdepth' => 
  array (
    0 => 'int|false',
  ),
  'arrayobject::__construct' => 
  array (
    0 => 'void',
    'array=' => 'object|array',
    'flags=' => 'int',
    'iteratorClass=' => 'string',
  ),
  'arrayobject::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'arrayobject::offsetget' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
  ),
  'arrayobject::offsetset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'arrayobject::offsetunset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
  ),
  'arrayobject::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'arrayobject::getarraycopy' => 
  array (
    0 => 'array',
  ),
  'arrayobject::count' => 
  array (
    0 => 'int',
  ),
  'arrayobject::getflags' => 
  array (
    0 => 'int',
  ),
  'arrayobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'arrayobject::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayobject::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayobject::uasort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'arrayobject::uksort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'arrayobject::natsort' => 
  array (
    0 => 'true',
  ),
  'arrayobject::natcasesort' => 
  array (
    0 => 'true',
  ),
  'arrayobject::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'arrayobject::serialize' => 
  array (
    0 => 'string',
  ),
  'arrayobject::__serialize' => 
  array (
    0 => 'array',
  ),
  'arrayobject::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'arrayobject::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'arrayobject::exchangearray' => 
  array (
    0 => 'array',
    'array' => 'object|array',
  ),
  'arrayobject::setiteratorclass' => 
  array (
    0 => 'void',
    'iteratorClass' => 'string',
  ),
  'arrayobject::getiteratorclass' => 
  array (
    0 => 'string',
  ),
  'arrayobject::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'arrayiterator::__construct' => 
  array (
    0 => 'void',
    'array=' => 'object|array',
    'flags=' => 'int',
  ),
  'arrayiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'arrayiterator::offsetget' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
  ),
  'arrayiterator::offsetset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'arrayiterator::offsetunset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
  ),
  'arrayiterator::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'arrayiterator::getarraycopy' => 
  array (
    0 => 'array',
  ),
  'arrayiterator::count' => 
  array (
    0 => 'int',
  ),
  'arrayiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'arrayiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'arrayiterator::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayiterator::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayiterator::uasort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'arrayiterator::uksort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'arrayiterator::natsort' => 
  array (
    0 => 'true',
  ),
  'arrayiterator::natcasesort' => 
  array (
    0 => 'true',
  ),
  'arrayiterator::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'arrayiterator::serialize' => 
  array (
    0 => 'string',
  ),
  'arrayiterator::__serialize' => 
  array (
    0 => 'array',
  ),
  'arrayiterator::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'arrayiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'arrayiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'arrayiterator::key' => 
  array (
    0 => 'string|int|null|null',
  ),
  'arrayiterator::next' => 
  array (
    0 => 'void',
  ),
  'arrayiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'arrayiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'arrayiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'recursivearrayiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivearrayiterator::getchildren' => 
  array (
    0 => 'RecursiveArrayIterator|null',
  ),
  'recursivearrayiterator::__construct' => 
  array (
    0 => 'void',
    'array=' => 'object|array',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'recursivearrayiterator::offsetget' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
  ),
  'recursivearrayiterator::offsetset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'recursivearrayiterator::offsetunset' => 
  array (
    0 => 'void',
    'key' => 'mixed',
  ),
  'recursivearrayiterator::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'recursivearrayiterator::getarraycopy' => 
  array (
    0 => 'array',
  ),
  'recursivearrayiterator::count' => 
  array (
    0 => 'int',
  ),
  'recursivearrayiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivearrayiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursivearrayiterator::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::uasort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'recursivearrayiterator::uksort' => 
  array (
    0 => 'true',
    'callback' => 'callable',
  ),
  'recursivearrayiterator::natsort' => 
  array (
    0 => 'true',
  ),
  'recursivearrayiterator::natcasesort' => 
  array (
    0 => 'true',
  ),
  'recursivearrayiterator::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'recursivearrayiterator::serialize' => 
  array (
    0 => 'string',
  ),
  'recursivearrayiterator::__serialize' => 
  array (
    0 => 'array',
  ),
  'recursivearrayiterator::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'recursivearrayiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivearrayiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivearrayiterator::key' => 
  array (
    0 => 'string|int|null|null',
  ),
  'recursivearrayiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivearrayiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivearrayiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'recursivearrayiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splfileinfo::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
  ),
  'splfileinfo::getpath' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getfilename' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getextension' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'splfileinfo::getpathname' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getperms' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getinode' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getsize' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getowner' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getatime' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::getctime' => 
  array (
    0 => 'int|false',
  ),
  'splfileinfo::gettype' => 
  array (
    0 => 'string|false',
  ),
  'splfileinfo::iswritable' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::isreadable' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::isfile' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::isdir' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::islink' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'splfileinfo::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'splfileinfo::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'splfileinfo::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'splfileinfo::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'splfileinfo::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileinfo::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileinfo::__tostring' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splfileinfo::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
  ),
  'directoryiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'directoryiterator::isdot' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'directoryiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'directoryiterator::next' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'directoryiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getperms' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getinode' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getsize' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getowner' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getatime' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::getctime' => 
  array (
    0 => 'int|false',
  ),
  'directoryiterator::gettype' => 
  array (
    0 => 'string|false',
  ),
  'directoryiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isfile' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'directoryiterator::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'directoryiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'directoryiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'directoryiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'directoryiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'directoryiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'directoryiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'directoryiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'filesystemiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::key' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::current' => 
  array (
    0 => 'SplFileInfo|FilesystemIterator|string',
  ),
  'filesystemiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'filesystemiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'filesystemiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'filesystemiterator::isdot' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::next' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'filesystemiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getperms' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getinode' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getsize' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getowner' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getatime' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::getctime' => 
  array (
    0 => 'int|false',
  ),
  'filesystemiterator::gettype' => 
  array (
    0 => 'string|false',
  ),
  'filesystemiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isfile' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'filesystemiterator::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'filesystemiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'filesystemiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'filesystemiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'filesystemiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'filesystemiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'filesystemiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'filesystemiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'recursivedirectoryiterator::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'recursivedirectoryiterator::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'recursivedirectoryiterator::getsubpath' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::key' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::current' => 
  array (
    0 => 'SplFileInfo|FilesystemIterator|string',
  ),
  'recursivedirectoryiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivedirectoryiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursivedirectoryiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'recursivedirectoryiterator::isdot' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'recursivedirectoryiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getperms' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getinode' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getsize' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getowner' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getatime' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::getctime' => 
  array (
    0 => 'int|false',
  ),
  'recursivedirectoryiterator::gettype' => 
  array (
    0 => 'string|false',
  ),
  'recursivedirectoryiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isfile' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'recursivedirectoryiterator::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'recursivedirectoryiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'recursivedirectoryiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'recursivedirectoryiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'recursivedirectoryiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'recursivedirectoryiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'recursivedirectoryiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'recursivedirectoryiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'globiterator::__construct' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'flags=' => 'int',
  ),
  'globiterator::count' => 
  array (
    0 => 'int',
  ),
  'globiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'globiterator::key' => 
  array (
    0 => 'string',
  ),
  'globiterator::current' => 
  array (
    0 => 'SplFileInfo|FilesystemIterator|string',
  ),
  'globiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'globiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'globiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'globiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'globiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'globiterator::isdot' => 
  array (
    0 => 'bool',
  ),
  'globiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'globiterator::next' => 
  array (
    0 => 'void',
  ),
  'globiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'globiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'globiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'globiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'globiterator::getperms' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getinode' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getsize' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getowner' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getatime' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::getctime' => 
  array (
    0 => 'int|false',
  ),
  'globiterator::gettype' => 
  array (
    0 => 'string|false',
  ),
  'globiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isfile' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'globiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'globiterator::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'globiterator::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'globiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'globiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'globiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'globiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'globiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'globiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'globiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'splfileobject::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'splfileobject::rewind' => 
  array (
    0 => 'void',
  ),
  'splfileobject::eof' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::valid' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::fgets' => 
  array (
    0 => 'string',
  ),
  'splfileobject::fread' => 
  array (
    0 => 'string|false',
    'length' => 'int',
  ),
  'splfileobject::fgetcsv' => 
  array (
    0 => 'array|false',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'splfileobject::fputcsv' => 
  array (
    0 => 'int|false',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'splfileobject::setcsvcontrol' => 
  array (
    0 => 'void',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'splfileobject::getcsvcontrol' => 
  array (
    0 => 'array',
  ),
  'splfileobject::flock' => 
  array (
    0 => 'bool',
    'operation' => 'int',
    '&wouldBlock=' => 'mixed',
  ),
  'splfileobject::fflush' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::ftell' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::fseek' => 
  array (
    0 => 'int',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'splfileobject::fgetc' => 
  array (
    0 => 'string|false',
  ),
  'splfileobject::fpassthru' => 
  array (
    0 => 'int',
  ),
  'splfileobject::fscanf' => 
  array (
    0 => 'array|int|null|null',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'splfileobject::fwrite' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'splfileobject::fstat' => 
  array (
    0 => 'array',
  ),
  'splfileobject::ftruncate' => 
  array (
    0 => 'bool',
    'size' => 'int',
  ),
  'splfileobject::current' => 
  array (
    0 => 'array|string|false',
  ),
  'splfileobject::key' => 
  array (
    0 => 'int',
  ),
  'splfileobject::next' => 
  array (
    0 => 'void',
  ),
  'splfileobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'splfileobject::getflags' => 
  array (
    0 => 'int',
  ),
  'splfileobject::setmaxlinelen' => 
  array (
    0 => 'void',
    'maxLength' => 'int',
  ),
  'splfileobject::getmaxlinelen' => 
  array (
    0 => 'int',
  ),
  'splfileobject::haschildren' => 
  array (
    0 => 'false',
  ),
  'splfileobject::getchildren' => 
  array (
    0 => 'null|null',
  ),
  'splfileobject::seek' => 
  array (
    0 => 'void',
    'line' => 'int',
  ),
  'splfileobject::getcurrentline' => 
  array (
    0 => 'string',
  ),
  'splfileobject::__tostring' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getpath' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getfilename' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getextension' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'splfileobject::getpathname' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getperms' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getinode' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getsize' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getowner' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getatime' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::getctime' => 
  array (
    0 => 'int|false',
  ),
  'splfileobject::gettype' => 
  array (
    0 => 'string|false',
  ),
  'splfileobject::iswritable' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::isreadable' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::isfile' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::isdir' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::islink' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'splfileobject::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'splfileobject::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'splfileobject::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'splfileobject::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'splfileobject::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileobject::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileobject::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splfileobject::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::__construct' => 
  array (
    0 => 'void',
    'maxMemory=' => 'int',
  ),
  'spltempfileobject::rewind' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::eof' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::valid' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::fgets' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::fread' => 
  array (
    0 => 'string|false',
    'length' => 'int',
  ),
  'spltempfileobject::fgetcsv' => 
  array (
    0 => 'array|false',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'spltempfileobject::fputcsv' => 
  array (
    0 => 'int|false',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'spltempfileobject::setcsvcontrol' => 
  array (
    0 => 'void',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'spltempfileobject::getcsvcontrol' => 
  array (
    0 => 'array',
  ),
  'spltempfileobject::flock' => 
  array (
    0 => 'bool',
    'operation' => 'int',
    '&wouldBlock=' => 'mixed',
  ),
  'spltempfileobject::fflush' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::ftell' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::fseek' => 
  array (
    0 => 'int',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'spltempfileobject::fgetc' => 
  array (
    0 => 'string|false',
  ),
  'spltempfileobject::fpassthru' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::fscanf' => 
  array (
    0 => 'array|int|null|null',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'spltempfileobject::fwrite' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'spltempfileobject::fstat' => 
  array (
    0 => 'array',
  ),
  'spltempfileobject::ftruncate' => 
  array (
    0 => 'bool',
    'size' => 'int',
  ),
  'spltempfileobject::current' => 
  array (
    0 => 'array|string|false',
  ),
  'spltempfileobject::key' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::next' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'spltempfileobject::getflags' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::setmaxlinelen' => 
  array (
    0 => 'void',
    'maxLength' => 'int',
  ),
  'spltempfileobject::getmaxlinelen' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::haschildren' => 
  array (
    0 => 'false',
  ),
  'spltempfileobject::getchildren' => 
  array (
    0 => 'null|null',
  ),
  'spltempfileobject::seek' => 
  array (
    0 => 'void',
    'line' => 'int',
  ),
  'spltempfileobject::getcurrentline' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::__tostring' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getpath' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getfilename' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getextension' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'spltempfileobject::getpathname' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getperms' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getinode' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getsize' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getowner' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getatime' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::getctime' => 
  array (
    0 => 'int|false',
  ),
  'spltempfileobject::gettype' => 
  array (
    0 => 'string|false',
  ),
  'spltempfileobject::iswritable' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::isreadable' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::isfile' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::isdir' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::islink' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'spltempfileobject::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'spltempfileobject::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'spltempfileobject::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'spltempfileobject::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'spltempfileobject::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'spltempfileobject::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'spltempfileobject::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'spltempfileobject::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::pop' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::shift' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::top' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::bottom' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'spldoublylinkedlist::count' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::isempty' => 
  array (
    0 => 'bool',
  ),
  'spldoublylinkedlist::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'spldoublylinkedlist::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::offsetexists' => 
  array (
    0 => 'bool',
    'index' => 'mixed',
  ),
  'spldoublylinkedlist::offsetget' => 
  array (
    0 => 'mixed',
    'index' => 'mixed',
  ),
  'spldoublylinkedlist::offsetset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::offsetunset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
  ),
  'spldoublylinkedlist::rewind' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::current' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::key' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::prev' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::next' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::valid' => 
  array (
    0 => 'bool',
  ),
  'spldoublylinkedlist::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'spldoublylinkedlist::serialize' => 
  array (
    0 => 'string',
  ),
  'spldoublylinkedlist::__serialize' => 
  array (
    0 => 'array',
  ),
  'spldoublylinkedlist::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splqueue::enqueue' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::dequeue' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'splqueue::pop' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::shift' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::top' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::bottom' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splqueue::count' => 
  array (
    0 => 'int',
  ),
  'splqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'splqueue::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'splqueue::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'splqueue::offsetexists' => 
  array (
    0 => 'bool',
    'index' => 'mixed',
  ),
  'splqueue::offsetget' => 
  array (
    0 => 'mixed',
    'index' => 'mixed',
  ),
  'splqueue::offsetset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
    'value' => 'mixed',
  ),
  'splqueue::offsetunset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
  ),
  'splqueue::rewind' => 
  array (
    0 => 'void',
  ),
  'splqueue::current' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::key' => 
  array (
    0 => 'int',
  ),
  'splqueue::prev' => 
  array (
    0 => 'void',
  ),
  'splqueue::next' => 
  array (
    0 => 'void',
  ),
  'splqueue::valid' => 
  array (
    0 => 'bool',
  ),
  'splqueue::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splqueue::serialize' => 
  array (
    0 => 'string',
  ),
  'splqueue::__serialize' => 
  array (
    0 => 'array',
  ),
  'splqueue::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splstack::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'splstack::pop' => 
  array (
    0 => 'mixed',
  ),
  'splstack::shift' => 
  array (
    0 => 'mixed',
  ),
  'splstack::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splstack::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splstack::top' => 
  array (
    0 => 'mixed',
  ),
  'splstack::bottom' => 
  array (
    0 => 'mixed',
  ),
  'splstack::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splstack::count' => 
  array (
    0 => 'int',
  ),
  'splstack::isempty' => 
  array (
    0 => 'bool',
  ),
  'splstack::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'splstack::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'splstack::offsetexists' => 
  array (
    0 => 'bool',
    'index' => 'mixed',
  ),
  'splstack::offsetget' => 
  array (
    0 => 'mixed',
    'index' => 'mixed',
  ),
  'splstack::offsetset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
    'value' => 'mixed',
  ),
  'splstack::offsetunset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
  ),
  'splstack::rewind' => 
  array (
    0 => 'void',
  ),
  'splstack::current' => 
  array (
    0 => 'mixed',
  ),
  'splstack::key' => 
  array (
    0 => 'int',
  ),
  'splstack::prev' => 
  array (
    0 => 'void',
  ),
  'splstack::next' => 
  array (
    0 => 'void',
  ),
  'splstack::valid' => 
  array (
    0 => 'bool',
  ),
  'splstack::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splstack::serialize' => 
  array (
    0 => 'string',
  ),
  'splstack::__serialize' => 
  array (
    0 => 'array',
  ),
  'splstack::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splheap::extract' => 
  array (
    0 => 'mixed',
  ),
  'splheap::insert' => 
  array (
    0 => 'true',
    'value' => 'mixed',
  ),
  'splheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splheap::count' => 
  array (
    0 => 'int',
  ),
  'splheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splheap::current' => 
  array (
    0 => 'mixed',
  ),
  'splheap::key' => 
  array (
    0 => 'int',
  ),
  'splheap::next' => 
  array (
    0 => 'void',
  ),
  'splheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splheap::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splheap::__serialize' => 
  array (
    0 => 'array',
  ),
  'splheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splminheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splminheap::extract' => 
  array (
    0 => 'mixed',
  ),
  'splminheap::insert' => 
  array (
    0 => 'true',
    'value' => 'mixed',
  ),
  'splminheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splminheap::count' => 
  array (
    0 => 'int',
  ),
  'splminheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splminheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splminheap::current' => 
  array (
    0 => 'mixed',
  ),
  'splminheap::key' => 
  array (
    0 => 'int',
  ),
  'splminheap::next' => 
  array (
    0 => 'void',
  ),
  'splminheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splminheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splminheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splminheap::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splminheap::__serialize' => 
  array (
    0 => 'array',
  ),
  'splminheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splmaxheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splmaxheap::extract' => 
  array (
    0 => 'mixed',
  ),
  'splmaxheap::insert' => 
  array (
    0 => 'true',
    'value' => 'mixed',
  ),
  'splmaxheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splmaxheap::count' => 
  array (
    0 => 'int',
  ),
  'splmaxheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splmaxheap::current' => 
  array (
    0 => 'mixed',
  ),
  'splmaxheap::key' => 
  array (
    0 => 'int',
  ),
  'splmaxheap::next' => 
  array (
    0 => 'void',
  ),
  'splmaxheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splmaxheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splmaxheap::__serialize' => 
  array (
    0 => 'array',
  ),
  'splmaxheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splpriorityqueue::compare' => 
  array (
    0 => 'int',
    'priority1' => 'mixed',
    'priority2' => 'mixed',
  ),
  'splpriorityqueue::insert' => 
  array (
    0 => 'true',
    'value' => 'mixed',
    'priority' => 'mixed',
  ),
  'splpriorityqueue::setextractflags' => 
  array (
    0 => 'int',
    'flags' => 'int',
  ),
  'splpriorityqueue::top' => 
  array (
    0 => 'mixed',
  ),
  'splpriorityqueue::extract' => 
  array (
    0 => 'mixed',
  ),
  'splpriorityqueue::count' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::rewind' => 
  array (
    0 => 'void',
  ),
  'splpriorityqueue::current' => 
  array (
    0 => 'mixed',
  ),
  'splpriorityqueue::key' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::next' => 
  array (
    0 => 'void',
  ),
  'splpriorityqueue::valid' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splpriorityqueue::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::getextractflags' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'splpriorityqueue::__serialize' => 
  array (
    0 => 'array',
  ),
  'splpriorityqueue::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splfixedarray::__construct' => 
  array (
    0 => 'void',
    'size=' => 'int',
  ),
  'splfixedarray::__wakeup' => 
  array (
    0 => 'void',
  ),
  'splfixedarray::__serialize' => 
  array (
    0 => 'array',
  ),
  'splfixedarray::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splfixedarray::count' => 
  array (
    0 => 'int',
  ),
  'splfixedarray::toarray' => 
  array (
    0 => 'array',
  ),
  'splfixedarray::fromarray' => 
  array (
    0 => 'SplFixedArray',
    'array' => 'array',
    'preserveKeys=' => 'bool',
  ),
  'splfixedarray::getsize' => 
  array (
    0 => 'int',
  ),
  'splfixedarray::setsize' => 
  array (
    0 => 'true',
    'size' => 'int',
  ),
  'splfixedarray::offsetexists' => 
  array (
    0 => 'bool',
    'index' => 'mixed',
  ),
  'splfixedarray::offsetget' => 
  array (
    0 => 'mixed',
    'index' => 'mixed',
  ),
  'splfixedarray::offsetset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
    'value' => 'mixed',
  ),
  'splfixedarray::offsetunset' => 
  array (
    0 => 'void',
    'index' => 'mixed',
  ),
  'splfixedarray::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'splfixedarray::jsonserialize' => 
  array (
    0 => 'array',
  ),
  'splobjectstorage::attach' => 
  array (
    0 => 'void',
    'object' => 'object',
    'info=' => 'mixed',
  ),
  'splobjectstorage::detach' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'splobjectstorage::contains' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'splobjectstorage::addall' => 
  array (
    0 => 'int',
    'storage' => 'SplObjectStorage',
  ),
  'splobjectstorage::removeall' => 
  array (
    0 => 'int',
    'storage' => 'SplObjectStorage',
  ),
  'splobjectstorage::removeallexcept' => 
  array (
    0 => 'int',
    'storage' => 'SplObjectStorage',
  ),
  'splobjectstorage::getinfo' => 
  array (
    0 => 'mixed',
  ),
  'splobjectstorage::setinfo' => 
  array (
    0 => 'void',
    'info' => 'mixed',
  ),
  'splobjectstorage::count' => 
  array (
    0 => 'int',
    'mode=' => 'int',
  ),
  'splobjectstorage::rewind' => 
  array (
    0 => 'void',
  ),
  'splobjectstorage::valid' => 
  array (
    0 => 'bool',
  ),
  'splobjectstorage::key' => 
  array (
    0 => 'int',
  ),
  'splobjectstorage::current' => 
  array (
    0 => 'object',
  ),
  'splobjectstorage::next' => 
  array (
    0 => 'void',
  ),
  'splobjectstorage::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'splobjectstorage::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splobjectstorage::serialize' => 
  array (
    0 => 'string',
  ),
  'splobjectstorage::offsetexists' => 
  array (
    0 => 'bool',
    'object' => 'mixed',
  ),
  'splobjectstorage::offsetget' => 
  array (
    0 => 'mixed',
    'object' => 'mixed',
  ),
  'splobjectstorage::offsetset' => 
  array (
    0 => 'void',
    'object' => 'mixed',
    'info=' => 'mixed',
  ),
  'splobjectstorage::offsetunset' => 
  array (
    0 => 'void',
    'object' => 'mixed',
  ),
  'splobjectstorage::gethash' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'splobjectstorage::__serialize' => 
  array (
    0 => 'array',
  ),
  'splobjectstorage::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'splobjectstorage::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'multipleiterator::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'multipleiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'multipleiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'multipleiterator::attachiterator' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'info=' => 'string|int|null|null',
  ),
  'multipleiterator::detachiterator' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'multipleiterator::containsiterator' => 
  array (
    0 => 'bool',
    'iterator' => 'Iterator',
  ),
  'multipleiterator::countiterators' => 
  array (
    0 => 'int',
  ),
  'multipleiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'multipleiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'multipleiterator::key' => 
  array (
    0 => 'array',
  ),
  'multipleiterator::current' => 
  array (
    0 => 'array',
  ),
  'multipleiterator::next' => 
  array (
    0 => 'void',
  ),
  'multipleiterator::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'sessionhandler::open' => 
  array (
    0 => 'bool',
    'path' => 'string',
    'name' => 'string',
  ),
  'sessionhandler::close' => 
  array (
    0 => 'bool',
  ),
  'sessionhandler::read' => 
  array (
    0 => 'string|false',
    'id' => 'string',
  ),
  'sessionhandler::write' => 
  array (
    0 => 'bool',
    'id' => 'string',
    'data' => 'string',
  ),
  'sessionhandler::destroy' => 
  array (
    0 => 'bool',
    'id' => 'string',
  ),
  'sessionhandler::gc' => 
  array (
    0 => 'int|false',
    'max_lifetime' => 'int',
  ),
  'sessionhandler::create_sid' => 
  array (
    0 => 'string',
  ),
  'assertionerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'assertionerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'assertionerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'assertionerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'assertionerror::getfile' => 
  array (
    0 => 'string',
  ),
  'assertionerror::getline' => 
  array (
    0 => 'int',
  ),
  'assertionerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'assertionerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'assertionerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'assertionerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'roundingmode::cases' => 
  array (
    0 => 'array',
  ),
  'php_user_filter::filter' => 
  array (
    0 => 'int',
    'in' => 'mixed',
    'out' => 'mixed',
    '&consumed' => 'mixed',
    'closing' => 'bool',
  ),
  'php_user_filter::oncreate' => 
  array (
    0 => 'bool',
  ),
  'php_user_filter::onclose' => 
  array (
    0 => 'void',
  ),
  'directory::close' => 
  array (
    0 => 'void',
  ),
  'directory::rewind' => 
  array (
    0 => 'void',
  ),
  'directory::read' => 
  array (
    0 => 'string|false',
  ),
  'pdoexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'pdoexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'pdoexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'pdoexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'pdoexception::getfile' => 
  array (
    0 => 'string',
  ),
  'pdoexception::getline' => 
  array (
    0 => 'int',
  ),
  'pdoexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'pdoexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'pdoexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'pdoexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'pdo::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo::errorcode' => 
  array (
    0 => 'string|null',
  ),
  'pdo::errorinfo' => 
  array (
    0 => 'array',
  ),
  'pdo::exec' => 
  array (
    0 => 'int|false',
    'statement' => 'string',
  ),
  'pdo::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo::getavailabledrivers' => 
  array (
    0 => 'array',
  ),
  'pdo::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo::lastinsertid' => 
  array (
    0 => 'string|false',
    'name=' => 'string|null',
  ),
  'pdo::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array',
  ),
  'pdo::query' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'fetchMode=' => 'int|null',
    '...fetchModeArgs=' => 'mixed',
  ),
  'pdo::quote' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'type=' => 'int',
  ),
  'pdo::rollback' => 
  array (
    0 => 'bool',
  ),
  'pdo::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'mixed',
  ),
  'pdostatement::bindcolumn' => 
  array (
    0 => 'bool',
    'column' => 'string|int',
    '&var' => 'mixed',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed',
  ),
  'pdostatement::bindparam' => 
  array (
    0 => 'bool',
    'param' => 'string|int',
    '&var' => 'mixed',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed',
  ),
  'pdostatement::bindvalue' => 
  array (
    0 => 'bool',
    'param' => 'string|int',
    'value' => 'mixed',
    'type=' => 'int',
  ),
  'pdostatement::closecursor' => 
  array (
    0 => 'bool',
  ),
  'pdostatement::columncount' => 
  array (
    0 => 'int',
  ),
  'pdostatement::debugdumpparams' => 
  array (
    0 => 'bool|null',
  ),
  'pdostatement::errorcode' => 
  array (
    0 => 'string|null',
  ),
  'pdostatement::errorinfo' => 
  array (
    0 => 'array',
  ),
  'pdostatement::execute' => 
  array (
    0 => 'bool',
    'params=' => 'array|null',
  ),
  'pdostatement::fetch' => 
  array (
    0 => 'mixed',
    'mode=' => 'int',
    'cursorOrientation=' => 'int',
    'cursorOffset=' => 'int',
  ),
  'pdostatement::fetchall' => 
  array (
    0 => 'array',
    'mode=' => 'int',
    '...args=' => 'mixed',
  ),
  'pdostatement::fetchcolumn' => 
  array (
    0 => 'mixed',
    'column=' => 'int',
  ),
  'pdostatement::fetchobject' => 
  array (
    0 => 'object|false',
    'class=' => 'string|null',
    'constructorArgs=' => 'array',
  ),
  'pdostatement::getattribute' => 
  array (
    0 => 'mixed',
    'name' => 'int',
  ),
  'pdostatement::getcolumnmeta' => 
  array (
    0 => 'array|false',
    'column' => 'int',
  ),
  'pdostatement::nextrowset' => 
  array (
    0 => 'bool',
  ),
  'pdostatement::rowcount' => 
  array (
    0 => 'int',
  ),
  'pdostatement::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'mixed',
  ),
  'pdostatement::setfetchmode' => 
  array (
    0 => 'true',
    'mode' => 'int',
    '...args=' => 'mixed',
  ),
  'pdostatement::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'pdo\\sqlite::createaggregate' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'step' => 'callable',
    'finalize' => 'callable',
    'numArgs=' => 'int',
  ),
  'pdo\\sqlite::createcollation' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'callback' => 'callable',
  ),
  'pdo\\sqlite::createfunction' => 
  array (
    0 => 'bool',
    'function_name' => 'string',
    'callback' => 'callable',
    'num_args=' => 'int',
    'flags=' => 'int',
  ),
  'pdo\\sqlite::loadextension' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'pdo\\sqlite::openblob' => 
  array (
    0 => 'mixed',
    'table' => 'string',
    'column' => 'string',
    'rowid' => 'int',
    'dbname=' => 'string|null',
    'flags=' => 'int',
  ),
  'pdo\\sqlite::setauthorizer' => 
  array (
    0 => 'void',
    'callback' => 'callable|null',
  ),
  'pdo\\sqlite::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo\\sqlite::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo\\sqlite::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::errorcode' => 
  array (
    0 => 'string|null',
  ),
  'pdo\\sqlite::errorinfo' => 
  array (
    0 => 'array',
  ),
  'pdo\\sqlite::exec' => 
  array (
    0 => 'int|false',
    'statement' => 'string',
  ),
  'pdo\\sqlite::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo\\sqlite::getavailabledrivers' => 
  array (
    0 => 'array',
  ),
  'pdo\\sqlite::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::lastinsertid' => 
  array (
    0 => 'string|false',
    'name=' => 'string|null',
  ),
  'pdo\\sqlite::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array',
  ),
  'pdo\\sqlite::query' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'fetchMode=' => 'int|null',
    '...fetchModeArgs=' => 'mixed',
  ),
  'pdo\\sqlite::quote' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'type=' => 'int',
  ),
  'pdo\\sqlite::rollback' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'mixed',
  ),
  'pharexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'pharexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'pharexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'pharexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'pharexception::getfile' => 
  array (
    0 => 'string',
  ),
  'pharexception::getline' => 
  array (
    0 => 'int',
  ),
  'pharexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'pharexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'pharexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'pharexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'phar::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'string|null',
  ),
  'phar::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'phar::addemptydir' => 
  array (
    0 => 'void',
    'directory' => 'string',
  ),
  'phar::addfile' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'localName=' => 'string|null',
  ),
  'phar::addfromstring' => 
  array (
    0 => 'void',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'phar::buildfromdirectory' => 
  array (
    0 => 'array',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'phar::buildfromiterator' => 
  array (
    0 => 'array',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'string|null',
  ),
  'phar::compressfiles' => 
  array (
    0 => 'void',
    'compression' => 'int',
  ),
  'phar::decompressfiles' => 
  array (
    0 => 'true',
  ),
  'phar::compress' => 
  array (
    0 => 'Phar|null',
    'compression' => 'int',
    'extension=' => 'string|null',
  ),
  'phar::decompress' => 
  array (
    0 => 'Phar|null',
    'extension=' => 'string|null',
  ),
  'phar::converttoexecutable' => 
  array (
    0 => 'Phar|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'phar::converttodata' => 
  array (
    0 => 'PharData|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'phar::copy' => 
  array (
    0 => 'true',
    'from' => 'string',
    'to' => 'string',
  ),
  'phar::count' => 
  array (
    0 => 'int',
    'mode=' => 'int',
  ),
  'phar::delete' => 
  array (
    0 => 'true',
    'localName' => 'string',
  ),
  'phar::delmetadata' => 
  array (
    0 => 'true',
  ),
  'phar::extractto' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'files=' => 'array|string|null|null',
    'overwrite=' => 'bool',
  ),
  'phar::getalias' => 
  array (
    0 => 'string|null',
  ),
  'phar::getpath' => 
  array (
    0 => 'string',
  ),
  'phar::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array',
  ),
  'phar::getmodified' => 
  array (
    0 => 'bool',
  ),
  'phar::getsignature' => 
  array (
    0 => 'array|false',
  ),
  'phar::getstub' => 
  array (
    0 => 'string',
  ),
  'phar::getversion' => 
  array (
    0 => 'string',
  ),
  'phar::hasmetadata' => 
  array (
    0 => 'bool',
  ),
  'phar::isbuffering' => 
  array (
    0 => 'bool',
  ),
  'phar::iscompressed' => 
  array (
    0 => 'int|false',
  ),
  'phar::isfileformat' => 
  array (
    0 => 'bool',
    'format' => 'int',
  ),
  'phar::iswritable' => 
  array (
    0 => 'bool',
  ),
  'phar::offsetexists' => 
  array (
    0 => 'bool',
    'localName' => 'mixed',
  ),
  'phar::offsetget' => 
  array (
    0 => 'SplFileInfo',
    'localName' => 'mixed',
  ),
  'phar::offsetset' => 
  array (
    0 => 'void',
    'localName' => 'mixed',
    'value' => 'mixed',
  ),
  'phar::offsetunset' => 
  array (
    0 => 'void',
    'localName' => 'mixed',
  ),
  'phar::setalias' => 
  array (
    0 => 'true',
    'alias' => 'string',
  ),
  'phar::setdefaultstub' => 
  array (
    0 => 'true',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'phar::setmetadata' => 
  array (
    0 => 'void',
    'metadata' => 'mixed',
  ),
  'phar::setsignaturealgorithm' => 
  array (
    0 => 'void',
    'algo' => 'int',
    'privateKey=' => 'string|null',
  ),
  'phar::setstub' => 
  array (
    0 => 'true',
    'stub' => 'mixed',
    'length=' => 'int',
  ),
  'phar::startbuffering' => 
  array (
    0 => 'void',
  ),
  'phar::stopbuffering' => 
  array (
    0 => 'void',
  ),
  'phar::apiversion' => 
  array (
    0 => 'string',
  ),
  'phar::cancompress' => 
  array (
    0 => 'bool',
    'compression=' => 'int',
  ),
  'phar::canwrite' => 
  array (
    0 => 'bool',
  ),
  'phar::createdefaultstub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'phar::getsupportedcompression' => 
  array (
    0 => 'array',
  ),
  'phar::getsupportedsignatures' => 
  array (
    0 => 'array',
  ),
  'phar::interceptfilefuncs' => 
  array (
    0 => 'void',
  ),
  'phar::isvalidpharfilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'phar::loadphar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'string|null',
  ),
  'phar::mapphar' => 
  array (
    0 => 'bool',
    'alias=' => 'string|null',
    'offset=' => 'int',
  ),
  'phar::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'phar::mount' => 
  array (
    0 => 'void',
    'pharPath' => 'string',
    'externalPath' => 'string',
  ),
  'phar::mungserver' => 
  array (
    0 => 'void',
    'variables' => 'array',
  ),
  'phar::unlinkarchive' => 
  array (
    0 => 'true',
    'filename' => 'string',
  ),
  'phar::webphar' => 
  array (
    0 => 'void',
    'alias=' => 'string|null',
    'index=' => 'string|null',
    'fileNotFoundScript=' => 'string|null',
    'mimeTypes=' => 'array',
    'rewrite=' => 'callable|null',
  ),
  'phar::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'phar::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'phar::getsubpath' => 
  array (
    0 => 'string',
  ),
  'phar::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'phar::rewind' => 
  array (
    0 => 'void',
  ),
  'phar::key' => 
  array (
    0 => 'string',
  ),
  'phar::current' => 
  array (
    0 => 'SplFileInfo|FilesystemIterator|string',
  ),
  'phar::getflags' => 
  array (
    0 => 'int',
  ),
  'phar::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'phar::getfilename' => 
  array (
    0 => 'string',
  ),
  'phar::getextension' => 
  array (
    0 => 'string',
  ),
  'phar::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'phar::isdot' => 
  array (
    0 => 'bool',
  ),
  'phar::valid' => 
  array (
    0 => 'bool',
  ),
  'phar::next' => 
  array (
    0 => 'void',
  ),
  'phar::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'phar::__tostring' => 
  array (
    0 => 'string',
  ),
  'phar::getpathname' => 
  array (
    0 => 'string',
  ),
  'phar::getperms' => 
  array (
    0 => 'int|false',
  ),
  'phar::getinode' => 
  array (
    0 => 'int|false',
  ),
  'phar::getsize' => 
  array (
    0 => 'int|false',
  ),
  'phar::getowner' => 
  array (
    0 => 'int|false',
  ),
  'phar::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'phar::getatime' => 
  array (
    0 => 'int|false',
  ),
  'phar::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'phar::getctime' => 
  array (
    0 => 'int|false',
  ),
  'phar::gettype' => 
  array (
    0 => 'string|false',
  ),
  'phar::isreadable' => 
  array (
    0 => 'bool',
  ),
  'phar::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'phar::isfile' => 
  array (
    0 => 'bool',
  ),
  'phar::isdir' => 
  array (
    0 => 'bool',
  ),
  'phar::islink' => 
  array (
    0 => 'bool',
  ),
  'phar::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'phar::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'phar::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'phar::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'phar::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'phar::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phar::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phar::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'phar::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'phardata::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'string|null',
    'format=' => 'int',
  ),
  'phardata::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'phardata::addemptydir' => 
  array (
    0 => 'void',
    'directory' => 'string',
  ),
  'phardata::addfile' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'localName=' => 'string|null',
  ),
  'phardata::addfromstring' => 
  array (
    0 => 'void',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'phardata::buildfromdirectory' => 
  array (
    0 => 'array',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'phardata::buildfromiterator' => 
  array (
    0 => 'array',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'string|null',
  ),
  'phardata::compressfiles' => 
  array (
    0 => 'void',
    'compression' => 'int',
  ),
  'phardata::decompressfiles' => 
  array (
    0 => 'true',
  ),
  'phardata::compress' => 
  array (
    0 => 'PharData|null',
    'compression' => 'int',
    'extension=' => 'string|null',
  ),
  'phardata::decompress' => 
  array (
    0 => 'PharData|null',
    'extension=' => 'string|null',
  ),
  'phardata::converttoexecutable' => 
  array (
    0 => 'Phar|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'phardata::converttodata' => 
  array (
    0 => 'PharData|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'phardata::copy' => 
  array (
    0 => 'true',
    'from' => 'string',
    'to' => 'string',
  ),
  'phardata::count' => 
  array (
    0 => 'int',
    'mode=' => 'int',
  ),
  'phardata::delete' => 
  array (
    0 => 'true',
    'localName' => 'string',
  ),
  'phardata::delmetadata' => 
  array (
    0 => 'true',
  ),
  'phardata::extractto' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'files=' => 'array|string|null|null',
    'overwrite=' => 'bool',
  ),
  'phardata::getalias' => 
  array (
    0 => 'string|null',
  ),
  'phardata::getpath' => 
  array (
    0 => 'string',
  ),
  'phardata::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array',
  ),
  'phardata::getmodified' => 
  array (
    0 => 'bool',
  ),
  'phardata::getsignature' => 
  array (
    0 => 'array|false',
  ),
  'phardata::getstub' => 
  array (
    0 => 'string',
  ),
  'phardata::getversion' => 
  array (
    0 => 'string',
  ),
  'phardata::hasmetadata' => 
  array (
    0 => 'bool',
  ),
  'phardata::isbuffering' => 
  array (
    0 => 'bool',
  ),
  'phardata::iscompressed' => 
  array (
    0 => 'int|false',
  ),
  'phardata::isfileformat' => 
  array (
    0 => 'bool',
    'format' => 'int',
  ),
  'phardata::iswritable' => 
  array (
    0 => 'bool',
  ),
  'phardata::offsetexists' => 
  array (
    0 => 'bool',
    'localName' => 'mixed',
  ),
  'phardata::offsetget' => 
  array (
    0 => 'SplFileInfo',
    'localName' => 'mixed',
  ),
  'phardata::offsetset' => 
  array (
    0 => 'void',
    'localName' => 'mixed',
    'value' => 'mixed',
  ),
  'phardata::offsetunset' => 
  array (
    0 => 'void',
    'localName' => 'mixed',
  ),
  'phardata::setalias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'phardata::setdefaultstub' => 
  array (
    0 => 'bool',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'phardata::setmetadata' => 
  array (
    0 => 'void',
    'metadata' => 'mixed',
  ),
  'phardata::setsignaturealgorithm' => 
  array (
    0 => 'void',
    'algo' => 'int',
    'privateKey=' => 'string|null',
  ),
  'phardata::setstub' => 
  array (
    0 => 'true',
    'stub' => 'mixed',
    'length=' => 'int',
  ),
  'phardata::startbuffering' => 
  array (
    0 => 'void',
  ),
  'phardata::stopbuffering' => 
  array (
    0 => 'void',
  ),
  'phardata::apiversion' => 
  array (
    0 => 'string',
  ),
  'phardata::cancompress' => 
  array (
    0 => 'bool',
    'compression=' => 'int',
  ),
  'phardata::canwrite' => 
  array (
    0 => 'bool',
  ),
  'phardata::createdefaultstub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'phardata::getsupportedcompression' => 
  array (
    0 => 'array',
  ),
  'phardata::getsupportedsignatures' => 
  array (
    0 => 'array',
  ),
  'phardata::interceptfilefuncs' => 
  array (
    0 => 'void',
  ),
  'phardata::isvalidpharfilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'phardata::loadphar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'string|null',
  ),
  'phardata::mapphar' => 
  array (
    0 => 'bool',
    'alias=' => 'string|null',
    'offset=' => 'int',
  ),
  'phardata::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'phardata::mount' => 
  array (
    0 => 'void',
    'pharPath' => 'string',
    'externalPath' => 'string',
  ),
  'phardata::mungserver' => 
  array (
    0 => 'void',
    'variables' => 'array',
  ),
  'phardata::unlinkarchive' => 
  array (
    0 => 'true',
    'filename' => 'string',
  ),
  'phardata::webphar' => 
  array (
    0 => 'void',
    'alias=' => 'string|null',
    'index=' => 'string|null',
    'fileNotFoundScript=' => 'string|null',
    'mimeTypes=' => 'array',
    'rewrite=' => 'callable|null',
  ),
  'phardata::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'phardata::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'phardata::getsubpath' => 
  array (
    0 => 'string',
  ),
  'phardata::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'phardata::rewind' => 
  array (
    0 => 'void',
  ),
  'phardata::key' => 
  array (
    0 => 'string',
  ),
  'phardata::current' => 
  array (
    0 => 'SplFileInfo|FilesystemIterator|string',
  ),
  'phardata::getflags' => 
  array (
    0 => 'int',
  ),
  'phardata::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'phardata::getfilename' => 
  array (
    0 => 'string',
  ),
  'phardata::getextension' => 
  array (
    0 => 'string',
  ),
  'phardata::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'phardata::isdot' => 
  array (
    0 => 'bool',
  ),
  'phardata::valid' => 
  array (
    0 => 'bool',
  ),
  'phardata::next' => 
  array (
    0 => 'void',
  ),
  'phardata::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'phardata::__tostring' => 
  array (
    0 => 'string',
  ),
  'phardata::getpathname' => 
  array (
    0 => 'string',
  ),
  'phardata::getperms' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getinode' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getsize' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getowner' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getatime' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'phardata::getctime' => 
  array (
    0 => 'int|false',
  ),
  'phardata::gettype' => 
  array (
    0 => 'string|false',
  ),
  'phardata::isreadable' => 
  array (
    0 => 'bool',
  ),
  'phardata::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'phardata::isfile' => 
  array (
    0 => 'bool',
  ),
  'phardata::isdir' => 
  array (
    0 => 'bool',
  ),
  'phardata::islink' => 
  array (
    0 => 'bool',
  ),
  'phardata::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'phardata::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'phardata::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'phardata::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'phardata::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'phardata::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phardata::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phardata::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'phardata::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'pharfileinfo::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
  ),
  'pharfileinfo::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'pharfileinfo::chmod' => 
  array (
    0 => 'void',
    'perms' => 'int',
  ),
  'pharfileinfo::compress' => 
  array (
    0 => 'true',
    'compression' => 'int',
  ),
  'pharfileinfo::decompress' => 
  array (
    0 => 'true',
  ),
  'pharfileinfo::delmetadata' => 
  array (
    0 => 'true',
  ),
  'pharfileinfo::getcompressedsize' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::getcrc32' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::getcontent' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array',
  ),
  'pharfileinfo::getpharflags' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::hasmetadata' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::iscompressed' => 
  array (
    0 => 'bool',
    'compression=' => 'int|null',
  ),
  'pharfileinfo::iscrcchecked' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::setmetadata' => 
  array (
    0 => 'void',
    'metadata' => 'mixed',
  ),
  'pharfileinfo::getpath' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getfilename' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getextension' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'pharfileinfo::getpathname' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getperms' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getinode' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getsize' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getowner' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getgroup' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getatime' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getmtime' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::getctime' => 
  array (
    0 => 'int|false',
  ),
  'pharfileinfo::gettype' => 
  array (
    0 => 'string|false',
  ),
  'pharfileinfo::iswritable' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::isreadable' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::isexecutable' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::isfile' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::isdir' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::islink' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::getlinktarget' => 
  array (
    0 => 'string|false',
  ),
  'pharfileinfo::getrealpath' => 
  array (
    0 => 'string|false',
  ),
  'pharfileinfo::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'string|null',
  ),
  'pharfileinfo::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'string|null',
  ),
  'pharfileinfo::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'pharfileinfo::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'pharfileinfo::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'pharfileinfo::__tostring' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'pharfileinfo::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'random\\randomerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'random\\randomerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'random\\randomerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\randomerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'random\\randomerror::getfile' => 
  array (
    0 => 'string',
  ),
  'random\\randomerror::getline' => 
  array (
    0 => 'int',
  ),
  'random\\randomerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'random\\randomerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\randomerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'random\\randomerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'random\\brokenrandomengineerror::__wakeup' => 
  array (
    0 => 'void',
  ),
  'random\\brokenrandomengineerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::getcode' => 
  array (
    0 => 'mixed',
  ),
  'random\\brokenrandomengineerror::getfile' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::getline' => 
  array (
    0 => 'int',
  ),
  'random\\brokenrandomengineerror::gettrace' => 
  array (
    0 => 'array',
  ),
  'random\\brokenrandomengineerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\brokenrandomengineerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'random\\randomexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'random\\randomexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'random\\randomexception::getfile' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::getline' => 
  array (
    0 => 'int',
  ),
  'random\\randomexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'random\\randomexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\randomexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\mt19937::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'random\\engine\\mt19937::generate' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\mt19937::__serialize' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\mt19937::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'random\\engine\\mt19937::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'string|int|null|null',
  ),
  'random\\engine\\pcgoneseq128xslrr64::generate' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\pcgoneseq128xslrr64::jump' => 
  array (
    0 => 'void',
    'advance' => 'int',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__serialize' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\xoshiro256starstar::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'string|int|null|null',
  ),
  'random\\engine\\xoshiro256starstar::generate' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\xoshiro256starstar::jump' => 
  array (
    0 => 'void',
  ),
  'random\\engine\\xoshiro256starstar::jumplong' => 
  array (
    0 => 'void',
  ),
  'random\\engine\\xoshiro256starstar::__serialize' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\xoshiro256starstar::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'random\\engine\\xoshiro256starstar::__debuginfo' => 
  array (
    0 => 'array',
  ),
  'random\\engine\\secure::generate' => 
  array (
    0 => 'string',
  ),
  'random\\randomizer::__construct' => 
  array (
    0 => 'void',
    'engine=' => 'Random\\Engine|null',
  ),
  'random\\randomizer::nextint' => 
  array (
    0 => 'int',
  ),
  'random\\randomizer::nextfloat' => 
  array (
    0 => 'float',
  ),
  'random\\randomizer::getfloat' => 
  array (
    0 => 'float',
    'min' => 'float',
    'max' => 'float',
    'boundary=' => 'Random\\IntervalBoundary',
  ),
  'random\\randomizer::getint' => 
  array (
    0 => 'int',
    'min' => 'int',
    'max' => 'int',
  ),
  'random\\randomizer::getbytes' => 
  array (
    0 => 'string',
    'length' => 'int',
  ),
  'random\\randomizer::getbytesfromstring' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length' => 'int',
  ),
  'random\\randomizer::shufflearray' => 
  array (
    0 => 'array',
    'array' => 'array',
  ),
  'random\\randomizer::shufflebytes' => 
  array (
    0 => 'string',
    'bytes' => 'string',
  ),
  'random\\randomizer::pickarraykeys' => 
  array (
    0 => 'array',
    'array' => 'array',
    'num' => 'int',
  ),
  'random\\randomizer::__serialize' => 
  array (
    0 => 'array',
  ),
  'random\\randomizer::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'random\\intervalboundary::cases' => 
  array (
    0 => 'array',
  ),
  'reflectionexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'reflectionexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'reflectionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'reflectionexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'reflectionexception::getfile' => 
  array (
    0 => 'string',
  ),
  'reflectionexception::getline' => 
  array (
    0 => 'int',
  ),
  'reflectionexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'reflectionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'reflectionexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'reflectionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflection::getmodifiernames' => 
  array (
    0 => 'array',
    'modifiers' => 'int',
  ),
  'reflectionfunctionabstract::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionfunctionabstract::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isclosure' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::getclosurethis' => 
  array (
    0 => 'object|null',
  ),
  'reflectionfunctionabstract::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunctionabstract::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunctionabstract::getclosureusedvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionfunctionabstract::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunctionabstract::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionfunctionabstract::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionfunctionabstract::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunctionabstract::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunctionabstract::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunctionabstract::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionfunctionabstract::getnumberofparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionfunctionabstract::getnumberofrequiredparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionfunctionabstract::getparameters' => 
  array (
    0 => 'array',
  ),
  'reflectionfunctionabstract::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunctionabstract::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionfunctionabstract::getstaticvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionfunctionabstract::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunctionabstract::hastentativereturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunctionabstract::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionfunctionabstract::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::__construct' => 
  array (
    0 => 'void',
    'function' => 'Closure|string',
  ),
  'reflectionfunction::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isdisabled' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::invoke' => 
  array (
    0 => 'mixed',
    '...args=' => 'mixed',
  ),
  'reflectionfunction::invokeargs' => 
  array (
    0 => 'mixed',
    'args' => 'array',
  ),
  'reflectionfunction::getclosure' => 
  array (
    0 => 'Closure',
  ),
  'reflectionfunction::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isclosure' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::getclosurethis' => 
  array (
    0 => 'object|null',
  ),
  'reflectionfunction::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunction::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunction::getclosureusedvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionfunction::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunction::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionfunction::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionfunction::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunction::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionfunction::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::getnumberofparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionfunction::getnumberofrequiredparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionfunction::getparameters' => 
  array (
    0 => 'array',
  ),
  'reflectionfunction::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionfunction::getstaticvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionfunction::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunction::hastentativereturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunction::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectiongenerator::__construct' => 
  array (
    0 => 'void',
    'generator' => 'Generator',
  ),
  'reflectiongenerator::getexecutingline' => 
  array (
    0 => 'int',
  ),
  'reflectiongenerator::getexecutingfile' => 
  array (
    0 => 'string',
  ),
  'reflectiongenerator::gettrace' => 
  array (
    0 => 'array',
    'options=' => 'int',
  ),
  'reflectiongenerator::getfunction' => 
  array (
    0 => 'ReflectionFunctionAbstract',
  ),
  'reflectiongenerator::getthis' => 
  array (
    0 => 'object|null',
  ),
  'reflectiongenerator::getexecutinggenerator' => 
  array (
    0 => 'Generator',
  ),
  'reflectiongenerator::isclosed' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionparameter::__construct' => 
  array (
    0 => 'void',
    'function' => 'mixed',
    'param' => 'string|int',
  ),
  'reflectionparameter::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionparameter::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionparameter::ispassedbyreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::canbepassedbyvalue' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getdeclaringfunction' => 
  array (
    0 => 'ReflectionFunctionAbstract',
  ),
  'reflectionparameter::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionparameter::getclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionparameter::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionparameter::isarray' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::iscallable' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getposition' => 
  array (
    0 => 'int',
  ),
  'reflectionparameter::isoptional' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isdefaultvalueavailable' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getdefaultvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionparameter::isdefaultvalueconstant' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getdefaultvalueconstantname' => 
  array (
    0 => 'string|null',
  ),
  'reflectionparameter::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::ispromoted' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectiontype::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectiontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectiontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionnamedtype::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionnamedtype::isbuiltin' => 
  array (
    0 => 'bool',
  ),
  'reflectionnamedtype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionnamedtype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionuniontype::gettypes' => 
  array (
    0 => 'array',
  ),
  'reflectionuniontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionuniontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionintersectiontype::gettypes' => 
  array (
    0 => 'array',
  ),
  'reflectionintersectiontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionintersectiontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::__construct' => 
  array (
    0 => 'void',
    'objectOrMethod' => 'object|string',
    'method=' => 'string|null',
  ),
  'reflectionmethod::createfrommethodname' => 
  array (
    0 => 'static',
    'method' => 'string',
  ),
  'reflectionmethod::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isconstructor' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isdestructor' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::getclosure' => 
  array (
    0 => 'Closure',
    'object=' => 'object|null',
  ),
  'reflectionmethod::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionmethod::invoke' => 
  array (
    0 => 'mixed',
    'object' => 'object|null',
    '...args=' => 'mixed',
  ),
  'reflectionmethod::invokeargs' => 
  array (
    0 => 'mixed',
    'object' => 'object|null',
    'args' => 'array',
  ),
  'reflectionmethod::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionmethod::getprototype' => 
  array (
    0 => 'ReflectionMethod',
  ),
  'reflectionmethod::hasprototype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::setaccessible' => 
  array (
    0 => 'void',
    'accessible' => 'bool',
  ),
  'reflectionmethod::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isclosure' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::getclosurethis' => 
  array (
    0 => 'object|null',
  ),
  'reflectionmethod::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionmethod::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionmethod::getclosureusedvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionmethod::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionmethod::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionmethod::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionmethod::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionmethod::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionmethod::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::getnumberofparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionmethod::getnumberofrequiredparameters' => 
  array (
    0 => 'int',
  ),
  'reflectionmethod::getparameters' => 
  array (
    0 => 'array',
  ),
  'reflectionmethod::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionmethod::getstaticvariables' => 
  array (
    0 => 'array',
  ),
  'reflectionmethod::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionmethod::hastentativereturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionmethod::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionclass::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionclass::__construct' => 
  array (
    0 => 'void',
    'objectOrClass' => 'object|string',
  ),
  'reflectionclass::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionclass::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionclass::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionclass::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionclass::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionclass::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionclass::getmethods' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionclass::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionclass::getproperties' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionclass::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::getconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getreflectionconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionclass::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionclass::getinterfaces' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::getinterfacenames' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::gettraits' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::gettraitnames' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::gettraitaliases' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionclass::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionclass::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionclass::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
  ),
  'reflectionclass::newinstanceargs' => 
  array (
    0 => 'object|null',
    'args=' => 'array',
  ),
  'reflectionclass::newlazyghost' => 
  array (
    0 => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionclass::newlazyproxy' => 
  array (
    0 => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionclass::resetaslazyghost' => 
  array (
    0 => 'void',
    'object' => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionclass::resetaslazyproxy' => 
  array (
    0 => 'void',
    'object' => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionclass::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionclass::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionclass::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionclass::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionclass::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionclass::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionclass::getstaticproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionclass::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
  ),
  'reflectionclass::getdefaultproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionclass::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionclass::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionclass::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionclass::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionobject::__construct' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'reflectionobject::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionobject::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionobject::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionobject::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionobject::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionobject::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionobject::getmethods' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionobject::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionobject::getproperties' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionobject::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::getconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getreflectionconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionobject::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionobject::getinterfaces' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::getinterfacenames' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::gettraits' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::gettraitnames' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::gettraitaliases' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionobject::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionobject::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionobject::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
  ),
  'reflectionobject::newinstanceargs' => 
  array (
    0 => 'object|null',
    'args=' => 'array',
  ),
  'reflectionobject::newlazyghost' => 
  array (
    0 => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionobject::newlazyproxy' => 
  array (
    0 => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionobject::resetaslazyghost' => 
  array (
    0 => 'void',
    'object' => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionobject::resetaslazyproxy' => 
  array (
    0 => 'void',
    'object' => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionobject::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionobject::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionobject::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionobject::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionobject::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionobject::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionobject::getstaticproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionobject::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
  ),
  'reflectionobject::getdefaultproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionobject::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionobject::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionobject::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionobject::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionproperty::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionproperty::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'property' => 'string',
  ),
  'reflectionproperty::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionproperty::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionproperty::getmangledname' => 
  array (
    0 => 'string',
  ),
  'reflectionproperty::getvalue' => 
  array (
    0 => 'mixed',
    'object=' => 'object|null',
  ),
  'reflectionproperty::setvalue' => 
  array (
    0 => 'void',
    'objectOrValue' => 'mixed',
    'value=' => 'mixed',
  ),
  'reflectionproperty::getrawvalue' => 
  array (
    0 => 'mixed',
    'object' => 'object',
  ),
  'reflectionproperty::setrawvalue' => 
  array (
    0 => 'void',
    'object' => 'object',
    'value' => 'mixed',
  ),
  'reflectionproperty::setrawvaluewithoutlazyinitialization' => 
  array (
    0 => 'void',
    'object' => 'object',
    'value' => 'mixed',
  ),
  'reflectionproperty::skiplazyinitialization' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'reflectionproperty::islazy' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionproperty::isinitialized' => 
  array (
    0 => 'bool',
    'object=' => 'object|null',
  ),
  'reflectionproperty::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprivateset' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprotectedset' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isdefault' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isdynamic' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isvirtual' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::ispromoted' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionproperty::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionproperty::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionproperty::setaccessible' => 
  array (
    0 => 'void',
    'accessible' => 'bool',
  ),
  'reflectionproperty::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionproperty::getsettabletype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionproperty::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::hasdefaultvalue' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::getdefaultvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionproperty::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionproperty::hashooks' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::gethooks' => 
  array (
    0 => 'array',
  ),
  'reflectionproperty::hashook' => 
  array (
    0 => 'bool',
    'type' => 'PropertyHookType',
  ),
  'reflectionproperty::gethook' => 
  array (
    0 => 'ReflectionMethod|null',
    'type' => 'PropertyHookType',
  ),
  'reflectionproperty::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionclassconstant::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'reflectionclassconstant::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionclassconstant::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionclassconstant::getvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionclassconstant::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionclassconstant::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionclassconstant::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionclassconstant::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionclassconstant::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionextension::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionextension::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'reflectionextension::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionextension::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionextension::getversion' => 
  array (
    0 => 'string|null',
  ),
  'reflectionextension::getfunctions' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::getconstants' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::getinientries' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::getclasses' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::getclassnames' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::getdependencies' => 
  array (
    0 => 'array',
  ),
  'reflectionextension::info' => 
  array (
    0 => 'void',
  ),
  'reflectionextension::ispersistent' => 
  array (
    0 => 'bool',
  ),
  'reflectionextension::istemporary' => 
  array (
    0 => 'bool',
  ),
  'reflectionzendextension::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionzendextension::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'reflectionzendextension::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getversion' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getauthor' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::geturl' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getcopyright' => 
  array (
    0 => 'string',
  ),
  'reflectionreference::fromarrayelement' => 
  array (
    0 => 'ReflectionReference|null',
    'array' => 'array',
    'key' => 'string|int',
  ),
  'reflectionreference::getid' => 
  array (
    0 => 'string',
  ),
  'reflectionreference::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionreference::__construct' => 
  array (
    0 => 'void',
  ),
  'reflectionattribute::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionattribute::gettarget' => 
  array (
    0 => 'int',
  ),
  'reflectionattribute::isrepeated' => 
  array (
    0 => 'bool',
  ),
  'reflectionattribute::getarguments' => 
  array (
    0 => 'array',
  ),
  'reflectionattribute::newinstance' => 
  array (
    0 => 'object',
  ),
  'reflectionattribute::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionattribute::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionattribute::__construct' => 
  array (
    0 => 'void',
  ),
  'reflectionenum::__construct' => 
  array (
    0 => 'void',
    'objectOrClass' => 'object|string',
  ),
  'reflectionenum::hascase' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::getcase' => 
  array (
    0 => 'ReflectionEnumUnitCase',
    'name' => 'string',
  ),
  'reflectionenum::getcases' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::isbacked' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::getbackingtype' => 
  array (
    0 => 'ReflectionNamedType|null',
  ),
  'reflectionenum::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionenum::getstartline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionenum::getendline' => 
  array (
    0 => 'int|false',
  ),
  'reflectionenum::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionenum::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionenum::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionenum::getmethods' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionenum::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionenum::getproperties' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionenum::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::getconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getreflectionconstants' => 
  array (
    0 => 'array',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionenum::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionenum::getinterfaces' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::getinterfacenames' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::gettraits' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::gettraitnames' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::gettraitaliases' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenum::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionenum::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionenum::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
  ),
  'reflectionenum::newinstanceargs' => 
  array (
    0 => 'object|null',
    'args=' => 'array',
  ),
  'reflectionenum::newlazyghost' => 
  array (
    0 => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionenum::newlazyproxy' => 
  array (
    0 => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionenum::resetaslazyghost' => 
  array (
    0 => 'void',
    'object' => 'object',
    'initializer' => 'callable',
    'options=' => 'int',
  ),
  'reflectionenum::resetaslazyproxy' => 
  array (
    0 => 'void',
    'object' => 'object',
    'factory' => 'callable',
    'options=' => 'int',
  ),
  'reflectionenum::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionenum::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionenum::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionenum::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionenum::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionenum::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionenum::getstaticproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionenum::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
  ),
  'reflectionenum::getdefaultproperties' => 
  array (
    0 => 'array',
  ),
  'reflectionenum::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionenum::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionenum::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionenum::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionenumunitcase::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'reflectionenumunitcase::getenum' => 
  array (
    0 => 'ReflectionEnum',
  ),
  'reflectionenumunitcase::getvalue' => 
  array (
    0 => 'UnitEnum',
  ),
  'reflectionenumunitcase::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenumunitcase::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenumunitcase::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenumunitcase::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionenumunitcase::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionenumunitcase::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionenumunitcase::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionenumbackedcase::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'reflectionenumbackedcase::getbackingvalue' => 
  array (
    0 => 'string|int',
  ),
  'reflectionenumbackedcase::getenum' => 
  array (
    0 => 'ReflectionEnum',
  ),
  'reflectionenumbackedcase::getvalue' => 
  array (
    0 => 'UnitEnum',
  ),
  'reflectionenumbackedcase::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenumbackedcase::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenumbackedcase::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenumbackedcase::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionenumbackedcase::getdoccomment' => 
  array (
    0 => 'string|false',
  ),
  'reflectionenumbackedcase::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'reflectionenumbackedcase::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfiber::__construct' => 
  array (
    0 => 'void',
    'fiber' => 'Fiber',
  ),
  'reflectionfiber::getfiber' => 
  array (
    0 => 'Fiber',
  ),
  'reflectionfiber::getexecutingfile' => 
  array (
    0 => 'string|null',
  ),
  'reflectionfiber::getexecutingline' => 
  array (
    0 => 'int|null',
  ),
  'reflectionfiber::getcallable' => 
  array (
    0 => 'callable',
  ),
  'reflectionfiber::gettrace' => 
  array (
    0 => 'array',
    'options=' => 'int',
  ),
  'reflectionconstant::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'reflectionconstant::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionconstant::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionconstant::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionconstant::getvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionconstant::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionconstant::getfilename' => 
  array (
    0 => 'string|false',
  ),
  'reflectionconstant::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionconstant::getextensionname' => 
  array (
    0 => 'string|false',
  ),
  'reflectionconstant::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionconstant::getattributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'propertyhooktype::cases' => 
  array (
    0 => 'array',
  ),
  'propertyhooktype::from' => 
  array (
    0 => 'static',
    'value' => 'string|int',
  ),
  'propertyhooktype::tryfrom' => 
  array (
    0 => 'static|null',
    'value' => 'string|int',
  ),
  'simplexmlelement::xpath' => 
  array (
    0 => 'array|false|null|null',
    'expression' => 'string',
  ),
  'simplexmlelement::registerxpathnamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'simplexmlelement::asxml' => 
  array (
    0 => 'string|bool',
    'filename=' => 'string|null',
  ),
  'simplexmlelement::savexml' => 
  array (
    0 => 'string|bool',
    'filename=' => 'string|null',
  ),
  'simplexmlelement::getnamespaces' => 
  array (
    0 => 'array',
    'recursive=' => 'bool',
  ),
  'simplexmlelement::getdocnamespaces' => 
  array (
    0 => 'array|false',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'simplexmlelement::children' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'simplexmlelement::attributes' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'simplexmlelement::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
    'options=' => 'int',
    'dataIsURL=' => 'bool',
    'namespaceOrPrefix=' => 'string',
    'isPrefix=' => 'bool',
  ),
  'simplexmlelement::addchild' => 
  array (
    0 => 'SimpleXMLElement|null',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string|null',
  ),
  'simplexmlelement::addattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'string|null',
  ),
  'simplexmlelement::getname' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::__tostring' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::count' => 
  array (
    0 => 'int',
  ),
  'simplexmlelement::rewind' => 
  array (
    0 => 'void',
  ),
  'simplexmlelement::valid' => 
  array (
    0 => 'bool',
  ),
  'simplexmlelement::current' => 
  array (
    0 => 'SimpleXMLElement',
  ),
  'simplexmlelement::key' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::next' => 
  array (
    0 => 'void',
  ),
  'simplexmlelement::haschildren' => 
  array (
    0 => 'bool',
  ),
  'simplexmlelement::getchildren' => 
  array (
    0 => 'SimpleXMLElement|null',
  ),
  'simplexmliterator::xpath' => 
  array (
    0 => 'array|false|null|null',
    'expression' => 'string',
  ),
  'simplexmliterator::registerxpathnamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'simplexmliterator::asxml' => 
  array (
    0 => 'string|bool',
    'filename=' => 'string|null',
  ),
  'simplexmliterator::savexml' => 
  array (
    0 => 'string|bool',
    'filename=' => 'string|null',
  ),
  'simplexmliterator::getnamespaces' => 
  array (
    0 => 'array',
    'recursive=' => 'bool',
  ),
  'simplexmliterator::getdocnamespaces' => 
  array (
    0 => 'array|false',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'simplexmliterator::children' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'simplexmliterator::attributes' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'simplexmliterator::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
    'options=' => 'int',
    'dataIsURL=' => 'bool',
    'namespaceOrPrefix=' => 'string',
    'isPrefix=' => 'bool',
  ),
  'simplexmliterator::addchild' => 
  array (
    0 => 'SimpleXMLElement|null',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string|null',
  ),
  'simplexmliterator::addattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'string|null',
  ),
  'simplexmliterator::getname' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::count' => 
  array (
    0 => 'int',
  ),
  'simplexmliterator::rewind' => 
  array (
    0 => 'void',
  ),
  'simplexmliterator::valid' => 
  array (
    0 => 'bool',
  ),
  'simplexmliterator::current' => 
  array (
    0 => 'SimpleXMLElement',
  ),
  'simplexmliterator::key' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::next' => 
  array (
    0 => 'void',
  ),
  'simplexmliterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'simplexmliterator::getchildren' => 
  array (
    0 => 'SimpleXMLElement|null',
  ),
  'phptoken::tokenize' => 
  array (
    0 => 'array',
    'code' => 'string',
    'flags=' => 'int',
  ),
  'phptoken::__construct' => 
  array (
    0 => 'void',
    'id' => 'int',
    'text' => 'string',
    'line=' => 'int',
    'pos=' => 'int',
  ),
  'phptoken::is' => 
  array (
    0 => 'bool',
    'kind' => 'mixed',
  ),
  'phptoken::isignorable' => 
  array (
    0 => 'bool',
  ),
  'phptoken::gettokenname' => 
  array (
    0 => 'string|null',
  ),
  'phptoken::__tostring' => 
  array (
    0 => 'string',
  ),
  'dom\\adjacentposition::cases' => 
  array (
    0 => 'array',
  ),
  'dom\\adjacentposition::from' => 
  array (
    0 => 'static',
    'value' => 'string|int',
  ),
  'dom\\adjacentposition::tryfrom' => 
  array (
    0 => 'static|null',
    'value' => 'string|int',
  ),
  'domexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'domexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'domexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'domexception::getfile' => 
  array (
    0 => 'string',
  ),
  'domexception::getline' => 
  array (
    0 => 'int',
  ),
  'domexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'domexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'domexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'domexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dom\\domexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\domexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'dom\\domexception::getfile' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::getline' => 
  array (
    0 => 'int',
  ),
  'dom\\domexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'dom\\domexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dom\\domexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'domimplementation::hasfeature' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domimplementation::createdocumenttype' => 
  array (
    0 => 'mixed',
    'qualifiedName' => 'string',
    'publicId=' => 'string',
    'systemId=' => 'string',
  ),
  'domimplementation::createdocument' => 
  array (
    0 => 'DOMDocument',
    'namespace=' => 'string|null',
    'qualifiedName=' => 'string',
    'doctype=' => 'DOMDocumentType|null',
  ),
  'dom\\implementation::createdocumenttype' => 
  array (
    0 => 'Dom\\DocumentType',
    'qualifiedName' => 'string',
    'publicId' => 'string',
    'systemId' => 'string',
  ),
  'dom\\implementation::createdocument' => 
  array (
    0 => 'Dom\\XMLDocument',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'doctype=' => 'Dom\\DocumentType|null',
  ),
  'dom\\implementation::createhtmldocument' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'title=' => 'string|null',
  ),
  'domnode::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domnode::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domnode::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domnode::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domnode::getlineno' => 
  array (
    0 => 'int',
  ),
  'domnode::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domnode::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domnode::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domnode::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domnode::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domnode::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domnode::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domnode::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domnode::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domnode::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domnode::normalize' => 
  array (
    0 => 'void',
  ),
  'domnode::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domnode::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domnode::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domnode::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domnode::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domnode::__sleep' => 
  array (
    0 => 'array',
  ),
  'domnode::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\node::__construct' => 
  array (
    0 => 'void',
  ),
  'dom\\node::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\node::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\node::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\node::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\node::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\node::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\node::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\node::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\node::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\node::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\node::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\node::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\node::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\node::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\node::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\node::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\node::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\node::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\node::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\node::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\node::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domnamespacenode::__sleep' => 
  array (
    0 => 'array',
  ),
  'domnamespacenode::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\namespaceinfo::__construct' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::__construct' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::appendxml' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'domdocumentfragment::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocumentfragment::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocumentfragment::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocumentfragment::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domdocumentfragment::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocumentfragment::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocumentfragment::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocumentfragment::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocumentfragment::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domdocumentfragment::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domdocumentfragment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domdocumentfragment::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domdocumentfragment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domdocumentfragment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocumentfragment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocumentfragment::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocumentfragment::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domdocumentfragment::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domdocumentfragment::normalize' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domdocumentfragment::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domdocumentfragment::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domdocumentfragment::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domdocumentfragment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocumentfragment::__sleep' => 
  array (
    0 => 'array',
  ),
  'domdocumentfragment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\documentfragment::appendxml' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'dom\\documentfragment::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documentfragment::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documentfragment::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documentfragment::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\documentfragment::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\documentfragment::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\documentfragment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\documentfragment::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\documentfragment::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\documentfragment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\documentfragment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\documentfragment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\documentfragment::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\documentfragment::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\documentfragment::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\documentfragment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\documentfragment::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\documentfragment::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\documentfragment::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documentfragment::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documentfragment::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\documentfragment::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\documentfragment::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\documentfragment::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\documentfragment::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\documentfragment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\document::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\document::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\document::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\document::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\document::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\document::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\document::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\document::createcdatasection' => 
  array (
    0 => 'Dom\\CDATASection',
    'data' => 'string',
  ),
  'dom\\document::createcomment' => 
  array (
    0 => 'Dom\\Comment',
    'data' => 'string',
  ),
  'dom\\document::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\document::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\document::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\document::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\document::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\document::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\document::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'string|null',
  ),
  'dom\\document::schemavalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'dom\\document::schemavalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'flags=' => 'int',
  ),
  'dom\\document::relaxngvalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'dom\\document::relaxngvalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
  ),
  'dom\\document::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\document::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\document::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\document::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\document::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\document::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\document::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\document::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\document::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\document::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\document::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\document::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\document::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\document::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\document::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\document::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\document::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\document::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\document::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\document::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\document::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\document::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\document::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\document::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\document::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\document::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\document::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domdocument::__construct' => 
  array (
    0 => 'void',
    'version=' => 'string',
    'encoding=' => 'string',
  ),
  'domdocument::createattribute' => 
  array (
    0 => 'mixed',
    'localName' => 'string',
  ),
  'domdocument::createattributens' => 
  array (
    0 => 'mixed',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'domdocument::createcdatasection' => 
  array (
    0 => 'mixed',
    'data' => 'string',
  ),
  'domdocument::createcomment' => 
  array (
    0 => 'DOMComment',
    'data' => 'string',
  ),
  'domdocument::createdocumentfragment' => 
  array (
    0 => 'DOMDocumentFragment',
  ),
  'domdocument::createelement' => 
  array (
    0 => 'mixed',
    'localName' => 'string',
    'value=' => 'string',
  ),
  'domdocument::createelementns' => 
  array (
    0 => 'mixed',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value=' => 'string',
  ),
  'domdocument::createentityreference' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'domdocument::createprocessinginstruction' => 
  array (
    0 => 'mixed',
    'target' => 'string',
    'data=' => 'string',
  ),
  'domdocument::createtextnode' => 
  array (
    0 => 'DOMText',
    'data' => 'string',
  ),
  'domdocument::getelementbyid' => 
  array (
    0 => 'DOMElement|null',
    'elementId' => 'string',
  ),
  'domdocument::getelementsbytagname' => 
  array (
    0 => 'DOMNodeList',
    'qualifiedName' => 'string',
  ),
  'domdocument::getelementsbytagnamens' => 
  array (
    0 => 'DOMNodeList',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domdocument::importnode' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'domdocument::load' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'domdocument::loadxml' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'options=' => 'int',
  ),
  'domdocument::normalizedocument' => 
  array (
    0 => 'void',
  ),
  'domdocument::registernodeclass' => 
  array (
    0 => 'true',
    'baseClass' => 'string',
    'extendedClass' => 'string|null',
  ),
  'domdocument::save' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'domdocument::loadhtml' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'options=' => 'int',
  ),
  'domdocument::loadhtmlfile' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'domdocument::savehtml' => 
  array (
    0 => 'string|false',
    'node=' => 'DOMNode|null',
  ),
  'domdocument::savehtmlfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'domdocument::savexml' => 
  array (
    0 => 'string|false',
    'node=' => 'DOMNode|null',
    'options=' => 'int',
  ),
  'domdocument::schemavalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'domdocument::schemavalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'flags=' => 'int',
  ),
  'domdocument::relaxngvalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'domdocument::relaxngvalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
  ),
  'domdocument::validate' => 
  array (
    0 => 'bool',
  ),
  'domdocument::xinclude' => 
  array (
    0 => 'int|false',
    'options=' => 'int',
  ),
  'domdocument::adoptnode' => 
  array (
    0 => 'DOMNode|false',
    'node' => 'DOMNode',
  ),
  'domdocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocument::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domdocument::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocument::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocument::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocument::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domdocument::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domdocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domdocument::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domdocument::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domdocument::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocument::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocument::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocument::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domdocument::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domdocument::normalize' => 
  array (
    0 => 'void',
  ),
  'domdocument::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domdocument::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domdocument::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domdocument::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domdocument::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocument::__sleep' => 
  array (
    0 => 'array',
  ),
  'domdocument::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\htmldocument::createempty' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'encoding=' => 'string',
  ),
  'dom\\htmldocument::createfromfile' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'path' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'string|null',
  ),
  'dom\\htmldocument::createfromstring' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'source' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'string|null',
  ),
  'dom\\htmldocument::savexml' => 
  array (
    0 => 'string|false',
    'node=' => 'Dom\\Node|null',
    'options=' => 'int',
  ),
  'dom\\htmldocument::savexmlfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'dom\\htmldocument::savehtml' => 
  array (
    0 => 'string',
    'node=' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::savehtmlfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
  ),
  'dom\\htmldocument::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\htmldocument::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmldocument::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\htmldocument::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\htmldocument::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmldocument::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\htmldocument::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\htmldocument::createcdatasection' => 
  array (
    0 => 'Dom\\CDATASection',
    'data' => 'string',
  ),
  'dom\\htmldocument::createcomment' => 
  array (
    0 => 'Dom\\Comment',
    'data' => 'string',
  ),
  'dom\\htmldocument::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\htmldocument::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\htmldocument::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmldocument::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\htmldocument::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmldocument::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\htmldocument::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'string|null',
  ),
  'dom\\htmldocument::schemavalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'dom\\htmldocument::schemavalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'flags=' => 'int',
  ),
  'dom\\htmldocument::relaxngvalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'dom\\htmldocument::relaxngvalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
  ),
  'dom\\htmldocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmldocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmldocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmldocument::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\htmldocument::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\htmldocument::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\htmldocument::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\htmldocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmldocument::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\htmldocument::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\htmldocument::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\htmldocument::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\htmldocument::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\htmldocument::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\htmldocument::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmldocument::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmldocument::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmldocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\htmldocument::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\htmldocument::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\htmldocument::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\htmldocument::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\htmldocument::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\xmldocument::createempty' => 
  array (
    0 => 'Dom\\XMLDocument',
    'version=' => 'string',
    'encoding=' => 'string',
  ),
  'dom\\xmldocument::createfromfile' => 
  array (
    0 => 'Dom\\XMLDocument',
    'path' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'string|null',
  ),
  'dom\\xmldocument::createfromstring' => 
  array (
    0 => 'Dom\\XMLDocument',
    'source' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'string|null',
  ),
  'dom\\xmldocument::createentityreference' => 
  array (
    0 => 'Dom\\EntityReference',
    'name' => 'string',
  ),
  'dom\\xmldocument::validate' => 
  array (
    0 => 'bool',
  ),
  'dom\\xmldocument::xinclude' => 
  array (
    0 => 'int',
    'options=' => 'int',
  ),
  'dom\\xmldocument::savexml' => 
  array (
    0 => 'string|false',
    'node=' => 'Dom\\Node|null',
    'options=' => 'int',
  ),
  'dom\\xmldocument::savexmlfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'dom\\xmldocument::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\xmldocument::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\xmldocument::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\xmldocument::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\xmldocument::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\xmldocument::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\xmldocument::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\xmldocument::createcdatasection' => 
  array (
    0 => 'Dom\\CDATASection',
    'data' => 'string',
  ),
  'dom\\xmldocument::createcomment' => 
  array (
    0 => 'Dom\\Comment',
    'data' => 'string',
  ),
  'dom\\xmldocument::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\xmldocument::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\xmldocument::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\xmldocument::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\xmldocument::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\xmldocument::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\xmldocument::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'string|null',
  ),
  'dom\\xmldocument::schemavalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'dom\\xmldocument::schemavalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'flags=' => 'int',
  ),
  'dom\\xmldocument::relaxngvalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'dom\\xmldocument::relaxngvalidatesource' => 
  array (
    0 => 'bool',
    'source' => 'string',
  ),
  'dom\\xmldocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\xmldocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\xmldocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\xmldocument::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\xmldocument::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\xmldocument::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\xmldocument::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\xmldocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\xmldocument::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\xmldocument::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\xmldocument::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\xmldocument::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\xmldocument::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\xmldocument::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\xmldocument::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\xmldocument::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\xmldocument::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\xmldocument::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\xmldocument::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\xmldocument::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\xmldocument::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\xmldocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\xmldocument::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\xmldocument::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\xmldocument::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\xmldocument::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\xmldocument::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domnodelist::count' => 
  array (
    0 => 'int',
  ),
  'domnodelist::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'domnodelist::item' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'dom\\nodelist::count' => 
  array (
    0 => 'int',
  ),
  'dom\\nodelist::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\nodelist::item' => 
  array (
    0 => 'Dom\\Node|null',
    'index' => 'int',
  ),
  'domnamednodemap::getnameditem' => 
  array (
    0 => 'DOMNode|null',
    'qualifiedName' => 'string',
  ),
  'domnamednodemap::getnameditemns' => 
  array (
    0 => 'DOMNode|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domnamednodemap::item' => 
  array (
    0 => 'DOMNode|null',
    'index' => 'int',
  ),
  'domnamednodemap::count' => 
  array (
    0 => 'int',
  ),
  'domnamednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\namednodemap::item' => 
  array (
    0 => 'Dom\\Attr|null',
    'index' => 'int',
  ),
  'dom\\namednodemap::getnameditem' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\namednodemap::getnameditemns' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\namednodemap::count' => 
  array (
    0 => 'int',
  ),
  'dom\\namednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\dtdnamednodemap::item' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null|null',
    'index' => 'int',
  ),
  'dom\\dtdnamednodemap::getnameditem' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null|null',
    'qualifiedName' => 'string',
  ),
  'dom\\dtdnamednodemap::getnameditemns' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\dtdnamednodemap::count' => 
  array (
    0 => 'int',
  ),
  'dom\\dtdnamednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\htmlcollection::item' => 
  array (
    0 => 'Dom\\Element|null',
    'index' => 'int',
  ),
  'dom\\htmlcollection::nameditem' => 
  array (
    0 => 'Dom\\Element|null',
    'key' => 'string',
  ),
  'dom\\htmlcollection::count' => 
  array (
    0 => 'int',
  ),
  'dom\\htmlcollection::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'domcharacterdata::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcharacterdata::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcharacterdata::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcharacterdata::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcharacterdata::replacedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'domcharacterdata::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcharacterdata::remove' => 
  array (
    0 => 'void',
  ),
  'domcharacterdata::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcharacterdata::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcharacterdata::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domcharacterdata::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcharacterdata::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcharacterdata::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcharacterdata::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcharacterdata::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domcharacterdata::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domcharacterdata::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domcharacterdata::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domcharacterdata::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcharacterdata::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcharacterdata::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcharacterdata::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcharacterdata::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domcharacterdata::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domcharacterdata::normalize' => 
  array (
    0 => 'void',
  ),
  'domcharacterdata::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domcharacterdata::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domcharacterdata::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domcharacterdata::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domcharacterdata::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcharacterdata::__sleep' => 
  array (
    0 => 'array',
  ),
  'domcharacterdata::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\characterdata::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\characterdata::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\characterdata::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\characterdata::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\characterdata::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\characterdata::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\characterdata::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\characterdata::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\characterdata::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\characterdata::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\characterdata::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\characterdata::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\characterdata::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\characterdata::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\characterdata::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\characterdata::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\characterdata::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\characterdata::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\characterdata::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\characterdata::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\characterdata::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\characterdata::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\characterdata::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\characterdata::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domattr::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'string',
  ),
  'domattr::isid' => 
  array (
    0 => 'bool',
  ),
  'domattr::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domattr::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domattr::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domattr::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domattr::getlineno' => 
  array (
    0 => 'int',
  ),
  'domattr::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domattr::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domattr::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domattr::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domattr::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domattr::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domattr::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domattr::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domattr::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domattr::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domattr::normalize' => 
  array (
    0 => 'void',
  ),
  'domattr::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domattr::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domattr::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domattr::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domattr::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domattr::__sleep' => 
  array (
    0 => 'array',
  ),
  'domattr::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\attr::isid' => 
  array (
    0 => 'bool',
  ),
  'dom\\attr::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\attr::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\attr::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\attr::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\attr::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\attr::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\attr::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\attr::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\attr::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\attr::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\attr::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\attr::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\attr::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\attr::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\attr::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\attr::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\attr::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\attr::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\attr::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\attr::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\attr::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\attr::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domelement::__construct' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string',
  ),
  'domelement::getattribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'domelement::getattributenames' => 
  array (
    0 => 'array',
  ),
  'domelement::getattributens' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domelement::getattributenode' => 
  array (
    0 => 'mixed',
    'qualifiedName' => 'string',
  ),
  'domelement::getattributenodens' => 
  array (
    0 => 'mixed',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domelement::getelementsbytagname' => 
  array (
    0 => 'DOMNodeList',
    'qualifiedName' => 'string',
  ),
  'domelement::getelementsbytagnamens' => 
  array (
    0 => 'DOMNodeList',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domelement::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'domelement::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domelement::removeattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'domelement::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'domelement::removeattributenode' => 
  array (
    0 => 'mixed',
    'attr' => 'DOMAttr',
  ),
  'domelement::setattribute' => 
  array (
    0 => 'mixed',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'domelement::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'domelement::setattributenode' => 
  array (
    0 => 'mixed',
    'attr' => 'DOMAttr',
  ),
  'domelement::setattributenodens' => 
  array (
    0 => 'mixed',
    'attr' => 'DOMAttr',
  ),
  'domelement::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'domelement::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'domelement::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'DOMAttr',
    'isId' => 'bool',
  ),
  'domelement::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'domelement::remove' => 
  array (
    0 => 'void',
  ),
  'domelement::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::insertadjacentelement' => 
  array (
    0 => 'DOMElement|null',
    'where' => 'string',
    'element' => 'DOMElement',
  ),
  'domelement::insertadjacenttext' => 
  array (
    0 => 'void',
    'where' => 'string',
    'data' => 'string',
  ),
  'domelement::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domelement::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domelement::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domelement::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domelement::getlineno' => 
  array (
    0 => 'int',
  ),
  'domelement::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domelement::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domelement::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domelement::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domelement::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domelement::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domelement::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domelement::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domelement::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domelement::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domelement::normalize' => 
  array (
    0 => 'void',
  ),
  'domelement::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domelement::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domelement::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domelement::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domelement::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domelement::__sleep' => 
  array (
    0 => 'array',
  ),
  'domelement::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\element::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'dom\\element::getattributenames' => 
  array (
    0 => 'array',
  ),
  'dom\\element::getattribute' => 
  array (
    0 => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getattributens' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\element::setattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\element::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\element::removeattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
  ),
  'dom\\element::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\element::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\element::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'dom\\element::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\element::getattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\element::setattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\element::setattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\element::removeattributenode' => 
  array (
    0 => 'Dom\\Attr',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\element::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\element::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\element::insertadjacentelement' => 
  array (
    0 => 'Dom\\Element|null',
    'where' => 'Dom\\AdjacentPosition',
    'element' => 'Dom\\Element',
  ),
  'dom\\element::insertadjacenttext' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'data' => 'string',
  ),
  'dom\\element::insertadjacenthtml' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'string' => 'string',
  ),
  'dom\\element::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\element::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\element::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'Dom\\Attr',
    'isId' => 'bool',
  ),
  'dom\\element::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\element::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\element::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\element::closest' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\element::matches' => 
  array (
    0 => 'bool',
    'selectors' => 'string',
  ),
  'dom\\element::getinscopenamespaces' => 
  array (
    0 => 'array',
  ),
  'dom\\element::getdescendantnamespaces' => 
  array (
    0 => 'array',
  ),
  'dom\\element::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\element::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\element::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\element::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\element::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\element::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\element::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\element::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\element::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\element::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\element::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\element::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\element::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\element::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\element::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\element::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\element::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\element::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\element::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\element::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\element::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmlelement::getattributenames' => 
  array (
    0 => 'array',
  ),
  'dom\\htmlelement::getattribute' => 
  array (
    0 => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getattributens' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmlelement::setattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\htmlelement::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\htmlelement::removeattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmlelement::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\htmlelement::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmlelement::getattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmlelement::setattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\htmlelement::setattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\htmlelement::removeattributenode' => 
  array (
    0 => 'Dom\\Attr',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\htmlelement::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'dom\\htmlelement::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\htmlelement::insertadjacentelement' => 
  array (
    0 => 'Dom\\Element|null',
    'where' => 'Dom\\AdjacentPosition',
    'element' => 'Dom\\Element',
  ),
  'dom\\htmlelement::insertadjacenttext' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'data' => 'string',
  ),
  'dom\\htmlelement::insertadjacenthtml' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'string' => 'string',
  ),
  'dom\\htmlelement::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'Dom\\Attr',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::queryselector' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\htmlelement::queryselectorall' => 
  array (
    0 => 'Dom\\NodeList',
    'selectors' => 'string',
  ),
  'dom\\htmlelement::closest' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
  ),
  'dom\\htmlelement::matches' => 
  array (
    0 => 'bool',
    'selectors' => 'string',
  ),
  'dom\\htmlelement::getinscopenamespaces' => 
  array (
    0 => 'array',
  ),
  'dom\\htmlelement::getdescendantnamespaces' => 
  array (
    0 => 'array',
  ),
  'dom\\htmlelement::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\htmlelement::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmlelement::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\htmlelement::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\htmlelement::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\htmlelement::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\htmlelement::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\htmlelement::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\htmlelement::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\htmlelement::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\htmlelement::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\htmlelement::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmlelement::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmlelement::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmlelement::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\htmlelement::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\htmlelement::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\htmlelement::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\htmlelement::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\htmlelement::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domtext::__construct' => 
  array (
    0 => 'void',
    'data=' => 'string',
  ),
  'domtext::iswhitespaceinelementcontent' => 
  array (
    0 => 'bool',
  ),
  'domtext::iselementcontentwhitespace' => 
  array (
    0 => 'bool',
  ),
  'domtext::splittext' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
  ),
  'domtext::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domtext::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domtext::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domtext::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domtext::replacedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'domtext::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domtext::remove' => 
  array (
    0 => 'void',
  ),
  'domtext::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domtext::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domtext::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domtext::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domtext::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domtext::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domtext::getlineno' => 
  array (
    0 => 'int',
  ),
  'domtext::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domtext::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domtext::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domtext::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domtext::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domtext::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domtext::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domtext::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domtext::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domtext::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domtext::normalize' => 
  array (
    0 => 'void',
  ),
  'domtext::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domtext::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domtext::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domtext::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domtext::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domtext::__sleep' => 
  array (
    0 => 'array',
  ),
  'domtext::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\text::splittext' => 
  array (
    0 => 'Dom\\Text',
    'offset' => 'int',
  ),
  'dom\\text::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\text::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\text::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\text::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\text::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\text::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\text::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\text::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\text::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\text::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\text::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\text::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\text::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\text::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\text::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\text::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\text::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\text::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\text::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\text::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\text::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\text::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\text::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\text::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\text::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\text::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\text::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\text::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\text::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\text::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domcomment::__construct' => 
  array (
    0 => 'void',
    'data=' => 'string',
  ),
  'domcomment::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcomment::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcomment::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcomment::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcomment::replacedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'domcomment::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcomment::remove' => 
  array (
    0 => 'void',
  ),
  'domcomment::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcomment::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcomment::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domcomment::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcomment::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcomment::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcomment::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcomment::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domcomment::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domcomment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domcomment::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domcomment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcomment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcomment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcomment::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcomment::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domcomment::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domcomment::normalize' => 
  array (
    0 => 'void',
  ),
  'domcomment::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domcomment::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domcomment::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domcomment::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domcomment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcomment::__sleep' => 
  array (
    0 => 'array',
  ),
  'domcomment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\comment::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\comment::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\comment::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\comment::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\comment::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\comment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\comment::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\comment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\comment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\comment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\comment::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\comment::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\comment::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\comment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\comment::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\comment::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\comment::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\comment::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\comment::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\comment::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\comment::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\comment::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\comment::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\comment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domcdatasection::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'domcdatasection::iswhitespaceinelementcontent' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::iselementcontentwhitespace' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::splittext' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
  ),
  'domcdatasection::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcdatasection::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcdatasection::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcdatasection::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcdatasection::replacedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'domcdatasection::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcdatasection::remove' => 
  array (
    0 => 'void',
  ),
  'domcdatasection::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcdatasection::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcdatasection::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domcdatasection::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcdatasection::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domcdatasection::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcdatasection::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcdatasection::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domcdatasection::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domcdatasection::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcdatasection::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcdatasection::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcdatasection::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcdatasection::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domcdatasection::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domcdatasection::normalize' => 
  array (
    0 => 'void',
  ),
  'domcdatasection::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domcdatasection::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domcdatasection::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domcdatasection::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domcdatasection::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcdatasection::__sleep' => 
  array (
    0 => 'array',
  ),
  'domcdatasection::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::splittext' => 
  array (
    0 => 'Dom\\Text',
    'offset' => 'int',
  ),
  'dom\\cdatasection::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\cdatasection::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\cdatasection::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\cdatasection::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\cdatasection::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\cdatasection::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\cdatasection::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\cdatasection::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\cdatasection::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\cdatasection::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\cdatasection::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\cdatasection::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\cdatasection::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\cdatasection::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\cdatasection::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\cdatasection::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\cdatasection::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\cdatasection::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\cdatasection::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\cdatasection::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\cdatasection::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\cdatasection::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\cdatasection::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\cdatasection::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\cdatasection::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\cdatasection::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\cdatasection::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\cdatasection::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domdocumenttype::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domdocumenttype::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocumenttype::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domdocumenttype::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocumenttype::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocumenttype::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domdocumenttype::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domdocumenttype::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domdocumenttype::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domdocumenttype::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domdocumenttype::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocumenttype::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocumenttype::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocumenttype::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domdocumenttype::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domdocumenttype::normalize' => 
  array (
    0 => 'void',
  ),
  'domdocumenttype::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domdocumenttype::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domdocumenttype::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domdocumenttype::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domdocumenttype::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocumenttype::__sleep' => 
  array (
    0 => 'array',
  ),
  'domdocumenttype::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\documenttype::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\documenttype::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\documenttype::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\documenttype::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\documenttype::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\documenttype::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\documenttype::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\documenttype::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\documenttype::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\documenttype::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\documenttype::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\documenttype::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documenttype::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documenttype::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\documenttype::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\documenttype::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\documenttype::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\documenttype::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\documenttype::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domnotation::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domnotation::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domnotation::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domnotation::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domnotation::getlineno' => 
  array (
    0 => 'int',
  ),
  'domnotation::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domnotation::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domnotation::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domnotation::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domnotation::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domnotation::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domnotation::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domnotation::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domnotation::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domnotation::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domnotation::normalize' => 
  array (
    0 => 'void',
  ),
  'domnotation::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domnotation::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domnotation::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domnotation::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domnotation::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domnotation::__sleep' => 
  array (
    0 => 'array',
  ),
  'domnotation::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\notation::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\notation::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\notation::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\notation::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\notation::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\notation::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\notation::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\notation::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\notation::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\notation::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\notation::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\notation::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\notation::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\notation::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\notation::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\notation::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\notation::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\notation::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\notation::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\notation::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\notation::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domentity::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domentity::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domentity::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domentity::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domentity::getlineno' => 
  array (
    0 => 'int',
  ),
  'domentity::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domentity::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domentity::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domentity::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domentity::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domentity::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domentity::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domentity::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domentity::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domentity::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domentity::normalize' => 
  array (
    0 => 'void',
  ),
  'domentity::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domentity::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domentity::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domentity::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domentity::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domentity::__sleep' => 
  array (
    0 => 'array',
  ),
  'domentity::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\entity::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\entity::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\entity::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\entity::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\entity::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\entity::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\entity::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\entity::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\entity::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\entity::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\entity::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\entity::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\entity::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\entity::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entity::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entity::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\entity::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\entity::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\entity::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\entity::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\entity::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domentityreference::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'domentityreference::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domentityreference::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domentityreference::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domentityreference::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domentityreference::getlineno' => 
  array (
    0 => 'int',
  ),
  'domentityreference::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domentityreference::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domentityreference::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domentityreference::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domentityreference::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domentityreference::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domentityreference::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domentityreference::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domentityreference::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domentityreference::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domentityreference::normalize' => 
  array (
    0 => 'void',
  ),
  'domentityreference::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domentityreference::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domentityreference::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domentityreference::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domentityreference::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domentityreference::__sleep' => 
  array (
    0 => 'array',
  ),
  'domentityreference::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\entityreference::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\entityreference::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\entityreference::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\entityreference::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\entityreference::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\entityreference::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\entityreference::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\entityreference::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\entityreference::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\entityreference::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\entityreference::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\entityreference::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\entityreference::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\entityreference::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entityreference::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entityreference::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\entityreference::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\entityreference::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\entityreference::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\entityreference::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\entityreference::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domprocessinginstruction::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'string',
  ),
  'domprocessinginstruction::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domprocessinginstruction::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domprocessinginstruction::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'domprocessinginstruction::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domprocessinginstruction::getlineno' => 
  array (
    0 => 'int',
  ),
  'domprocessinginstruction::getnodepath' => 
  array (
    0 => 'string|null',
  ),
  'domprocessinginstruction::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domprocessinginstruction::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domprocessinginstruction::insertbefore' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'domprocessinginstruction::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domprocessinginstruction::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domprocessinginstruction::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domprocessinginstruction::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domprocessinginstruction::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'domprocessinginstruction::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string',
  ),
  'domprocessinginstruction::normalize' => 
  array (
    0 => 'void',
  ),
  'domprocessinginstruction::removechild' => 
  array (
    0 => 'mixed',
    'child' => 'DOMNode',
  ),
  'domprocessinginstruction::replacechild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'domprocessinginstruction::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNode|DOMNameSpaceNode|null|null',
  ),
  'domprocessinginstruction::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array|null',
  ),
  'domprocessinginstruction::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domprocessinginstruction::__sleep' => 
  array (
    0 => 'array',
  ),
  'domprocessinginstruction::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\processinginstruction::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\processinginstruction::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\processinginstruction::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\processinginstruction::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\processinginstruction::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array',
  ),
  'dom\\processinginstruction::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\processinginstruction::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\processinginstruction::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\processinginstruction::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\processinginstruction::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::contains' => 
  array (
    0 => 'bool',
    'other' => 'Dom\\Node|null',
  ),
  'dom\\processinginstruction::lookupprefix' => 
  array (
    0 => 'string|null',
    'namespace' => 'string|null',
  ),
  'dom\\processinginstruction::lookupnamespaceuri' => 
  array (
    0 => 'string|null',
    'prefix' => 'string|null',
  ),
  'dom\\processinginstruction::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string|null',
  ),
  'dom\\processinginstruction::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\processinginstruction::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\processinginstruction::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\processinginstruction::c14n' => 
  array (
    0 => 'string|false',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\processinginstruction::c14nfile' => 
  array (
    0 => 'int|false',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'dom\\processinginstruction::__sleep' => 
  array (
    0 => 'array',
  ),
  'dom\\processinginstruction::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domxpath::__construct' => 
  array (
    0 => 'void',
    'document' => 'DOMDocument',
    'registerNodeNS=' => 'bool',
  ),
  'domxpath::evaluate' => 
  array (
    0 => 'mixed',
    'expression' => 'string',
    'contextNode=' => 'DOMNode|null',
    'registerNodeNS=' => 'bool',
  ),
  'domxpath::query' => 
  array (
    0 => 'mixed',
    'expression' => 'string',
    'contextNode=' => 'DOMNode|null',
    'registerNodeNS=' => 'bool',
  ),
  'domxpath::registernamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'domxpath::registerphpfunctions' => 
  array (
    0 => 'void',
    'restrict=' => 'array|string|null|null',
  ),
  'domxpath::registerphpfunctionns' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string',
    'name' => 'string',
    'callable' => 'callable',
  ),
  'domxpath::quote' => 
  array (
    0 => 'string',
    'str' => 'string',
  ),
  'dom\\xpath::__construct' => 
  array (
    0 => 'void',
    'document' => 'Dom\\Document',
    'registerNodeNS=' => 'bool',
  ),
  'dom\\xpath::evaluate' => 
  array (
    0 => 'Dom\\NodeList|string|float|bool|null|null',
    'expression' => 'string',
    'contextNode=' => 'Dom\\Node|null',
    'registerNodeNS=' => 'bool',
  ),
  'dom\\xpath::query' => 
  array (
    0 => 'Dom\\NodeList',
    'expression' => 'string',
    'contextNode=' => 'Dom\\Node|null',
    'registerNodeNS=' => 'bool',
  ),
  'dom\\xpath::registernamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'dom\\xpath::registerphpfunctions' => 
  array (
    0 => 'void',
    'restrict=' => 'array|string|null|null',
  ),
  'dom\\xpath::registerphpfunctionns' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string',
    'name' => 'string',
    'callable' => 'callable',
  ),
  'dom\\xpath::quote' => 
  array (
    0 => 'string',
    'str' => 'string',
  ),
  'dom\\tokenlist::__construct' => 
  array (
    0 => 'void',
  ),
  'dom\\tokenlist::item' => 
  array (
    0 => 'string|null',
    'index' => 'int',
  ),
  'dom\\tokenlist::contains' => 
  array (
    0 => 'bool',
    'token' => 'string',
  ),
  'dom\\tokenlist::add' => 
  array (
    0 => 'void',
    '...tokens=' => 'string',
  ),
  'dom\\tokenlist::remove' => 
  array (
    0 => 'void',
    '...tokens=' => 'string',
  ),
  'dom\\tokenlist::toggle' => 
  array (
    0 => 'bool',
    'token' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\tokenlist::replace' => 
  array (
    0 => 'bool',
    'token' => 'string',
    'newToken' => 'string',
  ),
  'dom\\tokenlist::supports' => 
  array (
    0 => 'bool',
    'token' => 'string',
  ),
  'dom\\tokenlist::count' => 
  array (
    0 => 'int',
  ),
  'dom\\tokenlist::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'xmlreader::close' => 
  array (
    0 => 'true',
  ),
  'xmlreader::getattribute' => 
  array (
    0 => 'string|null',
    'name' => 'string',
  ),
  'xmlreader::getattributeno' => 
  array (
    0 => 'string|null',
    'index' => 'int',
  ),
  'xmlreader::getattributens' => 
  array (
    0 => 'string|null',
    'name' => 'string',
    'namespace' => 'string',
  ),
  'xmlreader::getparserproperty' => 
  array (
    0 => 'bool',
    'property' => 'int',
  ),
  'xmlreader::isvalid' => 
  array (
    0 => 'bool',
  ),
  'xmlreader::lookupnamespace' => 
  array (
    0 => 'string|null',
    'prefix' => 'string',
  ),
  'xmlreader::movetoattribute' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlreader::movetoattributeno' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'xmlreader::movetoattributens' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'namespace' => 'string',
  ),
  'xmlreader::movetoelement' => 
  array (
    0 => 'bool',
  ),
  'xmlreader::movetofirstattribute' => 
  array (
    0 => 'bool',
  ),
  'xmlreader::movetonextattribute' => 
  array (
    0 => 'bool',
  ),
  'xmlreader::read' => 
  array (
    0 => 'bool',
  ),
  'xmlreader::next' => 
  array (
    0 => 'bool',
    'name=' => 'string|null',
  ),
  'xmlreader::open' => 
  array (
    0 => 'mixed',
    'uri' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'xmlreader::fromuri' => 
  array (
    0 => 'static',
    'uri' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'xmlreader::fromstream' => 
  array (
    0 => 'static',
    'stream' => 'mixed',
    'encoding=' => 'string|null',
    'flags=' => 'int',
    'documentUri=' => 'string|null',
  ),
  'xmlreader::readinnerxml' => 
  array (
    0 => 'string',
  ),
  'xmlreader::readouterxml' => 
  array (
    0 => 'string',
  ),
  'xmlreader::readstring' => 
  array (
    0 => 'string',
  ),
  'xmlreader::setschema' => 
  array (
    0 => 'bool',
    'filename' => 'string|null',
  ),
  'xmlreader::setparserproperty' => 
  array (
    0 => 'bool',
    'property' => 'int',
    'value' => 'bool',
  ),
  'xmlreader::setrelaxngschema' => 
  array (
    0 => 'bool',
    'filename' => 'string|null',
  ),
  'xmlreader::setrelaxngschemasource' => 
  array (
    0 => 'bool',
    'source' => 'string|null',
  ),
  'xmlreader::xml' => 
  array (
    0 => 'mixed',
    'source' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'xmlreader::fromstring' => 
  array (
    0 => 'static',
    'source' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'xmlreader::expand' => 
  array (
    0 => 'DOMNode|false',
    'baseNode=' => 'DOMNode|null',
  ),
  'xmlwriter::openuri' => 
  array (
    0 => 'bool',
    'uri' => 'string',
  ),
  'xmlwriter::touri' => 
  array (
    0 => 'static',
    'uri' => 'string',
  ),
  'xmlwriter::openmemory' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::tomemory' => 
  array (
    0 => 'static',
  ),
  'xmlwriter::tostream' => 
  array (
    0 => 'static',
    'stream' => 'mixed',
  ),
  'xmlwriter::setindent' => 
  array (
    0 => 'bool',
    'enable' => 'bool',
  ),
  'xmlwriter::setindentstring' => 
  array (
    0 => 'bool',
    'indentation' => 'string',
  ),
  'xmlwriter::startcomment' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endcomment' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::startattribute' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::endattribute' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writeattribute' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value' => 'string',
  ),
  'xmlwriter::startattributens' => 
  array (
    0 => 'bool',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'xmlwriter::writeattributens' => 
  array (
    0 => 'bool',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'value' => 'string',
  ),
  'xmlwriter::startelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::endelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::fullendelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::startelementns' => 
  array (
    0 => 'bool',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'xmlwriter::writeelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content=' => 'string|null',
  ),
  'xmlwriter::writeelementns' => 
  array (
    0 => 'bool',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'content=' => 'string|null',
  ),
  'xmlwriter::startpi' => 
  array (
    0 => 'bool',
    'target' => 'string',
  ),
  'xmlwriter::endpi' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writepi' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::startcdata' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endcdata' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writecdata' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::text' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::writeraw' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::startdocument' => 
  array (
    0 => 'bool',
    'version=' => 'string|null',
    'encoding=' => 'string|null',
    'standalone=' => 'string|null',
  ),
  'xmlwriter::enddocument' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writecomment' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::startdtd' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
  ),
  'xmlwriter::enddtd' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writedtd' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'content=' => 'string|null',
  ),
  'xmlwriter::startdtdelement' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'xmlwriter::enddtdelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writedtdelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::startdtdattlist' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::enddtdattlist' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writedtdattlist' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::startdtdentity' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'isParam' => 'bool',
  ),
  'xmlwriter::enddtdentity' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::writedtdentity' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
    'isParam=' => 'bool',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'notationData=' => 'string|null',
  ),
  'xmlwriter::outputmemory' => 
  array (
    0 => 'string',
    'flush=' => 'bool',
  ),
  'xmlwriter::flush' => 
  array (
    0 => 'string|int',
    'empty=' => 'bool',
  ),
  'apcuiterator::__construct' => 
  array (
    0 => 'void',
    'search=' => 'mixed',
    'format=' => 'int',
    'chunk_size=' => 'int',
    'list=' => 'int',
  ),
  'apcuiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'apcuiterator::next' => 
  array (
    0 => 'void',
  ),
  'apcuiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'apcuiterator::key' => 
  array (
    0 => 'string|int',
  ),
  'apcuiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'apcuiterator::gettotalhits' => 
  array (
    0 => 'int',
  ),
  'apcuiterator::gettotalsize' => 
  array (
    0 => 'int',
  ),
  'apcuiterator::gettotalcount' => 
  array (
    0 => 'int',
  ),
  'bcmath\\number::__construct' => 
  array (
    0 => 'void',
    'num' => 'string|int',
  ),
  'bcmath\\number::add' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::sub' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::mul' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::div' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::mod' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::divmod' => 
  array (
    0 => 'array',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::powmod' => 
  array (
    0 => 'BcMath\\Number',
    'exponent' => 'BcMath\\Number|string|int',
    'modulus' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::pow' => 
  array (
    0 => 'BcMath\\Number',
    'exponent' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::sqrt' => 
  array (
    0 => 'BcMath\\Number',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::floor' => 
  array (
    0 => 'BcMath\\Number',
  ),
  'bcmath\\number::ceil' => 
  array (
    0 => 'BcMath\\Number',
  ),
  'bcmath\\number::round' => 
  array (
    0 => 'BcMath\\Number',
    'precision=' => 'int',
    'mode=' => 'RoundingMode',
  ),
  'bcmath\\number::compare' => 
  array (
    0 => 'int',
    'num' => 'BcMath\\Number|string|int',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::__tostring' => 
  array (
    0 => 'string',
  ),
  'bcmath\\number::__serialize' => 
  array (
    0 => 'array',
  ),
  'bcmath\\number::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'ds\\vector::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\vector::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\vector::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\vector::apply' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
  ),
  'ds\\vector::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\vector::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\vector::filter' => 
  array (
    0 => 'Ds\\Sequence',
    'callback=' => 'callable|null',
  ),
  'ds\\vector::find' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\vector::first' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::get' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'ds\\vector::insert' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    '...values=' => 'mixed',
  ),
  'ds\\vector::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'ds\\vector::last' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::map' => 
  array (
    0 => 'Ds\\Sequence',
    'callback' => 'callable',
  ),
  'ds\\vector::merge' => 
  array (
    0 => 'Ds\\Sequence',
    'values' => 'mixed',
  ),
  'ds\\vector::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\vector::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\vector::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\vector::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\vector::pop' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::push' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\vector::reduce' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'ds\\vector::remove' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'ds\\vector::reverse' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::reversed' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'ds\\vector::rotate' => 
  array (
    0 => 'mixed',
    'rotations' => 'int',
  ),
  'ds\\vector::set' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'ds\\vector::shift' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::slice' => 
  array (
    0 => 'Ds\\Sequence',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'ds\\vector::sort' => 
  array (
    0 => 'mixed',
    'comparator=' => 'callable|null',
  ),
  'ds\\vector::sorted' => 
  array (
    0 => 'Ds\\Sequence',
    'comparator=' => 'callable|null',
  ),
  'ds\\vector::sum' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::unshift' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\vector::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\vector::count' => 
  array (
    0 => 'int',
  ),
  'ds\\vector::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\vector::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\deque::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\deque::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\deque::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\deque::count' => 
  array (
    0 => 'int',
  ),
  'ds\\deque::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\deque::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\deque::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\deque::apply' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
  ),
  'ds\\deque::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\deque::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\deque::filter' => 
  array (
    0 => 'Ds\\Sequence',
    'callback=' => 'callable|null',
  ),
  'ds\\deque::find' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\deque::first' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::get' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'ds\\deque::insert' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    '...values=' => 'mixed',
  ),
  'ds\\deque::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'ds\\deque::last' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::map' => 
  array (
    0 => 'Ds\\Sequence',
    'callback' => 'callable',
  ),
  'ds\\deque::merge' => 
  array (
    0 => 'Ds\\Sequence',
    'values' => 'mixed',
  ),
  'ds\\deque::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\deque::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\deque::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\deque::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\deque::pop' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::push' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\deque::reduce' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'ds\\deque::remove' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'ds\\deque::reverse' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::reversed' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'ds\\deque::rotate' => 
  array (
    0 => 'mixed',
    'rotations' => 'int',
  ),
  'ds\\deque::set' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'ds\\deque::shift' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::slice' => 
  array (
    0 => 'Ds\\Sequence',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'ds\\deque::sort' => 
  array (
    0 => 'mixed',
    'comparator=' => 'callable|null',
  ),
  'ds\\deque::sorted' => 
  array (
    0 => 'Ds\\Sequence',
    'comparator=' => 'callable|null',
  ),
  'ds\\deque::sum' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::unshift' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\stack::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\stack::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\stack::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\stack::peek' => 
  array (
    0 => 'mixed',
  ),
  'ds\\stack::pop' => 
  array (
    0 => 'mixed',
  ),
  'ds\\stack::push' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\stack::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\stack::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\stack::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\stack::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\stack::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\stack::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\stack::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\stack::count' => 
  array (
    0 => 'int',
  ),
  'ds\\stack::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\stack::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\stack::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\queue::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\queue::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\queue::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\queue::peek' => 
  array (
    0 => 'mixed',
  ),
  'ds\\queue::pop' => 
  array (
    0 => 'mixed',
  ),
  'ds\\queue::push' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\queue::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\queue::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\queue::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\queue::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\queue::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\queue::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\queue::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\queue::count' => 
  array (
    0 => 'int',
  ),
  'ds\\queue::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\queue::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\queue::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\map::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\map::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\map::apply' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
  ),
  'ds\\map::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\map::diff' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'ds\\map::filter' => 
  array (
    0 => 'Ds\\Map',
    'callback=' => 'callable|null',
  ),
  'ds\\map::first' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'ds\\map::get' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
    'default=' => 'mixed',
  ),
  'ds\\map::haskey' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
  ),
  'ds\\map::hasvalue' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'ds\\map::intersect' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'ds\\map::keys' => 
  array (
    0 => 'Ds\\Set',
  ),
  'ds\\map::ksort' => 
  array (
    0 => 'mixed',
    'comparator=' => 'callable|null',
  ),
  'ds\\map::ksorted' => 
  array (
    0 => 'Ds\\Map',
    'comparator=' => 'callable|null',
  ),
  'ds\\map::last' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'ds\\map::map' => 
  array (
    0 => 'Ds\\Map',
    'callback' => 'callable',
  ),
  'ds\\map::merge' => 
  array (
    0 => 'Ds\\Map',
    'values' => 'mixed',
  ),
  'ds\\map::pairs' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'ds\\map::put' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\map::putall' => 
  array (
    0 => 'mixed',
    'values' => 'mixed',
  ),
  'ds\\map::reduce' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'ds\\map::remove' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
    'default=' => 'mixed',
  ),
  'ds\\map::reverse' => 
  array (
    0 => 'mixed',
  ),
  'ds\\map::reversed' => 
  array (
    0 => 'Ds\\Map',
  ),
  'ds\\map::skip' => 
  array (
    0 => 'Ds\\Pair',
    'position' => 'int',
  ),
  'ds\\map::slice' => 
  array (
    0 => 'Ds\\Map',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'ds\\map::sort' => 
  array (
    0 => 'mixed',
    'comparator=' => 'callable|null',
  ),
  'ds\\map::sorted' => 
  array (
    0 => 'Ds\\Map',
    'comparator=' => 'callable|null',
  ),
  'ds\\map::sum' => 
  array (
    0 => 'mixed',
  ),
  'ds\\map::union' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'mixed',
  ),
  'ds\\map::values' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'ds\\map::xor' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'ds\\map::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\map::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\map::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\map::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\map::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\map::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\map::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\map::count' => 
  array (
    0 => 'int',
  ),
  'ds\\map::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\map::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\map::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\set::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
  ),
  'ds\\set::add' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\set::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\set::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\set::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\set::diff' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'ds\\set::filter' => 
  array (
    0 => 'Ds\\Set',
    'predicate=' => 'callable|null',
  ),
  'ds\\set::first' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::get' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'ds\\set::intersect' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'ds\\set::join' => 
  array (
    0 => 'mixed',
    'glue=' => 'string',
  ),
  'ds\\set::last' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::map' => 
  array (
    0 => 'Ds\\Set',
    'callback' => 'callable',
  ),
  'ds\\set::merge' => 
  array (
    0 => 'Ds\\Set',
    'values' => 'mixed',
  ),
  'ds\\set::reduce' => 
  array (
    0 => 'mixed',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'ds\\set::remove' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'ds\\set::reverse' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::reversed' => 
  array (
    0 => 'Ds\\Set',
  ),
  'ds\\set::slice' => 
  array (
    0 => 'Ds\\Set',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'ds\\set::sort' => 
  array (
    0 => 'mixed',
    'comparator=' => 'callable|null',
  ),
  'ds\\set::sorted' => 
  array (
    0 => 'Ds\\Set',
    'comparator=' => 'callable|null',
  ),
  'ds\\set::sum' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::union' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'ds\\set::xor' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'ds\\set::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\set::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'ds\\set::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'ds\\set::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'ds\\set::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'ds\\set::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\set::count' => 
  array (
    0 => 'int',
  ),
  'ds\\set::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\set::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\priorityqueue::__construct' => 
  array (
    0 => 'void',
  ),
  'ds\\priorityqueue::allocate' => 
  array (
    0 => 'mixed',
    'capacity' => 'int',
  ),
  'ds\\priorityqueue::capacity' => 
  array (
    0 => 'int',
  ),
  'ds\\priorityqueue::peek' => 
  array (
    0 => 'mixed',
  ),
  'ds\\priorityqueue::pop' => 
  array (
    0 => 'mixed',
  ),
  'ds\\priorityqueue::push' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    'priority' => 'mixed',
  ),
  'ds\\priorityqueue::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\priorityqueue::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\priorityqueue::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\priorityqueue::count' => 
  array (
    0 => 'int',
  ),
  'ds\\priorityqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\priorityqueue::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\priorityqueue::toarray' => 
  array (
    0 => 'array',
  ),
  'ds\\pair::__construct' => 
  array (
    0 => 'void',
    'key=' => 'mixed',
    'value=' => 'mixed',
  ),
  'ds\\pair::copy' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'ds\\pair::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'ds\\pair::toarray' => 
  array (
    0 => 'array',
  ),
  'ev::supportedbackends' => 
  array (
    0 => 'int',
  ),
  'ev::recommendedbackends' => 
  array (
    0 => 'int',
  ),
  'ev::embeddablebackends' => 
  array (
    0 => 'int',
  ),
  'ev::sleep' => 
  array (
    0 => 'void',
    'seconds' => 'float',
  ),
  'ev::time' => 
  array (
    0 => 'float',
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
  'ev::run' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'ev::now' => 
  array (
    0 => 'float',
  ),
  'ev::stop' => 
  array (
    0 => 'void',
    'how=' => 'int',
  ),
  'ev::iteration' => 
  array (
    0 => 'int',
  ),
  'ev::depth' => 
  array (
    0 => 'int',
  ),
  'ev::backend' => 
  array (
    0 => 'int',
  ),
  'ev::nowupdate' => 
  array (
    0 => 'void',
  ),
  'ev::suspend' => 
  array (
    0 => 'void',
  ),
  'ev::resume' => 
  array (
    0 => 'void',
  ),
  'ev::verify' => 
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
  'evloop::defaultloop' => 
  array (
    0 => 'EvLoop',
    'flags=' => 'int',
    'data=' => 'mixed',
    'io_interval=' => 'float',
    'timeout_interval=' => 'float',
  ),
  'evloop::loopfork' => 
  array (
    0 => 'void',
  ),
  'evloop::verify' => 
  array (
    0 => 'void',
  ),
  'evloop::invokepending' => 
  array (
    0 => 'void',
  ),
  'evloop::nowupdate' => 
  array (
    0 => 'void',
  ),
  'evloop::suspend' => 
  array (
    0 => 'void',
  ),
  'evloop::resume' => 
  array (
    0 => 'void',
  ),
  'evloop::backend' => 
  array (
    0 => 'int',
  ),
  'evloop::now' => 
  array (
    0 => 'float',
  ),
  'evloop::run' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'evloop::stop' => 
  array (
    0 => 'void',
    'how=' => 'int',
  ),
  'evloop::io' => 
  array (
    0 => 'EvIo',
    'fd' => 'mixed',
    'events' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::timer' => 
  array (
    0 => 'EvTimer',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::periodic' => 
  array (
    0 => 'EvPeriodic',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::signal' => 
  array (
    0 => 'EvSignal',
    'signum' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::child' => 
  array (
    0 => 'EvChild',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::stat' => 
  array (
    0 => 'EvStat',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::idle' => 
  array (
    0 => 'EvIdle',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::check' => 
  array (
    0 => 'EvCheck',
  ),
  'evloop::prepare' => 
  array (
    0 => 'EvPrepare',
  ),
  'evloop::embed' => 
  array (
    0 => 'EvEmbed',
  ),
  'evloop::fork' => 
  array (
    0 => 'EvFork',
  ),
  'evwatcher::start' => 
  array (
    0 => 'void',
  ),
  'evwatcher::stop' => 
  array (
    0 => 'void',
  ),
  'evwatcher::clear' => 
  array (
    0 => 'int',
  ),
  'evwatcher::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evwatcher::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evwatcher::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evio::__construct' => 
  array (
    0 => 'void',
    'fd' => 'mixed',
    'events' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evio::createstopped' => 
  array (
    0 => 'EvIo',
    'fd' => 'mixed',
    'events' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evio::set' => 
  array (
    0 => 'void',
    'fd' => 'mixed',
    'events' => 'int',
  ),
  'evio::start' => 
  array (
    0 => 'void',
  ),
  'evio::stop' => 
  array (
    0 => 'void',
  ),
  'evio::clear' => 
  array (
    0 => 'int',
  ),
  'evio::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evio::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evio::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evtimer::__construct' => 
  array (
    0 => 'void',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evtimer::createstopped' => 
  array (
    0 => 'EvTimer',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evtimer::set' => 
  array (
    0 => 'void',
    'after' => 'float',
    'repeat' => 'float',
  ),
  'evtimer::again' => 
  array (
    0 => 'void',
  ),
  'evtimer::start' => 
  array (
    0 => 'void',
  ),
  'evtimer::stop' => 
  array (
    0 => 'void',
  ),
  'evtimer::clear' => 
  array (
    0 => 'int',
  ),
  'evtimer::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evtimer::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evtimer::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evperiodic::__construct' => 
  array (
    0 => 'void',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evperiodic::createstopped' => 
  array (
    0 => 'EvPeriodic',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evperiodic::set' => 
  array (
    0 => 'void',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb=' => 'mixed',
  ),
  'evperiodic::again' => 
  array (
    0 => 'void',
  ),
  'evperiodic::at' => 
  array (
    0 => 'float',
  ),
  'evperiodic::start' => 
  array (
    0 => 'void',
  ),
  'evperiodic::stop' => 
  array (
    0 => 'void',
  ),
  'evperiodic::clear' => 
  array (
    0 => 'int',
  ),
  'evperiodic::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evperiodic::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evperiodic::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evsignal::__construct' => 
  array (
    0 => 'void',
    'signum' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evsignal::createstopped' => 
  array (
    0 => 'EvSignal',
    'signum' => 'int',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evsignal::set' => 
  array (
    0 => 'void',
    'signum' => 'int',
  ),
  'evsignal::start' => 
  array (
    0 => 'void',
  ),
  'evsignal::stop' => 
  array (
    0 => 'void',
  ),
  'evsignal::clear' => 
  array (
    0 => 'int',
  ),
  'evsignal::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evsignal::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evsignal::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evchild::__construct' => 
  array (
    0 => 'void',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evchild::createstopped' => 
  array (
    0 => 'EvChild',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evchild::set' => 
  array (
    0 => 'void',
    'pid' => 'int',
    'trace' => 'bool',
  ),
  'evchild::start' => 
  array (
    0 => 'void',
  ),
  'evchild::stop' => 
  array (
    0 => 'void',
  ),
  'evchild::clear' => 
  array (
    0 => 'int',
  ),
  'evchild::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evchild::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evchild::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evstat::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evstat::createstopped' => 
  array (
    0 => 'EvStat',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evstat::set' => 
  array (
    0 => 'void',
    'path' => 'string',
    'interval' => 'float',
  ),
  'evstat::attr' => 
  array (
    0 => 'mixed',
  ),
  'evstat::prev' => 
  array (
    0 => 'mixed',
  ),
  'evstat::stat' => 
  array (
    0 => 'bool',
  ),
  'evstat::start' => 
  array (
    0 => 'void',
  ),
  'evstat::stop' => 
  array (
    0 => 'void',
  ),
  'evstat::clear' => 
  array (
    0 => 'int',
  ),
  'evstat::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evstat::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evstat::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evidle::__construct' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evidle::createstopped' => 
  array (
    0 => 'EvIdle',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evidle::start' => 
  array (
    0 => 'void',
  ),
  'evidle::stop' => 
  array (
    0 => 'void',
  ),
  'evidle::clear' => 
  array (
    0 => 'int',
  ),
  'evidle::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evidle::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evidle::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evcheck::__construct' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evcheck::createstopped' => 
  array (
    0 => 'EvCheck',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evcheck::start' => 
  array (
    0 => 'void',
  ),
  'evcheck::stop' => 
  array (
    0 => 'void',
  ),
  'evcheck::clear' => 
  array (
    0 => 'int',
  ),
  'evcheck::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evcheck::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evcheck::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evprepare::__construct' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evprepare::createstopped' => 
  array (
    0 => 'EvPrepare',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evprepare::start' => 
  array (
    0 => 'void',
  ),
  'evprepare::stop' => 
  array (
    0 => 'void',
  ),
  'evprepare::clear' => 
  array (
    0 => 'int',
  ),
  'evprepare::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evprepare::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evprepare::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evembed::__construct' => 
  array (
    0 => 'void',
    'other' => 'EvLoop',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evembed::createstopped' => 
  array (
    0 => 'EvEmbed',
    'other' => 'EvLoop',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evembed::set' => 
  array (
    0 => 'void',
    'other' => 'EvLoop',
  ),
  'evembed::sweep' => 
  array (
    0 => 'void',
  ),
  'evembed::start' => 
  array (
    0 => 'void',
  ),
  'evembed::stop' => 
  array (
    0 => 'void',
  ),
  'evembed::clear' => 
  array (
    0 => 'int',
  ),
  'evembed::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evembed::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evembed::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'evfork::__construct' => 
  array (
    0 => 'void',
    'loop' => 'EvLoop',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evfork::createstopped' => 
  array (
    0 => 'EvFork',
    'loop' => 'EvLoop',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evfork::start' => 
  array (
    0 => 'void',
  ),
  'evfork::stop' => 
  array (
    0 => 'void',
  ),
  'evfork::clear' => 
  array (
    0 => 'int',
  ),
  'evfork::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
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
  'evfork::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'evfork::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
  ),
  'ffi\\exception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ffi\\exception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'ffi\\exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'ffi\\exception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'ffi\\exception::getfile' => 
  array (
    0 => 'string',
  ),
  'ffi\\exception::getline' => 
  array (
    0 => 'int',
  ),
  'ffi\\exception::gettrace' => 
  array (
    0 => 'array',
  ),
  'ffi\\exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ffi\\exception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'ffi\\exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ffi\\parserexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'ffi\\parserexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'ffi\\parserexception::getfile' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::getline' => 
  array (
    0 => 'int',
  ),
  'ffi\\parserexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'ffi\\parserexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ffi\\parserexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'ffi::cdef' => 
  array (
    0 => 'FFI',
    'code=' => 'string',
    'lib=' => 'string|null',
  ),
  'ffi::load' => 
  array (
    0 => 'FFI|null',
    'filename' => 'string',
  ),
  'ffi::scope' => 
  array (
    0 => 'FFI',
    'name' => 'string',
  ),
  'ffi::new' => 
  array (
    0 => 'FFI\\CData',
    'type' => 'FFI\\CType|string',
    'owned=' => 'bool',
    'persistent=' => 'bool',
  ),
  'ffi::free' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::cast' => 
  array (
    0 => 'FFI\\CData',
    'type' => 'FFI\\CType|string',
    '&ptr' => 'mixed',
  ),
  'ffi::type' => 
  array (
    0 => 'FFI\\CType',
    'type' => 'string',
  ),
  'ffi::typeof' => 
  array (
    0 => 'FFI\\CType',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::arraytype' => 
  array (
    0 => 'FFI\\CType',
    'type' => 'FFI\\CType',
    'dimensions' => 'array',
  ),
  'ffi::addr' => 
  array (
    0 => 'FFI\\CData',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::sizeof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'ffi::alignof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'ffi::memcpy' => 
  array (
    0 => 'void',
    '&to' => 'FFI\\CData',
    '&from' => 'mixed',
    'size' => 'int',
  ),
  'ffi::memcmp' => 
  array (
    0 => 'int',
    '&ptr1' => 'mixed',
    '&ptr2' => 'mixed',
    'size' => 'int',
  ),
  'ffi::memset' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
    'value' => 'int',
    'size' => 'int',
  ),
  'ffi::string' => 
  array (
    0 => 'string',
    '&ptr' => 'FFI\\CData',
    'size=' => 'int|null',
  ),
  'ffi::isnull' => 
  array (
    0 => 'bool',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi\\ctype::getname' => 
  array (
    0 => 'string',
  ),
  'ffi\\ctype::getkind' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getsize' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getalignment' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getattributes' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getenumkind' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getarrayelementtype' => 
  array (
    0 => 'FFI\\CType',
  ),
  'ffi\\ctype::getarraylength' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getpointertype' => 
  array (
    0 => 'FFI\\CType',
  ),
  'ffi\\ctype::getstructfieldnames' => 
  array (
    0 => 'array',
  ),
  'ffi\\ctype::getstructfieldoffset' => 
  array (
    0 => 'int',
    'name' => 'string',
  ),
  'ffi\\ctype::getstructfieldtype' => 
  array (
    0 => 'FFI\\CType',
    'name' => 'string',
  ),
  'ffi\\ctype::getfuncabi' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getfuncreturntype' => 
  array (
    0 => 'FFI\\CType',
  ),
  'ffi\\ctype::getfuncparametercount' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getfuncparametertype' => 
  array (
    0 => 'FFI\\CType',
    'index' => 'int',
  ),
  'gmp::__construct' => 
  array (
    0 => 'void',
    'num=' => 'string|int',
    'base=' => 'int',
  ),
  'gmp::__serialize' => 
  array (
    0 => 'array',
  ),
  'gmp::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'collator::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
  ),
  'collator::create' => 
  array (
    0 => 'Collator|null',
    'locale' => 'string',
  ),
  'collator::compare' => 
  array (
    0 => 'int|false',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'collator::sort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'collator::sortwithsortkeys' => 
  array (
    0 => 'bool',
    '&array' => 'array',
  ),
  'collator::asort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'collator::getattribute' => 
  array (
    0 => 'int|false',
    'attribute' => 'int',
  ),
  'collator::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'int',
  ),
  'collator::getstrength' => 
  array (
    0 => 'int',
  ),
  'collator::setstrength' => 
  array (
    0 => 'true',
    'strength' => 'int',
  ),
  'collator::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'collator::geterrorcode' => 
  array (
    0 => 'int|false',
  ),
  'collator::geterrormessage' => 
  array (
    0 => 'string|false',
  ),
  'collator::getsortkey' => 
  array (
    0 => 'string|false',
    'string' => 'string',
  ),
  'numberformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'string|null',
  ),
  'numberformatter::create' => 
  array (
    0 => 'NumberFormatter|null',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'string|null',
  ),
  'numberformatter::format' => 
  array (
    0 => 'string|false',
    'num' => 'int|float',
    'type=' => 'int',
  ),
  'numberformatter::parse' => 
  array (
    0 => 'int|float|false',
    'string' => 'string',
    'type=' => 'int',
    '&offset=' => 'mixed',
  ),
  'numberformatter::formatcurrency' => 
  array (
    0 => 'string|false',
    'amount' => 'float',
    'currency' => 'string',
  ),
  'numberformatter::parsecurrency' => 
  array (
    0 => 'float|false',
    'string' => 'string',
    '&currency' => 'mixed',
    '&offset=' => 'mixed',
  ),
  'numberformatter::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'int|float',
  ),
  'numberformatter::getattribute' => 
  array (
    0 => 'int|float|false',
    'attribute' => 'int',
  ),
  'numberformatter::settextattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'string',
  ),
  'numberformatter::gettextattribute' => 
  array (
    0 => 'string|false',
    'attribute' => 'int',
  ),
  'numberformatter::setsymbol' => 
  array (
    0 => 'bool',
    'symbol' => 'int',
    'value' => 'string',
  ),
  'numberformatter::getsymbol' => 
  array (
    0 => 'string|false',
    'symbol' => 'int',
  ),
  'numberformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'numberformatter::getpattern' => 
  array (
    0 => 'string|false',
  ),
  'numberformatter::getlocale' => 
  array (
    0 => 'string|false',
    'type=' => 'int',
  ),
  'numberformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'numberformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intllistformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
    'type=' => 'int',
    'width=' => 'int',
  ),
  'intllistformatter::format' => 
  array (
    0 => 'string|false',
    'strings' => 'array',
  ),
  'intllistformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intllistformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'normalizer::normalize' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer::isnormalized' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer::getrawdecomposition' => 
  array (
    0 => 'string|null',
    'string' => 'string',
    'form=' => 'int',
  ),
  'locale::getdefault' => 
  array (
    0 => 'string',
  ),
  'locale::setdefault' => 
  array (
    0 => 'true',
    'locale' => 'string',
  ),
  'locale::getprimarylanguage' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale::getscript' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale::getregion' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale::getkeywords' => 
  array (
    0 => 'array|false|null|null',
    'locale' => 'string',
  ),
  'locale::getdisplayscript' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale::getdisplayregion' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale::getdisplayname' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale::getdisplaylanguage' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale::getdisplayvariant' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'locale::composelocale' => 
  array (
    0 => 'string|false',
    'subtags' => 'array',
  ),
  'locale::parselocale' => 
  array (
    0 => 'array|null',
    'locale' => 'string',
  ),
  'locale::getallvariants' => 
  array (
    0 => 'array|null',
    'locale' => 'string',
  ),
  'locale::filtermatches' => 
  array (
    0 => 'bool|null',
    'languageTag' => 'string',
    'locale' => 'string',
    'canonicalize=' => 'bool',
  ),
  'locale::lookup' => 
  array (
    0 => 'string|null',
    'languageTag' => 'array',
    'locale' => 'string',
    'canonicalize=' => 'bool',
    'defaultLocale=' => 'string|null',
  ),
  'locale::canonicalize' => 
  array (
    0 => 'string|null',
    'locale' => 'string',
  ),
  'locale::acceptfromhttp' => 
  array (
    0 => 'string|false',
    'header' => 'string',
  ),
  'locale::isrighttoleft' => 
  array (
    0 => 'bool',
    'locale' => 'string',
  ),
  'locale::addlikelysubtags' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
  ),
  'locale::minimizesubtags' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
  ),
  'messageformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'messageformatter::create' => 
  array (
    0 => 'MessageFormatter|null',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'messageformatter::format' => 
  array (
    0 => 'string|false',
    'values' => 'array',
  ),
  'messageformatter::formatmessage' => 
  array (
    0 => 'string|false',
    'locale' => 'string',
    'pattern' => 'string',
    'values' => 'array',
  ),
  'messageformatter::parse' => 
  array (
    0 => 'array|false',
    'string' => 'string',
  ),
  'messageformatter::parsemessage' => 
  array (
    0 => 'array|false',
    'locale' => 'string',
    'pattern' => 'string',
    'message' => 'string',
  ),
  'messageformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'messageformatter::getpattern' => 
  array (
    0 => 'string|false',
  ),
  'messageformatter::getlocale' => 
  array (
    0 => 'string',
  ),
  'messageformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'messageformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intldateformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string|null',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'calendar=' => 'mixed',
    'pattern=' => 'string|null',
  ),
  'intldateformatter::create' => 
  array (
    0 => 'IntlDateFormatter|null',
    'locale' => 'string|null',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'calendar=' => 'IntlCalendar|int|null|null',
    'pattern=' => 'string|null',
  ),
  'intldateformatter::getdatetype' => 
  array (
    0 => 'int|false',
  ),
  'intldateformatter::gettimetype' => 
  array (
    0 => 'int|false',
  ),
  'intldateformatter::getcalendar' => 
  array (
    0 => 'int|false',
  ),
  'intldateformatter::setcalendar' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar|int|null|null',
  ),
  'intldateformatter::gettimezoneid' => 
  array (
    0 => 'string|false',
  ),
  'intldateformatter::getcalendarobject' => 
  array (
    0 => 'IntlCalendar|false|null|null',
  ),
  'intldateformatter::gettimezone' => 
  array (
    0 => 'IntlTimeZone|false',
  ),
  'intldateformatter::settimezone' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone|DateTimeZone|string|null|null',
  ),
  'intldateformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'intldateformatter::getpattern' => 
  array (
    0 => 'string|false',
  ),
  'intldateformatter::getlocale' => 
  array (
    0 => 'string|false',
    'type=' => 'int',
  ),
  'intldateformatter::setlenient' => 
  array (
    0 => 'void',
    'lenient' => 'bool',
  ),
  'intldateformatter::islenient' => 
  array (
    0 => 'bool',
  ),
  'intldateformatter::format' => 
  array (
    0 => 'string|false',
    'datetime' => 'mixed',
  ),
  'intldateformatter::formatobject' => 
  array (
    0 => 'string|false',
    'datetime' => 'mixed',
    'format=' => 'mixed',
    'locale=' => 'string|null',
  ),
  'intldateformatter::parse' => 
  array (
    0 => 'int|float|false',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::parsetocalendar' => 
  array (
    0 => 'int|float|false',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::localtime' => 
  array (
    0 => 'array|false',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intldateformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intldatepatterngenerator::__construct' => 
  array (
    0 => 'void',
    'locale=' => 'string|null',
  ),
  'intldatepatterngenerator::create' => 
  array (
    0 => 'IntlDatePatternGenerator|null',
    'locale=' => 'string|null',
  ),
  'intldatepatterngenerator::getbestpattern' => 
  array (
    0 => 'string|false',
    'skeleton' => 'string',
  ),
  'resourcebundle::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string|null',
    'bundle' => 'string|null',
    'fallback=' => 'bool',
  ),
  'resourcebundle::create' => 
  array (
    0 => 'ResourceBundle|null',
    'locale' => 'string|null',
    'bundle' => 'string|null',
    'fallback=' => 'bool',
  ),
  'resourcebundle::get' => 
  array (
    0 => 'ResourceBundle|array|string|int|null|null',
    'index' => 'string|int',
    'fallback=' => 'bool',
  ),
  'resourcebundle::count' => 
  array (
    0 => 'int',
  ),
  'resourcebundle::getlocales' => 
  array (
    0 => 'array|false',
    'bundle' => 'string',
  ),
  'resourcebundle::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'resourcebundle::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'resourcebundle::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'transliterator::__construct' => 
  array (
    0 => 'void',
  ),
  'transliterator::create' => 
  array (
    0 => 'Transliterator|null',
    'id' => 'string',
    'direction=' => 'int',
  ),
  'transliterator::createfromrules' => 
  array (
    0 => 'Transliterator|null',
    'rules' => 'string',
    'direction=' => 'int',
  ),
  'transliterator::createinverse' => 
  array (
    0 => 'Transliterator|null',
  ),
  'transliterator::listids' => 
  array (
    0 => 'array|false',
  ),
  'transliterator::transliterate' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'transliterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'transliterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intltimezone::__construct' => 
  array (
    0 => 'void',
  ),
  'intltimezone::countequivalentids' => 
  array (
    0 => 'int|false',
    'timezoneId' => 'string',
  ),
  'intltimezone::createdefault' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::createenumeration' => 
  array (
    0 => 'IntlIterator|false',
    'countryOrRawOffset=' => 'string|int|null|null',
  ),
  'intltimezone::createtimezone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezoneId' => 'string',
  ),
  'intltimezone::createtimezoneidenumeration' => 
  array (
    0 => 'IntlIterator|false',
    'type' => 'int',
    'region=' => 'string|null',
    'rawOffset=' => 'int|null',
  ),
  'intltimezone::fromdatetimezone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezone' => 'DateTimeZone',
  ),
  'intltimezone::getcanonicalid' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    '&isSystemId=' => 'mixed',
  ),
  'intltimezone::getdisplayname' => 
  array (
    0 => 'string|false',
    'dst=' => 'bool',
    'style=' => 'int',
    'locale=' => 'string|null',
  ),
  'intltimezone::getdstsavings' => 
  array (
    0 => 'int',
  ),
  'intltimezone::getequivalentid' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    'offset' => 'int',
  ),
  'intltimezone::geterrorcode' => 
  array (
    0 => 'int|false',
  ),
  'intltimezone::geterrormessage' => 
  array (
    0 => 'string|false',
  ),
  'intltimezone::getgmt' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::getianaid' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'intltimezone::getid' => 
  array (
    0 => 'string|false',
  ),
  'intltimezone::getoffset' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
    'local' => 'bool',
    '&rawOffset' => 'mixed',
    '&dstOffset' => 'mixed',
  ),
  'intltimezone::getrawoffset' => 
  array (
    0 => 'int',
  ),
  'intltimezone::getregion' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'intltimezone::gettzdataversion' => 
  array (
    0 => 'string|false',
  ),
  'intltimezone::getunknown' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::getwindowsid' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
  ),
  'intltimezone::getidforwindowsid' => 
  array (
    0 => 'string|false',
    'timezoneId' => 'string',
    'region=' => 'string|null',
  ),
  'intltimezone::hassamerules' => 
  array (
    0 => 'bool',
    'other' => 'IntlTimeZone',
  ),
  'intltimezone::todatetimezone' => 
  array (
    0 => 'DateTimeZone|false',
  ),
  'intltimezone::usedaylighttime' => 
  array (
    0 => 'bool',
  ),
  'intlcalendar::__construct' => 
  array (
    0 => 'void',
  ),
  'intlcalendar::createinstance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'locale=' => 'string|null',
  ),
  'intlcalendar::equals' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlcalendar::fielddifference' => 
  array (
    0 => 'int|false',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlcalendar::add' => 
  array (
    0 => 'bool',
    'field' => 'int',
    'value' => 'int',
  ),
  'intlcalendar::after' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlcalendar::before' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlcalendar::clear' => 
  array (
    0 => 'true',
    'field=' => 'int|null',
  ),
  'intlcalendar::fromdatetime' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'string|null',
  ),
  'intlcalendar::get' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getactualmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getactualminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getavailablelocales' => 
  array (
    0 => 'array',
  ),
  'intlcalendar::getdayofweektype' => 
  array (
    0 => 'int|false',
    'dayOfWeek' => 'int',
  ),
  'intlcalendar::geterrorcode' => 
  array (
    0 => 'int|false',
  ),
  'intlcalendar::geterrormessage' => 
  array (
    0 => 'string|false',
  ),
  'intlcalendar::getfirstdayofweek' => 
  array (
    0 => 'int|false',
  ),
  'intlcalendar::getgreatestminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getkeywordvaluesforlocale' => 
  array (
    0 => 'IntlIterator|false',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'intlcalendar::getleastmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'intlcalendar::getmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getminimaldaysinfirstweek' => 
  array (
    0 => 'int|false',
  ),
  'intlcalendar::setminimaldaysinfirstweek' => 
  array (
    0 => 'true',
    'days' => 'int',
  ),
  'intlcalendar::getminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlcalendar::getnow' => 
  array (
    0 => 'float',
  ),
  'intlcalendar::getrepeatedwalltimeoption' => 
  array (
    0 => 'int',
  ),
  'intlcalendar::getskippedwalltimeoption' => 
  array (
    0 => 'int',
  ),
  'intlcalendar::gettime' => 
  array (
    0 => 'float|false',
  ),
  'intlcalendar::gettimezone' => 
  array (
    0 => 'IntlTimeZone|false',
  ),
  'intlcalendar::gettype' => 
  array (
    0 => 'string',
  ),
  'intlcalendar::getweekendtransition' => 
  array (
    0 => 'int|false',
    'dayOfWeek' => 'int',
  ),
  'intlcalendar::indaylighttime' => 
  array (
    0 => 'bool',
  ),
  'intlcalendar::isequivalentto' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlcalendar::islenient' => 
  array (
    0 => 'bool',
  ),
  'intlcalendar::isweekend' => 
  array (
    0 => 'bool',
    'timestamp=' => 'float|null',
  ),
  'intlcalendar::roll' => 
  array (
    0 => 'bool',
    'field' => 'int',
    'value' => 'mixed',
  ),
  'intlcalendar::isset' => 
  array (
    0 => 'bool',
    'field' => 'int',
  ),
  'intlcalendar::set' => 
  array (
    0 => 'true',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth=' => 'int',
    'hour=' => 'int',
    'minute=' => 'int',
    'second=' => 'int',
  ),
  'intlcalendar::setdate' => 
  array (
    0 => 'void',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
  ),
  'intlcalendar::setdatetime' => 
  array (
    0 => 'void',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int|null',
  ),
  'intlcalendar::setfirstdayofweek' => 
  array (
    0 => 'true',
    'dayOfWeek' => 'int',
  ),
  'intlcalendar::setlenient' => 
  array (
    0 => 'true',
    'lenient' => 'bool',
  ),
  'intlcalendar::setrepeatedwalltimeoption' => 
  array (
    0 => 'true',
    'option' => 'int',
  ),
  'intlcalendar::setskippedwalltimeoption' => 
  array (
    0 => 'true',
    'option' => 'int',
  ),
  'intlcalendar::settime' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'intlcalendar::settimezone' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone|DateTimeZone|string|null|null',
  ),
  'intlcalendar::todatetime' => 
  array (
    0 => 'DateTime|false',
  ),
  'intlgregoriancalendar::createfromdate' => 
  array (
    0 => 'static',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
  ),
  'intlgregoriancalendar::createfromdatetime' => 
  array (
    0 => 'static',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int|null',
  ),
  'intlgregoriancalendar::__construct' => 
  array (
    0 => 'void',
    'timezoneOrYear=' => 'mixed',
    'localeOrMonth=' => 'mixed',
    'day=' => 'mixed',
    'hour=' => 'mixed',
    'minute=' => 'mixed',
    'second=' => 'mixed',
  ),
  'intlgregoriancalendar::setgregorianchange' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'intlgregoriancalendar::getgregorianchange' => 
  array (
    0 => 'float',
  ),
  'intlgregoriancalendar::isleapyear' => 
  array (
    0 => 'bool',
    'year' => 'int',
  ),
  'intlgregoriancalendar::createinstance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'IntlTimeZone|DateTimeZone|string|null|null',
    'locale=' => 'string|null',
  ),
  'intlgregoriancalendar::equals' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlgregoriancalendar::fielddifference' => 
  array (
    0 => 'int|false',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlgregoriancalendar::add' => 
  array (
    0 => 'bool',
    'field' => 'int',
    'value' => 'int',
  ),
  'intlgregoriancalendar::after' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlgregoriancalendar::before' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlgregoriancalendar::clear' => 
  array (
    0 => 'true',
    'field=' => 'int|null',
  ),
  'intlgregoriancalendar::fromdatetime' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'string|null',
  ),
  'intlgregoriancalendar::get' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getactualmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getactualminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getavailablelocales' => 
  array (
    0 => 'array',
  ),
  'intlgregoriancalendar::getdayofweektype' => 
  array (
    0 => 'int|false',
    'dayOfWeek' => 'int',
  ),
  'intlgregoriancalendar::geterrorcode' => 
  array (
    0 => 'int|false',
  ),
  'intlgregoriancalendar::geterrormessage' => 
  array (
    0 => 'string|false',
  ),
  'intlgregoriancalendar::getfirstdayofweek' => 
  array (
    0 => 'int|false',
  ),
  'intlgregoriancalendar::getgreatestminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getkeywordvaluesforlocale' => 
  array (
    0 => 'IntlIterator|false',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'intlgregoriancalendar::getleastmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'intlgregoriancalendar::getmaximum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getminimaldaysinfirstweek' => 
  array (
    0 => 'int|false',
  ),
  'intlgregoriancalendar::setminimaldaysinfirstweek' => 
  array (
    0 => 'true',
    'days' => 'int',
  ),
  'intlgregoriancalendar::getminimum' => 
  array (
    0 => 'int|false',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getnow' => 
  array (
    0 => 'float',
  ),
  'intlgregoriancalendar::getrepeatedwalltimeoption' => 
  array (
    0 => 'int',
  ),
  'intlgregoriancalendar::getskippedwalltimeoption' => 
  array (
    0 => 'int',
  ),
  'intlgregoriancalendar::gettime' => 
  array (
    0 => 'float|false',
  ),
  'intlgregoriancalendar::gettimezone' => 
  array (
    0 => 'IntlTimeZone|false',
  ),
  'intlgregoriancalendar::gettype' => 
  array (
    0 => 'string',
  ),
  'intlgregoriancalendar::getweekendtransition' => 
  array (
    0 => 'int|false',
    'dayOfWeek' => 'int',
  ),
  'intlgregoriancalendar::indaylighttime' => 
  array (
    0 => 'bool',
  ),
  'intlgregoriancalendar::isequivalentto' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlgregoriancalendar::islenient' => 
  array (
    0 => 'bool',
  ),
  'intlgregoriancalendar::isweekend' => 
  array (
    0 => 'bool',
    'timestamp=' => 'float|null',
  ),
  'intlgregoriancalendar::roll' => 
  array (
    0 => 'bool',
    'field' => 'int',
    'value' => 'mixed',
  ),
  'intlgregoriancalendar::isset' => 
  array (
    0 => 'bool',
    'field' => 'int',
  ),
  'intlgregoriancalendar::set' => 
  array (
    0 => 'true',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth=' => 'int',
    'hour=' => 'int',
    'minute=' => 'int',
    'second=' => 'int',
  ),
  'intlgregoriancalendar::setdate' => 
  array (
    0 => 'void',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
  ),
  'intlgregoriancalendar::setdatetime' => 
  array (
    0 => 'void',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth' => 'int',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int|null',
  ),
  'intlgregoriancalendar::setfirstdayofweek' => 
  array (
    0 => 'true',
    'dayOfWeek' => 'int',
  ),
  'intlgregoriancalendar::setlenient' => 
  array (
    0 => 'true',
    'lenient' => 'bool',
  ),
  'intlgregoriancalendar::setrepeatedwalltimeoption' => 
  array (
    0 => 'true',
    'option' => 'int',
  ),
  'intlgregoriancalendar::setskippedwalltimeoption' => 
  array (
    0 => 'true',
    'option' => 'int',
  ),
  'intlgregoriancalendar::settime' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'intlgregoriancalendar::settimezone' => 
  array (
    0 => 'bool',
    'timezone' => 'IntlTimeZone|DateTimeZone|string|null|null',
  ),
  'intlgregoriancalendar::todatetime' => 
  array (
    0 => 'DateTime|false',
  ),
  'spoofchecker::__construct' => 
  array (
    0 => 'void',
  ),
  'spoofchecker::issuspicious' => 
  array (
    0 => 'bool',
    'string' => 'string',
    '&errorCode=' => 'mixed',
  ),
  'spoofchecker::areconfusable' => 
  array (
    0 => 'bool',
    'string1' => 'string',
    'string2' => 'string',
    '&errorCode=' => 'mixed',
  ),
  'spoofchecker::setallowedlocales' => 
  array (
    0 => 'void',
    'locales' => 'string',
  ),
  'spoofchecker::setchecks' => 
  array (
    0 => 'void',
    'checks' => 'int',
  ),
  'spoofchecker::setrestrictionlevel' => 
  array (
    0 => 'void',
    'level' => 'int',
  ),
  'spoofchecker::setallowedchars' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'patternOptions=' => 'int',
  ),
  'intlexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'intlexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'intlexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'intlexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'intlexception::getfile' => 
  array (
    0 => 'string',
  ),
  'intlexception::getline' => 
  array (
    0 => 'int',
  ),
  'intlexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'intlexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'intlexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'intlexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'intliterator::current' => 
  array (
    0 => 'mixed',
  ),
  'intliterator::key' => 
  array (
    0 => 'mixed',
  ),
  'intliterator::next' => 
  array (
    0 => 'void',
  ),
  'intliterator::rewind' => 
  array (
    0 => 'void',
  ),
  'intliterator::valid' => 
  array (
    0 => 'bool',
  ),
  'intlbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlbreakiterator::__construct' => 
  array (
    0 => 'void',
  ),
  'intlbreakiterator::current' => 
  array (
    0 => 'int',
  ),
  'intlbreakiterator::first' => 
  array (
    0 => 'int',
  ),
  'intlbreakiterator::following' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlbreakiterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intlbreakiterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intlbreakiterator::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'intlbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlbreakiterator::gettext' => 
  array (
    0 => 'string|null',
  ),
  'intlbreakiterator::isboundary' => 
  array (
    0 => 'bool',
    'offset' => 'int',
  ),
  'intlbreakiterator::last' => 
  array (
    0 => 'int',
  ),
  'intlbreakiterator::next' => 
  array (
    0 => 'int',
    'offset=' => 'int|null',
  ),
  'intlbreakiterator::preceding' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlbreakiterator::previous' => 
  array (
    0 => 'int',
  ),
  'intlbreakiterator::settext' => 
  array (
    0 => 'bool',
    'text' => 'string',
  ),
  'intlbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlrulebasedbreakiterator::__construct' => 
  array (
    0 => 'void',
    'rules' => 'string',
    'compiled=' => 'bool',
  ),
  'intlrulebasedbreakiterator::getbinaryrules' => 
  array (
    0 => 'string|false',
  ),
  'intlrulebasedbreakiterator::getrules' => 
  array (
    0 => 'string|false',
  ),
  'intlrulebasedbreakiterator::getrulestatus' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::getrulestatusvec' => 
  array (
    0 => 'array|false',
  ),
  'intlrulebasedbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlrulebasedbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlrulebasedbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlrulebasedbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlrulebasedbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlrulebasedbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlrulebasedbreakiterator::current' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::first' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::following' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlrulebasedbreakiterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intlrulebasedbreakiterator::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'intlrulebasedbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlrulebasedbreakiterator::gettext' => 
  array (
    0 => 'string|null',
  ),
  'intlrulebasedbreakiterator::isboundary' => 
  array (
    0 => 'bool',
    'offset' => 'int',
  ),
  'intlrulebasedbreakiterator::last' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::next' => 
  array (
    0 => 'int',
    'offset=' => 'int|null',
  ),
  'intlrulebasedbreakiterator::preceding' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlrulebasedbreakiterator::previous' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::settext' => 
  array (
    0 => 'bool',
    'text' => 'string',
  ),
  'intlrulebasedbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlcodepointbreakiterator::getlastcodepoint' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlcodepointbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlcodepointbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlcodepointbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlcodepointbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlcodepointbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'string|null',
  ),
  'intlcodepointbreakiterator::current' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::first' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::following' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlcodepointbreakiterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intlcodepointbreakiterator::getlocale' => 
  array (
    0 => 'string|false',
    'type' => 'int',
  ),
  'intlcodepointbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlcodepointbreakiterator::gettext' => 
  array (
    0 => 'string|null',
  ),
  'intlcodepointbreakiterator::isboundary' => 
  array (
    0 => 'bool',
    'offset' => 'int',
  ),
  'intlcodepointbreakiterator::last' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::next' => 
  array (
    0 => 'int',
    'offset=' => 'int|null',
  ),
  'intlcodepointbreakiterator::preceding' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'intlcodepointbreakiterator::previous' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::settext' => 
  array (
    0 => 'bool',
    'text' => 'string',
  ),
  'intlcodepointbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlpartsiterator::getbreakiterator' => 
  array (
    0 => 'IntlBreakIterator',
  ),
  'intlpartsiterator::getrulestatus' => 
  array (
    0 => 'int',
  ),
  'intlpartsiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'intlpartsiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'intlpartsiterator::next' => 
  array (
    0 => 'void',
  ),
  'intlpartsiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'intlpartsiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'uconverter::__construct' => 
  array (
    0 => 'void',
    'destination_encoding=' => 'string|null',
    'source_encoding=' => 'string|null',
  ),
  'uconverter::convert' => 
  array (
    0 => 'string|false',
    'str' => 'string',
    'reverse=' => 'bool',
  ),
  'uconverter::fromucallback' => 
  array (
    0 => 'array|string|int|null|null',
    'reason' => 'int',
    'source' => 'array',
    'codePoint' => 'int',
    '&error' => 'mixed',
  ),
  'uconverter::getaliases' => 
  array (
    0 => 'array|false|null|null',
    'name' => 'string',
  ),
  'uconverter::getavailable' => 
  array (
    0 => 'array',
  ),
  'uconverter::getdestinationencoding' => 
  array (
    0 => 'string|false|null|null',
  ),
  'uconverter::getdestinationtype' => 
  array (
    0 => 'int|false|null|null',
  ),
  'uconverter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'uconverter::geterrormessage' => 
  array (
    0 => 'string|null',
  ),
  'uconverter::getsourceencoding' => 
  array (
    0 => 'string|false|null|null',
  ),
  'uconverter::getsourcetype' => 
  array (
    0 => 'int|false|null|null',
  ),
  'uconverter::getstandards' => 
  array (
    0 => 'array|null',
  ),
  'uconverter::getsubstchars' => 
  array (
    0 => 'string|false|null|null',
  ),
  'uconverter::reasontext' => 
  array (
    0 => 'string',
    'reason' => 'int',
  ),
  'uconverter::setdestinationencoding' => 
  array (
    0 => 'bool',
    'encoding' => 'string',
  ),
  'uconverter::setsourceencoding' => 
  array (
    0 => 'bool',
    'encoding' => 'string',
  ),
  'uconverter::setsubstchars' => 
  array (
    0 => 'bool',
    'chars' => 'string',
  ),
  'uconverter::toucallback' => 
  array (
    0 => 'array|string|int|null|null',
    'reason' => 'int',
    'source' => 'string',
    'codeUnits' => 'string',
    '&error' => 'mixed',
  ),
  'uconverter::transcode' => 
  array (
    0 => 'string|false',
    'str' => 'string',
    'toEncoding' => 'string',
    'fromEncoding' => 'string',
    'options=' => 'array|null',
  ),
  'intlchar::hasbinaryproperty' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
    'property' => 'int',
  ),
  'intlchar::charage' => 
  array (
    0 => 'array|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::chardigitvalue' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::chardirection' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::charfromname' => 
  array (
    0 => 'int|null',
    'name' => 'string',
    'type=' => 'int',
  ),
  'intlchar::charmirror' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::charname' => 
  array (
    0 => 'string|null',
    'codepoint' => 'string|int',
    'type=' => 'int',
  ),
  'intlchar::chartype' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::chr' => 
  array (
    0 => 'string|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::digit' => 
  array (
    0 => 'int|false|null|null',
    'codepoint' => 'string|int',
    'base=' => 'int',
  ),
  'intlchar::enumcharnames' => 
  array (
    0 => 'bool',
    'start' => 'string|int',
    'end' => 'string|int',
    'callback' => 'callable',
    'type=' => 'int',
  ),
  'intlchar::enumchartypes' => 
  array (
    0 => 'void',
    'callback' => 'callable',
  ),
  'intlchar::foldcase' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
    'options=' => 'int',
  ),
  'intlchar::fordigit' => 
  array (
    0 => 'int',
    'digit' => 'int',
    'base=' => 'int',
  ),
  'intlchar::getbidipairedbracket' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::getblockcode' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::getcombiningclass' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::getfc_nfkc_closure' => 
  array (
    0 => 'string|false|null|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::getintpropertymaxvalue' => 
  array (
    0 => 'int',
    'property' => 'int',
  ),
  'intlchar::getintpropertyminvalue' => 
  array (
    0 => 'int',
    'property' => 'int',
  ),
  'intlchar::getintpropertyvalue' => 
  array (
    0 => 'int|null',
    'codepoint' => 'string|int',
    'property' => 'int',
  ),
  'intlchar::getnumericvalue' => 
  array (
    0 => 'float|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::getpropertyenum' => 
  array (
    0 => 'int',
    'alias' => 'string',
  ),
  'intlchar::getpropertyname' => 
  array (
    0 => 'string|false',
    'property' => 'int',
    'type=' => 'int',
  ),
  'intlchar::getpropertyvalueenum' => 
  array (
    0 => 'int',
    'property' => 'int',
    'name' => 'string',
  ),
  'intlchar::getpropertyvaluename' => 
  array (
    0 => 'string|false',
    'property' => 'int',
    'value' => 'int',
    'type=' => 'int',
  ),
  'intlchar::getunicodeversion' => 
  array (
    0 => 'array',
  ),
  'intlchar::isalnum' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isalpha' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isbase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isblank' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::iscntrl' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isdefined' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isdigit' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isgraph' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isidignorable' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isidpart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isidstart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isisocontrol' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isjavaidpart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isjavaidstart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isjavaspacechar' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::islower' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::ismirrored' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isprint' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::ispunct' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isspace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::istitle' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isualphabetic' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isulowercase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isupper' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isuuppercase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isuwhitespace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::iswhitespace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::isxdigit' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::ord' => 
  array (
    0 => 'int|null',
    'character' => 'string|int',
  ),
  'intlchar::tolower' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::totitle' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
  ),
  'intlchar::toupper' => 
  array (
    0 => 'string|int|null|null',
    'codepoint' => 'string|int',
  ),
  'mongodb\\bson\\iterator::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\iterator::current' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\iterator::key' => 
  array (
    0 => 'string|int',
  ),
  'mongodb\\bson\\iterator::next' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\iterator::rewind' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\iterator::valid' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\bson\\packedarray::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\packedarray::fromjson' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'json' => 'string',
  ),
  'mongodb\\bson\\packedarray::fromphp' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'value' => 'array',
  ),
  'mongodb\\bson\\packedarray::get' => 
  array (
    0 => 'mixed',
    'index' => 'int',
  ),
  'mongodb\\bson\\packedarray::getiterator' => 
  array (
    0 => 'MongoDB\\BSON\\Iterator',
  ),
  'mongodb\\bson\\packedarray::has' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'mongodb\\bson\\packedarray::tophp' => 
  array (
    0 => 'object|array',
    'typeMap=' => 'array|null',
  ),
  'mongodb\\bson\\packedarray::tocanonicalextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\packedarray::torelaxedextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\packedarray::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\packedarray::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\packedarray::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'mongodb\\bson\\packedarray::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\packedarray::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\packedarray::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'properties' => 'array',
  ),
  'mongodb\\bson\\packedarray::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\packedarray::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\document::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\document::frombson' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'bson' => 'string',
  ),
  'mongodb\\bson\\document::fromjson' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'json' => 'string',
  ),
  'mongodb\\bson\\document::fromphp' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'value' => 'object|array',
  ),
  'mongodb\\bson\\document::get' => 
  array (
    0 => 'mixed',
    'key' => 'string',
  ),
  'mongodb\\bson\\document::getiterator' => 
  array (
    0 => 'MongoDB\\BSON\\Iterator',
  ),
  'mongodb\\bson\\document::has' => 
  array (
    0 => 'bool',
    'key' => 'string',
  ),
  'mongodb\\bson\\document::tophp' => 
  array (
    0 => 'object|array',
    'typeMap=' => 'array|null',
  ),
  'mongodb\\bson\\document::tocanonicalextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\document::torelaxedextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\document::offsetexists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\document::offsetget' => 
  array (
    0 => 'mixed',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\document::offsetset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
    'value' => 'mixed',
  ),
  'mongodb\\bson\\document::offsetunset' => 
  array (
    0 => 'void',
    'offset' => 'mixed',
  ),
  'mongodb\\bson\\document::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\document::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'properties' => 'array',
  ),
  'mongodb\\bson\\document::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\document::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\binary::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
    'type=' => 'int',
  ),
  'mongodb\\bson\\binary::getdata' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\binary::gettype' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\binary::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'properties' => 'array',
  ),
  'mongodb\\bson\\binary::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\binary::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\binary::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\binary::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\dbpointer::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\dbpointer::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\DBPointer',
    'properties' => 'array',
  ),
  'mongodb\\bson\\dbpointer::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\dbpointer::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\dbpointer::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\dbpointer::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\decimal128::__construct' => 
  array (
    0 => 'void',
    'value' => 'string',
  ),
  'mongodb\\bson\\decimal128::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\decimal128::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Decimal128',
    'properties' => 'array',
  ),
  'mongodb\\bson\\decimal128::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\decimal128::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\decimal128::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\int64::__construct' => 
  array (
    0 => 'void',
    'value' => 'string|int',
  ),
  'mongodb\\bson\\int64::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\int64::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Int64',
    'properties' => 'array',
  ),
  'mongodb\\bson\\int64::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\int64::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\int64::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\javascript::__construct' => 
  array (
    0 => 'void',
    'code' => 'string',
    'scope=' => 'object|array|null|null',
  ),
  'mongodb\\bson\\javascript::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Javascript',
    'properties' => 'array',
  ),
  'mongodb\\bson\\javascript::getcode' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\javascript::getscope' => 
  array (
    0 => 'object|null',
  ),
  'mongodb\\bson\\javascript::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\javascript::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\javascript::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\javascript::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\maxkey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MaxKey',
    'properties' => 'array',
  ),
  'mongodb\\bson\\maxkey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\maxkey::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\maxkey::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\minkey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MinKey',
    'properties' => 'array',
  ),
  'mongodb\\bson\\minkey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\minkey::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\minkey::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\objectid::__construct' => 
  array (
    0 => 'void',
    'id=' => 'string|null',
  ),
  'mongodb\\bson\\objectid::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\objectid::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\objectid::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
    'properties' => 'array',
  ),
  'mongodb\\bson\\objectid::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\objectid::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\objectid::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\regex::__construct' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'flags=' => 'string',
  ),
  'mongodb\\bson\\regex::getpattern' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::getflags' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Regex',
    'properties' => 'array',
  ),
  'mongodb\\bson\\regex::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\regex::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\regex::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\symbol::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\symbol::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\symbol::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Symbol',
    'properties' => 'array',
  ),
  'mongodb\\bson\\symbol::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\symbol::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\symbol::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\timestamp::__construct' => 
  array (
    0 => 'void',
    'increment' => 'string|int',
    'timestamp' => 'string|int',
  ),
  'mongodb\\bson\\timestamp::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\timestamp::getincrement' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\timestamp::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\timestamp::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Timestamp',
    'properties' => 'array',
  ),
  'mongodb\\bson\\timestamp::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\timestamp::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\timestamp::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\undefined::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\undefined::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\undefined::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Undefined',
    'properties' => 'array',
  ),
  'mongodb\\bson\\undefined::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\undefined::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\undefined::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\utcdatetime::__construct' => 
  array (
    0 => 'void',
    'milliseconds=' => 'DateTimeInterface|MongoDB\\BSON\\Int64|int|null|null',
  ),
  'mongodb\\bson\\utcdatetime::todatetime' => 
  array (
    0 => 'DateTime',
  ),
  'mongodb\\bson\\utcdatetime::todatetimeimmutable' => 
  array (
    0 => 'DateTimeImmutable',
  ),
  'mongodb\\bson\\utcdatetime::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\utcdatetime::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\UTCDateTime',
    'properties' => 'array',
  ),
  'mongodb\\bson\\utcdatetime::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\bson\\utcdatetime::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\bson\\utcdatetime::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\bulkwrite::__construct' => 
  array (
    0 => 'void',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwrite::count' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwrite::delete' => 
  array (
    0 => 'void',
    'filter' => 'object|array',
    'deleteOptions=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwrite::insert' => 
  array (
    0 => 'mixed',
    'document' => 'object|array',
  ),
  'mongodb\\driver\\bulkwrite::update' => 
  array (
    0 => 'void',
    'filter' => 'object|array',
    'newObj' => 'object|array',
    'updateOptions=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::__construct' => 
  array (
    0 => 'void',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::count' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommand::deleteone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::deletemany' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::insertone' => 
  array (
    0 => 'mixed',
    'namespace' => 'string',
    'document' => 'object|array',
  ),
  'mongodb\\driver\\bulkwritecommand::replaceone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'object|array',
    'replacement' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::updateone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'object|array',
    'update' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommand::updatemany' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'object|array',
    'update' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getinsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getmatchedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getmodifiedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getupsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getdeletedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getinsertresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getupdateresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getdeleteresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::isacknowledged' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\clientencryption::__construct' => 
  array (
    0 => 'void',
    'options' => 'array',
  ),
  'mongodb\\driver\\clientencryption::addkeyaltname' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::createdatakey' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'kmsProvider' => 'string',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\clientencryption::decrypt' => 
  array (
    0 => 'mixed',
    'value' => 'MongoDB\\BSON\\Binary',
  ),
  'mongodb\\driver\\clientencryption::deletekey' => 
  array (
    0 => 'object',
    'keyId' => 'MongoDB\\BSON\\Binary',
  ),
  'mongodb\\driver\\clientencryption::encrypt' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'value' => 'mixed',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\clientencryption::encryptexpression' => 
  array (
    0 => 'object',
    'expr' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\clientencryption::getkey' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
  ),
  'mongodb\\driver\\clientencryption::getkeybyaltname' => 
  array (
    0 => 'object|null',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::getkeys' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
  ),
  'mongodb\\driver\\clientencryption::removekeyaltname' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::rewrapmanydatakey' => 
  array (
    0 => 'object',
    'filter' => 'object|array',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\command::__construct' => 
  array (
    0 => 'void',
    'document' => 'object|array',
    'commandOptions=' => 'array|null',
  ),
  'mongodb\\driver\\cursor::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\cursor::current' => 
  array (
    0 => 'object|array|null|null',
  ),
  'mongodb\\driver\\cursor::getid' => 
  array (
    0 => 'MongoDB\\BSON\\Int64',
  ),
  'mongodb\\driver\\cursor::getserver' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'mongodb\\driver\\cursor::isdead' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\cursor::key' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\cursor::next' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\cursor::rewind' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\cursor::settypemap' => 
  array (
    0 => 'void',
    'typemap' => 'array',
  ),
  'mongodb\\driver\\cursor::toarray' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\cursor::valid' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\manager::__construct' => 
  array (
    0 => 'void',
    'uri=' => 'string|null',
    'uriOptions=' => 'array|null',
    'driverOptions=' => 'array|null',
  ),
  'mongodb\\driver\\manager::addsubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'mongodb\\driver\\manager::createclientencryption' => 
  array (
    0 => 'MongoDB\\Driver\\ClientEncryption',
    'options' => 'array',
  ),
  'mongodb\\driver\\manager::executebulkwrite' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
    'namespace' => 'string',
    'bulk' => 'MongoDB\\Driver\\BulkWrite',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executebulkwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult',
    'bulkWriteCommand' => 'MongoDB\\Driver\\BulkWriteCommand',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executequery' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executereadcommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executereadwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::executewritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\manager::getencryptedfieldsmap' => 
  array (
    0 => 'object|array|null|null',
  ),
  'mongodb\\driver\\manager::getreadconcern' => 
  array (
    0 => 'MongoDB\\Driver\\ReadConcern',
  ),
  'mongodb\\driver\\manager::getreadpreference' => 
  array (
    0 => 'MongoDB\\Driver\\ReadPreference',
  ),
  'mongodb\\driver\\manager::getservers' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\manager::getwriteconcern' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcern',
  ),
  'mongodb\\driver\\manager::removesubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'mongodb\\driver\\manager::selectserver' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
    'readPreference=' => 'MongoDB\\Driver\\ReadPreference|null',
  ),
  'mongodb\\driver\\manager::startsession' => 
  array (
    0 => 'MongoDB\\Driver\\Session',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\query::__construct' => 
  array (
    0 => 'void',
    'filter' => 'object|array',
    'queryOptions=' => 'array|null',
  ),
  'mongodb\\driver\\readconcern::__construct' => 
  array (
    0 => 'void',
    'level=' => 'string|null',
  ),
  'mongodb\\driver\\readconcern::getlevel' => 
  array (
    0 => 'string|null',
  ),
  'mongodb\\driver\\readconcern::isdefault' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\readconcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadConcern',
    'properties' => 'array',
  ),
  'mongodb\\driver\\readconcern::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\readconcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\driver\\readconcern::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\readpreference::__construct' => 
  array (
    0 => 'void',
    'mode' => 'string',
    'tagSets=' => 'array|null',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\readpreference::gethedge' => 
  array (
    0 => 'object|null',
  ),
  'mongodb\\driver\\readpreference::getmaxstalenessseconds' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\readpreference::getmodestring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\readpreference::gettagsets' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\readpreference::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadPreference',
    'properties' => 'array',
  ),
  'mongodb\\driver\\readpreference::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\readpreference::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\driver\\readpreference::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\server::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\server::executebulkwrite' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
    'namespace' => 'string',
    'bulkWrite' => 'MongoDB\\Driver\\BulkWrite',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executebulkwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult',
    'bulkWriteCommand' => 'MongoDB\\Driver\\BulkWriteCommand',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executequery' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executereadcommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executereadwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::executewritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\server::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\server::getinfo' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\server::getlatency' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\server::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\server::getserverdescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'mongodb\\driver\\server::gettags' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\server::gettype' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\server::isarbiter' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\server::ishidden' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\server::ispassive' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\server::isprimary' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\server::issecondary' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\serverapi::__construct' => 
  array (
    0 => 'void',
    'version' => 'string',
    'strict=' => 'bool|null',
    'deprecationErrors=' => 'bool|null',
  ),
  'mongodb\\driver\\serverapi::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ServerApi',
    'properties' => 'array',
  ),
  'mongodb\\driver\\serverapi::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\serverapi::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\driver\\serverapi::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\serverdescription::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\serverdescription::gethelloresponse' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\serverdescription::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\serverdescription::getlastupdatetime' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\serverdescription::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\serverdescription::getroundtriptime' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\serverdescription::gettype' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\topologydescription::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\topologydescription::getservers' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\topologydescription::gettype' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\topologydescription::hasreadableserver' => 
  array (
    0 => 'bool',
    'readPreference=' => 'MongoDB\\Driver\\ReadPreference|null',
  ),
  'mongodb\\driver\\topologydescription::haswritableserver' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\session::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\session::aborttransaction' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\session::advanceclustertime' => 
  array (
    0 => 'void',
    'clusterTime' => 'object|array',
  ),
  'mongodb\\driver\\session::advanceoperationtime' => 
  array (
    0 => 'void',
    'operationTime' => 'MongoDB\\BSON\\TimestampInterface',
  ),
  'mongodb\\driver\\session::committransaction' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\session::endsession' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\session::getclustertime' => 
  array (
    0 => 'object|null',
  ),
  'mongodb\\driver\\session::getlogicalsessionid' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\session::getoperationtime' => 
  array (
    0 => 'MongoDB\\BSON\\Timestamp|null',
  ),
  'mongodb\\driver\\session::getserver' => 
  array (
    0 => 'MongoDB\\Driver\\Server|null',
  ),
  'mongodb\\driver\\session::gettransactionoptions' => 
  array (
    0 => 'array|null',
  ),
  'mongodb\\driver\\session::gettransactionstate' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\session::isdirty' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\session::isintransaction' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\session::starttransaction' => 
  array (
    0 => 'void',
    'options=' => 'array|null',
  ),
  'mongodb\\driver\\writeconcern::__construct' => 
  array (
    0 => 'void',
    'w' => 'string|int',
    'wtimeout=' => 'int|null',
    'journal=' => 'bool|null',
  ),
  'mongodb\\driver\\writeconcern::getjournal' => 
  array (
    0 => 'bool|null',
  ),
  'mongodb\\driver\\writeconcern::getw' => 
  array (
    0 => 'string|int|null|null',
  ),
  'mongodb\\driver\\writeconcern::getwtimeout' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeconcern::isdefault' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\writeconcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcern',
    'properties' => 'array',
  ),
  'mongodb\\driver\\writeconcern::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\writeconcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'mongodb\\driver\\writeconcern::__serialize' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\writeconcernerror::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\writeconcernerror::getcode' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeconcernerror::getinfo' => 
  array (
    0 => 'object|null',
  ),
  'mongodb\\driver\\writeconcernerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\writeerror::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\writeerror::getcode' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeerror::getindex' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeerror::getinfo' => 
  array (
    0 => 'object|null',
  ),
  'mongodb\\driver\\writeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\writeresult::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\writeresult::getinsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getmatchedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getmodifiedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getdeletedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getupsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getserver' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'mongodb\\driver\\writeresult::getupsertedids' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\writeresult::getwriteconcernerror' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcernError|null',
  ),
  'mongodb\\driver\\writeresult::getwriteerrors' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\writeresult::geterrorreplies' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\writeresult::isacknowledged' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\exception\\runtimeexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\runtimeexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\runtimeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\serverexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\serverexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\serverexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\serverexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\serverexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\serverexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectionexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\connectionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\connectionexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\connectionexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\connectionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectionexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\authenticationexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\authenticationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getwriteresult' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::geterrorreply' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getpartialresult' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getwriteerrors' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getwriteconcernerrors' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::getresultdocument' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\exception\\commandexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\commandexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\commandexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\commandexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\commandexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\commandexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\commandexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\encryptionexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\encryptionexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\encryptionexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\logicexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\logicexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\logicexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\logicexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\logicexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\logicexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getcommandname' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getdatabasename' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getdurationmicros' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::geterror' => 
  array (
    0 => 'Exception',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getoperationid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getreply' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getrequestid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getcommand' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getcommandname' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getdatabasename' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getoperationid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getrequestid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getcommandname' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getdatabasename' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getdurationmicros' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getoperationid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getreply' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getrequestid' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::getnewdescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::getpreviousdescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'mongodb\\driver\\monitoring\\serverclosedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverclosedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverclosedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverclosedevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::getdurationmicros' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::geterror' => 
  array (
    0 => 'Exception',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::isawaited' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::isawaited' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::getdurationmicros' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::getreply' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::isawaited' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'mongodb\\driver\\monitoring\\topologychangedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\topologychangedevent::getnewdescription' => 
  array (
    0 => 'MongoDB\\Driver\\TopologyDescription',
  ),
  'mongodb\\driver\\monitoring\\topologychangedevent::getpreviousdescription' => 
  array (
    0 => 'MongoDB\\Driver\\TopologyDescription',
  ),
  'mongodb\\driver\\monitoring\\topologychangedevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'mongodb\\driver\\monitoring\\topologyclosedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\topologyclosedevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'mongodb\\driver\\monitoring\\topologyopeningevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\topologyopeningevent::gettopologyid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'pcntl\\qosclass::cases' => 
  array (
    0 => 'array',
  ),
  'pdo\\mysql::getwarningcount' => 
  array (
    0 => 'int',
  ),
  'pdo\\mysql::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo\\mysql::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'pdo\\mysql::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::errorcode' => 
  array (
    0 => 'string|null',
  ),
  'pdo\\mysql::errorinfo' => 
  array (
    0 => 'array',
  ),
  'pdo\\mysql::exec' => 
  array (
    0 => 'int|false',
    'statement' => 'string',
  ),
  'pdo\\mysql::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo\\mysql::getavailabledrivers' => 
  array (
    0 => 'array',
  ),
  'pdo\\mysql::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::lastinsertid' => 
  array (
    0 => 'string|false',
    'name=' => 'string|null',
  ),
  'pdo\\mysql::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array',
  ),
  'pdo\\mysql::query' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'fetchMode=' => 'int|null',
    '...fetchModeArgs=' => 'mixed',
  ),
  'pdo\\mysql::quote' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'type=' => 'int',
  ),
  'pdo\\mysql::rollback' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'mixed',
  ),
  'soapclient::__construct' => 
  array (
    0 => 'void',
    'wsdl' => 'string|null',
    'options=' => 'array',
  ),
  'soapclient::__call' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'args' => 'array',
  ),
  'soapclient::__soapcall' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'args' => 'array',
    'options=' => 'array|null',
    'inputHeaders=' => 'mixed',
    '&outputHeaders=' => 'mixed',
  ),
  'soapclient::__getfunctions' => 
  array (
    0 => 'array|null',
  ),
  'soapclient::__gettypes' => 
  array (
    0 => 'array|null',
  ),
  'soapclient::__getlastrequest' => 
  array (
    0 => 'string|null',
  ),
  'soapclient::__getlastresponse' => 
  array (
    0 => 'string|null',
  ),
  'soapclient::__getlastrequestheaders' => 
  array (
    0 => 'string|null',
  ),
  'soapclient::__getlastresponseheaders' => 
  array (
    0 => 'string|null',
  ),
  'soapclient::__dorequest' => 
  array (
    0 => 'string|null',
    'request' => 'string',
    'location' => 'string',
    'action' => 'string',
    'version' => 'int',
    'oneWay=' => 'bool',
    'uriParserClass=' => 'string|null',
  ),
  'soapclient::__setcookie' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'string|null',
  ),
  'soapclient::__getcookies' => 
  array (
    0 => 'array',
  ),
  'soapclient::__setsoapheaders' => 
  array (
    0 => 'bool',
    'headers=' => 'mixed',
  ),
  'soapclient::__setlocation' => 
  array (
    0 => 'string|null',
    'location=' => 'string|null',
  ),
  'soapvar::__construct' => 
  array (
    0 => 'void',
    'data' => 'mixed',
    'encoding' => 'int|null',
    'typeName=' => 'string|null',
    'typeNamespace=' => 'string|null',
    'nodeName=' => 'string|null',
    'nodeNamespace=' => 'string|null',
  ),
  'soapserver::__construct' => 
  array (
    0 => 'void',
    'wsdl' => 'string|null',
    'options=' => 'array',
  ),
  'soapserver::fault' => 
  array (
    0 => 'void',
    'code' => 'string',
    'string' => 'string',
    'actor=' => 'string',
    'details=' => 'mixed',
    'name=' => 'string',
    'lang=' => 'string',
  ),
  'soapserver::addsoapheader' => 
  array (
    0 => 'void',
    'header' => 'SoapHeader',
  ),
  'soapserver::setpersistence' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'soapserver::setclass' => 
  array (
    0 => 'void',
    'class' => 'string',
    '...args=' => 'mixed',
  ),
  'soapserver::setobject' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'soapserver::getfunctions' => 
  array (
    0 => 'array',
  ),
  'soapserver::addfunction' => 
  array (
    0 => 'void',
    'functions' => 'mixed',
  ),
  'soapserver::handle' => 
  array (
    0 => 'void',
    'request=' => 'string|null',
  ),
  'soapserver::__getlastresponse' => 
  array (
    0 => 'string|null',
  ),
  'soapfault::__construct' => 
  array (
    0 => 'void',
    'code' => 'array|string|null|null',
    'string' => 'string',
    'actor=' => 'string|null',
    'details=' => 'mixed',
    'name=' => 'string|null',
    'headerFault=' => 'mixed',
    'lang=' => 'string',
  ),
  'soapfault::__tostring' => 
  array (
    0 => 'string',
  ),
  'soapfault::__wakeup' => 
  array (
    0 => 'void',
  ),
  'soapfault::getmessage' => 
  array (
    0 => 'string',
  ),
  'soapfault::getcode' => 
  array (
    0 => 'mixed',
  ),
  'soapfault::getfile' => 
  array (
    0 => 'string',
  ),
  'soapfault::getline' => 
  array (
    0 => 'int',
  ),
  'soapfault::gettrace' => 
  array (
    0 => 'array',
  ),
  'soapfault::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'soapfault::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'soapparam::__construct' => 
  array (
    0 => 'void',
    'data' => 'mixed',
    'name' => 'string',
  ),
  'soapheader::__construct' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'name' => 'string',
    'data=' => 'mixed',
    'mustUnderstand=' => 'bool',
    'actor=' => 'string|int|null|null',
  ),
  'sodiumexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'sodiumexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'sodiumexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'sodiumexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'sodiumexception::getfile' => 
  array (
    0 => 'string',
  ),
  'sodiumexception::getline' => 
  array (
    0 => 'int',
  ),
  'sodiumexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'sodiumexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'sodiumexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'sodiumexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'ziparchive::open' => 
  array (
    0 => 'int|bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::setpassword' => 
  array (
    0 => 'bool',
    'password' => 'string',
  ),
  'ziparchive::close' => 
  array (
    0 => 'bool',
  ),
  'ziparchive::count' => 
  array (
    0 => 'int',
  ),
  'ziparchive::getstatusstring' => 
  array (
    0 => 'string',
  ),
  'ziparchive::clearerror' => 
  array (
    0 => 'void',
  ),
  'ziparchive::addemptydir' => 
  array (
    0 => 'bool',
    'dirname' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::addfromstring' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::addfile' => 
  array (
    0 => 'bool',
    'filepath' => 'string',
    'entryname=' => 'string',
    'start=' => 'int',
    'length=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::replacefile' => 
  array (
    0 => 'bool',
    'filepath' => 'string',
    'index' => 'int',
    'start=' => 'int',
    'length=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::addglob' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'flags=' => 'int',
    'options=' => 'array',
  ),
  'ziparchive::addpattern' => 
  array (
    0 => 'array|false',
    'pattern' => 'string',
    'path=' => 'string',
    'options=' => 'array',
  ),
  'ziparchive::renameindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'new_name' => 'string',
  ),
  'ziparchive::renamename' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'new_name' => 'string',
  ),
  'ziparchive::setarchivecomment' => 
  array (
    0 => 'bool',
    'comment' => 'string',
  ),
  'ziparchive::getarchivecomment' => 
  array (
    0 => 'string|false',
    'flags=' => 'int',
  ),
  'ziparchive::setarchiveflag' => 
  array (
    0 => 'bool',
    'flag' => 'int',
    'value' => 'int',
  ),
  'ziparchive::getarchiveflag' => 
  array (
    0 => 'int',
    'flag' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::setcommentindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'comment' => 'string',
  ),
  'ziparchive::setcommentname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'comment' => 'string',
  ),
  'ziparchive::setmtimeindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'timestamp' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::setmtimename' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'timestamp' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getcommentindex' => 
  array (
    0 => 'string|false',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getcommentname' => 
  array (
    0 => 'string|false',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::deleteindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'ziparchive::deletename' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'ziparchive::statname' => 
  array (
    0 => 'array|false',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::statindex' => 
  array (
    0 => 'array|false',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::locatename' => 
  array (
    0 => 'int|false',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::getnameindex' => 
  array (
    0 => 'string|false',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::unchangearchive' => 
  array (
    0 => 'bool',
  ),
  'ziparchive::unchangeall' => 
  array (
    0 => 'bool',
  ),
  'ziparchive::unchangeindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'ziparchive::unchangename' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'ziparchive::extractto' => 
  array (
    0 => 'bool',
    'pathto' => 'string',
    'files=' => 'array|string|null|null',
  ),
  'ziparchive::getfromname' => 
  array (
    0 => 'string|false',
    'name' => 'string',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getfromindex' => 
  array (
    0 => 'string|false',
    'index' => 'int',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getstreamindex' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getstreamname' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::getstream' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'ziparchive::setexternalattributesname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::setexternalattributesindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getexternalattributesname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    '&opsys' => 'mixed',
    '&attr' => 'mixed',
    'flags=' => 'int',
  ),
  'ziparchive::getexternalattributesindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    '&opsys' => 'mixed',
    '&attr' => 'mixed',
    'flags=' => 'int',
  ),
  'ziparchive::setcompressionname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ziparchive::setcompressionindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ziparchive::setencryptionname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'method' => 'int',
    'password=' => 'string|null',
  ),
  'ziparchive::setencryptionindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'method' => 'int',
    'password=' => 'string|null',
  ),
  'ziparchive::registerprogresscallback' => 
  array (
    0 => 'bool',
    'rate' => 'float',
    'callback' => 'callable',
  ),
  'ziparchive::registercancelcallback' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'ziparchive::iscompressionmethodsupported' => 
  array (
    0 => 'bool',
    'method' => 'int',
    'enc=' => 'bool',
  ),
  'ziparchive::isencryptionmethodsupported' => 
  array (
    0 => 'bool',
    'method' => 'int',
    'enc=' => 'bool',
  ),
  'event::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'fd' => 'mixed',
    'what' => 'int',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'event::free' => 
  array (
    0 => 'void',
  ),
  'event::set' => 
  array (
    0 => 'bool',
    'base' => 'EventBase',
    'fd' => 'mixed',
    'what=' => 'int',
    'cb=' => 'callable|null',
    'arg=' => 'mixed',
  ),
  'event::getsupportedmethods' => 
  array (
    0 => 'array',
  ),
  'event::add' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::del' => 
  array (
    0 => 'bool',
  ),
  'event::setpriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'event::pending' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'event::removetimer' => 
  array (
    0 => 'bool',
  ),
  'event::timer' => 
  array (
    0 => 'Event',
    'base' => 'EventBase',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'event::settimer' => 
  array (
    0 => 'bool',
    'base' => 'EventBase',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'event::signal' => 
  array (
    0 => 'Event',
    'base' => 'EventBase',
    'signum' => 'int',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'event::addtimer' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::deltimer' => 
  array (
    0 => 'bool',
  ),
  'event::addsignal' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::delsignal' => 
  array (
    0 => 'bool',
  ),
  'eventbase::__construct' => 
  array (
    0 => 'void',
    'cfg=' => 'EventConfig|null',
  ),
  'eventbase::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventbase::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventbase::getmethod' => 
  array (
    0 => 'string',
  ),
  'eventbase::getfeatures' => 
  array (
    0 => 'int',
  ),
  'eventbase::priorityinit' => 
  array (
    0 => 'bool',
    'n_priorities' => 'int',
  ),
  'eventbase::loop' => 
  array (
    0 => 'bool',
    'flags=' => 'int',
  ),
  'eventbase::dispatch' => 
  array (
    0 => 'bool',
  ),
  'eventbase::exit' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'eventbase::set' => 
  array (
    0 => 'bool',
    'event' => 'Event',
  ),
  'eventbase::stop' => 
  array (
    0 => 'bool',
  ),
  'eventbase::gotstop' => 
  array (
    0 => 'bool',
  ),
  'eventbase::gotexit' => 
  array (
    0 => 'bool',
  ),
  'eventbase::gettimeofdaycached' => 
  array (
    0 => 'float',
  ),
  'eventbase::reinit' => 
  array (
    0 => 'bool',
  ),
  'eventbase::free' => 
  array (
    0 => 'void',
  ),
  'eventbase::updatecachetime' => 
  array (
    0 => 'bool',
  ),
  'eventbase::resume' => 
  array (
    0 => 'bool',
  ),
  'eventconfig::__construct' => 
  array (
    0 => 'void',
  ),
  'eventconfig::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventconfig::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventconfig::avoidmethod' => 
  array (
    0 => 'bool',
    'method' => 'string',
  ),
  'eventconfig::requirefeatures' => 
  array (
    0 => 'bool',
    'feature' => 'int',
  ),
  'eventconfig::setmaxdispatchinterval' => 
  array (
    0 => 'void',
    'max_interval' => 'int',
    'max_callbacks' => 'int',
    'min_priority' => 'int',
  ),
  'eventconfig::setflags' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'eventbufferevent::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'socket=' => 'mixed',
    'options=' => 'int',
    'readcb=' => 'callable|null',
    'writecb=' => 'callable|null',
    'eventcb=' => 'callable|null',
    'arg=' => 'mixed',
  ),
  'eventbufferevent::free' => 
  array (
    0 => 'void',
  ),
  'eventbufferevent::close' => 
  array (
    0 => 'void',
  ),
  'eventbufferevent::connect' => 
  array (
    0 => 'bool',
    'addr' => 'string',
  ),
  'eventbufferevent::connecthost' => 
  array (
    0 => 'bool',
    'dns_base' => 'EventDnsBase|null',
    'hostname' => 'string',
    'port' => 'int',
    'family=' => 'int',
  ),
  'eventbufferevent::getdnserrorstring' => 
  array (
    0 => 'string',
  ),
  'eventbufferevent::setcallbacks' => 
  array (
    0 => 'void',
    'readcb' => 'callable|null',
    'writecb' => 'callable|null',
    'eventcb' => 'callable|null',
    'arg=' => 'mixed',
  ),
  'eventbufferevent::enable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'eventbufferevent::disable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'eventbufferevent::getenabled' => 
  array (
    0 => 'int',
  ),
  'eventbufferevent::getinput' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventbufferevent::getoutput' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventbufferevent::setwatermark' => 
  array (
    0 => 'void',
    'events' => 'int',
    'lowmark' => 'int',
    'highmark' => 'int',
  ),
  'eventbufferevent::write' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'eventbufferevent::writebuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'eventbufferevent::read' => 
  array (
    0 => 'string|null',
    'size' => 'int',
  ),
  'eventbufferevent::readbuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'eventbufferevent::createpair' => 
  array (
    0 => 'array|false',
    'base' => 'EventBase',
    'options=' => 'int',
  ),
  'eventbufferevent::setpriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'eventbufferevent::settimeouts' => 
  array (
    0 => 'bool',
    'timeout_read' => 'float',
    'timeout_write' => 'float',
  ),
  'eventbufferevent::createsslfilter' => 
  array (
    0 => 'EventBufferEvent',
    'unnderlying' => 'EventBufferEvent',
    'ctx' => 'EventSslContext',
    'state' => 'int',
    'options=' => 'int',
  ),
  'eventbufferevent::sslsocket' => 
  array (
    0 => 'EventBufferEvent',
    'base' => 'EventBase',
    'socket' => 'mixed',
    'ctx' => 'EventSslContext',
    'state' => 'int',
    'options=' => 'int',
  ),
  'eventbufferevent::sslerror' => 
  array (
    0 => 'string',
  ),
  'eventbufferevent::sslrenegotiate' => 
  array (
    0 => 'void',
  ),
  'eventbufferevent::sslgetcipherinfo' => 
  array (
    0 => 'string',
  ),
  'eventbufferevent::sslgetciphername' => 
  array (
    0 => 'string',
  ),
  'eventbufferevent::sslgetcipherversion' => 
  array (
    0 => 'string',
  ),
  'eventbufferevent::sslgetprotocol' => 
  array (
    0 => 'string',
  ),
  'eventbuffer::__construct' => 
  array (
    0 => 'void',
  ),
  'eventbuffer::freeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'eventbuffer::unfreeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'eventbuffer::lock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
  ),
  'eventbuffer::unlock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
  ),
  'eventbuffer::enablelocking' => 
  array (
    0 => 'void',
  ),
  'eventbuffer::add' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'eventbuffer::read' => 
  array (
    0 => 'string',
    'max_bytes' => 'int',
  ),
  'eventbuffer::addbuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'eventbuffer::appendfrom' => 
  array (
    0 => 'int',
    'buf' => 'EventBuffer',
    'len' => 'int',
  ),
  'eventbuffer::expand' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'eventbuffer::prepend' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'eventbuffer::prependbuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'eventbuffer::drain' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'eventbuffer::copyout' => 
  array (
    0 => 'int',
    '&data' => 'string',
    'max_bytes' => 'int',
  ),
  'eventbuffer::readline' => 
  array (
    0 => 'string|null',
    'eol_style' => 'int',
  ),
  'eventbuffer::search' => 
  array (
    0 => 'int|false',
    'what' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'eventbuffer::searcheol' => 
  array (
    0 => 'int|false',
    'start=' => 'int',
    'eol_style=' => 'int',
  ),
  'eventbuffer::pullup' => 
  array (
    0 => 'string|null',
    'size' => 'int',
  ),
  'eventbuffer::write' => 
  array (
    0 => 'int|false',
    'fd' => 'mixed',
    'howmuch=' => 'int',
  ),
  'eventbuffer::readfrom' => 
  array (
    0 => 'int|false',
    'fd' => 'mixed',
    'howmuch=' => 'int',
  ),
  'eventbuffer::substr' => 
  array (
    0 => 'string|false',
    'start' => 'int',
    'length=' => 'int',
  ),
  'eventdnsbase::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'initialize' => 'mixed',
  ),
  'eventdnsbase::parseresolvconf' => 
  array (
    0 => 'bool',
    'flags' => 'int',
    'filename' => 'string',
  ),
  'eventdnsbase::addnameserverip' => 
  array (
    0 => 'bool',
    'ip' => 'string',
  ),
  'eventdnsbase::loadhosts' => 
  array (
    0 => 'bool',
    'hosts' => 'string',
  ),
  'eventdnsbase::clearsearch' => 
  array (
    0 => 'void',
  ),
  'eventdnsbase::addsearch' => 
  array (
    0 => 'void',
    'domain' => 'string',
  ),
  'eventdnsbase::setsearchndots' => 
  array (
    0 => 'void',
    'ndots' => 'int',
  ),
  'eventdnsbase::setoption' => 
  array (
    0 => 'bool',
    'option' => 'string',
    'value' => 'string',
  ),
  'eventdnsbase::countnameservers' => 
  array (
    0 => 'int',
  ),
  'eventlistener::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'cb' => 'callable',
    'data' => 'mixed',
    'flags' => 'int',
    'backlog' => 'int',
    'target' => 'mixed',
  ),
  'eventlistener::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventlistener::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventlistener::free' => 
  array (
    0 => 'void',
  ),
  'eventlistener::enable' => 
  array (
    0 => 'bool',
  ),
  'eventlistener::disable' => 
  array (
    0 => 'bool',
  ),
  'eventlistener::setcallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'eventlistener::seterrorcallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
  ),
  'eventlistener::getbase' => 
  array (
    0 => 'EventBase',
  ),
  'eventlistener::getsocketname' => 
  array (
    0 => 'bool',
    '&address' => 'mixed',
    '&port' => 'mixed',
  ),
  'eventhttpconnection::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'dns_base' => 'EventDnsBase|null',
    'address' => 'string',
    'port' => 'int',
    'ctx=' => 'EventSslContext|null',
  ),
  'eventhttpconnection::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventhttpconnection::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventhttpconnection::getbase' => 
  array (
    0 => 'EventBase|false',
  ),
  'eventhttpconnection::getpeer' => 
  array (
    0 => 'void',
    '&address' => 'mixed',
    '&port' => 'mixed',
  ),
  'eventhttpconnection::setlocaladdress' => 
  array (
    0 => 'void',
    'address' => 'string',
  ),
  'eventhttpconnection::setlocalport' => 
  array (
    0 => 'void',
    'port' => 'int',
  ),
  'eventhttpconnection::settimeout' => 
  array (
    0 => 'void',
    'timeout' => 'int',
  ),
  'eventhttpconnection::setmaxheaderssize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'eventhttpconnection::setmaxbodysize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'eventhttpconnection::setretries' => 
  array (
    0 => 'void',
    'retries' => 'int',
  ),
  'eventhttpconnection::makerequest' => 
  array (
    0 => 'bool|null',
    'req' => 'EventHttpRequest',
    'type' => 'int',
    'uri' => 'string',
  ),
  'eventhttpconnection::setclosecallback' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    'data=' => 'mixed',
  ),
  'eventhttp::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'ctx=' => 'EventSslContext|null',
  ),
  'eventhttp::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventhttp::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventhttp::accept' => 
  array (
    0 => 'bool',
    'socket' => 'mixed',
  ),
  'eventhttp::bind' => 
  array (
    0 => 'bool',
    'address' => 'string',
    'port' => 'int',
  ),
  'eventhttp::setcallback' => 
  array (
    0 => 'bool',
    'path' => 'string',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'eventhttp::setdefaultcallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'eventhttp::setallowedmethods' => 
  array (
    0 => 'void',
    'methods' => 'int',
  ),
  'eventhttp::setmaxbodysize' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'eventhttp::setmaxheaderssize' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'eventhttp::settimeout' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'eventhttp::addserveralias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'eventhttp::removeserveralias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'eventhttprequest::__construct' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    'data=' => 'mixed',
  ),
  'eventhttprequest::__sleep' => 
  array (
    0 => 'array',
  ),
  'eventhttprequest::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::free' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::getcommand' => 
  array (
    0 => 'int',
  ),
  'eventhttprequest::gethost' => 
  array (
    0 => 'string',
  ),
  'eventhttprequest::geturi' => 
  array (
    0 => 'string',
  ),
  'eventhttprequest::getresponsecode' => 
  array (
    0 => 'int',
  ),
  'eventhttprequest::getinputheaders' => 
  array (
    0 => 'array',
  ),
  'eventhttprequest::getoutputheaders' => 
  array (
    0 => 'array',
  ),
  'eventhttprequest::getinputbuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventhttprequest::getoutputbuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventhttprequest::getbufferevent' => 
  array (
    0 => 'EventBufferEvent|null',
  ),
  'eventhttprequest::getconnection' => 
  array (
    0 => 'EventHttpConnection|null',
  ),
  'eventhttprequest::closeconnection' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::senderror' => 
  array (
    0 => 'void',
    'error' => 'int',
    'reason=' => 'string|null',
  ),
  'eventhttprequest::sendreply' => 
  array (
    0 => 'void',
    'code' => 'int',
    'reason' => 'string',
    'buf=' => 'EventBuffer|null',
  ),
  'eventhttprequest::sendreplychunk' => 
  array (
    0 => 'void',
    'buf' => 'EventBuffer',
  ),
  'eventhttprequest::sendreplyend' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::sendreplystart' => 
  array (
    0 => 'void',
    'code' => 'int',
    'reason' => 'string',
  ),
  'eventhttprequest::cancel' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::addheader' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'value' => 'string',
    'type' => 'int',
  ),
  'eventhttprequest::clearheaders' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::removeheader' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'type' => 'int',
  ),
  'eventhttprequest::findheader' => 
  array (
    0 => 'string|null',
    'key' => 'string',
    'type' => 'int',
  ),
  'eventutil::__construct' => 
  array (
    0 => 'void',
  ),
  'eventutil::getlastsocketerrno' => 
  array (
    0 => 'int|false',
    'socket=' => 'Socket|null',
  ),
  'eventutil::getlastsocketerror' => 
  array (
    0 => 'string|false',
    'socket=' => 'mixed',
  ),
  'eventutil::sslrandpoll' => 
  array (
    0 => 'bool',
  ),
  'eventutil::getsocketname' => 
  array (
    0 => 'bool',
    'socket' => 'mixed',
    '&address' => 'mixed',
    '&port=' => 'mixed',
  ),
  'eventutil::getsocketfd' => 
  array (
    0 => 'int',
    'socket' => 'mixed',
  ),
  'eventutil::setsocketoption' => 
  array (
    0 => 'bool',
    'socket' => 'mixed',
    'level' => 'int',
    'optname' => 'int',
    'optval' => 'mixed',
  ),
  'eventsslcontext::__construct' => 
  array (
    0 => 'void',
    'method' => 'int',
    'options' => 'array',
  ),
  'eventsslcontext::setminprotoversion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'eventsslcontext::setmaxprotoversion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'eventexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'eventexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'eventexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'eventexception::getfile' => 
  array (
    0 => 'string',
  ),
  'eventexception::getline' => 
  array (
    0 => 'int',
  ),
  'eventexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'eventexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'eventexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'eventexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewrow::id' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\viewrow::key' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\viewrow::value' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\viewrow::document' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\baseexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\baseexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\baseexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\baseexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\baseexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\baseexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\baseexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\baseexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\baseexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\requestcanceledexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\requestcanceledexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\requestcanceledexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\requestcanceledexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\requestcanceledexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\requestcanceledexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\requestcanceledexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\requestcanceledexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\ratelimitedexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\ratelimitedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\ratelimitedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\ratelimitedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\ratelimitedexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\ratelimitedexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\ratelimitedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\ratelimitedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\quotalimitedexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\quotalimitedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\quotalimitedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\quotalimitedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\quotalimitedexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\quotalimitedexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\quotalimitedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\quotalimitedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\httpexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\httpexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\httpexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\httpexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\httpexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\httpexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\httpexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\httpexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\parsingfailureexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\parsingfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\parsingfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\parsingfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\parsingfailureexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\parsingfailureexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\parsingfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\parsingfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\indexnotfoundexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\indexnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\indexnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\indexnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\indexnotfoundexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\indexnotfoundexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\indexnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\indexnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\planningfailureexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\planningfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\planningfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\planningfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\planningfailureexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\planningfailureexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\planningfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\planningfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\indexfailureexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\indexfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\indexfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\indexfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\indexfailureexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\indexfailureexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\indexfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\indexfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\keyspacenotfoundexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\keyspacenotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyspacenotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyspacenotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\keyspacenotfoundexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\keyspacenotfoundexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\keyspacenotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyspacenotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\queryexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\queryexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\queryexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\queryexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\queryexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\queryerrorexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\queryerrorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryerrorexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryerrorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\queryerrorexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\queryerrorexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\queryerrorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryerrorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\dmlfailureexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\dmlfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\dmlfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\dmlfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\dmlfailureexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\dmlfailureexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\dmlfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\dmlfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\preparedstatementexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\preparedstatementexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\preparedstatementexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\preparedstatementexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\preparedstatementexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\preparedstatementexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\preparedstatementexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\preparedstatementexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\queryserviceexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\queryserviceexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryserviceexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryserviceexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\queryserviceexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\queryserviceexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\queryserviceexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryserviceexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\searchexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\searchexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\searchexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\searchexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\searchexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\searchexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\searchexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\analyticsexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\analyticsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\analyticsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\analyticsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\analyticsexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\analyticsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\analyticsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\viewexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\viewexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\viewexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\viewexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\viewexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\viewexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\viewexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\viewexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\partialviewexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\partialviewexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\partialviewexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\partialviewexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\partialviewexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\partialviewexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\partialviewexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\partialviewexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\bindingsexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\bindingsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\bindingsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\bindingsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\bindingsexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bindingsexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\bindingsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\bindingsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\invalidstateexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\invalidstateexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidstateexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidstateexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\invalidstateexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\invalidstateexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\invalidstateexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidstateexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\keyvalueexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\keyvalueexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyvalueexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\keyvalueexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\keyvalueexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\keyvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyvalueexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\documentnotfoundexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\documentnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\documentnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\documentnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\documentnotfoundexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\documentnotfoundexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\documentnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\documentnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\keyexistsexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\keyexistsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyexistsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyexistsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\keyexistsexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\keyexistsexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\keyexistsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyexistsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\valuetoobigexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\valuetoobigexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\valuetoobigexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\valuetoobigexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\valuetoobigexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\valuetoobigexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\valuetoobigexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\valuetoobigexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\keylockedexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\keylockedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keylockedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keylockedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\keylockedexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\keylockedexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\keylockedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keylockedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\tempfailexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\tempfailexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\tempfailexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\tempfailexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\tempfailexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\tempfailexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\tempfailexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\tempfailexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\pathnotfoundexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\pathnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\pathnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\pathnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\pathnotfoundexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\pathnotfoundexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\pathnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\pathnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\pathexistsexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\pathexistsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\pathexistsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\pathexistsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\pathexistsexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\pathexistsexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\pathexistsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\pathexistsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\invalidrangeexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\invalidrangeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidrangeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidrangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\invalidrangeexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\invalidrangeexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\invalidrangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidrangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\keydeletedexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\keydeletedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keydeletedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keydeletedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\keydeletedexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\keydeletedexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\keydeletedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keydeletedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\casmismatchexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\casmismatchexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\casmismatchexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\casmismatchexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\casmismatchexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\casmismatchexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\casmismatchexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\casmismatchexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\invalidconfigurationexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\invalidconfigurationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidconfigurationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidconfigurationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\invalidconfigurationexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\invalidconfigurationexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\invalidconfigurationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidconfigurationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\servicemissingexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\servicemissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\servicemissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\servicemissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\servicemissingexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\servicemissingexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\servicemissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\servicemissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\networkexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\networkexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\networkexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\networkexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\networkexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\networkexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\networkexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\networkexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\timeoutexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\timeoutexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\timeoutexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\timeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\timeoutexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\timeoutexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\timeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\timeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\bucketmissingexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\bucketmissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\bucketmissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\bucketmissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\bucketmissingexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketmissingexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\bucketmissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\bucketmissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\scopemissingexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\scopemissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\scopemissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\scopemissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\scopemissingexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\scopemissingexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\scopemissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\scopemissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\collectionmissingexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\collectionmissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\collectionmissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\collectionmissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\collectionmissingexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\collectionmissingexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\collectionmissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\collectionmissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\authenticationexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\authenticationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\authenticationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\authenticationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\authenticationexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\authenticationexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\authenticationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\authenticationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\badinputexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\badinputexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\badinputexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\badinputexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\badinputexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\badinputexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\badinputexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\badinputexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\durabilityexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\durabilityexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\durabilityexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\durabilityexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\durabilityexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\durabilityexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\durabilityexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\durabilityexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::ref' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\subdocumentexception::context' => 
  array (
    0 => 'object|null',
  ),
  'couchbase\\subdocumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\subdocumentexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\subdocumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\subdocumentexception::getfile' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::getline' => 
  array (
    0 => 'int',
  ),
  'couchbase\\subdocumentexception::gettrace' => 
  array (
    0 => 'array',
  ),
  'couchbase\\subdocumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\subdocumentexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::isprimary' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\queryindex::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::state' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::keyspace' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::indexkey' => 
  array (
    0 => 'array',
  ),
  'couchbase\\queryindex::condition' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\createqueryindexoptions::condition' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'condition' => 'string',
  ),
  'couchbase\\createqueryindexoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createqueryindexoptions::numreplicas' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'number' => 'int',
  ),
  'couchbase\\createqueryindexoptions::deferred' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'isDeferred' => 'bool',
  ),
  'couchbase\\createqueryprimaryindexoptions::indexname' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'name' => 'string',
  ),
  'couchbase\\createqueryprimaryindexoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createqueryprimaryindexoptions::numreplicas' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'number' => 'int',
  ),
  'couchbase\\createqueryprimaryindexoptions::deferred' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'isDeferred' => 'bool',
  ),
  'couchbase\\dropqueryindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropQueryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropqueryprimaryindexoptions::indexname' => 
  array (
    0 => 'Couchbase\\DropQueryPrimaryIndexOptions',
    'name' => 'string',
  ),
  'couchbase\\dropqueryprimaryindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropQueryPrimaryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\watchqueryindexesoptions::watchprimary' => 
  array (
    0 => 'Couchbase\\WatchQueryIndexesOptions',
    'shouldWatch' => 'bool',
  ),
  'couchbase\\queryindexmanager::getallindexes' => 
  array (
    0 => 'array',
    'bucketName' => 'string',
  ),
  'couchbase\\queryindexmanager::createindex' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'indexName' => 'string',
    'fields' => 'array',
    'options=' => 'Couchbase\\CreateQueryIndexOptions|null',
  ),
  'couchbase\\queryindexmanager::createprimaryindex' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'options=' => 'Couchbase\\CreateQueryPrimaryIndexOptions|null',
  ),
  'couchbase\\queryindexmanager::dropindex' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'indexName' => 'string',
    'options=' => 'Couchbase\\DropQueryIndexOptions|null',
  ),
  'couchbase\\queryindexmanager::dropprimaryindex' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'options=' => 'Couchbase\\DropQueryPrimaryIndexOptions|null',
  ),
  'couchbase\\queryindexmanager::watchindexes' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'indexNames' => 'array',
    'timeout' => 'int',
    'options=' => 'Couchbase\\WatchQueryIndexesOptions|null',
  ),
  'couchbase\\queryindexmanager::builddeferredindexes' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
  ),
  'couchbase\\createanalyticsdataverseoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDataverseOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticsdataverseoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDataverseOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticsdatasetoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDatasetOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticsdatasetoptions::condition' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDatasetOptions',
    'condition' => 'string',
  ),
  'couchbase\\createanalyticsdatasetoptions::dataversename' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDatasetOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\dropanalyticsdatasetoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDatasetOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticsdatasetoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDatasetOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\createanalyticsindexoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticsindexoptions::dataversename' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsIndexOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\dropanalyticsindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticsindexoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DropAnalyticsIndexOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\connectanalyticslinkoptions::linkname' => 
  array (
    0 => 'Couchbase\\ConnectAnalyticsLinkOptions',
    'linkName' => 'Couchbase\\bstring',
  ),
  'couchbase\\connectanalyticslinkoptions::dataversename' => 
  array (
    0 => 'Couchbase\\ConnectAnalyticsLinkOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\disconnectanalyticslinkoptions::linkname' => 
  array (
    0 => 'Couchbase\\DisconnectAnalyticsLinkOptions',
    'linkName' => 'Couchbase\\bstring',
  ),
  'couchbase\\disconnectanalyticslinkoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DisconnectAnalyticsLinkOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\createanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\replaceanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\ReplaceAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\dropanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\getanalyticslinksoptions::timeout' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\getanalyticslinksoptions::linktype' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'type' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::dataverse' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'dataverse' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::name' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'name' => 'string',
  ),
  'couchbase\\encryptionsettings::level' => 
  array (
    0 => 'mixed',
    'level' => 'string',
  ),
  'couchbase\\encryptionsettings::certificate' => 
  array (
    0 => 'mixed',
    'certificate' => 'string',
  ),
  'couchbase\\encryptionsettings::clientcertificate' => 
  array (
    0 => 'mixed',
    'certificate' => 'string',
  ),
  'couchbase\\encryptionsettings::clientkey' => 
  array (
    0 => 'mixed',
    'key' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::name' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::hostname' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'hostname' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::username' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'username' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::password' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'password' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::encryption' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'settings' => 'Couchbase\\EncryptionSettings',
  ),
  'couchbase\\azureblobexternalanalyticslink::name' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::connectionstring' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'connectionString' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::accountname' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'accountName' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::accountkey' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'accountKey' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::sharedaccesssignature' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'signature' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::blobendpoint' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'blobEndpoint' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::endpointsuffix' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'suffix' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::name' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::accesskeyid' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'accessKeyId' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::secretaccesskey' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'secretAccessKey' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::region' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'region' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::sessiontoken' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'sessionToken' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::serviceendpoint' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'serviceEndpoint' => 'string',
  ),
  'couchbase\\analyticsindexmanager::createdataverse' => 
  array (
    0 => 'mixed',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\CreateAnalyticsDataverseOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropdataverse' => 
  array (
    0 => 'mixed',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsDataverseOptions|null',
  ),
  'couchbase\\analyticsindexmanager::createdataset' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'bucketName' => 'string',
    'options=' => 'Couchbase\\CreateAnalyticsDatasetOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropdataset' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsDatasetOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getalldatasets' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::createindex' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'indexName' => 'string',
    'fields' => 'array',
    'options=' => 'Couchbase\\CreateAnalyticsIndexOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropindex' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'indexName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsIndexOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getallindexes' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::connectlink' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\ConnectAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::disconnectlink' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\DisconnectAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getpendingmutations' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::createlink' => 
  array (
    0 => 'mixed',
    'link' => 'Couchbase\\AnalyticsLink',
    'options=' => 'Couchbase\\CreateAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::replacelink' => 
  array (
    0 => 'mixed',
    'link' => 'Couchbase\\AnalyticsLink',
    'options=' => 'Couchbase\\ReplaceAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::droplink' => 
  array (
    0 => 'mixed',
    'linkName' => 'string',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getlinks' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\GetAnalyticsLinksOptions|null',
  ),
  'couchbase\\searchindex::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchindex::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::uuid' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::params' => 
  array (
    0 => 'array',
  ),
  'couchbase\\searchindex::sourcetype' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::sourceuuid' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::sourcename' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::sourceparams' => 
  array (
    0 => 'array',
  ),
  'couchbase\\searchindex::settype' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'type' => 'string',
  ),
  'couchbase\\searchindex::setuuid' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'uuid' => 'string',
  ),
  'couchbase\\searchindex::setparams' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'params' => 'string',
  ),
  'couchbase\\searchindex::setsourcetype' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'type' => 'string',
  ),
  'couchbase\\searchindex::setsourceuuid' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'uuid' => 'string',
  ),
  'couchbase\\searchindex::setsourcename' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'params' => 'string',
  ),
  'couchbase\\searchindex::setsourceparams' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'params' => 'string',
  ),
  'couchbase\\searchindexmanager::getindex' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'name' => 'string',
  ),
  'couchbase\\searchindexmanager::getallindexes' => 
  array (
    0 => 'array',
  ),
  'couchbase\\searchindexmanager::upsertindex' => 
  array (
    0 => 'mixed',
    'indexDefinition' => 'Couchbase\\SearchIndex',
  ),
  'couchbase\\searchindexmanager::dropindex' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\searchindexmanager::getindexeddocumentscount' => 
  array (
    0 => 'int',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::pauseingest' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::resumeingest' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::allowquerying' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::disallowquerying' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::freezeplan' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::unfreezeplan' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::analyzedocument' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
    'document' => 'mixed',
  ),
  'couchbase\\cluster::__construct' => 
  array (
    0 => 'void',
    'connstr' => 'string',
    'options' => 'Couchbase\\ClusterOptions',
  ),
  'couchbase\\cluster::bucket' => 
  array (
    0 => 'Couchbase\\Bucket',
    'name' => 'string',
  ),
  'couchbase\\cluster::query' => 
  array (
    0 => 'Couchbase\\QueryResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\QueryOptions|null',
  ),
  'couchbase\\cluster::analyticsquery' => 
  array (
    0 => 'Couchbase\\AnalyticsResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\AnalyticsOptions|null',
  ),
  'couchbase\\cluster::searchquery' => 
  array (
    0 => 'Couchbase\\SearchResult',
    'indexName' => 'string',
    'query' => 'Couchbase\\SearchQuery',
    'options=' => 'Couchbase\\SearchOptions|null',
  ),
  'couchbase\\cluster::buckets' => 
  array (
    0 => 'Couchbase\\BucketManager',
  ),
  'couchbase\\cluster::users' => 
  array (
    0 => 'Couchbase\\UserManager',
  ),
  'couchbase\\cluster::analyticsindexes' => 
  array (
    0 => 'Couchbase\\AnalyticsIndexManager',
  ),
  'couchbase\\cluster::queryindexes' => 
  array (
    0 => 'Couchbase\\QueryIndexManager',
  ),
  'couchbase\\cluster::searchindexes' => 
  array (
    0 => 'Couchbase\\SearchIndexManager',
  ),
  'couchbase\\bucketsettings::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::flushenabled' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\bucketsettings::ramquotamb' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::numreplicas' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::replicaindexes' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\bucketsettings::buckettype' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::evictionpolicy' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::storagebackend' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::maxttl' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::compressionmode' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::setname' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'name' => 'string',
  ),
  'couchbase\\bucketsettings::enableflush' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'enable' => 'bool',
  ),
  'couchbase\\bucketsettings::setramquotamb' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'sizeInMb' => 'int',
  ),
  'couchbase\\bucketsettings::setnumreplicas' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'numReplicas' => 'int',
  ),
  'couchbase\\bucketsettings::enablereplicaindexes' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'enable' => 'bool',
  ),
  'couchbase\\bucketsettings::setbuckettype' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'type' => 'string',
  ),
  'couchbase\\bucketsettings::setevictionpolicy' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'policy' => 'string',
  ),
  'couchbase\\bucketsettings::setstoragebackend' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'policy' => 'string',
  ),
  'couchbase\\bucketsettings::setmaxttl' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'ttlSeconds' => 'int',
  ),
  'couchbase\\bucketsettings::setcompressionmode' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'mode' => 'string',
  ),
  'couchbase\\bucketsettings::minimaldurabilitylevel' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::setminimaldurabilitylevel' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'durabilityLevel' => 'int',
  ),
  'couchbase\\bucketmanager::createbucket' => 
  array (
    0 => 'mixed',
    'settings' => 'Couchbase\\BucketSettings',
  ),
  'couchbase\\bucketmanager::removebucket' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\bucketmanager::getbucket' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'name' => 'string',
  ),
  'couchbase\\bucketmanager::getallbuckets' => 
  array (
    0 => 'array',
  ),
  'couchbase\\bucketmanager::flush' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\role::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\role::bucket' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\role::scope' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\role::collection' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\role::setname' => 
  array (
    0 => 'Couchbase\\Role',
    'name' => 'string',
  ),
  'couchbase\\role::setbucket' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\role::setscope' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\role::setcollection' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\roleanddescription::role' => 
  array (
    0 => 'Couchbase\\Role',
  ),
  'couchbase\\roleanddescription::displayname' => 
  array (
    0 => 'string',
  ),
  'couchbase\\roleanddescription::description' => 
  array (
    0 => 'string',
  ),
  'couchbase\\origin::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\origin::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\roleandorigin::role' => 
  array (
    0 => 'Couchbase\\Role',
  ),
  'couchbase\\roleandorigin::origins' => 
  array (
    0 => 'array',
  ),
  'couchbase\\user::username' => 
  array (
    0 => 'string',
  ),
  'couchbase\\user::displayname' => 
  array (
    0 => 'string',
  ),
  'couchbase\\user::groups' => 
  array (
    0 => 'array',
  ),
  'couchbase\\user::roles' => 
  array (
    0 => 'array',
  ),
  'couchbase\\user::setusername' => 
  array (
    0 => 'Couchbase\\User',
    'username' => 'string',
  ),
  'couchbase\\user::setpassword' => 
  array (
    0 => 'Couchbase\\User',
    'password' => 'string',
  ),
  'couchbase\\user::setdisplayname' => 
  array (
    0 => 'Couchbase\\User',
    'name' => 'string',
  ),
  'couchbase\\user::setgroups' => 
  array (
    0 => 'Couchbase\\User',
    'groups' => 'array',
  ),
  'couchbase\\user::setroles' => 
  array (
    0 => 'Couchbase\\User',
    'roles' => 'array',
  ),
  'couchbase\\group::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\group::description' => 
  array (
    0 => 'string',
  ),
  'couchbase\\group::roles' => 
  array (
    0 => 'array',
  ),
  'couchbase\\group::ldapgroupreference' => 
  array (
    0 => 'string|null',
  ),
  'couchbase\\group::setname' => 
  array (
    0 => 'Couchbase\\Group',
    'name' => 'string',
  ),
  'couchbase\\group::setdescription' => 
  array (
    0 => 'Couchbase\\Group',
    'description' => 'string',
  ),
  'couchbase\\group::setroles' => 
  array (
    0 => 'Couchbase\\Group',
    'roles' => 'array',
  ),
  'couchbase\\userandmetadata::domain' => 
  array (
    0 => 'string',
  ),
  'couchbase\\userandmetadata::user' => 
  array (
    0 => 'Couchbase\\User',
  ),
  'couchbase\\userandmetadata::effectiveroles' => 
  array (
    0 => 'array',
  ),
  'couchbase\\userandmetadata::passwordchanged' => 
  array (
    0 => 'string',
  ),
  'couchbase\\userandmetadata::externalgroups' => 
  array (
    0 => 'array',
  ),
  'couchbase\\getallusersoptions::domainname' => 
  array (
    0 => 'Couchbase\\GetAllUsersOptions',
    'name' => 'string',
  ),
  'couchbase\\getuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\GetUserOptions',
    'name' => 'string',
  ),
  'couchbase\\dropuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\DropUserOptions',
    'name' => 'string',
  ),
  'couchbase\\upsertuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\DropUserOptions',
    'name' => 'string',
  ),
  'couchbase\\usermanager::getuser' => 
  array (
    0 => 'Couchbase\\UserAndMetadata',
    'name' => 'string',
    'options=' => 'Couchbase\\GetUserOptions|null',
  ),
  'couchbase\\usermanager::getallusers' => 
  array (
    0 => 'array',
    'options=' => 'Couchbase\\GetAllUsersOptions|null',
  ),
  'couchbase\\usermanager::upsertuser' => 
  array (
    0 => 'mixed',
    'user' => 'Couchbase\\User',
    'options=' => 'Couchbase\\UpsertUserOptions|null',
  ),
  'couchbase\\usermanager::dropuser' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'options=' => 'Couchbase\\DropUserOptions|null',
  ),
  'couchbase\\usermanager::getroles' => 
  array (
    0 => 'array',
  ),
  'couchbase\\usermanager::getgroup' => 
  array (
    0 => 'Couchbase\\Group',
    'name' => 'string',
  ),
  'couchbase\\usermanager::getallgroups' => 
  array (
    0 => 'array',
  ),
  'couchbase\\usermanager::upsertgroup' => 
  array (
    0 => 'mixed',
    'group' => 'Couchbase\\Group',
  ),
  'couchbase\\usermanager::dropgroup' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\binarycollection::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\binarycollection::append' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'string',
    'options=' => 'Couchbase\\AppendOptions|null',
  ),
  'couchbase\\binarycollection::prepend' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'string',
    'options=' => 'Couchbase\\PrependOptions|null',
  ),
  'couchbase\\binarycollection::increment' => 
  array (
    0 => 'Couchbase\\CounterResult',
    'id' => 'string',
    'options=' => 'Couchbase\\IncrementOptions|null',
  ),
  'couchbase\\binarycollection::decrement' => 
  array (
    0 => 'Couchbase\\CounterResult',
    'id' => 'string',
    'options=' => 'Couchbase\\DecrementOptions|null',
  ),
  'couchbase\\collection::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collection::get' => 
  array (
    0 => 'Couchbase\\GetResult',
    'id' => 'string',
    'options=' => 'Couchbase\\GetOptions|null',
  ),
  'couchbase\\collection::exists' => 
  array (
    0 => 'Couchbase\\ExistsResult',
    'id' => 'string',
    'options=' => 'Couchbase\\ExistsOptions|null',
  ),
  'couchbase\\collection::getandlock' => 
  array (
    0 => 'Couchbase\\GetResult',
    'id' => 'string',
    'lockTime' => 'int',
    'options=' => 'Couchbase\\GetAndLockOptions|null',
  ),
  'couchbase\\collection::getandtouch' => 
  array (
    0 => 'Couchbase\\GetResult',
    'id' => 'string',
    'expiry' => 'int',
    'options=' => 'Couchbase\\GetAndTouchOptions|null',
  ),
  'couchbase\\collection::getanyreplica' => 
  array (
    0 => 'Couchbase\\GetReplicaResult',
    'id' => 'string',
    'options=' => 'Couchbase\\GetAnyReplicaOptions|null',
  ),
  'couchbase\\collection::getallreplicas' => 
  array (
    0 => 'array',
    'id' => 'string',
    'options=' => 'Couchbase\\GetAllReplicasOptions|null',
  ),
  'couchbase\\collection::upsert' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\UpsertOptions|null',
  ),
  'couchbase\\collection::insert' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\InsertOptions|null',
  ),
  'couchbase\\collection::replace' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\ReplaceOptions|null',
  ),
  'couchbase\\collection::remove' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::unlock' => 
  array (
    0 => 'Couchbase\\Result',
    'id' => 'string',
    'cas' => 'string',
    'options=' => 'Couchbase\\UnlockOptions|null',
  ),
  'couchbase\\collection::touch' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'expiry' => 'int',
    'options=' => 'Couchbase\\TouchOptions|null',
  ),
  'couchbase\\collection::lookupin' => 
  array (
    0 => 'Couchbase\\LookupInResult',
    'id' => 'string',
    'specs' => 'array',
    'options=' => 'Couchbase\\LookupInOptions|null',
  ),
  'couchbase\\collection::mutatein' => 
  array (
    0 => 'Couchbase\\MutateInResult',
    'id' => 'string',
    'specs' => 'array',
    'options=' => 'Couchbase\\MutateInOptions|null',
  ),
  'couchbase\\collection::getmulti' => 
  array (
    0 => 'array',
    'ids' => 'array',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::removemulti' => 
  array (
    0 => 'array',
    'entries' => 'array',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::upsertmulti' => 
  array (
    0 => 'array',
    'entries' => 'array',
    'options=' => 'Couchbase\\UpsertOptions|null',
  ),
  'couchbase\\collection::binary' => 
  array (
    0 => 'Couchbase\\BinaryCollection',
  ),
  'couchbase\\scope::__construct' => 
  array (
    0 => 'void',
    'bucket' => 'Couchbase\\Bucket',
    'name' => 'string',
  ),
  'couchbase\\scope::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scope::collection' => 
  array (
    0 => 'Couchbase\\Collection',
    'name' => 'string',
  ),
  'couchbase\\scope::query' => 
  array (
    0 => 'Couchbase\\QueryResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\QueryOptions|null',
  ),
  'couchbase\\scope::analyticsquery' => 
  array (
    0 => 'Couchbase\\AnalyticsResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\AnalyticsOptions|null',
  ),
  'couchbase\\scopespec::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopespec::collections' => 
  array (
    0 => 'array',
  ),
  'couchbase\\collectionspec::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionspec::scopename' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionspec::setname' => 
  array (
    0 => 'Couchbase\\CollectionSpec',
    'name' => 'string',
  ),
  'couchbase\\collectionspec::setscopename' => 
  array (
    0 => 'Couchbase\\CollectionSpec',
    'name' => 'string',
  ),
  'couchbase\\collectionspec::setmaxexpiry' => 
  array (
    0 => 'Couchbase\\CollectionSpec',
    'ms' => 'int',
  ),
  'couchbase\\collectionmanager::getscope' => 
  array (
    0 => 'Couchbase\\ScopeSpec',
    'name' => 'string',
  ),
  'couchbase\\collectionmanager::getallscopes' => 
  array (
    0 => 'array',
  ),
  'couchbase\\collectionmanager::createscope' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\collectionmanager::dropscope' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\collectionmanager::createcollection' => 
  array (
    0 => 'mixed',
    'collection' => 'Couchbase\\CollectionSpec',
  ),
  'couchbase\\collectionmanager::dropcollection' => 
  array (
    0 => 'mixed',
    'collection' => 'Couchbase\\CollectionSpec',
  ),
  'couchbase\\bucket::defaultscope' => 
  array (
    0 => 'Couchbase\\Scope',
  ),
  'couchbase\\bucket::defaultcollection' => 
  array (
    0 => 'Couchbase\\Collection',
  ),
  'couchbase\\bucket::scope' => 
  array (
    0 => 'Couchbase\\Scope',
    'name' => 'string',
  ),
  'couchbase\\bucket::settranscoder' => 
  array (
    0 => 'mixed',
    'encoder' => 'callable',
    'decoder' => 'callable',
  ),
  'couchbase\\bucket::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucket::viewquery' => 
  array (
    0 => 'Couchbase\\ViewResult',
    'designDoc' => 'string',
    'viewName' => 'string',
    'options=' => 'Couchbase\\ViewOptions|null',
  ),
  'couchbase\\bucket::collections' => 
  array (
    0 => 'Couchbase\\CollectionManager',
  ),
  'couchbase\\bucket::viewindexes' => 
  array (
    0 => 'Couchbase\\ViewIndexManager',
  ),
  'couchbase\\bucket::ping' => 
  array (
    0 => 'mixed',
    'services' => 'mixed',
    'reportId' => 'mixed',
  ),
  'couchbase\\bucket::diagnostics' => 
  array (
    0 => 'mixed',
    'reportId' => 'mixed',
  ),
  'couchbase\\view::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::map' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::reduce' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::setname' => 
  array (
    0 => 'Couchbase\\View',
    'name' => 'string',
  ),
  'couchbase\\view::setmap' => 
  array (
    0 => 'Couchbase\\View',
    'mapJsCode' => 'string',
  ),
  'couchbase\\view::setreduce' => 
  array (
    0 => 'Couchbase\\View',
    'reduceJsCode' => 'string',
  ),
  'couchbase\\designdocument::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\designdocument::views' => 
  array (
    0 => 'array',
  ),
  'couchbase\\designdocument::setname' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'name' => 'string',
  ),
  'couchbase\\designdocument::setviews' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'views' => 'array',
  ),
  'couchbase\\viewindexmanager::getalldesigndocuments' => 
  array (
    0 => 'array',
  ),
  'couchbase\\viewindexmanager::getdesigndocument' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'name' => 'string',
  ),
  'couchbase\\viewindexmanager::dropdesigndocument' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\viewindexmanager::upsertdesigndocument' => 
  array (
    0 => 'mixed',
    'document' => 'Couchbase\\DesignDocument',
  ),
  'couchbase\\mutationstate::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\mutationstate::add' => 
  array (
    0 => 'Couchbase\\MutationState',
    'source' => 'Couchbase\\MutationResult',
  ),
  'couchbase\\analyticsoptions::timeout' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'arg' => 'int',
  ),
  'couchbase\\analyticsoptions::namedparameters' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'pairs' => 'array',
  ),
  'couchbase\\analyticsoptions::positionalparameters' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'args' => 'array',
  ),
  'couchbase\\analyticsoptions::raw' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'key' => 'string',
    'value' => 'mixed',
  ),
  'couchbase\\analyticsoptions::clientcontextid' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'value' => 'string',
  ),
  'couchbase\\analyticsoptions::priority' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'urgent' => 'bool',
  ),
  'couchbase\\analyticsoptions::readonly' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'arg' => 'bool',
  ),
  'couchbase\\analyticsoptions::scanconsistency' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'arg' => 'string',
  ),
  'couchbase\\lookupgetspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr=' => 'bool',
  ),
  'couchbase\\lookupcountspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr=' => 'bool',
  ),
  'couchbase\\lookupexistsspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr=' => 'bool',
  ),
  'couchbase\\lookupgetfullspec::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\mutateinsertspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'value' => 'mixed',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutateupsertspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'value' => 'mixed',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatereplacespec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'value' => 'mixed',
    'isXattr' => 'bool',
  ),
  'couchbase\\mutateremovespec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr' => 'bool',
  ),
  'couchbase\\mutatearrayappendspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatearrayprependspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatearrayinsertspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatearrayadduniquespec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'value' => 'mixed',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatecounterspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'delta' => 'int',
    'isXattr' => 'bool',
    'createPath' => 'bool',
  ),
  'couchbase\\searchoptions::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchoptions::timeout' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'ms' => 'int',
  ),
  'couchbase\\searchoptions::limit' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'limit' => 'int',
  ),
  'couchbase\\searchoptions::skip' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'skip' => 'int',
  ),
  'couchbase\\searchoptions::explain' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'explain' => 'bool',
  ),
  'couchbase\\searchoptions::disablescoring' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'disabled' => 'bool',
  ),
  'couchbase\\searchoptions::consistentwith' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'index' => 'string',
    'state' => 'Couchbase\\MutationState',
  ),
  'couchbase\\searchoptions::fields' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'fields' => 'array',
  ),
  'couchbase\\searchoptions::facets' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'facets' => 'array',
  ),
  'couchbase\\searchoptions::sort' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'specs' => 'array',
  ),
  'couchbase\\searchoptions::highlight' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'style=' => 'string|null',
    'fields=' => 'array|null',
  ),
  'couchbase\\searchoptions::collections' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'collectionNames' => 'array',
  ),
  'couchbase\\booleanfieldsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\booleanfieldsearchquery::__construct' => 
  array (
    0 => 'void',
    'arg' => 'bool',
  ),
  'couchbase\\booleanfieldsearchquery::boost' => 
  array (
    0 => 'Couchbase\\BooleanFieldSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\booleanfieldsearchquery::field' => 
  array (
    0 => 'Couchbase\\BooleanFieldSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\booleansearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\booleansearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\booleansearchquery::boost' => 
  array (
    0 => 'Couchbase\\BooleanSearchQuery',
    'boost' => 'mixed',
  ),
  'couchbase\\booleansearchquery::must' => 
  array (
    0 => 'Couchbase\\BooleanSearchQuery',
    'query' => 'Couchbase\\ConjunctionSearchQuery',
  ),
  'couchbase\\booleansearchquery::mustnot' => 
  array (
    0 => 'Couchbase\\BooleanSearchQuery',
    'query' => 'Couchbase\\DisjunctionSearchQuery',
  ),
  'couchbase\\booleansearchquery::should' => 
  array (
    0 => 'Couchbase\\BooleanSearchQuery',
    'query' => 'Couchbase\\DisjunctionSearchQuery',
  ),
  'couchbase\\conjunctionsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\conjunctionsearchquery::__construct' => 
  array (
    0 => 'void',
    'queries' => 'array',
  ),
  'couchbase\\conjunctionsearchquery::boost' => 
  array (
    0 => 'Couchbase\\ConjunctionSearchQuery',
    'boost' => 'mixed',
  ),
  'couchbase\\conjunctionsearchquery::every' => 
  array (
    0 => 'Couchbase\\ConjunctionSearchQuery',
    '...queries=' => 'Couchbase\\SearchQuery',
  ),
  'couchbase\\daterangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\daterangesearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\daterangesearchquery::boost' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\daterangesearchquery::field' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\daterangesearchquery::start' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'start' => 'mixed',
    'inclusive=' => 'bool',
  ),
  'couchbase\\daterangesearchquery::end' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'end' => 'mixed',
    'inclusive=' => 'bool',
  ),
  'couchbase\\daterangesearchquery::datetimeparser' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'dateTimeParser' => 'string',
  ),
  'couchbase\\disjunctionsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\disjunctionsearchquery::__construct' => 
  array (
    0 => 'void',
    'queries' => 'array',
  ),
  'couchbase\\disjunctionsearchquery::boost' => 
  array (
    0 => 'Couchbase\\DisjunctionSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\disjunctionsearchquery::either' => 
  array (
    0 => 'Couchbase\\DisjunctionSearchQuery',
    '...queries=' => 'Couchbase\\SearchQuery',
  ),
  'couchbase\\disjunctionsearchquery::min' => 
  array (
    0 => 'Couchbase\\DisjunctionSearchQuery',
    'min' => 'int',
  ),
  'couchbase\\docidsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\docidsearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\docidsearchquery::boost' => 
  array (
    0 => 'Couchbase\\DocIdSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\docidsearchquery::field' => 
  array (
    0 => 'Couchbase\\DocIdSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\docidsearchquery::docids' => 
  array (
    0 => 'Couchbase\\DocIdSearchQuery',
    '...documentIds=' => 'string',
  ),
  'couchbase\\geoboundingboxsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\geoboundingboxsearchquery::__construct' => 
  array (
    0 => 'void',
    'top_left_longitude' => 'float',
    'top_left_latitude' => 'float',
    'buttom_right_longitude' => 'float',
    'buttom_right_latitude' => 'float',
  ),
  'couchbase\\geoboundingboxsearchquery::boost' => 
  array (
    0 => 'Couchbase\\GeoBoundingBoxSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\geoboundingboxsearchquery::field' => 
  array (
    0 => 'Couchbase\\GeoBoundingBoxSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\geodistancesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\geodistancesearchquery::__construct' => 
  array (
    0 => 'void',
    'longitude' => 'float',
    'latitude' => 'float',
    'distance=' => 'string|null',
  ),
  'couchbase\\geodistancesearchquery::boost' => 
  array (
    0 => 'Couchbase\\GeoDistanceSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\geodistancesearchquery::field' => 
  array (
    0 => 'Couchbase\\GeoDistanceSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\coordinate::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\coordinate::__construct' => 
  array (
    0 => 'void',
    'longitude' => 'float',
    'latitude' => 'float',
  ),
  'couchbase\\geopolygonquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\geopolygonquery::__construct' => 
  array (
    0 => 'void',
    'coordinates' => 'array',
  ),
  'couchbase\\geopolygonquery::boost' => 
  array (
    0 => 'Couchbase\\GeoPolygonQuery',
    'boost' => 'float',
  ),
  'couchbase\\geopolygonquery::field' => 
  array (
    0 => 'Couchbase\\GeoPolygonQuery',
    'field' => 'string',
  ),
  'couchbase\\matchallsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchallsearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\matchallsearchquery::boost' => 
  array (
    0 => 'Couchbase\\MatchAllSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\matchnonesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchnonesearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\matchnonesearchquery::boost' => 
  array (
    0 => 'Couchbase\\MatchNoneSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\matchphrasesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchphrasesearchquery::__construct' => 
  array (
    0 => 'void',
    'value' => 'string',
  ),
  'couchbase\\matchphrasesearchquery::boost' => 
  array (
    0 => 'Couchbase\\MatchPhraseSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\matchphrasesearchquery::field' => 
  array (
    0 => 'Couchbase\\MatchPhraseSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\matchphrasesearchquery::analyzer' => 
  array (
    0 => 'Couchbase\\MatchPhraseSearchQuery',
    'analyzer' => 'string',
  ),
  'couchbase\\matchsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchsearchquery::__construct' => 
  array (
    0 => 'void',
    'value' => 'string',
  ),
  'couchbase\\matchsearchquery::boost' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\matchsearchquery::field' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\matchsearchquery::analyzer' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'analyzer' => 'string',
  ),
  'couchbase\\matchsearchquery::prefixlength' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'prefixLength' => 'int',
  ),
  'couchbase\\matchsearchquery::fuzziness' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'fuzziness' => 'int',
  ),
  'couchbase\\numericrangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\numericrangesearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\numericrangesearchquery::boost' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\numericrangesearchquery::field' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'field' => 'mixed',
  ),
  'couchbase\\numericrangesearchquery::min' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'min' => 'float',
    'inclusive=' => 'bool',
  ),
  'couchbase\\numericrangesearchquery::max' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'max' => 'float',
    'inclusive=' => 'bool',
  ),
  'couchbase\\phrasesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\phrasesearchquery::__construct' => 
  array (
    0 => 'void',
    '...terms=' => 'string',
  ),
  'couchbase\\phrasesearchquery::boost' => 
  array (
    0 => 'Couchbase\\PhraseSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\phrasesearchquery::field' => 
  array (
    0 => 'Couchbase\\PhraseSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\prefixsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\prefixsearchquery::__construct' => 
  array (
    0 => 'void',
    'prefix' => 'string',
  ),
  'couchbase\\prefixsearchquery::boost' => 
  array (
    0 => 'Couchbase\\PrefixSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\prefixsearchquery::field' => 
  array (
    0 => 'Couchbase\\PrefixSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\querystringsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\querystringsearchquery::__construct' => 
  array (
    0 => 'void',
    'query_string' => 'string',
  ),
  'couchbase\\querystringsearchquery::boost' => 
  array (
    0 => 'Couchbase\\QueryStringSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\regexpsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\regexpsearchquery::__construct' => 
  array (
    0 => 'void',
    'regexp' => 'string',
  ),
  'couchbase\\regexpsearchquery::boost' => 
  array (
    0 => 'Couchbase\\RegexpSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\regexpsearchquery::field' => 
  array (
    0 => 'Couchbase\\RegexpSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\termsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\termsearchquery::__construct' => 
  array (
    0 => 'void',
    'term' => 'string',
  ),
  'couchbase\\termsearchquery::boost' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\termsearchquery::field' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\termsearchquery::prefixlength' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'prefixLength' => 'int',
  ),
  'couchbase\\termsearchquery::fuzziness' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'fuzziness' => 'int',
  ),
  'couchbase\\termrangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\termrangesearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\termrangesearchquery::boost' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\termrangesearchquery::field' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\termrangesearchquery::min' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'min' => 'string',
    'inclusive=' => 'bool',
  ),
  'couchbase\\termrangesearchquery::max' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'max' => 'string',
    'inclusive=' => 'bool',
  ),
  'couchbase\\wildcardsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\wildcardsearchquery::__construct' => 
  array (
    0 => 'void',
    'wildcard' => 'string',
  ),
  'couchbase\\wildcardsearchquery::boost' => 
  array (
    0 => 'Couchbase\\WildcardSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\wildcardsearchquery::field' => 
  array (
    0 => 'Couchbase\\WildcardSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\termsearchfacet::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\termsearchfacet::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
    'limit' => 'int',
  ),
  'couchbase\\numericrangesearchfacet::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\numericrangesearchfacet::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
    'limit' => 'int',
  ),
  'couchbase\\numericrangesearchfacet::addrange' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchFacet',
    'name' => 'string',
    'min=' => 'float|null',
    'max=' => 'float|null',
  ),
  'couchbase\\daterangesearchfacet::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\daterangesearchfacet::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
    'limit' => 'int',
  ),
  'couchbase\\daterangesearchfacet::addrange' => 
  array (
    0 => 'Couchbase\\DateRangeSearchFacet',
    'name' => 'string',
    'start=' => 'mixed',
    'end=' => 'mixed',
  ),
  'couchbase\\searchsortfield::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortfield::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
  ),
  'couchbase\\searchsortfield::descending' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'descending' => 'bool',
  ),
  'couchbase\\searchsortfield::type' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'type' => 'string',
  ),
  'couchbase\\searchsortfield::mode' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'mode' => 'string',
  ),
  'couchbase\\searchsortfield::missing' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'missing' => 'string',
  ),
  'couchbase\\searchsortgeodistance::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortgeodistance::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
    'logitude' => 'float',
    'latitude' => 'float',
  ),
  'couchbase\\searchsortgeodistance::descending' => 
  array (
    0 => 'Couchbase\\SearchSortGeoDistance',
    'descending' => 'bool',
  ),
  'couchbase\\searchsortgeodistance::unit' => 
  array (
    0 => 'Couchbase\\SearchSortGeoDistance',
    'unit' => 'string',
  ),
  'couchbase\\searchsortid::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortid::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\searchsortid::descending' => 
  array (
    0 => 'Couchbase\\SearchSortId',
    'descending' => 'bool',
  ),
  'couchbase\\searchsortscore::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortscore::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\searchsortscore::descending' => 
  array (
    0 => 'Couchbase\\SearchSortScore',
    'descending' => 'bool',
  ),
  'couchbase\\getoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'int',
  ),
  'couchbase\\getoptions::withexpiry' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'bool',
  ),
  'couchbase\\getoptions::project' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'array',
  ),
  'couchbase\\getoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getandtouchoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAndTouchOptions',
    'arg' => 'int',
  ),
  'couchbase\\getandtouchoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAndTouchOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getandlockoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAndLockOptions',
    'arg' => 'int',
  ),
  'couchbase\\getandlockoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAndLockOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getallreplicasoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAllReplicasOptions',
    'arg' => 'int',
  ),
  'couchbase\\getallreplicasoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAllReplicasOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getanyreplicaoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAnyReplicaOptions',
    'arg' => 'int',
  ),
  'couchbase\\getanyreplicaoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAnyReplicaOptions',
    'arg' => 'callable',
  ),
  'couchbase\\existsoptions::timeout' => 
  array (
    0 => 'Couchbase\\ExistsOptions',
    'arg' => 'int',
  ),
  'couchbase\\unlockoptions::timeout' => 
  array (
    0 => 'Couchbase\\UnlockOptions',
    'arg' => 'int',
  ),
  'couchbase\\insertoptions::timeout' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\insertoptions::expiry' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\insertoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\insertoptions::encoder' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'callable',
  ),
  'couchbase\\upsertoptions::timeout' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\upsertoptions::expiry' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\upsertoptions::preserveexpiry' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'shouldPreserve' => 'bool',
  ),
  'couchbase\\upsertoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\upsertoptions::encoder' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'arg' => 'callable',
  ),
  'couchbase\\replaceoptions::timeout' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'int',
  ),
  'couchbase\\replaceoptions::expiry' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\replaceoptions::preserveexpiry' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'shouldPreserve' => 'bool',
  ),
  'couchbase\\replaceoptions::cas' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'string',
  ),
  'couchbase\\replaceoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'int',
  ),
  'couchbase\\replaceoptions::encoder' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'callable',
  ),
  'couchbase\\appendoptions::timeout' => 
  array (
    0 => 'Couchbase\\AppendOptions',
    'arg' => 'int',
  ),
  'couchbase\\appendoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\AppendOptions',
    'arg' => 'int',
  ),
  'couchbase\\prependoptions::timeout' => 
  array (
    0 => 'Couchbase\\PrependOptions',
    'arg' => 'int',
  ),
  'couchbase\\prependoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\PrependOptions',
    'arg' => 'int',
  ),
  'couchbase\\touchoptions::timeout' => 
  array (
    0 => 'Couchbase\\TouchOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::timeout' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::expiry' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\incrementoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::delta' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::initial' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::timeout' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::expiry' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\decrementoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::delta' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::initial' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\removeoptions::timeout' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'int',
  ),
  'couchbase\\removeoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'int',
  ),
  'couchbase\\removeoptions::cas' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'string',
  ),
  'couchbase\\lookupinoptions::timeout' => 
  array (
    0 => 'Couchbase\\LookupInOptions',
    'arg' => 'int',
  ),
  'couchbase\\lookupinoptions::withexpiry' => 
  array (
    0 => 'Couchbase\\LookupInOptions',
    'arg' => 'bool',
  ),
  'couchbase\\mutateinoptions::timeout' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
  ),
  'couchbase\\mutateinoptions::cas' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'string',
  ),
  'couchbase\\mutateinoptions::expiry' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\mutateinoptions::preserveexpiry' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'shouldPreserve' => 'bool',
  ),
  'couchbase\\mutateinoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
  ),
  'couchbase\\mutateinoptions::storesemantics' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::timeout' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::includedocuments' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'bool',
    'maxConcurrentDocuments=' => 'int',
  ),
  'couchbase\\viewoptions::key' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\viewoptions::keys' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'args' => 'array',
  ),
  'couchbase\\viewoptions::limit' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::skip' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::scanconsistency' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::order' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::reduce' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'bool',
  ),
  'couchbase\\viewoptions::group' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'bool',
  ),
  'couchbase\\viewoptions::grouplevel' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::range' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'start' => 'mixed',
    'end' => 'mixed',
    'inclusiveEnd=' => 'mixed',
  ),
  'couchbase\\viewoptions::idrange' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'start' => 'mixed',
    'end' => 'mixed',
    'inclusiveEnd=' => 'mixed',
  ),
  'couchbase\\viewoptions::raw' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'key' => 'string',
    'value' => 'mixed',
  ),
  'couchbase\\queryoptions::timeout' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::consistentwith' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'Couchbase\\MutationState',
  ),
  'couchbase\\queryoptions::scanconsistency' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::scancap' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::pipelinecap' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::pipelinebatch' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::maxparallelism' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::profile' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::readonly' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::flexindex' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::adhoc' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::namedparameters' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'pairs' => 'array',
  ),
  'couchbase\\queryoptions::positionalparameters' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'args' => 'array',
  ),
  'couchbase\\queryoptions::raw' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'key' => 'string',
    'value' => 'mixed',
  ),
  'couchbase\\queryoptions::clientcontextid' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'string',
  ),
  'couchbase\\queryoptions::metrics' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::scopename' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'string',
  ),
  'couchbase\\queryoptions::scopequalifier' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'string',
  ),
  'couchbase\\clusteroptions::credentials' => 
  array (
    0 => 'Couchbase\\ClusterOptions',
    'username' => 'string',
    'password' => 'string',
  ),
  'couchbase\\noopmeter::valuerecorder' => 
  array (
    0 => 'Couchbase\\ValueRecorder',
    'name' => 'string',
    'tags' => 'array',
  ),
  'couchbase\\loggingmeter::flushinterval' => 
  array (
    0 => 'Couchbase\\LoggingMeter',
    'duration' => 'int',
  ),
  'couchbase\\loggingmeter::valuerecorder' => 
  array (
    0 => 'Couchbase\\ValueRecorder',
    'name' => 'string',
    'tags' => 'array',
  ),
  'couchbase\\thresholdloggingtracer::requestspan' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'parent=' => 'Couchbase\\RequestSpan|null',
  ),
  'couchbase\\thresholdloggingtracer::emitinterval' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::kvthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::querythreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::viewsthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::searchthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::analyticsthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::samplesize' => 
  array (
    0 => 'mixed',
    'size' => 'int',
  ),
  'couchbase\\nooptracer::requestspan' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'parent=' => 'Couchbase\\RequestSpan|null',
  ),
);