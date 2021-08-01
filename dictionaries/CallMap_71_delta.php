<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.1 to php 7.0 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.0 or have different signatures in php 7.1.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.0.
 *   Functions are expected to be removed only in major releases of php. (e.g. php 7.0 removed various functions that were deprecated in 5.6)
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
'new' => [
    'Closure::fromCallable' => ['Closure', 'callable'=>'callable'],
    'DateTimeZone::listIdentifiers' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
    'SQLite3::createFunction' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int', 'flags='=>'int'],
    'curl_multi_errno' => ['int|false', 'mh'=>'resource'],
    'curl_share_errno' => ['int|false', 'sh'=>'resource'],
    'curl_share_strerror' => ['?string', 'error_code'=>'int'],
    'get_headers' => ['array|false', 'url'=>'string', 'associative='=>'int', 'context='=>'resource'],
    'getenv\'1' => ['array<string,string>'],
    'getopt' => ['array<string,string>|array<string,false>|array<string,list<mixed>>|false', 'short_options'=>'string', 'long_options='=>'array', '&w_rest_index='=>'int'],
    'hash_hkdf' => ['string|false', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    'is_iterable' => ['bool', 'value'=>'mixed'],
    'openssl_get_curve_names' => ['list<string>'],
    'pcntl_async_signals' => ['bool', 'enable='=>'bool'],
    'pcntl_signal_get_handler' => ['int|string', 'signal'=>'int'],
    'pg_fetch_all' => ['array<array>|false', 'result'=>'resource', 'result_type='=>'int'],
    'pg_last_error' => ['string', 'connection='=>'resource', 'operation='=>'int'],
    'pg_select' => ['bool|string', 'connection'=>'resource', 'table_name'=>'string', 'assoc_array'=>'array', 'options='=>'int', 'result_type='=>'int'],
    'sapi_windows_cp_conv' => ['string', 'in_codepage'=>'int|string', 'out_codepage'=>'int|string', 'subject'=>'string'],
    'sapi_windows_cp_get' => ['int'],
    'sapi_windows_cp_is_utf8' => ['bool'],
    'sapi_windows_cp_set' => ['bool', 'codepage'=>'int'],
    'session_create_id' => ['string', 'prefix='=>'string'],
    'session_gc' => ['int|false'],
    'timezone_identifiers_list' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
    'unpack' => ['array|false', 'format'=>'string', 'string'=>'string', 'offset='=>'int'],
],
'old' => [
    'DateTimeZone::listIdentifiers' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
    'SQLite3::createFunction' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int'],
    'get_headers' => ['array|false', 'url'=>'string', 'associative='=>'int'],
    'getopt' => ['array<string,string>|array<string,false>|array<string,list<mixed>>|false', 'short_options'=>'string', 'long_options='=>'array'],
    'pg_fetch_all' => ['array<array>|false', 'result'=>'resource'],
    'pg_last_error' => ['string', 'connection='=>'resource'],
    'pg_select' => ['bool|string', 'connection'=>'resource', 'table_name'=>'string', 'assoc_array'=>'array', 'options='=>'int'],
    'timezone_identifiers_list' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
    'unpack' => ['array', 'format'=>'string', 'string'=>'string'],
],
];
