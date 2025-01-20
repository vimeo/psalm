<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'Closure::fromCallable' => 
    array (
      0 => 'Closure',
      'callback' => 'callable',
    ),
    'curl_multi_errno' => 
    array (
      0 => 'false|int',
      'mh' => 'resource',
    ),
    'curl_share_errno' => 
    array (
      0 => 'false|int',
      'sh' => 'resource',
    ),
    'curl_share_strerror' => 
    array (
      0 => 'null|string',
      'error_code' => 'int',
    ),
    'getenv\'1' => 
    array (
      0 => 'array<string, string>',
    ),
    'hash_hkdf' => 
    array (
      0 => 'false|non-empty-string',
      'algo' => 'string',
      'key' => 'string',
      'length=' => 'int',
      'info=' => 'string',
      'salt=' => 'string',
    ),
    'is_iterable' => 
    array (
      0 => 'bool',
      'value' => 'mixed',
    ),
    'openssl_get_curve_names' => 
    array (
      0 => 'list<string>',
    ),
    'pcntl_async_signals' => 
    array (
      0 => 'bool',
      'enable=' => 'bool',
    ),
    'pcntl_signal_get_handler' => 
    array (
      0 => 'int|string',
      'signal' => 'int',
    ),
    'sapi_windows_cp_conv' => 
    array (
      0 => 'null|string',
      'in_codepage' => 'int|string',
      'out_codepage' => 'int|string',
      'subject' => 'string',
    ),
    'sapi_windows_cp_get' => 
    array (
      0 => 'int',
      'kind=' => 'string',
    ),
    'sapi_windows_cp_is_utf8' => 
    array (
      0 => 'bool',
    ),
    'sapi_windows_cp_set' => 
    array (
      0 => 'bool',
      'codepage' => 'int',
    ),
    'session_create_id' => 
    array (
      0 => 'false|string',
      'prefix=' => 'string',
    ),
    'session_gc' => 
    array (
      0 => 'false|int',
    ),
  ),
  'changed' => 
  array (
    'DateTimeZone::listIdentifiers' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
    ),
    'IntlDateFormatter::format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'value' => 'DateTime|IntlCalendar|array{0?: int, 1?: int, 2?: int, 3?: int, 4?: int, 5?: int, 6?: int, 7?: int, 8?: int, tm_hour?: int, tm_isdst?: int, tm_mday?: int, tm_min?: int, tm_mon?: int, tm_sec?: int, tm_wday?: int, tm_yday?: int, tm_year?: int}|float|int|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'value' => 'DateTimeInterface|IntlCalendar|array{0?: int, 1?: int, 2?: int, 3?: int, 4?: int, 5?: int, 6?: int, 7?: int, 8?: int, tm_hour?: int, tm_isdst?: int, tm_mday?: int, tm_min?: int, tm_mon?: int, tm_sec?: int, tm_wday?: int, tm_yday?: int, tm_year?: int}|float|int|string',
      ),
    ),
    'SessionHandler::gc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'max_lifetime' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'max_lifetime' => 'int',
      ),
    ),
    'SQLite3::createFunction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'callback' => 'callable',
        'argCount=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'callback' => 'callable',
        'argCount=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'get_headers' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'url' => 'string',
        'associative=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'url' => 'string',
        'associative=' => 'int',
        'context=' => 'null|resource',
      ),
    ),
    'getopt' => 
    array (
      'old' => 
      array (
        0 => 'array<string, false|list<false|string>|string>|false',
        'short_options' => 'string',
        'long_options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<string, false|list<false|string>|string>|false',
        'short_options' => 'string',
        'long_options=' => 'array<array-key, mixed>',
        '&w_rest_index=' => 'int',
      ),
    ),
    'pg_fetch_all' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'resource',
        'mode=' => 'int',
      ),
    ),
    'pg_select' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'timezone_identifiers_list' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
    ),
    'unpack' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'format' => 'string',
        'string' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'format' => 'string',
        'string' => 'string',
        'offset=' => 'int',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);