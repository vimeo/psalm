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
    'get_debug_type' => 
    array (
      0 => 'string',
      'value' => 'mixed',
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
    'reflectionproperty::getdefaultvalue' => 
    array (
      0 => 'mixed',
    ),
    'reflectionuniontype::gettypes' => 
    array (
      0 => 'list<ReflectionNamedType>',
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
  ),
  'changed' => 
  array (
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
    'array_filter' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'callback=' => 'callable(mixed, array-key=):mixed',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        'callback=' => 'callable(mixed, array-key=):mixed|null',
        'mode=' => 'int',
      ),
    ),
    'array_key_exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'int|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'array_splice' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        '&rw_array' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int',
        'replacement=' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '&rw_array' => 'array<array-key, mixed>',
        'offset' => 'int',
        'length=' => 'int|null',
        'replacement=' => 'array<array-key, mixed>|string',
      ),
    ),
    'bcadd' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
        'num' => 'numeric-string',
        'exponent' => 'numeric-string',
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
        'num' => 'numeric-string',
        'exponent' => 'numeric-string',
        'modulus' => 'numeric-string',
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
    'bcsqrt' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'num' => 'numeric-string',
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
        'num1' => 'numeric-string',
        'num2' => 'numeric-string',
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
    'count' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'value' => 'Countable|SimpleXMLElement|array<array-key, mixed>',
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
        'input' => 'string',
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
    'curl_multi_exec' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'mh' => 'resource',
        '&w_still_running' => 'int',
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
        0 => 'string',
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
    'curl_multi_setopt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mh' => 'resource',
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
    'curlfile::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'mime_type=' => 'string',
        'posted_filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'mime_type=' => 'null|string',
        'posted_filename=' => 'null|string',
      ),
    ),
    'datefmt_create' => 
    array (
      'old' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType=' => 'int',
        'timeType=' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
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
        'datetime' => 'DateTimeInterface',
      ),
      'new' => 
      array (
        0 => 'int',
        'datetime' => 'DateTimeInterface',
      ),
    ),
    'datetimezone::listidentifiers' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
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
    'directoryiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'domdocument::getelementsbytagnamens' => 
    array (
      'old' => 
      array (
        0 => 'DOMNodeList',
        'namespace' => 'string',
        'localName' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMNodeList',
        'namespace' => 'null|string',
        'localName' => 'string',
      ),
    ),
    'domdocument::load' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|bool',
        'filename' => 'string',
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
        'filename' => 'string',
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
    'domimplementation::createdocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => 'string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType',
      ),
      'new' => 
      array (
        0 => 'DOMDocument|false',
        'namespace=' => 'null|string',
        'qualifiedName=' => 'string',
        'doctype=' => 'DOMDocumentType|null',
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
    'errorexception::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'message=' => 'string',
        'code=' => 'int',
        'severity=' => 'int',
        'filename=' => 'string',
        'line=' => 'int',
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
    'explode' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'separator' => 'string',
        'string' => 'string',
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
    'fgetcsv' => 
    array (
      'old' => 
      array (
        0 => 'array{0?: null|string, ...<int<0, max>, string>}|false',
        'stream' => 'resource',
        'length=' => 'int',
        'separator=' => 'string',
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
        'stream' => 'resource',
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
        'use_include_path=' => 'bool',
        'context=' => 'null|resource',
        'offset=' => 'int',
        'length=' => 'int',
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
    'filesystemiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'finfo::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'flags=' => 'int',
        'magic_database=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags=' => 'int',
        'magic_database=' => 'null|string',
      ),
    ),
    'finfo_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'flags=' => 'int',
        'magic_database=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'flags=' => 'int',
        'magic_database=' => 'null|string',
      ),
    ),
    'fputs' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
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
    'fsockopen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
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
    'fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
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
    'get_class_methods' => 
    array (
      'old' => 
      array (
        0 => 'list<non-falsy-string>|null',
        'object_or_class' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'list<non-falsy-string>',
        'object_or_class' => 'class-string|object',
      ),
    ),
    'get_headers' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'url' => 'string',
        'associative=' => 'int',
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
    'get_parent_class' => 
    array (
      'old' => 
      array (
        0 => 'class-string|false',
        'object_or_class=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'class-string|false',
        'object_or_class=' => 'class-string|object',
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
    'globiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'gzgets' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int|null',
      ),
    ),
    'gzputs' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
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
    'gzwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'data' => 'string',
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
        'binary=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'binary=' => 'bool',
      ),
    ),
    'hash_hkdf' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'key' => 'string',
        'length=' => 'int',
        'info=' => 'string',
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
        'binary=' => 'bool',
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
        'data' => 'string',
        'key' => 'string',
        'binary=' => 'bool',
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
    'iconv_strlen' => 
    array (
      'old' => 
      array (
        0 => 'false|int<0, max>',
        'string' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int<0, max>',
        'string' => 'string',
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
    'imagebmp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
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
    'imagecolorset' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'image' => 'resource',
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
    'imagefilledpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilter' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'filter' => 'int',
        '...args=' => 'array<array-key, mixed>|bool|float|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'filter' => 'int',
        '...args=' => 'array<array-key, mixed>|bool|float|int',
      ),
    ),
    'imagegd' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
      ),
    ),
    'imagegd2' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
        'chunk_size=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'file=' => 'null|resource|string',
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
        'image' => 'resource',
        'file=' => 'null|resource|string',
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
    'imagejpeg' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
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
    'imageopenpolygon' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_points' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points' => 'int',
        'color' => 'int',
      ),
    ),
    'imagepng' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
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
        'image' => 'resource',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'points' => 'array<array-key, mixed>',
        'num_points_or_color' => 'int',
        'color' => 'int',
      ),
    ),
    'imagesetclip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'x1' => 'int',
        'x2' => 'int',
        'y1' => 'int',
        'y2' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'x2' => 'int',
        'y1' => 'int',
        'y2' => 'int',
      ),
    ),
    'imagesetstyle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'style' => 'non-empty-array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'style' => 'non-empty-array<array-key, mixed>',
      ),
    ),
    'imagewbmp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'file=' => 'null|resource|string',
        'foreground_color=' => 'int',
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
        'image' => 'resource',
        'file=' => 'null|resource|string',
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
        'value' => 'DateTimeInterface|IntlCalendar|array{0?: int, 1?: int, 2?: int, 3?: int, 4?: int, 5?: int, 6?: int, 7?: int, 8?: int, tm_hour?: int, tm_isdst?: int, tm_mday?: int, tm_min?: int, tm_mon?: int, tm_sec?: int, tm_wday?: int, tm_yday?: int, tm_year?: int}|float|int|string',
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
        'which=' => 'int',
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
        'value' => 'string',
        '&rw_position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'string' => 'string',
        '&rw_offset=' => 'int',
      ),
    ),
    'intldateformatter::parse' => 
    array (
      'old' => 
      array (
        0 => 'float|int',
        'value' => 'string',
        '&rw_position=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|float|int',
        'string' => 'string',
        '&rw_offset=' => 'int',
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
    'intltimezone::getidforwindowsid' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        'region=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'timezoneId' => 'string',
        'region=' => 'null|string',
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
    'locale_get_display_language' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'string',
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
        'displayLocale=' => 'string',
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
        'displayLocale=' => 'string',
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
        'displayLocale=' => 'string',
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
        'displayLocale=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'locale' => 'string',
        'displayLocale=' => 'null|string',
      ),
    ),
    'mb_chr' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'codepoint' => 'int',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'codepoint' => 'int',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_convert_encoding' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'to_encoding' => 'string',
        'from_encoding=' => 'mixed',
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
        '&w_matches=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        '&w_matches=' => 'array<array-key, mixed>|null',
      ),
    ),
    'mb_ereg_search_pos' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, int>|false',
        'pattern=' => 'string',
        'options=' => 'string',
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
        'options=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|false',
        'pattern=' => 'null|string',
        'options=' => 'null|string',
      ),
    ),
    'mb_eregi' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'pattern' => 'string',
        'string' => 'string',
        '&w_matches=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        '&w_matches=' => 'array<array-key, mixed>|null',
      ),
    ),
    'mb_parse_str' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'string' => 'string',
        '&w_result=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'string' => 'string',
        '&w_result' => 'array<array-key, mixed>',
      ),
    ),
    'mb_str_split' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
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
    'mb_strlen' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'string' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mb_strtolower' => 
    array (
      'old' => 
      array (
        0 => 'lowercase-string',
        'string' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'lowercase-string',
        'string' => 'string',
        'encoding=' => 'null|string',
      ),
    ),
    'mhash' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'algo' => 'int',
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
        0 => 'Odbc\\Result|false',
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
        0 => 'Odbc\\Result|false',
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
        0 => 'Odbc\\Result|false',
        'odbc' => 'resource',
        'catalog=' => 'null|string',
        'schema=' => 'null|string',
        'table=' => 'null|string',
        'types=' => 'null|string',
      ),
    ),
    'openssl_csr_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'csr' => 'resource|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_csr_new' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'distinguished_names' => 'array<array-key, mixed>',
        '&w_private_key' => 'resource',
        'options=' => 'array<array-key, mixed>',
        'extra_attributes=' => 'array<array-key, mixed>',
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
        'ca_certificate' => 'null|resource|string',
        'private_key' => 'array<array-key, mixed>|resource|string',
        'days' => 'int',
        'options=' => 'array<array-key, mixed>',
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
    'openssl_get_privatekey' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'private_key' => 'string',
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
        'public_key' => 'resource|string',
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
        '&w_output' => 'string',
        'encrypted_key' => 'string',
        'private_key' => 'array<array-key, mixed>|resource|string',
        'cipher_algo=' => 'string',
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
    'openssl_pkcs12_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'certificate' => 'resource|string',
        '&w_output' => 'string',
        'private_key' => 'array<array-key, mixed>|resource|string',
        'passphrase' => 'string',
        'options=' => 'array<array-key, mixed>',
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
        'certificate' => 'resource|string',
        'output_filename' => 'string',
        'private_key' => 'array<array-key, mixed>|resource|string',
        'passphrase' => 'string',
        'options=' => 'array<array-key, mixed>',
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
    'openssl_pkcs7_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'resource|string',
        'private_key=' => 'array<array-key, mixed>|resource|string',
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
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'array<array-key, mixed>|resource|string',
        'headers' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'cipher_algo=' => 'int',
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
    'openssl_pkcs7_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'input_filename' => 'string',
        'output_filename' => 'string',
        'certificate' => 'resource|string',
        'private_key' => 'array<array-key, mixed>|resource|string',
        'headers' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'untrusted_certificates_filename=' => 'string',
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
    'openssl_pkey_derive' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'public_key' => 'mixed',
        'private_key' => 'mixed',
        'key_length=' => 'int|null',
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
        '&w_output' => 'string',
        'passphrase=' => 'null|string',
        'options=' => 'array<array-key, mixed>',
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
        'output_filename' => 'string',
        'passphrase=' => 'null|string',
        'options=' => 'array<array-key, mixed>',
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
    'openssl_pkey_get_private' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'private_key' => 'string',
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
        'public_key' => 'resource|string',
      ),
      'new' => 
      array (
        0 => 'OpenSSLAsymmetricKey|false',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
      ),
    ),
    'openssl_private_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_decrypted_data' => 'string',
        'private_key' => 'array<array-key, mixed>|resource|string',
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
        '&w_encrypted_data' => 'string',
        'private_key' => 'array<array-key, mixed>|resource|string',
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
        '&w_decrypted_data' => 'string',
        'public_key' => 'resource|string',
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
        '&w_encrypted_data' => 'string',
        'public_key' => 'resource|string',
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
    'openssl_seal' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        '&w_sealed_data' => 'string',
        '&w_encrypted_keys' => 'array<array-key, mixed>',
        'public_key' => 'array<array-key, mixed>',
        'cipher_algo=' => 'string',
        '&rw_iv=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        '&w_sealed_data' => 'string',
        '&w_encrypted_keys' => 'array<array-key, mixed>',
        'public_key' => 'list<OpenSSLAsymmetricKey>',
        'cipher_algo' => 'string',
        '&rw_iv=' => 'string',
      ),
    ),
    'openssl_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_signature' => 'string',
        'private_key' => 'resource|string',
        'algorithm=' => 'int|string',
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
    'openssl_verify' => 
    array (
      'old' => 
      array (
        0 => '-1|0|1',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'resource|string',
        'algorithm=' => 'int|string',
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
        'certificate' => 'resource|string',
        'private_key' => 'array<array-key, mixed>|resource|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
      ),
    ),
    'openssl_x509_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'certificate' => 'resource|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'certificate' => 'OpenSSLCertificate|string',
        '&w_output' => 'string',
        'no_text=' => 'bool',
      ),
    ),
    'openssl_x509_verify' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'certificate' => 'resource|string',
        'public_key' => 'array<array-key, mixed>|resource|string',
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
        '...values=' => 'mixed',
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
        'string' => 'string',
        '&w_result=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'string' => 'string',
        '&w_result' => 'array<array-key, mixed>',
      ),
    ),
    'pcntl_exec' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'path' => 'string',
        'args=' => 'array<array-key, mixed>',
        'env_vars=' => 'array<array-key, mixed>',
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
        'process_id=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'process_id=' => 'int|null',
        'mode=' => 'int',
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
        'fetch_argument=' => 'callable|int|string',
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
    'pdostatement::setfetchmode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'mode' => 'int',
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
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
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
    'pg_lo_write' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'lob' => 'resource',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'lob' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
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
    'phar::addfile' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'null|string',
      ),
    ),
    'phar::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
    ),
    'phar::compress' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'compression' => 'int',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'compression' => 'int',
        'extension=' => 'null|string',
      ),
    ),
    'phar::converttodata' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int',
        'compression=' => 'int',
        'extension=' => 'string',
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
        'compression=' => 'int',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phar::decompress' => 
    array (
      'old' => 
      array (
        0 => 'Phar|null',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'extension=' => 'null|string',
      ),
    ),
    'phar::getmetadata' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'unserializeOptions=' => 'array<array-key, mixed>',
      ),
    ),
    'phar::setdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webIndex=' => 'string',
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
        'algo' => 'int',
        'privateKey=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'algo' => 'int',
        'privateKey=' => 'null|string',
      ),
    ),
    'phardata::addfile' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'filename' => 'string',
        'localName=' => 'null|string',
      ),
    ),
    'phardata::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'string',
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
        'compression' => 'int',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'compression' => 'int',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::converttodata' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'format=' => 'int',
        'compression=' => 'int',
        'extension=' => 'string',
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
        'compression=' => 'int',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'Phar|null',
        'format=' => 'int|null',
        'compression=' => 'int|null',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::decompress' => 
    array (
      'old' => 
      array (
        0 => 'PharData|null',
        'extension=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PharData|null',
        'extension=' => 'null|string',
      ),
    ),
    'phardata::setdefaultstub' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index=' => 'null|string',
        'webIndex=' => 'string',
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
        'algo' => 'int',
        'privateKey=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'algo' => 'int',
        'privateKey=' => 'null|string',
      ),
    ),
    'pharfileinfo::getmetadata' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'unserializeOptions=' => 'array<array-key, mixed>',
      ),
    ),
    'pharfileinfo::iscompressed' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'compression=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'compression=' => 'int|null',
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
    'readline_info' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'var_name=' => 'string',
        'value=' => 'bool|int|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'var_name=' => 'null|string',
        'value=' => 'bool|int|null|string',
      ),
    ),
    'recursivedirectoryiterator::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
      ),
      'new' => 
      array (
        0 => 'SplFileInfo|null',
        'class=' => 'class-string|null',
      ),
    ),
    'recursivedirectoryiterator::openfile' => 
    array (
      'old' => 
      array (
        0 => 'SplFileObject',
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
        'object' => 'object',
      ),
      'new' => 
      array (
        0 => 'bool',
        'object=' => 'null|object',
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
    'session_set_cookie_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lifetime' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lifetime' => 'int',
        'path=' => 'null|string',
        'domain=' => 'null|string',
        'secure=' => 'bool|null',
        'httponly=' => 'bool|null',
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
    'simplexmlelement::asxml' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'filename=' => 'null|string',
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
    'sizeof' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'value' => 'Countable|SimpleXMLElement|array<array-key, mixed>',
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
        'one_way=' => 'bool',
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
        'stream' => 'resource',
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
        'stream' => 'resource',
        'enable' => 'bool',
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
        'callback=' => 'callable(string):void',
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
    'splfileinfo::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'splfileobject::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'spltempfileobject::getfileinfo' => 
    array (
      'old' => 
      array (
        0 => 'SplFileInfo',
        'class=' => 'class-string',
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
        'class=' => 'class-string',
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
        'mode=' => 'string',
        'useIncludePath=' => 'bool',
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
    'str_word_count' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>|int',
        'string' => 'string',
        'format=' => 'int',
        'characters=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<int, string>|int',
        'string' => 'string',
        'format=' => 'int',
        'characters=' => 'null|string',
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
    'stream_copy_to_stream' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'from' => 'resource',
        'to' => 'resource',
        'length=' => 'int',
        'offset=' => 'int',
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
    'stream_get_contents' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'stream' => 'resource',
        'length=' => 'int',
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
    'stream_set_chunk_size' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'size' => 'int',
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
        'socket' => 'resource',
        'timeout=' => 'float',
        '&w_peer_name=' => 'string',
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
        'address' => 'string',
        '&w_error_code=' => 'int',
        '&w_error_message=' => 'string',
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
        'crypto_method=' => 'int|null',
        'session_stream=' => 'resource',
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
    'strip_tags' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'allowed_tags=' => 'list<non-empty-string>|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'allowed_tags=' => 'list<non-empty-string>|null|string',
      ),
    ),
    'substr_replace' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'replace' => 'array<array-key, string>|string',
        'offset' => 'array<array-key, int>|int',
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
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'list<string>',
        'timezoneGroup=' => 'int',
        'countryCode=' => 'null|string',
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
        'handler' => 'callable|null',
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
        'parser' => 'resource',
        'handler' => 'callable',
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
        'start_handler' => 'callable',
        'end_handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'start_handler' => 'callable|null',
        'end_handler' => 'callable',
      ),
    ),
    'xml_set_end_namespace_decl_handler' => 
    array (
      'old' => 
      array (
        0 => 'true',
        'parser' => 'resource',
        'handler' => 'callable',
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
        'handler' => 'callable',
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
        'handler' => 'callable',
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
        'object' => 'object',
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
        'parser' => 'resource',
        'handler' => 'callable',
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
        'handler' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'parser' => 'XMLParser',
        'handler' => 'callable',
      ),
    ),
    'xmlreader::next' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name=' => 'null|string',
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
    'xmlwriter::startattributens' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prefix' => 'string',
        'name' => 'string',
        'namespace' => 'null|string',
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
        'namespace' => 'null|string',
        'value' => 'string',
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
    'xmlwriter::writedtdentity' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'content' => 'string',
        'isParam' => 'bool',
        'publicId' => 'string',
        'systemId' => 'string',
        'notationData' => 'string',
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
  ),
  'removed' => 
  array (
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
    'amqpbasicproperties::getdeliverymode' => 
    array (
      0 => 'int',
    ),
    'amqpbasicproperties::getheaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpbasicproperties::getpriority' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::getchannelid' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpchannel::getprefetchcount' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::getprefetchsize' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::isconnected' => 
    array (
      0 => 'bool',
    ),
    'amqpconnection::getheartbeatinterval' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::gethost' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getlogin' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getmaxframesize' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getpassword' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getport' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getreadtimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::gettimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::getusedchannels' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getverify' => 
    array (
      0 => 'bool',
    ),
    'amqpconnection::getvhost' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getwritetimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::isconnected' => 
    array (
      0 => 'bool',
    ),
    'amqpdecimal::getexponent' => 
    array (
      0 => 'int',
    ),
    'amqpdecimal::getsignificand' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getbody' => 
    array (
      0 => 'string',
    ),
    'amqpenvelope::getdeliverymode' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getheaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpenvelope::getpriority' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getroutingkey' => 
    array (
      0 => 'string',
    ),
    'amqpenvelope::isredelivery' => 
    array (
      0 => 'bool',
    ),
    'amqpexchange::getarguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpexchange::getchannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'amqpexchange::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpexchange::getflags' => 
    array (
      0 => 'int',
    ),
    'amqpqueue::declarequeue' => 
    array (
      0 => 'int',
    ),
    'amqpqueue::getarguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpqueue::getchannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'amqpqueue::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpqueue::getconsumertag' => 
    array (
      0 => 'null|string',
    ),
    'amqpqueue::getflags' => 
    array (
      0 => 'int',
    ),
    'amqptimestamp::__tostring' => 
    array (
      0 => 'string',
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
    'apcu_enabled' => 
    array (
      0 => 'bool',
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
    'argumentcounterror::__tostring' => 
    array (
      0 => 'string',
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
    'argumentcounterror::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'arithmeticerror::__tostring' => 
    array (
      0 => 'string',
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
    'arithmeticerror::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'array_change_key_case' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      'case=' => 'int',
    ),
    'array_column' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      'column_key' => 'mixed',
      'index_key=' => 'mixed',
    ),
    'array_diff' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
    ),
    'array_diff_assoc' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
    ),
    'array_diff_key' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
    ),
    'array_intersect' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
    ),
    'array_intersect_assoc' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
    ),
    'array_intersect_key' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      '...arrays' => 'array<array-key, mixed>',
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
    'array_product' => 
    array (
      0 => 'float|int',
      'array' => 'array<array-key, mixed>',
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
    'array_slice' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      'offset' => 'int',
      'length=' => 'int|null',
      'preserve_keys=' => 'bool',
    ),
    'array_sum' => 
    array (
      0 => 'float|int',
      'array' => 'array<array-key, mixed>',
    ),
    'array_unique' => 
    array (
      0 => 'array<array-key, mixed>',
      'array' => 'array<array-key, mixed>',
      'flags=' => 'int',
    ),
    'arrayiterator::serialize' => 
    array (
      0 => 'string',
    ),
    'arrayobject::getiteratorclass' => 
    array (
      0 => 'string',
    ),
    'arrayobject::serialize' => 
    array (
      0 => 'string',
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
    'badfunctioncallexception::__tostring' => 
    array (
      0 => 'string',
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
    'badfunctioncallexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'badmethodcallexception::__tostring' => 
    array (
      0 => 'string',
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
    'badmethodcallexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'bcscale' => 
    array (
      0 => 'int',
      'scale=' => 'int',
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
    'cachingiterator::__tostring' => 
    array (
      0 => 'string',
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
    'closedgeneratorexception::__tostring' => 
    array (
      0 => 'string',
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
    'closedgeneratorexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'closure::fromcallable' => 
    array (
      0 => 'Closure',
      'callback' => 'callable',
    ),
    'collator::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'collator::getlocale' => 
    array (
      0 => 'string',
      'type' => 'int',
    ),
    'collator_create' => 
    array (
      0 => 'Collator|null',
      'locale' => 'string',
    ),
    'connection_aborted' => 
    array (
      0 => 'int',
    ),
    'connection_status' => 
    array (
      0 => 'int',
    ),
    'convert_uuencode' => 
    array (
      0 => 'string',
      'string' => 'string',
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
    'crc32' => 
    array (
      0 => 'int',
      'string' => 'string',
    ),
    'create_function' => 
    array (
      0 => 'string',
      'args' => 'string',
      'code' => 'string',
    ),
    'crypt' => 
    array (
      0 => 'string',
      'string' => 'string',
      'salt=' => 'string',
    ),
    'curl_close' => 
    array (
      0 => 'void',
      'ch' => 'resource',
    ),
    'curl_copy_handle' => 
    array (
      0 => 'false|resource',
      'ch' => 'resource',
    ),
    'curl_errno' => 
    array (
      0 => 'int',
      'ch' => 'resource',
    ),
    'curl_error' => 
    array (
      0 => 'string',
      'ch' => 'resource',
    ),
    'curl_escape' => 
    array (
      0 => 'false|string',
      'ch' => 'resource',
      'string' => 'string',
    ),
    'curl_exec' => 
    array (
      0 => 'bool|string',
      'ch' => 'resource',
    ),
    'curl_file_create' => 
    array (
      0 => 'CURLFile',
      'filename' => 'string',
      'mimetype=' => 'string',
      'postfilename=' => 'string',
    ),
    'curl_init' => 
    array (
      0 => 'false|resource',
      'url=' => 'string',
    ),
    'curl_multi_add_handle' => 
    array (
      0 => 'int',
      'mh' => 'resource',
      'ch' => 'resource',
    ),
    'curl_multi_close' => 
    array (
      0 => 'void',
      'mh' => 'resource',
    ),
    'curl_multi_errno' => 
    array (
      0 => 'false|int',
      'mh' => 'resource',
    ),
    'curl_multi_init' => 
    array (
      0 => 'resource',
    ),
    'curl_multi_remove_handle' => 
    array (
      0 => 'int',
      'mh' => 'resource',
      'ch' => 'resource',
    ),
    'curl_multi_select' => 
    array (
      0 => 'int',
      'mh' => 'resource',
      'timeout=' => 'float',
    ),
    'curl_multi_strerror' => 
    array (
      0 => 'null|string',
      'error_code' => 'int',
    ),
    'curl_pause' => 
    array (
      0 => 'int',
      'ch' => 'resource',
      'bitmask' => 'int',
    ),
    'curl_reset' => 
    array (
      0 => 'void',
      'ch' => 'resource',
    ),
    'curl_setopt_array' => 
    array (
      0 => 'bool',
      'ch' => 'resource',
      'options' => 'array<array-key, mixed>',
    ),
    'curl_share_close' => 
    array (
      0 => 'void',
      'sh' => 'resource',
    ),
    'curl_share_errno' => 
    array (
      0 => 'false|int',
      'sh' => 'resource',
    ),
    'curl_share_init' => 
    array (
      0 => 'resource',
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
      'ch' => 'resource',
      'string' => 'string',
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
    'date' => 
    array (
      0 => 'string',
      'format' => 'string',
      'timestamp=' => 'int',
    ),
    'date_add' => 
    array (
      0 => 'DateTime|false',
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
      0 => 'DateTime|false',
      'object' => 'DateTime',
      'year' => 'int',
      'month' => 'int',
      'day' => 'int',
    ),
    'date_diff' => 
    array (
      0 => 'DateInterval|false',
      'baseObject' => 'DateTimeInterface',
      'targetObject' => 'DateTimeInterface',
      'absolute=' => 'bool',
    ),
    'date_format' => 
    array (
      0 => 'false|string',
      'object' => 'DateTimeInterface',
      'format' => 'string',
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
      0 => 'false|int',
      'object' => 'DateTimeInterface',
    ),
    'date_parse' => 
    array (
      0 => 'array<array-key, mixed>|false',
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
      0 => 'DateTime|false',
      'object' => 'DateTime',
      'interval' => 'DateInterval',
    ),
    'date_sun_info' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'timestamp' => 'int',
      'latitude' => 'float',
      'longitude' => 'float',
    ),
    'date_sunrise' => 
    array (
      0 => 'false|float|int|string',
      'timestamp' => 'int',
      'returnFormat=' => 'int',
      'latitude=' => 'float',
      'longitude=' => 'float',
      'zenith=' => 'float',
      'utcOffset=' => 'float',
    ),
    'date_sunset' => 
    array (
      0 => 'false|float|int|string',
      'timestamp' => 'int',
      'returnFormat=' => 'int',
      'latitude=' => 'float',
      'longitude=' => 'float',
      'zenith=' => 'float',
      'utcOffset=' => 'float',
    ),
    'date_time_set' => 
    array (
      0 => 'DateTime|false',
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
      0 => 'DateTime|false',
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
      0 => 'DateTime|false',
      'object' => 'DateTime',
      'timezone' => 'DateTimeZone',
    ),
    'datefmt_get_calendar_object' => 
    array (
      0 => 'IntlCalendar|false|null',
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
    'dateinterval::format' => 
    array (
      0 => 'string',
      'format' => 'string',
    ),
    'datetime::format' => 
    array (
      0 => 'false|string',
      'format' => 'string',
    ),
    'debug_print_backtrace' => 
    array (
      0 => 'void',
      'options=' => 'int',
      'limit=' => 'int',
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
    'defined' => 
    array (
      0 => 'bool',
      'constant_name' => 'string',
    ),
    'deflate_add' => 
    array (
      0 => 'false|string',
      'context' => 'resource',
      'data' => 'string',
      'flush_mode=' => 'int',
    ),
    'deflate_init' => 
    array (
      0 => 'false|resource',
      'encoding' => 'int',
      'options=' => 'array<array-key, mixed>',
    ),
    'deg2rad' => 
    array (
      0 => 'float',
      'num' => 'float',
    ),
    'directoryiterator::__tostring' => 
    array (
      0 => 'string',
    ),
    'directoryiterator::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'directoryiterator::getextension' => 
    array (
      0 => 'string',
    ),
    'directoryiterator::getfilename' => 
    array (
      0 => 'string',
    ),
    'directoryiterator::getlinktarget' => 
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
    'directoryiterator::gettype' => 
    array (
      0 => 'string',
    ),
    'directoryiterator::key' => 
    array (
      0 => 'string',
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
    'domainexception::__tostring' => 
    array (
      0 => 'string',
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
    'domainexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'domcharacterdata::substringdata' => 
    array (
      0 => 'string',
      'offset' => 'int',
      'count' => 'int',
    ),
    'domelement::getattribute' => 
    array (
      0 => 'string',
      'qualifiedName' => 'string',
    ),
    'domelement::getattributens' => 
    array (
      0 => 'string',
      'namespace' => 'null|string',
      'localName' => 'string',
    ),
    'ds\\deque::capacity' => 
    array (
      0 => 'int',
    ),
    'ds\\deque::count' => 
    array (
      0 => 'int',
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
    'ds\\deque::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\map::capacity' => 
    array (
      0 => 'int',
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
    'ds\\map::first' => 
    array (
      0 => 'Ds\\Pair',
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
    'ds\\map::keys' => 
    array (
      0 => 'Ds\\Set',
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
    'ds\\map::pairs' => 
    array (
      0 => 'Ds\\Sequence',
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
    'ds\\map::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
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
    'ds\\pair::copy' => 
    array (
      0 => 'Ds\\Pair',
    ),
    'ds\\pair::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\priorityqueue::capacity' => 
    array (
      0 => 'int',
    ),
    'ds\\priorityqueue::count' => 
    array (
      0 => 'int',
    ),
    'ds\\priorityqueue::isempty' => 
    array (
      0 => 'bool',
    ),
    'ds\\priorityqueue::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\queue::capacity' => 
    array (
      0 => 'int',
    ),
    'ds\\queue::count' => 
    array (
      0 => 'int',
    ),
    'ds\\queue::isempty' => 
    array (
      0 => 'bool',
    ),
    'ds\\queue::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\set::capacity' => 
    array (
      0 => 'int',
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
      0 => 'string',
      'glue=' => 'string',
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
    'ds\\stack::capacity' => 
    array (
      0 => 'int',
    ),
    'ds\\stack::count' => 
    array (
      0 => 'int',
    ),
    'ds\\stack::isempty' => 
    array (
      0 => 'bool',
    ),
    'ds\\stack::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\vector::capacity' => 
    array (
      0 => 'int',
    ),
    'ds\\vector::count' => 
    array (
      0 => 'int',
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
    'ds\\vector::toarray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'each' => 
    array (
      0 => 'array{0: int|string, 1: mixed, key: int|string, value: mixed}',
      '&r_arr' => 'array<array-key, mixed>',
    ),
    'error::__clone' => 
    array (
      0 => 'void',
    ),
    'error::__tostring' => 
    array (
      0 => 'string',
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
    'error::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'error_clear_last' => 
    array (
      0 => 'void',
    ),
    'error_log' => 
    array (
      0 => 'bool',
      'message' => 'string',
      'message_type=' => 'int',
      'destination=' => 'string',
      'additional_headers=' => 'string',
    ),
    'error_reporting' => 
    array (
      0 => 'int',
      'error_level=' => 'int',
    ),
    'errorexception::__tostring' => 
    array (
      0 => 'string',
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
    'evcheck::clear' => 
    array (
      0 => 'int',
    ),
    'evcheck::start' => 
    array (
      0 => 'void',
    ),
    'evcheck::stop' => 
    array (
      0 => 'void',
    ),
    'evchild::clear' => 
    array (
      0 => 'int',
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
    'evembed::clear' => 
    array (
      0 => 'int',
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
    'event::setpriority' => 
    array (
      0 => 'bool',
      'priority' => 'int',
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
    'eventbase::stop' => 
    array (
      0 => 'bool',
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
    'eventbuffer::enablelocking' => 
    array (
      0 => 'void',
    ),
    'eventbuffer::freeze' => 
    array (
      0 => 'bool',
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
    'eventbuffer::read' => 
    array (
      0 => 'string',
      'max_bytes' => 'int',
    ),
    'eventbuffer::unfreeze' => 
    array (
      0 => 'bool',
      'at_front' => 'bool',
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
    'eventbufferevent::readbuffer' => 
    array (
      0 => 'bool',
      'buf' => 'EventBuffer',
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
    'eventhttprequest::free' => 
    array (
      0 => 'void',
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
    'eventhttprequest::getresponsecode' => 
    array (
      0 => 'int',
    ),
    'eventhttprequest::geturi' => 
    array (
      0 => 'string',
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
    'eventlistener::disable' => 
    array (
      0 => 'bool',
    ),
    'eventlistener::enable' => 
    array (
      0 => 'bool',
    ),
    'evfork::clear' => 
    array (
      0 => 'int',
    ),
    'evfork::start' => 
    array (
      0 => 'void',
    ),
    'evfork::stop' => 
    array (
      0 => 'void',
    ),
    'evidle::clear' => 
    array (
      0 => 'int',
    ),
    'evidle::start' => 
    array (
      0 => 'void',
    ),
    'evidle::stop' => 
    array (
      0 => 'void',
    ),
    'evio::clear' => 
    array (
      0 => 'int',
    ),
    'evio::start' => 
    array (
      0 => 'void',
    ),
    'evio::stop' => 
    array (
      0 => 'void',
    ),
    'evloop::backend' => 
    array (
      0 => 'int',
    ),
    'evloop::invokepending' => 
    array (
      0 => 'void',
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
    'evloop::resume' => 
    array (
      0 => 'void',
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
    'evloop::suspend' => 
    array (
      0 => 'void',
    ),
    'evloop::verify' => 
    array (
      0 => 'void',
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
    'evperiodic::start' => 
    array (
      0 => 'void',
    ),
    'evperiodic::stop' => 
    array (
      0 => 'void',
    ),
    'evprepare::clear' => 
    array (
      0 => 'int',
    ),
    'evprepare::start' => 
    array (
      0 => 'void',
    ),
    'evprepare::stop' => 
    array (
      0 => 'void',
    ),
    'evsignal::clear' => 
    array (
      0 => 'int',
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
    'evstat::clear' => 
    array (
      0 => 'int',
    ),
    'evstat::set' => 
    array (
      0 => 'void',
      'path' => 'string',
      'interval' => 'float',
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
    'evtimer::again' => 
    array (
      0 => 'void',
    ),
    'evtimer::clear' => 
    array (
      0 => 'int',
    ),
    'evtimer::set' => 
    array (
      0 => 'void',
      'after' => 'float',
      'repeat' => 'float',
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
    'exception::__tostring' => 
    array (
      0 => 'string',
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
    'exception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'exp' => 
    array (
      0 => 'float',
      'num' => 'float',
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
    'fgetss' => 
    array (
      0 => 'false|string',
      'fp' => 'resource',
      'length=' => 'int',
      'allowable_tags=' => 'string',
    ),
    'file_exists' => 
    array (
      0 => 'bool',
      'filename' => 'string',
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
    'filesystemiterator::__tostring' => 
    array (
      0 => 'string',
    ),
    'filesystemiterator::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'filesystemiterator::getextension' => 
    array (
      0 => 'string',
    ),
    'filesystemiterator::getfilename' => 
    array (
      0 => 'string',
    ),
    'filesystemiterator::getlinktarget' => 
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
    'filesystemiterator::gettype' => 
    array (
      0 => 'string',
    ),
    'filesystemiterator::key' => 
    array (
      0 => 'string',
    ),
    'filetype' => 
    array (
      0 => 'false|string',
      'filename' => 'string',
    ),
    'filter_id' => 
    array (
      0 => 'false|int',
      'name' => 'string',
    ),
    'filter_var_array' => 
    array (
      0 => 'array<array-key, mixed>|false|null',
      'array' => 'array<array-key, mixed>',
      'options=' => 'array<array-key, mixed>|int',
      'add_empty=' => 'bool',
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
    'ftok' => 
    array (
      0 => 'int',
      'filename' => 'string',
      'project_id' => 'string',
    ),
    'func_get_arg' => 
    array (
      0 => 'mixed|null',
      'position' => 'int',
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
    'gd_info' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'generator::next' => 
    array (
      0 => 'void',
    ),
    'generator::rewind' => 
    array (
      0 => 'void',
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
    'get_current_user' => 
    array (
      0 => 'string',
    ),
    'get_defined_vars' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'get_html_translation_table' => 
    array (
      0 => 'array<array-key, mixed>',
      'table=' => 'int',
      'flags=' => 'int',
      'encoding=' => 'string',
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
    'gethostname' => 
    array (
      0 => 'false|string',
    ),
    'getlastmod' => 
    array (
      0 => 'false|int',
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
    'getprotobyname' => 
    array (
      0 => 'false|int',
      'protocol' => 'string',
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
    'globiterator::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'globiterator::getextension' => 
    array (
      0 => 'string',
    ),
    'globiterator::getfilename' => 
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
    'globiterator::key' => 
    array (
      0 => 'string',
    ),
    'gmdate' => 
    array (
      0 => 'string',
      'format' => 'string',
      'timestamp=' => 'int',
    ),
    'gmmktime' => 
    array (
      0 => 'false|int',
      'hour=' => 'int',
      'minute=' => 'int',
      'second=' => 'int',
      'month=' => 'int',
      'day=' => 'int',
      'year=' => 'int',
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
      0 => 'GMP|false',
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
      0 => 'false|string',
      'num' => 'GMP|int|string',
      'word_size=' => 'int',
      'flags=' => 'int',
    ),
    'gmp_gcd' => 
    array (
      0 => 'GMP',
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
      0 => 'GMP|false',
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
    'gmp_random' => 
    array (
      0 => 'GMP',
      'limiter=' => 'int',
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
      'timestamp=' => 'int',
    ),
    'grapheme_stripos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
    ),
    'grapheme_stristr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'beforeNeedle=' => 'bool',
    ),
    'grapheme_strpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
    ),
    'grapheme_strripos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
    ),
    'grapheme_strrpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
    ),
    'grapheme_strstr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'beforeNeedle=' => 'bool',
    ),
    'grapheme_substr' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'offset' => 'int',
      'length=' => 'int|null',
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
    'gzgetss' => 
    array (
      0 => 'false|string',
      'zp' => 'resource',
      'length' => 'int',
      'allowable_tags=' => 'string',
    ),
    'gzinflate' => 
    array (
      0 => 'false|string',
      'data' => 'string',
      'max_length=' => 'int',
    ),
    'gzuncompress' => 
    array (
      0 => 'false|string',
      'data' => 'string',
      'max_length=' => 'int',
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
    'hash_init' => 
    array (
      0 => 'HashContext|false',
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
      'name=' => 'string',
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
      0 => 'bool|string',
      'string' => 'string',
      'return=' => 'bool',
    ),
    'html_entity_decode' => 
    array (
      0 => 'string',
      'string' => 'string',
      'flags=' => 'int',
      'encoding=' => 'string',
    ),
    'htmlentities' => 
    array (
      0 => 'string',
      'string' => 'string',
      'flags=' => 'int',
      'encoding=' => 'string',
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
      'encoding=' => 'string',
    ),
    'iconv_mime_decode_headers' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'headers' => 'string',
      'mode=' => 'int',
      'encoding=' => 'string',
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
    'iconv_strpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'encoding=' => 'string',
    ),
    'iconv_strrpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'encoding=' => 'string',
    ),
    'iconv_substr' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'offset' => 'int',
      'length=' => 'int',
      'encoding=' => 'string',
    ),
    'ignore_user_abort' => 
    array (
      0 => 'int',
      'enable=' => 'bool',
    ),
    'image2wbmp' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'filename=' => 'null|string',
      'threshold=' => 'int',
    ),
    'image_type_to_mime_type' => 
    array (
      0 => 'string',
      'image_type' => 'int',
    ),
    'imageaffine' => 
    array (
      0 => 'false|resource',
      'src' => 'resource',
      'affine' => 'array<array-key, mixed>',
      'clip=' => 'array<array-key, mixed>',
    ),
    'imagealphablending' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'enable' => 'bool',
    ),
    'imageantialias' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'enable' => 'bool',
    ),
    'imagearc' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'center_x' => 'int',
      'center_y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'start_angle' => 'int',
      'end_angle' => 'int',
      'color' => 'int',
    ),
    'imagechar' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'font' => 'int',
      'x' => 'int',
      'y' => 'int',
      'char' => 'string',
      'color' => 'int',
    ),
    'imagecharup' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'font' => 'int',
      'x' => 'int',
      'y' => 'int',
      'char' => 'string',
      'color' => 'int',
    ),
    'imagecolorallocate' => 
    array (
      0 => 'false|int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
    ),
    'imagecolorallocatealpha' => 
    array (
      0 => 'false|int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
      'alpha' => 'int',
    ),
    'imagecolorat' => 
    array (
      0 => 'false|int',
      'image' => 'resource',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagecolorclosest' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
    ),
    'imagecolorclosestalpha' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
      'alpha' => 'int',
    ),
    'imagecolorclosesthwb' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
    ),
    'imagecolordeallocate' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'color' => 'int',
    ),
    'imagecolorexact' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
    ),
    'imagecolorexactalpha' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
      'alpha' => 'int',
    ),
    'imagecolormatch' => 
    array (
      0 => 'bool',
      'image1' => 'resource',
      'image2' => 'resource',
    ),
    'imagecolorresolve' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
    ),
    'imagecolorresolvealpha' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
      'alpha' => 'int',
    ),
    'imagecolorsforindex' => 
    array (
      0 => 'array<array-key, mixed>',
      'image' => 'resource',
      'color' => 'int',
    ),
    'imagecolorstotal' => 
    array (
      0 => 'int',
      'image' => 'resource',
    ),
    'imagecolortransparent' => 
    array (
      0 => 'int',
      'image' => 'resource',
      'color=' => 'int',
    ),
    'imageconvolution' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'matrix' => 'array<array-key, mixed>',
      'divisor' => 'float',
      'offset' => 'float',
    ),
    'imagecopy' => 
    array (
      0 => 'bool',
      'dst_image' => 'resource',
      'src_image' => 'resource',
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
      'dst_image' => 'resource',
      'src_image' => 'resource',
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
      'dst_image' => 'resource',
      'src_image' => 'resource',
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
      0 => 'bool',
      'dst_image' => 'resource',
      'src_image' => 'resource',
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
      0 => 'bool',
      'dst_image' => 'resource',
      'src_image' => 'resource',
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
      0 => 'false|resource',
      'x_size' => 'int',
      'y_size' => 'int',
    ),
    'imagecreatefrombmp' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromgd' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromgd2' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromgd2part' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
      'srcx' => 'int',
      'srcy' => 'int',
      'width' => 'int',
      'height' => 'int',
    ),
    'imagecreatefromgif' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromjpeg' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefrompng' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromstring' => 
    array (
      0 => 'false|resource',
      'image' => 'string',
    ),
    'imagecreatefromwbmp' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromwebp' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromxbm' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatefromxpm' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagecreatetruecolor' => 
    array (
      0 => 'false|resource',
      'x_size' => 'int',
      'y_size' => 'int',
    ),
    'imagecrop' => 
    array (
      0 => 'false|resource',
      'im' => 'resource',
      'rect' => 'array<array-key, mixed>',
    ),
    'imagecropauto' => 
    array (
      0 => 'false|resource',
      'im' => 'resource',
      'mode=' => 'int',
      'threshold=' => 'float',
      'color=' => 'int',
    ),
    'imagedashedline' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x1' => 'int',
      'y1' => 'int',
      'x2' => 'int',
      'y2' => 'int',
      'color' => 'int',
    ),
    'imagedestroy' => 
    array (
      0 => 'bool',
      'image' => 'resource',
    ),
    'imageellipse' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'center_x' => 'int',
      'center_y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'color' => 'int',
    ),
    'imagefill' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x' => 'int',
      'y' => 'int',
      'color' => 'int',
    ),
    'imagefilledarc' => 
    array (
      0 => 'bool',
      'image' => 'resource',
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
      0 => 'bool',
      'image' => 'resource',
      'center_x' => 'int',
      'center_y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'color' => 'int',
    ),
    'imagefilledrectangle' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x1' => 'int',
      'y1' => 'int',
      'x2' => 'int',
      'y2' => 'int',
      'color' => 'int',
    ),
    'imagefilltoborder' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x' => 'int',
      'y' => 'int',
      'border_color' => 'int',
      'color' => 'int',
    ),
    'imageflip' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'mode' => 'int',
    ),
    'imagefontheight' => 
    array (
      0 => 'int',
      'font' => 'int',
    ),
    'imagefontwidth' => 
    array (
      0 => 'int',
      'font' => 'int',
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
      'image' => 'resource',
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
      0 => 'bool',
      'image' => 'resource',
      'input_gamma' => 'float',
      'output_gamma' => 'float',
    ),
    'imageinterlace' => 
    array (
      0 => 'false|int',
      'image' => 'resource',
      'enable=' => 'int',
    ),
    'imageistruecolor' => 
    array (
      0 => 'bool',
      'image' => 'resource',
    ),
    'imagelayereffect' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'effect' => 'int',
    ),
    'imageline' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x1' => 'int',
      'y1' => 'int',
      'x2' => 'int',
      'y2' => 'int',
      'color' => 'int',
    ),
    'imageloadfont' => 
    array (
      0 => 'false|int',
      'filename' => 'string',
    ),
    'imagepalettecopy' => 
    array (
      0 => 'void',
      'dst' => 'resource',
      'src' => 'resource',
    ),
    'imagepalettetotruecolor' => 
    array (
      0 => 'bool',
      'image' => 'resource',
    ),
    'imagerectangle' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x1' => 'int',
      'y1' => 'int',
      'x2' => 'int',
      'y2' => 'int',
      'color' => 'int',
    ),
    'imageresolution' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'image' => 'resource',
      'resolution_x=' => 'int',
      'resolution_y=' => 'int',
    ),
    'imagerotate' => 
    array (
      0 => 'false|resource',
      'src_im' => 'resource',
      'angle' => 'float',
      'bgdcolor' => 'int',
      'ignoretransparent=' => 'int',
    ),
    'imagesavealpha' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'enable' => 'bool',
    ),
    'imagescale' => 
    array (
      0 => 'false|resource',
      'im' => 'resource',
      'new_width' => 'int',
      'new_height=' => 'int',
      'method=' => 'int',
    ),
    'imagesetbrush' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'brush' => 'resource',
    ),
    'imagesetinterpolation' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'method=' => 'int',
    ),
    'imagesetpixel' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'x' => 'int',
      'y' => 'int',
      'color' => 'int',
    ),
    'imagesetthickness' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'thickness' => 'int',
    ),
    'imagesettile' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'tile' => 'resource',
    ),
    'imagestring' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'font' => 'int',
      'x' => 'int',
      'y' => 'int',
      'string' => 'string',
      'color' => 'int',
    ),
    'imagestringup' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'font' => 'int',
      'x' => 'int',
      'y' => 'int',
      'string' => 'string',
      'color' => 'int',
    ),
    'imagesx' => 
    array (
      0 => 'int',
      'image' => 'resource',
    ),
    'imagesy' => 
    array (
      0 => 'int',
      'image' => 'resource',
    ),
    'imagetruecolortopalette' => 
    array (
      0 => 'bool',
      'image' => 'resource',
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
    ),
    'imagettftext' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'image' => 'resource',
      'size' => 'float',
      'angle' => 'float',
      'x' => 'int',
      'y' => 'int',
      'color' => 'int',
      'font_filename' => 'string',
      'text' => 'string',
    ),
    'imagetypes' => 
    array (
      0 => 'int',
    ),
    'imagexbm' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'filename' => 'null|string',
      'foreground_color=' => 'int',
    ),
    'imagick::__tostring' => 
    array (
      0 => 'string',
    ),
    'imagick::adaptiveblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::adaptivesharpenimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::adaptivethresholdimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'offset' => 'int',
    ),
    'imagick::animateimages' => 
    array (
      0 => 'bool',
      'x_server' => 'string',
    ),
    'imagick::appendimages' => 
    array (
      0 => 'Imagick',
      'stack' => 'bool',
    ),
    'imagick::autoorient' => 
    array (
      0 => 'void',
    ),
    'imagick::averageimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::blueshiftimage' => 
    array (
      0 => 'bool',
      'factor=' => 'float',
    ),
    'imagick::blurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::charcoalimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'imagick::chopimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::clear' => 
    array (
      0 => 'bool',
    ),
    'imagick::clipimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::clippathimage' => 
    array (
      0 => 'bool',
      'pathname' => 'string',
      'inside' => 'bool',
    ),
    'imagick::clone' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::coalesceimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::commentimage' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'imagick::contrastimage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
    ),
    'imagick::contraststretchimage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
      'channel=' => 'int',
    ),
    'imagick::convolveimage' => 
    array (
      0 => 'bool',
      'kernel' => 'array<array-key, mixed>',
      'channel=' => 'int',
    ),
    'imagick::cropimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::cropthumbnailimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'legacy=' => 'bool',
    ),
    'imagick::current' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::cyclecolormapimage' => 
    array (
      0 => 'bool',
      'displace' => 'int',
    ),
    'imagick::decipherimage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'imagick::deconstructimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::deleteimageartifact' => 
    array (
      0 => 'bool',
      'artifact' => 'string',
    ),
    'imagick::deleteimageproperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
    ),
    'imagick::deskewimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'imagick::despeckleimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagick::displayimage' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'imagick::displayimages' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'imagick::edgeimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::embossimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'imagick::encipherimage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'imagick::enhanceimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::equalizeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::extentimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::flattenimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::flipimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::flopimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::forwardfouriertransformimage' => 
    array (
      0 => 'bool',
      'magnitude' => 'bool',
    ),
    'imagick::fximage' => 
    array (
      0 => 'Imagick',
      'expression' => 'string',
      'channel=' => 'int',
    ),
    'imagick::gammaimage' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::gaussianblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::getcolorspace' => 
    array (
      0 => 'int',
    ),
    'imagick::getcompression' => 
    array (
      0 => 'int',
    ),
    'imagick::getcompressionquality' => 
    array (
      0 => 'int',
    ),
    'imagick::getcopyright' => 
    array (
      0 => 'string',
    ),
    'imagick::getfeatures' => 
    array (
      0 => 'string',
    ),
    'imagick::getfilename' => 
    array (
      0 => 'string',
    ),
    'imagick::getfont' => 
    array (
      0 => 'string',
    ),
    'imagick::getformat' => 
    array (
      0 => 'string',
    ),
    'imagick::getgravity' => 
    array (
      0 => 'int',
    ),
    'imagick::gethdrienabled' => 
    array (
      0 => 'bool',
    ),
    'imagick::gethomeurl' => 
    array (
      0 => 'string',
    ),
    'imagick::getimage' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::getimagealphachannel' => 
    array (
      0 => 'bool',
    ),
    'imagick::getimagebackgroundcolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagick::getimageblob' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagebordercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagick::getimagechanneldepth' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'imagick::getimagechanneldistortion' => 
    array (
      0 => 'float',
      'reference' => 'Imagick',
      'channel' => 'int',
      'metric' => 'int',
    ),
    'imagick::getimagecolormapcolor' => 
    array (
      0 => 'ImagickPixel',
      'index' => 'int',
    ),
    'imagick::getimagecolors' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecolorspace' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompose' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompression' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompressionquality' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedelay' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedepth' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedispose' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagefilename' => 
    array (
      0 => 'string',
    ),
    'imagick::getimageformat' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagegamma' => 
    array (
      0 => 'float',
    ),
    'imagick::getimagegravity' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageheight' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageindex' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageinterlacescheme' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageinterpolatemethod' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageiterations' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagelength' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagemimetype' => 
    array (
      0 => 'string',
    ),
    'imagick::getimageorientation' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagepixelcolor' => 
    array (
      0 => 'ImagickPixel',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::getimageprofile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::getimageproperty' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::getimageregion' => 
    array (
      0 => 'Imagick',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::getimagerenderingintent' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagesblob' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagescene' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagesignature' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagesize' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagetickspersecond' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagetotalinkdensity' => 
    array (
      0 => 'float',
    ),
    'imagick::getimagetype' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageunits' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagevirtualpixelmethod' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagewidth' => 
    array (
      0 => 'int',
    ),
    'imagick::getinterlacescheme' => 
    array (
      0 => 'int',
    ),
    'imagick::getiteratorindex' => 
    array (
      0 => 'int',
    ),
    'imagick::getnumberimages' => 
    array (
      0 => 'int',
    ),
    'imagick::getoption' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'imagick::getpackagename' => 
    array (
      0 => 'string',
    ),
    'imagick::getpixeliterator' => 
    array (
      0 => 'ImagickPixelIterator',
    ),
    'imagick::getpixelregioniterator' => 
    array (
      0 => 'ImagickPixelIterator',
      'x' => 'int',
      'y' => 'int',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::getpointsize' => 
    array (
      0 => 'float',
    ),
    'imagick::getquantum' => 
    array (
      0 => 'int',
    ),
    'imagick::getregistry' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'imagick::getreleasedate' => 
    array (
      0 => 'string',
    ),
    'imagick::getresource' => 
    array (
      0 => 'int',
      'type' => 'int',
    ),
    'imagick::getresourcelimit' => 
    array (
      0 => 'int',
      'type' => 'int',
    ),
    'imagick::getsamplingfactors' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagick::getsizeoffset' => 
    array (
      0 => 'int',
    ),
    'imagick::haldclutimage' => 
    array (
      0 => 'bool',
      'clut' => 'Imagick',
      'channel=' => 'int',
    ),
    'imagick::hasnextimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::haspreviousimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::identifyimagetype' => 
    array (
      0 => 'int',
    ),
    'imagick::implodeimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::key' => 
    array (
      0 => 'int',
    ),
    'imagick::labelimage' => 
    array (
      0 => 'bool',
      'label' => 'string',
    ),
    'imagick::liquidrescaleimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'delta_x' => 'float',
      'rigidity' => 'float',
    ),
    'imagick::listregistry' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagick::localcontrastimage' => 
    array (
      0 => 'void',
      'radius' => 'float',
      'strength' => 'float',
    ),
    'imagick::magnifyimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::minifyimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::modulateimage' => 
    array (
      0 => 'bool',
      'brightness' => 'float',
      'saturation' => 'float',
      'hue' => 'float',
    ),
    'imagick::morphimages' => 
    array (
      0 => 'Imagick',
      'number_frames' => 'int',
    ),
    'imagick::motionblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
      'channel=' => 'int',
    ),
    'imagick::negateimage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'channel=' => 'int',
    ),
    'imagick::nextimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::normalizeimage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'imagick::oilpaintimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::optimizeimagelayers' => 
    array (
      0 => 'bool',
    ),
    'imagick::pingimage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::pingimageblob' => 
    array (
      0 => 'bool',
      'image' => 'string',
    ),
    'imagick::posterizeimage' => 
    array (
      0 => 'bool',
      'levels' => 'int',
      'dither' => 'bool',
    ),
    'imagick::previewimages' => 
    array (
      0 => 'bool',
      'preview' => 'int',
    ),
    'imagick::previousimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::queryfonts' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
    ),
    'imagick::raiseimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
      'raise' => 'bool',
    ),
    'imagick::randomthresholdimage' => 
    array (
      0 => 'bool',
      'low' => 'float',
      'high' => 'float',
      'channel=' => 'int',
    ),
    'imagick::readimage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::removeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::removeimageprofile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::resampleimage' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
      'filter' => 'int',
      'blur' => 'float',
    ),
    'imagick::resetimagepage' => 
    array (
      0 => 'bool',
      'page' => 'string',
    ),
    'imagick::resetiterator' => 
    array (
      0 => 'void',
    ),
    'imagick::rollimage' => 
    array (
      0 => 'bool',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::roundcorners' => 
    array (
      0 => 'bool',
      'x_rounding' => 'float',
      'y_rounding' => 'float',
      'stroke_width=' => 'float',
      'displace=' => 'float',
      'size_correction=' => 'float',
    ),
    'imagick::sampleimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::segmentimage' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
      'cluster_threshold' => 'float',
      'smooth_threshold' => 'float',
      'verbose=' => 'bool',
    ),
    'imagick::separateimagechannel' => 
    array (
      0 => 'bool',
      'channel' => 'int',
    ),
    'imagick::sepiatoneimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'imagick::setantialias' => 
    array (
      0 => 'void',
      'antialias' => 'bool',
    ),
    'imagick::setcolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::setcompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'imagick::setcompressionquality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'imagick::setfilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::setfirstiterator' => 
    array (
      0 => 'bool',
    ),
    'imagick::setfont' => 
    array (
      0 => 'bool',
      'font' => 'string',
    ),
    'imagick::setformat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'imagick::setgravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagick::setimagealpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'imagick::setimageblueprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimagechanneldepth' => 
    array (
      0 => 'bool',
      'channel' => 'int',
      'depth' => 'int',
    ),
    'imagick::setimagechannelmask' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'imagick::setimagecolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::setimagecompose' => 
    array (
      0 => 'bool',
      'compose' => 'int',
    ),
    'imagick::setimagecompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'imagick::setimagecompressionquality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'imagick::setimagedelay' => 
    array (
      0 => 'bool',
      'delay' => 'int',
    ),
    'imagick::setimagedepth' => 
    array (
      0 => 'bool',
      'depth' => 'int',
    ),
    'imagick::setimagedispose' => 
    array (
      0 => 'bool',
      'dispose' => 'int',
    ),
    'imagick::setimageextent' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::setimagefilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::setimageformat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'imagick::setimagegamma' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
    ),
    'imagick::setimagegravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagick::setimagegreenprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimageindex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'imagick::setimageinterpolatemethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'imagick::setimageiterations' => 
    array (
      0 => 'bool',
      'iterations' => 'int',
    ),
    'imagick::setimagematte' => 
    array (
      0 => 'bool',
      'matte' => 'bool',
    ),
    'imagick::setimageorientation' => 
    array (
      0 => 'bool',
      'orientation' => 'int',
    ),
    'imagick::setimagepage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::setimageprofile' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'profile' => 'string',
    ),
    'imagick::setimageproperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value' => 'string',
    ),
    'imagick::setimageredprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimagerenderingintent' => 
    array (
      0 => 'bool',
      'rendering_intent' => 'int',
    ),
    'imagick::setimageresolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'imagick::setimagescene' => 
    array (
      0 => 'bool',
      'scene' => 'int',
    ),
    'imagick::setimagetickspersecond' => 
    array (
      0 => 'bool',
      'ticks_per_second' => 'int',
    ),
    'imagick::setimagetype' => 
    array (
      0 => 'bool',
      'image_type' => 'int',
    ),
    'imagick::setimageunits' => 
    array (
      0 => 'bool',
      'units' => 'int',
    ),
    'imagick::setimagevirtualpixelmethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'imagick::setimagewhitepoint' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setiteratorindex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'imagick::setlastiterator' => 
    array (
      0 => 'bool',
    ),
    'imagick::setoption' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'imagick::setpage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::setpointsize' => 
    array (
      0 => 'bool',
      'point_size' => 'float',
    ),
    'imagick::setprogressmonitor' => 
    array (
      0 => 'bool',
      'callback' => 'callable',
    ),
    'imagick::setregistry' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'imagick::setresolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'imagick::setresourcelimit' => 
    array (
      0 => 'bool',
      'type' => 'int',
      'limit' => 'int',
    ),
    'imagick::setsize' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::setsizeoffset' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'offset' => 'int',
    ),
    'imagick::shadeimage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'azimuth' => 'float',
      'elevation' => 'float',
    ),
    'imagick::shadowimage' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
      'sigma' => 'float',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::sharpenimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::shaveimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::sigmoidalcontrastimage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
      'alpha' => 'float',
      'beta' => 'float',
      'channel=' => 'int',
    ),
    'imagick::sketchimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
    ),
    'imagick::solarizeimage' => 
    array (
      0 => 'bool',
      'threshold' => 'int',
    ),
    'imagick::spliceimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::spreadimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::stripimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::swirlimage' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagick::thresholdimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'imagick::transformimagecolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::transposeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::transverseimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::trimimage' => 
    array (
      0 => 'bool',
      'fuzz' => 'float',
    ),
    'imagick::uniqueimagecolors' => 
    array (
      0 => 'bool',
    ),
    'imagick::unsharpmaskimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'amount' => 'float',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'imagick::valid' => 
    array (
      0 => 'bool',
    ),
    'imagick::waveimage' => 
    array (
      0 => 'bool',
      'amplitude' => 'float',
      'length' => 'float',
    ),
    'imagick::writeimages' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'adjoin' => 'bool',
    ),
    'imagickdraw::annotation' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'text' => 'string',
    ),
    'imagickdraw::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::clone' => 
    array (
      0 => 'ImagickDraw',
    ),
    'imagickdraw::comment' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'imagickdraw::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::getbordercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getclippath' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::getcliprule' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getclipunits' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getdensity' => 
    array (
      0 => 'null|string',
    ),
    'imagickdraw::getfillcolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getfillopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getfillrule' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfont' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::getfontfamily' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::getfontresolution' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickdraw::getfontsize' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getfontstretch' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfontstyle' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfontweight' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getgravity' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokeantialias' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::getstrokecolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getstrokedasharray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickdraw::getstrokedashoffset' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokelinecap' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokelinejoin' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokemiterlimit' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokeopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokewidth' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextalignment' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextantialias' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::gettextdecoration' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextdirection' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextencoding' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::gettextinterlinespacing' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextinterwordspacing' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextkerning' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextundercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getvectorgraphics' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::pathclose' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pathcurvetoabsolute' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbeziersmoothabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbeziersmoothrelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetorelative' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetosmoothabsolute' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetosmoothrelative' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathfinish' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pathlinetoabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetohorizontalabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'imagickdraw::pathlinetohorizontalrelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'imagickdraw::pathlinetorelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetoverticalabsolute' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetoverticalrelative' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'imagickdraw::pathmovetoabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathmovetorelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathstart' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::point' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pop' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::popclippath' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::popdefs' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::poppattern' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::push' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pushclippath' => 
    array (
      0 => 'bool',
      'clip_mask_id' => 'string',
    ),
    'imagickdraw::pushdefs' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pushpattern' => 
    array (
      0 => 'bool',
      'pattern_id' => 'string',
      'x' => 'float',
      'y' => 'float',
      'width' => 'float',
      'height' => 'float',
    ),
    'imagickdraw::render' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::resetvectorgraphics' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::rotate' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::scale' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::setbordercolor' => 
    array (
      0 => 'bool',
      'color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setclippath' => 
    array (
      0 => 'bool',
      'clip_mask' => 'string',
    ),
    'imagickdraw::setfillpatternurl' => 
    array (
      0 => 'bool',
      'fill_url' => 'string',
    ),
    'imagickdraw::setfont' => 
    array (
      0 => 'bool',
      'font_name' => 'string',
    ),
    'imagickdraw::setfontfamily' => 
    array (
      0 => 'bool',
      'font_family' => 'string',
    ),
    'imagickdraw::setfontresolution' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::setfontstyle' => 
    array (
      0 => 'bool',
      'style' => 'int',
    ),
    'imagickdraw::setgravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagickdraw::setopacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'imagickdraw::setstrokedashoffset' => 
    array (
      0 => 'bool',
      'dash_offset' => 'float',
    ),
    'imagickdraw::setstrokelinecap' => 
    array (
      0 => 'bool',
      'linecap' => 'int',
    ),
    'imagickdraw::setstrokelinejoin' => 
    array (
      0 => 'bool',
      'linejoin' => 'int',
    ),
    'imagickdraw::setstrokemiterlimit' => 
    array (
      0 => 'bool',
      'miterlimit' => 'int',
    ),
    'imagickdraw::setstrokepatternurl' => 
    array (
      0 => 'bool',
      'stroke_url' => 'string',
    ),
    'imagickdraw::settextantialias' => 
    array (
      0 => 'bool',
      'antialias' => 'bool',
    ),
    'imagickdraw::settextdecoration' => 
    array (
      0 => 'bool',
      'decoration' => 'int',
    ),
    'imagickdraw::settextdirection' => 
    array (
      0 => 'bool',
      'direction' => 'int',
    ),
    'imagickdraw::settextencoding' => 
    array (
      0 => 'bool',
      'encoding' => 'string',
    ),
    'imagickdraw::settextinterlinespacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'imagickdraw::settextinterwordspacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'imagickdraw::settextkerning' => 
    array (
      0 => 'bool',
      'kerning' => 'float',
    ),
    'imagickdraw::settextundercolor' => 
    array (
      0 => 'bool',
      'under_color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setvectorgraphics' => 
    array (
      0 => 'bool',
      'xml' => 'string',
    ),
    'imagickdraw::skewx' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::skewy' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::translate' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickpixel::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickpixel::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickpixel::getcolorasstring' => 
    array (
      0 => 'string',
    ),
    'imagickpixel::getcolorcount' => 
    array (
      0 => 'int',
    ),
    'imagickpixel::getcolorquantum' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixel::getcolorvalue' => 
    array (
      0 => 'float',
      'color' => 'int',
    ),
    'imagickpixel::getindex' => 
    array (
      0 => 'int',
    ),
    'imagickpixel::setcolor' => 
    array (
      0 => 'bool',
      'color' => 'string',
    ),
    'imagickpixel::setcolorvalue' => 
    array (
      0 => 'bool',
      'color' => 'int',
      'value' => 'float',
    ),
    'imagickpixel::sethsl' => 
    array (
      0 => 'bool',
      'hue' => 'float',
      'saturation' => 'float',
      'luminosity' => 'float',
    ),
    'imagickpixeliterator::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::current' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixeliterator::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::getcurrentiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixeliterator::getiteratorrow' => 
    array (
      0 => 'int',
    ),
    'imagickpixeliterator::getnextiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixeliterator::getpreviousiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixeliterator::key' => 
    array (
      0 => 'int',
    ),
    'imagickpixeliterator::resetiterator' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::setiteratorfirstrow' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::setiteratorlastrow' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::setiteratorrow' => 
    array (
      0 => 'bool',
      'row' => 'int',
    ),
    'imagickpixeliterator::synciterator' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::valid' => 
    array (
      0 => 'bool',
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
    'inflate_add' => 
    array (
      0 => 'false|string',
      'context' => 'resource',
      'data' => 'string',
      'flush_mode=' => 'int',
    ),
    'inflate_get_read_len' => 
    array (
      0 => 'int',
      'context' => 'resource',
    ),
    'inflate_get_status' => 
    array (
      0 => 'int',
      'context' => 'resource',
    ),
    'inflate_init' => 
    array (
      0 => 'false|resource',
      'encoding' => 'int',
      'options=' => 'array<array-key, mixed>',
    ),
    'ini_alter' => 
    array (
      0 => 'false|string',
      'option' => 'string',
      'value' => 'string',
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
    'ini_restore' => 
    array (
      0 => 'void',
      'option' => 'string',
    ),
    'ini_set' => 
    array (
      0 => 'false|string',
      'option' => 'string',
      'value' => 'string',
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
    'intlbreakiterator::geterrormessage' => 
    array (
      0 => 'string',
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
      0 => 'bool',
      'calendar' => 'IntlCalendar',
      'field=' => 'int|null',
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
    'intlcal_get_available_locales' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'intlcal_get_keyword_values_for_locale' => 
    array (
      0 => 'IntlIterator|false',
      'keyword' => 'string',
      'locale' => 'string',
      'onlyCommon' => 'bool',
    ),
    'intlcal_get_maximum' => 
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
    'intlcal_set_time' => 
    array (
      0 => 'bool',
      'calendar' => 'IntlCalendar',
      'timestamp' => 'float',
    ),
    'intlcal_to_date_time' => 
    array (
      0 => 'DateTime|false',
      'calendar' => 'IntlCalendar',
    ),
    'intlcalendar::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intlcalendar::gettype' => 
    array (
      0 => 'string',
    ),
    'intlcodepointbreakiterator::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intldateformatter::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intlexception::__tostring' => 
    array (
      0 => 'string',
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
    'intlexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'intlgregoriancalendar::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intlgregoriancalendar::gettype' => 
    array (
      0 => 'string',
    ),
    'intliterator::key' => 
    array (
      0 => 'string',
    ),
    'intlrulebasedbreakiterator::getbinaryrules' => 
    array (
      0 => 'string',
    ),
    'intlrulebasedbreakiterator::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intlrulebasedbreakiterator::getrules' => 
    array (
      0 => 'string',
    ),
    'intltimezone::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'intltimezone::getid' => 
    array (
      0 => 'string',
    ),
    'intltimezone::gettzdataversion' => 
    array (
      0 => 'string',
    ),
    'intltz_create_time_zone' => 
    array (
      0 => 'IntlTimeZone|null',
      'timezoneId' => 'string',
    ),
    'intltz_from_date_time_zone' => 
    array (
      0 => 'IntlTimeZone|null',
      'timezone' => 'DateTimeZone',
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
    'intltz_get_raw_offset' => 
    array (
      0 => 'int',
      'timezone' => 'IntlTimeZone',
    ),
    'intltz_has_same_rules' => 
    array (
      0 => 'bool',
      'timezone' => 'IntlTimeZone',
      'other' => 'IntlTimeZone',
    ),
    'intltz_use_daylight_time' => 
    array (
      0 => 'bool',
      'timezone' => 'IntlTimeZone',
    ),
    'invalidargumentexception::__tostring' => 
    array (
      0 => 'string',
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
    'is_dir' => 
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
    'is_finite' => 
    array (
      0 => 'bool',
      'num' => 'float',
    ),
    'is_infinite' => 
    array (
      0 => 'bool',
      'num' => 'float',
    ),
    'is_link' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'is_nan' => 
    array (
      0 => 'bool',
      'num' => 'float',
    ),
    'is_readable' => 
    array (
      0 => 'bool',
      'filename' => 'string',
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
    'iterator_to_array' => 
    array (
      0 => 'array<array-key, mixed>',
      'iterator' => 'Traversable',
      'preserve_keys=' => 'bool',
    ),
    'jpeg2wbmp' => 
    array (
      0 => 'bool',
      'jpegname' => 'string',
      'wbmpname' => 'string',
      'dest_height' => 'int',
      'dest_width' => 'int',
      'threshold' => 'int',
    ),
    'json_last_error' => 
    array (
      0 => 'int',
    ),
    'json_last_error_msg' => 
    array (
      0 => 'string',
    ),
    'jsonexception::__tostring' => 
    array (
      0 => 'string',
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
    'jsonexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'key' => 
    array (
      0 => 'int|null|string',
      'array' => 'array<array-key, mixed>|object',
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
    'lengthexception::__tostring' => 
    array (
      0 => 'string',
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
    'lengthexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'libxml_get_last_error' => 
    array (
      0 => 'LibXMLError|false',
    ),
    'libxml_use_internal_errors' => 
    array (
      0 => 'bool',
      'use_errors=' => 'bool',
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
    'locale::composelocale' => 
    array (
      0 => 'string',
      'subtags' => 'array<array-key, mixed>',
    ),
    'locale::getdefault' => 
    array (
      0 => 'string',
    ),
    'locale::getdisplaylanguage' => 
    array (
      0 => 'string',
      'locale' => 'string',
      'displayLocale=' => 'string',
    ),
    'locale::getdisplayname' => 
    array (
      0 => 'string',
      'locale' => 'string',
      'displayLocale=' => 'string',
    ),
    'locale::getdisplayregion' => 
    array (
      0 => 'string',
      'locale' => 'string',
      'displayLocale=' => 'string',
    ),
    'locale::getdisplayscript' => 
    array (
      0 => 'string',
      'locale' => 'string',
      'displayLocale=' => 'string',
    ),
    'locale::getdisplayvariant' => 
    array (
      0 => 'string',
      'locale' => 'string',
      'displayLocale=' => 'string',
    ),
    'locale::getprimarylanguage' => 
    array (
      0 => 'string',
      'locale' => 'string',
    ),
    'locale::getregion' => 
    array (
      0 => 'string',
      'locale' => 'string',
    ),
    'locale::getscript' => 
    array (
      0 => 'string',
      'locale' => 'string',
    ),
    'locale_accept_from_http' => 
    array (
      0 => 'false|string',
      'header' => 'string',
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
    'locale_lookup' => 
    array (
      0 => 'null|string',
      'languageTag' => 'array<array-key, mixed>',
      'locale' => 'string',
      'canonicalize=' => 'bool',
      'defaultLocale=' => 'null|string',
    ),
    'locale_parse' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'locale' => 'string',
    ),
    'localeconv' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'localtime' => 
    array (
      0 => 'array<array-key, mixed>',
      'timestamp=' => 'int',
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
    'logicexception::__tostring' => 
    array (
      0 => 'string',
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
    'logicexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'mb_check_encoding' => 
    array (
      0 => 'bool',
      'value=' => 'array<array-key, mixed>|string',
      'encoding=' => 'string',
    ),
    'mb_convert_case' => 
    array (
      0 => 'string',
      'string' => 'string',
      'mode' => 'int',
      'encoding=' => 'string',
    ),
    'mb_convert_kana' => 
    array (
      0 => 'string',
      'string' => 'string',
      'mode=' => 'string',
      'encoding=' => 'string',
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
      'encoding=' => 'string',
    ),
    'mb_detect_encoding' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'encodings=' => 'mixed',
      'strict=' => 'bool',
    ),
    'mb_encode_mimeheader' => 
    array (
      0 => 'string',
      'string' => 'string',
      'charset=' => 'string',
      'transfer_encoding=' => 'string',
      'newline=' => 'string',
      'indent=' => 'int',
    ),
    'mb_encode_numericentity' => 
    array (
      0 => 'string',
      'string' => 'string',
      'map' => 'array<array-key, mixed>',
      'encoding=' => 'string',
      'hex=' => 'bool',
    ),
    'mb_ereg_match' => 
    array (
      0 => 'bool',
      'pattern' => 'string',
      'string' => 'string',
      'options=' => 'string',
    ),
    'mb_ereg_replace' => 
    array (
      0 => 'false|string',
      'pattern' => 'string',
      'replacement' => 'string',
      'string' => 'string',
      'options=' => 'string',
    ),
    'mb_ereg_replace_callback' => 
    array (
      0 => 'false|null|string',
      'pattern' => 'string',
      'callback' => 'callable',
      'string' => 'string',
      'options=' => 'string',
    ),
    'mb_ereg_search' => 
    array (
      0 => 'bool',
      'pattern=' => 'string',
      'options=' => 'string',
    ),
    'mb_ereg_search_getpos' => 
    array (
      0 => 'int',
    ),
    'mb_ereg_search_init' => 
    array (
      0 => 'bool',
      'string' => 'string',
      'pattern=' => 'string',
      'options=' => 'string',
    ),
    'mb_ereg_search_setpos' => 
    array (
      0 => 'bool',
      'offset' => 'int',
    ),
    'mb_eregi_replace' => 
    array (
      0 => 'false|string',
      'pattern' => 'string',
      'replacement' => 'string',
      'string' => 'string',
      'options=' => 'string',
    ),
    'mb_get_info' => 
    array (
      0 => 'array<array-key, mixed>|false|int|string',
      'type=' => 'string',
    ),
    'mb_http_input' => 
    array (
      0 => 'false|string',
      'type=' => 'string',
    ),
    'mb_http_output' => 
    array (
      0 => 'bool|string',
      'encoding=' => 'string',
    ),
    'mb_internal_encoding' => 
    array (
      0 => 'bool|string',
      'encoding=' => 'string',
    ),
    'mb_language' => 
    array (
      0 => 'bool|string',
      'language=' => 'string',
    ),
    'mb_ord' => 
    array (
      0 => 'false|int',
      'string' => 'string',
      'encoding=' => 'string',
    ),
    'mb_output_handler' => 
    array (
      0 => 'string',
      'string' => 'string',
      'status' => 'int',
    ),
    'mb_preferred_mime_name' => 
    array (
      0 => 'false|string',
      'encoding' => 'string',
    ),
    'mb_regex_encoding' => 
    array (
      0 => 'bool|string',
      'encoding=' => 'string',
    ),
    'mb_regex_set_options' => 
    array (
      0 => 'string',
      'options=' => 'string',
    ),
    'mb_scrub' => 
    array (
      0 => 'string',
      'string' => 'string',
      'encoding=' => 'string',
    ),
    'mb_send_mail' => 
    array (
      0 => 'bool',
      'to' => 'string',
      'subject' => 'string',
      'message' => 'string',
      'additional_headers=' => 'array<array-key, mixed>|string',
      'additional_params=' => 'string',
    ),
    'mb_strcut' => 
    array (
      0 => 'string',
      'string' => 'string',
      'start' => 'int',
      'length=' => 'int|null',
      'encoding=' => 'string',
    ),
    'mb_strimwidth' => 
    array (
      0 => 'string',
      'string' => 'string',
      'start' => 'int',
      'width' => 'int',
      'trim_marker=' => 'string',
      'encoding=' => 'string',
    ),
    'mb_stripos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'encoding=' => 'string',
    ),
    'mb_stristr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'before_needle=' => 'bool',
      'encoding=' => 'string',
    ),
    'mb_strpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'encoding=' => 'string',
    ),
    'mb_strrchr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'before_needle=' => 'bool',
      'encoding=' => 'string',
    ),
    'mb_strrichr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'before_needle=' => 'bool',
      'encoding=' => 'string',
    ),
    'mb_strripos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'encoding=' => 'string',
    ),
    'mb_strrpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'encoding=' => 'string',
    ),
    'mb_strstr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'string',
      'before_needle=' => 'bool',
      'encoding=' => 'string',
    ),
    'mb_strtoupper' => 
    array (
      0 => 'string',
      'string' => 'string',
      'encoding=' => 'string',
    ),
    'mb_strwidth' => 
    array (
      0 => 'int',
      'string' => 'string',
      'encoding=' => 'string',
    ),
    'mb_substitute_character' => 
    array (
      0 => 'bool|int|string',
      'substitute_character=' => 'mixed',
    ),
    'mb_substr' => 
    array (
      0 => 'string',
      'string' => 'string',
      'start' => 'int',
      'length=' => 'int|null',
      'encoding=' => 'string',
    ),
    'mb_substr_count' => 
    array (
      0 => 'int',
      'haystack' => 'string',
      'needle' => 'string',
      'encoding=' => 'string',
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
      0 => 'string',
    ),
    'metaphone' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'max_phonemes=' => 'int',
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
    'mktime' => 
    array (
      0 => 'false|int',
      'hour=' => 'int',
      'minute=' => 'int',
      'second=' => 'int',
      'month=' => 'int',
      'day=' => 'int',
      'year=' => 'int',
    ),
    'mongodb\\bson\\binary::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\binary::getdata' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\binary::gettype' => 
    array (
      0 => 'int',
    ),
    'mongodb\\bson\\binary::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\binary::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\dbpointer::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\dbpointer::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\dbpointer::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\decimal128::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\decimal128::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\decimal128::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\document::__tostring' => 
    array (
      0 => 'string',
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
    'mongodb\\bson\\document::getiterator' => 
    array (
      0 => 'MongoDB\\BSON\\Iterator',
    ),
    'mongodb\\bson\\document::has' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'mongodb\\bson\\document::serialize' => 
    array (
      0 => 'string',
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
    'mongodb\\bson\\document::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\fromjson' => 
    array (
      0 => 'string',
      'json' => 'string',
    ),
    'mongodb\\bson\\fromphp' => 
    array (
      0 => 'string',
      'value' => 'array<array-key, mixed>|object',
    ),
    'mongodb\\bson\\int64::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\int64::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\int64::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
    'mongodb\\bson\\javascript::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\javascript::getcode' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\javascript::getscope' => 
    array (
      0 => 'null|object',
    ),
    'mongodb\\bson\\javascript::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\javascript::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\maxkey::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\maxkey::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\minkey::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\minkey::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\objectid::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\objectid::gettimestamp' => 
    array (
      0 => 'int',
    ),
    'mongodb\\bson\\objectid::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\objectid::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\packedarray::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\packedarray::fromphp' => 
    array (
      0 => 'MongoDB\\BSON\\PackedArray',
      'value' => 'array<array-key, mixed>',
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
    'mongodb\\bson\\packedarray::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\packedarray::tophp' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'typeMap=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\bson\\packedarray::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\regex::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\regex::getflags' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\regex::getpattern' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\regex::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\regex::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\symbol::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\symbol::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\symbol::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\timestamp::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\timestamp::getincrement' => 
    array (
      0 => 'int',
    ),
    'mongodb\\bson\\timestamp::gettimestamp' => 
    array (
      0 => 'int',
    ),
    'mongodb\\bson\\timestamp::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\timestamp::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
      0 => 'array<array-key, mixed>|object',
      'bson' => 'string',
      'typemap=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\bson\\torelaxedextendedjson' => 
    array (
      0 => 'string',
      'bson' => 'string',
    ),
    'mongodb\\bson\\undefined::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\undefined::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\undefined::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\bson\\utcdatetime::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\utcdatetime::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\bson\\utcdatetime::todatetime' => 
    array (
      0 => 'DateTime',
    ),
    'mongodb\\bson\\utcdatetime::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
    'mongodb\\driver\\bulkwrite::update' => 
    array (
      0 => 'void',
      'filter' => 'array<array-key, mixed>|object',
      'newObj' => 'array<array-key, mixed>|object',
      'updateOptions=' => 'array<array-key, mixed>|null',
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
    'mongodb\\driver\\clientencryption::deletekey' => 
    array (
      0 => 'object',
      'keyId' => 'MongoDB\\BSON\\Binary',
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
    'mongodb\\driver\\cursor::current' => 
    array (
      0 => 'array<array-key, mixed>|null|object',
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
    'mongodb\\driver\\cursorid::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\cursorid::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\cursorid::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\driver\\exception\\authenticationexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\bulkwriteexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\commandexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\commandexception::getresultdocument' => 
    array (
      0 => 'object',
    ),
    'mongodb\\driver\\exception\\connectionexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\connectiontimeoutexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\encryptionexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\executiontimeoutexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\invalidargumentexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\logicexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\runtimeexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\runtimeexception::haserrorlabel' => 
    array (
      0 => 'bool',
      'errorLabel' => 'string',
    ),
    'mongodb\\driver\\exception\\serverexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\sslconnectionexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\unexpectedvalueexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\writeexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\exception\\writeexception::getwriteresult' => 
    array (
      0 => 'MongoDB\\Driver\\WriteResult',
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
      'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::executecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::executequery' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'namespace' => 'string',
      'query' => 'MongoDB\\Driver\\Query',
      'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::executereadcommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::executereadwritecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::executewritecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
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
    'mongodb\\driver\\monitoring\\commandfailedevent::getcommandname' => 
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
    'mongodb\\driver\\monitoring\\commandfailedevent::getoperationid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandfailedevent::getreply' => 
    array (
      0 => 'object',
    ),
    'mongodb\\driver\\monitoring\\commandfailedevent::getrequestid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandfailedevent::getserver' => 
    array (
      0 => 'MongoDB\\Driver\\Server',
    ),
    'mongodb\\driver\\monitoring\\commandfailedevent::getserverconnectionid' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\monitoring\\commandfailedevent::getserviceid' => 
    array (
      0 => 'MongoDB\\BSON\\ObjectId|null',
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
    'mongodb\\driver\\monitoring\\commandstartedevent::getoperationid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandstartedevent::getrequestid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandstartedevent::getserver' => 
    array (
      0 => 'MongoDB\\Driver\\Server',
    ),
    'mongodb\\driver\\monitoring\\commandstartedevent::getserverconnectionid' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\monitoring\\commandstartedevent::getserviceid' => 
    array (
      0 => 'MongoDB\\BSON\\ObjectId|null',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getcommandname' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getdurationmicros' => 
    array (
      0 => 'int',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getoperationid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getreply' => 
    array (
      0 => 'object',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getrequestid' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getserver' => 
    array (
      0 => 'MongoDB\\Driver\\Server',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getserverconnectionid' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\monitoring\\commandsucceededevent::getserviceid' => 
    array (
      0 => 'MongoDB\\BSON\\ObjectId|null',
    ),
    'mongodb\\driver\\monitoring\\removesubscriber' => 
    array (
      0 => 'void',
      'subscriber' => 'MongoDB\\Driver\\Monitoring\\Subscriber',
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
    'mongodb\\driver\\monitoring\\topologyclosedevent::gettopologyid' => 
    array (
      0 => 'MongoDB\\BSON\\ObjectId',
    ),
    'mongodb\\driver\\monitoring\\topologyopeningevent::gettopologyid' => 
    array (
      0 => 'MongoDB\\BSON\\ObjectId',
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
    'mongodb\\driver\\readconcern::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\readconcern::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
    'mongodb\\driver\\readpreference::getmode' => 
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
    'mongodb\\driver\\readpreference::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\readpreference::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
    ),
    'mongodb\\driver\\server::executebulkwrite' => 
    array (
      0 => 'MongoDB\\Driver\\WriteResult',
      'namespace' => 'string',
      'bulkWrite' => 'MongoDB\\Driver\\BulkWrite',
      'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\server::executecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\server::executequery' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'namespace' => 'string',
      'query' => 'MongoDB\\Driver\\Query',
      'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\server::executereadcommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\server::executereadwritecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
      'db' => 'string',
      'command' => 'MongoDB\\Driver\\Command',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\server::executewritecommand' => 
    array (
      0 => 'MongoDB\\Driver\\Cursor',
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
    'mongodb\\driver\\serverapi::bsonserialize' => 
    array (
      0 => 'stdClass',
    ),
    'mongodb\\driver\\serverapi::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\serverapi::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
    'mongodb\\driver\\writeconcern::serialize' => 
    array (
      0 => 'string',
    ),
    'mongodb\\driver\\writeconcern::unserialize' => 
    array (
      0 => 'void',
      'data' => 'string',
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
    'mongodb\\driver\\writeresult::getdeletedcount' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\writeresult::geterrorreplies' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'mongodb\\driver\\writeresult::getinsertedcount' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\writeresult::getmatchedcount' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\writeresult::getmodifiedcount' => 
    array (
      0 => 'int|null',
    ),
    'mongodb\\driver\\writeresult::getserver' => 
    array (
      0 => 'MongoDB\\Driver\\Server',
    ),
    'mongodb\\driver\\writeresult::getupsertedcount' => 
    array (
      0 => 'int|null',
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
    'mt_srand' => 
    array (
      0 => 'void',
      'seed=' => 'int',
      'mode=' => 'int',
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
    ),
    'number_format\'1' => 
    array (
      0 => 'string',
      'num' => 'float',
      'decimals' => 'int',
      'decimal_separator' => 'null|string',
      'thousands_separator' => 'null|string',
    ),
    'numberformatter::geterrormessage' => 
    array (
      0 => 'string',
    ),
    'numberformatter::getlocale' => 
    array (
      0 => 'string',
      'type=' => 'int',
    ),
    'numfmt_create' => 
    array (
      0 => 'NumberFormatter|null',
      'locale' => 'string',
      'style' => 'int',
      'pattern=' => 'string',
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
      'enable=' => 'int',
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
    'opcache_reset' => 
    array (
      0 => 'bool',
    ),
    'openssl_cipher_iv_length' => 
    array (
      0 => 'false|int',
      'cipher_algo' => 'string',
    ),
    'openssl_csr_export_to_file' => 
    array (
      0 => 'bool',
      'csr' => 'resource|string',
      'output_filename' => 'string',
      'no_text=' => 'bool',
    ),
    'openssl_csr_get_public_key' => 
    array (
      0 => 'false|resource',
      'csr' => 'resource|string',
      'short_names=' => 'bool',
    ),
    'openssl_csr_get_subject' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'csr' => 'resource|string',
      'short_names=' => 'bool',
    ),
    'openssl_decrypt' => 
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
    'openssl_dh_compute_key' => 
    array (
      0 => 'false|string',
      'public_key' => 'string',
      'private_key' => 'resource',
    ),
    'openssl_digest' => 
    array (
      0 => 'false|string',
      'data' => 'string',
      'digest_algo' => 'string',
      'binary=' => 'bool',
    ),
    'openssl_error_string' => 
    array (
      0 => 'false|string',
    ),
    'openssl_free_key' => 
    array (
      0 => 'void',
      'key' => 'resource',
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
    'openssl_get_md_methods' => 
    array (
      0 => 'array<array-key, mixed>',
      'aliases=' => 'bool',
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
    'openssl_pkcs7_verify' => 
    array (
      0 => 'bool|int',
      'input_filename' => 'string',
      'flags' => 'int',
      'signers_certificates_filename=' => 'string',
      'ca_info=' => 'array<array-key, mixed>',
      'untrusted_certificates_filename=' => 'string',
      'content=' => 'string',
      'output_filename=' => 'string',
    ),
    'openssl_pkey_free' => 
    array (
      0 => 'void',
      'key' => 'resource',
    ),
    'openssl_pkey_get_details' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'resource',
    ),
    'openssl_pkey_new' => 
    array (
      0 => 'false|resource',
      'options=' => 'array<array-key, mixed>',
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
      0 => 'null|string',
      'private_key' => 'resource',
      'challenge' => 'string',
      'digest_algo=' => 'int',
    ),
    'openssl_spki_verify' => 
    array (
      0 => 'bool',
      'spki' => 'string',
    ),
    'openssl_x509_checkpurpose' => 
    array (
      0 => 'bool|int',
      'certificate' => 'resource|string',
      'purpose' => 'int',
      'ca_info=' => 'array<array-key, mixed>',
      'untrusted_certificates_file=' => 'string',
    ),
    'openssl_x509_export_to_file' => 
    array (
      0 => 'bool',
      'certificate' => 'resource|string',
      'output_filename' => 'string',
      'no_text=' => 'bool',
    ),
    'openssl_x509_fingerprint' => 
    array (
      0 => 'false|string',
      'certificate' => 'resource|string',
      'digest_algo=' => 'string',
      'binary=' => 'bool',
    ),
    'openssl_x509_free' => 
    array (
      0 => 'void',
      'certificate' => 'resource',
    ),
    'openssl_x509_parse' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'certificate' => 'resource|string',
      'short_names=' => 'bool',
    ),
    'openssl_x509_read' => 
    array (
      0 => 'false|resource',
      'certificate' => 'resource|string',
    ),
    'outofboundsexception::__tostring' => 
    array (
      0 => 'string',
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
    'outofboundsexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'outofrangeexception::__tostring' => 
    array (
      0 => 'string',
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
    'overflowexception::__tostring' => 
    array (
      0 => 'string',
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
    'overflowexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'parse_url' => 
    array (
      0 => 'array<array-key, mixed>|false|int|null|string',
      'url' => 'string',
      'component=' => 'int',
    ),
    'parseerror::__tostring' => 
    array (
      0 => 'string',
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
    'parseerror::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'password_get_info' => 
    array (
      0 => 'array<array-key, mixed>',
      'hash' => 'string',
    ),
    'password_hash' => 
    array (
      0 => 'false|string',
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
    'pcntl_alarm' => 
    array (
      0 => 'int',
      'seconds' => 'int',
    ),
    'pcntl_async_signals' => 
    array (
      0 => 'bool',
      'enable=' => 'bool',
    ),
    'pcntl_errno' => 
    array (
      0 => 'int',
    ),
    'pcntl_fork' => 
    array (
      0 => 'int',
    ),
    'pcntl_get_last_error' => 
    array (
      0 => 'int',
    ),
    'pcntl_setpriority' => 
    array (
      0 => 'bool',
      'priority' => 'int',
      'process_id=' => 'int',
      'mode=' => 'int',
    ),
    'pcntl_signal_dispatch' => 
    array (
      0 => 'bool',
    ),
    'pcntl_strerror' => 
    array (
      0 => 'string',
      'error_code' => 'int',
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
    'pdo::lastinsertid' => 
    array (
      0 => 'string',
      'name=' => 'null|string',
    ),
    'pdo_drivers' => 
    array (
      0 => 'array<array-key, mixed>',
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
    'pdoexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'pg_unescape_bytea' => 
    array (
      0 => 'string',
      'string' => 'string',
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
      'index=' => 'string',
      'webIndex=' => 'string',
    ),
    'phar::getpath' => 
    array (
      0 => 'string',
    ),
    'phar::getstub' => 
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
    'phar::getversion' => 
    array (
      0 => 'string',
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
    'phar::running' => 
    array (
      0 => 'string',
      'returnPhar=' => 'bool',
    ),
    'phar::unlinkarchive' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'phar::webphar' => 
    array (
      0 => 'void',
      'alias=' => 'null|string',
      'index=' => 'null|string',
      'fileNotFoundScript=' => 'string',
      'mimeTypes=' => 'array<array-key, mixed>',
      'rewrite=' => 'callable',
    ),
    'pharfileinfo::getcontent' => 
    array (
      0 => 'string',
    ),
    'php_ini_loaded_file' => 
    array (
      0 => 'false|string',
    ),
    'php_ini_scanned_files' => 
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
    'phpversion' => 
    array (
      0 => 'false|string',
      'extension=' => 'string',
    ),
    'pi' => 
    array (
      0 => 'float',
    ),
    'png2wbmp' => 
    array (
      0 => 'bool',
      'pngname' => 'string',
      'wbmpname' => 'string',
      'dest_height' => 'int',
      'dest_width' => 'int',
      'threshold' => 'int',
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
    'preg_quote' => 
    array (
      0 => 'string',
      'str' => 'string',
      'delimiter=' => 'null|string',
    ),
    'proc_nice' => 
    array (
      0 => 'bool',
      'priority' => 'int',
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
    'random_int' => 
    array (
      0 => 'int',
      'min' => 'int',
      'max' => 'int',
    ),
    'rangeexception::__tostring' => 
    array (
      0 => 'string',
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
    'read_exif_data' => 
    array (
      0 => 'array<array-key, mixed>',
      'filename' => 'string',
      'sections_needed=' => 'string',
      'sub_arrays=' => 'bool',
      'read_thumbnail=' => 'bool',
    ),
    'readgzfile' => 
    array (
      0 => 'false|int',
      'filename' => 'string',
      'use_include_path=' => 'int',
    ),
    'readline' => 
    array (
      0 => 'false|string',
      'prompt=' => 'null|string',
    ),
    'readline_add_history' => 
    array (
      0 => 'bool',
      'prompt' => 'string',
    ),
    'readline_callback_handler_install' => 
    array (
      0 => 'bool',
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
      0 => 'bool',
    ),
    'readline_completion_function' => 
    array (
      0 => 'bool',
      'callback' => 'callable',
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
      'filename=' => 'string',
    ),
    'readline_redisplay' => 
    array (
      0 => 'void',
    ),
    'readline_write_history' => 
    array (
      0 => 'bool',
      'filename=' => 'string',
    ),
    'realpath_cache_get' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'realpath_cache_size' => 
    array (
      0 => 'int',
    ),
    'recursivearrayiterator::serialize' => 
    array (
      0 => 'string',
    ),
    'recursivecachingiterator::__tostring' => 
    array (
      0 => 'string',
    ),
    'recursivecachingiterator::offsetget' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'recursivedirectoryiterator::__tostring' => 
    array (
      0 => 'string',
    ),
    'recursivedirectoryiterator::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'recursivedirectoryiterator::getextension' => 
    array (
      0 => 'string',
    ),
    'recursivedirectoryiterator::getfilename' => 
    array (
      0 => 'string',
    ),
    'recursivedirectoryiterator::getlinktarget' => 
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
      0 => 'string',
    ),
    'recursivedirectoryiterator::key' => 
    array (
      0 => 'string',
    ),
    'recursiveregexiterator::getregex' => 
    array (
      0 => 'string',
    ),
    'recursivetreeiterator::current' => 
    array (
      0 => 'string',
    ),
    'recursivetreeiterator::getentry' => 
    array (
      0 => 'string',
    ),
    'recursivetreeiterator::getpostfix' => 
    array (
      0 => 'string',
    ),
    'recursivetreeiterator::getprefix' => 
    array (
      0 => 'string',
    ),
    'recursivetreeiterator::key' => 
    array (
      0 => 'string',
    ),
    'redis::clearlasterror' => 
    array (
      0 => 'bool',
    ),
    'redis::close' => 
    array (
      0 => 'bool',
    ),
    'redis::getlasterror' => 
    array (
      0 => 'null|string',
    ),
    'redis::getmode' => 
    array (
      0 => 'int',
    ),
    'redis::gettimeout' => 
    array (
      0 => 'false|float',
    ),
    'redis::isconnected' => 
    array (
      0 => 'bool',
    ),
    'redis::lastsave' => 
    array (
      0 => 'int',
    ),
    'redisarray::mset' => 
    array (
      0 => 'bool',
      'pairs' => 'array<array-key, mixed>',
    ),
    'rediscluster::_masters' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'rediscluster::clearlasterror' => 
    array (
      0 => 'bool',
    ),
    'rediscluster::getlasterror' => 
    array (
      0 => 'null|string',
    ),
    'rediscluster::getmode' => 
    array (
      0 => 'int',
    ),
    'reflection::export' => 
    array (
      0 => 'null|string',
      'r' => 'reflector',
      'return=' => 'bool',
    ),
    'reflectionclass::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionclass::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionclass::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object|string',
      'return=' => 'bool',
    ),
    'reflectionclass::getnamespacename' => 
    array (
      0 => 'string',
    ),
    'reflectionclass::getshortname' => 
    array (
      0 => 'string',
    ),
    'reflectionclassconstant::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionclassconstant::export' => 
    array (
      0 => 'string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionclassconstant::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionextension::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionextension::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionextension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionextension::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionfunction::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionfunction::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionfunction::getnamespacename' => 
    array (
      0 => 'string',
    ),
    'reflectionfunction::getshortname' => 
    array (
      0 => 'string',
    ),
    'reflectionfunctionabstract::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionfunctionabstract::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionfunctionabstract::export' => 
    array (
      0 => 'null|string',
    ),
    'reflectionfunctionabstract::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionfunctionabstract::getnamespacename' => 
    array (
      0 => 'string',
    ),
    'reflectionfunctionabstract::getshortname' => 
    array (
      0 => 'string',
    ),
    'reflectiongenerator::getexecutingfile' => 
    array (
      0 => 'string',
    ),
    'reflectionmethod::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionmethod::export' => 
    array (
      0 => 'null|string',
      'class' => 'string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionmethod::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionmethod::getnamespacename' => 
    array (
      0 => 'string',
    ),
    'reflectionmethod::getshortname' => 
    array (
      0 => 'string',
    ),
    'reflectionnamedtype::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionnamedtype::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionobject::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionobject::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object',
      'return=' => 'bool',
    ),
    'reflectionobject::getname' => 
    array (
      0 => 'string',
    ),
    'reflectionobject::getnamespacename' => 
    array (
      0 => 'string',
    ),
    'reflectionobject::getshortname' => 
    array (
      0 => 'string',
    ),
    'reflectionparameter::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionparameter::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionparameter::export' => 
    array (
      0 => 'null|string',
      'function' => 'string',
      'parameter' => 'string',
      'return=' => 'bool',
    ),
    'reflectionproperty::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionproperty::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionproperty::export' => 
    array (
      0 => 'null|string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'reflectionproperty::getname' => 
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
    'reflectiontype::isbuiltin' => 
    array (
      0 => 'bool',
    ),
    'reflectionzendextension::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionzendextension::__tostring' => 
    array (
      0 => 'string',
    ),
    'reflectionzendextension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
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
    'regexiterator::getregex' => 
    array (
      0 => 'string',
    ),
    'resourcebundle::geterrormessage' => 
    array (
      0 => 'string',
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
    'rtrim' => 
    array (
      0 => 'string',
      'string' => 'string',
      'characters=' => 'string',
    ),
    'runtimeexception::__tostring' => 
    array (
      0 => 'string',
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
    'runtimeexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'session_abort' => 
    array (
      0 => 'bool',
    ),
    'session_cache_expire' => 
    array (
      0 => 'false|int',
      'value=' => 'int',
    ),
    'session_cache_limiter' => 
    array (
      0 => 'false|string',
      'value=' => 'string',
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
    'session_id' => 
    array (
      0 => 'false|string',
      'id=' => 'string',
    ),
    'session_module_name' => 
    array (
      0 => 'false|string',
      'module=' => 'string',
    ),
    'session_name' => 
    array (
      0 => 'false|string',
      'name=' => 'string',
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
      'path=' => 'string',
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
    'sessionhandler::create_sid' => 
    array (
      0 => 'string',
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
    'simplexmlelement::__tostring' => 
    array (
      0 => 'string',
    ),
    'simplexmlelement::getname' => 
    array (
      0 => 'string',
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
    'sodium_crypto_aead_aes256gcm_is_available' => 
    array (
      0 => 'bool',
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
    'sodium_crypto_auth' => 
    array (
      0 => 'string',
      'message' => 'string',
      'key' => 'string',
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
    'sodium_crypto_kdf_derive_from_key' => 
    array (
      0 => 'string',
      'subkey_length' => 'int',
      'subkey_id' => 'int',
      'context' => 'string',
      'key' => 'string',
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
    'sodium_crypto_secretbox' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'string',
      'key' => 'string',
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
    'sodium_crypto_shorthash' => 
    array (
      0 => 'string',
      'message' => 'string',
      'key' => 'string',
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
    'sodium_memcmp' => 
    array (
      0 => 'int',
      'string1' => 'string',
      'string2' => 'string',
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
    'soundex' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'spl_autoload' => 
    array (
      0 => 'void',
      'class' => 'string',
      'file_extensions=' => 'string',
    ),
    'spl_autoload_call' => 
    array (
      0 => 'void',
      'class' => 'string',
    ),
    'spl_autoload_extensions' => 
    array (
      0 => 'string',
      'file_extensions=' => 'string',
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
    'spldoublylinkedlist::serialize' => 
    array (
      0 => 'string',
    ),
    'splfileinfo::__tostring' => 
    array (
      0 => 'string',
    ),
    'splfileinfo::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'splfileinfo::getextension' => 
    array (
      0 => 'string',
    ),
    'splfileinfo::getfilename' => 
    array (
      0 => 'string',
    ),
    'splfileinfo::getpath' => 
    array (
      0 => 'string',
    ),
    'splfileinfo::getpathname' => 
    array (
      0 => 'string',
    ),
    'splfileobject::__tostring' => 
    array (
      0 => 'string',
    ),
    'splfileobject::fgets' => 
    array (
      0 => 'false|string',
    ),
    'splfileobject::fgetss' => 
    array (
      0 => 'false|string',
      'allowable_tags=' => 'string',
    ),
    'splfileobject::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'splfileobject::getcurrentline' => 
    array (
      0 => 'false|string',
    ),
    'splfileobject::getextension' => 
    array (
      0 => 'string',
    ),
    'splfileobject::getfilename' => 
    array (
      0 => 'string',
    ),
    'splfileobject::getpath' => 
    array (
      0 => 'string',
    ),
    'splfileobject::getpathname' => 
    array (
      0 => 'string',
    ),
    'splfixedarray::current' => 
    array (
      0 => 'mixed',
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
    'splobjectstorage::gethash' => 
    array (
      0 => 'string',
      'object' => 'object',
    ),
    'splobjectstorage::serialize' => 
    array (
      0 => 'string',
    ),
    'splqueue::serialize' => 
    array (
      0 => 'string',
    ),
    'splstack::serialize' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::__tostring' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::fgets' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::fgetss' => 
    array (
      0 => 'string',
      'allowable_tags=' => 'string',
    ),
    'spltempfileobject::getbasename' => 
    array (
      0 => 'string',
      'suffix=' => 'string',
    ),
    'spltempfileobject::getcurrentline' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::getextension' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::getfilename' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::getpath' => 
    array (
      0 => 'string',
    ),
    'spltempfileobject::getpathname' => 
    array (
      0 => 'string',
    ),
    'sqlite3::escapestring' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'sqlite3::lasterrormsg' => 
    array (
      0 => 'string',
    ),
    'sqlite3result::columnname' => 
    array (
      0 => 'string',
      'column' => 'int',
    ),
    'sqlite3stmt::getsql' => 
    array (
      0 => 'string',
      'expand=' => 'bool',
    ),
    'sqrt' => 
    array (
      0 => 'float',
      'num' => 'float',
    ),
    'srand' => 
    array (
      0 => 'void',
      'seed=' => 'int',
      'mode=' => 'int',
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
      'needle' => 'int|string',
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
      'length=' => 'int',
    ),
    'stream_filter_register' => 
    array (
      0 => 'bool',
      'filter_name' => 'string',
      'class' => 'string',
    ),
    'stream_get_filters' => 
    array (
      0 => 'array<array-key, mixed>',
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
      'timestamp=' => 'int',
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
      'needle' => 'int|string',
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
      'needle' => 'int|string',
      'before_needle=' => 'bool',
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
      'needle' => 'int|string',
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
      'needle' => 'int|string',
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
      'needle' => 'int|string',
      'offset=' => 'int',
    ),
    'strrpos' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'int|string',
      'offset=' => 'int',
    ),
    'strspn' => 
    array (
      0 => 'int',
      'string' => 'string',
      'characters' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'strstr' => 
    array (
      0 => 'false|string',
      'haystack' => 'string',
      'needle' => 'int|string',
      'before_needle=' => 'bool',
    ),
    'strtotime' => 
    array (
      0 => 'false|int',
      'datetime' => 'string',
      'baseTimestamp=' => 'int',
    ),
    'strtoupper' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'substr' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'offset' => 'int',
      'length=' => 'int',
    ),
    'substr_compare' => 
    array (
      0 => 'false|int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset' => 'int',
      'length=' => 'int',
      'case_insensitive=' => 'bool',
    ),
    'substr_count' => 
    array (
      0 => 'int',
      'haystack' => 'string',
      'needle' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
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
    'time_sleep_until' => 
    array (
      0 => 'bool',
      'timestamp' => 'float',
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
      0 => 'false|int',
      'object' => 'DateTimeZone',
      'datetime' => 'DateTimeInterface',
    ),
    'timezone_open' => 
    array (
      0 => 'DateTimeZone|false',
      'timezone' => 'string',
    ),
    'timezone_version_get' => 
    array (
      0 => 'string',
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
      'mtime=' => 'int',
      'atime=' => 'int',
    ),
    'trait_exists' => 
    array (
      0 => 'bool',
      'trait' => 'string',
      'autoload=' => 'bool',
    ),
    'transliterator::geterrormessage' => 
    array (
      0 => 'string',
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
    'transliterator_transliterate' => 
    array (
      0 => 'false|string',
      'transliterator' => 'Transliterator|string',
      'string' => 'string',
      'start=' => 'int',
      'end=' => 'int',
    ),
    'trim' => 
    array (
      0 => 'string',
      'string' => 'string',
      'characters=' => 'string',
    ),
    'typeerror::__tostring' => 
    array (
      0 => 'string',
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
    'typeerror::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'ucfirst' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'uconverter::convert' => 
    array (
      0 => 'string',
      'str' => 'string',
      'reverse=' => 'bool',
    ),
    'uconverter::reasontext' => 
    array (
      0 => 'string',
      'reason' => 'int',
    ),
    'uconverter::transcode' => 
    array (
      0 => 'string',
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
    'umask' => 
    array (
      0 => 'int',
      'mask=' => 'int',
    ),
    'underflowexception::__tostring' => 
    array (
      0 => 'string',
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
    'underflowexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'unexpectedvalueexception::__tostring' => 
    array (
      0 => 'string',
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
    'unexpectedvalueexception::gettraceasstring' => 
    array (
      0 => 'string',
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
    'user_error' => 
    array (
      0 => 'bool',
      'message' => 'string',
      'error_level=' => 'int',
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
      0 => 'false|int',
      'parser' => 'resource',
    ),
    'xml_get_current_column_number' => 
    array (
      0 => 'false|int',
      'parser' => 'resource',
    ),
    'xml_get_current_line_number' => 
    array (
      0 => 'false|int',
      'parser' => 'resource',
    ),
    'xml_get_error_code' => 
    array (
      0 => 'false|int',
      'parser' => 'resource',
    ),
    'xml_parse' => 
    array (
      0 => 'int',
      'parser' => 'resource',
      'data' => 'string',
      'is_final=' => 'bool',
    ),
    'xml_parser_create' => 
    array (
      0 => 'resource',
      'encoding=' => 'string',
    ),
    'xml_parser_create_ns' => 
    array (
      0 => 'resource',
      'encoding=' => 'string',
      'separator=' => 'string',
    ),
    'xml_parser_free' => 
    array (
      0 => 'bool',
      'parser' => 'resource',
    ),
    'xml_parser_get_option' => 
    array (
      0 => 'int|string',
      'parser' => 'resource',
      'option' => 'int',
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
    'xmlwriter::outputmemory' => 
    array (
      0 => 'string',
      'flush=' => 'bool',
    ),
    'xmlwriter_end_attribute' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_cdata' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_comment' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_document' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_dtd' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_dtd_attlist' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_dtd_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_dtd_entity' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_end_pi' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_flush' => 
    array (
      0 => 'false|int|string',
      'writer' => 'resource',
      'empty=' => 'bool',
    ),
    'xmlwriter_full_end_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_open_memory' => 
    array (
      0 => 'false|resource',
    ),
    'xmlwriter_open_uri' => 
    array (
      0 => 'false|resource',
      'uri' => 'string',
    ),
    'xmlwriter_output_memory' => 
    array (
      0 => 'string',
      'writer' => 'resource',
      'flush=' => 'bool',
    ),
    'xmlwriter_set_indent' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'enable' => 'bool',
    ),
    'xmlwriter_set_indent_string' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'indentation' => 'string',
    ),
    'xmlwriter_start_attribute' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
    ),
    'xmlwriter_start_attribute_ns' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'prefix' => 'string',
      'name' => 'string',
      'namespace' => 'null|string',
    ),
    'xmlwriter_start_cdata' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_start_comment' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
    ),
    'xmlwriter_start_document' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'version=' => 'null|string',
      'encoding=' => 'null|string',
      'standalone=' => 'null|string',
    ),
    'xmlwriter_start_dtd' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'qualifiedName' => 'string',
      'publicId=' => 'null|string',
      'systemId=' => 'null|string',
    ),
    'xmlwriter_start_dtd_attlist' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
    ),
    'xmlwriter_start_dtd_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'qualifiedName' => 'string',
    ),
    'xmlwriter_start_dtd_entity' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'isParam' => 'bool',
    ),
    'xmlwriter_start_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
    ),
    'xmlwriter_start_element_ns' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'prefix' => 'null|string',
      'name' => 'string',
      'namespace' => 'null|string',
    ),
    'xmlwriter_start_pi' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'target' => 'string',
    ),
    'xmlwriter_text' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'content' => 'string',
    ),
    'xmlwriter_write_attribute' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'value' => 'string',
    ),
    'xmlwriter_write_attribute_ns' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'prefix' => 'string',
      'name' => 'string',
      'namespace' => 'null|string',
      'value' => 'string',
    ),
    'xmlwriter_write_cdata' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'content' => 'string',
    ),
    'xmlwriter_write_comment' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'content' => 'string',
    ),
    'xmlwriter_write_dtd' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'publicId=' => 'null|string',
      'systemId=' => 'null|string',
      'content=' => 'null|string',
    ),
    'xmlwriter_write_dtd_attlist' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'content' => 'string',
    ),
    'xmlwriter_write_dtd_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'content' => 'string',
    ),
    'xmlwriter_write_dtd_entity' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'content' => 'string',
      'isParam' => 'bool',
      'publicId' => 'string',
      'systemId' => 'string',
      'notationData' => 'string',
    ),
    'xmlwriter_write_element' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'name' => 'string',
      'content' => 'null|string',
    ),
    'xmlwriter_write_element_ns' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'prefix' => 'null|string',
      'name' => 'string',
      'namespace' => 'string',
      'content' => 'null|string',
    ),
    'xmlwriter_write_pi' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'target' => 'string',
      'content' => 'string',
    ),
    'xmlwriter_write_raw' => 
    array (
      0 => 'bool',
      'writer' => 'resource',
      'content' => 'string',
    ),
    'zend_version' => 
    array (
      0 => 'string',
    ),
    'ziparchive::getstatusstring' => 
    array (
      0 => 'false|string',
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
  ),
);