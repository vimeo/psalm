<?php // phpcs:ignoreFile

return array (
  'abs' => 
  array (
    0 => 'float|int',
    'num' => 'float|int',
  ),
  'acos' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'acosh' => 
  array (
    0 => 'float',
    'num' => 'float',
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
  'allowdynamicproperties::__construct' => 
  array (
    0 => 'void',
  ),
  'apcu_add' => 
  array (
    0 => 'array<array-key, mixed>|bool',
    'key' => 'mixed',
    'value=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_cache_info' => 
  array (
    0 => 'array<array-key, mixed>|false',
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
    '&success=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcu_delete' => 
  array (
    0 => 'array<array-key, mixed>|bool',
    'key' => 'mixed',
  ),
  'apcu_enabled' => 
  array (
    0 => 'bool',
  ),
  'apcu_entry' => 
  array (
    0 => 'mixed',
    'key' => 'string',
    'callback' => 'callable',
    'ttl=' => 'int',
  ),
  'apcu_exists' => 
  array (
    0 => 'array<array-key, mixed>|bool',
    'key' => 'mixed',
  ),
  'apcu_fetch' => 
  array (
    0 => 'mixed',
    'key' => 'mixed',
    '&success=' => 'mixed',
  ),
  'apcu_inc' => 
  array (
    0 => 'false|int',
    'key' => 'string',
    'step=' => 'int',
    '&success=' => 'mixed',
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
    0 => 'array<array-key, mixed>|bool',
    'key' => 'mixed',
    'value=' => 'mixed',
    'ttl=' => 'int',
  ),
  'apcuiterator::__construct' => 
  array (
    0 => 'void',
    'search=' => 'mixed',
    'format=' => 'int',
    'chunk_size=' => 'int',
    'list=' => 'int',
  ),
  'apcuiterator::current' => 
  array (
    0 => 'mixed',
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
    0 => 'int|string',
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
  'appenditerator::__construct' => 
  array (
    0 => 'void',
  ),
  'appenditerator::append' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'appenditerator::current' => 
  array (
    0 => 'mixed',
  ),
  'appenditerator::getarrayiterator' => 
  array (
    0 => 'ArrayIterator',
  ),
  'appenditerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'appenditerator::getiteratorindex' => 
  array (
    0 => 'int|null',
  ),
  'appenditerator::key' => 
  array (
    0 => 'mixed',
  ),
  'appenditerator::next' => 
  array (
    0 => 'void',
  ),
  'appenditerator::rewind' => 
  array (
    0 => 'void',
  ),
  'appenditerator::valid' => 
  array (
    0 => 'bool',
  ),
  'argumentcounterror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'argumentcounterror::__tostring' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::__wakeup' => 
  array (
    0 => 'void',
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
  'argumentcounterror::getmessage' => 
  array (
    0 => 'string',
  ),
  'argumentcounterror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'argumentcounterror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'argumentcounterror::gettraceasstring' => 
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
  'arithmeticerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::__wakeup' => 
  array (
    0 => 'void',
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
  'arithmeticerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'arithmeticerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'arithmeticerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arithmeticerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'array_all' => 
  array (
    0 => 'bool',
    'array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'array_any' => 
  array (
    0 => 'bool',
    'array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'array_change_key_case' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'case=' => 'int',
  ),
  'array_chunk' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'length' => 'int',
    'preserve_keys=' => 'bool',
  ),
  'array_column' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'column_key' => 'int|null|string',
    'index_key=' => 'int|null|string',
  ),
  'array_combine' => 
  array (
    0 => 'array<array-key, mixed>',
    'keys' => 'array<array-key, mixed>',
    'values' => 'array<array-key, mixed>',
  ),
  'array_count_values' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
  ),
  'array_diff' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_diff_assoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_diff_key' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_diff_uassoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_diff_ukey' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_fill' => 
  array (
    0 => 'array<array-key, mixed>',
    'start_index' => 'int',
    'count' => 'int',
    'value' => 'mixed',
  ),
  'array_fill_keys' => 
  array (
    0 => 'array<array-key, mixed>',
    'keys' => 'array<array-key, mixed>',
    'value' => 'mixed',
  ),
  'array_filter' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'callback=' => 'callable|null',
    'mode=' => 'int',
  ),
  'array_find' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'array_find_key' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'array_first' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>',
  ),
  'array_flip' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
  ),
  'array_intersect' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_intersect_assoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_intersect_key' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_intersect_uassoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_intersect_ukey' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_is_list' => 
  array (
    0 => 'bool',
    'array' => 'array<array-key, mixed>',
  ),
  'array_key_exists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'array' => 'array<array-key, mixed>',
  ),
  'array_key_first' => 
  array (
    0 => 'int|null|string',
    'array' => 'array<array-key, mixed>',
  ),
  'array_key_last' => 
  array (
    0 => 'int|null|string',
    'array' => 'array<array-key, mixed>',
  ),
  'array_keys' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'filter_value=' => 'mixed',
    'strict=' => 'bool',
  ),
  'array_last' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>',
  ),
  'array_map' => 
  array (
    0 => 'array<array-key, mixed>',
    'callback' => 'callable|null',
    'array' => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_merge' => 
  array (
    0 => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_merge_recursive' => 
  array (
    0 => 'array<array-key, mixed>',
    '...arrays=' => 'array<array-key, mixed>',
  ),
  'array_multisort' => 
  array (
    0 => 'true',
    '&array' => 'mixed',
    '&...rest=' => 'mixed',
  ),
  'array_pad' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'length' => 'int',
    'value' => 'mixed',
  ),
  'array_pop' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>',
  ),
  'array_product' => 
  array (
    0 => 'float|int',
    'array' => 'array<array-key, mixed>',
  ),
  'array_push' => 
  array (
    0 => 'int',
    '&array' => 'array<array-key, mixed>',
    '...values=' => 'mixed',
  ),
  'array_rand' => 
  array (
    0 => 'array<array-key, mixed>|int|string',
    'array' => 'array<array-key, mixed>',
    'num=' => 'int',
  ),
  'array_reduce' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>',
    'callback' => 'callable',
    'initial=' => 'mixed',
  ),
  'array_replace' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...replacements=' => 'array<array-key, mixed>',
  ),
  'array_replace_recursive' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...replacements=' => 'array<array-key, mixed>',
  ),
  'array_reverse' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'preserve_keys=' => 'bool',
  ),
  'array_search' => 
  array (
    0 => 'false|int|string',
    'needle' => 'mixed',
    'haystack' => 'array<array-key, mixed>',
    'strict=' => 'bool',
  ),
  'array_shift' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>',
  ),
  'array_slice' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'offset' => 'int',
    'length=' => 'int|null',
    'preserve_keys=' => 'bool',
  ),
  'array_splice' => 
  array (
    0 => 'array<array-key, mixed>',
    '&array' => 'array<array-key, mixed>',
    'offset' => 'int',
    'length=' => 'int|null',
    'replacement=' => 'mixed',
  ),
  'array_sum' => 
  array (
    0 => 'float|int',
    'array' => 'array<array-key, mixed>',
  ),
  'array_udiff' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_udiff_assoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_udiff_uassoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_uintersect' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_uintersect_assoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_uintersect_uassoc' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    '...rest=' => 'mixed',
  ),
  'array_unique' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'array_unshift' => 
  array (
    0 => 'int',
    '&array' => 'array<array-key, mixed>',
    '...values=' => 'mixed',
  ),
  'array_values' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
  ),
  'array_walk' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>|object',
    'callback' => 'callable',
    'arg=' => 'mixed',
  ),
  'array_walk_recursive' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>|object',
    'callback' => 'callable',
    'arg=' => 'mixed',
  ),
  'arrayiterator::__construct' => 
  array (
    0 => 'void',
    'array=' => 'array<array-key, mixed>|object',
    'flags=' => 'int',
  ),
  'arrayiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayiterator::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayiterator::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'arrayiterator::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'arrayiterator::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayiterator::count' => 
  array (
    0 => 'int',
  ),
  'arrayiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'arrayiterator::getarraycopy' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'arrayiterator::key' => 
  array (
    0 => 'int|null|string',
  ),
  'arrayiterator::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayiterator::natcasesort' => 
  array (
    0 => 'true',
  ),
  'arrayiterator::natsort' => 
  array (
    0 => 'true',
  ),
  'arrayiterator::next' => 
  array (
    0 => 'void',
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
  'arrayiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'arrayiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'arrayiterator::serialize' => 
  array (
    0 => 'string',
  ),
  'arrayiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
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
  'arrayiterator::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'arrayiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'arrayobject::__construct' => 
  array (
    0 => 'void',
    'array=' => 'array<array-key, mixed>|object',
    'flags=' => 'int',
    'iteratorClass=' => 'string',
  ),
  'arrayobject::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayobject::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayobject::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'arrayobject::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'arrayobject::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayobject::count' => 
  array (
    0 => 'int',
  ),
  'arrayobject::exchangearray' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>|object',
  ),
  'arrayobject::getarraycopy' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'arrayobject::getflags' => 
  array (
    0 => 'int',
  ),
  'arrayobject::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'arrayobject::getiteratorclass' => 
  array (
    0 => 'string',
  ),
  'arrayobject::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'arrayobject::natcasesort' => 
  array (
    0 => 'true',
  ),
  'arrayobject::natsort' => 
  array (
    0 => 'true',
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
  'arrayobject::serialize' => 
  array (
    0 => 'string',
  ),
  'arrayobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'arrayobject::setiteratorclass' => 
  array (
    0 => 'void',
    'iteratorClass' => 'string',
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
  'arrayobject::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'arsort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'asin' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'asinh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'asort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'assert' => 
  array (
    0 => 'bool',
    'assertion' => 'mixed',
    'description=' => 'Throwable|null|string',
  ),
  'assert_options' => 
  array (
    0 => 'mixed',
    'option' => 'int',
    'value=' => 'mixed',
  ),
  'assertionerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'assertionerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'assertionerror::__wakeup' => 
  array (
    0 => 'void',
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
  'assertionerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'assertionerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'assertionerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'assertionerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'atan' => 
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
  'atanh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'attribute::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'badfunctioncallexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'badfunctioncallexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::__wakeup' => 
  array (
    0 => 'void',
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
  'badfunctioncallexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'badfunctioncallexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'badfunctioncallexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'badfunctioncallexception::gettraceasstring' => 
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
  'badmethodcallexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::__wakeup' => 
  array (
    0 => 'void',
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
  'badmethodcallexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'badmethodcallexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'badmethodcallexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'badmethodcallexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'base64_decode' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'strict=' => 'bool',
  ),
  'base64_encode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'base_convert' => 
  array (
    0 => 'string',
    'num' => 'string',
    'from_base' => 'int',
    'to_base' => 'int',
  ),
  'basename' => 
  array (
    0 => 'string',
    'path' => 'string',
    'suffix=' => 'string',
  ),
  'bcadd' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcceil' => 
  array (
    0 => 'string',
    'num' => 'string',
  ),
  'bccomp' => 
  array (
    0 => 'int',
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
  'bcdivmod' => 
  array (
    0 => 'array<array-key, mixed>',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bcfloor' => 
  array (
    0 => 'string',
    'num' => 'string',
  ),
  'bcmath\\number::__construct' => 
  array (
    0 => 'void',
    'num' => 'int|string',
  ),
  'bcmath\\number::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'bcmath\\number::__tostring' => 
  array (
    0 => 'string',
  ),
  'bcmath\\number::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'bcmath\\number::add' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::ceil' => 
  array (
    0 => 'BcMath\\Number',
  ),
  'bcmath\\number::compare' => 
  array (
    0 => 'int',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::div' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::divmod' => 
  array (
    0 => 'array<array-key, mixed>',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::floor' => 
  array (
    0 => 'BcMath\\Number',
  ),
  'bcmath\\number::mod' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::mul' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::pow' => 
  array (
    0 => 'BcMath\\Number',
    'exponent' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::powmod' => 
  array (
    0 => 'BcMath\\Number',
    'exponent' => 'BcMath\\Number|int|string',
    'modulus' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::round' => 
  array (
    0 => 'BcMath\\Number',
    'precision=' => 'int',
    'mode=' => 'RoundingMode',
  ),
  'bcmath\\number::sqrt' => 
  array (
    0 => 'BcMath\\Number',
    'scale=' => 'int|null',
  ),
  'bcmath\\number::sub' => 
  array (
    0 => 'BcMath\\Number',
    'num' => 'BcMath\\Number|int|string',
    'scale=' => 'int|null',
  ),
  'bcmod' => 
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
  'bcpow' => 
  array (
    0 => 'string',
    'num' => 'string',
    'exponent' => 'string',
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
  'bcround' => 
  array (
    0 => 'string',
    'num' => 'string',
    'precision=' => 'int',
    'mode=' => 'RoundingMode',
  ),
  'bcscale' => 
  array (
    0 => 'int',
    'scale=' => 'int|null',
  ),
  'bcsqrt' => 
  array (
    0 => 'string',
    'num' => 'string',
    'scale=' => 'int|null',
  ),
  'bcsub' => 
  array (
    0 => 'string',
    'num1' => 'string',
    'num2' => 'string',
    'scale=' => 'int|null',
  ),
  'bin2hex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'bindec' => 
  array (
    0 => 'float|int',
    'binary_string' => 'string',
  ),
  'boolval' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'cachingiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'cachingiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'cachingiterator::count' => 
  array (
    0 => 'int',
  ),
  'cachingiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'cachingiterator::getcache' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'cachingiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'cachingiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'cachingiterator::hasnext' => 
  array (
    0 => 'bool',
  ),
  'cachingiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'cachingiterator::next' => 
  array (
    0 => 'void',
  ),
  'cachingiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
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
  'cachingiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'cachingiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'cachingiterator::valid' => 
  array (
    0 => 'bool',
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
    'args' => 'array<array-key, mixed>',
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
  'callbackfilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'callbackfilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'callbackfilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'callbackfilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'callbackfilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'callbackfilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'ceil' => 
  array (
    0 => 'float',
    'num' => 'float|int',
  ),
  'chdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
  ),
  'checkdate' => 
  array (
    0 => 'bool',
    'month' => 'int',
    'day' => 'int',
    'year' => 'int',
  ),
  'checkdnsrr' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    'type=' => 'string',
  ),
  'chgrp' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'group' => 'int|string',
  ),
  'chmod' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'permissions' => 'int',
  ),
  'chop' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'chown' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'user' => 'int|string',
  ),
  'chr' => 
  array (
    0 => 'string',
    'codepoint' => 'int',
  ),
  'chroot' => 
  array (
    0 => 'bool',
    'directory' => 'string',
  ),
  'chunk_split' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length=' => 'int',
    'separator=' => 'string',
  ),
  'class_alias' => 
  array (
    0 => 'bool',
    'class' => 'string',
    'alias' => 'string',
    'autoload=' => 'bool',
  ),
  'class_exists' => 
  array (
    0 => 'bool',
    'class' => 'string',
    'autoload=' => 'bool',
  ),
  'class_implements' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'class_parents' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'class_uses' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'object_or_class' => 'mixed',
    'autoload=' => 'bool',
  ),
  'clearstatcache' => 
  array (
    0 => 'void',
    'clear_realpath_cache=' => 'bool',
    'filename=' => 'string',
  ),
  'cli_get_process_title' => 
  array (
    0 => 'null|string',
  ),
  'cli_set_process_title' => 
  array (
    0 => 'bool',
    'title' => 'string',
  ),
  'clone' => 
  array (
    0 => 'object',
    'object' => 'object',
    'withProperties=' => 'array<array-key, mixed>',
  ),
  'closedgeneratorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'closedgeneratorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'closedgeneratorexception::__wakeup' => 
  array (
    0 => 'void',
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
  'closedgeneratorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'closedgeneratorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'closedgeneratorexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'closedgeneratorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'closedir' => 
  array (
    0 => 'void',
    'dir_handle=' => 'mixed',
  ),
  'closelog' => 
  array (
    0 => 'true',
  ),
  'closure::__construct' => 
  array (
    0 => 'void',
  ),
  'closure::__invoke' => 
  array (
    0 => 'mixed',
    '...args=' => 'mixed',
  ),
  'closure::bind' => 
  array (
    0 => 'Closure|null',
    'closure' => 'Closure',
    'newThis' => 'null|object',
    'newScope=' => 'null|object|string',
  ),
  'closure::bindto' => 
  array (
    0 => 'Closure|null',
    'newThis' => 'null|object',
    'newScope=' => 'null|object|string',
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
  'collator::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
  ),
  'collator::asort' => 
  array (
    0 => 'bool',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'collator::compare' => 
  array (
    0 => 'false|int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'collator::create' => 
  array (
    0 => 'Collator|null',
    'locale' => 'string',
  ),
  'collator::getattribute' => 
  array (
    0 => 'false|int',
    'attribute' => 'int',
  ),
  'collator::geterrorcode' => 
  array (
    0 => 'false|int',
  ),
  'collator::geterrormessage' => 
  array (
    0 => 'false|string',
  ),
  'collator::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'collator::getsortkey' => 
  array (
    0 => 'false|string',
    'string' => 'string',
  ),
  'collator::getstrength' => 
  array (
    0 => 'int',
  ),
  'collator::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'int',
  ),
  'collator::setstrength' => 
  array (
    0 => 'true',
    'strength' => 'int',
  ),
  'collator::sort' => 
  array (
    0 => 'bool',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'collator::sortwithsortkeys' => 
  array (
    0 => 'bool',
    '&array' => 'array<array-key, mixed>',
  ),
  'collator_asort' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'collator_compare' => 
  array (
    0 => 'false|int',
    'object' => 'Collator',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'collator_create' => 
  array (
    0 => 'Collator|null',
    'locale' => 'string',
  ),
  'collator_get_attribute' => 
  array (
    0 => 'false|int',
    'object' => 'Collator',
    'attribute' => 'int',
  ),
  'collator_get_error_code' => 
  array (
    0 => 'false|int',
    'object' => 'Collator',
  ),
  'collator_get_error_message' => 
  array (
    0 => 'false|string',
    'object' => 'Collator',
  ),
  'collator_get_locale' => 
  array (
    0 => 'false|string',
    'object' => 'Collator',
    'type' => 'int',
  ),
  'collator_get_sort_key' => 
  array (
    0 => 'false|string',
    'object' => 'Collator',
    'string' => 'string',
  ),
  'collator_get_strength' => 
  array (
    0 => 'int',
    'object' => 'Collator',
  ),
  'collator_set_attribute' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    'attribute' => 'int',
    'value' => 'int',
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
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'collator_sort_with_sort_keys' => 
  array (
    0 => 'bool',
    'object' => 'Collator',
    '&array' => 'array<array-key, mixed>',
  ),
  'compact' => 
  array (
    0 => 'array<array-key, mixed>',
    'var_name' => 'mixed',
    '...var_names=' => 'mixed',
  ),
  'compileerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'compileerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'compileerror::__wakeup' => 
  array (
    0 => 'void',
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
  'compileerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'compileerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'compileerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'compileerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'connection_aborted' => 
  array (
    0 => 'int',
  ),
  'connection_status' => 
  array (
    0 => 'int',
  ),
  'constant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'convert_uudecode' => 
  array (
    0 => 'false|string',
    'string' => 'string',
  ),
  'convert_uuencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'copy' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'mixed',
  ),
  'cos' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'cosh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'couchbase\\analyticsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\analyticsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\analyticsexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\analyticsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\analyticsexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\analyticsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\analyticsexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\analyticsindexmanager::connectlink' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\ConnectAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::createdataset' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'bucketName' => 'string',
    'options=' => 'Couchbase\\CreateAnalyticsDatasetOptions|null',
  ),
  'couchbase\\analyticsindexmanager::createdataverse' => 
  array (
    0 => 'mixed',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\CreateAnalyticsDataverseOptions|null',
  ),
  'couchbase\\analyticsindexmanager::createindex' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'indexName' => 'string',
    'fields' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\CreateAnalyticsIndexOptions|null',
  ),
  'couchbase\\analyticsindexmanager::createlink' => 
  array (
    0 => 'mixed',
    'link' => 'Couchbase\\AnalyticsLink',
    'options=' => 'Couchbase\\CreateAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::disconnectlink' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\DisconnectAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropdataset' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsDatasetOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropdataverse' => 
  array (
    0 => 'mixed',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsDataverseOptions|null',
  ),
  'couchbase\\analyticsindexmanager::dropindex' => 
  array (
    0 => 'mixed',
    'datasetName' => 'string',
    'indexName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsIndexOptions|null',
  ),
  'couchbase\\analyticsindexmanager::droplink' => 
  array (
    0 => 'mixed',
    'linkName' => 'string',
    'dataverseName' => 'string',
    'options=' => 'Couchbase\\DropAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getalldatasets' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::getallindexes' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::getlinks' => 
  array (
    0 => 'mixed',
    'options=' => 'Couchbase\\GetAnalyticsLinksOptions|null',
  ),
  'couchbase\\analyticsindexmanager::getpendingmutations' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\analyticsindexmanager::replacelink' => 
  array (
    0 => 'mixed',
    'link' => 'Couchbase\\AnalyticsLink',
    'options=' => 'Couchbase\\ReplaceAnalyticsLinkOptions|null',
  ),
  'couchbase\\analyticsoptions::clientcontextid' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'value' => 'string',
  ),
  'couchbase\\analyticsoptions::namedparameters' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'pairs' => 'array<array-key, mixed>',
  ),
  'couchbase\\analyticsoptions::positionalparameters' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'args' => 'array<array-key, mixed>',
  ),
  'couchbase\\analyticsoptions::priority' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'urgent' => 'bool',
  ),
  'couchbase\\analyticsoptions::raw' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'key' => 'string',
    'value' => 'mixed',
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
  'couchbase\\analyticsoptions::timeout' => 
  array (
    0 => 'Couchbase\\AnalyticsOptions',
    'arg' => 'int',
  ),
  'couchbase\\appendoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\AppendOptions',
    'arg' => 'int',
  ),
  'couchbase\\appendoptions::timeout' => 
  array (
    0 => 'Couchbase\\AppendOptions',
    'arg' => 'int',
  ),
  'couchbase\\authenticationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\authenticationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\authenticationexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\authenticationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\authenticationexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\authenticationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\authenticationexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\azureblobexternalanalyticslink::accountkey' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'accountKey' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::accountname' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'accountName' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::blobendpoint' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'blobEndpoint' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::connectionstring' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'connectionString' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::endpointsuffix' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'suffix' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::name' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\azureblobexternalanalyticslink::sharedaccesssignature' => 
  array (
    0 => 'Couchbase\\AzureBlobExternalAnalyticsLink',
    'signature' => 'string',
  ),
  'couchbase\\badinputexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\badinputexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\badinputexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\badinputexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\badinputexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\badinputexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\badinputexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\baseexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\baseexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\baseexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\baseexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\baseexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\baseexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\baseexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\binarycollection::append' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'string',
    'options=' => 'Couchbase\\AppendOptions|null',
  ),
  'couchbase\\binarycollection::decrement' => 
  array (
    0 => 'Couchbase\\CounterResult',
    'id' => 'string',
    'options=' => 'Couchbase\\DecrementOptions|null',
  ),
  'couchbase\\binarycollection::increment' => 
  array (
    0 => 'Couchbase\\CounterResult',
    'id' => 'string',
    'options=' => 'Couchbase\\IncrementOptions|null',
  ),
  'couchbase\\binarycollection::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\binarycollection::prepend' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'string',
    'options=' => 'Couchbase\\PrependOptions|null',
  ),
  'couchbase\\bindingsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\bindingsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\bindingsexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\bindingsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\bindingsexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\bindingsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bindingsexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\booleanfieldsearchquery::jsonserialize' => 
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
  'couchbase\\booleansearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'couchbase\\bucket::collections' => 
  array (
    0 => 'Couchbase\\CollectionManager',
  ),
  'couchbase\\bucket::defaultcollection' => 
  array (
    0 => 'Couchbase\\Collection',
  ),
  'couchbase\\bucket::defaultscope' => 
  array (
    0 => 'Couchbase\\Scope',
  ),
  'couchbase\\bucket::diagnostics' => 
  array (
    0 => 'mixed',
    'reportId' => 'mixed',
  ),
  'couchbase\\bucket::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucket::ping' => 
  array (
    0 => 'mixed',
    'services' => 'mixed',
    'reportId' => 'mixed',
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
  'couchbase\\bucket::viewindexes' => 
  array (
    0 => 'Couchbase\\ViewIndexManager',
  ),
  'couchbase\\bucket::viewquery' => 
  array (
    0 => 'Couchbase\\ViewResult',
    'designDoc' => 'string',
    'viewName' => 'string',
    'options=' => 'Couchbase\\ViewOptions|null',
  ),
  'couchbase\\bucketmanager::createbucket' => 
  array (
    0 => 'mixed',
    'settings' => 'Couchbase\\BucketSettings',
  ),
  'couchbase\\bucketmanager::flush' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\bucketmanager::getallbuckets' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\bucketmanager::getbucket' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'name' => 'string',
  ),
  'couchbase\\bucketmanager::removebucket' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\bucketmissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\bucketmissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\bucketmissingexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\bucketmissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\bucketmissingexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\bucketmissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketmissingexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\bucketsettings::buckettype' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::compressionmode' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::enableflush' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'enable' => 'bool',
  ),
  'couchbase\\bucketsettings::enablereplicaindexes' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'enable' => 'bool',
  ),
  'couchbase\\bucketsettings::evictionpolicy' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::flushenabled' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\bucketsettings::maxttl' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::minimaldurabilitylevel' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\bucketsettings::numreplicas' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::ramquotamb' => 
  array (
    0 => 'int',
  ),
  'couchbase\\bucketsettings::replicaindexes' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\bucketsettings::setbuckettype' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'type' => 'string',
  ),
  'couchbase\\bucketsettings::setcompressionmode' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'mode' => 'string',
  ),
  'couchbase\\bucketsettings::setevictionpolicy' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'policy' => 'string',
  ),
  'couchbase\\bucketsettings::setmaxttl' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'ttlSeconds' => 'int',
  ),
  'couchbase\\bucketsettings::setminimaldurabilitylevel' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'durabilityLevel' => 'int',
  ),
  'couchbase\\bucketsettings::setname' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'name' => 'string',
  ),
  'couchbase\\bucketsettings::setnumreplicas' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'numReplicas' => 'int',
  ),
  'couchbase\\bucketsettings::setramquotamb' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'sizeInMb' => 'int',
  ),
  'couchbase\\bucketsettings::setstoragebackend' => 
  array (
    0 => 'Couchbase\\BucketSettings',
    'policy' => 'string',
  ),
  'couchbase\\bucketsettings::storagebackend' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\casmismatchexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\casmismatchexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\casmismatchexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\casmismatchexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\casmismatchexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\casmismatchexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\cluster::__construct' => 
  array (
    0 => 'void',
    'connstr' => 'string',
    'options' => 'Couchbase\\ClusterOptions',
  ),
  'couchbase\\cluster::analyticsindexes' => 
  array (
    0 => 'Couchbase\\AnalyticsIndexManager',
  ),
  'couchbase\\cluster::analyticsquery' => 
  array (
    0 => 'Couchbase\\AnalyticsResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\AnalyticsOptions|null',
  ),
  'couchbase\\cluster::bucket' => 
  array (
    0 => 'Couchbase\\Bucket',
    'name' => 'string',
  ),
  'couchbase\\cluster::buckets' => 
  array (
    0 => 'Couchbase\\BucketManager',
  ),
  'couchbase\\cluster::query' => 
  array (
    0 => 'Couchbase\\QueryResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\QueryOptions|null',
  ),
  'couchbase\\cluster::queryindexes' => 
  array (
    0 => 'Couchbase\\QueryIndexManager',
  ),
  'couchbase\\cluster::searchindexes' => 
  array (
    0 => 'Couchbase\\SearchIndexManager',
  ),
  'couchbase\\cluster::searchquery' => 
  array (
    0 => 'Couchbase\\SearchResult',
    'indexName' => 'string',
    'query' => 'Couchbase\\SearchQuery',
    'options=' => 'Couchbase\\SearchOptions|null',
  ),
  'couchbase\\cluster::users' => 
  array (
    0 => 'Couchbase\\UserManager',
  ),
  'couchbase\\clusteroptions::credentials' => 
  array (
    0 => 'Couchbase\\ClusterOptions',
    'username' => 'string',
    'password' => 'string',
  ),
  'couchbase\\collection::binary' => 
  array (
    0 => 'Couchbase\\BinaryCollection',
  ),
  'couchbase\\collection::exists' => 
  array (
    0 => 'Couchbase\\ExistsResult',
    'id' => 'string',
    'options=' => 'Couchbase\\ExistsOptions|null',
  ),
  'couchbase\\collection::get' => 
  array (
    0 => 'Couchbase\\GetResult',
    'id' => 'string',
    'options=' => 'Couchbase\\GetOptions|null',
  ),
  'couchbase\\collection::getallreplicas' => 
  array (
    0 => 'array<array-key, mixed>',
    'id' => 'string',
    'options=' => 'Couchbase\\GetAllReplicasOptions|null',
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
  'couchbase\\collection::getmulti' => 
  array (
    0 => 'array<array-key, mixed>',
    'ids' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::insert' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\InsertOptions|null',
  ),
  'couchbase\\collection::lookupin' => 
  array (
    0 => 'Couchbase\\LookupInResult',
    'id' => 'string',
    'specs' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\LookupInOptions|null',
  ),
  'couchbase\\collection::mutatein' => 
  array (
    0 => 'Couchbase\\MutateInResult',
    'id' => 'string',
    'specs' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\MutateInOptions|null',
  ),
  'couchbase\\collection::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collection::remove' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::removemulti' => 
  array (
    0 => 'array<array-key, mixed>',
    'entries' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\RemoveOptions|null',
  ),
  'couchbase\\collection::replace' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\ReplaceOptions|null',
  ),
  'couchbase\\collection::touch' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'expiry' => 'int',
    'options=' => 'Couchbase\\TouchOptions|null',
  ),
  'couchbase\\collection::unlock' => 
  array (
    0 => 'Couchbase\\Result',
    'id' => 'string',
    'cas' => 'string',
    'options=' => 'Couchbase\\UnlockOptions|null',
  ),
  'couchbase\\collection::upsert' => 
  array (
    0 => 'Couchbase\\MutationResult',
    'id' => 'string',
    'value' => 'mixed',
    'options=' => 'Couchbase\\UpsertOptions|null',
  ),
  'couchbase\\collection::upsertmulti' => 
  array (
    0 => 'array<array-key, mixed>',
    'entries' => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\UpsertOptions|null',
  ),
  'couchbase\\collectionmanager::createcollection' => 
  array (
    0 => 'mixed',
    'collection' => 'Couchbase\\CollectionSpec',
  ),
  'couchbase\\collectionmanager::createscope' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\collectionmanager::dropcollection' => 
  array (
    0 => 'mixed',
    'collection' => 'Couchbase\\CollectionSpec',
  ),
  'couchbase\\collectionmanager::dropscope' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\collectionmanager::getallscopes' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\collectionmanager::getscope' => 
  array (
    0 => 'Couchbase\\ScopeSpec',
    'name' => 'string',
  ),
  'couchbase\\collectionmissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\collectionmissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\collectionmissingexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\collectionmissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\collectionmissingexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\collectionmissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionmissingexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\collectionspec::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionspec::scopename' => 
  array (
    0 => 'string',
  ),
  'couchbase\\collectionspec::setmaxexpiry' => 
  array (
    0 => 'Couchbase\\CollectionSpec',
    'ms' => 'int',
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
  'couchbase\\conjunctionsearchquery::__construct' => 
  array (
    0 => 'void',
    'queries' => 'array<array-key, mixed>',
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
  'couchbase\\conjunctionsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\connectanalyticslinkoptions::dataversename' => 
  array (
    0 => 'Couchbase\\ConnectAnalyticsLinkOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\connectanalyticslinkoptions::linkname' => 
  array (
    0 => 'Couchbase\\ConnectAnalyticsLinkOptions',
    'linkName' => 'Couchbase\\bstring',
  ),
  'couchbase\\coordinate::__construct' => 
  array (
    0 => 'void',
    'longitude' => 'float',
    'latitude' => 'float',
  ),
  'couchbase\\coordinate::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\couchbaseremoteanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::encryption' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'settings' => 'Couchbase\\EncryptionSettings',
  ),
  'couchbase\\couchbaseremoteanalyticslink::hostname' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'hostname' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::name' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::password' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'password' => 'string',
  ),
  'couchbase\\couchbaseremoteanalyticslink::username' => 
  array (
    0 => 'Couchbase\\CouchbaseRemoteAnalyticsLink',
    'username' => 'string',
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
  'couchbase\\createanalyticsdatasetoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDatasetOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticsdataverseoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsDataverseOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticsindexoptions::dataversename' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsIndexOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\createanalyticsindexoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\CreateAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\createqueryindexoptions::condition' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'condition' => 'string',
  ),
  'couchbase\\createqueryindexoptions::deferred' => 
  array (
    0 => 'Couchbase\\CreateQueryIndexOptions',
    'isDeferred' => 'bool',
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
  'couchbase\\createqueryprimaryindexoptions::deferred' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'isDeferred' => 'bool',
  ),
  'couchbase\\createqueryprimaryindexoptions::ignoreifexists' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\createqueryprimaryindexoptions::indexname' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'name' => 'string',
  ),
  'couchbase\\createqueryprimaryindexoptions::numreplicas' => 
  array (
    0 => 'Couchbase\\CreateQueryPrimaryIndexOptions',
    'number' => 'int',
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
  'couchbase\\daterangesearchfacet::jsonserialize' => 
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
  'couchbase\\daterangesearchquery::datetimeparser' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'dateTimeParser' => 'string',
  ),
  'couchbase\\daterangesearchquery::end' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'end' => 'mixed',
    'inclusive=' => 'bool',
  ),
  'couchbase\\daterangesearchquery::field' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\daterangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\daterangesearchquery::start' => 
  array (
    0 => 'Couchbase\\DateRangeSearchQuery',
    'start' => 'mixed',
    'inclusive=' => 'bool',
  ),
  'couchbase\\decrementoptions::delta' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::expiry' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\decrementoptions::initial' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\decrementoptions::timeout' => 
  array (
    0 => 'Couchbase\\DecrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\designdocument::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\designdocument::setname' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'name' => 'string',
  ),
  'couchbase\\designdocument::setviews' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'views' => 'array<array-key, mixed>',
  ),
  'couchbase\\designdocument::views' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\disconnectanalyticslinkoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DisconnectAnalyticsLinkOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\disconnectanalyticslinkoptions::linkname' => 
  array (
    0 => 'Couchbase\\DisconnectAnalyticsLinkOptions',
    'linkName' => 'Couchbase\\bstring',
  ),
  'couchbase\\disjunctionsearchquery::__construct' => 
  array (
    0 => 'void',
    'queries' => 'array<array-key, mixed>',
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
  'couchbase\\disjunctionsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\disjunctionsearchquery::min' => 
  array (
    0 => 'Couchbase\\DisjunctionSearchQuery',
    'min' => 'int',
  ),
  'couchbase\\dmlfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\dmlfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\dmlfailureexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\dmlfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\dmlfailureexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\dmlfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\dmlfailureexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\docidsearchquery::docids' => 
  array (
    0 => 'Couchbase\\DocIdSearchQuery',
    '...documentIds=' => 'string',
  ),
  'couchbase\\docidsearchquery::field' => 
  array (
    0 => 'Couchbase\\DocIdSearchQuery',
    'field' => 'string',
  ),
  'couchbase\\docidsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\documentnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\documentnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\documentnotfoundexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\documentnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\documentnotfoundexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\documentnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\documentnotfoundexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\dropanalyticsdatasetoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDatasetOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\dropanalyticsdatasetoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDatasetOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticsdataverseoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsDataverseOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticsindexoptions::dataversename' => 
  array (
    0 => 'Couchbase\\DropAnalyticsIndexOptions',
    'dataverseName' => 'string',
  ),
  'couchbase\\dropanalyticsindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropAnalyticsIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\dropqueryindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropQueryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropqueryprimaryindexoptions::ignoreifnotexists' => 
  array (
    0 => 'Couchbase\\DropQueryPrimaryIndexOptions',
    'shouldIgnore' => 'bool',
  ),
  'couchbase\\dropqueryprimaryindexoptions::indexname' => 
  array (
    0 => 'Couchbase\\DropQueryPrimaryIndexOptions',
    'name' => 'string',
  ),
  'couchbase\\dropuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\DropUserOptions',
    'name' => 'string',
  ),
  'couchbase\\durabilityexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\durabilityexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\durabilityexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\durabilityexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\durabilityexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\durabilityexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\durabilityexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\encryptionsettings::level' => 
  array (
    0 => 'mixed',
    'level' => 'string',
  ),
  'couchbase\\existsoptions::timeout' => 
  array (
    0 => 'Couchbase\\ExistsOptions',
    'arg' => 'int',
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
  'couchbase\\geoboundingboxsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\geodistancesearchquery::__construct' => 
  array (
    0 => 'void',
    'longitude' => 'float',
    'latitude' => 'float',
    'distance=' => 'null|string',
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
  'couchbase\\geodistancesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\geopolygonquery::__construct' => 
  array (
    0 => 'void',
    'coordinates' => 'array<array-key, mixed>',
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
  'couchbase\\geopolygonquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\getallreplicasoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAllReplicasOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getallreplicasoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAllReplicasOptions',
    'arg' => 'int',
  ),
  'couchbase\\getallusersoptions::domainname' => 
  array (
    0 => 'Couchbase\\GetAllUsersOptions',
    'name' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::dataverse' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'dataverse' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::linktype' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'type' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::name' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'name' => 'string',
  ),
  'couchbase\\getanalyticslinksoptions::timeout' => 
  array (
    0 => 'Couchbase\\DropAnalyticsLinkOptions',
    'arg' => 'int',
  ),
  'couchbase\\getandlockoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAndLockOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getandlockoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAndLockOptions',
    'arg' => 'int',
  ),
  'couchbase\\getandtouchoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAndTouchOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getandtouchoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAndTouchOptions',
    'arg' => 'int',
  ),
  'couchbase\\getanyreplicaoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetAnyReplicaOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getanyreplicaoptions::timeout' => 
  array (
    0 => 'Couchbase\\GetAnyReplicaOptions',
    'arg' => 'int',
  ),
  'couchbase\\getoptions::decoder' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'callable',
  ),
  'couchbase\\getoptions::project' => 
  array (
    0 => 'Couchbase\\GetOptions',
    'arg' => 'array<array-key, mixed>',
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
  'couchbase\\getuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\GetUserOptions',
    'name' => 'string',
  ),
  'couchbase\\group::description' => 
  array (
    0 => 'string',
  ),
  'couchbase\\group::ldapgroupreference' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\group::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\group::roles' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\group::setdescription' => 
  array (
    0 => 'Couchbase\\Group',
    'description' => 'string',
  ),
  'couchbase\\group::setname' => 
  array (
    0 => 'Couchbase\\Group',
    'name' => 'string',
  ),
  'couchbase\\group::setroles' => 
  array (
    0 => 'Couchbase\\Group',
    'roles' => 'array<array-key, mixed>',
  ),
  'couchbase\\httpexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\httpexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\httpexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\httpexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\httpexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\httpexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\httpexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\incrementoptions::delta' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::expiry' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'mixed',
  ),
  'couchbase\\incrementoptions::initial' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\incrementoptions::timeout' => 
  array (
    0 => 'Couchbase\\IncrementOptions',
    'arg' => 'int',
  ),
  'couchbase\\indexfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\indexfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\indexfailureexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\indexfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\indexfailureexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\indexfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexfailureexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\indexnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\indexnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\indexnotfoundexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\indexnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\indexnotfoundexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\indexnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\indexnotfoundexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\insertoptions::expiry' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\insertoptions::timeout' => 
  array (
    0 => 'Couchbase\\InsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\invalidconfigurationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidconfigurationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidconfigurationexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\invalidconfigurationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidconfigurationexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\invalidconfigurationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidconfigurationexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\invalidrangeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidrangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidrangeexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\invalidrangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidrangeexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\invalidrangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidrangeexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\invalidstateexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\invalidstateexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\invalidstateexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\invalidstateexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\invalidstateexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\invalidstateexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\invalidstateexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\keydeletedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keydeletedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keydeletedexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\keydeletedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keydeletedexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\keydeletedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keydeletedexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\keyexistsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyexistsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyexistsexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\keyexistsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyexistsexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\keyexistsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyexistsexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\keylockedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keylockedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keylockedexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\keylockedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keylockedexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\keylockedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keylockedexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\keyspacenotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyspacenotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyspacenotfoundexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\keyspacenotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyspacenotfoundexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\keyspacenotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyspacenotfoundexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\keyvalueexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\keyvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\keyvalueexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\keyvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\keyvalueexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\keyvalueexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\keyvalueexception::ref' => 
  array (
    0 => 'null|string',
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
    'tags' => 'array<array-key, mixed>',
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
  'couchbase\\lookupgetspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr=' => 'bool',
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
  'couchbase\\matchallsearchquery::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\matchallsearchquery::boost' => 
  array (
    0 => 'Couchbase\\MatchAllSearchQuery',
    'boost' => 'float',
  ),
  'couchbase\\matchallsearchquery::jsonserialize' => 
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
  'couchbase\\matchnonesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchphrasesearchquery::__construct' => 
  array (
    0 => 'void',
    'value' => 'string',
  ),
  'couchbase\\matchphrasesearchquery::analyzer' => 
  array (
    0 => 'Couchbase\\MatchPhraseSearchQuery',
    'analyzer' => 'string',
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
  'couchbase\\matchphrasesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchsearchquery::__construct' => 
  array (
    0 => 'void',
    'value' => 'string',
  ),
  'couchbase\\matchsearchquery::analyzer' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'analyzer' => 'string',
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
  'couchbase\\matchsearchquery::fuzziness' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'fuzziness' => 'int',
  ),
  'couchbase\\matchsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\matchsearchquery::prefixlength' => 
  array (
    0 => 'Couchbase\\MatchSearchQuery',
    'prefixLength' => 'int',
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
  'couchbase\\mutatearrayappendspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array<array-key, mixed>',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatearrayinsertspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array<array-key, mixed>',
    'isXattr' => 'bool',
    'createPath' => 'bool',
    'expandMacros' => 'bool',
  ),
  'couchbase\\mutatearrayprependspec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'values' => 'array<array-key, mixed>',
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
  'couchbase\\mutateinoptions::cas' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'string',
  ),
  'couchbase\\mutateinoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
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
  'couchbase\\mutateinoptions::storesemantics' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
  ),
  'couchbase\\mutateinoptions::timeout' => 
  array (
    0 => 'Couchbase\\MutateInOptions',
    'arg' => 'int',
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
  'couchbase\\mutateremovespec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'isXattr' => 'bool',
  ),
  'couchbase\\mutatereplacespec::__construct' => 
  array (
    0 => 'void',
    'path' => 'string',
    'value' => 'mixed',
    'isXattr' => 'bool',
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
  'couchbase\\mutationstate::__construct' => 
  array (
    0 => 'void',
  ),
  'couchbase\\mutationstate::add' => 
  array (
    0 => 'Couchbase\\MutationState',
    'source' => 'Couchbase\\MutationResult',
  ),
  'couchbase\\networkexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\networkexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\networkexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\networkexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\networkexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\networkexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\networkexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\noopmeter::valuerecorder' => 
  array (
    0 => 'Couchbase\\ValueRecorder',
    'name' => 'string',
    'tags' => 'array<array-key, mixed>',
  ),
  'couchbase\\nooptracer::requestspan' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'parent=' => 'Couchbase\\RequestSpan|null',
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
  'couchbase\\numericrangesearchfacet::jsonserialize' => 
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
  'couchbase\\numericrangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\numericrangesearchquery::max' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'max' => 'float',
    'inclusive=' => 'bool',
  ),
  'couchbase\\numericrangesearchquery::min' => 
  array (
    0 => 'Couchbase\\NumericRangeSearchQuery',
    'min' => 'float',
    'inclusive=' => 'bool',
  ),
  'couchbase\\origin::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\origin::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\parsingfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\parsingfailureexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\parsingfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\parsingfailureexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\parsingfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\parsingfailureexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\partialviewexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\partialviewexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\partialviewexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\partialviewexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\partialviewexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\partialviewexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\partialviewexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\pathexistsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\pathexistsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\pathexistsexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\pathexistsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\pathexistsexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\pathexistsexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathexistsexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\pathnotfoundexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\pathnotfoundexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\pathnotfoundexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\pathnotfoundexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\pathnotfoundexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\pathnotfoundexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\pathnotfoundexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\phrasesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\planningfailureexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\planningfailureexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\planningfailureexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\planningfailureexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\planningfailureexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\planningfailureexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\planningfailureexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\prefixsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\preparedstatementexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\preparedstatementexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\preparedstatementexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\preparedstatementexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\preparedstatementexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\preparedstatementexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\preparedstatementexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\prependoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\PrependOptions',
    'arg' => 'int',
  ),
  'couchbase\\prependoptions::timeout' => 
  array (
    0 => 'Couchbase\\PrependOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryerrorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryerrorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryerrorexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\queryerrorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryerrorexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\queryerrorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryerrorexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\queryexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\queryexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\queryexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\queryindex::condition' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\queryindex::indexkey' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\queryindex::isprimary' => 
  array (
    0 => 'bool',
  ),
  'couchbase\\queryindex::keyspace' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::state' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindex::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryindexmanager::builddeferredindexes' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
  ),
  'couchbase\\queryindexmanager::createindex' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'indexName' => 'string',
    'fields' => 'array<array-key, mixed>',
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
  'couchbase\\queryindexmanager::getallindexes' => 
  array (
    0 => 'array<array-key, mixed>',
    'bucketName' => 'string',
  ),
  'couchbase\\queryindexmanager::watchindexes' => 
  array (
    0 => 'mixed',
    'bucketName' => 'string',
    'indexNames' => 'array<array-key, mixed>',
    'timeout' => 'int',
    'options=' => 'Couchbase\\WatchQueryIndexesOptions|null',
  ),
  'couchbase\\queryoptions::adhoc' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::clientcontextid' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'string',
  ),
  'couchbase\\queryoptions::consistentwith' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'Couchbase\\MutationState',
  ),
  'couchbase\\queryoptions::flexindex' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::maxparallelism' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::metrics' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::namedparameters' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'pairs' => 'array<array-key, mixed>',
  ),
  'couchbase\\queryoptions::pipelinebatch' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::pipelinecap' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::positionalparameters' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'args' => 'array<array-key, mixed>',
  ),
  'couchbase\\queryoptions::profile' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::raw' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'key' => 'string',
    'value' => 'mixed',
  ),
  'couchbase\\queryoptions::readonly' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'bool',
  ),
  'couchbase\\queryoptions::scancap' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryoptions::scanconsistency' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
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
  'couchbase\\queryoptions::timeout' => 
  array (
    0 => 'Couchbase\\QueryOptions',
    'arg' => 'int',
  ),
  'couchbase\\queryserviceexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\queryserviceexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\queryserviceexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\queryserviceexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\queryserviceexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\queryserviceexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\queryserviceexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\querystringsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\quotalimitedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\quotalimitedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\quotalimitedexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\quotalimitedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\quotalimitedexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\quotalimitedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\quotalimitedexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\ratelimitedexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\ratelimitedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\ratelimitedexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\ratelimitedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\ratelimitedexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\ratelimitedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\ratelimitedexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\regexpsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\removeoptions::cas' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'string',
  ),
  'couchbase\\removeoptions::durabilitylevel' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'int',
  ),
  'couchbase\\removeoptions::timeout' => 
  array (
    0 => 'Couchbase\\RemoveOptions',
    'arg' => 'int',
  ),
  'couchbase\\replaceanalyticslinkoptions::timeout' => 
  array (
    0 => 'Couchbase\\ReplaceAnalyticsLinkOptions',
    'arg' => 'int',
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
  'couchbase\\replaceoptions::timeout' => 
  array (
    0 => 'Couchbase\\ReplaceOptions',
    'arg' => 'int',
  ),
  'couchbase\\requestcanceledexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\requestcanceledexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\requestcanceledexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\requestcanceledexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\requestcanceledexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\requestcanceledexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\requestcanceledexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\role::bucket' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\role::collection' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\role::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\role::scope' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\role::setbucket' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\role::setcollection' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\role::setname' => 
  array (
    0 => 'Couchbase\\Role',
    'name' => 'string',
  ),
  'couchbase\\role::setscope' => 
  array (
    0 => 'Couchbase\\Role',
    'bucket' => 'string',
  ),
  'couchbase\\roleanddescription::description' => 
  array (
    0 => 'string',
  ),
  'couchbase\\roleanddescription::displayname' => 
  array (
    0 => 'string',
  ),
  'couchbase\\roleanddescription::role' => 
  array (
    0 => 'Couchbase\\Role',
  ),
  'couchbase\\roleandorigin::origins' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\roleandorigin::role' => 
  array (
    0 => 'Couchbase\\Role',
  ),
  'couchbase\\s3externalanalyticslink::accesskeyid' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'accessKeyId' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::dataverse' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'dataverse' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::name' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'name' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::region' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'region' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::secretaccesskey' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'secretAccessKey' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::serviceendpoint' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'serviceEndpoint' => 'string',
  ),
  'couchbase\\s3externalanalyticslink::sessiontoken' => 
  array (
    0 => 'Couchbase\\S3ExternalAnalyticsLink',
    'sessionToken' => 'string',
  ),
  'couchbase\\scope::__construct' => 
  array (
    0 => 'void',
    'bucket' => 'Couchbase\\Bucket',
    'name' => 'string',
  ),
  'couchbase\\scope::analyticsquery' => 
  array (
    0 => 'Couchbase\\AnalyticsResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\AnalyticsOptions|null',
  ),
  'couchbase\\scope::collection' => 
  array (
    0 => 'Couchbase\\Collection',
    'name' => 'string',
  ),
  'couchbase\\scope::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scope::query' => 
  array (
    0 => 'Couchbase\\QueryResult',
    'statement' => 'string',
    'options=' => 'Couchbase\\QueryOptions|null',
  ),
  'couchbase\\scopemissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\scopemissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\scopemissingexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\scopemissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\scopemissingexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\scopemissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\scopemissingexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\scopespec::collections' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\scopespec::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\searchexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\searchexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\searchexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\searchexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\searchexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\searchindex::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchindex::params' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\searchindex::setparams' => 
  array (
    0 => 'Couchbase\\SearchIndex',
    'params' => 'string',
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
  'couchbase\\searchindex::sourcename' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::sourceparams' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\searchindex::sourcetype' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::sourceuuid' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::type' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindex::uuid' => 
  array (
    0 => 'string',
  ),
  'couchbase\\searchindexmanager::allowquerying' => 
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
  'couchbase\\searchindexmanager::disallowquerying' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::dropindex' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\searchindexmanager::freezeplan' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::getallindexes' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\searchindexmanager::getindex' => 
  array (
    0 => 'Couchbase\\SearchIndex',
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
  'couchbase\\searchindexmanager::unfreezeplan' => 
  array (
    0 => 'mixed',
    'indexName' => 'string',
  ),
  'couchbase\\searchindexmanager::upsertindex' => 
  array (
    0 => 'mixed',
    'indexDefinition' => 'Couchbase\\SearchIndex',
  ),
  'couchbase\\searchoptions::collections' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'collectionNames' => 'array<array-key, mixed>',
  ),
  'couchbase\\searchoptions::consistentwith' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'index' => 'string',
    'state' => 'Couchbase\\MutationState',
  ),
  'couchbase\\searchoptions::disablescoring' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'disabled' => 'bool',
  ),
  'couchbase\\searchoptions::explain' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'explain' => 'bool',
  ),
  'couchbase\\searchoptions::facets' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'facets' => 'array<array-key, mixed>',
  ),
  'couchbase\\searchoptions::fields' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'fields' => 'array<array-key, mixed>',
  ),
  'couchbase\\searchoptions::highlight' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'style=' => 'null|string',
    'fields=' => 'array<array-key, mixed>|null',
  ),
  'couchbase\\searchoptions::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'couchbase\\searchoptions::sort' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'specs' => 'array<array-key, mixed>',
  ),
  'couchbase\\searchoptions::timeout' => 
  array (
    0 => 'Couchbase\\SearchOptions',
    'ms' => 'int',
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
  'couchbase\\searchsortfield::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortfield::missing' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'missing' => 'string',
  ),
  'couchbase\\searchsortfield::mode' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'mode' => 'string',
  ),
  'couchbase\\searchsortfield::type' => 
  array (
    0 => 'Couchbase\\SearchSortField',
    'type' => 'string',
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
  'couchbase\\searchsortgeodistance::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\searchsortgeodistance::unit' => 
  array (
    0 => 'Couchbase\\SearchSortGeoDistance',
    'unit' => 'string',
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
  'couchbase\\searchsortid::jsonserialize' => 
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
  'couchbase\\searchsortscore::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\servicemissingexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\servicemissingexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\servicemissingexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\servicemissingexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\servicemissingexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\servicemissingexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\servicemissingexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\subdocumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\subdocumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\subdocumentexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\subdocumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\subdocumentexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\subdocumentexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\subdocumentexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\tempfailexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\tempfailexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\tempfailexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\tempfailexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\tempfailexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\tempfailexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\tempfailexception::ref' => 
  array (
    0 => 'null|string',
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
  'couchbase\\termrangesearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\termrangesearchquery::max' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'max' => 'string',
    'inclusive=' => 'bool',
  ),
  'couchbase\\termrangesearchquery::min' => 
  array (
    0 => 'Couchbase\\TermRangeSearchQuery',
    'min' => 'string',
    'inclusive=' => 'bool',
  ),
  'couchbase\\termsearchfacet::__construct' => 
  array (
    0 => 'void',
    'field' => 'string',
    'limit' => 'int',
  ),
  'couchbase\\termsearchfacet::jsonserialize' => 
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
  'couchbase\\termsearchquery::fuzziness' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'fuzziness' => 'int',
  ),
  'couchbase\\termsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\termsearchquery::prefixlength' => 
  array (
    0 => 'Couchbase\\TermSearchQuery',
    'prefixLength' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::analyticsthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
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
  'couchbase\\thresholdloggingtracer::requestspan' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'parent=' => 'Couchbase\\RequestSpan|null',
  ),
  'couchbase\\thresholdloggingtracer::samplesize' => 
  array (
    0 => 'mixed',
    'size' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::searchthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\thresholdloggingtracer::viewsthreshold' => 
  array (
    0 => 'mixed',
    'duration' => 'int',
  ),
  'couchbase\\timeoutexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\timeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\timeoutexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\timeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\timeoutexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\timeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\timeoutexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\touchoptions::timeout' => 
  array (
    0 => 'Couchbase\\TouchOptions',
    'arg' => 'int',
  ),
  'couchbase\\unlockoptions::timeout' => 
  array (
    0 => 'Couchbase\\UnlockOptions',
    'arg' => 'int',
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
  'couchbase\\upsertoptions::timeout' => 
  array (
    0 => 'Couchbase\\UpsertOptions',
    'arg' => 'int',
  ),
  'couchbase\\upsertuseroptions::domainname' => 
  array (
    0 => 'Couchbase\\DropUserOptions',
    'name' => 'string',
  ),
  'couchbase\\user::displayname' => 
  array (
    0 => 'string',
  ),
  'couchbase\\user::groups' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\user::roles' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\user::setdisplayname' => 
  array (
    0 => 'Couchbase\\User',
    'name' => 'string',
  ),
  'couchbase\\user::setgroups' => 
  array (
    0 => 'Couchbase\\User',
    'groups' => 'array<array-key, mixed>',
  ),
  'couchbase\\user::setpassword' => 
  array (
    0 => 'Couchbase\\User',
    'password' => 'string',
  ),
  'couchbase\\user::setroles' => 
  array (
    0 => 'Couchbase\\User',
    'roles' => 'array<array-key, mixed>',
  ),
  'couchbase\\user::setusername' => 
  array (
    0 => 'Couchbase\\User',
    'username' => 'string',
  ),
  'couchbase\\user::username' => 
  array (
    0 => 'string',
  ),
  'couchbase\\userandmetadata::domain' => 
  array (
    0 => 'string',
  ),
  'couchbase\\userandmetadata::effectiveroles' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\userandmetadata::externalgroups' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\userandmetadata::passwordchanged' => 
  array (
    0 => 'string',
  ),
  'couchbase\\userandmetadata::user' => 
  array (
    0 => 'Couchbase\\User',
  ),
  'couchbase\\usermanager::dropgroup' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\usermanager::dropuser' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'options=' => 'Couchbase\\DropUserOptions|null',
  ),
  'couchbase\\usermanager::getallgroups' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\usermanager::getallusers' => 
  array (
    0 => 'array<array-key, mixed>',
    'options=' => 'Couchbase\\GetAllUsersOptions|null',
  ),
  'couchbase\\usermanager::getgroup' => 
  array (
    0 => 'Couchbase\\Group',
    'name' => 'string',
  ),
  'couchbase\\usermanager::getroles' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\usermanager::getuser' => 
  array (
    0 => 'Couchbase\\UserAndMetadata',
    'name' => 'string',
    'options=' => 'Couchbase\\GetUserOptions|null',
  ),
  'couchbase\\usermanager::upsertgroup' => 
  array (
    0 => 'mixed',
    'group' => 'Couchbase\\Group',
  ),
  'couchbase\\usermanager::upsertuser' => 
  array (
    0 => 'mixed',
    'user' => 'Couchbase\\User',
    'options=' => 'Couchbase\\UpsertUserOptions|null',
  ),
  'couchbase\\valuetoobigexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\valuetoobigexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\valuetoobigexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\valuetoobigexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\valuetoobigexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\valuetoobigexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\valuetoobigexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\view::map' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::name' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::reduce' => 
  array (
    0 => 'string',
  ),
  'couchbase\\view::setmap' => 
  array (
    0 => 'Couchbase\\View',
    'mapJsCode' => 'string',
  ),
  'couchbase\\view::setname' => 
  array (
    0 => 'Couchbase\\View',
    'name' => 'string',
  ),
  'couchbase\\view::setreduce' => 
  array (
    0 => 'Couchbase\\View',
    'reduceJsCode' => 'string',
  ),
  'couchbase\\viewexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'couchbase\\viewexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'couchbase\\viewexception::context' => 
  array (
    0 => 'null|object',
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
  'couchbase\\viewexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'couchbase\\viewexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\viewexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'couchbase\\viewexception::ref' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\viewindexmanager::dropdesigndocument' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'couchbase\\viewindexmanager::getalldesigndocuments' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'couchbase\\viewindexmanager::getdesigndocument' => 
  array (
    0 => 'Couchbase\\DesignDocument',
    'name' => 'string',
  ),
  'couchbase\\viewindexmanager::upsertdesigndocument' => 
  array (
    0 => 'mixed',
    'document' => 'Couchbase\\DesignDocument',
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
  'couchbase\\viewoptions::idrange' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'start' => 'mixed',
    'end' => 'mixed',
    'inclusiveEnd=' => 'mixed',
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
    'args' => 'array<array-key, mixed>',
  ),
  'couchbase\\viewoptions::limit' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::order' => 
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
  'couchbase\\viewoptions::raw' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'key' => 'string',
    'value' => 'mixed',
  ),
  'couchbase\\viewoptions::reduce' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'bool',
  ),
  'couchbase\\viewoptions::scanconsistency' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::skip' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewoptions::timeout' => 
  array (
    0 => 'Couchbase\\ViewOptions',
    'arg' => 'int',
  ),
  'couchbase\\viewrow::document' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\viewrow::id' => 
  array (
    0 => 'null|string',
  ),
  'couchbase\\viewrow::key' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\viewrow::value' => 
  array (
    0 => 'mixed',
  ),
  'couchbase\\watchqueryindexesoptions::watchprimary' => 
  array (
    0 => 'Couchbase\\WatchQueryIndexesOptions',
    'shouldWatch' => 'bool',
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
  'couchbase\\wildcardsearchquery::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'count' => 
  array (
    0 => 'int',
    'value' => 'Countable|array<array-key, mixed>',
    'mode=' => 'int',
  ),
  'count_chars' => 
  array (
    0 => 'array<array-key, mixed>|string',
    'string' => 'string',
    'mode=' => 'int',
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
  'ctype_graph' => 
  array (
    0 => 'bool',
    'text' => 'mixed',
  ),
  'ctype_lower' => 
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
    0 => 'false|string',
    'handle' => 'CurlHandle',
    'string' => 'string',
  ),
  'curl_exec' => 
  array (
    0 => 'bool|string',
    'handle' => 'CurlHandle',
  ),
  'curl_file_create' => 
  array (
    0 => 'CURLFile',
    'filename' => 'string',
    'mime_type=' => 'null|string',
    'posted_filename=' => 'null|string',
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
    'url=' => 'null|string',
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
    '&still_running' => 'mixed',
  ),
  'curl_multi_get_handles' => 
  array (
    0 => 'array<array-key, mixed>',
    'multi_handle' => 'CurlMultiHandle',
  ),
  'curl_multi_getcontent' => 
  array (
    0 => 'null|string',
    'handle' => 'CurlHandle',
  ),
  'curl_multi_info_read' => 
  array (
    0 => 'array<array-key, mixed>|false',
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
  'curl_multi_setopt' => 
  array (
    0 => 'bool',
    'multi_handle' => 'CurlMultiHandle',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'curl_multi_strerror' => 
  array (
    0 => 'null|string',
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
  'curl_setopt' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'curl_setopt_array' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
    'options' => 'array<array-key, mixed>',
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
  'curl_share_init_persistent' => 
  array (
    0 => 'CurlSharePersistentHandle',
    'share_options' => 'array<array-key, mixed>',
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
    0 => 'null|string',
    'error_code' => 'int',
  ),
  'curl_strerror' => 
  array (
    0 => 'null|string',
    'error_code' => 'int',
  ),
  'curl_unescape' => 
  array (
    0 => 'false|string',
    'handle' => 'CurlHandle',
    'string' => 'string',
  ),
  'curl_upkeep' => 
  array (
    0 => 'bool',
    'handle' => 'CurlHandle',
  ),
  'curl_version' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'curlfile::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'mime_type=' => 'null|string',
    'posted_filename=' => 'null|string',
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
  'current' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>|object',
  ),
  'date' => 
  array (
    0 => 'string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'date_add' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'date_create' => 
  array (
    0 => 'DateTime|false',
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
  'date_create_immutable' => 
  array (
    0 => 'DateTimeImmutable|false',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_create_immutable_from_format' => 
  array (
    0 => 'DateTimeImmutable|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'date_date_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'year' => 'int',
    'month' => 'int',
    'day' => 'int',
  ),
  'date_default_timezone_get' => 
  array (
    0 => 'string',
  ),
  'date_default_timezone_set' => 
  array (
    0 => 'bool',
    'timezoneId' => 'string',
  ),
  'date_diff' => 
  array (
    0 => 'DateInterval',
    'baseObject' => 'DateTimeInterface',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'date_format' => 
  array (
    0 => 'string',
    'object' => 'DateTimeInterface',
    'format' => 'string',
  ),
  'date_get_last_errors' => 
  array (
    0 => 'array<array-key, mixed>|false',
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
  'date_isodate_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'year' => 'int',
    'week' => 'int',
    'dayOfWeek=' => 'int',
  ),
  'date_modify' => 
  array (
    0 => 'DateTime|false',
    'object' => 'DateTime',
    'modifier' => 'string',
  ),
  'date_offset_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeInterface',
  ),
  'date_parse' => 
  array (
    0 => 'array<array-key, mixed>',
    'datetime' => 'string',
  ),
  'date_parse_from_format' => 
  array (
    0 => 'array<array-key, mixed>',
    'format' => 'string',
    'datetime' => 'string',
  ),
  'date_sub' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'date_sun_info' => 
  array (
    0 => 'array<array-key, mixed>',
    'timestamp' => 'int',
    'latitude' => 'float',
    'longitude' => 'float',
  ),
  'date_sunrise' => 
  array (
    0 => 'false|float|int|string',
    'timestamp' => 'int',
    'returnFormat=' => 'int',
    'latitude=' => 'float|null',
    'longitude=' => 'float|null',
    'zenith=' => 'float|null',
    'utcOffset=' => 'float|null',
  ),
  'date_sunset' => 
  array (
    0 => 'false|float|int|string',
    'timestamp' => 'int',
    'returnFormat=' => 'int',
    'latitude=' => 'float|null',
    'longitude=' => 'float|null',
    'zenith=' => 'float|null',
    'utcOffset=' => 'float|null',
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
  'date_timestamp_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeInterface',
  ),
  'date_timestamp_set' => 
  array (
    0 => 'DateTime',
    'object' => 'DateTime',
    'timestamp' => 'int',
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
  'dateerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateerror::__wakeup' => 
  array (
    0 => 'void',
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
  'dateerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateerror::gettraceasstring' => 
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
  'dateexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateexception::__wakeup' => 
  array (
    0 => 'void',
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
  'dateexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'datefmt_create' => 
  array (
    0 => 'IntlDateFormatter|null',
    'locale' => 'null|string',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'calendar=' => 'IntlCalendar|int|null',
    'pattern=' => 'null|string',
  ),
  'datefmt_format' => 
  array (
    0 => 'false|string',
    'formatter' => 'IntlDateFormatter',
    'datetime' => 'mixed',
  ),
  'datefmt_format_object' => 
  array (
    0 => 'false|string',
    'datetime' => 'mixed',
    'format=' => 'mixed',
    'locale=' => 'null|string',
  ),
  'datefmt_get_calendar' => 
  array (
    0 => 'false|int',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_calendar_object' => 
  array (
    0 => 'IntlCalendar|false|null',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_datetype' => 
  array (
    0 => 'false|int',
    'formatter' => 'IntlDateFormatter',
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
  'datefmt_get_locale' => 
  array (
    0 => 'false|string',
    'formatter' => 'IntlDateFormatter',
    'type=' => 'int',
  ),
  'datefmt_get_pattern' => 
  array (
    0 => 'false|string',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_timetype' => 
  array (
    0 => 'false|int',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_timezone' => 
  array (
    0 => 'IntlTimeZone|false',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_get_timezone_id' => 
  array (
    0 => 'false|string',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_is_lenient' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
  ),
  'datefmt_localtime' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'datefmt_parse' => 
  array (
    0 => 'false|float|int',
    'formatter' => 'IntlDateFormatter',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'datefmt_set_calendar' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'calendar' => 'IntlCalendar|int|null',
  ),
  'datefmt_set_lenient' => 
  array (
    0 => 'void',
    'formatter' => 'IntlDateFormatter',
    'lenient' => 'bool',
  ),
  'datefmt_set_pattern' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'pattern' => 'string',
  ),
  'datefmt_set_timezone' => 
  array (
    0 => 'bool',
    'formatter' => 'IntlDateFormatter',
    'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
  ),
  'dateinterval::__construct' => 
  array (
    0 => 'void',
    'duration' => 'string',
  ),
  'dateinterval::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateinterval::__set_state' => 
  array (
    0 => 'DateInterval',
    'array' => 'array<array-key, mixed>',
  ),
  'dateinterval::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'dateinterval::__wakeup' => 
  array (
    0 => 'void',
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
  'dateinvalidoperationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dateinvalidoperationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::__wakeup' => 
  array (
    0 => 'void',
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
  'dateinvalidoperationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateinvalidoperationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateinvalidoperationexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateinvalidoperationexception::gettraceasstring' => 
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
  'dateinvalidtimezoneexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::__wakeup' => 
  array (
    0 => 'void',
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
  'dateinvalidtimezoneexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateinvalidtimezoneexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateinvalidtimezoneexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateinvalidtimezoneexception::gettraceasstring' => 
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
  'datemalformedintervalstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::__wakeup' => 
  array (
    0 => 'void',
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
  'datemalformedintervalstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedintervalstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedintervalstringexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datemalformedintervalstringexception::gettraceasstring' => 
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
  'datemalformedperiodstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::__wakeup' => 
  array (
    0 => 'void',
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
  'datemalformedperiodstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedperiodstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedperiodstringexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datemalformedperiodstringexception::gettraceasstring' => 
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
  'datemalformedstringexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::__wakeup' => 
  array (
    0 => 'void',
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
  'datemalformedstringexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'datemalformedstringexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'datemalformedstringexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datemalformedstringexception::gettraceasstring' => 
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
  'dateobjecterror::__tostring' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::__wakeup' => 
  array (
    0 => 'void',
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
  'dateobjecterror::getmessage' => 
  array (
    0 => 'string',
  ),
  'dateobjecterror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dateobjecterror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateobjecterror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dateperiod::__construct' => 
  array (
    0 => 'void',
    'start' => 'mixed',
    'interval=' => 'mixed',
    'end=' => 'mixed',
    'options=' => 'mixed',
  ),
  'dateperiod::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dateperiod::__set_state' => 
  array (
    0 => 'DatePeriod',
    'array' => 'array<array-key, mixed>',
  ),
  'dateperiod::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'dateperiod::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dateperiod::createfromiso8601string' => 
  array (
    0 => 'static',
    'specification' => 'string',
    'options=' => 'int',
  ),
  'dateperiod::getdateinterval' => 
  array (
    0 => 'DateInterval',
  ),
  'dateperiod::getenddate' => 
  array (
    0 => 'DateTimeInterface|null',
  ),
  'dateperiod::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dateperiod::getrecurrences' => 
  array (
    0 => 'int|null',
  ),
  'dateperiod::getstartdate' => 
  array (
    0 => 'DateTimeInterface',
  ),
  'daterangeerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'daterangeerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::__wakeup' => 
  array (
    0 => 'void',
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
  'daterangeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'daterangeerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'daterangeerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'daterangeerror::gettraceasstring' => 
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
    0 => 'array<array-key, mixed>',
  ),
  'datetime::__set_state' => 
  array (
    0 => 'DateTime',
    'array' => 'array<array-key, mixed>',
  ),
  'datetime::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'datetime::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetime::add' => 
  array (
    0 => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'datetime::createfromformat' => 
  array (
    0 => 'DateTime|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
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
  'datetime::createfromtimestamp' => 
  array (
    0 => 'static',
    'timestamp' => 'float|int',
  ),
  'datetime::diff' => 
  array (
    0 => 'DateInterval',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'datetime::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'datetime::getlasterrors' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'datetime::getmicrosecond' => 
  array (
    0 => 'int',
  ),
  'datetime::getoffset' => 
  array (
    0 => 'int',
  ),
  'datetime::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'datetime::gettimezone' => 
  array (
    0 => 'DateTimeZone|false',
  ),
  'datetime::modify' => 
  array (
    0 => 'DateTime',
    'modifier' => 'string',
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
  'datetime::setmicrosecond' => 
  array (
    0 => 'static',
    'microsecond' => 'int',
  ),
  'datetime::settime' => 
  array (
    0 => 'DateTime',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'datetime::settimestamp' => 
  array (
    0 => 'DateTime',
    'timestamp' => 'int',
  ),
  'datetime::settimezone' => 
  array (
    0 => 'DateTime',
    'timezone' => 'DateTimeZone',
  ),
  'datetime::sub' => 
  array (
    0 => 'DateTime',
    'interval' => 'DateInterval',
  ),
  'datetimeimmutable::__construct' => 
  array (
    0 => 'void',
    'datetime=' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetimeimmutable::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datetimeimmutable::__set_state' => 
  array (
    0 => 'DateTimeImmutable',
    'array' => 'array<array-key, mixed>',
  ),
  'datetimeimmutable::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'datetimeimmutable::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetimeimmutable::add' => 
  array (
    0 => 'DateTimeImmutable',
    'interval' => 'DateInterval',
  ),
  'datetimeimmutable::createfromformat' => 
  array (
    0 => 'DateTimeImmutable|false',
    'format' => 'string',
    'datetime' => 'string',
    'timezone=' => 'DateTimeZone|null',
  ),
  'datetimeimmutable::createfrominterface' => 
  array (
    0 => 'DateTimeImmutable',
    'object' => 'DateTimeInterface',
  ),
  'datetimeimmutable::createfrommutable' => 
  array (
    0 => 'static',
    'object' => 'DateTime',
  ),
  'datetimeimmutable::createfromtimestamp' => 
  array (
    0 => 'static',
    'timestamp' => 'float|int',
  ),
  'datetimeimmutable::diff' => 
  array (
    0 => 'DateInterval',
    'targetObject' => 'DateTimeInterface',
    'absolute=' => 'bool',
  ),
  'datetimeimmutable::format' => 
  array (
    0 => 'string',
    'format' => 'string',
  ),
  'datetimeimmutable::getlasterrors' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'datetimeimmutable::getmicrosecond' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::getoffset' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'datetimeimmutable::gettimezone' => 
  array (
    0 => 'DateTimeZone|false',
  ),
  'datetimeimmutable::modify' => 
  array (
    0 => 'DateTimeImmutable',
    'modifier' => 'string',
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
  'datetimeimmutable::setmicrosecond' => 
  array (
    0 => 'static',
    'microsecond' => 'int',
  ),
  'datetimeimmutable::settime' => 
  array (
    0 => 'DateTimeImmutable',
    'hour' => 'int',
    'minute' => 'int',
    'second=' => 'int',
    'microsecond=' => 'int',
  ),
  'datetimeimmutable::settimestamp' => 
  array (
    0 => 'DateTimeImmutable',
    'timestamp' => 'int',
  ),
  'datetimeimmutable::settimezone' => 
  array (
    0 => 'DateTimeImmutable',
    'timezone' => 'DateTimeZone',
  ),
  'datetimeimmutable::sub' => 
  array (
    0 => 'DateTimeImmutable',
    'interval' => 'DateInterval',
  ),
  'datetimezone::__construct' => 
  array (
    0 => 'void',
    'timezone' => 'string',
  ),
  'datetimezone::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datetimezone::__set_state' => 
  array (
    0 => 'DateTimeZone',
    'array' => 'array<array-key, mixed>',
  ),
  'datetimezone::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'datetimezone::__wakeup' => 
  array (
    0 => 'void',
  ),
  'datetimezone::getlocation' => 
  array (
    0 => 'array<array-key, mixed>|false',
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
    0 => 'array<array-key, mixed>|false',
    'timestampBegin=' => 'int',
    'timestampEnd=' => 'int',
  ),
  'datetimezone::listabbreviations' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'datetimezone::listidentifiers' => 
  array (
    0 => 'array<array-key, mixed>',
    'timezoneGroup=' => 'int',
    'countryCode=' => 'null|string',
  ),
  'db2_autocommit' => 
  array (
    0 => 'bool|int',
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
  'db2_client_info' => 
  array (
    0 => 'false|stdClass',
    'connection' => 'mixed',
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
    'qualifier=' => 'null|string',
    'schema=' => 'null|string',
    'table_name=' => 'null|string',
    'column_name=' => 'null|string',
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
  'db2_commit' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_conn_error' => 
  array (
    0 => 'mixed',
    'connection=' => 'mixed',
  ),
  'db2_conn_errormsg' => 
  array (
    0 => 'mixed',
    'connection=' => 'mixed',
  ),
  'db2_connect' => 
  array (
    0 => 'mixed',
    'database' => 'string',
    'username' => 'null|string',
    'password' => 'null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'db2_cursor_type' => 
  array (
    0 => 'int',
    'stmt' => 'mixed',
  ),
  'db2_escape_string' => 
  array (
    0 => 'string',
    'string_literal' => 'string',
  ),
  'db2_exec' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'statement' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'db2_execute' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
    'parameters=' => 'array<array-key, mixed>',
  ),
  'db2_fetch_array' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_assoc' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_both' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_object' => 
  array (
    0 => 'false|stdClass',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_fetch_row' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
    'row_number=' => 'int|null',
  ),
  'db2_field_display_size' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_name' => 
  array (
    0 => 'false|string',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_num' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_precision' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_scale' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_type' => 
  array (
    0 => 'false|string',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_field_width' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_foreign_keys' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'null|string',
    'schema' => 'null|string',
    'table_name' => 'string',
  ),
  'db2_foreignkeys' => 
  array (
    0 => 'mixed',
  ),
  'db2_free_result' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
  ),
  'db2_free_stmt' => 
  array (
    0 => 'bool',
    'stmt' => 'mixed',
  ),
  'db2_get_option' => 
  array (
    0 => 'false|string',
    'resource' => 'mixed',
    'option' => 'string',
  ),
  'db2_last_insert_id' => 
  array (
    0 => 'null|string',
    'resource' => 'mixed',
  ),
  'db2_lob_read' => 
  array (
    0 => 'false|string',
    'stmt' => 'mixed',
    'colnum' => 'int',
    'length' => 'int',
  ),
  'db2_next_result' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
  ),
  'db2_num_fields' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
  ),
  'db2_num_rows' => 
  array (
    0 => 'false|int',
    'stmt' => 'mixed',
  ),
  'db2_pclose' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_pconnect' => 
  array (
    0 => 'mixed',
    'database' => 'string',
    'username' => 'null|string',
    'password' => 'null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'db2_prepare' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'statement' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'db2_primary_keys' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'null|string',
    'schema' => 'null|string',
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
    'qualifier' => 'null|string',
    'schema' => 'string',
    'procedure' => 'string',
    'parameter' => 'null|string',
  ),
  'db2_procedurecolumns' => 
  array (
    0 => 'mixed',
  ),
  'db2_procedures' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'null|string',
    'schema' => 'string',
    'procedure' => 'string',
  ),
  'db2_result' => 
  array (
    0 => 'mixed',
    'stmt' => 'mixed',
    'column' => 'int|string',
  ),
  'db2_rollback' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
  ),
  'db2_server_info' => 
  array (
    0 => 'false|stdClass',
    'connection' => 'mixed',
  ),
  'db2_set_option' => 
  array (
    0 => 'bool',
    'resource' => 'mixed',
    'options' => 'array<array-key, mixed>',
    'type' => 'int',
  ),
  'db2_setoption' => 
  array (
    0 => 'bool',
  ),
  'db2_special_columns' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier' => 'null|string',
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
    'qualifier' => 'null|string',
    'schema' => 'null|string',
    'table_name' => 'string',
    'unique' => 'bool',
  ),
  'db2_stmt_error' => 
  array (
    0 => 'mixed',
    'stmt=' => 'mixed',
  ),
  'db2_stmt_errormsg' => 
  array (
    0 => 'mixed',
    'stmt=' => 'mixed',
  ),
  'db2_table_privileges' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'null|string',
    'schema=' => 'null|string',
    'table_name=' => 'null|string',
  ),
  'db2_tableprivileges' => 
  array (
    0 => 'mixed',
  ),
  'db2_tables' => 
  array (
    0 => 'mixed',
    'connection' => 'mixed',
    'qualifier=' => 'null|string',
    'schema=' => 'null|string',
    'table_name=' => 'null|string',
    'table_type=' => 'null|string',
  ),
  'debug_backtrace' => 
  array (
    0 => 'array<array-key, mixed>',
    'options=' => 'int',
    'limit=' => 'int',
  ),
  'debug_print_backtrace' => 
  array (
    0 => 'void',
    'options=' => 'int',
    'limit=' => 'int',
  ),
  'debug_zval_dump' => 
  array (
    0 => 'void',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'decbin' => 
  array (
    0 => 'string',
    'num' => 'int',
  ),
  'dechex' => 
  array (
    0 => 'string',
    'num' => 'int',
  ),
  'decoct' => 
  array (
    0 => 'string',
    'num' => 'int',
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
  'deflate_add' => 
  array (
    0 => 'false|string',
    'context' => 'DeflateContext',
    'data' => 'string',
    'flush_mode=' => 'int',
  ),
  'deflate_init' => 
  array (
    0 => 'DeflateContext|false',
    'encoding' => 'int',
    'options=' => 'array<array-key, mixed>|object',
  ),
  'deg2rad' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'deprecated::__construct' => 
  array (
    0 => 'void',
    'message=' => 'null|string',
    'since=' => 'null|string',
  ),
  'die' => 
  array (
    0 => 'never',
    'status=' => 'int|string',
  ),
  'dir' => 
  array (
    0 => 'Directory|false',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'directory::close' => 
  array (
    0 => 'void',
  ),
  'directory::read' => 
  array (
    0 => 'false|string',
  ),
  'directory::rewind' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
  ),
  'directoryiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'directoryiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'directoryiterator::getatime' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'directoryiterator::getctime' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'directoryiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getinode' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'directoryiterator::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getowner' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'directoryiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'directoryiterator::getperms' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'directoryiterator::getsize' => 
  array (
    0 => 'false|int',
  ),
  'directoryiterator::gettype' => 
  array (
    0 => 'false|string',
  ),
  'directoryiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isdot' => 
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
  'directoryiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'directoryiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'directoryiterator::next' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'directoryiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'directoryiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
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
  'directoryiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'dirname' => 
  array (
    0 => 'string',
    'path' => 'string',
    'levels=' => 'int',
  ),
  'disk_free_space' => 
  array (
    0 => 'false|float',
    'directory' => 'string',
  ),
  'disk_total_space' => 
  array (
    0 => 'false|float',
    'directory' => 'string',
  ),
  'diskfreespace' => 
  array (
    0 => 'false|float',
    'directory' => 'string',
  ),
  'divisionbyzeroerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'divisionbyzeroerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::__wakeup' => 
  array (
    0 => 'void',
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
  'divisionbyzeroerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'divisionbyzeroerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'divisionbyzeroerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'divisionbyzeroerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dl' => 
  array (
    0 => 'bool',
    'extension_filename' => 'string',
  ),
  'dns_check_record' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    'type=' => 'string',
  ),
  'dns_get_mx' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'mixed',
    '&weights=' => 'mixed',
  ),
  'dns_get_record' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'hostname' => 'string',
    'type=' => 'int',
    '&authoritative_name_servers=' => 'mixed',
    '&additional_records=' => 'mixed',
    'raw=' => 'bool',
  ),
  'dom\\adjacentposition::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\adjacentposition::from' => 
  array (
    0 => 'static',
    'value' => 'int|string',
  ),
  'dom\\adjacentposition::tryfrom' => 
  array (
    0 => 'null|static',
    'value' => 'int|string',
  ),
  'dom\\attr::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\attr::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\attr::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\attr::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\attr::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\attr::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\attr::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\attr::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\attr::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\attr::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\attr::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\attr::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
  ),
  'dom\\attr::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\attr::isid' => 
  array (
    0 => 'bool',
  ),
  'dom\\attr::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'Dom\\Node|null',
  ),
  'dom\\attr::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\attr::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\attr::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\attr::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\attr::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\attr::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\cdatasection::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\cdatasection::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\cdatasection::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\cdatasection::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\cdatasection::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\cdatasection::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\cdatasection::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\cdatasection::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\cdatasection::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\cdatasection::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\cdatasection::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\cdatasection::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\cdatasection::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\cdatasection::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\cdatasection::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\cdatasection::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\cdatasection::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\cdatasection::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\cdatasection::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\cdatasection::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\cdatasection::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\cdatasection::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\cdatasection::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\characterdata::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\characterdata::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\characterdata::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\characterdata::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\characterdata::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\characterdata::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\characterdata::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\characterdata::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\characterdata::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\characterdata::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\characterdata::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\characterdata::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\characterdata::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\characterdata::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\characterdata::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\characterdata::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\characterdata::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\characterdata::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\characterdata::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\characterdata::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\characterdata::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\characterdata::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\comment::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\comment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\comment::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\comment::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\comment::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\comment::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\comment::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\comment::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\comment::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\comment::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\comment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\comment::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\comment::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\comment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\comment::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\comment::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\comment::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\comment::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\comment::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\comment::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\comment::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\comment::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\document::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\document::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\document::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\document::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\document::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\document::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\document::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\document::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\document::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\document::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
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
  'dom\\document::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\document::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\document::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\document::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\document::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\document::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\document::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\document::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\document::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\document::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\document::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\document::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\document::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\document::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\document::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\document::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\document::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\document::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\document::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\document::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\document::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\document::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'null|string',
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
  'dom\\document::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\document::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\document::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\documentfragment::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\documentfragment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\documentfragment::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documentfragment::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\documentfragment::appendxml' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'dom\\documentfragment::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\documentfragment::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\documentfragment::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\documentfragment::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\documentfragment::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\documentfragment::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\documentfragment::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\documentfragment::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\documentfragment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\documentfragment::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\documentfragment::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\documentfragment::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\documentfragment::prepend' => 
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
  'dom\\documentfragment::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documentfragment::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documentfragment::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\documenttype::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\documenttype::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\documenttype::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\documenttype::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\documenttype::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\documenttype::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\documenttype::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\documenttype::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\documenttype::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\documenttype::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\documenttype::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\documenttype::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\documenttype::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\documenttype::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\documenttype::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documenttype::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\documenttype::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\domexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'dom\\domexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::__wakeup' => 
  array (
    0 => 'void',
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
  'dom\\domexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'dom\\domexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'dom\\domexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\domexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'dom\\dtdnamednodemap::count' => 
  array (
    0 => 'int',
  ),
  'dom\\dtdnamednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\dtdnamednodemap::getnameditem' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null',
    'qualifiedName' => 'string',
  ),
  'dom\\dtdnamednodemap::getnameditemns' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\dtdnamednodemap::item' => 
  array (
    0 => 'Dom\\Entity|Dom\\Notation|null',
    'index' => 'int',
  ),
  'dom\\element::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\element::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\element::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\element::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\element::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\element::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\element::closest' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
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
  'dom\\element::getattribute' => 
  array (
    0 => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getattributenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\element::getattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\element::getattributens' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\element::getdescendantnamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\element::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\element::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\element::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\element::getinscopenamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\element::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\element::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\element::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\element::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'dom\\element::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\element::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'dom\\element::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\element::insertadjacentelement' => 
  array (
    0 => 'Dom\\Element|null',
    'where' => 'Dom\\AdjacentPosition',
    'element' => 'Dom\\Element',
  ),
  'dom\\element::insertadjacenthtml' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'string' => 'string',
  ),
  'dom\\element::insertadjacenttext' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'data' => 'string',
  ),
  'dom\\element::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\element::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\element::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\element::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\element::matches' => 
  array (
    0 => 'bool',
    'selectors' => 'string',
  ),
  'dom\\element::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\element::prepend' => 
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
  'dom\\element::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\element::removeattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
  ),
  'dom\\element::removeattributenode' => 
  array (
    0 => 'Dom\\Attr',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\element::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\element::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\element::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\element::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\element::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\element::setattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
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
  'dom\\element::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\element::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\element::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'Dom\\Attr',
    'isId' => 'bool',
  ),
  'dom\\element::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\element::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\entity::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\entity::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\entity::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\entity::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\entity::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\entity::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\entity::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\entity::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\entity::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\entity::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\entity::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\entity::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\entity::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\entity::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\entity::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\entity::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entity::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entityreference::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\entityreference::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\entityreference::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\entityreference::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\entityreference::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\entityreference::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\entityreference::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\entityreference::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\entityreference::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\entityreference::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\entityreference::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\entityreference::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\entityreference::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\entityreference::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\entityreference::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\entityreference::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\entityreference::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmlcollection::count' => 
  array (
    0 => 'int',
  ),
  'dom\\htmlcollection::getiterator' => 
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
  'dom\\htmldocument::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\htmldocument::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\htmldocument::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmldocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmldocument::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmldocument::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\htmldocument::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\htmldocument::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\htmldocument::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\htmldocument::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
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
  'dom\\htmldocument::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\htmldocument::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\htmldocument::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
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
    'overrideEncoding=' => 'null|string',
  ),
  'dom\\htmldocument::createfromstring' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'source' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'null|string',
  ),
  'dom\\htmldocument::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\htmldocument::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\htmldocument::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\htmldocument::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\htmldocument::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\htmldocument::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmldocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\htmldocument::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\htmldocument::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\htmldocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmldocument::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\htmldocument::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\htmldocument::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\htmldocument::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\htmldocument::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\htmldocument::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\htmldocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\htmldocument::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'null|string',
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
  'dom\\htmldocument::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmldocument::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmldocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmldocument::savehtml' => 
  array (
    0 => 'string',
    'node=' => 'Dom\\Node|null',
  ),
  'dom\\htmldocument::savehtmlfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'dom\\htmldocument::savexml' => 
  array (
    0 => 'false|string',
    'node=' => 'Dom\\Node|null',
    'options=' => 'int',
  ),
  'dom\\htmldocument::savexmlfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'options=' => 'int',
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
  'dom\\htmlelement::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\htmlelement::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\htmlelement::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\htmlelement::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\htmlelement::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
  ),
  'dom\\htmlelement::closest' => 
  array (
    0 => 'Dom\\Element|null',
    'selectors' => 'string',
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
  'dom\\htmlelement::getattribute' => 
  array (
    0 => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getattributenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\htmlelement::getattributenode' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getattributenodens' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmlelement::getattributens' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmlelement::getdescendantnamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\htmlelement::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\htmlelement::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmlelement::getinscopenamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\htmlelement::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\htmlelement::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\htmlelement::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\htmlelement::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmlelement::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmlelement::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\htmlelement::insertadjacentelement' => 
  array (
    0 => 'Dom\\Element|null',
    'where' => 'Dom\\AdjacentPosition',
    'element' => 'Dom\\Element',
  ),
  'dom\\htmlelement::insertadjacenthtml' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'string' => 'string',
  ),
  'dom\\htmlelement::insertadjacenttext' => 
  array (
    0 => 'void',
    'where' => 'Dom\\AdjacentPosition',
    'data' => 'string',
  ),
  'dom\\htmlelement::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\htmlelement::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\htmlelement::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\htmlelement::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\htmlelement::matches' => 
  array (
    0 => 'bool',
    'selectors' => 'string',
  ),
  'dom\\htmlelement::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::prepend' => 
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
  'dom\\htmlelement::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\htmlelement::removeattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::removeattributenode' => 
  array (
    0 => 'Dom\\Attr',
    'attr' => 'Dom\\Attr',
  ),
  'dom\\htmlelement::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\htmlelement::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmlelement::rename' => 
  array (
    0 => 'void',
    'namespaceURI' => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\htmlelement::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\htmlelement::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\htmlelement::setattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
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
  'dom\\htmlelement::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'dom\\htmlelement::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'Dom\\Attr',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'dom\\htmlelement::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\implementation::createdocument' => 
  array (
    0 => 'Dom\\XMLDocument',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'doctype=' => 'Dom\\DocumentType|null',
  ),
  'dom\\implementation::createdocumenttype' => 
  array (
    0 => 'Dom\\DocumentType',
    'qualifiedName' => 'string',
    'publicId' => 'string',
    'systemId' => 'string',
  ),
  'dom\\implementation::createhtmldocument' => 
  array (
    0 => 'Dom\\HTMLDocument',
    'title=' => 'null|string',
  ),
  'dom\\import_simplexml' => 
  array (
    0 => 'Dom\\Attr|Dom\\Element',
    'node' => 'object',
  ),
  'dom\\namednodemap::count' => 
  array (
    0 => 'int',
  ),
  'dom\\namednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'dom\\namednodemap::getnameditem' => 
  array (
    0 => 'Dom\\Attr|null',
    'qualifiedName' => 'string',
  ),
  'dom\\namednodemap::getnameditemns' => 
  array (
    0 => 'Dom\\Attr|null',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\namednodemap::item' => 
  array (
    0 => 'Dom\\Attr|null',
    'index' => 'int',
  ),
  'dom\\namespaceinfo::__construct' => 
  array (
    0 => 'void',
  ),
  'dom\\node::__construct' => 
  array (
    0 => 'void',
  ),
  'dom\\node::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\node::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\node::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\node::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\node::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\node::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\node::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\node::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\node::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\node::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\node::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\node::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\node::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\node::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\node::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\node::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\node::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
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
  'dom\\notation::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\notation::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\notation::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\notation::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\notation::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\notation::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\notation::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\notation::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\notation::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\notation::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\notation::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\notation::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\notation::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\notation::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\notation::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\notation::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\notation::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\processinginstruction::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\processinginstruction::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\processinginstruction::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\processinginstruction::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\processinginstruction::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\processinginstruction::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\processinginstruction::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\processinginstruction::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\processinginstruction::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\processinginstruction::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\processinginstruction::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\processinginstruction::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\processinginstruction::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\processinginstruction::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\processinginstruction::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\processinginstruction::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\processinginstruction::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\processinginstruction::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\processinginstruction::substringdata' => 
  array (
    0 => 'string',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\text::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\text::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\text::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\text::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\text::appenddata' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'dom\\text::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\text::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\text::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\text::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\text::deletedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
  ),
  'dom\\text::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\text::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\text::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\text::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\text::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\text::insertdata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'data' => 'string',
  ),
  'dom\\text::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\text::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\text::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\text::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\text::remove' => 
  array (
    0 => 'void',
  ),
  'dom\\text::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\text::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\text::replacedata' => 
  array (
    0 => 'void',
    'offset' => 'int',
    'count' => 'int',
    'data' => 'string',
  ),
  'dom\\text::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\tokenlist::__construct' => 
  array (
    0 => 'void',
  ),
  'dom\\tokenlist::add' => 
  array (
    0 => 'void',
    '...tokens=' => 'string',
  ),
  'dom\\tokenlist::contains' => 
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
  'dom\\tokenlist::item' => 
  array (
    0 => 'null|string',
    'index' => 'int',
  ),
  'dom\\tokenlist::remove' => 
  array (
    0 => 'void',
    '...tokens=' => 'string',
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
  'dom\\tokenlist::toggle' => 
  array (
    0 => 'bool',
    'token' => 'string',
    'force=' => 'bool|null',
  ),
  'dom\\xmldocument::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'dom\\xmldocument::__wakeup' => 
  array (
    0 => 'void',
  ),
  'dom\\xmldocument::adoptnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\xmldocument::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\xmldocument::appendchild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
  ),
  'dom\\xmldocument::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\xmldocument::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'dom\\xmldocument::clonenode' => 
  array (
    0 => 'Dom\\Node',
    'deep=' => 'bool',
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
  'dom\\xmldocument::createattribute' => 
  array (
    0 => 'Dom\\Attr',
    'localName' => 'string',
  ),
  'dom\\xmldocument::createattributens' => 
  array (
    0 => 'Dom\\Attr',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
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
  'dom\\xmldocument::createdocumentfragment' => 
  array (
    0 => 'Dom\\DocumentFragment',
  ),
  'dom\\xmldocument::createelement' => 
  array (
    0 => 'Dom\\Element',
    'localName' => 'string',
  ),
  'dom\\xmldocument::createelementns' => 
  array (
    0 => 'Dom\\Element',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
  ),
  'dom\\xmldocument::createempty' => 
  array (
    0 => 'Dom\\XMLDocument',
    'version=' => 'string',
    'encoding=' => 'string',
  ),
  'dom\\xmldocument::createentityreference' => 
  array (
    0 => 'Dom\\EntityReference',
    'name' => 'string',
  ),
  'dom\\xmldocument::createfromfile' => 
  array (
    0 => 'Dom\\XMLDocument',
    'path' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'null|string',
  ),
  'dom\\xmldocument::createfromstring' => 
  array (
    0 => 'Dom\\XMLDocument',
    'source' => 'string',
    'options=' => 'int',
    'overrideEncoding=' => 'null|string',
  ),
  'dom\\xmldocument::createprocessinginstruction' => 
  array (
    0 => 'Dom\\ProcessingInstruction',
    'target' => 'string',
    'data' => 'string',
  ),
  'dom\\xmldocument::createtextnode' => 
  array (
    0 => 'Dom\\Text',
    'data' => 'string',
  ),
  'dom\\xmldocument::getelementbyid' => 
  array (
    0 => 'Dom\\Element|null',
    'elementId' => 'string',
  ),
  'dom\\xmldocument::getelementsbyclassname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'classNames' => 'string',
  ),
  'dom\\xmldocument::getelementsbytagname' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'qualifiedName' => 'string',
  ),
  'dom\\xmldocument::getelementsbytagnamens' => 
  array (
    0 => 'Dom\\HTMLCollection',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'dom\\xmldocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'dom\\xmldocument::getnodepath' => 
  array (
    0 => 'string',
  ),
  'dom\\xmldocument::getrootnode' => 
  array (
    0 => 'Dom\\Node',
    'options=' => 'array<array-key, mixed>',
  ),
  'dom\\xmldocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'dom\\xmldocument::importlegacynode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'DOMNode',
    'deep=' => 'bool',
  ),
  'dom\\xmldocument::importnode' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node|null',
    'deep=' => 'bool',
  ),
  'dom\\xmldocument::insertbefore' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node|null',
  ),
  'dom\\xmldocument::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
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
  'dom\\xmldocument::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'dom\\xmldocument::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'null|string',
  ),
  'dom\\xmldocument::normalize' => 
  array (
    0 => 'void',
  ),
  'dom\\xmldocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
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
  'dom\\xmldocument::registernodeclass' => 
  array (
    0 => 'void',
    'baseClass' => 'string',
    'extendedClass' => 'null|string',
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
  'dom\\xmldocument::removechild' => 
  array (
    0 => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\xmldocument::replacechild' => 
  array (
    0 => 'Dom\\Node',
    'node' => 'Dom\\Node',
    'child' => 'Dom\\Node',
  ),
  'dom\\xmldocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'Dom\\Node|string',
  ),
  'dom\\xmldocument::savexml' => 
  array (
    0 => 'false|string',
    'node=' => 'Dom\\Node|null',
    'options=' => 'int',
  ),
  'dom\\xmldocument::savexmlfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'options=' => 'int',
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
  'dom\\xmldocument::validate' => 
  array (
    0 => 'bool',
  ),
  'dom\\xmldocument::xinclude' => 
  array (
    0 => 'int',
    'options=' => 'int',
  ),
  'dom\\xpath::__construct' => 
  array (
    0 => 'void',
    'document' => 'Dom\\Document',
    'registerNodeNS=' => 'bool',
  ),
  'dom\\xpath::evaluate' => 
  array (
    0 => 'Dom\\NodeList|bool|float|null|string',
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
  'dom\\xpath::quote' => 
  array (
    0 => 'string',
    'str' => 'string',
  ),
  'dom\\xpath::registernamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'dom\\xpath::registerphpfunctionns' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string',
    'name' => 'string',
    'callable' => 'callable',
  ),
  'dom\\xpath::registerphpfunctions' => 
  array (
    0 => 'void',
    'restrict=' => 'array<array-key, mixed>|null|string',
  ),
  'dom_import_simplexml' => 
  array (
    0 => 'DOMAttr|DOMElement',
    'node' => 'object',
  ),
  'domainexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'domainexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'domainexception::__wakeup' => 
  array (
    0 => 'void',
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
  'domainexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'domainexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'domainexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domainexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'domattr::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'string',
  ),
  'domattr::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domattr::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domattr::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domattr::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domattr::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domattr::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domattr::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domattr::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domattr::getlineno' => 
  array (
    0 => 'int',
  ),
  'domattr::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domattr::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domattr::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domattr::isid' => 
  array (
    0 => 'bool',
  ),
  'domattr::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domattr::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domattr::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domattr::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domcdatasection::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'domcdatasection::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domcdatasection::__wakeup' => 
  array (
    0 => 'void',
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
  'domcdatasection::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcdatasection::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcdatasection::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcdatasection::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcdatasection::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcdatasection::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcdatasection::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domcdatasection::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcdatasection::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcdatasection::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domcdatasection::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domcdatasection::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcdatasection::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcdatasection::iselementcontentwhitespace' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcdatasection::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcdatasection::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcdatasection::iswhitespaceinelementcontent' => 
  array (
    0 => 'bool',
  ),
  'domcdatasection::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domcdatasection::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domcdatasection::normalize' => 
  array (
    0 => 'void',
  ),
  'domcdatasection::remove' => 
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
  'domcdatasection::splittext' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
  ),
  'domcdatasection::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcharacterdata::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domcharacterdata::__wakeup' => 
  array (
    0 => 'void',
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
  'domcharacterdata::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcharacterdata::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcharacterdata::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcharacterdata::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcharacterdata::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcharacterdata::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcharacterdata::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domcharacterdata::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcharacterdata::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcharacterdata::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domcharacterdata::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domcharacterdata::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcharacterdata::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcharacterdata::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcharacterdata::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcharacterdata::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcharacterdata::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domcharacterdata::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domcharacterdata::normalize' => 
  array (
    0 => 'void',
  ),
  'domcharacterdata::remove' => 
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
  'domcharacterdata::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcomment::__construct' => 
  array (
    0 => 'void',
    'data=' => 'string',
  ),
  'domcomment::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domcomment::__wakeup' => 
  array (
    0 => 'void',
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
  'domcomment::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domcomment::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domcomment::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcomment::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domcomment::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domcomment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domcomment::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domcomment::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domcomment::getlineno' => 
  array (
    0 => 'int',
  ),
  'domcomment::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domcomment::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domcomment::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domcomment::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domcomment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domcomment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domcomment::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domcomment::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domcomment::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domcomment::normalize' => 
  array (
    0 => 'void',
  ),
  'domcomment::remove' => 
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
  'domcomment::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domdocument::__construct' => 
  array (
    0 => 'void',
    'version=' => 'string',
    'encoding=' => 'string',
  ),
  'domdocument::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domdocument::__wakeup' => 
  array (
    0 => 'void',
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
  'domdocument::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domdocument::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocument::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocument::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocument::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocument::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domdocument::createattribute' => 
  array (
    0 => 'mixed',
    'localName' => 'string',
  ),
  'domdocument::createattributens' => 
  array (
    0 => 'mixed',
    'namespace' => 'null|string',
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
    'namespace' => 'null|string',
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
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'domdocument::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocument::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domdocument::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'domdocument::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domdocument::haschildnodes' => 
  array (
    0 => 'bool',
  ),
  'domdocument::importnode' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
    'deep=' => 'bool',
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
  'domdocument::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocument::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocument::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocument::load' => 
  array (
    0 => 'bool',
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
  'domdocument::loadxml' => 
  array (
    0 => 'bool',
    'source' => 'string',
    'options=' => 'int',
  ),
  'domdocument::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domdocument::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domdocument::normalize' => 
  array (
    0 => 'void',
  ),
  'domdocument::normalizedocument' => 
  array (
    0 => 'void',
  ),
  'domdocument::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocument::registernodeclass' => 
  array (
    0 => 'true',
    'baseClass' => 'string',
    'extendedClass' => 'null|string',
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
  'domdocument::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocument::save' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'options=' => 'int',
  ),
  'domdocument::savehtml' => 
  array (
    0 => 'false|string',
    'node=' => 'DOMNode|null',
  ),
  'domdocument::savehtmlfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'domdocument::savexml' => 
  array (
    0 => 'false|string',
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
  'domdocument::validate' => 
  array (
    0 => 'bool',
  ),
  'domdocument::xinclude' => 
  array (
    0 => 'false|int',
    'options=' => 'int',
  ),
  'domdocumentfragment::__construct' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domdocumentfragment::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocumentfragment::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domdocumentfragment::appendxml' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'domdocumentfragment::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocumentfragment::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocumentfragment::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocumentfragment::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocumentfragment::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domdocumentfragment::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocumentfragment::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domdocumentfragment::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domdocumentfragment::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocumentfragment::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocumentfragment::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocumentfragment::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domdocumentfragment::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domdocumentfragment::normalize' => 
  array (
    0 => 'void',
  ),
  'domdocumentfragment::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
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
  'domdocumentfragment::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domdocumenttype::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domdocumenttype::__wakeup' => 
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
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocumenttype::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domdocumenttype::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domdocumenttype::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domdocumenttype::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domdocumenttype::getlineno' => 
  array (
    0 => 'int',
  ),
  'domdocumenttype::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domdocumenttype::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domdocumenttype::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domdocumenttype::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domdocumenttype::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domdocumenttype::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domdocumenttype::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domelement::__construct' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value=' => 'null|string',
    'namespace=' => 'string',
  ),
  'domelement::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domelement::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domelement::after' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::append' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domelement::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domelement::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domelement::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domelement::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domelement::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domelement::getattribute' => 
  array (
    0 => 'string',
    'qualifiedName' => 'string',
  ),
  'domelement::getattributenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domelement::getattributenode' => 
  array (
    0 => 'mixed',
    'qualifiedName' => 'string',
  ),
  'domelement::getattributenodens' => 
  array (
    0 => 'mixed',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'domelement::getattributens' => 
  array (
    0 => 'string',
    'namespace' => 'null|string',
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
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'domelement::getlineno' => 
  array (
    0 => 'int',
  ),
  'domelement::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domelement::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'domelement::hasattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'domelement::hasattributens' => 
  array (
    0 => 'bool',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'domelement::hasattributes' => 
  array (
    0 => 'bool',
  ),
  'domelement::haschildnodes' => 
  array (
    0 => 'bool',
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
  'domelement::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domelement::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domelement::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domelement::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domelement::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domelement::normalize' => 
  array (
    0 => 'void',
  ),
  'domelement::prepend' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::remove' => 
  array (
    0 => 'void',
  ),
  'domelement::removeattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'domelement::removeattributenode' => 
  array (
    0 => 'mixed',
    'attr' => 'DOMAttr',
  ),
  'domelement::removeattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'localName' => 'string',
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
  'domelement::replacechildren' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::replacewith' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domelement::setattribute' => 
  array (
    0 => 'mixed',
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
  'domelement::setattributens' => 
  array (
    0 => 'void',
    'namespace' => 'null|string',
    'qualifiedName' => 'string',
    'value' => 'string',
  ),
  'domelement::setidattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'domelement::setidattributenode' => 
  array (
    0 => 'void',
    'attr' => 'DOMAttr',
    'isId' => 'bool',
  ),
  'domelement::setidattributens' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'qualifiedName' => 'string',
    'isId' => 'bool',
  ),
  'domelement::toggleattribute' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'force=' => 'bool|null',
  ),
  'domentity::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domentity::__wakeup' => 
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
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domentity::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domentity::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domentity::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domentity::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domentity::getlineno' => 
  array (
    0 => 'int',
  ),
  'domentity::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domentity::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domentity::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domentity::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domentity::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domentity::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domentity::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domentityreference::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'domentityreference::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domentityreference::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domentityreference::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domentityreference::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domentityreference::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domentityreference::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domentityreference::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domentityreference::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domentityreference::getlineno' => 
  array (
    0 => 'int',
  ),
  'domentityreference::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domentityreference::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domentityreference::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domentityreference::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domentityreference::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domentityreference::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domentityreference::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'domexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'domexception::__wakeup' => 
  array (
    0 => 'void',
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
  'domexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'domexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'domexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'domimplementation::createdocument' => 
  array (
    0 => 'DOMDocument',
    'namespace=' => 'null|string',
    'qualifiedName=' => 'string',
    'doctype=' => 'DOMDocumentType|null',
  ),
  'domimplementation::createdocumenttype' => 
  array (
    0 => 'mixed',
    'qualifiedName' => 'string',
    'publicId=' => 'string',
    'systemId=' => 'string',
  ),
  'domimplementation::hasfeature' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domnamednodemap::count' => 
  array (
    0 => 'int',
  ),
  'domnamednodemap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'domnamednodemap::getnameditem' => 
  array (
    0 => 'DOMNode|null',
    'qualifiedName' => 'string',
  ),
  'domnamednodemap::getnameditemns' => 
  array (
    0 => 'DOMNode|null',
    'namespace' => 'null|string',
    'localName' => 'string',
  ),
  'domnamednodemap::item' => 
  array (
    0 => 'DOMNode|null',
    'index' => 'int',
  ),
  'domnamespacenode::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domnamespacenode::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domnode::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domnode::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domnode::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domnode::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domnode::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domnode::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domnode::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domnode::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domnode::getlineno' => 
  array (
    0 => 'int',
  ),
  'domnode::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domnode::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domnode::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domnode::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domnode::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domnode::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domnode::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domnotation::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domnotation::__wakeup' => 
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
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domnotation::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domnotation::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domnotation::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domnotation::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domnotation::getlineno' => 
  array (
    0 => 'int',
  ),
  'domnotation::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domnotation::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domnotation::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domnotation::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domnotation::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domnotation::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domnotation::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domprocessinginstruction::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'string',
  ),
  'domprocessinginstruction::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domprocessinginstruction::__wakeup' => 
  array (
    0 => 'void',
  ),
  'domprocessinginstruction::appendchild' => 
  array (
    0 => 'mixed',
    'node' => 'DOMNode',
  ),
  'domprocessinginstruction::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domprocessinginstruction::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domprocessinginstruction::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domprocessinginstruction::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domprocessinginstruction::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domprocessinginstruction::getlineno' => 
  array (
    0 => 'int',
  ),
  'domprocessinginstruction::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domprocessinginstruction::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domprocessinginstruction::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domprocessinginstruction::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domprocessinginstruction::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domprocessinginstruction::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domprocessinginstruction::lookupprefix' => 
  array (
    0 => 'null|string',
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
  'domtext::__construct' => 
  array (
    0 => 'void',
    'data=' => 'string',
  ),
  'domtext::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'domtext::__wakeup' => 
  array (
    0 => 'void',
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
  'domtext::appenddata' => 
  array (
    0 => 'true',
    'data' => 'string',
  ),
  'domtext::before' => 
  array (
    0 => 'void',
    '...nodes=' => 'mixed',
  ),
  'domtext::c14n' => 
  array (
    0 => 'false|string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domtext::c14nfile' => 
  array (
    0 => 'false|int',
    'uri' => 'string',
    'exclusive=' => 'bool',
    'withComments=' => 'bool',
    'xpath=' => 'array<array-key, mixed>|null',
    'nsPrefixes=' => 'array<array-key, mixed>|null',
  ),
  'domtext::clonenode' => 
  array (
    0 => 'mixed',
    'deep=' => 'bool',
  ),
  'domtext::comparedocumentposition' => 
  array (
    0 => 'int',
    'other' => 'DOMNode',
  ),
  'domtext::contains' => 
  array (
    0 => 'bool',
    'other' => 'DOMNameSpaceNode|DOMNode|null',
  ),
  'domtext::deletedata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'count' => 'int',
  ),
  'domtext::getlineno' => 
  array (
    0 => 'int',
  ),
  'domtext::getnodepath' => 
  array (
    0 => 'null|string',
  ),
  'domtext::getrootnode' => 
  array (
    0 => 'DOMNode',
    'options=' => 'array<array-key, mixed>|null',
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
  'domtext::insertdata' => 
  array (
    0 => 'bool',
    'offset' => 'int',
    'data' => 'string',
  ),
  'domtext::isdefaultnamespace' => 
  array (
    0 => 'bool',
    'namespace' => 'string',
  ),
  'domtext::iselementcontentwhitespace' => 
  array (
    0 => 'bool',
  ),
  'domtext::isequalnode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode|null',
  ),
  'domtext::issamenode' => 
  array (
    0 => 'bool',
    'otherNode' => 'DOMNode',
  ),
  'domtext::issupported' => 
  array (
    0 => 'bool',
    'feature' => 'string',
    'version' => 'string',
  ),
  'domtext::iswhitespaceinelementcontent' => 
  array (
    0 => 'bool',
  ),
  'domtext::lookupnamespaceuri' => 
  array (
    0 => 'null|string',
    'prefix' => 'null|string',
  ),
  'domtext::lookupprefix' => 
  array (
    0 => 'null|string',
    'namespace' => 'string',
  ),
  'domtext::normalize' => 
  array (
    0 => 'void',
  ),
  'domtext::remove' => 
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
  'domtext::splittext' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
  ),
  'domtext::substringdata' => 
  array (
    0 => 'mixed',
    'offset' => 'int',
    'count' => 'int',
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
  'domxpath::quote' => 
  array (
    0 => 'string',
    'str' => 'string',
  ),
  'domxpath::registernamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'domxpath::registerphpfunctionns' => 
  array (
    0 => 'void',
    'namespaceURI' => 'string',
    'name' => 'string',
    'callable' => 'callable',
  ),
  'domxpath::registerphpfunctions' => 
  array (
    0 => 'void',
    'restrict=' => 'array<array-key, mixed>|null|string',
  ),
  'doubleval' => 
  array (
    0 => 'float',
    'value' => 'mixed',
  ),
  'ds\\deque::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
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
  'ds\\deque::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\deque::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\deque::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\deque::count' => 
  array (
    0 => 'int',
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
  'ds\\deque::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\deque::insert' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    '...values=' => 'mixed',
  ),
  'ds\\deque::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\deque::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'ds\\deque::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\deque::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ds\\deque::unshift' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
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
  'ds\\map::getiterator' => 
  array (
    0 => 'Traversable',
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
  'ds\\map::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\map::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\map::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
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
    0 => 'array<array-key, mixed>',
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
  'ds\\priorityqueue::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\priorityqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\priorityqueue::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\priorityqueue::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'ds\\queue::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\queue::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\queue::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\queue::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'ds\\set::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\set::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\set::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\set::count' => 
  array (
    0 => 'int',
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
  'ds\\set::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\set::intersect' => 
  array (
    0 => 'Ds\\Set',
    'set' => 'Ds\\Set',
  ),
  'ds\\set::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\set::join' => 
  array (
    0 => 'mixed',
    'glue=' => 'string',
  ),
  'ds\\set::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\set::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'ds\\stack::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\stack::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\stack::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\stack::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ds\\vector::__construct' => 
  array (
    0 => 'void',
    'values=' => 'mixed',
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
  'ds\\vector::clear' => 
  array (
    0 => 'mixed',
  ),
  'ds\\vector::contains' => 
  array (
    0 => 'bool',
    '...values=' => 'mixed',
  ),
  'ds\\vector::copy' => 
  array (
    0 => 'Ds\\Collection',
  ),
  'ds\\vector::count' => 
  array (
    0 => 'int',
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
  'ds\\vector::getiterator' => 
  array (
    0 => 'Traversable',
  ),
  'ds\\vector::insert' => 
  array (
    0 => 'mixed',
    'index' => 'int',
    '...values=' => 'mixed',
  ),
  'ds\\vector::isempty' => 
  array (
    0 => 'bool',
  ),
  'ds\\vector::join' => 
  array (
    0 => 'string',
    'glue=' => 'string',
  ),
  'ds\\vector::jsonserialize' => 
  array (
    0 => 'mixed',
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
  'ds\\vector::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ds\\vector::unshift' => 
  array (
    0 => 'mixed',
    '...values=' => 'mixed',
  ),
  'emptyiterator::current' => 
  array (
    0 => 'never',
  ),
  'emptyiterator::key' => 
  array (
    0 => 'never',
  ),
  'emptyiterator::next' => 
  array (
    0 => 'void',
  ),
  'emptyiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'emptyiterator::valid' => 
  array (
    0 => 'false',
  ),
  'end' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>|object',
  ),
  'enum_exists' => 
  array (
    0 => 'bool',
    'enum' => 'string',
    'autoload=' => 'bool',
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
  'error::__tostring' => 
  array (
    0 => 'string',
  ),
  'error::__wakeup' => 
  array (
    0 => 'void',
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
  'error::getmessage' => 
  array (
    0 => 'string',
  ),
  'error::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'error::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'error::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'error_clear_last' => 
  array (
    0 => 'void',
  ),
  'error_get_last' => 
  array (
    0 => 'array<array-key, mixed>|null',
  ),
  'error_log' => 
  array (
    0 => 'bool',
    'message' => 'string',
    'message_type=' => 'int',
    'destination=' => 'null|string',
    'additional_headers=' => 'null|string',
  ),
  'error_reporting' => 
  array (
    0 => 'int',
    'error_level=' => 'int|null',
  ),
  'errorexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'severity=' => 'int',
    'filename=' => 'null|string',
    'line=' => 'int|null',
    'previous=' => 'Throwable|null',
  ),
  'errorexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'errorexception::__wakeup' => 
  array (
    0 => 'void',
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
  'errorexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'errorexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'errorexception::getseverity' => 
  array (
    0 => 'int',
  ),
  'errorexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'errorexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'escapeshellarg' => 
  array (
    0 => 'string',
    'arg' => 'string',
  ),
  'escapeshellcmd' => 
  array (
    0 => 'string',
    'command' => 'string',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
  'event::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'fd' => 'mixed',
    'what' => 'int',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'event::add' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::addsignal' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::addtimer' => 
  array (
    0 => 'bool',
    'timeout=' => 'float',
  ),
  'event::del' => 
  array (
    0 => 'bool',
  ),
  'event::delsignal' => 
  array (
    0 => 'bool',
  ),
  'event::deltimer' => 
  array (
    0 => 'bool',
  ),
  'event::free' => 
  array (
    0 => 'void',
  ),
  'event::getsupportedmethods' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'event::set' => 
  array (
    0 => 'bool',
    'base' => 'EventBase',
    'fd' => 'mixed',
    'what=' => 'int',
    'cb=' => 'callable|null',
    'arg=' => 'mixed',
  ),
  'event::setpriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
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
  'event::timer' => 
  array (
    0 => 'Event',
    'base' => 'EventBase',
    'cb' => 'callable',
    'arg=' => 'mixed',
  ),
  'eventbase::__construct' => 
  array (
    0 => 'void',
    'cfg=' => 'EventConfig|null',
  ),
  'eventbase::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'eventbase::__wakeup' => 
  array (
    0 => 'void',
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
  'eventbase::free' => 
  array (
    0 => 'void',
  ),
  'eventbase::getfeatures' => 
  array (
    0 => 'int',
  ),
  'eventbase::getmethod' => 
  array (
    0 => 'string',
  ),
  'eventbase::gettimeofdaycached' => 
  array (
    0 => 'float',
  ),
  'eventbase::gotexit' => 
  array (
    0 => 'bool',
  ),
  'eventbase::gotstop' => 
  array (
    0 => 'bool',
  ),
  'eventbase::loop' => 
  array (
    0 => 'bool',
    'flags=' => 'int',
  ),
  'eventbase::priorityinit' => 
  array (
    0 => 'bool',
    'n_priorities' => 'int',
  ),
  'eventbase::reinit' => 
  array (
    0 => 'bool',
  ),
  'eventbase::resume' => 
  array (
    0 => 'bool',
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
  'eventbase::updatecachetime' => 
  array (
    0 => 'bool',
  ),
  'eventbuffer::__construct' => 
  array (
    0 => 'void',
  ),
  'eventbuffer::add' => 
  array (
    0 => 'bool',
    'data' => 'string',
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
  'eventbuffer::copyout' => 
  array (
    0 => 'int',
    '&data' => 'string',
    'max_bytes' => 'int',
  ),
  'eventbuffer::drain' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'eventbuffer::enablelocking' => 
  array (
    0 => 'void',
  ),
  'eventbuffer::expand' => 
  array (
    0 => 'bool',
    'len' => 'int',
  ),
  'eventbuffer::freeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'eventbuffer::lock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
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
  'eventbuffer::pullup' => 
  array (
    0 => 'null|string',
    'size' => 'int',
  ),
  'eventbuffer::read' => 
  array (
    0 => 'string',
    'max_bytes' => 'int',
  ),
  'eventbuffer::readfrom' => 
  array (
    0 => 'false|int',
    'fd' => 'mixed',
    'howmuch=' => 'int',
  ),
  'eventbuffer::readline' => 
  array (
    0 => 'null|string',
    'eol_style' => 'int',
  ),
  'eventbuffer::search' => 
  array (
    0 => 'false|int',
    'what' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'eventbuffer::searcheol' => 
  array (
    0 => 'false|int',
    'start=' => 'int',
    'eol_style=' => 'int',
  ),
  'eventbuffer::substr' => 
  array (
    0 => 'false|string',
    'start' => 'int',
    'length=' => 'int',
  ),
  'eventbuffer::unfreeze' => 
  array (
    0 => 'bool',
    'at_front' => 'bool',
  ),
  'eventbuffer::unlock' => 
  array (
    0 => 'void',
    'at_front' => 'bool',
  ),
  'eventbuffer::write' => 
  array (
    0 => 'false|int',
    'fd' => 'mixed',
    'howmuch=' => 'int',
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
  'eventbufferevent::createpair' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'base' => 'EventBase',
    'options=' => 'int',
  ),
  'eventbufferevent::createsslfilter' => 
  array (
    0 => 'EventBufferEvent',
    'unnderlying' => 'EventBufferEvent',
    'ctx' => 'EventSslContext',
    'state' => 'int',
    'options=' => 'int',
  ),
  'eventbufferevent::disable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'eventbufferevent::enable' => 
  array (
    0 => 'bool',
    'events' => 'int',
  ),
  'eventbufferevent::free' => 
  array (
    0 => 'void',
  ),
  'eventbufferevent::getdnserrorstring' => 
  array (
    0 => 'string',
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
  'eventbufferevent::read' => 
  array (
    0 => 'null|string',
    'size' => 'int',
  ),
  'eventbufferevent::readbuffer' => 
  array (
    0 => 'bool',
    'buf' => 'EventBuffer',
  ),
  'eventbufferevent::setcallbacks' => 
  array (
    0 => 'void',
    'readcb' => 'callable|null',
    'writecb' => 'callable|null',
    'eventcb' => 'callable|null',
    'arg=' => 'mixed',
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
  'eventbufferevent::setwatermark' => 
  array (
    0 => 'void',
    'events' => 'int',
    'lowmark' => 'int',
    'highmark' => 'int',
  ),
  'eventbufferevent::sslerror' => 
  array (
    0 => 'string',
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
  'eventbufferevent::sslrenegotiate' => 
  array (
    0 => 'void',
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
  'eventconfig::__construct' => 
  array (
    0 => 'void',
  ),
  'eventconfig::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'eventconfig::setflags' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'eventconfig::setmaxdispatchinterval' => 
  array (
    0 => 'void',
    'max_interval' => 'int',
    'max_callbacks' => 'int',
    'min_priority' => 'int',
  ),
  'eventdnsbase::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'initialize' => 'mixed',
  ),
  'eventdnsbase::addnameserverip' => 
  array (
    0 => 'bool',
    'ip' => 'string',
  ),
  'eventdnsbase::addsearch' => 
  array (
    0 => 'void',
    'domain' => 'string',
  ),
  'eventdnsbase::clearsearch' => 
  array (
    0 => 'void',
  ),
  'eventdnsbase::countnameservers' => 
  array (
    0 => 'int',
  ),
  'eventdnsbase::loadhosts' => 
  array (
    0 => 'bool',
    'hosts' => 'string',
  ),
  'eventdnsbase::parseresolvconf' => 
  array (
    0 => 'bool',
    'flags' => 'int',
    'filename' => 'string',
  ),
  'eventdnsbase::setoption' => 
  array (
    0 => 'bool',
    'option' => 'string',
    'value' => 'string',
  ),
  'eventdnsbase::setsearchndots' => 
  array (
    0 => 'void',
    'ndots' => 'int',
  ),
  'eventexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'eventexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'eventexception::__wakeup' => 
  array (
    0 => 'void',
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
  'eventexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'eventexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'eventexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'eventexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'eventhttp::__construct' => 
  array (
    0 => 'void',
    'base' => 'EventBase',
    'ctx=' => 'EventSslContext|null',
  ),
  'eventhttp::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'eventhttp::addserveralias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'eventhttp::bind' => 
  array (
    0 => 'bool',
    'address' => 'string',
    'port' => 'int',
  ),
  'eventhttp::removeserveralias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'eventhttp::setallowedmethods' => 
  array (
    0 => 'void',
    'methods' => 'int',
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
    0 => 'array<array-key, mixed>',
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
  'eventhttpconnection::setmaxbodysize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'eventhttpconnection::setmaxheaderssize' => 
  array (
    0 => 'void',
    'max_size' => 'int',
  ),
  'eventhttpconnection::setretries' => 
  array (
    0 => 'void',
    'retries' => 'int',
  ),
  'eventhttpconnection::settimeout' => 
  array (
    0 => 'void',
    'timeout' => 'int',
  ),
  'eventhttprequest::__construct' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    'data=' => 'mixed',
  ),
  'eventhttprequest::__sleep' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'eventhttprequest::__wakeup' => 
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
  'eventhttprequest::cancel' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::clearheaders' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::closeconnection' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::findheader' => 
  array (
    0 => 'null|string',
    'key' => 'string',
    'type' => 'int',
  ),
  'eventhttprequest::free' => 
  array (
    0 => 'void',
  ),
  'eventhttprequest::getbufferevent' => 
  array (
    0 => 'EventBufferEvent|null',
  ),
  'eventhttprequest::getcommand' => 
  array (
    0 => 'int',
  ),
  'eventhttprequest::getconnection' => 
  array (
    0 => 'EventHttpConnection|null',
  ),
  'eventhttprequest::gethost' => 
  array (
    0 => 'string',
  ),
  'eventhttprequest::getinputbuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventhttprequest::getinputheaders' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'eventhttprequest::getoutputbuffer' => 
  array (
    0 => 'EventBuffer',
  ),
  'eventhttprequest::getoutputheaders' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'eventhttprequest::getresponsecode' => 
  array (
    0 => 'int',
  ),
  'eventhttprequest::geturi' => 
  array (
    0 => 'string',
  ),
  'eventhttprequest::removeheader' => 
  array (
    0 => 'bool',
    'key' => 'string',
    'type' => 'int',
  ),
  'eventhttprequest::senderror' => 
  array (
    0 => 'void',
    'error' => 'int',
    'reason=' => 'null|string',
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
    0 => 'array<array-key, mixed>',
  ),
  'eventlistener::__wakeup' => 
  array (
    0 => 'void',
  ),
  'eventlistener::disable' => 
  array (
    0 => 'bool',
  ),
  'eventlistener::enable' => 
  array (
    0 => 'bool',
  ),
  'eventlistener::free' => 
  array (
    0 => 'void',
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
  'eventsslcontext::__construct' => 
  array (
    0 => 'void',
    'method' => 'int',
    'options' => 'array<array-key, mixed>',
  ),
  'eventsslcontext::setmaxprotoversion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'eventsslcontext::setminprotoversion' => 
  array (
    0 => 'bool',
    'proto' => 'int',
  ),
  'eventutil::__construct' => 
  array (
    0 => 'void',
  ),
  'eventutil::getlastsocketerrno' => 
  array (
    0 => 'false|int',
    'socket=' => 'Socket|null',
  ),
  'eventutil::getlastsocketerror' => 
  array (
    0 => 'false|string',
    'socket=' => 'mixed',
  ),
  'eventutil::getsocketfd' => 
  array (
    0 => 'int',
    'socket' => 'mixed',
  ),
  'eventutil::getsocketname' => 
  array (
    0 => 'bool',
    'socket' => 'mixed',
    '&address' => 'mixed',
    '&port=' => 'mixed',
  ),
  'eventutil::setsocketoption' => 
  array (
    0 => 'bool',
    'socket' => 'mixed',
    'level' => 'int',
    'optname' => 'int',
    'optval' => 'mixed',
  ),
  'eventutil::sslrandpoll' => 
  array (
    0 => 'bool',
  ),
  'evfork::__construct' => 
  array (
    0 => 'void',
    'loop' => 'EvLoop',
    'callback' => 'mixed',
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
    'callback' => 'mixed',
    'data=' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'fd' => 'mixed',
    'events' => 'int',
    'callback' => 'mixed',
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
    'fd' => 'mixed',
    'events' => 'int',
  ),
  'evio::setcallback' => 
  array (
    0 => 'void',
    'callback' => 'mixed',
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
  'evloop::check' => 
  array (
    0 => 'EvCheck',
    'callback' => 'callable',
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
    'callback' => 'callable',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::fork' => 
  array (
    0 => 'EvFork',
    'callback' => 'callable',
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
  'evloop::invokepending' => 
  array (
    0 => 'void',
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
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evloop::prepare' => 
  array (
    0 => 'EvPrepare',
    'callback' => 'callable',
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
    'callback' => 'mixed',
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
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
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
    'reschedule_cb' => 'mixed',
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evprepare::clear' => 
  array (
    0 => 'int',
  ),
  'evprepare::createstopped' => 
  array (
    0 => 'EvPrepare',
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
    'data=' => 'mixed',
    'priority=' => 'int',
  ),
  'evstat::attr' => 
  array (
    0 => 'mixed',
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
    'callback' => 'mixed',
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
    0 => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
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
    'callback' => 'mixed',
  ),
  'evwatcher::start' => 
  array (
    0 => 'void',
  ),
  'evwatcher::stop' => 
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
  'exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'exception::__wakeup' => 
  array (
    0 => 'void',
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
  'exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'exception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'exception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'exec' => 
  array (
    0 => 'false|string',
    'command' => 'string',
    '&output=' => 'mixed',
    '&result_code=' => 'mixed',
  ),
  'exit' => 
  array (
    0 => 'never',
    'status=' => 'int|string',
  ),
  'exp' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'explode' => 
  array (
    0 => 'array<array-key, mixed>',
    'separator' => 'string',
    'string' => 'string',
    'limit=' => 'int',
  ),
  'expm1' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'extension_loaded' => 
  array (
    0 => 'bool',
    'extension' => 'string',
  ),
  'extract' => 
  array (
    0 => 'int',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
    'prefix=' => 'string',
  ),
  'fclose' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fdatasync' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fdiv' => 
  array (
    0 => 'float',
    'num1' => 'float',
    'num2' => 'float',
  ),
  'feof' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'ffi::addr' => 
  array (
    0 => 'FFI\\CData',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::alignof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'ffi::arraytype' => 
  array (
    0 => 'FFI\\CType',
    'type' => 'FFI\\CType',
    'dimensions' => 'array<array-key, mixed>',
  ),
  'ffi::cast' => 
  array (
    0 => 'FFI\\CData',
    'type' => 'FFI\\CType|string',
    '&ptr' => 'mixed',
  ),
  'ffi::cdef' => 
  array (
    0 => 'FFI',
    'code=' => 'string',
    'lib=' => 'null|string',
  ),
  'ffi::free' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::isnull' => 
  array (
    0 => 'bool',
    '&ptr' => 'FFI\\CData',
  ),
  'ffi::load' => 
  array (
    0 => 'FFI|null',
    'filename' => 'string',
  ),
  'ffi::memcmp' => 
  array (
    0 => 'int',
    '&ptr1' => 'mixed',
    '&ptr2' => 'mixed',
    'size' => 'int',
  ),
  'ffi::memcpy' => 
  array (
    0 => 'void',
    '&to' => 'FFI\\CData',
    '&from' => 'mixed',
    'size' => 'int',
  ),
  'ffi::memset' => 
  array (
    0 => 'void',
    '&ptr' => 'FFI\\CData',
    'value' => 'int',
    'size' => 'int',
  ),
  'ffi::new' => 
  array (
    0 => 'FFI\\CData',
    'type' => 'FFI\\CType|string',
    'owned=' => 'bool',
    'persistent=' => 'bool',
  ),
  'ffi::scope' => 
  array (
    0 => 'FFI',
    'name' => 'string',
  ),
  'ffi::sizeof' => 
  array (
    0 => 'int',
    '&ptr' => 'FFI\\CData|FFI\\CType',
  ),
  'ffi::string' => 
  array (
    0 => 'string',
    '&ptr' => 'FFI\\CData',
    'size=' => 'int|null',
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
  'ffi\\ctype::getalignment' => 
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
  'ffi\\ctype::getattributes' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getenumkind' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getfuncabi' => 
  array (
    0 => 'int',
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
  'ffi\\ctype::getfuncreturntype' => 
  array (
    0 => 'FFI\\CType',
  ),
  'ffi\\ctype::getkind' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getname' => 
  array (
    0 => 'string',
  ),
  'ffi\\ctype::getpointertype' => 
  array (
    0 => 'FFI\\CType',
  ),
  'ffi\\ctype::getsize' => 
  array (
    0 => 'int',
  ),
  'ffi\\ctype::getstructfieldnames' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'ffi\\exception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'ffi\\exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'ffi\\exception::__wakeup' => 
  array (
    0 => 'void',
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
  'ffi\\exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'ffi\\exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ffi\\exception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ffi\\exception::gettraceasstring' => 
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
  'ffi\\parserexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::__wakeup' => 
  array (
    0 => 'void',
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
  'ffi\\parserexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'ffi\\parserexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'ffi\\parserexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ffi\\parserexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'fflush' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'fgetc' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
  ),
  'fgetcsv' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'stream' => 'mixed',
    'length=' => 'int|null',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'fgets' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length=' => 'int|null',
  ),
  'fiber::__construct' => 
  array (
    0 => 'void',
    'callback' => 'callable',
  ),
  'fiber::getcurrent' => 
  array (
    0 => 'Fiber|null',
  ),
  'fiber::getreturn' => 
  array (
    0 => 'mixed',
  ),
  'fiber::isrunning' => 
  array (
    0 => 'bool',
  ),
  'fiber::isstarted' => 
  array (
    0 => 'bool',
  ),
  'fiber::issuspended' => 
  array (
    0 => 'bool',
  ),
  'fiber::isterminated' => 
  array (
    0 => 'bool',
  ),
  'fiber::resume' => 
  array (
    0 => 'mixed',
    'value=' => 'mixed',
  ),
  'fiber::start' => 
  array (
    0 => 'mixed',
    '...args=' => 'mixed',
  ),
  'fiber::suspend' => 
  array (
    0 => 'mixed',
    'value=' => 'mixed',
  ),
  'fiber::throw' => 
  array (
    0 => 'mixed',
    'exception' => 'Throwable',
  ),
  'fibererror::__construct' => 
  array (
    0 => 'void',
  ),
  'fibererror::__tostring' => 
  array (
    0 => 'string',
  ),
  'fibererror::__wakeup' => 
  array (
    0 => 'void',
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
  'fibererror::getmessage' => 
  array (
    0 => 'string',
  ),
  'fibererror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'fibererror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'fibererror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'file' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'file_exists' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'file_get_contents' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'file_put_contents' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'data' => 'mixed',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'fileatime' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'filectime' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'filegroup' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'fileinode' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'filemtime' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'fileowner' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'fileperms' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'filesize' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
  ),
  'filesystemiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'filesystemiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'filesystemiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::current' => 
  array (
    0 => 'FilesystemIterator|SplFileInfo|string',
  ),
  'filesystemiterator::getatime' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'filesystemiterator::getctime' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'filesystemiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'filesystemiterator::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getinode' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'filesystemiterator::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getowner' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'filesystemiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::getperms' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'filesystemiterator::getsize' => 
  array (
    0 => 'false|int',
  ),
  'filesystemiterator::gettype' => 
  array (
    0 => 'false|string',
  ),
  'filesystemiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isdot' => 
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
  'filesystemiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'filesystemiterator::key' => 
  array (
    0 => 'string',
  ),
  'filesystemiterator::next' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'filesystemiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'filesystemiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'filesystemiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'filesystemiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'filesystemiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'filesystemiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'filetype' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
  ),
  'filter\\filterexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'filter\\filterexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'filter\\filterexception::__wakeup' => 
  array (
    0 => 'void',
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
  'filter\\filterexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'filter\\filterexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'filter\\filterexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'filter\\filterexception::gettraceasstring' => 
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
  'filter\\filterfailedexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::__wakeup' => 
  array (
    0 => 'void',
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
  'filter\\filterfailedexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'filter\\filterfailedexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'filter\\filterfailedexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'filter\\filterfailedexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'filter_has_var' => 
  array (
    0 => 'bool',
    'input_type' => 'int',
    'var_name' => 'string',
  ),
  'filter_id' => 
  array (
    0 => 'false|int',
    'name' => 'string',
  ),
  'filter_input' => 
  array (
    0 => 'mixed',
    'type' => 'int',
    'var_name' => 'string',
    'filter=' => 'int',
    'options=' => 'array<array-key, mixed>|int',
  ),
  'filter_input_array' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'type' => 'int',
    'options=' => 'array<array-key, mixed>|int',
    'add_empty=' => 'bool',
  ),
  'filter_list' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'filter_var' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    'filter=' => 'int',
    'options=' => 'array<array-key, mixed>|int',
  ),
  'filter_var_array' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'array' => 'array<array-key, mixed>',
    'options=' => 'array<array-key, mixed>|int',
    'add_empty=' => 'bool',
  ),
  'filteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'filteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'filteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'filteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'filteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'filteriterator::next' => 
  array (
    0 => 'void',
  ),
  'filteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'filteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'finfo::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
    'magic_database=' => 'null|string',
  ),
  'finfo::buffer' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo::file' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo::set_flags' => 
  array (
    0 => 'true',
    'flags' => 'int',
  ),
  'finfo_buffer' => 
  array (
    0 => 'false|string',
    'finfo' => 'finfo',
    'string' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo_close' => 
  array (
    0 => 'true',
    'finfo' => 'finfo',
  ),
  'finfo_file' => 
  array (
    0 => 'false|string',
    'finfo' => 'finfo',
    'filename' => 'string',
    'flags=' => 'int',
    'context=' => 'mixed',
  ),
  'finfo_open' => 
  array (
    0 => 'false|finfo',
    'flags=' => 'int',
    'magic_database=' => 'null|string',
  ),
  'finfo_set_flags' => 
  array (
    0 => 'true',
    'finfo' => 'finfo',
    'flags' => 'int',
  ),
  'floatval' => 
  array (
    0 => 'float',
    'value' => 'mixed',
  ),
  'flock' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'operation' => 'int',
    '&would_block=' => 'mixed',
  ),
  'floor' => 
  array (
    0 => 'float',
    'num' => 'float|int',
  ),
  'flush' => 
  array (
    0 => 'void',
  ),
  'fmod' => 
  array (
    0 => 'float',
    'num1' => 'float',
    'num2' => 'float',
  ),
  'fnmatch' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'fopen' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
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
    'args' => 'array<array-key, mixed>',
  ),
  'fpassthru' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
  ),
  'fpow' => 
  array (
    0 => 'float',
    'num' => 'float',
    'exponent' => 'float',
  ),
  'fprintf' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'fputcsv' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
    'fields' => 'array<array-key, mixed>',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'fputs' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'fread' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length' => 'int',
  ),
  'fscanf' => 
  array (
    0 => 'array<array-key, mixed>|false|int|null',
    'stream' => 'mixed',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'fseek' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'offset' => 'int',
    'whence=' => 'int',
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
  'fstat' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'stream' => 'mixed',
  ),
  'fsync' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'ftell' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
  ),
  'ftok' => 
  array (
    0 => 'int',
    'filename' => 'string',
    'project_id' => 'string',
  ),
  'ftruncate' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'func_get_arg' => 
  array (
    0 => 'mixed',
    'position' => 'int',
  ),
  'func_get_args' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'func_num_args' => 
  array (
    0 => 'int',
  ),
  'function_exists' => 
  array (
    0 => 'bool',
    'function' => 'string',
  ),
  'fwrite' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gc_collect_cycles' => 
  array (
    0 => 'int',
  ),
  'gc_disable' => 
  array (
    0 => 'void',
  ),
  'gc_enable' => 
  array (
    0 => 'void',
  ),
  'gc_enabled' => 
  array (
    0 => 'bool',
  ),
  'gc_mem_caches' => 
  array (
    0 => 'int',
  ),
  'gc_status' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'gd_info' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'generator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'generator::current' => 
  array (
    0 => 'mixed',
  ),
  'generator::getreturn' => 
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
  'generator::rewind' => 
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
  'generator::valid' => 
  array (
    0 => 'bool',
  ),
  'get_browser' => 
  array (
    0 => 'array<array-key, mixed>|false|object',
    'user_agent=' => 'null|string',
    'return_array=' => 'bool',
  ),
  'get_called_class' => 
  array (
    0 => 'string',
  ),
  'get_cfg_var' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'option' => 'string',
  ),
  'get_class' => 
  array (
    0 => 'string',
    'object=' => 'object',
  ),
  'get_class_methods' => 
  array (
    0 => 'array<array-key, mixed>',
    'object_or_class' => 'object|string',
  ),
  'get_class_vars' => 
  array (
    0 => 'array<array-key, mixed>',
    'class' => 'string',
  ),
  'get_current_user' => 
  array (
    0 => 'string',
  ),
  'get_debug_type' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'get_declared_classes' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_declared_interfaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_declared_traits' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_defined_constants' => 
  array (
    0 => 'array<array-key, mixed>',
    'categorize=' => 'bool',
  ),
  'get_defined_functions' => 
  array (
    0 => 'array<array-key, mixed>',
    'exclude_disabled=' => 'bool',
  ),
  'get_defined_vars' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_error_handler' => 
  array (
    0 => 'callable|null',
  ),
  'get_exception_handler' => 
  array (
    0 => 'callable|null',
  ),
  'get_extension_funcs' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'extension' => 'string',
  ),
  'get_headers' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'url' => 'string',
    'associative=' => 'bool',
    'context=' => 'mixed',
  ),
  'get_html_translation_table' => 
  array (
    0 => 'array<array-key, mixed>',
    'table=' => 'int',
    'flags=' => 'int',
    'encoding=' => 'string',
  ),
  'get_include_path' => 
  array (
    0 => 'false|string',
  ),
  'get_included_files' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_loaded_extensions' => 
  array (
    0 => 'array<array-key, mixed>',
    'zend_extensions=' => 'bool',
  ),
  'get_mangled_object_vars' => 
  array (
    0 => 'array<array-key, mixed>',
    'object' => 'object',
  ),
  'get_meta_tags' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'get_object_vars' => 
  array (
    0 => 'array<array-key, mixed>',
    'object' => 'object',
  ),
  'get_parent_class' => 
  array (
    0 => 'false|string',
    'object_or_class=' => 'object|string',
  ),
  'get_required_files' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'get_resource_id' => 
  array (
    0 => 'int',
    'resource' => 'mixed',
  ),
  'get_resource_type' => 
  array (
    0 => 'string',
    'resource' => 'mixed',
  ),
  'get_resources' => 
  array (
    0 => 'array<array-key, mixed>',
    'type=' => 'null|string',
  ),
  'getcwd' => 
  array (
    0 => 'false|string',
  ),
  'getdate' => 
  array (
    0 => 'array<array-key, mixed>',
    'timestamp=' => 'int|null',
  ),
  'getenv' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'name=' => 'null|string',
    'local_only=' => 'bool',
  ),
  'gethostbyaddr' => 
  array (
    0 => 'false|string',
    'ip' => 'string',
  ),
  'gethostbyname' => 
  array (
    0 => 'string',
    'hostname' => 'string',
  ),
  'gethostbynamel' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'hostname' => 'string',
  ),
  'gethostname' => 
  array (
    0 => 'false|string',
  ),
  'getimagesize' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
    '&image_info=' => 'mixed',
  ),
  'getimagesizefromstring' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'string' => 'string',
    '&image_info=' => 'mixed',
  ),
  'getlastmod' => 
  array (
    0 => 'false|int',
  ),
  'getmxrr' => 
  array (
    0 => 'bool',
    'hostname' => 'string',
    '&hosts' => 'mixed',
    '&weights=' => 'mixed',
  ),
  'getmygid' => 
  array (
    0 => 'false|int',
  ),
  'getmyinode' => 
  array (
    0 => 'false|int',
  ),
  'getmypid' => 
  array (
    0 => 'false|int',
  ),
  'getmyuid' => 
  array (
    0 => 'false|int',
  ),
  'getopt' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'short_options' => 'string',
    'long_options=' => 'array<array-key, mixed>',
    '&rest_index=' => 'mixed',
  ),
  'getprotobyname' => 
  array (
    0 => 'false|int',
    'protocol' => 'string',
  ),
  'getprotobynumber' => 
  array (
    0 => 'false|string',
    'protocol' => 'int',
  ),
  'getrandmax' => 
  array (
    0 => 'int',
  ),
  'getrusage' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'mode=' => 'int',
  ),
  'getservbyname' => 
  array (
    0 => 'false|int',
    'service' => 'string',
    'protocol' => 'string',
  ),
  'getservbyport' => 
  array (
    0 => 'false|string',
    'port' => 'int',
    'protocol' => 'string',
  ),
  'gettimeofday' => 
  array (
    0 => 'array<array-key, mixed>|float',
    'as_float=' => 'bool',
  ),
  'gettype' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'glob' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'flags=' => 'int',
  ),
  'globiterator::__construct' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'flags=' => 'int',
  ),
  'globiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'globiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'globiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'globiterator::count' => 
  array (
    0 => 'int',
  ),
  'globiterator::current' => 
  array (
    0 => 'FilesystemIterator|SplFileInfo|string',
  ),
  'globiterator::getatime' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'globiterator::getctime' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'globiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'globiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'globiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'globiterator::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getinode' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'globiterator::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getowner' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'globiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'globiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'globiterator::getperms' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'globiterator::getsize' => 
  array (
    0 => 'false|int',
  ),
  'globiterator::gettype' => 
  array (
    0 => 'false|string',
  ),
  'globiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isdot' => 
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
  'globiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'globiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'globiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'globiterator::key' => 
  array (
    0 => 'string',
  ),
  'globiterator::next' => 
  array (
    0 => 'void',
  ),
  'globiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'globiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'globiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'globiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'globiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'globiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'globiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'gmdate' => 
  array (
    0 => 'string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'gmmktime' => 
  array (
    0 => 'false|int',
    'hour' => 'int',
    'minute=' => 'int|null',
    'second=' => 'int|null',
    'month=' => 'int|null',
    'day=' => 'int|null',
    'year=' => 'int|null',
  ),
  'gmp::__construct' => 
  array (
    0 => 'void',
    'num=' => 'int|string',
    'base=' => 'int',
  ),
  'gmp::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'gmp::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'gmp_abs' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_add' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_and' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_binomial' => 
  array (
    0 => 'GMP',
    'n' => 'GMP|int|string',
    'k' => 'int',
  ),
  'gmp_clrbit' => 
  array (
    0 => 'void',
    'num' => 'GMP',
    'index' => 'int',
  ),
  'gmp_cmp' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_com' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_div' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
    'rounding_mode=' => 'int',
  ),
  'gmp_div_q' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
    'rounding_mode=' => 'int',
  ),
  'gmp_div_qr' => 
  array (
    0 => 'array<array-key, mixed>',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
    'rounding_mode=' => 'int',
  ),
  'gmp_div_r' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
    'rounding_mode=' => 'int',
  ),
  'gmp_divexact' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_export' => 
  array (
    0 => 'string',
    'num' => 'GMP|int|string',
    'word_size=' => 'int',
    'flags=' => 'int',
  ),
  'gmp_fact' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_gcd' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_gcdext' => 
  array (
    0 => 'array<array-key, mixed>',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_hamdist' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_import' => 
  array (
    0 => 'GMP',
    'data' => 'string',
    'word_size=' => 'int',
    'flags=' => 'int',
  ),
  'gmp_init' => 
  array (
    0 => 'GMP',
    'num' => 'int|string',
    'base=' => 'int',
  ),
  'gmp_intval' => 
  array (
    0 => 'int',
    'num' => 'GMP|int|string',
  ),
  'gmp_invert' => 
  array (
    0 => 'GMP|false',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_jacobi' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_kronecker' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_lcm' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_legendre' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_mod' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_mul' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_neg' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_nextprime' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_or' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_perfect_power' => 
  array (
    0 => 'bool',
    'num' => 'GMP|int|string',
  ),
  'gmp_perfect_square' => 
  array (
    0 => 'bool',
    'num' => 'GMP|int|string',
  ),
  'gmp_popcount' => 
  array (
    0 => 'int',
    'num' => 'GMP|int|string',
  ),
  'gmp_pow' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
    'exponent' => 'int',
  ),
  'gmp_powm' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
    'exponent' => 'GMP|int|string',
    'modulus' => 'GMP|int|string',
  ),
  'gmp_prob_prime' => 
  array (
    0 => 'int',
    'num' => 'GMP|int|string',
    'repetitions=' => 'int',
  ),
  'gmp_random_bits' => 
  array (
    0 => 'GMP',
    'bits' => 'int',
  ),
  'gmp_random_range' => 
  array (
    0 => 'GMP',
    'min' => 'GMP|int|string',
    'max' => 'GMP|int|string',
  ),
  'gmp_random_seed' => 
  array (
    0 => 'void',
    'seed' => 'GMP|int|string',
  ),
  'gmp_root' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
    'nth' => 'int',
  ),
  'gmp_rootrem' => 
  array (
    0 => 'array<array-key, mixed>',
    'num' => 'GMP|int|string',
    'nth' => 'int',
  ),
  'gmp_scan0' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'start' => 'int',
  ),
  'gmp_scan1' => 
  array (
    0 => 'int',
    'num1' => 'GMP|int|string',
    'start' => 'int',
  ),
  'gmp_setbit' => 
  array (
    0 => 'void',
    'num' => 'GMP',
    'index' => 'int',
    'value=' => 'bool',
  ),
  'gmp_sign' => 
  array (
    0 => 'int',
    'num' => 'GMP|int|string',
  ),
  'gmp_sqrt' => 
  array (
    0 => 'GMP',
    'num' => 'GMP|int|string',
  ),
  'gmp_sqrtrem' => 
  array (
    0 => 'array<array-key, mixed>',
    'num' => 'GMP|int|string',
  ),
  'gmp_strval' => 
  array (
    0 => 'string',
    'num' => 'GMP|int|string',
    'base=' => 'int',
  ),
  'gmp_sub' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmp_testbit' => 
  array (
    0 => 'bool',
    'num' => 'GMP|int|string',
    'index' => 'int',
  ),
  'gmp_xor' => 
  array (
    0 => 'GMP',
    'num1' => 'GMP|int|string',
    'num2' => 'GMP|int|string',
  ),
  'gmstrftime' => 
  array (
    0 => 'false|string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'grapheme_extract' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'size' => 'int',
    'type=' => 'int',
    'offset=' => 'int',
    '&next=' => 'mixed',
  ),
  'grapheme_levenshtein' => 
  array (
    0 => 'false|int',
    'string1' => 'string',
    'string2' => 'string',
    'insertion_cost=' => 'int',
    'replacement_cost=' => 'int',
    'deletion_cost=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_str_split' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'string' => 'string',
    'length=' => 'int',
  ),
  'grapheme_stripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_stristr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
    'locale=' => 'string',
  ),
  'grapheme_strlen' => 
  array (
    0 => 'false|int|null',
    'string' => 'string',
  ),
  'grapheme_strpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_strripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_strrpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'locale=' => 'string',
  ),
  'grapheme_strstr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'beforeNeedle=' => 'bool',
    'locale=' => 'string',
  ),
  'grapheme_substr' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
    'locale=' => 'string',
  ),
  'gzclose' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'gzcompress' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzdecode' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzdeflate' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzencode' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'level=' => 'int',
    'encoding=' => 'int',
  ),
  'gzeof' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'gzfile' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'gzgetc' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
  ),
  'gzgets' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length=' => 'int|null',
  ),
  'gzinflate' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzopen' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
    'mode' => 'string',
    'use_include_path=' => 'bool',
  ),
  'gzpassthru' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
  ),
  'gzputs' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'gzread' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length' => 'int',
  ),
  'gzrewind' => 
  array (
    0 => 'bool',
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
    0 => 'false|int',
    'stream' => 'mixed',
  ),
  'gzuncompress' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'gzwrite' => 
  array (
    0 => 'false|int',
    'stream' => 'mixed',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'hash' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'data' => 'string',
    'binary=' => 'bool',
    'options=' => 'array<array-key, mixed>',
  ),
  'hash_algos' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'hash_copy' => 
  array (
    0 => 'HashContext',
    'context' => 'HashContext',
  ),
  'hash_equals' => 
  array (
    0 => 'bool',
    'known_string' => 'string',
    'user_string' => 'string',
  ),
  'hash_file' => 
  array (
    0 => 'false|string',
    'algo' => 'string',
    'filename' => 'string',
    'binary=' => 'bool',
    'options=' => 'array<array-key, mixed>',
  ),
  'hash_final' => 
  array (
    0 => 'string',
    'context' => 'HashContext',
    'binary=' => 'bool',
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
  'hash_hmac' => 
  array (
    0 => 'string',
    'algo' => 'string',
    'data' => 'string',
    'key' => 'string',
    'binary=' => 'bool',
  ),
  'hash_hmac_algos' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'hash_hmac_file' => 
  array (
    0 => 'false|string',
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
    'options=' => 'array<array-key, mixed>',
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
    'options=' => 'array<array-key, mixed>',
  ),
  'hash_update' => 
  array (
    0 => 'true',
    'context' => 'HashContext',
    'data' => 'string',
  ),
  'hash_update_file' => 
  array (
    0 => 'bool',
    'context' => 'HashContext',
    'filename' => 'string',
    'stream_context=' => 'mixed',
  ),
  'hash_update_stream' => 
  array (
    0 => 'int',
    'context' => 'HashContext',
    'stream' => 'mixed',
    'length=' => 'int',
  ),
  'hashcontext::__construct' => 
  array (
    0 => 'void',
  ),
  'hashcontext::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'hashcontext::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'hashcontext::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'header' => 
  array (
    0 => 'void',
    'header' => 'string',
    'replace=' => 'bool',
    'response_code=' => 'int',
  ),
  'header_register_callback' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'header_remove' => 
  array (
    0 => 'void',
    'name=' => 'null|string',
  ),
  'headers_list' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'headers_sent' => 
  array (
    0 => 'bool',
    '&filename=' => 'mixed',
    '&line=' => 'mixed',
  ),
  'hebrev' => 
  array (
    0 => 'string',
    'string' => 'string',
    'max_chars_per_line=' => 'int',
  ),
  'hex2bin' => 
  array (
    0 => 'false|string',
    'string' => 'string',
  ),
  'hexdec' => 
  array (
    0 => 'float|int',
    'hex_string' => 'string',
  ),
  'highlight_file' => 
  array (
    0 => 'bool|string',
    'filename' => 'string',
    'return=' => 'bool',
  ),
  'highlight_string' => 
  array (
    0 => 'string|true',
    'string' => 'string',
    'return=' => 'bool',
  ),
  'hrtime' => 
  array (
    0 => 'array<array-key, mixed>|false|float|int',
    'as_number=' => 'bool',
  ),
  'html_entity_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'null|string',
  ),
  'htmlentities' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'null|string',
    'double_encode=' => 'bool',
  ),
  'htmlspecialchars' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
    'encoding=' => 'null|string',
    'double_encode=' => 'bool',
  ),
  'htmlspecialchars_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
    'flags=' => 'int',
  ),
  'http_build_query' => 
  array (
    0 => 'string',
    'data' => 'array<array-key, mixed>|object',
    'numeric_prefix=' => 'string',
    'arg_separator=' => 'null|string',
    'encoding_type=' => 'int',
  ),
  'http_clear_last_response_headers' => 
  array (
    0 => 'void',
  ),
  'http_get_last_response_headers' => 
  array (
    0 => 'array<array-key, mixed>|null',
  ),
  'http_response_code' => 
  array (
    0 => 'bool|int',
    'response_code=' => 'int',
  ),
  'hypot' => 
  array (
    0 => 'float',
    'x' => 'float',
    'y' => 'float',
  ),
  'iconv' => 
  array (
    0 => 'false|string',
    'from_encoding' => 'string',
    'to_encoding' => 'string',
    'string' => 'string',
  ),
  'iconv_get_encoding' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'type=' => 'string',
  ),
  'iconv_mime_decode' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'mode=' => 'int',
    'encoding=' => 'null|string',
  ),
  'iconv_mime_decode_headers' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'headers' => 'string',
    'mode=' => 'int',
    'encoding=' => 'null|string',
  ),
  'iconv_mime_encode' => 
  array (
    0 => 'false|string',
    'field_name' => 'string',
    'field_value' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'iconv_set_encoding' => 
  array (
    0 => 'bool',
    'type' => 'string',
    'encoding' => 'string',
  ),
  'iconv_strlen' => 
  array (
    0 => 'false|int',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'iconv_strpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'null|string',
  ),
  'iconv_strrpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'encoding=' => 'null|string',
  ),
  'iconv_substr' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'null|string',
  ),
  'idate' => 
  array (
    0 => 'false|int',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'idn_to_ascii' => 
  array (
    0 => 'false|string',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'mixed',
  ),
  'idn_to_utf8' => 
  array (
    0 => 'false|string',
    'domain' => 'string',
    'flags=' => 'int',
    'variant=' => 'int',
    '&idna_info=' => 'mixed',
  ),
  'ignore_user_abort' => 
  array (
    0 => 'int',
    'enable=' => 'bool|null',
  ),
  'image_type_to_extension' => 
  array (
    0 => 'false|string',
    'image_type' => 'int',
    'include_dot=' => 'bool',
  ),
  'image_type_to_mime_type' => 
  array (
    0 => 'string',
    'image_type' => 'int',
  ),
  'imageaffine' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'affine' => 'array<array-key, mixed>',
    'clip=' => 'array<array-key, mixed>|null',
  ),
  'imageaffinematrixconcat' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'matrix1' => 'array<array-key, mixed>',
    'matrix2' => 'array<array-key, mixed>',
  ),
  'imageaffinematrixget' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'type' => 'int',
    'options' => 'mixed',
  ),
  'imagealphablending' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imageantialias' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
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
  'imageavif' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
    'speed=' => 'int',
  ),
  'imagebmp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'compressed=' => 'bool',
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
  'imagecolorallocate' => 
  array (
    0 => 'false|int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
  ),
  'imagecolorallocatealpha' => 
  array (
    0 => 'false|int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
  ),
  'imagecolorat' => 
  array (
    0 => 'false|int',
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
  'imagecolorclosestalpha' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
    'alpha' => 'int',
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
  'imagecolorexact' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
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
  'imagecolormatch' => 
  array (
    0 => 'true',
    'image1' => 'GdImage',
    'image2' => 'GdImage',
  ),
  'imagecolorresolve' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
    'red' => 'int',
    'green' => 'int',
    'blue' => 'int',
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
    0 => 'array<array-key, mixed>',
    'image' => 'GdImage',
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
  'imageconvolution' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'matrix' => 'array<array-key, mixed>',
    'divisor' => 'float',
    'offset' => 'float',
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
  'imagecreate' => 
  array (
    0 => 'GdImage|false',
    'width' => 'int',
    'height' => 'int',
  ),
  'imagecreatefromavif' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefrombmp' => 
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
  'imagecreatefromstring' => 
  array (
    0 => 'GdImage|false',
    'data' => 'string',
  ),
  'imagecreatefromtga' => 
  array (
    0 => 'GdImage|false',
    'filename' => 'string',
  ),
  'imagecreatefromwbmp' => 
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
  'imagecreatetruecolor' => 
  array (
    0 => 'GdImage|false',
    'width' => 'int',
    'height' => 'int',
  ),
  'imagecrop' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'rectangle' => 'array<array-key, mixed>',
  ),
  'imagecropauto' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'mode=' => 'int',
    'threshold=' => 'float',
    'color=' => 'int',
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
  'imagedestroy' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
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
  'imagefill' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
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
  'imagefilledpolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array<array-key, mixed>',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
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
  'imagefilltoborder' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'border_color' => 'int',
    'color' => 'int',
  ),
  'imagefilter' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'filter' => 'int',
    '...args=' => 'mixed',
  ),
  'imageflip' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'mode' => 'int',
  ),
  'imagefontheight' => 
  array (
    0 => 'int',
    'font' => 'GdFont|int',
  ),
  'imagefontwidth' => 
  array (
    0 => 'int',
    'font' => 'GdFont|int',
  ),
  'imageftbbox' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'size' => 'float',
    'angle' => 'float',
    'font_filename' => 'string',
    'string' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'imagefttext' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'image' => 'GdImage',
    'size' => 'float',
    'angle' => 'float',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
    'font_filename' => 'string',
    'text' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'imagegammacorrect' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'input_gamma' => 'float',
    'output_gamma' => 'float',
  ),
  'imagegd' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'null|string',
  ),
  'imagegd2' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'null|string',
    'chunk_size=' => 'int',
    'mode=' => 'int',
  ),
  'imagegetclip' => 
  array (
    0 => 'array<array-key, mixed>',
    'image' => 'GdImage',
  ),
  'imagegetinterpolation' => 
  array (
    0 => 'int',
    'image' => 'GdImage',
  ),
  'imagegif' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
  ),
  'imageinterlace' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'enable=' => 'bool|null',
  ),
  'imageistruecolor' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
  ),
  'imagejpeg' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
  ),
  'imagelayereffect' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'effect' => 'int',
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
  'imageloadfont' => 
  array (
    0 => 'GdFont|false',
    'filename' => 'string',
  ),
  'imageopenpolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array<array-key, mixed>',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
  ),
  'imagepalettecopy' => 
  array (
    0 => 'void',
    'dst' => 'GdImage',
    'src' => 'GdImage',
  ),
  'imagepalettetotruecolor' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
  ),
  'imagepng' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
    'filters=' => 'int',
  ),
  'imagepolygon' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'points' => 'array<array-key, mixed>',
    'num_points_or_color' => 'int',
    'color=' => 'int|null',
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
  'imageresolution' => 
  array (
    0 => 'array<array-key, mixed>|true',
    'image' => 'GdImage',
    'resolution_x=' => 'int|null',
    'resolution_y=' => 'int|null',
  ),
  'imagerotate' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'angle' => 'float',
    'background_color' => 'int',
  ),
  'imagesavealpha' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'enable' => 'bool',
  ),
  'imagescale' => 
  array (
    0 => 'GdImage|false',
    'image' => 'GdImage',
    'width' => 'int',
    'height=' => 'int',
    'mode=' => 'int',
  ),
  'imagesetbrush' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'brush' => 'GdImage',
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
  'imagesetinterpolation' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'method=' => 'int',
  ),
  'imagesetpixel' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
  ),
  'imagesetstyle' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'style' => 'array<array-key, mixed>',
  ),
  'imagesetthickness' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'thickness' => 'int',
  ),
  'imagesettile' => 
  array (
    0 => 'true',
    'image' => 'GdImage',
    'tile' => 'GdImage',
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
  'imagetruecolortopalette' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'dither' => 'bool',
    'num_colors' => 'int',
  ),
  'imagettfbbox' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'size' => 'float',
    'angle' => 'float',
    'font_filename' => 'string',
    'string' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'imagettftext' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'image' => 'GdImage',
    'size' => 'float',
    'angle' => 'float',
    'x' => 'int',
    'y' => 'int',
    'color' => 'int',
    'font_filename' => 'string',
    'text' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'imagetypes' => 
  array (
    0 => 'int',
  ),
  'imagewbmp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'foreground_color=' => 'int|null',
  ),
  'imagewebp' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'file=' => 'mixed',
    'quality=' => 'int',
  ),
  'imagexbm' => 
  array (
    0 => 'bool',
    'image' => 'GdImage',
    'filename' => 'null|string',
    'foreground_color=' => 'int|null',
  ),
  'implode' => 
  array (
    0 => 'string',
    'separator' => 'array<array-key, mixed>|string',
    'array=' => 'array<array-key, mixed>|null',
  ),
  'in_array' => 
  array (
    0 => 'bool',
    'needle' => 'mixed',
    'haystack' => 'array<array-key, mixed>',
    'strict=' => 'bool',
  ),
  'inet_ntop' => 
  array (
    0 => 'false|string',
    'ip' => 'string',
  ),
  'inet_pton' => 
  array (
    0 => 'false|string',
    'ip' => 'string',
  ),
  'infiniteiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'infiniteiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'infiniteiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'infiniteiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'infiniteiterator::next' => 
  array (
    0 => 'void',
  ),
  'infiniteiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'infiniteiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'inflate_add' => 
  array (
    0 => 'false|string',
    'context' => 'InflateContext',
    'data' => 'string',
    'flush_mode=' => 'int',
  ),
  'inflate_get_read_len' => 
  array (
    0 => 'int',
    'context' => 'InflateContext',
  ),
  'inflate_get_status' => 
  array (
    0 => 'int',
    'context' => 'InflateContext',
  ),
  'inflate_init' => 
  array (
    0 => 'InflateContext|false',
    'encoding' => 'int',
    'options=' => 'array<array-key, mixed>|object',
  ),
  'ini_alter' => 
  array (
    0 => 'false|string',
    'option' => 'string',
    'value' => 'null|scalar',
  ),
  'ini_get' => 
  array (
    0 => 'false|string',
    'option' => 'string',
  ),
  'ini_get_all' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'extension=' => 'null|string',
    'details=' => 'bool',
  ),
  'ini_parse_quantity' => 
  array (
    0 => 'int',
    'shorthand' => 'string',
  ),
  'ini_restore' => 
  array (
    0 => 'void',
    'option' => 'string',
  ),
  'ini_set' => 
  array (
    0 => 'false|string',
    'option' => 'string',
    'value' => 'null|scalar',
  ),
  'intdiv' => 
  array (
    0 => 'int',
    'num1' => 'int',
    'num2' => 'int',
  ),
  'interface_exists' => 
  array (
    0 => 'bool',
    'interface' => 'string',
    'autoload=' => 'bool',
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
  'internaliterator::rewind' => 
  array (
    0 => 'void',
  ),
  'internaliterator::valid' => 
  array (
    0 => 'bool',
  ),
  'intl_error_name' => 
  array (
    0 => 'string',
    'errorCode' => 'int',
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
  'intlbreakiterator::__construct' => 
  array (
    0 => 'void',
  ),
  'intlbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
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
  'intlbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlbreakiterator::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'intlbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlbreakiterator::gettext' => 
  array (
    0 => 'null|string',
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
  'intlcal_add' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
    'value' => 'int',
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
  'intlcal_clear' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'field=' => 'int|null',
  ),
  'intlcal_create_instance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'locale=' => 'null|string',
  ),
  'intlcal_equals' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
  ),
  'intlcal_field_difference' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlcal_from_date_time' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'null|string',
  ),
  'intlcal_get' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_actual_maximum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_actual_minimum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_available_locales' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'intlcal_get_day_of_week_type' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_get_error_code' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_error_message' => 
  array (
    0 => 'false|string',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_first_day_of_week' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_greatest_minimum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_keyword_values_for_locale' => 
  array (
    0 => 'IntlIterator|false',
    'keyword' => 'string',
    'locale' => 'string',
    'onlyCommon' => 'bool',
  ),
  'intlcal_get_least_maximum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_locale' => 
  array (
    0 => 'false|string',
    'calendar' => 'IntlCalendar',
    'type' => 'int',
  ),
  'intlcal_get_maximum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_minimal_days_in_first_week' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_minimum' => 
  array (
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
  ),
  'intlcal_get_now' => 
  array (
    0 => 'float',
  ),
  'intlcal_get_repeated_wall_time_option' => 
  array (
    0 => 'int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_skipped_wall_time_option' => 
  array (
    0 => 'int',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_get_time' => 
  array (
    0 => 'false|float',
    'calendar' => 'IntlCalendar',
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
    0 => 'false|int',
    'calendar' => 'IntlCalendar',
    'dayOfWeek' => 'int',
  ),
  'intlcal_in_daylight_time' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
  ),
  'intlcal_is_equivalent_to' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'other' => 'IntlCalendar',
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
  'intlcal_is_weekend' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timestamp=' => 'float|null',
  ),
  'intlcal_roll' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'field' => 'int',
    'value' => 'mixed',
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
  'intlcal_set_minimal_days_in_first_week' => 
  array (
    0 => 'true',
    'calendar' => 'IntlCalendar',
    'days' => 'int',
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
  'intlcal_set_time' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timestamp' => 'float',
  ),
  'intlcal_set_time_zone' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar',
    'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
  ),
  'intlcal_to_date_time' => 
  array (
    0 => 'DateTime|false',
    'calendar' => 'IntlCalendar',
  ),
  'intlcalendar::__construct' => 
  array (
    0 => 'void',
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
  'intlcalendar::createinstance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'locale=' => 'null|string',
  ),
  'intlcalendar::equals' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlcalendar::fielddifference' => 
  array (
    0 => 'false|int',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlcalendar::fromdatetime' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'null|string',
  ),
  'intlcalendar::get' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlcalendar::getactualmaximum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlcalendar::getactualminimum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlcalendar::getavailablelocales' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'intlcalendar::getdayofweektype' => 
  array (
    0 => 'false|int',
    'dayOfWeek' => 'int',
  ),
  'intlcalendar::geterrorcode' => 
  array (
    0 => 'false|int',
  ),
  'intlcalendar::geterrormessage' => 
  array (
    0 => 'false|string',
  ),
  'intlcalendar::getfirstdayofweek' => 
  array (
    0 => 'false|int',
  ),
  'intlcalendar::getgreatestminimum' => 
  array (
    0 => 'false|int',
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
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlcalendar::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'intlcalendar::getmaximum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlcalendar::getminimaldaysinfirstweek' => 
  array (
    0 => 'false|int',
  ),
  'intlcalendar::getminimum' => 
  array (
    0 => 'false|int',
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
    0 => 'false|float',
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
    0 => 'false|int',
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
  'intlcalendar::isset' => 
  array (
    0 => 'bool',
    'field' => 'int',
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
  'intlcalendar::setminimaldaysinfirstweek' => 
  array (
    0 => 'true',
    'days' => 'int',
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
    'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
  ),
  'intlcalendar::todatetime' => 
  array (
    0 => 'DateTime|false',
  ),
  'intlchar::charage' => 
  array (
    0 => 'array<array-key, mixed>|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::chardigitvalue' => 
  array (
    0 => 'int|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::chardirection' => 
  array (
    0 => 'int|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::charfromname' => 
  array (
    0 => 'int|null',
    'name' => 'string',
    'type=' => 'int',
  ),
  'intlchar::charmirror' => 
  array (
    0 => 'int|null|string',
    'codepoint' => 'int|string',
  ),
  'intlchar::charname' => 
  array (
    0 => 'null|string',
    'codepoint' => 'int|string',
    'type=' => 'int',
  ),
  'intlchar::chartype' => 
  array (
    0 => 'int|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::chr' => 
  array (
    0 => 'null|string',
    'codepoint' => 'int|string',
  ),
  'intlchar::digit' => 
  array (
    0 => 'false|int|null',
    'codepoint' => 'int|string',
    'base=' => 'int',
  ),
  'intlchar::enumcharnames' => 
  array (
    0 => 'bool',
    'start' => 'int|string',
    'end' => 'int|string',
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
    0 => 'int|null|string',
    'codepoint' => 'int|string',
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
    0 => 'int|null|string',
    'codepoint' => 'int|string',
  ),
  'intlchar::getblockcode' => 
  array (
    0 => 'int|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::getcombiningclass' => 
  array (
    0 => 'int|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::getfc_nfkc_closure' => 
  array (
    0 => 'false|null|string',
    'codepoint' => 'int|string',
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
    'codepoint' => 'int|string',
    'property' => 'int',
  ),
  'intlchar::getnumericvalue' => 
  array (
    0 => 'float|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::getpropertyenum' => 
  array (
    0 => 'int',
    'alias' => 'string',
  ),
  'intlchar::getpropertyname' => 
  array (
    0 => 'false|string',
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
    0 => 'false|string',
    'property' => 'int',
    'value' => 'int',
    'type=' => 'int',
  ),
  'intlchar::getunicodeversion' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'intlchar::hasbinaryproperty' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
    'property' => 'int',
  ),
  'intlchar::isalnum' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isalpha' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isbase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isblank' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::iscntrl' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isdefined' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isdigit' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isgraph' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isidignorable' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isidpart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isidstart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isisocontrol' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isjavaidpart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isjavaidstart' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isjavaspacechar' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::islower' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::ismirrored' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isprint' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::ispunct' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isspace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::istitle' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isualphabetic' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isulowercase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isupper' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isuuppercase' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isuwhitespace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::iswhitespace' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::isxdigit' => 
  array (
    0 => 'bool|null',
    'codepoint' => 'int|string',
  ),
  'intlchar::ord' => 
  array (
    0 => 'int|null',
    'character' => 'int|string',
  ),
  'intlchar::tolower' => 
  array (
    0 => 'int|null|string',
    'codepoint' => 'int|string',
  ),
  'intlchar::totitle' => 
  array (
    0 => 'int|null|string',
    'codepoint' => 'int|string',
  ),
  'intlchar::toupper' => 
  array (
    0 => 'int|null|string',
    'codepoint' => 'int|string',
  ),
  'intlcodepointbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlcodepointbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlcodepointbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlcodepointbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlcodepointbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlcodepointbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
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
  'intlcodepointbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlcodepointbreakiterator::getlastcodepoint' => 
  array (
    0 => 'int',
  ),
  'intlcodepointbreakiterator::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'intlcodepointbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlcodepointbreakiterator::gettext' => 
  array (
    0 => 'null|string',
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
  'intldateformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'null|string',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'calendar=' => 'mixed',
    'pattern=' => 'null|string',
  ),
  'intldateformatter::create' => 
  array (
    0 => 'IntlDateFormatter|null',
    'locale' => 'null|string',
    'dateType=' => 'int',
    'timeType=' => 'int',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'calendar=' => 'IntlCalendar|int|null',
    'pattern=' => 'null|string',
  ),
  'intldateformatter::format' => 
  array (
    0 => 'false|string',
    'datetime' => 'mixed',
  ),
  'intldateformatter::formatobject' => 
  array (
    0 => 'false|string',
    'datetime' => 'mixed',
    'format=' => 'mixed',
    'locale=' => 'null|string',
  ),
  'intldateformatter::getcalendar' => 
  array (
    0 => 'false|int',
  ),
  'intldateformatter::getcalendarobject' => 
  array (
    0 => 'IntlCalendar|false|null',
  ),
  'intldateformatter::getdatetype' => 
  array (
    0 => 'false|int',
  ),
  'intldateformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intldateformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intldateformatter::getlocale' => 
  array (
    0 => 'false|string',
    'type=' => 'int',
  ),
  'intldateformatter::getpattern' => 
  array (
    0 => 'false|string',
  ),
  'intldateformatter::gettimetype' => 
  array (
    0 => 'false|int',
  ),
  'intldateformatter::gettimezone' => 
  array (
    0 => 'IntlTimeZone|false',
  ),
  'intldateformatter::gettimezoneid' => 
  array (
    0 => 'false|string',
  ),
  'intldateformatter::islenient' => 
  array (
    0 => 'bool',
  ),
  'intldateformatter::localtime' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::parse' => 
  array (
    0 => 'false|float|int',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::parsetocalendar' => 
  array (
    0 => 'false|float|int',
    'string' => 'string',
    '&offset=' => 'mixed',
  ),
  'intldateformatter::setcalendar' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlCalendar|int|null',
  ),
  'intldateformatter::setlenient' => 
  array (
    0 => 'void',
    'lenient' => 'bool',
  ),
  'intldateformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'intldateformatter::settimezone' => 
  array (
    0 => 'bool',
    'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
  ),
  'intldatepatterngenerator::__construct' => 
  array (
    0 => 'void',
    'locale=' => 'null|string',
  ),
  'intldatepatterngenerator::create' => 
  array (
    0 => 'IntlDatePatternGenerator|null',
    'locale=' => 'null|string',
  ),
  'intldatepatterngenerator::getbestpattern' => 
  array (
    0 => 'false|string',
    'skeleton' => 'string',
  ),
  'intlexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'intlexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'intlexception::__wakeup' => 
  array (
    0 => 'void',
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
  'intlexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'intlexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'intlexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'intlexception::gettraceasstring' => 
  array (
    0 => 'string',
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
  'intlgregcal_set_gregorian_change' => 
  array (
    0 => 'bool',
    'calendar' => 'IntlGregorianCalendar',
    'timestamp' => 'float',
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
  'intlgregoriancalendar::createinstance' => 
  array (
    0 => 'IntlCalendar|null',
    'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
    'locale=' => 'null|string',
  ),
  'intlgregoriancalendar::equals' => 
  array (
    0 => 'bool',
    'other' => 'IntlCalendar',
  ),
  'intlgregoriancalendar::fielddifference' => 
  array (
    0 => 'false|int',
    'timestamp' => 'float',
    'field' => 'int',
  ),
  'intlgregoriancalendar::fromdatetime' => 
  array (
    0 => 'IntlCalendar|null',
    'datetime' => 'DateTime|string',
    'locale=' => 'null|string',
  ),
  'intlgregoriancalendar::get' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getactualmaximum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getactualminimum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getavailablelocales' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'intlgregoriancalendar::getdayofweektype' => 
  array (
    0 => 'false|int',
    'dayOfWeek' => 'int',
  ),
  'intlgregoriancalendar::geterrorcode' => 
  array (
    0 => 'false|int',
  ),
  'intlgregoriancalendar::geterrormessage' => 
  array (
    0 => 'false|string',
  ),
  'intlgregoriancalendar::getfirstdayofweek' => 
  array (
    0 => 'false|int',
  ),
  'intlgregoriancalendar::getgreatestminimum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getgregorianchange' => 
  array (
    0 => 'float',
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
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'intlgregoriancalendar::getmaximum' => 
  array (
    0 => 'false|int',
    'field' => 'int',
  ),
  'intlgregoriancalendar::getminimaldaysinfirstweek' => 
  array (
    0 => 'false|int',
  ),
  'intlgregoriancalendar::getminimum' => 
  array (
    0 => 'false|int',
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
    0 => 'false|float',
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
    0 => 'false|int',
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
  'intlgregoriancalendar::isleapyear' => 
  array (
    0 => 'bool',
    'year' => 'int',
  ),
  'intlgregoriancalendar::islenient' => 
  array (
    0 => 'bool',
  ),
  'intlgregoriancalendar::isset' => 
  array (
    0 => 'bool',
    'field' => 'int',
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
  'intlgregoriancalendar::setgregorianchange' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'intlgregoriancalendar::setlenient' => 
  array (
    0 => 'true',
    'lenient' => 'bool',
  ),
  'intlgregoriancalendar::setminimaldaysinfirstweek' => 
  array (
    0 => 'true',
    'days' => 'int',
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
    'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
  ),
  'intlgregoriancalendar::todatetime' => 
  array (
    0 => 'DateTime|false',
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
  'intllistformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
    'type=' => 'int',
    'width=' => 'int',
  ),
  'intllistformatter::format' => 
  array (
    0 => 'false|string',
    'strings' => 'array<array-key, mixed>',
  ),
  'intllistformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intllistformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intlpartsiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'intlpartsiterator::getbreakiterator' => 
  array (
    0 => 'IntlBreakIterator',
  ),
  'intlpartsiterator::getrulestatus' => 
  array (
    0 => 'int',
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
  'intlrulebasedbreakiterator::__construct' => 
  array (
    0 => 'void',
    'rules' => 'string',
    'compiled=' => 'bool',
  ),
  'intlrulebasedbreakiterator::createcharacterinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlrulebasedbreakiterator::createcodepointinstance' => 
  array (
    0 => 'IntlCodePointBreakIterator',
  ),
  'intlrulebasedbreakiterator::createlineinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlrulebasedbreakiterator::createsentenceinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlrulebasedbreakiterator::createtitleinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
  ),
  'intlrulebasedbreakiterator::createwordinstance' => 
  array (
    0 => 'IntlBreakIterator|null',
    'locale=' => 'null|string',
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
  'intlrulebasedbreakiterator::getbinaryrules' => 
  array (
    0 => 'false|string',
  ),
  'intlrulebasedbreakiterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'intlrulebasedbreakiterator::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'intlrulebasedbreakiterator::getlocale' => 
  array (
    0 => 'false|string',
    'type' => 'int',
  ),
  'intlrulebasedbreakiterator::getpartsiterator' => 
  array (
    0 => 'IntlPartsIterator',
    'type=' => 'string',
  ),
  'intlrulebasedbreakiterator::getrules' => 
  array (
    0 => 'false|string',
  ),
  'intlrulebasedbreakiterator::getrulestatus' => 
  array (
    0 => 'int',
  ),
  'intlrulebasedbreakiterator::getrulestatusvec' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'intlrulebasedbreakiterator::gettext' => 
  array (
    0 => 'null|string',
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
  'intltimezone::__construct' => 
  array (
    0 => 'void',
  ),
  'intltimezone::countequivalentids' => 
  array (
    0 => 'false|int',
    'timezoneId' => 'string',
  ),
  'intltimezone::createdefault' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::createenumeration' => 
  array (
    0 => 'IntlIterator|false',
    'countryOrRawOffset=' => 'int|null|string',
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
    'region=' => 'null|string',
    'rawOffset=' => 'int|null',
  ),
  'intltimezone::fromdatetimezone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezone' => 'DateTimeZone',
  ),
  'intltimezone::getcanonicalid' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    '&isSystemId=' => 'mixed',
  ),
  'intltimezone::getdisplayname' => 
  array (
    0 => 'false|string',
    'dst=' => 'bool',
    'style=' => 'int',
    'locale=' => 'null|string',
  ),
  'intltimezone::getdstsavings' => 
  array (
    0 => 'int',
  ),
  'intltimezone::getequivalentid' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    'offset' => 'int',
  ),
  'intltimezone::geterrorcode' => 
  array (
    0 => 'false|int',
  ),
  'intltimezone::geterrormessage' => 
  array (
    0 => 'false|string',
  ),
  'intltimezone::getgmt' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::getianaid' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
  ),
  'intltimezone::getid' => 
  array (
    0 => 'false|string',
  ),
  'intltimezone::getidforwindowsid' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    'region=' => 'null|string',
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
    0 => 'false|string',
    'timezoneId' => 'string',
  ),
  'intltimezone::gettzdataversion' => 
  array (
    0 => 'false|string',
  ),
  'intltimezone::getunknown' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltimezone::getwindowsid' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
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
  'intltz_count_equivalent_ids' => 
  array (
    0 => 'false|int',
    'timezoneId' => 'string',
  ),
  'intltz_create_default' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_create_enumeration' => 
  array (
    0 => 'IntlIterator|false',
    'countryOrRawOffset=' => 'int|null|string',
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
    'region=' => 'null|string',
    'rawOffset=' => 'int|null',
  ),
  'intltz_from_date_time_zone' => 
  array (
    0 => 'IntlTimeZone|null',
    'timezone' => 'DateTimeZone',
  ),
  'intltz_get_canonical_id' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    '&isSystemId=' => 'mixed',
  ),
  'intltz_get_display_name' => 
  array (
    0 => 'false|string',
    'timezone' => 'IntlTimeZone',
    'dst=' => 'bool',
    'style=' => 'int',
    'locale=' => 'null|string',
  ),
  'intltz_get_dst_savings' => 
  array (
    0 => 'int',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_equivalent_id' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    'offset' => 'int',
  ),
  'intltz_get_error_code' => 
  array (
    0 => 'false|int',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_error_message' => 
  array (
    0 => 'false|string',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_gmt' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_get_iana_id' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
  ),
  'intltz_get_id' => 
  array (
    0 => 'false|string',
    'timezone' => 'IntlTimeZone',
  ),
  'intltz_get_id_for_windows_id' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
    'region=' => 'null|string',
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
    0 => 'false|string',
    'timezoneId' => 'string',
  ),
  'intltz_get_tz_data_version' => 
  array (
    0 => 'false|string',
  ),
  'intltz_get_unknown' => 
  array (
    0 => 'IntlTimeZone',
  ),
  'intltz_get_windows_id' => 
  array (
    0 => 'false|string',
    'timezoneId' => 'string',
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
  'intval' => 
  array (
    0 => 'int',
    'value' => 'mixed',
    'base=' => 'int',
  ),
  'invalidargumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'invalidargumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::__wakeup' => 
  array (
    0 => 'void',
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
  'invalidargumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'invalidargumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'invalidargumentexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'invalidargumentexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'ip2long' => 
  array (
    0 => 'false|int',
    'ip' => 'string',
  ),
  'iptcembed' => 
  array (
    0 => 'bool|string',
    'iptc_data' => 'string',
    'filename' => 'string',
    'spool=' => 'int',
  ),
  'iptcparse' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'iptc_block' => 'string',
  ),
  'is_a' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'class' => 'string',
    'allow_string=' => 'bool',
  ),
  'is_array' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_bool' => 
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
  'is_countable' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_dir' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_double' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
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
  'is_finite' => 
  array (
    0 => 'bool',
    'num' => 'float',
  ),
  'is_float' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_infinite' => 
  array (
    0 => 'bool',
    'num' => 'float',
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
  'is_iterable' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_link' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_long' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_nan' => 
  array (
    0 => 'bool',
    'num' => 'float',
  ),
  'is_null' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_numeric' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_object' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_readable' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'is_resource' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_scalar' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_soap_fault' => 
  array (
    0 => 'bool',
    'object' => 'mixed',
  ),
  'is_string' => 
  array (
    0 => 'bool',
    'value' => 'mixed',
  ),
  'is_subclass_of' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'class' => 'string',
    'allow_string=' => 'bool',
  ),
  'is_uploaded_file' => 
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
  'iterator_apply' => 
  array (
    0 => 'int',
    'iterator' => 'Traversable',
    'callback' => 'callable',
    'args=' => 'array<array-key, mixed>|null',
  ),
  'iterator_count' => 
  array (
    0 => 'int',
    'iterator' => 'Traversable|array<array-key, mixed>',
  ),
  'iterator_to_array' => 
  array (
    0 => 'array<array-key, mixed>',
    'iterator' => 'Traversable|array<array-key, mixed>',
    'preserve_keys=' => 'bool',
  ),
  'iteratoriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Traversable',
    'class=' => 'null|string',
  ),
  'iteratoriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'iteratoriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'iteratoriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'iteratoriterator::next' => 
  array (
    0 => 'void',
  ),
  'iteratoriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'iteratoriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'join' => 
  array (
    0 => 'string',
    'separator' => 'array<array-key, mixed>|string',
    'array=' => 'array<array-key, mixed>|null',
  ),
  'json_decode' => 
  array (
    0 => 'mixed',
    'json' => 'string',
    'associative=' => 'bool|null',
    'depth=' => 'int',
    'flags=' => 'int',
  ),
  'json_encode' => 
  array (
    0 => 'false|string',
    'value' => 'mixed',
    'flags=' => 'int',
    'depth=' => 'int',
  ),
  'json_last_error' => 
  array (
    0 => 'int',
  ),
  'json_last_error_msg' => 
  array (
    0 => 'string',
  ),
  'json_validate' => 
  array (
    0 => 'bool',
    'json' => 'string',
    'depth=' => 'int',
    'flags=' => 'int',
  ),
  'jsonexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'jsonexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'jsonexception::__wakeup' => 
  array (
    0 => 'void',
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
  'jsonexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'jsonexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'jsonexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'jsonexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'key' => 
  array (
    0 => 'int|null|string',
    'array' => 'array<array-key, mixed>|object',
  ),
  'key_exists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'array' => 'array<array-key, mixed>',
  ),
  'krsort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'ksort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'lcfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'lcg_value' => 
  array (
    0 => 'float',
  ),
  'lchgrp' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'group' => 'int|string',
  ),
  'lchown' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'user' => 'int|string',
  ),
  'lengthexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'lengthexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'lengthexception::__wakeup' => 
  array (
    0 => 'void',
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
  'lengthexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'lengthexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'lengthexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'lengthexception::gettraceasstring' => 
  array (
    0 => 'string',
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
  'libxml_clear_errors' => 
  array (
    0 => 'void',
  ),
  'libxml_disable_entity_loader' => 
  array (
    0 => 'bool',
    'disable=' => 'bool',
  ),
  'libxml_get_errors' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'libxml_get_external_entity_loader' => 
  array (
    0 => 'callable|null',
  ),
  'libxml_get_last_error' => 
  array (
    0 => 'LibXMLError|false',
  ),
  'libxml_set_external_entity_loader' => 
  array (
    0 => 'true',
    'resolver_function' => 'callable|null',
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
  'limititerator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'offset=' => 'int',
    'limit=' => 'int',
  ),
  'limititerator::current' => 
  array (
    0 => 'mixed',
  ),
  'limititerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'limititerator::getposition' => 
  array (
    0 => 'int',
  ),
  'limititerator::key' => 
  array (
    0 => 'mixed',
  ),
  'limititerator::next' => 
  array (
    0 => 'void',
  ),
  'limititerator::rewind' => 
  array (
    0 => 'void',
  ),
  'limititerator::seek' => 
  array (
    0 => 'int',
    'offset' => 'int',
  ),
  'limititerator::valid' => 
  array (
    0 => 'bool',
  ),
  'link' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'link' => 'string',
  ),
  'linkinfo' => 
  array (
    0 => 'false|int',
    'path' => 'string',
  ),
  'locale::acceptfromhttp' => 
  array (
    0 => 'false|string',
    'header' => 'string',
  ),
  'locale::addlikelysubtags' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
  ),
  'locale::canonicalize' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale::composelocale' => 
  array (
    0 => 'false|string',
    'subtags' => 'array<array-key, mixed>',
  ),
  'locale::filtermatches' => 
  array (
    0 => 'bool|null',
    'languageTag' => 'string',
    'locale' => 'string',
    'canonicalize=' => 'bool',
  ),
  'locale::getallvariants' => 
  array (
    0 => 'array<array-key, mixed>|null',
    'locale' => 'string',
  ),
  'locale::getdefault' => 
  array (
    0 => 'string',
  ),
  'locale::getdisplaylanguage' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale::getdisplayname' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale::getdisplayregion' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale::getdisplayscript' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale::getdisplayvariant' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale::getkeywords' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'locale' => 'string',
  ),
  'locale::getprimarylanguage' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale::getregion' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale::getscript' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale::isrighttoleft' => 
  array (
    0 => 'bool',
    'locale' => 'string',
  ),
  'locale::lookup' => 
  array (
    0 => 'null|string',
    'languageTag' => 'array<array-key, mixed>',
    'locale' => 'string',
    'canonicalize=' => 'bool',
    'defaultLocale=' => 'null|string',
  ),
  'locale::minimizesubtags' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
  ),
  'locale::parselocale' => 
  array (
    0 => 'array<array-key, mixed>|null',
    'locale' => 'string',
  ),
  'locale::setdefault' => 
  array (
    0 => 'true',
    'locale' => 'string',
  ),
  'locale_accept_from_http' => 
  array (
    0 => 'false|string',
    'header' => 'string',
  ),
  'locale_add_likely_subtags' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
  ),
  'locale_canonicalize' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale_compose' => 
  array (
    0 => 'false|string',
    'subtags' => 'array<array-key, mixed>',
  ),
  'locale_filter_matches' => 
  array (
    0 => 'bool|null',
    'languageTag' => 'string',
    'locale' => 'string',
    'canonicalize=' => 'bool',
  ),
  'locale_get_all_variants' => 
  array (
    0 => 'array<array-key, mixed>|null',
    'locale' => 'string',
  ),
  'locale_get_default' => 
  array (
    0 => 'string',
  ),
  'locale_get_display_language' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale_get_display_name' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale_get_display_region' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale_get_display_script' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale_get_display_variant' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'displayLocale=' => 'null|string',
  ),
  'locale_get_keywords' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'locale' => 'string',
  ),
  'locale_get_primary_language' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale_get_region' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale_get_script' => 
  array (
    0 => 'null|string',
    'locale' => 'string',
  ),
  'locale_is_right_to_left' => 
  array (
    0 => 'bool',
    'locale' => 'string',
  ),
  'locale_lookup' => 
  array (
    0 => 'null|string',
    'languageTag' => 'array<array-key, mixed>',
    'locale' => 'string',
    'canonicalize=' => 'bool',
    'defaultLocale=' => 'null|string',
  ),
  'locale_minimize_subtags' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
  ),
  'locale_parse' => 
  array (
    0 => 'array<array-key, mixed>|null',
    'locale' => 'string',
  ),
  'locale_set_default' => 
  array (
    0 => 'true',
    'locale' => 'string',
  ),
  'localeconv' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'localtime' => 
  array (
    0 => 'array<array-key, mixed>',
    'timestamp=' => 'int|null',
    'associative=' => 'bool',
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
  'log1p' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'logicexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'logicexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'logicexception::__wakeup' => 
  array (
    0 => 'void',
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
  'logicexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'logicexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'logicexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'logicexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'long2ip' => 
  array (
    0 => 'string',
    'ip' => 'int',
  ),
  'lstat' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
  ),
  'ltrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'mail' => 
  array (
    0 => 'bool',
    'to' => 'string',
    'subject' => 'string',
    'message' => 'string',
    'additional_headers=' => 'array<array-key, mixed>|string',
    'additional_params=' => 'string',
  ),
  'max' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'mb_check_encoding' => 
  array (
    0 => 'bool',
    'value=' => 'array<array-key, mixed>|null|string',
    'encoding=' => 'null|string',
  ),
  'mb_chr' => 
  array (
    0 => 'false|string',
    'codepoint' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_convert_case' => 
  array (
    0 => 'string',
    'string' => 'string',
    'mode' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_convert_encoding' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'string' => 'array<array-key, mixed>|string',
    'to_encoding' => 'string',
    'from_encoding=' => 'array<array-key, mixed>|null|string',
  ),
  'mb_convert_kana' => 
  array (
    0 => 'string',
    'string' => 'string',
    'mode=' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_convert_variables' => 
  array (
    0 => 'false|string',
    'to_encoding' => 'string',
    'from_encoding' => 'array<array-key, mixed>|string',
    '&var' => 'array<array-key, mixed>|object|string',
    '...&vars=' => 'array<array-key, mixed>|object|string',
  ),
  'mb_decode_mimeheader' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'mb_decode_numericentity' => 
  array (
    0 => 'string',
    'string' => 'string',
    'map' => 'array<array-key, mixed>',
    'encoding=' => 'null|string',
  ),
  'mb_detect_encoding' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'encodings=' => 'array<array-key, mixed>|null|string',
    'strict=' => 'bool',
  ),
  'mb_detect_order' => 
  array (
    0 => 'array<array-key, mixed>|bool',
    'encoding=' => 'array<array-key, mixed>|null|string',
  ),
  'mb_encode_mimeheader' => 
  array (
    0 => 'string',
    'string' => 'string',
    'charset=' => 'null|string',
    'transfer_encoding=' => 'null|string',
    'newline=' => 'string',
    'indent=' => 'int',
  ),
  'mb_encode_numericentity' => 
  array (
    0 => 'string',
    'string' => 'string',
    'map' => 'array<array-key, mixed>',
    'encoding=' => 'null|string',
    'hex=' => 'bool',
  ),
  'mb_encoding_aliases' => 
  array (
    0 => 'array<array-key, mixed>',
    'encoding' => 'string',
  ),
  'mb_ereg' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    '&matches=' => 'mixed',
  ),
  'mb_ereg_match' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    'options=' => 'null|string',
  ),
  'mb_ereg_replace' => 
  array (
    0 => 'false|null|string',
    'pattern' => 'string',
    'replacement' => 'string',
    'string' => 'string',
    'options=' => 'null|string',
  ),
  'mb_ereg_replace_callback' => 
  array (
    0 => 'false|null|string',
    'pattern' => 'string',
    'callback' => 'callable',
    'string' => 'string',
    'options=' => 'null|string',
  ),
  'mb_ereg_search' => 
  array (
    0 => 'bool',
    'pattern=' => 'null|string',
    'options=' => 'null|string',
  ),
  'mb_ereg_search_getpos' => 
  array (
    0 => 'int',
  ),
  'mb_ereg_search_getregs' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'mb_ereg_search_init' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'pattern=' => 'null|string',
    'options=' => 'null|string',
  ),
  'mb_ereg_search_pos' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern=' => 'null|string',
    'options=' => 'null|string',
  ),
  'mb_ereg_search_regs' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern=' => 'null|string',
    'options=' => 'null|string',
  ),
  'mb_ereg_search_setpos' => 
  array (
    0 => 'bool',
    'offset' => 'int',
  ),
  'mb_eregi' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
    'string' => 'string',
    '&matches=' => 'mixed',
  ),
  'mb_eregi_replace' => 
  array (
    0 => 'false|null|string',
    'pattern' => 'string',
    'replacement' => 'string',
    'string' => 'string',
    'options=' => 'null|string',
  ),
  'mb_get_info' => 
  array (
    0 => 'array<array-key, mixed>|false|int|null|string',
    'type=' => 'string',
  ),
  'mb_http_input' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'type=' => 'null|string',
  ),
  'mb_http_output' => 
  array (
    0 => 'bool|string',
    'encoding=' => 'null|string',
  ),
  'mb_internal_encoding' => 
  array (
    0 => 'bool|string',
    'encoding=' => 'null|string',
  ),
  'mb_language' => 
  array (
    0 => 'bool|string',
    'language=' => 'null|string',
  ),
  'mb_lcfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_list_encodings' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mb_ltrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'null|string',
    'encoding=' => 'null|string',
  ),
  'mb_ord' => 
  array (
    0 => 'false|int',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_output_handler' => 
  array (
    0 => 'string',
    'string' => 'string',
    'status' => 'int',
  ),
  'mb_parse_str' => 
  array (
    0 => 'bool',
    'string' => 'string',
    '&result' => 'mixed',
  ),
  'mb_preferred_mime_name' => 
  array (
    0 => 'false|string',
    'encoding' => 'string',
  ),
  'mb_regex_encoding' => 
  array (
    0 => 'bool|string',
    'encoding=' => 'null|string',
  ),
  'mb_regex_set_options' => 
  array (
    0 => 'string',
    'options=' => 'null|string',
  ),
  'mb_rtrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'null|string',
    'encoding=' => 'null|string',
  ),
  'mb_scrub' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_send_mail' => 
  array (
    0 => 'bool',
    'to' => 'string',
    'subject' => 'string',
    'message' => 'string',
    'additional_headers=' => 'array<array-key, mixed>|string',
    'additional_params=' => 'null|string',
  ),
  'mb_split' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'string' => 'string',
    'limit=' => 'int',
  ),
  'mb_str_pad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length' => 'int',
    'pad_string=' => 'string',
    'pad_type=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_str_split' => 
  array (
    0 => 'array<array-key, mixed>',
    'string' => 'string',
    'length=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_strcut' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'null|string',
  ),
  'mb_strimwidth' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'width' => 'int',
    'trim_marker=' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_stripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_stristr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'null|string',
  ),
  'mb_strlen' => 
  array (
    0 => 'int',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_strpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_strrchr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'null|string',
  ),
  'mb_strrichr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'null|string',
  ),
  'mb_strripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_strrpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'encoding=' => 'null|string',
  ),
  'mb_strstr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
    'encoding=' => 'null|string',
  ),
  'mb_strtolower' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_strtoupper' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_strwidth' => 
  array (
    0 => 'int',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_substitute_character' => 
  array (
    0 => 'bool|int|string',
    'substitute_character=' => 'int|null|string',
  ),
  'mb_substr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'start' => 'int',
    'length=' => 'int|null',
    'encoding=' => 'null|string',
  ),
  'mb_substr_count' => 
  array (
    0 => 'int',
    'haystack' => 'string',
    'needle' => 'string',
    'encoding=' => 'null|string',
  ),
  'mb_trim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'null|string',
    'encoding=' => 'null|string',
  ),
  'mb_ucfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
    'encoding=' => 'null|string',
  ),
  'md5' => 
  array (
    0 => 'string',
    'string' => 'string',
    'binary=' => 'bool',
  ),
  'md5_file' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
    'binary=' => 'bool',
  ),
  'memory_get_peak_usage' => 
  array (
    0 => 'int',
    'real_usage=' => 'bool',
  ),
  'memory_get_usage' => 
  array (
    0 => 'int',
    'real_usage=' => 'bool',
  ),
  'memory_reset_peak_usage' => 
  array (
    0 => 'void',
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
    0 => 'false|string',
    'values' => 'array<array-key, mixed>',
  ),
  'messageformatter::formatmessage' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'pattern' => 'string',
    'values' => 'array<array-key, mixed>',
  ),
  'messageformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'messageformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'messageformatter::getlocale' => 
  array (
    0 => 'string',
  ),
  'messageformatter::getpattern' => 
  array (
    0 => 'false|string',
  ),
  'messageformatter::parse' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'string' => 'string',
  ),
  'messageformatter::parsemessage' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'locale' => 'string',
    'pattern' => 'string',
    'message' => 'string',
  ),
  'messageformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'metaphone' => 
  array (
    0 => 'string',
    'string' => 'string',
    'max_phonemes=' => 'int',
  ),
  'method_exists' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'method' => 'string',
  ),
  'mhash' => 
  array (
    0 => 'false|string',
    'algo' => 'int',
    'data' => 'string',
    'key=' => 'null|string',
  ),
  'mhash_count' => 
  array (
    0 => 'int',
  ),
  'mhash_get_block_size' => 
  array (
    0 => 'false|int',
    'algo' => 'int',
  ),
  'mhash_get_hash_name' => 
  array (
    0 => 'false|string',
    'algo' => 'int',
  ),
  'mhash_keygen_s2k' => 
  array (
    0 => 'false|string',
    'algo' => 'int',
    'password' => 'string',
    'salt' => 'string',
    'length' => 'int',
  ),
  'microtime' => 
  array (
    0 => 'float|string',
    'as_float=' => 'bool',
  ),
  'mime_content_type' => 
  array (
    0 => 'false|string',
    'filename' => 'mixed',
  ),
  'min' => 
  array (
    0 => 'mixed',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'mkdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'permissions=' => 'int',
    'recursive=' => 'bool',
    'context=' => 'mixed',
  ),
  'mktime' => 
  array (
    0 => 'false|int',
    'hour' => 'int',
    'minute=' => 'int|null',
    'second=' => 'int|null',
    'month=' => 'int|null',
    'day=' => 'int|null',
    'year=' => 'int|null',
  ),
  'mongodb\\bson\\binary::__construct' => 
  array (
    0 => 'void',
    'data' => 'string',
    'type=' => 'int',
  ),
  'mongodb\\bson\\binary::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\binary::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\binary::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\binary::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\binary::getdata' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\binary::gettype' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\binary::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\dbpointer::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\dbpointer::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\dbpointer::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\DBPointer',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\dbpointer::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\dbpointer::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
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
  'mongodb\\bson\\decimal128::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\decimal128::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Decimal128',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\decimal128::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\decimal128::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\decimal128::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\document::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\document::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\document::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Document',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\document::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\document::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
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
    'value' => 'array<array-key, mixed>|object',
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
  'mongodb\\bson\\document::tocanonicalextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\document::tophp' => 
  array (
    0 => 'array<array-key, mixed>|object',
    'typeMap=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\bson\\document::torelaxedextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\int64::__construct' => 
  array (
    0 => 'void',
    'value' => 'int|string',
  ),
  'mongodb\\bson\\int64::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\int64::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Int64',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\int64::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\int64::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\int64::jsonserialize' => 
  array (
    0 => 'mixed',
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
    0 => 'int|string',
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
  'mongodb\\bson\\javascript::__construct' => 
  array (
    0 => 'void',
    'code' => 'string',
    'scope=' => 'array<array-key, mixed>|null|object',
  ),
  'mongodb\\bson\\javascript::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\javascript::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Javascript',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\javascript::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\javascript::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\javascript::getcode' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\javascript::getscope' => 
  array (
    0 => 'null|object',
  ),
  'mongodb\\bson\\javascript::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\maxkey::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\maxkey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MaxKey',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\maxkey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\maxkey::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\minkey::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\minkey::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\MinKey',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\minkey::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\minkey::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\objectid::__construct' => 
  array (
    0 => 'void',
    'id=' => 'null|string',
  ),
  'mongodb\\bson\\objectid::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\objectid::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\objectid::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\objectid::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\objectid::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\objectid::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\packedarray::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\packedarray::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\packedarray::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\packedarray::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\packedarray::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\packedarray::fromjson' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'json' => 'string',
  ),
  'mongodb\\bson\\packedarray::fromphp' => 
  array (
    0 => 'MongoDB\\BSON\\PackedArray',
    'value' => 'array<array-key, mixed>',
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
  'mongodb\\bson\\packedarray::tocanonicalextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\packedarray::tophp' => 
  array (
    0 => 'array<array-key, mixed>|object',
    'typeMap=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\bson\\packedarray::torelaxedextendedjson' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::__construct' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'flags=' => 'string',
  ),
  'mongodb\\bson\\regex::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\regex::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Regex',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\regex::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\regex::getflags' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::getpattern' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\regex::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\symbol::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\symbol::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\symbol::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Symbol',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\symbol::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\symbol::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\symbol::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\timestamp::__construct' => 
  array (
    0 => 'void',
    'increment' => 'int|string',
    'timestamp' => 'int|string',
  ),
  'mongodb\\bson\\timestamp::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\timestamp::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Timestamp',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\timestamp::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\timestamp::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\timestamp::getincrement' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\timestamp::gettimestamp' => 
  array (
    0 => 'int',
  ),
  'mongodb\\bson\\timestamp::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\undefined::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\bson\\undefined::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\undefined::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\Undefined',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\undefined::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\undefined::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\undefined::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\utcdatetime::__construct' => 
  array (
    0 => 'void',
    'milliseconds=' => 'DateTimeInterface|MongoDB\\BSON\\Int64|int|null',
  ),
  'mongodb\\bson\\utcdatetime::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\utcdatetime::__set_state' => 
  array (
    0 => 'MongoDB\\BSON\\UTCDateTime',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\utcdatetime::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\bson\\utcdatetime::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\bson\\utcdatetime::jsonserialize' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\bson\\utcdatetime::todatetime' => 
  array (
    0 => 'DateTime',
  ),
  'mongodb\\bson\\utcdatetime::todatetimeimmutable' => 
  array (
    0 => 'DateTimeImmutable',
  ),
  'mongodb\\driver\\bulkwrite::__construct' => 
  array (
    0 => 'void',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwrite::count' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwrite::delete' => 
  array (
    0 => 'void',
    'filter' => 'array<array-key, mixed>|object',
    'deleteOptions=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwrite::insert' => 
  array (
    0 => 'mixed',
    'document' => 'array<array-key, mixed>|object',
  ),
  'mongodb\\driver\\bulkwrite::update' => 
  array (
    0 => 'void',
    'filter' => 'array<array-key, mixed>|object',
    'newObj' => 'array<array-key, mixed>|object',
    'updateOptions=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::__construct' => 
  array (
    0 => 'void',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::count' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommand::deletemany' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::deleteone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::insertone' => 
  array (
    0 => 'mixed',
    'namespace' => 'string',
    'document' => 'array<array-key, mixed>|object',
  ),
  'mongodb\\driver\\bulkwritecommand::replaceone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'array<array-key, mixed>|object',
    'replacement' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::updatemany' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'array<array-key, mixed>|object',
    'update' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommand::updateone' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'filter' => 'array<array-key, mixed>|object',
    'update' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getdeletedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getdeleteresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getinsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getinsertresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getmatchedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getmodifiedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getupdateresults' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\bulkwritecommandresult::getupsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\bulkwritecommandresult::isacknowledged' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\clientencryption::__construct' => 
  array (
    0 => 'void',
    'options' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\clientencryption::addkeyaltname' => 
  array (
    0 => 'null|object',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::createdatakey' => 
  array (
    0 => 'MongoDB\\BSON\\Binary',
    'kmsProvider' => 'string',
    'options=' => 'array<array-key, mixed>|null',
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
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\clientencryption::encryptexpression' => 
  array (
    0 => 'object',
    'expr' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\clientencryption::getkey' => 
  array (
    0 => 'null|object',
    'keyId' => 'MongoDB\\BSON\\Binary',
  ),
  'mongodb\\driver\\clientencryption::getkeybyaltname' => 
  array (
    0 => 'null|object',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::getkeys' => 
  array (
    0 => 'MongoDB\\Driver\\Cursor',
  ),
  'mongodb\\driver\\clientencryption::removekeyaltname' => 
  array (
    0 => 'null|object',
    'keyId' => 'MongoDB\\BSON\\Binary',
    'keyAltName' => 'string',
  ),
  'mongodb\\driver\\clientencryption::rewrapmanydatakey' => 
  array (
    0 => 'object',
    'filter' => 'array<array-key, mixed>|object',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\command::__construct' => 
  array (
    0 => 'void',
    'document' => 'array<array-key, mixed>|object',
    'commandOptions=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\cursor::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\cursor::current' => 
  array (
    0 => 'array<array-key, mixed>|null|object',
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
    'typemap' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\cursor::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\cursor::valid' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\authenticationexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\authenticationexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\authenticationexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\authenticationexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\bulkwritecommandexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::__wakeup' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getcode' => 
  array (
    0 => 'mixed',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::geterrorreply' => 
  array (
    0 => 'MongoDB\\BSON\\Document|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getfile' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getline' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getpartialresult' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getwriteconcernerrors' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::getwriteerrors' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\bulkwritecommandexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\bulkwriteexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\bulkwriteexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\bulkwriteexception::gettraceasstring' => 
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
  'mongodb\\driver\\exception\\commandexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\commandexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\commandexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\commandexception::getresultdocument' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\exception\\commandexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\commandexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\commandexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\connectionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\connectionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectionexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\connectionexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectionexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\connectiontimeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\connectiontimeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\connectiontimeoutexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\encryptionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\encryptionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\encryptionexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\encryptionexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\encryptionexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\executiontimeoutexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\executiontimeoutexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\executiontimeoutexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\invalidargumentexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\invalidargumentexception::gettraceasstring' => 
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
  'mongodb\\driver\\exception\\logicexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\logicexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\logicexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\logicexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\logicexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\runtimeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\runtimeexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\runtimeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\runtimeexception::haserrorlabel' => 
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
  'mongodb\\driver\\exception\\serverexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\serverexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\serverexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\serverexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\serverexception::haserrorlabel' => 
  array (
    0 => 'bool',
    'errorLabel' => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::__wakeup' => 
  array (
    0 => 'void',
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
  'mongodb\\driver\\exception\\unexpectedvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\exception\\unexpectedvalueexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\manager::__construct' => 
  array (
    0 => 'void',
    'uri=' => 'null|string',
    'uriOptions=' => 'array<array-key, mixed>|null',
    'driverOptions=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::addsubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
  ),
  'mongodb\\driver\\manager::createclientencryption' => 
  array (
    0 => 'MongoDB\\Driver\\ClientEncryption',
    'options' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\manager::executebulkwrite' => 
  array (
    0 => 'MongoDB\\Driver\\WriteResult',
    'namespace' => 'string',
    'bulk' => 'MongoDB\\Driver\\BulkWrite',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executebulkwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult',
    'bulkWriteCommand' => 'MongoDB\\Driver\\BulkWriteCommand',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executequery' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executereadcommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executereadwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::executewritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\manager::getencryptedfieldsmap' => 
  array (
    0 => 'array<array-key, mixed>|null|object',
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
    0 => 'array<array-key, mixed>',
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
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\monitoring\\addsubscriber' => 
  array (
    0 => 'void',
    'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
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
  'mongodb\\driver\\monitoring\\commandfailedevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\commandfailedevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
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
  'mongodb\\driver\\monitoring\\commandstartedevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\commandstartedevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
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
  'mongodb\\driver\\monitoring\\commandsucceededevent::getserverconnectionid' => 
  array (
    0 => 'int|null',
  ),
  'mongodb\\driver\\monitoring\\commandsucceededevent::getserviceid' => 
  array (
    0 => 'MongoDB\\BSON\\ObjectId|null',
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
  'mongodb\\driver\\monitoring\\serverchangedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::getnewdescription' => 
  array (
    0 => 'MongoDB\\Driver\\ServerDescription',
  ),
  'mongodb\\driver\\monitoring\\serverchangedevent::getport' => 
  array (
    0 => 'int',
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
  'mongodb\\driver\\monitoring\\serverclosedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverclosedevent::getport' => 
  array (
    0 => 'int',
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
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatfailedevent::isawaited' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatstartedevent::getport' => 
  array (
    0 => 'int',
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
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::getport' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::getreply' => 
  array (
    0 => 'object',
  ),
  'mongodb\\driver\\monitoring\\serverheartbeatsucceededevent::isawaited' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\monitoring\\serveropeningevent::getport' => 
  array (
    0 => 'int',
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
  'mongodb\\driver\\query::__construct' => 
  array (
    0 => 'void',
    'filter' => 'array<array-key, mixed>|object',
    'queryOptions=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\readconcern::__construct' => 
  array (
    0 => 'void',
    'level=' => 'null|string',
  ),
  'mongodb\\driver\\readconcern::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readconcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadConcern',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readconcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readconcern::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\readconcern::getlevel' => 
  array (
    0 => 'null|string',
  ),
  'mongodb\\driver\\readconcern::isdefault' => 
  array (
    0 => 'bool',
  ),
  'mongodb\\driver\\readpreference::__construct' => 
  array (
    0 => 'void',
    'mode' => 'string',
    'tagSets=' => 'array<array-key, mixed>|null',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\readpreference::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readpreference::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ReadPreference',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readpreference::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\readpreference::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\readpreference::gethedge' => 
  array (
    0 => 'null|object',
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
    0 => 'array<array-key, mixed>',
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
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executebulkwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\BulkWriteCommandResult',
    'bulkWriteCommand' => 'MongoDB\\Driver\\BulkWriteCommand',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executequery' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'namespace' => 'string',
    'query' => 'MongoDB\\Driver\\Query',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executereadcommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executereadwritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::executewritecommand' => 
  array (
    0 => 'MongoDB\\Driver\\CursorInterface',
    'db' => 'string',
    'command' => 'MongoDB\\Driver\\Command',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\server::gethost' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\server::getinfo' => 
  array (
    0 => 'array<array-key, mixed>',
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
    0 => 'array<array-key, mixed>',
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
  'mongodb\\driver\\serverapi::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\serverapi::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\ServerApi',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\serverapi::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\serverapi::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\serverdescription::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\serverdescription::gethelloresponse' => 
  array (
    0 => 'array<array-key, mixed>',
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
    'clusterTime' => 'array<array-key, mixed>|object',
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
    0 => 'null|object',
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
    0 => 'array<array-key, mixed>|null',
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
    'options=' => 'array<array-key, mixed>|null',
  ),
  'mongodb\\driver\\topologydescription::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\topologydescription::getservers' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'mongodb\\driver\\writeconcern::__construct' => 
  array (
    0 => 'void',
    'w' => 'int|string',
    'wtimeout=' => 'int|null',
    'journal=' => 'bool|null',
  ),
  'mongodb\\driver\\writeconcern::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\writeconcern::__set_state' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcern',
    'properties' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\writeconcern::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\writeconcern::bsonserialize' => 
  array (
    0 => 'stdClass',
  ),
  'mongodb\\driver\\writeconcern::getjournal' => 
  array (
    0 => 'bool|null',
  ),
  'mongodb\\driver\\writeconcern::getw' => 
  array (
    0 => 'int|null|string',
  ),
  'mongodb\\driver\\writeconcern::getwtimeout' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeconcern::isdefault' => 
  array (
    0 => 'bool',
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
    0 => 'null|object',
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
    0 => 'null|object',
  ),
  'mongodb\\driver\\writeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'mongodb\\driver\\writeresult::__construct' => 
  array (
    0 => 'void',
  ),
  'mongodb\\driver\\writeresult::getdeletedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::geterrorreplies' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'mongodb\\driver\\writeresult::getserver' => 
  array (
    0 => 'MongoDB\\Driver\\Server',
  ),
  'mongodb\\driver\\writeresult::getupsertedcount' => 
  array (
    0 => 'int',
  ),
  'mongodb\\driver\\writeresult::getupsertedids' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\writeresult::getwriteconcernerror' => 
  array (
    0 => 'MongoDB\\Driver\\WriteConcernError|null',
  ),
  'mongodb\\driver\\writeresult::getwriteerrors' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'mongodb\\driver\\writeresult::isacknowledged' => 
  array (
    0 => 'bool',
  ),
  'move_uploaded_file' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
  ),
  'msgfmt_create' => 
  array (
    0 => 'MessageFormatter|null',
    'locale' => 'string',
    'pattern' => 'string',
  ),
  'msgfmt_format' => 
  array (
    0 => 'false|string',
    'formatter' => 'MessageFormatter',
    'values' => 'array<array-key, mixed>',
  ),
  'msgfmt_format_message' => 
  array (
    0 => 'false|string',
    'locale' => 'string',
    'pattern' => 'string',
    'values' => 'array<array-key, mixed>',
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
  'msgfmt_get_locale' => 
  array (
    0 => 'string',
    'formatter' => 'MessageFormatter',
  ),
  'msgfmt_get_pattern' => 
  array (
    0 => 'false|string',
    'formatter' => 'MessageFormatter',
  ),
  'msgfmt_parse' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'formatter' => 'MessageFormatter',
    'string' => 'string',
  ),
  'msgfmt_parse_message' => 
  array (
    0 => 'array<array-key, mixed>|false',
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
  'mt_getrandmax' => 
  array (
    0 => 'int',
  ),
  'mt_rand' => 
  array (
    0 => 'int',
    'min=' => 'int',
    'max=' => 'int',
  ),
  'mt_srand' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'multipleiterator::__construct' => 
  array (
    0 => 'void',
    'flags=' => 'int',
  ),
  'multipleiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'multipleiterator::attachiterator' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'info=' => 'int|null|string',
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
  'multipleiterator::current' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'multipleiterator::detachiterator' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'multipleiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'multipleiterator::key' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'multipleiterator::next' => 
  array (
    0 => 'void',
  ),
  'multipleiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'multipleiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'multipleiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'natcasesort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
  ),
  'natsort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
  ),
  'net_get_interfaces' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'next' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>|object',
  ),
  'nl2br' => 
  array (
    0 => 'string',
    'string' => 'string',
    'use_xhtml=' => 'bool',
  ),
  'nl_langinfo' => 
  array (
    0 => 'false|string',
    'item' => 'int',
  ),
  'nodiscard::__construct' => 
  array (
    0 => 'void',
    'message=' => 'null|string',
  ),
  'norewinditerator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
  ),
  'norewinditerator::current' => 
  array (
    0 => 'mixed',
  ),
  'norewinditerator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'norewinditerator::key' => 
  array (
    0 => 'mixed',
  ),
  'norewinditerator::next' => 
  array (
    0 => 'void',
  ),
  'norewinditerator::rewind' => 
  array (
    0 => 'void',
  ),
  'norewinditerator::valid' => 
  array (
    0 => 'bool',
  ),
  'normalizer::getrawdecomposition' => 
  array (
    0 => 'null|string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer::isnormalized' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer::normalize' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer_get_raw_decomposition' => 
  array (
    0 => 'null|string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer_is_normalized' => 
  array (
    0 => 'bool',
    'string' => 'string',
    'form=' => 'int',
  ),
  'normalizer_normalize' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'form=' => 'int',
  ),
  'number_format' => 
  array (
    0 => 'string',
    'num' => 'float',
    'decimals=' => 'int',
    'decimal_separator=' => 'null|string',
    'thousands_separator=' => 'null|string',
  ),
  'numberformatter::__construct' => 
  array (
    0 => 'void',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'null|string',
  ),
  'numberformatter::create' => 
  array (
    0 => 'NumberFormatter|null',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'null|string',
  ),
  'numberformatter::format' => 
  array (
    0 => 'false|string',
    'num' => 'float|int',
    'type=' => 'int',
  ),
  'numberformatter::formatcurrency' => 
  array (
    0 => 'false|string',
    'amount' => 'float',
    'currency' => 'string',
  ),
  'numberformatter::getattribute' => 
  array (
    0 => 'false|float|int',
    'attribute' => 'int',
  ),
  'numberformatter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'numberformatter::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'numberformatter::getlocale' => 
  array (
    0 => 'false|string',
    'type=' => 'int',
  ),
  'numberformatter::getpattern' => 
  array (
    0 => 'false|string',
  ),
  'numberformatter::getsymbol' => 
  array (
    0 => 'false|string',
    'symbol' => 'int',
  ),
  'numberformatter::gettextattribute' => 
  array (
    0 => 'false|string',
    'attribute' => 'int',
  ),
  'numberformatter::parse' => 
  array (
    0 => 'false|float|int',
    'string' => 'string',
    'type=' => 'int',
    '&offset=' => 'mixed',
  ),
  'numberformatter::parsecurrency' => 
  array (
    0 => 'false|float',
    'string' => 'string',
    '&currency' => 'mixed',
    '&offset=' => 'mixed',
  ),
  'numberformatter::setattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'float|int',
  ),
  'numberformatter::setpattern' => 
  array (
    0 => 'bool',
    'pattern' => 'string',
  ),
  'numberformatter::setsymbol' => 
  array (
    0 => 'bool',
    'symbol' => 'int',
    'value' => 'string',
  ),
  'numberformatter::settextattribute' => 
  array (
    0 => 'bool',
    'attribute' => 'int',
    'value' => 'string',
  ),
  'numfmt_create' => 
  array (
    0 => 'NumberFormatter|null',
    'locale' => 'string',
    'style' => 'int',
    'pattern=' => 'null|string',
  ),
  'numfmt_format' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
    'num' => 'float|int',
    'type=' => 'int',
  ),
  'numfmt_format_currency' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
    'amount' => 'float',
    'currency' => 'string',
  ),
  'numfmt_get_attribute' => 
  array (
    0 => 'false|float|int',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
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
  'numfmt_get_locale' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
    'type=' => 'int',
  ),
  'numfmt_get_pattern' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
  ),
  'numfmt_get_symbol' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
    'symbol' => 'int',
  ),
  'numfmt_get_text_attribute' => 
  array (
    0 => 'false|string',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
  ),
  'numfmt_parse' => 
  array (
    0 => 'false|float|int',
    'formatter' => 'NumberFormatter',
    'string' => 'string',
    'type=' => 'int',
    '&offset=' => 'mixed',
  ),
  'numfmt_parse_currency' => 
  array (
    0 => 'false|float',
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
    'value' => 'float|int',
  ),
  'numfmt_set_pattern' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'pattern' => 'string',
  ),
  'numfmt_set_symbol' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'symbol' => 'int',
    'value' => 'string',
  ),
  'numfmt_set_text_attribute' => 
  array (
    0 => 'bool',
    'formatter' => 'NumberFormatter',
    'attribute' => 'int',
    'value' => 'string',
  ),
  'ob_clean' => 
  array (
    0 => 'bool',
  ),
  'ob_end_clean' => 
  array (
    0 => 'bool',
  ),
  'ob_end_flush' => 
  array (
    0 => 'bool',
  ),
  'ob_flush' => 
  array (
    0 => 'bool',
  ),
  'ob_get_clean' => 
  array (
    0 => 'false|string',
  ),
  'ob_get_contents' => 
  array (
    0 => 'false|string',
  ),
  'ob_get_flush' => 
  array (
    0 => 'false|string',
  ),
  'ob_get_length' => 
  array (
    0 => 'false|int',
  ),
  'ob_get_level' => 
  array (
    0 => 'int',
  ),
  'ob_get_status' => 
  array (
    0 => 'array<array-key, mixed>',
    'full_status=' => 'bool',
  ),
  'ob_gzhandler' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'flags' => 'int',
  ),
  'ob_implicit_flush' => 
  array (
    0 => 'void',
    'enable=' => 'bool',
  ),
  'ob_list_handlers' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'ob_start' => 
  array (
    0 => 'bool',
    'callback=' => 'mixed',
    'chunk_size=' => 'int',
    'flags=' => 'int',
  ),
  'octdec' => 
  array (
    0 => 'float|int',
    'octal_string' => 'string',
  ),
  'opcache_compile_file' => 
  array (
    0 => 'bool',
    'filename' => 'string',
  ),
  'opcache_get_configuration' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'opcache_get_status' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'include_scripts=' => 'bool',
  ),
  'opcache_invalidate' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'force=' => 'bool',
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
  'opcache_jit_blacklist' => 
  array (
    0 => 'void',
    'closure' => 'Closure',
  ),
  'opcache_reset' => 
  array (
    0 => 'bool',
  ),
  'opendir' => 
  array (
    0 => 'mixed',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'openlog' => 
  array (
    0 => 'true',
    'prefix' => 'string',
    'flags' => 'int',
    'facility' => 'int',
  ),
  'openssl_cipher_iv_length' => 
  array (
    0 => 'false|int',
    'cipher_algo' => 'string',
  ),
  'openssl_cipher_key_length' => 
  array (
    0 => 'false|int',
    'cipher_algo' => 'string',
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
  'openssl_cms_encrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'headers' => 'array<array-key, mixed>|null',
    'flags=' => 'int',
    'encoding=' => 'int',
    'cipher_algo=' => 'int|string',
  ),
  'openssl_cms_read' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    '&certificates' => 'mixed',
  ),
  'openssl_cms_sign' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
    'headers' => 'array<array-key, mixed>|null',
    'flags=' => 'int',
    'encoding=' => 'int',
    'untrusted_certificates_filename=' => 'null|string',
  ),
  'openssl_cms_verify' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'flags=' => 'int',
    'certificates=' => 'null|string',
    'ca_info=' => 'array<array-key, mixed>',
    'untrusted_certificates_filename=' => 'null|string',
    'content=' => 'null|string',
    'pk7=' => 'null|string',
    'sigfile=' => 'null|string',
    'encoding=' => 'int',
  ),
  'openssl_csr_export' => 
  array (
    0 => 'bool',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    '&output' => 'mixed',
    'no_text=' => 'bool',
  ),
  'openssl_csr_export_to_file' => 
  array (
    0 => 'bool',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'output_filename' => 'string',
    'no_text=' => 'bool',
  ),
  'openssl_csr_get_public_key' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'short_names=' => 'bool',
  ),
  'openssl_csr_get_subject' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'short_names=' => 'bool',
  ),
  'openssl_csr_new' => 
  array (
    0 => 'OpenSSLCertificateSigningRequest|bool',
    'distinguished_names' => 'array<array-key, mixed>',
    '&private_key' => 'mixed',
    'options=' => 'array<array-key, mixed>|null',
    'extra_attributes=' => 'array<array-key, mixed>|null',
  ),
  'openssl_csr_sign' => 
  array (
    0 => 'OpenSSLCertificate|false',
    'csr' => 'OpenSSLCertificateSigningRequest|string',
    'ca_certificate' => 'OpenSSLCertificate|null|string',
    'private_key' => 'mixed',
    'days' => 'int',
    'options=' => 'array<array-key, mixed>|null',
    'serial=' => 'int',
    'serial_hex=' => 'null|string',
  ),
  'openssl_decrypt' => 
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
  'openssl_dh_compute_key' => 
  array (
    0 => 'false|string',
    'public_key' => 'string',
    'private_key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_digest' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'digest_algo' => 'string',
    'binary=' => 'bool',
  ),
  'openssl_encrypt' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'cipher_algo' => 'string',
    'passphrase' => 'string',
    'options=' => 'int',
    'iv=' => 'string',
    '&tag=' => 'mixed',
    'aad=' => 'string',
    'tag_length=' => 'int',
  ),
  'openssl_error_string' => 
  array (
    0 => 'false|string',
  ),
  'openssl_free_key' => 
  array (
    0 => 'void',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_get_cert_locations' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'openssl_get_cipher_methods' => 
  array (
    0 => 'array<array-key, mixed>',
    'aliases=' => 'bool',
  ),
  'openssl_get_curve_names' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'openssl_get_md_methods' => 
  array (
    0 => 'array<array-key, mixed>',
    'aliases=' => 'bool',
  ),
  'openssl_get_privatekey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'private_key' => 'mixed',
    'passphrase=' => 'null|string',
  ),
  'openssl_get_publickey' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'mixed',
  ),
  'openssl_open' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&output' => 'mixed',
    'encrypted_key' => 'string',
    'private_key' => 'mixed',
    'cipher_algo' => 'string',
    'iv=' => 'null|string',
  ),
  'openssl_pbkdf2' => 
  array (
    0 => 'false|string',
    'password' => 'string',
    'salt' => 'string',
    'key_length' => 'int',
    'iterations' => 'int',
    'digest_algo=' => 'string',
  ),
  'openssl_pkcs12_export' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    '&output' => 'mixed',
    'private_key' => 'mixed',
    'passphrase' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'openssl_pkcs12_export_to_file' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'output_filename' => 'string',
    'private_key' => 'mixed',
    'passphrase' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'openssl_pkcs12_read' => 
  array (
    0 => 'bool',
    'pkcs12' => 'string',
    '&certificates' => 'mixed',
    'passphrase' => 'string',
  ),
  'openssl_pkcs7_decrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'private_key=' => 'mixed',
  ),
  'openssl_pkcs7_encrypt' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'mixed',
    'headers' => 'array<array-key, mixed>|null',
    'flags=' => 'int',
    'cipher_algo=' => 'int',
  ),
  'openssl_pkcs7_read' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&certificates' => 'mixed',
  ),
  'openssl_pkcs7_sign' => 
  array (
    0 => 'bool',
    'input_filename' => 'string',
    'output_filename' => 'string',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
    'headers' => 'array<array-key, mixed>|null',
    'flags=' => 'int',
    'untrusted_certificates_filename=' => 'null|string',
  ),
  'openssl_pkcs7_verify' => 
  array (
    0 => 'bool|int',
    'input_filename' => 'string',
    'flags' => 'int',
    'signers_certificates_filename=' => 'null|string',
    'ca_info=' => 'array<array-key, mixed>',
    'untrusted_certificates_filename=' => 'null|string',
    'content=' => 'null|string',
    'output_filename=' => 'null|string',
  ),
  'openssl_pkey_derive' => 
  array (
    0 => 'false|string',
    'public_key' => 'mixed',
    'private_key' => 'mixed',
    'key_length=' => 'int',
  ),
  'openssl_pkey_export' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    '&output' => 'mixed',
    'passphrase=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'openssl_pkey_export_to_file' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
    'output_filename' => 'string',
    'passphrase=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'openssl_pkey_free' => 
  array (
    0 => 'void',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_pkey_get_details' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'key' => 'OpenSSLAsymmetricKey',
  ),
  'openssl_pkey_get_private' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'private_key' => 'mixed',
    'passphrase=' => 'null|string',
  ),
  'openssl_pkey_get_public' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'public_key' => 'mixed',
  ),
  'openssl_pkey_new' => 
  array (
    0 => 'OpenSSLAsymmetricKey|false',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'openssl_private_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'mixed',
    'private_key' => 'mixed',
    'padding=' => 'int',
    'digest_algo=' => 'null|string',
  ),
  'openssl_private_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'mixed',
    'private_key' => 'mixed',
    'padding=' => 'int',
  ),
  'openssl_public_decrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&decrypted_data' => 'mixed',
    'public_key' => 'mixed',
    'padding=' => 'int',
  ),
  'openssl_public_encrypt' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&encrypted_data' => 'mixed',
    'public_key' => 'mixed',
    'padding=' => 'int',
    'digest_algo=' => 'null|string',
  ),
  'openssl_random_pseudo_bytes' => 
  array (
    0 => 'string',
    'length' => 'int',
    '&strong_result=' => 'mixed',
  ),
  'openssl_seal' => 
  array (
    0 => 'false|int',
    'data' => 'string',
    '&sealed_data' => 'mixed',
    '&encrypted_keys' => 'mixed',
    'public_key' => 'array<array-key, mixed>',
    'cipher_algo' => 'string',
    '&iv=' => 'mixed',
  ),
  'openssl_sign' => 
  array (
    0 => 'bool',
    'data' => 'string',
    '&signature' => 'mixed',
    'private_key' => 'mixed',
    'algorithm=' => 'int|string',
    'padding=' => 'int',
  ),
  'openssl_spki_export' => 
  array (
    0 => 'false|string',
    'spki' => 'string',
  ),
  'openssl_spki_export_challenge' => 
  array (
    0 => 'false|string',
    'spki' => 'string',
  ),
  'openssl_spki_new' => 
  array (
    0 => 'false|string',
    'private_key' => 'OpenSSLAsymmetricKey',
    'challenge' => 'string',
    'digest_algo=' => 'int',
  ),
  'openssl_spki_verify' => 
  array (
    0 => 'bool',
    'spki' => 'string',
  ),
  'openssl_verify' => 
  array (
    0 => 'false|int',
    'data' => 'string',
    'signature' => 'string',
    'public_key' => 'mixed',
    'algorithm=' => 'int|string',
    'padding=' => 'int',
  ),
  'openssl_x509_check_private_key' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'private_key' => 'mixed',
  ),
  'openssl_x509_checkpurpose' => 
  array (
    0 => 'bool|int',
    'certificate' => 'OpenSSLCertificate|string',
    'purpose' => 'int',
    'ca_info=' => 'array<array-key, mixed>',
    'untrusted_certificates_file=' => 'null|string',
  ),
  'openssl_x509_export' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    '&output' => 'mixed',
    'no_text=' => 'bool',
  ),
  'openssl_x509_export_to_file' => 
  array (
    0 => 'bool',
    'certificate' => 'OpenSSLCertificate|string',
    'output_filename' => 'string',
    'no_text=' => 'bool',
  ),
  'openssl_x509_fingerprint' => 
  array (
    0 => 'false|string',
    'certificate' => 'OpenSSLCertificate|string',
    'digest_algo=' => 'string',
    'binary=' => 'bool',
  ),
  'openssl_x509_free' => 
  array (
    0 => 'void',
    'certificate' => 'OpenSSLCertificate',
  ),
  'openssl_x509_parse' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'certificate' => 'OpenSSLCertificate|string',
    'short_names=' => 'bool',
  ),
  'openssl_x509_read' => 
  array (
    0 => 'OpenSSLCertificate|false',
    'certificate' => 'OpenSSLCertificate|string',
  ),
  'openssl_x509_verify' => 
  array (
    0 => 'int',
    'certificate' => 'OpenSSLCertificate|string',
    'public_key' => 'mixed',
  ),
  'ord' => 
  array (
    0 => 'int',
    'character' => 'string',
  ),
  'outofboundsexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'outofboundsexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::__wakeup' => 
  array (
    0 => 'void',
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
  'outofboundsexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'outofboundsexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'outofboundsexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'outofboundsexception::gettraceasstring' => 
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
  'outofrangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::__wakeup' => 
  array (
    0 => 'void',
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
  'outofrangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'outofrangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'outofrangeexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'outofrangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'output_add_rewrite_var' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value' => 'string',
  ),
  'output_reset_rewrite_vars' => 
  array (
    0 => 'bool',
  ),
  'overflowexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'overflowexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'overflowexception::__wakeup' => 
  array (
    0 => 'void',
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
  'overflowexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'overflowexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'overflowexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'overflowexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'override::__construct' => 
  array (
    0 => 'void',
  ),
  'pack' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...values=' => 'mixed',
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
  'parentiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'parentiterator::getchildren' => 
  array (
    0 => 'RecursiveFilterIterator|null',
  ),
  'parentiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'parentiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'parentiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'parentiterator::next' => 
  array (
    0 => 'void',
  ),
  'parentiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'parentiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'parse_ini_file' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
    'process_sections=' => 'bool',
    'scanner_mode=' => 'int',
  ),
  'parse_ini_string' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'ini_string' => 'string',
    'process_sections=' => 'bool',
    'scanner_mode=' => 'int',
  ),
  'parse_str' => 
  array (
    0 => 'void',
    'string' => 'string',
    '&result' => 'mixed',
  ),
  'parse_url' => 
  array (
    0 => 'array<array-key, mixed>|false|int|null|string',
    'url' => 'string',
    'component=' => 'int',
  ),
  'parseerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'parseerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'parseerror::__wakeup' => 
  array (
    0 => 'void',
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
  'parseerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'parseerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'parseerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'parseerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'passthru' => 
  array (
    0 => 'false|null',
    'command' => 'string',
    '&result_code=' => 'mixed',
  ),
  'password_algos' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'password_get_info' => 
  array (
    0 => 'array<array-key, mixed>',
    'hash' => 'string',
  ),
  'password_hash' => 
  array (
    0 => 'string',
    'password' => 'string',
    'algo' => 'int|null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'password_needs_rehash' => 
  array (
    0 => 'bool',
    'hash' => 'string',
    'algo' => 'int|null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'password_verify' => 
  array (
    0 => 'bool',
    'password' => 'string',
    'hash' => 'string',
  ),
  'pathinfo' => 
  array (
    0 => 'array<array-key, mixed>|string',
    'path' => 'string',
    'flags=' => 'int',
  ),
  'pclose' => 
  array (
    0 => 'int',
    'handle' => 'mixed',
  ),
  'pcntl\\qosclass::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pcntl_alarm' => 
  array (
    0 => 'int',
    'seconds' => 'int',
  ),
  'pcntl_async_signals' => 
  array (
    0 => 'bool',
    'enable=' => 'bool|null',
  ),
  'pcntl_errno' => 
  array (
    0 => 'int',
  ),
  'pcntl_exec' => 
  array (
    0 => 'false',
    'path' => 'string',
    'args=' => 'array<array-key, mixed>',
    'env_vars=' => 'array<array-key, mixed>',
  ),
  'pcntl_fork' => 
  array (
    0 => 'int',
  ),
  'pcntl_get_last_error' => 
  array (
    0 => 'int',
  ),
  'pcntl_getcpuaffinity' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'process_id=' => 'int|null',
  ),
  'pcntl_getpriority' => 
  array (
    0 => 'false|int',
    'process_id=' => 'int|null',
    'mode=' => 'int',
  ),
  'pcntl_setcpuaffinity' => 
  array (
    0 => 'bool',
    'process_id=' => 'int|null',
    'cpu_ids=' => 'array<array-key, mixed>',
  ),
  'pcntl_setpriority' => 
  array (
    0 => 'bool',
    'priority' => 'int',
    'process_id=' => 'int|null',
    'mode=' => 'int',
  ),
  'pcntl_signal' => 
  array (
    0 => 'bool',
    'signal' => 'int',
    'handler' => 'mixed',
    'restart_syscalls=' => 'bool',
  ),
  'pcntl_signal_dispatch' => 
  array (
    0 => 'bool',
  ),
  'pcntl_signal_get_handler' => 
  array (
    0 => 'mixed',
    'signal' => 'int',
  ),
  'pcntl_sigprocmask' => 
  array (
    0 => 'bool',
    'mode' => 'int',
    'signals' => 'array<array-key, mixed>',
    '&old_signals=' => 'mixed',
  ),
  'pcntl_sigtimedwait' => 
  array (
    0 => 'false|int',
    'signals' => 'array<array-key, mixed>',
    '&info=' => 'mixed',
    'seconds=' => 'int',
    'nanoseconds=' => 'int',
  ),
  'pcntl_sigwaitinfo' => 
  array (
    0 => 'false|int',
    'signals' => 'array<array-key, mixed>',
    '&info=' => 'mixed',
  ),
  'pcntl_strerror' => 
  array (
    0 => 'string',
    'error_code' => 'int',
  ),
  'pcntl_unshare' => 
  array (
    0 => 'bool',
    'flags' => 'int',
  ),
  'pcntl_wait' => 
  array (
    0 => 'int',
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
  'pcntl_waitpid' => 
  array (
    0 => 'int',
    'process_id' => 'int',
    '&status' => 'mixed',
    'flags=' => 'int',
    '&resource_usage=' => 'mixed',
  ),
  'pcntl_wexitstatus' => 
  array (
    0 => 'false|int',
    'status' => 'int',
  ),
  'pcntl_wifcontinued' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifexited' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifsignaled' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wifstopped' => 
  array (
    0 => 'bool',
    'status' => 'int',
  ),
  'pcntl_wstopsig' => 
  array (
    0 => 'false|int',
    'status' => 'int',
  ),
  'pcntl_wtermsig' => 
  array (
    0 => 'false|int',
    'status' => 'int',
  ),
  'pdo::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'pdo::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'pdo::errorcode' => 
  array (
    0 => 'null|string',
  ),
  'pdo::errorinfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo::exec' => 
  array (
    0 => 'false|int',
    'statement' => 'string',
  ),
  'pdo::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo::getavailabledrivers' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo::lastinsertid' => 
  array (
    0 => 'false|string',
    'name=' => 'null|string',
  ),
  'pdo::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array<array-key, mixed>',
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
    0 => 'false|string',
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
  'pdo\\mysql::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'pdo\\mysql::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'pdo\\mysql::errorcode' => 
  array (
    0 => 'null|string',
  ),
  'pdo\\mysql::errorinfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo\\mysql::exec' => 
  array (
    0 => 'false|int',
    'statement' => 'string',
  ),
  'pdo\\mysql::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo\\mysql::getavailabledrivers' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo\\mysql::getwarningcount' => 
  array (
    0 => 'int',
  ),
  'pdo\\mysql::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\mysql::lastinsertid' => 
  array (
    0 => 'false|string',
    'name=' => 'null|string',
  ),
  'pdo\\mysql::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array<array-key, mixed>',
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
    0 => 'false|string',
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
  'pdo\\sqlite::__construct' => 
  array (
    0 => 'void',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'pdo\\sqlite::begintransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::commit' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::connect' => 
  array (
    0 => 'static',
    'dsn' => 'string',
    'username=' => 'null|string',
    'password=' => 'null|string',
    'options=' => 'array<array-key, mixed>|null',
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
  'pdo\\sqlite::errorcode' => 
  array (
    0 => 'null|string',
  ),
  'pdo\\sqlite::errorinfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo\\sqlite::exec' => 
  array (
    0 => 'false|int',
    'statement' => 'string',
  ),
  'pdo\\sqlite::getattribute' => 
  array (
    0 => 'mixed',
    'attribute' => 'int',
  ),
  'pdo\\sqlite::getavailabledrivers' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdo\\sqlite::intransaction' => 
  array (
    0 => 'bool',
  ),
  'pdo\\sqlite::lastinsertid' => 
  array (
    0 => 'false|string',
    'name=' => 'null|string',
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
    'dbname=' => 'null|string',
    'flags=' => 'int',
  ),
  'pdo\\sqlite::prepare' => 
  array (
    0 => 'PDOStatement|false',
    'query' => 'string',
    'options=' => 'array<array-key, mixed>',
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
    0 => 'false|string',
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
  'pdo\\sqlite::setauthorizer' => 
  array (
    0 => 'void',
    'callback' => 'callable|null',
  ),
  'pdo_drivers' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdoexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'pdoexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'pdoexception::__wakeup' => 
  array (
    0 => 'void',
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
  'pdoexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'pdoexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'pdoexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdoexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'pdostatement::bindcolumn' => 
  array (
    0 => 'bool',
    'column' => 'int|string',
    '&var' => 'mixed',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed',
  ),
  'pdostatement::bindparam' => 
  array (
    0 => 'bool',
    'param' => 'int|string',
    '&var' => 'mixed',
    'type=' => 'int',
    'maxLength=' => 'int',
    'driverOptions=' => 'mixed',
  ),
  'pdostatement::bindvalue' => 
  array (
    0 => 'bool',
    'param' => 'int|string',
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
    0 => 'null|string',
  ),
  'pdostatement::errorinfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pdostatement::execute' => 
  array (
    0 => 'bool',
    'params=' => 'array<array-key, mixed>|null',
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
    0 => 'array<array-key, mixed>',
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
    0 => 'false|object',
    'class=' => 'null|string',
    'constructorArgs=' => 'array<array-key, mixed>',
  ),
  'pdostatement::getattribute' => 
  array (
    0 => 'mixed',
    'name' => 'int',
  ),
  'pdostatement::getcolumnmeta' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'column' => 'int',
  ),
  'pdostatement::getiterator' => 
  array (
    0 => 'Iterator',
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
  'pfsockopen' => 
  array (
    0 => 'mixed',
    'hostname' => 'string',
    'port=' => 'int',
    '&error_code=' => 'mixed',
    '&error_message=' => 'mixed',
    'timeout=' => 'float|null',
  ),
  'pg_affected_rows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_cancel_query' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_change_password' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'user' => 'string',
    'password' => 'string',
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
  'pg_close' => 
  array (
    0 => 'true',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_close_stmt' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'Pgsql\\Connection',
    'statement_name' => 'string',
  ),
  'pg_cmdtuples' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_connect' => 
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
  'pg_connection_busy' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_connection_reset' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_connection_status' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_consume_input' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_convert' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'pg_copy_from' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'rows' => 'Traversable|array<array-key, mixed>',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_copy_to' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'separator=' => 'string',
    'null_as=' => 'string',
  ),
  'pg_dbname' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_delete' => 
  array (
    0 => 'bool|string',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'conditions' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'pg_end_copy' => 
  array (
    0 => 'bool',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_errormessage' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_escape_bytea' => 
  array (
    0 => 'string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_escape_identifier' => 
  array (
    0 => 'false|string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_escape_literal' => 
  array (
    0 => 'false|string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_escape_string' => 
  array (
    0 => 'string',
    'connection' => 'mixed',
    'string=' => 'string',
  ),
  'pg_exec' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'query=' => 'string',
  ),
  'pg_execute' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'statement_name' => 'mixed',
    'params=' => 'array<array-key, mixed>',
  ),
  'pg_fetch_all' => 
  array (
    0 => 'array<array-key, mixed>',
    'result' => 'PgSql\\Result',
    'mode=' => 'int',
  ),
  'pg_fetch_all_columns' => 
  array (
    0 => 'array<array-key, mixed>',
    'result' => 'PgSql\\Result',
    'field=' => 'int',
  ),
  'pg_fetch_array' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_fetch_assoc' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
  ),
  'pg_fetch_object' => 
  array (
    0 => 'false|object',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'class=' => 'string',
    'constructor_args=' => 'array<array-key, mixed>',
  ),
  'pg_fetch_result' => 
  array (
    0 => 'false|null|string',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_fetch_row' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'result' => 'PgSql\\Result',
    'row=' => 'int|null',
    'mode=' => 'int',
  ),
  'pg_field_is_null' => 
  array (
    0 => 'false|int',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_field_name' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_num' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'string',
  ),
  'pg_field_prtlen' => 
  array (
    0 => 'false|int',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_field_size' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_table' => 
  array (
    0 => 'false|int|string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
    'oid_only=' => 'bool',
  ),
  'pg_field_type' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_field_type_oid' => 
  array (
    0 => 'int|string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldisnull' => 
  array (
    0 => 'false|int',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_fieldname' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldnum' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'string',
  ),
  'pg_fieldprtlen' => 
  array (
    0 => 'false|int',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_fieldsize' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_fieldtype' => 
  array (
    0 => 'string',
    'result' => 'PgSql\\Result',
    'field' => 'int',
  ),
  'pg_flush' => 
  array (
    0 => 'bool|int',
    'connection' => 'PgSql\\Connection',
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
  'pg_get_notify' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'connection' => 'PgSql\\Connection',
    'mode=' => 'int',
  ),
  'pg_get_pid' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_get_result' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_getlastoid' => 
  array (
    0 => 'false|int|string',
    'result' => 'PgSql\\Result',
  ),
  'pg_host' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_insert' => 
  array (
    0 => 'PgSql\\Result|bool|string',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'pg_jit' => 
  array (
    0 => 'array<array-key, mixed>',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_last_error' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_last_notice' => 
  array (
    0 => 'array<array-key, mixed>|bool|string',
    'connection' => 'PgSql\\Connection',
    'mode=' => 'int',
  ),
  'pg_last_oid' => 
  array (
    0 => 'false|int|string',
    'result' => 'PgSql\\Result',
  ),
  'pg_lo_close' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_lo_create' => 
  array (
    0 => 'false|int|string',
    'connection=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_export' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'filename=' => 'mixed',
  ),
  'pg_lo_import' => 
  array (
    0 => 'false|int|string',
    'connection' => 'mixed',
    'filename=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_open' => 
  array (
    0 => 'PgSql\\Lob|false',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'mode=' => 'string',
  ),
  'pg_lo_read' => 
  array (
    0 => 'false|string',
    'lob' => 'PgSql\\Lob',
    'length=' => 'int',
  ),
  'pg_lo_read_all' => 
  array (
    0 => 'int',
    'lob' => 'PgSql\\Lob',
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
  'pg_lo_unlink' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lo_write' => 
  array (
    0 => 'false|int',
    'lob' => 'PgSql\\Lob',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_loclose' => 
  array (
    0 => 'bool',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_locreate' => 
  array (
    0 => 'false|int|string',
    'connection=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_loexport' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'filename=' => 'mixed',
  ),
  'pg_loimport' => 
  array (
    0 => 'false|int|string',
    'connection' => 'mixed',
    'filename=' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_loopen' => 
  array (
    0 => 'PgSql\\Lob|false',
    'connection' => 'mixed',
    'oid=' => 'mixed',
    'mode=' => 'string',
  ),
  'pg_loread' => 
  array (
    0 => 'false|string',
    'lob' => 'PgSql\\Lob',
    'length=' => 'int',
  ),
  'pg_loreadall' => 
  array (
    0 => 'int',
    'lob' => 'PgSql\\Lob',
  ),
  'pg_lounlink' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'oid=' => 'mixed',
  ),
  'pg_lowrite' => 
  array (
    0 => 'false|int',
    'lob' => 'PgSql\\Lob',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'pg_meta_data' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'extended=' => 'bool',
  ),
  'pg_num_fields' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_num_rows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_numfields' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_numrows' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_options' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_parameter_status' => 
  array (
    0 => 'false|string',
    'connection' => 'mixed',
    'name=' => 'string',
  ),
  'pg_pconnect' => 
  array (
    0 => 'PgSql\\Connection|false',
    'connection_string' => 'string',
    'flags=' => 'int',
  ),
  'pg_ping' => 
  array (
    0 => 'bool',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_port' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_prepare' => 
  array (
    0 => 'PgSql\\Result|false',
    'connection' => 'mixed',
    'statement_name' => 'string',
    'query=' => 'string',
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
    'error=' => 'null|string',
  ),
  'pg_put_line' => 
  array (
    0 => 'bool',
    'connection' => 'mixed',
    'query=' => 'string',
  ),
  'pg_query' => 
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
    'params=' => 'array<array-key, mixed>',
  ),
  'pg_result' => 
  array (
    0 => 'false|null|string',
    'result' => 'PgSql\\Result',
    'row' => 'mixed',
    'field=' => 'int|string',
  ),
  'pg_result_error' => 
  array (
    0 => 'false|string',
    'result' => 'PgSql\\Result',
  ),
  'pg_result_error_field' => 
  array (
    0 => 'false|null|string',
    'result' => 'PgSql\\Result',
    'field_code' => 'int',
  ),
  'pg_result_memory_size' => 
  array (
    0 => 'int',
    'result' => 'PgSql\\Result',
  ),
  'pg_result_seek' => 
  array (
    0 => 'bool',
    'result' => 'PgSql\\Result',
    'row' => 'int',
  ),
  'pg_result_status' => 
  array (
    0 => 'int|string',
    'result' => 'PgSql\\Result',
    'mode=' => 'int',
  ),
  'pg_select' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'conditions=' => 'array<array-key, mixed>',
    'flags=' => 'int',
    'mode=' => 'int',
  ),
  'pg_send_execute' => 
  array (
    0 => 'bool|int',
    'connection' => 'PgSql\\Connection',
    'statement_name' => 'string',
    'params' => 'array<array-key, mixed>',
  ),
  'pg_send_prepare' => 
  array (
    0 => 'bool|int',
    'connection' => 'PgSql\\Connection',
    'statement_name' => 'string',
    'query' => 'string',
  ),
  'pg_send_query' => 
  array (
    0 => 'bool|int',
    'connection' => 'PgSql\\Connection',
    'query' => 'string',
  ),
  'pg_send_query_params' => 
  array (
    0 => 'bool|int',
    'connection' => 'PgSql\\Connection',
    'query' => 'string',
    'params' => 'array<array-key, mixed>',
  ),
  'pg_set_chunked_rows_size' => 
  array (
    0 => 'bool',
    'connection' => 'PgSql\\Connection',
    'size' => 'int',
  ),
  'pg_set_client_encoding' => 
  array (
    0 => 'int',
    'connection' => 'mixed',
    'encoding=' => 'string',
  ),
  'pg_set_error_context_visibility' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
    'visibility' => 'int',
  ),
  'pg_set_error_verbosity' => 
  array (
    0 => 'false|int',
    'connection' => 'mixed',
    'verbosity=' => 'int',
  ),
  'pg_setclientencoding' => 
  array (
    0 => 'int',
    'connection' => 'mixed',
    'encoding=' => 'string',
  ),
  'pg_socket' => 
  array (
    0 => 'mixed',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_socket_poll' => 
  array (
    0 => 'int',
    'socket' => 'mixed',
    'read' => 'int',
    'write' => 'int',
    'timeout=' => 'int',
  ),
  'pg_trace' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'mode=' => 'string',
    'connection=' => 'PgSql\\Connection|null',
    'trace_mode=' => 'int',
  ),
  'pg_transaction_status' => 
  array (
    0 => 'int',
    'connection' => 'PgSql\\Connection',
  ),
  'pg_tty' => 
  array (
    0 => 'string',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_unescape_bytea' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'pg_untrace' => 
  array (
    0 => 'true',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'pg_update' => 
  array (
    0 => 'bool|string',
    'connection' => 'PgSql\\Connection',
    'table_name' => 'string',
    'values' => 'array<array-key, mixed>',
    'conditions' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'pg_version' => 
  array (
    0 => 'array<array-key, mixed>',
    'connection=' => 'PgSql\\Connection|null',
  ),
  'phar::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'null|string',
  ),
  'phar::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phar::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'phar::__tostring' => 
  array (
    0 => 'string',
  ),
  'phar::_bad_state_ex' => 
  array (
    0 => 'void',
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
    'localName=' => 'null|string',
  ),
  'phar::addfromstring' => 
  array (
    0 => 'void',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'phar::apiversion' => 
  array (
    0 => 'string',
  ),
  'phar::buildfromdirectory' => 
  array (
    0 => 'array<array-key, mixed>',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'phar::buildfromiterator' => 
  array (
    0 => 'array<array-key, mixed>',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'null|string',
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
  'phar::compress' => 
  array (
    0 => 'Phar|null',
    'compression' => 'int',
    'extension=' => 'null|string',
  ),
  'phar::compressfiles' => 
  array (
    0 => 'void',
    'compression' => 'int',
  ),
  'phar::converttodata' => 
  array (
    0 => 'PharData|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'null|string',
  ),
  'phar::converttoexecutable' => 
  array (
    0 => 'Phar|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'null|string',
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
  'phar::createdefaultstub' => 
  array (
    0 => 'string',
    'index=' => 'null|string',
    'webIndex=' => 'null|string',
  ),
  'phar::current' => 
  array (
    0 => 'FilesystemIterator|SplFileInfo|string',
  ),
  'phar::decompress' => 
  array (
    0 => 'Phar|null',
    'extension=' => 'null|string',
  ),
  'phar::decompressfiles' => 
  array (
    0 => 'true',
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
    'files=' => 'array<array-key, mixed>|null|string',
    'overwrite=' => 'bool',
  ),
  'phar::getalias' => 
  array (
    0 => 'null|string',
  ),
  'phar::getatime' => 
  array (
    0 => 'false|int',
  ),
  'phar::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'phar::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'phar::getctime' => 
  array (
    0 => 'false|int',
  ),
  'phar::getextension' => 
  array (
    0 => 'string',
  ),
  'phar::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'phar::getfilename' => 
  array (
    0 => 'string',
  ),
  'phar::getflags' => 
  array (
    0 => 'int',
  ),
  'phar::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'phar::getinode' => 
  array (
    0 => 'false|int',
  ),
  'phar::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'phar::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array<array-key, mixed>',
  ),
  'phar::getmodified' => 
  array (
    0 => 'bool',
  ),
  'phar::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'phar::getowner' => 
  array (
    0 => 'false|int',
  ),
  'phar::getpath' => 
  array (
    0 => 'string',
  ),
  'phar::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'phar::getpathname' => 
  array (
    0 => 'string',
  ),
  'phar::getperms' => 
  array (
    0 => 'false|int',
  ),
  'phar::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'phar::getsignature' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'phar::getsize' => 
  array (
    0 => 'false|int',
  ),
  'phar::getstub' => 
  array (
    0 => 'string',
  ),
  'phar::getsubpath' => 
  array (
    0 => 'string',
  ),
  'phar::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'phar::getsupportedcompression' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phar::getsupportedsignatures' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phar::gettype' => 
  array (
    0 => 'false|string',
  ),
  'phar::getversion' => 
  array (
    0 => 'string',
  ),
  'phar::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'phar::hasmetadata' => 
  array (
    0 => 'bool',
  ),
  'phar::interceptfilefuncs' => 
  array (
    0 => 'void',
  ),
  'phar::isbuffering' => 
  array (
    0 => 'bool',
  ),
  'phar::iscompressed' => 
  array (
    0 => 'false|int',
  ),
  'phar::isdir' => 
  array (
    0 => 'bool',
  ),
  'phar::isdot' => 
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
  'phar::isfileformat' => 
  array (
    0 => 'bool',
    'format' => 'int',
  ),
  'phar::islink' => 
  array (
    0 => 'bool',
  ),
  'phar::isreadable' => 
  array (
    0 => 'bool',
  ),
  'phar::isvalidpharfilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'phar::iswritable' => 
  array (
    0 => 'bool',
  ),
  'phar::key' => 
  array (
    0 => 'string',
  ),
  'phar::loadphar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'null|string',
  ),
  'phar::mapphar' => 
  array (
    0 => 'bool',
    'alias=' => 'null|string',
    'offset=' => 'int',
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
    'variables' => 'array<array-key, mixed>',
  ),
  'phar::next' => 
  array (
    0 => 'void',
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
  'phar::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'phar::rewind' => 
  array (
    0 => 'void',
  ),
  'phar::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'phar::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'phar::setalias' => 
  array (
    0 => 'true',
    'alias' => 'string',
  ),
  'phar::setdefaultstub' => 
  array (
    0 => 'true',
    'index=' => 'null|string',
    'webIndex=' => 'null|string',
  ),
  'phar::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phar::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'phar::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
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
    'privateKey=' => 'null|string',
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
  'phar::unlinkarchive' => 
  array (
    0 => 'true',
    'filename' => 'string',
  ),
  'phar::valid' => 
  array (
    0 => 'bool',
  ),
  'phar::webphar' => 
  array (
    0 => 'void',
    'alias=' => 'null|string',
    'index=' => 'null|string',
    'fileNotFoundScript=' => 'null|string',
    'mimeTypes=' => 'array<array-key, mixed>',
    'rewrite=' => 'callable|null',
  ),
  'phardata::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'alias=' => 'null|string',
    'format=' => 'int',
  ),
  'phardata::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phardata::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'phardata::__tostring' => 
  array (
    0 => 'string',
  ),
  'phardata::_bad_state_ex' => 
  array (
    0 => 'void',
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
    'localName=' => 'null|string',
  ),
  'phardata::addfromstring' => 
  array (
    0 => 'void',
    'localName' => 'string',
    'contents' => 'string',
  ),
  'phardata::apiversion' => 
  array (
    0 => 'string',
  ),
  'phardata::buildfromdirectory' => 
  array (
    0 => 'array<array-key, mixed>',
    'directory' => 'string',
    'pattern=' => 'string',
  ),
  'phardata::buildfromiterator' => 
  array (
    0 => 'array<array-key, mixed>',
    'iterator' => 'Traversable',
    'baseDirectory=' => 'null|string',
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
  'phardata::compress' => 
  array (
    0 => 'PharData|null',
    'compression' => 'int',
    'extension=' => 'null|string',
  ),
  'phardata::compressfiles' => 
  array (
    0 => 'void',
    'compression' => 'int',
  ),
  'phardata::converttodata' => 
  array (
    0 => 'PharData|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'null|string',
  ),
  'phardata::converttoexecutable' => 
  array (
    0 => 'Phar|null',
    'format=' => 'int|null',
    'compression=' => 'int|null',
    'extension=' => 'null|string',
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
  'phardata::createdefaultstub' => 
  array (
    0 => 'string',
    'index=' => 'null|string',
    'webIndex=' => 'null|string',
  ),
  'phardata::current' => 
  array (
    0 => 'FilesystemIterator|SplFileInfo|string',
  ),
  'phardata::decompress' => 
  array (
    0 => 'PharData|null',
    'extension=' => 'null|string',
  ),
  'phardata::decompressfiles' => 
  array (
    0 => 'true',
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
    'files=' => 'array<array-key, mixed>|null|string',
    'overwrite=' => 'bool',
  ),
  'phardata::getalias' => 
  array (
    0 => 'null|string',
  ),
  'phardata::getatime' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'phardata::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'phardata::getctime' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getextension' => 
  array (
    0 => 'string',
  ),
  'phardata::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'phardata::getfilename' => 
  array (
    0 => 'string',
  ),
  'phardata::getflags' => 
  array (
    0 => 'int',
  ),
  'phardata::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getinode' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'phardata::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array<array-key, mixed>',
  ),
  'phardata::getmodified' => 
  array (
    0 => 'bool',
  ),
  'phardata::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getowner' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getpath' => 
  array (
    0 => 'string',
  ),
  'phardata::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'phardata::getpathname' => 
  array (
    0 => 'string',
  ),
  'phardata::getperms' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'phardata::getsignature' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'phardata::getsize' => 
  array (
    0 => 'false|int',
  ),
  'phardata::getstub' => 
  array (
    0 => 'string',
  ),
  'phardata::getsubpath' => 
  array (
    0 => 'string',
  ),
  'phardata::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'phardata::getsupportedcompression' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phardata::getsupportedsignatures' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'phardata::gettype' => 
  array (
    0 => 'false|string',
  ),
  'phardata::getversion' => 
  array (
    0 => 'string',
  ),
  'phardata::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'phardata::hasmetadata' => 
  array (
    0 => 'bool',
  ),
  'phardata::interceptfilefuncs' => 
  array (
    0 => 'void',
  ),
  'phardata::isbuffering' => 
  array (
    0 => 'bool',
  ),
  'phardata::iscompressed' => 
  array (
    0 => 'false|int',
  ),
  'phardata::isdir' => 
  array (
    0 => 'bool',
  ),
  'phardata::isdot' => 
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
  'phardata::isfileformat' => 
  array (
    0 => 'bool',
    'format' => 'int',
  ),
  'phardata::islink' => 
  array (
    0 => 'bool',
  ),
  'phardata::isreadable' => 
  array (
    0 => 'bool',
  ),
  'phardata::isvalidpharfilename' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'executable=' => 'bool',
  ),
  'phardata::iswritable' => 
  array (
    0 => 'bool',
  ),
  'phardata::key' => 
  array (
    0 => 'string',
  ),
  'phardata::loadphar' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'alias=' => 'null|string',
  ),
  'phardata::mapphar' => 
  array (
    0 => 'bool',
    'alias=' => 'null|string',
    'offset=' => 'int',
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
    'variables' => 'array<array-key, mixed>',
  ),
  'phardata::next' => 
  array (
    0 => 'void',
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
  'phardata::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'phardata::rewind' => 
  array (
    0 => 'void',
  ),
  'phardata::running' => 
  array (
    0 => 'string',
    'returnPhar=' => 'bool',
  ),
  'phardata::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'phardata::setalias' => 
  array (
    0 => 'bool',
    'alias' => 'string',
  ),
  'phardata::setdefaultstub' => 
  array (
    0 => 'bool',
    'index=' => 'null|string',
    'webIndex=' => 'null|string',
  ),
  'phardata::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'phardata::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'phardata::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
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
    'privateKey=' => 'null|string',
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
  'phardata::unlinkarchive' => 
  array (
    0 => 'true',
    'filename' => 'string',
  ),
  'phardata::valid' => 
  array (
    0 => 'bool',
  ),
  'phardata::webphar' => 
  array (
    0 => 'void',
    'alias=' => 'null|string',
    'index=' => 'null|string',
    'fileNotFoundScript=' => 'null|string',
    'mimeTypes=' => 'array<array-key, mixed>',
    'rewrite=' => 'callable|null',
  ),
  'pharexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'pharexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'pharexception::__wakeup' => 
  array (
    0 => 'void',
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
  'pharexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'pharexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'pharexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pharexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
  ),
  'pharfileinfo::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'pharfileinfo::__destruct' => 
  array (
    0 => 'mixed',
  ),
  'pharfileinfo::__tostring' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::_bad_state_ex' => 
  array (
    0 => 'void',
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
  'pharfileinfo::getatime' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'pharfileinfo::getcompressedsize' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::getcontent' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getcrc32' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::getctime' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getextension' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'pharfileinfo::getfilename' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getinode' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'pharfileinfo::getmetadata' => 
  array (
    0 => 'mixed',
    'unserializeOptions=' => 'array<array-key, mixed>',
  ),
  'pharfileinfo::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getowner' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getpath' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'pharfileinfo::getpathname' => 
  array (
    0 => 'string',
  ),
  'pharfileinfo::getperms' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::getpharflags' => 
  array (
    0 => 'int',
  ),
  'pharfileinfo::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'pharfileinfo::getsize' => 
  array (
    0 => 'false|int',
  ),
  'pharfileinfo::gettype' => 
  array (
    0 => 'false|string',
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
  'pharfileinfo::isdir' => 
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
  'pharfileinfo::islink' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::isreadable' => 
  array (
    0 => 'bool',
  ),
  'pharfileinfo::iswritable' => 
  array (
    0 => 'bool',
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
  'pharfileinfo::setmetadata' => 
  array (
    0 => 'void',
    'metadata' => 'mixed',
  ),
  'php_ini_loaded_file' => 
  array (
    0 => 'false|string',
  ),
  'php_ini_scanned_files' => 
  array (
    0 => 'false|string',
  ),
  'php_sapi_name' => 
  array (
    0 => 'false|string',
  ),
  'php_strip_whitespace' => 
  array (
    0 => 'string',
    'filename' => 'string',
  ),
  'php_uname' => 
  array (
    0 => 'string',
    'mode=' => 'string',
  ),
  'php_user_filter::filter' => 
  array (
    0 => 'int',
    'in' => 'mixed',
    'out' => 'mixed',
    '&consumed' => 'mixed',
    'closing' => 'bool',
  ),
  'php_user_filter::onclose' => 
  array (
    0 => 'void',
  ),
  'php_user_filter::oncreate' => 
  array (
    0 => 'bool',
  ),
  'phpcredits' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'phpinfo' => 
  array (
    0 => 'true',
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
  'phptoken::__tostring' => 
  array (
    0 => 'string',
  ),
  'phptoken::gettokenname' => 
  array (
    0 => 'null|string',
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
  'phptoken::tokenize' => 
  array (
    0 => 'array<array-key, mixed>',
    'code' => 'string',
    'flags=' => 'int',
  ),
  'phpversion' => 
  array (
    0 => 'false|string',
    'extension=' => 'null|string',
  ),
  'pi' => 
  array (
    0 => 'float',
  ),
  'popen' => 
  array (
    0 => 'mixed',
    'command' => 'string',
    'mode' => 'string',
  ),
  'pos' => 
  array (
    0 => 'mixed',
    'array' => 'array<array-key, mixed>|object',
  ),
  'posix_access' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'posix_ctermid' => 
  array (
    0 => 'false|string',
  ),
  'posix_eaccess' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'posix_errno' => 
  array (
    0 => 'int',
  ),
  'posix_get_last_error' => 
  array (
    0 => 'int',
  ),
  'posix_getcwd' => 
  array (
    0 => 'false|string',
  ),
  'posix_getegid' => 
  array (
    0 => 'int',
  ),
  'posix_geteuid' => 
  array (
    0 => 'int',
  ),
  'posix_getgid' => 
  array (
    0 => 'int',
  ),
  'posix_getgrgid' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'group_id' => 'int',
  ),
  'posix_getgrnam' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'name' => 'string',
  ),
  'posix_getgroups' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'posix_getlogin' => 
  array (
    0 => 'false|string',
  ),
  'posix_getpgid' => 
  array (
    0 => 'false|int',
    'process_id' => 'int',
  ),
  'posix_getpgrp' => 
  array (
    0 => 'int',
  ),
  'posix_getpid' => 
  array (
    0 => 'int',
  ),
  'posix_getppid' => 
  array (
    0 => 'int',
  ),
  'posix_getpwnam' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'username' => 'string',
  ),
  'posix_getpwuid' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'user_id' => 'int',
  ),
  'posix_getrlimit' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'resource=' => 'int|null',
  ),
  'posix_getsid' => 
  array (
    0 => 'false|int',
    'process_id' => 'int',
  ),
  'posix_getuid' => 
  array (
    0 => 'int',
  ),
  'posix_initgroups' => 
  array (
    0 => 'bool',
    'username' => 'string',
    'group_id' => 'int',
  ),
  'posix_isatty' => 
  array (
    0 => 'bool',
    'file_descriptor' => 'mixed',
  ),
  'posix_kill' => 
  array (
    0 => 'bool',
    'process_id' => 'int',
    'signal' => 'int',
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
  'posix_setegid' => 
  array (
    0 => 'bool',
    'group_id' => 'int',
  ),
  'posix_seteuid' => 
  array (
    0 => 'bool',
    'user_id' => 'int',
  ),
  'posix_setgid' => 
  array (
    0 => 'bool',
    'group_id' => 'int',
  ),
  'posix_setpgid' => 
  array (
    0 => 'bool',
    'process_id' => 'int',
    'process_group_id' => 'int',
  ),
  'posix_setrlimit' => 
  array (
    0 => 'bool',
    'resource' => 'int',
    'soft_limit' => 'int',
    'hard_limit' => 'int',
  ),
  'posix_setsid' => 
  array (
    0 => 'int',
  ),
  'posix_setuid' => 
  array (
    0 => 'bool',
    'user_id' => 'int',
  ),
  'posix_strerror' => 
  array (
    0 => 'string',
    'error_code' => 'int',
  ),
  'posix_sysconf' => 
  array (
    0 => 'int',
    'conf_id' => 'int',
  ),
  'posix_times' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'posix_ttyname' => 
  array (
    0 => 'false|string',
    'file_descriptor' => 'mixed',
  ),
  'posix_uname' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'pow' => 
  array (
    0 => 'float|int|object',
    'num' => 'mixed',
    'exponent' => 'mixed',
  ),
  'preg_filter' => 
  array (
    0 => 'array<array-key, mixed>|null|string',
    'pattern' => 'array<array-key, mixed>|string',
    'replacement' => 'array<array-key, mixed>|string',
    'subject' => 'array<array-key, mixed>|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
  ),
  'preg_grep' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'array' => 'array<array-key, mixed>',
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
  'preg_match' => 
  array (
    0 => 'false|int',
    'pattern' => 'string',
    'subject' => 'string',
    '&matches=' => 'mixed',
    'flags=' => 'int',
    'offset=' => 'int',
  ),
  'preg_match_all' => 
  array (
    0 => 'false|int',
    'pattern' => 'string',
    'subject' => 'string',
    '&matches=' => 'mixed',
    'flags=' => 'int',
    'offset=' => 'int',
  ),
  'preg_quote' => 
  array (
    0 => 'string',
    'str' => 'string',
    'delimiter=' => 'null|string',
  ),
  'preg_replace' => 
  array (
    0 => 'array<array-key, mixed>|null|string',
    'pattern' => 'array<array-key, mixed>|string',
    'replacement' => 'array<array-key, mixed>|string',
    'subject' => 'array<array-key, mixed>|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
  ),
  'preg_replace_callback' => 
  array (
    0 => 'array<array-key, mixed>|null|string',
    'pattern' => 'array<array-key, mixed>|string',
    'callback' => 'callable',
    'subject' => 'array<array-key, mixed>|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
    'flags=' => 'int',
  ),
  'preg_replace_callback_array' => 
  array (
    0 => 'array<array-key, mixed>|null|string',
    'pattern' => 'array<array-key, mixed>',
    'subject' => 'array<array-key, mixed>|string',
    'limit=' => 'int',
    '&count=' => 'mixed',
    'flags=' => 'int',
  ),
  'preg_split' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'subject' => 'string',
    'limit=' => 'int',
    'flags=' => 'int',
  ),
  'prev' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>|object',
  ),
  'print_r' => 
  array (
    0 => 'string|true',
    'value' => 'mixed',
    'return=' => 'bool',
  ),
  'printf' => 
  array (
    0 => 'int',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'proc_close' => 
  array (
    0 => 'int',
    'process' => 'mixed',
  ),
  'proc_get_status' => 
  array (
    0 => 'array<array-key, mixed>',
    'process' => 'mixed',
  ),
  'proc_nice' => 
  array (
    0 => 'bool',
    'priority' => 'int',
  ),
  'proc_open' => 
  array (
    0 => 'mixed',
    'command' => 'array<array-key, mixed>|string',
    'descriptor_spec' => 'array<array-key, mixed>',
    '&pipes' => 'mixed',
    'cwd=' => 'null|string',
    'env_vars=' => 'array<array-key, mixed>|null',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'proc_terminate' => 
  array (
    0 => 'bool',
    'process' => 'mixed',
    'signal=' => 'int',
  ),
  'property_exists' => 
  array (
    0 => 'bool',
    'object_or_class' => 'mixed',
    'property' => 'string',
  ),
  'propertyhooktype::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'propertyhooktype::from' => 
  array (
    0 => 'static',
    'value' => 'int|string',
  ),
  'propertyhooktype::tryfrom' => 
  array (
    0 => 'null|static',
    'value' => 'int|string',
  ),
  'putenv' => 
  array (
    0 => 'bool',
    'assignment' => 'string',
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
  'quotemeta' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'rad2deg' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'rand' => 
  array (
    0 => 'int',
    'min=' => 'int',
    'max=' => 'int',
  ),
  'random\\brokenrandomengineerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'random\\brokenrandomengineerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::__wakeup' => 
  array (
    0 => 'void',
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
  'random\\brokenrandomengineerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\brokenrandomengineerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\brokenrandomengineerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\brokenrandomengineerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\mt19937::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'random\\engine\\mt19937::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\mt19937::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\mt19937::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'random\\engine\\mt19937::generate' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'int|null|string',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\pcgoneseq128xslrr64::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
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
  'random\\engine\\secure::generate' => 
  array (
    0 => 'string',
  ),
  'random\\engine\\xoshiro256starstar::__construct' => 
  array (
    0 => 'void',
    'seed=' => 'int|null|string',
  ),
  'random\\engine\\xoshiro256starstar::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\xoshiro256starstar::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\engine\\xoshiro256starstar::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
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
  'random\\intervalboundary::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\randomerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'random\\randomerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\randomerror::__wakeup' => 
  array (
    0 => 'void',
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
  'random\\randomerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\randomerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\randomerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\randomerror::gettraceasstring' => 
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
  'random\\randomexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::__wakeup' => 
  array (
    0 => 'void',
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
  'random\\randomexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'random\\randomexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'random\\randomexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\randomexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'random\\randomizer::__construct' => 
  array (
    0 => 'void',
    'engine=' => 'Random\\Engine|null',
  ),
  'random\\randomizer::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'random\\randomizer::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
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
  'random\\randomizer::nextfloat' => 
  array (
    0 => 'float',
  ),
  'random\\randomizer::nextint' => 
  array (
    0 => 'int',
  ),
  'random\\randomizer::pickarraykeys' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
    'num' => 'int',
  ),
  'random\\randomizer::shufflearray' => 
  array (
    0 => 'array<array-key, mixed>',
    'array' => 'array<array-key, mixed>',
  ),
  'random\\randomizer::shufflebytes' => 
  array (
    0 => 'string',
    'bytes' => 'string',
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
  'range' => 
  array (
    0 => 'array<array-key, mixed>',
    'start' => 'float|int|string',
    'end' => 'float|int|string',
    'step=' => 'float|int',
  ),
  'rangeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'rangeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'rangeexception::__wakeup' => 
  array (
    0 => 'void',
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
  'rangeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'rangeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'rangeexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'rangeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'rawurldecode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'rawurlencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'readdir' => 
  array (
    0 => 'false|string',
    'dir_handle=' => 'mixed',
  ),
  'readfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'use_include_path=' => 'bool',
    'context=' => 'mixed',
  ),
  'readgzfile' => 
  array (
    0 => 'false|int',
    'filename' => 'string',
    'use_include_path=' => 'bool',
  ),
  'readline' => 
  array (
    0 => 'false|string',
    'prompt=' => 'null|string',
  ),
  'readline_add_history' => 
  array (
    0 => 'true',
    'prompt' => 'string',
  ),
  'readline_callback_handler_install' => 
  array (
    0 => 'true',
    'prompt' => 'string',
    'callback' => 'callable',
  ),
  'readline_callback_handler_remove' => 
  array (
    0 => 'bool',
  ),
  'readline_callback_read_char' => 
  array (
    0 => 'void',
  ),
  'readline_clear_history' => 
  array (
    0 => 'true',
  ),
  'readline_completion_function' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'readline_info' => 
  array (
    0 => 'mixed',
    'var_name=' => 'null|string',
    'value=' => 'mixed',
  ),
  'readline_list_history' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'readline_on_new_line' => 
  array (
    0 => 'void',
  ),
  'readline_read_history' => 
  array (
    0 => 'bool',
    'filename=' => 'null|string',
  ),
  'readline_redisplay' => 
  array (
    0 => 'void',
  ),
  'readline_write_history' => 
  array (
    0 => 'bool',
    'filename=' => 'null|string',
  ),
  'readlink' => 
  array (
    0 => 'false|string',
    'path' => 'string',
  ),
  'realpath' => 
  array (
    0 => 'false|string',
    'path' => 'string',
  ),
  'realpath_cache_get' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'realpath_cache_size' => 
  array (
    0 => 'int',
  ),
  'recursivearrayiterator::__construct' => 
  array (
    0 => 'void',
    'array=' => 'array<array-key, mixed>|object',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'recursivearrayiterator::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'recursivearrayiterator::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'recursivearrayiterator::append' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'recursivearrayiterator::asort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::count' => 
  array (
    0 => 'int',
  ),
  'recursivearrayiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivearrayiterator::getarraycopy' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'recursivearrayiterator::getchildren' => 
  array (
    0 => 'RecursiveArrayIterator|null',
  ),
  'recursivearrayiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivearrayiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivearrayiterator::key' => 
  array (
    0 => 'int|null|string',
  ),
  'recursivearrayiterator::ksort' => 
  array (
    0 => 'true',
    'flags=' => 'int',
  ),
  'recursivearrayiterator::natcasesort' => 
  array (
    0 => 'true',
  ),
  'recursivearrayiterator::natsort' => 
  array (
    0 => 'true',
  ),
  'recursivearrayiterator::next' => 
  array (
    0 => 'void',
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
  'recursivearrayiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivearrayiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'recursivearrayiterator::serialize' => 
  array (
    0 => 'string',
  ),
  'recursivearrayiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
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
  'recursivearrayiterator::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'recursivearrayiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Iterator',
    'flags=' => 'int',
  ),
  'recursivecachingiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'recursivecachingiterator::count' => 
  array (
    0 => 'int',
  ),
  'recursivecachingiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivecachingiterator::getcache' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'recursivecachingiterator::getchildren' => 
  array (
    0 => 'RecursiveCachingIterator|null',
  ),
  'recursivecachingiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivecachingiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivecachingiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::hasnext' => 
  array (
    0 => 'bool',
  ),
  'recursivecachingiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivecachingiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivecachingiterator::offsetexists' => 
  array (
    0 => 'bool',
    'key' => 'mixed',
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
  'recursivecachingiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivecachingiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursivecachingiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
    'callback' => 'callable',
  ),
  'recursivecallbackfilteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivecallbackfilteriterator::getchildren' => 
  array (
    0 => 'RecursiveCallbackFilterIterator',
  ),
  'recursivecallbackfilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivecallbackfilteriterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivecallbackfilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivecallbackfilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivecallbackfilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivecallbackfilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::__construct' => 
  array (
    0 => 'void',
    'directory' => 'string',
    'flags=' => 'int',
  ),
  'recursivedirectoryiterator::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'recursivedirectoryiterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::current' => 
  array (
    0 => 'FilesystemIterator|SplFileInfo|string',
  ),
  'recursivedirectoryiterator::getatime' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'recursivedirectoryiterator::getchildren' => 
  array (
    0 => 'RecursiveDirectoryIterator',
  ),
  'recursivedirectoryiterator::getctime' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getextension' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'recursivedirectoryiterator::getfilename' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursivedirectoryiterator::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getinode' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'recursivedirectoryiterator::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getowner' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getpath' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'recursivedirectoryiterator::getpathname' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getperms' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'recursivedirectoryiterator::getsize' => 
  array (
    0 => 'false|int',
  ),
  'recursivedirectoryiterator::getsubpath' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::getsubpathname' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::gettype' => 
  array (
    0 => 'false|string',
  ),
  'recursivedirectoryiterator::haschildren' => 
  array (
    0 => 'bool',
    'allowLinks=' => 'bool',
  ),
  'recursivedirectoryiterator::isdir' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isdot' => 
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
  'recursivedirectoryiterator::islink' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::isreadable' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::iswritable' => 
  array (
    0 => 'bool',
  ),
  'recursivedirectoryiterator::key' => 
  array (
    0 => 'string',
  ),
  'recursivedirectoryiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'recursivedirectoryiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivedirectoryiterator::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'recursivedirectoryiterator::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'recursivedirectoryiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursivedirectoryiterator::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'recursivedirectoryiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'RecursiveIterator',
  ),
  'recursivefilteriterator::accept' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivefilteriterator::getchildren' => 
  array (
    0 => 'RecursiveFilterIterator|null',
  ),
  'recursivefilteriterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursivefilteriterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivefilteriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivefilteriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivefilteriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivefilteriterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursiveiteratoriterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'Traversable',
    'mode=' => 'int',
    'flags=' => 'int',
  ),
  'recursiveiteratoriterator::beginchildren' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::beginiteration' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::callgetchildren' => 
  array (
    0 => 'RecursiveIterator|null',
  ),
  'recursiveiteratoriterator::callhaschildren' => 
  array (
    0 => 'bool',
  ),
  'recursiveiteratoriterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursiveiteratoriterator::endchildren' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::enditeration' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::getdepth' => 
  array (
    0 => 'int',
  ),
  'recursiveiteratoriterator::getinneriterator' => 
  array (
    0 => 'RecursiveIterator',
  ),
  'recursiveiteratoriterator::getmaxdepth' => 
  array (
    0 => 'false|int',
  ),
  'recursiveiteratoriterator::getsubiterator' => 
  array (
    0 => 'RecursiveIterator|null',
    'level=' => 'int|null',
  ),
  'recursiveiteratoriterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursiveiteratoriterator::next' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::nextelement' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursiveiteratoriterator::setmaxdepth' => 
  array (
    0 => 'void',
    'maxDepth=' => 'int',
  ),
  'recursiveiteratoriterator::valid' => 
  array (
    0 => 'bool',
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
  'recursiveregexiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursiveregexiterator::getchildren' => 
  array (
    0 => 'RecursiveRegexIterator',
  ),
  'recursiveregexiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'recursiveregexiterator::getmode' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::getpregflags' => 
  array (
    0 => 'int',
  ),
  'recursiveregexiterator::getregex' => 
  array (
    0 => 'string',
  ),
  'recursiveregexiterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'recursiveregexiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursiveregexiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursiveregexiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursiveregexiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'recursiveregexiterator::setmode' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'recursiveregexiterator::setpregflags' => 
  array (
    0 => 'void',
    'pregFlags' => 'int',
  ),
  'recursiveregexiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'recursivetreeiterator::__construct' => 
  array (
    0 => 'void',
    'iterator' => 'IteratorAggregate|RecursiveIterator',
    'flags=' => 'int',
    'cachingIteratorFlags=' => 'int',
    'mode=' => 'int',
  ),
  'recursivetreeiterator::beginchildren' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::beginiteration' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::callgetchildren' => 
  array (
    0 => 'RecursiveIterator|null',
  ),
  'recursivetreeiterator::callhaschildren' => 
  array (
    0 => 'bool',
  ),
  'recursivetreeiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'recursivetreeiterator::endchildren' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::enditeration' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::getdepth' => 
  array (
    0 => 'int',
  ),
  'recursivetreeiterator::getentry' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::getinneriterator' => 
  array (
    0 => 'RecursiveIterator',
  ),
  'recursivetreeiterator::getmaxdepth' => 
  array (
    0 => 'false|int',
  ),
  'recursivetreeiterator::getpostfix' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::getprefix' => 
  array (
    0 => 'string',
  ),
  'recursivetreeiterator::getsubiterator' => 
  array (
    0 => 'RecursiveIterator|null',
    'level=' => 'int|null',
  ),
  'recursivetreeiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'recursivetreeiterator::next' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::nextelement' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'recursivetreeiterator::setmaxdepth' => 
  array (
    0 => 'void',
    'maxDepth=' => 'int',
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
  'recursivetreeiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'reflection::getmodifiernames' => 
  array (
    0 => 'array<array-key, mixed>',
    'modifiers' => 'int',
  ),
  'reflectionattribute::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionattribute::__construct' => 
  array (
    0 => 'void',
  ),
  'reflectionattribute::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionattribute::getarguments' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'reflectionattribute::newinstance' => 
  array (
    0 => 'object',
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
  'reflectionclass::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionclass::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionclass::getconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionclass::getdefaultproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionclass::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionclass::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionclass::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionclass::getfilename' => 
  array (
    0 => 'false|string',
  ),
  'reflectionclass::getinterfacenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::getinterfaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionclass::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionclass::getmethods' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionclass::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionclass::getproperties' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionclass::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionclass::getreflectionconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionclass::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionclass::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionclass::getstaticproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionclass::gettraitaliases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::gettraitnames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::gettraits' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionclass::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionclass::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionclass::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionclass::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionclass::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionclass::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionclass::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionclass::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionclass::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionclass::newinstanceargs' => 
  array (
    0 => 'null|object',
    'args=' => 'array<array-key, mixed>',
  ),
  'reflectionclass::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
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
  'reflectionclass::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
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
  'reflectionclassconstant::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionclassconstant::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionclassconstant::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionclassconstant::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionclassconstant::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionclassconstant::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionclassconstant::getvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionclassconstant::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionclassconstant::isfinal' => 
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
  'reflectionclassconstant::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionconstant::__construct' => 
  array (
    0 => 'void',
    'name' => 'string',
  ),
  'reflectionconstant::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionconstant::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionconstant::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionconstant::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionconstant::getfilename' => 
  array (
    0 => 'false|string',
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
  'reflectionenum::__construct' => 
  array (
    0 => 'void',
    'objectOrClass' => 'object|string',
  ),
  'reflectionenum::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionenum::getbackingtype' => 
  array (
    0 => 'ReflectionNamedType|null',
  ),
  'reflectionenum::getcase' => 
  array (
    0 => 'ReflectionEnumUnitCase',
    'name' => 'string',
  ),
  'reflectionenum::getcases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionenum::getconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionenum::getdefaultproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionenum::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionenum::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionenum::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionenum::getfilename' => 
  array (
    0 => 'false|string',
  ),
  'reflectionenum::getinterfacenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::getinterfaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionenum::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionenum::getmethods' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenum::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionenum::getproperties' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionenum::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionenum::getreflectionconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionenum::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionenum::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionenum::getstaticproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionenum::gettraitaliases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::gettraitnames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::gettraits' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionenum::hascase' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionenum::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionenum::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionenum::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isbacked' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionenum::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionenum::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionenum::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionenum::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionenum::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionenum::newinstanceargs' => 
  array (
    0 => 'null|object',
    'args=' => 'array<array-key, mixed>',
  ),
  'reflectionenum::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
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
  'reflectionenum::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
  ),
  'reflectionenumbackedcase::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'reflectionenumbackedcase::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenumbackedcase::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionenumbackedcase::getbackingvalue' => 
  array (
    0 => 'int|string',
  ),
  'reflectionenumbackedcase::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionenumbackedcase::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionenumbackedcase::getenum' => 
  array (
    0 => 'ReflectionEnum',
  ),
  'reflectionenumbackedcase::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenumbackedcase::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenumbackedcase::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionenumbackedcase::getvalue' => 
  array (
    0 => 'UnitEnum',
  ),
  'reflectionenumbackedcase::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumbackedcase::isfinal' => 
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
  'reflectionenumbackedcase::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::__construct' => 
  array (
    0 => 'void',
    'class' => 'object|string',
    'constant' => 'string',
  ),
  'reflectionenumunitcase::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionenumunitcase::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionenumunitcase::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionenumunitcase::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionenumunitcase::getenum' => 
  array (
    0 => 'ReflectionEnum',
  ),
  'reflectionenumunitcase::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionenumunitcase::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionenumunitcase::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionenumunitcase::getvalue' => 
  array (
    0 => 'UnitEnum',
  ),
  'reflectionenumunitcase::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isenumcase' => 
  array (
    0 => 'bool',
  ),
  'reflectionenumunitcase::isfinal' => 
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
  'reflectionenumunitcase::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'reflectionexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionexception::__wakeup' => 
  array (
    0 => 'void',
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
  'reflectionexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'reflectionexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'reflectionexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionexception::gettraceasstring' => 
  array (
    0 => 'string',
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
  'reflectionextension::getclasses' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getclassnames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getconstants' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getdependencies' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getfunctions' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getinientries' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionextension::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionextension::getversion' => 
  array (
    0 => 'null|string',
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
  'reflectionfiber::__construct' => 
  array (
    0 => 'void',
    'fiber' => 'Fiber',
  ),
  'reflectionfiber::getcallable' => 
  array (
    0 => 'callable',
  ),
  'reflectionfiber::getexecutingfile' => 
  array (
    0 => 'null|string',
  ),
  'reflectionfiber::getexecutingline' => 
  array (
    0 => 'int|null',
  ),
  'reflectionfiber::getfiber' => 
  array (
    0 => 'Fiber',
  ),
  'reflectionfiber::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
    'options=' => 'int',
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
  'reflectionfunction::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionfunction::getclosure' => 
  array (
    0 => 'Closure',
  ),
  'reflectionfunction::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunction::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunction::getclosurethis' => 
  array (
    0 => 'null|object',
  ),
  'reflectionfunction::getclosureusedvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunction::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionfunction::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionfunction::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionfunction::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionfunction::getfilename' => 
  array (
    0 => 'false|string',
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
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunction::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunction::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunction::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionfunction::getstaticvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunction::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunction::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::hastentativereturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::innamespace' => 
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
    'args' => 'array<array-key, mixed>',
  ),
  'reflectionfunction::isanonymous' => 
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
  'reflectionfunction::isdisabled' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunction::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionfunctionabstract::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionfunctionabstract::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionfunctionabstract::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunctionabstract::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionfunctionabstract::getclosurethis' => 
  array (
    0 => 'null|object',
  ),
  'reflectionfunctionabstract::getclosureusedvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunctionabstract::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionfunctionabstract::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionfunctionabstract::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionfunctionabstract::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionfunctionabstract::getfilename' => 
  array (
    0 => 'false|string',
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
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunctionabstract::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunctionabstract::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionfunctionabstract::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionfunctionabstract::getstaticvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionfunctionabstract::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionfunctionabstract::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::hastentativereturntype' => 
  array (
    0 => 'bool',
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
  'reflectionfunctionabstract::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionfunctionabstract::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectiongenerator::__construct' => 
  array (
    0 => 'void',
    'generator' => 'Generator',
  ),
  'reflectiongenerator::getexecutingfile' => 
  array (
    0 => 'string',
  ),
  'reflectiongenerator::getexecutinggenerator' => 
  array (
    0 => 'Generator',
  ),
  'reflectiongenerator::getexecutingline' => 
  array (
    0 => 'int',
  ),
  'reflectiongenerator::getfunction' => 
  array (
    0 => 'ReflectionFunctionAbstract',
  ),
  'reflectiongenerator::getthis' => 
  array (
    0 => 'null|object',
  ),
  'reflectiongenerator::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
    'options=' => 'int',
  ),
  'reflectiongenerator::isclosed' => 
  array (
    0 => 'bool',
  ),
  'reflectionintersectiontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionintersectiontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionintersectiontype::gettypes' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionmethod::__construct' => 
  array (
    0 => 'void',
    'objectOrMethod' => 'object|string',
    'method=' => 'null|string',
  ),
  'reflectionmethod::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::createfrommethodname' => 
  array (
    0 => 'static',
    'method' => 'string',
  ),
  'reflectionmethod::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionmethod::getclosure' => 
  array (
    0 => 'Closure',
    'object=' => 'null|object',
  ),
  'reflectionmethod::getclosurecalledclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionmethod::getclosurescopeclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionmethod::getclosurethis' => 
  array (
    0 => 'null|object',
  ),
  'reflectionmethod::getclosureusedvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionmethod::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionmethod::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionmethod::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionmethod::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionmethod::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionmethod::getfilename' => 
  array (
    0 => 'false|string',
  ),
  'reflectionmethod::getmodifiers' => 
  array (
    0 => 'int',
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
    0 => 'array<array-key, mixed>',
  ),
  'reflectionmethod::getprototype' => 
  array (
    0 => 'ReflectionMethod',
  ),
  'reflectionmethod::getreturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionmethod::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionmethod::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionmethod::getstaticvariables' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionmethod::gettentativereturntype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionmethod::hasprototype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::hasreturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::hastentativereturntype' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::invoke' => 
  array (
    0 => 'mixed',
    'object' => 'null|object',
    '...args=' => 'mixed',
  ),
  'reflectionmethod::invokeargs' => 
  array (
    0 => 'mixed',
    'object' => 'null|object',
    'args' => 'array<array-key, mixed>',
  ),
  'reflectionmethod::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isclosure' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isconstructor' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isdeprecated' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isdestructor' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isgenerator' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isinternal' => 
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
  'reflectionmethod::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::isvariadic' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::returnsreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionmethod::setaccessible' => 
  array (
    0 => 'void',
    'accessible' => 'bool',
  ),
  'reflectionnamedtype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionnamedtype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionnamedtype::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionnamedtype::isbuiltin' => 
  array (
    0 => 'bool',
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
  'reflectionobject::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionobject::getconstant' => 
  array (
    0 => 'mixed',
    'name' => 'string',
  ),
  'reflectionobject::getconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getconstructor' => 
  array (
    0 => 'ReflectionMethod|null',
  ),
  'reflectionobject::getdefaultproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionobject::getendline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionobject::getextension' => 
  array (
    0 => 'ReflectionExtension|null',
  ),
  'reflectionobject::getextensionname' => 
  array (
    0 => 'false|string',
  ),
  'reflectionobject::getfilename' => 
  array (
    0 => 'false|string',
  ),
  'reflectionobject::getinterfacenames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::getinterfaces' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::getlazyinitializer' => 
  array (
    0 => 'callable|null',
    'object' => 'object',
  ),
  'reflectionobject::getmethod' => 
  array (
    0 => 'ReflectionMethod',
    'name' => 'string',
  ),
  'reflectionobject::getmethods' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionobject::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getnamespacename' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getparentclass' => 
  array (
    0 => 'ReflectionClass|false',
  ),
  'reflectionobject::getproperties' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getproperty' => 
  array (
    0 => 'ReflectionProperty',
    'name' => 'string',
  ),
  'reflectionobject::getreflectionconstant' => 
  array (
    0 => 'ReflectionClassConstant|false',
    'name' => 'string',
  ),
  'reflectionobject::getreflectionconstants' => 
  array (
    0 => 'array<array-key, mixed>',
    'filter=' => 'int|null',
  ),
  'reflectionobject::getshortname' => 
  array (
    0 => 'string',
  ),
  'reflectionobject::getstartline' => 
  array (
    0 => 'false|int',
  ),
  'reflectionobject::getstaticproperties' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::getstaticpropertyvalue' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'default=' => 'mixed',
  ),
  'reflectionobject::gettraitaliases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::gettraitnames' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::gettraits' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionobject::hasconstant' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::hasmethod' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::hasproperty' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'reflectionobject::implementsinterface' => 
  array (
    0 => 'bool',
    'interface' => 'ReflectionClass|string',
  ),
  'reflectionobject::initializelazyobject' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionobject::innamespace' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isabstract' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isanonymous' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::iscloneable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isenum' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isinstance' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionobject::isinstantiable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isinterface' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isinternal' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isiterable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isiterateable' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::issubclassof' => 
  array (
    0 => 'bool',
    'class' => 'ReflectionClass|string',
  ),
  'reflectionobject::istrait' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::isuninitializedlazyobject' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionobject::isuserdefined' => 
  array (
    0 => 'bool',
  ),
  'reflectionobject::marklazyobjectasinitialized' => 
  array (
    0 => 'object',
    'object' => 'object',
  ),
  'reflectionobject::newinstance' => 
  array (
    0 => 'object',
    '...args=' => 'mixed',
  ),
  'reflectionobject::newinstanceargs' => 
  array (
    0 => 'null|object',
    'args=' => 'array<array-key, mixed>',
  ),
  'reflectionobject::newinstancewithoutconstructor' => 
  array (
    0 => 'object',
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
  'reflectionobject::setstaticpropertyvalue' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value' => 'mixed',
  ),
  'reflectionparameter::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionparameter::__construct' => 
  array (
    0 => 'void',
    'function' => 'mixed',
    'param' => 'int|string',
  ),
  'reflectionparameter::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionparameter::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::canbepassedbyvalue' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionparameter::getclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionparameter::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass|null',
  ),
  'reflectionparameter::getdeclaringfunction' => 
  array (
    0 => 'ReflectionFunctionAbstract',
  ),
  'reflectionparameter::getdefaultvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionparameter::getdefaultvalueconstantname' => 
  array (
    0 => 'null|string',
  ),
  'reflectionparameter::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionparameter::getposition' => 
  array (
    0 => 'int',
  ),
  'reflectionparameter::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionparameter::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isarray' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::iscallable' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isdefaultvalueavailable' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isdefaultvalueconstant' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isoptional' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::ispassedbyreference' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::ispromoted' => 
  array (
    0 => 'bool',
  ),
  'reflectionparameter::isvariadic' => 
  array (
    0 => 'bool',
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
  'reflectionproperty::getattributes' => 
  array (
    0 => 'array<array-key, mixed>',
    'name=' => 'null|string',
    'flags=' => 'int',
  ),
  'reflectionproperty::getdeclaringclass' => 
  array (
    0 => 'ReflectionClass',
  ),
  'reflectionproperty::getdefaultvalue' => 
  array (
    0 => 'mixed',
  ),
  'reflectionproperty::getdoccomment' => 
  array (
    0 => 'false|string',
  ),
  'reflectionproperty::gethook' => 
  array (
    0 => 'ReflectionMethod|null',
    'type' => 'PropertyHookType',
  ),
  'reflectionproperty::gethooks' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'reflectionproperty::getmangledname' => 
  array (
    0 => 'string',
  ),
  'reflectionproperty::getmodifiers' => 
  array (
    0 => 'int',
  ),
  'reflectionproperty::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionproperty::getrawvalue' => 
  array (
    0 => 'mixed',
    'object' => 'object',
  ),
  'reflectionproperty::getsettabletype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionproperty::gettype' => 
  array (
    0 => 'ReflectionType|null',
  ),
  'reflectionproperty::getvalue' => 
  array (
    0 => 'mixed',
    'object=' => 'null|object',
  ),
  'reflectionproperty::hasdefaultvalue' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::hashook' => 
  array (
    0 => 'bool',
    'type' => 'PropertyHookType',
  ),
  'reflectionproperty::hashooks' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::hastype' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isabstract' => 
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
  'reflectionproperty::isfinal' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isinitialized' => 
  array (
    0 => 'bool',
    'object=' => 'null|object',
  ),
  'reflectionproperty::islazy' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'reflectionproperty::isprivate' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprivateset' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::ispromoted' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprotected' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isprotectedset' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::ispublic' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isreadonly' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isstatic' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::isvirtual' => 
  array (
    0 => 'bool',
  ),
  'reflectionproperty::setaccessible' => 
  array (
    0 => 'void',
    'accessible' => 'bool',
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
  'reflectionproperty::setvalue' => 
  array (
    0 => 'void',
    'objectOrValue' => 'mixed',
    'value=' => 'mixed',
  ),
  'reflectionproperty::skiplazyinitialization' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'reflectionreference::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectionreference::__construct' => 
  array (
    0 => 'void',
  ),
  'reflectionreference::fromarrayelement' => 
  array (
    0 => 'ReflectionReference|null',
    'array' => 'array<array-key, mixed>',
    'key' => 'int|string',
  ),
  'reflectionreference::getid' => 
  array (
    0 => 'string',
  ),
  'reflectiontype::__clone' => 
  array (
    0 => 'void',
  ),
  'reflectiontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectiontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionuniontype::__tostring' => 
  array (
    0 => 'string',
  ),
  'reflectionuniontype::allowsnull' => 
  array (
    0 => 'bool',
  ),
  'reflectionuniontype::gettypes' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'reflectionzendextension::getauthor' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getcopyright' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getname' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::geturl' => 
  array (
    0 => 'string',
  ),
  'reflectionzendextension::getversion' => 
  array (
    0 => 'string',
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
  'regexiterator::current' => 
  array (
    0 => 'mixed',
  ),
  'regexiterator::getflags' => 
  array (
    0 => 'int',
  ),
  'regexiterator::getinneriterator' => 
  array (
    0 => 'Iterator|null',
  ),
  'regexiterator::getmode' => 
  array (
    0 => 'int',
  ),
  'regexiterator::getpregflags' => 
  array (
    0 => 'int',
  ),
  'regexiterator::getregex' => 
  array (
    0 => 'string',
  ),
  'regexiterator::key' => 
  array (
    0 => 'mixed',
  ),
  'regexiterator::next' => 
  array (
    0 => 'void',
  ),
  'regexiterator::rewind' => 
  array (
    0 => 'void',
  ),
  'regexiterator::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'regexiterator::setmode' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'regexiterator::setpregflags' => 
  array (
    0 => 'void',
    'pregFlags' => 'int',
  ),
  'regexiterator::valid' => 
  array (
    0 => 'bool',
  ),
  'register_shutdown_function' => 
  array (
    0 => 'void',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'register_tick_function' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
    '...args=' => 'mixed',
  ),
  'rename' => 
  array (
    0 => 'bool',
    'from' => 'string',
    'to' => 'string',
    'context=' => 'mixed',
  ),
  'request_parse_body' => 
  array (
    0 => 'array<array-key, mixed>',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'requestparsebodyexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'requestparsebodyexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::__wakeup' => 
  array (
    0 => 'void',
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
  'requestparsebodyexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'requestparsebodyexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'requestparsebodyexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'requestparsebodyexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'reset' => 
  array (
    0 => 'mixed',
    '&array' => 'array<array-key, mixed>|object',
  ),
  'resourcebundle::__construct' => 
  array (
    0 => 'void',
    'locale' => 'null|string',
    'bundle' => 'null|string',
    'fallback=' => 'bool',
  ),
  'resourcebundle::count' => 
  array (
    0 => 'int',
  ),
  'resourcebundle::create' => 
  array (
    0 => 'ResourceBundle|null',
    'locale' => 'null|string',
    'bundle' => 'null|string',
    'fallback=' => 'bool',
  ),
  'resourcebundle::get' => 
  array (
    0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
    'index' => 'int|string',
    'fallback=' => 'bool',
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
  'resourcebundle::getlocales' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'bundle' => 'string',
  ),
  'resourcebundle_count' => 
  array (
    0 => 'int',
    'bundle' => 'ResourceBundle',
  ),
  'resourcebundle_create' => 
  array (
    0 => 'ResourceBundle|null',
    'locale' => 'null|string',
    'bundle' => 'null|string',
    'fallback=' => 'bool',
  ),
  'resourcebundle_get' => 
  array (
    0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
    'bundle' => 'ResourceBundle',
    'index' => 'int|string',
    'fallback=' => 'bool',
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
  'resourcebundle_locales' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'bundle' => 'string',
  ),
  'restore_error_handler' => 
  array (
    0 => 'true',
  ),
  'restore_exception_handler' => 
  array (
    0 => 'true',
  ),
  'returntypewillchange::__construct' => 
  array (
    0 => 'void',
  ),
  'rewind' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'rewinddir' => 
  array (
    0 => 'void',
    'dir_handle=' => 'mixed',
  ),
  'rmdir' => 
  array (
    0 => 'bool',
    'directory' => 'string',
    'context=' => 'mixed',
  ),
  'round' => 
  array (
    0 => 'float',
    'num' => 'float|int',
    'precision=' => 'int',
    'mode=' => 'RoundingMode|int',
  ),
  'roundingmode::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'rsort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'rtrim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'runtimeexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'runtimeexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::__wakeup' => 
  array (
    0 => 'void',
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
  'runtimeexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'runtimeexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'runtimeexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'runtimeexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'scandir' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'directory' => 'string',
    'sorting_order=' => 'int',
    'context=' => 'mixed',
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
  'sensitiveparametervalue::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'sensitiveparametervalue::getvalue' => 
  array (
    0 => 'mixed',
  ),
  'serialize' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'session_abort' => 
  array (
    0 => 'bool',
  ),
  'session_cache_expire' => 
  array (
    0 => 'false|int',
    'value=' => 'int|null',
  ),
  'session_cache_limiter' => 
  array (
    0 => 'false|string',
    'value=' => 'null|string',
  ),
  'session_commit' => 
  array (
    0 => 'bool',
  ),
  'session_create_id' => 
  array (
    0 => 'false|string',
    'prefix=' => 'string',
  ),
  'session_decode' => 
  array (
    0 => 'bool',
    'data' => 'string',
  ),
  'session_destroy' => 
  array (
    0 => 'bool',
  ),
  'session_encode' => 
  array (
    0 => 'false|string',
  ),
  'session_gc' => 
  array (
    0 => 'false|int',
  ),
  'session_get_cookie_params' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'session_id' => 
  array (
    0 => 'false|string',
    'id=' => 'null|string',
  ),
  'session_module_name' => 
  array (
    0 => 'false|string',
    'module=' => 'null|string',
  ),
  'session_name' => 
  array (
    0 => 'false|string',
    'name=' => 'null|string',
  ),
  'session_regenerate_id' => 
  array (
    0 => 'bool',
    'delete_old_session=' => 'bool',
  ),
  'session_register_shutdown' => 
  array (
    0 => 'void',
  ),
  'session_reset' => 
  array (
    0 => 'bool',
  ),
  'session_save_path' => 
  array (
    0 => 'false|string',
    'path=' => 'null|string',
  ),
  'session_set_cookie_params' => 
  array (
    0 => 'bool',
    'lifetime_or_options' => 'array<array-key, mixed>|int',
    'path=' => 'null|string',
    'domain=' => 'null|string',
    'secure=' => 'bool|null',
    'httponly=' => 'bool|null',
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
  'session_start' => 
  array (
    0 => 'bool',
    'options=' => 'array<array-key, mixed>',
  ),
  'session_status' => 
  array (
    0 => 'int',
  ),
  'session_unset' => 
  array (
    0 => 'bool',
  ),
  'session_write_close' => 
  array (
    0 => 'bool',
  ),
  'sessionhandler::close' => 
  array (
    0 => 'bool',
  ),
  'sessionhandler::create_sid' => 
  array (
    0 => 'string',
  ),
  'sessionhandler::destroy' => 
  array (
    0 => 'bool',
    'id' => 'string',
  ),
  'sessionhandler::gc' => 
  array (
    0 => 'false|int',
    'max_lifetime' => 'int',
  ),
  'sessionhandler::open' => 
  array (
    0 => 'bool',
    'path' => 'string',
    'name' => 'string',
  ),
  'sessionhandler::read' => 
  array (
    0 => 'false|string',
    'id' => 'string',
  ),
  'sessionhandler::write' => 
  array (
    0 => 'bool',
    'id' => 'string',
    'data' => 'string',
  ),
  'set_error_handler' => 
  array (
    0 => 'mixed',
    'callback' => 'callable|null',
    'error_levels=' => 'int',
  ),
  'set_exception_handler' => 
  array (
    0 => 'mixed',
    'callback' => 'callable|null',
  ),
  'set_file_buffer' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'set_include_path' => 
  array (
    0 => 'false|string',
    'include_path' => 'string',
  ),
  'set_time_limit' => 
  array (
    0 => 'bool',
    'seconds' => 'int',
  ),
  'setcookie' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value=' => 'string',
    'expires_or_options=' => 'array<array-key, mixed>|int',
    'path=' => 'string',
    'domain=' => 'string',
    'secure=' => 'bool',
    'httponly=' => 'bool',
  ),
  'setlocale' => 
  array (
    0 => 'false|string',
    'category' => 'int',
    'locales' => 'mixed',
    '...rest=' => 'mixed',
  ),
  'setrawcookie' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value=' => 'string',
    'expires_or_options=' => 'array<array-key, mixed>|int',
    'path=' => 'string',
    'domain=' => 'string',
    'secure=' => 'bool',
    'httponly=' => 'bool',
  ),
  'settype' => 
  array (
    0 => 'bool',
    '&var' => 'mixed',
    'type' => 'string',
  ),
  'sha1' => 
  array (
    0 => 'string',
    'string' => 'string',
    'binary=' => 'bool',
  ),
  'sha1_file' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
    'binary=' => 'bool',
  ),
  'shell_exec' => 
  array (
    0 => 'false|null|string',
    'command' => 'string',
  ),
  'show_source' => 
  array (
    0 => 'bool|string',
    'filename' => 'string',
    'return=' => 'bool',
  ),
  'shuffle' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
  ),
  'similar_text' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    '&percent=' => 'mixed',
  ),
  'simplexml_import_dom' => 
  array (
    0 => 'SimpleXMLElement|null',
    'node' => 'object',
    'class_name=' => 'null|string',
  ),
  'simplexml_load_file' => 
  array (
    0 => 'SimpleXMLElement|false',
    'filename' => 'string',
    'class_name=' => 'null|string',
    'options=' => 'int',
    'namespace_or_prefix=' => 'string',
    'is_prefix=' => 'bool',
  ),
  'simplexml_load_string' => 
  array (
    0 => 'SimpleXMLElement|false',
    'data' => 'string',
    'class_name=' => 'null|string',
    'options=' => 'int',
    'namespace_or_prefix=' => 'string',
    'is_prefix=' => 'bool',
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
  'simplexmlelement::__tostring' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::addattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'null|string',
  ),
  'simplexmlelement::addchild' => 
  array (
    0 => 'SimpleXMLElement|null',
    'qualifiedName' => 'string',
    'value=' => 'null|string',
    'namespace=' => 'null|string',
  ),
  'simplexmlelement::asxml' => 
  array (
    0 => 'bool|string',
    'filename=' => 'null|string',
  ),
  'simplexmlelement::attributes' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'null|string',
    'isPrefix=' => 'bool',
  ),
  'simplexmlelement::children' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'null|string',
    'isPrefix=' => 'bool',
  ),
  'simplexmlelement::count' => 
  array (
    0 => 'int',
  ),
  'simplexmlelement::current' => 
  array (
    0 => 'SimpleXMLElement',
  ),
  'simplexmlelement::getchildren' => 
  array (
    0 => 'SimpleXMLElement|null',
  ),
  'simplexmlelement::getdocnamespaces' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'simplexmlelement::getname' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::getnamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
    'recursive=' => 'bool',
  ),
  'simplexmlelement::haschildren' => 
  array (
    0 => 'bool',
  ),
  'simplexmlelement::key' => 
  array (
    0 => 'string',
  ),
  'simplexmlelement::next' => 
  array (
    0 => 'void',
  ),
  'simplexmlelement::registerxpathnamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'simplexmlelement::rewind' => 
  array (
    0 => 'void',
  ),
  'simplexmlelement::savexml' => 
  array (
    0 => 'bool|string',
    'filename=' => 'null|string',
  ),
  'simplexmlelement::valid' => 
  array (
    0 => 'bool',
  ),
  'simplexmlelement::xpath' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'expression' => 'string',
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
  'simplexmliterator::__tostring' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::addattribute' => 
  array (
    0 => 'void',
    'qualifiedName' => 'string',
    'value' => 'string',
    'namespace=' => 'null|string',
  ),
  'simplexmliterator::addchild' => 
  array (
    0 => 'SimpleXMLElement|null',
    'qualifiedName' => 'string',
    'value=' => 'null|string',
    'namespace=' => 'null|string',
  ),
  'simplexmliterator::asxml' => 
  array (
    0 => 'bool|string',
    'filename=' => 'null|string',
  ),
  'simplexmliterator::attributes' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'null|string',
    'isPrefix=' => 'bool',
  ),
  'simplexmliterator::children' => 
  array (
    0 => 'SimpleXMLElement|null',
    'namespaceOrPrefix=' => 'null|string',
    'isPrefix=' => 'bool',
  ),
  'simplexmliterator::count' => 
  array (
    0 => 'int',
  ),
  'simplexmliterator::current' => 
  array (
    0 => 'SimpleXMLElement',
  ),
  'simplexmliterator::getchildren' => 
  array (
    0 => 'SimpleXMLElement|null',
  ),
  'simplexmliterator::getdocnamespaces' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'recursive=' => 'bool',
    'fromRoot=' => 'bool',
  ),
  'simplexmliterator::getname' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::getnamespaces' => 
  array (
    0 => 'array<array-key, mixed>',
    'recursive=' => 'bool',
  ),
  'simplexmliterator::haschildren' => 
  array (
    0 => 'bool',
  ),
  'simplexmliterator::key' => 
  array (
    0 => 'string',
  ),
  'simplexmliterator::next' => 
  array (
    0 => 'void',
  ),
  'simplexmliterator::registerxpathnamespace' => 
  array (
    0 => 'bool',
    'prefix' => 'string',
    'namespace' => 'string',
  ),
  'simplexmliterator::rewind' => 
  array (
    0 => 'void',
  ),
  'simplexmliterator::savexml' => 
  array (
    0 => 'bool|string',
    'filename=' => 'null|string',
  ),
  'simplexmliterator::valid' => 
  array (
    0 => 'bool',
  ),
  'simplexmliterator::xpath' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'expression' => 'string',
  ),
  'sin' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'sinh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'sizeof' => 
  array (
    0 => 'int',
    'value' => 'Countable|array<array-key, mixed>',
    'mode=' => 'int',
  ),
  'sleep' => 
  array (
    0 => 'int',
    'seconds' => 'int',
  ),
  'soapclient::__call' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'args' => 'array<array-key, mixed>',
  ),
  'soapclient::__construct' => 
  array (
    0 => 'void',
    'wsdl' => 'null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'soapclient::__dorequest' => 
  array (
    0 => 'null|string',
    'request' => 'string',
    'location' => 'string',
    'action' => 'string',
    'version' => 'int',
    'oneWay=' => 'bool',
    'uriParserClass=' => 'null|string',
  ),
  'soapclient::__getcookies' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'soapclient::__getfunctions' => 
  array (
    0 => 'array<array-key, mixed>|null',
  ),
  'soapclient::__getlastrequest' => 
  array (
    0 => 'null|string',
  ),
  'soapclient::__getlastrequestheaders' => 
  array (
    0 => 'null|string',
  ),
  'soapclient::__getlastresponse' => 
  array (
    0 => 'null|string',
  ),
  'soapclient::__getlastresponseheaders' => 
  array (
    0 => 'null|string',
  ),
  'soapclient::__gettypes' => 
  array (
    0 => 'array<array-key, mixed>|null',
  ),
  'soapclient::__setcookie' => 
  array (
    0 => 'void',
    'name' => 'string',
    'value=' => 'null|string',
  ),
  'soapclient::__setlocation' => 
  array (
    0 => 'null|string',
    'location=' => 'null|string',
  ),
  'soapclient::__setsoapheaders' => 
  array (
    0 => 'bool',
    'headers=' => 'mixed',
  ),
  'soapclient::__soapcall' => 
  array (
    0 => 'mixed',
    'name' => 'string',
    'args' => 'array<array-key, mixed>',
    'options=' => 'array<array-key, mixed>|null',
    'inputHeaders=' => 'mixed',
    '&outputHeaders=' => 'mixed',
  ),
  'soapfault::__construct' => 
  array (
    0 => 'void',
    'code' => 'array<array-key, mixed>|null|string',
    'string' => 'string',
    'actor=' => 'null|string',
    'details=' => 'mixed',
    'name=' => 'null|string',
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
  'soapfault::getmessage' => 
  array (
    0 => 'string',
  ),
  'soapfault::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'soapfault::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'soapfault::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'soapheader::__construct' => 
  array (
    0 => 'void',
    'namespace' => 'string',
    'name' => 'string',
    'data=' => 'mixed',
    'mustUnderstand=' => 'bool',
    'actor=' => 'int|null|string',
  ),
  'soapparam::__construct' => 
  array (
    0 => 'void',
    'data' => 'mixed',
    'name' => 'string',
  ),
  'soapserver::__construct' => 
  array (
    0 => 'void',
    'wsdl' => 'null|string',
    'options=' => 'array<array-key, mixed>',
  ),
  'soapserver::__getlastresponse' => 
  array (
    0 => 'null|string',
  ),
  'soapserver::addfunction' => 
  array (
    0 => 'void',
    'functions' => 'mixed',
  ),
  'soapserver::addsoapheader' => 
  array (
    0 => 'void',
    'header' => 'SoapHeader',
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
  'soapserver::getfunctions' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'soapserver::handle' => 
  array (
    0 => 'void',
    'request=' => 'null|string',
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
  'soapserver::setpersistence' => 
  array (
    0 => 'void',
    'mode' => 'int',
  ),
  'soapvar::__construct' => 
  array (
    0 => 'void',
    'data' => 'mixed',
    'encoding' => 'int|null',
    'typeName=' => 'null|string',
    'typeNamespace=' => 'null|string',
    'nodeName=' => 'null|string',
    'nodeNamespace=' => 'null|string',
  ),
  'socket_get_status' => 
  array (
    0 => 'array<array-key, mixed>',
    'stream' => 'mixed',
  ),
  'socket_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'enable' => 'bool',
  ),
  'socket_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'sodium_add' => 
  array (
    0 => 'void',
    '&string1' => 'string',
    'string2' => 'string',
  ),
  'sodium_base642bin' => 
  array (
    0 => 'string',
    'string' => 'string',
    'id' => 'int',
    'ignore=' => 'string',
  ),
  'sodium_bin2base64' => 
  array (
    0 => 'string',
    'string' => 'string',
    'id' => 'int',
  ),
  'sodium_bin2hex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'sodium_compare' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'sodium_crypto_aead_aegis128l_decrypt' => 
  array (
    0 => 'false|string',
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
    0 => 'false|string',
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
  'sodium_crypto_aead_aes256gcm_decrypt' => 
  array (
    0 => 'false|string',
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
  'sodium_crypto_aead_aes256gcm_is_available' => 
  array (
    0 => 'bool',
  ),
  'sodium_crypto_aead_aes256gcm_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_chacha20poly1305_decrypt' => 
  array (
    0 => 'false|string',
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
  'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => 
  array (
    0 => 'false|string',
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
  'sodium_crypto_aead_chacha20poly1305_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt' => 
  array (
    0 => 'false|string',
    'ciphertext' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => 
  array (
    0 => 'string',
    'message' => 'string',
    'additional_data' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_aead_xchacha20poly1305_ietf_keygen' => 
  array (
    0 => 'string',
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
  'sodium_crypto_box_keypair_from_secretkey_and_publickey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_box_open' => 
  array (
    0 => 'false|string',
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
    0 => 'false|string',
    'ciphertext' => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_secretkey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_box_seed_keypair' => 
  array (
    0 => 'string',
    'seed' => 'string',
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
  'sodium_crypto_generichash' => 
  array (
    0 => 'string',
    'message' => 'string',
    'key=' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_generichash_final' => 
  array (
    0 => 'string',
    '&state' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_generichash_init' => 
  array (
    0 => 'string',
    'key=' => 'string',
    'length=' => 'int',
  ),
  'sodium_crypto_generichash_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_generichash_update' => 
  array (
    0 => 'true',
    '&state' => 'string',
    'message' => 'string',
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
  'sodium_crypto_kx_client_session_keys' => 
  array (
    0 => 'array<array-key, mixed>',
    'client_key_pair' => 'string',
    'server_key' => 'string',
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
  'sodium_crypto_kx_server_session_keys' => 
  array (
    0 => 'array<array-key, mixed>',
    'server_key_pair' => 'string',
    'client_key' => 'string',
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
  'sodium_crypto_pwhash_str' => 
  array (
    0 => 'string',
    'password' => 'string',
    'opslimit' => 'int',
    'memlimit' => 'int',
  ),
  'sodium_crypto_pwhash_str_needs_rehash' => 
  array (
    0 => 'bool',
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
  'sodium_crypto_scalarmult' => 
  array (
    0 => 'string',
    'n' => 'string',
    'p' => 'string',
  ),
  'sodium_crypto_scalarmult_base' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
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
    0 => 'false|string',
    'ciphertext' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => 
  array (
    0 => 'string',
    'header' => 'string',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_init_push' => 
  array (
    0 => 'array<array-key, mixed>',
    'key' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_keygen' => 
  array (
    0 => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_pull' => 
  array (
    0 => 'array<array-key, mixed>|false',
    '&state' => 'string',
    'ciphertext' => 'string',
    'additional_data=' => 'string',
  ),
  'sodium_crypto_secretstream_xchacha20poly1305_push' => 
  array (
    0 => 'string',
    '&state' => 'string',
    'message' => 'string',
    'additional_data=' => 'string',
    'tag=' => 'int',
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
    0 => 'false|string',
    'signed_message' => 'string',
    'public_key' => 'string',
  ),
  'sodium_crypto_sign_publickey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
  ),
  'sodium_crypto_sign_publickey_from_secretkey' => 
  array (
    0 => 'string',
    'secret_key' => 'string',
  ),
  'sodium_crypto_sign_secretkey' => 
  array (
    0 => 'string',
    'key_pair' => 'string',
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
  'sodium_crypto_stream_xor' => 
  array (
    0 => 'string',
    'message' => 'string',
    'nonce' => 'string',
    'key' => 'string',
  ),
  'sodium_hex2bin' => 
  array (
    0 => 'string',
    'string' => 'string',
    'ignore=' => 'string',
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
  'sodiumexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'sodiumexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'sodiumexception::__wakeup' => 
  array (
    0 => 'void',
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
  'sodiumexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'sodiumexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'sodiumexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'sodiumexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'sort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'flags=' => 'int',
  ),
  'soundex' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'spl_autoload' => 
  array (
    0 => 'void',
    'class' => 'string',
    'file_extensions=' => 'null|string',
  ),
  'spl_autoload_call' => 
  array (
    0 => 'void',
    'class' => 'string',
  ),
  'spl_autoload_extensions' => 
  array (
    0 => 'string',
    'file_extensions=' => 'null|string',
  ),
  'spl_autoload_functions' => 
  array (
    0 => 'array<array-key, mixed>',
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
    0 => 'array<array-key, mixed>',
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
  'spldoublylinkedlist::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'spldoublylinkedlist::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'spldoublylinkedlist::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'spldoublylinkedlist::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::bottom' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::count' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::current' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::isempty' => 
  array (
    0 => 'bool',
  ),
  'spldoublylinkedlist::key' => 
  array (
    0 => 'int',
  ),
  'spldoublylinkedlist::next' => 
  array (
    0 => 'void',
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
  'spldoublylinkedlist::pop' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::prev' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::rewind' => 
  array (
    0 => 'void',
  ),
  'spldoublylinkedlist::serialize' => 
  array (
    0 => 'string',
  ),
  'spldoublylinkedlist::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'spldoublylinkedlist::shift' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::top' => 
  array (
    0 => 'mixed',
  ),
  'spldoublylinkedlist::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'spldoublylinkedlist::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'spldoublylinkedlist::valid' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
  ),
  'splfileinfo::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splfileinfo::__tostring' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'splfileinfo::getatime' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'splfileinfo::getctime' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getextension' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'splfileinfo::getfilename' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getinode' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'splfileinfo::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getowner' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getpath' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'splfileinfo::getpathname' => 
  array (
    0 => 'string',
  ),
  'splfileinfo::getperms' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'splfileinfo::getsize' => 
  array (
    0 => 'false|int',
  ),
  'splfileinfo::gettype' => 
  array (
    0 => 'false|string',
  ),
  'splfileinfo::isdir' => 
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
  'splfileinfo::islink' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::isreadable' => 
  array (
    0 => 'bool',
  ),
  'splfileinfo::iswritable' => 
  array (
    0 => 'bool',
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
  'splfileobject::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'splfileobject::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splfileobject::__tostring' => 
  array (
    0 => 'string',
  ),
  'splfileobject::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'splfileobject::current' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
  ),
  'splfileobject::eof' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::fflush' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::fgetc' => 
  array (
    0 => 'false|string',
  ),
  'splfileobject::fgetcsv' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'splfileobject::fgets' => 
  array (
    0 => 'string',
  ),
  'splfileobject::flock' => 
  array (
    0 => 'bool',
    'operation' => 'int',
    '&wouldBlock=' => 'mixed',
  ),
  'splfileobject::fpassthru' => 
  array (
    0 => 'int',
  ),
  'splfileobject::fputcsv' => 
  array (
    0 => 'false|int',
    'fields' => 'array<array-key, mixed>',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'splfileobject::fread' => 
  array (
    0 => 'false|string',
    'length' => 'int',
  ),
  'splfileobject::fscanf' => 
  array (
    0 => 'array<array-key, mixed>|int|null',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'splfileobject::fseek' => 
  array (
    0 => 'int',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'splfileobject::fstat' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splfileobject::ftell' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::ftruncate' => 
  array (
    0 => 'bool',
    'size' => 'int',
  ),
  'splfileobject::fwrite' => 
  array (
    0 => 'false|int',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'splfileobject::getatime' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'splfileobject::getchildren' => 
  array (
    0 => 'null',
  ),
  'splfileobject::getcsvcontrol' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splfileobject::getctime' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getcurrentline' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getextension' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'splfileobject::getfilename' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getflags' => 
  array (
    0 => 'int',
  ),
  'splfileobject::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getinode' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'splfileobject::getmaxlinelen' => 
  array (
    0 => 'int',
  ),
  'splfileobject::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getowner' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getpath' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'splfileobject::getpathname' => 
  array (
    0 => 'string',
  ),
  'splfileobject::getperms' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'splfileobject::getsize' => 
  array (
    0 => 'false|int',
  ),
  'splfileobject::gettype' => 
  array (
    0 => 'false|string',
  ),
  'splfileobject::haschildren' => 
  array (
    0 => 'false',
  ),
  'splfileobject::isdir' => 
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
  'splfileobject::islink' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::isreadable' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::iswritable' => 
  array (
    0 => 'bool',
  ),
  'splfileobject::key' => 
  array (
    0 => 'int',
  ),
  'splfileobject::next' => 
  array (
    0 => 'void',
  ),
  'splfileobject::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'splfileobject::rewind' => 
  array (
    0 => 'void',
  ),
  'splfileobject::seek' => 
  array (
    0 => 'void',
    'line' => 'int',
  ),
  'splfileobject::setcsvcontrol' => 
  array (
    0 => 'void',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'splfileobject::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'splfileobject::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'splfileobject::setmaxlinelen' => 
  array (
    0 => 'void',
    'maxLength' => 'int',
  ),
  'splfileobject::valid' => 
  array (
    0 => 'bool',
  ),
  'splfixedarray::__construct' => 
  array (
    0 => 'void',
    'size=' => 'int',
  ),
  'splfixedarray::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splfixedarray::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splfixedarray::__wakeup' => 
  array (
    0 => 'void',
  ),
  'splfixedarray::count' => 
  array (
    0 => 'int',
  ),
  'splfixedarray::fromarray' => 
  array (
    0 => 'SplFixedArray',
    'array' => 'array<array-key, mixed>',
    'preserveKeys=' => 'bool',
  ),
  'splfixedarray::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'splfixedarray::getsize' => 
  array (
    0 => 'int',
  ),
  'splfixedarray::jsonserialize' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'splfixedarray::setsize' => 
  array (
    0 => 'true',
    'size' => 'int',
  ),
  'splfixedarray::toarray' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splheap::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splheap::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splheap::count' => 
  array (
    0 => 'int',
  ),
  'splheap::current' => 
  array (
    0 => 'mixed',
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
  'splheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splheap::key' => 
  array (
    0 => 'int',
  ),
  'splheap::next' => 
  array (
    0 => 'void',
  ),
  'splheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splmaxheap::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splmaxheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splmaxheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splmaxheap::count' => 
  array (
    0 => 'int',
  ),
  'splmaxheap::current' => 
  array (
    0 => 'mixed',
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
  'splmaxheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splmaxheap::key' => 
  array (
    0 => 'int',
  ),
  'splmaxheap::next' => 
  array (
    0 => 'void',
  ),
  'splmaxheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splmaxheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splmaxheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splmaxheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splminheap::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splminheap::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splminheap::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splminheap::compare' => 
  array (
    0 => 'int',
    'value1' => 'mixed',
    'value2' => 'mixed',
  ),
  'splminheap::count' => 
  array (
    0 => 'int',
  ),
  'splminheap::current' => 
  array (
    0 => 'mixed',
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
  'splminheap::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splminheap::isempty' => 
  array (
    0 => 'bool',
  ),
  'splminheap::key' => 
  array (
    0 => 'int',
  ),
  'splminheap::next' => 
  array (
    0 => 'void',
  ),
  'splminheap::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splminheap::rewind' => 
  array (
    0 => 'void',
  ),
  'splminheap::top' => 
  array (
    0 => 'mixed',
  ),
  'splminheap::valid' => 
  array (
    0 => 'bool',
  ),
  'splobjectstorage::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splobjectstorage::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splobjectstorage::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splobjectstorage::addall' => 
  array (
    0 => 'int',
    'storage' => 'SplObjectStorage',
  ),
  'splobjectstorage::attach' => 
  array (
    0 => 'void',
    'object' => 'object',
    'info=' => 'mixed',
  ),
  'splobjectstorage::contains' => 
  array (
    0 => 'bool',
    'object' => 'object',
  ),
  'splobjectstorage::count' => 
  array (
    0 => 'int',
    'mode=' => 'int',
  ),
  'splobjectstorage::current' => 
  array (
    0 => 'object',
  ),
  'splobjectstorage::detach' => 
  array (
    0 => 'void',
    'object' => 'object',
  ),
  'splobjectstorage::gethash' => 
  array (
    0 => 'string',
    'object' => 'object',
  ),
  'splobjectstorage::getinfo' => 
  array (
    0 => 'mixed',
  ),
  'splobjectstorage::key' => 
  array (
    0 => 'int',
  ),
  'splobjectstorage::next' => 
  array (
    0 => 'void',
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
  'splobjectstorage::rewind' => 
  array (
    0 => 'void',
  ),
  'splobjectstorage::seek' => 
  array (
    0 => 'void',
    'offset' => 'int',
  ),
  'splobjectstorage::serialize' => 
  array (
    0 => 'string',
  ),
  'splobjectstorage::setinfo' => 
  array (
    0 => 'void',
    'info' => 'mixed',
  ),
  'splobjectstorage::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splobjectstorage::valid' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splpriorityqueue::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splpriorityqueue::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splpriorityqueue::compare' => 
  array (
    0 => 'int',
    'priority1' => 'mixed',
    'priority2' => 'mixed',
  ),
  'splpriorityqueue::count' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::current' => 
  array (
    0 => 'mixed',
  ),
  'splpriorityqueue::extract' => 
  array (
    0 => 'mixed',
  ),
  'splpriorityqueue::getextractflags' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::insert' => 
  array (
    0 => 'true',
    'value' => 'mixed',
    'priority' => 'mixed',
  ),
  'splpriorityqueue::iscorrupted' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'splpriorityqueue::key' => 
  array (
    0 => 'int',
  ),
  'splpriorityqueue::next' => 
  array (
    0 => 'void',
  ),
  'splpriorityqueue::recoverfromcorruption' => 
  array (
    0 => 'true',
  ),
  'splpriorityqueue::rewind' => 
  array (
    0 => 'void',
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
  'splpriorityqueue::valid' => 
  array (
    0 => 'bool',
  ),
  'splqueue::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splqueue::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splqueue::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splqueue::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'splqueue::bottom' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::count' => 
  array (
    0 => 'int',
  ),
  'splqueue::current' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::dequeue' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::enqueue' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'splqueue::isempty' => 
  array (
    0 => 'bool',
  ),
  'splqueue::key' => 
  array (
    0 => 'int',
  ),
  'splqueue::next' => 
  array (
    0 => 'void',
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
  'splqueue::pop' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::prev' => 
  array (
    0 => 'void',
  ),
  'splqueue::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::rewind' => 
  array (
    0 => 'void',
  ),
  'splqueue::serialize' => 
  array (
    0 => 'string',
  ),
  'splqueue::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'splqueue::shift' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::top' => 
  array (
    0 => 'mixed',
  ),
  'splqueue::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splqueue::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splqueue::valid' => 
  array (
    0 => 'bool',
  ),
  'splstack::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splstack::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'splstack::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'splstack::add' => 
  array (
    0 => 'void',
    'index' => 'int',
    'value' => 'mixed',
  ),
  'splstack::bottom' => 
  array (
    0 => 'mixed',
  ),
  'splstack::count' => 
  array (
    0 => 'int',
  ),
  'splstack::current' => 
  array (
    0 => 'mixed',
  ),
  'splstack::getiteratormode' => 
  array (
    0 => 'int',
  ),
  'splstack::isempty' => 
  array (
    0 => 'bool',
  ),
  'splstack::key' => 
  array (
    0 => 'int',
  ),
  'splstack::next' => 
  array (
    0 => 'void',
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
  'splstack::pop' => 
  array (
    0 => 'mixed',
  ),
  'splstack::prev' => 
  array (
    0 => 'void',
  ),
  'splstack::push' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splstack::rewind' => 
  array (
    0 => 'void',
  ),
  'splstack::serialize' => 
  array (
    0 => 'string',
  ),
  'splstack::setiteratormode' => 
  array (
    0 => 'int',
    'mode' => 'int',
  ),
  'splstack::shift' => 
  array (
    0 => 'mixed',
  ),
  'splstack::top' => 
  array (
    0 => 'mixed',
  ),
  'splstack::unserialize' => 
  array (
    0 => 'void',
    'data' => 'string',
  ),
  'splstack::unshift' => 
  array (
    0 => 'void',
    'value' => 'mixed',
  ),
  'splstack::valid' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::__construct' => 
  array (
    0 => 'void',
    'maxMemory=' => 'int',
  ),
  'spltempfileobject::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'spltempfileobject::__tostring' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::_bad_state_ex' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::current' => 
  array (
    0 => 'array<array-key, mixed>|false|string',
  ),
  'spltempfileobject::eof' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::fflush' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::fgetc' => 
  array (
    0 => 'false|string',
  ),
  'spltempfileobject::fgetcsv' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'spltempfileobject::fgets' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::flock' => 
  array (
    0 => 'bool',
    'operation' => 'int',
    '&wouldBlock=' => 'mixed',
  ),
  'spltempfileobject::fpassthru' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::fputcsv' => 
  array (
    0 => 'false|int',
    'fields' => 'array<array-key, mixed>',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
    'eol=' => 'string',
  ),
  'spltempfileobject::fread' => 
  array (
    0 => 'false|string',
    'length' => 'int',
  ),
  'spltempfileobject::fscanf' => 
  array (
    0 => 'array<array-key, mixed>|int|null',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'spltempfileobject::fseek' => 
  array (
    0 => 'int',
    'offset' => 'int',
    'whence=' => 'int',
  ),
  'spltempfileobject::fstat' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'spltempfileobject::ftell' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::ftruncate' => 
  array (
    0 => 'bool',
    'size' => 'int',
  ),
  'spltempfileobject::fwrite' => 
  array (
    0 => 'false|int',
    'data' => 'string',
    'length=' => 'int|null',
  ),
  'spltempfileobject::getatime' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getbasename' => 
  array (
    0 => 'string',
    'suffix=' => 'string',
  ),
  'spltempfileobject::getchildren' => 
  array (
    0 => 'null',
  ),
  'spltempfileobject::getcsvcontrol' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'spltempfileobject::getctime' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getcurrentline' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getextension' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getfileinfo' => 
  array (
    0 => 'SplFileInfo',
    'class=' => 'null|string',
  ),
  'spltempfileobject::getfilename' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getflags' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::getgroup' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getinode' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getlinktarget' => 
  array (
    0 => 'false|string',
  ),
  'spltempfileobject::getmaxlinelen' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::getmtime' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getowner' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getpath' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getpathinfo' => 
  array (
    0 => 'SplFileInfo|null',
    'class=' => 'null|string',
  ),
  'spltempfileobject::getpathname' => 
  array (
    0 => 'string',
  ),
  'spltempfileobject::getperms' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::getrealpath' => 
  array (
    0 => 'false|string',
  ),
  'spltempfileobject::getsize' => 
  array (
    0 => 'false|int',
  ),
  'spltempfileobject::gettype' => 
  array (
    0 => 'false|string',
  ),
  'spltempfileobject::haschildren' => 
  array (
    0 => 'false',
  ),
  'spltempfileobject::isdir' => 
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
  'spltempfileobject::islink' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::isreadable' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::iswritable' => 
  array (
    0 => 'bool',
  ),
  'spltempfileobject::key' => 
  array (
    0 => 'int',
  ),
  'spltempfileobject::next' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::openfile' => 
  array (
    0 => 'SplFileObject',
    'mode=' => 'string',
    'useIncludePath=' => 'bool',
    'context=' => 'mixed',
  ),
  'spltempfileobject::rewind' => 
  array (
    0 => 'void',
  ),
  'spltempfileobject::seek' => 
  array (
    0 => 'void',
    'line' => 'int',
  ),
  'spltempfileobject::setcsvcontrol' => 
  array (
    0 => 'void',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'spltempfileobject::setfileclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'spltempfileobject::setflags' => 
  array (
    0 => 'void',
    'flags' => 'int',
  ),
  'spltempfileobject::setinfoclass' => 
  array (
    0 => 'void',
    'class=' => 'string',
  ),
  'spltempfileobject::setmaxlinelen' => 
  array (
    0 => 'void',
    'maxLength' => 'int',
  ),
  'spltempfileobject::valid' => 
  array (
    0 => 'bool',
  ),
  'spoofchecker::__construct' => 
  array (
    0 => 'void',
  ),
  'spoofchecker::areconfusable' => 
  array (
    0 => 'bool',
    'string1' => 'string',
    'string2' => 'string',
    '&errorCode=' => 'mixed',
  ),
  'spoofchecker::issuspicious' => 
  array (
    0 => 'bool',
    'string' => 'string',
    '&errorCode=' => 'mixed',
  ),
  'spoofchecker::setallowedchars' => 
  array (
    0 => 'void',
    'pattern' => 'string',
    'patternOptions=' => 'int',
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
  'sprintf' => 
  array (
    0 => 'string',
    'format' => 'string',
    '...values=' => 'mixed',
  ),
  'sqlite3::__construct' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
  ),
  'sqlite3::backup' => 
  array (
    0 => 'bool',
    'destination' => 'SQLite3',
    'sourceDatabase=' => 'string',
    'destinationDatabase=' => 'string',
  ),
  'sqlite3::busytimeout' => 
  array (
    0 => 'bool',
    'milliseconds' => 'int',
  ),
  'sqlite3::changes' => 
  array (
    0 => 'int',
  ),
  'sqlite3::close' => 
  array (
    0 => 'bool',
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
  'sqlite3::createfunction' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'callback' => 'callable',
    'argCount=' => 'int',
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
  'sqlite3::escapestring' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'sqlite3::exec' => 
  array (
    0 => 'bool',
    'query' => 'string',
  ),
  'sqlite3::lasterrorcode' => 
  array (
    0 => 'int',
  ),
  'sqlite3::lasterrormsg' => 
  array (
    0 => 'string',
  ),
  'sqlite3::lastextendederrorcode' => 
  array (
    0 => 'int',
  ),
  'sqlite3::lastinsertrowid' => 
  array (
    0 => 'int',
  ),
  'sqlite3::loadextension' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'sqlite3::open' => 
  array (
    0 => 'void',
    'filename' => 'string',
    'flags=' => 'int',
    'encryptionKey=' => 'string',
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
  'sqlite3::prepare' => 
  array (
    0 => 'SQLite3Stmt|false',
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
  'sqlite3::setauthorizer' => 
  array (
    0 => 'bool',
    'callback' => 'callable|null',
  ),
  'sqlite3::version' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'sqlite3exception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'sqlite3exception::__tostring' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::__wakeup' => 
  array (
    0 => 'void',
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
  'sqlite3exception::getmessage' => 
  array (
    0 => 'string',
  ),
  'sqlite3exception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'sqlite3exception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'sqlite3exception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'sqlite3result::__construct' => 
  array (
    0 => 'void',
  ),
  'sqlite3result::columnname' => 
  array (
    0 => 'false|string',
    'column' => 'int',
  ),
  'sqlite3result::columntype' => 
  array (
    0 => 'false|int',
    'column' => 'int',
  ),
  'sqlite3result::fetchall' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'mode=' => 'int',
  ),
  'sqlite3result::fetcharray' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'mode=' => 'int',
  ),
  'sqlite3result::finalize' => 
  array (
    0 => 'true',
  ),
  'sqlite3result::numcolumns' => 
  array (
    0 => 'int',
  ),
  'sqlite3result::reset' => 
  array (
    0 => 'bool',
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
    'param' => 'int|string',
    '&var' => 'mixed',
    'type=' => 'int',
  ),
  'sqlite3stmt::bindvalue' => 
  array (
    0 => 'bool',
    'param' => 'int|string',
    'value' => 'mixed',
    'type=' => 'int',
  ),
  'sqlite3stmt::busy' => 
  array (
    0 => 'bool',
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
  'sqlite3stmt::explain' => 
  array (
    0 => 'int',
  ),
  'sqlite3stmt::getsql' => 
  array (
    0 => 'false|string',
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
  'sqlite3stmt::setexplain' => 
  array (
    0 => 'bool',
    'mode' => 'int',
  ),
  'sqrt' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'srand' => 
  array (
    0 => 'void',
    'seed=' => 'int|null',
    'mode=' => 'int',
  ),
  'sscanf' => 
  array (
    0 => 'array<array-key, mixed>|int|null',
    'string' => 'string',
    'format' => 'string',
    '&...vars=' => 'mixed',
  ),
  'stat' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'filename' => 'string',
  ),
  'str_contains' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'str_decrement' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_ends_with' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'str_getcsv' => 
  array (
    0 => 'array<array-key, mixed>',
    'string' => 'string',
    'separator=' => 'string',
    'enclosure=' => 'string',
    'escape=' => 'string',
  ),
  'str_increment' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'str_ireplace' => 
  array (
    0 => 'array<array-key, mixed>|string',
    'search' => 'array<array-key, mixed>|string',
    'replace' => 'array<array-key, mixed>|string',
    'subject' => 'array<array-key, mixed>|string',
    '&count=' => 'mixed',
  ),
  'str_pad' => 
  array (
    0 => 'string',
    'string' => 'string',
    'length' => 'int',
    'pad_string=' => 'string',
    'pad_type=' => 'int',
  ),
  'str_repeat' => 
  array (
    0 => 'string',
    'string' => 'string',
    'times' => 'int',
  ),
  'str_replace' => 
  array (
    0 => 'array<array-key, mixed>|string',
    'search' => 'array<array-key, mixed>|string',
    'replace' => 'array<array-key, mixed>|string',
    'subject' => 'array<array-key, mixed>|string',
    '&count=' => 'mixed',
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
  'str_split' => 
  array (
    0 => 'array<array-key, mixed>',
    'string' => 'string',
    'length=' => 'int',
  ),
  'str_starts_with' => 
  array (
    0 => 'bool',
    'haystack' => 'string',
    'needle' => 'string',
  ),
  'str_word_count' => 
  array (
    0 => 'array<array-key, mixed>|int',
    'string' => 'string',
    'format=' => 'int',
    'characters=' => 'null|string',
  ),
  'strcasecmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strchr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strcmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strcoll' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strcspn' => 
  array (
    0 => 'int',
    'string' => 'string',
    'characters' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'stream_bucket_append' => 
  array (
    0 => 'void',
    'brigade' => 'mixed',
    'bucket' => 'StreamBucket',
  ),
  'stream_bucket_make_writeable' => 
  array (
    0 => 'StreamBucket|null',
    'brigade' => 'mixed',
  ),
  'stream_bucket_new' => 
  array (
    0 => 'StreamBucket',
    'stream' => 'mixed',
    'buffer' => 'string',
  ),
  'stream_bucket_prepend' => 
  array (
    0 => 'void',
    'brigade' => 'mixed',
    'bucket' => 'StreamBucket',
  ),
  'stream_context_create' => 
  array (
    0 => 'mixed',
    'options=' => 'array<array-key, mixed>|null',
    'params=' => 'array<array-key, mixed>|null',
  ),
  'stream_context_get_default' => 
  array (
    0 => 'mixed',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'stream_context_get_options' => 
  array (
    0 => 'array<array-key, mixed>',
    'stream_or_context' => 'mixed',
  ),
  'stream_context_get_params' => 
  array (
    0 => 'array<array-key, mixed>',
    'context' => 'mixed',
  ),
  'stream_context_set_default' => 
  array (
    0 => 'mixed',
    'options' => 'array<array-key, mixed>',
  ),
  'stream_context_set_option' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'wrapper_or_options' => 'array<array-key, mixed>|string',
    'option_name=' => 'null|string',
    'value=' => 'mixed',
  ),
  'stream_context_set_options' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'options' => 'array<array-key, mixed>',
  ),
  'stream_context_set_params' => 
  array (
    0 => 'true',
    'context' => 'mixed',
    'params' => 'array<array-key, mixed>',
  ),
  'stream_copy_to_stream' => 
  array (
    0 => 'false|int',
    'from' => 'mixed',
    'to' => 'mixed',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_filter_append' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed',
  ),
  'stream_filter_prepend' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'filter_name' => 'string',
    'mode=' => 'int',
    'params=' => 'mixed',
  ),
  'stream_filter_register' => 
  array (
    0 => 'bool',
    'filter_name' => 'string',
    'class' => 'string',
  ),
  'stream_filter_remove' => 
  array (
    0 => 'bool',
    'stream_filter' => 'mixed',
  ),
  'stream_get_contents' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length=' => 'int|null',
    'offset=' => 'int',
  ),
  'stream_get_filters' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'stream_get_line' => 
  array (
    0 => 'false|string',
    'stream' => 'mixed',
    'length' => 'int',
    'ending=' => 'string',
  ),
  'stream_get_meta_data' => 
  array (
    0 => 'array<array-key, mixed>',
    'stream' => 'mixed',
  ),
  'stream_get_transports' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'stream_get_wrappers' => 
  array (
    0 => 'array<array-key, mixed>',
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
  'stream_register_wrapper' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
    'class' => 'string',
    'flags=' => 'int',
  ),
  'stream_resolve_include_path' => 
  array (
    0 => 'false|string',
    'filename' => 'string',
  ),
  'stream_select' => 
  array (
    0 => 'false|int',
    '&read' => 'array<array-key, mixed>|null',
    '&write' => 'array<array-key, mixed>|null',
    '&except' => 'array<array-key, mixed>|null',
    'seconds' => 'int|null',
    'microseconds=' => 'int|null',
  ),
  'stream_set_blocking' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'enable' => 'bool',
  ),
  'stream_set_chunk_size' => 
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
  'stream_set_timeout' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'seconds' => 'int',
    'microseconds=' => 'int',
  ),
  'stream_set_write_buffer' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'size' => 'int',
  ),
  'stream_socket_accept' => 
  array (
    0 => 'mixed',
    'socket' => 'mixed',
    'timeout=' => 'float|null',
    '&peer_name=' => 'mixed',
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
  'stream_socket_enable_crypto' => 
  array (
    0 => 'bool|int',
    'stream' => 'mixed',
    'enable' => 'bool',
    'crypto_method=' => 'int|null',
    'session_stream=' => 'mixed',
  ),
  'stream_socket_get_name' => 
  array (
    0 => 'false|string',
    'socket' => 'mixed',
    'remote' => 'bool',
  ),
  'stream_socket_pair' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'domain' => 'int',
    'type' => 'int',
    'protocol' => 'int',
  ),
  'stream_socket_recvfrom' => 
  array (
    0 => 'false|string',
    'socket' => 'mixed',
    'length' => 'int',
    'flags=' => 'int',
    '&address=' => 'mixed',
  ),
  'stream_socket_sendto' => 
  array (
    0 => 'false|int',
    'socket' => 'mixed',
    'data' => 'string',
    'flags=' => 'int',
    'address=' => 'string',
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
  'stream_socket_shutdown' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
    'mode' => 'int',
  ),
  'stream_supports_lock' => 
  array (
    0 => 'bool',
    'stream' => 'mixed',
  ),
  'stream_wrapper_register' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
    'class' => 'string',
    'flags=' => 'int',
  ),
  'stream_wrapper_restore' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
  ),
  'stream_wrapper_unregister' => 
  array (
    0 => 'bool',
    'protocol' => 'string',
  ),
  'strftime' => 
  array (
    0 => 'false|string',
    'format' => 'string',
    'timestamp=' => 'int|null',
  ),
  'strip_tags' => 
  array (
    0 => 'string',
    'string' => 'string',
    'allowed_tags=' => 'array<array-key, mixed>|null|string',
  ),
  'stripcslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'stripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'stripslashes' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'stristr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strlen' => 
  array (
    0 => 'int',
    'string' => 'string',
  ),
  'strnatcasecmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
  ),
  'strnatcmp' => 
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
  'strncmp' => 
  array (
    0 => 'int',
    'string1' => 'string',
    'string2' => 'string',
    'length' => 'int',
  ),
  'strpbrk' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'characters' => 'string',
  ),
  'strpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strptime' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'timestamp' => 'string',
    'format' => 'string',
  ),
  'strrchr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strrev' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'strripos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strrpos' => 
  array (
    0 => 'false|int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
  ),
  'strspn' => 
  array (
    0 => 'int',
    'string' => 'string',
    'characters' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'strstr' => 
  array (
    0 => 'false|string',
    'haystack' => 'string',
    'needle' => 'string',
    'before_needle=' => 'bool',
  ),
  'strtok' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'token=' => 'null|string',
  ),
  'strtolower' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'strtotime' => 
  array (
    0 => 'false|int',
    'datetime' => 'string',
    'baseTimestamp=' => 'int|null',
  ),
  'strtoupper' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'strtr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'from' => 'array<array-key, mixed>|string',
    'to=' => 'null|string',
  ),
  'strval' => 
  array (
    0 => 'string',
    'value' => 'mixed',
  ),
  'substr' => 
  array (
    0 => 'string',
    'string' => 'string',
    'offset' => 'int',
    'length=' => 'int|null',
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
  'substr_count' => 
  array (
    0 => 'int',
    'haystack' => 'string',
    'needle' => 'string',
    'offset=' => 'int',
    'length=' => 'int|null',
  ),
  'substr_replace' => 
  array (
    0 => 'array<array-key, mixed>|string',
    'string' => 'array<array-key, mixed>|string',
    'replace' => 'array<array-key, mixed>|string',
    'offset' => 'array<array-key, mixed>|int',
    'length=' => 'array<array-key, mixed>|int|null',
  ),
  'symlink' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'link' => 'string',
  ),
  'sys_get_temp_dir' => 
  array (
    0 => 'string',
  ),
  'sys_getloadavg' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'syslog' => 
  array (
    0 => 'true',
    'priority' => 'int',
    'message' => 'string',
  ),
  'system' => 
  array (
    0 => 'false|string',
    'command' => 'string',
    '&result_code=' => 'mixed',
  ),
  'tan' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'tanh' => 
  array (
    0 => 'float',
    'num' => 'float',
  ),
  'tempnam' => 
  array (
    0 => 'false|string',
    'directory' => 'string',
    'prefix' => 'string',
  ),
  'time' => 
  array (
    0 => 'int',
  ),
  'time_nanosleep' => 
  array (
    0 => 'array<array-key, mixed>|bool',
    'seconds' => 'int',
    'nanoseconds' => 'int',
  ),
  'time_sleep_until' => 
  array (
    0 => 'bool',
    'timestamp' => 'float',
  ),
  'timezone_abbreviations_list' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'timezone_identifiers_list' => 
  array (
    0 => 'array<array-key, mixed>',
    'timezoneGroup=' => 'int',
    'countryCode=' => 'null|string',
  ),
  'timezone_location_get' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'object' => 'DateTimeZone',
  ),
  'timezone_name_from_abbr' => 
  array (
    0 => 'false|string',
    'abbr' => 'string',
    'utcOffset=' => 'int',
    'isDST=' => 'int',
  ),
  'timezone_name_get' => 
  array (
    0 => 'string',
    'object' => 'DateTimeZone',
  ),
  'timezone_offset_get' => 
  array (
    0 => 'int',
    'object' => 'DateTimeZone',
    'datetime' => 'DateTimeInterface',
  ),
  'timezone_open' => 
  array (
    0 => 'DateTimeZone|false',
    'timezone' => 'string',
  ),
  'timezone_transitions_get' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'object' => 'DateTimeZone',
    'timestampBegin=' => 'int',
    'timestampEnd=' => 'int',
  ),
  'timezone_version_get' => 
  array (
    0 => 'string',
  ),
  'tmpfile' => 
  array (
    0 => 'mixed',
  ),
  'token_get_all' => 
  array (
    0 => 'array<array-key, mixed>',
    'code' => 'string',
    'flags=' => 'int',
  ),
  'token_name' => 
  array (
    0 => 'string',
    'id' => 'int',
  ),
  'touch' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'mtime=' => 'int|null',
    'atime=' => 'int|null',
  ),
  'trait_exists' => 
  array (
    0 => 'bool',
    'trait' => 'string',
    'autoload=' => 'bool',
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
  'transliterator::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'transliterator::geterrormessage' => 
  array (
    0 => 'string',
  ),
  'transliterator::listids' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'transliterator::transliterate' => 
  array (
    0 => 'false|string',
    'string' => 'string',
    'start=' => 'int',
    'end=' => 'int',
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
  'transliterator_create_inverse' => 
  array (
    0 => 'Transliterator|null',
    'transliterator' => 'Transliterator',
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
  'transliterator_list_ids' => 
  array (
    0 => 'array<array-key, mixed>|false',
  ),
  'transliterator_transliterate' => 
  array (
    0 => 'false|string',
    'transliterator' => 'Transliterator|string',
    'string' => 'string',
    'start=' => 'int',
    'end=' => 'int',
  ),
  'trigger_error' => 
  array (
    0 => 'true',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'trim' => 
  array (
    0 => 'string',
    'string' => 'string',
    'characters=' => 'string',
  ),
  'typeerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'typeerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'typeerror::__wakeup' => 
  array (
    0 => 'void',
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
  'typeerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'typeerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'typeerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'typeerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uasort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'ucfirst' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'uconverter::__construct' => 
  array (
    0 => 'void',
    'destination_encoding=' => 'null|string',
    'source_encoding=' => 'null|string',
  ),
  'uconverter::convert' => 
  array (
    0 => 'false|string',
    'str' => 'string',
    'reverse=' => 'bool',
  ),
  'uconverter::fromucallback' => 
  array (
    0 => 'array<array-key, mixed>|int|null|string',
    'reason' => 'int',
    'source' => 'array<array-key, mixed>',
    'codePoint' => 'int',
    '&error' => 'mixed',
  ),
  'uconverter::getaliases' => 
  array (
    0 => 'array<array-key, mixed>|false|null',
    'name' => 'string',
  ),
  'uconverter::getavailable' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uconverter::getdestinationencoding' => 
  array (
    0 => 'false|null|string',
  ),
  'uconverter::getdestinationtype' => 
  array (
    0 => 'false|int|null',
  ),
  'uconverter::geterrorcode' => 
  array (
    0 => 'int',
  ),
  'uconverter::geterrormessage' => 
  array (
    0 => 'null|string',
  ),
  'uconverter::getsourceencoding' => 
  array (
    0 => 'false|null|string',
  ),
  'uconverter::getsourcetype' => 
  array (
    0 => 'false|int|null',
  ),
  'uconverter::getstandards' => 
  array (
    0 => 'array<array-key, mixed>|null',
  ),
  'uconverter::getsubstchars' => 
  array (
    0 => 'false|null|string',
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
    0 => 'array<array-key, mixed>|int|null|string',
    'reason' => 'int',
    'source' => 'string',
    'codeUnits' => 'string',
    '&error' => 'mixed',
  ),
  'uconverter::transcode' => 
  array (
    0 => 'false|string',
    'str' => 'string',
    'toEncoding' => 'string',
    'fromEncoding' => 'string',
    'options=' => 'array<array-key, mixed>|null',
  ),
  'ucwords' => 
  array (
    0 => 'string',
    'string' => 'string',
    'separators=' => 'string',
  ),
  'uksort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'umask' => 
  array (
    0 => 'int',
    'mask=' => 'int|null',
  ),
  'underflowexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'underflowexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'underflowexception::__wakeup' => 
  array (
    0 => 'void',
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
  'underflowexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'underflowexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'underflowexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'underflowexception::gettraceasstring' => 
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
  'unexpectedvalueexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::__wakeup' => 
  array (
    0 => 'void',
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
  'unexpectedvalueexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'unexpectedvalueexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'unexpectedvalueexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'unexpectedvalueexception::gettraceasstring' => 
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
  'unhandledmatcherror::__tostring' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::__wakeup' => 
  array (
    0 => 'void',
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
  'unhandledmatcherror::getmessage' => 
  array (
    0 => 'string',
  ),
  'unhandledmatcherror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'unhandledmatcherror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'unhandledmatcherror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uniqid' => 
  array (
    0 => 'string',
    'prefix=' => 'string',
    'more_entropy=' => 'bool',
  ),
  'unlink' => 
  array (
    0 => 'bool',
    'filename' => 'string',
    'context=' => 'mixed',
  ),
  'unpack' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'format' => 'string',
    'string' => 'string',
    'offset=' => 'int',
  ),
  'unregister_tick_function' => 
  array (
    0 => 'void',
    'callback' => 'callable',
  ),
  'unserialize' => 
  array (
    0 => 'mixed',
    'data' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'uri\\invaliduriexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\invaliduriexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::__wakeup' => 
  array (
    0 => 'void',
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
  'uri\\invaliduriexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\invaliduriexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\invaliduriexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\invaliduriexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::__construct' => 
  array (
    0 => 'void',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\Rfc3986\\Uri|null',
  ),
  'uri\\rfc3986\\uri::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\rfc3986\\uri::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\rfc3986\\uri::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'uri\\rfc3986\\uri::equals' => 
  array (
    0 => 'bool',
    'uri' => 'Uri\\Rfc3986\\Uri',
    'comparisonMode=' => 'Uri\\UriComparisonMode',
  ),
  'uri\\rfc3986\\uri::getfragment' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::gethost' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getpassword' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getpath' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::getport' => 
  array (
    0 => 'int|null',
  ),
  'uri\\rfc3986\\uri::getquery' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawfragment' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawhost' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawpassword' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawpath' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::getrawquery' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawscheme' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawuserinfo' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getrawusername' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getscheme' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getuserinfo' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::getusername' => 
  array (
    0 => 'null|string',
  ),
  'uri\\rfc3986\\uri::parse' => 
  array (
    0 => 'null|static',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\Rfc3986\\Uri|null',
  ),
  'uri\\rfc3986\\uri::resolve' => 
  array (
    0 => 'static',
    'uri' => 'string',
  ),
  'uri\\rfc3986\\uri::torawstring' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\rfc3986\\uri::withfragment' => 
  array (
    0 => 'static',
    'fragment' => 'null|string',
  ),
  'uri\\rfc3986\\uri::withhost' => 
  array (
    0 => 'static',
    'host' => 'null|string',
  ),
  'uri\\rfc3986\\uri::withpath' => 
  array (
    0 => 'static',
    'path' => 'string',
  ),
  'uri\\rfc3986\\uri::withport' => 
  array (
    0 => 'static',
    'port' => 'int|null',
  ),
  'uri\\rfc3986\\uri::withquery' => 
  array (
    0 => 'static',
    'query' => 'null|string',
  ),
  'uri\\rfc3986\\uri::withscheme' => 
  array (
    0 => 'static',
    'scheme' => 'null|string',
  ),
  'uri\\rfc3986\\uri::withuserinfo' => 
  array (
    0 => 'static',
    'userinfo' => 'null|string',
  ),
  'uri\\uricomparisonmode::cases' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\urierror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\urierror::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::__wakeup' => 
  array (
    0 => 'void',
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
  'uri\\urierror::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\urierror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\urierror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\urierror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\uriexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::__wakeup' => 
  array (
    0 => 'void',
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
  'uri\\uriexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\uriexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\uriexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\uriexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'errors=' => 'array<array-key, mixed>',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'uri\\whatwg\\invalidurlexception::__tostring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::__wakeup' => 
  array (
    0 => 'void',
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
  'uri\\whatwg\\invalidurlexception::getmessage' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\invalidurlexception::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'uri\\whatwg\\invalidurlexception::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\whatwg\\invalidurlexception::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::__construct' => 
  array (
    0 => 'void',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\WhatWg\\Url|null',
    '&softErrors=' => 'mixed',
  ),
  'uri\\whatwg\\url::__debuginfo' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\whatwg\\url::__serialize' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'uri\\whatwg\\url::__unserialize' => 
  array (
    0 => 'void',
    'data' => 'array<array-key, mixed>',
  ),
  'uri\\whatwg\\url::equals' => 
  array (
    0 => 'bool',
    'url' => 'Uri\\WhatWg\\Url',
    'comparisonMode=' => 'Uri\\UriComparisonMode',
  ),
  'uri\\whatwg\\url::getasciihost' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::getfragment' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::getpassword' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::getpath' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::getport' => 
  array (
    0 => 'int|null',
  ),
  'uri\\whatwg\\url::getquery' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::getscheme' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::getunicodehost' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::getusername' => 
  array (
    0 => 'null|string',
  ),
  'uri\\whatwg\\url::parse' => 
  array (
    0 => 'null|static',
    'uri' => 'string',
    'baseUrl=' => 'Uri\\WhatWg\\Url|null',
    '&errors=' => 'mixed',
  ),
  'uri\\whatwg\\url::resolve' => 
  array (
    0 => 'static',
    'uri' => 'string',
    '&softErrors=' => 'mixed',
  ),
  'uri\\whatwg\\url::toasciistring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::tounicodestring' => 
  array (
    0 => 'string',
  ),
  'uri\\whatwg\\url::withfragment' => 
  array (
    0 => 'static',
    'fragment' => 'null|string',
  ),
  'uri\\whatwg\\url::withhost' => 
  array (
    0 => 'static',
    'host' => 'null|string',
  ),
  'uri\\whatwg\\url::withpassword' => 
  array (
    0 => 'static',
    'password' => 'null|string',
  ),
  'uri\\whatwg\\url::withpath' => 
  array (
    0 => 'static',
    'path' => 'string',
  ),
  'uri\\whatwg\\url::withport' => 
  array (
    0 => 'static',
    'port' => 'int|null',
  ),
  'uri\\whatwg\\url::withquery' => 
  array (
    0 => 'static',
    'query' => 'null|string',
  ),
  'uri\\whatwg\\url::withscheme' => 
  array (
    0 => 'static',
    'scheme' => 'string',
  ),
  'uri\\whatwg\\url::withusername' => 
  array (
    0 => 'static',
    'username' => 'null|string',
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
    0 => 'array<array-key, mixed>',
  ),
  'urldecode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'urlencode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'use_soap_error_handler' => 
  array (
    0 => 'bool',
    'enable=' => 'bool',
  ),
  'user_error' => 
  array (
    0 => 'true',
    'message' => 'string',
    'error_level=' => 'int',
  ),
  'usleep' => 
  array (
    0 => 'void',
    'microseconds' => 'int',
  ),
  'usort' => 
  array (
    0 => 'true',
    '&array' => 'array<array-key, mixed>',
    'callback' => 'callable',
  ),
  'utf8_decode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'utf8_encode' => 
  array (
    0 => 'string',
    'string' => 'string',
  ),
  'uv_accept' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'client' => 'mixed',
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
  'uv_chdir' => 
  array (
    0 => 'mixed',
    'dir' => 'mixed',
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
  'uv_close' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_cpu_info' => 
  array (
    0 => 'mixed',
  ),
  'uv_cwd' => 
  array (
    0 => 'mixed',
  ),
  'uv_default_loop' => 
  array (
    0 => 'mixed',
  ),
  'uv_err_name' => 
  array (
    0 => 'mixed',
    'error' => 'mixed',
  ),
  'uv_exepath' => 
  array (
    0 => 'mixed',
  ),
  'uv_fs_chmod' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
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
  'uv_fs_close' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
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
  'uv_fs_fchmod' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'mode' => 'mixed',
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
  'uv_fs_fdatasync' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_fstat' => 
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
  'uv_fs_ftruncate' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'offset' => 'mixed',
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
  'uv_fs_link' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'from' => 'mixed',
    'to' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_fs_lstat' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
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
  'uv_fs_open' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
    'flag' => 'mixed',
    'mode' => 'mixed',
    'callback=' => 'mixed',
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
  'uv_fs_read' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'offset=' => 'mixed',
    'size=' => 'mixed',
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
  'uv_fs_readlink' => 
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
  'uv_fs_rmdir' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
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
  'uv_fs_stat' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
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
  'uv_fs_unlink' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'path' => 'mixed',
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
  'uv_fs_write' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'buffer' => 'mixed',
    'offset' => 'mixed',
    'callback=' => 'mixed',
  ),
  'uv_get_free_memory' => 
  array (
    0 => 'mixed',
  ),
  'uv_get_total_memory' => 
  array (
    0 => 'mixed',
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
  'uv_guess_handle' => 
  array (
    0 => 'mixed',
    'fd' => 'mixed',
  ),
  'uv_hrtime' => 
  array (
    0 => 'mixed',
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
  'uv_interface_addresses' => 
  array (
    0 => 'mixed',
  ),
  'uv_ip4_addr' => 
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
  'uv_ip6_addr' => 
  array (
    0 => 'mixed',
    'address' => 'mixed',
    'port' => 'mixed',
  ),
  'uv_ip6_name' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
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
  'uv_kill' => 
  array (
    0 => 'mixed',
    'pid' => 'mixed',
    'signal' => 'mixed',
  ),
  'uv_listen' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'backlog' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_loadavg' => 
  array (
    0 => 'mixed',
  ),
  'uv_loop_delete' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_loop_new' => 
  array (
    0 => 'mixed',
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
  'uv_now' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_pipe_bind' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'name' => 'mixed',
  ),
  'uv_pipe_connect' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'name' => 'mixed',
    'callback' => 'mixed',
  ),
  'uv_pipe_init' => 
  array (
    0 => 'mixed',
    'file=' => 'mixed',
    'ipc=' => 'mixed',
  ),
  'uv_pipe_open' => 
  array (
    0 => 'mixed',
    'file' => 'mixed',
    'pipe' => 'mixed',
  ),
  'uv_pipe_pending_count' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_pipe_pending_instances' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'count' => 'mixed',
  ),
  'uv_pipe_pending_type' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
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
  'uv_process_get_pid' => 
  array (
    0 => 'mixed',
    'process' => 'mixed',
  ),
  'uv_process_kill' => 
  array (
    0 => 'mixed',
    'process' => 'mixed',
    'signal' => 'mixed',
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
  'uv_ref' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_resident_set_memory' => 
  array (
    0 => 'mixed',
  ),
  'uv_run' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
    'run_mode=' => 'mixed',
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
  'uv_rwlock_rdunlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_tryrdlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_trywrlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_wrlock' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_rwlock_wrunlock' => 
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
  'uv_sem_trywait' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
  ),
  'uv_sem_wait' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
  ),
  'uv_shutdown' => 
  array (
    0 => 'mixed',
    'stream' => 'mixed',
    'callback' => 'mixed',
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
  'uv_stdio_new' => 
  array (
    0 => 'mixed',
  ),
  'uv_stop' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_strerror' => 
  array (
    0 => 'mixed',
    'error' => 'mixed',
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
  'uv_tcp_getpeername' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_tcp_getsockname' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
  ),
  'uv_tcp_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_tcp_nodelay' => 
  array (
    0 => 'mixed',
    'tcp' => 'mixed',
    'enabled' => 'mixed',
  ),
  'uv_tcp_open' => 
  array (
    0 => 'mixed',
    'resource' => 'mixed',
    'tcpfd' => 'mixed',
  ),
  'uv_tcp_simultaneous_accepts' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
    'enable' => 'mixed',
  ),
  'uv_timer_again' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
  ),
  'uv_timer_get_repeat' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
  ),
  'uv_timer_init' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_timer_set_repeat' => 
  array (
    0 => 'mixed',
    'timer' => 'mixed',
    'timeout' => 'mixed',
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
  'uv_tty_get_winsize' => 
  array (
    0 => 'mixed',
    'tty' => 'mixed',
    '&width' => 'mixed',
    '&height' => 'mixed',
  ),
  'uv_tty_init' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'fd' => 'mixed',
    'readable' => 'mixed',
  ),
  'uv_tty_reset_mode' => 
  array (
    0 => 'mixed',
  ),
  'uv_tty_set_mode' => 
  array (
    0 => 'mixed',
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
  'uv_udp_getsockname' => 
  array (
    0 => 'mixed',
    'handle' => 'mixed',
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
  'uv_udp_set_broadcast' => 
  array (
    0 => 'mixed',
    'server' => 'mixed',
    'enabled' => 'mixed',
  ),
  'uv_udp_set_membership' => 
  array (
    0 => 'mixed',
    'client' => 'mixed',
    'multicast_addr' => 'mixed',
    'interface_addr' => 'mixed',
    'membership' => 'mixed',
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
  'uv_unref' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
  ),
  'uv_update_time' => 
  array (
    0 => 'mixed',
    'loop=' => 'mixed',
  ),
  'uv_uptime' => 
  array (
    0 => 'mixed',
  ),
  'uv_walk' => 
  array (
    0 => 'mixed',
    'loop' => 'mixed',
    'callback' => 'mixed',
    'opaque=' => 'mixed',
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
  'valueerror::__construct' => 
  array (
    0 => 'void',
    'message=' => 'string',
    'code=' => 'int',
    'previous=' => 'Throwable|null',
  ),
  'valueerror::__tostring' => 
  array (
    0 => 'string',
  ),
  'valueerror::__wakeup' => 
  array (
    0 => 'void',
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
  'valueerror::getmessage' => 
  array (
    0 => 'string',
  ),
  'valueerror::getprevious' => 
  array (
    0 => 'Throwable|null',
  ),
  'valueerror::gettrace' => 
  array (
    0 => 'array<array-key, mixed>',
  ),
  'valueerror::gettraceasstring' => 
  array (
    0 => 'string',
  ),
  'var_dump' => 
  array (
    0 => 'void',
    'value' => 'mixed',
    '...values=' => 'mixed',
  ),
  'var_export' => 
  array (
    0 => 'null|string',
    'value' => 'mixed',
    'return=' => 'bool',
  ),
  'version_compare' => 
  array (
    0 => 'bool|int',
    'version1' => 'string',
    'version2' => 'string',
    'operator=' => 'null|string',
  ),
  'vfprintf' => 
  array (
    0 => 'int',
    'stream' => 'mixed',
    'format' => 'string',
    'values' => 'array<array-key, mixed>',
  ),
  'vprintf' => 
  array (
    0 => 'int',
    'format' => 'string',
    'values' => 'array<array-key, mixed>',
  ),
  'vsprintf' => 
  array (
    0 => 'string',
    'format' => 'string',
    'values' => 'array<array-key, mixed>',
  ),
  'weakmap::count' => 
  array (
    0 => 'int',
  ),
  'weakmap::getiterator' => 
  array (
    0 => 'Iterator',
  ),
  'weakmap::offsetexists' => 
  array (
    0 => 'bool',
    'object' => 'mixed',
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
  'weakmap::offsetunset' => 
  array (
    0 => 'void',
    'object' => 'mixed',
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
    0 => 'null|object',
  ),
  'wordwrap' => 
  array (
    0 => 'string',
    'string' => 'string',
    'width=' => 'int',
    'break=' => 'string',
    'cut_long_words=' => 'bool',
  ),
  'xml_error_string' => 
  array (
    0 => 'null|string',
    'error_code' => 'int',
  ),
  'xml_get_current_byte_index' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_get_current_column_number' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_get_current_line_number' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
  ),
  'xml_get_error_code' => 
  array (
    0 => 'int',
    'parser' => 'XMLParser',
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
    0 => 'false|int',
    'parser' => 'XMLParser',
    'data' => 'string',
    '&values' => 'mixed',
    '&index=' => 'mixed',
  ),
  'xml_parser_create' => 
  array (
    0 => 'XMLParser',
    'encoding=' => 'null|string',
  ),
  'xml_parser_create_ns' => 
  array (
    0 => 'XMLParser',
    'encoding=' => 'null|string',
    'separator=' => 'string',
  ),
  'xml_parser_free' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
  ),
  'xml_parser_get_option' => 
  array (
    0 => 'bool|int|string',
    'parser' => 'XMLParser',
    'option' => 'int',
  ),
  'xml_parser_set_option' => 
  array (
    0 => 'bool',
    'parser' => 'XMLParser',
    'option' => 'int',
    'value' => 'mixed',
  ),
  'xml_set_character_data_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_default_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_element_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'start_handler' => 'callable|null|string',
    'end_handler' => 'callable|null|string',
  ),
  'xml_set_end_namespace_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_external_entity_ref_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_notation_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_object' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'object' => 'object',
  ),
  'xml_set_processing_instruction_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_start_namespace_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xml_set_unparsed_entity_decl_handler' => 
  array (
    0 => 'true',
    'parser' => 'XMLParser',
    'handler' => 'callable|null|string',
  ),
  'xmlreader::close' => 
  array (
    0 => 'true',
  ),
  'xmlreader::expand' => 
  array (
    0 => 'DOMNode|false',
    'baseNode=' => 'DOMNode|null',
  ),
  'xmlreader::fromstream' => 
  array (
    0 => 'static',
    'stream' => 'mixed',
    'encoding=' => 'null|string',
    'flags=' => 'int',
    'documentUri=' => 'null|string',
  ),
  'xmlreader::fromstring' => 
  array (
    0 => 'static',
    'source' => 'string',
    'encoding=' => 'null|string',
    'flags=' => 'int',
  ),
  'xmlreader::fromuri' => 
  array (
    0 => 'static',
    'uri' => 'string',
    'encoding=' => 'null|string',
    'flags=' => 'int',
  ),
  'xmlreader::getattribute' => 
  array (
    0 => 'null|string',
    'name' => 'string',
  ),
  'xmlreader::getattributeno' => 
  array (
    0 => 'null|string',
    'index' => 'int',
  ),
  'xmlreader::getattributens' => 
  array (
    0 => 'null|string',
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
    0 => 'null|string',
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
  'xmlreader::next' => 
  array (
    0 => 'bool',
    'name=' => 'null|string',
  ),
  'xmlreader::open' => 
  array (
    0 => 'mixed',
    'uri' => 'string',
    'encoding=' => 'null|string',
    'flags=' => 'int',
  ),
  'xmlreader::read' => 
  array (
    0 => 'bool',
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
  'xmlreader::setparserproperty' => 
  array (
    0 => 'bool',
    'property' => 'int',
    'value' => 'bool',
  ),
  'xmlreader::setrelaxngschema' => 
  array (
    0 => 'bool',
    'filename' => 'null|string',
  ),
  'xmlreader::setrelaxngschemasource' => 
  array (
    0 => 'bool',
    'source' => 'null|string',
  ),
  'xmlreader::setschema' => 
  array (
    0 => 'bool',
    'filename' => 'null|string',
  ),
  'xmlreader::xml' => 
  array (
    0 => 'mixed',
    'source' => 'string',
    'encoding=' => 'null|string',
    'flags=' => 'int',
  ),
  'xmlwriter::endattribute' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endcdata' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endcomment' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::enddocument' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::enddtd' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::enddtdattlist' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::enddtdelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::enddtdentity' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::endpi' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::flush' => 
  array (
    0 => 'int|string',
    'empty=' => 'bool',
  ),
  'xmlwriter::fullendelement' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::openmemory' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::openuri' => 
  array (
    0 => 'bool',
    'uri' => 'string',
  ),
  'xmlwriter::outputmemory' => 
  array (
    0 => 'string',
    'flush=' => 'bool',
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
  'xmlwriter::startattribute' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::startattributens' => 
  array (
    0 => 'bool',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
  ),
  'xmlwriter::startcdata' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::startcomment' => 
  array (
    0 => 'bool',
  ),
  'xmlwriter::startdocument' => 
  array (
    0 => 'bool',
    'version=' => 'null|string',
    'encoding=' => 'null|string',
    'standalone=' => 'null|string',
  ),
  'xmlwriter::startdtd' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
  ),
  'xmlwriter::startdtdattlist' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::startdtdelement' => 
  array (
    0 => 'bool',
    'qualifiedName' => 'string',
  ),
  'xmlwriter::startdtdentity' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'isParam' => 'bool',
  ),
  'xmlwriter::startelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
  ),
  'xmlwriter::startelementns' => 
  array (
    0 => 'bool',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
  ),
  'xmlwriter::startpi' => 
  array (
    0 => 'bool',
    'target' => 'string',
  ),
  'xmlwriter::text' => 
  array (
    0 => 'bool',
    'content' => 'string',
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
  'xmlwriter::touri' => 
  array (
    0 => 'static',
    'uri' => 'string',
  ),
  'xmlwriter::writeattribute' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'value' => 'string',
  ),
  'xmlwriter::writeattributens' => 
  array (
    0 => 'bool',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
    'value' => 'string',
  ),
  'xmlwriter::writecdata' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::writecomment' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter::writedtd' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
    'content=' => 'null|string',
  ),
  'xmlwriter::writedtdattlist' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::writedtdelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::writedtdentity' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
    'isParam=' => 'bool',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
    'notationData=' => 'null|string',
  ),
  'xmlwriter::writeelement' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content=' => 'null|string',
  ),
  'xmlwriter::writeelementns' => 
  array (
    0 => 'bool',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
    'content=' => 'null|string',
  ),
  'xmlwriter::writepi' => 
  array (
    0 => 'bool',
    'target' => 'string',
    'content' => 'string',
  ),
  'xmlwriter::writeraw' => 
  array (
    0 => 'bool',
    'content' => 'string',
  ),
  'xmlwriter_end_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_document' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_end_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_flush' => 
  array (
    0 => 'int|string',
    'writer' => 'XMLWriter',
    'empty=' => 'bool',
  ),
  'xmlwriter_full_end_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_open_memory' => 
  array (
    0 => 'XMLWriter|false',
  ),
  'xmlwriter_open_uri' => 
  array (
    0 => 'XMLWriter|false',
    'uri' => 'string',
  ),
  'xmlwriter_output_memory' => 
  array (
    0 => 'string',
    'writer' => 'XMLWriter',
    'flush=' => 'bool',
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
  'xmlwriter_start_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_start_attribute_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
  ),
  'xmlwriter_start_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_start_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
  ),
  'xmlwriter_start_document' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'version=' => 'null|string',
    'encoding=' => 'null|string',
    'standalone=' => 'null|string',
  ),
  'xmlwriter_start_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'qualifiedName' => 'string',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
  ),
  'xmlwriter_start_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_start_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'qualifiedName' => 'string',
  ),
  'xmlwriter_start_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'isParam' => 'bool',
  ),
  'xmlwriter_start_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
  ),
  'xmlwriter_start_element_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
  ),
  'xmlwriter_start_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'target' => 'string',
  ),
  'xmlwriter_text' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_write_attribute' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'value' => 'string',
  ),
  'xmlwriter_write_attribute_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
    'value' => 'string',
  ),
  'xmlwriter_write_cdata' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_write_comment' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'xmlwriter_write_dtd' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
    'content=' => 'null|string',
  ),
  'xmlwriter_write_dtd_attlist' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_write_dtd_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_write_dtd_entity' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content' => 'string',
    'isParam=' => 'bool',
    'publicId=' => 'null|string',
    'systemId=' => 'null|string',
    'notationData=' => 'null|string',
  ),
  'xmlwriter_write_element' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'name' => 'string',
    'content=' => 'null|string',
  ),
  'xmlwriter_write_element_ns' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'prefix' => 'null|string',
    'name' => 'string',
    'namespace' => 'null|string',
    'content=' => 'null|string',
  ),
  'xmlwriter_write_pi' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'target' => 'string',
    'content' => 'string',
  ),
  'xmlwriter_write_raw' => 
  array (
    0 => 'bool',
    'writer' => 'XMLWriter',
    'content' => 'string',
  ),
  'zend_version' => 
  array (
    0 => 'string',
  ),
  'zip_close' => 
  array (
    0 => 'void',
    'zip' => 'mixed',
  ),
  'zip_entry_close' => 
  array (
    0 => 'bool',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_compressedsize' => 
  array (
    0 => 'false|int',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_compressionmethod' => 
  array (
    0 => 'false|string',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_filesize' => 
  array (
    0 => 'false|int',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_name' => 
  array (
    0 => 'false|string',
    'zip_entry' => 'mixed',
  ),
  'zip_entry_open' => 
  array (
    0 => 'bool',
    'zip_dp' => 'mixed',
    'zip_entry' => 'mixed',
    'mode=' => 'string',
  ),
  'zip_entry_read' => 
  array (
    0 => 'false|string',
    'zip_entry' => 'mixed',
    'len=' => 'int',
  ),
  'zip_open' => 
  array (
    0 => 'mixed',
    'filename' => 'string',
  ),
  'zip_read' => 
  array (
    0 => 'mixed',
    'zip' => 'mixed',
  ),
  'ziparchive::addemptydir' => 
  array (
    0 => 'bool',
    'dirname' => 'string',
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
  'ziparchive::addfromstring' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'content' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::addglob' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'flags=' => 'int',
    'options=' => 'array<array-key, mixed>',
  ),
  'ziparchive::addpattern' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'pattern' => 'string',
    'path=' => 'string',
    'options=' => 'array<array-key, mixed>',
  ),
  'ziparchive::clearerror' => 
  array (
    0 => 'void',
  ),
  'ziparchive::close' => 
  array (
    0 => 'bool',
  ),
  'ziparchive::count' => 
  array (
    0 => 'int',
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
  'ziparchive::extractto' => 
  array (
    0 => 'bool',
    'pathto' => 'string',
    'files=' => 'array<array-key, mixed>|null|string',
  ),
  'ziparchive::getarchivecomment' => 
  array (
    0 => 'false|string',
    'flags=' => 'int',
  ),
  'ziparchive::getarchiveflag' => 
  array (
    0 => 'int',
    'flag' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getcommentindex' => 
  array (
    0 => 'false|string',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getcommentname' => 
  array (
    0 => 'false|string',
    'name' => 'string',
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
  'ziparchive::getexternalattributesname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    '&opsys' => 'mixed',
    '&attr' => 'mixed',
    'flags=' => 'int',
  ),
  'ziparchive::getfromindex' => 
  array (
    0 => 'false|string',
    'index' => 'int',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getfromname' => 
  array (
    0 => 'false|string',
    'name' => 'string',
    'len=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getnameindex' => 
  array (
    0 => 'false|string',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::getstatusstring' => 
  array (
    0 => 'string',
  ),
  'ziparchive::getstream' => 
  array (
    0 => 'mixed',
    'name' => 'string',
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
  'ziparchive::locatename' => 
  array (
    0 => 'false|int',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::open' => 
  array (
    0 => 'bool|int',
    'filename' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::registercancelcallback' => 
  array (
    0 => 'bool',
    'callback' => 'callable',
  ),
  'ziparchive::registerprogresscallback' => 
  array (
    0 => 'bool',
    'rate' => 'float',
    'callback' => 'callable',
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
  'ziparchive::replacefile' => 
  array (
    0 => 'bool',
    'filepath' => 'string',
    'index' => 'int',
    'start=' => 'int',
    'length=' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::setarchivecomment' => 
  array (
    0 => 'bool',
    'comment' => 'string',
  ),
  'ziparchive::setarchiveflag' => 
  array (
    0 => 'bool',
    'flag' => 'int',
    'value' => 'int',
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
  'ziparchive::setcompressionindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ziparchive::setcompressionname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'method' => 'int',
    'compflags=' => 'int',
  ),
  'ziparchive::setencryptionindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'method' => 'int',
    'password=' => 'null|string',
  ),
  'ziparchive::setencryptionname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'method' => 'int',
    'password=' => 'null|string',
  ),
  'ziparchive::setexternalattributesindex' => 
  array (
    0 => 'bool',
    'index' => 'int',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::setexternalattributesname' => 
  array (
    0 => 'bool',
    'name' => 'string',
    'opsys' => 'int',
    'attr' => 'int',
    'flags=' => 'int',
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
  'ziparchive::setpassword' => 
  array (
    0 => 'bool',
    'password' => 'string',
  ),
  'ziparchive::statindex' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'index' => 'int',
    'flags=' => 'int',
  ),
  'ziparchive::statname' => 
  array (
    0 => 'array<array-key, mixed>|false',
    'name' => 'string',
    'flags=' => 'int',
  ),
  'ziparchive::unchangeall' => 
  array (
    0 => 'bool',
  ),
  'ziparchive::unchangearchive' => 
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
  'zlib_decode' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'max_length=' => 'int',
  ),
  'zlib_encode' => 
  array (
    0 => 'false|string',
    'data' => 'string',
    'encoding' => 'int',
    'level=' => 'int',
  ),
  'zlib_get_coding_type' => 
  array (
    0 => 'false|string',
  ),
);