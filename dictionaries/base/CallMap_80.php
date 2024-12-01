<?php // phpcs:ignoreFile

return array (
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
    0 => 'mixed|null',
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
    'value' => 'string',
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
    'object_or_class' => 'mixed|null',
    'class' => 'string',
    'allow_string=' => 'bool',
  ),
  'is_a' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed|null',
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
    'object_or_class' => 'string',
    'method' => 'string',
  ),
  'property_exists' => 
  array (
    0 => 'bool',
    'object_or_class' => 'string',
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
    0 => 'bool',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'user_error' => 
  array (
    0 => 'bool',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'set_error_handler' => 
  array (
    0 => 'string',
    'callback' => 'callable|null',
    'error_levels=' => 'int',
  ),
  'restore_error_handler' => 
  array (
    0 => 'bool',
  ),
  'set_exception_handler' => 
  array (
    0 => 'string',
    'callback' => 'callable|null',
  ),
  'restore_exception_handler' => 
  array (
    0 => 'bool',
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
    'resource' => 'string',
  ),
  'get_resource_id' => 
  array (
    0 => 'int',
    'resource' => 'string',
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
    'context' => 'string',
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
    0 => 'bool',
    'resolver_function' => 'callable|null',
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
    '&output' => 'string',
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
    'private_key' => 'string',
  ),
  'openssl_x509_verify' => 
  array (
    0 => 'int',
    'certificate' => 'OpenSSLCertificate|string',
    'public_key' => 'string',
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
    'private_key' => 'string',
    'passphrase' => 'string',
    'options=' => 'array',
  ),
  'openssl_pkcs12_export' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    '&output' => 'string',
    'private_key' => 'string',
    'passphrase' => 'string',
    'options=' => 'array',
  ),
  'openssl_pkcs12_read' => 
  array (
    0 => 'bool',
    'pkcs12' => 'string',
    '&certificates' => 'string',
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
    '&output' => 'string',
    'no_text=' => 'bool',
  ),
  'openssl_csr_sign' => 
  array (
    0 => 'OpenSSLCertificate|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'ca_certificate' => 'OpenSSLCertificate|string|null|null',
    'private_key' => 'string',
    'days' => 'int',
    'options=' => 'array|null',
    'serial=' => 'int',
  ),
  'openssl_csr_new' => 
  array (
    0 => 'OpenSSLCertificateSigningRequest|false',
    'distinguished_names' => 'array',
    '&private_key' => 'string',
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
    'key' => 'string',
    'output_filename' => 'string',
    'passphrase=' => 'string|null',
    'options=' => 'array|null',
  ),
  'openssl_pkey_export' => 
  array (
    0 => 'bool',
    'key' => 'string',
    '&output' => 'string',
    'passphrase=' => 'string|null',
    'options=' => 'array|null',
  ),
  'openssl_pkey_get_public' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'string',
  ),
  'openssl_get_publickey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'string',
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
    'private_key' => 'string',
    'passphrase=' => 'string|null',
  ),
  'openssl_get_privatekey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'private_key' => 'string',
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
    'certificate' => 'string',
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
    'private_key' => 'string',
    'headers' => 'array|null',
    'flags=' => 'int',
    'untrusted_certificates_filename=' => 'string|null',
  ),
  'openssl_pkcs7_decrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'string',
    'private_key=' => 'string',
  ),
  'openssl_pkcs7_read' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&certificates' => 'string',
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
    'certificate' => 'string',
    'headers' => 'array|null',
    'flags=' => 'int',
    'encoding=' => 'int',
    'cipher_algo=' => 'int',
  ),
  'openssl_cms_sign' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'string',
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
    'certificate' => 'string',
    'private_key=' => 'string',
    'encoding=' => 'int',
  ),
  'openssl_cms_read' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    '&certificates' => 'string',
  ),
  'openssl_private_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'string',
    'private_key' => 'string',
    'padding=' => 'int',
  ),
  'openssl_private_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'string',
    'private_key' => 'string',
    'padding=' => 'int',
  ),
  'openssl_public_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'string',
    'public_key' => 'string',
    'padding=' => 'int',
  ),
  'openssl_public_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'string',
    'public_key' => 'string',
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
    '&signature' => 'string',
    'private_key' => 'string',
    'algorithm=' => 'string|int',
  ),
  'openssl_verify' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    'signature' => 'string',
    'public_key' => 'string',
    'algorithm=' => 'string|int',
  ),
  'openssl_seal' => 
  array (
    0 => 'int|false',
    'data' => 'string',
    '&sealed_data' => 'string',
    '&encrypted_keys' => 'string',
    'public_key' => 'array',
    'cipher_algo' => 'string',
    '&iv=' => 'string',
  ),
  'openssl_open' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&output' => 'string',
    'encrypted_key' => 'string',
    'private_key' => 'string',
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
    '&tag=' => 'string',
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
    'tag=' => 'string',
    'aad=' => 'string',
  ),
  'openssl_cipher_iv_length' => 
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
    'public_key' => 'string',
    'private_key' => 'string',
    'key_length=' => 'int',
  ),
  'openssl_random_pseudo_bytes' => 
  array (
    0 => 'string',
    'length' => 'int',
    '&strong_result=' => 'string',
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
    '&matches=' => 'string',
    'flags=' => 'int',
    'offset=' => 'int',
  ),
  'preg_match_all' => 
  array (
    0 => 'int|false',
    'pattern' => 'string',
    'subject' => 'string',
    '&matches=' => 'string',
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
    '&count=' => 'string',
  ),
  'preg_filter' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array|string',
    'replacement' => 'array|string',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'string',
  ),
  'preg_replace_callback' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array|string',
    'callback' => 'callable',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'string',
    'flags=' => 'int',
  ),
  'preg_replace_callback_array' => 
  array (
    0 => 'array|string|null|null',
    'pattern' => 'array',
    'subject' => 'array|string',
    'limit=' => 'int',
    '&count=' => 'string',
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
    'use_include_path=' => 'int',
  ),
  'gzopen' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'int',
  ),
  'readgzfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'use_include_path=' => 'int',
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
    'stream' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gzputs' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gzrewind' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'gzclose' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'gzeof' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'gzgetc' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
  ),
  'gzpassthru' => 
  array (
    0 => 'int',
    'stream' => 'string',
  ),
  'gzseek' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'gztell' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
  ),
  'gzread' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
    'length' => 'int',
  ),
  'gzgets' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
    'length=' => 'int|null',
  ),
  'deflate_init' => 
  array (
    0 => 'DeflateContext|false',
    'encoding' => 'int',
    'options=' => 'array',
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
    'options=' => 'array',
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
    'text' => 'mixed|null',
  ),
  'ctype_alpha' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_cntrl' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_digit' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_lower' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_graph' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_print' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_punct' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_space' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_upper' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
  ),
  'ctype_xdigit' => 
  array (
    0 => 'bool',
    'text' => 'mixed|null',
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
    'value' => 'mixed|null',
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
    0 => 'mixed|null',
    'handle' => 'CurlHandle',
    'option=' => 'int|null',
  ),
  'curl_init' => 
  array (
    0 => 'CurlHandle|false',
    'url=' => 'string|null',
  ),
  'curl_multi_add_handle' => 
  array (
    0 => 'int',
    'multi_handle' => 'CurlMultiHandle',
    'handle' => 'CurlHandle',
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
    '&still_running' => 'string',
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
    '&queued_messages=' => 'string',
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
    'value' => 'mixed|null',
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
    'value' => 'mixed|null',
  ),
  'curl_share_strerror' => 
  array (
    0 => 'string|null',
    'error_code' => 'int',
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
    0 => 'DOMElement',
    'node' => 'object',
  ),
  'finfo_open' => 
  array (
    0 => 'string',
    'flags=' => 'int',
    'magic_database=' => 'string|null',
  ),
  'finfo_close' => 
  array (
    0 => 'bool',
    'finfo' => 'string',
  ),
  'finfo_set_flags' => 
  array (
    0 => 'bool',
    'finfo' => 'string',
    'flags' => 'int',
  ),
  'finfo_file' => 
  array (
    0 => 'string|false',
    'finfo' => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'finfo_buffer' => 
  array (
    0 => 'string|false',
    'finfo' => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'mime_content_type' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
  ),
  'filter_has_var' => 
  array (
    0 => 'bool',
    'input_type' => 'int',
    'var_name' => 'string',
  ),
  'filter_input' => 
  array (
    0 => 'mixed|null',
    'type' => 'int',
    'var_name' => 'string',
    'filter=' => 'int',
    'options=' => 'array|int',
  ),
  'filter_var' => 
  array (
    0 => 'mixed|null',
    'value' => 'mixed|null',
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
  'ftp_connect' => 
  array (
    0 => 'string',
    'hostname' => 'string',
    'port=' => 'int',
    'timeout=' => 'int',
  ),
  'ftp_ssl_connect' => 
  array (
    0 => 'string',
    'hostname' => 'string',
    'port=' => 'int',
    'timeout=' => 'int',
  ),
  'ftp_login' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'username' => 'string',
    'password' => 'string',
  ),
  'ftp_pwd' => 
  array (
    0 => 'string|false',
    'ftp' => 'string',
  ),
  'ftp_cdup' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
  ),
  'ftp_chdir' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'directory' => 'string',
  ),
  'ftp_exec' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'command' => 'string',
  ),
  'ftp_raw' => 
  array (
    0 => 'array|null',
    'ftp' => 'string',
    'command' => 'string',
  ),
  'ftp_mkdir' => 
  array (
    0 => 'string|false',
    'ftp' => 'string',
    'directory' => 'string',
  ),
  'ftp_rmdir' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'directory' => 'string',
  ),
  'ftp_chmod' => 
  array (
    0 => 'int|false',
    'ftp' => 'string',
    'permissions' => 'int',
    'filename' => 'string',
  ),
  'ftp_alloc' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'size' => 'int',
    '&response=' => 'string',
  ),
  'ftp_nlist' => 
  array (
    0 => 'array|false',
    'ftp' => 'string',
    'directory' => 'string',
  ),
  'ftp_rawlist' => 
  array (
    0 => 'array|false',
    'ftp' => 'string',
    'directory' => 'string',
    'recursive=' => 'bool',
  ),
  'ftp_mlsd' => 
  array (
    0 => 'array|false',
    'ftp' => 'string',
    'directory' => 'string',
  ),
  'ftp_systype' => 
  array (
    0 => 'string|false',
    'ftp' => 'string',
  ),
  'ftp_fget' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'stream' => 'string',
    'remote_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_nb_fget' => 
  array (
    0 => 'int',
    'ftp' => 'string',
    'stream' => 'string',
    'remote_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_pasv' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'enable' => 'bool',
  ),
  'ftp_get' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'local_filename' => 'string',
    'remote_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_nb_get' => 
  array (
    0 => 'int',
    'ftp' => 'string',
    'local_filename' => 'string',
    'remote_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_nb_continue' => 
  array (
    0 => 'int',
    'ftp' => 'string',
  ),
  'ftp_fput' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'remote_filename' => 'string',
    'stream' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_nb_fput' => 
  array (
    0 => 'int',
    'ftp' => 'string',
    'remote_filename' => 'string',
    'stream' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_put' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'remote_filename' => 'string',
    'local_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_append' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'remote_filename' => 'string',
    'local_filename' => 'string',
    'mode=' => 'int',
  ),
  'ftp_nb_put' => 
  array (
    0 => 'int|false',
    'ftp' => 'string',
    'remote_filename' => 'string',
    'local_filename' => 'string',
    'mode=' => 'int',
    'offset=' => 'int',
  ),
  'ftp_size' => 
  array (
    0 => 'int',
    'ftp' => 'string',
    'filename' => 'string',
  ),
  'ftp_mdtm' => 
  array (
    0 => 'int',
    'ftp' => 'string',
    'filename' => 'string',
  ),
  'ftp_rename' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'from' => 'string',
    'to' => 'string',
  ),
  'ftp_delete' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'filename' => 'string',
  ),
  'ftp_site' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'command' => 'string',
  ),
  'ftp_close' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
  ),
  'ftp_quit' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
  ),
  'ftp_set_option' => 
  array (
    0 => 'bool',
    'ftp' => 'string',
    'option' => 'int',
    'value' => 'string',
  ),
  'ftp_get_option' => 
  array (
    0 => 'int|bool',
    'ftp' => 'string',
    'option' => 'int',
  ),
  'hash' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'data' => 'string',
    'binary=' => 'bool',
  ),
  'hash_file' => 
  array (
    0 => 'string|false',
    'algo' => 'string',
    'filename' => 'string',
    'binary=' => 'bool',
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
  ),
  'hash_update' => 
  array (
    0 => 'bool',
    'context' => 'HashContext',
    'data' => 'string',
  ),
  'hash_update_stream' => 
  array (
    0 => 'int',
    'context' => 'HashContext',
    'stream' => 'string',
    'length=' => 'int',
  ),
  'hash_update_file' => 
  array (
    0 => 'bool',
    'context' => 'HashContext',
    'filename' => 'string',
    'stream_context=' => 'string',
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
    'value' => 'mixed|null',
    'flags=' => 'int',
    'depth=' => 'int',
  ),
  'json_decode' => 
  array (
    0 => 'mixed|null',
    'json' => 'string',
    'associative=' => 'bool|null',
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
    '&result' => 'string',
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
    '&var' => 'mixed|null',
    '...&vars=' => 'mixed|null',
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
    0 => 'array|string|int|false',
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
    '&matches=' => 'string',
  ),
  'mb_eregi' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    '&matches=' => 'string',
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
  'class_implements' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'string',
    'autoload=' => 'bool',
  ),
  'class_parents' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'string',
    'autoload=' => 'bool',
  ),
  'class_uses' => 
  array (
    0 => 'array|false',
    'object_or_class' => 'string',
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
    'iterator' => 'Traversable',
  ),
  'iterator_to_array' => 
  array (
    0 => 'array',
    'iterator' => 'Traversable',
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
    'file_descriptor' => 'string',
  ),
  'posix_isatty' => 
  array (
    0 => 'bool',
    'file_descriptor' => 'string',
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
  'readline' => 
  array (
    0 => 'string|false',
    'prompt=' => 'string|null',
  ),
  'readline_info' => 
  array (
    0 => 'mixed|null',
    'var_name=' => 'string|null',
    'value=' => 'string',
  ),
  'readline_add_history' => 
  array (
    0 => 'bool',
    'prompt' => 'string',
  ),
  'readline_clear_history' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    'open' => 'string',
    'close=' => 'string',
    'read=' => 'callable',
    'write=' => 'callable',
    'destroy=' => 'callable',
    'gc=' => 'callable',
    'create_sid=' => 'callable',
    'validate_sid=' => 'callable',
    'update_timestamp=' => 'callable',
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
    'node' => 'SimpleXMLElement|DOMNode',
    'class_name=' => 'string|null',
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
    'callback=' => 'string',
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
    '...values=' => 'mixed|null',
  ),
  'krsort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'ksort' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
    '&array' => 'array',
  ),
  'natcasesort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
  ),
  'asort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'arsort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'sort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'rsort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'usort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'uasort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'uksort' => 
  array (
    0 => 'bool',
    '&array' => 'array',
    'callback' => 'callable',
  ),
  'end' => 
  array (
    0 => 'mixed|null',
    '&array' => 'object|array',
  ),
  'prev' => 
  array (
    0 => 'mixed|null',
    '&array' => 'object|array',
  ),
  'next' => 
  array (
    0 => 'mixed|null',
    '&array' => 'object|array',
  ),
  'reset' => 
  array (
    0 => 'mixed|null',
    '&array' => 'object|array',
  ),
  'current' => 
  array (
    0 => 'mixed|null',
    'array' => 'object|array',
  ),
  'pos' => 
  array (
    0 => 'mixed|null',
    'array' => 'object|array',
  ),
  'key' => 
  array (
    0 => 'string|int|null|null',
    'array' => 'object|array',
  ),
  'min' => 
  array (
    0 => 'mixed|null',
    'value' => 'mixed|null',
    '...values=' => 'mixed|null',
  ),
  'max' => 
  array (
    0 => 'mixed|null',
    'value' => 'mixed|null',
    '...values=' => 'mixed|null',
  ),
  'array_walk' => 
  array (
    0 => 'bool',
    '&array' => 'object|array',
    'callback' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'array_walk_recursive' => 
  array (
    0 => 'bool',
    '&array' => 'object|array',
    'callback' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'in_array' => 
  array (
    0 => 'bool',
    'needle' => 'mixed|null',
    'haystack' => 'array',
    'strict=' => 'bool',
  ),
  'array_search' => 
  array (
    0 => 'string|int|false',
    'needle' => 'mixed|null',
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
    'var_name' => 'string',
    '...var_names=' => 'string',
  ),
  'array_fill' => 
  array (
    0 => 'array',
    'start_index' => 'int',
    'count' => 'int',
    'value' => 'mixed|null',
  ),
  'array_fill_keys' => 
  array (
    0 => 'array',
    'keys' => 'array',
    'value' => 'mixed|null',
  ),
  'range' => 
  array (
    0 => 'array',
    'start' => 'string',
    'end' => 'string',
    'step=' => 'int|float',
  ),
  'shuffle' => 
  array (
    0 => 'bool',
    '&array' => 'array',
  ),
  'array_pop' => 
  array (
    0 => 'mixed|null',
    '&array' => 'array',
  ),
  'array_shift' => 
  array (
    0 => 'mixed|null',
    '&array' => 'array',
  ),
  'array_unshift' => 
  array (
    0 => 'int',
    '&array' => 'array',
    '...values=' => 'mixed|null',
  ),
  'array_splice' => 
  array (
    0 => 'array',
    '&array' => 'array',
    'offset' => 'int',
    'length=' => 'int|null',
    'replacement=' => 'mixed|null',
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
    'filter_value=' => 'mixed|null',
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
    'value' => 'mixed|null',
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
    '...rest=' => 'string',
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
    '...rest=' => 'string',
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
    '...rest=' => 'string',
  ),
  'array_intersect_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'string',
  ),
  'array_uintersect_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'string',
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
    '...rest=' => 'string',
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
    '...rest=' => 'string',
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
    '...rest=' => 'string',
  ),
  'array_udiff_assoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'string',
  ),
  'array_udiff_uassoc' => 
  array (
    0 => 'array',
    'array' => 'array',
    '...rest=' => 'string',
  ),
  'array_multisort' => 
  array (
    0 => 'bool',
    '&array' => 'string',
    '...&rest=' => 'string',
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
    0 => 'mixed|null',
    'array' => 'array',
    'callback' => 'callable',
    'initial=' => 'mixed|null',
  ),
  'array_filter' => 
  array (
    0 => 'array',
    'array' => 'array',
    'callback=' => 'callable|null',
    'mode=' => 'int',
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
    'key' => 'string',
    'array' => 'array',
  ),
  'key_exists' => 
  array (
    0 => 'bool',
    'key' => 'string',
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
    0 => 'mixed|null',
    'name' => 'string',
  ),
  'ip2long' => 
  array (
    0 => 'int|false',
    'ip' => 'string',
  ),
  'long2ip' => 
  array (
    0 => 'string|false',
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
    '&rest_index=' => 'string',
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
    0 => 'mixed|null',
    'callback' => 'callable',
    '...args=' => 'mixed|null',
  ),
  'call_user_func_array' => 
  array (
    0 => 'mixed|null',
    'callback' => 'callable',
    'args' => 'array',
  ),
  'forward_static_call' => 
  array (
    0 => 'mixed|null',
    'callback' => 'callable',
    '...args=' => 'mixed|null',
  ),
  'forward_static_call_array' => 
  array (
    0 => 'mixed|null',
    'callback' => 'callable',
    'args' => 'array',
  ),
  'register_shutdown_function' => 
  array (
    0 => 'bool|null',
    'callback' => 'callable',
    '...args=' => 'mixed|null',
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
    0 => 'string|bool',
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
    'value' => 'string',
  ),
  'ini_alter' => 
  array (
    0 => 'string|false',
    'option' => 'string',
    'value' => 'string',
  ),
  'ini_restore' => 
  array (
    0 => 'void',
    'option' => 'string',
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
    0 => 'string|bool',
    'value' => 'mixed|null',
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
    '...args=' => 'mixed|null',
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
    '&authoritative_name_servers=' => 'string',
    '&additional_records=' => 'string',
    'raw=' => 'bool',
  ),
  'dns_get_mx' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'string',
    '&weights=' => 'string',
  ),
  'getmxrr' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'string',
    '&weights=' => 'string',
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
  'lcg_value' => 
  array (
    0 => 'float',
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
    0 => 'bool',
    'prefix' => 'string',
    'flags' => 'int',
    'facility' => 'int',
  ),
  'closelog' => 
  array (
    0 => 'bool',
  ),
  'syslog' => 
  array (
    0 => 'bool',
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
    '&filename=' => 'string',
    '&line=' => 'string',
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
    'assertion' => 'mixed|null',
    'description=' => 'Throwable|string|null|null',
  ),
  'assert_options' => 
  array (
    0 => 'mixed|null',
    'option' => 'int',
    'value=' => 'mixed|null',
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
    '&percent=' => 'string',
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
    '&count=' => 'string',
  ),
  'str_ireplace' => 
  array (
    0 => 'array|string',
    'search' => 'array|string',
    'replace' => 'array|string',
    'subject' => 'array|string',
    '&count=' => 'string',
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
    'locales' => 'string',
    '...rest=' => 'string',
  ),
  'parse_str' => 
  array (
    0 => 'void',
    'string' => 'string',
    '&result' => 'string',
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
    '...&vars=' => 'mixed|null',
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
    0 => 'string',
    'directory' => 'string',
    'context=' => 'string',
  ),
  'dir' => 
  array (
    0 => 'Directory|false',
    'directory' => 'string',
    'context=' => 'string',
  ),
  'closedir' => 
  array (
    0 => 'void',
    'dir_handle=' => 'string',
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
    'dir_handle=' => 'string',
  ),
  'readdir' => 
  array (
    0 => 'string|false',
    'dir_handle=' => 'string',
  ),
  'scandir' => 
  array (
    0 => 'array|false',
    'directory' => 'string',
    'sorting_order=' => 'int',
    'context=' => 'string',
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
    '&output=' => 'string',
    '&result_code=' => 'string',
  ),
  'system' => 
  array (
    0 => 'string|false',
    'command' => 'string',
    '&result_code=' => 'string',
  ),
  'passthru' => 
  array (
    0 => 'bool|null',
    'command' => 'string',
    '&result_code=' => 'string',
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
    'stream' => 'string',
    'operation' => 'int',
    '&would_block=' => 'string',
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
    'handle' => 'string',
  ),
  'popen' => 
  array (
    0 => 'string',
    'command' => 'string',
    'mode' => 'string',
  ),
  'readfile' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'string',
  ),
  'rewind' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'rmdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'context=' => 'string',
  ),
  'umask' => 
  array (
    0 => 'int',
    'mask=' => 'int|null',
  ),
  'fclose' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'feof' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'fgetc' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
  ),
  'fgets' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
    'length=' => 'int|null',
  ),
  'fread' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
    'length' => 'int',
  ),
  'fopen' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'string',
  ),
  'fscanf' => 
  array (
    0 => 'array|int|false|null|null',
    'stream' => 'string',
    'format' => 'string',
    '...&vars=' => 'mixed|null',
  ),
  'fpassthru' => 
  array (
    0 => 'int',
    'stream' => 'string',
  ),
  'ftruncate' => 
  array (
    0 => 'bool',
    'stream' => 'string',
    'size' => 'int',
  ),
  'fstat' => 
  array (
    0 => 'array|false',
    'stream' => 'string',
  ),
  'fseek' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'ftell' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
  ),
  'fflush' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'fwrite' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'fputs' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'mkdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'permissions=' => 'int',
    'recursive=' => 'bool',
    'context=' => 'string',
  ),
  'rename' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'string',
  ),
  'copy' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'string',
  ),
  'tempnam' => 
  array (
    0 => 'string|false',
    'directory' => 'string',
    'prefix' => 'string',
  ),
  'tmpfile' => 
  array (
    0 => 'string',
  ),
  'file' => 
  array (
    0 => 'array|false',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'file_get_contents' => 
  array (
    0 => 'string|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'unlink' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'context=' => 'string',
  ),
  'file_put_contents' => 
  array (
    0 => 'int|false',
    'filename' => 'string',
    'data' => 'mixed|null',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'fputcsv' => 
  array (
    0 => 'int|false',
    'stream' => 'string',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'fgetcsv' => 
  array (
    0 => 'array|false',
    'stream' => 'string',
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
    '...values=' => 'mixed|null',
  ),
  'printf' => 
  array (
    0 => 'int',
    'format' => 'string',
    '...values=' => 'mixed|null',
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
    'stream' => 'string',
    'format' => 'string',
    '...values=' => 'mixed|null',
  ),
  'vfprintf' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'format' => 'string',
    'values' => 'array',
  ),
  'fsockopen' => 
  array (
    0 => 'string',
    'hostname' => 'string',
    'port=' => 'int',
    '&error_code=' => 'string',
    '&error_message=' => 'string',
    'timeout=' => 'float|null',
  ),
  'pfsockopen' => 
  array (
    0 => 'string',
    'hostname' => 'string',
    'port=' => 'int',
    '&error_code=' => 'string',
    '&error_message=' => 'string',
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
    '&image_info=' => 'string',
  ),
  'getimagesizefromstring' => 
  array (
    0 => 'array|false',
    'string' => 'string',
    '&image_info=' => 'string',
  ),
  'phpinfo' => 
  array (
    0 => 'bool',
    'flags=' => 'int',
  ),
  'phpversion' => 
  array (
    0 => 'string|false',
    'extension=' => 'string|null',
  ),
  'phpcredits' => 
  array (
    0 => 'bool',
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
    'mode=' => 'int',
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
    'num' => 'mixed|null',
    'exponent' => 'mixed|null',
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
    '...values=' => 'mixed|null',
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
    0 => 'string',
    'command' => 'array|string',
    'descriptor_spec' => 'array',
    '&pipes' => 'string',
    'cwd=' => 'string|null',
    'env_vars=' => 'array|null',
    'options=' => 'array|null',
  ),
  'proc_close' => 
  array (
    0 => 'int',
    'process' => 'string',
  ),
  'proc_terminate' => 
  array (
    0 => 'bool',
    'process' => 'string',
    'signal=' => 'int',
  ),
  'proc_get_status' => 
  array (
    0 => 'array',
    'process' => 'string',
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
  'mt_srand' => 
  array (
    0 => 'void',
    'seed=' => 'int',
    'mode=' => 'int',
  ),
  'srand' => 
  array (
    0 => 'void',
    'seed=' => 'int',
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
    'microseconds=' => 'int',
  ),
  'stream_context_create' => 
  array (
    0 => 'string',
    'options=' => 'array|null',
    'params=' => 'array|null',
  ),
  'stream_context_set_params' => 
  array (
    0 => 'bool',
    'context' => 'string',
    'params' => 'array',
  ),
  'stream_context_get_params' => 
  array (
    0 => 'array',
    'context' => 'string',
  ),
  'stream_context_set_option' => 
  array (
    0 => 'bool',
    'context' => 'string',
    'wrapper_or_options' => 'array|string',
    'option_name=' => 'string|null',
    'value=' => 'mixed|null',
  ),
  'stream_context_get_options' => 
  array (
    0 => 'array',
    'stream_or_context' => 'string',
  ),
  'stream_context_get_default' => 
  array (
    0 => 'string',
    'options=' => 'array|null',
  ),
  'stream_context_set_default' => 
  array (
    0 => 'string',
    'options' => 'array',
  ),
  'stream_filter_prepend' => 
  array (
    0 => 'string',
    'stream' => 'string',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed|null',
  ),
  'stream_filter_append' => 
  array (
    0 => 'string',
    'stream' => 'string',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed|null',
  ),
  'stream_filter_remove' => 
  array (
    0 => 'bool',
    'stream_filter' => 'string',
  ),
  'stream_socket_client' => 
  array (
    0 => 'string',
    'address' => 'string',
    '&error_code=' => 'string',
    '&error_message=' => 'string',
    'timeout=' => 'float|null',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'stream_socket_server' => 
  array (
    0 => 'string',
    'address' => 'string',
    '&error_code=' => 'string',
    '&error_message=' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'stream_socket_accept' => 
  array (
    0 => 'string',
    'socket' => 'string',
    'timeout=' => 'float|null',
    '&peer_name=' => 'string',
  ),
  'stream_socket_get_name' => 
  array (
    0 => 'string|false',
    'socket' => 'string',
    'remote' => 'bool',
  ),
  'stream_socket_recvfrom' => 
  array (
    0 => 'string|false',
    'socket' => 'string',
    'length' => 'int',
    'flags=' => 'int',
    '&address=' => 'string',
  ),
  'stream_socket_sendto' => 
  array (
    0 => 'int|false',
    'socket' => 'string',
    'data' => 'string',
    'flags=' => 'int',
    'address=' => 'string',
  ),
  'stream_socket_enable_crypto' => 
  array (
    0 => 'int|bool',
    'stream' => 'string',
    'enable' => 'bool',
    'crypto_method=' => 'int|null',
    'session_stream=' => 'string',
  ),
  'stream_socket_shutdown' => 
  array (
    0 => 'bool',
    'stream' => 'string',
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
    'from' => 'string',
    'to' => 'string',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_get_contents' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_supports_lock' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'stream_set_write_buffer' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'size' => 'int',
  ),
  'set_file_buffer' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'size' => 'int',
  ),
  'stream_set_read_buffer' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'size' => 'int',
  ),
  'stream_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'string',
    'enable' => 'bool',
  ),
  'socket_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'string',
    'enable' => 'bool',
  ),
  'stream_get_meta_data' => 
  array (
    0 => 'array',
    'stream' => 'string',
  ),
  'socket_get_status' => 
  array (
    0 => 'array',
    'stream' => 'string',
  ),
  'stream_get_line' => 
  array (
    0 => 'string|false',
    'stream' => 'string',
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
    'stream' => 'string',
  ),
  'stream_isatty' => 
  array (
    0 => 'bool',
    'stream' => 'string',
  ),
  'stream_set_chunk_size' => 
  array (
    0 => 'int',
    'stream' => 'string',
    'size' => 'int',
  ),
  'stream_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'string',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'socket_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'string',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'gettype' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'get_debug_type' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'settype' => 
  array (
    0 => 'bool',
    '&var' => 'mixed|null',
    'type' => 'string',
  ),
  'intval' => 
  array (
    0 => 'int',
    'value' => 'mixed|null',
    'base=' => 'int',
  ),
  'floatval' => 
  array (
    0 => 'float',
    'value' => 'mixed|null',
  ),
  'doubleval' => 
  array (
    0 => 'float',
    'value' => 'mixed|null',
  ),
  'boolval' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'strval' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'is_null' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_resource' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_bool' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_int' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_integer' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_long' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_float' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_double' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_numeric' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_string' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_array' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_object' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_scalar' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_callable' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
    'syntax_only=' => 'bool',
    '&callable_name=' => 'string',
  ),
  'is_iterable' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
  ),
  'is_countable' => 
  array (
    0 => 'bool',
    'value' => 'mixed|null',
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
    'context=' => 'string',
  ),
  'stream_bucket_make_writeable' => 
  array (
    0 => 'object|null',
    'brigade' => 'string',
  ),
  'stream_bucket_prepend' => 
  array (
    0 => 'void',
    'brigade' => 'string',
    'bucket' => 'object',
  ),
  'stream_bucket_append' => 
  array (
    0 => 'void',
    'brigade' => 'string',
    'bucket' => 'object',
  ),
  'stream_bucket_new' => 
  array (
    0 => 'object',
    'stream' => 'string',
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
    'value' => 'mixed|null',
    '...values=' => 'mixed|null',
  ),
  'var_export' => 
  array (
    0 => 'string|null',
    'value' => 'mixed|null',
    'return=' => 'bool',
  ),
  'debug_zval_dump' => 
  array (
    0 => 'void',
    'value' => 'mixed|null',
    '...values=' => 'mixed|null',
  ),
  'serialize' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'unserialize' => 
  array (
    0 => 'mixed|null',
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
  'version_compare' => 
  array (
    0 => 'int|bool',
    'version1' => 'string',
    'version2' => 'string',
    'operator=' => 'string|null',
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
    0 => 'bool',
    'parser' => 'XMLParser',
    'object' => 'object',
  ),
  'xml_set_element_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'start_handler' => 'string',
    'end_handler' => 'string',
  ),
  'xml_set_character_data_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_processing_instruction_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_default_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_unparsed_entity_decl_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_notation_decl_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_external_entity_ref_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_start_namespace_decl_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
  ),
  'xml_set_end_namespace_decl_handler' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'handler' => 'string',
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
    0 => 'int',
    'parser' => 'XMLParser',
    'data' => 'string',
    '&values' => 'string',
    '&index=' => 'string',
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
    'value' => 'string',
  ),
  'xml_parser_get_option' => 
  array (
    0 => 'string|int',
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
    'key' => 'string',
    'value=' => 'mixed|null',
    'ttl=' => 'int',
  ),
  'apcu_add' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
    'value=' => 'mixed|null',
    'ttl=' => 'int',
  ),
  'apcu_inc' => 
  array (
    0 => 'int|false',
    'key' => 'string',
    'step=' => 'int',
    '&success=' => 'string',
    'ttl=' => 'int',
  ),
  'apcu_dec' => 
  array (
    0 => 'int|false',
    'key' => 'string',
    'step=' => 'int',
    '&success=' => 'string',
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
    0 => 'mixed|null',
    'key' => 'string',
    '&success=' => 'string',
  ),
  'apcu_exists' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
  ),
  'apcu_delete' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
  ),
  'apcu_entry' => 
  array (
    0 => 'mixed|null',
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
  'gd_info' => 
  array (
    0 => 'array',
  ),
  'imageloadfont' => 
  array (
    0 => 'int|false',
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
    0 => 'bool',
    'image1' => 'GdImage',
    'image2' => 'GdImage',
  ),
  'imagesetthickness' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'thickness' => 'int',
  ),
  'imagefilledellipse' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'color' => 'int',
  ),
  'imagefilledarc' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagesavealpha' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagelayereffect' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    'ignore_transparent=' => 'bool',
  ),
  'imagesettile' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'tile' => 'GdImage',
  ),
  'imagesetbrush' => 
  array (
    0 => 'bool',
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
  'imagegif' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string',
  ),
  'imagepng' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string',
    'quality=' => 'int',
    'filters=' => 'int',
  ),
  'imagewebp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string',
    'quality=' => 'int',
  ),
  'imagejpeg' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string',
    'quality=' => 'int',
  ),
  'imagewbmp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'string',
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
    'file=' => 'string',
    'compressed=' => 'bool',
  ),
  'imagedestroy' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'bool|null',
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
    0 => 'bool',
    'image' => 'GdImage',
    'input_gamma' => 'float',
    'output_gamma' => 'float',
  ),
  'imagesetpixel' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
  ),
  'imageline' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagedashedline' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagerectangle' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagefilledrectangle' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x1' => 'int',
    'y1' => 'int',
    'x2' => 'int',
    'y2' => 'int',
    'color' => 'int',
  ),
  'imagearc' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
    'image' => 'GdImage',
    'center_x' => 'int',
    'center_y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'color' => 'int',
  ),
  'imagefilltoborder' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'border_color' => 'int',
    'color' => 'int',
  ),
  'imagefill' => 
  array (
    0 => 'bool',
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
    'font' => 'int',
  ),
  'imagefontheight' => 
  array (
    0 => 'int',
    'font' => 'int',
  ),
  'imagechar' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'font' => 'int',
    'x' => 'int',
    'y' => 'int',
    'char' => 'string',
    'color' => 'int',
  ),
  'imagecharup' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'font' => 'int',
    'x' => 'int',
    'y' => 'int',
    'char' => 'string',
    'color' => 'int',
  ),
  'imagestring' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'font' => 'int',
    'x' => 'int',
    'y' => 'int',
    'string' => 'string',
    'color' => 'int',
  ),
  'imagestringup' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'font' => 'int',
    'x' => 'int',
    'y' => 'int',
    'string' => 'string',
    'color' => 'int',
  ),
  'imagecopy' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'bool',
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
    '...args=' => 'string',
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
    0 => 'bool',
    'image' => 'GdImage',
    'mode' => 'int',
  ),
  'imageantialias' => 
  array (
    0 => 'bool',
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
    'options' => 'string',
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
    0 => 'array|bool',
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
  'igbinary_serialize' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'igbinary_unserialize' => 
  array (
    0 => 'string',
    'str' => 'string',
  ),
  'intlcal_create_instance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'string',
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
    'timezone' => 'string',
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
    0 => 'bool',
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
    'value' => 'string',
  ),
  'intlcal_clear' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_set_lenient' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'option' => 'int',
  ),
  'intlcal_set_skipped_wall_time_option' => 
  array (
    0 => 'bool',
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
    'timezoneOrYear=' => 'string',
    'localeOrMonth=' => 'string',
    'day=' => 'string',
    'hour=' => 'string',
    'minute=' => 'string',
    'second=' => 'string',
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
    0 => 'bool',
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
    'dateType' => 'int',
    'timeType' => 'int',
    'timezone=' => 'string',
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
    0 => 'bool|null',
    'formatter' => 'IntlDateFormatter',
    'timezone' => 'string',
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
    'datetime' => 'string',
  ),
  'datefmt_format_object' => 
  array (
    0 => 'string|false',
    'datetime' => 'string',
    'format=' => 'string',
    'locale=' => 'string|null',
  ),
  'datefmt_parse' => 
  array (
    0 => 'int|float|false',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'string',
  ),
  'datefmt_localtime' => 
  array (
    0 => 'array|false',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'string',
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
    '&offset=' => 'string',
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
    '&currency' => 'string',
    '&offset=' => 'string',
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
  ),
  'grapheme_stripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'grapheme_strrpos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'grapheme_strripos' => 
  array (
    0 => 'int|false',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'grapheme_substr' => 
  array (
    0 => 'string|false',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
  ),
  'grapheme_strstr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
  ),
  'grapheme_stristr' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
  ),
  'grapheme_extract' => 
  array (
    0 => 'string|false',
    'haystack' => 'string',
    'size' => 'int',
    'type=' => 'int',
    'offset=' => 'int',
    '&next=' => 'string',
  ),
  'idn_to_ascii' => 
  array (
    0 => 'string|false',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'string',
  ),
  'idn_to_utf8' => 
  array (
    0 => 'string|false',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'string',
  ),
  'locale_get_default' => 
  array (
    0 => 'string',
  ),
  'locale_set_default' => 
  array (
    0 => 'bool',
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
    0 => 'mixed|null',
    'bundle' => 'ResourceBundle',
    'index' => 'string',
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
    'countryOrRawOffset=' => 'string',
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
    '&isSystemId=' => 'string',
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
    '&rawOffset' => 'string',
    '&dstOffset' => 'string',
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
    0 => 'int|false',
    'transliterator' => 'Transliterator',
  ),
  'transliterator_get_error_message' => 
  array (
    0 => 'string|false',
    'transliterator' => 'Transliterator',
  ),
  'mongodb\\bson\\fromjson' => 
  array (
    0 => 'string',
    'json' => 'string',
  ),
  'mongodb\\bson\\fromphp' => 
  array (
    0 => 'string',
    'value' => 'object|array',
  ),
  'mongodb\\bson\\tocanonicalextendedjson' => 
  array (
    0 => 'string',
    'bson' => 'string',
  ),
  'mongodb\\bson\\tojson' => 
  array (
    0 => 'string',
    'bson' => 'string',
  ),
  'mongodb\\bson\\tophp' => 
  array (
    0 => 'object|array',
    'bson' => 'string',
    'typemap=' => 'array|null',
  ),
  'mongodb\\bson\\torelaxedextendedjson' => 
  array (
    0 => 'string',
    'bson' => 'string',
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
    '&status' => 'string',
    'flags=' => 'int',
    '&resource_usage=' => 'string',
  ),
  'pcntl_wait' => 
  array (
    0 => 'int',
    '&status' => 'string',
    'flags=' => 'int',
    '&resource_usage=' => 'string',
  ),
  'pcntl_signal' => 
  array (
    0 => 'bool',
    'signal' => 'int',
    'handler' => 'string',
    'restart_syscalls=' => 'bool',
  ),
  'pcntl_signal_get_handler' => 
  array (
    0 => 'string',
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
    '&old_signals=' => 'string',
  ),
  'pcntl_sigwaitinfo' => 
  array (
    0 => 'int|false',
    'signals' => 'array',
    '&info=' => 'string',
  ),
  'pcntl_sigtimedwait' => 
  array (
    0 => 'int|false',
    'signals' => 'array',
    '&info=' => 'string',
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
    0 => 'bool',
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
  'pg_connect' => 
  array (
    0 => 'string',
    'connection_string' => 'string',
    'flags=' => 'int',
  ),
  'pg_pconnect' => 
  array (
    0 => 'string',
    'connection_string' => 'string',
    'flags=' => 'int',
  ),
  'pg_connect_poll' => 
  array (
    0 => 'int',
    'connection' => 'string',
  ),
  'pg_close' => 
  array (
    0 => 'bool',
    'connection=' => 'string',
  ),
  'pg_dbname' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_last_error' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_errormessage' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_options' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_port' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_tty' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_host' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_version' => 
  array (
    0 => 'array',
    'connection=' => 'string',
  ),
  'pg_parameter_status' => 
  array (
    0 => 'string|false',
    'connection' => 'string',
    'name=' => 'string',
  ),
  'pg_ping' => 
  array (
    0 => 'bool',
    'connection=' => 'string',
  ),
  'pg_query' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'query=' => 'string',
  ),
  'pg_exec' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'query=' => 'string',
  ),
  'pg_query_params' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'query' => 'string',
    'params=' => 'array',
  ),
  'pg_prepare' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'statement_name' => 'string',
    'query=' => 'string',
  ),
  'pg_execute' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'statement_name' => 'string',
    'params=' => 'array',
  ),
  'pg_num_rows' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_numrows' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_num_fields' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_numfields' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_affected_rows' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_cmdtuples' => 
  array (
    0 => 'int',
    'result' => 'string',
  ),
  'pg_last_notice' => 
  array (
    0 => 'array|string|bool',
    'connection' => 'string',
    'mode=' => 'int',
  ),
  'pg_field_table' => 
  array (
    0 => 'string|int|false',
    'result' => 'string',
    'field' => 'int',
    'oid_only=' => 'bool',
  ),
  'pg_field_name' => 
  array (
    0 => 'string',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_fieldname' => 
  array (
    0 => 'string',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_field_size' => 
  array (
    0 => 'int',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_fieldsize' => 
  array (
    0 => 'int',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_field_type' => 
  array (
    0 => 'string',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_fieldtype' => 
  array (
    0 => 'string',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_field_type_oid' => 
  array (
    0 => 'string|int',
    'result' => 'string',
    'field' => 'int',
  ),
  'pg_field_num' => 
  array (
    0 => 'int',
    'result' => 'string',
    'field' => 'string',
  ),
  'pg_fieldnum' => 
  array (
    0 => 'int',
    'result' => 'string',
    'field' => 'string',
  ),
  'pg_fetch_result' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_result' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_fetch_row' => 
  array (
    0 => 'array|false',
    'result' => 'string',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_fetch_assoc' => 
  array (
    0 => 'array|false',
    'result' => 'string',
    'row=' => 'int|null',
  ),
  'pg_fetch_array' => 
  array (
    0 => 'array|false',
    'result' => 'string',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_fetch_object' => 
  array (
    0 => 'object|false',
    'result' => 'string',
    'row=' => 'int|null',
    'class=' => 'string',
    'constructor_args=' => 'array',
  ),
  'pg_fetch_all' => 
  array (
    0 => 'array',
    'result' => 'string',
    'mode=' => 'int',
  ),
  'pg_fetch_all_columns' => 
  array (
    0 => 'array',
    'result' => 'string',
    'field=' => 'int',
  ),
  'pg_result_seek' => 
  array (
    0 => 'bool',
    'result' => 'string',
    'row' => 'int',
  ),
  'pg_field_prtlen' => 
  array (
    0 => 'int|false',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_fieldprtlen' => 
  array (
    0 => 'int|false',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_field_is_null' => 
  array (
    0 => 'int|false',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_fieldisnull' => 
  array (
    0 => 'int|false',
    'result' => 'string',
    'row' => 'string',
    'field=' => 'string|int',
  ),
  'pg_free_result' => 
  array (
    0 => 'bool',
    'result' => 'string',
  ),
  'pg_freeresult' => 
  array (
    0 => 'bool',
    'result' => 'string',
  ),
  'pg_last_oid' => 
  array (
    0 => 'string|int|false',
    'result' => 'string',
  ),
  'pg_getlastoid' => 
  array (
    0 => 'string|int|false',
    'result' => 'string',
  ),
  'pg_trace' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'mode=' => 'string',
    'connection=' => 'string',
  ),
  'pg_untrace' => 
  array (
    0 => 'bool',
    'connection=' => 'string',
  ),
  'pg_lo_create' => 
  array (
    0 => 'string|int|false',
    'connection=' => 'string',
    'oid=' => 'string',
  ),
  'pg_locreate' => 
  array (
    0 => 'string|int|false',
    'connection=' => 'string',
    'oid=' => 'string',
  ),
  'pg_lo_unlink' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'oid=' => 'string',
  ),
  'pg_lounlink' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'oid=' => 'string',
  ),
  'pg_lo_open' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'oid=' => 'string',
    'mode=' => 'string',
  ),
  'pg_loopen' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'oid=' => 'string',
    'mode=' => 'string',
  ),
  'pg_lo_close' => 
  array (
    0 => 'bool',
    'lob' => 'string',
  ),
  'pg_loclose' => 
  array (
    0 => 'bool',
    'lob' => 'string',
  ),
  'pg_lo_read' => 
  array (
    0 => 'string|false',
    'lob' => 'string',
    'length=' => 'int',
  ),
  'pg_loread' => 
  array (
    0 => 'string|false',
    'lob' => 'string',
    'length=' => 'int',
  ),
  'pg_lo_write' => 
  array (
    0 => 'int|false',
    'lob' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_lowrite' => 
  array (
    0 => 'int|false',
    'lob' => 'string',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_lo_read_all' => 
  array (
    0 => 'int',
    'lob' => 'string',
  ),
  'pg_loreadall' => 
  array (
    0 => 'int',
    'lob' => 'string',
  ),
  'pg_lo_import' => 
  array (
    0 => 'string|int|false',
    'connection' => 'string',
    'filename=' => 'string',
    'oid=' => 'string',
  ),
  'pg_loimport' => 
  array (
    0 => 'string|int|false',
    'connection' => 'string',
    'filename=' => 'string',
    'oid=' => 'string',
  ),
  'pg_lo_export' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'oid=' => 'string',
    'filename=' => 'string',
  ),
  'pg_loexport' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'oid=' => 'string',
    'filename=' => 'string',
  ),
  'pg_lo_seek' => 
  array (
    0 => 'bool',
    'lob' => 'string',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'pg_lo_tell' => 
  array (
    0 => 'int',
    'lob' => 'string',
  ),
  'pg_lo_truncate' => 
  array (
    0 => 'bool',
    'lob' => 'string',
    'size' => 'int',
  ),
  'pg_set_error_verbosity' => 
  array (
    0 => 'int|false',
    'connection' => 'string',
    'verbosity=' => 'int',
  ),
  'pg_set_client_encoding' => 
  array (
    0 => 'int',
    'connection' => 'string',
    'encoding=' => 'string',
  ),
  'pg_setclientencoding' => 
  array (
    0 => 'int',
    'connection' => 'string',
    'encoding=' => 'string',
  ),
  'pg_client_encoding' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_clientencoding' => 
  array (
    0 => 'string',
    'connection=' => 'string',
  ),
  'pg_end_copy' => 
  array (
    0 => 'bool',
    'connection=' => 'string',
  ),
  'pg_put_line' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'query=' => 'string',
  ),
  'pg_copy_to' => 
  array (
    0 => 'array|false',
    'connection' => 'string',
    'table_name' => 'string',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_copy_from' => 
  array (
    0 => 'bool',
    'connection' => 'string',
    'table_name' => 'string',
    'rows' => 'array',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_escape_string' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'string=' => 'string',
  ),
  'pg_escape_bytea' => 
  array (
    0 => 'string',
    'connection' => 'string',
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
    'connection' => 'string',
    'string=' => 'string',
  ),
  'pg_escape_identifier' => 
  array (
    0 => 'string|false',
    'connection' => 'string',
    'string=' => 'string',
  ),
  'pg_result_error' => 
  array (
    0 => 'string|false',
    'result' => 'string',
  ),
  'pg_result_error_field' => 
  array (
    0 => 'string|false|null|null',
    'result' => 'string',
    'field_code' => 'int',
  ),
  'pg_connection_status' => 
  array (
    0 => 'int',
    'connection' => 'string',
  ),
  'pg_transaction_status' => 
  array (
    0 => 'int',
    'connection' => 'string',
  ),
  'pg_connection_reset' => 
  array (
    0 => 'bool',
    'connection' => 'string',
  ),
  'pg_cancel_query' => 
  array (
    0 => 'bool',
    'connection' => 'string',
  ),
  'pg_connection_busy' => 
  array (
    0 => 'bool',
    'connection' => 'string',
  ),
  'pg_send_query' => 
  array (
    0 => 'int|bool',
    'connection' => 'string',
    'query' => 'string',
  ),
  'pg_send_query_params' => 
  array (
    0 => 'int|bool',
    'connection' => 'string',
    'query' => 'string',
    'params' => 'array',
  ),
  'pg_send_prepare' => 
  array (
    0 => 'int|bool',
    'connection' => 'string',
    'statement_name' => 'string',
    'query' => 'string',
  ),
  'pg_send_execute' => 
  array (
    0 => 'int|bool',
    'connection' => 'string',
    'statement_name' => 'string',
    'params' => 'array',
  ),
  'pg_get_result' => 
  array (
    0 => 'string',
    'connection' => 'string',
  ),
  'pg_result_status' => 
  array (
    0 => 'string|int',
    'result' => 'string',
    'mode=' => 'int',
  ),
  'pg_get_notify' => 
  array (
    0 => 'array|false',
    'connection' => 'string',
    'mode=' => 'int',
  ),
  'pg_get_pid' => 
  array (
    0 => 'int',
    'connection' => 'string',
  ),
  'pg_socket' => 
  array (
    0 => 'string',
    'connection' => 'string',
  ),
  'pg_consume_input' => 
  array (
    0 => 'bool',
    'connection' => 'string',
  ),
  'pg_flush' => 
  array (
    0 => 'int|bool',
    'connection' => 'string',
  ),
  'pg_meta_data' => 
  array (
    0 => 'array|false',
    'connection' => 'string',
    'table_name' => 'string',
    'extended=' => 'bool',
  ),
  'pg_convert' => 
  array (
    0 => 'array|false',
    'connection' => 'string',
    'table_name' => 'string',
    'values' => 'array',
    'flags=' => 'int',
  ),
  'pg_insert' => 
  array (
    0 => 'string',
    'connection' => 'string',
    'table_name' => 'string',
    'values' => 'array',
    'flags=' => 'int',
  ),
  'pg_update' => 
  array (
    0 => 'string|bool',
    'connection' => 'string',
    'table_name' => 'string',
    'values' => 'array',
    'conditions' => 'array',
    'flags=' => 'int',
  ),
  'pg_delete' => 
  array (
    0 => 'string|bool',
    'connection' => 'string',
    'table_name' => 'string',
    'conditions' => 'array',
    'flags=' => 'int',
  ),
  'pg_select' => 
  array (
    0 => 'array|string|false',
    'connection' => 'string',
    'table_name' => 'string',
    'conditions' => 'array',
    'flags=' => 'int',
    'mode=' => 'int',
  ),
  'sodium_crypto_aead_aes256gcm_is_available' => 
  array (
    0 => 'bool',
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
    0 => 'bool',
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
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_ref' => 
  array (
    0 => 'string',
    'loop' => 'string',
  ),
  'uv_unref' => 
  array (
    0 => 'string',
    'loop' => 'string',
  ),
  'uv_loop_new' => 
  array (
    0 => 'string',
  ),
  'uv_default_loop' => 
  array (
    0 => 'string',
  ),
  'uv_stop' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_run' => 
  array (
    0 => 'string',
    'loop=' => 'string',
    'run_mode=' => 'string',
  ),
  'uv_ip4_addr' => 
  array (
    0 => 'string',
    'address' => 'string',
    'port' => 'string',
  ),
  'uv_ip6_addr' => 
  array (
    0 => 'string',
    'address' => 'string',
    'port' => 'string',
  ),
  'uv_ip4_name' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_ip6_name' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_write' => 
  array (
    0 => 'string',
    'client' => 'string',
    'data' => 'string',
    'callback' => 'string',
  ),
  'uv_write2' => 
  array (
    0 => 'string',
    'client' => 'string',
    'data' => 'string',
    'send' => 'string',
    'callback' => 'string',
  ),
  'uv_shutdown' => 
  array (
    0 => 'string',
    'stream' => 'string',
    'callback' => 'string',
  ),
  'uv_close' => 
  array (
    0 => 'string',
    'stream' => 'string',
    'callback=' => 'string',
  ),
  'uv_now' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_loop_delete' => 
  array (
    0 => 'string',
    'loop' => 'string',
  ),
  'uv_read_start' => 
  array (
    0 => 'string',
    'server' => 'string',
    'callback' => 'string',
  ),
  'uv_read_stop' => 
  array (
    0 => 'string',
    'server' => 'string',
  ),
  'uv_err_name' => 
  array (
    0 => 'string',
    'error' => 'string',
  ),
  'uv_strerror' => 
  array (
    0 => 'string',
    'error' => 'string',
  ),
  'uv_is_active' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_is_closing' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_is_readable' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_is_writable' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_walk' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'callback' => 'string',
    'opaque=' => 'string',
  ),
  'uv_guess_handle' => 
  array (
    0 => 'string',
    'fd' => 'string',
  ),
  'uv_idle_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_idle_start' => 
  array (
    0 => 'string',
    'timer' => 'string',
    'callback' => 'string',
  ),
  'uv_idle_stop' => 
  array (
    0 => 'string',
    'idle' => 'string',
  ),
  'uv_timer_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_timer_start' => 
  array (
    0 => 'string',
    'timer' => 'string',
    'timeout' => 'string',
    'repeat' => 'string',
    'callback=' => 'string',
  ),
  'uv_timer_stop' => 
  array (
    0 => 'string',
    'timer' => 'string',
  ),
  'uv_timer_again' => 
  array (
    0 => 'string',
    'timer' => 'string',
  ),
  'uv_timer_set_repeat' => 
  array (
    0 => 'string',
    'timer' => 'string',
    'timeout' => 'string',
  ),
  'uv_timer_get_repeat' => 
  array (
    0 => 'string',
    'timer' => 'string',
  ),
  'uv_tcp_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_tcp_open' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'tcpfd' => 'string',
  ),
  'uv_tcp_nodelay' => 
  array (
    0 => 'string',
    'tcp' => 'string',
    'enabled' => 'string',
  ),
  'uv_tcp_bind' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'address' => 'string',
  ),
  'uv_tcp_bind6' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'address' => 'string',
  ),
  'uv_listen' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'backlog' => 'string',
    'callback' => 'string',
  ),
  'uv_accept' => 
  array (
    0 => 'string',
    'server' => 'string',
    'client' => 'string',
  ),
  'uv_tcp_connect' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'sock_addr' => 'string',
    'callback=' => 'string',
  ),
  'uv_tcp_connect6' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'ipv6_addr' => 'string',
    'callback=' => 'string',
  ),
  'uv_udp_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_udp_open' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'udpfd' => 'string',
  ),
  'uv_udp_bind' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'address' => 'string',
    'flags=' => 'string',
  ),
  'uv_udp_bind6' => 
  array (
    0 => 'string',
    'resource' => 'string',
    'address' => 'string',
    'flags=' => 'string',
  ),
  'uv_udp_set_multicast_loop' => 
  array (
    0 => 'string',
    'server' => 'string',
    'enabled' => 'string',
  ),
  'uv_udp_set_multicast_ttl' => 
  array (
    0 => 'string',
    'server' => 'string',
    'ttl' => 'string',
  ),
  'uv_udp_send' => 
  array (
    0 => 'string',
    'server' => 'string',
    'buffer' => 'string',
    'address' => 'string',
    'callback=' => 'string',
  ),
  'uv_udp_send6' => 
  array (
    0 => 'string',
    'server' => 'string',
    'buffer' => 'string',
    'address' => 'string',
    'callback=' => 'string',
  ),
  'uv_udp_recv_start' => 
  array (
    0 => 'string',
    'server' => 'string',
    'callback' => 'string',
  ),
  'uv_udp_recv_stop' => 
  array (
    0 => 'string',
    'server' => 'string',
  ),
  'uv_udp_set_membership' => 
  array (
    0 => 'string',
    'client' => 'string',
    'multicast_addr' => 'string',
    'interface_addr' => 'string',
    'membership' => 'string',
  ),
  'uv_udp_set_broadcast' => 
  array (
    0 => 'string',
    'server' => 'string',
    'enabled' => 'string',
  ),
  'uv_poll_init' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
  ),
  'uv_poll_init_socket' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
  ),
  'uv_poll_start' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'events' => 'string',
    'callback' => 'string',
  ),
  'uv_poll_stop' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_fs_poll_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_fs_poll_start' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'callback' => 'string',
    'path' => 'string',
    'interval' => 'string',
  ),
  'uv_fs_poll_stop' => 
  array (
    0 => 'string',
    'loop' => 'string',
  ),
  'uv_tcp_getsockname' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_tcp_getpeername' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_udp_getsockname' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_tcp_simultaneous_accepts' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'enable' => 'string',
  ),
  'uv_pipe_init' => 
  array (
    0 => 'string',
    'file=' => 'string',
    'ipc=' => 'string',
  ),
  'uv_pipe_bind' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'name' => 'string',
  ),
  'uv_pipe_open' => 
  array (
    0 => 'string',
    'file' => 'string',
    'pipe' => 'string',
  ),
  'uv_pipe_connect' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'name' => 'string',
    'callback' => 'string',
  ),
  'uv_pipe_pending_instances' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'count' => 'string',
  ),
  'uv_pipe_pending_count' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_pipe_pending_type' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_stdio_new' => 
  array (
    0 => 'string',
  ),
  'uv_spawn' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'command' => 'string',
    'args' => 'string',
    'stdio' => 'string',
    'cwd' => 'string',
    'env' => 'string',
    'callback' => 'string',
    'flags=' => 'string',
    'options=' => 'string',
  ),
  'uv_process_kill' => 
  array (
    0 => 'string',
    'process' => 'string',
    'signal' => 'string',
  ),
  'uv_process_get_pid' => 
  array (
    0 => 'string',
    'process' => 'string',
  ),
  'uv_kill' => 
  array (
    0 => 'string',
    'pid' => 'string',
    'signal' => 'string',
  ),
  'uv_getaddrinfo' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'callback' => 'string',
    'node' => 'string',
    'service' => 'string',
    'hints=' => 'string',
  ),
  'uv_rwlock_init' => 
  array (
    0 => 'string',
  ),
  'uv_rwlock_rdlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_rwlock_tryrdlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_rwlock_rdunlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_rwlock_wrlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_rwlock_trywrlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_rwlock_wrunlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_mutex_init' => 
  array (
    0 => 'string',
  ),
  'uv_mutex_lock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_mutex_trylock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_mutex_unlock' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_sem_init' => 
  array (
    0 => 'string',
    'val' => 'string',
  ),
  'uv_sem_post' => 
  array (
    0 => 'string',
    'resource' => 'string',
  ),
  'uv_sem_wait' => 
  array (
    0 => 'string',
    'resource' => 'string',
  ),
  'uv_sem_trywait' => 
  array (
    0 => 'string',
    'resource' => 'string',
  ),
  'uv_prepare_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_prepare_start' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'callback' => 'string',
  ),
  'uv_prepare_stop' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_check_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_check_start' => 
  array (
    0 => 'string',
    'handle' => 'string',
    'callback' => 'string',
  ),
  'uv_check_stop' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_async_init' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'callback' => 'string',
  ),
  'uv_async_send' => 
  array (
    0 => 'string',
    'handle' => 'string',
  ),
  'uv_fs_open' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'flag' => 'string',
    'mode' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_read' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'offset=' => 'string',
    'size=' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_write' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'buffer' => 'string',
    'offset' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_close' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_fsync' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_fdatasync' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_ftruncate' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'offset' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_mkdir' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'mode' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_rmdir' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_unlink' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_rename' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'from' => 'string',
    'to' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_utime' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'utime' => 'string',
    'atime' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_futime' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'utime' => 'string',
    'atime' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_chmod' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'mode' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_fchmod' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'mode' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_chown' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'uid' => 'string',
    'gid' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_fchown' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'uid' => 'string',
    'gid' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_link' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'from' => 'string',
    'to' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_symlink' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'from' => 'string',
    'to' => 'string',
    'callback' => 'string',
    'flags=' => 'string',
  ),
  'uv_fs_readlink' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_stat' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_lstat' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_fstat' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_readdir' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'flags' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_scandir' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'flags' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_sendfile' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'in' => 'string',
    'out' => 'string',
    'offset' => 'string',
    'length' => 'string',
    'callback=' => 'string',
  ),
  'uv_fs_event_init' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'path' => 'string',
    'callback' => 'string',
    'flags=' => 'string',
  ),
  'uv_tty_init' => 
  array (
    0 => 'string',
    'loop' => 'string',
    'fd' => 'string',
    'readable' => 'string',
  ),
  'uv_tty_get_winsize' => 
  array (
    0 => 'string',
    'tty' => 'string',
    '&width' => 'string',
    '&height' => 'string',
  ),
  'uv_tty_set_mode' => 
  array (
    0 => 'string',
  ),
  'uv_tty_reset_mode' => 
  array (
    0 => 'string',
  ),
  'uv_loadavg' => 
  array (
    0 => 'string',
  ),
  'uv_uptime' => 
  array (
    0 => 'string',
  ),
  'uv_cpu_info' => 
  array (
    0 => 'string',
  ),
  'uv_interface_addresses' => 
  array (
    0 => 'string',
  ),
  'uv_get_free_memory' => 
  array (
    0 => 'string',
  ),
  'uv_get_total_memory' => 
  array (
    0 => 'string',
  ),
  'uv_hrtime' => 
  array (
    0 => 'string',
  ),
  'uv_exepath' => 
  array (
    0 => 'string',
  ),
  'uv_cwd' => 
  array (
    0 => 'string',
  ),
  'uv_chdir' => 
  array (
    0 => 'string',
    'dir' => 'string',
  ),
  'uv_resident_set_memory' => 
  array (
    0 => 'string',
  ),
  'uv_signal_init' => 
  array (
    0 => 'string',
    'loop=' => 'string',
  ),
  'uv_signal_start' => 
  array (
    0 => 'string',
    'sig_handle' => 'string',
    'sig_callback' => 'string',
    'sig_num' => 'string',
  ),
  'uv_signal_stop' => 
  array (
    0 => 'string',
    'sig_handle' => 'string',
  ),
  'zip_open' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'zip_close' => 
  array (
    0 => 'void',
    'zip' => 'string',
  ),
  'zip_read' => 
  array (
    0 => 'string',
    'zip' => 'string',
  ),
  'zip_entry_open' => 
  array (
    0 => 'bool',
    'zip_dp' => 'string',
    'zip_entry' => 'string',
    'mode=' => 'string',
  ),
  'zip_entry_close' => 
  array (
    0 => 'bool',
    'zip_entry' => 'string',
  ),
  'zip_entry_read' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'string',
    'len=' => 'int',
  ),
  'zip_entry_name' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'string',
  ),
  'zip_entry_compressedsize' => 
  array (
    0 => 'int|false',
    'zip_entry' => 'string',
  ),
  'zip_entry_filesize' => 
  array (
    0 => 'int|false',
    'zip_entry' => 'string',
  ),
  'zip_entry_compressionmethod' => 
  array (
    0 => 'string|false',
    'zip_entry' => 'string',
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
  'opcache_get_configuration' => 
  array (
    0 => 'array|false',
  ),
  'opcache_is_script_cached' => 
  array (
    0 => 'bool',
    'filename' => 'string',
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
  'InternalIterator::__construct' => 
  array (
    0 => 'string',
  ),
  'InternalIterator::current' => 
  array (
    0 => 'string',
  ),
  'InternalIterator::key' => 
  array (
    0 => 'string',
  ),
  'InternalIterator::next' => 
  array (
    0 => 'void',
  ),
  'InternalIterator::valid' => 
  array (
    0 => 'bool',
  ),
  'InternalIterator::rewind' => 
  array (
    0 => 'void',
  ),
  'Exception::__clone' => 
  array (
    0 => 'void',
  ),
  'Exception::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'Exception::__wakeup' => 
  array (
    0 => 'string',
  ),
  'Exception::getMessage' => 
  array (
    0 => 'string',
  ),
  'Exception::getCode' => 
  array (
    0 => 'string',
  ),
  'Exception::getFile' => 
  array (
    0 => 'string',
  ),
  'Exception::getLine' => 
  array (
    0 => 'int',
  ),
  'Exception::getTrace' => 
  array (
    0 => 'array',
  ),
  'Exception::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'Exception::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'Exception::__toString' => 
  array (
    0 => 'string',
  ),
  'ErrorException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'severity=' => 'int',
    'filename=' => 'string|null',
    'line=' => 'int|null',
    'previous=' => 'Throwable|null',
  ),
  'ErrorException::getSeverity' => 
  array (
    0 => 'int',
  ),
  'ErrorException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ErrorException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ErrorException::getCode' => 
  array (
    0 => 'string',
  ),
  'ErrorException::getFile' => 
  array (
    0 => 'string',
  ),
  'ErrorException::getLine' => 
  array (
    0 => 'int',
  ),
  'ErrorException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ErrorException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ErrorException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ErrorException::__toString' => 
  array (
    0 => 'string',
  ),
  'Error::__clone' => 
  array (
    0 => 'void',
  ),
  'Error::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'Error::__wakeup' => 
  array (
    0 => 'string',
  ),
  'Error::getMessage' => 
  array (
    0 => 'string',
  ),
  'Error::getCode' => 
  array (
    0 => 'string',
  ),
  'Error::getFile' => 
  array (
    0 => 'string',
  ),
  'Error::getLine' => 
  array (
    0 => 'int',
  ),
  'Error::getTrace' => 
  array (
    0 => 'array',
  ),
  'Error::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'Error::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'Error::__toString' => 
  array (
    0 => 'string',
  ),
  'CompileError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'CompileError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'CompileError::getMessage' => 
  array (
    0 => 'string',
  ),
  'CompileError::getCode' => 
  array (
    0 => 'string',
  ),
  'CompileError::getFile' => 
  array (
    0 => 'string',
  ),
  'CompileError::getLine' => 
  array (
    0 => 'int',
  ),
  'CompileError::getTrace' => 
  array (
    0 => 'array',
  ),
  'CompileError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'CompileError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'CompileError::__toString' => 
  array (
    0 => 'string',
  ),
  'ParseError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ParseError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ParseError::getMessage' => 
  array (
    0 => 'string',
  ),
  'ParseError::getCode' => 
  array (
    0 => 'string',
  ),
  'ParseError::getFile' => 
  array (
    0 => 'string',
  ),
  'ParseError::getLine' => 
  array (
    0 => 'int',
  ),
  'ParseError::getTrace' => 
  array (
    0 => 'array',
  ),
  'ParseError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ParseError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ParseError::__toString' => 
  array (
    0 => 'string',
  ),
  'TypeError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'TypeError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'TypeError::getMessage' => 
  array (
    0 => 'string',
  ),
  'TypeError::getCode' => 
  array (
    0 => 'string',
  ),
  'TypeError::getFile' => 
  array (
    0 => 'string',
  ),
  'TypeError::getLine' => 
  array (
    0 => 'int',
  ),
  'TypeError::getTrace' => 
  array (
    0 => 'array',
  ),
  'TypeError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'TypeError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'TypeError::__toString' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ArgumentCountError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::getMessage' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::getCode' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::getFile' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::getLine' => 
  array (
    0 => 'int',
  ),
  'ArgumentCountError::getTrace' => 
  array (
    0 => 'array',
  ),
  'ArgumentCountError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ArgumentCountError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ArgumentCountError::__toString' => 
  array (
    0 => 'string',
  ),
  'ValueError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ValueError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ValueError::getMessage' => 
  array (
    0 => 'string',
  ),
  'ValueError::getCode' => 
  array (
    0 => 'string',
  ),
  'ValueError::getFile' => 
  array (
    0 => 'string',
  ),
  'ValueError::getLine' => 
  array (
    0 => 'int',
  ),
  'ValueError::getTrace' => 
  array (
    0 => 'array',
  ),
  'ValueError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ValueError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ValueError::__toString' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ArithmeticError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::getMessage' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::getCode' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::getFile' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::getLine' => 
  array (
    0 => 'int',
  ),
  'ArithmeticError::getTrace' => 
  array (
    0 => 'array',
  ),
  'ArithmeticError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ArithmeticError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ArithmeticError::__toString' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'DivisionByZeroError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::getMessage' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::getCode' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::getFile' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::getLine' => 
  array (
    0 => 'int',
  ),
  'DivisionByZeroError::getTrace' => 
  array (
    0 => 'array',
  ),
  'DivisionByZeroError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'DivisionByZeroError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'DivisionByZeroError::__toString' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'UnhandledMatchError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::getMessage' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::getCode' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::getFile' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::getLine' => 
  array (
    0 => 'int',
  ),
  'UnhandledMatchError::getTrace' => 
  array (
    0 => 'array',
  ),
  'UnhandledMatchError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'UnhandledMatchError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'UnhandledMatchError::__toString' => 
  array (
    0 => 'string',
  ),
  'Closure::__construct' => 
  array (
    0 => 'string',
  ),
  'Closure::bind' => 
  array (
    0 => 'Closure|null',
    'closure' => 'Closure',
    'newThis' => 'object|null',
    'newScope=' => 'object|string|null|null',
  ),
  'Closure::bindTo' => 
  array (
    0 => 'Closure|null',
    'newThis' => 'object|null',
    'newScope=' => 'object|string|null|null',
  ),
  'Closure::call' => 
  array (
    0 => 'mixed|null',
    'newThis' => 'object',
    '...args=' => 'mixed|null',
  ),
  'Closure::fromCallable' => 
  array (
    0 => 'Closure',
    'callback' => 'callable',
  ),
  'Closure::__invoke' => 
  array (
    0 => 'string',
  ),
  'Generator::rewind' => 
  array (
    0 => 'void',
  ),
  'Generator::valid' => 
  array (
    0 => 'bool',
  ),
  'Generator::current' => 
  array (
    0 => 'mixed|null',
  ),
  'Generator::key' => 
  array (
    0 => 'mixed|null',
  ),
  'Generator::next' => 
  array (
    0 => 'void',
  ),
  'Generator::send' => 
  array (
    0 => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Generator::throw' => 
  array (
    0 => 'mixed|null',
    'exception' => 'Throwable',
  ),
  'Generator::getReturn' => 
  array (
    0 => 'mixed|null',
  ),
  'ClosedGeneratorException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ClosedGeneratorException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ClosedGeneratorException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ClosedGeneratorException::getCode' => 
  array (
    0 => 'string',
  ),
  'ClosedGeneratorException::getFile' => 
  array (
    0 => 'string',
  ),
  'ClosedGeneratorException::getLine' => 
  array (
    0 => 'int',
  ),
  'ClosedGeneratorException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ClosedGeneratorException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ClosedGeneratorException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ClosedGeneratorException::__toString' => 
  array (
    0 => 'string',
  ),
  'WeakReference::__construct' => 
  array (
    0 => 'string',
  ),
  'WeakReference::create' => 
  array (
    0 => 'WeakReference',
    'object' => 'object',
  ),
  'WeakReference::get' => 
  array (
    0 => 'object|null',
  ),
  'WeakMap::offsetGet' => 
  array (
    0 => 'mixed|null',
    'object' => 'string',
  ),
  'WeakMap::offsetSet' => 
  array (
    0 => 'void',
    'object' => 'string',
    'value' => 'mixed|null',
  ),
  'WeakMap::offsetExists' => 
  array (
    0 => 'bool',
    'object' => 'string',
  ),
  'WeakMap::offsetUnset' => 
  array (
    0 => 'void',
    'object' => 'string',
  ),
  'WeakMap::count' => 
  array (
    0 => 'int',
  ),
  'WeakMap::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'Attribute::__construct' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'DateTime::__construct' => 
  array (
    0 => 'string',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'DateTime::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DateTime::__set_state' => 
  array (
    0 => 'string',
    'array' => 'array',
  ),
  'DateTime::createFromImmutable' => 
  array (
    0 => 'string',
    'object' => 'DateTimeImmutable',
  ),
  'DateTime::createFromInterface' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTimeInterface',
  ),
  'DateTime::createFromFormat' => 
  array (
    0 => 'string',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'DateTime::getLastErrors' => 
  array (
    0 => 'string',
  ),
  'DateTime::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'DateTime::modify' => 
  array (
    0 => 'string',
    'modifier' => 'string',
  ),
  'DateTime::add' => 
  array (
    0 => 'string',
    'interval' => 'DateInterval',
  ),
  'DateTime::sub' => 
  array (
    0 => 'string',
    'interval' => 'DateInterval',
  ),
  'DateTime::getTimezone' => 
  array (
    0 => 'string',
  ),
  'DateTime::setTimezone' => 
  array (
    0 => 'string',
    'timezone' => 'DateTimeZone',
  ),
  'DateTime::getOffset' => 
  array (
    0 => 'string',
  ),
  'DateTime::setTime' => 
  array (
    0 => 'string',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'DateTime::setDate' => 
  array (
    0 => 'string',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'DateTime::setISODate' => 
  array (
    0 => 'string',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'DateTime::setTimestamp' => 
  array (
    0 => 'string',
    'timestamp' => 'int',
  ),
  'DateTime::getTimestamp' => 
  array (
    0 => 'string',
  ),
  'DateTime::diff' => 
  array (
    0 => 'string',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'DateTimeImmutable::__construct' => 
  array (
    0 => 'string',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'DateTimeImmutable::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DateTimeImmutable::__set_state' => 
  array (
    0 => 'string',
    'array' => 'array',
  ),
  'DateTimeImmutable::createFromFormat' => 
  array (
    0 => 'string',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'DateTimeImmutable::getLastErrors' => 
  array (
    0 => 'string',
  ),
  'DateTimeImmutable::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'DateTimeImmutable::getTimezone' => 
  array (
    0 => 'string',
  ),
  'DateTimeImmutable::getOffset' => 
  array (
    0 => 'string',
  ),
  'DateTimeImmutable::getTimestamp' => 
  array (
    0 => 'string',
  ),
  'DateTimeImmutable::diff' => 
  array (
    0 => 'string',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'DateTimeImmutable::modify' => 
  array (
    0 => 'string',
    'modifier' => 'string',
  ),
  'DateTimeImmutable::add' => 
  array (
    0 => 'string',
    'interval' => 'DateInterval',
  ),
  'DateTimeImmutable::sub' => 
  array (
    0 => 'string',
    'interval' => 'DateInterval',
  ),
  'DateTimeImmutable::setTimezone' => 
  array (
    0 => 'string',
    'timezone' => 'DateTimeZone',
  ),
  'DateTimeImmutable::setTime' => 
  array (
    0 => 'string',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'DateTimeImmutable::setDate' => 
  array (
    0 => 'string',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'DateTimeImmutable::setISODate' => 
  array (
    0 => 'string',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'DateTimeImmutable::setTimestamp' => 
  array (
    0 => 'string',
    'timestamp' => 'int',
  ),
  'DateTimeImmutable::createFromMutable' => 
  array (
    0 => 'string',
    'object' => 'DateTime',
  ),
  'DateTimeImmutable::createFromInterface' => 
  array (
    0 => 'DateTimeImmutable',
    'object' => 'DateTimeInterface',
  ),
  'DateTimeZone::__construct' => 
  array (
    0 => 'string',
    'timezone' => 'string',
  ),
  'DateTimeZone::getName' => 
  array (
    0 => 'string',
  ),
  'DateTimeZone::getOffset' => 
  array (
    0 => 'string',
    'datetime' => 'DateTimeInterface',
  ),
  'DateTimeZone::getTransitions' => 
  array (
    0 => 'string',
    'timestampBegin=' => 'int',
    'timestampEnd=' => 'int',
  ),
  'DateTimeZone::getLocation' => 
  array (
    0 => 'string',
  ),
  'DateTimeZone::listAbbreviations' => 
  array (
    0 => 'string',
  ),
  'DateTimeZone::listIdentifiers' => 
  array (
    0 => 'string',
    'timezoneGroup=' => 'int',
    'countryCode=' => 'string|null',
  ),
  'DateTimeZone::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DateTimeZone::__set_state' => 
  array (
    0 => 'string',
    'array' => 'array',
  ),
  'DateInterval::__construct' => 
  array (
    0 => 'string',
    'duration' => 'string',
  ),
  'DateInterval::createFromDateString' => 
  array (
    0 => 'string',
    'datetime' => 'string',
  ),
  'DateInterval::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'DateInterval::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DateInterval::__set_state' => 
  array (
    0 => 'string',
    'array' => 'array',
  ),
  'DatePeriod::__construct' => 
  array (
    0 => 'string',
    'start' => 'string',
    'interval=' => 'string',
    'end=' => 'string',
    'options=' => 'string',
  ),
  'DatePeriod::getStartDate' => 
  array (
    0 => 'string',
  ),
  'DatePeriod::getEndDate' => 
  array (
    0 => 'string',
  ),
  'DatePeriod::getDateInterval' => 
  array (
    0 => 'string',
  ),
  'DatePeriod::getRecurrences' => 
  array (
    0 => 'string',
  ),
  'DatePeriod::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DatePeriod::__set_state' => 
  array (
    0 => 'string',
    'array' => 'array',
  ),
  'DatePeriod::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'SQLite3::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
  ),
  'SQLite3::open' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
  ),
  'SQLite3::close' => 
  array (
    0 => 'string',
  ),
  'SQLite3::version' => 
  array (
    0 => 'string',
  ),
  'SQLite3::lastInsertRowID' => 
  array (
    0 => 'string',
  ),
  'SQLite3::lastErrorCode' => 
  array (
    0 => 'string',
  ),
  'SQLite3::lastExtendedErrorCode' => 
  array (
    0 => 'string',
  ),
  'SQLite3::lastErrorMsg' => 
  array (
    0 => 'string',
  ),
  'SQLite3::changes' => 
  array (
    0 => 'string',
  ),
  'SQLite3::busyTimeout' => 
  array (
    0 => 'string',
    'milliseconds' => 'int',
  ),
  'SQLite3::loadExtension' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'SQLite3::backup' => 
  array (
    0 => 'string',
    'destination' => 'SQLite3',
    'sourceDatabase=' => 'string',
    'destinationDatabase=' => 'string',
  ),
  'SQLite3::escapeString' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'SQLite3::prepare' => 
  array (
    0 => 'string',
    'query' => 'string',
  ),
  'SQLite3::exec' => 
  array (
    0 => 'string',
    'query' => 'string',
  ),
  'SQLite3::query' => 
  array (
    0 => 'string',
    'query' => 'string',
  ),
  'SQLite3::querySingle' => 
  array (
    0 => 'string',
    'query' => 'string',
    'entireRow=' => 'bool',
  ),
  'SQLite3::createFunction' => 
  array (
    0 => 'string',
    'name' => 'string',
    'callback' => 'callable',
    'argCount=' => 'int',
    'flags=' => 'int',
  ),
  'SQLite3::createAggregate' => 
  array (
    0 => 'string',
    'name' => 'string',
    'stepCallback' => 'callable',
    'finalCallback' => 'callable',
    'argCount=' => 'int',
  ),
  'SQLite3::createCollation' => 
  array (
    0 => 'string',
    'name' => 'string',
    'callback' => 'callable',
  ),
  'SQLite3::openBlob' => 
  array (
    0 => 'string',
    'table' => 'string',
    'column' => 'string',
    'rowid' => 'int',
    'database=' => 'string',
    'flags=' => 'int',
  ),
  'SQLite3::enableExceptions' => 
  array (
    0 => 'string',
    'enable=' => 'bool',
  ),
  'SQLite3::enableExtendedResultCodes' => 
  array (
    0 => 'string',
    'enable=' => 'bool',
  ),
  'SQLite3::setAuthorizer' => 
  array (
    0 => 'string',
    'callback' => 'callable|null',
  ),
  'SQLite3Stmt::__construct' => 
  array (
    0 => 'string',
    'sqlite3' => 'SQLite3',
    'query' => 'string',
  ),
  'SQLite3Stmt::bindParam' => 
  array (
    0 => 'string',
    'param' => 'string|int',
    '&var' => 'mixed|null',
    'type=' => 'int',
  ),
  'SQLite3Stmt::bindValue' => 
  array (
    0 => 'string',
    'param' => 'string|int',
    'value' => 'mixed|null',
    'type=' => 'int',
  ),
  'SQLite3Stmt::clear' => 
  array (
    0 => 'string',
  ),
  'SQLite3Stmt::close' => 
  array (
    0 => 'string',
  ),
  'SQLite3Stmt::execute' => 
  array (
    0 => 'string',
  ),
  'SQLite3Stmt::getSQL' => 
  array (
    0 => 'string',
    'expand=' => 'bool',
  ),
  'SQLite3Stmt::paramCount' => 
  array (
    0 => 'string',
  ),
  'SQLite3Stmt::readOnly' => 
  array (
    0 => 'string',
  ),
  'SQLite3Stmt::reset' => 
  array (
    0 => 'string',
  ),
  'SQLite3Result::__construct' => 
  array (
    0 => 'string',
  ),
  'SQLite3Result::numColumns' => 
  array (
    0 => 'string',
  ),
  'SQLite3Result::columnName' => 
  array (
    0 => 'string',
    'column' => 'int',
  ),
  'SQLite3Result::columnType' => 
  array (
    0 => 'string',
    'column' => 'int',
  ),
  'SQLite3Result::fetchArray' => 
  array (
    0 => 'string',
    'mode=' => 'int',
  ),
  'SQLite3Result::reset' => 
  array (
    0 => 'string',
  ),
  'SQLite3Result::finalize' => 
  array (
    0 => 'string',
  ),
  'CURLFile::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'mime_type=' => 'string|null',
    'posted_filename=' => 'string|null',
  ),
  'CURLFile::getFilename' => 
  array (
    0 => 'string',
  ),
  'CURLFile::getMimeType' => 
  array (
    0 => 'string',
  ),
  'CURLFile::getPostFilename' => 
  array (
    0 => 'string',
  ),
  'CURLFile::setMimeType' => 
  array (
    0 => 'string',
    'mime_type' => 'string',
  ),
  'CURLFile::setPostFilename' => 
  array (
    0 => 'string',
    'posted_filename' => 'string',
  ),
  'DOMException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'DOMException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DOMException::getMessage' => 
  array (
    0 => 'string',
  ),
  'DOMException::getCode' => 
  array (
    0 => 'string',
  ),
  'DOMException::getFile' => 
  array (
    0 => 'string',
  ),
  'DOMException::getLine' => 
  array (
    0 => 'int',
  ),
  'DOMException::getTrace' => 
  array (
    0 => 'array',
  ),
  'DOMException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'DOMException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'DOMException::__toString' => 
  array (
    0 => 'string',
  ),
  'DOMImplementation::getFeature' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMImplementation::hasFeature' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMImplementation::createDocumentType' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'publicId=' => 'string',
    'systemId=' => 'string',
  ),
  'DOMImplementation::createDocument' => 
  array (
    0 => 'string',
    'namespace=' => 'string|null',
    'qualifiedName=' => 'string',
    'doctype=' => 'DOMDocumentType|null',
  ),
  'DOMNode::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMNode::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMNode::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMNode::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMNode::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMNode::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMNode::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMNode::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMNode::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMNode::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMNode::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMNode::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMNode::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMNode::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMNode::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMNode::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMNode::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMDocumentFragment::__construct' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::appendXML' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMDocumentFragment::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMDocumentFragment::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMDocumentFragment::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMDocumentFragment::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocumentFragment::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocumentFragment::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMDocumentFragment::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMDocumentFragment::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocumentFragment::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMDocumentFragment::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMDocumentFragment::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMDocumentFragment::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocumentFragment::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentFragment::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMDocumentFragment::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMDocument::__construct' => 
  array (
    0 => 'string',
    'version=' => 'string',
    'encoding=' => 'string',
  ),
  'DOMDocument::createAttribute' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'DOMDocument::createAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
  ),
  'DOMDocument::createCDATASection' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMDocument::createComment' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMDocument::createDocumentFragment' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::createElement' => 
  array (
    0 => 'string',
    'localName' => 'string',
    'value=' => 'string',
  ),
  'DOMDocument::createElementNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value=' => 'string',
  ),
  'DOMDocument::createEntityReference' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'DOMDocument::createProcessingInstruction' => 
  array (
    0 => 'string',
    'target' => 'string',
    'data=' => 'string',
  ),
  'DOMDocument::createTextNode' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMDocument::getElementById' => 
  array (
    0 => 'string',
    'elementId' => 'string',
  ),
  'DOMDocument::getElementsByTagName' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMDocument::getElementsByTagNameNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMDocument::importNode' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'DOMDocument::load' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::loadXML' => 
  array (
    0 => 'string',
    'source' => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::normalizeDocument' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::registerNodeClass' => 
  array (
    0 => 'string',
    'baseClass' => 'string',
    'extendedClass' => 'string|null',
  ),
  'DOMDocument::save' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::loadHTML' => 
  array (
    0 => 'string',
    'source' => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::loadHTMLFile' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::saveHTML' => 
  array (
    0 => 'string',
    'node=' => 'DOMNode|null',
  ),
  'DOMDocument::saveHTMLFile' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'DOMDocument::saveXML' => 
  array (
    0 => 'string',
    'node=' => 'DOMNode|null',
    'options=' => 'int',
  ),
  'DOMDocument::schemaValidate' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'DOMDocument::schemaValidateSource' => 
  array (
    0 => 'string',
    'source' => 'string',
    'flags=' => 'int',
  ),
  'DOMDocument::relaxNGValidate' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'DOMDocument::relaxNGValidateSource' => 
  array (
    0 => 'string',
    'source' => 'string',
  ),
  'DOMDocument::validate' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::xinclude' => 
  array (
    0 => 'string',
    'options=' => 'int',
  ),
  'DOMDocument::adoptNode' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMDocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMDocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMDocument::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMDocument::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocument::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocument::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMDocument::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMDocument::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocument::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMDocument::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMDocument::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMDocument::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocument::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMDocument::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMDocument::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMNodeList::count' => 
  array (
    0 => 'string',
  ),
  'DOMNodeList::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'DOMNodeList::item' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'DOMNamedNodeMap::getNamedItem' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMNamedNodeMap::getNamedItemNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMNamedNodeMap::item' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'DOMNamedNodeMap::count' => 
  array (
    0 => 'string',
  ),
  'DOMNamedNodeMap::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'DOMCharacterData::appendData' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMCharacterData::substringData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMCharacterData::insertData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'data' => 'string',
  ),
  'DOMCharacterData::deleteData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMCharacterData::replaceData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'DOMCharacterData::replaceWith' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCharacterData::remove' => 
  array (
    0 => 'void',
  ),
  'DOMCharacterData::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCharacterData::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCharacterData::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMCharacterData::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMCharacterData::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMCharacterData::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMCharacterData::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMCharacterData::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMCharacterData::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMCharacterData::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMCharacterData::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMCharacterData::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMCharacterData::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMCharacterData::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMCharacterData::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMCharacterData::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMCharacterData::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMCharacterData::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMCharacterData::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMAttr::__construct' => 
  array (
    0 => 'string',
    'name' => 'string',
    'value=' => 'string',
  ),
  'DOMAttr::isId' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMAttr::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMAttr::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMAttr::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMAttr::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMAttr::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMAttr::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMAttr::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMAttr::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMAttr::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMAttr::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMAttr::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMAttr::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMElement::__construct' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string',
  ),
  'DOMElement::getAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMElement::getAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMElement::getAttributeNode' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMElement::getAttributeNodeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMElement::getElementsByTagName' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMElement::getElementsByTagNameNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMElement::hasAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMElement::hasAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMElement::removeAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'DOMElement::removeAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'localName' => 'string',
  ),
  'DOMElement::removeAttributeNode' => 
  array (
    0 => 'string',
    'attr' => 'DOMAttr',
  ),
  'DOMElement::setAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'DOMElement::setAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string|null',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'DOMElement::setAttributeNode' => 
  array (
    0 => 'string',
    'attr' => 'DOMAttr',
  ),
  'DOMElement::setAttributeNodeNS' => 
  array (
    0 => 'string',
    'attr' => 'DOMAttr',
  ),
  'DOMElement::setIdAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'DOMElement::setIdAttributeNS' => 
  array (
    0 => 'string',
    'namespace' => 'string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'DOMElement::setIdAttributeNode' => 
  array (
    0 => 'string',
    'attr' => 'DOMAttr',
    'isId' => 'bool',
  ),
  'DOMElement::remove' => 
  array (
    0 => 'void',
  ),
  'DOMElement::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMElement::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMElement::replaceWith' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMElement::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMElement::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMElement::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMElement::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMElement::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMElement::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMElement::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMElement::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMElement::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMElement::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMElement::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMElement::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMElement::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMElement::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMElement::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMElement::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMElement::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMElement::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMElement::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMText::__construct' => 
  array (
    0 => 'string',
    'data=' => 'string',
  ),
  'DOMText::isWhitespaceInElementContent' => 
  array (
    0 => 'string',
  ),
  'DOMText::isElementContentWhitespace' => 
  array (
    0 => 'string',
  ),
  'DOMText::splitText' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'DOMText::appendData' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMText::substringData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMText::insertData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'data' => 'string',
  ),
  'DOMText::deleteData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMText::replaceData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'DOMText::replaceWith' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMText::remove' => 
  array (
    0 => 'void',
  ),
  'DOMText::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMText::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMText::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMText::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMText::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMText::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMText::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMText::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMText::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMText::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMText::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMText::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMText::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMText::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMText::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMText::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMText::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMText::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMText::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMComment::__construct' => 
  array (
    0 => 'string',
    'data=' => 'string',
  ),
  'DOMComment::appendData' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMComment::substringData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMComment::insertData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'data' => 'string',
  ),
  'DOMComment::deleteData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMComment::replaceData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'DOMComment::replaceWith' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMComment::remove' => 
  array (
    0 => 'void',
  ),
  'DOMComment::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMComment::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMComment::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMComment::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMComment::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMComment::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMComment::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMComment::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMComment::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMComment::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMComment::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMComment::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMComment::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMComment::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMComment::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMComment::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMComment::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMComment::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMComment::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMCdataSection::__construct' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMCdataSection::isWhitespaceInElementContent' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::isElementContentWhitespace' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::splitText' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'DOMCdataSection::appendData' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'DOMCdataSection::substringData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMCdataSection::insertData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'data' => 'string',
  ),
  'DOMCdataSection::deleteData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'DOMCdataSection::replaceData' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'DOMCdataSection::replaceWith' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCdataSection::remove' => 
  array (
    0 => 'void',
  ),
  'DOMCdataSection::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCdataSection::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'string',
  ),
  'DOMCdataSection::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMCdataSection::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMCdataSection::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMCdataSection::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMCdataSection::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMCdataSection::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMCdataSection::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMCdataSection::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMCdataSection::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMCdataSection::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMCdataSection::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMCdataSection::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMCdataSection::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMDocumentType::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMDocumentType::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocumentType::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMDocumentType::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMDocumentType::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentType::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentType::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentType::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentType::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMDocumentType::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocumentType::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMDocumentType::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMDocumentType::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMDocumentType::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMDocumentType::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMDocumentType::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMDocumentType::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMNotation::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMNotation::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMNotation::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMNotation::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMNotation::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMNotation::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMNotation::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMNotation::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMNotation::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMNotation::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMNotation::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMNotation::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMNotation::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMNotation::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMNotation::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMNotation::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMNotation::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMEntity::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMEntity::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMEntity::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMEntity::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMEntity::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMEntity::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMEntity::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMEntity::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMEntity::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMEntity::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMEntity::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMEntity::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMEntity::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMEntity::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMEntity::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMEntity::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMEntity::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMEntityReference::__construct' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'DOMEntityReference::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMEntityReference::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMEntityReference::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMEntityReference::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMEntityReference::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMEntityReference::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMEntityReference::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMEntityReference::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMEntityReference::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMEntityReference::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMEntityReference::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMEntityReference::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMEntityReference::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMEntityReference::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMEntityReference::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMEntityReference::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMEntityReference::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMProcessingInstruction::__construct' => 
  array (
    0 => 'string',
    'name' => 'string',
    'value=' => 'string',
  ),
  'DOMProcessingInstruction::appendChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
  ),
  'DOMProcessingInstruction::C14N' => 
  array (
    0 => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMProcessingInstruction::C14NFile' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array|null',
    'nsPrefixes=' => 'array|null',
  ),
  'DOMProcessingInstruction::cloneNode' => 
  array (
    0 => 'string',
    'deep=' => 'bool',
  ),
  'DOMProcessingInstruction::getLineNo' => 
  array (
    0 => 'string',
  ),
  'DOMProcessingInstruction::getNodePath' => 
  array (
    0 => 'string',
  ),
  'DOMProcessingInstruction::hasAttributes' => 
  array (
    0 => 'string',
  ),
  'DOMProcessingInstruction::hasChildNodes' => 
  array (
    0 => 'string',
  ),
  'DOMProcessingInstruction::insertBefore' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child=' => 'DOMNode|null',
  ),
  'DOMProcessingInstruction::isDefaultNamespace' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMProcessingInstruction::isSameNode' => 
  array (
    0 => 'string',
    'otherNode' => 'DOMNode',
  ),
  'DOMProcessingInstruction::isSupported' => 
  array (
    0 => 'string',
    'feature' => 'string',
    'version' => 'string',
  ),
  'DOMProcessingInstruction::lookupNamespaceURI' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
  ),
  'DOMProcessingInstruction::lookupPrefix' => 
  array (
    0 => 'string',
    'namespace' => 'string',
  ),
  'DOMProcessingInstruction::normalize' => 
  array (
    0 => 'string',
  ),
  'DOMProcessingInstruction::removeChild' => 
  array (
    0 => 'string',
    'child' => 'DOMNode',
  ),
  'DOMProcessingInstruction::replaceChild' => 
  array (
    0 => 'string',
    'node' => 'DOMNode',
    'child' => 'DOMNode',
  ),
  'DOMXPath::__construct' => 
  array (
    0 => 'string',
    'document' => 'DOMDocument',
    'registerNodeNS=' => 'bool',
  ),
  'DOMXPath::evaluate' => 
  array (
    0 => 'string',
    'expression' => 'string',
    'contextNode=' => 'DOMNode|null',
    'registerNodeNS=' => 'bool',
  ),
  'DOMXPath::query' => 
  array (
    0 => 'string',
    'expression' => 'string',
    'contextNode=' => 'DOMNode|null',
    'registerNodeNS=' => 'bool',
  ),
  'DOMXPath::registerNamespace' => 
  array (
    0 => 'string',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'DOMXPath::registerPhpFunctions' => 
  array (
    0 => 'string',
    'restrict=' => 'array|string|null|null',
  ),
  'finfo::__construct' => 
  array (
    0 => 'string',
    'flags=' => 'int',
    'magic_database=' => 'string|null',
  ),
  'finfo::file' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'finfo::buffer' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'string',
  ),
  'finfo::set_flags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'HashContext::__construct' => 
  array (
    0 => 'string',
  ),
  'HashContext::__serialize' => 
  array (
    0 => 'array',
  ),
  'HashContext::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'JsonException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'JsonException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'JsonException::getMessage' => 
  array (
    0 => 'string',
  ),
  'JsonException::getCode' => 
  array (
    0 => 'string',
  ),
  'JsonException::getFile' => 
  array (
    0 => 'string',
  ),
  'JsonException::getLine' => 
  array (
    0 => 'int',
  ),
  'JsonException::getTrace' => 
  array (
    0 => 'array',
  ),
  'JsonException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'JsonException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'JsonException::__toString' => 
  array (
    0 => 'string',
  ),
  'LogicException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'LogicException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'LogicException::getMessage' => 
  array (
    0 => 'string',
  ),
  'LogicException::getCode' => 
  array (
    0 => 'string',
  ),
  'LogicException::getFile' => 
  array (
    0 => 'string',
  ),
  'LogicException::getLine' => 
  array (
    0 => 'int',
  ),
  'LogicException::getTrace' => 
  array (
    0 => 'array',
  ),
  'LogicException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'LogicException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'LogicException::__toString' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'BadFunctionCallException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::getMessage' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::getCode' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::getFile' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::getLine' => 
  array (
    0 => 'int',
  ),
  'BadFunctionCallException::getTrace' => 
  array (
    0 => 'array',
  ),
  'BadFunctionCallException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'BadFunctionCallException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'BadFunctionCallException::__toString' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'BadMethodCallException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::getMessage' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::getCode' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::getFile' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::getLine' => 
  array (
    0 => 'int',
  ),
  'BadMethodCallException::getTrace' => 
  array (
    0 => 'array',
  ),
  'BadMethodCallException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'BadMethodCallException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'BadMethodCallException::__toString' => 
  array (
    0 => 'string',
  ),
  'DomainException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'DomainException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'DomainException::getMessage' => 
  array (
    0 => 'string',
  ),
  'DomainException::getCode' => 
  array (
    0 => 'string',
  ),
  'DomainException::getFile' => 
  array (
    0 => 'string',
  ),
  'DomainException::getLine' => 
  array (
    0 => 'int',
  ),
  'DomainException::getTrace' => 
  array (
    0 => 'array',
  ),
  'DomainException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'DomainException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'DomainException::__toString' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'InvalidArgumentException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::getMessage' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::getCode' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::getFile' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::getLine' => 
  array (
    0 => 'int',
  ),
  'InvalidArgumentException::getTrace' => 
  array (
    0 => 'array',
  ),
  'InvalidArgumentException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'InvalidArgumentException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'InvalidArgumentException::__toString' => 
  array (
    0 => 'string',
  ),
  'LengthException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'LengthException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'LengthException::getMessage' => 
  array (
    0 => 'string',
  ),
  'LengthException::getCode' => 
  array (
    0 => 'string',
  ),
  'LengthException::getFile' => 
  array (
    0 => 'string',
  ),
  'LengthException::getLine' => 
  array (
    0 => 'int',
  ),
  'LengthException::getTrace' => 
  array (
    0 => 'array',
  ),
  'LengthException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'LengthException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'LengthException::__toString' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'OutOfRangeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::getCode' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::getFile' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::getLine' => 
  array (
    0 => 'int',
  ),
  'OutOfRangeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'OutOfRangeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'OutOfRangeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'OutOfRangeException::__toString' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'RuntimeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::getCode' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::getFile' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::getLine' => 
  array (
    0 => 'int',
  ),
  'RuntimeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'RuntimeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'RuntimeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'RuntimeException::__toString' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'OutOfBoundsException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::getMessage' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::getCode' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::getFile' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::getLine' => 
  array (
    0 => 'int',
  ),
  'OutOfBoundsException::getTrace' => 
  array (
    0 => 'array',
  ),
  'OutOfBoundsException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'OutOfBoundsException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'OutOfBoundsException::__toString' => 
  array (
    0 => 'string',
  ),
  'OverflowException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'OverflowException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'OverflowException::getMessage' => 
  array (
    0 => 'string',
  ),
  'OverflowException::getCode' => 
  array (
    0 => 'string',
  ),
  'OverflowException::getFile' => 
  array (
    0 => 'string',
  ),
  'OverflowException::getLine' => 
  array (
    0 => 'int',
  ),
  'OverflowException::getTrace' => 
  array (
    0 => 'array',
  ),
  'OverflowException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'OverflowException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'OverflowException::__toString' => 
  array (
    0 => 'string',
  ),
  'RangeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'RangeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'RangeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'RangeException::getCode' => 
  array (
    0 => 'string',
  ),
  'RangeException::getFile' => 
  array (
    0 => 'string',
  ),
  'RangeException::getLine' => 
  array (
    0 => 'int',
  ),
  'RangeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'RangeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'RangeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'RangeException::__toString' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'UnderflowException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::getMessage' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::getCode' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::getFile' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::getLine' => 
  array (
    0 => 'int',
  ),
  'UnderflowException::getTrace' => 
  array (
    0 => 'array',
  ),
  'UnderflowException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'UnderflowException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'UnderflowException::__toString' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'UnexpectedValueException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::getMessage' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::getCode' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::getFile' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::getLine' => 
  array (
    0 => 'int',
  ),
  'UnexpectedValueException::getTrace' => 
  array (
    0 => 'array',
  ),
  'UnexpectedValueException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'UnexpectedValueException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'UnexpectedValueException::__toString' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Traversable',
    'mode=' => 'int',
    'flags=' => 'int',
  ),
  'RecursiveIteratorIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::getDepth' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::getSubIterator' => 
  array (
    0 => 'string',
    'level=' => 'int|null',
  ),
  'RecursiveIteratorIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::beginIteration' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::endIteration' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::callHasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::callGetChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::beginChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::endChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::nextElement' => 
  array (
    0 => 'string',
  ),
  'RecursiveIteratorIterator::setMaxDepth' => 
  array (
    0 => 'string',
    'maxDepth=' => 'int',
  ),
  'RecursiveIteratorIterator::getMaxDepth' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Traversable',
    'class=' => 'string|null',
  ),
  'IteratorIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::valid' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::key' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::current' => 
  array (
    0 => 'string',
  ),
  'IteratorIterator::next' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::accept' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'FilterIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::next' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::valid' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::key' => 
  array (
    0 => 'string',
  ),
  'FilterIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'RecursiveIterator',
  ),
  'RecursiveFilterIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::accept' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveFilterIterator::current' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'callback' => 'callable',
  ),
  'CallbackFilterIterator::accept' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::next' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::valid' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::key' => 
  array (
    0 => 'string',
  ),
  'CallbackFilterIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'RecursiveIterator',
    'callback' => 'callable',
  ),
  'RecursiveCallbackFilterIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::accept' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveCallbackFilterIterator::current' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'RecursiveIterator',
  ),
  'ParentIterator::accept' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::next' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::valid' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::key' => 
  array (
    0 => 'string',
  ),
  'ParentIterator::current' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'offset=' => 'int',
    'limit=' => 'int',
  ),
  'LimitIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::valid' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::next' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'LimitIterator::getPosition' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::key' => 
  array (
    0 => 'string',
  ),
  'LimitIterator::current' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'CachingIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::valid' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::next' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::hasNext' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'CachingIterator::offsetGet' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'CachingIterator::offsetSet' => 
  array (
    0 => 'string',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'CachingIterator::offsetUnset' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'CachingIterator::offsetExists' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'CachingIterator::getCache' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::count' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::key' => 
  array (
    0 => 'string',
  ),
  'CachingIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'RecursiveCachingIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::hasNext' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'RecursiveCachingIterator::offsetGet' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'RecursiveCachingIterator::offsetSet' => 
  array (
    0 => 'string',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RecursiveCachingIterator::offsetUnset' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'RecursiveCachingIterator::offsetExists' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'RecursiveCachingIterator::getCache' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::count' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveCachingIterator::current' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'NoRewindIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::valid' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::key' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::current' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::next' => 
  array (
    0 => 'string',
  ),
  'NoRewindIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::__construct' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::append' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'AppendIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::valid' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::current' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::next' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::getIteratorIndex' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::getArrayIterator' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'AppendIterator::key' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'InfiniteIterator::next' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::valid' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::key' => 
  array (
    0 => 'string',
  ),
  'InfiniteIterator::current' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'pattern' => 'string',
    'mode=' => 'int',
    'flags=' => 'int',
    'pregFlags=' => 'int',
  ),
  'RegexIterator::accept' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::getMode' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::setMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
  ),
  'RegexIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'RegexIterator::getRegex' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::getPregFlags' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::setPregFlags' => 
  array (
    0 => 'string',
    'pregFlags' => 'int',
  ),
  'RegexIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::next' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::key' => 
  array (
    0 => 'string',
  ),
  'RegexIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'RecursiveIterator',
    'pattern' => 'string',
    'mode=' => 'int',
    'flags=' => 'int',
    'pregFlags=' => 'int',
  ),
  'RecursiveRegexIterator::accept' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::getMode' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::setMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
  ),
  'RecursiveRegexIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'RecursiveRegexIterator::getRegex' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::getPregFlags' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::setPregFlags' => 
  array (
    0 => 'string',
    'pregFlags' => 'int',
  ),
  'RecursiveRegexIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveRegexIterator::current' => 
  array (
    0 => 'string',
  ),
  'EmptyIterator::current' => 
  array (
    0 => 'string',
  ),
  'EmptyIterator::next' => 
  array (
    0 => 'string',
  ),
  'EmptyIterator::key' => 
  array (
    0 => 'string',
  ),
  'EmptyIterator::valid' => 
  array (
    0 => 'string',
  ),
  'EmptyIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::__construct' => 
  array (
    0 => 'string',
    'iterator' => 'string',
    'flags=' => 'int',
    'cachingIteratorFlags=' => 'int',
    'mode=' => 'int',
  ),
  'RecursiveTreeIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::getPrefix' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::setPostfix' => 
  array (
    0 => 'string',
    'postfix' => 'string',
  ),
  'RecursiveTreeIterator::setPrefixPart' => 
  array (
    0 => 'string',
    'part' => 'int',
    'value' => 'string',
  ),
  'RecursiveTreeIterator::getEntry' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::getPostfix' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::getDepth' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::getSubIterator' => 
  array (
    0 => 'string',
    'level=' => 'int|null',
  ),
  'RecursiveTreeIterator::getInnerIterator' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::beginIteration' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::endIteration' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::callHasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::callGetChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::beginChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::endChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::nextElement' => 
  array (
    0 => 'string',
  ),
  'RecursiveTreeIterator::setMaxDepth' => 
  array (
    0 => 'string',
    'maxDepth=' => 'int',
  ),
  'RecursiveTreeIterator::getMaxDepth' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::__construct' => 
  array (
    0 => 'string',
    'array=' => 'object|array',
    'flags=' => 'int',
    'iteratorClass=' => 'string',
  ),
  'ArrayObject::offsetExists' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayObject::offsetGet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayObject::offsetSet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'ArrayObject::offsetUnset' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayObject::append' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'ArrayObject::getArrayCopy' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::count' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::getFlags' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'ArrayObject::asort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'ArrayObject::ksort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'ArrayObject::uasort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'ArrayObject::uksort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'ArrayObject::natsort' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::natcasesort' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'ArrayObject::serialize' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::__serialize' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'ArrayObject::getIterator' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::exchangeArray' => 
  array (
    0 => 'string',
    'array' => 'object|array',
  ),
  'ArrayObject::setIteratorClass' => 
  array (
    0 => 'string',
    'iteratorClass' => 'string',
  ),
  'ArrayObject::getIteratorClass' => 
  array (
    0 => 'string',
  ),
  'ArrayObject::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::__construct' => 
  array (
    0 => 'string',
    'array=' => 'object|array',
    'flags=' => 'int',
  ),
  'ArrayIterator::offsetExists' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayIterator::offsetGet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayIterator::offsetSet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'ArrayIterator::offsetUnset' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'ArrayIterator::append' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'ArrayIterator::getArrayCopy' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::count' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'ArrayIterator::asort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'ArrayIterator::ksort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'ArrayIterator::uasort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'ArrayIterator::uksort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'ArrayIterator::natsort' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::natcasesort' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'ArrayIterator::serialize' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::__serialize' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'ArrayIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::current' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::key' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::next' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::valid' => 
  array (
    0 => 'string',
  ),
  'ArrayIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'ArrayIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::__construct' => 
  array (
    0 => 'string',
    'array=' => 'object|array',
    'flags=' => 'int',
  ),
  'RecursiveArrayIterator::offsetExists' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'RecursiveArrayIterator::offsetGet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'RecursiveArrayIterator::offsetSet' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'RecursiveArrayIterator::offsetUnset' => 
  array (
    0 => 'string',
    'key' => 'mixed|null',
  ),
  'RecursiveArrayIterator::append' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'RecursiveArrayIterator::getArrayCopy' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::count' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'RecursiveArrayIterator::asort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'RecursiveArrayIterator::ksort' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'RecursiveArrayIterator::uasort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'RecursiveArrayIterator::uksort' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'RecursiveArrayIterator::natsort' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::natcasesort' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'RecursiveArrayIterator::serialize' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::__serialize' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'RecursiveArrayIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveArrayIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'RecursiveArrayIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'SplFileInfo::getPath' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getFilename' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getExtension' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'SplFileInfo::getPathname' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getPerms' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getInode' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getSize' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getOwner' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getGroup' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getATime' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getMTime' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getCTime' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getType' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isWritable' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isReadable' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isExecutable' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isFile' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isDir' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::isLink' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getRealPath' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplFileInfo::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplFileInfo::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'SplFileInfo::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplFileInfo::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplFileInfo::__toString' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplFileInfo::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::__construct' => 
  array (
    0 => 'string',
    'directory' => 'string',
  ),
  'DirectoryIterator::getFilename' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getExtension' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'DirectoryIterator::isDot' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::valid' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::key' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::current' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::next' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'DirectoryIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getPath' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getPathname' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getPerms' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getInode' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getSize' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getOwner' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getGroup' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getATime' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getMTime' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getCTime' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getType' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isWritable' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isReadable' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isExecutable' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isFile' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isDir' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::isLink' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getRealPath' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'DirectoryIterator::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'DirectoryIterator::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'DirectoryIterator::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'DirectoryIterator::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'DirectoryIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'DirectoryIterator::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::__construct' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'FilesystemIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::key' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::current' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'FilesystemIterator::getFilename' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getExtension' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'FilesystemIterator::isDot' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::valid' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::next' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'FilesystemIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getPath' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getPathname' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getPerms' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getInode' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getSize' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getOwner' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getGroup' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getATime' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getMTime' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getCTime' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getType' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isWritable' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isReadable' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isExecutable' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isFile' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isDir' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::isLink' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getRealPath' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'FilesystemIterator::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'FilesystemIterator::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'FilesystemIterator::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'FilesystemIterator::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'FilesystemIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'FilesystemIterator::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::__construct' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'RecursiveDirectoryIterator::hasChildren' => 
  array (
    0 => 'string',
    'allowLinks=' => 'bool',
  ),
  'RecursiveDirectoryIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getSubPath' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getSubPathname' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::key' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::current' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'RecursiveDirectoryIterator::getFilename' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getExtension' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'RecursiveDirectoryIterator::isDot' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::valid' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::next' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'RecursiveDirectoryIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getPath' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getPathname' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getPerms' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getInode' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getSize' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getOwner' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getGroup' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getATime' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getMTime' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getCTime' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getType' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isWritable' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isReadable' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isExecutable' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isFile' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isDir' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::isLink' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getRealPath' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'RecursiveDirectoryIterator::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'RecursiveDirectoryIterator::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'RecursiveDirectoryIterator::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'RecursiveDirectoryIterator::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'RecursiveDirectoryIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'RecursiveDirectoryIterator::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::__construct' => 
  array (
    0 => 'string',
    'pattern' => 'string',
    'flags=' => 'int',
  ),
  'GlobIterator::count' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::key' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::current' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'GlobIterator::getFilename' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getExtension' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'GlobIterator::isDot' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::valid' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::next' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'GlobIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getPath' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getPathname' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getPerms' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getInode' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getSize' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getOwner' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getGroup' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getATime' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getMTime' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getCTime' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getType' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isWritable' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isReadable' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isExecutable' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isFile' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isDir' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::isLink' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getRealPath' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'GlobIterator::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'GlobIterator::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'GlobIterator::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'GlobIterator::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'GlobIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'GlobIterator::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'SplFileObject::rewind' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::eof' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::valid' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::fgets' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::fread' => 
  array (
    0 => 'string',
    'length' => 'int',
  ),
  'SplFileObject::fgetcsv' => 
  array (
    0 => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplFileObject::fputcsv' => 
  array (
    0 => 'string',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplFileObject::setCsvControl' => 
  array (
    0 => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplFileObject::getCsvControl' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::flock' => 
  array (
    0 => 'string',
    'operation' => 'int',
    '&wouldBlock=' => 'string',
  ),
  'SplFileObject::fflush' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::ftell' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::fseek' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'SplFileObject::fgetc' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::fpassthru' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::fscanf' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...&vars=' => 'mixed|null',
  ),
  'SplFileObject::fwrite' => 
  array (
    0 => 'string',
    'data' => 'string',
    'length=' => 'int',
  ),
  'SplFileObject::fstat' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::ftruncate' => 
  array (
    0 => 'string',
    'size' => 'int',
  ),
  'SplFileObject::current' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::key' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::next' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'SplFileObject::getFlags' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::setMaxLineLen' => 
  array (
    0 => 'string',
    'maxLength' => 'int',
  ),
  'SplFileObject::getMaxLineLen' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::hasChildren' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getChildren' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::seek' => 
  array (
    0 => 'string',
    'line' => 'int',
  ),
  'SplFileObject::getCurrentLine' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::__toString' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getPath' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getFilename' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getExtension' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'SplFileObject::getPathname' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getPerms' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getInode' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getSize' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getOwner' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getGroup' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getATime' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getMTime' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getCTime' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getType' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isWritable' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isReadable' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isExecutable' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isFile' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isDir' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::isLink' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getRealPath' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplFileObject::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplFileObject::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'SplFileObject::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplFileObject::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplFileObject::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplFileObject::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::__construct' => 
  array (
    0 => 'string',
    'maxMemory=' => 'int',
  ),
  'SplTempFileObject::rewind' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::eof' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::valid' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::fgets' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::fread' => 
  array (
    0 => 'string',
    'length' => 'int',
  ),
  'SplTempFileObject::fgetcsv' => 
  array (
    0 => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplTempFileObject::fputcsv' => 
  array (
    0 => 'string',
    'fields' => 'array',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplTempFileObject::setCsvControl' => 
  array (
    0 => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'SplTempFileObject::getCsvControl' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::flock' => 
  array (
    0 => 'string',
    'operation' => 'int',
    '&wouldBlock=' => 'string',
  ),
  'SplTempFileObject::fflush' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::ftell' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::fseek' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'SplTempFileObject::fgetc' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::fpassthru' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::fscanf' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...&vars=' => 'mixed|null',
  ),
  'SplTempFileObject::fwrite' => 
  array (
    0 => 'string',
    'data' => 'string',
    'length=' => 'int',
  ),
  'SplTempFileObject::fstat' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::ftruncate' => 
  array (
    0 => 'string',
    'size' => 'int',
  ),
  'SplTempFileObject::current' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::key' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::next' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'SplTempFileObject::getFlags' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::setMaxLineLen' => 
  array (
    0 => 'string',
    'maxLength' => 'int',
  ),
  'SplTempFileObject::getMaxLineLen' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::hasChildren' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getChildren' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::seek' => 
  array (
    0 => 'string',
    'line' => 'int',
  ),
  'SplTempFileObject::getCurrentLine' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::__toString' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getPath' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getFilename' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getExtension' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'SplTempFileObject::getPathname' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getPerms' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getInode' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getSize' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getOwner' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getGroup' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getATime' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getMTime' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getCTime' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getType' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isWritable' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isReadable' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isExecutable' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isFile' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isDir' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::isLink' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getRealPath' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplTempFileObject::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'SplTempFileObject::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'SplTempFileObject::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplTempFileObject::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'SplTempFileObject::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplTempFileObject::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::add' => 
  array (
    0 => 'string',
    'index' => 'int',
    'value' => 'mixed|null',
  ),
  'SplDoublyLinkedList::pop' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::shift' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::push' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplDoublyLinkedList::unshift' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplDoublyLinkedList::top' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::bottom' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::count' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::setIteratorMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
  ),
  'SplDoublyLinkedList::getIteratorMode' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::offsetExists' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplDoublyLinkedList::offsetGet' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplDoublyLinkedList::offsetSet' => 
  array (
    0 => 'string',
    'index' => 'string',
    'value' => 'mixed|null',
  ),
  'SplDoublyLinkedList::offsetUnset' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplDoublyLinkedList::rewind' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::current' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::key' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::prev' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::next' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::valid' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'SplDoublyLinkedList::serialize' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::__serialize' => 
  array (
    0 => 'string',
  ),
  'SplDoublyLinkedList::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'SplQueue::enqueue' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplQueue::dequeue' => 
  array (
    0 => 'string',
  ),
  'SplQueue::add' => 
  array (
    0 => 'string',
    'index' => 'int',
    'value' => 'mixed|null',
  ),
  'SplQueue::pop' => 
  array (
    0 => 'string',
  ),
  'SplQueue::shift' => 
  array (
    0 => 'string',
  ),
  'SplQueue::push' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplQueue::unshift' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplQueue::top' => 
  array (
    0 => 'string',
  ),
  'SplQueue::bottom' => 
  array (
    0 => 'string',
  ),
  'SplQueue::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplQueue::count' => 
  array (
    0 => 'string',
  ),
  'SplQueue::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplQueue::setIteratorMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
  ),
  'SplQueue::getIteratorMode' => 
  array (
    0 => 'string',
  ),
  'SplQueue::offsetExists' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplQueue::offsetGet' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplQueue::offsetSet' => 
  array (
    0 => 'string',
    'index' => 'string',
    'value' => 'mixed|null',
  ),
  'SplQueue::offsetUnset' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplQueue::rewind' => 
  array (
    0 => 'string',
  ),
  'SplQueue::current' => 
  array (
    0 => 'string',
  ),
  'SplQueue::key' => 
  array (
    0 => 'string',
  ),
  'SplQueue::prev' => 
  array (
    0 => 'string',
  ),
  'SplQueue::next' => 
  array (
    0 => 'string',
  ),
  'SplQueue::valid' => 
  array (
    0 => 'string',
  ),
  'SplQueue::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'SplQueue::serialize' => 
  array (
    0 => 'string',
  ),
  'SplQueue::__serialize' => 
  array (
    0 => 'string',
  ),
  'SplQueue::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'SplStack::add' => 
  array (
    0 => 'string',
    'index' => 'int',
    'value' => 'mixed|null',
  ),
  'SplStack::pop' => 
  array (
    0 => 'string',
  ),
  'SplStack::shift' => 
  array (
    0 => 'string',
  ),
  'SplStack::push' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplStack::unshift' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplStack::top' => 
  array (
    0 => 'string',
  ),
  'SplStack::bottom' => 
  array (
    0 => 'string',
  ),
  'SplStack::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplStack::count' => 
  array (
    0 => 'string',
  ),
  'SplStack::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplStack::setIteratorMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
  ),
  'SplStack::getIteratorMode' => 
  array (
    0 => 'string',
  ),
  'SplStack::offsetExists' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplStack::offsetGet' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplStack::offsetSet' => 
  array (
    0 => 'string',
    'index' => 'string',
    'value' => 'mixed|null',
  ),
  'SplStack::offsetUnset' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplStack::rewind' => 
  array (
    0 => 'string',
  ),
  'SplStack::current' => 
  array (
    0 => 'string',
  ),
  'SplStack::key' => 
  array (
    0 => 'string',
  ),
  'SplStack::prev' => 
  array (
    0 => 'string',
  ),
  'SplStack::next' => 
  array (
    0 => 'string',
  ),
  'SplStack::valid' => 
  array (
    0 => 'string',
  ),
  'SplStack::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'SplStack::serialize' => 
  array (
    0 => 'string',
  ),
  'SplStack::__serialize' => 
  array (
    0 => 'string',
  ),
  'SplStack::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'SplHeap::extract' => 
  array (
    0 => 'string',
  ),
  'SplHeap::insert' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplHeap::top' => 
  array (
    0 => 'string',
  ),
  'SplHeap::count' => 
  array (
    0 => 'string',
  ),
  'SplHeap::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplHeap::rewind' => 
  array (
    0 => 'string',
  ),
  'SplHeap::current' => 
  array (
    0 => 'string',
  ),
  'SplHeap::key' => 
  array (
    0 => 'string',
  ),
  'SplHeap::next' => 
  array (
    0 => 'string',
  ),
  'SplHeap::valid' => 
  array (
    0 => 'string',
  ),
  'SplHeap::recoverFromCorruption' => 
  array (
    0 => 'string',
  ),
  'SplHeap::compare' => 
  array (
    0 => 'string',
    'value1' => 'mixed|null',
    'value2' => 'mixed|null',
  ),
  'SplHeap::isCorrupted' => 
  array (
    0 => 'string',
  ),
  'SplHeap::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::compare' => 
  array (
    0 => 'string',
    'value1' => 'mixed|null',
    'value2' => 'mixed|null',
  ),
  'SplMinHeap::extract' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::insert' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplMinHeap::top' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::count' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::rewind' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::current' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::key' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::next' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::valid' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::recoverFromCorruption' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::isCorrupted' => 
  array (
    0 => 'string',
  ),
  'SplMinHeap::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::compare' => 
  array (
    0 => 'string',
    'value1' => 'mixed|null',
    'value2' => 'mixed|null',
  ),
  'SplMaxHeap::extract' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::insert' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'SplMaxHeap::top' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::count' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::rewind' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::current' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::key' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::next' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::valid' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::recoverFromCorruption' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::isCorrupted' => 
  array (
    0 => 'string',
  ),
  'SplMaxHeap::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::compare' => 
  array (
    0 => 'string',
    'priority1' => 'mixed|null',
    'priority2' => 'mixed|null',
  ),
  'SplPriorityQueue::insert' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
    'priority' => 'mixed|null',
  ),
  'SplPriorityQueue::setExtractFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'SplPriorityQueue::top' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::extract' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::count' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::isEmpty' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::rewind' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::current' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::key' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::next' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::valid' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::recoverFromCorruption' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::isCorrupted' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::getExtractFlags' => 
  array (
    0 => 'string',
  ),
  'SplPriorityQueue::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'SplFixedArray::__construct' => 
  array (
    0 => 'string',
    'size=' => 'int',
  ),
  'SplFixedArray::__wakeup' => 
  array (
    0 => 'string',
  ),
  'SplFixedArray::count' => 
  array (
    0 => 'string',
  ),
  'SplFixedArray::toArray' => 
  array (
    0 => 'string',
  ),
  'SplFixedArray::fromArray' => 
  array (
    0 => 'string',
    'array' => 'array',
    'preserveKeys=' => 'bool',
  ),
  'SplFixedArray::getSize' => 
  array (
    0 => 'string',
  ),
  'SplFixedArray::setSize' => 
  array (
    0 => 'string',
    'size' => 'int',
  ),
  'SplFixedArray::offsetExists' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplFixedArray::offsetGet' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplFixedArray::offsetSet' => 
  array (
    0 => 'string',
    'index' => 'string',
    'value' => 'mixed|null',
  ),
  'SplFixedArray::offsetUnset' => 
  array (
    0 => 'string',
    'index' => 'string',
  ),
  'SplFixedArray::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'SplObjectStorage::attach' => 
  array (
    0 => 'string',
    'object' => 'object',
    'info=' => 'mixed|null',
  ),
  'SplObjectStorage::detach' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'SplObjectStorage::contains' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'SplObjectStorage::addAll' => 
  array (
    0 => 'string',
    'storage' => 'SplObjectStorage',
  ),
  'SplObjectStorage::removeAll' => 
  array (
    0 => 'string',
    'storage' => 'SplObjectStorage',
  ),
  'SplObjectStorage::removeAllExcept' => 
  array (
    0 => 'string',
    'storage' => 'SplObjectStorage',
  ),
  'SplObjectStorage::getInfo' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::setInfo' => 
  array (
    0 => 'string',
    'info' => 'mixed|null',
  ),
  'SplObjectStorage::count' => 
  array (
    0 => 'string',
    'mode=' => 'int',
  ),
  'SplObjectStorage::rewind' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::valid' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::key' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::current' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::next' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::unserialize' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'SplObjectStorage::serialize' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::offsetExists' => 
  array (
    0 => 'string',
    'object' => 'string',
  ),
  'SplObjectStorage::offsetGet' => 
  array (
    0 => 'string',
    'object' => 'string',
  ),
  'SplObjectStorage::offsetSet' => 
  array (
    0 => 'string',
    'object' => 'string',
    'info=' => 'mixed|null',
  ),
  'SplObjectStorage::offsetUnset' => 
  array (
    0 => 'string',
    'object' => 'string',
  ),
  'SplObjectStorage::getHash' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'SplObjectStorage::__serialize' => 
  array (
    0 => 'string',
  ),
  'SplObjectStorage::__unserialize' => 
  array (
    0 => 'string',
    'data' => 'array',
  ),
  'SplObjectStorage::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::__construct' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'MultipleIterator::getFlags' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'MultipleIterator::attachIterator' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
    'info=' => 'string|int|null|null',
  ),
  'MultipleIterator::detachIterator' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'MultipleIterator::containsIterator' => 
  array (
    0 => 'string',
    'iterator' => 'Iterator',
  ),
  'MultipleIterator::countIterators' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::valid' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::key' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::current' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::next' => 
  array (
    0 => 'string',
  ),
  'MultipleIterator::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'PDOException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'PDOException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'PDOException::getMessage' => 
  array (
    0 => 'string',
  ),
  'PDOException::getCode' => 
  array (
    0 => 'string',
  ),
  'PDOException::getFile' => 
  array (
    0 => 'string',
  ),
  'PDOException::getLine' => 
  array (
    0 => 'int',
  ),
  'PDOException::getTrace' => 
  array (
    0 => 'array',
  ),
  'PDOException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'PDOException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'PDOException::__toString' => 
  array (
    0 => 'string',
  ),
  'PDO::__construct' => 
  array (
    0 => 'string',
    'dsn' => 'string',
    'username=' => 'string|null',
    'password=' => 'string|null',
    'options=' => 'array|null',
  ),
  'PDO::beginTransaction' => 
  array (
    0 => 'string',
  ),
  'PDO::commit' => 
  array (
    0 => 'string',
  ),
  'PDO::errorCode' => 
  array (
    0 => 'string',
  ),
  'PDO::errorInfo' => 
  array (
    0 => 'string',
  ),
  'PDO::exec' => 
  array (
    0 => 'string',
    'statement' => 'string',
  ),
  'PDO::getAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
  ),
  'PDO::getAvailableDrivers' => 
  array (
    0 => 'string',
  ),
  'PDO::inTransaction' => 
  array (
    0 => 'string',
  ),
  'PDO::lastInsertId' => 
  array (
    0 => 'string',
    'name=' => 'string|null',
  ),
  'PDO::prepare' => 
  array (
    0 => 'string',
    'query' => 'string',
    'options=' => 'array',
  ),
  'PDO::query' => 
  array (
    0 => 'string',
    'query' => 'string',
    'fetchMode=' => 'int|null',
    '...fetchModeArgs=' => 'mixed|null',
  ),
  'PDO::quote' => 
  array (
    0 => 'string',
    'string' => 'string',
    'type=' => 'int',
  ),
  'PDO::rollBack' => 
  array (
    0 => 'string',
  ),
  'PDO::setAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
    'value' => 'mixed|null',
  ),
  'PDOStatement::bindColumn' => 
  array (
    0 => 'string',
    'column' => 'string|int',
    '&var' => 'mixed|null',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed|null',
  ),
  'PDOStatement::bindParam' => 
  array (
    0 => 'string',
    'param' => 'string|int',
    '&var' => 'mixed|null',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed|null',
  ),
  'PDOStatement::bindValue' => 
  array (
    0 => 'string',
    'param' => 'string|int',
    'value' => 'mixed|null',
    'type=' => 'int',
  ),
  'PDOStatement::closeCursor' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::columnCount' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::debugDumpParams' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::errorCode' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::errorInfo' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::execute' => 
  array (
    0 => 'string',
    'params=' => 'array|null',
  ),
  'PDOStatement::fetch' => 
  array (
    0 => 'string',
    'mode=' => 'int',
    'cursorOrientation=' => 'int',
    'cursorOffset=' => 'int',
  ),
  'PDOStatement::fetchAll' => 
  array (
    0 => 'string',
    'mode=' => 'int',
    '...args=' => 'mixed|null',
  ),
  'PDOStatement::fetchColumn' => 
  array (
    0 => 'string',
    'column=' => 'int',
  ),
  'PDOStatement::fetchObject' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
    'constructorArgs=' => 'array',
  ),
  'PDOStatement::getAttribute' => 
  array (
    0 => 'string',
    'name' => 'int',
  ),
  'PDOStatement::getColumnMeta' => 
  array (
    0 => 'string',
    'column' => 'int',
  ),
  'PDOStatement::nextRowset' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::rowCount' => 
  array (
    0 => 'string',
  ),
  'PDOStatement::setAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
    'value' => 'mixed|null',
  ),
  'PDOStatement::setFetchMode' => 
  array (
    0 => 'string',
    'mode' => 'int',
    '...args=' => 'mixed|null',
  ),
  'PDOStatement::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'SessionHandler::open' => 
  array (
    0 => 'string',
    'path' => 'string',
    'name' => 'string',
  ),
  'SessionHandler::close' => 
  array (
    0 => 'string',
  ),
  'SessionHandler::read' => 
  array (
    0 => 'string',
    'id' => 'string',
  ),
  'SessionHandler::write' => 
  array (
    0 => 'string',
    'id' => 'string',
    'data' => 'string',
  ),
  'SessionHandler::destroy' => 
  array (
    0 => 'string',
    'id' => 'string',
  ),
  'SessionHandler::gc' => 
  array (
    0 => 'string',
    'max_lifetime' => 'int',
  ),
  'SessionHandler::create_sid' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ReflectionException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::getCode' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::getFile' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::getLine' => 
  array (
    0 => 'int',
  ),
  'ReflectionException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ReflectionException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ReflectionException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ReflectionException::__toString' => 
  array (
    0 => 'string',
  ),
  'Reflection::getModifierNames' => 
  array (
    0 => 'string',
    'modifiers' => 'int',
  ),
  'ReflectionFunctionAbstract::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionFunctionAbstract::inNamespace' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isClosure' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isDeprecated' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isInternal' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isUserDefined' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isGenerator' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::isVariadic' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getClosureThis' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getClosureScopeClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getClosureCalledClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getEndLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getExtension' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getExtensionName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getFileName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getNamespaceName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getNumberOfParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getNumberOfRequiredParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getShortName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getStartLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getStaticVariables' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::returnsReference' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::hasReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunctionAbstract::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionFunctionAbstract::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::__construct' => 
  array (
    0 => 'string',
    'function' => 'Closure|string',
  ),
  'ReflectionFunction::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isDisabled' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::invoke' => 
  array (
    0 => 'string',
    '...args=' => 'mixed|null',
  ),
  'ReflectionFunction::invokeArgs' => 
  array (
    0 => 'string',
    'args' => 'array',
  ),
  'ReflectionFunction::getClosure' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::inNamespace' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isClosure' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isDeprecated' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isInternal' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isUserDefined' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isGenerator' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::isVariadic' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getClosureThis' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getClosureScopeClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getClosureCalledClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getEndLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getExtension' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getExtensionName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getFileName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getNamespaceName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getNumberOfParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getNumberOfRequiredParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getShortName' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getStartLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getStaticVariables' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::returnsReference' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::hasReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionFunction::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionGenerator::__construct' => 
  array (
    0 => 'string',
    'generator' => 'Generator',
  ),
  'ReflectionGenerator::getExecutingLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionGenerator::getExecutingFile' => 
  array (
    0 => 'string',
  ),
  'ReflectionGenerator::getTrace' => 
  array (
    0 => 'string',
    'options=' => 'int',
  ),
  'ReflectionGenerator::getFunction' => 
  array (
    0 => 'string',
  ),
  'ReflectionGenerator::getThis' => 
  array (
    0 => 'string',
  ),
  'ReflectionGenerator::getExecutingGenerator' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionParameter::__construct' => 
  array (
    0 => 'string',
    'function' => 'string',
    'param' => 'string|int',
  ),
  'ReflectionParameter::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isPassedByReference' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::canBePassedByValue' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getDeclaringFunction' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getDeclaringClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::hasType' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getType' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isArray' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isCallable' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::allowsNull' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getPosition' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isOptional' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isDefaultValueAvailable' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getDefaultValue' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isDefaultValueConstant' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::getDefaultValueConstantName' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isVariadic' => 
  array (
    0 => 'string',
  ),
  'ReflectionParameter::isPromoted' => 
  array (
    0 => 'bool',
  ),
  'ReflectionParameter::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionType::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionType::allowsNull' => 
  array (
    0 => 'string',
  ),
  'ReflectionType::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionNamedType::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionNamedType::isBuiltin' => 
  array (
    0 => 'string',
  ),
  'ReflectionNamedType::allowsNull' => 
  array (
    0 => 'string',
  ),
  'ReflectionNamedType::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionUnionType::getTypes' => 
  array (
    0 => 'array',
  ),
  'ReflectionUnionType::allowsNull' => 
  array (
    0 => 'string',
  ),
  'ReflectionUnionType::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::__construct' => 
  array (
    0 => 'string',
    'objectOrMethod' => 'object|string',
    'method=' => 'string|null',
  ),
  'ReflectionMethod::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isPublic' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isPrivate' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isProtected' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isAbstract' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isFinal' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isStatic' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isConstructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isDestructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getClosure' => 
  array (
    0 => 'string',
    'object=' => 'object|null',
  ),
  'ReflectionMethod::getModifiers' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::invoke' => 
  array (
    0 => 'string',
    'object' => 'object|null',
    '...args=' => 'mixed|null',
  ),
  'ReflectionMethod::invokeArgs' => 
  array (
    0 => 'string',
    'object' => 'object|null',
    'args' => 'array',
  ),
  'ReflectionMethod::getDeclaringClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getPrototype' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::setAccessible' => 
  array (
    0 => 'string',
    'accessible' => 'bool',
  ),
  'ReflectionMethod::inNamespace' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isClosure' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isDeprecated' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isInternal' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isUserDefined' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isGenerator' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::isVariadic' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getClosureThis' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getClosureScopeClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getClosureCalledClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getEndLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getExtension' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getExtensionName' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getFileName' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getNamespaceName' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getNumberOfParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getNumberOfRequiredParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getParameters' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getShortName' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getStartLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getStaticVariables' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::returnsReference' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::hasReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getReturnType' => 
  array (
    0 => 'string',
  ),
  'ReflectionMethod::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionClass::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionClass::__construct' => 
  array (
    0 => 'string',
    'objectOrClass' => 'object|string',
  ),
  'ReflectionClass::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isInternal' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isUserDefined' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isAnonymous' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isInstantiable' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isCloneable' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getFileName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getStartLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getEndLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getConstructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::hasMethod' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getMethod' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getMethods' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionClass::hasProperty' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getProperty' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getProperties' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionClass::hasConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getConstants' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionClass::getReflectionConstants' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionClass::getConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getReflectionConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionClass::getInterfaces' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getInterfaceNames' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isInterface' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getTraits' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getTraitNames' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getTraitAliases' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isTrait' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isAbstract' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isFinal' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getModifiers' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isInstance' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'ReflectionClass::newInstance' => 
  array (
    0 => 'string',
    '...args=' => 'mixed|null',
  ),
  'ReflectionClass::newInstanceWithoutConstructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::newInstanceArgs' => 
  array (
    0 => 'string',
    'args=' => 'array',
  ),
  'ReflectionClass::getParentClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isSubclassOf' => 
  array (
    0 => 'string',
    'class' => 'ReflectionClass|string',
  ),
  'ReflectionClass::getStaticProperties' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getStaticPropertyValue' => 
  array (
    0 => 'string',
    'name' => 'string',
    'default=' => 'mixed|null',
  ),
  'ReflectionClass::setStaticPropertyValue' => 
  array (
    0 => 'string',
    'name' => 'string',
    'value' => 'mixed|null',
  ),
  'ReflectionClass::getDefaultProperties' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isIterable' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::isIterateable' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::implementsInterface' => 
  array (
    0 => 'string',
    'interface' => 'ReflectionClass|string',
  ),
  'ReflectionClass::getExtension' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getExtensionName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::inNamespace' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getNamespaceName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getShortName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClass::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionObject::__construct' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'ReflectionObject::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isInternal' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isUserDefined' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isAnonymous' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isInstantiable' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isCloneable' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getFileName' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getStartLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getEndLine' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getConstructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::hasMethod' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getMethod' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getMethods' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionObject::hasProperty' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getProperty' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getProperties' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionObject::hasConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getConstants' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionObject::getReflectionConstants' => 
  array (
    0 => 'string',
    'filter=' => 'int|null',
  ),
  'ReflectionObject::getConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getReflectionConstant' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionObject::getInterfaces' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getInterfaceNames' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isInterface' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getTraits' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getTraitNames' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getTraitAliases' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isTrait' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isAbstract' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isFinal' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getModifiers' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isInstance' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'ReflectionObject::newInstance' => 
  array (
    0 => 'string',
    '...args=' => 'mixed|null',
  ),
  'ReflectionObject::newInstanceWithoutConstructor' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::newInstanceArgs' => 
  array (
    0 => 'string',
    'args=' => 'array',
  ),
  'ReflectionObject::getParentClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isSubclassOf' => 
  array (
    0 => 'string',
    'class' => 'ReflectionClass|string',
  ),
  'ReflectionObject::getStaticProperties' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getStaticPropertyValue' => 
  array (
    0 => 'string',
    'name' => 'string',
    'default=' => 'mixed|null',
  ),
  'ReflectionObject::setStaticPropertyValue' => 
  array (
    0 => 'string',
    'name' => 'string',
    'value' => 'mixed|null',
  ),
  'ReflectionObject::getDefaultProperties' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isIterable' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::isIterateable' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::implementsInterface' => 
  array (
    0 => 'string',
    'interface' => 'ReflectionClass|string',
  ),
  'ReflectionObject::getExtension' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getExtensionName' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::inNamespace' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getNamespaceName' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getShortName' => 
  array (
    0 => 'string',
  ),
  'ReflectionObject::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionProperty::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionProperty::__construct' => 
  array (
    0 => 'string',
    'class' => 'object|string',
    'property' => 'string',
  ),
  'ReflectionProperty::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::getValue' => 
  array (
    0 => 'string',
    'object=' => 'object|null',
  ),
  'ReflectionProperty::setValue' => 
  array (
    0 => 'string',
    'objectOrValue' => 'mixed|null',
    'value=' => 'mixed|null',
  ),
  'ReflectionProperty::isInitialized' => 
  array (
    0 => 'string',
    'object=' => 'object|null',
  ),
  'ReflectionProperty::isPublic' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::isPrivate' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::isProtected' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::isStatic' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::isDefault' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::isPromoted' => 
  array (
    0 => 'bool',
  ),
  'ReflectionProperty::getModifiers' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::getDeclaringClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::setAccessible' => 
  array (
    0 => 'string',
    'accessible' => 'bool',
  ),
  'ReflectionProperty::getType' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::hasType' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::hasDefaultValue' => 
  array (
    0 => 'bool',
  ),
  'ReflectionProperty::getDefaultValue' => 
  array (
    0 => 'string',
  ),
  'ReflectionProperty::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionClassConstant::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionClassConstant::__construct' => 
  array (
    0 => 'string',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'ReflectionClassConstant::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getValue' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::isPublic' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::isPrivate' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::isProtected' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getModifiers' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getDeclaringClass' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getDocComment' => 
  array (
    0 => 'string',
  ),
  'ReflectionClassConstant::getAttributes' => 
  array (
    0 => 'array',
    'name=' => 'string|null',
    'flags=' => 'int',
  ),
  'ReflectionExtension::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionExtension::__construct' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionExtension::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getVersion' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getFunctions' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getConstants' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getINIEntries' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getClasses' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getClassNames' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::getDependencies' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::info' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::isPersistent' => 
  array (
    0 => 'string',
  ),
  'ReflectionExtension::isTemporary' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionZendExtension::__construct' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ReflectionZendExtension::__toString' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::getVersion' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::getAuthor' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::getURL' => 
  array (
    0 => 'string',
  ),
  'ReflectionZendExtension::getCopyright' => 
  array (
    0 => 'string',
  ),
  'ReflectionReference::fromArrayElement' => 
  array (
    0 => 'ReflectionReference|null',
    'array' => 'array',
    'key' => 'string|int',
  ),
  'ReflectionReference::getId' => 
  array (
    0 => 'string',
  ),
  'ReflectionReference::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionReference::__construct' => 
  array (
    0 => 'string',
  ),
  'ReflectionAttribute::getName' => 
  array (
    0 => 'string',
  ),
  'ReflectionAttribute::getTarget' => 
  array (
    0 => 'int',
  ),
  'ReflectionAttribute::isRepeated' => 
  array (
    0 => 'bool',
  ),
  'ReflectionAttribute::getArguments' => 
  array (
    0 => 'array',
  ),
  'ReflectionAttribute::newInstance' => 
  array (
    0 => 'object',
  ),
  'ReflectionAttribute::__clone' => 
  array (
    0 => 'void',
  ),
  'ReflectionAttribute::__construct' => 
  array (
    0 => 'string',
  ),
  'php_user_filter::filter' => 
  array (
    0 => 'string',
    'in' => 'string',
    'out' => 'string',
    '&consumed' => 'string',
    'closing' => 'bool',
  ),
  'php_user_filter::onCreate' => 
  array (
    0 => 'string',
  ),
  'php_user_filter::onClose' => 
  array (
    0 => 'string',
  ),
  'Directory::close' => 
  array (
    0 => 'string',
  ),
  'Directory::rewind' => 
  array (
    0 => 'string',
  ),
  'Directory::read' => 
  array (
    0 => 'string',
  ),
  'AssertionError::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AssertionError::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AssertionError::getMessage' => 
  array (
    0 => 'string',
  ),
  'AssertionError::getCode' => 
  array (
    0 => 'string',
  ),
  'AssertionError::getFile' => 
  array (
    0 => 'string',
  ),
  'AssertionError::getLine' => 
  array (
    0 => 'int',
  ),
  'AssertionError::getTrace' => 
  array (
    0 => 'array',
  ),
  'AssertionError::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AssertionError::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AssertionError::__toString' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::xpath' => 
  array (
    0 => 'string',
    'expression' => 'string',
  ),
  'SimpleXMLElement::registerXPathNamespace' => 
  array (
    0 => 'string',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'SimpleXMLElement::asXML' => 
  array (
    0 => 'string',
    'filename=' => 'string|null',
  ),
  'SimpleXMLElement::saveXML' => 
  array (
    0 => 'string',
    'filename=' => 'string|null',
  ),
  'SimpleXMLElement::getNamespaces' => 
  array (
    0 => 'string',
    'recursive=' => 'bool',
  ),
  'SimpleXMLElement::getDocNamespaces' => 
  array (
    0 => 'string',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'SimpleXMLElement::children' => 
  array (
    0 => 'string',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLElement::attributes' => 
  array (
    0 => 'string',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLElement::__construct' => 
  array (
    0 => 'string',
    'data' => 'string',
    'options=' => 'int',
    'dataIsURL=' => 'bool',
    'namespaceOrPrefix=' => 'string',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLElement::addChild' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string|null',
  ),
  'SimpleXMLElement::addAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'string|null',
  ),
  'SimpleXMLElement::getName' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::__toString' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::count' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::rewind' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::valid' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::current' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::key' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::next' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::hasChildren' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLElement::getChildren' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::xpath' => 
  array (
    0 => 'string',
    'expression' => 'string',
  ),
  'SimpleXMLIterator::registerXPathNamespace' => 
  array (
    0 => 'string',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'SimpleXMLIterator::asXML' => 
  array (
    0 => 'string',
    'filename=' => 'string|null',
  ),
  'SimpleXMLIterator::saveXML' => 
  array (
    0 => 'string',
    'filename=' => 'string|null',
  ),
  'SimpleXMLIterator::getNamespaces' => 
  array (
    0 => 'string',
    'recursive=' => 'bool',
  ),
  'SimpleXMLIterator::getDocNamespaces' => 
  array (
    0 => 'string',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'SimpleXMLIterator::children' => 
  array (
    0 => 'string',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLIterator::attributes' => 
  array (
    0 => 'string',
    'namespaceOrPrefix=' => 'string|null',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLIterator::__construct' => 
  array (
    0 => 'string',
    'data' => 'string',
    'options=' => 'int',
    'dataIsURL=' => 'bool',
    'namespaceOrPrefix=' => 'string',
    'isPrefix=' => 'bool',
  ),
  'SimpleXMLIterator::addChild' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value=' => 'string|null',
    'namespace=' => 'string|null',
  ),
  'SimpleXMLIterator::addAttribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'string|null',
  ),
  'SimpleXMLIterator::getName' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::__toString' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::count' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::valid' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::current' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::key' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::next' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::hasChildren' => 
  array (
    0 => 'string',
  ),
  'SimpleXMLIterator::getChildren' => 
  array (
    0 => 'string',
  ),
  'PharException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'PharException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'PharException::getMessage' => 
  array (
    0 => 'string',
  ),
  'PharException::getCode' => 
  array (
    0 => 'string',
  ),
  'PharException::getFile' => 
  array (
    0 => 'string',
  ),
  'PharException::getLine' => 
  array (
    0 => 'int',
  ),
  'PharException::getTrace' => 
  array (
    0 => 'array',
  ),
  'PharException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'PharException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'PharException::__toString' => 
  array (
    0 => 'string',
  ),
  'Phar::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'string|null',
  ),
  'Phar::__destruct' => 
  array (
    0 => 'string',
  ),
  'Phar::addEmptyDir' => 
  array (
    0 => 'string',
    'directory' => 'string',
  ),
  'Phar::addFile' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'localName=' => 'string|null',
  ),
  'Phar::addFromString' => 
  array (
    0 => 'string',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'Phar::buildFromDirectory' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'Phar::buildFromIterator' => 
  array (
    0 => 'string',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'string|null',
  ),
  'Phar::compressFiles' => 
  array (
    0 => 'string',
    'compression' => 'int',
  ),
  'Phar::decompressFiles' => 
  array (
    0 => 'string',
  ),
  'Phar::compress' => 
  array (
    0 => 'string',
    'compression' => 'int',
    'extension=' => 'string|null',
  ),
  'Phar::decompress' => 
  array (
    0 => 'string',
    'extension=' => 'string|null',
  ),
  'Phar::convertToExecutable' => 
  array (
    0 => 'string',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'Phar::convertToData' => 
  array (
    0 => 'string',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'Phar::copy' => 
  array (
    0 => 'string',
    'from' => 'string',
    'to' => 'string',
  ),
  'Phar::count' => 
  array (
    0 => 'string',
    'mode=' => 'int',
  ),
  'Phar::delete' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'Phar::delMetadata' => 
  array (
    0 => 'string',
  ),
  'Phar::extractTo' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'files=' => 'array|string|null|null',
    'overwrite=' => 'bool',
  ),
  'Phar::getAlias' => 
  array (
    0 => 'string',
  ),
  'Phar::getPath' => 
  array (
    0 => 'string',
  ),
  'Phar::getMetadata' => 
  array (
    0 => 'string',
    'unserializeOptions=' => 'array',
  ),
  'Phar::getModified' => 
  array (
    0 => 'string',
  ),
  'Phar::getSignature' => 
  array (
    0 => 'string',
  ),
  'Phar::getStub' => 
  array (
    0 => 'string',
  ),
  'Phar::getVersion' => 
  array (
    0 => 'string',
  ),
  'Phar::hasMetadata' => 
  array (
    0 => 'string',
  ),
  'Phar::isBuffering' => 
  array (
    0 => 'string',
  ),
  'Phar::isCompressed' => 
  array (
    0 => 'string',
  ),
  'Phar::isFileFormat' => 
  array (
    0 => 'string',
    'format' => 'int',
  ),
  'Phar::isWritable' => 
  array (
    0 => 'string',
  ),
  'Phar::offsetExists' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'Phar::offsetGet' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'Phar::offsetSet' => 
  array (
    0 => 'string',
    'localName' => 'string',
    'value' => 'string',
  ),
  'Phar::offsetUnset' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'Phar::setAlias' => 
  array (
    0 => 'string',
    'alias' => 'string',
  ),
  'Phar::setDefaultStub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'Phar::setMetadata' => 
  array (
    0 => 'string',
    'metadata' => 'mixed|null',
  ),
  'Phar::setSignatureAlgorithm' => 
  array (
    0 => 'string',
    'algo' => 'int',
    'privateKey=' => 'string|null',
  ),
  'Phar::setStub' => 
  array (
    0 => 'string',
    'stub' => 'string',
    'length=' => 'int',
  ),
  'Phar::startBuffering' => 
  array (
    0 => 'string',
  ),
  'Phar::stopBuffering' => 
  array (
    0 => 'string',
  ),
  'Phar::apiVersion' => 
  array (
    0 => 'string',
  ),
  'Phar::canCompress' => 
  array (
    0 => 'bool',
    'compression=' => 'int',
  ),
  'Phar::canWrite' => 
  array (
    0 => 'bool',
  ),
  'Phar::createDefaultStub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'Phar::getSupportedCompression' => 
  array (
    0 => 'array',
  ),
  'Phar::getSupportedSignatures' => 
  array (
    0 => 'array',
  ),
  'Phar::interceptFileFuncs' => 
  array (
    0 => 'void',
  ),
  'Phar::isValidPharFilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'Phar::loadPhar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'string|null',
  ),
  'Phar::mapPhar' => 
  array (
    0 => 'bool',
    'alias=' => 'string|null',
    'offset=' => 'int',
  ),
  'Phar::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'Phar::mount' => 
  array (
    0 => 'void',
    'pharPath' => 'string',
    'externalPath' => 'string',
  ),
  'Phar::mungServer' => 
  array (
    0 => 'void',
    'variables' => 'array',
  ),
  'Phar::unlinkArchive' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Phar::webPhar' => 
  array (
    0 => 'void',
    'alias=' => 'string|null',
    'index=' => 'string|null',
    'fileNotFoundScript=' => 'string|null',
    'mimeTypes=' => 'array',
    'rewrite=' => 'callable|null',
  ),
  'Phar::hasChildren' => 
  array (
    0 => 'string',
    'allowLinks=' => 'bool',
  ),
  'Phar::getChildren' => 
  array (
    0 => 'string',
  ),
  'Phar::getSubPath' => 
  array (
    0 => 'string',
  ),
  'Phar::getSubPathname' => 
  array (
    0 => 'string',
  ),
  'Phar::rewind' => 
  array (
    0 => 'string',
  ),
  'Phar::key' => 
  array (
    0 => 'string',
  ),
  'Phar::current' => 
  array (
    0 => 'string',
  ),
  'Phar::getFlags' => 
  array (
    0 => 'string',
  ),
  'Phar::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'Phar::getFilename' => 
  array (
    0 => 'string',
  ),
  'Phar::getExtension' => 
  array (
    0 => 'string',
  ),
  'Phar::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'Phar::isDot' => 
  array (
    0 => 'string',
  ),
  'Phar::valid' => 
  array (
    0 => 'string',
  ),
  'Phar::next' => 
  array (
    0 => 'string',
  ),
  'Phar::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'Phar::__toString' => 
  array (
    0 => 'string',
  ),
  'Phar::getPathname' => 
  array (
    0 => 'string',
  ),
  'Phar::getPerms' => 
  array (
    0 => 'string',
  ),
  'Phar::getInode' => 
  array (
    0 => 'string',
  ),
  'Phar::getSize' => 
  array (
    0 => 'string',
  ),
  'Phar::getOwner' => 
  array (
    0 => 'string',
  ),
  'Phar::getGroup' => 
  array (
    0 => 'string',
  ),
  'Phar::getATime' => 
  array (
    0 => 'string',
  ),
  'Phar::getMTime' => 
  array (
    0 => 'string',
  ),
  'Phar::getCTime' => 
  array (
    0 => 'string',
  ),
  'Phar::getType' => 
  array (
    0 => 'string',
  ),
  'Phar::isReadable' => 
  array (
    0 => 'string',
  ),
  'Phar::isExecutable' => 
  array (
    0 => 'string',
  ),
  'Phar::isFile' => 
  array (
    0 => 'string',
  ),
  'Phar::isDir' => 
  array (
    0 => 'string',
  ),
  'Phar::isLink' => 
  array (
    0 => 'string',
  ),
  'Phar::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'Phar::getRealPath' => 
  array (
    0 => 'string',
  ),
  'Phar::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'Phar::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'Phar::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'Phar::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'Phar::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'Phar::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'Phar::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'PharData::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'string|null',
    'format=' => 'int',
  ),
  'PharData::__destruct' => 
  array (
    0 => 'string',
  ),
  'PharData::addEmptyDir' => 
  array (
    0 => 'string',
    'directory' => 'string',
  ),
  'PharData::addFile' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'localName=' => 'string|null',
  ),
  'PharData::addFromString' => 
  array (
    0 => 'string',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'PharData::buildFromDirectory' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'PharData::buildFromIterator' => 
  array (
    0 => 'string',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'string|null',
  ),
  'PharData::compressFiles' => 
  array (
    0 => 'string',
    'compression' => 'int',
  ),
  'PharData::decompressFiles' => 
  array (
    0 => 'string',
  ),
  'PharData::compress' => 
  array (
    0 => 'string',
    'compression' => 'int',
    'extension=' => 'string|null',
  ),
  'PharData::decompress' => 
  array (
    0 => 'string',
    'extension=' => 'string|null',
  ),
  'PharData::convertToExecutable' => 
  array (
    0 => 'string',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'PharData::convertToData' => 
  array (
    0 => 'string',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'string|null',
  ),
  'PharData::copy' => 
  array (
    0 => 'string',
    'from' => 'string',
    'to' => 'string',
  ),
  'PharData::count' => 
  array (
    0 => 'string',
    'mode=' => 'int',
  ),
  'PharData::delete' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'PharData::delMetadata' => 
  array (
    0 => 'string',
  ),
  'PharData::extractTo' => 
  array (
    0 => 'string',
    'directory' => 'string',
    'files=' => 'array|string|null|null',
    'overwrite=' => 'bool',
  ),
  'PharData::getAlias' => 
  array (
    0 => 'string',
  ),
  'PharData::getPath' => 
  array (
    0 => 'string',
  ),
  'PharData::getMetadata' => 
  array (
    0 => 'string',
    'unserializeOptions=' => 'array',
  ),
  'PharData::getModified' => 
  array (
    0 => 'string',
  ),
  'PharData::getSignature' => 
  array (
    0 => 'string',
  ),
  'PharData::getStub' => 
  array (
    0 => 'string',
  ),
  'PharData::getVersion' => 
  array (
    0 => 'string',
  ),
  'PharData::hasMetadata' => 
  array (
    0 => 'string',
  ),
  'PharData::isBuffering' => 
  array (
    0 => 'string',
  ),
  'PharData::isCompressed' => 
  array (
    0 => 'string',
  ),
  'PharData::isFileFormat' => 
  array (
    0 => 'string',
    'format' => 'int',
  ),
  'PharData::isWritable' => 
  array (
    0 => 'string',
  ),
  'PharData::offsetExists' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'PharData::offsetGet' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'PharData::offsetSet' => 
  array (
    0 => 'string',
    'localName' => 'string',
    'value' => 'string',
  ),
  'PharData::offsetUnset' => 
  array (
    0 => 'string',
    'localName' => 'string',
  ),
  'PharData::setAlias' => 
  array (
    0 => 'string',
    'alias' => 'string',
  ),
  'PharData::setDefaultStub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'PharData::setMetadata' => 
  array (
    0 => 'string',
    'metadata' => 'mixed|null',
  ),
  'PharData::setSignatureAlgorithm' => 
  array (
    0 => 'string',
    'algo' => 'int',
    'privateKey=' => 'string|null',
  ),
  'PharData::setStub' => 
  array (
    0 => 'string',
    'stub' => 'string',
    'length=' => 'int',
  ),
  'PharData::startBuffering' => 
  array (
    0 => 'string',
  ),
  'PharData::stopBuffering' => 
  array (
    0 => 'string',
  ),
  'PharData::apiVersion' => 
  array (
    0 => 'string',
  ),
  'PharData::canCompress' => 
  array (
    0 => 'bool',
    'compression=' => 'int',
  ),
  'PharData::canWrite' => 
  array (
    0 => 'bool',
  ),
  'PharData::createDefaultStub' => 
  array (
    0 => 'string',
    'index=' => 'string|null',
    'webIndex=' => 'string|null',
  ),
  'PharData::getSupportedCompression' => 
  array (
    0 => 'array',
  ),
  'PharData::getSupportedSignatures' => 
  array (
    0 => 'array',
  ),
  'PharData::interceptFileFuncs' => 
  array (
    0 => 'void',
  ),
  'PharData::isValidPharFilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'PharData::loadPhar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'string|null',
  ),
  'PharData::mapPhar' => 
  array (
    0 => 'bool',
    'alias=' => 'string|null',
    'offset=' => 'int',
  ),
  'PharData::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'PharData::mount' => 
  array (
    0 => 'void',
    'pharPath' => 'string',
    'externalPath' => 'string',
  ),
  'PharData::mungServer' => 
  array (
    0 => 'void',
    'variables' => 'array',
  ),
  'PharData::unlinkArchive' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'PharData::webPhar' => 
  array (
    0 => 'void',
    'alias=' => 'string|null',
    'index=' => 'string|null',
    'fileNotFoundScript=' => 'string|null',
    'mimeTypes=' => 'array',
    'rewrite=' => 'callable|null',
  ),
  'PharData::hasChildren' => 
  array (
    0 => 'string',
    'allowLinks=' => 'bool',
  ),
  'PharData::getChildren' => 
  array (
    0 => 'string',
  ),
  'PharData::getSubPath' => 
  array (
    0 => 'string',
  ),
  'PharData::getSubPathname' => 
  array (
    0 => 'string',
  ),
  'PharData::rewind' => 
  array (
    0 => 'string',
  ),
  'PharData::key' => 
  array (
    0 => 'string',
  ),
  'PharData::current' => 
  array (
    0 => 'string',
  ),
  'PharData::getFlags' => 
  array (
    0 => 'string',
  ),
  'PharData::setFlags' => 
  array (
    0 => 'string',
    'flags' => 'int',
  ),
  'PharData::getFilename' => 
  array (
    0 => 'string',
  ),
  'PharData::getExtension' => 
  array (
    0 => 'string',
  ),
  'PharData::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'PharData::isDot' => 
  array (
    0 => 'string',
  ),
  'PharData::valid' => 
  array (
    0 => 'string',
  ),
  'PharData::next' => 
  array (
    0 => 'string',
  ),
  'PharData::seek' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'PharData::__toString' => 
  array (
    0 => 'string',
  ),
  'PharData::getPathname' => 
  array (
    0 => 'string',
  ),
  'PharData::getPerms' => 
  array (
    0 => 'string',
  ),
  'PharData::getInode' => 
  array (
    0 => 'string',
  ),
  'PharData::getSize' => 
  array (
    0 => 'string',
  ),
  'PharData::getOwner' => 
  array (
    0 => 'string',
  ),
  'PharData::getGroup' => 
  array (
    0 => 'string',
  ),
  'PharData::getATime' => 
  array (
    0 => 'string',
  ),
  'PharData::getMTime' => 
  array (
    0 => 'string',
  ),
  'PharData::getCTime' => 
  array (
    0 => 'string',
  ),
  'PharData::getType' => 
  array (
    0 => 'string',
  ),
  'PharData::isReadable' => 
  array (
    0 => 'string',
  ),
  'PharData::isExecutable' => 
  array (
    0 => 'string',
  ),
  'PharData::isFile' => 
  array (
    0 => 'string',
  ),
  'PharData::isDir' => 
  array (
    0 => 'string',
  ),
  'PharData::isLink' => 
  array (
    0 => 'string',
  ),
  'PharData::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'PharData::getRealPath' => 
  array (
    0 => 'string',
  ),
  'PharData::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'PharData::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'PharData::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'PharData::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'PharData::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'PharData::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'PharData::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::__construct' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'PharFileInfo::__destruct' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::chmod' => 
  array (
    0 => 'string',
    'perms' => 'int',
  ),
  'PharFileInfo::compress' => 
  array (
    0 => 'string',
    'compression' => 'int',
  ),
  'PharFileInfo::decompress' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::delMetadata' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getCompressedSize' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getCRC32' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getContent' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getMetadata' => 
  array (
    0 => 'string',
    'unserializeOptions=' => 'array',
  ),
  'PharFileInfo::getPharFlags' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::hasMetadata' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isCompressed' => 
  array (
    0 => 'string',
    'compression=' => 'int|null',
  ),
  'PharFileInfo::isCRCChecked' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::setMetadata' => 
  array (
    0 => 'string',
    'metadata' => 'mixed|null',
  ),
  'PharFileInfo::getPath' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getFilename' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getExtension' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getBasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'PharFileInfo::getPathname' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getPerms' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getInode' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getSize' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getOwner' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getGroup' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getATime' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getMTime' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getCTime' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getType' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isWritable' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isReadable' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isExecutable' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isFile' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isDir' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::isLink' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getLinkTarget' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getRealPath' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::getFileInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'PharFileInfo::getPathInfo' => 
  array (
    0 => 'string',
    'class=' => 'string|null',
  ),
  'PharFileInfo::openFile' => 
  array (
    0 => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'string',
  ),
  'PharFileInfo::setFileClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'PharFileInfo::setInfoClass' => 
  array (
    0 => 'string',
    'class=' => 'string',
  ),
  'PharFileInfo::__toString' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::__debugInfo' => 
  array (
    0 => 'string',
  ),
  'PharFileInfo::_bad_state_ex' => 
  array (
    0 => 'string',
  ),
  'PhpToken::tokenize' => 
  array (
    0 => 'array',
    'code' => 'string',
    'flags=' => 'int',
  ),
  'PhpToken::__construct' => 
  array (
    0 => 'string',
    'id' => 'int',
    'text' => 'string',
    'line=' => 'int',
    'pos=' => 'int',
  ),
  'PhpToken::is' => 
  array (
    0 => 'bool',
    'kind' => 'string',
  ),
  'PhpToken::isIgnorable' => 
  array (
    0 => 'bool',
  ),
  'PhpToken::getTokenName' => 
  array (
    0 => 'string|null',
  ),
  'PhpToken::__toString' => 
  array (
    0 => 'string',
  ),
  'XMLReader::close' => 
  array (
    0 => 'string',
  ),
  'XMLReader::getAttribute' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'XMLReader::getAttributeNo' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'XMLReader::getAttributeNs' => 
  array (
    0 => 'string',
    'name' => 'string',
    'namespace' => 'string',
  ),
  'XMLReader::getParserProperty' => 
  array (
    0 => 'string',
    'property' => 'int',
  ),
  'XMLReader::isValid' => 
  array (
    0 => 'string',
  ),
  'XMLReader::lookupNamespace' => 
  array (
    0 => 'string',
    'prefix' => 'string',
  ),
  'XMLReader::moveToAttribute' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'XMLReader::moveToAttributeNo' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'XMLReader::moveToAttributeNs' => 
  array (
    0 => 'string',
    'name' => 'string',
    'namespace' => 'string',
  ),
  'XMLReader::moveToElement' => 
  array (
    0 => 'string',
  ),
  'XMLReader::moveToFirstAttribute' => 
  array (
    0 => 'string',
  ),
  'XMLReader::moveToNextAttribute' => 
  array (
    0 => 'string',
  ),
  'XMLReader::read' => 
  array (
    0 => 'string',
  ),
  'XMLReader::next' => 
  array (
    0 => 'string',
    'name=' => 'string|null',
  ),
  'XMLReader::open' => 
  array (
    0 => 'string',
    'uri' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'XMLReader::readInnerXml' => 
  array (
    0 => 'string',
  ),
  'XMLReader::readOuterXml' => 
  array (
    0 => 'string',
  ),
  'XMLReader::readString' => 
  array (
    0 => 'string',
  ),
  'XMLReader::setSchema' => 
  array (
    0 => 'string',
    'filename' => 'string|null',
  ),
  'XMLReader::setParserProperty' => 
  array (
    0 => 'string',
    'property' => 'int',
    'value' => 'bool',
  ),
  'XMLReader::setRelaxNGSchema' => 
  array (
    0 => 'string',
    'filename' => 'string|null',
  ),
  'XMLReader::setRelaxNGSchemaSource' => 
  array (
    0 => 'string',
    'source' => 'string|null',
  ),
  'XMLReader::XML' => 
  array (
    0 => 'string',
    'source' => 'string',
    'encoding=' => 'string|null',
    'flags=' => 'int',
  ),
  'XMLReader::expand' => 
  array (
    0 => 'string',
    'baseNode=' => 'DOMNode|null',
  ),
  'XMLWriter::openUri' => 
  array (
    0 => 'string',
    'uri' => 'string',
  ),
  'XMLWriter::openMemory' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::setIndent' => 
  array (
    0 => 'string',
    'enable' => 'bool',
  ),
  'XMLWriter::setIndentString' => 
  array (
    0 => 'string',
    'indentation' => 'string',
  ),
  'XMLWriter::startComment' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::endComment' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::startAttribute' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'XMLWriter::endAttribute' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeAttribute' => 
  array (
    0 => 'string',
    'name' => 'string',
    'value' => 'string',
  ),
  'XMLWriter::startAttributeNs' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'XMLWriter::writeAttributeNs' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'value' => 'string',
  ),
  'XMLWriter::startElement' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'XMLWriter::endElement' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::fullEndElement' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::startElementNs' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
  ),
  'XMLWriter::writeElement' => 
  array (
    0 => 'string',
    'name' => 'string',
    'content=' => 'string|null',
  ),
  'XMLWriter::writeElementNs' => 
  array (
    0 => 'string',
    'prefix' => 'string|null',
    'name' => 'string',
    'namespace' => 'string|null',
    'content=' => 'string|null',
  ),
  'XMLWriter::startPi' => 
  array (
    0 => 'string',
    'target' => 'string',
  ),
  'XMLWriter::endPi' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writePi' => 
  array (
    0 => 'string',
    'target' => 'string',
    'content' => 'string',
  ),
  'XMLWriter::startCdata' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::endCdata' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeCdata' => 
  array (
    0 => 'string',
    'content' => 'string',
  ),
  'XMLWriter::text' => 
  array (
    0 => 'string',
    'content' => 'string',
  ),
  'XMLWriter::writeRaw' => 
  array (
    0 => 'string',
    'content' => 'string',
  ),
  'XMLWriter::startDocument' => 
  array (
    0 => 'string',
    'version=' => 'string|null',
    'encoding=' => 'string|null',
    'standalone=' => 'string|null',
  ),
  'XMLWriter::endDocument' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeComment' => 
  array (
    0 => 'string',
    'content' => 'string',
  ),
  'XMLWriter::startDtd' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
  ),
  'XMLWriter::endDtd' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeDtd' => 
  array (
    0 => 'string',
    'name' => 'string',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'content=' => 'string|null',
  ),
  'XMLWriter::startDtdElement' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'XMLWriter::endDtdElement' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeDtdElement' => 
  array (
    0 => 'string',
    'name' => 'string',
    'content' => 'string',
  ),
  'XMLWriter::startDtdAttlist' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'XMLWriter::endDtdAttlist' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeDtdAttlist' => 
  array (
    0 => 'string',
    'name' => 'string',
    'content' => 'string',
  ),
  'XMLWriter::startDtdEntity' => 
  array (
    0 => 'string',
    'name' => 'string',
    'isParam' => 'bool',
  ),
  'XMLWriter::endDtdEntity' => 
  array (
    0 => 'string',
  ),
  'XMLWriter::writeDtdEntity' => 
  array (
    0 => 'string',
    'name' => 'string',
    'content' => 'string',
    'isParam=' => 'bool',
    'publicId=' => 'string|null',
    'systemId=' => 'string|null',
    'notationData=' => 'string|null',
  ),
  'XMLWriter::outputMemory' => 
  array (
    0 => 'string',
    'flush=' => 'bool',
  ),
  'XMLWriter::flush' => 
  array (
    0 => 'string',
    'empty=' => 'bool',
  ),
  'AMQPException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPConnectionException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPConnectionException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPConnectionException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPConnectionException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPConnectionException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPChannelException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPChannelException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPChannelException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPChannelException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPChannelException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPQueueException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPQueueException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPQueueException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPQueueException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPQueueException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPExchangeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPExchangeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPExchangeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPExchangeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPExchangeException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPValueException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPValueException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPValueException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPValueException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPValueException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPConnection::__construct' => 
  array (
    0 => 'string',
    'credentials=' => 'array',
  ),
  'AMQPConnection::isConnected' => 
  array (
    0 => 'bool',
  ),
  'AMQPConnection::connect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::pconnect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::pdisconnect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::disconnect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::reconnect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::preconnect' => 
  array (
    0 => 'void',
  ),
  'AMQPConnection::getLogin' => 
  array (
    0 => 'string',
  ),
  'AMQPConnection::setLogin' => 
  array (
    0 => 'void',
    'login' => 'string',
  ),
  'AMQPConnection::getPassword' => 
  array (
    0 => 'string',
  ),
  'AMQPConnection::setPassword' => 
  array (
    0 => 'void',
    'password' => 'string',
  ),
  'AMQPConnection::getHost' => 
  array (
    0 => 'string',
  ),
  'AMQPConnection::setHost' => 
  array (
    0 => 'void',
    'host' => 'string',
  ),
  'AMQPConnection::getPort' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::setPort' => 
  array (
    0 => 'void',
    'port' => 'int',
  ),
  'AMQPConnection::getVhost' => 
  array (
    0 => 'string',
  ),
  'AMQPConnection::setVhost' => 
  array (
    0 => 'void',
    'vhost' => 'string',
  ),
  'AMQPConnection::getTimeout' => 
  array (
    0 => 'float',
  ),
  'AMQPConnection::setTimeout' => 
  array (
    0 => 'void',
    'timeout' => 'float',
  ),
  'AMQPConnection::getReadTimeout' => 
  array (
    0 => 'float',
  ),
  'AMQPConnection::setReadTimeout' => 
  array (
    0 => 'void',
    'timeout' => 'float',
  ),
  'AMQPConnection::getWriteTimeout' => 
  array (
    0 => 'float',
  ),
  'AMQPConnection::setWriteTimeout' => 
  array (
    0 => 'void',
    'timeout' => 'float',
  ),
  'AMQPConnection::getConnectTimeout' => 
  array (
    0 => 'float',
  ),
  'AMQPConnection::getRpcTimeout' => 
  array (
    0 => 'float',
  ),
  'AMQPConnection::setRpcTimeout' => 
  array (
    0 => 'void',
    'timeout' => 'float',
  ),
  'AMQPConnection::getUsedChannels' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::getMaxChannels' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::isPersistent' => 
  array (
    0 => 'bool',
  ),
  'AMQPConnection::getHeartbeatInterval' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::getMaxFrameSize' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::getCACert' => 
  array (
    0 => 'string|null',
  ),
  'AMQPConnection::setCACert' => 
  array (
    0 => 'void',
    'cacert' => 'string|null',
  ),
  'AMQPConnection::getCert' => 
  array (
    0 => 'string|null',
  ),
  'AMQPConnection::setCert' => 
  array (
    0 => 'void',
    'cert' => 'string|null',
  ),
  'AMQPConnection::getKey' => 
  array (
    0 => 'string|null',
  ),
  'AMQPConnection::setKey' => 
  array (
    0 => 'void',
    'key' => 'string|null',
  ),
  'AMQPConnection::getVerify' => 
  array (
    0 => 'bool',
  ),
  'AMQPConnection::setVerify' => 
  array (
    0 => 'void',
    'verify' => 'bool',
  ),
  'AMQPConnection::getSaslMethod' => 
  array (
    0 => 'int',
  ),
  'AMQPConnection::setSaslMethod' => 
  array (
    0 => 'void',
    'saslMethod' => 'int',
  ),
  'AMQPConnection::getConnectionName' => 
  array (
    0 => 'string|null',
  ),
  'AMQPConnection::setConnectionName' => 
  array (
    0 => 'void',
    'connectionName' => 'string|null',
  ),
  'AMQPChannel::__construct' => 
  array (
    0 => 'string',
    'connection' => 'AMQPConnection',
  ),
  'AMQPChannel::isConnected' => 
  array (
    0 => 'bool',
  ),
  'AMQPChannel::close' => 
  array (
    0 => 'void',
  ),
  'AMQPChannel::getChannelId' => 
  array (
    0 => 'int',
  ),
  'AMQPChannel::setPrefetchSize' => 
  array (
    0 => 'void',
    'size' => 'int',
  ),
  'AMQPChannel::getPrefetchSize' => 
  array (
    0 => 'int',
  ),
  'AMQPChannel::setPrefetchCount' => 
  array (
    0 => 'void',
    'count' => 'int',
  ),
  'AMQPChannel::getPrefetchCount' => 
  array (
    0 => 'int',
  ),
  'AMQPChannel::setGlobalPrefetchSize' => 
  array (
    0 => 'void',
    'size' => 'int',
  ),
  'AMQPChannel::getGlobalPrefetchSize' => 
  array (
    0 => 'int',
  ),
  'AMQPChannel::setGlobalPrefetchCount' => 
  array (
    0 => 'void',
    'count' => 'int',
  ),
  'AMQPChannel::getGlobalPrefetchCount' => 
  array (
    0 => 'int',
  ),
  'AMQPChannel::qos' => 
  array (
    0 => 'void',
    'size' => 'int',
    'count' => 'int',
    'global=' => 'bool',
  ),
  'AMQPChannel::startTransaction' => 
  array (
    0 => 'void',
  ),
  'AMQPChannel::commitTransaction' => 
  array (
    0 => 'void',
  ),
  'AMQPChannel::rollbackTransaction' => 
  array (
    0 => 'void',
  ),
  'AMQPChannel::getConnection' => 
  array (
    0 => 'AMQPConnection',
  ),
  'AMQPChannel::basicRecover' => 
  array (
    0 => 'void',
    'requeue=' => 'bool',
  ),
  'AMQPChannel::confirmSelect' => 
  array (
    0 => 'void',
  ),
  'AMQPChannel::waitForConfirm' => 
  array (
    0 => 'void',
    'timeout=' => 'float',
  ),
  'AMQPChannel::setConfirmCallback' => 
  array (
    0 => 'void',
    'ackCallback' => 'callable|null',
    'nackCallback=' => 'callable|null',
  ),
  'AMQPChannel::setReturnCallback' => 
  array (
    0 => 'void',
    'returnCallback' => 'callable|null',
  ),
  'AMQPChannel::waitForBasicReturn' => 
  array (
    0 => 'void',
    'timeout=' => 'float',
  ),
  'AMQPChannel::getConsumers' => 
  array (
    0 => 'array',
  ),
  'AMQPQueue::__construct' => 
  array (
    0 => 'string',
    'channel' => 'AMQPChannel',
  ),
  'AMQPQueue::getName' => 
  array (
    0 => 'string|null',
  ),
  'AMQPQueue::setName' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'AMQPQueue::getFlags' => 
  array (
    0 => 'int',
  ),
  'AMQPQueue::setFlags' => 
  array (
    0 => 'void',
    'flags' => 'int|null',
  ),
  'AMQPQueue::getArgument' => 
  array (
    0 => 'string',
    'argumentName' => 'string',
  ),
  'AMQPQueue::getArguments' => 
  array (
    0 => 'array',
  ),
  'AMQPQueue::setArgument' => 
  array (
    0 => 'void',
    'argumentName' => 'string',
    'argumentValue' => 'string',
  ),
  'AMQPQueue::removeArgument' => 
  array (
    0 => 'void',
    'argumentName' => 'string',
  ),
  'AMQPQueue::setArguments' => 
  array (
    0 => 'void',
    'arguments' => 'array',
  ),
  'AMQPQueue::hasArgument' => 
  array (
    0 => 'bool',
    'argumentName' => 'string',
  ),
  'AMQPQueue::declareQueue' => 
  array (
    0 => 'int',
  ),
  'AMQPQueue::declare' => 
  array (
    0 => 'int',
  ),
  'AMQPQueue::bind' => 
  array (
    0 => 'void',
    'exchangeName' => 'string',
    'routingKey=' => 'string|null',
    'arguments=' => 'array',
  ),
  'AMQPQueue::get' => 
  array (
    0 => 'AMQPEnvelope|null',
    'flags=' => 'int|null',
  ),
  'AMQPQueue::consume' => 
  array (
    0 => 'void',
    'callback=' => 'callable|null',
    'flags=' => 'int|null',
    'consumerTag=' => 'string|null',
  ),
  'AMQPQueue::ack' => 
  array (
    0 => 'void',
    'deliveryTag' => 'int',
    'flags=' => 'int|null',
  ),
  'AMQPQueue::nack' => 
  array (
    0 => 'void',
    'deliveryTag' => 'int',
    'flags=' => 'int|null',
  ),
  'AMQPQueue::reject' => 
  array (
    0 => 'void',
    'deliveryTag' => 'int',
    'flags=' => 'int|null',
  ),
  'AMQPQueue::recover' => 
  array (
    0 => 'void',
    'requeue=' => 'bool',
  ),
  'AMQPQueue::purge' => 
  array (
    0 => 'int',
  ),
  'AMQPQueue::cancel' => 
  array (
    0 => 'void',
    'consumerTag=' => 'string',
  ),
  'AMQPQueue::delete' => 
  array (
    0 => 'int',
    'flags=' => 'int|null',
  ),
  'AMQPQueue::unbind' => 
  array (
    0 => 'void',
    'exchangeName' => 'string',
    'routingKey=' => 'string|null',
    'arguments=' => 'array',
  ),
  'AMQPQueue::getChannel' => 
  array (
    0 => 'AMQPChannel',
  ),
  'AMQPQueue::getConnection' => 
  array (
    0 => 'AMQPConnection',
  ),
  'AMQPQueue::getConsumerTag' => 
  array (
    0 => 'string|null',
  ),
  'AMQPExchange::__construct' => 
  array (
    0 => 'string',
    'channel' => 'AMQPChannel',
  ),
  'AMQPExchange::getName' => 
  array (
    0 => 'string|null',
  ),
  'AMQPExchange::setName' => 
  array (
    0 => 'void',
    'exchangeName' => 'string|null',
  ),
  'AMQPExchange::getFlags' => 
  array (
    0 => 'int',
  ),
  'AMQPExchange::setFlags' => 
  array (
    0 => 'void',
    'flags' => 'int|null',
  ),
  'AMQPExchange::getType' => 
  array (
    0 => 'string|null',
  ),
  'AMQPExchange::setType' => 
  array (
    0 => 'void',
    'exchangeType' => 'string|null',
  ),
  'AMQPExchange::getArgument' => 
  array (
    0 => 'string',
    'argumentName' => 'string',
  ),
  'AMQPExchange::getArguments' => 
  array (
    0 => 'array',
  ),
  'AMQPExchange::setArgument' => 
  array (
    0 => 'void',
    'argumentName' => 'string',
    'argumentValue' => 'string',
  ),
  'AMQPExchange::removeArgument' => 
  array (
    0 => 'void',
    'argumentName' => 'string',
  ),
  'AMQPExchange::setArguments' => 
  array (
    0 => 'void',
    'arguments' => 'array',
  ),
  'AMQPExchange::hasArgument' => 
  array (
    0 => 'bool',
    'argumentName' => 'string',
  ),
  'AMQPExchange::declareExchange' => 
  array (
    0 => 'void',
  ),
  'AMQPExchange::declare' => 
  array (
    0 => 'void',
  ),
  'AMQPExchange::bind' => 
  array (
    0 => 'void',
    'exchangeName' => 'string',
    'routingKey=' => 'string|null',
    'arguments=' => 'array',
  ),
  'AMQPExchange::unbind' => 
  array (
    0 => 'void',
    'exchangeName' => 'string',
    'routingKey=' => 'string|null',
    'arguments=' => 'array',
  ),
  'AMQPExchange::delete' => 
  array (
    0 => 'void',
    'exchangeName=' => 'string|null',
    'flags=' => 'int|null',
  ),
  'AMQPExchange::publish' => 
  array (
    0 => 'void',
    'message' => 'string',
    'routingKey=' => 'string|null',
    'flags=' => 'int|null',
    'headers=' => 'array',
  ),
  'AMQPExchange::getChannel' => 
  array (
    0 => 'AMQPChannel',
  ),
  'AMQPExchange::getConnection' => 
  array (
    0 => 'AMQPConnection',
  ),
  'AMQPBasicProperties::__construct' => 
  array (
    0 => 'string',
    'contentType=' => 'string|null',
    'contentEncoding=' => 'string|null',
    'headers=' => 'array',
    'deliveryMode=' => 'int',
    'priority=' => 'int',
    'correlationId=' => 'string|null',
    'replyTo=' => 'string|null',
    'expiration=' => 'string|null',
    'messageId=' => 'string|null',
    'timestamp=' => 'int|null',
    'type=' => 'string|null',
    'userId=' => 'string|null',
    'appId=' => 'string|null',
    'clusterId=' => 'string|null',
  ),
  'AMQPBasicProperties::getContentType' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getContentEncoding' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getHeaders' => 
  array (
    0 => 'array',
  ),
  'AMQPBasicProperties::getDeliveryMode' => 
  array (
    0 => 'int',
  ),
  'AMQPBasicProperties::getPriority' => 
  array (
    0 => 'int',
  ),
  'AMQPBasicProperties::getCorrelationId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getReplyTo' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getExpiration' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getMessageId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getTimestamp' => 
  array (
    0 => 'int|null',
  ),
  'AMQPBasicProperties::getType' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getUserId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getAppId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPBasicProperties::getClusterId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::__construct' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelope::getBody' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelope::getRoutingKey' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelope::getConsumerTag' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getDeliveryTag' => 
  array (
    0 => 'int|null',
  ),
  'AMQPEnvelope::getExchangeName' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::isRedelivery' => 
  array (
    0 => 'bool',
  ),
  'AMQPEnvelope::getHeader' => 
  array (
    0 => 'mixed|null',
    'headerName' => 'string',
  ),
  'AMQPEnvelope::hasHeader' => 
  array (
    0 => 'bool',
    'headerName' => 'string',
  ),
  'AMQPEnvelope::getContentType' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getContentEncoding' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getHeaders' => 
  array (
    0 => 'array',
  ),
  'AMQPEnvelope::getDeliveryMode' => 
  array (
    0 => 'int',
  ),
  'AMQPEnvelope::getPriority' => 
  array (
    0 => 'int',
  ),
  'AMQPEnvelope::getCorrelationId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getReplyTo' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getExpiration' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getMessageId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getTimestamp' => 
  array (
    0 => 'int|null',
  ),
  'AMQPEnvelope::getType' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getUserId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getAppId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelope::getClusterId' => 
  array (
    0 => 'string|null',
  ),
  'AMQPEnvelopeException::getEnvelope' => 
  array (
    0 => 'AMQPEnvelope',
  ),
  'AMQPEnvelopeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'AMQPEnvelopeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelopeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelopeException::getCode' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelopeException::getFile' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelopeException::getLine' => 
  array (
    0 => 'int',
  ),
  'AMQPEnvelopeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'AMQPEnvelopeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'AMQPEnvelopeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'AMQPEnvelopeException::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPTimestamp::__construct' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
  ),
  'AMQPTimestamp::getTimestamp' => 
  array (
    0 => 'float',
  ),
  'AMQPTimestamp::__toString' => 
  array (
    0 => 'string',
  ),
  'AMQPTimestamp::toAmqpValue' => 
  array (
    0 => 'string',
  ),
  'AMQPDecimal::__construct' => 
  array (
    0 => 'string',
    'exponent' => 'int',
    'significand' => 'int',
  ),
  'AMQPDecimal::getExponent' => 
  array (
    0 => 'int',
  ),
  'AMQPDecimal::getSignificand' => 
  array (
    0 => 'int',
  ),
  'AMQPDecimal::toAmqpValue' => 
  array (
    0 => 'string',
  ),
  'APCUIterator::__construct' => 
  array (
    0 => 'string',
    'search=' => 'string',
    'format=' => 'int',
    'chunk_size=' => 'int',
    'list=' => 'int',
  ),
  'APCUIterator::rewind' => 
  array (
    0 => 'void',
  ),
  'APCUIterator::next' => 
  array (
    0 => 'void',
  ),
  'APCUIterator::valid' => 
  array (
    0 => 'bool',
  ),
  'APCUIterator::key' => 
  array (
    0 => 'string|int',
  ),
  'APCUIterator::current' => 
  array (
    0 => 'mixed|null',
  ),
  'APCUIterator::getTotalHits' => 
  array (
    0 => 'int',
  ),
  'APCUIterator::getTotalSize' => 
  array (
    0 => 'int',
  ),
  'APCUIterator::getTotalCount' => 
  array (
    0 => 'int',
  ),
  'Ds\\Vector::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Vector::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Vector::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Vector::apply' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'Ds\\Vector::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Vector::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'string',
  ),
  'Ds\\Vector::filter' => 
  array (
    0 => 'Ds\\Sequence',
    'callback=' => 'callable|null',
  ),
  'Ds\\Vector::find' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'Ds\\Vector::first' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::get' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'Ds\\Vector::insert' => 
  array (
    0 => 'string',
    'index' => 'int',
    '...values=' => 'string',
  ),
  'Ds\\Vector::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'Ds\\Vector::last' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::map' => 
  array (
    0 => 'Ds\\Sequence',
    'callback' => 'callable',
  ),
  'Ds\\Vector::merge' => 
  array (
    0 => 'Ds\\Sequence',
    'values' => 'string',
  ),
  'Ds\\Vector::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Vector::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Vector::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Vector::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Vector::pop' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::push' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Vector::reduce' => 
  array (
    0 => 'string',
    'callback' => 'callable',
    'initial=' => 'string',
  ),
  'Ds\\Vector::remove' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'Ds\\Vector::reverse' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::reversed' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'Ds\\Vector::rotate' => 
  array (
    0 => 'string',
    'rotations' => 'int',
  ),
  'Ds\\Vector::set' => 
  array (
    0 => 'string',
    'index' => 'int',
    'value' => 'string',
  ),
  'Ds\\Vector::shift' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::slice' => 
  array (
    0 => 'Ds\\Sequence',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'Ds\\Vector::sort' => 
  array (
    0 => 'string',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Vector::sorted' => 
  array (
    0 => 'Ds\\Sequence',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Vector::sum' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::unshift' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Vector::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Vector::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Vector::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Vector::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Vector::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Deque::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Deque::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Deque::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Deque::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Deque::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Deque::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Deque::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Deque::apply' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'Ds\\Deque::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Deque::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'string',
  ),
  'Ds\\Deque::filter' => 
  array (
    0 => 'Ds\\Sequence',
    'callback=' => 'callable|null',
  ),
  'Ds\\Deque::find' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'Ds\\Deque::first' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::get' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'Ds\\Deque::insert' => 
  array (
    0 => 'string',
    'index' => 'int',
    '...values=' => 'string',
  ),
  'Ds\\Deque::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'Ds\\Deque::last' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::map' => 
  array (
    0 => 'Ds\\Sequence',
    'callback' => 'callable',
  ),
  'Ds\\Deque::merge' => 
  array (
    0 => 'Ds\\Sequence',
    'values' => 'string',
  ),
  'Ds\\Deque::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Deque::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Deque::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Deque::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Deque::pop' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::push' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Deque::reduce' => 
  array (
    0 => 'string',
    'callback' => 'callable',
    'initial=' => 'string',
  ),
  'Ds\\Deque::remove' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'Ds\\Deque::reverse' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::reversed' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'Ds\\Deque::rotate' => 
  array (
    0 => 'string',
    'rotations' => 'int',
  ),
  'Ds\\Deque::set' => 
  array (
    0 => 'string',
    'index' => 'int',
    'value' => 'string',
  ),
  'Ds\\Deque::shift' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::slice' => 
  array (
    0 => 'Ds\\Sequence',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'Ds\\Deque::sort' => 
  array (
    0 => 'string',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Deque::sorted' => 
  array (
    0 => 'Ds\\Sequence',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Deque::sum' => 
  array (
    0 => 'string',
  ),
  'Ds\\Deque::unshift' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Stack::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Stack::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Stack::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Stack::peek' => 
  array (
    0 => 'string',
  ),
  'Ds\\Stack::pop' => 
  array (
    0 => 'string',
  ),
  'Ds\\Stack::push' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Stack::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Stack::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Stack::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Stack::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Stack::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Stack::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Stack::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Stack::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Stack::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Stack::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Stack::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Queue::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Queue::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Queue::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Queue::peek' => 
  array (
    0 => 'string',
  ),
  'Ds\\Queue::pop' => 
  array (
    0 => 'string',
  ),
  'Ds\\Queue::push' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Queue::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Queue::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Queue::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Queue::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Queue::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Queue::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Queue::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Queue::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Queue::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Queue::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Queue::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Map::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Map::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Map::apply' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'Ds\\Map::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Map::diff' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'Ds\\Map::filter' => 
  array (
    0 => 'Ds\\Map',
    'callback=' => 'callable|null',
  ),
  'Ds\\Map::first' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'Ds\\Map::get' => 
  array (
    0 => 'string',
    'key' => 'string',
    'default=' => 'string',
  ),
  'Ds\\Map::hasKey' => 
  array (
    0 => 'bool',
    'key' => 'string',
  ),
  'Ds\\Map::hasValue' => 
  array (
    0 => 'bool',
    'value' => 'string',
  ),
  'Ds\\Map::intersect' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'Ds\\Map::keys' => 
  array (
    0 => 'Ds\\Set',
  ),
  'Ds\\Map::ksort' => 
  array (
    0 => 'string',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Map::ksorted' => 
  array (
    0 => 'Ds\\Map',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Map::last' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'Ds\\Map::map' => 
  array (
    0 => 'Ds\\Map',
    'callback' => 'callable',
  ),
  'Ds\\Map::merge' => 
  array (
    0 => 'Ds\\Map',
    'values' => 'string',
  ),
  'Ds\\Map::pairs' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'Ds\\Map::put' => 
  array (
    0 => 'string',
    'key' => 'string',
    'value' => 'string',
  ),
  'Ds\\Map::putAll' => 
  array (
    0 => 'string',
    'values' => 'string',
  ),
  'Ds\\Map::reduce' => 
  array (
    0 => 'string',
    'callback' => 'callable',
    'initial=' => 'string',
  ),
  'Ds\\Map::remove' => 
  array (
    0 => 'string',
    'key' => 'string',
    'default=' => 'string',
  ),
  'Ds\\Map::reverse' => 
  array (
    0 => 'string',
  ),
  'Ds\\Map::reversed' => 
  array (
    0 => 'Ds\\Map',
  ),
  'Ds\\Map::skip' => 
  array (
    0 => 'Ds\\Pair',
    'position' => 'int',
  ),
  'Ds\\Map::slice' => 
  array (
    0 => 'Ds\\Map',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'Ds\\Map::sort' => 
  array (
    0 => 'string',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Map::sorted' => 
  array (
    0 => 'Ds\\Map',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Map::sum' => 
  array (
    0 => 'string',
  ),
  'Ds\\Map::union' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'string',
  ),
  'Ds\\Map::values' => 
  array (
    0 => 'Ds\\Sequence',
  ),
  'Ds\\Map::xor' => 
  array (
    0 => 'Ds\\Map',
    'map' => 'Ds\\Map',
  ),
  'Ds\\Map::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Map::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Map::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Map::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Map::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Map::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Map::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Map::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Map::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Map::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Map::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Set::__construct' => 
  array (
    0 => 'string',
    'values=' => 'string',
  ),
  'Ds\\Set::add' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Set::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\Set::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\Set::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'string',
  ),
  'Ds\\Set::diff' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'Ds\\Set::filter' => 
  array (
    0 => 'Ds\\Set',
    'predicate=' => 'callable|null',
  ),
  'Ds\\Set::first' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::get' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'Ds\\Set::intersect' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'Ds\\Set::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'Ds\\Set::last' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::map' => 
  array (
    0 => 'Ds\\Set',
    'callback' => 'callable',
  ),
  'Ds\\Set::merge' => 
  array (
    0 => 'Ds\\Set',
    'values' => 'string',
  ),
  'Ds\\Set::reduce' => 
  array (
    0 => 'string',
    'callback' => 'callable',
    'initial=' => 'string',
  ),
  'Ds\\Set::remove' => 
  array (
    0 => 'string',
    '...values=' => 'string',
  ),
  'Ds\\Set::reverse' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::reversed' => 
  array (
    0 => 'Ds\\Set',
  ),
  'Ds\\Set::slice' => 
  array (
    0 => 'Ds\\Set',
    'index' => 'int',
    'length=' => 'int|null',
  ),
  'Ds\\Set::sort' => 
  array (
    0 => 'string',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Set::sorted' => 
  array (
    0 => 'Ds\\Set',
    'comparator=' => 'callable|null',
  ),
  'Ds\\Set::sum' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::union' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'Ds\\Set::xor' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'Ds\\Set::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\Set::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'string',
  ),
  'Ds\\Set::offsetGet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Set::offsetSet' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Ds\\Set::offsetUnset' => 
  array (
    0 => 'string',
    'offset' => 'mixed|null',
  ),
  'Ds\\Set::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\Set::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\Set::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\Set::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Set::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\PriorityQueue::__construct' => 
  array (
    0 => 'string',
  ),
  'Ds\\PriorityQueue::allocate' => 
  array (
    0 => 'string',
    'capacity' => 'int',
  ),
  'Ds\\PriorityQueue::capacity' => 
  array (
    0 => 'int',
  ),
  'Ds\\PriorityQueue::peek' => 
  array (
    0 => 'string',
  ),
  'Ds\\PriorityQueue::pop' => 
  array (
    0 => 'string',
  ),
  'Ds\\PriorityQueue::push' => 
  array (
    0 => 'string',
    'value' => 'string',
    'priority' => 'string',
  ),
  'Ds\\PriorityQueue::getIterator' => 
  array (
    0 => 'Traversable',
  ),
  'Ds\\PriorityQueue::clear' => 
  array (
    0 => 'string',
  ),
  'Ds\\PriorityQueue::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'Ds\\PriorityQueue::count' => 
  array (
    0 => 'int',
  ),
  'Ds\\PriorityQueue::isEmpty' => 
  array (
    0 => 'bool',
  ),
  'Ds\\PriorityQueue::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\PriorityQueue::toArray' => 
  array (
    0 => 'array',
  ),
  'Ds\\Pair::__construct' => 
  array (
    0 => 'string',
    'key=' => 'string',
    'value=' => 'string',
  ),
  'Ds\\Pair::copy' => 
  array (
    0 => 'Ds\\Pair',
  ),
  'Ds\\Pair::jsonSerialize' => 
  array (
    0 => 'string',
  ),
  'Ds\\Pair::toArray' => 
  array (
    0 => 'array',
  ),
  'Ev::supportedBackends' => 
  array (
    0 => 'int',
  ),
  'Ev::recommendedBackends' => 
  array (
    0 => 'int',
  ),
  'Ev::embeddableBackends' => 
  array (
    0 => 'int',
  ),
  'Ev::sleep' => 
  array (
    0 => 'void',
    'seconds' => 'float',
  ),
  'Ev::time' => 
  array (
    0 => 'float',
  ),
  'Ev::feedSignal' => 
  array (
    0 => 'void',
    'signum' => 'int',
  ),
  'Ev::feedSignalEvent' => 
  array (
    0 => 'void',
    'signum' => 'int',
  ),
  'Ev::run' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'Ev::now' => 
  array (
    0 => 'float',
  ),
  'Ev::stop' => 
  array (
    0 => 'void',
    'how=' => 'int',
  ),
  'Ev::iteration' => 
  array (
    0 => 'int',
  ),
  'Ev::depth' => 
  array (
    0 => 'int',
  ),
  'Ev::backend' => 
  array (
    0 => 'int',
  ),
  'Ev::nowUpdate' => 
  array (
    0 => 'void',
  ),
  'Ev::suspend' => 
  array (
    0 => 'void',
  ),
  'Ev::resume' => 
  array (
    0 => 'void',
  ),
  'Ev::verify' => 
  array (
    0 => 'void',
  ),
  'EvLoop::__construct' => 
  array (
    0 => 'string',
    'flags=' => 'int',
    'data=' => 'mixed|null',
    'io_interval=' => 'float',
    'timeout_interval=' => 'float',
  ),
  'EvLoop::defaultLoop' => 
  array (
    0 => 'EvLoop',
    'flags=' => 'int',
    'data=' => 'mixed|null',
    'io_interval=' => 'float',
    'timeout_interval=' => 'float',
  ),
  'EvLoop::loopFork' => 
  array (
    0 => 'void',
  ),
  'EvLoop::verify' => 
  array (
    0 => 'void',
  ),
  'EvLoop::invokePending' => 
  array (
    0 => 'void',
  ),
  'EvLoop::nowUpdate' => 
  array (
    0 => 'void',
  ),
  'EvLoop::suspend' => 
  array (
    0 => 'void',
  ),
  'EvLoop::resume' => 
  array (
    0 => 'void',
  ),
  'EvLoop::backend' => 
  array (
    0 => 'int',
  ),
  'EvLoop::now' => 
  array (
    0 => 'float',
  ),
  'EvLoop::run' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'EvLoop::stop' => 
  array (
    0 => 'void',
    'how=' => 'int',
  ),
  'EvLoop::io' => 
  array (
    0 => 'EvIo',
    'fd' => 'mixed|null',
    'events' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::timer' => 
  array (
    0 => 'EvTimer',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::periodic' => 
  array (
    0 => 'EvPeriodic',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed|null',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::signal' => 
  array (
    0 => 'EvSignal',
    'signum' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::child' => 
  array (
    0 => 'EvChild',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::stat' => 
  array (
    0 => 'EvStat',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::idle' => 
  array (
    0 => 'EvIdle',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvLoop::check' => 
  array (
    0 => 'EvCheck',
  ),
  'EvLoop::prepare' => 
  array (
    0 => 'EvPrepare',
  ),
  'EvLoop::embed' => 
  array (
    0 => 'EvEmbed',
  ),
  'EvLoop::fork' => 
  array (
    0 => 'EvFork',
  ),
  'EvWatcher::start' => 
  array (
    0 => 'void',
  ),
  'EvWatcher::stop' => 
  array (
    0 => 'void',
  ),
  'EvWatcher::clear' => 
  array (
    0 => 'int',
  ),
  'EvWatcher::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvWatcher::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvWatcher::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvWatcher::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvWatcher::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvIo::__construct' => 
  array (
    0 => 'string',
    'fd' => 'mixed|null',
    'events' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvIo::createStopped' => 
  array (
    0 => 'EvIo',
    'fd' => 'mixed|null',
    'events' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvIo::set' => 
  array (
    0 => 'void',
    'fd' => 'mixed|null',
    'events' => 'int',
  ),
  'EvIo::start' => 
  array (
    0 => 'void',
  ),
  'EvIo::stop' => 
  array (
    0 => 'void',
  ),
  'EvIo::clear' => 
  array (
    0 => 'int',
  ),
  'EvIo::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvIo::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvIo::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvIo::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvIo::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvTimer::__construct' => 
  array (
    0 => 'string',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvTimer::createStopped' => 
  array (
    0 => 'EvTimer',
    'after' => 'float',
    'repeat' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvTimer::set' => 
  array (
    0 => 'void',
    'after' => 'float',
    'repeat' => 'float',
  ),
  'EvTimer::again' => 
  array (
    0 => 'void',
  ),
  'EvTimer::start' => 
  array (
    0 => 'void',
  ),
  'EvTimer::stop' => 
  array (
    0 => 'void',
  ),
  'EvTimer::clear' => 
  array (
    0 => 'int',
  ),
  'EvTimer::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvTimer::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvTimer::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvTimer::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvTimer::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvPeriodic::__construct' => 
  array (
    0 => 'string',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed|null',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvPeriodic::createStopped' => 
  array (
    0 => 'EvPeriodic',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb' => 'mixed|null',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvPeriodic::set' => 
  array (
    0 => 'void',
    'offset' => 'float',
    'interval' => 'float',
    'reschedule_cb=' => 'mixed|null',
  ),
  'EvPeriodic::again' => 
  array (
    0 => 'void',
  ),
  'EvPeriodic::at' => 
  array (
    0 => 'float',
  ),
  'EvPeriodic::start' => 
  array (
    0 => 'void',
  ),
  'EvPeriodic::stop' => 
  array (
    0 => 'void',
  ),
  'EvPeriodic::clear' => 
  array (
    0 => 'int',
  ),
  'EvPeriodic::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvPeriodic::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvPeriodic::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvPeriodic::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvPeriodic::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvSignal::__construct' => 
  array (
    0 => 'string',
    'signum' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvSignal::createStopped' => 
  array (
    0 => 'EvSignal',
    'signum' => 'int',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvSignal::set' => 
  array (
    0 => 'void',
    'signum' => 'int',
  ),
  'EvSignal::start' => 
  array (
    0 => 'void',
  ),
  'EvSignal::stop' => 
  array (
    0 => 'void',
  ),
  'EvSignal::clear' => 
  array (
    0 => 'int',
  ),
  'EvSignal::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvSignal::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvSignal::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvSignal::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvSignal::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvChild::__construct' => 
  array (
    0 => 'string',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvChild::createStopped' => 
  array (
    0 => 'EvChild',
    'pid' => 'int',
    'trace' => 'bool',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvChild::set' => 
  array (
    0 => 'void',
    'pid' => 'int',
    'trace' => 'bool',
  ),
  'EvChild::start' => 
  array (
    0 => 'void',
  ),
  'EvChild::stop' => 
  array (
    0 => 'void',
  ),
  'EvChild::clear' => 
  array (
    0 => 'int',
  ),
  'EvChild::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvChild::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvChild::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvChild::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvChild::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvStat::__construct' => 
  array (
    0 => 'string',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvStat::createStopped' => 
  array (
    0 => 'EvStat',
    'path' => 'string',
    'interval' => 'float',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvStat::set' => 
  array (
    0 => 'void',
    'path' => 'string',
    'interval' => 'float',
  ),
  'EvStat::attr' => 
  array (
    0 => 'mixed|null',
  ),
  'EvStat::prev' => 
  array (
    0 => 'mixed|null',
  ),
  'EvStat::stat' => 
  array (
    0 => 'bool',
  ),
  'EvStat::start' => 
  array (
    0 => 'void',
  ),
  'EvStat::stop' => 
  array (
    0 => 'void',
  ),
  'EvStat::clear' => 
  array (
    0 => 'int',
  ),
  'EvStat::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvStat::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvStat::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvStat::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvStat::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvIdle::__construct' => 
  array (
    0 => 'string',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvIdle::createStopped' => 
  array (
    0 => 'EvIdle',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvIdle::start' => 
  array (
    0 => 'void',
  ),
  'EvIdle::stop' => 
  array (
    0 => 'void',
  ),
  'EvIdle::clear' => 
  array (
    0 => 'int',
  ),
  'EvIdle::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvIdle::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvIdle::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvIdle::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvIdle::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvCheck::__construct' => 
  array (
    0 => 'string',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvCheck::createStopped' => 
  array (
    0 => 'EvCheck',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvCheck::start' => 
  array (
    0 => 'void',
  ),
  'EvCheck::stop' => 
  array (
    0 => 'void',
  ),
  'EvCheck::clear' => 
  array (
    0 => 'int',
  ),
  'EvCheck::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvCheck::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvCheck::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvCheck::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvCheck::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvPrepare::__construct' => 
  array (
    0 => 'string',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvPrepare::createStopped' => 
  array (
    0 => 'EvPrepare',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvPrepare::start' => 
  array (
    0 => 'void',
  ),
  'EvPrepare::stop' => 
  array (
    0 => 'void',
  ),
  'EvPrepare::clear' => 
  array (
    0 => 'int',
  ),
  'EvPrepare::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvPrepare::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvPrepare::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvPrepare::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvPrepare::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvEmbed::__construct' => 
  array (
    0 => 'string',
    'other' => 'EvLoop',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvEmbed::createStopped' => 
  array (
    0 => 'EvEmbed',
    'other' => 'EvLoop',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvEmbed::set' => 
  array (
    0 => 'void',
    'other' => 'EvLoop',
  ),
  'EvEmbed::sweep' => 
  array (
    0 => 'void',
  ),
  'EvEmbed::start' => 
  array (
    0 => 'void',
  ),
  'EvEmbed::stop' => 
  array (
    0 => 'void',
  ),
  'EvEmbed::clear' => 
  array (
    0 => 'int',
  ),
  'EvEmbed::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvEmbed::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvEmbed::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvEmbed::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvEmbed::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'EvFork::__construct' => 
  array (
    0 => 'string',
    'loop' => 'EvLoop',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvFork::createStopped' => 
  array (
    0 => 'EvFork',
    'loop' => 'EvLoop',
    'callback' => 'mixed|null',
    'data=' => 'mixed|null',
    'priority=' => 'int',
  ),
  'EvFork::start' => 
  array (
    0 => 'void',
  ),
  'EvFork::stop' => 
  array (
    0 => 'void',
  ),
  'EvFork::clear' => 
  array (
    0 => 'int',
  ),
  'EvFork::invoke' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvFork::feed' => 
  array (
    0 => 'void',
    'revents' => 'int',
  ),
  'EvFork::getLoop' => 
  array (
    0 => 'EvLoop|null',
  ),
  'EvFork::keepalive' => 
  array (
    0 => 'bool',
    'value=' => 'bool',
  ),
  'EvFork::setCallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed|null',
  ),
  'FFI\\Exception::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'FFI\\Exception::__wakeup' => 
  array (
    0 => 'string',
  ),
  'FFI\\Exception::getMessage' => 
  array (
    0 => 'string',
  ),
  'FFI\\Exception::getCode' => 
  array (
    0 => 'string',
  ),
  'FFI\\Exception::getFile' => 
  array (
    0 => 'string',
  ),
  'FFI\\Exception::getLine' => 
  array (
    0 => 'int',
  ),
  'FFI\\Exception::getTrace' => 
  array (
    0 => 'array',
  ),
  'FFI\\Exception::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'FFI\\Exception::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'FFI\\Exception::__toString' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'FFI\\ParserException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::getMessage' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::getCode' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::getFile' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::getLine' => 
  array (
    0 => 'int',
  ),
  'FFI\\ParserException::getTrace' => 
  array (
    0 => 'array',
  ),
  'FFI\\ParserException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'FFI\\ParserException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'FFI\\ParserException::__toString' => 
  array (
    0 => 'string',
  ),
  'FFI::cdef' => 
  array (
    0 => 'FFI',
    'code=' => 'string',
    'lib=' => 'string|null',
  ),
  'FFI::load' => 
  array (
    0 => 'FFI|null',
    'filename' => 'string',
  ),
  'FFI::scope' => 
  array (
    0 => 'FFI',
    'name' => 'string',
  ),
  'FFI::new' => 
  array (
    0 => 'FFI\\CData|null',
    'type' => 'FFI\\CType|string',
    'owned=' => 'bool',
    'persistent=' => 'bool',
  ),
  'FFI::free' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
  ),
  'FFI::cast' => 
  array (
    0 => 'FFI\\CData|null',
    'type' => 'FFI\\CType|string',
    '&ptr' => 'string',
  ),
  'FFI::type' => 
  array (
    0 => 'FFI\\CType|null',
    'type' => 'string',
  ),
  'FFI::typeof' => 
  array (
    0 => 'FFI\\CType',
    '&ptr' => 'FFI\\CData',
  ),
  'FFI::arrayType' => 
  array (
    0 => 'FFI\\CType',
    'type' => 'FFI\\CType',
    'dimensions' => 'array',
  ),
  'FFI::addr' => 
  array (
    0 => 'FFI\\CData',
    '&ptr' => 'FFI\\CData',
  ),
  'FFI::sizeof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'FFI::alignof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'FFI::memcpy' => 
  array (
    0 => 'void',
    '&to' => 'FFI\\CData',
    '&from' => 'string',
    'size' => 'int',
  ),
  'FFI::memcmp' => 
  array (
    0 => 'int',
    '&ptr1' => 'string',
    '&ptr2' => 'string',
    'size' => 'int',
  ),
  'FFI::memset' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
    'value' => 'int',
    'size' => 'int',
  ),
  'FFI::string' => 
  array (
    0 => 'string',
    '&ptr' => 'FFI\\CData',
    'size=' => 'int|null',
  ),
  'FFI::isNull' => 
  array (
    0 => 'bool',
    '&ptr' => 'FFI\\CData',
  ),
  'FFI\\CType::getName' => 
  array (
    0 => 'string',
  ),
  'ImagickException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ImagickException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ImagickException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ImagickException::getCode' => 
  array (
    0 => 'string',
  ),
  'ImagickException::getFile' => 
  array (
    0 => 'string',
  ),
  'ImagickException::getLine' => 
  array (
    0 => 'int',
  ),
  'ImagickException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ImagickException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ImagickException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickException::__toString' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ImagickDrawException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::getCode' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::getFile' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::getLine' => 
  array (
    0 => 'int',
  ),
  'ImagickDrawException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ImagickDrawException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ImagickDrawException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickDrawException::__toString' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ImagickPixelIteratorException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::getCode' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::getFile' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::getLine' => 
  array (
    0 => 'int',
  ),
  'ImagickPixelIteratorException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelIteratorException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ImagickPixelIteratorException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIteratorException::__toString' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ImagickPixelException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::getCode' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::getFile' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::getLine' => 
  array (
    0 => 'int',
  ),
  'ImagickPixelException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ImagickPixelException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelException::__toString' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ImagickKernelException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::getCode' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::getFile' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::getLine' => 
  array (
    0 => 'int',
  ),
  'ImagickKernelException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ImagickKernelException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ImagickKernelException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickKernelException::__toString' => 
  array (
    0 => 'string',
  ),
  'Imagick::optimizeImageLayers' => 
  array (
    0 => 'bool',
  ),
  'Imagick::compareImageLayers' => 
  array (
    0 => 'Imagick',
    'metric' => 'int',
  ),
  'Imagick::pingImageBlob' => 
  array (
    0 => 'bool',
    'image' => 'string',
  ),
  'Imagick::pingImageFile' => 
  array (
    0 => 'bool',
    'filehandle' => 'mixed|null',
    'filename=' => 'string|null',
  ),
  'Imagick::transposeImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::transverseImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::trimImage' => 
  array (
    0 => 'bool',
    'fuzz' => 'float',
  ),
  'Imagick::waveImage' => 
  array (
    0 => 'bool',
    'amplitude' => 'float',
    'length' => 'float',
  ),
  'Imagick::waveImageWithMethod' => 
  array (
    0 => 'bool',
    'amplitude' => 'float',
    'length' => 'float',
    'interpolate_method' => 'int',
  ),
  'Imagick::vignetteImage' => 
  array (
    0 => 'bool',
    'black_point' => 'float',
    'white_point' => 'float',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::uniqueImageColors' => 
  array (
    0 => 'bool',
  ),
  'Imagick::setImageMatte' => 
  array (
    0 => 'bool',
    'matte' => 'bool',
  ),
  'Imagick::adaptiveResizeImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'bestfit=' => 'bool',
    'legacy=' => 'bool',
  ),
  'Imagick::sketchImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'angle' => 'float',
  ),
  'Imagick::shadeImage' => 
  array (
    0 => 'bool',
    'gray' => 'bool',
    'azimuth' => 'float',
    'elevation' => 'float',
  ),
  'Imagick::getSizeOffset' => 
  array (
    0 => 'int',
  ),
  'Imagick::setSizeOffset' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'offset' => 'int',
  ),
  'Imagick::adaptiveBlurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::contrastStretchImage' => 
  array (
    0 => 'bool',
    'black_point' => 'float',
    'white_point' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::adaptiveSharpenImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::randomThresholdImage' => 
  array (
    0 => 'bool',
    'low' => 'float',
    'high' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::roundCornersImage' => 
  array (
    0 => 'bool',
    'x_rounding' => 'float',
    'y_rounding' => 'float',
    'stroke_width=' => 'float',
    'displace=' => 'float',
    'size_correction=' => 'float',
  ),
  'Imagick::roundCorners' => 
  array (
    0 => 'bool',
    'x_rounding' => 'float',
    'y_rounding' => 'float',
    'stroke_width=' => 'float',
    'displace=' => 'float',
    'size_correction=' => 'float',
  ),
  'Imagick::setIteratorIndex' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'Imagick::getIteratorIndex' => 
  array (
    0 => 'int',
  ),
  'Imagick::setImageAlpha' => 
  array (
    0 => 'bool',
    'alpha' => 'float',
  ),
  'Imagick::polaroidWithTextAndMethod' => 
  array (
    0 => 'bool',
    'settings' => 'ImagickDraw',
    'angle' => 'float',
    'caption' => 'string',
    'method' => 'int',
  ),
  'Imagick::polaroidImage' => 
  array (
    0 => 'bool',
    'settings' => 'ImagickDraw',
    'angle' => 'float',
  ),
  'Imagick::getImageProperty' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'Imagick::setImageProperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value' => 'string',
  ),
  'Imagick::deleteImageProperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'Imagick::identifyFormat' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'Imagick::setImageInterpolateMethod' => 
  array (
    0 => 'bool',
    'method' => 'int',
  ),
  'Imagick::getImageInterpolateMethod' => 
  array (
    0 => 'int',
  ),
  'Imagick::linearStretchImage' => 
  array (
    0 => 'bool',
    'black_point' => 'float',
    'white_point' => 'float',
  ),
  'Imagick::getImageLength' => 
  array (
    0 => 'int',
  ),
  'Imagick::extentImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::getImageOrientation' => 
  array (
    0 => 'int',
  ),
  'Imagick::setImageOrientation' => 
  array (
    0 => 'bool',
    'orientation' => 'int',
  ),
  'Imagick::clutImage' => 
  array (
    0 => 'bool',
    'lookup_table' => 'Imagick',
    'channel=' => 'int',
  ),
  'Imagick::getImageProperties' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
    'include_values=' => 'bool',
  ),
  'Imagick::getImageProfiles' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
    'include_values=' => 'bool',
  ),
  'Imagick::distortImage' => 
  array (
    0 => 'bool',
    'distortion' => 'int',
    'arguments' => 'array',
    'bestfit' => 'bool',
  ),
  'Imagick::writeImageFile' => 
  array (
    0 => 'bool',
    'filehandle' => 'mixed|null',
    'format=' => 'string|null',
  ),
  'Imagick::writeImagesFile' => 
  array (
    0 => 'bool',
    'filehandle' => 'mixed|null',
    'format=' => 'string|null',
  ),
  'Imagick::resetImagePage' => 
  array (
    0 => 'bool',
    'page' => 'string',
  ),
  'Imagick::animateImages' => 
  array (
    0 => 'bool',
    'x_server' => 'string',
  ),
  'Imagick::setFont' => 
  array (
    0 => 'bool',
    'font' => 'string',
  ),
  'Imagick::getFont' => 
  array (
    0 => 'string',
  ),
  'Imagick::setPointSize' => 
  array (
    0 => 'bool',
    'point_size' => 'float',
  ),
  'Imagick::getPointSize' => 
  array (
    0 => 'float',
  ),
  'Imagick::mergeImageLayers' => 
  array (
    0 => 'Imagick',
    'layermethod' => 'int',
  ),
  'Imagick::setImageAlphaChannel' => 
  array (
    0 => 'bool',
    'alphachannel' => 'int',
  ),
  'Imagick::floodfillPaintImage' => 
  array (
    0 => 'bool',
    'fill_color' => 'ImagickPixel|string',
    'fuzz' => 'float',
    'border_color' => 'ImagickPixel|string',
    'x' => 'int',
    'y' => 'int',
    'invert' => 'bool',
    'channel=' => 'int|null',
  ),
  'Imagick::opaquePaintImage' => 
  array (
    0 => 'bool',
    'target_color' => 'ImagickPixel|string',
    'fill_color' => 'ImagickPixel|string',
    'fuzz' => 'float',
    'invert' => 'bool',
    'channel=' => 'int',
  ),
  'Imagick::transparentPaintImage' => 
  array (
    0 => 'bool',
    'target_color' => 'ImagickPixel|string',
    'alpha' => 'float',
    'fuzz' => 'float',
    'invert' => 'bool',
  ),
  'Imagick::liquidRescaleImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'delta_x' => 'float',
    'rigidity' => 'float',
  ),
  'Imagick::encipherImage' => 
  array (
    0 => 'bool',
    'passphrase' => 'string',
  ),
  'Imagick::decipherImage' => 
  array (
    0 => 'bool',
    'passphrase' => 'string',
  ),
  'Imagick::setGravity' => 
  array (
    0 => 'bool',
    'gravity' => 'int',
  ),
  'Imagick::getGravity' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageChannelRange' => 
  array (
    0 => 'array',
    'channel' => 'int',
  ),
  'Imagick::getImageAlphaChannel' => 
  array (
    0 => 'bool',
  ),
  'Imagick::getImageChannelDistortions' => 
  array (
    0 => 'float',
    'reference_image' => 'Imagick',
    'metric' => 'int',
    'channel=' => 'int',
  ),
  'Imagick::setImageGravity' => 
  array (
    0 => 'bool',
    'gravity' => 'int',
  ),
  'Imagick::getImageGravity' => 
  array (
    0 => 'int',
  ),
  'Imagick::importImagePixels' => 
  array (
    0 => 'bool',
    'x' => 'int',
    'y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'map' => 'string',
    'pixelstorage' => 'int',
    'pixels' => 'array',
  ),
  'Imagick::deskewImage' => 
  array (
    0 => 'bool',
    'threshold' => 'float',
  ),
  'Imagick::segmentImage' => 
  array (
    0 => 'bool',
    'colorspace' => 'int',
    'cluster_threshold' => 'float',
    'smooth_threshold' => 'float',
    'verbose=' => 'bool',
  ),
  'Imagick::sparseColorImage' => 
  array (
    0 => 'bool',
    'sparsecolormethod' => 'int',
    'arguments' => 'array',
    'channel=' => 'int',
  ),
  'Imagick::remapImage' => 
  array (
    0 => 'bool',
    'replacement' => 'Imagick',
    'dither_method' => 'int',
  ),
  'Imagick::houghLineImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'threshold' => 'float',
  ),
  'Imagick::exportImagePixels' => 
  array (
    0 => 'array',
    'x' => 'int',
    'y' => 'int',
    'width' => 'int',
    'height' => 'int',
    'map' => 'string',
    'pixelstorage' => 'int',
  ),
  'Imagick::getImageChannelKurtosis' => 
  array (
    0 => 'array',
    'channel=' => 'int',
  ),
  'Imagick::functionImage' => 
  array (
    0 => 'bool',
    'function' => 'int',
    'parameters' => 'array',
    'channel=' => 'int',
  ),
  'Imagick::transformImageColorspace' => 
  array (
    0 => 'bool',
    'colorspace' => 'int',
  ),
  'Imagick::haldClutImage' => 
  array (
    0 => 'bool',
    'clut' => 'Imagick',
    'channel=' => 'int',
  ),
  'Imagick::autoLevelImage' => 
  array (
    0 => 'bool',
    'channel=' => 'int',
  ),
  'Imagick::blueShiftImage' => 
  array (
    0 => 'bool',
    'factor=' => 'float',
  ),
  'Imagick::getImageArtifact' => 
  array (
    0 => 'string|null',
    'artifact' => 'string',
  ),
  'Imagick::setImageArtifact' => 
  array (
    0 => 'bool',
    'artifact' => 'string',
    'value' => 'string|null',
  ),
  'Imagick::deleteImageArtifact' => 
  array (
    0 => 'bool',
    'artifact' => 'string',
  ),
  'Imagick::getColorspace' => 
  array (
    0 => 'int',
  ),
  'Imagick::setColorspace' => 
  array (
    0 => 'bool',
    'colorspace' => 'int',
  ),
  'Imagick::clampImage' => 
  array (
    0 => 'bool',
    'channel=' => 'int',
  ),
  'Imagick::smushImages' => 
  array (
    0 => 'Imagick',
    'stack' => 'bool',
    'offset' => 'int',
  ),
  'Imagick::__construct' => 
  array (
    0 => 'string',
    'files=' => 'array|string|int|float|null|null',
  ),
  'Imagick::__toString' => 
  array (
    0 => 'string',
  ),
  'Imagick::count' => 
  array (
    0 => 'int',
    'mode=' => 'int',
  ),
  'Imagick::getPixelIterator' => 
  array (
    0 => 'ImagickPixelIterator',
  ),
  'Imagick::getPixelRegionIterator' => 
  array (
    0 => 'ImagickPixelIterator',
    'x' => 'int',
    'y' => 'int',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'Imagick::readImage' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Imagick::readImages' => 
  array (
    0 => 'bool',
    'filenames' => 'array',
  ),
  'Imagick::readImageBlob' => 
  array (
    0 => 'bool',
    'image' => 'string',
    'filename=' => 'string|null',
  ),
  'Imagick::setImageFormat' => 
  array (
    0 => 'bool',
    'format' => 'string',
  ),
  'Imagick::scaleImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'bestfit=' => 'bool',
    'legacy=' => 'bool',
  ),
  'Imagick::writeImage' => 
  array (
    0 => 'bool',
    'filename=' => 'string|null',
  ),
  'Imagick::writeImages' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'adjoin' => 'bool',
  ),
  'Imagick::blurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::thumbnailImage' => 
  array (
    0 => 'bool',
    'columns' => 'int|null',
    'rows' => 'int|null',
    'bestfit=' => 'bool',
    'fill=' => 'bool',
    'legacy=' => 'bool',
  ),
  'Imagick::cropThumbnailImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'legacy=' => 'bool',
  ),
  'Imagick::getImageFilename' => 
  array (
    0 => 'string',
  ),
  'Imagick::setImageFilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Imagick::getImageFormat' => 
  array (
    0 => 'string',
  ),
  'Imagick::getImageMimeType' => 
  array (
    0 => 'string',
  ),
  'Imagick::removeImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::destroy' => 
  array (
    0 => 'bool',
  ),
  'Imagick::clear' => 
  array (
    0 => 'bool',
  ),
  'Imagick::clone' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::getImageSize' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageBlob' => 
  array (
    0 => 'string',
  ),
  'Imagick::getImagesBlob' => 
  array (
    0 => 'string',
  ),
  'Imagick::setFirstIterator' => 
  array (
    0 => 'bool',
  ),
  'Imagick::setLastIterator' => 
  array (
    0 => 'bool',
  ),
  'Imagick::resetIterator' => 
  array (
    0 => 'void',
  ),
  'Imagick::previousImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::nextImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::hasPreviousImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::hasNextImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::setImageIndex' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'Imagick::getImageIndex' => 
  array (
    0 => 'int',
  ),
  'Imagick::commentImage' => 
  array (
    0 => 'bool',
    'comment' => 'string',
  ),
  'Imagick::cropImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::labelImage' => 
  array (
    0 => 'bool',
    'label' => 'string',
  ),
  'Imagick::getImageGeometry' => 
  array (
    0 => 'array',
  ),
  'Imagick::drawImage' => 
  array (
    0 => 'bool',
    'drawing' => 'ImagickDraw',
  ),
  'Imagick::setImageCompressionQuality' => 
  array (
    0 => 'bool',
    'quality' => 'int',
  ),
  'Imagick::getImageCompressionQuality' => 
  array (
    0 => 'int',
  ),
  'Imagick::setImageCompression' => 
  array (
    0 => 'bool',
    'compression' => 'int',
  ),
  'Imagick::getImageCompression' => 
  array (
    0 => 'int',
  ),
  'Imagick::annotateImage' => 
  array (
    0 => 'bool',
    'settings' => 'ImagickDraw',
    'x' => 'float',
    'y' => 'float',
    'angle' => 'float',
    'text' => 'string',
  ),
  'Imagick::compositeImage' => 
  array (
    0 => 'bool',
    'composite_image' => 'Imagick',
    'composite' => 'int',
    'x' => 'int',
    'y' => 'int',
    'channel=' => 'int',
  ),
  'Imagick::modulateImage' => 
  array (
    0 => 'bool',
    'brightness' => 'float',
    'saturation' => 'float',
    'hue' => 'float',
  ),
  'Imagick::getImageColors' => 
  array (
    0 => 'int',
  ),
  'Imagick::montageImage' => 
  array (
    0 => 'Imagick',
    'settings' => 'ImagickDraw',
    'tile_geometry' => 'string',
    'thumbnail_geometry' => 'string',
    'monatgemode' => 'int',
    'frame' => 'string',
  ),
  'Imagick::identifyImage' => 
  array (
    0 => 'array',
    'append_raw_output=' => 'bool',
  ),
  'Imagick::thresholdImage' => 
  array (
    0 => 'bool',
    'threshold' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::adaptiveThresholdImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'offset' => 'int',
  ),
  'Imagick::blackThresholdImage' => 
  array (
    0 => 'bool',
    'threshold_color' => 'ImagickPixel|string',
  ),
  'Imagick::whiteThresholdImage' => 
  array (
    0 => 'bool',
    'threshold_color' => 'ImagickPixel|string',
  ),
  'Imagick::appendImages' => 
  array (
    0 => 'Imagick',
    'stack' => 'bool',
  ),
  'Imagick::charcoalImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
  ),
  'Imagick::normalizeImage' => 
  array (
    0 => 'bool',
    'channel=' => 'int',
  ),
  'Imagick::oilPaintImageWithSigma' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
  ),
  'Imagick::oilPaintImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
  ),
  'Imagick::posterizeImage' => 
  array (
    0 => 'bool',
    'levels' => 'int',
    'dither' => 'bool',
  ),
  'Imagick::raiseImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
    'raise' => 'bool',
  ),
  'Imagick::resampleImage' => 
  array (
    0 => 'bool',
    'x_resolution' => 'float',
    'y_resolution' => 'float',
    'filter' => 'int',
    'blur' => 'float',
  ),
  'Imagick::resizeImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'filter' => 'int',
    'blur' => 'float',
    'bestfit=' => 'bool',
    'legacy=' => 'bool',
  ),
  'Imagick::rollImage' => 
  array (
    0 => 'bool',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::rotateImage' => 
  array (
    0 => 'bool',
    'background_color' => 'ImagickPixel|string',
    'degrees' => 'float',
  ),
  'Imagick::sampleImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'Imagick::solarizeImage' => 
  array (
    0 => 'bool',
    'threshold' => 'int',
  ),
  'Imagick::shadowImage' => 
  array (
    0 => 'bool',
    'opacity' => 'float',
    'sigma' => 'float',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::setImageBackgroundColor' => 
  array (
    0 => 'bool',
    'background_color' => 'ImagickPixel|string',
  ),
  'Imagick::setImageChannelMask' => 
  array (
    0 => 'int',
    'channel' => 'int',
  ),
  'Imagick::setImageCompose' => 
  array (
    0 => 'bool',
    'compose' => 'int',
  ),
  'Imagick::setImageDelay' => 
  array (
    0 => 'bool',
    'delay' => 'int',
  ),
  'Imagick::setImageDepth' => 
  array (
    0 => 'bool',
    'depth' => 'int',
  ),
  'Imagick::setImageGamma' => 
  array (
    0 => 'bool',
    'gamma' => 'float',
  ),
  'Imagick::setImageIterations' => 
  array (
    0 => 'bool',
    'iterations' => 'int',
  ),
  'Imagick::setImageMatteColor' => 
  array (
    0 => 'bool',
    'matte_color' => 'ImagickPixel|string',
  ),
  'Imagick::setImagePage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::setImageProgressMonitor' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Imagick::setProgressMonitor' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'Imagick::setImageResolution' => 
  array (
    0 => 'bool',
    'x_resolution' => 'float',
    'y_resolution' => 'float',
  ),
  'Imagick::setImageScene' => 
  array (
    0 => 'bool',
    'scene' => 'int',
  ),
  'Imagick::setImageTicksPerSecond' => 
  array (
    0 => 'bool',
    'ticks_per_second' => 'int',
  ),
  'Imagick::setImageType' => 
  array (
    0 => 'bool',
    'image_type' => 'int',
  ),
  'Imagick::setImageUnits' => 
  array (
    0 => 'bool',
    'units' => 'int',
  ),
  'Imagick::sharpenImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::shaveImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'Imagick::shearImage' => 
  array (
    0 => 'bool',
    'background_color' => 'ImagickPixel|string',
    'x_shear' => 'float',
    'y_shear' => 'float',
  ),
  'Imagick::spliceImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::pingImage' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Imagick::readImageFile' => 
  array (
    0 => 'bool',
    'filehandle' => 'mixed|null',
    'filename=' => 'string|null',
  ),
  'Imagick::displayImage' => 
  array (
    0 => 'bool',
    'servername' => 'string',
  ),
  'Imagick::displayImages' => 
  array (
    0 => 'bool',
    'servername' => 'string',
  ),
  'Imagick::spreadImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
  ),
  'Imagick::spreadImageWithMethod' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'interpolate_method' => 'int',
  ),
  'Imagick::swirlImage' => 
  array (
    0 => 'bool',
    'degrees' => 'float',
  ),
  'Imagick::swirlImageWithMethod' => 
  array (
    0 => 'bool',
    'degrees' => 'float',
    'interpolate_method' => 'int',
  ),
  'Imagick::stripImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::queryFormats' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
  ),
  'Imagick::queryFonts' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
  ),
  'Imagick::queryFontMetrics' => 
  array (
    0 => 'array',
    'settings' => 'ImagickDraw',
    'text' => 'string',
    'multiline=' => 'bool|null',
  ),
  'Imagick::steganoImage' => 
  array (
    0 => 'Imagick',
    'watermark' => 'Imagick',
    'offset' => 'int',
  ),
  'Imagick::addNoiseImage' => 
  array (
    0 => 'bool',
    'noise' => 'int',
    'channel=' => 'int',
  ),
  'Imagick::addNoiseImageWithAttenuate' => 
  array (
    0 => 'bool',
    'noise' => 'int',
    'attenuate' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::motionBlurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'angle' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::morphImages' => 
  array (
    0 => 'Imagick',
    'number_frames' => 'int',
  ),
  'Imagick::minifyImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::affineTransformImage' => 
  array (
    0 => 'bool',
    'settings' => 'ImagickDraw',
  ),
  'Imagick::averageImages' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::borderImage' => 
  array (
    0 => 'bool',
    'border_color' => 'ImagickPixel|string',
    'width' => 'int',
    'height' => 'int',
  ),
  'Imagick::borderImageWithComposite' => 
  array (
    0 => 'bool',
    'border_color' => 'ImagickPixel|string',
    'width' => 'int',
    'height' => 'int',
    'composite' => 'int',
  ),
  'Imagick::calculateCrop' => 
  array (
    0 => 'array',
    'original_width' => 'int',
    'original_height' => 'int',
    'desired_width' => 'int',
    'desired_height' => 'int',
    'legacy=' => 'bool',
  ),
  'Imagick::chopImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::clipImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::clipPathImage' => 
  array (
    0 => 'bool',
    'pathname' => 'string',
    'inside' => 'bool',
  ),
  'Imagick::clipImagePath' => 
  array (
    0 => 'void',
    'pathname' => 'string',
    'inside' => 'bool',
  ),
  'Imagick::coalesceImages' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::colorizeImage' => 
  array (
    0 => 'bool',
    'colorize_color' => 'ImagickPixel|string',
    'opacity_color' => 'ImagickPixel|string|false',
    'legacy=' => 'bool|null',
  ),
  'Imagick::compareImageChannels' => 
  array (
    0 => 'array',
    'reference' => 'Imagick',
    'channel' => 'int',
    'metric' => 'int',
  ),
  'Imagick::compareImages' => 
  array (
    0 => 'array',
    'reference' => 'Imagick',
    'metric' => 'int',
  ),
  'Imagick::contrastImage' => 
  array (
    0 => 'bool',
    'sharpen' => 'bool',
  ),
  'Imagick::combineImages' => 
  array (
    0 => 'Imagick',
    'colorspace' => 'int',
  ),
  'Imagick::convolveImage' => 
  array (
    0 => 'bool',
    'kernel' => 'array',
    'channel=' => 'int',
  ),
  'Imagick::cycleColormapImage' => 
  array (
    0 => 'bool',
    'displace' => 'int',
  ),
  'Imagick::deconstructImages' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::despeckleImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::edgeImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
  ),
  'Imagick::embossImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
  ),
  'Imagick::enhanceImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::equalizeImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::evaluateImage' => 
  array (
    0 => 'bool',
    'evaluate' => 'int',
    'constant' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::evaluateImages' => 
  array (
    0 => 'bool',
    'evaluate' => 'int',
  ),
  'Imagick::flattenImages' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::flipImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::flopImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::forwardFourierTransformImage' => 
  array (
    0 => 'bool',
    'magnitude' => 'bool',
  ),
  'Imagick::frameImage' => 
  array (
    0 => 'bool',
    'matte_color' => 'ImagickPixel|string',
    'width' => 'int',
    'height' => 'int',
    'inner_bevel' => 'int',
    'outer_bevel' => 'int',
  ),
  'Imagick::frameImageWithComposite' => 
  array (
    0 => 'bool',
    'matte_color' => 'ImagickPixel|string',
    'width' => 'int',
    'height' => 'int',
    'inner_bevel' => 'int',
    'outer_bevel' => 'int',
    'composite' => 'int',
  ),
  'Imagick::fxImage' => 
  array (
    0 => 'Imagick',
    'expression' => 'string',
    'channel=' => 'int',
  ),
  'Imagick::gammaImage' => 
  array (
    0 => 'bool',
    'gamma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::gaussianBlurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::getImageBackgroundColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'Imagick::getImageBluePrimary' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageBorderColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'Imagick::getImageChannelDepth' => 
  array (
    0 => 'int',
    'channel' => 'int',
  ),
  'Imagick::getImageChannelDistortion' => 
  array (
    0 => 'float',
    'reference' => 'Imagick',
    'channel' => 'int',
    'metric' => 'int',
  ),
  'Imagick::getImageChannelMean' => 
  array (
    0 => 'array',
    'channel' => 'int',
  ),
  'Imagick::getImageChannelStatistics' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageColormapColor' => 
  array (
    0 => 'ImagickPixel',
    'index' => 'int',
  ),
  'Imagick::getImageColorspace' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageCompose' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageDelay' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageDepth' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageDistortion' => 
  array (
    0 => 'float',
    'reference' => 'Imagick',
    'metric' => 'int',
  ),
  'Imagick::getImageDispose' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageGamma' => 
  array (
    0 => 'float',
  ),
  'Imagick::getImageGreenPrimary' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageHeight' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageHistogram' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageInterlaceScheme' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageIterations' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImagePage' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImagePixelColor' => 
  array (
    0 => 'ImagickPixel',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::setImagePixelColor' => 
  array (
    0 => 'ImagickPixel',
    'x' => 'int',
    'y' => 'int',
    'color' => 'ImagickPixel|string',
  ),
  'Imagick::getImageProfile' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'Imagick::getImageRedPrimary' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageRenderingIntent' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageResolution' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageScene' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageSignature' => 
  array (
    0 => 'string',
  ),
  'Imagick::getImageTicksPerSecond' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageType' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageUnits' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageVirtualPixelMethod' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageWhitePoint' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageWidth' => 
  array (
    0 => 'int',
  ),
  'Imagick::getNumberImages' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageTotalInkDensity' => 
  array (
    0 => 'float',
  ),
  'Imagick::getImageRegion' => 
  array (
    0 => 'Imagick',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::implodeImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
  ),
  'Imagick::implodeImageWithMethod' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'pixel_interpolate_method' => 'int',
  ),
  'Imagick::inverseFourierTransformImage' => 
  array (
    0 => 'bool',
    'complement' => 'Imagick',
    'magnitude' => 'bool',
  ),
  'Imagick::levelImage' => 
  array (
    0 => 'bool',
    'black_point' => 'float',
    'gamma' => 'float',
    'white_point' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::magnifyImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::negateImage' => 
  array (
    0 => 'bool',
    'gray' => 'bool',
    'channel=' => 'int',
  ),
  'Imagick::previewImages' => 
  array (
    0 => 'bool',
    'preview' => 'int',
  ),
  'Imagick::profileImage' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'profile' => 'string|null',
  ),
  'Imagick::quantizeImage' => 
  array (
    0 => 'bool',
    'number_colors' => 'int',
    'colorspace' => 'int',
    'tree_depth' => 'int',
    'dither' => 'bool',
    'measure_error' => 'bool',
  ),
  'Imagick::quantizeImages' => 
  array (
    0 => 'bool',
    'number_colors' => 'int',
    'colorspace' => 'int',
    'tree_depth' => 'int',
    'dither' => 'bool',
    'measure_error' => 'bool',
  ),
  'Imagick::removeImageProfile' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'Imagick::separateImageChannel' => 
  array (
    0 => 'bool',
    'channel' => 'int',
  ),
  'Imagick::sepiaToneImage' => 
  array (
    0 => 'bool',
    'threshold' => 'float',
  ),
  'Imagick::setImageBluePrimary' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'Imagick::setImageBorderColor' => 
  array (
    0 => 'bool',
    'border_color' => 'ImagickPixel|string',
  ),
  'Imagick::setImageChannelDepth' => 
  array (
    0 => 'bool',
    'channel' => 'int',
    'depth' => 'int',
  ),
  'Imagick::setImageColormapColor' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'color' => 'ImagickPixel|string',
  ),
  'Imagick::setImageColorspace' => 
  array (
    0 => 'bool',
    'colorspace' => 'int',
  ),
  'Imagick::setImageDispose' => 
  array (
    0 => 'bool',
    'dispose' => 'int',
  ),
  'Imagick::setImageExtent' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'Imagick::setImageGreenPrimary' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'Imagick::setImageInterlaceScheme' => 
  array (
    0 => 'bool',
    'interlace' => 'int',
  ),
  'Imagick::setImageProfile' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'profile' => 'string',
  ),
  'Imagick::setImageRedPrimary' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'Imagick::setImageRenderingIntent' => 
  array (
    0 => 'bool',
    'rendering_intent' => 'int',
  ),
  'Imagick::setImageVirtualPixelMethod' => 
  array (
    0 => 'bool',
    'method' => 'int',
  ),
  'Imagick::setImageWhitePoint' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'Imagick::sigmoidalContrastImage' => 
  array (
    0 => 'bool',
    'sharpen' => 'bool',
    'alpha' => 'float',
    'beta' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::stereoImage' => 
  array (
    0 => 'bool',
    'offset_image' => 'Imagick',
  ),
  'Imagick::textureImage' => 
  array (
    0 => 'Imagick',
    'texture' => 'Imagick',
  ),
  'Imagick::tintImage' => 
  array (
    0 => 'bool',
    'tint_color' => 'ImagickPixel|string',
    'opacity_color' => 'ImagickPixel|string',
    'legacy=' => 'bool',
  ),
  'Imagick::unsharpMaskImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'amount' => 'float',
    'threshold' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::getImage' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::addImage' => 
  array (
    0 => 'bool',
    'image' => 'Imagick',
  ),
  'Imagick::setImage' => 
  array (
    0 => 'bool',
    'image' => 'Imagick',
  ),
  'Imagick::newImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'background_color' => 'ImagickPixel|string',
    'format=' => 'string',
  ),
  'Imagick::newPseudoImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'pseudo_format' => 'string',
  ),
  'Imagick::getCompression' => 
  array (
    0 => 'int',
  ),
  'Imagick::getCompressionQuality' => 
  array (
    0 => 'int',
  ),
  'Imagick::getCopyright' => 
  array (
    0 => 'string',
  ),
  'Imagick::getConfigureOptions' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
  ),
  'Imagick::getFeatures' => 
  array (
    0 => 'string',
  ),
  'Imagick::getFilename' => 
  array (
    0 => 'string',
  ),
  'Imagick::getFormat' => 
  array (
    0 => 'string',
  ),
  'Imagick::getHomeURL' => 
  array (
    0 => 'string',
  ),
  'Imagick::getInterlaceScheme' => 
  array (
    0 => 'int',
  ),
  'Imagick::getOption' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'Imagick::getPackageName' => 
  array (
    0 => 'string',
  ),
  'Imagick::getPage' => 
  array (
    0 => 'array',
  ),
  'Imagick::getQuantum' => 
  array (
    0 => 'int',
  ),
  'Imagick::getHdriEnabled' => 
  array (
    0 => 'bool',
  ),
  'Imagick::getQuantumDepth' => 
  array (
    0 => 'array',
  ),
  'Imagick::getQuantumRange' => 
  array (
    0 => 'array',
  ),
  'Imagick::getReleaseDate' => 
  array (
    0 => 'string',
  ),
  'Imagick::getResource' => 
  array (
    0 => 'int',
    'type' => 'int',
  ),
  'Imagick::getResourceLimit' => 
  array (
    0 => 'int',
    'type' => 'int',
  ),
  'Imagick::getSamplingFactors' => 
  array (
    0 => 'array',
  ),
  'Imagick::getSize' => 
  array (
    0 => 'array',
  ),
  'Imagick::getVersion' => 
  array (
    0 => 'array',
  ),
  'Imagick::setBackgroundColor' => 
  array (
    0 => 'bool',
    'background_color' => 'ImagickPixel|string',
  ),
  'Imagick::setCompression' => 
  array (
    0 => 'bool',
    'compression' => 'int',
  ),
  'Imagick::setCompressionQuality' => 
  array (
    0 => 'bool',
    'quality' => 'int',
  ),
  'Imagick::setFilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'Imagick::setFormat' => 
  array (
    0 => 'bool',
    'format' => 'string',
  ),
  'Imagick::setInterlaceScheme' => 
  array (
    0 => 'bool',
    'interlace' => 'int',
  ),
  'Imagick::setOption' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'value' => 'string',
  ),
  'Imagick::setPage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'x' => 'int',
    'y' => 'int',
  ),
  'Imagick::setResourceLimit' => 
  array (
    0 => 'bool',
    'type' => 'int',
    'limit' => 'int',
  ),
  'Imagick::setResolution' => 
  array (
    0 => 'bool',
    'x_resolution' => 'float',
    'y_resolution' => 'float',
  ),
  'Imagick::setSamplingFactors' => 
  array (
    0 => 'bool',
    'factors' => 'array',
  ),
  'Imagick::setSize' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'Imagick::setType' => 
  array (
    0 => 'bool',
    'imgtype' => 'int',
  ),
  'Imagick::key' => 
  array (
    0 => 'int',
  ),
  'Imagick::next' => 
  array (
    0 => 'string',
  ),
  'Imagick::rewind' => 
  array (
    0 => 'string',
  ),
  'Imagick::valid' => 
  array (
    0 => 'bool',
  ),
  'Imagick::current' => 
  array (
    0 => 'Imagick',
  ),
  'Imagick::brightnessContrastImage' => 
  array (
    0 => 'bool',
    'brightness' => 'float',
    'contrast' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::colorMatrixImage' => 
  array (
    0 => 'bool',
    'color_matrix' => 'array',
  ),
  'Imagick::selectiveBlurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'threshold' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::rotationalBlurImage' => 
  array (
    0 => 'bool',
    'angle' => 'float',
    'channel=' => 'int',
  ),
  'Imagick::statisticImage' => 
  array (
    0 => 'bool',
    'type' => 'int',
    'width' => 'int',
    'height' => 'int',
    'channel=' => 'int',
  ),
  'Imagick::subimageMatch' => 
  array (
    0 => 'Imagick',
    'image' => 'Imagick',
    '&offset=' => 'array|null',
    '&similarity=' => 'float|null',
    'threshold=' => 'float',
    'metric=' => 'int',
  ),
  'Imagick::similarityImage' => 
  array (
    0 => 'Imagick',
    'image' => 'Imagick',
    '&offset=' => 'array|null',
    '&similarity=' => 'float|null',
    'threshold=' => 'float',
    'metric=' => 'int',
  ),
  'Imagick::setRegistry' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'value' => 'string',
  ),
  'Imagick::getRegistry' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'Imagick::listRegistry' => 
  array (
    0 => 'array',
  ),
  'Imagick::morphology' => 
  array (
    0 => 'bool',
    'morphology' => 'int',
    'iterations' => 'int',
    'kernel' => 'ImagickKernel',
    'channel=' => 'int',
  ),
  'Imagick::setAntialias' => 
  array (
    0 => 'void',
    'antialias' => 'bool',
  ),
  'Imagick::getAntialias' => 
  array (
    0 => 'bool',
  ),
  'Imagick::colorDecisionListImage' => 
  array (
    0 => 'bool',
    'color_correction_collection' => 'string',
  ),
  'Imagick::optimizeImageTransparency' => 
  array (
    0 => 'void',
  ),
  'Imagick::autoGammaImage' => 
  array (
    0 => 'void',
    'channel=' => 'int|null',
  ),
  'Imagick::autoOrient' => 
  array (
    0 => 'void',
  ),
  'Imagick::autoOrientate' => 
  array (
    0 => 'void',
  ),
  'Imagick::compositeImageGravity' => 
  array (
    0 => 'bool',
    'image' => 'Imagick',
    'composite_constant' => 'int',
    'gravity' => 'int',
  ),
  'Imagick::localContrastImage' => 
  array (
    0 => 'void',
    'radius' => 'float',
    'strength' => 'float',
  ),
  'Imagick::identifyImageType' => 
  array (
    0 => 'int',
  ),
  'Imagick::getImageMask' => 
  array (
    0 => 'Imagick|null',
    'pixelmask' => 'int',
  ),
  'Imagick::setImageMask' => 
  array (
    0 => 'void',
    'clip_mask' => 'Imagick',
    'pixelmask' => 'int',
  ),
  'Imagick::cannyEdgeImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'lower_percent' => 'float',
    'upper_percent' => 'float',
  ),
  'Imagick::setSeed' => 
  array (
    0 => 'void',
    'seed' => 'int',
  ),
  'Imagick::waveletDenoiseImage' => 
  array (
    0 => 'bool',
    'threshold' => 'float',
    'softness' => 'float',
  ),
  'Imagick::meanShiftImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'color_distance' => 'float',
  ),
  'Imagick::kmeansImage' => 
  array (
    0 => 'bool',
    'number_colors' => 'int',
    'max_iterations' => 'int',
    'tolerance' => 'float',
  ),
  'Imagick::rangeThresholdImage' => 
  array (
    0 => 'bool',
    'low_black' => 'float',
    'low_white' => 'float',
    'high_white' => 'float',
    'high_black' => 'float',
  ),
  'Imagick::autoThresholdImage' => 
  array (
    0 => 'bool',
    'auto_threshold_method' => 'int',
  ),
  'Imagick::bilateralBlurImage' => 
  array (
    0 => 'bool',
    'radius' => 'float',
    'sigma' => 'float',
    'intensity_sigma' => 'float',
    'spatial_sigma' => 'float',
  ),
  'Imagick::claheImage' => 
  array (
    0 => 'bool',
    'width' => 'int',
    'height' => 'int',
    'number_bins' => 'int',
    'clip_limit' => 'float',
  ),
  'Imagick::channelFxImage' => 
  array (
    0 => 'Imagick',
    'expression' => 'string',
  ),
  'Imagick::colorThresholdImage' => 
  array (
    0 => 'bool',
    'start_color' => 'ImagickPixel|string',
    'stop_color' => 'ImagickPixel|string',
  ),
  'Imagick::complexImages' => 
  array (
    0 => 'Imagick',
    'complex_operator' => 'int',
  ),
  'Imagick::interpolativeResizeImage' => 
  array (
    0 => 'bool',
    'columns' => 'int',
    'rows' => 'int',
    'interpolate' => 'int',
  ),
  'Imagick::levelImageColors' => 
  array (
    0 => 'bool',
    'black_color' => 'ImagickPixel|string',
    'white_color' => 'ImagickPixel|string',
    'invert' => 'bool',
  ),
  'Imagick::levelizeImage' => 
  array (
    0 => 'bool',
    'black_point' => 'float',
    'gamma' => 'float',
    'white_point' => 'float',
  ),
  'Imagick::orderedDitherImage' => 
  array (
    0 => 'bool',
    'dither_format' => 'string',
  ),
  'Imagick::whiteBalanceImage' => 
  array (
    0 => 'bool',
  ),
  'Imagick::deleteOption' => 
  array (
    0 => 'bool',
    'option' => 'string',
  ),
  'Imagick::getBackgroundColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'Imagick::getImageArtifacts' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
  ),
  'Imagick::getImageKurtosis' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageMean' => 
  array (
    0 => 'array',
  ),
  'Imagick::getImageRange' => 
  array (
    0 => 'array',
  ),
  'Imagick::getInterpolateMethod' => 
  array (
    0 => 'int',
  ),
  'Imagick::getOptions' => 
  array (
    0 => 'array',
    'pattern=' => 'string',
  ),
  'Imagick::getOrientation' => 
  array (
    0 => 'int',
  ),
  'Imagick::getResolution' => 
  array (
    0 => 'array',
  ),
  'Imagick::getType' => 
  array (
    0 => 'int',
  ),
  'Imagick::polynomialImage' => 
  array (
    0 => 'bool',
    'terms' => 'array',
  ),
  'Imagick::setDepth' => 
  array (
    0 => 'bool',
    'depth' => 'int',
  ),
  'Imagick::setExtract' => 
  array (
    0 => 'bool',
    'geometry' => 'string',
  ),
  'Imagick::setInterpolateMethod' => 
  array (
    0 => 'bool',
    'method' => 'int',
  ),
  'Imagick::setOrientation' => 
  array (
    0 => 'bool',
    'orientation' => 'int',
  ),
  'ImagickDraw::resetVectorGraphics' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::getTextKerning' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::setTextKerning' => 
  array (
    0 => 'bool',
    'kerning' => 'float',
  ),
  'ImagickDraw::getTextInterwordSpacing' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::setTextInterwordSpacing' => 
  array (
    0 => 'bool',
    'spacing' => 'float',
  ),
  'ImagickDraw::getTextInterlineSpacing' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::setTextInterlineSpacing' => 
  array (
    0 => 'bool',
    'spacing' => 'float',
  ),
  'ImagickDraw::__construct' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::setFillColor' => 
  array (
    0 => 'bool',
    'fill_color' => 'ImagickPixel|string',
  ),
  'ImagickDraw::setFillAlpha' => 
  array (
    0 => 'bool',
    'alpha' => 'float',
  ),
  'ImagickDraw::setResolution' => 
  array (
    0 => 'bool',
    'resolution_x' => 'float',
    'resolution_y' => 'float',
  ),
  'ImagickDraw::setStrokeColor' => 
  array (
    0 => 'bool',
    'color' => 'ImagickPixel|string',
  ),
  'ImagickDraw::setStrokeAlpha' => 
  array (
    0 => 'bool',
    'alpha' => 'float',
  ),
  'ImagickDraw::setStrokeWidth' => 
  array (
    0 => 'bool',
    'width' => 'float',
  ),
  'ImagickDraw::clear' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::circle' => 
  array (
    0 => 'bool',
    'origin_x' => 'float',
    'origin_y' => 'float',
    'perimeter_x' => 'float',
    'perimeter_y' => 'float',
  ),
  'ImagickDraw::annotation' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
    'text' => 'string',
  ),
  'ImagickDraw::setTextAntialias' => 
  array (
    0 => 'bool',
    'antialias' => 'bool',
  ),
  'ImagickDraw::setTextEncoding' => 
  array (
    0 => 'bool',
    'encoding' => 'string',
  ),
  'ImagickDraw::setFont' => 
  array (
    0 => 'bool',
    'font_name' => 'string',
  ),
  'ImagickDraw::setFontFamily' => 
  array (
    0 => 'bool',
    'font_family' => 'string',
  ),
  'ImagickDraw::setFontSize' => 
  array (
    0 => 'bool',
    'point_size' => 'float',
  ),
  'ImagickDraw::setFontStyle' => 
  array (
    0 => 'bool',
    'style' => 'int',
  ),
  'ImagickDraw::setFontWeight' => 
  array (
    0 => 'bool',
    'weight' => 'int',
  ),
  'ImagickDraw::getFont' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::getFontFamily' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::getFontSize' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::getFontStyle' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getFontWeight' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::destroy' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::rectangle' => 
  array (
    0 => 'bool',
    'top_left_x' => 'float',
    'top_left_y' => 'float',
    'bottom_right_x' => 'float',
    'bottom_right_y' => 'float',
  ),
  'ImagickDraw::roundRectangle' => 
  array (
    0 => 'bool',
    'top_left_x' => 'float',
    'top_left_y' => 'float',
    'bottom_right_x' => 'float',
    'bottom_right_y' => 'float',
    'rounding_x' => 'float',
    'rounding_y' => 'float',
  ),
  'ImagickDraw::ellipse' => 
  array (
    0 => 'bool',
    'origin_x' => 'float',
    'origin_y' => 'float',
    'radius_x' => 'float',
    'radius_y' => 'float',
    'angle_start' => 'float',
    'angle_end' => 'float',
  ),
  'ImagickDraw::skewX' => 
  array (
    0 => 'bool',
    'degrees' => 'float',
  ),
  'ImagickDraw::skewY' => 
  array (
    0 => 'bool',
    'degrees' => 'float',
  ),
  'ImagickDraw::translate' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::line' => 
  array (
    0 => 'bool',
    'start_x' => 'float',
    'start_y' => 'float',
    'end_x' => 'float',
    'end_y' => 'float',
  ),
  'ImagickDraw::arc' => 
  array (
    0 => 'bool',
    'start_x' => 'float',
    'start_y' => 'float',
    'end_x' => 'float',
    'end_y' => 'float',
    'start_angle' => 'float',
    'end_angle' => 'float',
  ),
  'ImagickDraw::alpha' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
    'paint' => 'int',
  ),
  'ImagickDraw::polygon' => 
  array (
    0 => 'bool',
    'coordinates' => 'array',
  ),
  'ImagickDraw::point' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::getTextDecoration' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getTextEncoding' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::getFontStretch' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::setFontStretch' => 
  array (
    0 => 'bool',
    'stretch' => 'int',
  ),
  'ImagickDraw::setStrokeAntialias' => 
  array (
    0 => 'bool',
    'enabled' => 'bool',
  ),
  'ImagickDraw::setTextAlignment' => 
  array (
    0 => 'bool',
    'align' => 'int',
  ),
  'ImagickDraw::setTextDecoration' => 
  array (
    0 => 'bool',
    'decoration' => 'int',
  ),
  'ImagickDraw::setTextUnderColor' => 
  array (
    0 => 'bool',
    'under_color' => 'ImagickPixel|string',
  ),
  'ImagickDraw::setViewbox' => 
  array (
    0 => 'bool',
    'left_x' => 'int',
    'top_y' => 'int',
    'right_x' => 'int',
    'bottom_y' => 'int',
  ),
  'ImagickDraw::clone' => 
  array (
    0 => 'ImagickDraw',
  ),
  'ImagickDraw::affine' => 
  array (
    0 => 'bool',
    'affine' => 'array',
  ),
  'ImagickDraw::bezier' => 
  array (
    0 => 'bool',
    'coordinates' => 'array',
  ),
  'ImagickDraw::composite' => 
  array (
    0 => 'bool',
    'composite' => 'int',
    'x' => 'float',
    'y' => 'float',
    'width' => 'float',
    'height' => 'float',
    'image' => 'Imagick',
  ),
  'ImagickDraw::color' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
    'paint' => 'int',
  ),
  'ImagickDraw::comment' => 
  array (
    0 => 'bool',
    'comment' => 'string',
  ),
  'ImagickDraw::getClipPath' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::getClipRule' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getClipUnits' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getFillColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'ImagickDraw::getFillOpacity' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::getFillRule' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getGravity' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getStrokeAntialias' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::getStrokeColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'ImagickDraw::getStrokeDashArray' => 
  array (
    0 => 'array',
  ),
  'ImagickDraw::getStrokeDashOffset' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::getStrokeLineCap' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getStrokeLineJoin' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getStrokeMiterLimit' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getStrokeOpacity' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::getStrokeWidth' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::getTextAlignment' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::getTextAntialias' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::getVectorGraphics' => 
  array (
    0 => 'string',
  ),
  'ImagickDraw::getTextUnderColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'ImagickDraw::pathClose' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::pathCurveToAbsolute' => 
  array (
    0 => 'bool',
    'x1' => 'float',
    'y1' => 'float',
    'x2' => 'float',
    'y2' => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToRelative' => 
  array (
    0 => 'bool',
    'x1' => 'float',
    'y1' => 'float',
    'x2' => 'float',
    'y2' => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToQuadraticBezierAbsolute' => 
  array (
    0 => 'bool',
    'x1' => 'float',
    'y1' => 'float',
    'x_end' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToQuadraticBezierRelative' => 
  array (
    0 => 'bool',
    'x1' => 'float',
    'y1' => 'float',
    'x_end' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToQuadraticBezierSmoothAbsolute' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToQuadraticBezierSmoothRelative' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToSmoothAbsolute' => 
  array (
    0 => 'bool',
    'x2' => 'float',
    'y2' => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathCurveToSmoothRelative' => 
  array (
    0 => 'bool',
    'x2' => 'float',
    'y2' => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathEllipticArcAbsolute' => 
  array (
    0 => 'bool',
    'rx' => 'float',
    'ry' => 'float',
    'x_axis_rotation' => 'float',
    'large_arc' => 'bool',
    'sweep' => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathEllipticArcRelative' => 
  array (
    0 => 'bool',
    'rx' => 'float',
    'ry' => 'float',
    'x_axis_rotation' => 'float',
    'large_arc' => 'bool',
    'sweep' => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathFinish' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::pathLineToAbsolute' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathLineToRelative' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathLineToHorizontalAbsolute' => 
  array (
    0 => 'bool',
    'x' => 'float',
  ),
  'ImagickDraw::pathLineToHorizontalRelative' => 
  array (
    0 => 'bool',
    'x' => 'float',
  ),
  'ImagickDraw::pathLineToVerticalAbsolute' => 
  array (
    0 => 'bool',
    'y' => 'float',
  ),
  'ImagickDraw::pathLineToVerticalRelative' => 
  array (
    0 => 'bool',
    'y' => 'float',
  ),
  'ImagickDraw::pathMoveToAbsolute' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathMoveToRelative' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::pathStart' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::polyline' => 
  array (
    0 => 'bool',
    'coordinates' => 'array',
  ),
  'ImagickDraw::popClipPath' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::popDefs' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::popPattern' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::pushClipPath' => 
  array (
    0 => 'bool',
    'clip_mask_id' => 'string',
  ),
  'ImagickDraw::pushDefs' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::pushPattern' => 
  array (
    0 => 'bool',
    'pattern_id' => 'string',
    'x' => 'float',
    'y' => 'float',
    'width' => 'float',
    'height' => 'float',
  ),
  'ImagickDraw::render' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::rotate' => 
  array (
    0 => 'bool',
    'degrees' => 'float',
  ),
  'ImagickDraw::scale' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::setClipPath' => 
  array (
    0 => 'bool',
    'clip_mask' => 'string',
  ),
  'ImagickDraw::setClipRule' => 
  array (
    0 => 'bool',
    'fillrule' => 'int',
  ),
  'ImagickDraw::setClipUnits' => 
  array (
    0 => 'bool',
    'pathunits' => 'int',
  ),
  'ImagickDraw::setFillOpacity' => 
  array (
    0 => 'bool',
    'opacity' => 'float',
  ),
  'ImagickDraw::setFillPatternUrl' => 
  array (
    0 => 'bool',
    'fill_url' => 'string',
  ),
  'ImagickDraw::setFillRule' => 
  array (
    0 => 'bool',
    'fillrule' => 'int',
  ),
  'ImagickDraw::setGravity' => 
  array (
    0 => 'bool',
    'gravity' => 'int',
  ),
  'ImagickDraw::setStrokePatternUrl' => 
  array (
    0 => 'bool',
    'stroke_url' => 'string',
  ),
  'ImagickDraw::setStrokeDashOffset' => 
  array (
    0 => 'bool',
    'dash_offset' => 'float',
  ),
  'ImagickDraw::setStrokeLineCap' => 
  array (
    0 => 'bool',
    'linecap' => 'int',
  ),
  'ImagickDraw::setStrokeLineJoin' => 
  array (
    0 => 'bool',
    'linejoin' => 'int',
  ),
  'ImagickDraw::setStrokeMiterLimit' => 
  array (
    0 => 'bool',
    'miterlimit' => 'int',
  ),
  'ImagickDraw::setStrokeOpacity' => 
  array (
    0 => 'bool',
    'opacity' => 'float',
  ),
  'ImagickDraw::setVectorGraphics' => 
  array (
    0 => 'bool',
    'xml' => 'string',
  ),
  'ImagickDraw::pop' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::push' => 
  array (
    0 => 'bool',
  ),
  'ImagickDraw::setStrokeDashArray' => 
  array (
    0 => 'bool',
    'dashes' => 'array',
  ),
  'ImagickDraw::getOpacity' => 
  array (
    0 => 'float',
  ),
  'ImagickDraw::setOpacity' => 
  array (
    0 => 'bool',
    'opacity' => 'float',
  ),
  'ImagickDraw::getFontResolution' => 
  array (
    0 => 'array',
  ),
  'ImagickDraw::setFontResolution' => 
  array (
    0 => 'bool',
    'x' => 'float',
    'y' => 'float',
  ),
  'ImagickDraw::getBorderColor' => 
  array (
    0 => 'ImagickPixel',
  ),
  'ImagickDraw::setBorderColor' => 
  array (
    0 => 'bool',
    'color' => 'ImagickPixel|string',
  ),
  'ImagickDraw::setDensity' => 
  array (
    0 => 'bool',
    'density' => 'string',
  ),
  'ImagickDraw::getDensity' => 
  array (
    0 => 'string|null',
  ),
  'ImagickDraw::getTextDirection' => 
  array (
    0 => 'int',
  ),
  'ImagickDraw::setTextDirection' => 
  array (
    0 => 'bool',
    'direction' => 'int',
  ),
  'ImagickPixelIterator::__construct' => 
  array (
    0 => 'string',
    'imagick' => 'Imagick',
  ),
  'ImagickPixelIterator::clear' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::getPixelIterator' => 
  array (
    0 => 'ImagickPixelIterator',
    'imagick' => 'Imagick',
  ),
  'ImagickPixelIterator::getPixelRegionIterator' => 
  array (
    0 => 'ImagickPixelIterator',
    'imagick' => 'Imagick',
    'x' => 'int',
    'y' => 'int',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'ImagickPixelIterator::destroy' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::getCurrentIteratorRow' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelIterator::getIteratorRow' => 
  array (
    0 => 'int',
  ),
  'ImagickPixelIterator::getNextIteratorRow' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelIterator::getPreviousIteratorRow' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelIterator::key' => 
  array (
    0 => 'int',
  ),
  'ImagickPixelIterator::next' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'ImagickPixelIterator::current' => 
  array (
    0 => 'array',
  ),
  'ImagickPixelIterator::newPixelIterator' => 
  array (
    0 => 'bool',
    'imagick' => 'Imagick',
  ),
  'ImagickPixelIterator::newPixelRegionIterator' => 
  array (
    0 => 'bool',
    'imagick' => 'Imagick',
    'x' => 'int',
    'y' => 'int',
    'columns' => 'int',
    'rows' => 'int',
  ),
  'ImagickPixelIterator::resetIterator' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::setIteratorFirstRow' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::setIteratorLastRow' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::setIteratorRow' => 
  array (
    0 => 'bool',
    'row' => 'int',
  ),
  'ImagickPixelIterator::syncIterator' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixelIterator::valid' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixel::__construct' => 
  array (
    0 => 'string',
    'color=' => 'string|null',
  ),
  'ImagickPixel::clear' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixel::destroy' => 
  array (
    0 => 'bool',
  ),
  'ImagickPixel::getColor' => 
  array (
    0 => 'array',
    'normalized=' => 'int',
  ),
  'ImagickPixel::getColorAsString' => 
  array (
    0 => 'string',
  ),
  'ImagickPixel::getColorCount' => 
  array (
    0 => 'int',
  ),
  'ImagickPixel::getColorQuantum' => 
  array (
    0 => 'array',
  ),
  'ImagickPixel::getColorValue' => 
  array (
    0 => 'float',
    'color' => 'int',
  ),
  'ImagickPixel::getColorValueQuantum' => 
  array (
    0 => 'float',
    'color' => 'int',
  ),
  'ImagickPixel::getHSL' => 
  array (
    0 => 'array',
  ),
  'ImagickPixel::getIndex' => 
  array (
    0 => 'int',
  ),
  'ImagickPixel::isPixelSimilar' => 
  array (
    0 => 'bool',
    'color' => 'ImagickPixel|string',
    'fuzz' => 'float',
  ),
  'ImagickPixel::isPixelSimilarQuantum' => 
  array (
    0 => 'bool',
    'color' => 'ImagickPixel|string',
    'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
  ),
  'ImagickPixel::isSimilar' => 
  array (
    0 => 'bool',
    'color' => 'ImagickPixel|string',
    'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
  ),
  'ImagickPixel::setColor' => 
  array (
    0 => 'bool',
    'color' => 'string',
  ),
  'ImagickPixel::setColorCount' => 
  array (
    0 => 'bool',
    'color_count' => 'int',
  ),
  'ImagickPixel::setColorValue' => 
  array (
    0 => 'bool',
    'color' => 'int',
    'value' => 'float',
  ),
  'ImagickPixel::setColorValueQuantum' => 
  array (
    0 => 'bool',
    'color' => 'int',
    'value' => 'float',
  ),
  'ImagickPixel::setHSL' => 
  array (
    0 => 'bool',
    'hue' => 'float',
    'saturation' => 'float',
    'luminosity' => 'float',
  ),
  'ImagickPixel::setIndex' => 
  array (
    0 => 'bool',
    'index' => 'float',
  ),
  'ImagickPixel::setColorFromPixel' => 
  array (
    0 => 'bool',
    'pixel' => 'ImagickPixel',
  ),
  'ImagickKernel::addKernel' => 
  array (
    0 => 'void',
    'kernel' => 'ImagickKernel',
  ),
  'ImagickKernel::addUnityKernel' => 
  array (
    0 => 'void',
    'scale' => 'float',
  ),
  'ImagickKernel::fromBuiltin' => 
  array (
    0 => 'ImagickKernel',
    'kernel' => 'int',
    'shape' => 'string',
  ),
  'ImagickKernel::fromMatrix' => 
  array (
    0 => 'ImagickKernel',
    'matrix' => 'array',
    'origin' => 'array|null',
  ),
  'ImagickKernel::getMatrix' => 
  array (
    0 => 'array',
  ),
  'ImagickKernel::scale' => 
  array (
    0 => 'void',
    'scale' => 'float',
    'normalize_kernel=' => 'int|null',
  ),
  'ImagickKernel::separate' => 
  array (
    0 => 'array',
  ),
  'Collator::__construct' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Collator::create' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Collator::compare' => 
  array (
    0 => 'string',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'Collator::sort' => 
  array (
    0 => 'string',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'Collator::sortWithSortKeys' => 
  array (
    0 => 'string',
    '&array' => 'array',
  ),
  'Collator::asort' => 
  array (
    0 => 'string',
    '&array' => 'array',
    'flags=' => 'int',
  ),
  'Collator::getAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
  ),
  'Collator::setAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
    'value' => 'int',
  ),
  'Collator::getStrength' => 
  array (
    0 => 'string',
  ),
  'Collator::setStrength' => 
  array (
    0 => 'string',
    'strength' => 'int',
  ),
  'Collator::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'Collator::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'Collator::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'Collator::getSortKey' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'NumberFormatter::__construct' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'string|null',
  ),
  'NumberFormatter::create' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'string|null',
  ),
  'NumberFormatter::format' => 
  array (
    0 => 'string',
    'num' => 'int|float',
    'type=' => 'int',
  ),
  'NumberFormatter::parse' => 
  array (
    0 => 'string',
    'string' => 'string',
    'type=' => 'int',
    '&offset=' => 'string',
  ),
  'NumberFormatter::formatCurrency' => 
  array (
    0 => 'string',
    'amount' => 'float',
    'currency' => 'string',
  ),
  'NumberFormatter::parseCurrency' => 
  array (
    0 => 'string',
    'string' => 'string',
    '&currency' => 'string',
    '&offset=' => 'string',
  ),
  'NumberFormatter::setAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
    'value' => 'int|float',
  ),
  'NumberFormatter::getAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
  ),
  'NumberFormatter::setTextAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
    'value' => 'string',
  ),
  'NumberFormatter::getTextAttribute' => 
  array (
    0 => 'string',
    'attribute' => 'int',
  ),
  'NumberFormatter::setSymbol' => 
  array (
    0 => 'string',
    'symbol' => 'int',
    'value' => 'string',
  ),
  'NumberFormatter::getSymbol' => 
  array (
    0 => 'string',
    'symbol' => 'int',
  ),
  'NumberFormatter::setPattern' => 
  array (
    0 => 'string',
    'pattern' => 'string',
  ),
  'NumberFormatter::getPattern' => 
  array (
    0 => 'string',
  ),
  'NumberFormatter::getLocale' => 
  array (
    0 => 'string',
    'type=' => 'int',
  ),
  'NumberFormatter::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'NumberFormatter::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'Normalizer::normalize' => 
  array (
    0 => 'string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'Normalizer::isNormalized' => 
  array (
    0 => 'string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'Normalizer::getRawDecomposition' => 
  array (
    0 => 'string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'Locale::getDefault' => 
  array (
    0 => 'string',
  ),
  'Locale::setDefault' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getPrimaryLanguage' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getScript' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getRegion' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getKeywords' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getDisplayScript' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'Locale::getDisplayRegion' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'Locale::getDisplayName' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'Locale::getDisplayLanguage' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'Locale::getDisplayVariant' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'displayLocale=' => 'string|null',
  ),
  'Locale::composeLocale' => 
  array (
    0 => 'string',
    'subtags' => 'array',
  ),
  'Locale::parseLocale' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::getAllVariants' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::filterMatches' => 
  array (
    0 => 'string',
    'languageTag' => 'string',
    'locale' => 'string',
    'canonicalize=' => 'bool',
  ),
  'Locale::lookup' => 
  array (
    0 => 'string',
    'languageTag' => 'array',
    'locale' => 'string',
    'canonicalize=' => 'bool',
    'defaultLocale=' => 'string|null',
  ),
  'Locale::canonicalize' => 
  array (
    0 => 'string',
    'locale' => 'string',
  ),
  'Locale::acceptFromHttp' => 
  array (
    0 => 'string',
    'header' => 'string',
  ),
  'MessageFormatter::__construct' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'MessageFormatter::create' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'MessageFormatter::format' => 
  array (
    0 => 'string',
    'values' => 'array',
  ),
  'MessageFormatter::formatMessage' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'pattern' => 'string',
    'values' => 'array',
  ),
  'MessageFormatter::parse' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'MessageFormatter::parseMessage' => 
  array (
    0 => 'string',
    'locale' => 'string',
    'pattern' => 'string',
    'message' => 'string',
  ),
  'MessageFormatter::setPattern' => 
  array (
    0 => 'string',
    'pattern' => 'string',
  ),
  'MessageFormatter::getPattern' => 
  array (
    0 => 'string',
  ),
  'MessageFormatter::getLocale' => 
  array (
    0 => 'string',
  ),
  'MessageFormatter::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'MessageFormatter::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::__construct' => 
  array (
    0 => 'string',
    'locale' => 'string|null',
    'dateType' => 'int',
    'timeType' => 'int',
    'timezone=' => 'string',
    'calendar=' => 'string',
    'pattern=' => 'string|null',
  ),
  'IntlDateFormatter::create' => 
  array (
    0 => 'string',
    'locale' => 'string|null',
    'dateType' => 'int',
    'timeType' => 'int',
    'timezone=' => 'string',
    'calendar=' => 'IntlCalendar|int|null|null',
    'pattern=' => 'string|null',
  ),
  'IntlDateFormatter::getDateType' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getTimeType' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getCalendar' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::setCalendar' => 
  array (
    0 => 'string',
    'calendar' => 'IntlCalendar|int|null|null',
  ),
  'IntlDateFormatter::getTimeZoneId' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getCalendarObject' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getTimeZone' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::setTimeZone' => 
  array (
    0 => 'string',
    'timezone' => 'string',
  ),
  'IntlDateFormatter::setPattern' => 
  array (
    0 => 'string',
    'pattern' => 'string',
  ),
  'IntlDateFormatter::getPattern' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getLocale' => 
  array (
    0 => 'string',
    'type=' => 'int',
  ),
  'IntlDateFormatter::setLenient' => 
  array (
    0 => 'string',
    'lenient' => 'bool',
  ),
  'IntlDateFormatter::isLenient' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::format' => 
  array (
    0 => 'string',
    'datetime' => 'string',
  ),
  'IntlDateFormatter::formatObject' => 
  array (
    0 => 'string',
    'datetime' => 'string',
    'format=' => 'string',
    'locale=' => 'string|null',
  ),
  'IntlDateFormatter::parse' => 
  array (
    0 => 'string',
    'string' => 'string',
    '&offset=' => 'string',
  ),
  'IntlDateFormatter::localtime' => 
  array (
    0 => 'string',
    'string' => 'string',
    '&offset=' => 'string',
  ),
  'IntlDateFormatter::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlDateFormatter::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'ResourceBundle::__construct' => 
  array (
    0 => 'string',
    'locale' => 'string|null',
    'bundle' => 'string|null',
    'fallback=' => 'bool',
  ),
  'ResourceBundle::create' => 
  array (
    0 => 'string',
    'locale' => 'string|null',
    'bundle' => 'string|null',
    'fallback=' => 'bool',
  ),
  'ResourceBundle::get' => 
  array (
    0 => 'string',
    'index' => 'string',
    'fallback=' => 'bool',
  ),
  'ResourceBundle::count' => 
  array (
    0 => 'string',
  ),
  'ResourceBundle::getLocales' => 
  array (
    0 => 'string',
    'bundle' => 'string',
  ),
  'ResourceBundle::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'ResourceBundle::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'ResourceBundle::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'Transliterator::__construct' => 
  array (
    0 => 'string',
  ),
  'Transliterator::create' => 
  array (
    0 => 'string',
    'id' => 'string',
    'direction=' => 'int',
  ),
  'Transliterator::createFromRules' => 
  array (
    0 => 'string',
    'rules' => 'string',
    'direction=' => 'int',
  ),
  'Transliterator::createInverse' => 
  array (
    0 => 'string',
  ),
  'Transliterator::listIDs' => 
  array (
    0 => 'string',
  ),
  'Transliterator::transliterate' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'Transliterator::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'Transliterator::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::__construct' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::countEquivalentIDs' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
  ),
  'IntlTimeZone::createDefault' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::createEnumeration' => 
  array (
    0 => 'string',
    'countryOrRawOffset=' => 'string',
  ),
  'IntlTimeZone::createTimeZone' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
  ),
  'IntlTimeZone::createTimeZoneIDEnumeration' => 
  array (
    0 => 'string',
    'type' => 'int',
    'region=' => 'string|null',
    'rawOffset=' => 'int|null',
  ),
  'IntlTimeZone::fromDateTimeZone' => 
  array (
    0 => 'string',
    'timezone' => 'DateTimeZone',
  ),
  'IntlTimeZone::getCanonicalID' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
    '&isSystemId=' => 'string',
  ),
  'IntlTimeZone::getDisplayName' => 
  array (
    0 => 'string',
    'dst=' => 'bool',
    'style=' => 'int',
    'locale=' => 'string|null',
  ),
  'IntlTimeZone::getDSTSavings' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getEquivalentID' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
    'offset' => 'int',
  ),
  'IntlTimeZone::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getGMT' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getID' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getOffset' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
    'local' => 'bool',
    '&rawOffset' => 'string',
    '&dstOffset' => 'string',
  ),
  'IntlTimeZone::getRawOffset' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getRegion' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
  ),
  'IntlTimeZone::getTZDataVersion' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getUnknown' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::getWindowsID' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
  ),
  'IntlTimeZone::getIDForWindowsID' => 
  array (
    0 => 'string',
    'timezoneId' => 'string',
    'region=' => 'string|null',
  ),
  'IntlTimeZone::hasSameRules' => 
  array (
    0 => 'string',
    'other' => 'IntlTimeZone',
  ),
  'IntlTimeZone::toDateTimeZone' => 
  array (
    0 => 'string',
  ),
  'IntlTimeZone::useDaylightTime' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::__construct' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::createInstance' => 
  array (
    0 => 'string',
    'timezone=' => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCalendar::equals' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlCalendar::fieldDifference' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'IntlCalendar::add' => 
  array (
    0 => 'string',
    'field' => 'int',
    'value' => 'int',
  ),
  'IntlCalendar::after' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlCalendar::before' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlCalendar::clear' => 
  array (
    0 => 'string',
    'field=' => 'int|null',
  ),
  'IntlCalendar::fromDateTime' => 
  array (
    0 => 'string',
    'datetime' => 'DateTime|string',
    'locale=' => 'string|null',
  ),
  'IntlCalendar::get' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getActualMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getActualMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getAvailableLocales' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getDayOfWeekType' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlCalendar::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getFirstDayOfWeek' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getGreatestMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getKeywordValuesForLocale' => 
  array (
    0 => 'string',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'IntlCalendar::getLeastMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'IntlCalendar::getMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getMinimalDaysInFirstWeek' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::setMinimalDaysInFirstWeek' => 
  array (
    0 => 'string',
    'days' => 'int',
  ),
  'IntlCalendar::getMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::getNow' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getRepeatedWallTimeOption' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getSkippedWallTimeOption' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getTime' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getTimeZone' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getType' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::getWeekendTransition' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlCalendar::inDaylightTime' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::isEquivalentTo' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlCalendar::isLenient' => 
  array (
    0 => 'string',
  ),
  'IntlCalendar::isWeekend' => 
  array (
    0 => 'string',
    'timestamp=' => 'float|null',
  ),
  'IntlCalendar::roll' => 
  array (
    0 => 'string',
    'field' => 'int',
    'value' => 'string',
  ),
  'IntlCalendar::isSet' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlCalendar::set' => 
  array (
    0 => 'string',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth=' => 'int',
    'hour=' => 'int',
    'minute=' => 'int',
    'second=' => 'int',
  ),
  'IntlCalendar::setFirstDayOfWeek' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlCalendar::setLenient' => 
  array (
    0 => 'string',
    'lenient' => 'bool',
  ),
  'IntlCalendar::setRepeatedWallTimeOption' => 
  array (
    0 => 'string',
    'option' => 'int',
  ),
  'IntlCalendar::setSkippedWallTimeOption' => 
  array (
    0 => 'string',
    'option' => 'int',
  ),
  'IntlCalendar::setTime' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
  ),
  'IntlCalendar::setTimeZone' => 
  array (
    0 => 'string',
    'timezone' => 'string',
  ),
  'IntlCalendar::toDateTime' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::__construct' => 
  array (
    0 => 'string',
    'timezoneOrYear=' => 'string',
    'localeOrMonth=' => 'string',
    'day=' => 'string',
    'hour=' => 'string',
    'minute=' => 'string',
    'second=' => 'string',
  ),
  'IntlGregorianCalendar::setGregorianChange' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
  ),
  'IntlGregorianCalendar::getGregorianChange' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::isLeapYear' => 
  array (
    0 => 'string',
    'year' => 'int',
  ),
  'IntlGregorianCalendar::createInstance' => 
  array (
    0 => 'string',
    'timezone=' => 'string',
    'locale=' => 'string|null',
  ),
  'IntlGregorianCalendar::equals' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlGregorianCalendar::fieldDifference' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::add' => 
  array (
    0 => 'string',
    'field' => 'int',
    'value' => 'int',
  ),
  'IntlGregorianCalendar::after' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlGregorianCalendar::before' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlGregorianCalendar::clear' => 
  array (
    0 => 'string',
    'field=' => 'int|null',
  ),
  'IntlGregorianCalendar::fromDateTime' => 
  array (
    0 => 'string',
    'datetime' => 'DateTime|string',
    'locale=' => 'string|null',
  ),
  'IntlGregorianCalendar::get' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getActualMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getActualMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getAvailableLocales' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getDayOfWeekType' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlGregorianCalendar::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getFirstDayOfWeek' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getGreatestMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getKeywordValuesForLocale' => 
  array (
    0 => 'string',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'IntlGregorianCalendar::getLeastMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'IntlGregorianCalendar::getMaximum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getMinimalDaysInFirstWeek' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::setMinimalDaysInFirstWeek' => 
  array (
    0 => 'string',
    'days' => 'int',
  ),
  'IntlGregorianCalendar::getMinimum' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::getNow' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getRepeatedWallTimeOption' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getSkippedWallTimeOption' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getTime' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getTimeZone' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getType' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::getWeekendTransition' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlGregorianCalendar::inDaylightTime' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::isEquivalentTo' => 
  array (
    0 => 'string',
    'other' => 'IntlCalendar',
  ),
  'IntlGregorianCalendar::isLenient' => 
  array (
    0 => 'string',
  ),
  'IntlGregorianCalendar::isWeekend' => 
  array (
    0 => 'string',
    'timestamp=' => 'float|null',
  ),
  'IntlGregorianCalendar::roll' => 
  array (
    0 => 'string',
    'field' => 'int',
    'value' => 'string',
  ),
  'IntlGregorianCalendar::isSet' => 
  array (
    0 => 'string',
    'field' => 'int',
  ),
  'IntlGregorianCalendar::set' => 
  array (
    0 => 'string',
    'year' => 'int',
    'month' => 'int',
    'dayOfMonth=' => 'int',
    'hour=' => 'int',
    'minute=' => 'int',
    'second=' => 'int',
  ),
  'IntlGregorianCalendar::setFirstDayOfWeek' => 
  array (
    0 => 'string',
    'dayOfWeek' => 'int',
  ),
  'IntlGregorianCalendar::setLenient' => 
  array (
    0 => 'string',
    'lenient' => 'bool',
  ),
  'IntlGregorianCalendar::setRepeatedWallTimeOption' => 
  array (
    0 => 'string',
    'option' => 'int',
  ),
  'IntlGregorianCalendar::setSkippedWallTimeOption' => 
  array (
    0 => 'string',
    'option' => 'int',
  ),
  'IntlGregorianCalendar::setTime' => 
  array (
    0 => 'string',
    'timestamp' => 'float',
  ),
  'IntlGregorianCalendar::setTimeZone' => 
  array (
    0 => 'string',
    'timezone' => 'string',
  ),
  'IntlGregorianCalendar::toDateTime' => 
  array (
    0 => 'string',
  ),
  'Spoofchecker::__construct' => 
  array (
    0 => 'string',
  ),
  'Spoofchecker::isSuspicious' => 
  array (
    0 => 'string',
    'string' => 'string',
    '&errorCode=' => 'string',
  ),
  'Spoofchecker::areConfusable' => 
  array (
    0 => 'string',
    'string1' => 'string',
    'string2' => 'string',
    '&errorCode=' => 'string',
  ),
  'Spoofchecker::setAllowedLocales' => 
  array (
    0 => 'string',
    'locales' => 'string',
  ),
  'Spoofchecker::setChecks' => 
  array (
    0 => 'string',
    'checks' => 'int',
  ),
  'Spoofchecker::setRestrictionLevel' => 
  array (
    0 => 'string',
    'level' => 'int',
  ),
  'IntlException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'IntlException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'IntlException::getMessage' => 
  array (
    0 => 'string',
  ),
  'IntlException::getCode' => 
  array (
    0 => 'string',
  ),
  'IntlException::getFile' => 
  array (
    0 => 'string',
  ),
  'IntlException::getLine' => 
  array (
    0 => 'int',
  ),
  'IntlException::getTrace' => 
  array (
    0 => 'array',
  ),
  'IntlException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'IntlException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'IntlException::__toString' => 
  array (
    0 => 'string',
  ),
  'IntlIterator::current' => 
  array (
    0 => 'string',
  ),
  'IntlIterator::key' => 
  array (
    0 => 'string',
  ),
  'IntlIterator::next' => 
  array (
    0 => 'string',
  ),
  'IntlIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'IntlIterator::valid' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::createCharacterInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlBreakIterator::createCodePointInstance' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::createLineInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlBreakIterator::createSentenceInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlBreakIterator::createTitleInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlBreakIterator::createWordInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlBreakIterator::__construct' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::current' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::first' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::following' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlBreakIterator::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'IntlBreakIterator::getPartsIterator' => 
  array (
    0 => 'string',
    'type=' => 'string',
  ),
  'IntlBreakIterator::getText' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::isBoundary' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlBreakIterator::last' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::next' => 
  array (
    0 => 'string',
    'offset=' => 'int|null',
  ),
  'IntlBreakIterator::preceding' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlBreakIterator::previous' => 
  array (
    0 => 'string',
  ),
  'IntlBreakIterator::setText' => 
  array (
    0 => 'string',
    'text' => 'string',
  ),
  'IntlBreakIterator::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'IntlRuleBasedBreakIterator::__construct' => 
  array (
    0 => 'string',
    'rules' => 'string',
    'compiled=' => 'bool',
  ),
  'IntlRuleBasedBreakIterator::getBinaryRules' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::getRules' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::getRuleStatus' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::getRuleStatusVec' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::createCharacterInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlRuleBasedBreakIterator::createCodePointInstance' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::createLineInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlRuleBasedBreakIterator::createSentenceInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlRuleBasedBreakIterator::createTitleInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlRuleBasedBreakIterator::createWordInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlRuleBasedBreakIterator::current' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::first' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::following' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlRuleBasedBreakIterator::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'IntlRuleBasedBreakIterator::getPartsIterator' => 
  array (
    0 => 'string',
    'type=' => 'string',
  ),
  'IntlRuleBasedBreakIterator::getText' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::isBoundary' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlRuleBasedBreakIterator::last' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::next' => 
  array (
    0 => 'string',
    'offset=' => 'int|null',
  ),
  'IntlRuleBasedBreakIterator::preceding' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlRuleBasedBreakIterator::previous' => 
  array (
    0 => 'string',
  ),
  'IntlRuleBasedBreakIterator::setText' => 
  array (
    0 => 'string',
    'text' => 'string',
  ),
  'IntlRuleBasedBreakIterator::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'IntlCodePointBreakIterator::getLastCodePoint' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::createCharacterInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCodePointBreakIterator::createCodePointInstance' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::createLineInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCodePointBreakIterator::createSentenceInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCodePointBreakIterator::createTitleInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCodePointBreakIterator::createWordInstance' => 
  array (
    0 => 'string',
    'locale=' => 'string|null',
  ),
  'IntlCodePointBreakIterator::current' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::first' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::following' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlCodePointBreakIterator::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::getLocale' => 
  array (
    0 => 'string',
    'type' => 'int',
  ),
  'IntlCodePointBreakIterator::getPartsIterator' => 
  array (
    0 => 'string',
    'type=' => 'string',
  ),
  'IntlCodePointBreakIterator::getText' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::isBoundary' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlCodePointBreakIterator::last' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::next' => 
  array (
    0 => 'string',
    'offset=' => 'int|null',
  ),
  'IntlCodePointBreakIterator::preceding' => 
  array (
    0 => 'string',
    'offset' => 'int',
  ),
  'IntlCodePointBreakIterator::previous' => 
  array (
    0 => 'string',
  ),
  'IntlCodePointBreakIterator::setText' => 
  array (
    0 => 'string',
    'text' => 'string',
  ),
  'IntlCodePointBreakIterator::getIterator' => 
  array (
    0 => 'Iterator',
  ),
  'IntlPartsIterator::getBreakIterator' => 
  array (
    0 => 'string',
  ),
  'IntlPartsIterator::current' => 
  array (
    0 => 'string',
  ),
  'IntlPartsIterator::key' => 
  array (
    0 => 'string',
  ),
  'IntlPartsIterator::next' => 
  array (
    0 => 'string',
  ),
  'IntlPartsIterator::rewind' => 
  array (
    0 => 'string',
  ),
  'IntlPartsIterator::valid' => 
  array (
    0 => 'string',
  ),
  'UConverter::__construct' => 
  array (
    0 => 'string',
    'destination_encoding=' => 'string|null',
    'source_encoding=' => 'string|null',
  ),
  'UConverter::convert' => 
  array (
    0 => 'string',
    'str' => 'string',
    'reverse=' => 'bool',
  ),
  'UConverter::fromUCallback' => 
  array (
    0 => 'string',
    'reason' => 'int',
    'source' => 'array',
    'codePoint' => 'int',
    '&error' => 'string',
  ),
  'UConverter::getAliases' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'UConverter::getAvailable' => 
  array (
    0 => 'string',
  ),
  'UConverter::getDestinationEncoding' => 
  array (
    0 => 'string',
  ),
  'UConverter::getDestinationType' => 
  array (
    0 => 'string',
  ),
  'UConverter::getErrorCode' => 
  array (
    0 => 'string',
  ),
  'UConverter::getErrorMessage' => 
  array (
    0 => 'string',
  ),
  'UConverter::getSourceEncoding' => 
  array (
    0 => 'string',
  ),
  'UConverter::getSourceType' => 
  array (
    0 => 'string',
  ),
  'UConverter::getStandards' => 
  array (
    0 => 'string',
  ),
  'UConverter::getSubstChars' => 
  array (
    0 => 'string',
  ),
  'UConverter::reasonText' => 
  array (
    0 => 'string',
    'reason' => 'int',
  ),
  'UConverter::setDestinationEncoding' => 
  array (
    0 => 'string',
    'encoding' => 'string',
  ),
  'UConverter::setSourceEncoding' => 
  array (
    0 => 'string',
    'encoding' => 'string',
  ),
  'UConverter::setSubstChars' => 
  array (
    0 => 'string',
    'chars' => 'string',
  ),
  'UConverter::toUCallback' => 
  array (
    0 => 'string',
    'reason' => 'int',
    'source' => 'string',
    'codeUnits' => 'string',
    '&error' => 'string',
  ),
  'UConverter::transcode' => 
  array (
    0 => 'string',
    'str' => 'string',
    'toEncoding' => 'string',
    'fromEncoding' => 'string',
    'options=' => 'array|null',
  ),
  'IntlChar::hasBinaryProperty' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
    'property' => 'int',
  ),
  'IntlChar::charAge' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::charDigitValue' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::charDirection' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::charFromName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'type=' => 'int',
  ),
  'IntlChar::charMirror' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::charName' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
    'type=' => 'int',
  ),
  'IntlChar::charType' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::chr' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::digit' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
    'base=' => 'int',
  ),
  'IntlChar::enumCharNames' => 
  array (
    0 => 'string',
    'start' => 'string|int',
    'end' => 'string|int',
    'callback' => 'callable',
    'type=' => 'int',
  ),
  'IntlChar::enumCharTypes' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'IntlChar::foldCase' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
    'options=' => 'int',
  ),
  'IntlChar::forDigit' => 
  array (
    0 => 'string',
    'digit' => 'int',
    'base=' => 'int',
  ),
  'IntlChar::getBidiPairedBracket' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::getBlockCode' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::getCombiningClass' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::getFC_NFKC_Closure' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::getIntPropertyMaxValue' => 
  array (
    0 => 'string',
    'property' => 'int',
  ),
  'IntlChar::getIntPropertyMinValue' => 
  array (
    0 => 'string',
    'property' => 'int',
  ),
  'IntlChar::getIntPropertyValue' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
    'property' => 'int',
  ),
  'IntlChar::getNumericValue' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::getPropertyEnum' => 
  array (
    0 => 'string',
    'alias' => 'string',
  ),
  'IntlChar::getPropertyName' => 
  array (
    0 => 'string',
    'property' => 'int',
    'type=' => 'int',
  ),
  'IntlChar::getPropertyValueEnum' => 
  array (
    0 => 'string',
    'property' => 'int',
    'name' => 'string',
  ),
  'IntlChar::getPropertyValueName' => 
  array (
    0 => 'string',
    'property' => 'int',
    'value' => 'int',
    'type=' => 'int',
  ),
  'IntlChar::getUnicodeVersion' => 
  array (
    0 => 'string',
  ),
  'IntlChar::isalnum' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isalpha' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isbase' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isblank' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::iscntrl' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isdefined' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isdigit' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isgraph' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isIDIgnorable' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isIDPart' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isIDStart' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isISOControl' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isJavaIDPart' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isJavaIDStart' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isJavaSpaceChar' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::islower' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isMirrored' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isprint' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::ispunct' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isspace' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::istitle' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isUAlphabetic' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isULowercase' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isupper' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isUUppercase' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isUWhiteSpace' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isWhitespace' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::isxdigit' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::ord' => 
  array (
    0 => 'string',
    'character' => 'string|int',
  ),
  'IntlChar::tolower' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::totitle' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'IntlChar::toupper' => 
  array (
    0 => 'string',
    'codepoint' => 'string|int',
  ),
  'MongoDB\\BSON\\Iterator::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Iterator::current' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Iterator::key' => 
  array (
    0 => 'string|int',
  ),
  'MongoDB\\BSON\\Iterator::next' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\BSON\\Iterator::rewind' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\BSON\\Iterator::valid' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\BSON\\PackedArray::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::fromJSON' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'json' => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::fromPHP' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'value' => 'array',
  ),
  'MongoDB\\BSON\\PackedArray::get' => 
  array (
    0 => 'mixed|null',
    'index' => 'int',
  ),
  'MongoDB\\BSON\\PackedArray::getIterator' => 
  array (
    0 => 'MongoDB\\BSON\\Iterator',
  ),
  'MongoDB\\BSON\\PackedArray::has' => 
  array (
    0 => 'bool',
    'index' => 'int',
  ),
  'MongoDB\\BSON\\PackedArray::toPHP' => 
  array (
    0 => 'object|array',
    'typeMap=' => 'array|null',
  ),
  'MongoDB\\BSON\\PackedArray::toCanonicalExtendedJSON' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::toRelaxedExtendedJSON' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\PackedArray::offsetGet' => 
  array (
    0 => 'mixed|null',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\PackedArray::offsetSet' => 
  array (
    0 => 'void',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'MongoDB\\BSON\\PackedArray::offsetUnset' => 
  array (
    0 => 'void',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\PackedArray::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\PackedArray::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\PackedArray::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\PackedArray::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Document::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Document::fromBSON' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'bson' => 'string',
  ),
  'MongoDB\\BSON\\Document::fromJSON' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'json' => 'string',
  ),
  'MongoDB\\BSON\\Document::fromPHP' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'value' => 'object|array',
  ),
  'MongoDB\\BSON\\Document::get' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
  ),
  'MongoDB\\BSON\\Document::getIterator' => 
  array (
    0 => 'MongoDB\\BSON\\Iterator',
  ),
  'MongoDB\\BSON\\Document::has' => 
  array (
    0 => 'bool',
    'key' => 'string',
  ),
  'MongoDB\\BSON\\Document::toPHP' => 
  array (
    0 => 'object|array',
    'typeMap=' => 'array|null',
  ),
  'MongoDB\\BSON\\Document::toCanonicalExtendedJSON' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Document::toRelaxedExtendedJSON' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Document::offsetExists' => 
  array (
    0 => 'bool',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\Document::offsetGet' => 
  array (
    0 => 'mixed|null',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\Document::offsetSet' => 
  array (
    0 => 'void',
    'offset' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'MongoDB\\BSON\\Document::offsetUnset' => 
  array (
    0 => 'void',
    'offset' => 'mixed|null',
  ),
  'MongoDB\\BSON\\Document::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Document::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Document::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Document::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Document::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Document::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Binary::__construct' => 
  array (
    0 => 'string',
    'data' => 'string',
    'type=' => 'int',
  ),
  'MongoDB\\BSON\\Binary::getData' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Binary::getType' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\BSON\\Binary::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Binary::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Binary::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Binary::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Binary::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Binary::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Binary::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\DBPointer::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\DBPointer::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\DBPointer',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\DBPointer::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\DBPointer::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\DBPointer::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\DBPointer::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\DBPointer::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\DBPointer::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Decimal128::__construct' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'MongoDB\\BSON\\Decimal128::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Decimal128::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Decimal128',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Decimal128::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Decimal128::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Decimal128::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Decimal128::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Decimal128::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Int64::__construct' => 
  array (
    0 => 'string',
    'value' => 'string|int',
  ),
  'MongoDB\\BSON\\Int64::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Int64::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Int64',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Int64::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Int64::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Int64::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Int64::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Int64::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Javascript::__construct' => 
  array (
    0 => 'string',
    'code' => 'string',
    'scope=' => 'object|array|null|null',
  ),
  'MongoDB\\BSON\\Javascript::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Javascript',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Javascript::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Javascript::getScope' => 
  array (
    0 => 'object|null',
  ),
  'MongoDB\\BSON\\Javascript::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Javascript::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Javascript::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Javascript::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Javascript::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Javascript::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\MaxKey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MaxKey',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\MaxKey::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\MaxKey::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\MaxKey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\MaxKey::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\MaxKey::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\MinKey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MinKey',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\MinKey::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\MinKey::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\MinKey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\MinKey::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\MinKey::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\ObjectId::__construct' => 
  array (
    0 => 'string',
    'id=' => 'string|null',
  ),
  'MongoDB\\BSON\\ObjectId::getTimestamp' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\BSON\\ObjectId::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\ObjectId::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\ObjectId::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\ObjectId::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\ObjectId::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\ObjectId::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\ObjectId::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Regex::__construct' => 
  array (
    0 => 'string',
    'pattern' => 'string',
    'flags=' => 'string',
  ),
  'MongoDB\\BSON\\Regex::getPattern' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Regex::getFlags' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Regex::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Regex::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Regex',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Regex::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Regex::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Regex::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Regex::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Regex::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Symbol::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Symbol::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Symbol::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Symbol',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Symbol::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Symbol::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Symbol::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Symbol::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Symbol::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Timestamp::__construct' => 
  array (
    0 => 'string',
    'increment' => 'string|int',
    'timestamp' => 'string|int',
  ),
  'MongoDB\\BSON\\Timestamp::getTimestamp' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\BSON\\Timestamp::getIncrement' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\BSON\\Timestamp::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Timestamp::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Timestamp',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Timestamp::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Timestamp::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Timestamp::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Timestamp::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Timestamp::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\Undefined::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Undefined::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Undefined::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Undefined',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\Undefined::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\Undefined::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\Undefined::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\Undefined::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\Undefined::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\BSON\\UTCDateTime::__construct' => 
  array (
    0 => 'string',
    'milliseconds=' => 'DateTimeInterface|MongoDB\\BSON\\Int64|string|int|float|null|null',
  ),
  'MongoDB\\BSON\\UTCDateTime::toDateTime' => 
  array (
    0 => 'DateTime',
  ),
  'MongoDB\\BSON\\UTCDateTime::toDateTimeImmutable' => 
  array (
    0 => 'DateTimeImmutable',
  ),
  'MongoDB\\BSON\\UTCDateTime::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\UTCDateTime::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\UTCDateTime',
    'properties' => 'array',
  ),
  'MongoDB\\BSON\\UTCDateTime::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\BSON\\UTCDateTime::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\BSON\\UTCDateTime::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\BSON\\UTCDateTime::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\BSON\\UTCDateTime::jsonSerialize' => 
  array (
    0 => 'mixed|null',
  ),
  'MongoDB\\Driver\\BulkWrite::__construct' => 
  array (
    0 => 'string',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\BulkWrite::count' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\BulkWrite::delete' => 
  array (
    0 => 'void',
    'filter' => 'object|array',
    'deleteOptions=' => 'array|null',
  ),
  'MongoDB\\Driver\\BulkWrite::insert' => 
  array (
    0 => 'mixed|null',
    'document' => 'object|array',
  ),
  'MongoDB\\Driver\\BulkWrite::update' => 
  array (
    0 => 'void',
    'filter' => 'object|array',
    'newObj' => 'object|array',
    'updateOptions=' => 'array|null',
  ),
  'MongoDB\\Driver\\ClientEncryption::__construct' => 
  array (
    0 => 'string',
    'options' => 'array',
  ),
  'MongoDB\\Driver\\ClientEncryption::addKeyAltName' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'MongoDB\\Driver\\ClientEncryption::createDataKey' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'kmsProvider' => 'string',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\ClientEncryption::decrypt' => 
  array (
    0 => 'mixed|null',
    'value' => 'MongoDB\\BSON\\Binary',
  ),
  'MongoDB\\Driver\\ClientEncryption::deleteKey' => 
  array (
    0 => 'object',
    'keyId' => 'MongoDB\\BSON\\Binary',
  ),
  'MongoDB\\Driver\\ClientEncryption::encrypt' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'value' => 'mixed|null',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\ClientEncryption::encryptExpression' => 
  array (
    0 => 'object',
    'expr' => 'object|array',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\ClientEncryption::getKey' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
  ),
  'MongoDB\\Driver\\ClientEncryption::getKeyByAltName' => 
  array (
    0 => 'object|null',
    'keyAltName' => 'string',
  ),
  'MongoDB\\Driver\\ClientEncryption::getKeys' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
  ),
  'MongoDB\\Driver\\ClientEncryption::removeKeyAltName' => 
  array (
    0 => 'object|null',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'MongoDB\\Driver\\ClientEncryption::rewrapManyDataKey' => 
  array (
    0 => 'object',
    'filter' => 'object|array',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Command::__construct' => 
  array (
    0 => 'string',
    'document' => 'object|array',
    'commandOptions=' => 'array|null',
  ),
  'MongoDB\\Driver\\Cursor::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Cursor::current' => 
  array (
    0 => 'object|array|null|null',
  ),
  'MongoDB\\Driver\\Cursor::getId' => 
  array (
    0 => 'string',
    'asInt64=' => 'bool',
  ),
  'MongoDB\\Driver\\Cursor::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'MongoDB\\Driver\\Cursor::isDead' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Cursor::key' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\Cursor::next' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\Driver\\Cursor::rewind' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\Driver\\Cursor::setTypeMap' => 
  array (
    0 => 'void',
    'typemap' => 'array',
  ),
  'MongoDB\\Driver\\Cursor::toArray' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Cursor::valid' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\CursorId::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\CursorId::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\CursorId',
    'properties' => 'array',
  ),
  'MongoDB\\Driver\\CursorId::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\CursorId::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\CursorId::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\Driver\\CursorId::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\Driver\\CursorId::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Manager::__construct' => 
  array (
    0 => 'string',
    'uri=' => 'string|null',
    'uriOptions=' => 'array|null',
    'driverOptions=' => 'array|null',
  ),
  'MongoDB\\Driver\\Manager::addSubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'MongoDB\\Driver\\Manager::createClientEncryption' => 
  array (
    0 => 'MongoDB\\Driver\\ClientEncryption',
    'options' => 'array',
  ),
  'MongoDB\\Driver\\Manager::executeBulkWrite' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
    'namespace' => 'string',
    'bulk' => 'MongoDB\\Driver\\BulkWrite',
    'options=' => 'MongoDB\\Driver\\WriteConcern|array|null|null',
  ),
  'MongoDB\\Driver\\Manager::executeCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'MongoDB\\Driver\\ReadPreference|array|null|null',
  ),
  'MongoDB\\Driver\\Manager::executeQuery' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'MongoDB\\Driver\\ReadPreference|array|null|null',
  ),
  'MongoDB\\Driver\\Manager::executeReadCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Manager::executeReadWriteCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Manager::executeWriteCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Manager::getEncryptedFieldsMap' => 
  array (
    0 => 'object|array|null|null',
  ),
  'MongoDB\\Driver\\Manager::getReadConcern' => 
  array (
    0 => 'MongoDB\\Driver\\ReadConcern',
  ),
  'MongoDB\\Driver\\Manager::getReadPreference' => 
  array (
    0 => 'MongoDB\\Driver\\ReadPreference',
  ),
  'MongoDB\\Driver\\Manager::getServers' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Manager::getWriteConcern' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcern',
  ),
  'MongoDB\\Driver\\Manager::removeSubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'MongoDB\\Driver\\Manager::selectServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
    'readPreference=' => 'MongoDB\\Driver\\ReadPreference|null',
  ),
  'MongoDB\\Driver\\Manager::startSession' => 
  array (
    0 => 'MongoDB\\Driver\\Session',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Query::__construct' => 
  array (
    0 => 'string',
    'filter' => 'object|array',
    'queryOptions=' => 'array|null',
  ),
  'MongoDB\\Driver\\ReadConcern::__construct' => 
  array (
    0 => 'string',
    'level=' => 'string|null',
  ),
  'MongoDB\\Driver\\ReadConcern::getLevel' => 
  array (
    0 => 'string|null',
  ),
  'MongoDB\\Driver\\ReadConcern::isDefault' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\ReadConcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadConcern',
    'properties' => 'array',
  ),
  'MongoDB\\Driver\\ReadConcern::bsonSerialize' => 
  array (
    0 => 'stdClass',
  ),
  'MongoDB\\Driver\\ReadConcern::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ReadConcern::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\Driver\\ReadConcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\Driver\\ReadConcern::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\ReadPreference::__construct' => 
  array (
    0 => 'string',
    'mode' => 'string|int',
    'tagSets=' => 'array|null',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\ReadPreference::getHedge' => 
  array (
    0 => 'object|null',
  ),
  'MongoDB\\Driver\\ReadPreference::getMaxStalenessSeconds' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\ReadPreference::getMode' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\ReadPreference::getModeString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ReadPreference::getTagSets' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\ReadPreference::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadPreference',
    'properties' => 'array',
  ),
  'MongoDB\\Driver\\ReadPreference::bsonSerialize' => 
  array (
    0 => 'stdClass',
  ),
  'MongoDB\\Driver\\ReadPreference::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ReadPreference::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\Driver\\ReadPreference::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\Driver\\ReadPreference::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Server::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Server::executeBulkWrite' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
    'namespace' => 'string',
    'bulkWrite' => 'MongoDB\\Driver\\BulkWrite',
    'options=' => 'MongoDB\\Driver\\WriteConcern|array|null|null',
  ),
  'MongoDB\\Driver\\Server::executeCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'MongoDB\\Driver\\ReadPreference|array|null|null',
  ),
  'MongoDB\\Driver\\Server::executeQuery' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'MongoDB\\Driver\\ReadPreference|array|null|null',
  ),
  'MongoDB\\Driver\\Server::executeReadCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Server::executeReadWriteCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Server::executeWriteCommand' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\Server::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Server::getInfo' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Server::getLatency' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\Server::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Server::getServerDescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'MongoDB\\Driver\\Server::getTags' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Server::getType' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Server::isArbiter' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Server::isHidden' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Server::isPassive' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Server::isPrimary' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Server::isSecondary' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\ServerApi::__construct' => 
  array (
    0 => 'string',
    'version' => 'string',
    'strict=' => 'bool|null',
    'deprecationErrors=' => 'bool|null',
  ),
  'MongoDB\\Driver\\ServerApi::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ServerApi',
    'properties' => 'array',
  ),
  'MongoDB\\Driver\\ServerApi::bsonSerialize' => 
  array (
    0 => 'stdClass',
  ),
  'MongoDB\\Driver\\ServerApi::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ServerApi::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\Driver\\ServerApi::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\Driver\\ServerApi::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\ServerDescription::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ServerDescription::getHelloResponse' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\ServerDescription::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\ServerDescription::getLastUpdateTime' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\ServerDescription::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\ServerDescription::getRoundTripTime' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\ServerDescription::getType' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\TopologyDescription::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\TopologyDescription::getServers' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\TopologyDescription::getType' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\TopologyDescription::hasReadableServer' => 
  array (
    0 => 'bool',
    'readPreference=' => 'MongoDB\\Driver\\ReadPreference|null',
  ),
  'MongoDB\\Driver\\TopologyDescription::hasWritableServer' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Session::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Session::abortTransaction' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\Driver\\Session::advanceClusterTime' => 
  array (
    0 => 'void',
    'clusterTime' => 'object|array',
  ),
  'MongoDB\\Driver\\Session::advanceOperationTime' => 
  array (
    0 => 'void',
    'operationTime' => 'MongoDB\\BSON\\TimestampInterface',
  ),
  'MongoDB\\Driver\\Session::commitTransaction' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\Driver\\Session::endSession' => 
  array (
    0 => 'void',
  ),
  'MongoDB\\Driver\\Session::getClusterTime' => 
  array (
    0 => 'object|null',
  ),
  'MongoDB\\Driver\\Session::getLogicalSessionId' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Session::getOperationTime' => 
  array (
    0 => 'MongoDB\\BSON\\Timestamp|null',
  ),
  'MongoDB\\Driver\\Session::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server|null',
  ),
  'MongoDB\\Driver\\Session::getTransactionOptions' => 
  array (
    0 => 'array|null',
  ),
  'MongoDB\\Driver\\Session::getTransactionState' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Session::isDirty' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Session::isInTransaction' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Session::startTransaction' => 
  array (
    0 => 'void',
    'options=' => 'array|null',
  ),
  'MongoDB\\Driver\\WriteConcern::__construct' => 
  array (
    0 => 'string',
    'w' => 'string|int',
    'wtimeout=' => 'int|null',
    'journal=' => 'bool|null',
  ),
  'MongoDB\\Driver\\WriteConcern::getJournal' => 
  array (
    0 => 'bool|null',
  ),
  'MongoDB\\Driver\\WriteConcern::getW' => 
  array (
    0 => 'string|int|null|null',
  ),
  'MongoDB\\Driver\\WriteConcern::getWtimeout' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\WriteConcern::isDefault' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\WriteConcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcern',
    'properties' => 'array',
  ),
  'MongoDB\\Driver\\WriteConcern::bsonSerialize' => 
  array (
    0 => 'stdClass',
  ),
  'MongoDB\\Driver\\WriteConcern::serialize' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteConcern::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'MongoDB\\Driver\\WriteConcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array',
  ),
  'MongoDB\\Driver\\WriteConcern::__serialize' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\WriteConcernError::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteConcernError::getCode' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\WriteConcernError::getInfo' => 
  array (
    0 => 'object|null',
  ),
  'MongoDB\\Driver\\WriteConcernError::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteError::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteError::getCode' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\WriteError::getIndex' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\WriteError::getInfo' => 
  array (
    0 => 'object|null',
  ),
  'MongoDB\\Driver\\WriteError::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteResult::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\WriteResult::getInsertedCount' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\WriteResult::getMatchedCount' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\WriteResult::getModifiedCount' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\WriteResult::getDeletedCount' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\WriteResult::getUpsertedCount' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\WriteResult::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'MongoDB\\Driver\\WriteResult::getUpsertedIds' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\WriteResult::getWriteConcernError' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcernError|null',
  ),
  'MongoDB\\Driver\\WriteResult::getWriteErrors' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\WriteResult::getErrorReplies' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\WriteResult::isAcknowledged' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\RuntimeException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ServerException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getWriteResult' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\WriteException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\AuthenticationException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getWriteResult' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\BulkWriteException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getResultDocument' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\CommandException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ConnectionTimeoutException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\EncryptionException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\ExecutionTimeoutException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\InvalidArgumentException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\LogicException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::hasErrorLabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\SSLConnectionException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getMessage' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getCode' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getFile' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getLine' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getTrace' => 
  array (
    0 => 'array',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Exception\\UnexpectedValueException::__toString' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getCommandName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getDatabaseName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getDurationMicros' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getError' => 
  array (
    0 => 'Exception',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getOperationId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getReply' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getRequestId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getServiceId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandFailedEvent::getServerConnectionId' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getCommand' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getCommandName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getDatabaseName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getOperationId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getRequestId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getServiceId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandStartedEvent::getServerConnectionId' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getCommandName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getDatabaseName' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getDurationMicros' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getOperationId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getReply' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getRequestId' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getServer' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getServiceId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
  ),
  'MongoDB\\Driver\\Monitoring\\CommandSucceededEvent::getServerConnectionId' => 
  array (
    0 => 'int|null',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::getNewDescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::getPreviousDescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerChangedEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerClosedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerClosedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerClosedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerClosedEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::getDurationMicros' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::getError' => 
  array (
    0 => 'Exception',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatFailedEvent::isAwaited' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatStartedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatStartedEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatStartedEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatStartedEvent::isAwaited' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::getDurationMicros' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::getReply' => 
  array (
    0 => 'object',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerHeartbeatSucceededEvent::isAwaited' => 
  array (
    0 => 'bool',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerOpeningEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerOpeningEvent::getPort' => 
  array (
    0 => 'int',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerOpeningEvent::getHost' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\ServerOpeningEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyChangedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyChangedEvent::getNewDescription' => 
  array (
    0 => 'MongoDB\\Driver\\TopologyDescription',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyChangedEvent::getPreviousDescription' => 
  array (
    0 => 'MongoDB\\Driver\\TopologyDescription',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyChangedEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyClosedEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyClosedEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyOpeningEvent::__construct' => 
  array (
    0 => 'string',
  ),
  'MongoDB\\Driver\\Monitoring\\TopologyOpeningEvent::getTopologyId' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
  ),
  'Redis::__construct' => 
  array (
    0 => 'string',
    'options=' => 'array|null',
  ),
  'Redis::__destruct' => 
  array (
    0 => 'string',
  ),
  'Redis::_compress' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'Redis::_uncompress' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'Redis::_prefix' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'Redis::_serialize' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::_unserialize' => 
  array (
    0 => 'mixed|null',
    'value' => 'string',
  ),
  'Redis::_pack' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::_unpack' => 
  array (
    0 => 'mixed|null',
    'value' => 'string',
  ),
  'Redis::acl' => 
  array (
    0 => 'mixed|null',
    'subcmd' => 'string',
    '...args=' => 'string',
  ),
  'Redis::append' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::auth' => 
  array (
    0 => 'Redis|bool',
    'credentials' => 'mixed|null',
  ),
  'Redis::bgSave' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::bgrewriteaof' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::waitaof' => 
  array (
    0 => 'Redis|array|false',
    'numlocal' => 'int',
    'numreplicas' => 'int',
    'timeout' => 'int',
  ),
  'Redis::bitcount' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'start=' => 'int',
    'end=' => 'int',
    'bybit=' => 'bool',
  ),
  'Redis::bitop' => 
  array (
    0 => 'Redis|int|false',
    'operation' => 'string',
    'deskey' => 'string',
    'srckey' => 'string',
    '...other_keys=' => 'string',
  ),
  'Redis::bitpos' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'bit' => 'bool',
    'start=' => 'int',
    'end=' => 'int',
    'bybit=' => 'bool',
  ),
  'Redis::blPop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'key_or_keys' => 'array|string',
    'timeout_or_key' => 'string|int|float',
    '...extra_args=' => 'mixed|null',
  ),
  'Redis::brPop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'key_or_keys' => 'array|string',
    'timeout_or_key' => 'string|int|float',
    '...extra_args=' => 'mixed|null',
  ),
  'Redis::brpoplpush' => 
  array (
    0 => 'Redis|string|false',
    'src' => 'string',
    'dst' => 'string',
    'timeout' => 'int|float',
  ),
  'Redis::bzPopMax' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int',
    '...extra_args=' => 'mixed|null',
  ),
  'Redis::bzPopMin' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int',
    '...extra_args=' => 'mixed|null',
  ),
  'Redis::bzmpop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'timeout' => 'float',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'Redis::zmpop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'Redis::blmpop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'timeout' => 'float',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'Redis::lmpop' => 
  array (
    0 => 'Redis|array|false|null|null',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'Redis::clearLastError' => 
  array (
    0 => 'bool',
  ),
  'Redis::client' => 
  array (
    0 => 'mixed|null',
    'opt' => 'string',
    '...args=' => 'mixed|null',
  ),
  'Redis::close' => 
  array (
    0 => 'bool',
  ),
  'Redis::command' => 
  array (
    0 => 'mixed|null',
    'opt=' => 'string|null',
    '...args=' => 'mixed|null',
  ),
  'Redis::config' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'key_or_settings=' => 'array|string|null|null',
    'value=' => 'string|null',
  ),
  'Redis::connect' => 
  array (
    0 => 'bool',
    'host' => 'string',
    'port=' => 'int',
    'timeout=' => 'float',
    'persistent_id=' => 'string|null',
    'retry_interval=' => 'int',
    'read_timeout=' => 'float',
    'context=' => 'array|null',
  ),
  'Redis::copy' => 
  array (
    0 => 'Redis|bool',
    'src' => 'string',
    'dst' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::dbSize' => 
  array (
    0 => 'Redis|int|false',
  ),
  'Redis::debug' => 
  array (
    0 => 'Redis|string',
    'key' => 'string',
  ),
  'Redis::decr' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'by=' => 'int',
  ),
  'Redis::decrBy' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'int',
  ),
  'Redis::del' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::delete' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::discard' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::dump' => 
  array (
    0 => 'Redis|string|false',
    'key' => 'string',
  ),
  'Redis::echo' => 
  array (
    0 => 'Redis|string|false',
    'str' => 'string',
  ),
  'Redis::eval' => 
  array (
    0 => 'mixed|null',
    'script' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'Redis::eval_ro' => 
  array (
    0 => 'mixed|null',
    'script_sha' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'Redis::evalsha' => 
  array (
    0 => 'mixed|null',
    'sha1' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'Redis::evalsha_ro' => 
  array (
    0 => 'mixed|null',
    'sha1' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'Redis::exec' => 
  array (
    0 => 'Redis|array|false',
  ),
  'Redis::exists' => 
  array (
    0 => 'Redis|int|bool',
    'key' => 'mixed|null',
    '...other_keys=' => 'mixed|null',
  ),
  'Redis::expire' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'timeout' => 'int',
    'mode=' => 'string|null',
  ),
  'Redis::expireAt' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'timestamp' => 'int',
    'mode=' => 'string|null',
  ),
  'Redis::failover' => 
  array (
    0 => 'Redis|bool',
    'to=' => 'array|null',
    'abort=' => 'bool',
    'timeout=' => 'int',
  ),
  'Redis::expiretime' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::pexpiretime' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::fcall' => 
  array (
    0 => 'mixed|null',
    'fn' => 'string',
    'keys=' => 'array',
    'args=' => 'array',
  ),
  'Redis::fcall_ro' => 
  array (
    0 => 'mixed|null',
    'fn' => 'string',
    'keys=' => 'array',
    'args=' => 'array',
  ),
  'Redis::flushAll' => 
  array (
    0 => 'Redis|bool',
    'sync=' => 'bool|null',
  ),
  'Redis::flushDB' => 
  array (
    0 => 'Redis|bool',
    'sync=' => 'bool|null',
  ),
  'Redis::function' => 
  array (
    0 => 'Redis|array|string|bool',
    'operation' => 'string',
    '...args=' => 'mixed|null',
  ),
  'Redis::geoadd' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'member' => 'string',
    '...other_triples_and_options=' => 'mixed|null',
  ),
  'Redis::geodist' => 
  array (
    0 => 'Redis|float|false',
    'key' => 'string',
    'src' => 'string',
    'dst' => 'string',
    'unit=' => 'string|null',
  ),
  'Redis::geohash' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'Redis::geopos' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'Redis::georadius' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::georadius_ro' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::georadiusbymember' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::georadiusbymember_ro' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::geosearch' => 
  array (
    0 => 'array',
    'key' => 'string',
    'position' => 'array|string',
    'shape' => 'array|int|float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::geosearchstore' => 
  array (
    0 => 'Redis|array|int|false',
    'dst' => 'string',
    'src' => 'string',
    'position' => 'array|string',
    'shape' => 'array|int|float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'Redis::get' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
  ),
  'Redis::getAuth' => 
  array (
    0 => 'mixed|null',
  ),
  'Redis::getBit' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'idx' => 'int',
  ),
  'Redis::getEx' => 
  array (
    0 => 'Redis|string|bool',
    'key' => 'string',
    'options=' => 'array',
  ),
  'Redis::getDBNum' => 
  array (
    0 => 'int',
  ),
  'Redis::getDel' => 
  array (
    0 => 'Redis|string|bool',
    'key' => 'string',
  ),
  'Redis::getHost' => 
  array (
    0 => 'string',
  ),
  'Redis::getLastError' => 
  array (
    0 => 'string|null',
  ),
  'Redis::getMode' => 
  array (
    0 => 'int',
  ),
  'Redis::getOption' => 
  array (
    0 => 'mixed|null',
    'option' => 'int',
  ),
  'Redis::getPersistentID' => 
  array (
    0 => 'string|null',
  ),
  'Redis::getPort' => 
  array (
    0 => 'int',
  ),
  'Redis::getRange' => 
  array (
    0 => 'Redis|string|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'Redis::lcs' => 
  array (
    0 => 'Redis|array|string|int|false',
    'key1' => 'string',
    'key2' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::getReadTimeout' => 
  array (
    0 => 'float',
  ),
  'Redis::getset' => 
  array (
    0 => 'Redis|string|false',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::getTimeout' => 
  array (
    0 => 'float|false',
  ),
  'Redis::getTransferredBytes' => 
  array (
    0 => 'array',
  ),
  'Redis::clearTransferredBytes' => 
  array (
    0 => 'void',
  ),
  'Redis::hDel' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'field' => 'string',
    '...other_fields=' => 'string',
  ),
  'Redis::hExists' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'field' => 'string',
  ),
  'Redis::hGet' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
  ),
  'Redis::hGetAll' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
  ),
  'Redis::hIncrBy' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'field' => 'string',
    'value' => 'int',
  ),
  'Redis::hIncrByFloat' => 
  array (
    0 => 'Redis|float|false',
    'key' => 'string',
    'field' => 'string',
    'value' => 'float',
  ),
  'Redis::hKeys' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
  ),
  'Redis::hLen' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::hMget' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'fields' => 'array',
  ),
  'Redis::hMset' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'fieldvals' => 'array',
  ),
  'Redis::hRandField' => 
  array (
    0 => 'Redis|array|string|false',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::hSet' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    '...fields_and_vals=' => 'mixed|null',
  ),
  'Redis::hSetNx' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'field' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::hStrLen' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'field' => 'string',
  ),
  'Redis::hVals' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
  ),
  'Redis::hscan' => 
  array (
    0 => 'Redis|array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'Redis::incr' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'by=' => 'int',
  ),
  'Redis::incrBy' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'int',
  ),
  'Redis::incrByFloat' => 
  array (
    0 => 'Redis|float|false',
    'key' => 'string',
    'value' => 'float',
  ),
  'Redis::info' => 
  array (
    0 => 'Redis|array|false',
    '...sections=' => 'string',
  ),
  'Redis::isConnected' => 
  array (
    0 => 'bool',
  ),
  'Redis::keys' => 
  array (
    0 => 'string',
    'pattern' => 'string',
  ),
  'Redis::lInsert' => 
  array (
    0 => 'string',
    'key' => 'string',
    'pos' => 'string',
    'pivot' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'Redis::lLen' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::lMove' => 
  array (
    0 => 'Redis|string|false',
    'src' => 'string',
    'dst' => 'string',
    'wherefrom' => 'string',
    'whereto' => 'string',
  ),
  'Redis::blmove' => 
  array (
    0 => 'Redis|string|false',
    'src' => 'string',
    'dst' => 'string',
    'wherefrom' => 'string',
    'whereto' => 'string',
    'timeout' => 'float',
  ),
  'Redis::lPop' => 
  array (
    0 => 'Redis|array|string|bool',
    'key' => 'string',
    'count=' => 'int',
  ),
  'Redis::lPos' => 
  array (
    0 => 'Redis|array|int|bool|null|null',
    'key' => 'string',
    'value' => 'mixed|null',
    'options=' => 'array|null',
  ),
  'Redis::lPush' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    '...elements=' => 'mixed|null',
  ),
  'Redis::rPush' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    '...elements=' => 'mixed|null',
  ),
  'Redis::lPushx' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::rPushx' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::lSet' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'index' => 'int',
    'value' => 'mixed|null',
  ),
  'Redis::lastSave' => 
  array (
    0 => 'int',
  ),
  'Redis::lindex' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'index' => 'int',
  ),
  'Redis::lrange' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'Redis::lrem' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
    'count=' => 'int',
  ),
  'Redis::ltrim' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'Redis::mget' => 
  array (
    0 => 'Redis|array|false',
    'keys' => 'array',
  ),
  'Redis::migrate' => 
  array (
    0 => 'Redis|bool',
    'host' => 'string',
    'port' => 'int',
    'key' => 'array|string',
    'dstdb' => 'int',
    'timeout' => 'int',
    'copy=' => 'bool',
    'replace=' => 'bool',
    'credentials=' => 'mixed|null',
  ),
  'Redis::move' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'index' => 'int',
  ),
  'Redis::mset' => 
  array (
    0 => 'Redis|bool',
    'key_values' => 'array',
  ),
  'Redis::msetnx' => 
  array (
    0 => 'Redis|bool',
    'key_values' => 'array',
  ),
  'Redis::multi' => 
  array (
    0 => 'Redis|bool',
    'value=' => 'int',
  ),
  'Redis::object' => 
  array (
    0 => 'Redis|string|int|false',
    'subcommand' => 'string',
    'key' => 'string',
  ),
  'Redis::open' => 
  array (
    0 => 'bool',
    'host' => 'string',
    'port=' => 'int',
    'timeout=' => 'float',
    'persistent_id=' => 'string|null',
    'retry_interval=' => 'int',
    'read_timeout=' => 'float',
    'context=' => 'array|null',
  ),
  'Redis::pconnect' => 
  array (
    0 => 'bool',
    'host' => 'string',
    'port=' => 'int',
    'timeout=' => 'float',
    'persistent_id=' => 'string|null',
    'retry_interval=' => 'int',
    'read_timeout=' => 'float',
    'context=' => 'array|null',
  ),
  'Redis::persist' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
  ),
  'Redis::pexpire' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'timeout' => 'int',
    'mode=' => 'string|null',
  ),
  'Redis::pexpireAt' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'timestamp' => 'int',
    'mode=' => 'string|null',
  ),
  'Redis::pfadd' => 
  array (
    0 => 'Redis|int',
    'key' => 'string',
    'elements' => 'array',
  ),
  'Redis::pfcount' => 
  array (
    0 => 'Redis|int|false',
    'key_or_keys' => 'array|string',
  ),
  'Redis::pfmerge' => 
  array (
    0 => 'Redis|bool',
    'dst' => 'string',
    'srckeys' => 'array',
  ),
  'Redis::ping' => 
  array (
    0 => 'Redis|string|bool',
    'message=' => 'string|null',
  ),
  'Redis::pipeline' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::popen' => 
  array (
    0 => 'bool',
    'host' => 'string',
    'port=' => 'int',
    'timeout=' => 'float',
    'persistent_id=' => 'string|null',
    'retry_interval=' => 'int',
    'read_timeout=' => 'float',
    'context=' => 'array|null',
  ),
  'Redis::psetex' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'expire' => 'int',
    'value' => 'mixed|null',
  ),
  'Redis::psubscribe' => 
  array (
    0 => 'bool',
    'patterns' => 'array',
    'cb' => 'callable',
  ),
  'Redis::pttl' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::publish' => 
  array (
    0 => 'Redis|int|false',
    'channel' => 'string',
    'message' => 'string',
  ),
  'Redis::pubsub' => 
  array (
    0 => 'mixed|null',
    'command' => 'string',
    'arg=' => 'mixed|null',
  ),
  'Redis::punsubscribe' => 
  array (
    0 => 'Redis|array|bool',
    'patterns' => 'array',
  ),
  'Redis::rPop' => 
  array (
    0 => 'Redis|array|string|bool',
    'key' => 'string',
    'count=' => 'int',
  ),
  'Redis::randomKey' => 
  array (
    0 => 'Redis|string|false',
  ),
  'Redis::rawcommand' => 
  array (
    0 => 'mixed|null',
    'command' => 'string',
    '...args=' => 'mixed|null',
  ),
  'Redis::rename' => 
  array (
    0 => 'Redis|bool',
    'old_name' => 'string',
    'new_name' => 'string',
  ),
  'Redis::renameNx' => 
  array (
    0 => 'Redis|bool',
    'key_src' => 'string',
    'key_dst' => 'string',
  ),
  'Redis::reset' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::restore' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'ttl' => 'int',
    'value' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::role' => 
  array (
    0 => 'mixed|null',
  ),
  'Redis::rpoplpush' => 
  array (
    0 => 'Redis|string|false',
    'srckey' => 'string',
    'dstkey' => 'string',
  ),
  'Redis::sAdd' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
    '...other_values=' => 'mixed|null',
  ),
  'Redis::sAddArray' => 
  array (
    0 => 'int',
    'key' => 'string',
    'values' => 'array',
  ),
  'Redis::sDiff' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'Redis::sDiffStore' => 
  array (
    0 => 'Redis|int|false',
    'dst' => 'string',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'Redis::sInter' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::sintercard' => 
  array (
    0 => 'Redis|int|false',
    'keys' => 'array',
    'limit=' => 'int',
  ),
  'Redis::sInterStore' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::sMembers' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
  ),
  'Redis::sMisMember' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'Redis::sMove' => 
  array (
    0 => 'Redis|bool',
    'src' => 'string',
    'dst' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::sPop' => 
  array (
    0 => 'Redis|array|string|false',
    'key' => 'string',
    'count=' => 'int',
  ),
  'Redis::sRandMember' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'count=' => 'int',
  ),
  'Redis::sUnion' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'Redis::sUnionStore' => 
  array (
    0 => 'Redis|int|false',
    'dst' => 'string',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'Redis::save' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::scan' => 
  array (
    0 => 'array|false',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
    'type=' => 'string|null',
  ),
  'Redis::scard' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::script' => 
  array (
    0 => 'mixed|null',
    'command' => 'string',
    '...args=' => 'mixed|null',
  ),
  'Redis::select' => 
  array (
    0 => 'Redis|bool',
    'db' => 'int',
  ),
  'Redis::set' => 
  array (
    0 => 'Redis|string|bool',
    'key' => 'string',
    'value' => 'mixed|null',
    'options=' => 'mixed|null',
  ),
  'Redis::setBit' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'idx' => 'int',
    'value' => 'bool',
  ),
  'Redis::setRange' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'index' => 'int',
    'value' => 'string',
  ),
  'Redis::setOption' => 
  array (
    0 => 'bool',
    'option' => 'int',
    'value' => 'mixed|null',
  ),
  'Redis::setex' => 
  array (
    0 => 'string',
    'key' => 'string',
    'expire' => 'int',
    'value' => 'mixed|null',
  ),
  'Redis::setnx' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::sismember' => 
  array (
    0 => 'Redis|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'Redis::slaveof' => 
  array (
    0 => 'Redis|bool',
    'host=' => 'string|null',
    'port=' => 'int',
  ),
  'Redis::replicaof' => 
  array (
    0 => 'Redis|bool',
    'host=' => 'string|null',
    'port=' => 'int',
  ),
  'Redis::touch' => 
  array (
    0 => 'Redis|int|false',
    'key_or_array' => 'array|string',
    '...more_keys=' => 'string',
  ),
  'Redis::slowlog' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'length=' => 'int',
  ),
  'Redis::sort' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::sort_ro' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::sortAsc' => 
  array (
    0 => 'array',
    'key' => 'string',
    'pattern=' => 'string|null',
    'get=' => 'mixed|null',
    'offset=' => 'int',
    'count=' => 'int',
    'store=' => 'string|null',
  ),
  'Redis::sortAscAlpha' => 
  array (
    0 => 'array',
    'key' => 'string',
    'pattern=' => 'string|null',
    'get=' => 'mixed|null',
    'offset=' => 'int',
    'count=' => 'int',
    'store=' => 'string|null',
  ),
  'Redis::sortDesc' => 
  array (
    0 => 'array',
    'key' => 'string',
    'pattern=' => 'string|null',
    'get=' => 'mixed|null',
    'offset=' => 'int',
    'count=' => 'int',
    'store=' => 'string|null',
  ),
  'Redis::sortDescAlpha' => 
  array (
    0 => 'array',
    'key' => 'string',
    'pattern=' => 'string|null',
    'get=' => 'mixed|null',
    'offset=' => 'int',
    'count=' => 'int',
    'store=' => 'string|null',
  ),
  'Redis::srem' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
    '...other_values=' => 'mixed|null',
  ),
  'Redis::sscan' => 
  array (
    0 => 'array|false',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'Redis::ssubscribe' => 
  array (
    0 => 'bool',
    'channels' => 'array',
    'cb' => 'callable',
  ),
  'Redis::strlen' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::subscribe' => 
  array (
    0 => 'bool',
    'channels' => 'array',
    'cb' => 'callable',
  ),
  'Redis::sunsubscribe' => 
  array (
    0 => 'Redis|array|bool',
    'channels' => 'array',
  ),
  'Redis::swapdb' => 
  array (
    0 => 'Redis|bool',
    'src' => 'int',
    'dst' => 'int',
  ),
  'Redis::time' => 
  array (
    0 => 'Redis|array',
  ),
  'Redis::ttl' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::type' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::unlink' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::unsubscribe' => 
  array (
    0 => 'Redis|array|bool',
    'channels' => 'array',
  ),
  'Redis::unwatch' => 
  array (
    0 => 'Redis|bool',
  ),
  'Redis::watch' => 
  array (
    0 => 'Redis|bool',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'Redis::wait' => 
  array (
    0 => 'int|false',
    'numreplicas' => 'int',
    'timeout' => 'int',
  ),
  'Redis::xack' => 
  array (
    0 => 'int|false',
    'key' => 'string',
    'group' => 'string',
    'ids' => 'array',
  ),
  'Redis::xadd' => 
  array (
    0 => 'Redis|string|false',
    'key' => 'string',
    'id' => 'string',
    'values' => 'array',
    'maxlen=' => 'int',
    'approx=' => 'bool',
    'nomkstream=' => 'bool',
  ),
  'Redis::xautoclaim' => 
  array (
    0 => 'Redis|array|bool',
    'key' => 'string',
    'group' => 'string',
    'consumer' => 'string',
    'min_idle' => 'int',
    'start' => 'string',
    'count=' => 'int',
    'justid=' => 'bool',
  ),
  'Redis::xclaim' => 
  array (
    0 => 'Redis|array|bool',
    'key' => 'string',
    'group' => 'string',
    'consumer' => 'string',
    'min_idle' => 'int',
    'ids' => 'array',
    'options' => 'array',
  ),
  'Redis::xdel' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'ids' => 'array',
  ),
  'Redis::xgroup' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'key=' => 'string|null',
    'group=' => 'string|null',
    'id_or_consumer=' => 'string|null',
    'mkstream=' => 'bool',
    'entries_read=' => 'int',
  ),
  'Redis::xinfo' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'arg1=' => 'string|null',
    'arg2=' => 'string|null',
    'count=' => 'int',
  ),
  'Redis::xlen' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::xpending' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'group' => 'string',
    'start=' => 'string|null',
    'end=' => 'string|null',
    'count=' => 'int',
    'consumer=' => 'string|null',
  ),
  'Redis::xrange' => 
  array (
    0 => 'Redis|array|bool',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
    'count=' => 'int',
  ),
  'Redis::xread' => 
  array (
    0 => 'Redis|array|bool',
    'streams' => 'array',
    'count=' => 'int',
    'block=' => 'int',
  ),
  'Redis::xreadgroup' => 
  array (
    0 => 'Redis|array|bool',
    'group' => 'string',
    'consumer' => 'string',
    'streams' => 'array',
    'count=' => 'int',
    'block=' => 'int',
  ),
  'Redis::xrevrange' => 
  array (
    0 => 'Redis|array|bool',
    'key' => 'string',
    'end' => 'string',
    'start' => 'string',
    'count=' => 'int',
  ),
  'Redis::xtrim' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'threshold' => 'string',
    'approx=' => 'bool',
    'minid=' => 'bool',
    'limit=' => 'int',
  ),
  'Redis::zAdd' => 
  array (
    0 => 'Redis|int|float|false',
    'key' => 'string',
    'score_or_options' => 'array|float',
    '...more_scores_and_mems=' => 'mixed|null',
  ),
  'Redis::zCard' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
  ),
  'Redis::zCount' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'start' => 'string|int',
    'end' => 'string|int',
  ),
  'Redis::zIncrBy' => 
  array (
    0 => 'Redis|float|false',
    'key' => 'string',
    'value' => 'float',
    'member' => 'mixed|null',
  ),
  'Redis::zLexCount' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'Redis::zMscore' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'member' => 'mixed|null',
    '...other_members=' => 'mixed|null',
  ),
  'Redis::zPopMax' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'count=' => 'int|null',
  ),
  'Redis::zPopMin' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'count=' => 'int|null',
  ),
  'Redis::zRange' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'start' => 'string|int',
    'end' => 'string|int',
    'options=' => 'array|bool|null|null',
  ),
  'Redis::zRangeByLex' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
    'offset=' => 'int',
    'count=' => 'int',
  ),
  'Redis::zRangeByScore' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
    'options=' => 'array',
  ),
  'Redis::zrangestore' => 
  array (
    0 => 'Redis|int|false',
    'dstkey' => 'string',
    'srckey' => 'string',
    'start' => 'string',
    'end' => 'string',
    'options=' => 'array|bool|null|null',
  ),
  'Redis::zRandMember' => 
  array (
    0 => 'Redis|array|string',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'Redis::zRank' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'Redis::zRem' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'mixed|null',
    'member' => 'mixed|null',
    '...other_members=' => 'mixed|null',
  ),
  'Redis::zRemRangeByLex' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'Redis::zRemRangeByRank' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'Redis::zRemRangeByScore' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
  ),
  'Redis::zRevRange' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
    'scores=' => 'mixed|null',
  ),
  'Redis::zRevRangeByLex' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'max' => 'string',
    'min' => 'string',
    'offset=' => 'int',
    'count=' => 'int',
  ),
  'Redis::zRevRangeByScore' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'max' => 'string',
    'min' => 'string',
    'options=' => 'array|bool',
  ),
  'Redis::zRevRank' => 
  array (
    0 => 'Redis|int|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'Redis::zScore' => 
  array (
    0 => 'Redis|float|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'Redis::zdiff' => 
  array (
    0 => 'Redis|array|false',
    'keys' => 'array',
    'options=' => 'array|null',
  ),
  'Redis::zdiffstore' => 
  array (
    0 => 'Redis|int|false',
    'dst' => 'string',
    'keys' => 'array',
  ),
  'Redis::zinter' => 
  array (
    0 => 'Redis|array|false',
    'keys' => 'array',
    'weights=' => 'array|null',
    'options=' => 'array|null',
  ),
  'Redis::zintercard' => 
  array (
    0 => 'Redis|int|false',
    'keys' => 'array',
    'limit=' => 'int',
  ),
  'Redis::zinterstore' => 
  array (
    0 => 'Redis|int|false',
    'dst' => 'string',
    'keys' => 'array',
    'weights=' => 'array|null',
    'aggregate=' => 'string|null',
  ),
  'Redis::zscan' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'Redis::zunion' => 
  array (
    0 => 'Redis|array|false',
    'keys' => 'array',
    'weights=' => 'array|null',
    'options=' => 'array|null',
  ),
  'Redis::zunionstore' => 
  array (
    0 => 'Redis|int|false',
    'dst' => 'string',
    'keys' => 'array',
    'weights=' => 'array|null',
    'aggregate=' => 'string|null',
  ),
  'RedisArray::__call' => 
  array (
    0 => 'mixed|null',
    'function_name' => 'string',
    'arguments' => 'array',
  ),
  'RedisArray::__construct' => 
  array (
    0 => 'string',
    'name_or_hosts' => 'array|string',
    'options=' => 'array|null',
  ),
  'RedisArray::_continuum' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::_distributor' => 
  array (
    0 => 'callable|bool',
  ),
  'RedisArray::_function' => 
  array (
    0 => 'callable|bool',
  ),
  'RedisArray::_hosts' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::_instance' => 
  array (
    0 => 'Redis|bool|null|null',
    'host' => 'string',
  ),
  'RedisArray::_rehash' => 
  array (
    0 => 'bool|null',
    'fn=' => 'callable|null',
  ),
  'RedisArray::_target' => 
  array (
    0 => 'string|bool|null|null',
    'key' => 'string',
  ),
  'RedisArray::bgsave' => 
  array (
    0 => 'array',
  ),
  'RedisArray::del' => 
  array (
    0 => 'int|bool',
    'key' => 'array|string',
    '...otherkeys=' => 'string',
  ),
  'RedisArray::discard' => 
  array (
    0 => 'bool|null',
  ),
  'RedisArray::exec' => 
  array (
    0 => 'array|bool|null|null',
  ),
  'RedisArray::flushall' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::flushdb' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::getOption' => 
  array (
    0 => 'array|bool',
    'opt' => 'int',
  ),
  'RedisArray::hscan' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisArray::info' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::keys' => 
  array (
    0 => 'array|bool',
    'pattern' => 'string',
  ),
  'RedisArray::mget' => 
  array (
    0 => 'array|bool',
    'keys' => 'array',
  ),
  'RedisArray::mset' => 
  array (
    0 => 'bool',
    'pairs' => 'array',
  ),
  'RedisArray::multi' => 
  array (
    0 => 'RedisArray|bool',
    'host' => 'string',
    'mode=' => 'int|null',
  ),
  'RedisArray::ping' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::save' => 
  array (
    0 => 'array|bool',
  ),
  'RedisArray::scan' => 
  array (
    0 => 'array|bool',
    '&iterator' => 'string|int|null|null',
    'node' => 'string',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisArray::select' => 
  array (
    0 => 'array|bool',
    'index' => 'int',
  ),
  'RedisArray::setOption' => 
  array (
    0 => 'array|bool',
    'opt' => 'int',
    'value' => 'string',
  ),
  'RedisArray::sscan' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisArray::unlink' => 
  array (
    0 => 'int|bool',
    'key' => 'array|string',
    '...otherkeys=' => 'string',
  ),
  'RedisArray::unwatch' => 
  array (
    0 => 'bool|null',
  ),
  'RedisArray::zscan' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::__construct' => 
  array (
    0 => 'string',
    'name' => 'string|null',
    'seeds=' => 'array|null',
    'timeout=' => 'int|float',
    'read_timeout=' => 'int|float',
    'persistent=' => 'bool',
    'auth=' => 'mixed|null',
    'context=' => 'array|null',
  ),
  'RedisCluster::_compress' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'RedisCluster::_uncompress' => 
  array (
    0 => 'string',
    'value' => 'string',
  ),
  'RedisCluster::_serialize' => 
  array (
    0 => 'string|bool',
    'value' => 'mixed|null',
  ),
  'RedisCluster::_unserialize' => 
  array (
    0 => 'mixed|null',
    'value' => 'string',
  ),
  'RedisCluster::_pack' => 
  array (
    0 => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::_unpack' => 
  array (
    0 => 'mixed|null',
    'value' => 'string',
  ),
  'RedisCluster::_prefix' => 
  array (
    0 => 'string|bool',
    'key' => 'string',
  ),
  'RedisCluster::_masters' => 
  array (
    0 => 'array',
  ),
  'RedisCluster::_redir' => 
  array (
    0 => 'string|null',
  ),
  'RedisCluster::acl' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    'subcmd' => 'string',
    '...args=' => 'string',
  ),
  'RedisCluster::append' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::bgrewriteaof' => 
  array (
    0 => 'RedisCluster|bool',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::waitaof' => 
  array (
    0 => 'RedisCluster|array|false',
    'key_or_address' => 'array|string',
    'numlocal' => 'int',
    'numreplicas' => 'int',
    'timeout' => 'int',
  ),
  'RedisCluster::bgsave' => 
  array (
    0 => 'RedisCluster|bool',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::bitcount' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'start=' => 'int',
    'end=' => 'int',
    'bybit=' => 'bool',
  ),
  'RedisCluster::bitop' => 
  array (
    0 => 'RedisCluster|int|bool',
    'operation' => 'string',
    'deskey' => 'string',
    'srckey' => 'string',
    '...otherkeys=' => 'string',
  ),
  'RedisCluster::bitpos' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'bit' => 'bool',
    'start=' => 'int',
    'end=' => 'int',
    'bybit=' => 'bool',
  ),
  'RedisCluster::blpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int|float',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::brpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int|float',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::brpoplpush' => 
  array (
    0 => 'mixed|null',
    'srckey' => 'string',
    'deskey' => 'string',
    'timeout' => 'int',
  ),
  'RedisCluster::lmove' => 
  array (
    0 => 'Redis|string|false',
    'src' => 'string',
    'dst' => 'string',
    'wherefrom' => 'string',
    'whereto' => 'string',
  ),
  'RedisCluster::blmove' => 
  array (
    0 => 'Redis|string|false',
    'src' => 'string',
    'dst' => 'string',
    'wherefrom' => 'string',
    'whereto' => 'string',
    'timeout' => 'float',
  ),
  'RedisCluster::bzpopmax' => 
  array (
    0 => 'array',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::bzpopmin' => 
  array (
    0 => 'array',
    'key' => 'array|string',
    'timeout_or_key' => 'string|int',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::bzmpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'timeout' => 'float',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::zmpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::blmpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'timeout' => 'float',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::lmpop' => 
  array (
    0 => 'RedisCluster|array|false|null|null',
    'keys' => 'array',
    'from' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::clearlasterror' => 
  array (
    0 => 'bool',
  ),
  'RedisCluster::client' => 
  array (
    0 => 'array|string|bool',
    'key_or_address' => 'array|string',
    'subcommand' => 'string',
    'arg=' => 'string|null',
  ),
  'RedisCluster::close' => 
  array (
    0 => 'bool',
  ),
  'RedisCluster::cluster' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    'command' => 'string',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::command' => 
  array (
    0 => 'mixed|null',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::config' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    'subcommand' => 'string',
    '...extra_args=' => 'mixed|null',
  ),
  'RedisCluster::dbsize' => 
  array (
    0 => 'RedisCluster|int',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::copy' => 
  array (
    0 => 'RedisCluster|bool',
    'src' => 'string',
    'dst' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::decr' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'by=' => 'int',
  ),
  'RedisCluster::decrby' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'int',
  ),
  'RedisCluster::decrbyfloat' => 
  array (
    0 => 'float',
    'key' => 'string',
    'value' => 'float',
  ),
  'RedisCluster::del' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::discard' => 
  array (
    0 => 'bool',
  ),
  'RedisCluster::dump' => 
  array (
    0 => 'RedisCluster|string|false',
    'key' => 'string',
  ),
  'RedisCluster::echo' => 
  array (
    0 => 'RedisCluster|string|false',
    'key_or_address' => 'array|string',
    'msg' => 'string',
  ),
  'RedisCluster::eval' => 
  array (
    0 => 'mixed|null',
    'script' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'RedisCluster::eval_ro' => 
  array (
    0 => 'mixed|null',
    'script' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'RedisCluster::evalsha' => 
  array (
    0 => 'mixed|null',
    'script_sha' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'RedisCluster::evalsha_ro' => 
  array (
    0 => 'mixed|null',
    'script_sha' => 'string',
    'args=' => 'array',
    'num_keys=' => 'int',
  ),
  'RedisCluster::exec' => 
  array (
    0 => 'array|false',
  ),
  'RedisCluster::exists' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'mixed|null',
    '...other_keys=' => 'mixed|null',
  ),
  'RedisCluster::touch' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'mixed|null',
    '...other_keys=' => 'mixed|null',
  ),
  'RedisCluster::expire' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timeout' => 'int',
    'mode=' => 'string|null',
  ),
  'RedisCluster::expireat' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timestamp' => 'int',
    'mode=' => 'string|null',
  ),
  'RedisCluster::expiretime' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::pexpiretime' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::flushall' => 
  array (
    0 => 'RedisCluster|bool',
    'key_or_address' => 'array|string',
    'async=' => 'bool',
  ),
  'RedisCluster::flushdb' => 
  array (
    0 => 'RedisCluster|bool',
    'key_or_address' => 'array|string',
    'async=' => 'bool',
  ),
  'RedisCluster::geoadd' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'member' => 'string',
    '...other_triples_and_options=' => 'mixed|null',
  ),
  'RedisCluster::geodist' => 
  array (
    0 => 'RedisCluster|float|false',
    'key' => 'string',
    'src' => 'string',
    'dest' => 'string',
    'unit=' => 'string|null',
  ),
  'RedisCluster::geohash' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'RedisCluster::geopos' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'RedisCluster::georadius' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::georadius_ro' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'lng' => 'float',
    'lat' => 'float',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::georadiusbymember' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::georadiusbymember_ro' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
    'radius' => 'float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::geosearch' => 
  array (
    0 => 'RedisCluster|array',
    'key' => 'string',
    'position' => 'array|string',
    'shape' => 'array|int|float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::geosearchstore' => 
  array (
    0 => 'RedisCluster|array|int|false',
    'dst' => 'string',
    'src' => 'string',
    'position' => 'array|string',
    'shape' => 'array|int|float',
    'unit' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::get' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
  ),
  'RedisCluster::getex' => 
  array (
    0 => 'RedisCluster|string|false',
    'key' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::getbit' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'int',
  ),
  'RedisCluster::getlasterror' => 
  array (
    0 => 'string|null',
  ),
  'RedisCluster::getmode' => 
  array (
    0 => 'int',
  ),
  'RedisCluster::getoption' => 
  array (
    0 => 'mixed|null',
    'option' => 'int',
  ),
  'RedisCluster::getrange' => 
  array (
    0 => 'RedisCluster|string|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'RedisCluster::lcs' => 
  array (
    0 => 'RedisCluster|array|string|int|false',
    'key1' => 'string',
    'key2' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::getset' => 
  array (
    0 => 'RedisCluster|string|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::gettransferredbytes' => 
  array (
    0 => 'array|false',
  ),
  'RedisCluster::cleartransferredbytes' => 
  array (
    0 => 'void',
  ),
  'RedisCluster::hdel' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'RedisCluster::hexists' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'member' => 'string',
  ),
  'RedisCluster::hget' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'member' => 'string',
  ),
  'RedisCluster::hgetall' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
  ),
  'RedisCluster::hincrby' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'member' => 'string',
    'value' => 'int',
  ),
  'RedisCluster::hincrbyfloat' => 
  array (
    0 => 'RedisCluster|float|false',
    'key' => 'string',
    'member' => 'string',
    'value' => 'float',
  ),
  'RedisCluster::hkeys' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
  ),
  'RedisCluster::hlen' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::hmget' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'keys' => 'array',
  ),
  'RedisCluster::hmset' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'key_values' => 'array',
  ),
  'RedisCluster::hscan' => 
  array (
    0 => 'array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::hrandfield' => 
  array (
    0 => 'RedisCluster|array|string',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::hset' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'member' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::hsetnx' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'member' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::hstrlen' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'field' => 'string',
  ),
  'RedisCluster::hvals' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
  ),
  'RedisCluster::incr' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'by=' => 'int',
  ),
  'RedisCluster::incrby' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'int',
  ),
  'RedisCluster::incrbyfloat' => 
  array (
    0 => 'RedisCluster|float|false',
    'key' => 'string',
    'value' => 'float',
  ),
  'RedisCluster::info' => 
  array (
    0 => 'RedisCluster|array|false',
    'key_or_address' => 'array|string',
    '...sections=' => 'string',
  ),
  'RedisCluster::keys' => 
  array (
    0 => 'RedisCluster|array|false',
    'pattern' => 'string',
  ),
  'RedisCluster::lastsave' => 
  array (
    0 => 'RedisCluster|int|false',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::lget' => 
  array (
    0 => 'RedisCluster|string|bool',
    'key' => 'string',
    'index' => 'int',
  ),
  'RedisCluster::lindex' => 
  array (
    0 => 'mixed|null',
    'key' => 'string',
    'index' => 'int',
  ),
  'RedisCluster::linsert' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'pos' => 'string',
    'pivot' => 'mixed|null',
    'value' => 'mixed|null',
  ),
  'RedisCluster::llen' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
  ),
  'RedisCluster::lpop' => 
  array (
    0 => 'RedisCluster|array|string|bool',
    'key' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::lpos' => 
  array (
    0 => 'Redis|array|int|bool|null|null',
    'key' => 'string',
    'value' => 'mixed|null',
    'options=' => 'array|null',
  ),
  'RedisCluster::lpush' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'value' => 'mixed|null',
    '...other_values=' => 'mixed|null',
  ),
  'RedisCluster::lpushx' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::lrange' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'RedisCluster::lrem' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'value' => 'mixed|null',
    'count=' => 'int',
  ),
  'RedisCluster::lset' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'index' => 'int',
    'value' => 'mixed|null',
  ),
  'RedisCluster::ltrim' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'start' => 'int',
    'end' => 'int',
  ),
  'RedisCluster::mget' => 
  array (
    0 => 'RedisCluster|array|false',
    'keys' => 'array',
  ),
  'RedisCluster::mset' => 
  array (
    0 => 'RedisCluster|bool',
    'key_values' => 'array',
  ),
  'RedisCluster::msetnx' => 
  array (
    0 => 'RedisCluster|array|false',
    'key_values' => 'array',
  ),
  'RedisCluster::multi' => 
  array (
    0 => 'RedisCluster|bool',
    'value=' => 'int',
  ),
  'RedisCluster::object' => 
  array (
    0 => 'RedisCluster|string|int|false',
    'subcommand' => 'string',
    'key' => 'string',
  ),
  'RedisCluster::persist' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
  ),
  'RedisCluster::pexpire' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timeout' => 'int',
    'mode=' => 'string|null',
  ),
  'RedisCluster::pexpireat' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timestamp' => 'int',
    'mode=' => 'string|null',
  ),
  'RedisCluster::pfadd' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'elements' => 'array',
  ),
  'RedisCluster::pfcount' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::pfmerge' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'keys' => 'array',
  ),
  'RedisCluster::ping' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    'message=' => 'string|null',
  ),
  'RedisCluster::psetex' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timeout' => 'int',
    'value' => 'string',
  ),
  'RedisCluster::psubscribe' => 
  array (
    0 => 'void',
    'patterns' => 'array',
    'callback' => 'callable',
  ),
  'RedisCluster::pttl' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::publish' => 
  array (
    0 => 'RedisCluster|int|bool',
    'channel' => 'string',
    'message' => 'string',
  ),
  'RedisCluster::pubsub' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    '...values=' => 'string',
  ),
  'RedisCluster::punsubscribe' => 
  array (
    0 => 'array|bool',
    'pattern' => 'string',
    '...other_patterns=' => 'string',
  ),
  'RedisCluster::randomkey' => 
  array (
    0 => 'RedisCluster|string|bool',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::rawcommand' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    'command' => 'string',
    '...args=' => 'mixed|null',
  ),
  'RedisCluster::rename' => 
  array (
    0 => 'RedisCluster|bool',
    'key_src' => 'string',
    'key_dst' => 'string',
  ),
  'RedisCluster::renamenx' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'newkey' => 'string',
  ),
  'RedisCluster::restore' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'timeout' => 'int',
    'value' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::role' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::rpop' => 
  array (
    0 => 'RedisCluster|array|string|bool',
    'key' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::rpoplpush' => 
  array (
    0 => 'RedisCluster|string|bool',
    'src' => 'string',
    'dst' => 'string',
  ),
  'RedisCluster::rpush' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    '...elements=' => 'mixed|null',
  ),
  'RedisCluster::rpushx' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'value' => 'string',
  ),
  'RedisCluster::sadd' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
    '...other_values=' => 'mixed|null',
  ),
  'RedisCluster::saddarray' => 
  array (
    0 => 'RedisCluster|int|bool',
    'key' => 'string',
    'values' => 'array',
  ),
  'RedisCluster::save' => 
  array (
    0 => 'RedisCluster|bool',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::scan' => 
  array (
    0 => 'array|bool',
    '&iterator' => 'string|int|null|null',
    'key_or_address' => 'array|string',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::scard' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::script' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    '...args=' => 'mixed|null',
  ),
  'RedisCluster::sdiff' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::sdiffstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dst' => 'string',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::set' => 
  array (
    0 => 'RedisCluster|string|bool',
    'key' => 'string',
    'value' => 'mixed|null',
    'options=' => 'mixed|null',
  ),
  'RedisCluster::setbit' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'offset' => 'int',
    'onoff' => 'bool',
  ),
  'RedisCluster::setex' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'expire' => 'int',
    'value' => 'mixed|null',
  ),
  'RedisCluster::setnx' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::setoption' => 
  array (
    0 => 'bool',
    'option' => 'int',
    'value' => 'mixed|null',
  ),
  'RedisCluster::setrange' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'offset' => 'int',
    'value' => 'string',
  ),
  'RedisCluster::sinter' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::sintercard' => 
  array (
    0 => 'RedisCluster|int|false',
    'keys' => 'array',
    'limit=' => 'int',
  ),
  'RedisCluster::sinterstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::sismember' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    'value' => 'mixed|null',
  ),
  'RedisCluster::smismember' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'member' => 'string',
    '...other_members=' => 'string',
  ),
  'RedisCluster::slowlog' => 
  array (
    0 => 'mixed|null',
    'key_or_address' => 'array|string',
    '...args=' => 'mixed|null',
  ),
  'RedisCluster::smembers' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
  ),
  'RedisCluster::smove' => 
  array (
    0 => 'RedisCluster|bool',
    'src' => 'string',
    'dst' => 'string',
    'member' => 'string',
  ),
  'RedisCluster::sort' => 
  array (
    0 => 'RedisCluster|array|string|int|bool',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::sort_ro' => 
  array (
    0 => 'RedisCluster|array|string|int|bool',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::spop' => 
  array (
    0 => 'RedisCluster|array|string|false',
    'key' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::srandmember' => 
  array (
    0 => 'RedisCluster|array|string|false',
    'key' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::srem' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'mixed|null',
    '...other_values=' => 'mixed|null',
  ),
  'RedisCluster::sscan' => 
  array (
    0 => 'array|false',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::strlen' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::subscribe' => 
  array (
    0 => 'void',
    'channels' => 'array',
    'cb' => 'callable',
  ),
  'RedisCluster::sunion' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::sunionstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dst' => 'string',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::time' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key_or_address' => 'array|string',
  ),
  'RedisCluster::ttl' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::type' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::unsubscribe' => 
  array (
    0 => 'array|bool',
    'channels' => 'array',
  ),
  'RedisCluster::unlink' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'array|string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::unwatch' => 
  array (
    0 => 'bool',
  ),
  'RedisCluster::watch' => 
  array (
    0 => 'RedisCluster|bool',
    'key' => 'string',
    '...other_keys=' => 'string',
  ),
  'RedisCluster::xack' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'group' => 'string',
    'ids' => 'array',
  ),
  'RedisCluster::xadd' => 
  array (
    0 => 'RedisCluster|string|false',
    'key' => 'string',
    'id' => 'string',
    'values' => 'array',
    'maxlen=' => 'int',
    'approx=' => 'bool',
  ),
  'RedisCluster::xclaim' => 
  array (
    0 => 'RedisCluster|array|string|false',
    'key' => 'string',
    'group' => 'string',
    'consumer' => 'string',
    'min_iddle' => 'int',
    'ids' => 'array',
    'options' => 'array',
  ),
  'RedisCluster::xdel' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'ids' => 'array',
  ),
  'RedisCluster::xgroup' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'key=' => 'string|null',
    'group=' => 'string|null',
    'id_or_consumer=' => 'string|null',
    'mkstream=' => 'bool',
    'entries_read=' => 'int',
  ),
  'RedisCluster::xautoclaim' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'group' => 'string',
    'consumer' => 'string',
    'min_idle' => 'int',
    'start' => 'string',
    'count=' => 'int',
    'justid=' => 'bool',
  ),
  'RedisCluster::xinfo' => 
  array (
    0 => 'mixed|null',
    'operation' => 'string',
    'arg1=' => 'string|null',
    'arg2=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::xlen' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::xpending' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'group' => 'string',
    'start=' => 'string|null',
    'end=' => 'string|null',
    'count=' => 'int',
    'consumer=' => 'string|null',
  ),
  'RedisCluster::xrange' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::xread' => 
  array (
    0 => 'RedisCluster|array|bool',
    'streams' => 'array',
    'count=' => 'int',
    'block=' => 'int',
  ),
  'RedisCluster::xreadgroup' => 
  array (
    0 => 'RedisCluster|array|bool',
    'group' => 'string',
    'consumer' => 'string',
    'streams' => 'array',
    'count=' => 'int',
    'block=' => 'int',
  ),
  'RedisCluster::xrevrange' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
    'count=' => 'int',
  ),
  'RedisCluster::xtrim' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'maxlen' => 'int',
    'approx=' => 'bool',
    'minid=' => 'bool',
    'limit=' => 'int',
  ),
  'RedisCluster::zadd' => 
  array (
    0 => 'RedisCluster|int|float|false',
    'key' => 'string',
    'score_or_options' => 'array|float',
    '...more_scores_and_mems=' => 'mixed|null',
  ),
  'RedisCluster::zcard' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
  ),
  'RedisCluster::zcount' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
  ),
  'RedisCluster::zincrby' => 
  array (
    0 => 'RedisCluster|float|false',
    'key' => 'string',
    'value' => 'float',
    'member' => 'string',
  ),
  'RedisCluster::zinterstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dst' => 'string',
    'keys' => 'array',
    'weights=' => 'array|null',
    'aggregate=' => 'string|null',
  ),
  'RedisCluster::zintercard' => 
  array (
    0 => 'RedisCluster|int|false',
    'keys' => 'array',
    'limit=' => 'int',
  ),
  'RedisCluster::zlexcount' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'RedisCluster::zpopmax' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'value=' => 'int|null',
  ),
  'RedisCluster::zpopmin' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'value=' => 'int|null',
  ),
  'RedisCluster::zrange' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'start' => 'mixed|null',
    'end' => 'mixed|null',
    'options=' => 'array|bool|null|null',
  ),
  'RedisCluster::zrangestore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dstkey' => 'string',
    'srckey' => 'string',
    'start' => 'int',
    'end' => 'int',
    'options=' => 'array|bool|null|null',
  ),
  'RedisCluster::zrandmember' => 
  array (
    0 => 'RedisCluster|array|string',
    'key' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::zrangebylex' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
    'offset=' => 'int',
    'count=' => 'int',
  ),
  'RedisCluster::zrangebyscore' => 
  array (
    0 => 'RedisCluster|array|false',
    'key' => 'string',
    'start' => 'string',
    'end' => 'string',
    'options=' => 'array',
  ),
  'RedisCluster::zrank' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'RedisCluster::zrem' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'value' => 'string',
    '...other_values=' => 'string',
  ),
  'RedisCluster::zremrangebylex' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'RedisCluster::zremrangebyrank' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'RedisCluster::zremrangebyscore' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
  ),
  'RedisCluster::zrevrange' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::zrevrangebylex' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::zrevrangebyscore' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    'min' => 'string',
    'max' => 'string',
    'options=' => 'array|null',
  ),
  'RedisCluster::zrevrank' => 
  array (
    0 => 'RedisCluster|int|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'RedisCluster::zscan' => 
  array (
    0 => 'RedisCluster|array|bool',
    'key' => 'string',
    '&iterator' => 'string|int|null|null',
    'pattern=' => 'string|null',
    'count=' => 'int',
  ),
  'RedisCluster::zscore' => 
  array (
    0 => 'RedisCluster|float|false',
    'key' => 'string',
    'member' => 'mixed|null',
  ),
  'RedisCluster::zmscore' => 
  array (
    0 => 'Redis|array|false',
    'key' => 'string',
    'member' => 'mixed|null',
    '...other_members=' => 'mixed|null',
  ),
  'RedisCluster::zunionstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dst' => 'string',
    'keys' => 'array',
    'weights=' => 'array|null',
    'aggregate=' => 'string|null',
  ),
  'RedisCluster::zinter' => 
  array (
    0 => 'RedisCluster|array|false',
    'keys' => 'array',
    'weights=' => 'array|null',
    'options=' => 'array|null',
  ),
  'RedisCluster::zdiffstore' => 
  array (
    0 => 'RedisCluster|int|false',
    'dst' => 'string',
    'keys' => 'array',
  ),
  'RedisCluster::zunion' => 
  array (
    0 => 'RedisCluster|array|false',
    'keys' => 'array',
    'weights=' => 'array|null',
    'options=' => 'array|null',
  ),
  'RedisCluster::zdiff' => 
  array (
    0 => 'RedisCluster|array|false',
    'keys' => 'array',
    'options=' => 'array|null',
  ),
  'RedisClusterException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'RedisClusterException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'RedisClusterException::getMessage' => 
  array (
    0 => 'string',
  ),
  'RedisClusterException::getCode' => 
  array (
    0 => 'string',
  ),
  'RedisClusterException::getFile' => 
  array (
    0 => 'string',
  ),
  'RedisClusterException::getLine' => 
  array (
    0 => 'int',
  ),
  'RedisClusterException::getTrace' => 
  array (
    0 => 'array',
  ),
  'RedisClusterException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'RedisClusterException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'RedisClusterException::__toString' => 
  array (
    0 => 'string',
  ),
  'RedisSentinel::__construct' => 
  array (
    0 => 'string',
    'options=' => 'array|null',
  ),
  'RedisSentinel::ckquorum' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisSentinel::failover' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisSentinel::flushconfig' => 
  array (
    0 => 'string',
  ),
  'RedisSentinel::getMasterAddrByName' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisSentinel::master' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisSentinel::masters' => 
  array (
    0 => 'string',
  ),
  'RedisSentinel::myid' => 
  array (
    0 => 'string',
  ),
  'RedisSentinel::ping' => 
  array (
    0 => 'string',
  ),
  'RedisSentinel::reset' => 
  array (
    0 => 'string',
    'pattern' => 'string',
  ),
  'RedisSentinel::sentinels' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisSentinel::slaves' => 
  array (
    0 => 'string',
    'master' => 'string',
  ),
  'RedisException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'RedisException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'RedisException::getMessage' => 
  array (
    0 => 'string',
  ),
  'RedisException::getCode' => 
  array (
    0 => 'string',
  ),
  'RedisException::getFile' => 
  array (
    0 => 'string',
  ),
  'RedisException::getLine' => 
  array (
    0 => 'int',
  ),
  'RedisException::getTrace' => 
  array (
    0 => 'array',
  ),
  'RedisException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'RedisException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'RedisException::__toString' => 
  array (
    0 => 'string',
  ),
  'SodiumException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'SodiumException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'SodiumException::getMessage' => 
  array (
    0 => 'string',
  ),
  'SodiumException::getCode' => 
  array (
    0 => 'string',
  ),
  'SodiumException::getFile' => 
  array (
    0 => 'string',
  ),
  'SodiumException::getLine' => 
  array (
    0 => 'int',
  ),
  'SodiumException::getTrace' => 
  array (
    0 => 'array',
  ),
  'SodiumException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'SodiumException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'SodiumException::__toString' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::open' => 
  array (
    0 => 'string',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::setPassword' => 
  array (
    0 => 'string',
    'password' => 'string',
  ),
  'ZipArchive::close' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::count' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::getStatusString' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::addEmptyDir' => 
  array (
    0 => 'string',
    'dirname' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::addFromString' => 
  array (
    0 => 'string',
    'name' => 'string',
    'content' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::addFile' => 
  array (
    0 => 'string',
    'filepath' => 'string',
    'entryname=' => 'string',
    'start=' => 'int',
    'length=' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::replaceFile' => 
  array (
    0 => 'string',
    'filepath' => 'string',
    'index' => 'int',
    'start=' => 'int',
    'length=' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::addGlob' => 
  array (
    0 => 'string',
    'pattern' => 'string',
    'flags=' => 'int',
    'options=' => 'array',
  ),
  'ZipArchive::addPattern' => 
  array (
    0 => 'string',
    'pattern' => 'string',
    'path=' => 'string',
    'options=' => 'array',
  ),
  'ZipArchive::renameIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'new_name' => 'string',
  ),
  'ZipArchive::renameName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'new_name' => 'string',
  ),
  'ZipArchive::setArchiveComment' => 
  array (
    0 => 'string',
    'comment' => 'string',
  ),
  'ZipArchive::getArchiveComment' => 
  array (
    0 => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::setCommentIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'comment' => 'string',
  ),
  'ZipArchive::setCommentName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'comment' => 'string',
  ),
  'ZipArchive::setMtimeIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'timestamp' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::setMtimeName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'timestamp' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::getCommentIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::getCommentName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::deleteIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'ZipArchive::deleteName' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ZipArchive::statName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::statIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::locateName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::getNameIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::unchangeArchive' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::unchangeAll' => 
  array (
    0 => 'string',
  ),
  'ZipArchive::unchangeIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
  ),
  'ZipArchive::unchangeName' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ZipArchive::extractTo' => 
  array (
    0 => 'string',
    'pathto' => 'string',
    'files=' => 'array|string|null|null',
  ),
  'ZipArchive::getFromName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::getFromIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::getStream' => 
  array (
    0 => 'string',
    'name' => 'string',
  ),
  'ZipArchive::setExternalAttributesName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::setExternalAttributesIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
  ),
  'ZipArchive::getExternalAttributesName' => 
  array (
    0 => 'string',
    'name' => 'string',
    '&opsys' => 'string',
    '&attr' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::getExternalAttributesIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    '&opsys' => 'string',
    '&attr' => 'string',
    'flags=' => 'int',
  ),
  'ZipArchive::setCompressionName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ZipArchive::setCompressionIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ZipArchive::setEncryptionName' => 
  array (
    0 => 'string',
    'name' => 'string',
    'method' => 'int',
    'password=' => 'string|null',
  ),
  'ZipArchive::setEncryptionIndex' => 
  array (
    0 => 'string',
    'index' => 'int',
    'method' => 'int',
    'password=' => 'string|null',
  ),
  'ZipArchive::registerProgressCallback' => 
  array (
    0 => 'string',
    'rate' => 'float',
    'callback' => 'callable',
  ),
  'ZipArchive::registerCancelCallback' => 
  array (
    0 => 'string',
    'callback' => 'callable',
  ),
  'ZipArchive::isCompressionMethodSupported' => 
  array (
    0 => 'bool',
    'method' => 'int',
    'enc=' => 'bool',
  ),
  'ZipArchive::isEncryptionMethodSupported' => 
  array (
    0 => 'bool',
    'method' => 'int',
    'enc=' => 'bool',
  ),
  'ZMQ::__construct' => 
  array (
    0 => 'string',
  ),
  'ZMQ::clock' => 
  array (
    0 => 'string',
  ),
  'ZMQ::z85encode' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'ZMQ::z85decode' => 
  array (
    0 => 'string',
    'data' => 'string',
  ),
  'ZMQ::curvekeypair' => 
  array (
    0 => 'string',
  ),
  'ZMQContext::__construct' => 
  array (
    0 => 'string',
    'io_threads=' => 'string',
    'persistent=' => 'string',
  ),
  'ZMQContext::acquire' => 
  array (
    0 => 'string',
  ),
  'ZMQContext::getsocketcount' => 
  array (
    0 => 'string',
  ),
  'ZMQContext::getsocket' => 
  array (
    0 => 'string',
    'type' => 'string',
    'dsn' => 'string',
    'on_new_socket=' => 'string',
  ),
  'ZMQContext::ispersistent' => 
  array (
    0 => 'string',
  ),
  'ZMQContext::__clone' => 
  array (
    0 => 'string',
  ),
  'ZMQContext::setOpt' => 
  array (
    0 => 'string',
    'option' => 'string',
    'value' => 'string',
  ),
  'ZMQContext::getOpt' => 
  array (
    0 => 'string',
    'option' => 'string',
  ),
  'ZMQSocket::__construct' => 
  array (
    0 => 'string',
    'ZMQContext' => 'ZMQContext',
    'type' => 'string',
    'persistent_id=' => 'string',
    'on_new_socket=' => 'string',
  ),
  'ZMQSocket::send' => 
  array (
    0 => 'string',
    'message' => 'string',
    'mode=' => 'string',
  ),
  'ZMQSocket::recv' => 
  array (
    0 => 'string',
    'mode=' => 'string',
  ),
  'ZMQSocket::sendmulti' => 
  array (
    0 => 'string',
    'message' => 'string',
    'mode=' => 'string',
  ),
  'ZMQSocket::recvmulti' => 
  array (
    0 => 'string',
    'mode=' => 'string',
  ),
  'ZMQSocket::bind' => 
  array (
    0 => 'string',
    'dsn' => 'string',
    'force=' => 'string',
  ),
  'ZMQSocket::connect' => 
  array (
    0 => 'string',
    'dsn' => 'string',
    'force=' => 'string',
  ),
  'ZMQSocket::monitor' => 
  array (
    0 => 'string',
    'dsn' => 'string',
    'events=' => 'string',
  ),
  'ZMQSocket::recvevent' => 
  array (
    0 => 'string',
    'flags=' => 'string',
  ),
  'ZMQSocket::unbind' => 
  array (
    0 => 'string',
    'dsn' => 'string',
  ),
  'ZMQSocket::disconnect' => 
  array (
    0 => 'string',
    'dsn' => 'string',
  ),
  'ZMQSocket::setsockopt' => 
  array (
    0 => 'string',
    'key' => 'string',
    'value' => 'string',
  ),
  'ZMQSocket::getendpoints' => 
  array (
    0 => 'string',
  ),
  'ZMQSocket::getsockettype' => 
  array (
    0 => 'string',
  ),
  'ZMQSocket::ispersistent' => 
  array (
    0 => 'string',
  ),
  'ZMQSocket::getpersistentid' => 
  array (
    0 => 'string',
  ),
  'ZMQSocket::getsockopt' => 
  array (
    0 => 'string',
    'key' => 'string',
  ),
  'ZMQSocket::__clone' => 
  array (
    0 => 'string',
  ),
  'ZMQSocket::sendmsg' => 
  array (
    0 => 'string',
    'message' => 'string',
    'mode=' => 'string',
  ),
  'ZMQSocket::recvmsg' => 
  array (
    0 => 'string',
    'mode=' => 'string',
  ),
  'ZMQPoll::add' => 
  array (
    0 => 'string',
    'entry' => 'string',
    'type' => 'string',
  ),
  'ZMQPoll::poll' => 
  array (
    0 => 'string',
    '&readable' => 'string',
    '&writable' => 'string',
    'timeout=' => 'string',
  ),
  'ZMQPoll::getlasterrors' => 
  array (
    0 => 'string',
  ),
  'ZMQPoll::remove' => 
  array (
    0 => 'string',
    'remove' => 'string',
  ),
  'ZMQPoll::count' => 
  array (
    0 => 'string',
  ),
  'ZMQPoll::clear' => 
  array (
    0 => 'string',
  ),
  'ZMQPoll::items' => 
  array (
    0 => 'string',
  ),
  'ZMQPoll::__clone' => 
  array (
    0 => 'string',
  ),
  'ZMQDevice::__construct' => 
  array (
    0 => 'string',
    'frontend' => 'ZMQSocket',
    'backend' => 'ZMQSocket',
    'capture=' => 'ZMQSocket',
  ),
  'ZMQDevice::run' => 
  array (
    0 => 'string',
  ),
  'ZMQDevice::setidlecallback' => 
  array (
    0 => 'string',
    'idle_callback' => 'string',
    'timeout' => 'string',
    'user_data=' => 'string',
  ),
  'ZMQDevice::setidletimeout' => 
  array (
    0 => 'string',
    'timeout' => 'string',
  ),
  'ZMQDevice::getidletimeout' => 
  array (
    0 => 'string',
  ),
  'ZMQDevice::settimercallback' => 
  array (
    0 => 'string',
    'idle_callback' => 'string',
    'timeout' => 'string',
    'user_data=' => 'string',
  ),
  'ZMQDevice::settimertimeout' => 
  array (
    0 => 'string',
    'timeout' => 'string',
  ),
  'ZMQDevice::gettimertimeout' => 
  array (
    0 => 'string',
  ),
  'ZMQDevice::__clone' => 
  array (
    0 => 'string',
  ),
  'ZMQException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ZMQException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ZMQException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ZMQException::getCode' => 
  array (
    0 => 'string',
  ),
  'ZMQException::getFile' => 
  array (
    0 => 'string',
  ),
  'ZMQException::getLine' => 
  array (
    0 => 'int',
  ),
  'ZMQException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ZMQException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ZMQException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ZMQException::__toString' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ZMQContextException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::getCode' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::getFile' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::getLine' => 
  array (
    0 => 'int',
  ),
  'ZMQContextException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ZMQContextException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ZMQContextException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ZMQContextException::__toString' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ZMQSocketException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::getCode' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::getFile' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::getLine' => 
  array (
    0 => 'int',
  ),
  'ZMQSocketException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ZMQSocketException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ZMQSocketException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ZMQSocketException::__toString' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ZMQPollException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::getCode' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::getFile' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::getLine' => 
  array (
    0 => 'int',
  ),
  'ZMQPollException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ZMQPollException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ZMQPollException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ZMQPollException::__toString' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ZMQDeviceException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::getMessage' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::getCode' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::getFile' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::getLine' => 
  array (
    0 => 'int',
  ),
  'ZMQDeviceException::getTrace' => 
  array (
    0 => 'array',
  ),
  'ZMQDeviceException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ZMQDeviceException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'ZMQDeviceException::__toString' => 
  array (
    0 => 'string',
  ),
  'Event::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'fd' => 'mixed|null',
    'what' => 'int',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'Event::free' => 
  array (
    0 => 'void',
  ),
  'Event::set' => 
  array (
    0 => 'bool',
    'base' => 'EventBase',
    'fd' => 'mixed|null',
    'what=' => 'int',
    'cb=' => 'callable|null',
    'arg=' => 'mixed|null',
  ),
  'Event::getSupportedMethods' => 
  array (
    0 => 'array',
  ),
  'Event::add' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'Event::del' => 
  array (
    0 => 'bool',
  ),
  'Event::setPriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'Event::pending' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'Event::removeTimer' => 
  array (
    0 => 'bool',
  ),
  'Event::timer' => 
  array (
    0 => 'Event',
    'base' => 'EventBase',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'Event::setTimer' => 
  array (
    0 => 'bool',
    'base' => 'EventBase',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'Event::signal' => 
  array (
    0 => 'Event',
    'base' => 'EventBase',
    'signum' => 'int',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'Event::addTimer' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'Event::delTimer' => 
  array (
    0 => 'bool',
  ),
  'Event::addSignal' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'Event::delSignal' => 
  array (
    0 => 'bool',
  ),
  'EventBase::__construct' => 
  array (
    0 => 'string',
    'cfg=' => 'EventConfig|null',
  ),
  'EventBase::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventBase::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventBase::getMethod' => 
  array (
    0 => 'string',
  ),
  'EventBase::getFeatures' => 
  array (
    0 => 'int',
  ),
  'EventBase::priorityInit' => 
  array (
    0 => 'bool',
    'n_priorities' => 'int',
  ),
  'EventBase::loop' => 
  array (
    0 => 'bool',
    'flags=' => 'int',
  ),
  'EventBase::dispatch' => 
  array (
    0 => 'bool',
  ),
  'EventBase::exit' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'EventBase::set' => 
  array (
    0 => 'bool',
    'event' => 'Event',
  ),
  'EventBase::stop' => 
  array (
    0 => 'bool',
  ),
  'EventBase::gotStop' => 
  array (
    0 => 'bool',
  ),
  'EventBase::gotExit' => 
  array (
    0 => 'bool',
  ),
  'EventBase::getTimeOfDayCached' => 
  array (
    0 => 'float',
  ),
  'EventBase::reInit' => 
  array (
    0 => 'bool',
  ),
  'EventBase::free' => 
  array (
    0 => 'void',
  ),
  'EventBase::updateCacheTime' => 
  array (
    0 => 'bool',
  ),
  'EventBase::resume' => 
  array (
    0 => 'bool',
  ),
  'EventConfig::__construct' => 
  array (
    0 => 'string',
  ),
  'EventConfig::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventConfig::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventConfig::avoidMethod' => 
  array (
    0 => 'bool',
    'method' => 'string',
  ),
  'EventConfig::requireFeatures' => 
  array (
    0 => 'bool',
    'feature' => 'int',
  ),
  'EventConfig::setMaxDispatchInterval' => 
  array (
    0 => 'void',
    'max_interval' => 'int',
    'max_callbacks' => 'int',
    'min_priority' => 'int',
  ),
  'EventConfig::setFlags' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'EventBufferEvent::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'socket=' => 'mixed|null',
    'options=' => 'int',
    'readcb=' => 'callable|null',
    'writecb=' => 'callable|null',
    'eventcb=' => 'callable|null',
    'arg=' => 'mixed|null',
  ),
  'EventBufferEvent::free' => 
  array (
    0 => 'void',
  ),
  'EventBufferEvent::close' => 
  array (
    0 => 'void',
  ),
  'EventBufferEvent::connect' => 
  array (
    0 => 'bool',
    'addr' => 'string',
  ),
  'EventBufferEvent::connectHost' => 
  array (
    0 => 'bool',
    'dns_base' => 'EventDnsBase|null',
    'hostname' => 'string',
    'port' => 'int',
    'family=' => 'int',
  ),
  'EventBufferEvent::getDnsErrorString' => 
  array (
    0 => 'string',
  ),
  'EventBufferEvent::setCallbacks' => 
  array (
    0 => 'void',
    'readcb' => 'callable|null',
    'writecb' => 'callable|null',
    'eventcb' => 'callable|null',
    'arg=' => 'mixed|null',
  ),
  'EventBufferEvent::enable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'EventBufferEvent::disable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'EventBufferEvent::getEnabled' => 
  array (
    0 => 'int',
  ),
  'EventBufferEvent::getInput' => 
  array (
    0 => 'EventBuffer',
  ),
  'EventBufferEvent::getOutput' => 
  array (
    0 => 'EventBuffer',
  ),
  'EventBufferEvent::setWatermark' => 
  array (
    0 => 'void',
    'events' => 'int',
    'lowmark' => 'int',
    'highmark' => 'int',
  ),
  'EventBufferEvent::write' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'EventBufferEvent::writeBuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'EventBufferEvent::read' => 
  array (
    0 => 'string|null',
    'size' => 'int',
  ),
  'EventBufferEvent::readBuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'EventBufferEvent::createPair' => 
  array (
    0 => 'array|false',
    'base' => 'EventBase',
    'options=' => 'int',
  ),
  'EventBufferEvent::setPriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'EventBufferEvent::setTimeouts' => 
  array (
    0 => 'bool',
    'timeout_read' => 'float',
    'timeout_write' => 'float',
  ),
  'EventBufferEvent::createSslFilter' => 
  array (
    0 => 'EventBufferEvent',
    'unnderlying' => 'EventBufferEvent',
    'ctx' => 'EventSslContext',
    'state' => 'int',
    'options=' => 'int',
  ),
  'EventBufferEvent::sslSocket' => 
  array (
    0 => 'EventBufferEvent',
    'base' => 'EventBase',
    'socket' => 'mixed|null',
    'ctx' => 'EventSslContext',
    'state' => 'int',
    'options=' => 'int',
  ),
  'EventBufferEvent::sslError' => 
  array (
    0 => 'string',
  ),
  'EventBufferEvent::sslRenegotiate' => 
  array (
    0 => 'void',
  ),
  'EventBufferEvent::sslGetCipherInfo' => 
  array (
    0 => 'string',
  ),
  'EventBufferEvent::sslGetCipherName' => 
  array (
    0 => 'string',
  ),
  'EventBufferEvent::sslGetCipherVersion' => 
  array (
    0 => 'string',
  ),
  'EventBufferEvent::sslGetProtocol' => 
  array (
    0 => 'string',
  ),
  'EventBuffer::__construct' => 
  array (
    0 => 'string',
  ),
  'EventBuffer::freeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'EventBuffer::unfreeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'EventBuffer::lock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
  ),
  'EventBuffer::unlock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
  ),
  'EventBuffer::enableLocking' => 
  array (
    0 => 'void',
  ),
  'EventBuffer::add' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'EventBuffer::read' => 
  array (
    0 => 'string',
    'max_bytes' => 'int',
  ),
  'EventBuffer::addBuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'EventBuffer::appendFrom' => 
  array (
    0 => 'int',
    'buf' => 'EventBuffer',
    'len' => 'int',
  ),
  'EventBuffer::expand' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'EventBuffer::prepend' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'EventBuffer::prependBuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'EventBuffer::drain' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'EventBuffer::copyout' => 
  array (
    0 => 'int',
    '&data' => 'string',
    'max_bytes' => 'int',
  ),
  'EventBuffer::readLine' => 
  array (
    0 => 'string|null',
    'eol_style' => 'int',
  ),
  'EventBuffer::search' => 
  array (
    0 => 'int|false',
    'what' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'EventBuffer::searchEol' => 
  array (
    0 => 'int|false',
    'start=' => 'int',
    'eol_style=' => 'int',
  ),
  'EventBuffer::pullup' => 
  array (
    0 => 'string|null',
    'size' => 'int',
  ),
  'EventBuffer::write' => 
  array (
    0 => 'int|false',
    'fd' => 'mixed|null',
    'howmuch=' => 'int',
  ),
  'EventBuffer::readFrom' => 
  array (
    0 => 'int|false',
    'fd' => 'mixed|null',
    'howmuch=' => 'int',
  ),
  'EventBuffer::substr' => 
  array (
    0 => 'string|false',
    'start' => 'int',
    'length=' => 'int',
  ),
  'EventDnsBase::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'initialize' => 'mixed|null',
  ),
  'EventDnsBase::parseResolvConf' => 
  array (
    0 => 'bool',
    'flags' => 'int',
    'filename' => 'string',
  ),
  'EventDnsBase::addNameserverIp' => 
  array (
    0 => 'bool',
    'ip' => 'string',
  ),
  'EventDnsBase::loadHosts' => 
  array (
    0 => 'bool',
    'hosts' => 'string',
  ),
  'EventDnsBase::clearSearch' => 
  array (
    0 => 'void',
  ),
  'EventDnsBase::addSearch' => 
  array (
    0 => 'void',
    'domain' => 'string',
  ),
  'EventDnsBase::setSearchNdots' => 
  array (
    0 => 'void',
    'ndots' => 'int',
  ),
  'EventDnsBase::setOption' => 
  array (
    0 => 'bool',
    'option' => 'string',
    'value' => 'string',
  ),
  'EventDnsBase::countNameservers' => 
  array (
    0 => 'int',
  ),
  'EventListener::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'cb' => 'callable',
    'data' => 'mixed|null',
    'flags' => 'int',
    'backlog' => 'int',
    'target' => 'mixed|null',
  ),
  'EventListener::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventListener::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventListener::free' => 
  array (
    0 => 'void',
  ),
  'EventListener::enable' => 
  array (
    0 => 'bool',
  ),
  'EventListener::disable' => 
  array (
    0 => 'bool',
  ),
  'EventListener::setCallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'EventListener::setErrorCallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
  ),
  'EventListener::getBase' => 
  array (
    0 => 'EventBase',
  ),
  'EventListener::getSocketName' => 
  array (
    0 => 'bool',
    '&address' => 'mixed|null',
    '&port' => 'mixed|null',
  ),
  'EventHttpConnection::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'dns_base' => 'EventDnsBase|null',
    'address' => 'string',
    'port' => 'int',
    'ctx=' => 'EventSslContext|null',
  ),
  'EventHttpConnection::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventHttpConnection::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventHttpConnection::getBase' => 
  array (
    0 => 'EventBase|false',
  ),
  'EventHttpConnection::getPeer' => 
  array (
    0 => 'void',
    '&address' => 'mixed|null',
    '&port' => 'mixed|null',
  ),
  'EventHttpConnection::setLocalAddress' => 
  array (
    0 => 'void',
    'address' => 'string',
  ),
  'EventHttpConnection::setLocalPort' => 
  array (
    0 => 'void',
    'port' => 'int',
  ),
  'EventHttpConnection::setTimeout' => 
  array (
    0 => 'void',
    'timeout' => 'int',
  ),
  'EventHttpConnection::setMaxHeadersSize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'EventHttpConnection::setMaxBodySize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'EventHttpConnection::setRetries' => 
  array (
    0 => 'void',
    'retries' => 'int',
  ),
  'EventHttpConnection::makeRequest' => 
  array (
    0 => 'bool|null',
    'req' => 'EventHttpRequest',
    'type' => 'int',
    'uri' => 'string',
  ),
  'EventHttpConnection::setCloseCallback' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    'data=' => 'mixed|null',
  ),
  'EventHttp::__construct' => 
  array (
    0 => 'string',
    'base' => 'EventBase',
    'ctx=' => 'EventSslContext|null',
  ),
  'EventHttp::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventHttp::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventHttp::accept' => 
  array (
    0 => 'bool',
    'socket' => 'mixed|null',
  ),
  'EventHttp::bind' => 
  array (
    0 => 'bool',
    'address' => 'string',
    'port' => 'int',
  ),
  'EventHttp::setCallback' => 
  array (
    0 => 'bool',
    'path' => 'string',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'EventHttp::setDefaultCallback' => 
  array (
    0 => 'void',
    'cb' => 'callable',
    'arg=' => 'mixed|null',
  ),
  'EventHttp::setAllowedMethods' => 
  array (
    0 => 'void',
    'methods' => 'int',
  ),
  'EventHttp::setMaxBodySize' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'EventHttp::setMaxHeadersSize' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'EventHttp::setTimeout' => 
  array (
    0 => 'void',
    'value' => 'int',
  ),
  'EventHttp::addServerAlias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'EventHttp::removeServerAlias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'EventHttpRequest::__construct' => 
  array (
    0 => 'string',
    'callback' => 'callable',
    'data=' => 'mixed|null',
  ),
  'EventHttpRequest::__sleep' => 
  array (
    0 => 'array',
  ),
  'EventHttpRequest::__wakeup' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::free' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::getCommand' => 
  array (
    0 => 'int',
  ),
  'EventHttpRequest::getHost' => 
  array (
    0 => 'string',
  ),
  'EventHttpRequest::getUri' => 
  array (
    0 => 'string',
  ),
  'EventHttpRequest::getResponseCode' => 
  array (
    0 => 'int',
  ),
  'EventHttpRequest::getInputHeaders' => 
  array (
    0 => 'array',
  ),
  'EventHttpRequest::getOutputHeaders' => 
  array (
    0 => 'array',
  ),
  'EventHttpRequest::getInputBuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'EventHttpRequest::getOutputBuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'EventHttpRequest::getBufferEvent' => 
  array (
    0 => 'EventBufferEvent|null',
  ),
  'EventHttpRequest::getConnection' => 
  array (
    0 => 'EventHttpConnection|null',
  ),
  'EventHttpRequest::closeConnection' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::sendError' => 
  array (
    0 => 'void',
    'error' => 'int',
    'reason=' => 'string|null',
  ),
  'EventHttpRequest::sendReply' => 
  array (
    0 => 'void',
    'code' => 'int',
    'reason' => 'string',
    'buf=' => 'EventBuffer|null',
  ),
  'EventHttpRequest::sendReplyChunk' => 
  array (
    0 => 'void',
    'buf' => 'EventBuffer',
  ),
  'EventHttpRequest::sendReplyEnd' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::sendReplyStart' => 
  array (
    0 => 'void',
    'code' => 'int',
    'reason' => 'string',
  ),
  'EventHttpRequest::cancel' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::addHeader' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'value' => 'string',
    'type' => 'int',
  ),
  'EventHttpRequest::clearHeaders' => 
  array (
    0 => 'void',
  ),
  'EventHttpRequest::removeHeader' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'type' => 'int',
  ),
  'EventHttpRequest::findHeader' => 
  array (
    0 => 'string|null',
    'key' => 'string',
    'type' => 'int',
  ),
  'EventUtil::__construct' => 
  array (
    0 => 'string',
  ),
  'EventUtil::getLastSocketErrno' => 
  array (
    0 => 'int|false',
    'socket=' => 'Socket|null',
  ),
  'EventUtil::getLastSocketError' => 
  array (
    0 => 'string|false',
    'socket=' => 'mixed|null',
  ),
  'EventUtil::sslRandPoll' => 
  array (
    0 => 'bool',
  ),
  'EventUtil::getSocketName' => 
  array (
    0 => 'bool',
    'socket' => 'mixed|null',
    '&address' => 'mixed|null',
    '&port=' => 'mixed|null',
  ),
  'EventUtil::getSocketFd' => 
  array (
    0 => 'int',
    'socket' => 'mixed|null',
  ),
  'EventUtil::setSocketOption' => 
  array (
    0 => 'bool',
    'socket' => 'mixed|null',
    'level' => 'int',
    'optname' => 'int',
    'optval' => 'mixed|null',
  ),
  'EventSslContext::__construct' => 
  array (
    0 => 'string',
    'method' => 'int',
    'options' => 'array',
  ),
  'EventSslContext::setMinProtoVersion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'EventSslContext::setMaxProtoVersion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'EventException::__construct' => 
  array (
    0 => 'string',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'EventException::__wakeup' => 
  array (
    0 => 'string',
  ),
  'EventException::getMessage' => 
  array (
    0 => 'string',
  ),
  'EventException::getCode' => 
  array (
    0 => 'string',
  ),
  'EventException::getFile' => 
  array (
    0 => 'string',
  ),
  'EventException::getLine' => 
  array (
    0 => 'int',
  ),
  'EventException::getTrace' => 
  array (
    0 => 'array',
  ),
  'EventException::getPrevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'EventException::getTraceAsString' => 
  array (
    0 => 'string',
  ),
  'EventException::__toString' => 
  array (
    0 => 'string',
  ),
);