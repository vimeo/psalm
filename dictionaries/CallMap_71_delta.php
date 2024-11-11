<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.1 to php 7.0 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that did not exist prior to PHP 7.1
 * The 'removed' section contains the signatures that were removed in php 7.1.
 * The 'changed' section contains functions for which the signature has changed for php 7.1.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP before 7.1 and in PHP 7.1, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'Closure::fromCallable' => ['Closure', 'callback'=>'callable'],
    'curl_multi_errno' => ['int<0, 12>|false', 'mh'=>'resource'],
    'curl_share_errno' => ['0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90|false', 'sh'=>'resource'],
    'curl_share_strerror' => ['?string', 'error_code'=>'0|1|2|3|4|5|6|7|8|9|10|11|12|13|14|15|16|17|18|19|20|21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|52|53|54|55|56|57|58|59|60|61|62|63|64|77|79|90'],
    'getenv\'1' => ['array<string,string>'],
    'hash_hkdf' => ['non-empty-string|false', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    'is_iterable' => ['bool', 'value'=>'mixed'],
    'openssl_get_curve_names' => ['list<string>'],
    'pcntl_async_signals' => ['bool', 'enable='=>'bool'],
    'pcntl_signal_get_handler' => ['int|string', 'signal'=>'int'],
    'sapi_windows_cp_conv' => ['?string', 'in_codepage'=>'int|string', 'out_codepage'=>'int|string', 'subject'=>'string'],
    'sapi_windows_cp_get' => ['int', 'kind='=>'string'],
    'sapi_windows_cp_is_utf8' => ['bool'],
    'sapi_windows_cp_set' => ['bool', 'codepage'=>'int'],
    'session_create_id' => ['string|false', 'prefix='=>'string'],
    'session_gc' => ['int|false'],
  ],
  'changed' => [
    'DateTimeZone::listIdentifiers' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
      'new' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
    ],
    'IntlDateFormatter::format' => [
        'old' => ['string|false', 'value'=>'IntlCalendar|DateTime|array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int}|array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}|string|int|float'],
        'new' => ['string|false', 'value'=>'IntlCalendar|DateTimeInterface|array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int}|array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}|string|int|float'],
    ],
    'SessionHandler::gc' => [
      'old' => ['bool', 'max_lifetime'=>'int'],
      'new' => ['int|false', 'max_lifetime'=>'int'],
    ],
    'SQLite3::createFunction' => [
      'old' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int'],
      'new' => ['bool', 'name'=>'string', 'callback'=>'callable', 'argCount='=>'int', 'flags='=>'int'],
    ],
    'curl_multi_setopt' => [
        'old' => ['bool', 'mh'=>'resource', 'option'=>'3|6', 'value'=>'int<0, max>'],
        'new' => ['bool', 'mh'=>'resource', 'option'=>'3|6|7|8|13|16|20014|30009|30010', 'value'=>'int<0, max>|callable'],
    ],
    'get_headers' => [
      'old' => ['array|false', 'url'=>'string', 'associative='=>'int'],
      'new' => ['array|false', 'url'=>'string', 'associative='=>'int', 'context='=>'?resource'],
    ],
    'getopt' => [
      'old' => ['array<string,string|false|list<string|false>>|false', 'short_options'=>'string', 'long_options='=>'array'],
      'new' => ['array<string,string|false|list<string|false>>|false', 'short_options'=>'string', 'long_options='=>'array', '&w_rest_index='=>'int'],
    ],
    'pg_fetch_all' => [
      'old' => ['array<array>', 'result'=>'resource'],
      'new' => ['array<array>', 'result'=>'resource', 'mode='=>'int'],
    ],
    'pg_select' => [
      'old' => ['string|array|false', 'connection'=>'resource', 'table_name'=>'string', 'conditions'=>'array', 'flags='=>'int'],
      'new' => ['string|array|false', 'connection'=>'resource', 'table_name'=>'string', 'conditions'=>'array', 'flags='=>'int', 'mode='=>'int'],
    ],
    'timezone_identifiers_list' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string'],
      'new' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
    ],
    'unpack' => [
      'old' => ['array', 'format'=>'string', 'string'=>'string'],
      'new' => ['array|false', 'format'=>'string', 'string'=>'string', 'offset='=>'int'],
    ],
  ],
  'removed' => [
  ],
];
