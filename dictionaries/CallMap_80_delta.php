<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'DateTime::createFromInterface' => 
    array (
      0 => 'static',
      'object' => 'DateTimeInterface',
    ),
    'DateTimeImmutable::createFromInterface' => 
    array (
      0 => 'static',
      'object' => 'DateTimeInterface',
    ),
    'PhpToken::getTokenName' => 
    array (
      0 => 'null|string',
    ),
    'PhpToken::is' => 
    array (
      0 => 'bool',
      'kind' => 'array<array-key, int|string>|int|string',
    ),
    'PhpToken::isIgnorable' => 
    array (
      0 => 'bool',
    ),
    'PhpToken::tokenize' => 
    array (
      0 => 'list<PhpToken>',
      'code' => 'string',
      'flags=' => 'int',
    ),
    'ReflectionClass::getAttributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'ReflectionClassConstant::getAttributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'ReflectionFunctionAbstract::getAttributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'ReflectionParameter::getAttributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'ReflectionProperty::getAttributes' => 
    array (
      0 => 'list<ReflectionAttribute>',
      'name=' => 'null|string',
      'flags=' => 'int',
    ),
    'ReflectionProperty::getDefaultValue' => 
    array (
      0 => 'mixed',
    ),
    'ReflectionProperty::hasDefaultValue' => 
    array (
      0 => 'bool',
    ),
    'ReflectionProperty::isPromoted' => 
    array (
      0 => 'bool',
    ),
    'ReflectionUnionType::getTypes' => 
    array (
      0 => 'list<ReflectionNamedType>',
    ),
    'SplFixedArray::getIterator' => 
    array (
      0 => 'Iterator',
    ),
    'WeakMap::count' => 
    array (
      0 => 'int',
    ),
    'WeakMap::getIterator' => 
    array (
      0 => 'Iterator',
    ),
    'WeakMap::offsetExists' => 
    array (
      0 => 'bool',
      'object' => 'object',
    ),
    'WeakMap::offsetGet' => 
    array (
      0 => 'mixed',
      'object' => 'object',
    ),
    'WeakMap::offsetSet' => 
    array (
      0 => 'void',
      'object' => 'object',
      'value' => 'mixed',
    ),
    'WeakMap::offsetUnset' => 
    array (
      0 => 'void',
      'object' => 'object',
    ),
    'fdiv' => 
    array (
      0 => 'float',
      'num1' => 'float',
      'num2' => 'float',
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
    'imagegetinterpolation' => 
    array (
      0 => 'int',
      'image' => 'GdImage',
    ),
    'str_contains' => 
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
    'str_starts_with' => 
    array (
      0 => 'bool',
      'haystack' => 'string',
      'needle' => 'string',
    ),
  ),
  'changed' => 
  array (
    'Collator::getStrength' => 
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
    'CURLFile::__construct' => 
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
    'DateTime::format' => 
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
    'DateTime::getTimestamp' => 
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
    'DateTimeInterface::getTimestamp' => 
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
    'DateTimeZone::getOffset' => 
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
    'DateTimeZone::listIdentifiers' => 
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
    'Directory::close' => 
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
    'Directory::read' => 
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
    'Directory::rewind' => 
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
    'DirectoryIterator::getFileInfo' => 
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
    'DirectoryIterator::getPathInfo' => 
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
    'DirectoryIterator::openFile' => 
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
    'DOMDocument::getElementsByTagNameNS' => 
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
    'DOMDocument::load' => 
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
    'DOMDocument::loadXML' => 
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
    'DOMDocument::loadHTML' => 
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
    'DOMDocument::loadHTMLFile' => 
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
    'DOMImplementation::createDocument' => 
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
    'ErrorException::__construct' => 
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
    'FilesystemIterator::getFileInfo' => 
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
    'FilesystemIterator::getPathInfo' => 
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
    'FilesystemIterator::openFile' => 
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
    'GlobIterator::getFileInfo' => 
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
    'GlobIterator::getPathInfo' => 
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
    'GlobIterator::openFile' => 
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
    'IntlDateFormatter::__construct' => 
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
    'IntlDateFormatter::create' => 
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
    'IntlDateFormatter::format' => 
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
    'IntlDateFormatter::formatObject' => 
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
    'IntlDateFormatter::getCalendar' => 
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
    'IntlDateFormatter::getCalendarObject' => 
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
    'IntlDateFormatter::getDateType' => 
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
    'IntlDateFormatter::getLocale' => 
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
    'IntlDateFormatter::getPattern' => 
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
    'IntlDateFormatter::getTimeType' => 
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
    'IntlDateFormatter::getTimeZoneId' => 
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
    'IntlDateFormatter::localtime' => 
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
    'IntlDateFormatter::parse' => 
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
    'IntlDateFormatter::setCalendar' => 
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
    'IntlDateFormatter::setLenient' => 
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
    'IntlDateFormatter::setTimeZone' => 
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
    'IntlTimeZone::getIDForWindowsID' => 
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
    'Locale::getDisplayLanguage' => 
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
    'Locale::getDisplayName' => 
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
    'Locale::getDisplayRegion' => 
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
    'Locale::getDisplayScript' => 
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
    'Locale::getDisplayVariant' => 
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
    'NumberFormatter::__construct' => 
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
    'NumberFormatter::create' => 
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
    'PDOStatement::debugDumpParams' => 
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
    'PDOStatement::errorCode' => 
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
    'PDOStatement::execute' => 
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
    'PDOStatement::fetch' => 
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
    'PDOStatement::fetchAll' => 
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
    'PDOStatement::fetchColumn' => 
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
    'PDOStatement::setFetchMode' => 
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
    'Phar::addFile' => 
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
    'Phar::buildFromIterator' => 
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
    'Phar::createDefaultStub' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'index=' => 'string',
        'webIndex=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'index=' => 'null|string',
        'webIndex=' => 'null|string',
      ),
    ),
    'Phar::compress' => 
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
    'Phar::convertToData' => 
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
    'Phar::convertToExecutable' => 
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
    'Phar::decompress' => 
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
    'Phar::getMetadata' => 
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
    'Phar::setDefaultStub' => 
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
    'Phar::setSignatureAlgorithm' => 
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
    'Phar::webPhar' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'alias=' => 'null|string',
        'index=' => 'null|string',
        'fileNotFoundScript=' => 'string',
        'mimeTypes=' => 'array<array-key, mixed>',
        'rewrite=' => 'callable',
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
    'PharData::addFile' => 
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
    'PharData::buildFromIterator' => 
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
    'PharData::compress' => 
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
    'PharData::convertToData' => 
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
    'PharData::convertToExecutable' => 
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
    'PharData::decompress' => 
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
    'PharData::setDefaultStub' => 
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
    'PharData::setSignatureAlgorithm' => 
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
    'PharFileInfo::getMetadata' => 
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
    'PharFileInfo::isCompressed' => 
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
    'RecursiveDirectoryIterator::getFileInfo' => 
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
    'RecursiveDirectoryIterator::getPathInfo' => 
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
    'RecursiveDirectoryIterator::openFile' => 
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
    'RecursiveIteratorIterator::getSubIterator' => 
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
    'RecursiveTreeIterator::getSubIterator' => 
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
    'ReflectionClass::getConstants' => 
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
    'ReflectionClass::getReflectionConstants' => 
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
    'ReflectionClass::newInstanceArgs' => 
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
    'ReflectionMethod::getClosure' => 
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
    'ReflectionObject::getConstants' => 
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
    'ReflectionObject::getReflectionConstants' => 
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
    'ReflectionObject::newInstanceArgs' => 
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
    'ReflectionProperty::getValue' => 
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
    'ReflectionProperty::isInitialized' => 
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
    'SplFileInfo::getFileInfo' => 
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
    'SplFileInfo::getPathInfo' => 
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
    'SplFileInfo::openFile' => 
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
    'SplFileObject::getFileInfo' => 
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
    'SplFileObject::getPathInfo' => 
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
    'SplFileObject::openFile' => 
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
    'SplTempFileObject::getFileInfo' => 
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
    'SplTempFileObject::getPathInfo' => 
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
    'SplTempFileObject::openFile' => 
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
    'tidy::parseFile' => 
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
    'tidy::parseString' => 
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
    'tidy::repairFile' => 
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
    'tidy::repairString' => 
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
    'XMLWriter::flush' => 
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
    'SimpleXMLElement::asXML' => 
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
    'SimpleXMLElement::saveXML' => 
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
    'SoapClient::__doRequest' => 
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
    'SplFileObject::fgets' => 
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
    'SplFileObject::getCurrentLine' => 
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
    'XMLReader::next' => 
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
    'XMLWriter::startAttributeNs' => 
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
    'XMLWriter::writeAttributeNs' => 
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
    'XMLWriter::writeDtdEntity' => 
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
    'ZipArchive::getStatusString' => 
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
    'ZipArchive::setEncryptionIndex' => 
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
    'ZipArchive::setEncryptionName' => 
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
    'array_column' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
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
    'array_diff' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
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
        'array' => 'array<array-key, mixed>',
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
        'array' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
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
    'array_intersect' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
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
        'array' => 'array<array-key, mixed>',
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
        'array' => 'array<array-key, mixed>',
        '...arrays' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
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
    'crypt' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
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
        'string' => 'string',
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
        'postfilename=' => 'string',
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
    'curl_unescape' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ch' => 'resource',
        'string' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'handle' => 'CurlHandle',
        'string' => 'string',
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
    'date_diff' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval|false',
        'baseObject' => 'DateTimeInterface',
        'targetObject' => 'DateTimeInterface',
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
        'datetime' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
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
        'timestamp' => 'int',
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
        'timestamp' => 'int',
        'returnFormat=' => 'int',
        'latitude=' => 'float',
        'longitude=' => 'float',
        'zenith=' => 'float',
        'utcOffset=' => 'float',
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
        'timestamp' => 'int',
        'returnFormat=' => 'int',
        'latitude=' => 'float',
        'longitude=' => 'float',
        'zenith=' => 'float',
        'utcOffset=' => 'float',
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
    'date_time_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'hour' => 'int',
        'minute' => 'int',
        'second=' => 'int',
        'microsecond=' => 'int',
      ),
      'new' => 
      array (
        0 => 'DateTime',
        'object' => 'DateTime',
        'hour' => 'int',
        'minute' => 'int',
        'second=' => 'int',
        'microsecond=' => 'int',
      ),
    ),
    'date_timestamp_set' => 
    array (
      'old' => 
      array (
        0 => 'DateTime|false',
        'object' => 'DateTime',
        'timestamp' => 'int',
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
    'deflate_add' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'context' => 'resource',
        'data' => 'string',
        'flush_mode=' => 'int',
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
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'DeflateContext|false',
        'encoding' => 'int',
        'options=' => 'array<array-key, mixed>',
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
    'error_log' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'message_type=' => 'int',
        'destination=' => 'string',
        'additional_headers=' => 'string',
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
        'error_level=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'error_level=' => 'int|null',
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
        'minute=' => 'int',
        'second=' => 'int',
        'month=' => 'int',
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
    'gmp_binomial' => 
    array (
      'old' => 
      array (
        0 => 'GMP|false',
        'n' => 'GMP|int|string',
        'k' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'n' => 'GMP|int|string',
        'k' => 'int',
      ),
    ),
    'gmp_export' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'num' => 'GMP|int|string',
        'word_size=' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'num' => 'GMP|int|string',
        'word_size=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'gmp_import' => 
    array (
      'old' => 
      array (
        0 => 'GMP|false',
        'data' => 'string',
        'word_size=' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'GMP',
        'data' => 'string',
        'word_size=' => 'int',
        'flags=' => 'int',
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
    'hash_init' => 
    array (
      'old' => 
      array (
        0 => 'HashContext|false',
        'algo' => 'string',
        'flags=' => 'int',
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
    'html_entity_decode' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'flags=' => 'int',
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
        'flags=' => 'int',
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
    'iconv_mime_decode' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'mode=' => 'int',
        'encoding=' => 'string',
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
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'headers' => 'string',
        'mode=' => 'int',
        'encoding=' => 'null|string',
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
    'iconv_strpos' => 
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
    'iconv_strrpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'encoding=' => 'string',
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
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int',
        'encoding=' => 'string',
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
    'ignore_user_abort' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'enable=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
        'enable=' => 'bool|null',
      ),
    ),
    'imageaffine' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'src' => 'resource',
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
    'imagealphablending' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'enable' => 'bool',
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
        'image' => 'resource',
        'enable' => 'bool',
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
        'image' => 'resource',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'start_angle' => 'int',
        'end_angle' => 'int',
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
    'imagechar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
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
        'image' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
        'color' => 'int',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image1' => 'resource',
        'image2' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
    'imagecolorsforindex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'image' => 'resource',
        'color' => 'int',
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
        'image' => 'resource',
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
        'image' => 'resource',
        'color=' => 'int',
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
        'image' => 'resource',
        'matrix' => 'array<array-key, mixed>',
        'divisor' => 'float',
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
        'dst_image' => 'resource',
        'src_image' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
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
        'srcx' => 'int',
        'srcy' => 'int',
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
        'image' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
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
        'image' => 'resource',
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
        'image' => 'resource',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
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
        'image' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
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
        'image' => 'resource',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
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
    'imagefilledrectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
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
        'image' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'border_color' => 'int',
        'color' => 'int',
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
    'imageflip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'mode' => 'int',
      ),
    ),
    'imagefttext' => 
    array (
      'old' => 
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
        'image' => 'resource',
        'input_gamma' => 'float',
        'output_gamma' => 'float',
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
    'imageinterlace' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'image' => 'resource',
        'enable=' => 'int',
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
        'image' => 'resource',
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
    'imagelayereffect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
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
        'image' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
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
        'image' => 'resource',
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
    'imagerectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
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
        'image' => 'resource',
        'resolution_x=' => 'int',
        'resolution_y=' => 'int',
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
        'src_im' => 'resource',
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
        'image' => 'resource',
        'enable' => 'bool',
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
        'method=' => 'int',
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
        'image' => 'resource',
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
    'imagesetinterpolation' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
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
        'image' => 'resource',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
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
    'imagesetthickness' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
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
        'image' => 'resource',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
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
        'image' => 'resource',
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
        'image' => 'resource',
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
        'image' => 'resource',
        'dither' => 'bool',
        'num_colors' => 'int',
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
        'font_filename' => 'string',
        'string' => 'string',
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
        'image' => 'resource',
        'size' => 'float',
        'angle' => 'float',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
        'font_filename' => 'string',
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
    'imagexbm' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'resource',
        'filename' => 'null|string',
        'foreground_color=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'filename' => 'null|string',
        'foreground_color=' => 'int|null',
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
    'inflate_add' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'context' => 'resource',
        'data' => 'string',
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
        'context' => 'resource',
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
        'context' => 'resource',
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
    'localtime' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'timestamp=' => 'int',
        'associative=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'timestamp=' => 'int|null',
        'associative=' => 'bool',
      ),
    ),
    'mb_check_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value=' => 'array<array-key, mixed>|string',
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
    'mb_convert_case' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
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
    'mb_convert_kana' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'mode=' => 'string',
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
        'map' => 'array<array-key, mixed>',
        'encoding=' => 'string',
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
        'string' => 'string',
        'encodings=' => 'mixed',
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
        'string' => 'string',
        'charset=' => 'string',
        'transfer_encoding=' => 'string',
        'newline=' => 'string',
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
        'map' => 'array<array-key, mixed>',
        'encoding=' => 'string',
        'hex=' => 'bool',
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
    'mb_ereg_match' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pattern' => 'string',
        'string' => 'string',
        'options=' => 'string',
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
        'options=' => 'string',
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
        'options=' => 'string',
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
        'options=' => 'string',
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
        'options=' => 'string',
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
    'mb_eregi_replace' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'pattern' => 'string',
        'replacement' => 'string',
        'string' => 'string',
        'options=' => 'string',
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
        'string' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'string' => 'string',
        'encoding=' => 'null|string',
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
        'string' => 'string',
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
        'additional_params=' => 'string',
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
    'mb_strcut' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
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
        'string' => 'string',
        'start' => 'int',
        'width' => 'int',
        'trim_marker=' => 'string',
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
        'before_needle=' => 'bool',
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
        'before_needle=' => 'bool',
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
        'before_needle=' => 'bool',
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
        'before_needle=' => 'bool',
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
    'mb_strtoupper' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
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
        'string' => 'string',
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
        'substitute_character=' => 'mixed',
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
        'string' => 'string',
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
    'metaphone' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'max_phonemes=' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'max_phonemes=' => 'int',
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
    'mktime' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'hour=' => 'int',
        'minute=' => 'int',
        'second=' => 'int',
        'month=' => 'int',
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
    'number_format' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'num' => 'float',
        'decimals=' => 'int',
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
    'ob_implicit_flush' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'enable=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'enable=' => 'bool',
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
    'openssl_csr_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'csr' => 'resource|string',
        'output_filename' => 'string',
        'no_text=' => 'bool',
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
        'short_names=' => 'bool',
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
        'short_names=' => 'bool',
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
      ),
    ),
    'openssl_dh_compute_key' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'public_key' => 'string',
        'private_key' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'public_key' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey',
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
    'openssl_pkcs7_verify' => 
    array (
      'old' => 
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
    'openssl_pkey_new' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'options=' => 'array<array-key, mixed>',
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
    'openssl_spki_new' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'private_key' => 'resource',
        'challenge' => 'string',
        'digest_algo=' => 'int',
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
    'openssl_x509_checkpurpose' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'certificate' => 'resource|string',
        'purpose' => 'int',
        'ca_info=' => 'array<array-key, mixed>',
        'untrusted_certificates_file=' => 'string',
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
    'openssl_x509_export_to_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'certificate' => 'resource|string',
        'output_filename' => 'string',
        'no_text=' => 'bool',
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
        'certificate' => 'resource|string',
        'digest_algo=' => 'string',
        'binary=' => 'bool',
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
        'certificate' => 'resource',
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
        'certificate' => 'resource|string',
        'short_names=' => 'bool',
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
        'certificate' => 'resource|string',
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
    'pcntl_async_signals' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'enable=' => 'bool',
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
    'pcntl_setpriority' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'priority' => 'int',
        'process_id=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'priority' => 'int',
        'process_id=' => 'int|null',
        'mode=' => 'int',
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
    'session_cache_expire' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'value=' => 'int',
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
        'value=' => 'string',
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
    'socket_set_timeout' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'seconds' => 'int',
        'microseconds=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream' => 'resource',
        'seconds' => 'int',
        'microseconds=' => 'int',
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
    'spl_autoload' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class' => 'string',
        'file_extensions=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'class' => 'string',
        'file_extensions=' => 'null|string',
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
    'strchr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
        'before_needle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
    ),
    'strcspn' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string' => 'string',
        'characters' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
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
    'stristr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'int|string',
        'before_needle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
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
        'string' => 'string',
        'characters' => 'string',
        'offset=' => 'int',
        'length=' => 'int',
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
        'before_needle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
      ),
    ),
    'strtotime' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'datetime' => 'string',
        'baseTimestamp=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'datetime' => 'string',
        'baseTimestamp=' => 'int|null',
      ),
    ),
    'substr_compare' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset' => 'int',
        'length=' => 'int',
        'case_insensitive=' => 'bool',
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
    'substr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'offset' => 'int',
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
    'touch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mtime=' => 'int',
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
        'is_final=' => 'bool',
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
        'separator=' => 'string',
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
        'handler' => 'callable',
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
        'handler' => 'callable',
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
    'xmlwriter_end_attribute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'enable' => 'bool',
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
        'writer' => 'resource',
        'indentation' => 'string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'prefix' => 'string',
        'name' => 'string',
        'namespace' => 'null|string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'name' => 'string',
        'isParam' => 'bool',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'null|string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'prefix' => 'string',
        'name' => 'string',
        'namespace' => 'null|string',
        'value' => 'string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'name' => 'string',
        'publicId=' => 'null|string',
        'systemId=' => 'null|string',
        'content=' => 'null|string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'name' => 'string',
        'content' => 'null|string',
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
        'writer' => 'resource',
        'prefix' => 'null|string',
        'name' => 'string',
        'namespace' => 'string',
        'content' => 'null|string',
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
        'writer' => 'resource',
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
        'writer' => 'resource',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'writer' => 'XMLWriter',
        'content' => 'string',
      ),
    ),
    'ZipArchive::addEmptyDir' => 
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
    'ZipArchive::addFile' => 
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
    'ZipArchive::addFromString' => 
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
  ),
  'removed' => 
  array (
    'PDOStatement::setFetchMode\'1' => 
    array (
      0 => 'bool',
      'fetch_column' => 'int',
      'colno' => 'int',
    ),
    'PDOStatement::setFetchMode\'2' => 
    array (
      0 => 'bool',
      'fetch_class' => 'int',
      'classname' => 'string',
      'ctorargs' => 'array<array-key, mixed>',
    ),
    'PDOStatement::setFetchMode\'3' => 
    array (
      0 => 'bool',
      'fetch_into' => 'int',
      'object' => 'object',
    ),
    'ReflectionType::isBuiltin' => 
    array (
      0 => 'bool',
    ),
    'SplFileObject::fgetss' => 
    array (
      0 => 'false|string',
      'allowable_tags=' => 'string',
    ),
    'create_function' => 
    array (
      0 => 'string',
      'args' => 'string',
      'code' => 'string',
    ),
    'each' => 
    array (
      0 => 'array{0: int|string, 1: mixed, key: int|string, value: mixed}',
      '&r_arr' => 'array<array-key, mixed>',
    ),
    'fgetss' => 
    array (
      0 => 'false|string',
      'fp' => 'resource',
      'length=' => 'int',
      'allowable_tags=' => 'string',
    ),
    'gmp_random' => 
    array (
      0 => 'GMP',
      'limiter=' => 'int',
    ),
    'gzgetss' => 
    array (
      0 => 'false|string',
      'zp' => 'resource',
      'length' => 'int',
      'allowable_tags=' => 'string',
    ),
    'image2wbmp' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'filename=' => 'null|string',
      'threshold=' => 'int',
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
    'number_format\'1' => 
    array (
      0 => 'string',
      'num' => 'float',
      'decimals' => 'int',
      'decimal_separator' => 'null|string',
      'thousands_separator' => 'null|string',
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
    'read_exif_data' => 
    array (
      0 => 'array<array-key, mixed>',
      'filename' => 'string',
      'sections_needed=' => 'string',
      'sub_arrays=' => 'bool',
      'read_thumbnail=' => 'bool',
    ),
    'Reflection::export' => 
    array (
      0 => 'null|string',
      'r' => 'reflector',
      'return=' => 'bool',
    ),
    'ReflectionClass::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object|string',
      'return=' => 'bool',
    ),
    'ReflectionClassConstant::export' => 
    array (
      0 => 'string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionExtension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionFunction::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionFunctionAbstract::export' => 
    array (
      0 => 'null|string',
    ),
    'ReflectionMethod::export' => 
    array (
      0 => 'null|string',
      'class' => 'string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionObject::export' => 
    array (
      0 => 'null|string',
      'argument' => 'object',
      'return=' => 'bool',
    ),
    'ReflectionParameter::export' => 
    array (
      0 => 'null|string',
      'function' => 'string',
      'parameter' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionProperty::export' => 
    array (
      0 => 'null|string',
      'class' => 'mixed',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'ReflectionZendExtension::export' => 
    array (
      0 => 'null|string',
      'name' => 'string',
      'return=' => 'bool',
    ),
    'SimpleXMLIterator::rewind' => 
    array (
      0 => 'void',
    ),
    'SimpleXMLIterator::valid' => 
    array (
      0 => 'bool',
    ),
    'SimpleXMLIterator::current' => 
    array (
      0 => 'SimpleXMLIterator|null',
    ),
    'SimpleXMLIterator::key' => 
    array (
      0 => 'false|string',
    ),
    'SimpleXMLIterator::next' => 
    array (
      0 => 'void',
    ),
    'SimpleXMLIterator::hasChildren' => 
    array (
      0 => 'bool',
    ),
    'SimpleXMLIterator::getChildren' => 
    array (
      0 => 'SimpleXMLIterator|null',
    ),
    'SplFixedArray::current' => 
    array (
      0 => 'mixed',
    ),
    'SplFixedArray::key' => 
    array (
      0 => 'int',
    ),
    'SplFixedArray::next' => 
    array (
      0 => 'void',
    ),
    'SplFixedArray::rewind' => 
    array (
      0 => 'void',
    ),
    'SplFixedArray::valid' => 
    array (
      0 => 'bool',
    ),
    'SplTempFileObject::fgetss' => 
    array (
      0 => 'string',
      'allowable_tags=' => 'string',
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