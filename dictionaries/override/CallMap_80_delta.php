<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'datetime::createfrominterface' => 
    array (
      0 => 'static',
      'object' => 'DateTimeInterface',
    ),
    'datetimeimmutable::createfrominterface' => 
    array (
      0 => 'static',
      'object' => 'DateTimeInterface',
    ),
    'get_resource_id' => 
    array (
      0 => 'int',
      'resource' => 'resource',
    ),
    'phptoken::is' => 
    array (
      0 => 'bool',
      'kind' => 'array<array-key, int|string>|int|string',
    ),
    'phptoken::tokenize' => 
    array (
      0 => 'list<PhpToken>',
      'code' => 'string',
      'flags=' => 'int',
    ),
    'reflectionclass::getattributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'reflectionclassconstant::getattributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'reflectionfunctionabstract::getattributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'reflectionparameter::getattributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'reflectionproperty::getattributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'reflectionuniontype::gettypes' => 
    array (
      0 => 'list<ReflectionNamedType>',
    ),
    'soapheader::__construct' => 
    array (
      0 => 'void',
      'namespace' => 'string',
      'name' => 'string',
      'data=' => 'mixed',
      'mustUnderstand=' => 'bool',
      'actor=' => 'null|string',
    ),
    'weakmap::offsetexists' => 
    array (
      0 => 'bool',
      'object' => 'object',
    ),
    'weakmap::offsetget' => 
    array (
      0 => 'mixed',
      'object' => 'object',
    ),
    'weakmap::offsetset' => 
    array (
      0 => 'void',
      'object' => 'object',
      'value' => 'mixed',
    ),
    'weakmap::offsetunset' => 
    array (
      0 => 'void',
      'object' => 'object',
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
    'ziparchive::replacefile' => 
    array (
      0 => 'bool',
      'filepath' => 'string',
      'index' => 'int',
      'start=' => 'int',
      'length=' => 'int',
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
  ),
  'changed' => 
  array (
    'abs' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'number' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'num' => 'int',
      ),
    ),
    'acos' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'acosh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'addcslashes' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'charlist' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'characters' => 'string',
      ),
    ),
    'addslashes' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'array_change_key_case' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'input' => 'array<array-key, mixed>',
        'case=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'case=' => 'int',
      ),
    ),
    'array_chunk' => 
    array (
      'old' => 
      array (
        0 => 'list<array<array-key, array<array-key, mixed>>>',
        'arg' => 'array<array-key, mixed>',
        'size' => 'int',
        'preserve_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'list<array<array-key, array<array-key, mixed>>>',
        'array' => 'array<array-key, mixed>',
        'length' => 'int',
        'preserve_keys=' => 'bool',
      ),
    ),
    'array_column' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arg' => 'array<array-key, mixed>',
        'column_key' => 'mixed',
        'index_key=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'column_key' => 'int|null|string',
        'index_key=' => 'int|null|string',
      ),
    ),
    'array_combine' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'keys' => 'array<array-key, int|string>',
        'values' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'keys' => 'array<array-key, int|string>',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'array_count_values' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, int>',
        'arg' => 'array<array-key, int|string>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, int>',
        'array' => 'array<array-key, int|string>',
      ),
    ),
    'array_diff' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_diff_assoc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_diff_key' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_fill' => 
    array (
      'old' => 
      array (
        0 => 'array<int, mixed>',
        'start_key' => 'int',
        'num' => 'int',
        'val' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<int, mixed>',
        'start_index' => 'int',
        'count' => 'int',
        'value' => 'mixed',
      ),
    ),
    'array_fill_keys' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'keys' => 'array<array-key, mixed>',
        'val' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'keys' => 'array<array-key, mixed>',
        'value' => 'mixed',
      ),
    ),
    'array_filter' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arg' => 'array<array-key, mixed>',
        'callback=' => 'callable(mixed, array-key=):mixed',
        'use_keys=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'callback=' => 'callable(mixed, array-key=):mixed|null',
        'mode=' => 'int',
      ),
    ),
    'array_flip' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, int|string>',
        'arg' => 'array<array-key, int|string>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, int|string>',
        'array' => 'array<array-key, int|string>',
      ),
    ),
    'array_intersect' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_intersect_assoc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_intersect_key' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_key_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'search' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_key_first' => 
    array (
      'old' => 
      array (
        0 => 'int|null|string',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int|null|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_key_last' => 
    array (
      'old' => 
      array (
        0 => 'int|null|string',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int|null|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_keys' => 
    array (
      'old' => 
      array (
        0 => 'list<int|string>',
        'arg' => 'array<array-key, mixed>',
        'search_value=' => 'mixed',
        'strict=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'list<int|string>',
        'array' => 'array<array-key, mixed>',
        'filter_value=' => 'mixed',
        'strict=' => 'bool',
      ),
    ),
    'array_map' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'callback' => 'callable|null',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'callback' => 'callable|null',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_multisort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arr1' => 'array<array-key, mixed>',
        '&sort_order=' => 'array<array-key, mixed>|int',
        '&sort_flags=' => 'array<array-key, mixed>|int',
        '&...arr2=' => 'array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        '&...rest=' => 'array<array-key, mixed>|int',
      ),
    ),
    'array_pad' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arg' => 'array<array-key, mixed>',
        'pad_size' => 'int',
        'pad_value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'length' => 'int',
        'value' => 'mixed',
      ),
    ),
    'array_pop' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        '&stack' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'array_product' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_push' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&array' => 'array<array-key, mixed>',
        '...values=' => 'mixed',
      ),
    ),
    'array_rand' => 
    array (
      'old' => 
      array (
        0 => 'array<int, int|string>|int|string',
        'arg' => 'non-empty-array<array-key, mixed>',
        'num_req=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<int, int|string>|int|string',
        'array' => 'non-empty-array<array-key, mixed>',
        'num=' => 'int',
      ),
    ),
    'array_reduce' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'arg' => 'array<array-key, mixed>',
        'callback' => 'callable(mixed, mixed):mixed',
        'initial=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'array' => 'array<array-key, mixed>',
        'callback' => 'callable(mixed, mixed):mixed',
        'initial=' => 'mixed',
      ),
    ),
    'array_replace' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...replacements=' => 'array<array-key, mixed>',
      ),
    ),
    'array_replace_recursive' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...replacements=' => 'array<array-key, mixed>',
      ),
    ),
    'array_reverse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'input' => 'array<array-key, mixed>',
        'preserve_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'preserve_keys=' => 'bool',
      ),
    ),
    'array_shift' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        '&stack' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed|null',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'array_slice' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arg' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int|null',
        'preserve_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int|null',
        'preserve_keys=' => 'bool',
      ),
    ),
    'array_splice' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        '&arg' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int',
        'replacement=' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '&array' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int|null',
        'replacement=' => 'array<array-key, mixed>|string',
      ),
    ),
    'array_sum' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_unique' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arg' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'array_unshift' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&array' => 'array<array-key, mixed>',
        '...values=' => 'mixed',
      ),
    ),
    'array_values' => 
    array (
      'old' => 
      array (
        0 => 'list<mixed>',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'list<mixed>',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_walk' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&input' => 'array<array-key, mixed>',
        'funcname' => 'callable',
        'userdata=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'array_walk_recursive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&input' => 'array<array-key, mixed>',
        'funcname' => 'callable',
        'userdata=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'arrayiterator::asort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'arrayiterator::ksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'arrayiterator::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
      ),
    ),
    'arrayiterator::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'int|string',
      ),
    ),
    'arrayiterator::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null|string',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|null|string',
        'value' => 'mixed',
      ),
    ),
    'arrayiterator::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|string',
      ),
    ),
    'arrayiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'arrayiterator::uasort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'arrayiterator::uksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'arrayiterator::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'arrayobject::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'input=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
        'iterator_class=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
        'iteratorClass=' => 'class-string',
      ),
    ),
    'arrayobject::asort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'arrayobject::exchangearray' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'input' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>|object',
      ),
    ),
    'arrayobject::ksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'arrayobject::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
      ),
    ),
    'arrayobject::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'mixed|null',
        'key' => 'int|string',
      ),
    ),
    'arrayobject::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null|string',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|null|string',
        'value' => 'mixed',
      ),
    ),
    'arrayobject::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|string',
      ),
    ),
    'arrayobject::uasort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'arrayobject::uksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'arrayobject::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'arsort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'asin' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'asinh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'asort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'assert_options' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'what' => 'int',
        'value=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'option' => 'int',
        'value=' => 'mixed',
      ),
    ),
    'atan' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'atanh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'base64_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'strict=' => 'false',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'strict=' => 'false',
      ),
    ),
    'base64_encode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'base_convert' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'number' => 'string',
        'frombase' => 'int',
        'tobase' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'string',
        'from_base' => 'int',
        'to_base' => 'int',
      ),
    ),
    'bcadd' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bccomp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcdiv' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcmod' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcmul' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcpow' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'x' => 'numeric-string',
        'y' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num' => 'numeric-string',
        'exponent' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcpowmod' => 
    array (
      'old' => 
      array (
        0 => 'false|numeric-string',
        'x' => 'numeric-string',
        'y' => 'numeric-string',
        'mod' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num' => 'numeric-string',
        'exponent' => 'numeric-string',
        'modulus' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcscale' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'scale=' => 'int|null',
      ),
    ),
    'bcsqrt' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bcsub' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
        'scale=' => 'int|null',
      ),
    ),
    'bin2hex' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'bind_textdomain_codeset' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'domain' => 'string',
        'codeset' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'domain' => 'string',
        'codeset' => 'null|string',
      ),
    ),
    'bindec' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'binary_number' => 'string',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'binary_string' => 'string',
      ),
    ),
    'bindtextdomain' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'domain' => 'string',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'domain' => 'string',
        'directory' => 'null|string',
      ),
    ),
    'boolval' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'bzdecompress' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'data' => 'string',
        'use_less_memory=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'data' => 'string',
        'use_less_memory=' => 'bool',
      ),
    ),
    'bzwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'bz' => 'resource',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'bz' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'cachingiterator::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
    ),
    'cachingiterator::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
      ),
    ),
    'cachingiterator::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'string',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'mixed',
      ),
    ),
    'cachingiterator::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'string',
      ),
    ),
    'call_user_func' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'function_name' => 'callable',
        '...parameters=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'callback' => 'callable',
        '...args=' => 'mixed',
      ),
    ),
    'call_user_func_array' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'function_name' => 'callable',
        'parameters' => 'list<mixed>',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'callback' => 'callable',
        'args' => 'list<mixed>',
      ),
    ),
    'ceil' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float|int',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float|int',
      ),
    ),
    'checkdnsrr' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        'type=' => 'string',
      ),
    ),
    'chmod' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'permissions' => 'int',
      ),
    ),
    'chop' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'character_mask=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'characters=' => 'string',
      ),
    ),
    'chunk_split' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'chunklen=' => 'int',
        'ending=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'length=' => 'int',
        'separator=' => 'string',
      ),
    ),
    'class_alias' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'user_class_name' => 'string',
        'alias_name' => 'string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'class' => 'string',
        'alias' => 'string',
        'autoload=' => 'bool',
      ),
    ),
    'class_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'classname' => 'string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'class' => 'string',
        'autoload=' => 'bool',
      ),
    ),
    'class_implements' => 
    array (
      'old' => 
      array (
        0 => 'array<interface-string, interface-string>|false',
        'what' => 'object|string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<interface-string, interface-string>|false',
        'object_or_class' => 'object|string',
        'autoload=' => 'bool',
      ),
    ),
    'class_parents' => 
    array (
      'old' => 
      array (
        0 => 'array<class-string, class-string>|false',
        'instance' => 'object|string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<class-string, class-string>|false',
        'object_or_class' => 'object|string',
        'autoload=' => 'bool',
      ),
    ),
    'class_uses' => 
    array (
      'old' => 
      array (
        0 => 'array<trait-string, trait-string>|false',
        'what' => 'object|string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<trait-string, trait-string>|false',
        'object_or_class' => 'object|string',
        'autoload=' => 'bool',
      ),
    ),
    'closure::bind' => 
    array (
      'old' => 
      array (
        0 => 'Closure|null',
        'closure' => 'Closure',
        'newthis' => 'null|object',
        'newscope=' => 'null|object|string',
      ),
      'new' => 
      array (
        0 => 'Closure|null',
        'closure' => 'Closure',
        'newThis' => 'null|object',
        'newScope=' => 'null|object|string',
      ),
    ),
    'closure::bindto' => 
    array (
      'old' => 
      array (
        0 => 'Closure|null',
        'newthis' => 'null|object',
        'newscope=' => 'null|object|string',
      ),
      'new' => 
      array (
        0 => 'Closure|null',
        'newThis' => 'null|object',
        'newScope=' => 'null|object|string',
      ),
    ),
    'closure::call' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'newthis' => 'object',
        '...parameters=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'newThis' => 'object',
        '...args=' => 'mixed',
      ),
    ),
    'closure::fromcallable' => 
    array (
      'old' => 
      array (
        0 => 'Closure',
        'callable' => 'callable',
      ),
      'new' => 
      array (
        0 => 'Closure',
        'callback' => 'callable',
      ),
    ),
    'collator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'locale' => 'string',
      ),
    ),
    'collator::asort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arr' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'collator::compare' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'arg1' => 'string',
        'arg2' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'collator::create' => 
    array (
      'old' => 
      array (
        0 => 'Collator|null',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'Collator|null',
        'locale' => 'string',
      ),
    ),
    'collator::getattribute' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'attribute' => 'int',
      ),
    ),
    'collator::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'type' => 'int',
      ),
    ),
    'collator::getsortkey' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
      ),
    ),
    'collator::getstrength' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'collator::setattribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arg1' => 'int',
        'arg2' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'attribute' => 'int',
        'value' => 'int',
      ),
    ),
    'collator::setstrength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'strength' => 'int',
      ),
    ),
    'collator::sort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arr' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'collator::sortwithsortkeys' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arr' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'collator_asort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        '&arr' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'collator_compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'object' => 'collator',
        'arg1' => 'string',
        'arg2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'object' => 'collator',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'collator_create' => 
    array (
      'old' => 
      array (
        0 => 'Collator|null',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'Collator|null',
        'locale' => 'string',
      ),
    ),
    'collator_get_attribute' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'object' => 'collator',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'object' => 'collator',
        'attribute' => 'int',
      ),
    ),
    'collator_get_locale' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'object' => 'collator',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'object' => 'collator',
        'type' => 'int',
      ),
    ),
    'collator_get_sort_key' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'object' => 'collator',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'object' => 'collator',
        'string' => 'string',
      ),
    ),
    'collator_get_strength' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'object' => 'collator',
      ),
      'new' => 
      array (
        0 => 'int',
        'object' => 'collator',
      ),
    ),
    'collator_set_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'arg1' => 'int',
        'arg2' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'attribute' => 'int',
        'value' => 'int',
      ),
    ),
    'collator_set_strength' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        'strength' => 'int',
      ),
    ),
    'collator_sort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        '&arr' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'collator_sort_with_sort_keys' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'coll' => 'collator',
        '&arr' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object' => 'collator',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'com_load_typelib' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'typelib_name' => 'string',
        'case_insensitive=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'typelib_name' => 'string',
        'case_insensitive=' => 'true',
      ),
    ),
    'compact' => 
    array (
      'old' => 
      array (
        0 => 'array<string, mixed>',
        '...var_names' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'array<string, mixed>',
        'var_name' => 'array<array-key, mixed>|string',
        '...var_names=' => 'array<array-key, mixed>|string',
      ),
    ),
    'constant' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'const_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'name' => 'string',
      ),
    ),
    'convert_uudecode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'convert_uuencode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'source_file' => 'string',
        'destination_file' => 'string',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
        'context=' => 'resource',
      ),
    ),
    'cos' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'cosh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'count' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'var' => 'Countable|SimpleXMLElement|array<array-key, mixed>',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'value' => 'Countable|array<array-key, mixed>',
        'mode=' => 'int',
      ),
    ),
    'count_chars' => 
    array (
      'old' => 
      array (
        0 => 'array<int, int>|false',
        'input' => 'string',
        'mode=' => '0|1|2',
      ),
      'new' => 
      array (
        0 => 'array<int, int>',
        'string' => 'string',
        'mode=' => '0|1|2',
      ),
    ),
    'count_chars\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'input' => 'string',
        'mode=' => '3|4',
      ),
      'new' => 
      array (
        0 => 'string',
        'input' => 'string',
        'mode=' => '3|4',
      ),
    ),
    'crc32' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string' => 'string',
      ),
    ),
    'crypt' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'salt=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'salt' => 'string',
      ),
    ),
    'curl_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_copy_handle' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'CurlHandle|false',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_errno' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_escape' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ch' => 'resource',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'handle' => 'CurlHandle',
        'string' => 'string',
      ),
    ),
    'curl_exec' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_file_create' => 
    array (
      'old' => 
      array (
        0 => 'CURLFile',
        'filename' => 'string',
        'mimetype=' => 'string',
        'postname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'CURLFile',
        'filename' => 'string',
        'mime_type=' => 'null|string',
        'posted_filename=' => 'null|string',
      ),
    ),
    'curl_getinfo' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'ch' => 'resource',
        'option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'handle' => 'CurlHandle',
        'option=' => 'int|null',
      ),
    ),
    'curl_init' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'url=' => 'string',
      ),
      'new' => 
      array (
        0 => 'CurlHandle|false',
        'url=' => 'null|string',
      ),
    ),
    'curl_multi_add_handle' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mh' => 'resource',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'multi_handle' => 'CurlMultiHandle',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_multi_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'mh' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'multi_handle' => 'CurlMultiHandle',
      ),
    ),
    'curl_multi_errno' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'mh' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'multi_handle' => 'CurlMultiHandle',
      ),
    ),
    'curl_multi_exec' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mh' => 'resource',
        '&w_still_running=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'multi_handle' => 'CurlMultiHandle',
        '&w_still_running' => 'int',
      ),
    ),
    'curl_multi_getcontent' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_multi_info_read' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'mh' => 'resource',
        '&w_msgs_in_queue=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'multi_handle' => 'CurlMultiHandle',
        '&w_queued_messages=' => 'int',
      ),
    ),
    'curl_multi_init' => 
    array (
      'old' => 
      array (
        0 => 'resource',
      ),
      'new' => 
      array (
        0 => 'CurlMultiHandle',
      ),
    ),
    'curl_multi_remove_handle' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mh' => 'resource',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'multi_handle' => 'CurlMultiHandle',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_multi_select' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mh' => 'resource',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        'multi_handle' => 'CurlMultiHandle',
        'timeout=' => 'float',
      ),
    ),
    'curl_multi_setopt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'sh' => 'resource',
        'option' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'multi_handle' => 'CurlMultiHandle',
        'option' => 'int',
        'value' => 'mixed',
      ),
    ),
    'curl_multi_strerror' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'errornum' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'error_code' => 'int',
      ),
    ),
    'curl_pause' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ch' => 'resource',
        'bitmask' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'handle' => 'CurlHandle',
        'flags' => 'int',
      ),
    ),
    'curl_reset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'ch' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'handle' => 'CurlHandle',
      ),
    ),
    'curl_setopt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ch' => 'resource',
        'option' => 'int',
        'value' => 'callable|mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'handle' => 'CurlHandle',
        'option' => 'int',
        'value' => 'callable|mixed',
      ),
    ),
    'curl_setopt_array' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ch' => 'resource',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'handle' => 'CurlHandle',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'curl_share_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'sh' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'share_handle' => 'CurlShareHandle',
      ),
    ),
    'curl_share_errno' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'sh' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'share_handle' => 'CurlShareHandle',
      ),
    ),
    'curl_share_init' => 
    array (
      'old' => 
      array (
        0 => 'resource',
      ),
      'new' => 
      array (
        0 => 'CurlShareHandle',
      ),
    ),
    'curl_share_setopt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'sh' => 'resource',
        'option' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'share_handle' => 'CurlShareHandle',
        'option' => 'int',
        'value' => 'mixed',
      ),
    ),
    'curl_share_strerror' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'errornum' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'error_code' => 'int',
      ),
    ),
    'curl_strerror' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'errornum' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'error_code' => 'int',
      ),
    ),
    'curl_unescape' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ch' => 'resource',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'handle' => 'CurlHandle',
        'string' => 'string',
      ),
    ),
    'curl_version' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'version=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'curlfile::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'mimetype=' => 'string',
        'postname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'mime_type=' => 'null|string',
        'posted_filename=' => 'null|string',
      ),
    ),
    'curlfile::setmimetype' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'mime_type' => 'string',
      ),
    ),
    'curlfile::setpostfilename' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'posted_filename' => 'string',
      ),
    ),
    'current' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'array' => 'array<array-key, mixed>|object',
      ),
    ),
    'date' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'format' => 'string',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
        'timestamp=' => 'int|null',
      ),
    ),
    'date_add' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'interval' => 'DateInterval',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'interval' => 'DateInterval',
      ),
    ),
    'date_create' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'time=' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
      'new' => 
      array (
        0 => 'DateTime|false',
        'datetime=' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'date_create_from_format' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'format' => 'string',
        'time' => 'string',
        'object=' => 'DateTimeZone|null',
      ),
      'new' => 
      array (
        0 => 'DateTime|false',
        'format' => 'string',
        'datetime' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'date_create_immutable' => 
    array (
      'old' => 
      array (
        0 => 'DateTimeImmutable|false',
        'time=' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
      'new' => 
      array (
        0 => 'DateTimeImmutable|false',
        'datetime=' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'date_create_immutable_from_format' => 
    array (
      'old' => 
      array (
        0 => 'DateTimeImmutable|false',
        'format' => 'string',
        'time' => 'string',
        'object=' => 'DateTimeZone|null',
      ),
      'new' => 
      array (
        0 => 'DateTimeImmutable|false',
        'format' => 'string',
        'datetime' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'date_date_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'year' => 'int',
        'month' => 'int',
        'day' => 'int',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'year' => 'int',
        'month' => 'int',
        'day' => 'int',
      ),
    ),
    'date_default_timezone_set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timezone_identifier' => 'non-empty-string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezoneId' => 'non-empty-string',
      ),
    ),
    'date_diff' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval|false',
        'object' => 'DateTimeInterface',
        'object2' => 'DateTimeInterface',
        'absolute=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'baseObject' => 'DateTimeInterface',
        'targetObject' => 'DateTimeInterface',
        'absolute=' => 'bool',
      ),
    ),
    'date_format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'object' => 'DateTimeInterface',
        'format' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'object' => 'DateTimeInterface',
        'format' => 'string',
      ),
    ),
    'date_interval_create_from_date_string' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval',
        'time' => 'string',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'datetime' => 'string',
      ),
    ),
    'date_isodate_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'year' => 'int',
        'week' => 'int',
        'day=' => 'int',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'year' => 'int',
        'week' => 'int',
        'dayOfWeek=' => 'int',
      ),
    ),
    'date_modify' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'modify' => 'string',
      ),
      'new' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'modifier' => 'string',
      ),
    ),
    'date_offset_get' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'object' => 'DateTimeInterface',
      ),
      'new' => 
      array (
        0 => 'int',
        'object' => 'DateTimeInterface',
      ),
    ),
    'date_parse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'date' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'datetime' => 'string',
      ),
    ),
    'date_parse_from_format' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'format' => 'string',
        'date' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'format' => 'string',
        'datetime' => 'string',
      ),
    ),
    'date_sub' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'interval' => 'DateInterval',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'interval' => 'DateInterval',
      ),
    ),
    'date_sun_info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'time' => 'int',
        'latitude' => 'float',
        'longitude' => 'float',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'timestamp' => 'int',
        'latitude' => 'float',
        'longitude' => 'float',
      ),
    ),
    'date_sunrise' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int|string',
        'time' => 'int',
        'format=' => 'int',
        'latitude=' => 'float',
        'longitude=' => 'float',
        'zenith=' => 'float',
        'gmt_offset=' => 'float',
      ),
      'new' => 
      array (
        0 => 'false|float|int|string',
        'timestamp' => 'int',
        'returnFormat=' => 'int',
        'latitude=' => 'float|null',
        'longitude=' => 'float|null',
        'zenith=' => 'float|null',
        'utcOffset=' => 'float|null',
      ),
    ),
    'date_sunset' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int|string',
        'time' => 'int',
        'format=' => 'int',
        'latitude=' => 'float',
        'longitude=' => 'float',
        'zenith=' => 'float',
        'gmt_offset=' => 'float',
      ),
      'new' => 
      array (
        0 => 'false|float|int|string',
        'timestamp' => 'int',
        'returnFormat=' => 'int',
        'latitude=' => 'float|null',
        'longitude=' => 'float|null',
        'zenith=' => 'float|null',
        'utcOffset=' => 'float|null',
      ),
    ),
    'date_timestamp_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'unixtimestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'timestamp' => 'int',
      ),
    ),
    'date_timezone_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'timezone' => 'DateTimeZone',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'timezone' => 'DateTimeZone',
      ),
    ),
    'datefmt_create' => 
    array (
      'old' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'date_type' => 'int',
        'time_type' => 'int',
        'timezone_str=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'datefmt_format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'args=' => 'IntlDateFormatter',
        'array=' => 'DateTime|IntlCalendar|array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'IntlDateFormatter',
        'datetime' => 'DateTime|IntlCalendar|array<array-key, mixed>|int',
      ),
    ),
    'datefmt_format_object' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'object' => 'object',
        'format=' => 'mixed',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'datetime' => 'object',
        'format=' => 'mixed',
        'locale=' => 'null|string',
      ),
    ),
    'datefmt_get_calendar' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_calendar_object' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|false|null',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|false|null',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_datetype' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'nf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_error_message' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'coll' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_locale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'IntlDateFormatter',
        'type=' => 'int',
      ),
    ),
    'datefmt_get_pattern' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_timetype' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_timezone' => 
    array (
      'old' => 
      array (
        0 => 'IntlTimeZone|false',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'IntlTimeZone|false',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_get_timezone_id' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_is_lenient' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'IntlDateFormatter',
      ),
    ),
    'datefmt_localtime' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'formatter' => 'IntlDateFormatter',
        'string' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'formatter' => 'IntlDateFormatter',
        'string' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'datefmt_parse' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int',
        'formatter' => 'IntlDateFormatter',
        'string' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'formatter' => 'IntlDateFormatter',
        'string' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'datefmt_set_calendar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mf' => 'IntlDateFormatter',
        'calendar' => 'IntlCalendar|int|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'IntlDateFormatter',
        'calendar' => 'IntlCalendar|int|null',
      ),
    ),
    'datefmt_set_lenient' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'mf' => 'IntlDateFormatter',
      ),
      'new' => 
      array (
        0 => 'void',
        'formatter' => 'IntlDateFormatter',
        'lenient' => 'bool',
      ),
    ),
    'datefmt_set_pattern' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mf' => 'IntlDateFormatter',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'IntlDateFormatter',
        'pattern' => 'string',
      ),
    ),
    'datefmt_set_timezone' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'mf' => 'IntlDateFormatter',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'formatter' => 'IntlDateFormatter',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'dateinterval::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'interval_spec' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'duration' => 'string',
      ),
    ),
    'dateinterval::createfromdatestring' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval|false',
        'time' => 'string',
      ),
      'new' => 
      array (
        0 => 'DateInterval|false',
        'datetime' => 'string',
      ),
    ),
    'dateperiod::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'start' => 'DateTimeInterface',
        'interval' => 'DateInterval',
        'end' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'start' => 'DateTimeInterface',
        'interval=' => 'DateInterval',
        'end=' => 'int',
        'options=' => 'int',
      ),
    ),
    'datetime::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'time=' => 'string',
        'timezone=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'datetime=' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'datetime::createfromformat' => 
    array (
      'old' => 
      array (
        0 => 'false|static',
        'format' => 'string',
        'time' => 'string',
        'object=' => 'DateTimeZone|null',
      ),
      'new' => 
      array (
        0 => 'false|static',
        'format' => 'string',
        'datetime' => 'string',
        'timezone=' => 'DateTimeZone|null',
      ),
    ),
    'datetime::createfromimmutable' => 
    array (
      'old' => 
      array (
        0 => 'static',
        'DateTimeImmutable' => 'DateTimeImmutable',
      ),
      'new' => 
      array (
        0 => 'static',
        'object' => 'DateTimeImmutable',
      ),
    ),
    'datetime::diff' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval',
        'object' => 'DateTimeInterface',
        'absolute=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'targetObject' => 'DateTimeInterface',
        'absolute=' => 'bool',
      ),
    ),
    'datetime::format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
      ),
    ),
    'datetime::gettimestamp' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'datetime::modify' => 
    array (
      'old' => 
      array (
        0 => 'false|static',
        'modify' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|static',
        'modifier' => 'string',
      ),
    ),
    'datetime::setisodate' => 
    array (
      'old' => 
      array (
        0 => 'static',
        'year' => 'int',
        'week' => 'int',
        'day=' => 'int',
      ),
      'new' => 
      array (
        0 => 'static',
        'year' => 'int',
        'week' => 'int',
        'dayOfWeek=' => 'int',
      ),
    ),
    'datetime::settime' => 
    array (
      'old' => 
      array (
        0 => 'static',
        'hour' => 'int',
        'minute' => 'int',
        'second=' => 'int',
        'microseconds=' => 'int',
      ),
      'new' => 
      array (
        0 => 'static',
        'hour' => 'int',
        'minute' => 'int',
        'second=' => 'int',
        'microsecond=' => 'int',
      ),
    ),
    'datetime::settimestamp' => 
    array (
      'old' => 
      array (
        0 => 'static',
        'unixtimestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'static',
        'timestamp' => 'int',
      ),
    ),
    'datetimeinterface::gettimestamp' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'datetimezone::getoffset' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'object' => 'DateTimeInterface',
      ),
      'new' => 
      array (
        0 => 'int',
        'datetime' => 'DateTimeInterface',
      ),
    ),
    'datetimezone::gettransitions' => 
    array (
      'old' => 
      array (
        0 => 'false|list<array{abbr: string, isdst: bool, offset: int, time: string, ts: int}>',
        'timestamp_begin=' => 'int',
        'timestamp_end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|list<array{abbr: string, isdst: bool, offset: int, time: string, ts: int}>',
        'timestampBegin=' => 'int',
        'timestampEnd=' => 'int',
      ),
    ),
    'datetimezone::listidentifiers' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'what=' => 'int',
        'country=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
    ),
    'db2_autocommit' => 
    array (
      'old' => 
      array (
        0 => '0|1|bool',
        'connection' => 'resource',
        'value=' => '0|1',
      ),
      'new' => 
      array (
        0 => '0|1|bool',
        'connection' => 'resource',
        'value=' => '0|1|null',
      ),
    ),
    'db2_pclose' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'resource' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
      ),
    ),
    'decbin' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'decimal_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'int',
      ),
    ),
    'dechex' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'decimal_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'int',
      ),
    ),
    'decoct' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'decimal_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'int',
      ),
    ),
    'deflate_add' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'resource' => 'resource',
        'add' => 'string',
        'flush_behavior=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'context' => 'DeflateContext',
        'data' => 'string',
        'flush_mode=' => 'int',
      ),
    ),
    'deflate_init' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'encoding' => 'int',
        'level=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'DeflateContext|false',
        'encoding' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'deg2rad' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'directory::close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dir_handle=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'directory::read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'dir_handle=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
      ),
    ),
    'directory::rewind' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dir_handle=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'directoryiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'directory' => 'string',
      ),
    ),
    'directoryiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'directoryiterator::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'directoryiterator::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'directoryiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'directoryiterator::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'directoryiterator::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'disk_free_space' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|float',
        'directory' => 'string',
      ),
    ),
    'disk_total_space' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|float',
        'directory' => 'string',
      ),
    ),
    'diskfreespace' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|float',
        'directory' => 'string',
      ),
    ),
    'dns_check_record' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        'type=' => 'string',
      ),
    ),
    'dns_get_mx' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        '&w_mxhosts' => 'array<array-key, mixed>',
        '&w_weight=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        '&w_hosts' => 'array<array-key, mixed>',
        '&w_weights=' => 'array<array-key, mixed>',
      ),
    ),
    'dns_get_record' => 
    array (
      'old' => 
      array (
        0 => 'false|list<array<array-key, mixed>>',
        'hostname' => 'string',
        'type=' => 'int',
        '&w_authns=' => 'array<array-key, mixed>|null',
        '&w_addtl=' => 'array<array-key, mixed>|null',
        'raw=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|list<array<array-key, mixed>>',
        'hostname' => 'string',
        'type=' => 'int',
        '&w_authoritative_name_servers=' => 'array<array-key, mixed>',
        '&w_additional_records=' => 'array<array-key, mixed>',
        'raw=' => 'bool',
      ),
    ),
    'dom_import_simplexml' => 
    array (
      'old' => 
      array (
        0 => 'DOMElement|null',
        'node' => 'SimpleXMLElement',
      ),
      'new' => 
      array (
        0 => 'DOMElement',
        'node' => 'SimpleXMLElement',
      ),
    ),
    'domattr::insertbefore' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'newChild' => 'DOMNode',
        'refChild=' => 'DOMNode|null',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
        'child=' => 'DOMNode|null',
      ),
    ),
    'domattr::isdefaultnamespace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'namespace' => 'string',
      ),
    ),
    'domattr::issamenode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'other' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'bool',
        'otherNode' => 'DOMNode',
      ),
    ),
    'domattr::lookupprefix' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'namespace' => 'string',
      ),
    ),
    'domattr::removechild' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'oldChild' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'child' => 'DOMNode',
      ),
    ),
    'domattr::replacechild' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'newChild' => 'DOMNode',
        'oldChild' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
        'child' => 'DOMNode',
      ),
    ),
    'domcdatasection::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'domcharacterdata::appenddata' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'arg' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'data' => 'string',
      ),
    ),
    'domcharacterdata::insertdata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'offset' => 'int',
        'arg' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'offset' => 'int',
        'data' => 'string',
      ),
    ),
    'domcharacterdata::replacedata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'offset' => 'int',
        'count' => 'int',
        'arg' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'offset' => 'int',
        'count' => 'int',
        'data' => 'string',
      ),
    ),
    'domcomment::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data=' => 'string',
      ),
    ),
    'domdocument::createattribute' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr|false',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMAttr|false',
        'localName' => 'string',
      ),
    ),
    'domdocument::createattributens' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr|false',
        'namespaceURI' => 'null|string',
        'qualifiedName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMAttr|false',
        'namespace' => 'null|string',
        'qualifiedName' => 'string',
      ),
    ),
    'domdocument::createelement' => 
    array (
      'old' => 
      array (
        0 => 'DOMElement|false',
        'tagName' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMElement|false',
        'localName' => 'string',
        'value=' => 'string',
      ),
    ),
    'domdocument::createelementns' => 
    array (
      'old' => 
      array (
        0 => 'DOMElement|false',
        'namespaceURI' => 'null|string',
        'qualifiedName' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMElement|false',
        'namespace' => 'null|string',
        'qualifiedName' => 'string',
        'value=' => 'string',
      ),
    ),
    'domdocument::getelementsbytagname' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList',
        'tagName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList',
        'qualifiedName' => 'string',
      ),
    ),
    'domdocument::getelementsbytagnamens' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList',
        'namespaceURI' => 'string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domdocument::importnode' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'importedNode' => 'DOMNode',
        'deep=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
        'deep=' => 'bool',
      ),
    ),
    'domdocument::load' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|bool',
        'source' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'options=' => 'int',
      ),
    ),
    'domdocument::loadhtml' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|bool',
        'source' => 'non-empty-string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'source' => 'non-empty-string',
        'options=' => 'int',
      ),
    ),
    'domdocument::loadhtmlfile' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|bool',
        'source' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'options=' => 'int',
      ),
    ),
    'domdocument::loadxml' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|bool',
        'source' => 'non-empty-string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'source' => 'non-empty-string',
        'options=' => 'int',
      ),
    ),
    'domdocument::save' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'file' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'options=' => 'int',
      ),
    ),
    'domdocument::savehtml' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'node=' => 'DOMNode|null',
      ),
    ),
    'domdocument::savehtmlfile' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'file' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
      ),
    ),
    'domdocument::schemavalidate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'flags=' => 'int',
      ),
    ),
    'domdocument::schemavalidatesource' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'source' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'source' => 'string',
        'flags=' => 'int',
      ),
    ),
    'domelement::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'null|string',
        'uri=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'qualifiedName' => 'string',
        'value=' => 'null|string',
        'namespace=' => 'string',
      ),
    ),
    'domelement::getattribute' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'qualifiedName' => 'string',
      ),
    ),
    'domelement::getattributenode' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMAttr',
        'qualifiedName' => 'string',
      ),
    ),
    'domelement::getattributenodens' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr',
        'namespaceURI' => 'null|string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMAttr',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domelement::getattributens' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'namespaceURI' => 'null|string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domelement::getelementsbytagname' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList',
        'qualifiedName' => 'string',
      ),
    ),
    'domelement::getelementsbytagnamens' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList',
        'namespaceURI' => 'null|string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domelement::hasattribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'qualifiedName' => 'string',
      ),
    ),
    'domelement::hasattributens' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'namespaceURI' => 'null|string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domelement::removeattribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'qualifiedName' => 'string',
      ),
    ),
    'domelement::removeattributenode' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr|false',
        'oldAttr' => 'DOMAttr',
      ),
      'new' => 
      array (
        0 => 'DOMAttr|false',
        'attr' => 'DOMAttr',
      ),
    ),
    'domelement::removeattributens' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'namespaceURI' => 'null|string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domelement::setattribute' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr|false',
        'name' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMAttr|false',
        'qualifiedName' => 'string',
        'value' => 'string',
      ),
    ),
    'domelement::setattributenode' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr|null',
        'newAttr' => 'DOMAttr',
      ),
      'new' => 
      array (
        0 => 'DOMAttr|null',
        'attr' => 'DOMAttr',
      ),
    ),
    'domelement::setattributenodens' => 
    array (
      'old' => 
      array (
        0 => 'DOMAttr',
        'newAttr' => 'DOMAttr',
      ),
      'new' => 
      array (
        0 => 'DOMAttr',
        'attr' => 'DOMAttr',
      ),
    ),
    'domelement::setattributens' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'namespaceURI' => 'null|string',
        'qualifiedName' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'namespace' => 'null|string',
        'qualifiedName' => 'string',
        'value' => 'string',
      ),
    ),
    'domelement::setidattribute' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
        'isId' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'qualifiedName' => 'string',
        'isId' => 'bool',
      ),
    ),
    'domelement::setidattributens' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'namespaceURI' => 'string',
        'localName' => 'string',
        'isId' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'namespace' => 'string',
        'qualifiedName' => 'string',
        'isId' => 'bool',
      ),
    ),
    'domimplementation::createdocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespaceURI' => 'string',
        'qualifiedName' => 'string',
        'docType=' => 'DOMDocumentType',
      ),
      'new' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
      ),
    ),
    'domimplementation::createdocumenttype' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocumentType|false',
        'qualifiedName' => 'string',
        'publicId' => 'string',
        'systemId' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMDocumentType|false',
        'qualifiedName' => 'string',
        'publicId=' => 'string',
        'systemId=' => 'string',
      ),
    ),
    'domimplementation::hasfeature' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'feature' => 'string',
        'version' => 'string',
      ),
    ),
    'domnamednodemap::getnameditem' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|null',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNode|null',
        'qualifiedName' => 'string',
      ),
    ),
    'domnamednodemap::getnameditemns' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|null',
        'namespaceURI=' => 'null|string',
        'localName=' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNode|null',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domnamednodemap::item' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|null',
        'index=' => 'int',
      ),
      'new' => 
      array (
        0 => 'DOMNode|null',
        'index' => 'int',
      ),
    ),
    'domnode::appendchild' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'newChild' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
      ),
    ),
    'domnode::c14n' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'exclusive=' => 'bool',
        'with_comments=' => 'bool',
        'xpath=' => 'array<array-key, mixed>|null',
        'ns_prefixes=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'exclusive=' => 'bool',
        'withComments=' => 'bool',
        'xpath=' => 'array<array-key, mixed>|null',
        'nsPrefixes=' => 'array<array-key, mixed>|null',
      ),
    ),
    'domnode::c14nfile' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'uri' => 'string',
        'exclusive=' => 'bool',
        'with_comments=' => 'bool',
        'xpath=' => 'array<array-key, mixed>|null',
        'ns_prefixes=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'uri' => 'string',
        'exclusive=' => 'bool',
        'withComments=' => 'bool',
        'xpath=' => 'array<array-key, mixed>|null',
        'nsPrefixes=' => 'array<array-key, mixed>|null',
      ),
    ),
    'domnode::insertbefore' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'newChild' => 'DOMNode',
        'refChild=' => 'DOMNode|null',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
        'child=' => 'DOMNode|null',
      ),
    ),
    'domnode::isdefaultnamespace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'namespace' => 'string',
      ),
    ),
    'domnode::issamenode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'other' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'bool',
        'otherNode' => 'DOMNode',
      ),
    ),
    'domnode::lookupprefix' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'namespace' => 'string',
      ),
    ),
    'domnode::removechild' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'oldChild' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'child' => 'DOMNode',
      ),
    ),
    'domnode::replacechild' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'newChild' => 'DOMNode',
        'oldChild' => 'DOMNode',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'node' => 'DOMNode',
        'child' => 'DOMNode',
      ),
    ),
    'domtext::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data=' => 'string',
      ),
    ),
    'domxpath::evaluate' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'expr' => 'string',
        'context=' => 'DOMNode|null',
        'registerNodeNS=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'expression' => 'string',
        'contextNode=' => 'DOMNode|null',
        'registerNodeNS=' => 'bool',
      ),
    ),
    'domxpath::query' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList|false',
        'expr' => 'string',
        'context=' => 'DOMNode|null',
        'registerNodeNS=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList|false',
        'expression' => 'string',
        'contextNode=' => 'DOMNode|null',
        'registerNodeNS=' => 'bool',
      ),
    ),
    'domxpath::registernamespace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'uri' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'namespace' => 'string',
      ),
    ),
    'domxpath::registerphpfunctions' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'void',
        'restrict=' => 'array<array-key, mixed>|null|string',
      ),
    ),
    'doubleval' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'float',
        'value' => 'mixed',
      ),
    ),
    'easter_date' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'year=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'year=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'easter_days' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'year=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'year=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'enchant_broker_describe' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'broker' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'broker' => 'EnchantBroker',
      ),
    ),
    'enchant_broker_dict_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'broker' => 'resource',
        'tag' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'broker' => 'EnchantBroker',
        'tag' => 'string',
      ),
    ),
    'enchant_broker_free' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'broker' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'broker' => 'EnchantBroker',
      ),
    ),
    'enchant_broker_free_dict' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'EnchantBroker',
      ),
    ),
    'enchant_broker_get_dict_path' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'broker' => 'resource',
        'type' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'broker' => 'EnchantBroker',
        'type' => 'int',
      ),
    ),
    'enchant_broker_get_error' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'broker' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'broker' => 'EnchantBroker',
      ),
    ),
    'enchant_broker_init' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
      ),
      'new' => 
      array (
        0 => 'EnchantBroker|false',
      ),
    ),
    'enchant_broker_list_dicts' => 
    array (
      'old' => 
      array (
        0 => 'array<int, array{lang_tag: string, provider_desc: string, provider_file: string, provider_name: string}>|false',
        'broker' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<int, array{lang_tag: string, provider_desc: string, provider_file: string, provider_name: string}>',
        'broker' => 'EnchantBroker',
      ),
    ),
    'enchant_broker_request_dict' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'broker' => 'resource',
        'tag' => 'string',
      ),
      'new' => 
      array (
        0 => 'EnchantDictionary|false',
        'broker' => 'EnchantBroker',
        'tag' => 'string',
      ),
    ),
    'enchant_broker_request_pwl_dict' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'broker' => 'resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'EnchantDictionary|false',
        'broker' => 'EnchantBroker',
        'filename' => 'string',
      ),
    ),
    'enchant_broker_set_dict_path' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'broker' => 'resource',
        'type' => 'int',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'broker' => 'EnchantBroker',
        'type' => 'int',
        'path' => 'string',
      ),
    ),
    'enchant_broker_set_ordering' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'broker' => 'resource',
        'tag' => 'string',
        'ordering' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'broker' => 'EnchantBroker',
        'tag' => 'string',
        'ordering' => 'string',
      ),
    ),
    'enchant_dict_add_to_personal' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dictionary' => 'resource',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
      ),
    ),
    'enchant_dict_add_to_session' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dictionary' => 'resource',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
      ),
    ),
    'enchant_dict_check' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'resource',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
      ),
    ),
    'enchant_dict_describe' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'EnchantDictionary',
      ),
    ),
    'enchant_dict_get_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'dictionary' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'dictionary' => 'EnchantDictionary',
      ),
    ),
    'enchant_dict_is_in_session' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'resource',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
      ),
    ),
    'enchant_dict_quick_check' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'resource',
        'word' => 'string',
        '&w_suggestions=' => 'array<int, string>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
        '&w_suggestions=' => 'array<int, string>',
      ),
    ),
    'enchant_dict_store_replacement' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dictionary' => 'resource',
        'misspelled' => 'string',
        'correct' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'dictionary' => 'EnchantDictionary',
        'misspelled' => 'string',
        'correct' => 'string',
      ),
    ),
    'enchant_dict_suggest' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'resource',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'EnchantDictionary',
        'word' => 'string',
      ),
    ),
    'end' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        '&r_arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
    ),
    'error_log' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'message_type=' => 'int',
        'destination=' => 'string',
        'extra_headers=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'message_type=' => 'int',
        'destination=' => 'null|string',
        'additional_headers=' => 'null|string',
      ),
    ),
    'error_reporting' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'new_error_level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'error_level=' => 'int|null',
      ),
    ),
    'errorexception::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'message=' => 'string',
        'code=' => 'int',
        'severity=' => 'int',
        'filename=' => 'string',
        'lineno=' => 'int',
        'previous=' => 'Throwable|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'message=' => 'string',
        'code=' => 'int',
        'severity=' => 'int',
        'filename=' => 'null|string',
        'line=' => 'int|null',
        'previous=' => 'Throwable|null',
      ),
    ),
    'evcheck::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evcheck::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evchild::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evchild::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evembed::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'other' => 'object',
        'callback=' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'other' => 'EvLoop',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
    ),
    'evembed::createstopped' => 
    array (
      'old' => 
      array (
        0 => 'EvEmbed',
        'other' => 'object',
        'callback=' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
      'new' => 
      array (
        0 => 'EvEmbed',
        'other' => 'EvLoop',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
    ),
    'evembed::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evembed::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evembed::set' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'other' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'other' => 'EvLoop',
      ),
    ),
    'event::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'base' => 'EventBase',
        'fd' => 'mixed',
        'what=' => 'int',
        'cb=' => 'callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'base' => 'EventBase',
        'fd' => 'mixed',
        'what=' => 'int',
        'cb=' => 'callable|null',
        'arg=' => 'mixed',
      ),
    ),
    'eventbase::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'cfg=' => 'EventConfig',
      ),
      'new' => 
      array (
        0 => 'void',
        'cfg=' => 'EventConfig|null',
      ),
    ),
    'eventbase::dispatch' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'eventbuffer::lock' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'void',
        'at_front' => 'bool',
      ),
    ),
    'eventbuffer::pullup' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'size' => 'int',
      ),
    ),
    'eventbuffer::readline' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'eol_style' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'eol_style' => 'int',
      ),
    ),
    'eventbuffer::search' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'what' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'what' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
    ),
    'eventbuffer::searcheol' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'start=' => 'int',
        'eol_style=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'start=' => 'int',
        'eol_style=' => 'int',
      ),
    ),
    'eventbuffer::unlock' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'at_front' => 'bool',
      ),
    ),
    'eventbufferevent::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'base' => 'EventBase',
        'socket=' => 'mixed',
        'options=' => 'int',
        'readcb=' => 'callable',
        'writecb=' => 'callable',
        'eventcb=' => 'callable',
      ),
      'new' => 
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
    ),
    'eventbufferevent::connecthost' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dns_base' => 'EventDnsBase',
        'hostname' => 'string',
        'port' => 'int',
        'family=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dns_base' => 'EventDnsBase|null',
        'hostname' => 'string',
        'port' => 'int',
        'family=' => 'int',
      ),
    ),
    'eventbufferevent::read' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'size' => 'int',
      ),
    ),
    'eventbufferevent::setcallbacks' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'readcb' => 'callable',
        'writecb' => 'callable',
        'eventcb' => 'callable',
        'arg=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'readcb' => 'callable|null',
        'writecb' => 'callable|null',
        'eventcb' => 'callable|null',
        'arg=' => 'string',
      ),
    ),
    'eventdnsbase::setsearchndots' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ndots' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'ndots' => 'int',
      ),
    ),
    'eventhttp::bind' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'address' => 'string',
        'port' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'address' => 'string',
        'port' => 'int',
      ),
    ),
    'eventhttp::setcallback' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'path' => 'string',
        'cb' => 'string',
        'arg=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
        'cb' => 'callable',
        'arg=' => 'string',
      ),
    ),
    'eventhttp::setdefaultcallback' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'cb' => 'string',
        'arg=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'cb' => 'callable',
        'arg=' => 'string',
      ),
    ),
    'eventhttpconnection::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'base' => 'EventBase',
        'dns_base' => 'EventDnsBase',
        'address' => 'string',
        'port' => 'int',
        'ctx' => 'EventSslContext',
      ),
      'new' => 
      array (
        0 => 'void',
        'base' => 'EventBase',
        'dns_base' => 'EventDnsBase|null',
        'address' => 'string',
        'port' => 'int',
        'ctx=' => 'EventSslContext|null',
      ),
    ),
    'eventhttpconnection::makerequest' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'req' => 'EventHttpRequest',
        'type' => 'int',
        'uri' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'req' => 'EventHttpRequest',
        'type' => 'int',
        'uri' => 'string',
      ),
    ),
    'eventhttpconnection::setmaxbodysize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_size' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'max_size' => 'int',
      ),
    ),
    'eventhttpconnection::setmaxheaderssize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_size' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'max_size' => 'int',
      ),
    ),
    'eventhttprequest::findheader' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'type' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'key' => 'string',
        'type' => 'int',
      ),
    ),
    'eventhttprequest::getbufferevent' => 
    array (
      'old' => 
      array (
        0 => 'EventBufferEvent',
      ),
      'new' => 
      array (
        0 => 'EventBufferEvent|null',
      ),
    ),
    'eventhttprequest::getcommand' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'eventhttprequest::getconnection' => 
    array (
      'old' => 
      array (
        0 => 'EventHttpConnection',
      ),
      'new' => 
      array (
        0 => 'EventHttpConnection|null',
      ),
    ),
    'eventhttprequest::getoutputheaders' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'eventhttprequest::removeheader' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'type' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'type' => 'int',
      ),
    ),
    'eventhttprequest::senderror' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'error' => 'int',
        'reason=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'error' => 'int',
        'reason=' => 'null|string',
      ),
    ),
    'eventhttprequest::sendreply' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'code' => 'int',
        'reason' => 'string',
        'buf=' => 'EventBuffer',
      ),
      'new' => 
      array (
        0 => 'void',
        'code' => 'int',
        'reason' => 'string',
        'buf=' => 'EventBuffer|null',
      ),
    ),
    'eventlistener::getbase' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'EventBase',
      ),
    ),
    'eventlistener::getsocketname' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&w_address' => 'string',
        '&w_port=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&w_address' => 'string',
        '&w_port' => 'mixed',
      ),
    ),
    'eventlistener::seterrorcallback' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'cb' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'cb' => 'callable',
      ),
    ),
    'eventsslcontext::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'method' => 'string',
        'options' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'method' => 'int',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'eventutil::getlastsocketerrno' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'socket=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'socket=' => 'Socket|null',
      ),
    ),
    'eventutil::sslrandpoll' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'evfork::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'loop' => 'EvLoop',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
    ),
    'evfork::createstopped' => 
    array (
      'old' => 
      array (
        0 => 'EvFork',
        'callback' => 'callable',
        'data=' => 'string',
        'priority=' => 'string',
      ),
      'new' => 
      array (
        0 => 'EvFork',
        'loop' => 'EvLoop',
        'callback' => 'callable',
        'data=' => 'string',
        'priority=' => 'int',
      ),
    ),
    'evfork::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evfork::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evidle::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evidle::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evio::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evio::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evloop::periodic' => 
    array (
      'old' => 
      array (
        0 => 'EvPeriodic',
        'offset' => 'float',
        'interval' => 'float',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
      'new' => 
      array (
        0 => 'EvPeriodic',
        'offset' => 'float',
        'interval' => 'float',
        'reschedule_cb' => 'callable',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
    ),
    'evperiodic::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'offset' => 'float',
        'interval' => 'string',
        'reschedule_cb' => 'callable',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'float',
        'interval' => 'float',
        'reschedule_cb' => 'callable',
        'callback' => 'callable',
        'data=' => 'mixed',
        'priority=' => 'int',
      ),
    ),
    'evperiodic::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evperiodic::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evperiodic::set' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'offset' => 'float',
        'interval' => 'float',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'float',
        'interval' => 'float',
        'reschedule_cb=' => 'mixed',
      ),
    ),
    'evprepare::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'string',
        'data=' => 'string',
        'priority=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback' => 'string',
        'data=' => 'string',
        'priority=' => 'int',
      ),
    ),
    'evprepare::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evprepare::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evsignal::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evsignal::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evstat::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evstat::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evtimer::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'evtimer::keepalive' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'bool',
      ),
    ),
    'evwatcher::getloop' => 
    array (
      'old' => 
      array (
        0 => 'EvLoop',
      ),
      'new' => 
      array (
        0 => 'EvLoop|null',
      ),
    ),
    'exec' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'command' => 'string',
        '&w_output=' => 'array<array-key, mixed>',
        '&w_return_value=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'command' => 'string',
        '&w_output=' => 'array<array-key, mixed>',
        '&w_result_code=' => 'int',
      ),
    ),
    'exif_read_data' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'file' => 'resource|string',
        'required_sections=' => 'string',
        'as_arrays=' => 'bool',
        'read_thumbnail=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'file' => 'resource|string',
        'required_sections=' => 'null|string',
        'as_arrays=' => 'bool',
        'read_thumbnail=' => 'bool',
      ),
    ),
    'exp' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'explode' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'separator' => 'string',
        'str' => 'string',
        'limit=' => 'int',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'separator' => 'string',
        'string' => 'string',
        'limit=' => 'int',
      ),
    ),
    'expm1' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'extension_loaded' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'extension_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'extension' => 'string',
      ),
    ),
    'extract' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&arg' => 'array<array-key, mixed>',
        'extract_type=' => 'int',
        'prefix=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'prefix=' => 'string',
      ),
    ),
    'fclose' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'feof' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'fflush' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'fgetc' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
      ),
    ),
    'fgetcsv' => 
    array (
      'old' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'fp' => 'resource',
        'length=' => 'int',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'stream' => 'resource',
        'length=' => 'int|null',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'fgets' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int|null',
      ),
    ),
    'file_get_contents' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'context=' => 'null|resource',
        'offset=' => 'int',
        'maxlen=' => 'int',
        'use_include_path=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'null|resource',
        'offset=' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'filesystemiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'path' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'directory' => 'string',
        'flags=' => 'int',
      ),
    ),
    'filesystemiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'filesystemiterator::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'filesystemiterator::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'filesystemiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'filesystemiterator::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'filesystemiterator::setflags' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags' => 'int',
      ),
    ),
    'filesystemiterator::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'filter_has_var' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'type' => '0|1|2|4|5',
        'variable_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'input_type' => '0|1|2|4|5',
        'var_name' => 'string',
      ),
    ),
    'filter_id' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filtername' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'name' => 'string',
      ),
    ),
    'filter_input' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed|null',
        'type' => '0|1|2|4|5',
        'variable_name' => 'string',
        'filter=' => 'int',
        'options=' => 'array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'false|mixed|null',
        'type' => '0|1|2|4|5',
        'var_name' => 'string',
        'filter=' => 'int',
        'options=' => 'array<array-key, mixed>|int',
      ),
    ),
    'filter_input_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'type' => '0|1|2|4|5',
        'definition=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'type' => '0|1|2|4|5',
        'options=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
    ),
    'filter_var' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'variable' => 'mixed',
        'filter=' => 'int',
        'options=' => 'array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'value' => 'mixed',
        'filter=' => 'int',
        'options=' => 'array<array-key, mixed>|int',
      ),
    ),
    'filter_var_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'data' => 'array<array-key, mixed>',
        'definition=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'array' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|int',
        'add_empty=' => 'bool',
      ),
    ),
    'finfo::buffer' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'options=' => 'int',
        'context=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'flags=' => 'int',
        'context=' => 'null|resource',
      ),
    ),
    'finfo::file' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'options=' => 'int',
        'context=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'flags=' => 'int',
        'context=' => 'null|resource',
      ),
    ),
    'finfo::set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'options' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
    ),
    'finfo_buffer' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'string' => 'string',
        'options=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'string' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'finfo_file' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'filename' => 'string',
        'options=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'filename' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'finfo_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'options=' => 'int',
        'arg=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'flags=' => 'int',
        'magic_database=' => 'null|string',
      ),
    ),
    'finfo_set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'resource',
        'options' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'finfo' => 'resource',
        'flags' => 'int',
      ),
    ),
    'floatval' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'float',
        'value' => 'mixed',
      ),
    ),
    'flock' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
        'operation' => 'int',
        '&w_wouldblock=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'operation' => 'int',
        '&w_would_block=' => 'int',
      ),
    ),
    'floor' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float|int',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float|int',
      ),
    ),
    'fmod' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'x' => 'float',
        'y' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num1' => 'float',
        'num2' => 'float',
      ),
    ),
    'forward_static_call' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'function_name' => 'callable',
        '...parameters=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'callback' => 'callable',
        '...args=' => 'mixed',
      ),
    ),
    'forward_static_call_array' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'function_name' => 'callable',
        'parameters' => 'list<mixed>',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'callback' => 'callable',
        'args' => 'list<mixed>',
      ),
    ),
    'fpassthru' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
      ),
    ),
    'fprintf' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'format' => 'string',
        '...args=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'format' => 'string',
        '...values=' => 'float|int|string',
      ),
    ),
    'fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape_char=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'fputs' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'fread' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length' => 'int',
      ),
    ),
    'fscanf' => 
    array (
      'old' => 
      array (
        0 => 'list<mixed>',
        'stream' => 'resource',
        'format' => 'string',
        '&...vars=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'list<mixed>|null',
        'stream' => 'resource',
        'format' => 'string',
        '&...vars=' => 'mixed',
      ),
    ),
    'fseek' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'fsockopen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        '&w_errno=' => 'int',
        '&w_errstr=' => 'string',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
        'timeout=' => 'float|null',
      ),
    ),
    'fstat' => 
    array (
      'old' => 
      array (
        0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
        'stream' => 'resource',
      ),
    ),
    'ftell' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
      ),
    ),
    'ftok' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pathname' => 'string',
        'proj' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'filename' => 'string',
        'project_id' => 'string',
      ),
    ),
    'ftp_append' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
      ),
    ),
    'ftp_chmod' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'ftp' => 'resource',
        'mode' => 'int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ftp' => 'resource',
        'permissions' => 'int',
        'filename' => 'string',
      ),
    ),
    'ftp_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
    ),
    'ftp_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'file' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'filename' => 'string',
      ),
    ),
    'ftp_fget' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resumepos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_fput' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_get' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resume_pos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_fget' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resumepos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_fput' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_get' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resume_pos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_put' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_pasv' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'pasv' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'enable' => 'bool',
      ),
    ),
    'ftp_put' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'src' => 'string',
        'dest' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'ftp_site' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'cmd' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'command' => 'string',
      ),
    ),
    'ftp_ssl_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
    ),
    'ftruncate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'size' => 'int',
      ),
    ),
    'func_get_arg' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'arg_num' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'position' => 'int',
      ),
    ),
    'function_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'function_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'function' => 'string',
      ),
    ),
    'fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'get_browser' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|object',
        'browser_name=' => 'null|string',
        'return_array=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|object',
        'user_agent=' => 'null|string',
        'return_array=' => 'bool',
      ),
    ),
    'get_cfg_var' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'option_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'option' => 'string',
      ),
    ),
    'get_class_methods' => 
    array (
      'old' => 
      array (
        0 => 'list<non-falsy-string>|null',
        'class' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'list<non-falsy-string>',
        'object_or_class' => 'class-string|object',
      ),
    ),
    'get_class_vars' => 
    array (
      'old' => 
      array (
        0 => 'array<non-falsy-string, mixed>',
        'class_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<non-falsy-string, mixed>',
        'class' => 'string',
      ),
    ),
    'get_extension_funcs' => 
    array (
      'old' => 
      array (
        0 => 'false|list<callable-string>',
        'extension_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|list<callable-string>',
        'extension' => 'string',
      ),
    ),
    'get_headers' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'url' => 'string',
        'format=' => 'int',
        'context=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'url' => 'string',
        'associative=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'get_html_translation_table' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'table=' => 'int',
        'quote_style=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'table=' => 'int',
        'flags=' => 'int',
        'encoding=' => 'string',
      ),
    ),
    'get_object_vars' => 
    array (
      'old' => 
      array (
        0 => 'array<string, mixed>',
        'obj' => 'object',
      ),
      'new' => 
      array (
        0 => 'array<string, mixed>',
        'object' => 'object',
      ),
    ),
    'get_parent_class' => 
    array (
      'old' => 
      array (
        0 => 'class-string|false',
        'object=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'class-string|false',
        'object_or_class=' => 'class-string|object',
      ),
    ),
    'get_resource_type' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'res' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'resource' => 'resource',
      ),
    ),
    'get_resources' => 
    array (
      'old' => 
      array (
        0 => 'array<int, resource>',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<int, resource>',
        'type=' => 'null|string',
      ),
    ),
    'getdate' => 
    array (
      'old' => 
      array (
        0 => 'array{0: int, hours: int<0, 23>, mday: int<1, 31>, minutes: int<0, 59>, mon: int<1, 12>, month: \'April\'|\'August\'|\'December\'|\'February\'|\'January\'|\'July\'|\'June\'|\'March\'|\'May\'|\'November\'|\'October\'|\'September\', seconds: int<0, 59>, wday: int<0, 6>, weekday: \'Friday\'|\'Monday\'|\'Saturday\'|\'Sunday\'|\'Thursday\'|\'Tuesday\'|\'Wednesday\', yday: int<0, 365>, year: int}',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array{0: int, hours: int<0, 23>, mday: int<1, 31>, minutes: int<0, 59>, mon: int<1, 12>, month: \'April\'|\'August\'|\'December\'|\'February\'|\'January\'|\'July\'|\'June\'|\'March\'|\'May\'|\'November\'|\'October\'|\'September\', seconds: int<0, 59>, wday: int<0, 6>, weekday: \'Friday\'|\'Monday\'|\'Saturday\'|\'Sunday\'|\'Thursday\'|\'Tuesday\'|\'Wednesday\', yday: int<0, 365>, year: int}',
        'timestamp=' => 'int|null',
      ),
    ),
    'getenv' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'varname=' => 'string',
        'local_only=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'name=' => 'null|string',
        'local_only=' => 'bool',
      ),
    ),
    'gethostbyaddr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ip_address' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ip' => 'string',
      ),
    ),
    'getimagesize' => 
    array (
      'old' => 
      array (
        0 => 'array{0: int, 1: int, 2: int, 3: string, bits?: int, channels?: 3|4, mime: string}|false',
        'imagefile' => 'string',
        '&w_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array{0: int, 1: int, 2: int, 3: string, bits?: int, channels?: 3|4, mime: string}|false',
        'filename' => 'string',
        '&w_image_info=' => 'array<array-key, mixed>',
      ),
    ),
    'getimagesizefromstring' => 
    array (
      'old' => 
      array (
        0 => 'array{0: int, 1: int, 2: int, 3: string, bits?: int, channels?: 3|4, mime: string}|false',
        'imagefile' => 'string',
        '&w_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array{0: int, 1: int, 2: int, 3: string, bits?: int, channels?: 3|4, mime: string}|false',
        'string' => 'string',
        '&w_image_info=' => 'array<array-key, mixed>',
      ),
    ),
    'getmxrr' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        '&w_mxhosts' => 'array<int, string>',
        '&w_weight=' => 'array<int, int>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hostname' => 'string',
        '&w_hosts' => 'array<int, string>',
        '&w_weights=' => 'array<int, int>',
      ),
    ),
    'getopt' => 
    array (
      'old' => 
      array (
        0 => 'array<string, false|list<false|string>|string>|false',
        'options' => 'string',
        'opts=' => 'array<array-key, mixed>',
        '&w_optind=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<string, false|list<false|string>|string>|false',
        'short_options' => 'string',
        'long_options=' => 'array<array-key, mixed>',
        '&w_rest_index=' => 'int',
      ),
    ),
    'getprotobyname' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'protocol' => 'string',
      ),
    ),
    'getprotobynumber' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'proto' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'protocol' => 'int',
      ),
    ),
    'getrusage' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'who=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'mode=' => 'int',
      ),
    ),
    'gettimeofday' => 
    array (
      'old' => 
      array (
        0 => 'array<string, int>',
        'get_as_float=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<string, int>',
        'as_float=' => 'bool',
      ),
    ),
    'gettype' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'value' => 'mixed',
      ),
    ),
    'globiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'path' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'pattern' => 'string',
        'flags=' => 'int',
      ),
    ),
    'globiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'globiterator::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'globiterator::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'globiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'globiterator::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'globiterator::setflags' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags' => 'int',
      ),
    ),
    'globiterator::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'gmdate' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'format' => 'string',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
        'timestamp=' => 'int|null',
      ),
    ),
    'gmmktime' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'hour=' => 'int',
        'min=' => 'int',
        'sec=' => 'int',
        'mon=' => 'int',
        'day=' => 'int',
        'year=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'hour' => 'int',
        'minute=' => 'int|null',
        'second=' => 'int|null',
        'month=' => 'int|null',
        'day=' => 'int|null',
        'year=' => 'int|null',
      ),
    ),
    'gmp_abs' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_add' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_and' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_binomial' => 
    array (
      'old' => 
      array (
        0 => 'GMP|false',
        'a' => 'GMP|int|string',
        'b' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'n' => 'GMP|int|string',
        'k' => 'int',
      ),
    ),
    'gmp_clrbit' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'a' => 'GMP',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'num' => 'GMP',
        'index' => 'int',
      ),
    ),
    'gmp_cmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_com' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_div' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
        'round=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
        'rounding_mode=' => 'int',
      ),
    ),
    'gmp_div_q' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
        'round=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
        'rounding_mode=' => 'int',
      ),
    ),
    'gmp_div_qr' => 
    array (
      'old' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
        'round=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
        'rounding_mode=' => 'int',
      ),
    ),
    'gmp_div_r' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
        'round=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
        'rounding_mode=' => 'int',
      ),
    ),
    'gmp_divexact' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_export' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'gmpnumber' => 'GMP|int|string',
        'word_size=' => 'int',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'GMP|int|string',
        'word_size=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'gmp_fact' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'int',
      ),
    ),
    'gmp_gcd' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_gcdext' => 
    array (
      'old' => 
      array (
        0 => 'array<string, GMP>',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'array<string, GMP>',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_hamdist' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_import' => 
    array (
      'old' => 
      array (
        0 => 'GMP|false',
        'data' => 'string',
        'word_size=' => 'int',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'data' => 'string',
        'word_size=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'gmp_init' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'number' => 'int|string',
        'base=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'int|string',
        'base=' => 'int',
      ),
    ),
    'gmp_intval' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'gmpnumber' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_invert' => 
    array (
      'old' => 
      array (
        0 => 'GMP|false',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP|false',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_jacobi' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_kronecker' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_lcm' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_legendre' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_mod' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_mul' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_neg' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_nextprime' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_or' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_perfect_power' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_perfect_square' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_popcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_pow' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'base' => 'GMP|int|string',
        'exp' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
        'exponent' => 'int',
      ),
    ),
    'gmp_powm' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'base' => 'GMP|int|string',
        'exp' => 'GMP|int|string',
        'mod' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
        'exponent' => 'GMP|int|string',
        'modulus' => 'GMP|int|string',
      ),
    ),
    'gmp_prob_prime' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'reps=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'num' => 'GMP|int|string',
        'repetitions=' => 'int',
      ),
    ),
    'gmp_root' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'nth' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
        'nth' => 'int',
      ),
    ),
    'gmp_rootrem' => 
    array (
      'old' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'a' => 'GMP|int|string',
        'nth' => 'int',
      ),
      'new' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'num' => 'GMP|int|string',
        'nth' => 'int',
      ),
    ),
    'gmp_scan0' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'start' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'start' => 'int',
      ),
    ),
    'gmp_scan1' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
        'start' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'GMP|int|string',
        'start' => 'int',
      ),
    ),
    'gmp_setbit' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'a' => 'GMP',
        'index' => 'int',
        'set_clear=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'num' => 'GMP',
        'index' => 'int',
        'value=' => 'bool',
      ),
    ),
    'gmp_sign' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_sqrt' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_sqrtrem' => 
    array (
      'old' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'a' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'array{0: GMP, 1: GMP}',
        'num' => 'GMP|int|string',
      ),
    ),
    'gmp_strval' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'gmpnumber' => 'GMP|int|string',
        'base=' => 'int',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'num' => 'GMP|int|string',
        'base=' => 'int',
      ),
    ),
    'gmp_sub' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmp_testbit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'a' => 'GMP|int|string',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'GMP|int|string',
        'index' => 'int',
      ),
    ),
    'gmp_xor' => 
    array (
      'old' => 
      array (
        0 => 'GMP',
        'a' => 'GMP|int|string',
        'b' => 'GMP|int|string',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'num1' => 'GMP|int|string',
        'num2' => 'GMP|int|string',
      ),
    ),
    'gmstrftime' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        'timestamp=' => 'int|null',
      ),
    ),
    'grapheme_extract' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'arg1' => 'string',
        'arg2' => 'int',
        'arg3=' => 'int',
        'arg4=' => 'int',
        '&w_arg5=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'size' => 'int',
        'type=' => 'int',
        'offset=' => 'int',
        '&w_next=' => 'int',
      ),
    ),
    'grapheme_stristr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
      ),
    ),
    'grapheme_strstr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
      ),
    ),
    'grapheme_substr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'start' => 'int',
        'length=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'gzclose' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'gzdecode' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_decoded_len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_length=' => 'int',
      ),
    ),
    'gzeof' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'gzgetc' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
      ),
    ),
    'gzgets' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int|null',
      ),
    ),
    'gzinflate' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_decoded_len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_length=' => 'int',
      ),
    ),
    'gzpassthru' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
      ),
    ),
    'gzputs' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'gzread' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length' => 'int',
      ),
    ),
    'gzrewind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'gzseek' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'gztell' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
      ),
    ),
    'gzuncompress' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_decoded_len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_length=' => 'int',
      ),
    ),
    'gzwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'hash' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'algo' => 'string',
        'data' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'hash_file' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'hash_final' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-string',
        'context' => 'HashContext',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'context' => 'HashContext',
        'binary=' => 'bool',
      ),
    ),
    'hash_hkdf' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'ikm' => 'string',
        'algo' => 'string',
        'length=' => 'int',
        'string=' => 'string',
        'salt=' => 'string',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'key' => 'string',
        'length=' => 'int',
        'info=' => 'string',
        'salt=' => 'string',
      ),
    ),
    'hash_hmac' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'key' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'key' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'hash_hmac_file' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'key' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'key' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'hash_init' => 
    array (
      'old' => 
      array (
        0 => 'HashContext|false',
        'algo' => 'string',
        'options=' => 'int',
        'key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'HashContext',
        'algo' => 'string',
        'flags=' => 'int',
        'key=' => 'string',
      ),
    ),
    'hash_pbkdf2' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'password' => 'string',
        'salt' => 'string',
        'iterations' => 'int',
        'length=' => 'int',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'password' => 'string',
        'salt' => 'string',
        'iterations' => 'int',
        'length=' => 'int',
        'binary=' => 'bool',
      ),
    ),
    'hash_update_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'filename' => 'string',
        'stream_context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'filename' => 'string',
        'stream_context=' => 'null|resource',
      ),
    ),
    'hash_update_stream' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'context' => 'HashContext',
        'handle' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'context' => 'HashContext',
        'stream' => 'resource',
        'length=' => 'int',
      ),
    ),
    'header' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'header' => 'string',
        'replace=' => 'bool',
        'http_response_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'header' => 'string',
        'replace=' => 'bool',
        'response_code=' => 'int',
      ),
    ),
    'header_remove' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name=' => 'null|string',
      ),
    ),
    'headers_sent' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&w_file=' => 'string',
        '&w_line=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&w_filename=' => 'string',
        '&w_line=' => 'int',
      ),
    ),
    'hebrev' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'max_chars_per_line=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'max_chars_per_line=' => 'int',
      ),
    ),
    'hex2bin' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
      ),
    ),
    'hexdec' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'hexadecimal_number' => 'string',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'hex_string' => 'string',
      ),
    ),
    'highlight_file' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'file_name' => 'string',
        'return=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'filename' => 'string',
        'return=' => 'bool',
      ),
    ),
    'hrtime' => 
    array (
      'old' => 
      array (
        0 => 'array{0: int, 1: int}|false',
        'get_as_number' => 'false',
      ),
      'new' => 
      array (
        0 => 'array{0: int, 1: int}|false',
        'as_number=' => 'false',
      ),
    ),
    'html_entity_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'quote_style=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'flags=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'htmlentities' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'quote_style=' => 'int',
        'encoding=' => 'string',
        'double_encode=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'flags=' => 'int',
        'encoding=' => 'null|string',
        'double_encode=' => 'bool',
      ),
    ),
    'htmlspecialchars' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'quote_style=' => 'int',
        'encoding=' => 'null|string',
        'double_encode=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'flags=' => 'int',
        'encoding=' => 'null|string',
        'double_encode=' => 'bool',
      ),
    ),
    'htmlspecialchars_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'quote_style=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'flags=' => 'int',
      ),
    ),
    'http_build_query' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'formdata' => 'array<array-key, mixed>|object',
        'prefix=' => 'string',
        'arg_separator=' => 'null|string',
        'enc_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'data' => 'array<array-key, mixed>|object',
        'numeric_prefix=' => 'string',
        'arg_separator=' => 'null|string',
        'encoding_type=' => 'int',
      ),
    ),
    'hypot' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'num1' => 'float',
        'num2' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'x' => 'float',
        'y' => 'float',
      ),
    ),
    'iconv' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'in_charset' => 'string',
        'out_charset' => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'from_encoding' => 'string',
        'to_encoding' => 'string',
        'string' => 'string',
      ),
    ),
    'iconv_mime_decode' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'encoded_string' => 'string',
        'mode=' => 'int',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'mode=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'iconv_mime_decode_headers' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'headers' => 'string',
        'mode=' => 'int',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'headers' => 'string',
        'mode=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'iconv_mime_encode' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'field_name' => 'string',
        'field_value' => 'string',
        'preference=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'field_name' => 'string',
        'field_value' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'iconv_set_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'type' => 'string',
        'charset' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'type' => 'string',
        'encoding' => 'string',
      ),
    ),
    'iconv_strlen' => 
    array (
      'old' => 
      array (
        0 => 'false|int<0, max>',
        'str' => 'string',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int<0, max>',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'iconv_strpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'iconv_strrpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'iconv_substr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'str' => 'string',
        'offset' => 'int',
        'length=' => 'int',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
        'encoding=' => 'null|string',
      ),
    ),
    'idate' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'format' => 'string',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'format' => 'string',
        'timestamp=' => 'int|null',
      ),
    ),
    'idn_to_ascii' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'option=' => 'int',
        'variant=' => 'int',
        '&w_idn_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w_idna_info=' => 'array<array-key, mixed>',
      ),
    ),
    'idn_to_utf8' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'option=' => 'int',
        'variant=' => 'int',
        '&w_idn_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'domain' => 'string',
        'flags=' => 'int',
        'variant=' => 'int',
        '&w_idna_info=' => 'array<array-key, mixed>',
      ),
    ),
    'ignore_user_abort' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'value=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
        'enable=' => 'bool|null',
      ),
    ),
    'image_type_to_extension' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'imagetype' => 'int',
        'include_dot=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'image_type' => 'int',
        'include_dot=' => 'bool',
      ),
    ),
    'image_type_to_mime_type' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'imagetype' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'image_type' => 'int',
      ),
    ),
    'imageaffine' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'im' => 'resource',
        'affine' => 'array<array-key, mixed>',
        'clip=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'affine' => 'array<array-key, mixed>',
        'clip=' => 'array<array-key, mixed>|null',
      ),
    ),
    'imageaffinematrixconcat' => 
    array (
      'old' => 
      array (
        0 => 'array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}|false',
        'm1' => 'array<array-key, mixed>',
        'm2' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}|false',
        'matrix1' => 'array<array-key, mixed>',
        'matrix2' => 'array<array-key, mixed>',
      ),
    ),
    'imageaffinematrixget' => 
    array (
      'old' => 
      array (
        0 => 'array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}|false',
        'type' => 'int',
        'options=' => 'array<array-key, mixed>|float',
      ),
      'new' => 
      array (
        0 => 'array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float}|false',
        'type' => 'int',
        'options' => 'array<array-key, mixed>|float',
      ),
    ),
    'imagealphablending' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'blend' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imageantialias' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'on' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imagearc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'cx' => 'int',
        'cy' => 'int',
        'w' => 'int',
        'h' => 'int',
        's' => 'int',
        'e' => 'int',
        'col' => 'int',
      ),
      'new' => 
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
    ),
    'imagebmp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'compressed=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'compressed=' => 'bool',
      ),
    ),
    'imagechar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'c' => 'string',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
    ),
    'imagecharup' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'c' => 'string',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
    ),
    'imagecolorallocate' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
    ),
    'imagecolorallocatealpha' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
    ),
    'imagecolorat' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'im' => 'resource',
        'x' => 'int',
        'y' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
      ),
    ),
    'imagecolorclosest' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
    ),
    'imagecolorclosestalpha' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
    ),
    'imagecolorclosesthwb' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
    ),
    'imagecolordeallocate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'color' => 'int',
      ),
    ),
    'imagecolorexact' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
    ),
    'imagecolorexactalpha' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
    ),
    'imagecolormatch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im1' => 'resource',
        'im2' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image1' => 'GdImage',
        'image2' => 'GdImage',
      ),
    ),
    'imagecolorresolve' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
      ),
    ),
    'imagecolorresolvealpha' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha' => 'int',
      ),
    ),
    'imagecolorset' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'im' => 'resource',
        'color' => 'int',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'image' => 'GdImage',
        'color' => 'int',
        'red' => 'int',
        'green' => 'int',
        'blue' => 'int',
        'alpha=' => 'int',
      ),
    ),
    'imagecolorsforindex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'im' => 'resource',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'image' => 'GdImage',
        'color' => 'int',
      ),
    ),
    'imagecolorstotal' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
      ),
    ),
    'imagecolortransparent' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
        'col=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
        'color=' => 'int|null',
      ),
    ),
    'imageconvolution' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'matrix3x3' => 'array<array-key, mixed>',
        'div' => 'float',
        'offset' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'matrix' => 'array<array-key, mixed>',
        'divisor' => 'float',
        'offset' => 'float',
      ),
    ),
    'imagecopy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
      ),
      'new' => 
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
    ),
    'imagecopymerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
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
    ),
    'imagecopymergegray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
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
    ),
    'imagecopyresampled' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_w' => 'int',
        'dst_h' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
      ),
      'new' => 
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
    ),
    'imagecopyresized' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_w' => 'int',
        'dst_h' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
      ),
      'new' => 
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
    ),
    'imagecreate' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'x_size' => 'int',
        'y_size' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'width' => 'int',
        'height' => 'int',
      ),
    ),
    'imagecreatefrombmp' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromgd' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromgd2' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromgd2part' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
        'srcX' => 'int',
        'srcY' => 'int',
        'width' => 'int',
        'height' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
        'x' => 'int',
        'y' => 'int',
        'width' => 'int',
        'height' => 'int',
      ),
    ),
    'imagecreatefromgif' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromjpeg' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefrompng' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromstring' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'image' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'data' => 'string',
      ),
    ),
    'imagecreatefromwbmp' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromwebp' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromxbm' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatefromxpm' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'filename' => 'string',
      ),
    ),
    'imagecreatetruecolor' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'x_size' => 'int',
        'y_size' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'width' => 'int',
        'height' => 'int',
      ),
    ),
    'imagecrop' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'im' => 'resource',
        'rect' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'rectangle' => 'array<array-key, mixed>',
      ),
    ),
    'imagecropauto' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'im' => 'resource',
        'mode=' => 'int',
        'threshold=' => 'float',
        'color=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'mode=' => 'int',
        'threshold=' => 'float',
        'color=' => 'int',
      ),
    ),
    'imagedashedline' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imagedestroy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
      ),
    ),
    'imageellipse' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'cx' => 'int',
        'cy' => 'int',
        'w' => 'int',
        'h' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefill' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilledarc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'cx' => 'int',
        'cy' => 'int',
        'w' => 'int',
        'h' => 'int',
        's' => 'int',
        'e' => 'int',
        'col' => 'int',
        'style' => 'int',
      ),
      'new' => 
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
    ),
    'imagefilledellipse' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'cx' => 'int',
        'cy' => 'int',
        'w' => 'int',
        'h' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilledpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_pos' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color=' => 'int|null',
      ),
    ),
    'imagefilledrectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilltoborder' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'border' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'border_color' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilter' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'filtertype' => 'int',
        'arg1=' => 'array<array-key, mixed>|bool|float|int',
        'arg2=' => 'mixed',
        'arg3=' => 'mixed',
        'arg4=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'filter' => 'int',
        '...args=' => 'array<array-key, mixed>|bool|float|int',
      ),
    ),
    'imageflip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'mode' => 'int',
      ),
    ),
    'imageftbbox' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'size' => 'float',
        'angle' => 'float',
        'font_file' => 'string',
        'text' => 'string',
        'extrainfo=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'size' => 'float',
        'angle' => 'float',
        'font_filename' => 'string',
        'string' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'imagefttext' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'im' => 'resource',
        'size' => 'float',
        'angle' => 'float',
        'x' => 'int',
        'y' => 'int',
        'col' => 'int',
        'font_file' => 'string',
        'text' => 'string',
        'extrainfo=' => 'array<array-key, mixed>',
      ),
      'new' => 
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
    ),
    'imagegammacorrect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'inputgamma' => 'float',
        'outputgamma' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'input_gamma' => 'float',
        'output_gamma' => 'float',
      ),
    ),
    'imagegd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|string',
      ),
    ),
    'imagegd2' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'chunk_size=' => 'int',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|string',
        'chunk_size=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'imagegetclip' => 
    array (
      'old' => 
      array (
        0 => 'array<int, int>|false',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<int, int>',
        'image' => 'GdImage',
      ),
    ),
    'imagegif' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
      ),
    ),
    'imagegrabscreen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
      ),
    ),
    'imagegrabwindow' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'window_handle' => 'int',
        'client_area=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'handle' => 'int',
        'client_area=' => 'int',
      ),
    ),
    'imageinterlace' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'im' => 'resource',
        'interlace=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable=' => 'bool|null',
      ),
    ),
    'imageistruecolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
      ),
    ),
    'imagejpeg' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'quality=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'quality=' => 'int',
      ),
    ),
    'imagelayereffect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'effect' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'effect' => 'int',
      ),
    ),
    'imageline' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imageopenpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_pos' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color=' => 'int|null',
      ),
    ),
    'imagepalettecopy' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dst' => 'resource',
        'src' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'dst' => 'GdImage',
        'src' => 'GdImage',
      ),
    ),
    'imagepalettetotruecolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
      ),
    ),
    'imagepng' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'quality=' => 'int',
        'filters=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'quality=' => 'int',
        'filters=' => 'int',
      ),
    ),
    'imagepolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_pos' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color=' => 'int|null',
      ),
    ),
    'imagerectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imageresolution' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'im' => 'resource',
        'res_x=' => 'int',
        'res_y=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'image' => 'GdImage',
        'resolution_x=' => 'int|null',
        'resolution_y=' => 'int|null',
      ),
    ),
    'imagerotate' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'im' => 'resource',
        'angle' => 'float',
        'bgdcolor' => 'int',
        'ignoretransparent=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'angle' => 'float',
        'background_color' => 'int',
        'ignore_transparent=' => 'bool',
      ),
    ),
    'imagesavealpha' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'save' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imagescale' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'im' => 'resource',
        'new_width' => 'int',
        'new_height=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'width' => 'int',
        'height=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'imagesetbrush' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'brush' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'brush' => 'GdImage',
      ),
    ),
    'imagesetclip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
      ),
    ),
    'imagesetinterpolation' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'method=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'method=' => 'int',
      ),
    ),
    'imagesetpixel' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
    ),
    'imagesetstyle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'styles' => 'non-empty-array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'style' => 'non-empty-array<array-key, mixed>',
      ),
    ),
    'imagesetthickness' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'thickness' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'thickness' => 'int',
      ),
    ),
    'imagesettile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'tile' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'tile' => 'GdImage',
      ),
    ),
    'imagestring' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'str' => 'string',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
    ),
    'imagestringup' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'str' => 'string',
        'col' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
    ),
    'imagesx' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
      ),
    ),
    'imagesy' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'im' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'image' => 'GdImage',
      ),
    ),
    'imagetruecolortopalette' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'ditherFlag' => 'bool',
        'colorsWanted' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'dither' => 'bool',
        'num_colors' => 'int',
      ),
    ),
    'imagettfbbox' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'size' => 'float',
        'angle' => 'float',
        'font_file' => 'string',
        'text' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'size' => 'float',
        'angle' => 'float',
        'font_filename' => 'string',
        'string' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'imagettftext' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'im' => 'resource',
        'size' => 'float',
        'angle' => 'float',
        'x' => 'int',
        'y' => 'int',
        'col' => 'int',
        'font_file' => 'string',
        'text' => 'string',
      ),
      'new' => 
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
    ),
    'imagewbmp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'foreground=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'foreground_color=' => 'int|null',
      ),
    ),
    'imagewebp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'to=' => 'null|resource|string',
        'quality=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
        'quality=' => 'int',
      ),
    ),
    'imagexbm' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'filename' => 'null|string',
        'foreground=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'filename' => 'null|string',
        'foreground_color=' => 'int|null',
      ),
    ),
    'imagick::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'files=' => 'array<array-key, string>|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'files=' => 'array<array-key, string>|null|string',
      ),
    ),
    'imagick::adaptiveresizeimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'bestfit=' => 'bool',
        'legacy=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'bestfit=' => 'bool',
        'legacy=' => 'bool',
      ),
    ),
    'imagick::autogammaimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'channel=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'channel=' => 'int|null',
      ),
    ),
    'imagick::autolevelimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'channel=' => 'int',
      ),
    ),
    'imagick::autoorient' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'imagick::blackthresholdimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'threshold_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'threshold_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::blueshiftimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'factor=' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'factor=' => 'float',
      ),
    ),
    'imagick::borderimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'border_color' => 'mixed',
        'width' => 'int',
        'height' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'border_color' => 'ImagickPixel|string',
        'width' => 'int',
        'height' => 'int',
      ),
    ),
    'imagick::brightnesscontrastimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brightness' => 'string',
        'contrast' => 'string',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'brightness' => 'float',
        'contrast' => 'float',
        'channel=' => 'int',
      ),
    ),
    'imagick::clampimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'channel=' => 'int',
      ),
    ),
    'imagick::clipimagepath' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'pathname' => 'string',
        'inside' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'pathname' => 'string',
        'inside' => 'bool',
      ),
    ),
    'imagick::clutimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lookup_table' => 'Imagick',
        'channel=' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lookup_table' => 'Imagick',
        'channel=' => 'int',
      ),
    ),
    'imagick::colorizeimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'colorize_color' => 'mixed',
        'opacity_color' => 'mixed',
        'legacy=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'colorize_color' => 'ImagickPixel|string',
        'opacity_color' => 'ImagickPixel|false|string',
        'legacy=' => 'bool|null',
      ),
    ),
    'imagick::colormatriximage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color_matrix' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color_matrix' => 'array<array-key, mixed>',
      ),
    ),
    'imagick::count' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'mode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode=' => 'int',
      ),
    ),
    'imagick::deleteimageproperty' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
    ),
    'imagick::floodfillpaintimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fill_color' => 'mixed',
        'fuzz' => 'float',
        'border_color' => 'mixed',
        'x' => 'int',
        'y' => 'int',
        'invert' => 'bool',
        'channel=' => 'int',
      ),
      'new' => 
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
    ),
    'imagick::forwardfouriertransformimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'magnitude' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'magnitude' => 'bool',
      ),
    ),
    'imagick::frameimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'matte_color' => 'mixed',
        'width' => 'int',
        'height' => 'int',
        'inner_bevel' => 'int',
        'outer_bevel' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'matte_color' => 'ImagickPixel|string',
        'width' => 'int',
        'height' => 'int',
        'inner_bevel' => 'int',
        'outer_bevel' => 'int',
      ),
    ),
    'imagick::getconfigureoptions' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'pattern=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'pattern=' => 'string',
      ),
    ),
    'imagick::getfont' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'imagick::gethdrienabled' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'imagick::getimagealphachannel' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'imagick::getimageartifact' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'artifact' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'artifact' => 'string',
      ),
    ),
    'imagick::getimageproperty' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'name' => 'string',
      ),
    ),
    'imagick::getregistry' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
    ),
    'imagick::identifyformat' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
      ),
    ),
    'imagick::inversefouriertransformimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'complement' => 'Imagick',
        'magnitude' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'complement' => 'Imagick',
        'magnitude' => 'bool',
      ),
    ),
    'imagick::key' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'imagick::localcontrastimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'radius' => 'float',
        'strength' => 'float',
      ),
      'new' => 
      array (
        0 => 'void',
        'radius' => 'float',
        'strength' => 'float',
      ),
    ),
    'imagick::morphology' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'morphology' => 'int',
        'iterations' => 'int',
        'kernel' => 'ImagickKernel',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'morphology' => 'int',
        'iterations' => 'int',
        'kernel' => 'ImagickKernel',
        'channel=' => 'int',
      ),
    ),
    'imagick::newimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'background_color' => 'mixed',
        'format=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'background_color' => 'ImagickPixel|string',
        'format=' => 'string',
      ),
    ),
    'imagick::opaquepaintimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'target_color' => 'mixed',
        'fill_color' => 'mixed',
        'fuzz' => 'float',
        'invert' => 'bool',
        'channel=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'target_color' => 'ImagickPixel|string',
        'fill_color' => 'ImagickPixel|string',
        'fuzz' => 'float',
        'invert' => 'bool',
        'channel=' => 'int',
      ),
    ),
    'imagick::pingimagefile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'filename=' => 'null|string',
      ),
    ),
    'imagick::profileimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'profile' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'profile' => 'null|string',
      ),
    ),
    'imagick::queryfontmetrics' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'settings' => 'ImagickDraw',
        'text' => 'string',
        'multiline=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'settings' => 'ImagickDraw',
        'text' => 'string',
        'multiline=' => 'bool|null',
      ),
    ),
    'imagick::readimageblob' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'string',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'string',
        'filename=' => 'null|string',
      ),
    ),
    'imagick::readimagefile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'filename=' => 'null|string',
      ),
    ),
    'imagick::readimages' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'filenames' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filenames' => 'array<array-key, mixed>',
      ),
    ),
    'imagick::resizeimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'filter' => 'int',
        'blur' => 'float',
        'bestfit=' => 'bool',
        'legacy=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'filter' => 'int',
        'blur' => 'float',
        'bestfit=' => 'bool',
        'legacy=' => 'bool',
      ),
    ),
    'imagick::rotateimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'background_color' => 'mixed',
        'degrees' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'background_color' => 'ImagickPixel|string',
        'degrees' => 'float',
      ),
    ),
    'imagick::rotationalblurimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'angle' => 'string',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'angle' => 'float',
        'channel=' => 'int',
      ),
    ),
    'imagick::scaleimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'bestfit=' => 'bool',
        'legacy=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'bestfit=' => 'bool',
        'legacy=' => 'bool',
      ),
    ),
    'imagick::selectiveblurimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'radius' => 'float',
        'sigma' => 'float',
        'threshold' => 'float',
        'channel=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'radius' => 'float',
        'sigma' => 'float',
        'threshold' => 'float',
        'channel=' => 'int',
      ),
    ),
    'imagick::setantialias' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'antialias' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'antialias' => 'bool',
      ),
    ),
    'imagick::setbackgroundcolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'background_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'background_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::setimageartifact' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'artifact' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'artifact' => 'string',
        'value' => 'null|string',
      ),
    ),
    'imagick::setimagebackgroundcolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'background_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'background_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::setimagebordercolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'border_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'border_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::setimagechannelmask' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'channel' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'channel' => 'int',
      ),
    ),
    'imagick::setimagemattecolor' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'matte_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'matte_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::setprogressmonitor' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable',
      ),
    ),
    'imagick::setregistry' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'string',
      ),
    ),
    'imagick::shearimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'background_color' => 'mixed',
        'x_shear' => 'float',
        'y_shear' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'background_color' => 'ImagickPixel|string',
        'x_shear' => 'float',
        'y_shear' => 'float',
      ),
    ),
    'imagick::similarityimage' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&offset=' => 'array<array-key, mixed>',
        '&similarity=' => 'float',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&offset=' => 'array<array-key, mixed>|null',
        '&similarity=' => 'float|null',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
    ),
    'imagick::smushimages' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'stack' => 'string',
        'offset' => 'string',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'stack' => 'bool',
        'offset' => 'int',
      ),
    ),
    'imagick::statisticimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'type' => 'int',
        'width' => 'int',
        'height' => 'int',
        'channel=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'type' => 'int',
        'width' => 'int',
        'height' => 'int',
        'channel=' => 'int',
      ),
    ),
    'imagick::subimagematch' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w_offset=' => 'array<array-key, mixed>',
        '&w_similarity=' => 'float',
        'threshold=' => 'mixed',
        'metric=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'image' => 'Imagick',
        '&w_offset=' => 'array<array-key, mixed>|null',
        '&w_similarity=' => 'float|null',
        'threshold=' => 'float',
        'metric=' => 'int',
      ),
    ),
    'imagick::textureimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'texture' => 'Imagick',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'texture' => 'Imagick',
      ),
    ),
    'imagick::thumbnailimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'columns' => 'int',
        'rows' => 'int',
        'bestfit=' => 'bool',
        'fill=' => 'bool',
        'legacy=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'columns' => 'int|null',
        'rows' => 'int|null',
        'bestfit=' => 'bool',
        'fill=' => 'bool',
        'legacy=' => 'bool',
      ),
    ),
    'imagick::tintimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'tint_color' => 'mixed',
        'opacity_color' => 'mixed',
        'legacy=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'tint_color' => 'ImagickPixel|string',
        'opacity_color' => 'ImagickPixel|string',
        'legacy=' => 'bool',
      ),
    ),
    'imagick::transparentpaintimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'target_color' => 'mixed',
        'alpha' => 'float',
        'fuzz' => 'float',
        'invert' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'target_color' => 'ImagickPixel|string',
        'alpha' => 'float',
        'fuzz' => 'float',
        'invert' => 'bool',
      ),
    ),
    'imagick::whitethresholdimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'threshold_color' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'threshold_color' => 'ImagickPixel|string',
      ),
    ),
    'imagick::writeimage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename=' => 'null|string',
      ),
    ),
    'imagick::writeimagefile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'format=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'format=' => 'null|string',
      ),
    ),
    'imagick::writeimagesfile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'format=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filehandle' => 'resource',
        'format=' => 'null|string',
      ),
    ),
    'imagickdraw::getclippath' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'imagickdraw::getfont' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'imagickdraw::getfontfamily' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'imagickdraw::gettextdirection' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'imagickdraw::resetvectorgraphics' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'imagickdraw::setopacity' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'opacity' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'opacity' => 'float',
      ),
    ),
    'imagickdraw::setresolution' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'resolution_x' => 'float',
        'resolution_y' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'resolution_x' => 'float',
        'resolution_y' => 'float',
      ),
    ),
    'imagickdraw::settextinterlinespacing' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'spacing' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'spacing' => 'float',
      ),
    ),
    'imagickdraw::settextinterwordspacing' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'spacing' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'spacing' => 'float',
      ),
    ),
    'imagickdraw::settextkerning' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'kerning' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'kerning' => 'float',
      ),
    ),
    'imagickkernel::addunitykernel' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'scale' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'scale' => 'float',
      ),
    ),
    'imagickkernel::frombuiltin' => 
    array (
      'old' => 
      array (
        0 => 'ImagickKernel',
        'kernel' => 'string',
        'shape' => 'string',
      ),
      'new' => 
      array (
        0 => 'ImagickKernel',
        'kernel' => 'int',
        'shape' => 'string',
      ),
    ),
    'imagickkernel::frommatrix' => 
    array (
      'old' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin' => 'array<array-key, mixed>|null',
      ),
    ),
    'imagickkernel::scale' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'scale' => 'mixed',
        'normalize_kernel=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'scale' => 'float',
        'normalize_kernel=' => 'int|null',
      ),
    ),
    'imagickpixel::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'color=' => 'null|string',
      ),
    ),
    'imagickpixel::ispixelsimilarquantum' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'string',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color' => 'string',
        'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
      ),
    ),
    'imagickpixel::setcolorcount' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color_count' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color_count' => 'int',
      ),
    ),
    'imagickpixel::setcolorvaluequantum' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color' => 'int',
        'value' => 'float',
      ),
    ),
    'imagickpixel::setindex' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index' => 'float',
      ),
    ),
    'imagickpixeliterator::key' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'imap_append' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'folder' => 'string',
        'message' => 'string',
        'options=' => 'string',
        'internal_date=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'folder' => 'string',
        'message' => 'string',
        'options=' => 'null|string',
        'internal_date=' => 'null|string',
      ),
    ),
    'imap_headerinfo' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'message_num' => 'int',
        'from_length=' => 'int',
        'subject_length=' => 'int',
        'default_host=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'message_num' => 'int',
        'from_length=' => 'int',
        'subject_length=' => 'int',
      ),
    ),
    'imap_mail' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'string',
        'cc=' => 'string',
        'bcc=' => 'string',
        'return_path=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'null|string',
        'cc=' => 'null|string',
        'bcc=' => 'null|string',
        'return_path=' => 'null|string',
      ),
    ),
    'imap_sort' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'criteria' => 'int',
        'reverse' => 'int',
        'flags=' => 'int',
        'search_criteria=' => 'string',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'criteria' => 'int',
        'reverse' => 'bool',
        'flags=' => 'int',
        'search_criteria=' => 'null|string',
        'charset=' => 'null|string',
      ),
    ),
    'implode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'glue' => 'string',
        'pieces' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array=' => 'array<array-key, mixed>|null',
      ),
    ),
    'inet_ntop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'in_addr' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ip' => 'string',
      ),
    ),
    'inet_pton' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ip_address' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ip' => 'string',
      ),
    ),
    'inflate_add' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'context' => 'resource',
        'encoded_data' => 'string',
        'flush_mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'context' => 'InflateContext',
        'data' => 'string',
        'flush_mode=' => 'int',
      ),
    ),
    'inflate_get_read_len' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'resource' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'context' => 'InflateContext',
      ),
    ),
    'inflate_get_status' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'resource' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'context' => 'InflateContext',
      ),
    ),
    'inflate_init' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'encoding' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'InflateContext|false',
        'encoding' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'ini_alter' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'varname' => 'string',
        'newvalue' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'string',
      ),
    ),
    'ini_get' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'varname' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'option' => 'string',
      ),
    ),
    'ini_restore' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'varname' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'option' => 'string',
      ),
    ),
    'ini_set' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'varname' => 'string',
        'newvalue' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'string',
      ),
    ),
    'intdiv' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dividend' => 'int',
        'divisor' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'num1' => 'int',
        'num2' => 'int',
      ),
    ),
    'interface_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'classname' => 'string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'interface' => 'string',
        'autoload=' => 'bool',
      ),
    ),
    'intl_error_name' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'errorCode' => 'int',
      ),
    ),
    'intl_is_failure' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arg1' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'errorCode' => 'int',
      ),
    ),
    'intlbreakiterator::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'locale_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
      ),
    ),
    'intlbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'key_type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
    ),
    'intlcal_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'field' => 'int',
        'amount' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'field' => 'int',
        'value' => 'int',
      ),
    ),
    'intlcal_after' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'otherCalendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcal_before' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'otherCalendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcal_create_instance' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'timeZone=' => 'mixed',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'timezone=' => 'mixed',
        'locale=' => 'null|string',
      ),
    ),
    'intlcal_equals' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'otherCalendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcal_field_difference' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'calendar' => 'IntlCalendar',
        'when' => 'float',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'calendar' => 'IntlCalendar',
        'timestamp' => 'float',
        'field' => 'int',
      ),
    ),
    'intlcal_from_date_time' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'dateTime' => 'DateTime|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'datetime' => 'DateTime|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlcal_get_keyword_values_for_locale' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'key' => 'string',
        'locale' => 'string',
        'commonlyUsed' => 'bool',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'keyword' => 'string',
        'locale' => 'string',
        'onlyCommon' => 'bool',
      ),
    ),
    'intlcal_get_locale' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'calendar' => 'IntlCalendar',
        'localeType' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'calendar' => 'IntlCalendar',
        'type' => 'int',
      ),
    ),
    'intlcal_is_equivalent_to' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'otherCalendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcal_is_weekend' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'date=' => 'float|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timestamp=' => 'float|null',
      ),
    ),
    'intlcal_roll' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'field' => 'int',
        'amountOrUpOrDown=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'field' => 'int',
        'value' => 'mixed',
      ),
    ),
    'intlcal_set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'fieldOrYear' => 'int',
        'valueOrMonth' => 'int',
        'dayOfMonth=' => 'mixed',
        'hour=' => 'mixed',
        'minute=' => 'mixed',
        'second=' => 'mixed',
      ),
      'new' => 
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
    ),
    'intlcal_set_lenient' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'isLenient' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'lenient' => 'bool',
      ),
    ),
    'intlcal_set_repeated_wall_time_option' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'option' => 'int',
      ),
    ),
    'intlcal_set_skipped_wall_time_option' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'option' => 'int',
      ),
    ),
    'intlcal_set_time' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'date' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timestamp' => 'float',
      ),
    ),
    'intlcal_set_time_zone' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timeZone' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timezone' => 'mixed',
      ),
    ),
    'intlcalendar::add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'amount' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'int',
      ),
    ),
    'intlcalendar::after' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcalendar::before' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcalendar::createinstance' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'timeZone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlcalendar::equals' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcalendar::fielddifference' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'when' => 'float',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'timestamp' => 'float',
        'field' => 'int',
      ),
    ),
    'intlcalendar::fromdatetime' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'dateTime' => 'DateTime|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'datetime' => 'DateTime|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlcalendar::getkeywordvaluesforlocale' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'key' => 'string',
        'locale' => 'string',
        'commonlyUsed' => 'bool',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'keyword' => 'string',
        'locale' => 'string',
        'onlyCommon' => 'bool',
      ),
    ),
    'intlcalendar::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'localeType' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
      ),
    ),
    'intlcalendar::isequivalentto' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlcalendar::isweekend' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date=' => 'float|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp=' => 'float|null',
      ),
    ),
    'intlcalendar::roll' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'amountOrUpOrDown' => 'bool|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'bool|int',
      ),
    ),
    'intlcalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fieldOrYear' => 'int',
        'valueOrMonth' => 'int',
        'dayOfMonth=' => 'mixed',
        'hour=' => 'mixed',
        'minute=' => 'mixed',
        'second=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
    ),
    'intlcalendar::setlenient' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'isLenient' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'lenient' => 'bool',
      ),
    ),
    'intlcalendar::setminimaldaysinfirstweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'numberOfDays' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
    ),
    'intlcalendar::setrepeatedwalltimeoption' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'option' => 'int',
      ),
    ),
    'intlcalendar::setskippedwalltimeoption' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'option' => 'int',
      ),
    ),
    'intlcalendar::settime' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp' => 'float',
      ),
    ),
    'intlcalendar::settimezone' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeZone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'intlchar::charfromname' => 
    array (
      'old' => 
      array (
        0 => 'int|null',
        'characterName' => 'string',
        'nameChoice=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|null',
        'name' => 'string',
        'type=' => 'int',
      ),
    ),
    'intlchar::charname' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'codepoint' => 'int|string',
        'nameChoice=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'codepoint' => 'int|string',
        'type=' => 'int',
      ),
    ),
    'intlchar::digit' => 
    array (
      'old' => 
      array (
        0 => 'false|int|null',
        'codepoint' => 'int|string',
        'radix=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int|null',
        'codepoint' => 'int|string',
        'base=' => 'int',
      ),
    ),
    'intlchar::enumcharnames' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'start' => 'int|string',
        'limit' => 'int|string',
        'callback' => 'callable(int, int, int):void',
        'nameChoice=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'start' => 'int|string',
        'end' => 'int|string',
        'callback' => 'callable(int, int, int):void',
        'type=' => 'int',
      ),
    ),
    'intlchar::enumchartypes' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback=' => 'callable(int, int, int):void',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback' => 'callable(int, int, int):void',
      ),
    ),
    'intlchar::fordigit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'digit' => 'int',
        'radix=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'digit' => 'int',
        'base=' => 'int',
      ),
    ),
    'intlchar::getpropertyname' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'property' => 'int',
        'nameChoice=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'property' => 'int',
        'type=' => 'int',
      ),
    ),
    'intlchar::getpropertyvaluename' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'property' => 'int',
        'value' => 'int',
        'nameChoice=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'property' => 'int',
        'value' => 'int',
        'type=' => 'int',
      ),
    ),
    'intlcodepointbreakiterator::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'locale_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
      ),
    ),
    'intlcodepointbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'key_type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
    ),
    'intldateformatter::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'datetype' => 'int|null',
        'timetype' => 'int|null',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'intldateformatter::create' => 
    array (
      'old' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'datetype' => 'int|null',
        'timetype' => 'int|null',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'intldateformatter::format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'args=' => 'DateTimeInterface|IntlCalendar|array{0?: int, 1?: int, 2?: int, 3?: int, 4?: int, 5?: int, 6?: int, 7?: int, 8?: int, tm_hour?: int, tm_isdst?: int, tm_mday?: int, tm_min?: int, tm_mon?: int, tm_sec?: int, tm_wday?: int, tm_yday?: int, tm_year?: int}|float|int|string',
        'array=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'datetime' => 'DateTimeInterface|IntlCalendar|array{0?: int, 1?: int, 2?: int, 3?: int, 4?: int, 5?: int, 6?: int, 7?: int, 8?: int, tm_hour?: int, tm_isdst?: int, tm_mday?: int, tm_min?: int, tm_mon?: int, tm_sec?: int, tm_wday?: int, tm_yday?: int, tm_year?: int}|float|int|string',
      ),
    ),
    'intldateformatter::formatobject' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'object' => 'DateTime|IntlCalendar',
        'format=' => 'array{0: int, 1: int}|int|null|string',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'datetime' => 'DateTimeInterface|IntlCalendar',
        'format=' => 'array{0: int, 1: int}|int|null|string',
        'locale=' => 'null|string',
      ),
    ),
    'intldateformatter::getcalendar' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
      ),
    ),
    'intldateformatter::getcalendarobject' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|false|null',
      ),
    ),
    'intldateformatter::getdatetype' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
      ),
    ),
    'intldateformatter::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type=' => 'int',
      ),
    ),
    'intldateformatter::getpattern' => 
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
    'intldateformatter::gettimetype' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
      ),
    ),
    'intldateformatter::gettimezoneid' => 
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
    'intldateformatter::localtime' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'string' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'string' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'intldateformatter::parse' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'string' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'string' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'intldateformatter::setcalendar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'which' => 'IntlCalendar|int|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar|int|null',
      ),
    ),
    'intldateformatter::setlenient' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lenient' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'lenient' => 'bool',
      ),
    ),
    'intldateformatter::settimezone' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'zone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'intlgregcal_create_instance' => 
    array (
      'old' => 
      array (
        0 => 'IntlGregorianCalendar|null',
        'timeZoneOrYear=' => 'DateTimeZone|IntlTimeZone|null|string',
        'localeOrMonth=' => 'int|null|string',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
      'new' => 
      array (
        0 => 'IntlGregorianCalendar|null',
        'timezoneOrYear=' => 'DateTimeZone|IntlTimeZone|null|string',
        'localeOrMonth=' => 'int|null|string',
        'day=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
    ),
    'intlgregcal_set_gregorian_change' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlGregorianCalendar',
        'date' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlGregorianCalendar',
        'timestamp' => 'float',
      ),
    ),
    'intlgregoriancalendar::add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'amount' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'int',
      ),
    ),
    'intlgregoriancalendar::after' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlgregoriancalendar::before' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlgregoriancalendar::createinstance' => 
    array (
      'old' => 
      array (
        0 => 'IntlGregorianCalendar|null',
        'timeZone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlGregorianCalendar|null',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlgregoriancalendar::equals' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlgregoriancalendar::fielddifference' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'when' => 'float',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'timestamp' => 'float',
        'field' => 'int',
      ),
    ),
    'intlgregoriancalendar::fromdatetime' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'dateTime' => 'DateTime|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'datetime' => 'DateTime|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlgregoriancalendar::getkeywordvaluesforlocale' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'key' => 'string',
        'locale' => 'string',
        'commonlyUsed' => 'bool',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'keyword' => 'string',
        'locale' => 'string',
        'onlyCommon' => 'bool',
      ),
    ),
    'intlgregoriancalendar::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'localeType' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
      ),
    ),
    'intlgregoriancalendar::isequivalentto' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlCalendar',
      ),
    ),
    'intlgregoriancalendar::isweekend' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date=' => 'float|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp=' => 'float|null',
      ),
    ),
    'intlgregoriancalendar::roll' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'amountOrUpOrDown' => 'bool|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'field' => 'int',
        'value' => 'bool|int',
      ),
    ),
    'intlgregoriancalendar::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fieldOrYear' => 'int',
        'valueOrMonth' => 'int',
        'dayOfMonth=' => 'mixed',
        'hour=' => 'mixed',
        'minute=' => 'mixed',
        'second=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'year' => 'int',
        'month' => 'int',
        'dayOfMonth=' => 'int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
      ),
    ),
    'intlgregoriancalendar::setgregorianchange' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp' => 'float',
      ),
    ),
    'intlgregoriancalendar::setlenient' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'isLenient' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'lenient' => 'bool',
      ),
    ),
    'intlgregoriancalendar::setminimaldaysinfirstweek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'numberOfDays' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'days' => 'int',
      ),
    ),
    'intlgregoriancalendar::setrepeatedwalltimeoption' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'option' => 'int',
      ),
    ),
    'intlgregoriancalendar::setskippedwalltimeoption' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'wallTimeOption' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'option' => 'int',
      ),
    ),
    'intlgregoriancalendar::settime' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp' => 'float',
      ),
    ),
    'intlgregoriancalendar::settimezone' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeZone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'intlrulebasedbreakiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'rules' => 'string',
        'areCompiled=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'rules' => 'string',
        'compiled=' => 'bool',
      ),
    ),
    'intlrulebasedbreakiterator::getlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'locale_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
      ),
    ),
    'intlrulebasedbreakiterator::getpartsiterator' => 
    array (
      'old' => 
      array (
        0 => 'IntlPartsIterator',
        'key_type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlPartsIterator',
        'type=' => 'string',
      ),
    ),
    'intltimezone::countequivalentids' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'zoneId' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'timezoneId' => 'string',
      ),
    ),
    'intltimezone::createtimezone' => 
    array (
      'old' => 
      array (
        0 => 'IntlTimeZone|null',
        'zoneId' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlTimeZone|null',
        'timezoneId' => 'string',
      ),
    ),
    'intltimezone::createtimezoneidenumeration' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'zoneType' => 'int',
        'region=' => 'null|string',
        'rawOffset=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'type' => 'int',
        'region=' => 'null|string',
        'rawOffset=' => 'int|null',
      ),
    ),
    'intltimezone::fromdatetimezone' => 
    array (
      'old' => 
      array (
        0 => 'IntlTimeZone|null',
        'zoneId' => 'DateTimeZone',
      ),
      'new' => 
      array (
        0 => 'IntlTimeZone|null',
        'timezone' => 'DateTimeZone',
      ),
    ),
    'intltimezone::getcanonicalid' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'zoneId' => 'string',
        '&w_isSystemID=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        '&w_isSystemId=' => 'bool',
      ),
    ),
    'intltimezone::getdisplayname' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'isDaylight=' => 'bool',
        'style=' => 'int',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'dst=' => 'bool',
        'style=' => 'int',
        'locale=' => 'null|string',
      ),
    ),
    'intltimezone::getequivalentid' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'zoneId' => 'string',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        'offset' => 'int',
      ),
    ),
    'intltimezone::getidforwindowsid' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'timezone' => 'string',
        'region=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        'region=' => 'null|string',
      ),
    ),
    'intltimezone::getoffset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'date' => 'float',
        'local' => 'bool',
        '&w_rawOffset' => 'int',
        '&w_dstOffset' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timestamp' => 'float',
        'local' => 'bool',
        '&w_rawOffset' => 'int',
        '&w_dstOffset' => 'int',
      ),
    ),
    'intltimezone::getregion' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'zoneId' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
      ),
    ),
    'intltimezone::getwindowsid' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'timezone' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
      ),
    ),
    'intltimezone::hassamerules' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'otherTimeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'bool',
        'other' => 'IntlTimeZone',
      ),
    ),
    'intltz_count_equivalent_ids' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'zoneId' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'timezoneId' => 'string',
      ),
    ),
    'intltz_create_time_zone' => 
    array (
      'old' => 
      array (
        0 => 'IntlTimeZone|null',
        'zoneId' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlTimeZone|null',
        'timezoneId' => 'string',
      ),
    ),
    'intltz_from_date_time_zone' => 
    array (
      'old' => 
      array (
        0 => 'IntlTimeZone|null',
        'dateTimeZone' => 'DateTimeZone',
      ),
      'new' => 
      array (
        0 => 'IntlTimeZone|null',
        'timezone' => 'DateTimeZone',
      ),
    ),
    'intltz_get_canonical_id' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'zoneId' => 'string',
        '&isSystemID=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        '&isSystemId=' => 'bool',
      ),
    ),
    'intltz_get_display_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'timeZone' => 'IntlTimeZone',
        'isDaylight=' => 'bool',
        'style=' => 'int',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezone' => 'IntlTimeZone',
        'dst=' => 'bool',
        'style=' => 'int',
        'locale=' => 'null|string',
      ),
    ),
    'intltz_get_dst_savings' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'int',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_get_equivalent_id' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'zoneId' => 'string',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'timezoneId' => 'string',
        'offset' => 'int',
      ),
    ),
    'intltz_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'int',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_get_error_message' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'string',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_get_id' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'string',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_get_offset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeZone' => 'IntlTimeZone',
        'date' => 'float',
        'local' => 'bool',
        '&rawOffset' => 'int',
        '&dstOffset' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'IntlTimeZone',
        'timestamp' => 'float',
        'local' => 'bool',
        '&rawOffset' => 'int',
        '&dstOffset' => 'int',
      ),
    ),
    'intltz_get_raw_offset' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'int',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_has_same_rules' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeZone' => 'IntlTimeZone',
        'otherTimeZone=' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'IntlTimeZone',
        'other' => 'IntlTimeZone',
      ),
    ),
    'intltz_to_date_time_zone' => 
    array (
      'old' => 
      array (
        0 => 'DateTimeZone',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'DateTimeZone',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intltz_use_daylight_time' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeZone' => 'IntlTimeZone',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'IntlTimeZone',
      ),
    ),
    'intval' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'var' => 'mixed',
        'base=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'value' => 'mixed',
        'base=' => 'int',
      ),
    ),
    'ip2long' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'ip_address' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ip' => 'string',
      ),
    ),
    'iptcembed' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'iptcdata' => 'string',
        'jpeg_file_name' => 'string',
        'spool=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'iptc_data' => 'string',
        'filename' => 'string',
        'spool=' => 'int',
      ),
    ),
    'iptcparse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iptcdata' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iptc_block' => 'string',
      ),
    ),
    'is_a' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'mixed',
        'class_name' => 'string',
        'allow_string=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object_or_class' => 'mixed',
        'class' => 'string',
        'allow_string=' => 'bool',
      ),
    ),
    'is_array' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_bool' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_callable' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'callable|mixed',
        'syntax_only=' => 'bool',
        '&w_callable_name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'callable|mixed',
        'syntax_only=' => 'bool',
        '&w_callable_name=' => 'string',
      ),
    ),
    'is_countable' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_double' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_finite' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'val' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'float',
      ),
    ),
    'is_float' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_infinite' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'val' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'float',
      ),
    ),
    'is_int' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_integer' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_iterable' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_long' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_nan' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'val' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'num' => 'float',
      ),
    ),
    'is_null' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_object' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_resource' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_string' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value' => 'mixed',
      ),
    ),
    'is_subclass_of' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'object|string',
        'class_name' => 'class-string',
        'allow_string=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object_or_class' => 'object|string',
        'class' => 'class-string',
        'allow_string=' => 'bool',
      ),
    ),
    'is_uploaded_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
    ),
    'iterator_apply' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable',
        'function' => 'callable(mixed):bool',
        'args=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable',
        'callback' => 'callable(mixed):bool',
        'args=' => 'array<array-key, mixed>|null',
      ),
    ),
    'iterator_to_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'use_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'preserve_keys=' => 'bool',
      ),
    ),
    'jdtounix' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'julian_day' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'julian_day' => 'int',
      ),
    ),
    'join' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'glue' => 'string',
        'pieces' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'string',
        'separator' => 'string',
        'array=' => 'array<array-key, mixed>|null',
      ),
    ),
    'json_decode' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'assoc=' => 'bool|null',
        'depth=' => 'int',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'associative=' => 'bool|null',
        'depth=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'json_encode' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'value' => 'mixed',
        'options=' => 'int',
        'depth=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'value' => 'mixed',
        'flags=' => 'int',
        'depth=' => 'int',
      ),
    ),
    'key' => 
    array (
      'old' => 
      array (
        0 => 'int|null|string',
        'arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'int|null|string',
        'array' => 'array<array-key, mixed>|object',
      ),
    ),
    'key_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'search' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'krsort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'ksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'lcfirst' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'ldap_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_add_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_bind_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_compare' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_delete_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_exop_passwd' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_list' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_add_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_del' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_del_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_replace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_replace_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_modify' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_modify_batch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_read' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_rename_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_sasl_bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn=' => 'string',
        'password=' => 'string',
        'mech=' => 'string',
        'realm=' => 'string',
        'authc_id=' => 'string',
        'authz_id=' => 'string',
        'props=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'mech=' => 'null|string',
        'realm=' => 'null|string',
        'authc_id=' => 'null|string',
        'authz_id=' => 'null|string',
        'props=' => 'null|string',
      ),
    ),
    'ldap_search' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, resource>|false|resource',
        'ldap' => 'array<array-key, resource>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, resource>|false|resource',
        'ldap' => 'array<array-key, resource>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_set_rebind_proc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'callback' => 'callable|null',
      ),
    ),
    'levenshtein' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
        'cost_ins=' => 'mixed',
        'cost_rep=' => 'mixed',
        'cost_del=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'insertion_cost=' => 'int',
        'replacement_cost=' => 'int',
        'deletion_cost=' => 'int',
      ),
    ),
    'libxml_use_internal_errors' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'use_errors=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'use_errors=' => 'bool|null',
      ),
    ),
    'limititerator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'offset=' => 'int',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'offset=' => 'int',
        'limit=' => 'int',
      ),
    ),
    'limititerator::seek' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'offset' => 'int',
      ),
    ),
    'linkinfo' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'path' => 'string',
      ),
    ),
    'locale::filtermatches' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'langtag' => 'string',
        'locale' => 'string',
        'canonicalize=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'languageTag' => 'string',
        'locale' => 'string',
        'canonicalize=' => 'bool',
      ),
    ),
    'locale::getdisplaylanguage' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale::getdisplayname' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale::getdisplayregion' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale::getdisplayscript' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale::getdisplayvariant' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale::lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'default=' => 'null|string',
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
    'locale_accept_from_http' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'header' => 'string',
      ),
    ),
    'locale_canonicalize' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale_compose' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'arg1' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'subtags' => 'array<array-key, mixed>',
      ),
    ),
    'locale_filter_matches' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'langtag' => 'string',
        'locale' => 'string',
        'canonicalize=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'languageTag' => 'string',
        'locale' => 'string',
        'canonicalize=' => 'bool',
      ),
    ),
    'locale_get_all_variants' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'locale_get_display_language' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale_get_display_name' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale_get_display_region' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale_get_display_script' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale_get_display_variant' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'in_locale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'locale_get_keywords' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'locale' => 'string',
      ),
    ),
    'locale_get_primary_language' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale_get_region' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale_get_script' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale_lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'def=' => 'null|string',
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
    'locale_parse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'locale_set_default' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arg1' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'locale' => 'string',
      ),
    ),
    'localtime' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'timestamp=' => 'int',
        'associative_array=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'timestamp=' => 'int|null',
        'associative=' => 'bool',
      ),
    ),
    'log' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
        'base=' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
        'base=' => 'float',
      ),
    ),
    'log10' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'log1p' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'long2ip' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'proper_address' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'ip' => 'int',
      ),
    ),
    'ltrim' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'character_mask=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'characters=' => 'string',
      ),
    ),
    'mail' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'array<array-key, mixed>|string',
        'additional_parameters=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'array<array-key, mixed>|string',
        'additional_params=' => 'string',
      ),
    ),
    'mb_check_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_chr' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'cp' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'codepoint' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_convert_case' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'sourcestring' => 'string',
        'mode' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'mode' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_convert_encoding' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'str' => 'string',
        'to' => 'string',
        'from=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'to_encoding' => 'string',
        'from_encoding=' => 'array<array-key, mixed>|null|string',
      ),
    ),
    'mb_convert_encoding\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'string' => 'array<array-key, mixed>',
        'to_encoding' => 'string',
        'from_encoding=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'string' => 'array<array-key, mixed>',
        'to_encoding' => 'string',
        'from_encoding=' => 'array<array-key, mixed>|null|string',
      ),
    ),
    'mb_convert_kana' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'option=' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'mode=' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_decode_numericentity' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'convmap' => 'array<array-key, mixed>',
        'encoding=' => 'string',
        'is_hex=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'map' => 'array<array-key, mixed>',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_detect_encoding' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'str' => 'string',
        'encoding_list=' => 'mixed',
        'strict=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'encodings=' => 'array<array-key, mixed>|null|string',
        'strict=' => 'bool',
      ),
    ),
    'mb_detect_order' => 
    array (
      'old' => 
      array (
        0 => 'bool|list<string>',
        'encoding=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool|list<string>',
        'encoding=' => 'array<array-key, mixed>|null|string',
      ),
    ),
    'mb_encode_mimeheader' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'charset=' => 'string',
        'transfer=' => 'string',
        'linefeed=' => 'string',
        'indent=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'charset=' => 'null|string',
        'transfer_encoding=' => 'null|string',
        'newline=' => 'string',
        'indent=' => 'int',
      ),
    ),
    'mb_encode_numericentity' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'convmap' => 'array<array-key, mixed>',
        'encoding=' => 'string',
        'is_hex=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'map' => 'array<array-key, mixed>',
        'encoding=' => 'null|string',
        'hex=' => 'bool',
      ),
    ),
    'mb_encoding_aliases' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'encoding' => 'string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'encoding' => 'string',
      ),
    ),
    'mb_ereg' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'pattern' => 'string',
        'string' => 'string',
        '&w_registers=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        '&w_matches=' => 'array<array-key, mixed>|null',
      ),
    ),
    'mb_ereg_match' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_replace' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'pattern' => 'string',
        'replacement' => 'string',
        'string' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'pattern' => 'string',
        'replacement' => 'string',
        'string' => 'string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_replace_callback' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'pattern' => 'string',
        'callback' => 'callable',
        'string' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'pattern' => 'string',
        'callback' => 'callable',
        'string' => 'string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_search' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pattern=' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern=' => 'null|string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_search_init' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'pattern=' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'pattern=' => 'null|string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_search_pos' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, int>|false',
        'pattern=' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, int>|false',
        'pattern=' => 'null|string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_search_regs' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|false',
        'pattern=' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|false',
        'pattern=' => 'null|string',
        'options=' => 'null|string',
      ),
    ),
    'mb_ereg_search_setpos' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'offset' => 'int',
      ),
    ),
    'mb_eregi' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'pattern' => 'string',
        'string' => 'string',
        '&w_registers=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        '&w_matches=' => 'array<array-key, mixed>|null',
      ),
    ),
    'mb_eregi_replace' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'pattern' => 'string',
        'replacement' => 'string',
        'string' => 'string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'pattern' => 'string',
        'replacement' => 'string',
        'string' => 'string',
        'options=' => 'null|string',
      ),
    ),
    'mb_http_input' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'type=' => 'null|string',
      ),
    ),
    'mb_http_output' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_internal_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_language' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'language=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'language=' => 'null|string',
      ),
    ),
    'mb_ord' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'str' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_output_handler' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'contents' => 'string',
        'status' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'status' => 'int',
      ),
    ),
    'mb_parse_str' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'encoded_string' => 'string',
        '&w_result=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        '&w_result' => 'array<array-key, mixed>',
      ),
    ),
    'mb_regex_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_regex_set_options' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'options=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'options=' => 'null|string',
      ),
    ),
    'mb_scrub' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_send_mail' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'array<array-key, mixed>|string',
        'additional_parameters=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'to' => 'string',
        'subject' => 'string',
        'message' => 'string',
        'additional_headers=' => 'array<array-key, mixed>|string',
        'additional_params=' => 'null|string',
      ),
    ),
    'mb_str_split' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'str' => 'string',
        'split_length=' => 'int<1, max>',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strcut' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'start' => 'int',
        'length=' => 'int|null',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'start' => 'int',
        'length=' => 'int|null',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strimwidth' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'start' => 'int',
        'width' => 'int',
        'trimmarker=' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'start' => 'int',
        'width' => 'int',
        'trim_marker=' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_stripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_stristr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'part=' => 'bool',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strlen' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'str' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strrchr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'part=' => 'bool',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strrichr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'part=' => 'bool',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strrpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strstr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'part=' => 'bool',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strtolower' => 
    array (
      'old' => 
      array (
        0 => 'lowercase-string',
        'sourcestring' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'lowercase-string',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strtoupper' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'sourcestring' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strwidth' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_substitute_character' => 
    array (
      'old' => 
      array (
        0 => 'bool|int|string',
        'substchar=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool|int|string',
        'substitute_character=' => 'int|null|string',
      ),
    ),
    'mb_substr' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'start' => 'int',
        'length=' => 'int|null',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'start' => 'int',
        'length=' => 'int|null',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_substr_count' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'haystack' => 'string',
        'needle' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'haystack' => 'string',
        'needle' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'md5' => 
    array (
      'old' => 
      array (
        0 => 'non-falsy-string',
        'str' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-falsy-string',
        'string' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'md5_file' => 
    array (
      'old' => 
      array (
        0 => 'false|non-falsy-string',
        'filename' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|non-falsy-string',
        'filename' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'messageformatter::format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'args' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'messageformatter::formatmessage' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'locale' => 'string',
        'pattern' => 'string',
        'args' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'locale' => 'string',
        'pattern' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'messageformatter::parse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'source' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'string' => 'string',
      ),
    ),
    'messageformatter::parsemessage' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
        'pattern' => 'string',
        'args' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
        'pattern' => 'string',
        'message' => 'string',
      ),
    ),
    'metaphone' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'text' => 'string',
        'phones=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'max_phonemes=' => 'int',
      ),
    ),
    'method_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object' => 'class-string|object',
        'method' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object_or_class' => 'class-string|object',
        'method' => 'string',
      ),
    ),
    'mhash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'hash' => 'int',
        'data' => 'string',
        'key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'algo' => 'int',
        'data' => 'string',
        'key=' => 'null|string',
      ),
    ),
    'mhash_get_block_size' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'hash' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'algo' => 'int',
      ),
    ),
    'mhash_get_hash_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'hash' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'algo' => 'int',
      ),
    ),
    'mhash_keygen_s2k' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'hash' => 'int',
        'input_password' => 'string',
        'salt' => 'string',
        'bytes' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'algo' => 'int',
        'password' => 'string',
        'salt' => 'string',
        'length' => 'int',
      ),
    ),
    'microtime' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'get_as_float=' => 'false',
      ),
      'new' => 
      array (
        0 => 'string',
        'as_float=' => 'false',
      ),
    ),
    'mime_content_type' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'filename' => 'resource|string',
      ),
    ),
    'mkdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathname' => 'string',
        'mode=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'permissions=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'mktime' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'hour=' => 'int',
        'min=' => 'int',
        'sec=' => 'int',
        'mon=' => 'int',
        'day=' => 'int',
        'year=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'hour' => 'int',
        'minute=' => 'int|null',
        'second=' => 'int|null',
        'month=' => 'int|null',
        'day=' => 'int|null',
        'year=' => 'int|null',
      ),
    ),
    'mongodb\\bson\\binary::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\dbpointer::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\decimal128::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\document::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\int64::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\javascript::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\maxkey::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\minkey::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\objectid::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\packedarray::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\regex::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\symbol::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\timestamp::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\undefined::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\bson\\utcdatetime::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\driver\\cursorid::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\driver\\readconcern::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\driver\\readpreference::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\driver\\serverapi::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'mongodb\\driver\\writeconcern::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'move_uploaded_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'path' => 'string',
        'new_path' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'msg_get_queue' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'int',
        'permissions=' => 'int',
      ),
      'new' => 
      array (
        0 => 'SysvMessageQueue|false',
        'key' => 'int',
        'permissions=' => 'int',
      ),
    ),
    'msg_receive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue' => 'resource',
        'desired_message_type' => 'int',
        '&w_received_message_type' => 'int',
        'max_message_size' => 'int',
        '&w_message' => 'mixed',
        'unserialize=' => 'bool',
        'flags=' => 'int',
        '&w_error_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'queue' => 'SysvMessageQueue',
        'desired_message_type' => 'int',
        '&w_received_message_type' => 'int',
        'max_message_size' => 'int',
        '&w_message' => 'mixed',
        'unserialize=' => 'bool',
        'flags=' => 'int',
        '&w_error_code=' => 'int',
      ),
    ),
    'msg_remove_queue' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'queue' => 'SysvMessageQueue',
      ),
    ),
    'msg_send' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue' => 'resource',
        'message_type' => 'int',
        'message' => 'mixed',
        'serialize=' => 'bool',
        'blocking=' => 'bool',
        '&w_error_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'queue' => 'SysvMessageQueue',
        'message_type' => 'int',
        'message' => 'mixed',
        'serialize=' => 'bool',
        'blocking=' => 'bool',
        '&w_error_code=' => 'int',
      ),
    ),
    'msg_set_queue' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue' => 'resource',
        'data' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'queue' => 'SysvMessageQueue',
        'data' => 'array<array-key, mixed>',
      ),
    ),
    'msg_stat_queue' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'queue' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'queue' => 'SysvMessageQueue',
      ),
    ),
    'msgfmt_format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'MessageFormatter',
        'args' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'MessageFormatter',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'msgfmt_format_message' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'locale' => 'string',
        'pattern' => 'string',
        'args' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'locale' => 'string',
        'pattern' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'msgfmt_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'nf' => 'MessageFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'MessageFormatter',
      ),
    ),
    'msgfmt_get_error_message' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'coll' => 'MessageFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'MessageFormatter',
      ),
    ),
    'msgfmt_get_locale' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'mf' => 'MessageFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'MessageFormatter',
      ),
    ),
    'msgfmt_get_pattern' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'mf' => 'MessageFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'MessageFormatter',
      ),
    ),
    'msgfmt_parse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'nf' => 'MessageFormatter',
        'source' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'formatter' => 'MessageFormatter',
        'string' => 'string',
      ),
    ),
    'msgfmt_parse_message' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
        'pattern' => 'string',
        'source' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
        'pattern' => 'string',
        'message' => 'string',
      ),
    ),
    'msgfmt_set_pattern' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mf' => 'MessageFormatter',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'MessageFormatter',
        'pattern' => 'string',
      ),
    ),
    'multipleiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags=' => 'int',
      ),
    ),
    'multipleiterator::attachiterator' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'infos=' => 'int|null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'info=' => 'int|null|string',
      ),
    ),
    'mysqli::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'hostname=' => 'string',
        'username=' => 'string',
        'password=' => 'string',
        'database=' => 'string',
        'port=' => 'int',
        'socket=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'hostname=' => 'null|string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'database=' => 'null|string',
        'port=' => 'int|null',
        'socket=' => 'null|string',
      ),
    ),
    'mysqli::begin_transaction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli::commit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli::connect' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'hostname=' => 'string',
        'username=' => 'string',
        'password=' => 'string',
        'database=' => 'string',
        'port=' => 'int',
        'socket=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'hostname=' => 'null|string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'database=' => 'null|string',
        'port=' => 'int|null',
        'socket=' => 'null|string',
      ),
    ),
    'mysqli::rollback' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli_begin_transaction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli_commit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|mysqli',
        'hostname=' => 'string',
        'username=' => 'string',
        'password=' => 'string',
        'database=' => 'string',
        'port=' => 'int',
        'socket=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|mysqli',
        'hostname=' => 'null|string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'database=' => 'null|string',
        'port=' => 'int|null',
        'socket=' => 'null|string',
      ),
    ),
    'mysqli_field_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'result' => 'mysqli_result',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'result' => 'mysqli_result',
        'index' => 'int',
      ),
    ),
    'mysqli_result::field_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'index' => 'int',
      ),
    ),
    'mysqli_rollback' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mysql' => 'mysqli',
        'flags=' => 'int',
        'name=' => 'null|string',
      ),
    ),
    'mysqli_stmt::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'mysql' => 'mysqli',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'mysql' => 'mysqli',
        'query=' => 'null|string',
      ),
    ),
    'natcasesort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'natsort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'next' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        '&r_arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
    ),
    'nl2br' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'is_xhtml=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'use_xhtml=' => 'bool',
      ),
    ),
    'normalizer::getrawdecomposition' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'input' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'normalizer::isnormalized' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'input' => 'string',
        'form=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'normalizer::normalize' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'input' => 'string',
        'form=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'normalizer_get_raw_decomposition' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'input' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'normalizer_is_normalized' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'input' => 'string',
        'form=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'normalizer_normalize' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'input' => 'string',
        'form=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'form=' => 'int',
      ),
    ),
    'number_format' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'number' => 'float',
        'num_decimal_places=' => 'int',
        'dec_separator=' => 'mixed',
        'thousands_separator=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'float',
        'decimals=' => 'int',
        'decimal_separator=' => 'null|string',
        'thousands_separator=' => 'null|string',
      ),
    ),
    'numberformatter::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'null|string',
      ),
    ),
    'numberformatter::create' => 
    array (
      'old' => 
      array (
        0 => 'NumberFormatter|null',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'NumberFormatter|null',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'null|string',
      ),
    ),
    'numberformatter::formatcurrency' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'num' => 'float',
        'currency' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'amount' => 'float',
        'currency' => 'string',
      ),
    ),
    'numberformatter::getattribute' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'attribute' => 'int',
      ),
    ),
    'numberformatter::getsymbol' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'symbol' => 'int',
      ),
    ),
    'numberformatter::gettextattribute' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'attribute' => 'int',
      ),
    ),
    'numberformatter::parse' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int',
        'string' => 'string',
        'type=' => 'int',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'string' => 'string',
        'type=' => 'int',
        '&offset=' => 'int',
      ),
    ),
    'numberformatter::parsecurrency' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
        'string' => 'string',
        '&w_currency' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float',
        'string' => 'string',
        '&w_currency' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'numberformatter::setattribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'attr' => 'int',
        'value' => 'float|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'attribute' => 'int',
        'value' => 'float|int',
      ),
    ),
    'numberformatter::setsymbol' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'attr' => 'int',
        'symbol' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'symbol' => 'int',
        'value' => 'string',
      ),
    ),
    'numberformatter::settextattribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'attr' => 'int',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'attribute' => 'int',
        'value' => 'string',
      ),
    ),
    'numfmt_create' => 
    array (
      'old' => 
      array (
        0 => 'NumberFormatter|null',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'NumberFormatter|null',
        'locale' => 'string',
        'style' => 'int',
        'pattern=' => 'null|string',
      ),
    ),
    'numfmt_format' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'NumberFormatter',
        'num' => 'float|int',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'NumberFormatter',
        'num' => 'float|int',
        'type=' => 'int',
      ),
    ),
    'numfmt_format_currency' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'NumberFormatter',
        'num' => 'float',
        'currency' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'NumberFormatter',
        'amount' => 'float',
        'currency' => 'string',
      ),
    ),
    'numfmt_get_attribute' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'formatter' => 'NumberFormatter',
        'attribute' => 'int',
      ),
    ),
    'numfmt_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'nf' => 'NumberFormatter',
      ),
      'new' => 
      array (
        0 => 'int',
        'formatter' => 'NumberFormatter',
      ),
    ),
    'numfmt_get_error_message' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'nf' => 'NumberFormatter',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'NumberFormatter',
      ),
    ),
    'numfmt_get_locale' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'nf' => 'NumberFormatter',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'formatter' => 'NumberFormatter',
        'type=' => 'int',
      ),
    ),
    'numfmt_get_pattern' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'NumberFormatter',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'NumberFormatter',
      ),
    ),
    'numfmt_get_symbol' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'NumberFormatter',
        'symbol' => 'int',
      ),
    ),
    'numfmt_get_text_attribute' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'formatter' => 'NumberFormatter',
        'attribute' => 'int',
      ),
    ),
    'numfmt_parse' => 
    array (
      'old' => 
      array (
        0 => 'false|float|int',
        'formatter' => 'NumberFormatter',
        'string' => 'string',
        'type=' => 'int',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'formatter' => 'NumberFormatter',
        'string' => 'string',
        'type=' => 'int',
        '&offset=' => 'int',
      ),
    ),
    'numfmt_parse_currency' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
        'formatter' => 'NumberFormatter',
        'string' => 'string',
        '&w_currency' => 'string',
        '&position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float',
        'formatter' => 'NumberFormatter',
        'string' => 'string',
        '&w_currency' => 'string',
        '&offset=' => 'int',
      ),
    ),
    'numfmt_set_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
        'value' => 'float|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'NumberFormatter',
        'attribute' => 'int',
        'value' => 'float|int',
      ),
    ),
    'numfmt_set_pattern' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'nf' => 'NumberFormatter',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'NumberFormatter',
        'pattern' => 'string',
      ),
    ),
    'numfmt_set_symbol' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
        'symbol' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'NumberFormatter',
        'symbol' => 'int',
        'value' => 'string',
      ),
    ),
    'numfmt_set_text_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'nf' => 'NumberFormatter',
        'attr' => 'int',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'NumberFormatter',
        'attribute' => 'int',
        'value' => 'string',
      ),
    ),
    'ob_implicit_flush' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flag=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'enable=' => 'bool',
      ),
    ),
    'ob_start' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'user_function=' => 'array<array-key, mixed>|callable|null|string',
        'chunk_size=' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback=' => 'array<array-key, mixed>|callable|null|string',
        'chunk_size=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'octdec' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'octal_number' => 'string',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'octal_string' => 'string',
      ),
    ),
    'odbc_do' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
      ),
    ),
    'odbc_exec' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'resource',
        'odbc' => 'resource',
        'query' => 'string',
      ),
    ),
    'odbc_fetch_row' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'row=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'resource',
        'row=' => 'int|null',
      ),
    ),
    'odbc_tables' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'string',
        'table=' => 'string',
        'types=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'types=' => 'null|string',
      ),
    ),
    'opcache_compile_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'file' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
    ),
    'opcache_get_status' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'fetch_scripts=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'include_scripts=' => 'bool',
      ),
    ),
    'opcache_invalidate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'script' => 'string',
        'force=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'force=' => 'bool',
      ),
    ),
    'opcache_is_script_cached' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'script' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
    ),
    'opendir' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'path' => 'string',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'directory' => 'string',
        'context=' => 'resource',
      ),
    ),
    'openlog' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'ident' => 'string',
        'option' => 'int',
        'facility' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'prefix' => 'string',
        'flags' => 'int',
        'facility' => 'int',
      ),
    ),
    'openssl_cipher_iv_length' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'method' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'cipher_algo' => 'string',
      ),
    ),
    'openssl_csr_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'csr' => 'resource|string',
        '&w_out' => 'string',
        'notext=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_csr_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'csr' => 'resource|string',
        'outfilename' => 'string',
        'notext=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'output_filename' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_csr_get_public_key' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'csr' => 'resource|string',
        'use_shortnames=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'short_names=' => 'bool',
      ),
    ),
    'openssl_csr_get_subject' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'csr' => 'resource|string',
        'use_shortnames=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'short_names=' => 'bool',
      ),
    ),
    'openssl_csr_new' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'dn' => 'array<array-key, mixed>',
        '&w_privkey' => 'resource',
        'configargs=' => 'array<array-key, mixed>',
        'extraattribs=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'OpenSSLCertificateSigningRequest|false',
        'distinguished_names' => 'array<array-key, mixed>',
        '&w_private_key' => 'OpenSSLAsymmetricKey',
        'options=' => 'array<array-key, mixed>|null',
        'extra_attributes=' => 'array<array-key, mixed>|null',
      ),
    ),
    'openssl_csr_sign' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'csr' => 'resource|string',
        'x509' => 'null|resource|string',
        'priv_key' => 'array<array-key, mixed>|resource|string',
        'days' => 'int',
        'config_args=' => 'array<array-key, mixed>',
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
      ),
    ),
    'openssl_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'method' => 'string',
        'password' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        'tag=' => 'string',
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
        'tag=' => 'string',
        'aad=' => 'string',
      ),
    ),
    'openssl_dh_compute_key' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'pub_key' => 'string',
        'dh_key' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'public_key' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey',
      ),
    ),
    'openssl_digest' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'method' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'digest_algo' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'openssl_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'method' => 'string',
        'password' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        '&w_tag=' => 'string',
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
        '&w_tag=' => 'string',
        'aad=' => 'string',
        'tag_length=' => 'int',
      ),
    ),
    'openssl_free_key' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'OpenSSLAsymmetricKey',
      ),
    ),
    'openssl_get_privatekey' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'string',
        'passphrase=' => 'string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'passphrase=' => 'null|string',
      ),
    ),
    'openssl_get_publickey' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'cert' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
      ),
    ),
    'openssl_open' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_opendata' => 'string',
        'ekey' => 'string',
        'privkey' => 'array<array-key, mixed>|resource|string',
        'method=' => 'string',
        'iv=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_output' => 'string',
        'encrypted_key' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'cipher_algo' => 'string',
        'iv=' => 'null|string',
      ),
    ),
    'openssl_pbkdf2' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'salt' => 'string',
        'key_length' => 'int',
        'iterations' => 'int',
        'digest_algorithm=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'salt' => 'string',
        'key_length' => 'int',
        'iterations' => 'int',
        'digest_algo=' => 'string',
      ),
    ),
    'openssl_pkcs12_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'x509' => 'resource|string',
        '&w_out' => 'string',
        'priv_key' => 'array<array-key, mixed>|resource|string',
        'pass' => 'string',
        'args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        '&w_output' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'passphrase' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'openssl_pkcs12_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'x509' => 'resource|string',
        'filename' => 'string',
        'priv_key' => 'array<array-key, mixed>|resource|string',
        'pass' => 'string',
        'args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        'output_filename' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'passphrase' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'openssl_pkcs12_read' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'PKCS12' => 'string',
        '&w_certs' => 'array<array-key, mixed>',
        'pass' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pkcs12' => 'string',
        '&w_certificates' => 'array<array-key, mixed>',
        'passphrase' => 'string',
      ),
    ),
    'openssl_pkcs7_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'infilename' => 'string',
        'outfilename' => 'string',
        'recipcert' => 'resource|string',
        'recipkey=' => 'array<array-key, mixed>|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'OpenSSLCertificate|string',
        'private_key=' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|null|string',
      ),
    ),
    'openssl_pkcs7_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'infile' => 'string',
        'outfile' => 'string',
        'recipcerts' => 'array<array-key, mixed>|resource|string',
        'headers' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'cipher=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'OpenSSLCertificate|list<OpenSSLCertificate|string>|string',
        'headers' => 'array<array-key, mixed>|null',
        'flags=' => 'int',
        'cipher_algo=' => 'int',
      ),
    ),
    'openssl_pkcs7_read' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'infilename' => 'string',
        '&w_certs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_certificates' => 'array<array-key, mixed>',
      ),
    ),
    'openssl_pkcs7_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'infile' => 'string',
        'outfile' => 'string',
        'signcert' => 'resource|string',
        'signkey' => 'array<array-key, mixed>|resource|string',
        'headers' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'extracertsfilename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'OpenSSLCertificate|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'headers' => 'array<array-key, mixed>|null',
        'flags=' => 'int',
        'untrusted_certificates_filename=' => 'null|string',
      ),
    ),
    'openssl_pkcs7_verify' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'filename' => 'string',
        'flags' => 'int',
        'signerscerts=' => 'string',
        'cainfo=' => 'array<array-key, mixed>',
        'extracerts=' => 'string',
        'content=' => 'string',
        'pk7=' => 'string',
      ),
      'new' => 
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
    ),
    'openssl_pkey_derive' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'peer_pub_key' => 'mixed',
        'priv_key' => 'mixed',
        'keylen=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'key_length=' => 'int',
      ),
    ),
    'openssl_pkey_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'resource',
        '&w_out' => 'string',
        'passphrase=' => 'null|string',
        'config_args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        '&w_output' => 'string',
        'passphrase=' => 'null|string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'openssl_pkey_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'array<array-key, mixed>|resource|string',
        'outfilename' => 'string',
        'passphrase=' => 'null|string',
        'config_args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'output_filename' => 'string',
        'passphrase=' => 'null|string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'openssl_pkey_free' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'OpenSSLAsymmetricKey',
      ),
    ),
    'openssl_pkey_get_details' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'OpenSSLAsymmetricKey',
      ),
    ),
    'openssl_pkey_get_private' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'string',
        'passphrase=' => 'string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|array<array-key, mixed>|string',
        'passphrase=' => 'null|string',
      ),
    ),
    'openssl_pkey_get_public' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'cert' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
      ),
    ),
    'openssl_pkey_new' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'configargs=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'openssl_private_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_crypted' => 'string',
        'key' => 'array<array-key, mixed>|resource|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_decrypted_data' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
    ),
    'openssl_private_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_crypted' => 'string',
        'key' => 'array<array-key, mixed>|resource|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_encrypted_data' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
    ),
    'openssl_public_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_crypted' => 'string',
        'key' => 'resource|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_decrypted_data' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
    ),
    'openssl_public_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_crypted' => 'string',
        'key' => 'resource|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_encrypted_data' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
    ),
    'openssl_random_pseudo_bytes' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w_result_is_strong=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w_strong_result=' => 'bool',
      ),
    ),
    'openssl_seal' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        '&w_sealdata' => 'string',
        '&w_ekeys' => 'array<array-key, mixed>',
        'pubkeys' => 'array<array-key, mixed>',
        'method=' => 'string',
        '&iv=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        '&w_sealed_data' => 'string',
        '&w_encrypted_keys' => 'array<array-key, mixed>',
        'public_key' => 'list<OpenSSLAsymmetricKey>',
        'cipher_algo' => 'string',
        '&iv=' => 'string',
      ),
    ),
    'openssl_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_signature' => 'string',
        'key' => 'resource|string',
        'method=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_signature' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
      ),
    ),
    'openssl_spki_new' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'privkey' => 'resource',
        'challenge' => 'string',
        'algo=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'private_key' => 'OpenSSLAsymmetricKey',
        'challenge' => 'string',
        'digest_algo=' => 'int',
      ),
    ),
    'openssl_verify' => 
    array (
      'old' => 
      array (
        0 => '-1|0|1',
        'data' => 'string',
        'signature' => 'string',
        'key' => 'resource|string',
        'method=' => 'int|string',
      ),
      'new' => 
      array (
        0 => '-1|0|1|false',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
      ),
    ),
    'openssl_x509_check_private_key' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'cert' => 'resource|string',
        'key' => 'array<array-key, mixed>|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
      ),
    ),
    'openssl_x509_checkpurpose' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'x509cert' => 'resource|string',
        'purpose' => 'int',
        'cainfo=' => 'array<array-key, mixed>',
        'untrustedfile=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'certificate' => 'OpenSSLCertificate|string',
        'purpose' => 'int',
        'ca_info=' => 'array<array-key, mixed>',
        'untrusted_certificates_file=' => 'null|string',
      ),
    ),
    'openssl_x509_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'x509' => 'resource|string',
        '&w_out' => 'string',
        'notext=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_x509_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'x509' => 'resource|string',
        'outfilename' => 'string',
        'notext=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        'output_filename' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_x509_fingerprint' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'x509' => 'resource|string',
        'method=' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'certificate' => 'OpenSSLCertificate|string',
        'digest_algo=' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'openssl_x509_free' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'x509' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'certificate' => 'OpenSSLCertificate',
      ),
    ),
    'openssl_x509_parse' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'x509' => 'resource|string',
        'shortname=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'certificate' => 'OpenSSLCertificate|string',
        'short_names=' => 'bool',
      ),
    ),
    'openssl_x509_read' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'cert' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLCertificate|false',
        'certificate' => 'OpenSSLCertificate|string',
      ),
    ),
    'openssl_x509_verify' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'cert' => 'resource|string',
        'key' => 'array<array-key, mixed>|resource|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'certificate' => 'OpenSSLCertificate|string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|array<array-key, mixed>|string',
      ),
    ),
    'pack' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
        '...values=' => 'mixed',
      ),
    ),
    'parse_str' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'encoded_string' => 'string',
        '&w_result=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'string' => 'string',
        '&w_result' => 'array<array-key, mixed>',
      ),
    ),
    'passthru' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'command' => 'string',
        '&w_return_value=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
    ),
    'password_hash' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'string',
        'password' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'pathinfo' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|string',
        'path' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|string',
        'path' => 'string',
        'flags=' => 'int',
      ),
    ),
    'pclose' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'handle' => 'resource',
      ),
    ),
    'pcntl_async_signals' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'on' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'enable=' => 'bool|null',
      ),
    ),
    'pcntl_exec' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'path' => 'string',
        'args=' => 'array<array-key, mixed>',
        'envs=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false',
        'path' => 'string',
        'args=' => 'array<array-key, mixed>',
        'env_vars=' => 'array<array-key, mixed>',
      ),
    ),
    'pcntl_getpriority' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pid=' => 'int',
        'process_identifier=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'process_id=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pcntl_setpriority' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'priority' => 'int',
        'pid=' => 'int',
        'process_identifier=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'priority' => 'int',
        'process_id=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pcntl_signal' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'signo' => 'int',
        'handler' => 'callable():void|callable(int):void|callable(int, array<array-key, mixed>):void|int',
        'restart_syscalls=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'signal' => 'int',
        'handler' => 'callable():void|callable(int):void|callable(int, array<array-key, mixed>):void|int',
        'restart_syscalls=' => 'bool',
      ),
    ),
    'pcntl_signal_get_handler' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'signo' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'signal' => 'int',
      ),
    ),
    'pcntl_sigprocmask' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'how' => 'int',
        'set' => 'array<array-key, mixed>',
        '&w_oldset=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mode' => 'int',
        'signals' => 'array<array-key, mixed>',
        '&w_old_signals=' => 'array<array-key, mixed>',
      ),
    ),
    'pcntl_sigtimedwait' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'set' => 'array<array-key, mixed>',
        '&w_info=' => 'array<array-key, mixed>',
        'seconds=' => 'int',
        'nanoseconds=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'signals' => 'array<array-key, mixed>',
        '&w_info=' => 'array<array-key, mixed>',
        'seconds=' => 'int',
        'nanoseconds=' => 'int',
      ),
    ),
    'pcntl_sigwaitinfo' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'set' => 'array<array-key, mixed>',
        '&w_info=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'signals' => 'array<array-key, mixed>',
        '&w_info=' => 'array<array-key, mixed>',
      ),
    ),
    'pcntl_strerror' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'errno' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'error_code' => 'int',
      ),
    ),
    'pcntl_wait' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&w_status' => 'int',
        'options=' => 'int',
        '&w_rusage=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        '&w_status' => 'int',
        'flags=' => 'int',
        '&w_resource_usage=' => 'array<array-key, mixed>',
      ),
    ),
    'pcntl_waitpid' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pid' => 'int',
        '&w_status' => 'int',
        'options=' => 'int',
        '&w_rusage=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'process_id' => 'int',
        '&w_status' => 'int',
        'flags=' => 'int',
        '&w_resource_usage=' => 'array<array-key, mixed>',
      ),
    ),
    'pdo::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dsn' => 'string',
        'username=' => 'null|string',
        'passwd=' => 'null|string',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'dsn' => 'string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'pdo::exec' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'statement' => 'string',
      ),
    ),
    'pdo::lastinsertid' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'seqname=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'name=' => 'null|string',
      ),
    ),
    'pdo::prepare' => 
    array (
      'old' => 
      array (
        0 => 'PDOStatement|false',
        'statement' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'PDOStatement|false',
        'query' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'pdo::query' => 
    array (
      'old' => 
      array (
        0 => 'PDOStatement|false',
      ),
      'new' => 
      array (
        0 => 'PDOStatement|false',
        'query' => 'string',
        'fetchMode=' => 'int|null',
        '...fetchModeArgs=' => 'mixed',
      ),
    ),
    'pdo::quote' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'paramtype=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'type=' => 'int',
      ),
    ),
    'pdostatement::bindcolumn' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'column' => 'int|string',
        '&param' => 'mixed',
        'type=' => 'int',
        'maxlen=' => 'int',
        'driverdata=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'column' => 'int|string',
        '&var' => 'mixed',
        'type=' => 'int',
        'maxLength=' => 'int',
        'driverOptions=' => 'mixed',
      ),
    ),
    'pdostatement::bindparam' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'paramno' => 'int|string',
        '&param' => 'int|string',
        'type=' => 'int',
        'maxlen=' => 'int',
        'driverdata=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'param' => 'int|string',
        '&var' => 'mixed',
        'type=' => 'int',
        'maxLength=' => 'int',
        'driverOptions=' => 'mixed',
      ),
    ),
    'pdostatement::bindvalue' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'paramno' => 'int|string',
        'param' => 'int|string',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'param' => 'int|string',
        'value' => 'mixed',
        'type=' => 'int',
      ),
    ),
    'pdostatement::debugdumpparams' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool|null',
      ),
    ),
    'pdostatement::errorcode' => 
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
    'pdostatement::execute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'bound_input_params=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'params=' => 'array<array-key, mixed>|null',
      ),
    ),
    'pdostatement::fetch' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'how=' => 'int',
        'orientation=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'mode=' => 'int',
        'cursorOrientation=' => 'int',
        'cursorOffset=' => 'int',
      ),
    ),
    'pdostatement::fetchall' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'how=' => 'int',
        'class_name=' => 'callable|int|string',
        'ctor_args=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'mode=' => 'int',
        '...args=' => 'mixed',
      ),
    ),
    'pdostatement::fetchcolumn' => 
    array (
      'old' => 
      array (
        0 => 'null|scalar',
        'column_number=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'column=' => 'int',
      ),
    ),
    'pdostatement::fetchobject' => 
    array (
      'old' => 
      array (
        0 => 'false|object',
        'class_name=' => 'class-string|null',
        'ctor_args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|object',
        'class=' => 'class-string|null',
        'constructorArgs=' => 'array<array-key, mixed>',
      ),
    ),
    'pdostatement::getattribute' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'attribute' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'name' => 'int',
      ),
    ),
    'pdostatement::setfetchmode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mode' => 'int',
        'params=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mode' => 'int',
        '...args=' => 'mixed',
      ),
    ),
    'pfsockopen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        '&w_errno=' => 'int',
        '&w_errstr=' => 'string',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
        'timeout=' => 'float|null',
      ),
    ),
    'pg_client_encoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'connect_type=' => 'int',
        'host=' => 'mixed',
        'port=' => 'mixed',
        'options=' => 'mixed',
        'tty=' => 'mixed',
        'database=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
    ),
    'pg_connect_poll' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
    ),
    'pg_convert' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'db' => 'resource',
        'table' => 'string',
        'values' => 'array<array-key, mixed>',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_copy_from' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'table_name' => 'string',
        'rows' => 'array<array-key, mixed>',
        'delimiter=' => 'string',
        'null_as=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'table_name' => 'string',
        'rows' => 'array<array-key, mixed>',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
    ),
    'pg_copy_to' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'delimiter=' => 'string',
        'null_as=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
    ),
    'pg_dbname' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'db' => 'resource',
        'table' => 'string',
        'ids' => 'array<array-key, mixed>',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_end_copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_escape_bytea' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
        'data=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
    ),
    'pg_escape_identifier' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection=' => 'resource',
        'data=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
    ),
    'pg_escape_literal' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection=' => 'resource',
        'data=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
    ),
    'pg_escape_string' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
        'data=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
    ),
    'pg_exec' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query=' => 'string',
      ),
    ),
    'pg_execute' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'stmtname=' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'statement_name' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_fetch_all' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'resource',
        'result_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'resource',
        'mode=' => 'int',
      ),
    ),
    'pg_fetch_all_columns' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'result' => 'resource',
        'column_number=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'result' => 'resource',
        'field=' => 'int',
      ),
    ),
    'pg_fetch_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, null|string>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'result_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, null|string>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pg_fetch_object' => 
    array (
      'old' => 
      array (
        0 => 'false|object',
        'result' => 'resource',
        'row=' => 'int|null',
        'class_name=' => 'string',
        'l=' => 'array<array-key, mixed>',
        'ctor_params=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|object',
        'result' => 'resource',
        'row=' => 'int|null',
        'class=' => 'string',
        'constructor_args=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_fetch_result' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'row_number=' => 'int|string',
        'field_name=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_fetch_row' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'result_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pg_field_is_null' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row=' => 'int|string',
        'field_name_or_number=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_field_name' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field' => 'int',
      ),
    ),
    'pg_field_num' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field' => 'string',
      ),
    ),
    'pg_field_prtlen' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row=' => 'int|string',
        'field_name_or_number=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_field_size' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field' => 'int',
      ),
    ),
    'pg_field_table' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'result' => 'resource',
        'field_number' => 'int',
        'oid_only=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'result' => 'resource',
        'field' => 'int',
        'oid_only=' => 'bool',
      ),
    ),
    'pg_field_type' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field' => 'int',
      ),
    ),
    'pg_field_type_oid' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'field_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'field' => 'int',
      ),
    ),
    'pg_get_notify' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection=' => 'resource',
        'e=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'mode=' => 'int',
      ),
    ),
    'pg_get_pid' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
    ),
    'pg_insert' => 
    array (
      'old' => 
      array (
        0 => 'false|resource|string',
        'db' => 'resource',
        'table' => 'string',
        'values' => 'array<array-key, mixed>',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_last_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_last_notice' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'connection' => 'resource',
        'option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'connection' => 'resource',
        'mode=' => 'int',
      ),
    ),
    'pg_lo_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'large_object' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
      ),
    ),
    'pg_lo_create' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'connection=' => 'resource',
        'large_object_id=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'connection=' => 'resource',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
        'objoid=' => 'int|string',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'oid=' => 'int|string',
        'filename=' => 'string',
      ),
    ),
    'pg_lo_import' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'connection=' => 'resource',
        'filename=' => 'string',
        'large_object_oid=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'connection' => 'resource',
        'filename=' => 'string',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'large_object_oid=' => 'int|string',
        'mode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'oid=' => 'int|string',
        'mode=' => 'string',
      ),
    ),
    'pg_lo_read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'large_object' => 'resource',
        'len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'lob' => 'resource',
        'length=' => 'int',
      ),
    ),
    'pg_lo_read_all' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'large_object' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'lob' => 'resource',
      ),
    ),
    'pg_lo_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'large_object' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'pg_lo_tell' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'large_object' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'lob' => 'resource',
      ),
    ),
    'pg_lo_truncate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'large_object' => 'resource',
        'size=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
        'size' => 'int',
      ),
    ),
    'pg_lo_unlink' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
        'large_object_oid=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_write' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'large_object' => 'resource',
        'buf' => 'string',
        'len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'lob' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'pg_meta_data' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'db' => 'resource',
        'table' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'extended=' => 'bool',
      ),
    ),
    'pg_options' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_parameter_status' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'param_name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'name=' => 'string',
      ),
    ),
    'pg_pconnect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'host=' => 'int',
        'port=' => 'mixed',
        'options=' => 'mixed',
        'tty=' => 'mixed',
        'database=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
    ),
    'pg_ping' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_port' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_prepare' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'stmtname=' => 'string',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'statement_name' => 'string',
        'query=' => 'string',
      ),
    ),
    'pg_put_line' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'query=' => 'string',
      ),
    ),
    'pg_query' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query=' => 'string',
      ),
    ),
    'pg_query_params' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection=' => 'resource',
        'query=' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_result_error_field' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'fieldcode' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'field_code' => 'int',
      ),
    ),
    'pg_result_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'result' => 'resource',
        'offset' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'result' => 'resource',
        'row' => 'int',
      ),
    ),
    'pg_result_status' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'result_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'mode=' => 'int',
      ),
    ),
    'pg_select' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'db' => 'resource',
        'table' => 'string',
        'ids' => 'array<array-key, mixed>',
        'options=' => 'int',
        'result_type=' => 'int',
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
    'pg_send_execute' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'stmtname' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'statement_name' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
    ),
    'pg_send_prepare' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'stmtname' => 'string',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'statement_name' => 'string',
        'query' => 'string',
      ),
    ),
    'pg_set_client_encoding' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection=' => 'resource',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'resource',
        'encoding=' => 'string',
      ),
    ),
    'pg_set_error_verbosity' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'connection=' => 'resource',
        'verbosity=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'connection' => 'resource',
        'verbosity=' => 'int',
      ),
    ),
    'pg_trace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_tty' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_unescape_bytea' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'pg_untrace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
    ),
    'pg_update' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'db' => 'resource',
        'table' => 'string',
        'fields' => 'array<array-key, mixed>',
        'ids' => 'array<array-key, mixed>',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_version' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'connection=' => 'null|resource',
      ),
    ),
    'phar::addemptydir' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dirname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'directory' => 'string',
      ),
    ),
    'phar::addfile' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'null|string',
      ),
    ),
    'phar::addfromstring' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'localname' => 'string',
        'contents=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
        'contents' => 'string',
      ),
    ),
    'phar::buildfromdirectory' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'base_dir' => 'string',
        'regex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
    ),
    'phar::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'base_directory=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
    ),
    'phar::cancompress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'method=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'compression=' => 'int',
      ),
    ),
    'phar::compress' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'compression_type' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'compression' => 'int',
        'extension=' => 'null|string',
      ),
    ),
    'phar::compressfiles' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'compression_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'compression' => 'int',
      ),
    ),
    'phar::converttodata' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int',
        'compression_type=' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phar::converttoexecutable' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int',
        'compression_type=' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phar::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'newfile' => 'string',
        'oldfile' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'phar::count' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode=' => 'int',
      ),
    ),
    'phar::createdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'index=' => 'string',
        'webindex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
    ),
    'phar::decompress' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'extension=' => 'null|string',
      ),
    ),
    'phar::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
    ),
    'phar::extractto' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathto' => 'string',
        'files=' => 'array<array-key, mixed>|null|string',
        'overwrite=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'files=' => 'array<array-key, mixed>|null|string',
        'overwrite=' => 'bool',
      ),
    ),
    'phar::isfileformat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fileformat' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'format' => 'int',
      ),
    ),
    'phar::mount' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'inphar' => 'string',
        'externalfile' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'pharPath' => 'string',
        'externalPath' => 'string',
      ),
    ),
    'phar::mungserver' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'munglist' => 'list<string>',
      ),
      'new' => 
      array (
        0 => 'void',
        'variables' => 'list<string>',
      ),
    ),
    'phar::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
    ),
    'phar::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'PharFileInfo',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharFileInfo',
        'localName' => 'string',
      ),
    ),
    'phar::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'entry' => 'string',
        'value' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
        'value' => 'resource|string',
      ),
    ),
    'phar::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
      ),
    ),
    'phar::running' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'retphar=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'returnPhar=' => 'bool',
      ),
    ),
    'phar::setdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webindex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
    ),
    'phar::setsignaturealgorithm' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'algorithm' => 'int',
        'privatekey=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'algo' => 'int',
        'privateKey=' => 'null|string',
      ),
    ),
    'phar::setstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'newstub' => 'string',
        'maxlen=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'phar::unlinkarchive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'archive' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
    ),
    'phar::webphar' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'alias=' => 'null|string',
        'index=' => 'null|string',
        'f404=' => 'string',
        'mimetypes=' => 'array<array-key, mixed>',
        'rewrites=' => 'callable',
      ),
      'new' => 
      array (
        0 => 'void',
        'alias=' => 'null|string',
        'index=' => 'null|string',
        'fileNotFoundScript=' => 'null|string',
        'mimeTypes=' => 'array<array-key, mixed>',
        'rewrite=' => 'callable|null',
      ),
    ),
    'phardata::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'alias=' => 'null|string',
        'fileformat=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'alias=' => 'null|string',
        'format=' => 'int',
      ),
    ),
    'phardata::addemptydir' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'dirname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'directory' => 'string',
      ),
    ),
    'phardata::addfile' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'null|string',
      ),
    ),
    'phardata::addfromstring' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'localname' => 'string',
        'contents=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
        'contents' => 'string',
      ),
    ),
    'phardata::buildfromdirectory' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'base_dir' => 'string',
        'regex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
    ),
    'phardata::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'base_directory=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
    ),
    'phardata::compress' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'compression_type' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'compression' => 'int',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::compressfiles' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'compression_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'compression' => 'int',
      ),
    ),
    'phardata::converttodata' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int',
        'compression_type=' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::converttoexecutable' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int',
        'compression_type=' => 'int',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'newfile' => 'string',
        'oldfile' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'phardata::decompress' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'file_ext=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
    ),
    'phardata::extractto' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathto' => 'string',
        'files=' => 'array<array-key, mixed>|null|string',
        'overwrite=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'files=' => 'array<array-key, mixed>|null|string',
        'overwrite=' => 'bool',
      ),
    ),
    'phardata::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'localName' => 'string',
      ),
    ),
    'phardata::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'PharFileInfo',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharFileInfo',
        'localName' => 'string',
      ),
    ),
    'phardata::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'entry' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
        'value' => 'string',
      ),
    ),
    'phardata::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'entry' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'localName' => 'string',
      ),
    ),
    'phardata::setdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webindex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
    ),
    'phardata::setsignaturealgorithm' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'algorithm' => 'int',
        'privatekey=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'algo' => 'int',
        'privateKey=' => 'null|string',
      ),
    ),
    'phardata::setstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'newstub' => 'string',
        'maxlen=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stub' => 'string',
        'length=' => 'int',
      ),
    ),
    'pharfileinfo::compress' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'compression_type' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'compression' => 'int',
      ),
    ),
    'pharfileinfo::iscompressed' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'compression_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'compression=' => 'int|null',
      ),
    ),
    'php_strip_whitespace' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'file_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'filename' => 'string',
      ),
    ),
    'phpcredits' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'flag=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'phpinfo' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'what=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'phpversion' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'extension=' => 'null|string',
      ),
    ),
    'pos' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'posix_access' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'file' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'flags=' => 'int',
      ),
    ),
    'posix_getgrgid' => 
    array (
      'old' => 
      array (
        0 => 'array{gid: int, members: list<string>, name: string, passwd: string}|false',
        'gid' => 'int',
      ),
      'new' => 
      array (
        0 => 'array{gid: int, members: list<string>, name: string, passwd: string}|false',
        'group_id' => 'int',
      ),
    ),
    'posix_getpgid' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'pid' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'process_id' => 'int',
      ),
    ),
    'posix_getpwuid' => 
    array (
      'old' => 
      array (
        0 => 'array{dir: string, gecos: string, gid: int, name: string, passwd: string, shell: string, uid: int}|false',
        'uid' => 'int',
      ),
      'new' => 
      array (
        0 => 'array{dir: string, gecos: string, gid: int, name: string, passwd: string, shell: string, uid: int}|false',
        'user_id' => 'int',
      ),
    ),
    'posix_getsid' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'pid' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'process_id' => 'int',
      ),
    ),
    'posix_initgroups' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'base_group_id' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'username' => 'string',
        'group_id' => 'int',
      ),
    ),
    'posix_isatty' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fd' => 'int|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'file_descriptor' => 'int|resource',
      ),
    ),
    'posix_kill' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pid' => 'int',
        'sig' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'process_id' => 'int',
        'signal' => 'int',
      ),
    ),
    'posix_mkfifo' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathname' => 'string',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'permissions' => 'int',
      ),
    ),
    'posix_mknod' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathname' => 'string',
        'mode' => 'int',
        'major=' => 'int',
        'minor=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'flags' => 'int',
        'major=' => 'int',
        'minor=' => 'int',
      ),
    ),
    'posix_setegid' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'gid' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'group_id' => 'int',
      ),
    ),
    'posix_seteuid' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'uid' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'user_id' => 'int',
      ),
    ),
    'posix_setgid' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'gid' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'group_id' => 'int',
      ),
    ),
    'posix_setpgid' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pid' => 'int',
        'pgid' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'process_id' => 'int',
        'process_group_id' => 'int',
      ),
    ),
    'posix_setrlimit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'resource' => 'int',
        'softlimit' => 'int',
        'hardlimit' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'resource' => 'int',
        'soft_limit' => 'int',
        'hard_limit' => 'int',
      ),
    ),
    'posix_setuid' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'uid' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'user_id' => 'int',
      ),
    ),
    'posix_strerror' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'errno' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'error_code' => 'int',
      ),
    ),
    'posix_ttyname' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'fd' => 'int|resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'file_descriptor' => 'int|resource',
      ),
    ),
    'pow' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'base' => 'float|int',
        'exponent' => 'float|int',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'num' => 'float|int',
        'exponent' => 'float|int',
      ),
    ),
    'preg_filter' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null|string',
        'regex' => 'array<array-key, string>|string',
        'replace' => 'array<array-key, string>|string',
        'subject' => 'array<array-key, string>|string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null|string',
        'pattern' => 'array<array-key, string>|string',
        'replacement' => 'array<array-key, string>|string',
        'subject' => 'array<array-key, string>|string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
    ),
    'preg_grep' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'regex' => 'string',
        'input' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'pattern' => 'string',
        'array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'preg_match' => 
    array (
      'old' => 
      array (
        0 => '0|1|false',
        'pattern' => 'string',
        'subject' => 'string',
        '&w_subpatterns=' => 'array<array-key, string>',
        'flags=' => '0',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => '0|1|false',
        'pattern' => 'string',
        'subject' => 'string',
        '&w_matches=' => 'array<array-key, string>',
        'flags=' => '0',
        'offset=' => 'int',
      ),
    ),
    'preg_match_all' => 
    array (
      'old' => 
      array (
        0 => 'false|int<0, max>',
        'pattern' => 'string',
        'subject' => 'string',
        '&w_subpatterns=' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int<0, max>',
        'pattern' => 'string',
        'subject' => 'string',
        '&w_matches=' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'preg_quote' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delim_char=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delimiter=' => 'null|string',
      ),
    ),
    'preg_replace' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null|string',
        'regex' => 'array<array-key, mixed>|string',
        'replace' => 'array<array-key, mixed>|string',
        'subject' => 'array<array-key, mixed>|string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null|string',
        'pattern' => 'array<array-key, mixed>|string',
        'replacement' => 'array<array-key, mixed>|string',
        'subject' => 'array<array-key, mixed>|string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
    ),
    'preg_replace_callback' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'regex' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
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
    'preg_split' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'pattern' => 'string',
        'subject' => 'string',
        'limit=' => 'int',
        'flags=' => 'null',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'pattern' => 'string',
        'subject' => 'string',
        'limit=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'prev' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        '&r_arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
    ),
    'print_r' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'var' => 'mixed',
        'return=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'value' => 'mixed',
        'return=' => 'bool',
      ),
    ),
    'printf' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'format' => 'string',
        '...args=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'format' => 'string',
        '...values=' => 'float|int|string',
      ),
    ),
    'proc_get_status' => 
    array (
      'old' => 
      array (
        0 => 'array{command: string, exitcode: int, pid: int, running: bool, signaled: bool, stopped: bool, stopsig: int, termsig: int}|false',
        'process' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array{command: string, exitcode: int, pid: int, running: bool, signaled: bool, stopped: bool, stopsig: int, termsig: int}',
        'process' => 'resource',
      ),
    ),
    'proc_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'command' => 'array<array-key, mixed>|string',
        'descriptorspec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env=' => 'array<array-key, mixed>|null',
        'other_options=' => 'array<array-key, mixed>|null',
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
    'property_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object_or_class' => 'object|string',
        'property_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object_or_class' => 'object|string',
        'property' => 'string',
      ),
    ),
    'putenv' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'setting' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'assignment' => 'string',
      ),
    ),
    'quoted_printable_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'quoted_printable_encode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'quotemeta' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'rad2deg' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'range' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-array<array-key, mixed>',
        'low' => 'float|int|string',
        'high' => 'float|int|string',
        'step=' => 'float|int<1, max>',
      ),
      'new' => 
      array (
        0 => 'non-empty-array<array-key, mixed>',
        'start' => 'float|int|string',
        'end' => 'float|int|string',
        'step=' => 'float|int<1, max>',
      ),
    ),
    'rawurldecode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'rawurlencode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'readfile' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'flags=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
    ),
    'readline_completion_function' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'funcname' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable',
      ),
    ),
    'readline_info' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'varname=' => 'string',
        'newvalue=' => 'bool|int|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'var_name=' => 'null|string',
        'value=' => 'bool|int|null|string',
      ),
    ),
    'readline_read_history' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename=' => 'null|string',
      ),
    ),
    'readline_write_history' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename=' => 'null|string',
      ),
    ),
    'readlink' => 
    array (
      'old' => 
      array (
        0 => 'false|non-falsy-string',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|non-falsy-string',
        'path' => 'string',
      ),
    ),
    'recursivearrayiterator::asort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'recursivearrayiterator::ksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
      ),
      'new' => 
      array (
        0 => 'true',
        'flags=' => 'int',
      ),
    ),
    'recursivearrayiterator::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
      ),
    ),
    'recursivearrayiterator::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'int|string',
      ),
    ),
    'recursivearrayiterator::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null|string',
        'newval' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|null|string',
        'value' => 'string',
      ),
    ),
    'recursivearrayiterator::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'int|string',
      ),
    ),
    'recursivearrayiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'recursivearrayiterator::uasort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'recursivearrayiterator::uksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'recursivearrayiterator::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'recursivecachingiterator::offsetexists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
    ),
    'recursivecachingiterator::offsetget' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
    ),
    'recursivecachingiterator::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'string',
        'newval' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
      ),
    ),
    'recursivecachingiterator::offsetunset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'string',
      ),
    ),
    'recursivedirectoryiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'path' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'directory' => 'string',
        'flags=' => 'int',
      ),
    ),
    'recursivedirectoryiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'recursivedirectoryiterator::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'recursivedirectoryiterator::haschildren' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'allow_links=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'allowLinks=' => 'bool',
      ),
    ),
    'recursivedirectoryiterator::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'recursivedirectoryiterator::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'position' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'offset' => 'int',
      ),
    ),
    'recursivedirectoryiterator::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'recursivedirectoryiterator::setflags' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags' => 'int',
      ),
    ),
    'recursivedirectoryiterator::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'recursiveiteratoriterator::getsubiterator' => 
    array (
      'old' => 
      array (
        0 => 'RecursiveIterator|null',
        'level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'RecursiveIterator|null',
        'level=' => 'int|null',
      ),
    ),
    'recursiveiteratoriterator::setmaxdepth' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_depth=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'maxDepth=' => 'int',
      ),
    ),
    'recursiveregexiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'RecursiveIterator',
        'regex' => 'string',
        'mode=' => 'int',
        'flags=' => 'int',
        'preg_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'RecursiveIterator',
        'pattern' => 'string',
        'mode=' => 'int',
        'flags=' => 'int',
        'pregFlags=' => 'int',
      ),
    ),
    'recursiveregexiterator::setpregflags' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'preg_flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'pregFlags' => 'int',
      ),
    ),
    'recursivetreeiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'IteratorAggregate|RecursiveIterator',
        'flags=' => 'int',
        'caching_it_flags=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'IteratorAggregate|RecursiveIterator',
        'flags=' => 'int',
        'cachingIteratorFlags=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'recursivetreeiterator::getsubiterator' => 
    array (
      'old' => 
      array (
        0 => 'RecursiveIterator|null',
        'level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'RecursiveIterator|null',
        'level=' => 'int|null',
      ),
    ),
    'recursivetreeiterator::setmaxdepth' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_depth=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'maxDepth=' => 'int',
      ),
    ),
    'redis::_prefix' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
    ),
    'redis::bitcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'bybit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'bool',
      ),
    ),
    'redis::bitpos' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'bool',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'bool',
      ),
    ),
    'redis::blpop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'redis::brpop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'redis::config' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'operation' => 'string',
        'key_or_settings=' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'operation' => 'string',
        'key_or_settings=' => 'null|string',
        'value=' => 'null|string',
      ),
    ),
    'redis::connect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'float',
        'context=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int',
        'read_timeout=' => 'float',
        'context=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::decr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'int',
      ),
    ),
    'redis::expire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'redis::expireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'redis::flushall' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'sync=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'sync=' => 'bool|null',
      ),
    ),
    'redis::flushdb' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'sync=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'sync=' => 'bool|null',
      ),
    ),
    'redis::geodist' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'key' => 'string',
        'src' => 'string',
        'dst' => 'string',
        'unit=' => 'string',
      ),
      'new' => 
      array (
        0 => 'float',
        'key' => 'string',
        'src' => 'string',
        'dst' => 'string',
        'unit=' => 'null|string',
      ),
    ),
    'redis::georadius' => 
    array (
      'old' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'radius' => 'float',
        'unit' => 'float',
        'options=' => 'array<string, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'radius' => 'float',
        'unit' => 'string',
        'options=' => 'array<string, mixed>',
      ),
    ),
    'redis::getdbnum' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'redis::gethost' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'redis::getpersistentid' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'redis::getport' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'redis::getrange' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
    ),
    'redis::getreadtimeout' => 
    array (
      'old' => 
      array (
        0 => 'false|float',
      ),
      'new' => 
      array (
        0 => 'float',
      ),
    ),
    'redis::hscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'redis::incr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'int',
      ),
    ),
    'redis::linsert' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'string',
        'pivot' => 'string',
        'value' => 'string',
      ),
    ),
    'redis::lpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'redis::ltrim' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
    ),
    'redis::object' => 
    array (
      'old' => 
      array (
        0 => 'false|long|string',
        'subcommand' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'subcommand' => 'string',
        'key' => 'string',
      ),
    ),
    'redis::open' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'float',
        'context=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int',
        'read_timeout=' => 'float',
        'context=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::pconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'string',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'mixed',
        'context=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null|string',
        'retry_interval=' => 'int',
        'read_timeout=' => 'float',
        'context=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::pexpire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'redis::pexpireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'redis::pfadd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'elements' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'elements' => 'array<array-key, mixed>',
      ),
    ),
    'redis::ping' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'message=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'message=' => 'null|string',
      ),
    ),
    'redis::popen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'string',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'mixed',
        'context=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null|string',
        'retry_interval=' => 'int',
        'read_timeout=' => 'float',
        'context=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::psubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'patterns' => 'array<array-key, mixed>',
        'cb' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'patterns' => 'array<array-key, mixed>',
        'cb' => 'callable',
      ),
    ),
    'redis::punsubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'patterns' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'patterns' => 'array<array-key, mixed>',
      ),
    ),
    'redis::restore' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'ttl' => 'int',
        'value' => 'string',
        'options=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'ttl' => 'int',
        'value' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::rpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'redis::saddarray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'redis::scan' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>|false',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int|null',
        'type=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<int, string>|false',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
        'type=' => 'null|string',
      ),
    ),
    'redis::setbit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'idx' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'idx' => 'int',
        'value' => 'bool',
      ),
    ),
    'redis::setrange' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'index' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'index' => 'int',
        'value' => 'string',
      ),
    ),
    'redis::slaveof' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host=' => 'string',
        'port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host=' => 'null|string',
        'port=' => 'int',
      ),
    ),
    'redis::sort' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'key' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'key' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::sortasc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'null|string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'null|string',
      ),
    ),
    'redis::sortascalpha' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'null|string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'null|string',
      ),
    ),
    'redis::sortdesc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'null|string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'null|string',
      ),
    ),
    'redis::sortdescalpha' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'null|string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'null|string',
      ),
    ),
    'redis::spop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'redis::sscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'redis::subscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'callable',
      ),
    ),
    'redis::unsubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'channels' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'channels' => 'array<array-key, mixed>',
      ),
    ),
    'redis::watch' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
    ),
    'redis::xack' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'redis::xadd' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'mixed',
        'approx=' => 'mixed',
        'nomkstream=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'int',
        'approx=' => 'bool',
        'nomkstream=' => 'bool',
      ),
    ),
    'redis::xclaim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_idle' => 'mixed',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_idle' => 'int',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'redis::xdel' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'redis::xgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'string',
        'group=' => 'mixed',
        'id_or_consumer=' => 'mixed',
        'mkstream=' => 'mixed',
        'entries_read=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'null|string',
        'group=' => 'null|string',
        'id_or_consumer=' => 'null|string',
        'mkstream=' => 'bool',
        'entries_read=' => 'int',
      ),
    ),
    'redis::xinfo' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'string',
        'arg2=' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'null|string',
        'arg2=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'redis::xpending' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'count=' => 'mixed',
        'consumer=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'null|string',
        'end=' => 'null|string',
        'count=' => 'int',
        'consumer=' => 'null|string',
      ),
    ),
    'redis::xrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'count=' => 'int',
      ),
    ),
    'redis::xread' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'int',
        'block=' => 'int',
      ),
    ),
    'redis::xreadgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'int',
        'block=' => 'int',
      ),
    ),
    'redis::xrevrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'end' => 'mixed',
        'start' => 'mixed',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        'end' => 'string',
        'start' => 'string',
        'count=' => 'int',
      ),
    ),
    'redis::xtrim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'threshold' => 'mixed',
        'approx=' => 'mixed',
        'minid=' => 'mixed',
        'limit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'threshold' => 'string',
        'approx=' => 'bool',
        'minid=' => 'bool',
        'limit=' => 'int',
      ),
    ),
    'redis::zinter' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'keys' => 'string',
        'weights=' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::zinterstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'null|string',
      ),
    ),
    'redis::zrange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool|null',
      ),
    ),
    'redis::zrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
      ),
    ),
    'redis::zrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int|string',
        'end' => 'int|string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'redis::zremrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'float|string',
        'end' => 'float|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
      ),
    ),
    'redis::zscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'redis::zunion' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'keys' => 'string',
        'weights=' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'redis::zunionstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'null|string',
      ),
    ),
    'redisarray::_function' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|callable',
      ),
    ),
    'redisarray::_rehash' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'fn=' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'fn=' => 'callable|null',
      ),
    ),
    'redisarray::_target' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'key' => 'string',
      ),
    ),
    'redisarray::exec' => 
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
    'redisarray::keys' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>',
        'pattern' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<int, string>',
        'pattern' => 'string',
      ),
    ),
    'redisarray::multi' => 
    array (
      'old' => 
      array (
        0 => 'RedisArray',
        'host' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'RedisArray',
        'host' => 'string',
        'mode=' => 'int|null',
      ),
    ),
    'redisarray::ping' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
      ),
    ),
    'rediscluster::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'null|string',
        'seeds=' => 'array<array-key, string>',
        'timeout=' => 'float',
        'read_timeout=' => 'float',
        'persistent=' => 'bool',
        'auth=' => 'null|string',
        'context=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'null|string',
        'seeds=' => 'array<array-key, string>|null',
        'timeout=' => 'float',
        'read_timeout=' => 'float',
        'persistent=' => 'bool',
        'auth=' => 'null|string',
        'context=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::_prefix' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
    ),
    'rediscluster::bitcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'bybit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'bool',
      ),
    ),
    'rediscluster::bitpos' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'bool',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'bool',
      ),
    ),
    'rediscluster::blpop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'array<array-key, mixed>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'key' => 'array<array-key, mixed>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'rediscluster::brpop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'array<array-key, mixed>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'key' => 'array<array-key, mixed>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'rediscluster::client' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'subcommand' => 'string',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'subcommand' => 'string',
        'arg=' => 'null|string',
      ),
    ),
    'rediscluster::decr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'int',
      ),
    ),
    'rediscluster::exec' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
      ),
    ),
    'rediscluster::expire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'rediscluster::expireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'rediscluster::geodist' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'src' => 'string',
        'dest' => 'string',
        'unit=' => 'string',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|float',
        'key' => 'string',
        'src' => 'string',
        'dest' => 'string',
        'unit=' => 'null|string',
      ),
    ),
    'rediscluster::hdel' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'member' => 'string',
        '...other_members=' => 'array<array-key, string>',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'member' => 'string',
        '...other_members=' => 'string',
      ),
    ),
    'rediscluster::hscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::incr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'int',
      ),
    ),
    'rediscluster::lget' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|bool|string',
        'key' => 'string',
        'index' => 'int',
      ),
    ),
    'rediscluster::linsert' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'string',
        'pivot' => 'string',
        'value' => 'string',
      ),
    ),
    'rediscluster::lpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::ltrim' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|bool',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
    ),
    'rediscluster::msetnx' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key_values' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|false',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'rediscluster::multi' => 
    array (
      'old' => 
      array (
        0 => 'Redis',
        'value=' => 'int',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|bool',
        'value=' => 'int',
      ),
    ),
    'rediscluster::pexpire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'rediscluster::pexpireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'null|string',
      ),
    ),
    'rediscluster::ping' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'message=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'message=' => 'null|string',
      ),
    ),
    'rediscluster::psubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'patterns' => 'array<array-key, mixed>',
        'callback' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'patterns' => 'array<array-key, mixed>',
        'callback' => 'callable',
      ),
    ),
    'rediscluster::restore' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'value' => 'string',
        'options=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'value' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::role' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'array<array-key, mixed>|string',
      ),
    ),
    'rediscluster::rpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::scan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&iterator' => 'int',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&iterator' => 'int|null',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::setbit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'onoff' => 'bool|int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'onoff' => 'bool',
      ),
    ),
    'rediscluster::setrange' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
        'offset' => 'int',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|int',
        'key' => 'string',
        'offset' => 'int',
        'value' => 'string',
      ),
    ),
    'rediscluster::sort' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::spop' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::sscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'null',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null',
        'count=' => 'int',
      ),
    ),
    'rediscluster::subscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'callable',
      ),
    ),
    'rediscluster::time' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'array<array-key, mixed>|string',
      ),
    ),
    'rediscluster::watch' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|bool',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
    ),
    'rediscluster::xack' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|int',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'rediscluster::xadd' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'mixed',
        'approx=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|string',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'int',
        'approx=' => 'bool',
      ),
    ),
    'rediscluster::xclaim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_iddle' => 'mixed',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|false|string',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_iddle' => 'int',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'rediscluster::xdel' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|int',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'rediscluster::xgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'string',
        'group=' => 'mixed',
        'id_or_consumer=' => 'mixed',
        'mkstream=' => 'mixed',
        'entries_read=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'null|string',
        'group=' => 'null|string',
        'id_or_consumer=' => 'null|string',
        'mkstream=' => 'bool',
        'entries_read=' => 'int',
      ),
    ),
    'rediscluster::xinfo' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'string',
        'arg2=' => 'string',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'null|string',
        'arg2=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::xpending' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'count=' => 'mixed',
        'consumer=' => 'string',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|false',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'null|string',
        'end=' => 'null|string',
        'count=' => 'int',
        'consumer=' => 'null|string',
      ),
    ),
    'rediscluster::xrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|bool',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::xread' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|bool',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'int',
        'block=' => 'int',
      ),
    ),
    'rediscluster::xreadgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|bool',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'int',
        'block=' => 'int',
      ),
    ),
    'rediscluster::xrevrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|array<array-key, mixed>|bool',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::xtrim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'maxlen' => 'mixed',
        'approx=' => 'mixed',
        'minid=' => 'mixed',
        'limit=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|int',
        'key' => 'string',
        'maxlen' => 'int',
        'approx=' => 'bool',
        'minid=' => 'bool',
        'limit=' => 'int',
      ),
    ),
    'rediscluster::zinterstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'null|string',
      ),
    ),
    'rediscluster::zlexcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
    ),
    'rediscluster::zrange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool|null',
      ),
    ),
    'rediscluster::zrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
      ),
    ),
    'rediscluster::zrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'rediscluster::zremrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
      ),
      'new' => 
      array (
        0 => 'RedisCluster|false|int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
    ),
    'rediscluster::zremrangebyrank' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
    ),
    'rediscluster::zremrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'float|string',
        'max' => 'float|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
    ),
    'rediscluster::zrevrange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::zrevrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::zrevrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'rediscluster::zscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int',
      ),
    ),
    'rediscluster::zunionstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'null|string',
      ),
    ),
    'reflectionclass::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'argument' => 'class-string|object',
      ),
      'new' => 
      array (
        0 => 'void',
        'objectOrClass' => 'class-string|object',
      ),
    ),
    'reflectionclass::getconstants' => 
    array (
      'old' => 
      array (
        0 => 'array<string, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<string, mixed>',
        'filter=' => 'int|null',
      ),
    ),
    'reflectionclass::getreflectionconstants' => 
    array (
      'old' => 
      array (
        0 => 'list<ReflectionClassConstant>',
      ),
      'new' => 
      array (
        0 => 'list<ReflectionClassConstant>',
        'filter=' => 'int|null',
      ),
    ),
    'reflectionclass::newinstance' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'object',
        '...args=' => 'mixed',
      ),
    ),
    'reflectionclass::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'list<mixed>',
      ),
      'new' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'reflectionclassconstant::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class' => 'class-string|object',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'class-string|object',
        'constant' => 'string',
      ),
    ),
    'reflectionfunction::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'Closure|callable-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'function' => 'Closure|callable-string',
      ),
    ),
    'reflectionmethod::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_or_method' => 'class-string|object',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'objectOrMethod' => 'class-string|object',
        'method=' => 'null|string',
      ),
    ),
    'reflectionmethod::getclosure' => 
    array (
      'old' => 
      array (
        0 => 'Closure|null',
        'object=' => 'object',
      ),
      'new' => 
      array (
        0 => 'Closure',
        'object=' => 'null|object',
      ),
    ),
    'reflectionmethod::invoke' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'object' => 'null|object',
        'args' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'object' => 'null|object',
        '...args=' => 'mixed',
      ),
    ),
    'reflectionmethod::setaccessible' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'value' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'accessible' => 'bool',
      ),
    ),
    'reflectionobject::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'argument' => 'object',
      ),
      'new' => 
      array (
        0 => 'void',
        'object' => 'object',
      ),
    ),
    'reflectionobject::getconstants' => 
    array (
      'old' => 
      array (
        0 => 'array<string, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<string, mixed>',
        'filter=' => 'int|null',
      ),
    ),
    'reflectionobject::getreflectionconstants' => 
    array (
      'old' => 
      array (
        0 => 'list<ReflectionClassConstant>',
      ),
      'new' => 
      array (
        0 => 'list<ReflectionClassConstant>',
        'filter=' => 'int|null',
      ),
    ),
    'reflectionobject::newinstance' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'object',
        '...args=' => 'array<array-key, mixed>',
      ),
    ),
    'reflectionobject::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'list<mixed>',
      ),
      'new' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'reflectionparameter::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'function' => 'array<array-key, mixed>|object|string',
        'parameter' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'function' => 'array<array-key, mixed>|object|string',
        'param' => 'int|string',
      ),
    ),
    'reflectionproperty::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class' => 'class-string|object',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'class-string|object',
        'property' => 'string',
      ),
    ),
    'reflectionproperty::getvalue' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'object=' => 'object',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'object=' => 'null|object',
      ),
    ),
    'reflectionproperty::isinitialized' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'object=' => 'object',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object=' => 'null|object',
      ),
    ),
    'reflectionproperty::setaccessible' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'visible' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'accessible' => 'bool',
      ),
    ),
    'reflectionproperty::setvalue' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'null|object',
        'value=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'objectOrValue' => 'null|object',
        'value=' => 'mixed',
      ),
    ),
    'regexiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'regex' => 'string',
        'mode=' => 'int',
        'flags=' => 'int',
        'preg_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'iterator' => 'Iterator',
        'pattern' => 'string',
        'mode=' => 'int',
        'flags=' => 'int',
        'pregFlags=' => 'int',
      ),
    ),
    'regexiterator::setpregflags' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'preg_flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'pregFlags' => 'int',
      ),
    ),
    'register_shutdown_function' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'function_name' => 'callable',
        '...parameters=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'callback' => 'callable',
        '...args=' => 'mixed',
      ),
    ),
    'register_tick_function' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'function_name' => 'callable():void',
        '...parameters=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable():void',
        '...args=' => 'mixed',
      ),
    ),
    'rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'old_name' => 'string',
        'new_name' => 'string',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'from' => 'string',
        'to' => 'string',
        'context=' => 'resource',
      ),
    ),
    'reset' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        '&r_arg' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
    ),
    'resourcebundle::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'bundlename' => 'null|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'bundle' => 'null|string',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle::create' => 
    array (
      'old' => 
      array (
        0 => 'ResourceBundle|null',
        'locale' => 'null|string',
        'bundlename' => 'null|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|null',
        'locale' => 'null|string',
        'bundle' => 'null|string',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle::getlocales' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'bundlename' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'bundle' => 'string',
      ),
    ),
    'resourcebundle_create' => 
    array (
      'old' => 
      array (
        0 => 'ResourceBundle|null',
        'locale' => 'null|string',
        'bundlename' => 'null|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|null',
        'locale' => 'null|string',
        'bundle' => 'null|string',
        'fallback=' => 'bool',
      ),
    ),
    'resourcebundle_locales' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'bundlename' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'bundle' => 'string',
      ),
    ),
    'rewind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
      ),
    ),
    'rmdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dirname' => 'string',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'directory' => 'string',
        'context=' => 'resource',
      ),
    ),
    'round' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float|int',
        'precision=' => 'int',
        'mode=' => 'int<0, max>',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float|int',
        'precision=' => 'int',
        'mode=' => 'int<0, max>',
      ),
    ),
    'rsort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'rtrim' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'character_mask=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'characters=' => 'string',
      ),
    ),
    'sapi_windows_vt100_support' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'enable=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'enable=' => 'bool|null',
      ),
    ),
    'scandir' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'dir' => 'string',
        'sorting_order=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'directory' => 'string',
        'sorting_order=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'sem_acquire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'semaphore' => 'resource',
        'non_blocking=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'semaphore' => 'SysvSemaphore',
        'non_blocking=' => 'bool',
      ),
    ),
    'sem_get' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'int',
        'max_acquire=' => 'int',
        'permissions=' => 'int',
        'auto_release=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SysvSemaphore|false',
        'key' => 'int',
        'max_acquire=' => 'int',
        'permissions=' => 'int',
        'auto_release=' => 'bool',
      ),
    ),
    'sem_release' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'semaphore' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'semaphore' => 'SysvSemaphore',
      ),
    ),
    'sem_remove' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'semaphore' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'semaphore' => 'SysvSemaphore',
      ),
    ),
    'serialize' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'value' => 'mixed',
      ),
    ),
    'session_cache_expire' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'new_cache_expire=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'value=' => 'int|null',
      ),
    ),
    'session_cache_limiter' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'cache_limiter=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'value=' => 'null|string',
      ),
    ),
    'session_id' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'id=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'id=' => 'null|string',
      ),
    ),
    'session_module_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'module=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'module=' => 'null|string',
      ),
    ),
    'session_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'name=' => 'null|string',
      ),
    ),
    'session_save_path' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'path=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'path=' => 'null|string',
      ),
    ),
    'session_set_cookie_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lifetime_or_options' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lifetime_or_options' => 'int',
        'path=' => 'null|string',
        'domain=' => 'null|string',
        'secure=' => 'bool|null',
        'httponly=' => 'bool|null',
      ),
    ),
    'sessionhandler::destroy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'id' => 'string',
      ),
    ),
    'sessionhandler::gc' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'maxlifetime' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'max_lifetime' => 'int',
      ),
    ),
    'sessionhandler::open' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'save_path' => 'string',
        'session_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
        'name' => 'string',
      ),
    ),
    'sessionhandler::read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'id' => 'string',
      ),
    ),
    'sessionhandler::write' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'val' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'id' => 'string',
        'data' => 'string',
      ),
    ),
    'set_error_handler' => 
    array (
      'old' => 
      array (
        0 => 'callable(int, string, string=, int=, array<array-key, mixed>=):bool|null',
        'error_handler' => 'callable(int, string, string=, int=, array<array-key, mixed>=):bool|null',
        'error_types=' => 'int',
      ),
      'new' => 
      array (
        0 => 'callable(int, string, string=, int=, array<array-key, mixed>=):bool|null',
        'callback' => 'callable(int, string, string=, int=, array<array-key, mixed>=):bool|null',
        'error_levels=' => 'int',
      ),
    ),
    'set_exception_handler' => 
    array (
      'old' => 
      array (
        0 => 'callable(Throwable):void|null',
        'exception_handler' => 'callable(Throwable):void|null',
      ),
      'new' => 
      array (
        0 => 'callable(Throwable):void|null',
        'callback' => 'callable(Throwable):void|null',
      ),
    ),
    'set_file_buffer' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
        'buffer' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'size' => 'int',
      ),
    ),
    'set_include_path' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'new_include_path' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'include_path' => 'string',
      ),
    ),
    'setlocale' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'category' => 'int',
        '...locales' => '0|null|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'category' => 'int',
        'locales' => '0|null|string',
        '...rest=' => 'string',
      ),
    ),
    'sha1' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'sha1_file' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'filename' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'shell_exec' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'cmd' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'command' => 'string',
      ),
    ),
    'shm_attach' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'int',
        'size=' => 'int',
        'permissions=' => 'int',
      ),
      'new' => 
      array (
        0 => 'SysvSharedMemory|false',
        'key' => 'int',
        'size=' => 'int|null',
        'permissions=' => 'int',
      ),
    ),
    'shm_detach' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shm' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shm' => 'SysvSharedMemory',
      ),
    ),
    'shm_get_var' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'shm' => 'resource',
        'key' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'shm' => 'SysvSharedMemory',
        'key' => 'int',
      ),
    ),
    'shm_has_var' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shm' => 'resource',
        'key' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shm' => 'SysvSharedMemory',
        'key' => 'int',
      ),
    ),
    'shm_put_var' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shm' => 'resource',
        'key' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shm' => 'SysvSharedMemory',
        'key' => 'int',
        'value' => 'mixed',
      ),
    ),
    'shm_remove' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shm' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shm' => 'SysvSharedMemory',
      ),
    ),
    'shm_remove_var' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shm' => 'resource',
        'key' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shm' => 'SysvSharedMemory',
        'key' => 'int',
      ),
    ),
    'shmop_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'shmop' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'shmop' => 'Shmop',
      ),
    ),
    'shmop_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shmop' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'shmop' => 'Shmop',
      ),
    ),
    'shmop_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'key' => 'int',
        'mode' => 'string',
        'permissions' => 'int',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'Shmop|false',
        'key' => 'int',
        'mode' => 'string',
        'permissions' => 'int',
        'size' => 'int',
      ),
    ),
    'shmop_read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'shmop' => 'resource',
        'offset' => 'int',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'shmop' => 'Shmop',
        'offset' => 'int',
        'size' => 'int',
      ),
    ),
    'shmop_size' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'shmop' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'shmop' => 'Shmop',
      ),
    ),
    'shmop_write' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'shmop' => 'resource',
        'data' => 'string',
        'offset' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'shmop' => 'Shmop',
        'data' => 'string',
        'offset' => 'int',
      ),
    ),
    'show_source' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'file_name' => 'string',
        'return=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'filename' => 'string',
        'return=' => 'bool',
      ),
    ),
    'shuffle' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'similar_text' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
        '&w_percent=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        '&w_percent=' => 'float',
      ),
    ),
    'simplexml_load_file' => 
    array (
      'old' => 
      array (
        0 => 'SimpleXMLElement|false',
        'filename' => 'string',
        'class_name=' => 'null|string',
        'options=' => 'int',
        'ns=' => 'string',
        'is_prefix=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SimpleXMLElement|false',
        'filename' => 'string',
        'class_name=' => 'null|string',
        'options=' => 'int',
        'namespace_or_prefix=' => 'string',
        'is_prefix=' => 'bool',
      ),
    ),
    'simplexml_load_string' => 
    array (
      'old' => 
      array (
        0 => 'SimpleXMLElement|false',
        'data' => 'string',
        'class_name=' => 'null|string',
        'options=' => 'int',
        'ns=' => 'string',
        'is_prefix=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SimpleXMLElement|false',
        'data' => 'string',
        'class_name=' => 'null|string',
        'options=' => 'int',
        'namespace_or_prefix=' => 'string',
        'is_prefix=' => 'bool',
      ),
    ),
    'simplexmlelement::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'string',
        'options=' => 'int',
        'data_is_url=' => 'bool',
        'ns=' => 'string',
        'is_prefix=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
        'options=' => 'int',
        'dataIsURL=' => 'bool',
        'namespaceOrPrefix=' => 'string',
        'isPrefix=' => 'bool',
      ),
    ),
    'simplexmlelement::addattribute' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'string',
        'ns=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'qualifiedName' => 'string',
        'value' => 'string',
        'namespace=' => 'null|string',
      ),
    ),
    'simplexmlelement::addchild' => 
    array (
      'old' => 
      array (
        0 => 'SimpleXMLElement|null',
        'name' => 'string',
        'value=' => 'null|string',
        'ns=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'SimpleXMLElement|null',
        'qualifiedName' => 'string',
        'value=' => 'null|string',
        'namespace=' => 'null|string',
      ),
    ),
    'simplexmlelement::asxml' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'filename=' => 'null|string',
      ),
    ),
    'simplexmlelement::attributes' => 
    array (
      'old' => 
      array (
        0 => 'SimpleXMLElement|null',
        'ns=' => 'null|string',
        'is_prefix=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SimpleXMLElement|null',
        'namespaceOrPrefix=' => 'null|string',
        'isPrefix=' => 'bool',
      ),
    ),
    'simplexmlelement::children' => 
    array (
      'old' => 
      array (
        0 => 'SimpleXMLElement|null',
        'ns=' => 'null|string',
        'is_prefix=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SimpleXMLElement|null',
        'namespaceOrPrefix=' => 'null|string',
        'isPrefix=' => 'bool',
      ),
    ),
    'simplexmlelement::getdocnamespaces' => 
    array (
      'old' => 
      array (
        0 => 'array<string, string>',
        'recursve=' => 'bool',
        'from_root=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<string, string>',
        'recursive=' => 'bool',
        'fromRoot=' => 'bool',
      ),
    ),
    'simplexmlelement::getnamespaces' => 
    array (
      'old' => 
      array (
        0 => 'array<string, string>',
        'recursve=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<string, string>',
        'recursive=' => 'bool',
      ),
    ),
    'simplexmlelement::registerxpathnamespace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'ns' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'namespace' => 'string',
      ),
    ),
    'simplexmlelement::savexml' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'filename=' => 'null|string',
      ),
    ),
    'simplexmlelement::xpath' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, SimpleXMLElement>|false|null',
        'path' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, SimpleXMLElement>|false|null',
        'expression' => 'string',
      ),
    ),
    'sin' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'sinh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'sizeof' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'var' => 'Countable|SimpleXMLElement|array<array-key, mixed>',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'value' => 'Countable|array<array-key, mixed>',
        'mode=' => 'int',
      ),
    ),
    'sleep' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'seconds' => 'int<0, max>',
      ),
      'new' => 
      array (
        0 => 'int',
        'seconds' => 'int<0, max>',
      ),
    ),
    'soapclient::__call' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'function_name' => 'string',
        'arguments' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'name' => 'string',
        'args' => 'array<array-key, mixed>',
      ),
    ),
    'soapclient::__dorequest' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'request' => 'string',
        'location' => 'string',
        'action' => 'string',
        'version' => 'int',
        'one_way=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'request' => 'string',
        'location' => 'string',
        'action' => 'string',
        'version' => 'int',
        'oneWay=' => 'bool',
      ),
    ),
    'soapclient::__setcookie' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'name' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'name' => 'string',
        'value=' => 'null|string',
      ),
    ),
    'soapclient::__setlocation' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'new_location=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'location=' => 'null|string',
      ),
    ),
    'soapclient::__setsoapheaders' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'soapheaders=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'headers=' => 'mixed',
      ),
    ),
    'soapclient::__soapcall' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'function_name' => 'string',
        'arguments' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>',
        'input_headers=' => 'SoapHeader|array<array-key, mixed>',
        '&w_output_headers=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'name' => 'string',
        'args' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
        'inputHeaders=' => 'SoapHeader|array<array-key, mixed>',
        '&w_outputHeaders=' => 'array<array-key, mixed>',
      ),
    ),
    'soapfault::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'message=' => 'array<array-key, mixed>|null|string',
        'code=' => 'array<array-key, mixed>|null|string',
        'previous=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'code' => 'array<array-key, mixed>|null|string',
        'string' => 'string',
        'actor=' => 'null|string',
        'details=' => 'mixed|null',
        'name=' => 'null|string',
        'headerFault=' => 'mixed|null',
      ),
    ),
    'soapserver::addsoapheader' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'SoapHeader',
      ),
      'new' => 
      array (
        0 => 'void',
        'header' => 'SoapHeader',
      ),
    ),
    'soapserver::handle' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'soap_request=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'request=' => 'null|string',
      ),
    ),
    'soapserver::setclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name' => 'string',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'string',
        '...args=' => 'mixed',
      ),
    ),
    'socket_accept' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'socket' => 'resource',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'socket' => 'Socket',
      ),
    ),
    'socket_addrinfo_bind' => 
    array (
      'old' => 
      array (
        0 => 'null|resource',
        'addrinfo' => 'resource',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'address' => 'AddressInfo',
      ),
    ),
    'socket_addrinfo_connect' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'addrinfo' => 'resource',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'address' => 'AddressInfo',
      ),
    ),
    'socket_addrinfo_explain' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'addrinfo' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'address' => 'AddressInfo',
      ),
    ),
    'socket_addrinfo_lookup' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, resource>',
        'host' => 'string',
        'service=' => 'string',
        'hints=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, AddressInfo>|false',
        'host' => 'string',
        'service=' => 'null|string',
        'hints=' => 'array<array-key, mixed>',
      ),
    ),
    'socket_bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'address' => 'string',
        'port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'address' => 'string',
        'port=' => 'int',
      ),
    ),
    'socket_clear_error' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'socket=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'socket=' => 'Socket|null',
      ),
    ),
    'socket_close' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'socket' => 'resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'socket' => 'Socket',
      ),
    ),
    'socket_connect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'address' => 'string',
        'port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'address' => 'string',
        'port=' => 'int|null',
      ),
    ),
    'socket_create' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
      ),
    ),
    'socket_create_listen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'port' => 'int',
        'backlog=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'port' => 'int',
        'backlog=' => 'int',
      ),
    ),
    'socket_create_pair' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
        '&w_pair' => 'array<array-key, resource>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'domain' => 'int',
        'type' => 'int',
        'protocol' => 'int',
        '&w_pair' => 'array<array-key, Socket>',
      ),
    ),
    'socket_export_stream' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'socket' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'socket' => 'Socket',
      ),
    ),
    'socket_get_option' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|int',
        'socket' => 'resource',
        'level' => 'int',
        'option' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|int',
        'socket' => 'Socket',
        'level' => 'int',
        'option' => 'int',
      ),
    ),
    'socket_get_status' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'stream' => 'Socket',
      ),
    ),
    'socket_getopt' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|int',
        'socket' => 'resource',
        'level' => 'int',
        'option' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|int',
        'socket' => 'Socket',
        'level' => 'int',
        'option' => 'int',
      ),
    ),
    'socket_getpeername' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
    ),
    'socket_getsockname' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
    ),
    'socket_import_stream' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'stream' => 'resource',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'stream' => 'resource',
      ),
    ),
    'socket_last_error' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'socket=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'socket=' => 'Socket|null',
      ),
    ),
    'socket_listen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'backlog=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'backlog=' => 'int',
      ),
    ),
    'socket_read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'socket' => 'resource',
        'length' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'socket' => 'Socket',
        'length' => 'int',
        'mode=' => 'int',
      ),
    ),
    'socket_recv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        '&w_data' => 'string',
        'length' => 'int',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        '&w_data' => 'string',
        'length' => 'int',
        'flags' => 'int',
      ),
    ),
    'socket_recvfrom' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        '&w_data' => 'string',
        'length' => 'int',
        'flags' => 'int',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        '&w_data' => 'string',
        'length' => 'int',
        'flags' => 'int',
        '&w_address' => 'string',
        '&w_port=' => 'int',
      ),
    ),
    'socket_recvmsg' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        '&w_message' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        '&w_message' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'socket_select' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        '&rw_read' => 'array<array-key, resource>|null',
        '&rw_write' => 'array<array-key, resource>|null',
        '&rw_except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        '&rw_read' => 'array<array-key, Socket>|null',
        '&rw_write' => 'array<array-key, Socket>|null',
        '&rw_except' => 'array<array-key, Socket>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int',
      ),
    ),
    'socket_send' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        'data' => 'string',
        'length' => 'int',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        'data' => 'string',
        'length' => 'int',
        'flags' => 'int',
      ),
    ),
    'socket_sendmsg' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        'message' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        'message' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'socket_sendto' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        'data' => 'string',
        'length' => 'int',
        'flags' => 'int',
        'address' => 'string',
        'port=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        'data' => 'string',
        'length' => 'int',
        'flags' => 'int',
        'address' => 'string',
        'port=' => 'int|null',
      ),
    ),
    'socket_set_block' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
      ),
    ),
    'socket_set_blocking' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'mode' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'Socket',
        'enable' => 'bool',
      ),
    ),
    'socket_set_nonblock' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
      ),
    ),
    'socket_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'level' => 'int',
        'option' => 'int',
        'value' => 'array<array-key, mixed>|int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'level' => 'int',
        'option' => 'int',
        'value' => 'array<array-key, mixed>|int|string',
      ),
    ),
    'socket_setopt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'level' => 'int',
        'option' => 'int',
        'value' => 'array<array-key, mixed>|int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'level' => 'int',
        'option' => 'int',
        'value' => 'array<array-key, mixed>|int|string',
      ),
    ),
    'socket_shutdown' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'socket' => 'Socket',
        'mode=' => 'int',
      ),
    ),
    'socket_write' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'Socket',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'socket_wsaprotocol_info_export' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'socket' => 'resource',
        'process_id' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'socket' => 'Socket',
        'process_id' => 'int',
      ),
    ),
    'socket_wsaprotocol_info_import' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'info_id' => 'string',
      ),
      'new' => 
      array (
        0 => 'Socket|false',
        'info_id' => 'string',
      ),
    ),
    'sodium_add' => 
    array (
      'old' => 
      array (
        0 => 'void',
        '&string_1' => 'string',
        'string_2' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        '&string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'sodium_base642bin' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string_1' => 'string',
        'id' => 'int',
        'string_2=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'id' => 'int',
        'ignore=' => 'string',
      ),
    ),
    'sodium_compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string_1' => 'string',
        'string_2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'sodium_crypto_aead_chacha20poly1305_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_aead_chacha20poly1305_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'ad' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'additional_data' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_auth' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_auth_verify' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'signature' => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'mac' => 'string',
        'message' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_box' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'nonce' => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_box_open' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'nonce' => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_box_publickey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_box_publickey_from_secretkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_box_seal' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'public_key' => 'string',
      ),
    ),
    'sodium_crypto_box_seal_open' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_box_secretkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_box_seed_keypair' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'seed' => 'string',
      ),
    ),
    'sodium_crypto_generichash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'key=' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'key=' => 'string',
        'length=' => 'int',
      ),
    ),
    'sodium_crypto_generichash_update' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&state' => 'string',
        'string' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        '&state' => 'string',
        'message' => 'string',
      ),
    ),
    'sodium_crypto_kdf_derive_from_key' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'subkey_len' => 'int',
        'subkey_id' => 'int',
        'context' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'subkey_length' => 'int',
        'subkey_id' => 'int',
        'context' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_kx_client_session_keys' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>',
        'client_keypair' => 'string',
        'server_key' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<int, string>',
        'client_key_pair' => 'string',
        'server_key' => 'string',
      ),
    ),
    'sodium_crypto_kx_publickey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_kx_secretkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_kx_seed_keypair' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'seed' => 'string',
      ),
    ),
    'sodium_crypto_kx_server_session_keys' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>',
        'server_keypair' => 'string',
        'client_key' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<int, string>',
        'server_key_pair' => 'string',
        'client_key' => 'string',
      ),
    ),
    'sodium_crypto_pwhash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'length' => 'int',
        'password' => 'string',
        'salt' => 'string',
        'opslimit' => 'int',
        'memlimit' => 'int',
        'alg=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        'password' => 'string',
        'salt' => 'string',
        'opslimit' => 'int',
        'memlimit' => 'int',
        'algo=' => 'int',
      ),
    ),
    'sodium_crypto_pwhash_scryptsalsa208sha256' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'length' => 'int',
        'password' => 'string',
        'salt' => 'string',
        'opslimit' => 'int',
        'memlimit' => 'int',
        'alg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        'password' => 'string',
        'salt' => 'string',
        'opslimit' => 'int',
        'memlimit' => 'int',
      ),
    ),
    'sodium_crypto_scalarmult' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string_1' => 'string',
        'string_2' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'n' => 'string',
        'p' => 'string',
      ),
    ),
    'sodium_crypto_scalarmult_base' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string_1' => 'string',
        'string_2' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_secretbox' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_secretbox_open' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ciphertext' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'header' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_pull' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&r_state' => 'string',
        'string=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&r_state' => 'string',
        'ciphertext' => 'string',
        'additional_data=' => 'string',
      ),
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_push' => 
    array (
      'old' => 
      array (
        0 => 'string',
        '&w_state' => 'string',
        'string=' => 'string',
        'long=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        '&w_state' => 'string',
        'message' => 'string',
        'additional_data=' => 'string',
        'tag=' => 'int',
      ),
    ),
    'sodium_crypto_shorthash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_crypto_sign' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'keypair' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_detached' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'keypair' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_ed25519_pk_to_curve25519' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'public_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_ed25519_sk_to_curve25519' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_open' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'keypair' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'signed_message' => 'string',
        'public_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_publickey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_sign_publickey_from_secretkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'secret_key' => 'string',
      ),
    ),
    'sodium_crypto_sign_secretkey' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_pair' => 'string',
      ),
    ),
    'sodium_crypto_sign_seed_keypair' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'seed' => 'string',
      ),
    ),
    'sodium_crypto_sign_verify_detached' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'signature' => 'string',
        'string' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'signature' => 'string',
        'message' => 'string',
        'public_key' => 'string',
      ),
    ),
    'sodium_crypto_stream_xor' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message' => 'string',
        'nonce' => 'string',
        'key' => 'string',
      ),
    ),
    'sodium_hex2bin' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string_1' => 'string',
        'string_2=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'ignore=' => 'string',
      ),
    ),
    'sodium_memcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string_1' => 'string',
        'string_2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'sodium_memzero' => 
    array (
      'old' => 
      array (
        0 => 'void',
        '&w_reference' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        '&w_string' => 'string',
      ),
    ),
    'sodium_pad' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'block_size' => 'int',
      ),
    ),
    'sodium_unpad' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'block_size' => 'int',
      ),
    ),
    'sort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'sort_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'soundex' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'spl_autoload' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name' => 'string',
        'file_extensions=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'string',
        'file_extensions=' => 'null|string',
      ),
    ),
    'spl_autoload_call' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'string',
      ),
    ),
    'spl_autoload_extensions' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'file_extensions=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'file_extensions=' => 'null|string',
      ),
    ),
    'spl_autoload_functions' => 
    array (
      'old' => 
      array (
        0 => 'false|list<callable(string):void>',
      ),
      'new' => 
      array (
        0 => 'list<callable(string):void>',
      ),
    ),
    'spl_autoload_register' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'autoload_function=' => 'callable(string):void',
        'throw=' => 'bool',
        'prepend=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback=' => 'callable(string):void|null',
        'throw=' => 'bool',
        'prepend=' => 'bool',
      ),
    ),
    'spl_autoload_unregister' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'autoload_function' => 'callable(string):void',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable(string):void',
      ),
    ),
    'spl_object_hash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'obj' => 'object',
      ),
      'new' => 
      array (
        0 => 'string',
        'object' => 'object',
      ),
    ),
    'spl_object_id' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'obj' => 'object',
      ),
      'new' => 
      array (
        0 => 'int',
        'object' => 'object',
      ),
    ),
    'spldoublylinkedlist::add' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int',
        'value' => 'mixed',
      ),
    ),
    'spldoublylinkedlist::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'value' => 'mixed',
      ),
    ),
    'spldoublylinkedlist::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'splfileinfo::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'file_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
      ),
    ),
    'splfileinfo::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'splfileinfo::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'splfileinfo::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'splfileinfo::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'splfileinfo::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'splfileobject::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'file_name' => 'string',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'splfileobject::fgetcsv' => 
    array (
      'old' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'splfileobject::fgets' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'splfileobject::flock' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'operation' => 'int',
        '&w_wouldblock=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'operation' => 'int',
        '&w_wouldBlock=' => 'int',
      ),
    ),
    'splfileobject::fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'splfileobject::fseek' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pos' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'splfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
    ),
    'splfileobject::getcurrentline' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'splfileobject::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'splfileobject::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'splfileobject::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'splfileobject::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'line_pos' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'line' => 'int',
      ),
    ),
    'splfileobject::setcsvcontrol' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'splfileobject::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'splfileobject::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'splfileobject::setmaxlinelen' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_len' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'maxLength' => 'int',
      ),
    ),
    'splfixedarray::fromarray' => 
    array (
      'old' => 
      array (
        0 => 'SplFixedArray',
        'array' => 'array<array-key, mixed>',
        'save_indexes=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SplFixedArray',
        'array' => 'array<array-key, mixed>',
        'preserveKeys=' => 'bool',
      ),
    ),
    'splfixedarray::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int',
        'value' => 'mixed',
      ),
    ),
    'splfixedarray::setsize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'size' => 'int',
      ),
    ),
    'splheap::compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'value1' => 'mixed',
        'value2' => 'mixed',
      ),
    ),
    'splobjectstorage::addall' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'object' => 'SplObjectStorage',
      ),
      'new' => 
      array (
        0 => 'int',
        'storage' => 'SplObjectStorage',
      ),
    ),
    'splobjectstorage::attach' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'object',
        'data=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'object' => 'object',
        'info=' => 'mixed',
      ),
    ),
    'splobjectstorage::count' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode=' => 'int',
      ),
    ),
    'splobjectstorage::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'object',
        'data=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'object' => 'object',
        'info=' => 'mixed',
      ),
    ),
    'splobjectstorage::removeall' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'object' => 'SplObjectStorage',
      ),
      'new' => 
      array (
        0 => 'int',
        'storage' => 'SplObjectStorage',
      ),
    ),
    'splobjectstorage::removeallexcept' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'object' => 'SplObjectStorage',
      ),
      'new' => 
      array (
        0 => 'int',
        'storage' => 'SplObjectStorage',
      ),
    ),
    'splobjectstorage::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'splpriorityqueue::compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'value1' => 'mixed',
        'value2' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'priority1' => 'mixed',
        'priority2' => 'mixed',
      ),
    ),
    'splqueue::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'value' => 'mixed',
      ),
    ),
    'splqueue::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'splstack::add' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int',
        'value' => 'mixed',
      ),
    ),
    'splstack::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'newval' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'index' => 'int|null',
        'value' => 'mixed',
      ),
    ),
    'splstack::unserialize' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'serialized' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
    ),
    'spltempfileobject::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_memory=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'maxMemory=' => 'int',
      ),
    ),
    'spltempfileobject::fgetcsv' => 
    array (
      'old' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'spltempfileobject::flock' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'operation' => 'int',
        '&w_wouldblock=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'operation' => 'int',
        '&w_wouldBlock=' => 'int',
      ),
    ),
    'spltempfileobject::fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'spltempfileobject::fseek' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pos' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'spltempfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
    ),
    'spltempfileobject::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string|null',
      ),
    ),
    'spltempfileobject::getpathinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo|null',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'spltempfileobject::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplTempFileObject',
        'open_mode=' => 'string',
        'use_include_path=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'SplTempFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'spltempfileobject::seek' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'line_pos' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'line' => 'int',
      ),
    ),
    'spltempfileobject::setcsvcontrol' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'spltempfileobject::setfileclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'spltempfileobject::setinfoclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class=' => 'class-string',
      ),
    ),
    'spltempfileobject::setmaxlinelen' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'max_len' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'maxLength' => 'int',
      ),
    ),
    'spoofchecker::areconfusable' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        's1' => 'string',
        's2' => 'string',
        '&w_error=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string1' => 'string',
        'string2' => 'string',
        '&w_errorCode=' => 'int',
      ),
    ),
    'spoofchecker::issuspicious' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'string',
        '&w_error=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        '&w_errorCode=' => 'int',
      ),
    ),
    'spoofchecker::setallowedlocales' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'locale_list' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'locales' => 'string',
      ),
    ),
    'sprintf' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'format' => 'string',
        '...args=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
        '...values=' => 'float|int|string',
      ),
    ),
    'sqlite3::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'encryption_key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'encryptionKey=' => 'string',
      ),
    ),
    'sqlite3::busytimeout' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ms' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'milliseconds' => 'int',
      ),
    ),
    'sqlite3::createaggregate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'step_callback' => 'callable',
        'final_callback' => 'callable',
        'argument_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'stepCallback' => 'callable',
        'finalCallback' => 'callable',
        'argCount=' => 'int',
      ),
    ),
    'sqlite3::createfunction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'callback' => 'callable',
        'argument_count=' => 'int',
        'flags=' => 'int',
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
    'sqlite3::enableexceptions' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'enableExceptions=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'enable=' => 'bool',
      ),
    ),
    'sqlite3::escapestring' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'sqlite3::loadextension' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'shared_library' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
    ),
    'sqlite3::open' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'encryption_key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'flags=' => 'int',
        'encryptionKey=' => 'string',
      ),
    ),
    'sqlite3::openblob' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'table' => 'string',
        'column' => 'string',
        'rowid' => 'int',
        'dbname=' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'table' => 'string',
        'column' => 'string',
        'rowid' => 'int',
        'database=' => 'string',
        'flags=' => 'int',
      ),
    ),
    'sqlite3::querysingle' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|null|scalar',
        'query' => 'string',
        'entire_row=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null|scalar',
        'query' => 'string',
        'entireRow=' => 'bool',
      ),
    ),
    'sqlite3result::columnname' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'column_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'column' => 'int',
      ),
    ),
    'sqlite3result::columntype' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'column_number' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'column' => 'int',
      ),
    ),
    'sqlite3stmt::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'sqlite3' => 'sqlite3',
      ),
      'new' => 
      array (
        0 => 'void',
        'sqlite3' => 'sqlite3',
        'query' => 'string',
      ),
    ),
    'sqlite3stmt::bindparam' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'param_number' => 'int|string',
        '&param' => 'int|string',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'param' => 'int|string',
        '&var' => 'mixed',
        'type=' => 'int',
      ),
    ),
    'sqlite3stmt::bindvalue' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'param_number' => 'int|string',
        'param' => 'int|string',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'param' => 'int|string',
        'value' => 'mixed',
        'type=' => 'int',
      ),
    ),
    'sqlite3stmt::getsql' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'expanded=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'expand=' => 'bool',
      ),
    ),
    'sqrt' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'sscanf' => 
    array (
      'old' => 
      array (
        0 => 'int|list<float|int|null|string>|null',
        'str' => 'string',
        'format' => 'string',
        '&...vars=' => 'float|int|null|string',
      ),
      'new' => 
      array (
        0 => 'int|list<float|int|null|string>|null',
        'string' => 'string',
        'format' => 'string',
        '&...vars=' => 'float|int|null|string',
      ),
    ),
    'str_getcsv' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-list<null|string>',
        'string' => 'string',
        'delimiter=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'non-empty-list<null|string>',
        'string' => 'string',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
    ),
    'str_ireplace' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'search' => 'string',
        'replace' => 'string',
        'subject' => 'string',
        '&w_replace_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'search' => 'string',
        'replace' => 'string',
        'subject' => 'string',
        '&w_count=' => 'int',
      ),
    ),
    'str_pad' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'input' => 'string',
        'pad_length' => 'int',
        'pad_string=' => 'string',
        'pad_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'length' => 'int',
        'pad_string=' => 'string',
        'pad_type=' => 'int',
      ),
    ),
    'str_repeat' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'input' => 'string',
        'mult' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'times' => 'int',
      ),
    ),
    'str_replace' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'search' => 'string',
        'replace' => 'string',
        'subject' => 'string',
        '&w_replace_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'search' => 'string',
        'replace' => 'string',
        'subject' => 'string',
        '&w_count=' => 'int',
      ),
    ),
    'str_rot13' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'str_shuffle' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'str_split' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-list<string>',
        'str' => 'string',
        'split_length=' => 'int<1, max>',
      ),
      'new' => 
      array (
        0 => 'non-empty-list<string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
      ),
    ),
    'str_word_count' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>|int',
        'str' => 'string',
        'format=' => 'int',
        'charlist=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<int, string>|int',
        'string' => 'string',
        'format=' => 'int',
        'characters=' => 'null|string',
      ),
    ),
    'strcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strchr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
        'part=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
    ),
    'strcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strcoll' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strcspn' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
        'mask' => 'string',
        'start=' => 'int',
        'len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'string' => 'string',
        'characters' => 'string',
        'offset=' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'stream_context_create' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'options=' => 'array<array-key, mixed>',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'resource',
        'options=' => 'array<array-key, mixed>|null',
        'params=' => 'array<array-key, mixed>|null',
      ),
    ),
    'stream_context_get_default' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'resource',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'stream_context_get_params' => 
    array (
      'old' => 
      array (
        0 => 'array{notification: string, options: array<array-key, mixed>}',
        'stream_or_context' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array{notification: string, options: array<array-key, mixed>}',
        'context' => 'resource',
      ),
    ),
    'stream_context_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream_or_context' => 'mixed',
        'wrappername' => 'string',
        'optionname=' => 'string',
        'value=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
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
        'stream_or_context' => 'resource',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'params' => 'array<array-key, mixed>',
      ),
    ),
    'stream_copy_to_stream' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'source' => 'resource',
        'dest' => 'resource',
        'maxlen=' => 'int',
        'pos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'from' => 'resource',
        'to' => 'resource',
        'length=' => 'int|null',
        'offset=' => 'int',
      ),
    ),
    'stream_filter_append' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'stream' => 'resource',
        'filtername' => 'string',
        'read_write=' => 'int',
        'filterparams=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'stream' => 'resource',
        'filter_name' => 'string',
        'mode=' => 'int',
        'params=' => 'mixed',
      ),
    ),
    'stream_filter_prepend' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'stream' => 'resource',
        'filtername' => 'string',
        'read_write=' => 'int',
        'filterparams=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'stream' => 'resource',
        'filter_name' => 'string',
        'mode=' => 'int',
        'params=' => 'mixed',
      ),
    ),
    'stream_filter_register' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filtername' => 'string',
        'classname' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filter_name' => 'string',
        'class' => 'string',
      ),
    ),
    'stream_get_contents' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'source' => 'resource',
        'maxlen=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int|null',
        'offset=' => 'int',
      ),
    ),
    'stream_get_line' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'maxlen' => 'int',
        'ending=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length' => 'int',
        'ending=' => 'string',
      ),
    ),
    'stream_get_meta_data' => 
    array (
      'old' => 
      array (
        0 => 'array{blocked: bool, crypto?: array{cipher_bits: int, cipher_name: string, cipher_version: string, protocol: string}, eof: bool, mediatype: string, mode: string, seekable: bool, stream_type: string, timed_out: bool, unread_bytes: int, uri: string, wrapper_data: mixed, wrapper_type: string}',
        'fp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array{blocked: bool, crypto?: array{cipher_bits: int, cipher_name: string, cipher_version: string, protocol: string}, eof: bool, mediatype: string, mode: string, seekable: bool, stream_type: string, timed_out: bool, unread_bytes: int, uri: string, wrapper_data: mixed, wrapper_type: string}',
        'stream' => 'resource',
      ),
    ),
    'stream_register_wrapper' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'protocol' => 'string',
        'classname' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'protocol' => 'string',
        'class' => 'string',
        'flags=' => 'int',
      ),
    ),
    'stream_select' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        '&read_streams' => 'array<array-key, resource>|null',
        '&write_streams' => 'array<array-key, resource>|null',
        '&except_streams' => 'array<array-key, resource>|null',
        'tv_sec' => 'int|null',
        'tv_usec=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        '&read' => 'array<array-key, resource>|null',
        '&write' => 'array<array-key, resource>|null',
        '&except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int',
      ),
    ),
    'stream_set_blocking' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'socket' => 'resource',
        'mode' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'enable' => 'bool',
      ),
    ),
    'stream_set_chunk_size' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fp' => 'resource',
        'chunk_size' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'size' => 'int',
      ),
    ),
    'stream_set_read_buffer' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
        'buffer' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'size' => 'int',
      ),
    ),
    'stream_set_write_buffer' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fp' => 'resource',
        'buffer' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'stream' => 'resource',
        'size' => 'int',
      ),
    ),
    'stream_socket_accept' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'serverstream' => 'resource',
        'timeout=' => 'float',
        '&w_peername=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'socket' => 'resource',
        'timeout=' => 'float|null',
        '&w_peer_name=' => 'string',
      ),
    ),
    'stream_socket_client' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'remoteaddress' => 'string',
        '&w_errcode=' => 'int',
        '&w_errstring=' => 'string',
        'timeout=' => 'float',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'address' => 'string',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
        'timeout=' => 'float|null',
        'flags=' => 'int',
        'context=' => 'null|resource',
      ),
    ),
    'stream_socket_enable_crypto' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'stream' => 'resource',
        'enable' => 'bool',
        'cryptokind=' => 'int|null',
        'sessionstream=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'stream' => 'resource',
        'enable' => 'bool',
        'crypto_method=' => 'int|null',
        'session_stream=' => 'null|resource',
      ),
    ),
    'stream_socket_get_name' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'want_peer' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'socket' => 'resource',
        'remote' => 'bool',
      ),
    ),
    'stream_socket_recvfrom' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'amount' => 'int',
        'flags=' => 'int',
        '&w_remote_addr=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'socket' => 'resource',
        'length' => 'int',
        'flags=' => 'int',
        '&w_address=' => 'string',
      ),
    ),
    'stream_socket_sendto' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
        'flags=' => 'int',
        'target_addr=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'socket' => 'resource',
        'data' => 'string',
        'flags=' => 'int',
        'address=' => 'string',
      ),
    ),
    'stream_socket_server' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'localaddress' => 'string',
        '&w_errcode=' => 'int',
        '&w_errstring=' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'address' => 'string',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'stream_socket_shutdown' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'how' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'mode' => 'int',
      ),
    ),
    'stream_wrapper_register' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'protocol' => 'string',
        'classname' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'protocol' => 'string',
        'class' => 'string',
        'flags=' => 'int',
      ),
    ),
    'strftime' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        'timestamp=' => 'int|null',
      ),
    ),
    'strip_tags' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'allowable_tags=' => 'list<non-empty-string>|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'allowed_tags=' => 'list<non-empty-string>|null|string',
      ),
    ),
    'stripcslashes' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'stripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'int|string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
    ),
    'stripslashes' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'stristr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
        'part=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
    ),
    'strlen' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'string' => 'string',
      ),
    ),
    'strnatcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        's1' => 'string',
        's2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strnatcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        's1' => 'string',
        's2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strncasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
        'len' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
    ),
    'strncmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str1' => 'string',
        'str2' => 'string',
        'len' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
    ),
    'strpbrk' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'char_list' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'characters' => 'string',
      ),
    ),
    'strpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'int|string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
    ),
    'strrchr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
      ),
    ),
    'strrev' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'strripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'int|string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
    ),
    'strrpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'int|string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
    ),
    'strspn' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
        'mask' => 'string',
        'start=' => 'int',
        'len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'string' => 'string',
        'characters' => 'string',
        'offset=' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'strstr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
        'part=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
    ),
    'strtok' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'str' => 'string',
        'token=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'string' => 'string',
        'token=' => 'null|string',
      ),
    ),
    'strtolower' => 
    array (
      'old' => 
      array (
        0 => 'lowercase-string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'lowercase-string',
        'string' => 'string',
      ),
    ),
    'strtotime' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'time' => 'string',
        'now=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'datetime' => 'string',
        'baseTimestamp=' => 'int|null',
      ),
    ),
    'strtoupper' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'strtr' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'from' => 'string',
        'to=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'from' => 'string',
        'to=' => 'null|string',
      ),
    ),
    'strval' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'var' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'string',
        'value' => 'mixed',
      ),
    ),
    'substr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'str' => 'string',
        'start' => 'int',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'substr_compare' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'main_str' => 'string',
        'str' => 'string',
        'offset' => 'int',
        'length=' => 'int',
        'case_sensitivity=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
        'case_insensitive=' => 'bool',
      ),
    ),
    'substr_count' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'length=' => 'int|null',
      ),
    ),
    'substr_replace' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'replace' => 'array<array-key, string>|string',
        'start' => 'array<array-key, int>|int',
        'length=' => 'array<array-key, int>|int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'replace' => 'array<array-key, string>|string',
        'offset' => 'array<array-key, int>|int',
        'length=' => 'array<array-key, int>|int|null',
      ),
    ),
    'substr_replace\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>',
        'string' => 'array<array-key, string>',
        'replace' => 'array<array-key, string>|string',
        'offset' => 'array<array-key, int>|int',
        'length=' => 'array<array-key, int>|int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>',
        'string' => 'array<array-key, string>',
        'replace' => 'array<array-key, string>|string',
        'offset' => 'array<array-key, int>|int',
        'length=' => 'array<array-key, int>|int|null',
      ),
    ),
    'swoole\\atomic::cmpset' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'cmp_value' => 'int',
        'new_value' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'cmp_value' => 'int',
        'new_value' => 'int',
      ),
    ),
    'swoole\\atomic::set' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'value' => 'int',
      ),
    ),
    'swoole\\client::connect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
        'sock_flag=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'sock_flag=' => 'int',
      ),
    ),
    'swoole\\client::recv' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'size=' => 'string',
        'flag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'size=' => 'int',
        'flag=' => 'int',
      ),
    ),
    'swoole\\client::send' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'data' => 'string',
        'flag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'data' => 'string',
        'flag=' => 'int',
      ),
    ),
    'swoole\\client::sendfile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
    ),
    'swoole\\client::set' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'settings' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'settings' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\coroutine::create' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'func' => 'callable',
        '...params=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'func' => 'callable',
        '...param=' => 'mixed',
      ),
    ),
    'swoole\\coroutine::getuid' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'swoole\\coroutine::resume' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'cid' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'cid' => 'int',
      ),
    ),
    'swoole\\coroutine::suspend' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\coroutine\\client::close' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\coroutine\\client::connect' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'host' => 'mixed',
        'port=' => 'mixed',
        'timeout=' => 'mixed',
        'sock_flag=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'sock_flag=' => 'int',
      ),
    ),
    'swoole\\coroutine\\client::getpeername' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
      ),
    ),
    'swoole\\coroutine\\client::getsockname' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
      ),
    ),
    'swoole\\coroutine\\client::isconnected' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\coroutine\\client::recv' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'timeout=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timeout=' => 'float',
      ),
    ),
    'swoole\\coroutine\\client::send' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'data' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'timeout=' => 'float',
      ),
    ),
    'swoole\\coroutine\\client::sendfile' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'filename' => 'mixed',
        'offset=' => 'mixed',
        'length=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
    ),
    'swoole\\coroutine\\client::sendto' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'address' => 'mixed',
        'port' => 'mixed',
        'data' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'address' => 'string',
        'port' => 'int',
        'data' => 'string',
      ),
    ),
    'swoole\\coroutine\\client::set' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'settings' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'settings' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\coroutine\\http\\client::addfile' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'path' => 'mixed',
        'name' => 'mixed',
        'type=' => 'mixed',
        'filename=' => 'mixed',
        'offset=' => 'mixed',
        'length=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
        'name' => 'string',
        'type=' => 'null|string',
        'filename=' => 'null|string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
    ),
    'swoole\\coroutine\\http\\client::close' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\coroutine\\http\\client::execute' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'path' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
      ),
    ),
    'swoole\\coroutine\\http\\client::get' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'path' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
      ),
    ),
    'swoole\\coroutine\\http\\client::getdefer' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\coroutine\\http\\client::post' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'path' => 'mixed',
        'data' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'path' => 'string',
        'data' => 'mixed',
      ),
    ),
    'swoole\\coroutine\\http\\client::recv' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'timeout=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'Swoole\\WebSocket\\Frame|bool',
        'timeout=' => 'float',
      ),
    ),
    'swoole\\coroutine\\http\\client::set' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'settings' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'settings' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\coroutine\\http\\client::setcookies' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'cookies' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'cookies' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\coroutine\\http\\client::setdata' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'data' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'array<array-key, mixed>|string',
      ),
    ),
    'swoole\\coroutine\\http\\client::setdefer' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'defer=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'defer=' => 'bool',
      ),
    ),
    'swoole\\coroutine\\http\\client::setheaders' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'headers' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'headers' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\coroutine\\http\\client::setmethod' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'method' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'method' => 'string',
      ),
    ),
    'swoole\\event::add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'read_callback' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'fd' => 'int',
        'read_callback=' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'int',
      ),
    ),
    'swoole\\event::defer' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable',
      ),
    ),
    'swoole\\event::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'read_callback=' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'read_callback=' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'int',
      ),
    ),
    'swoole\\event::write' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'fd' => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'string',
        'data' => 'string',
      ),
    ),
    'swoole\\http\\response::cookie' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'string',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'string',
        'httponly=' => 'string',
        'samesite=' => 'mixed',
        'priority=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
      ),
    ),
    'swoole\\http\\response::end' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'content=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'content=' => 'null|string',
      ),
    ),
    'swoole\\http\\response::header' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
        'format=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'string',
        'format=' => 'bool',
      ),
    ),
    'swoole\\http\\response::initheader' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\http\\response::rawcookie' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'string',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'string',
        'httponly=' => 'string',
        'samesite=' => 'mixed',
        'priority=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
      ),
    ),
    'swoole\\http\\response::sendfile' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
    ),
    'swoole\\http\\response::status' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'http_code' => 'string',
        'reason=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'http_code' => 'int',
        'reason=' => 'string',
      ),
    ),
    'swoole\\http\\response::write' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'content' => 'string',
      ),
    ),
    'swoole\\http\\server::on' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
    ),
    'swoole\\http\\server::start' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\lock::lock' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\lock::lock_read' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\lock::trylock' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\lock::trylock_read' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\lock::unlock' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\process::alarm' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'usec' => 'int',
        'type=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'usec' => 'int',
        'type=' => 'int',
      ),
    ),
    'swoole\\process::close' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
        'which=' => 'int',
      ),
    ),
    'swoole\\process::daemon' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'nochdir=' => 'bool',
        'noclose=' => 'bool',
        'pipes=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'nochdir=' => 'bool',
        'noclose=' => 'bool',
        'pipes=' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\process::exec' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'exec_file' => 'string',
        'args' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exec_file' => 'string',
        'args' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\process::exit' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'exit_code=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'exit_code=' => 'int',
      ),
    ),
    'swoole\\process::freequeue' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\process::kill' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'pid' => 'int',
        'signal_no=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pid' => 'int',
        'signal_no=' => 'int',
      ),
    ),
    'swoole\\process::name' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'process_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'process_name' => 'string',
      ),
    ),
    'swoole\\process::pop' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'size=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'size=' => 'int',
      ),
    ),
    'swoole\\process::signal' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'signal_no' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'signal_no' => 'int',
        'callback=' => 'callable|null',
      ),
    ),
    'swoole\\process::start' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool|int',
      ),
    ),
    'swoole\\process::usequeue' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key=' => 'int',
        'mode=' => 'int',
        'capacity=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key=' => 'int',
        'mode=' => 'int',
        'capacity=' => 'int',
      ),
    ),
    'swoole\\redis\\server::format' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'type' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'type' => 'int',
        'value=' => 'string',
      ),
    ),
    'swoole\\redis\\server::sethandler' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'command' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'command' => 'string',
        'callback' => 'callable',
      ),
    ),
    'swoole\\redis\\server::start' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\server::addlistener' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'host' => 'string',
        'port' => 'int',
        'sock_type' => 'string',
      ),
      'new' => 
      array (
        0 => 'Swoole\\Server\\Port|false',
        'host' => 'string',
        'port' => 'int',
        'sock_type' => 'int',
      ),
    ),
    'swoole\\server::addprocess' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'process' => 'swoole_process',
      ),
      'new' => 
      array (
        0 => 'int',
        'process' => 'Swoole\\Process',
      ),
    ),
    'swoole\\server::connection_info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'fd' => 'int',
        'reactor_id=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'fd' => 'int',
        'reactor_id=' => 'int',
        'ignoreError=' => 'bool',
      ),
    ),
    'swoole\\server::connection_list' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'start_fd' => 'int',
        'find_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'start_fd=' => 'int',
        'find_count=' => 'int',
      ),
    ),
    'swoole\\server::finish' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
      ),
    ),
    'swoole\\server::getclientinfo' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'fd' => 'int',
        'reactor_id=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'fd' => 'int',
        'reactor_id=' => 'int',
        'ignoreError=' => 'bool',
      ),
    ),
    'swoole\\server::getclientlist' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'start_fd' => 'int',
        'find_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'start_fd=' => 'int',
        'find_count=' => 'int',
      ),
    ),
    'swoole\\server::heartbeat' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'reactor_id' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ifCloseConnection=' => 'bool',
      ),
    ),
    'swoole\\server::listen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port' => 'int',
        'sock_type' => 'string',
      ),
      'new' => 
      array (
        0 => 'Swoole\\Server\\Port|false',
        'host' => 'string',
        'port' => 'int',
        'sock_type' => 'int',
      ),
    ),
    'swoole\\server::on' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
    ),
    'swoole\\server::pause' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'fd' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
      ),
    ),
    'swoole\\server::protect' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'fd' => 'int',
        'is_protected=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'is_protected=' => 'bool',
      ),
    ),
    'swoole\\server::reload' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'only_reload_taskworker=' => 'bool',
      ),
    ),
    'swoole\\server::resume' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'fd' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
      ),
    ),
    'swoole\\server::send' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'send_data' => 'string',
        'server_socket=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'send_data' => 'string',
        'serverSocket=' => 'int',
      ),
    ),
    'swoole\\server::sendfile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'conn_fd' => 'int',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'conn_fd' => 'int',
        'filename' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
      ),
    ),
    'swoole\\server::sendmessage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'int',
        'dst_worker_id' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'message' => 'int',
        'dst_worker_id' => 'int',
      ),
    ),
    'swoole\\server::sendto' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ip' => 'string',
        'port' => 'int',
        'send_data' => 'string',
        'server_socket=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ip' => 'string',
        'port' => 'int',
        'send_data' => 'string',
        'server_socket=' => 'int',
      ),
    ),
    'swoole\\server::set' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'settings' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'settings' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\server::shutdown' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\server::start' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\server::stop' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'worker_id=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'workerId=' => 'int',
        'waitEvent=' => 'bool',
      ),
    ),
    'swoole\\server::task' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'task_worker_index=' => 'int',
        'finish_callback=' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'taskWorkerIndex=' => 'int',
        'finishCallback=' => 'callable|null',
      ),
    ),
    'swoole\\server::taskwait' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'string',
        'timeout=' => 'float',
        'task_worker_index=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
        'timeout=' => 'float',
        'taskWorkerIndex=' => 'int',
      ),
    ),
    'swoole\\server::taskwaitmulti' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'tasks' => 'array<array-key, mixed>',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'tasks' => 'array<array-key, mixed>',
        'timeout=' => 'float',
      ),
    ),
    'swoole\\server\\port::on' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
    ),
    'swoole\\table::column' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'name' => 'string',
        'type' => 'string',
        'size=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'type' => 'int',
        'size=' => 'int',
      ),
    ),
    'swoole\\table::create' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\table::decr' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'key' => 'string',
        'column' => 'string',
        'decrby=' => 'int',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'key' => 'string',
        'column' => 'string',
        'incrby=' => 'int',
      ),
    ),
    'swoole\\table::del' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
    ),
    'swoole\\table::destroy' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'swoole\\table::get' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'field=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'field=' => 'null|string',
      ),
    ),
    'swoole\\table::incr' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'column' => 'string',
        'incrby=' => 'int',
      ),
      'new' => 
      array (
        0 => 'float|int',
        'key' => 'string',
        'column' => 'string',
        'incrby=' => 'int',
      ),
    ),
    'swoole\\table::set' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'array<array-key, mixed>',
      ),
    ),
    'swoole\\timer::after' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'ms' => 'int',
        'callback' => 'callable',
        '...params=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ms' => 'int',
        'callback' => 'callable',
        '...params=' => 'mixed',
      ),
    ),
    'swoole\\timer::clear' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'timer_id' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timer_id' => 'int',
      ),
    ),
    'swoole\\timer::tick' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'ms' => 'int',
        'callback' => 'callable',
        '...params=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ms' => 'int',
        'callback' => 'callable',
        '...params=' => 'string',
      ),
    ),
    'swoole\\websocket\\server::on' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'event_name' => 'string',
        'callback' => 'callable',
      ),
    ),
    'swoole\\websocket\\server::pack' => 
    array (
      'old' => 
      array (
        0 => 'binary',
        'data' => 'string',
        'opcode=' => 'string',
        'flags=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'data' => 'string',
        'opcode=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'swoole\\websocket\\server::push' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'fd' => 'string',
        'data' => 'string',
        'opcode=' => 'string',
        'flags=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'fd' => 'int',
        'data' => 'string',
        'opcode=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'swoole\\websocket\\server::unpack' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'binary',
      ),
      'new' => 
      array (
        0 => 'Swoole\\WebSocket\\Frame',
        'data' => 'string',
      ),
    ),
    'swoole_event_add' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'fd' => 'int',
        'read_callback' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'fd' => 'int',
        'read_callback=' => 'callable|null',
        'write_callback=' => 'callable|null',
        'events=' => 'int',
      ),
    ),
    'swoole_set_process_name' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'process_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'process_name' => 'string',
      ),
    ),
    'system' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'command' => 'string',
        '&w_return_value=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
    ),
    'tan' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'tanh' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'number' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'num' => 'float',
      ),
    ),
    'tempnam' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'dir' => 'string',
        'prefix' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'directory' => 'string',
        'prefix' => 'string',
      ),
    ),
    'tidy::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename=' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
        'useIncludePath=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename=' => 'null|string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
        'useIncludePath=' => 'bool',
      ),
    ),
    'tidy::parsefile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
        'useIncludePath=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
        'useIncludePath=' => 'bool',
      ),
    ),
    'tidy::parsestring' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'tidy::repairfile' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
        'useIncludePath=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
        'useIncludePath=' => 'bool',
      ),
    ),
    'tidy::repairstring' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'tidy_parse_file' => 
    array (
      'old' => 
      array (
        0 => 'tidy',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
        'useIncludePath=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'tidy',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
        'useIncludePath=' => 'bool',
      ),
    ),
    'tidy_parse_string' => 
    array (
      'old' => 
      array (
        0 => 'tidy',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'tidy',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'tidy_repair_file' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
        'useIncludePath=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'filename' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
        'useIncludePath=' => 'bool',
      ),
    ),
    'tidy_repair_string' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'config=' => 'array<array-key, mixed>|null|string',
        'encoding=' => 'null|string',
      ),
    ),
    'timezone_identifiers_list' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'what=' => 'int',
        'country=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
    ),
    'timezone_name_from_abbr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'abbr' => 'string',
        'gmtoffset=' => 'int',
        'isdst=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'abbr' => 'string',
        'utcOffset=' => 'int',
        'isDST=' => 'int',
      ),
    ),
    'timezone_offset_get' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'object' => 'DateTimeZone',
        'datetime' => 'DateTimeInterface',
      ),
      'new' => 
      array (
        0 => 'int',
        'object' => 'DateTimeZone',
        'datetime' => 'DateTimeInterface',
      ),
    ),
    'timezone_transitions_get' => 
    array (
      'old' => 
      array (
        0 => 'false|list<array{abbr: string, isdst: bool, offset: int, time: string, ts: int}>',
        'object' => 'DateTimeZone',
        'timestamp_begin=' => 'int',
        'timestamp_end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|list<array{abbr: string, isdst: bool, offset: int, time: string, ts: int}>',
        'object' => 'DateTimeZone',
        'timestampBegin=' => 'int',
        'timestampEnd=' => 'int',
      ),
    ),
    'token_get_all' => 
    array (
      'old' => 
      array (
        0 => 'list<array{0: int, 1: string, 2: int}|string>',
        'source' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'list<array{0: int, 1: string, 2: int}|string>',
        'code' => 'string',
        'flags=' => 'int',
      ),
    ),
    'token_name' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'token' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'id' => 'int',
      ),
    ),
    'touch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'time=' => 'int',
        'atime=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mtime=' => 'int|null',
        'atime=' => 'int|null',
      ),
    ),
    'trait_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'traitname' => 'string',
        'autoload=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'trait' => 'string',
        'autoload=' => 'bool',
      ),
    ),
    'transliterator::transliterate' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'subject' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
    ),
    'transliterator_create_inverse' => 
    array (
      'old' => 
      array (
        0 => 'Transliterator|null',
        'orig_trans' => 'Transliterator',
      ),
      'new' => 
      array (
        0 => 'Transliterator|null',
        'transliterator' => 'Transliterator',
      ),
    ),
    'transliterator_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'trans' => 'Transliterator',
      ),
      'new' => 
      array (
        0 => 'int',
        'transliterator' => 'Transliterator',
      ),
    ),
    'transliterator_get_error_message' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'trans' => 'Transliterator',
      ),
      'new' => 
      array (
        0 => 'string',
        'transliterator' => 'Transliterator',
      ),
    ),
    'transliterator_transliterate' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'trans' => 'Transliterator|string',
        'subject' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'transliterator' => 'Transliterator|string',
        'string' => 'string',
        'start=' => 'int',
        'end=' => 'int',
      ),
    ),
    'trigger_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_type=' => '256|512|1024|16384',
      ),
      'new' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => '256|512|1024|16384',
      ),
    ),
    'trim' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'character_mask=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'characters=' => 'string',
      ),
    ),
    'uasort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'ucfirst' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'uconverter::reasontext' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'reason=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'reason' => 'int',
      ),
    ),
    'ucwords' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delimiters=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'separators=' => 'string',
      ),
    ),
    'uksort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'umask' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mask=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mask=' => 'int|null',
      ),
    ),
    'unixtojd' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'timestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'timestamp=' => 'int|null',
      ),
    ),
    'unpack' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'format' => 'string',
        'input' => 'string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'format' => 'string',
        'string' => 'string',
        'offset=' => 'int',
      ),
    ),
    'unregister_tick_function' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'function_name' => 'callable',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback' => 'callable',
      ),
    ),
    'unserialize' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'variable_representation' => 'string',
        'allowed_classes=' => 'array{allowed_classes?: array<array-key, class-string>|bool}',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'options=' => 'array{allowed_classes?: array<array-key, class-string>|bool}',
      ),
    ),
    'urldecode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'urlencode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'use_soap_error_handler' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'handler=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'enable=' => 'bool',
      ),
    ),
    'user_error' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'error_level=' => 'int',
      ),
    ),
    'usleep' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'micro_seconds' => 'int<0, max>',
      ),
      'new' => 
      array (
        0 => 'void',
        'microseconds' => 'int<0, max>',
      ),
    ),
    'usort' => 
    array (
      'old' => 
      array (
        0 => 'true',
        '&arg' => 'array<array-key, mixed>',
        'cmp_function' => 'callable(mixed, mixed):int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'callable(mixed, mixed):int',
      ),
    ),
    'utf8_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'utf8_encode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
      ),
    ),
    'var_dump' => 
    array (
      'old' => 
      array (
        0 => 'void',
        '...vars' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'value' => 'mixed',
        '...values=' => 'mixed',
      ),
    ),
    'var_export' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'var' => 'mixed',
        'return=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'value' => 'mixed',
        'return=' => 'bool',
      ),
    ),
    'version_compare' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ver1' => 'string',
        'ver2' => 'string',
        'oper' => '\'!=\'|\'<\'|\'<=\'|\'<>\'|\'=\'|\'==\'|\'>\'|\'>=\'|\'eq\'|\'ge\'|\'gt\'|\'le\'|\'lt\'|\'ne\'',
      ),
      'new' => 
      array (
        0 => 'bool',
        'version1' => 'string',
        'version2' => 'string',
        'operator' => '\'!=\'|\'<\'|\'<=\'|\'<>\'|\'=\'|\'==\'|\'>\'|\'>=\'|\'eq\'|\'ge\'|\'gt\'|\'le\'|\'lt\'|\'ne\'|null',
      ),
    ),
    'vfprintf' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'stream' => 'resource',
        'format' => 'string',
        'args' => 'array<array-key, float|int|string>',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'stream' => 'resource',
        'format' => 'string',
        'values' => 'array<array-key, float|int|string>',
      ),
    ),
    'vprintf' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'format' => 'string',
        'args' => 'array<array-key, float|int|string>',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'format' => 'string',
        'values' => 'array<array-key, float|int|string>',
      ),
    ),
    'vsprintf' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'format' => 'string',
        'args' => 'array<array-key, float|int|string>',
      ),
      'new' => 
      array (
        0 => 'string',
        'format' => 'string',
        'values' => 'array<array-key, float|int|string>',
      ),
    ),
    'wordwrap' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'width=' => 'int',
        'break=' => 'string',
        'cut=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'width=' => 'int',
        'break=' => 'string',
        'cut_long_words=' => 'bool',
      ),
    ),
    'xml_error_string' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'code' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'error_code' => 'int',
      ),
    ),
    'xml_get_current_byte_index' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'parser' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
      ),
    ),
    'xml_get_current_column_number' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'parser' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
      ),
    ),
    'xml_get_current_line_number' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'parser' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
      ),
    ),
    'xml_get_error_code' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'parser' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
      ),
    ),
    'xml_parse' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'parser' => 'resource',
        'data' => 'string',
        'isfinal=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
        'data' => 'string',
        'is_final=' => 'bool',
      ),
    ),
    'xml_parse_into_struct' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'parser' => 'resource',
        'data' => 'string',
        '&w_values' => 'array<array-key, mixed>',
        '&w_index=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'parser' => 'XMLParser',
        'data' => 'string',
        '&w_values' => 'array<array-key, mixed>',
        '&w_index=' => 'array<array-key, mixed>',
      ),
    ),
    'xml_parser_create' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'XMLParser',
        'encoding=' => 'null|string',
      ),
    ),
    'xml_parser_create_ns' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'encoding=' => 'string',
        'sep=' => 'string',
      ),
      'new' => 
      array (
        0 => 'XMLParser',
        'encoding=' => 'null|string',
        'separator=' => 'string',
      ),
    ),
    'xml_parser_free' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'parser' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'parser' => 'XMLParser',
      ),
    ),
    'xml_parser_get_option' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'parser' => 'resource',
        'option' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'parser' => 'XMLParser',
        'option' => 'int',
      ),
    ),
    'xml_parser_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'parser' => 'resource',
        'option' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'parser' => 'XMLParser',
        'option' => 'int',
        'value' => 'mixed',
      ),
    ),
    'xml_set_character_data_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_default_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_element_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'shdl' => 'callable',
        'ehdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable',
        'end_handler' => 'callable',
      ),
    ),
    'xml_set_end_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_external_entity_ref_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_notation_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_object' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'obj' => 'object',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'object' => 'object',
      ),
    ),
    'xml_set_processing_instruction_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_start_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xml_set_unparsed_entity_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'hdl' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xmlreader::expand' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'basenode=' => 'DOMNode|null',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'baseNode=' => 'DOMNode|null',
      ),
    ),
    'xmlreader::getattributens' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'name' => 'string',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'name' => 'string',
        'namespace' => 'string',
      ),
    ),
    'xmlreader::movetoattributens' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'namespaceURI' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'namespace' => 'string',
      ),
    ),
    'xmlreader::next' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'localname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name=' => 'null|string',
      ),
    ),
    'xmlreader::open' => 
    array (
      'old' => 
      array (
        0 => 'XmlReader|bool',
        'URI' => 'string',
        'encoding=' => 'null|string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'XmlReader|bool',
        'uri' => 'string',
        'encoding=' => 'null|string',
        'flags=' => 'int',
      ),
    ),
    'xmlreader::xml' => 
    array (
      'old' => 
      array (
        0 => 'XMLReader|bool',
        'source' => 'string',
        'encoding=' => 'null|string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'XMLReader|bool',
        'source' => 'string',
        'encoding=' => 'null|string',
        'flags=' => 'int',
      ),
    ),
    'xmlwriter::flush' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'empty=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'empty=' => 'bool',
      ),
    ),
    'xmlwriter::setindent' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'indent' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'enable' => 'bool',
      ),
    ),
    'xmlwriter::setindentstring' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'indentString' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'indentation' => 'string',
      ),
    ),
    'xmlwriter::startattributens' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'name' => 'string',
        'uri' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
      ),
    ),
    'xmlwriter::startdtdentity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'isparam' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'isParam' => 'bool',
      ),
    ),
    'xmlwriter::startelementns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'uri' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
      ),
    ),
    'xmlwriter::writeattributens' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'name' => 'string',
        'uri' => 'null|string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
        'value' => 'string',
      ),
    ),
    'xmlwriter::writedtd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'subset=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'content=' => 'null|string',
      ),
    ),
    'xmlwriter::writedtdentity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'content' => 'string',
        'isParam=' => 'bool',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'notationData=' => 'null|string',
      ),
    ),
    'xmlwriter::writeelementns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'uri' => 'null|string',
        'content=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
        'content=' => 'null|string',
      ),
    ),
    'xmlwriter_end_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_cdata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_comment' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_document' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_dtd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_dtd_attlist' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_dtd_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_dtd_entity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_end_pi' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_flush' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'xmlwriter' => 'resource',
        'empty=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'writer' => 'XMLWriter',
        'empty=' => 'bool',
      ),
    ),
    'xmlwriter_full_end_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_open_memory' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
      ),
      'new' => 
      array (
        0 => 'XMLWriter|false',
      ),
    ),
    'xmlwriter_open_uri' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'uri' => 'string',
      ),
      'new' => 
      array (
        0 => 'XMLWriter|false',
        'uri' => 'string',
      ),
    ),
    'xmlwriter_output_memory' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'xmlwriter' => 'resource',
        'flush=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'writer' => 'XMLWriter',
        'flush=' => 'bool',
      ),
    ),
    'xmlwriter_set_indent' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'indent' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'enable' => 'bool',
      ),
    ),
    'xmlwriter_set_indent_string' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'indentString' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'indentation' => 'string',
      ),
    ),
    'xmlwriter_start_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
      ),
    ),
    'xmlwriter_start_attribute_ns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'prefix' => 'string',
        'name' => 'string',
        'uri' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
      ),
    ),
    'xmlwriter_start_cdata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_start_comment' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
      ),
    ),
    'xmlwriter_start_document' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'version=' => 'null|string',
        'encoding=' => 'null|string',
        'standalone=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'version=' => 'null|string',
        'encoding=' => 'null|string',
        'standalone=' => 'null|string',
      ),
    ),
    'xmlwriter_start_dtd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'qualifiedName' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'qualifiedName' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
      ),
    ),
    'xmlwriter_start_dtd_attlist' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
      ),
    ),
    'xmlwriter_start_dtd_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'qualifiedName' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'qualifiedName' => 'string',
      ),
    ),
    'xmlwriter_start_dtd_entity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'isparam' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'isParam' => 'bool',
      ),
    ),
    'xmlwriter_start_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
      ),
    ),
    'xmlwriter_start_element_ns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'prefix' => 'null|string',
        'name' => 'string',
        'uri' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
      ),
    ),
    'xmlwriter_start_pi' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'target' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'target' => 'string',
      ),
    ),
    'xmlwriter_text' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'value' => 'string',
      ),
    ),
    'xmlwriter_write_attribute_ns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'prefix' => 'string',
        'name' => 'string',
        'uri' => 'null|string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
        'value' => 'string',
      ),
    ),
    'xmlwriter_write_cdata' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_comment' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_dtd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'subset=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'content=' => 'null|string',
      ),
    ),
    'xmlwriter_write_dtd_attlist' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_dtd_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_dtd_entity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'content' => 'string',
      ),
      'new' => 
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
    ),
    'xmlwriter_write_element' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'name' => 'string',
        'content=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'name' => 'string',
        'content=' => 'null|string',
      ),
    ),
    'xmlwriter_write_element_ns' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'prefix' => 'null|string',
        'name' => 'string',
        'uri' => 'string',
        'content=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
        'content=' => 'null|string',
      ),
    ),
    'xmlwriter_write_pi' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'target' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'target' => 'string',
        'content' => 'string',
      ),
    ),
    'xmlwriter_write_raw' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'xmlwriter' => 'resource',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'content' => 'string',
      ),
    ),
    'zip_entry_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'zip_ent' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'zip_entry' => 'resource',
      ),
    ),
    'ziparchive::addemptydir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dirname' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dirname' => 'string',
        'flags=' => 'int',
      ),
    ),
    'ziparchive::addfile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filepath' => 'string',
        'entryname=' => 'string',
        'start=' => 'int',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filepath' => 'string',
        'entryname=' => 'string',
        'start=' => 'int',
        'length=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'ziparchive::addfromstring' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'content' => 'string',
        'flags=' => 'int',
      ),
    ),
    'ziparchive::getfromname' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'entryname' => 'string',
        'len=' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'name' => 'string',
        'len=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'ziparchive::getstatusstring' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'ziparchive::getstream' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'entryname' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'name' => 'string',
      ),
    ),
    'ziparchive::locatename' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'name' => 'string',
        'flags=' => 'int',
      ),
    ),
    'ziparchive::setencryptionindex' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'int',
        'method' => 'int',
        'password=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index' => 'int',
        'method' => 'int',
        'password=' => 'null|string',
      ),
    ),
    'ziparchive::setencryptionname' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'method' => 'int',
        'password=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'method' => 'int',
        'password=' => 'null|string',
      ),
    ),
    'ziparchive::statname' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'filename' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'name' => 'string',
        'flags=' => 'int',
      ),
    ),
    'zlib_decode' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_decoded_len=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'max_length=' => 'int',
      ),
    ),
  ),
  'removed' => 
  array (
    'argumentcounterror::__clone' => 
    array (
      0 => 'void',
    ),
    'arithmeticerror::__clone' => 
    array (
      0 => 'void',
    ),
    'badfunctioncallexception::__clone' => 
    array (
      0 => 'void',
    ),
    'badmethodcallexception::__clone' => 
    array (
      0 => 'void',
    ),
    'closedgeneratorexception::__clone' => 
    array (
      0 => 'void',
    ),
    'convert_cyr_string' => 
    array (
      0 => 'string',
      'str' => 'string',
      'from' => 'string',
      'to' => 'string',
    ),
    'create_function' => 
    array (
      0 => 'string',
      'args' => 'string',
      'code' => 'string',
    ),
    'domainexception::__clone' => 
    array (
      0 => 'void',
    ),
    'each' => 
    array (
      0 => 'array{0: int|string, 1: mixed, key: int|string, value: mixed}',
      '&r_arr' => 'array<array-key, mixed>',
    ),
    'errorexception::__clone' => 
    array (
      0 => 'void',
    ),
    'eventbufferevent::sslfilter' => 
    array (
      0 => 'EventBufferEvent',
      'unused' => 'EventBase',
      'underlying' => 'EventBufferEvent',
      'ctx' => 'EventSslContext',
      'state' => 'int',
      'options=' => 'int',
    ),
    'ezmlm_hash' => 
    array (
      0 => 'int',
      'addr' => 'string',
    ),
    'fgetss' => 
    array (
      0 => 'false|string',
      'fp' => 'resource',
      'length=' => 'int',
      'allowable_tags=' => 'string',
    ),
    'get_magic_quotes_gpc' => 
    array (
      0 => 'false|int',
    ),
    'get_magic_quotes_runtime' => 
    array (
      0 => 'false|int',
    ),
    'gmp_random' => 
    array (
      0 => 'GMP',
      'limiter=' => 'int',
    ),
    'gzgetss' => 
    array (
      0 => 'false|string',
      'fp' => 'resource',
      'length=' => 'int',
      'allowable_tags=' => 'string',
    ),
    'hebrevc' => 
    array (
      0 => 'string',
      'str' => 'string',
      'max_chars_per_line=' => 'int',
    ),
    'image2wbmp' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'filename=' => 'null|string',
      'foreground=' => 'int',
    ),
    'intlexception::__clone' => 
    array (
      0 => 'void',
    ),
    'invalidargumentexception::__clone' => 
    array (
      0 => 'void',
    ),
    'is_real' => 
    array (
      0 => 'bool',
      'var' => 'mixed',
    ),
    'jpeg2wbmp' => 
    array (
      0 => 'bool',
      'f_org' => 'string',
      'f_dest' => 'string',
      'd_height' => 'int',
      'd_width' => 'int',
      'd_threshold' => 'int',
    ),
    'jsonexception::__clone' => 
    array (
      0 => 'void',
    ),
    'ldap_control_paged_result' => 
    array (
      0 => 'bool',
      'link_identifier' => 'resource',
      'pagesize' => 'int',
      'iscritical=' => 'bool',
      'cookie=' => 'string',
    ),
    'ldap_control_paged_result_response' => 
    array (
      0 => 'bool',
      'link_identifier' => 'resource',
      'result_identifier' => 'resource',
      '&w_cookie' => 'string',
      '&w_estimated' => 'int',
    ),
    'ldap_sort' => 
    array (
      0 => 'bool',
      'link_identifier' => 'resource',
      'result_identifier' => 'resource',
      'sortfilter' => 'string',
    ),
    'lengthexception::__clone' => 
    array (
      0 => 'void',
    ),
    'logicexception::__clone' => 
    array (
      0 => 'void',
    ),
    'money_format' => 
    array (
      0 => 'string',
      'format' => 'string',
      'value' => 'float',
    ),
    'number_format\'1' => 
    array (
      0 => 'string',
      'num' => 'float',
      'decimals' => 'int',
      'decimal_separator' => 'null|string',
      'thousands_separator' => 'null|string',
    ),
    'outofboundsexception::__clone' => 
    array (
      0 => 'void',
    ),
    'outofrangeexception::__clone' => 
    array (
      0 => 'void',
    ),
    'overflowexception::__clone' => 
    array (
      0 => 'void',
    ),
    'parseerror::__clone' => 
    array (
      0 => 'void',
    ),
    'pdostatement::setfetchmode\'1' => 
    array (
      0 => 'bool',
      'fetch_column' => 'int',
      'colno' => 'int',
    ),
    'pdostatement::setfetchmode\'2' => 
    array (
      0 => 'bool',
      'fetch_class' => 'int',
      'classname' => 'string',
      'ctorargs' => 'array<array-key, mixed>',
    ),
    'pdostatement::setfetchmode\'3' => 
    array (
      0 => 'bool',
      'fetch_into' => 'int',
      'object' => 'object',
    ),
    'png2wbmp' => 
    array (
      0 => 'bool',
      'f_org' => 'string',
      'f_dest' => 'string',
      'd_height' => 'int',
      'd_width' => 'int',
      'd_threshold' => 'int',
    ),
    'rangeexception::__clone' => 
    array (
      0 => 'void',
    ),
    'read_exif_data' => 
    array (
      0 => 'array<array-key, mixed>',
      'filename' => 'string',
      'sections_needed=' => 'string',
      'sub_arrays=' => 'bool',
      'read_thumbnail=' => 'bool',
    ),
    'reflection::export' => 
    array (
      0 => 'null|string',
      'reflector' => 'reflector',
      'return=' => 'bool',
    ),
    'reflectionclass::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object|string',
      'return=' => 'bool',
    ),
    'reflectionclassconstant::export' => 
    array (
      0 => 'string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionextension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionfunction::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionmethod::export' => 
    array (
      0 => 'null|string',
      'class' => 'string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionnamedtype::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionobject::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionobject::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object',
      'return=' => 'bool',
    ),
    'reflectionparameter::export' => 
    array (
      0 => 'null|string',
      'function' => 'string',
      'parameter' => 'string',
      'return=' => 'bool',
    ),
    'reflectionproperty::export' => 
    array (
      0 => 'null|string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectiontype::isbuiltin' => 
    array (
      0 => 'bool',
    ),
    'reflectionzendextension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'restore_include_path' => 
    array (
      0 => 'void',
    ),
    'runtimeexception::__clone' => 
    array (
      0 => 'void',
    ),
    'simplexmliterator::current' => 
    array (
      0 => 'SimpleXMLIterator|null',
    ),
    'simplexmliterator::getchildren' => 
    array (
      0 => 'SimpleXMLIterator|null',
    ),
    'simplexmliterator::haschildren' => 
    array (
      0 => 'bool',
    ),
    'simplexmliterator::key' => 
    array (
      0 => 'false|string',
    ),
    'simplexmliterator::next' => 
    array (
      0 => 'void',
    ),
    'simplexmliterator::rewind' => 
    array (
      0 => 'void',
    ),
    'simplexmliterator::valid' => 
    array (
      0 => 'bool',
    ),
    'soapclient::soapclient' => 
    array (
      0 => 'object',
      'wsdl' => 'mixed',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'soapfault::__clone' => 
    array (
      0 => 'void',
    ),
    'soapfault::soapfault' => 
    array (
      0 => 'object',
      'faultcode' => 'string',
      'faultstring' => 'string',
      'faultactor=' => 'null|string',
      'detail=' => 'mixed|null',
      'faultname=' => 'null|string',
      'headerfault=' => 'mixed|null',
    ),
    'soapheader::soapheader' => 
    array (
      0 => 'object',
      'namespace' => 'string',
      'name' => 'string',
      'data=' => 'mixed',
      'mustunderstand=' => 'bool',
      'actor=' => 'string',
    ),
    'soapparam::soapparam' => 
    array (
      0 => 'object',
      'data' => 'mixed',
      'name' => 'string',
    ),
    'soapserver::soapserver' => 
    array (
      0 => 'object',
      'wsdl' => 'null|string',
      'options=' => 'array<array-key, mixed>',
    ),
    'soapvar::soapvar' => 
    array (
      0 => 'object',
      'data' => 'mixed',
      'encoding' => 'int',
      'type_name=' => 'null|string',
      'type_namespace=' => 'null|string',
      'node_name=' => 'null|string',
      'node_namespace=' => 'null|string',
    ),
    'splfileobject::fgetss' => 
    array (
      0 => 'false|string',
      'allowable_tags=' => 'string',
    ),
    'splfixedarray::key' => 
    array (
      0 => 'int',
    ),
    'splfixedarray::next' => 
    array (
      0 => 'void',
    ),
    'splfixedarray::rewind' => 
    array (
      0 => 'void',
    ),
    'splfixedarray::valid' => 
    array (
      0 => 'bool',
    ),
    'spltempfileobject::fgetss' => 
    array (
      0 => 'string',
      'allowable_tags=' => 'string',
    ),
    'swoole\\http\\request::__destruct' => 
    array (
      0 => 'void',
    ),
    'swoole\\http\\response::__destruct' => 
    array (
      0 => 'void',
    ),
    'swoole\\server::after' => 
    array (
      0 => 'ReturnType',
      'ms' => 'int',
      'callback' => 'callable',
    ),
    'swoole\\server::defer' => 
    array (
      0 => 'void',
      'callback' => 'callable',
    ),
    'swoole\\server::tick' => 
    array (
      0 => 'void',
      'ms' => 'int',
      'callback' => 'callable',
    ),
    'typeerror::__clone' => 
    array (
      0 => 'void',
    ),
    'underflowexception::__clone' => 
    array (
      0 => 'void',
    ),
    'unexpectedvalueexception::__clone' => 
    array (
      0 => 'void',
    ),
    'xmlrpc_decode' => 
    array (
      0 => 'mixed',
      'xml' => 'string',
      'encoding=' => 'string',
    ),
    'xmlrpc_decode_request' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'xml' => 'string',
      '&w_method' => 'string',
      'encoding=' => 'string',
    ),
    'xmlrpc_encode' => 
    array (
      0 => 'string',
      'value' => 'mixed',
    ),
    'xmlrpc_encode_request' => 
    array (
      0 => 'string',
      'method' => 'string',
      'params' => 'mixed',
      'output_options=' => 'array<array-key, mixed>',
    ),
    'xmlrpc_get_type' => 
    array (
      0 => 'string',
      'value' => 'mixed',
    ),
    'xmlrpc_is_fault' => 
    array (
      0 => 'bool',
      'arg' => 'array<array-key, mixed>',
    ),
    'xmlrpc_parse_method_descriptions' => 
    array (
      0 => 'array<array-key, mixed>',
      'xml' => 'string',
    ),
    'xmlrpc_server_add_introspection_data' => 
    array (
      0 => 'int',
      'server' => 'resource',
      'desc' => 'array<array-key, mixed>',
    ),
    'xmlrpc_server_call_method' => 
    array (
      0 => 'string',
      'server' => 'resource',
      'xml' => 'string',
      'user_data' => 'mixed',
      'output_options=' => 'array<array-key, mixed>',
    ),
    'xmlrpc_server_create' => 
    array (
      0 => 'resource',
    ),
    'xmlrpc_server_destroy' => 
    array (
      0 => 'int',
      'server' => 'resource',
    ),
    'xmlrpc_server_register_introspection_callback' => 
    array (
      0 => 'bool',
      'server' => 'resource',
      'function' => 'string',
    ),
    'xmlrpc_server_register_method' => 
    array (
      0 => 'bool',
      'server' => 'resource',
      'method_name' => 'string',
      'function' => 'string',
    ),
    'xmlrpc_set_type' => 
    array (
      0 => 'bool',
      '&rw_value' => 'DateTime|string',
      'type' => 'string',
    ),
  ),
);