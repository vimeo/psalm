<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.0 to php 7.4 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.4
 * The 'removed' section contains the signatures that were removed in php 8.0
 * The 'changed' section contains functions for which the signature has changed for php 8.0.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.4 and in PHP 8.0, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'DateTime::createFromInterface' => ['static', 'object'=>'DateTimeInterface'],
    'DateTimeImmutable::createFromInterface' => ['static', 'object'=>'DateTimeInterface'],
    'PhpToken::getTokenName' => ['?string'],
    'PhpToken::is' => ['bool', 'kind'=>'string|int|string[]|int[]'],
    'PhpToken::isIgnorable' => ['bool'],
    'PhpToken::tokenize' => ['list<PhpToken>', 'code'=>'string', 'flags='=>'int'],
    'ReflectionClass::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionClassConstant::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionFunctionAbstract::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionParameter::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionProperty::getAttributes' => ['list<ReflectionAttribute>', 'name='=>'?string', 'flags='=>'int'],
    'ReflectionProperty::getDefaultValue' => ['mixed'],
    'ReflectionProperty::hasDefaultValue' => ['bool'],
    'ReflectionProperty::isPromoted' => ['bool'],
    'ReflectionUnionType::getTypes' => ['list<ReflectionNamedType>'],
    'SplFixedArray::getIterator' => ['Iterator'],
    'WeakMap::count' => ['int'],
    'WeakMap::getIterator' => ['Iterator'],
    'WeakMap::offsetExists' => ['bool', 'object'=>'object'],
    'WeakMap::offsetGet' => ['mixed', 'object'=>'object'],
    'WeakMap::offsetSet' => ['void', 'object'=>'object', 'value'=>'mixed'],
    'WeakMap::offsetUnset' => ['void', 'object'=>'object'],
    'fdiv' => ['float', 'num1'=>'float', 'num2'=>'float'],
    'get_debug_type' => ['string', 'value'=>'mixed'],
    'get_resource_id' => ['int', 'resource'=>'resource'],
    'imagegetinterpolation' => ['int', 'image'=>'GdImage'],
    'str_contains' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
    'str_ends_with' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
    'str_starts_with' => ['bool', 'haystack'=>'string', 'needle'=>'string'],
  ],
  'changed' => [
    'Collator::getStrength' => [
      'old' => ['int|false'],
      'new' => ['int'],
    ],
    'CURLFile::__construct' => [
      'old' => ['void', 'filename'=>'string', 'mime_type='=>'string', 'posted_filename='=>'string'],
      'new' => ['void', 'filename'=>'string', 'mime_type='=>'?string', 'posted_filename='=>'?string'],
    ],
    'DateTime::format' => [
      'old' => ['string|false', 'format'=>'string'],
      'new' => ['string', 'format'=>'string'],
    ],
    'DateTime::getTimestamp' => [
      'old' => ['int|false'],
      'new' => ['int'],
    ],
    'DateTimeInterface::getTimestamp' => [
       'old' => ['int|false'],
       'new' => ['int'],
    ],
    'DateTimeZone::getOffset' => [
      'old' => ['int|false', 'datetime'=>'DateTimeInterface'],
      'new' => ['int', 'datetime'=>'DateTimeInterface'],
    ],
    'DateTimeZone::listIdentifiers' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
      'new' => ['list<string>', 'timezoneGroup='=>'int', 'countryCode='=>'string|null'],
    ],
    'Directory::close' => [
      'old' => ['void', 'dir_handle='=>'resource'],
      'new' => ['void'],
    ],
    'Directory::read' => [
      'old' => ['string|false', 'dir_handle='=>'resource'],
      'new' => ['string|false'],
    ],
    'Directory::rewind' => [
      'old' => ['void', 'dir_handle='=>'resource'],
      'new' => ['void'],
    ],
    'DirectoryIterator::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'DirectoryIterator::getPathInfo' => [
      'old' => ['?SplFileInfo', 'class='=>'class-string'],
      'new' => ['?SplFileInfo', 'class='=>'?class-string'],
    ],
    'DirectoryIterator::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'DOMDocument::getElementsByTagNameNS' => [
      'old' => ['DOMNodeList', 'namespace'=>'string', 'localName'=>'string'],
      'new' => ['DOMNodeList', 'namespace'=>'?string', 'localName'=>'string'],
    ],
    'DOMDocument::load' => [
      'old' => ['DOMDocument|bool', 'filename'=>'string', 'options='=>'int'],
      'new' => ['bool', 'filename'=>'string', 'options='=>'int'],
    ],
    'DOMDocument::loadXML' => [
      'old' => ['DOMDocument|bool', 'source'=>'non-empty-string', 'options='=>'int'],
      'new' => ['bool', 'source'=>'non-empty-string', 'options='=>'int'],
    ],
    'DOMDocument::loadHTML' => [
      'old' => ['DOMDocument|bool', 'source'=>'non-empty-string', 'options='=>'int'],
      'new' => ['bool', 'source'=>'non-empty-string', 'options='=>'int'],
    ],
    'DOMDocument::loadHTMLFile' => [
      'old' => ['DOMDocument|bool', 'filename'=>'string', 'options='=>'int'],
      'new' => ['bool', 'filename'=>'string', 'options='=>'int'],
    ],
    'DOMImplementation::createDocument' => [
      'old' => ['DOMDocument|false', 'namespace='=>'string', 'qualifiedName='=>'string', 'doctype='=>'DOMDocumentType'],
      'new' => ['DOMDocument|false', 'namespace='=>'?string', 'qualifiedName='=>'string', 'doctype='=>'?DOMDocumentType'],
    ],
    'ErrorException::__construct' => [
      'old' => ['void', 'message='=>'string', 'code='=>'int', 'severity='=>'int', 'filename='=>'string', 'line='=>'int', 'previous='=>'?Throwable'],
      'new' => ['void', 'message='=>'string', 'code='=>'int', 'severity='=>'int', 'filename='=>'?string', 'line='=>'?int', 'previous='=>'?Throwable'],
    ],
    'FilesystemIterator::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'FilesystemIterator::getPathInfo' => [
      'old' => ['?SplFileInfo', 'class='=>'class-string'],
      'new' => ['?SplFileInfo', 'class='=>'?class-string'],
    ],
    'FilesystemIterator::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'finfo::__construct' => [
      'old' => ['void', 'flags='=>'int', 'magic_database='=>'string'],
      'new' => ['void', 'flags='=>'int', 'magic_database='=>'?string'],
    ],
    'GlobIterator::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'GlobIterator::getPathInfo' => [
      'old' => ['?SplFileInfo', 'class='=>'class-string'],
      'new' => ['?SplFileInfo', 'class='=>'?class-string'],
    ],
    'GlobIterator::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'IntlDateFormatter::__construct' => [
      'old' => ['void', 'locale'=>'?string', 'datetype'=>'null|int', 'timetype'=>'null|int', 'timezone='=>'IntlTimeZone|DateTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
      'new' => ['void', 'locale'=>'?string', 'dateType'=>'int', 'timeType'=>'int', 'timezone='=>'IntlTimeZone|DateTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
    ],
    'IntlDateFormatter::create' => [
      'old' => ['?IntlDateFormatter', 'locale'=>'?string', 'datetype'=>'null|int', 'timetype'=>'null|int', 'timezone='=>'IntlTimeZone|DateTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
      'new' => ['?IntlDateFormatter', 'locale'=>'?string', 'dateType'=>'int', 'timeType'=>'int', 'timezone='=>'IntlTimeZone|DateTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
    ],
    'IntlDateFormatter::format' => [
      'old' => ['string|false', 'value'=>'IntlCalendar|DateTimeInterface|array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int}|array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}|string|int|float'],
      'new' => ['string|false', 'datetime'=>'IntlCalendar|DateTimeInterface|array{0: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int}|array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}|string|int|float'],
    ],
    'IntlDateFormatter::formatObject' => [
      'old' => ['string|false', 'object'=>'IntlCalendar|DateTime', 'format='=>'array{0: int, 1: int}|int|string|null', 'locale='=>'?string'],
      'new' => ['string|false', 'datetime'=>'IntlCalendar|DateTimeInterface', 'format='=>'array{0: int, 1: int}|int|string|null', 'locale='=>'?string'],
    ],
    'IntlDateFormatter::getCalendar' => [
      'old' => ['int'],
      'new' => ['int|false'],
    ],
    'IntlDateFormatter::getCalendarObject' => [
      'old' => ['IntlCalendar'],
      'new' => ['IntlCalendar|false|null'],
    ],
    'IntlDateFormatter::getDateType' => [
      'old' => ['int'],
      'new' => ['int|false'],
    ],
    'IntlDateFormatter::getLocale' => [
      'old' => ['string', 'which='=>'int'],
      'new' => ['string|false', 'type='=>'int'],
    ],
    'IntlDateFormatter::getPattern' => [
      'old' => ['string'],
      'new' => ['string|false'],
    ],
    'IntlDateFormatter::getTimeType' => [
      'old' => ['int'],
      'new' => ['int|false'],
    ],
    'IntlDateFormatter::getTimeZoneId' => [
      'old' => ['string'],
      'new' => ['string|false'],
    ],
    'IntlDateFormatter::localtime' => [
      'old' => ['array', 'value'=>'string', '&rw_position='=>'int'],
      'new' => ['array|false', 'string'=>'string', '&rw_offset='=>'int'],
    ],
    'IntlDateFormatter::parse' => [
      'old' => ['int|float', 'value'=>'string', '&rw_position='=>'int'],
      'new' => ['int|float|false', 'string'=>'string', '&rw_offset='=>'int'],
    ],
    'IntlDateFormatter::setCalendar' => [
      'old' => ['bool', 'which'=>'IntlCalendar|int|null'],
      'new' => ['bool', 'calendar'=>'IntlCalendar|int|null'],
    ],
    'IntlDateFormatter::setLenient' => [
      'old' => ['bool', 'lenient'=>'bool'],
      'new' => ['void', 'lenient'=>'bool'],
    ],
    'IntlDateFormatter::setTimeZone' => [
      'old' => ['null|false', 'zone'=>'IntlTimeZone|DateTimeZone|string|null'],
      'new' => ['null|false', 'timezone'=>'IntlTimeZone|DateTimeZone|string|null'],
    ],
    'IntlTimeZone::getIDForWindowsID' => [
      'old' => ['string|false', 'timezoneId'=>'string', 'region='=>'string'],
      'new' => ['string|false', 'timezoneId'=>'string', 'region='=>'?string'],
    ],
    'Locale::getDisplayLanguage' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'Locale::getDisplayName' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'Locale::getDisplayRegion' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'Locale::getDisplayScript' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'Locale::getDisplayVariant' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'mysqli_field_seek' => [
      'old' => ['bool', 'result'=>'mysqli_result', 'index'=>'int'],
      'new' => ['true', 'result'=>'mysqli_result', 'index'=>'int'],
    ],
    'mysqli_result::field_seek' => [
      'old' => ['bool', 'index'=>'int'],
      'new' => ['true', 'index'=>'int'],
    ],
    'mysqli_stmt::__construct' => [
      'old' => ['void', 'mysql'=>'mysqli', 'query='=>'string'],
      'new' => ['void', 'mysql'=>'mysqli', 'query='=>'?string'],
    ],
    'NumberFormatter::__construct' => [
      'old' => ['void', 'locale'=>'string', 'style'=>'int', 'pattern='=>'string'],
      'new' => ['void', 'locale'=>'string', 'style'=>'int', 'pattern='=>'?string'],
    ],
    'NumberFormatter::create' => [
      'old' => ['NumberFormatter|null', 'locale'=>'string', 'style'=>'int', 'pattern='=>'string'],
      'new' => ['NumberFormatter|null', 'locale'=>'string', 'style'=>'int', 'pattern='=>'?string'],
    ],
    'PDOStatement::debugDumpParams' => [
      'old' => ['void'],
      'new' => ['bool|null'],
    ],
    'PDOStatement::errorCode' => [
      'old' => ['string'],
      'new' => ['string|null'],
    ],
    'PDOStatement::execute' => [
      'old' => ['bool', 'bound_input_params='=>'?array'],
      'new' => ['bool', 'params='=>'?array'],
    ],
    'PDOStatement::fetch' => [
      'old' => ['mixed', 'how='=>'int', 'orientation='=>'int', 'offset='=>'int'],
      'new' => ['mixed', 'mode='=>'int', 'cursorOrientation='=>'int', 'cursorOffset='=>'int'],
    ],
    'PDOStatement::fetchAll' => [
      'old' => ['array|false', 'how='=>'int', 'fetch_argument='=>'int|string|callable', 'ctor_args='=>'?array'],
      'new' => ['array', 'mode='=>'int', '...args='=>'mixed'],
    ],
    'PDOStatement::fetchColumn' => [
      'old' => ['string|int|float|bool|null', 'column_number='=>'int'],
      'new' => ['mixed', 'column='=>'int'],
    ],
    'PDOStatement::setFetchMode' => [
      'old' => ['bool', 'mode'=>'int'],
      'new' => ['bool', 'mode'=>'int', '...args='=>'mixed'],
    ],
    'Phar::addFile' => [
      'old' => ['void', 'filename'=>'string', 'localName='=>'string'],
      'new' => ['void', 'filename'=>'string', 'localName='=>'?string'],
    ],
    'Phar::buildFromIterator' => [
      'old' => ['array|false', 'iterator'=>'Traversable', 'baseDirectory='=>'string'],
      'new' => ['array|false', 'iterator'=>'Traversable', 'baseDirectory='=>'?string'],
    ],
    'Phar::createDefaultStub' => [
      'old' => ['string', 'index='=>'string', 'webIndex='=>'string'],
      'new' => ['string', 'index='=>'?string', 'webIndex='=>'?string'],
    ],
    'Phar::compress' => [
      'old' => ['?Phar', 'compression'=>'int', 'extension='=>'string'],
      'new' => ['?Phar', 'compression'=>'int', 'extension='=>'?string'],
    ],
    'Phar::convertToData' => [
      'old' => ['?PharData', 'format='=>'int', 'compression='=>'int', 'extension='=>'string'],
      'new' => ['?PharData', 'format='=>'?int', 'compression='=>'?int', 'extension='=>'?string'],
    ],
    'Phar::convertToExecutable' => [
      'old' => ['?Phar', 'format='=>'int', 'compression='=>'int', 'extension='=>'string'],
      'new' => ['?Phar', 'format='=>'?int', 'compression='=>'?int', 'extension='=>'?string'],
    ],
    'Phar::decompress' => [
      'old' => ['?Phar', 'extension='=>'string'],
      'new' => ['?Phar', 'extension='=>'?string'],
    ],
    'Phar::getMetadata' => [
      'old' => ['mixed'],
      'new' => ['mixed', 'unserializeOptions='=>'array'],
    ],
    'Phar::setDefaultStub' => [
      'old' => ['bool', 'index='=>'?string', 'webIndex='=>'string'],
      'new' => ['bool', 'index='=>'?string', 'webIndex='=>'?string'],
    ],
    'Phar::setSignatureAlgorithm' => [
      'old' => ['void', 'algo'=>'int', 'privateKey='=>'string'],
      'new' => ['void', 'algo'=>'int', 'privateKey='=>'?string'],
    ],
    'Phar::webPhar' => [
      'old' => ['void', 'alias='=>'?string', 'index='=>'?string', 'fileNotFoundScript='=>'string', 'mimeTypes='=>'array', 'rewrite='=>'callable'],
      'new' => ['void', 'alias='=>'?string', 'index='=>'?string', 'fileNotFoundScript='=>'?string', 'mimeTypes='=>'array', 'rewrite='=>'?callable'],
    ],
    'PharData::addFile' => [
      'old' => ['void', 'filename'=>'string', 'localName='=>'string'],
      'new' => ['void', 'filename'=>'string', 'localName='=>'?string'],
    ],
    'PharData::buildFromIterator' => [
      'old' => ['array|false', 'iterator'=>'Traversable', 'baseDirectory='=>'string'],
      'new' => ['array|false', 'iterator'=>'Traversable', 'baseDirectory='=>'?string'],
    ],
    'PharData::compress' => [
      'old' => ['?PharData', 'compression'=>'int', 'extension='=>'string'],
      'new' => ['?PharData', 'compression'=>'int', 'extension='=>'?string'],
    ],
    'PharData::convertToData' => [
      'old' => ['?PharData', 'format='=>'int', 'compression='=>'int', 'extension='=>'string'],
      'new' => ['?PharData', 'format='=>'?int', 'compression='=>'?int', 'extension='=>'?string'],
    ],
    'PharData::convertToExecutable' => [
      'old' => ['?Phar', 'format='=>'int', 'compression='=>'int', 'extension='=>'string'],
      'new' => ['?Phar', 'format='=>'?int', 'compression='=>'?int', 'extension='=>'?string'],
    ],
    'PharData::decompress' => [
      'old' => ['?PharData', 'extension='=>'string'],
      'new' => ['?PharData', 'extension='=>'?string'],
    ],
    'PharData::setDefaultStub' => [
      'old' => ['bool', 'index='=>'?string', 'webIndex='=>'string'],
      'new' => ['bool', 'index='=>'?string', 'webIndex='=>'?string'],
    ],
    'PharData::setSignatureAlgorithm' => [
      'old' => ['void', 'algo'=>'int', 'privateKey='=>'string'],
      'new' => ['void', 'algo'=>'int', 'privateKey='=>'?string'],
    ],
    'PharFileInfo::getMetadata' => [
      'old' => ['mixed'],
      'new' => ['mixed', 'unserializeOptions='=>'array'],
    ],
    'PharFileInfo::isCompressed' => [
      'old' => ['bool', 'compression='=>'int'],
      'new' => ['bool', 'compression='=>'?int'],
    ],
    'RecursiveDirectoryIterator::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'RecursiveDirectoryIterator::getPathInfo' => [
      'old' => ['?SplFileInfo', 'class='=>'class-string'],
      'new' => ['?SplFileInfo', 'class='=>'?class-string'],
    ],
    'RecursiveDirectoryIterator::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'RecursiveIteratorIterator::getSubIterator' => [
      'old' => ['?RecursiveIterator', 'level='=>'int'],
      'new' => ['?RecursiveIterator', 'level='=>'?int'],
    ],
    'RecursiveTreeIterator::getSubIterator' => [
      'old' => ['?RecursiveIterator', 'level='=>'int'],
      'new' => ['?RecursiveIterator', 'level='=>'?int'],
    ],
    'ReflectionClass::getConstants' => [
      'old' => ['array<string,mixed>'],
      'new' => ['array<string,mixed>', 'filter='=>'?int'],
    ],
    'ReflectionClass::getReflectionConstants' => [
      'old' => ['list<ReflectionClassConstant>'],
      'new' => ['list<ReflectionClassConstant>', 'filter='=>'?int'],
    ],
    'ReflectionClass::newInstanceArgs' => [
      'old' => ['object', 'args='=>'list<mixed>'],
      'new' => ['object', 'args='=>'list<mixed>|array<string, mixed>'],
    ],
    'ReflectionMethod::getClosure' => [
      'old' => ['?Closure', 'object='=>'object'],
      'new' => ['Closure', 'object='=>'?object'],
    ],
    'ReflectionObject::getConstants' => [
      'old' => ['array<string,mixed>'],
      'new' => ['array<string,mixed>', 'filter='=>'?int'],
    ],
    'ReflectionObject::getReflectionConstants' => [
      'old' => ['list<\ReflectionClassConstant>'],
      'new' => ['list<\ReflectionClassConstant>', 'filter='=>'?int'],
    ],
    'ReflectionObject::newInstanceArgs' => [
      'old' => ['object', 'args='=>'list<mixed>'],
      'new' => ['object', 'args='=>'list<mixed>|array<string, mixed>'],
    ],
    'ReflectionProperty::getValue' => [
      'old' => ['mixed', 'object='=>'object'],
      'new' => ['mixed', 'object='=>'null|object'],
    ],
    'ReflectionProperty::isInitialized' => [
      'old' => ['bool', 'object'=>'object'],
      'new' => ['bool', 'object='=>'null|object'],
    ],
    'SplFileInfo::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'SplFileInfo::getPathInfo' => [
      'old' => ['SplFileInfo|null', 'class='=>'class-string'],
      'new' => ['SplFileInfo|null', 'class='=>'?class-string'],
    ],
    'SplFileInfo::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'SplFileObject::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'SplFileObject::getPathInfo' => [
      'old' => ['SplFileInfo|null', 'class='=>'class-string'],
      'new' => ['SplFileInfo|null', 'class='=>'?class-string'],
    ],
    'SplFileObject::openFile' => [
      'old' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'SplTempFileObject::getFileInfo' => [
      'old' => ['SplFileInfo', 'class='=>'class-string'],
      'new' => ['SplFileInfo', 'class='=>'?class-string'],
    ],
    'SplTempFileObject::getPathInfo' => [
      'old' => ['SplFileInfo|null', 'class='=>'class-string'],
      'new' => ['SplFileInfo|null', 'class='=>'?class-string'],
    ],
    'SplTempFileObject::openFile' => [
      'old' => ['SplTempFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'resource'],
      'new' => ['SplTempFileObject', 'mode='=>'string', 'useIncludePath='=>'bool', 'context='=>'?resource'],
    ],
    'tidy::__construct' => [
      'old' => ['void', 'filename='=>'string', 'config='=>'array|string', 'encoding='=>'string', 'useIncludePath='=>'bool'],
      'new' => ['void', 'filename='=>'?string', 'config='=>'array|string|null', 'encoding='=>'?string', 'useIncludePath='=>'bool'],
    ],
    'tidy::parseFile' => [
      'old' => ['bool', 'filename'=>'string', 'config='=>'array|string', 'encoding='=>'string', 'useIncludePath='=>'bool'],
      'new' => ['bool', 'filename'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string', 'useIncludePath='=>'bool'],
    ],
    'tidy::parseString' => [
      'old' => ['bool', 'string'=>'string', 'config='=>'array|string', 'encoding='=>'string'],
      'new' => ['bool', 'string'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string'],
    ],
    'tidy::repairFile' => [
      'old' => ['string', 'filename'=>'string', 'config='=>'array|string', 'encoding='=>'string', 'useIncludePath='=>'bool'],
      'new' => ['string', 'filename'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string', 'useIncludePath='=>'bool'],
    ],
    'tidy::repairString' => [
      'old' => ['string', 'string'=>'string', 'config='=>'array|string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string'],
    ],
    'XMLWriter::flush' => [
      'old' => ['string|int|false', 'empty='=>'bool'],
      'new' => ['string|int', 'empty='=>'bool'],
    ],
    'SimpleXMLElement::asXML' => [
      'old' => ['string|bool', 'filename'=>'string'],
      'new' => ['string|bool', 'filename='=>'?string'],
    ],
    'SimpleXMLElement::saveXML' => [
      'old' => ['string|bool', 'filename='=>'string'],
      'new' => ['string|bool', 'filename='=>'?string'],
    ],
    'SoapClient::__doRequest' => [
      'old' => ['?string', 'request'=>'string', 'location'=>'string', 'action'=>'string', 'version'=>'int', 'one_way='=>'int'],
      'new' => ['?string', 'request'=>'string', 'location'=>'string', 'action'=>'string', 'version'=>'int', 'one_way='=>'bool'],
    ],
    'SplFileObject::fgets' => [
      'old' => ['string|false'],
      'new' => ['string'],
    ],
    'SplFileObject::getCurrentLine' => [
      'old' => ['string|false'],
      'new' => ['string'],
    ],
    'XMLReader::next' => [
      'old' => ['bool', 'name='=>'string'],
      'new' => ['bool', 'name='=>'?string'],
    ],
    'XMLWriter::startAttributeNs' => [
      'old' => ['bool', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'XMLWriter::writeAttributeNs' => [
      'old' => ['bool', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
      'new' => ['bool', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
    ],
    'XMLWriter::writeDtdEntity' => [
      'old' => ['bool', 'name'=>'string', 'content'=>'string', 'isParam'=>'bool', 'publicId'=>'string', 'systemId'=>'string', 'notationData'=>'string'],
      'new' => ['bool', 'name'=>'string', 'content'=>'string', 'isParam='=>'bool', 'publicId='=>'?string', 'systemId='=>'?string', 'notationData='=>'?string'],
    ],
    'ZipArchive::getStatusString' => [
      'old' => ['string|false'],
      'new' => ['string'],
    ],
    'ZipArchive::setEncryptionIndex' => [
      'old' => ['bool', 'index'=>'int', 'method'=>'int', 'password='=>'string'],
      'new' => ['bool', 'index'=>'int', 'method'=>'int', 'password='=>'?string'],
    ],
    'ZipArchive::setEncryptionName' => [
      'old' => ['bool', 'name'=>'string', 'method'=>'int', 'password='=>'string'],
      'new' => ['bool', 'name'=>'string', 'method'=>'int', 'password='=>'?string'],
    ],
    'array_column' => [
      'old' => ['array', 'array'=>'array', 'column_key'=>'mixed', 'index_key='=>'mixed'],
      'new' => ['array', 'array'=>'array', 'column_key'=>'int|string|null', 'index_key='=>'int|string|null'],
    ],
    'array_combine' => [
      'old' => ['array|false', 'keys'=>'string[]|int[]', 'values'=>'array'],
      'new' => ['array', 'keys'=>'string[]|int[]', 'values'=>'array'],
    ],
    'array_diff' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_diff_assoc' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_diff_key' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_filter' => [
      'old' => ['array', 'array'=>'array', 'callback='=>'callable(mixed,array-key=):mixed', 'mode='=>'int'],
      'new' => ['array', 'array'=>'array', 'callback='=>'callable(mixed,array-key=):mixed|null', 'mode='=>'int'],
    ],
    'array_key_exists' => [
      'old' => ['bool', 'key'=>'string|int', 'array'=>'array|object'],
      'new' => ['bool', 'key'=>'string|int', 'array'=>'array'],
    ],
    'array_intersect' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_intersect_assoc' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_intersect_key' => [
      'old' => ['array', 'array'=>'array', '...arrays'=>'array'],
      'new' => ['array', 'array'=>'array', '...arrays='=>'array'],
    ],
    'array_splice' => [
      'old' => ['array', '&rw_array'=>'array', 'offset'=>'int', 'length='=>'int', 'replacement='=>'array|string'],
      'new' => ['array', '&rw_array'=>'array', 'offset'=>'int', 'length='=>'?int', 'replacement='=>'array|string'],
    ],
    'bcadd' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bccomp' => [
      'old' => ['int', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['int', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcdiv' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcmod' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcmul' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcpow' => [
      'old' => ['numeric-string', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcpowmod' => [
      'old' => ['numeric-string|false', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'modulus'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num'=>'numeric-string', 'exponent'=>'numeric-string', 'modulus'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcscale' => [
      'old' => ['int', 'scale='=>'int'],
      'new' => ['int', 'scale='=>'int|null'],
    ],
    'bcsqrt' => [
      'old' => ['numeric-string', 'num'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bcsub' => [
      'old' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int'],
      'new' => ['numeric-string', 'num1'=>'numeric-string', 'num2'=>'numeric-string', 'scale='=>'int|null'],
    ],
    'bind_textdomain_codeset' => [
      'old' => ['string', 'domain'=>'string', 'codeset'=>'string'],
      'new' => ['string', 'domain'=>'string', 'codeset'=>'?string'],
    ],
    'bindtextdomain' => [
      'old' => ['string', 'domain'=>'string', 'directory'=>'string'],
      'new' => ['string', 'domain'=>'string', 'directory'=>'?string'],
    ],
    'bzdecompress' => [
      'old' => ['string|int|false', 'data'=>'string', 'use_less_memory='=>'int'],
      'new' => ['string|int|false', 'data'=>'string', 'use_less_memory='=>'bool'],
    ],
    'bzwrite' => [
      'old' => ['int|false', 'bz'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'bz'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'collator_get_strength' => [
      'old' => ['int|false', 'object'=>'collator'],
      'new' => ['int', 'object'=>'collator'],
    ],
    'com_load_typelib' => [
      'old' => ['bool', 'typelib_name'=>'string', 'case_insensitive='=>'bool'],
      'new' => ['bool', 'typelib_name'=>'string', 'case_insensitive='=>'true'],
    ],
    'count' => [
      'old' => ['int<0, max>', 'value'=>'Countable|array|SimpleXMLElement', 'mode='=>'int'],
      'new' => ['int<0, max>', 'value'=>'Countable|array', 'mode='=>'int'],
    ],
    'sizeof' => [
      'old' => ['int<0, max>', 'value'=>'Countable|array|SimpleXMLElement', 'mode='=>'int'],
      'new' => ['int<0, max>', 'value'=>'Countable|array', 'mode='=>'int'],
    ],
    'count_chars' => [
      'old' => ['array<int,int>|false', 'input'=>'string', 'mode='=>'0|1|2'],
      'new' => ['array<int,int>', 'input'=>'string', 'mode='=>'0|1|2'],
    ],
    'count_chars\'1' => [
      'old' => ['string|false', 'input'=>'string', 'mode='=>'3|4'],
      'new' => ['string', 'input'=>'string', 'mode='=>'3|4'],
    ],
    'crypt' => [
      'old' => ['string', 'string'=>'string', 'salt='=>'string'],
      'new' => ['string', 'string'=>'string', 'salt'=>'string'],
    ],
    'curl_close' => [
      'old' => ['void', 'ch'=>'resource'],
      'new' => ['void', 'handle'=>'CurlHandle'],
    ],
    'curl_copy_handle' => [
      'old' => ['resource|false', 'ch'=>'resource'],
      'new' => ['CurlHandle|false', 'handle'=>'CurlHandle'],
    ],
    'curl_errno' => [
      'old' => ['int', 'ch'=>'resource'],
      'new' => ['int', 'handle'=>'CurlHandle'],
    ],
    'curl_error' => [
      'old' => ['string', 'ch'=>'resource'],
      'new' => ['string', 'handle'=>'CurlHandle'],
    ],
    'curl_escape' => [
      'old' => ['string|false', 'ch'=>'resource', 'string'=>'string'],
      'new' => ['string|false', 'handle'=>'CurlHandle', 'string'=>'string'],
    ],
    'curl_exec' => [
      'old' => ['bool|string', 'ch'=>'resource'],
      'new' => ['bool|string', 'handle'=>'CurlHandle'],
    ],
    'curl_file_create' => [
      'old' => ['CURLFile', 'filename'=>'string', 'mimetype='=>'string', 'postfilename='=>'string'],
      'new' => ['CURLFile', 'filename'=>'string', 'mime_type='=>'string|null', 'posted_filename='=>'string|null'],
    ],
    'curl_getinfo' => [
      'old' => ['mixed', 'ch'=>'resource', 'option='=>'int'],
      'new' => ['mixed', 'handle'=>'CurlHandle', 'option='=>'?int'],
    ],
    'curl_init' => [
      'old' => ['resource|false', 'url='=>'string'],
      'new' => ['CurlHandle|false', 'url='=>'?string'],
    ],
    'curl_multi_add_handle' => [
      'old' => ['int', 'mh'=>'resource', 'ch'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_close' => [
      'old' => ['void', 'mh'=>'resource'],
      'new' => ['void', 'multi_handle'=>'CurlMultiHandle'],
    ],
    'curl_multi_errno' => [
      'old' => ['int|false', 'mh'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle'],
    ],
    'curl_multi_exec' => [
      'old' => ['int', 'mh'=>'resource', '&w_still_running'=>'int'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', '&w_still_running'=>'int'],
    ],
    'curl_multi_getcontent' => [
      'old' => ['string', 'ch'=>'resource'],
      'new' => ['string', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_info_read' => [
      'old' => ['array|false', 'mh'=>'resource', '&w_msgs_in_queue='=>'int'],
      'new' => ['array|false', 'multi_handle'=>'CurlMultiHandle', '&w_queued_messages='=>'int'],
    ],
    'curl_multi_init' => [
      'old' => ['resource'],
      'new' => ['CurlMultiHandle'],
    ],
    'curl_multi_remove_handle' => [
      'old' => ['int', 'mh'=>'resource', 'ch'=>'resource'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'handle'=>'CurlHandle'],
    ],
    'curl_multi_select' => [
      'old' => ['int', 'mh'=>'resource', 'timeout='=>'float'],
      'new' => ['int', 'multi_handle'=>'CurlMultiHandle', 'timeout='=>'float'],
    ],
    'curl_multi_setopt' => [
      'old' => ['bool', 'mh'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'multi_handle'=>'CurlMultiHandle', 'option'=>'int', 'value'=>'mixed'],
    ],
    'curl_pause' => [
      'old' => ['int', 'ch'=>'resource', 'bitmask'=>'int'],
      'new' => ['int', 'handle'=>'CurlHandle', 'flags'=>'int'],
    ],
    'curl_reset' => [
      'old' => ['void', 'ch'=>'resource'],
      'new' => ['void', 'handle'=>'CurlHandle'],
    ],
    'curl_setopt' => [
      'old' => ['bool', 'ch'=>'resource', 'option'=>'int', 'value'=>'callable|mixed'],
      'new' => ['bool', 'handle'=>'CurlHandle', 'option'=>'int', 'value'=>'callable|mixed'],
    ],
    'curl_setopt_array' => [
      'old' => ['bool', 'ch'=>'resource', 'options'=>'array'],
      'new' => ['bool', 'handle'=>'CurlHandle', 'options'=>'array'],
    ],
    'curl_share_close' => [
      'old' => ['void', 'sh'=>'resource'],
      'new' => ['void', 'share_handle'=>'CurlShareHandle'],
    ],
    'curl_share_errno' => [
      'old' => ['int|false', 'sh'=>'resource'],
      'new' => ['int', 'share_handle'=>'CurlShareHandle'],
    ],
    'curl_share_init' => [
      'old' => ['resource'],
      'new' => ['CurlShareHandle'],
    ],
    'curl_share_setopt' => [
      'old' => ['bool', 'sh'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'share_handle'=>'CurlShareHandle', 'option'=>'int', 'value'=>'mixed'],
    ],
    'curl_unescape' => [
      'old' => ['string|false', 'ch'=>'resource', 'string'=>'string'],
      'new' => ['string|false', 'handle'=>'CurlHandle', 'string'=>'string'],
    ],
    'date' => [
      'old' => ['string', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'date_add' => [
      'old' => ['DateTime|false', 'object'=>'DateTime', 'interval'=>'DateInterval'],
      'new' => ['DateTime', 'object'=>'DateTime', 'interval'=>'DateInterval'],
    ],
    'date_date_set' => [
      'old' => ['DateTime|false', 'object'=>'DateTime', 'year'=>'int', 'month'=>'int', 'day'=>'int'],
      'new' => ['DateTime', 'object'=>'DateTime', 'year'=>'int', 'month'=>'int', 'day'=>'int'],
    ],
    'date_diff' => [
      'old' => ['DateInterval|false', 'baseObject'=>'DateTimeInterface', 'targetObject'=>'DateTimeInterface', 'absolute='=>'bool'],
      'new' => ['DateInterval', 'baseObject'=>'DateTimeInterface', 'targetObject'=>'DateTimeInterface', 'absolute='=>'bool'],
    ],
    'date_format' => [
      'old' => ['string|false', 'object'=>'DateTimeInterface', 'format'=>'string'],
      'new' => ['string', 'object'=>'DateTimeInterface', 'format'=>'string'],
    ],
    'date_offset_get' => [
      'old' => ['int|false', 'object'=>'DateTimeInterface'],
      'new' => ['int', 'object'=>'DateTimeInterface'],
    ],
    'date_parse' => [
      'old' => ['array|false', 'datetime'=>'string'],
      'new' => ['array', 'datetime'=>'string'],
    ],
    'date_sub' => [
      'old' => ['DateTime|false', 'object'=>'DateTime', 'interval'=>'DateInterval'],
      'new' => ['DateTime', 'object'=>'DateTime', 'interval'=>'DateInterval'],
    ],
    'date_sun_info' => [
      'old' => ['array|false', 'timestamp'=>'int', 'latitude'=>'float', 'longitude'=>'float'],
      'new' => ['array', 'timestamp'=>'int', 'latitude'=>'float', 'longitude'=>'float'],
    ],
    'date_sunrise' => [
      'old' => ['string|int|float|false', 'timestamp'=>'int', 'returnFormat='=>'int', 'latitude='=>'float', 'longitude='=>'float', 'zenith='=>'float', 'utcOffset='=>'float'],
      'new' => ['string|int|float|false', 'timestamp'=>'int', 'returnFormat='=>'int', 'latitude='=>'?float', 'longitude='=>'?float', 'zenith='=>'?float', 'utcOffset='=>'?float'],
    ],
    'date_sunset' => [
      'old' => ['string|int|float|false', 'timestamp'=>'int', 'returnFormat='=>'int', 'latitude='=>'float', 'longitude='=>'float', 'zenith='=>'float', 'utcOffset='=>'float'],
      'new' => ['string|int|float|false', 'timestamp'=>'int', 'returnFormat='=>'int', 'latitude='=>'?float', 'longitude='=>'?float', 'zenith='=>'?float', 'utcOffset='=>'?float'],
    ],
    'date_time_set' => [
      'old' => ['DateTime|false', 'object'=>'', 'hour'=>'', 'minute'=>'', 'second='=>'', 'microsecond='=>''],
      'new' => ['DateTime', 'object'=>'', 'hour'=>'', 'minute'=>'', 'second='=>'', 'microsecond='=>''],
    ],
    'date_timestamp_set' => [
      'old' => ['DateTime|false', 'object'=>'DateTime', 'timestamp'=>'int'],
      'new' => ['DateTime', 'object'=>'DateTime', 'timestamp'=>'int'],
    ],
    'date_timezone_set' => [
      'old' => ['DateTime|false', 'object'=>'DateTime', 'timezone'=>'DateTimeZone'],
      'new' => ['DateTime', 'object'=>'DateTime', 'timezone'=>'DateTimeZone'],
    ],
    'datefmt_create' => [
      'old' => ['?IntlDateFormatter', 'locale'=>'?string', 'dateType'=>'int', 'timeType'=>'int', 'timezone='=>'DateTimeZone|IntlTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'string'],
      'new' => ['?IntlDateFormatter', 'locale'=>'?string', 'dateType='=>'int', 'timeType='=>'int', 'timezone='=>'DateTimeZone|IntlTimeZone|string|null', 'calendar='=>'IntlCalendar|int|null', 'pattern='=>'?string'],
    ],
    'deflate_add' => [
      'old' => ['string|false', 'context'=>'resource', 'data'=>'string', 'flush_mode='=>'int'],
      'new' => ['string|false', 'context'=>'DeflateContext', 'data'=>'string', 'flush_mode='=>'int'],
    ],
    'deflate_init' => [
      'old' => ['resource|false', 'encoding'=>'int', 'options='=>'array'],
      'new' => ['DeflateContext|false', 'encoding'=>'int', 'options='=>'array'],
    ],
    'dom_import_simplexml' => [
      'old' => ['DOMElement|null', 'node'=>'SimpleXMLElement'],
      'new' => ['DOMElement', 'node'=>'SimpleXMLElement'],
    ],
    'easter_date' => [
      'old' => ['int', 'year='=>'int', 'mode='=>'int'],
      'new' => ['int', 'year='=>'?int', 'mode='=>'int'],
    ],
    'easter_days' => [
      'old' => ['int', 'year='=>'int', 'mode='=>'int'],
      'new' => ['int', 'year='=>'?int', 'mode='=>'int'],
    ],
    'enchant_broker_describe' => [
      'old' => ['array|false', 'broker'=>'resource'],
      'new' => ['array', 'broker'=>'EnchantBroker'],
    ],
    'enchant_broker_dict_exists' => [
      'old' => ['bool', 'broker'=>'resource', 'tag'=>'string'],
      'new' => ['bool', 'broker'=>'EnchantBroker', 'tag'=>'string'],
    ],
    'enchant_broker_free' => [
      'old' => ['bool', 'broker'=>'resource'],
      'new' => ['bool', 'broker'=>'EnchantBroker'],
    ],
    'enchant_broker_free_dict' => [
      'old' => ['bool', 'dictionary'=>'resource'],
      'new' => ['bool', 'dictionary'=>'EnchantBroker'],
    ],
    'enchant_broker_get_dict_path' => [
      'old' => ['string', 'broker'=>'resource', 'type'=>'int'],
      'new' => ['string', 'broker'=>'EnchantBroker', 'type'=>'int'],
    ],
    'enchant_broker_get_error' => [
      'old' => ['string|false', 'broker'=>'resource'],
      'new' => ['string|false', 'broker'=>'EnchantBroker'],
    ],
    'enchant_broker_init' => [
      'old' => ['resource|false'],
      'new' => ['EnchantBroker|false'],
    ],
    'enchant_broker_list_dicts' => [
      'old' => ['array<int,array{lang_tag:string,provider_name:string,provider_desc:string,provider_file:string}>|false', 'broker'=>'resource'],
      'new' => ['array<int,array{lang_tag:string,provider_name:string,provider_desc:string,provider_file:string}>', 'broker'=>'EnchantBroker'],
    ],
    'enchant_broker_request_dict' => [
      'old' => ['resource|false', 'broker'=>'resource', 'tag'=>'string'],
      'new' => ['EnchantDictionary|false', 'broker'=>'EnchantBroker', 'tag'=>'string'],
    ],
    'enchant_broker_request_pwl_dict' => [
      'old' => ['resource|false', 'broker'=>'resource', 'filename'=>'string'],
      'new' => ['EnchantDictionary|false', 'broker'=>'EnchantBroker', 'filename'=>'string'],
    ],
    'enchant_broker_set_dict_path' => [
      'old' => ['bool', 'broker'=>'resource', 'type'=>'int', 'path'=>'string'],
      'new' => ['bool', 'broker'=>'EnchantBroker', 'type'=>'int', 'path'=>'string'],
    ],
    'enchant_broker_set_ordering' => [
      'old' => ['bool', 'broker'=>'resource', 'tag'=>'string', 'ordering'=>'string'],
      'new' => ['bool', 'broker'=>'EnchantBroker', 'tag'=>'string', 'ordering'=>'string'],
    ],
    'enchant_dict_add_to_personal' => [
      'old' => ['void', 'dictionary'=>'resource', 'word'=>'string'],
      'new' => ['void', 'dictionary'=>'EnchantDictionary', 'word'=>'string'],
    ],
    'enchant_dict_add_to_session' => [
      'old' => ['void', 'dictionary'=>'resource', 'word'=>'string'],
      'new' => ['void', 'dictionary'=>'EnchantDictionary', 'word'=>'string'],
    ],
    'enchant_dict_check' => [
      'old' => ['bool', 'dictionary'=>'resource', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'EnchantDictionary', 'word'=>'string'],
    ],
    'enchant_dict_describe' => [
      'old' => ['array', 'dictionary'=>'resource'],
      'new' => ['array', 'dictionary'=>'EnchantDictionary'],
    ],
    'enchant_dict_get_error' => [
      'old' => ['string', 'dictionary'=>'resource'],
      'new' => ['string', 'dictionary'=>'EnchantDictionary'],
    ],
    'enchant_dict_is_in_session' => [
      'old' => ['bool', 'dictionary'=>'resource', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'EnchantDictionary', 'word'=>'string'],
    ],
    'enchant_dict_quick_check' => [
      'old' => ['bool', 'dictionary'=>'resource', 'word'=>'string', '&w_suggestions='=>'array<int,string>'],
      'new' => ['bool', 'dictionary'=>'EnchantDictionary', 'word'=>'string', '&w_suggestions='=>'array<int,string>'],
    ],
    'enchant_dict_store_replacement' => [
      'old' => ['void', 'dictionary'=>'resource', 'misspelled'=>'string', 'correct'=>'string'],
      'new' => ['void', 'dictionary'=>'EnchantDictionary', 'misspelled'=>'string', 'correct'=>'string'],
    ],
    'enchant_dict_suggest' => [
      'old' => ['array', 'dictionary'=>'resource', 'word'=>'string'],
      'new' => ['array', 'dictionary'=>'EnchantDictionary', 'word'=>'string'],
    ],
    'error_log' => [
      'old' => ['bool', 'message'=>'string', 'message_type='=>'int', 'destination='=>'string', 'additional_headers='=>'string'],
      'new' => ['bool', 'message'=>'string', 'message_type='=>'int', 'destination='=>'?string', 'additional_headers='=>'?string'],
    ],
    'error_reporting' => [
      'old' => ['int', 'error_level='=>'int'],
      'new' => ['int', 'error_level='=>'?int'],
    ],
    'exif_read_data' => [
      'old' => ['array|false', 'file'=>'string|resource', 'required_sections='=>'string', 'as_arrays='=>'bool', 'read_thumbnail='=>'bool'],
      'new' => ['array|false', 'file'=>'string|resource', 'required_sections='=>'?string', 'as_arrays='=>'bool', 'read_thumbnail='=>'bool'],
    ],
    'explode' => [
      'old' => ['list<string>|false', 'separator'=>'string', 'string'=>'string', 'limit='=>'int'],
      'new' => ['list<string>', 'separator'=>'string', 'string'=>'string', 'limit='=>'int'],
    ],
    'fgetcsv' => [
      'old' => ['list<string>|array{0: null}|false', 'stream'=>'resource', 'length='=>'int', 'separator='=>'string', 'enclosure='=>'string', 'escape='=>'string'],
      'new' => ['list<string>|array{0: null}|false', 'stream'=>'resource', 'length='=>'?int', 'separator='=>'string', 'enclosure='=>'string', 'escape='=>'string'],
    ],
    'fgets' => [
      'old' => ['string|false', 'stream'=>'resource', 'length='=>'int'],
      'new' => ['string|false', 'stream'=>'resource', 'length='=>'?int'],
    ],
    'file_get_contents' => [
      'old' => ['string|false', 'filename'=>'string', 'use_include_path='=>'bool', 'context='=>'?resource', 'offset='=>'int', 'length='=>'int'],
      'new' => ['string|false', 'filename'=>'string', 'use_include_path='=>'bool', 'context='=>'?resource', 'offset='=>'int', 'length='=>'?int'],
    ],
    'finfo_open' => [
      'old' => ['resource|false', 'flags='=>'int', 'magic_database='=>'string'],
      'new' => ['resource|false', 'flags='=>'int', 'magic_database='=>'?string'],
    ],
    'fputs' => [
      'old' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'fsockopen' => [
      'old' => ['resource|false', 'hostname'=>'string', 'port='=>'int', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'float'],
      'new' => ['resource|false', 'hostname'=>'string', 'port='=>'int', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'?float'],
    ],
    'fwrite' => [
      'old' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'get_class_methods' => [
      'old' => ['list<non-falsy-string>|null', 'object_or_class'=>'mixed'],
      'new' => ['list<non-falsy-string>', 'object_or_class'=>'object|class-string'],
    ],
    'get_headers' => [
      'old' => ['array|false', 'url'=>'string', 'associative='=>'int', 'context='=>'?resource'],
      'new' => ['array|false', 'url'=>'string', 'associative='=>'bool', 'context='=>'?resource'],
    ],
    'get_parent_class' => [
      'old' => ['class-string|false', 'object_or_class='=>'mixed'],
      'new' => ['class-string|false', 'object_or_class='=>'object|class-string'],
    ],
    'get_resources' => [
      'old' => ['array<int,resource>', 'type='=>'string'],
      'new' => ['array<int,resource>', 'type='=>'?string'],
    ],
    'getdate' => [
      'old' => ['array{seconds: int<0, 59>, minutes: int<0, 59>, hours: int<0, 23>, mday: int<1, 31>, wday: int<0, 6>, mon: int<1, 12>, year: int, yday: int<0, 365>, weekday: "Monday"|"Tuesday"|"Wednesday"|"Thursday"|"Friday"|"Saturday"|"Sunday", month: "January"|"February"|"March"|"April"|"May"|"June"|"July"|"August"|"September"|"October"|"November"|"December", 0: int}', 'timestamp='=>'int'],
      'new' => ['array{seconds: int<0, 59>, minutes: int<0, 59>, hours: int<0, 23>, mday: int<1, 31>, wday: int<0, 6>, mon: int<1, 12>, year: int, yday: int<0, 365>, weekday: "Monday"|"Tuesday"|"Wednesday"|"Thursday"|"Friday"|"Saturday"|"Sunday", month: "January"|"February"|"March"|"April"|"May"|"June"|"July"|"August"|"September"|"October"|"November"|"December", 0: int}', 'timestamp='=>'?int'],
    ],
    'gmdate' => [
      'old' => ['string', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string', 'format'=>'string', 'timestamp='=>'int|null'],
    ],
    'gmmktime' => [
      'old' => ['int|false', 'hour='=>'int', 'minute='=>'int', 'second='=>'int', 'month='=>'int', 'day='=>'int', 'year='=>'int'],
      'new' => ['int|false', 'hour'=>'int', 'minute='=>'int|null', 'second='=>'int|null', 'month='=>'int|null', 'day='=>'int|null', 'year='=>'int|null'],
    ],
    'gmp_binomial' => [
      'old' => ['GMP|false', 'n'=>'GMP|string|int', 'k'=>'int'],
      'new' => ['GMP', 'n'=>'GMP|string|int', 'k'=>'int'],
    ],
    'gmp_export' => [
      'old' => ['string|false', 'num'=>'GMP|string|int', 'word_size='=>'int', 'flags='=>'int'],
      'new' => ['string', 'num'=>'GMP|string|int', 'word_size='=>'int', 'flags='=>'int'],
    ],
    'gmp_import' => [
      'old' => ['GMP|false', 'data'=>'string', 'word_size='=>'int', 'flags='=>'int'],
      'new' => ['GMP', 'data'=>'string', 'word_size='=>'int', 'flags='=>'int'],
    ],
    'gmstrftime' => [
      'old' => ['string|false', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string|false', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'gzgets' => [
      'old' => ['string|false', 'stream'=>'resource', 'length='=>'int'],
      'new' => ['string|false', 'stream'=>'resource', 'length='=>'?int'],
    ],
    'gzputs' => [
      'old' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'gzwrite' => [
      'old' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'stream'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'hash' => [
      'old' => ['string|false', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
    ],
    'hash_hmac' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'data'=>'string', 'key'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'data'=>'string', 'key'=>'string', 'binary='=>'bool'],
    ],
    'hash_hmac_file' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'data'=>'string', 'key'=>'string', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'filename'=>'string', 'key'=>'string', 'binary='=>'bool'],
    ],
    'hash_init' => [
      'old' => ['HashContext|false', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
      'new' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
    ],
    'hash_hkdf' => [
      'old' => ['non-empty-string|false', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
      'new' => ['non-empty-string', 'algo'=>'string', 'key'=>'string', 'length='=>'int', 'info='=>'string', 'salt='=>'string'],
    ],
    'hash_update_file' => [
      'old' => ['bool', 'context'=>'HashContext', 'filename'=>'string', 'stream_context='=>'resource'],
      'new' => ['bool', 'context'=>'HashContext', 'filename'=>'string', 'stream_context='=>'?resource'],
    ],
    'header_remove' => [
      'old' => ['void', 'name='=>'string'],
      'new' => ['void', 'name='=>'?string'],
    ],
    'html_entity_decode' => [
      'old' => ['string', 'string'=>'string', 'flags='=>'int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'flags='=>'int', 'encoding='=>'?string'],
    ],
    'htmlentities' => [
      'old' => ['string', 'string'=>'string', 'flags='=>'int', 'encoding='=>'string', 'double_encode='=>'bool'],
      'new' => ['string', 'string'=>'string', 'flags='=>'int', 'encoding='=>'?string', 'double_encode='=>'bool'],
    ],
    'iconv_mime_decode' => [
      'old' => ['string|false', 'string'=>'string', 'mode='=>'int', 'encoding='=>'string'],
      'new' => ['string|false', 'string'=>'string', 'mode='=>'int', 'encoding='=>'?string'],
    ],
    'iconv_mime_decode_headers' => [
      'old' => ['array|false', 'headers'=>'string', 'mode='=>'int', 'encoding='=>'string'],
      'new' => ['array|false', 'headers'=>'string', 'mode='=>'int', 'encoding='=>'?string'],
    ],
    'iconv_strlen' => [
      'old' => ['0|positive-int|false', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['0|positive-int|false', 'string'=>'string', 'encoding='=>'?string'],
    ],
    'iconv_strpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'?string'],
    ],
    'iconv_strrpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'?string'],
    ],
    'iconv_substr' => [
      'old' => ['string|false', 'string'=>'string', 'offset'=>'int', 'length='=>'int', 'encoding='=>'string'],
      'new' => ['string|false', 'string'=>'string', 'offset'=>'int', 'length='=>'?int', 'encoding='=>'?string'],
    ],
    'idate' => [
      'old' => ['int', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['int', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'ignore_user_abort' => [
      'old' => ['int', 'enable='=>'bool'],
      'new' => ['int', 'enable='=>'?bool'],
    ],
    'imageaffine' => [
      'old' => ['resource|false', 'src'=>'resource', 'affine'=>'array', 'clip='=>'array'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'affine'=>'array', 'clip='=>'?array'],
    ],
    'imagealphablending' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imageantialias' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imagearc' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int'],
    ],
    'imagebmp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'resource|string|null', 'compressed='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'resource|string|null', 'compressed='=>'bool'],
    ],
    'imagechar' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
    ],
    'imagecharup' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'char'=>'string', 'color'=>'int'],
    ],
    'imagecolorallocate' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorallocatealpha' => [
      'old' => ['int|false', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorat' => [
      'old' => ['int|false', 'image'=>'resource', 'x'=>'int', 'y'=>'int'],
      'new' => ['int|false', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int'],
    ],
    'imagecolorclosest' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorclosestalpha' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorclosesthwb' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolordeallocate' => [
      'old' => ['bool', 'image'=>'resource', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'color'=>'int'],
    ],
    'imagecolorexact' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorexactalpha' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolormatch' => [
      'old' => ['bool', 'image1'=>'resource', 'image2'=>'resource'],
      'new' => ['bool', 'image1'=>'GdImage', 'image2'=>'GdImage'],
    ],
    'imagecolorresolve' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int'],
    ],
    'imagecolorresolvealpha' => [
      'old' => ['int', 'image'=>'resource', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha'=>'int'],
    ],
    'imagecolorset' => [
      'old' => ['false|null', 'image'=>'resource', 'color'=>'int', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha='=>'int'],
      'new' => ['false|null', 'image'=>'GdImage', 'color'=>'int', 'red'=>'int', 'green'=>'int', 'blue'=>'int', 'alpha='=>'int'],
    ],
    'imagecolorsforindex' => [
      'old' => ['array', 'image'=>'resource', 'color'=>'int'],
      'new' => ['array', 'image'=>'GdImage', 'color'=>'int'],
    ],
    'imagecolorstotal' => [
      'old' => ['int', 'image'=>'resource'],
      'new' => ['int', 'image'=>'GdImage'],
    ],
    'imagecolortransparent' => [
      'old' => ['int', 'image'=>'resource', 'color='=>'int'],
      'new' => ['int', 'image'=>'GdImage', 'color='=>'?int'],
    ],
    'imageconvolution' => [
      'old' => ['bool', 'image'=>'resource', 'matrix'=>'array', 'divisor'=>'float', 'offset'=>'float'],
      'new' => ['bool', 'image'=>'GdImage', 'matrix'=>'array', 'divisor'=>'float', 'offset'=>'float'],
    ],
    'imagecopy' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecopymerge' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
    ],
    'imagecopymergegray' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'src_width'=>'int', 'src_height'=>'int', 'pct'=>'int'],
    ],
    'imagecopyresampled' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecopyresized' => [
      'old' => ['bool', 'dst_image'=>'resource', 'src_image'=>'resource', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
      'new' => ['bool', 'dst_image'=>'GdImage', 'src_image'=>'GdImage', 'dst_x'=>'int', 'dst_y'=>'int', 'src_x'=>'int', 'src_y'=>'int', 'dst_width'=>'int', 'dst_height'=>'int', 'src_width'=>'int', 'src_height'=>'int'],
    ],
    'imagecreate' => [
      'old' => ['resource|false', 'x_size'=>'int', 'y_size'=>'int'],
      'new' => ['false|GdImage', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecreatefrombmp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd2' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromgd2part' => [
      'old' => ['resource|false', 'filename'=>'string', 'srcx'=>'int', 'srcy'=>'int', 'width'=>'int', 'height'=>'int'],
      'new' => ['false|GdImage', 'filename'=>'string', 'x'=>'int', 'y'=>'int', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecreatefromgif' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromjpeg' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefrompng' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromstring' => [
      'old' => ['resource|false', 'image'=>'string'],
      'new' => ['false|GdImage', 'data'=>'string'],
    ],
    'imagecreatefromwbmp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromwebp' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromxbm' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatefromxpm' => [
      'old' => ['resource|false', 'filename'=>'string'],
      'new' => ['false|GdImage', 'filename'=>'string'],
    ],
    'imagecreatetruecolor' => [
      'old' => ['resource|false', 'x_size'=>'int', 'y_size'=>'int'],
      'new' => ['false|GdImage', 'width'=>'int', 'height'=>'int'],
    ],
    'imagecrop' => [
      'old' => ['resource|false', 'im'=>'resource', 'rect'=>'array'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'rectangle'=>'array'],
    ],
    'imagecropauto' => [
      'old' => ['resource|false', 'im'=>'resource', 'mode='=>'int', 'threshold='=>'float', 'color='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'mode='=>'int', 'threshold='=>'float', 'color='=>'int'],
    ],
    'imagedashedline' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imagedestroy' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imageellipse' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
    ],
    'imagefill' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
    ],
    'imagefilledarc' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int', 'style'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'start_angle'=>'int', 'end_angle'=>'int', 'color'=>'int', 'style'=>'int'],
    ],
    'imagefilledellipse' => [
      'old' => ['bool', 'image'=>'resource', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'center_x'=>'int', 'center_y'=>'int', 'width'=>'int', 'height'=>'int', 'color'=>'int'],
    ],
    'imagefilledpolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
    ],
    'imagefilledrectangle' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imagefilltoborder' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'border_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'border_color'=>'int', 'color'=>'int'],
    ],
    'imagefilter' => [
      'old' => ['bool', 'image'=>'resource', 'filter'=>'int', '...args='=>'array|int|float|bool'],
      'new' => ['bool', 'image'=>'GdImage', 'filter'=>'int', '...args='=>'array|int|float|bool'],
    ],
    'imageflip' => [
      'old' => ['bool', 'image'=>'resource', 'mode'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'mode'=>'int'],
    ],
    'imagefttext' => [
      'old' => ['array|false', 'image'=>'resource', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string', 'options='=>'array'],
      'new' => ['array|false', 'image'=>'GdImage', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string', 'options='=>'array'],
    ],
    'imagegammacorrect' => [
      'old' => ['bool', 'image'=>'resource', 'input_gamma'=>'float', 'output_gamma'=>'float'],
      'new' => ['bool', 'image'=>'GdImage', 'input_gamma'=>'float', 'output_gamma'=>'float'],
    ],
    'imagegd' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null'],
    ],
    'imagegd2' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'chunk_size='=>'int', 'mode='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'chunk_size='=>'int', 'mode='=>'int'],
    ],
    'imagegetclip' => [
      'old' => ['array<int,int>|false', 'im'=>'resource'],
      'new' => ['array<int,int>', 'image'=>'GdImage'],
    ],
    'imagegif' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null'],
    ],
    'imagegrabscreen' => [
      'old' => ['false|resource'],
      'new' => ['false|GdImage'],
    ],
    'imagegrabwindow' => [
      'old' => ['false|resource', 'window_handle'=>'int', 'client_area='=>'int'],
      'new' => ['false|GdImage', 'handle'=>'int', 'client_area='=>'int'],
    ],
    'imageinterlace' => [
      'old' => ['int|false', 'image'=>'resource', 'enable='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'enable='=>'bool|null'],
    ],
    'imageistruecolor' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imagejpeg' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int'],
    ],
    'imagelayereffect' => [
      'old' => ['bool', 'image'=>'resource', 'effect'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'effect'=>'int'],
    ],
    'imageline' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imageopenpolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points'=>'int', 'color'=>'int'],
    ],
    'imagepalettecopy' => [
      'old' => ['void', 'dst'=>'resource', 'src'=>'resource'],
      'new' => ['void', 'dst'=>'GdImage', 'src'=>'GdImage'],
    ],
    'imagepalettetotruecolor' => [
      'old' => ['bool', 'image'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage'],
    ],
    'imagepng' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int', 'filters='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int', 'filters='=>'int'],
    ],
    'imagepolygon' => [
      'old' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'points'=>'array', 'num_points_or_color'=>'int', 'color'=>'int'],
    ],
    'imagerectangle' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int', 'color'=>'int'],
    ],
    'imageresolution' => [
      'old' => ['array|bool', 'image'=>'resource', 'resolution_x='=>'int', 'resolution_y='=>'int'],
      'new' => ['array|bool', 'image'=>'GdImage', 'resolution_x='=>'?int', 'resolution_y='=>'?int'],
    ],
    'imagerotate' => [
      'old' => ['resource|false', 'src_im'=>'resource', 'angle'=>'float', 'bgdcolor'=>'int', 'ignoretransparent='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'angle'=>'float', 'background_color'=>'int', 'ignore_transparent='=>'bool'],
    ],
    'imagesavealpha' => [
      'old' => ['bool', 'image'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'image'=>'GdImage', 'enable'=>'bool'],
    ],
    'imagescale' => [
      'old' => ['resource|false', 'im'=>'resource', 'new_width'=>'int', 'new_height='=>'int', 'method='=>'int'],
      'new' => ['false|GdImage', 'image'=>'GdImage', 'width'=>'int', 'height='=>'int', 'mode='=>'int'],
    ],
    'imagesetbrush' => [
      'old' => ['bool', 'image'=>'resource', 'brush'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage', 'brush'=>'GdImage'],
    ],
    'imagesetclip' => [
      'old' => ['bool', 'image'=>'resource', 'x1'=>'int', 'x2'=>'int', 'y1'=>'int', 'y2'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x1'=>'int', 'x2'=>'int', 'y1'=>'int', 'y2'=>'int'],
    ],
    'imagesetinterpolation' => [
      'old' => ['bool', 'image'=>'resource', 'method='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'method='=>'int'],
    ],
    'imagesetpixel' => [
      'old' => ['bool', 'image'=>'resource', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'x'=>'int', 'y'=>'int', 'color'=>'int'],
    ],
    'imagesetstyle' => [
      'old' => ['bool', 'image'=>'resource', 'style'=>'non-empty-array'],
      'new' => ['bool', 'image'=>'GdImage', 'style'=>'non-empty-array'],
    ],
    'imagesetthickness' => [
      'old' => ['bool', 'image'=>'resource', 'thickness'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'thickness'=>'int'],
    ],
    'imagesettile' => [
      'old' => ['bool', 'image'=>'resource', 'tile'=>'resource'],
      'new' => ['bool', 'image'=>'GdImage', 'tile'=>'GdImage'],
    ],
    'imagestring' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
    ],
    'imagestringup' => [
      'old' => ['bool', 'image'=>'resource', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'font'=>'int', 'x'=>'int', 'y'=>'int', 'string'=>'string', 'color'=>'int'],
    ],
    'imagesx' => [
      'old' => ['int', 'image'=>'resource'],
      'new' => ['int', 'image'=>'GdImage'],
    ],
    'imagesy' => [
      'old' => ['int', 'image'=>'resource'],
      'new' => ['int', 'image'=>'GdImage'],
    ],
    'imagetruecolortopalette' => [
      'old' => ['bool', 'image'=>'resource', 'dither'=>'bool', 'num_colors'=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'dither'=>'bool', 'num_colors'=>'int'],
    ],
    'imagettfbbox' => [
      'old' => ['false|array', 'size'=>'float', 'angle'=>'float', 'font_filename'=>'string', 'string'=>'string'],
      'new' => ['false|array', 'size'=>'float', 'angle'=>'float', 'font_filename'=>'string', 'string'=>'string', 'options='=>'array'],
    ],
    'imagettftext' => [
      'old' => ['false|array', 'image'=>'resource', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string'],
      'new' => ['false|array', 'image'=>'GdImage', 'size'=>'float', 'angle'=>'float', 'x'=>'int', 'y'=>'int', 'color'=>'int', 'font_filename'=>'string', 'text'=>'string', 'options='=>'array'],
    ],
    'imagewbmp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'foreground_color='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'foreground_color='=>'?int'],
    ],
    'imagewebp' => [
      'old' => ['bool', 'image'=>'resource', 'file='=>'string|resource|null', 'quality='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'file='=>'string|resource|null', 'quality='=>'int'],
    ],
    'imagexbm' => [
      'old' => ['bool', 'image'=>'resource', 'filename'=>'?string', 'foreground_color='=>'int'],
      'new' => ['bool', 'image'=>'GdImage', 'filename'=>'?string', 'foreground_color='=>'?int'],
    ],
    'imap_append' => [
      'old' => ['bool', 'imap'=>'resource', 'folder'=>'string', 'message'=>'string', 'options='=>'string', 'internal_date='=>'string'],
      'new' => ['bool', 'imap'=>'resource', 'folder'=>'string', 'message'=>'string', 'options='=>'?string', 'internal_date='=>'?string'],
    ],
    'imap_headerinfo' => [
      'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int', 'default_host='=>'string|null'],
      'new' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int'],
    ],
    'imap_mail' => [
      'old' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'string', 'cc='=>'string', 'bcc='=>'string', 'return_path='=>'string'],
      'new' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'?string', 'cc='=>'?string', 'bcc='=>'?string', 'return_path='=>'?string'],
    ],
    'imap_sort' => [
      'old' => ['array|false', 'imap'=>'resource', 'criteria'=>'int', 'reverse'=>'int', 'flags='=>'int', 'search_criteria='=>'string', 'charset='=>'string'],
      'new' => ['array|false', 'imap'=>'resource', 'criteria'=>'int', 'reverse'=>'bool', 'flags='=>'int', 'search_criteria='=>'?string', 'charset='=>'?string'],
    ],
    'inflate_add' => [
      'old' => ['string|false', 'context'=>'resource', 'data'=>'string', 'flush_mode='=>'int'],
      'new' => ['string|false', 'context'=>'InflateContext', 'data'=>'string', 'flush_mode='=>'int'],
    ],
    'inflate_get_read_len' => [
      'old' => ['int', 'context'=>'resource'],
      'new' => ['int', 'context'=>'InflateContext'],
    ],
    'inflate_get_status' => [
      'old' => ['int', 'context'=>'resource'],
      'new' => ['int', 'context'=>'InflateContext'],
    ],
    'inflate_init' => [
      'old' => ['resource|false', 'encoding'=>'int', 'options='=>'array'],
      'new' => ['InflateContext|false', 'encoding'=>'int', 'options='=>'array'],
    ],
    'jdtounix' => [
      'old' => ['int|false', 'julian_day'=>'int'],
      'new' => ['int', 'julian_day'=>'int'],
    ],
    'ldap_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_bind_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'?array'],
    ],
    'ldap_compare' => [
      'old' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string', 'controls='=>'array'],
      'new' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string', 'controls='=>'?array'],
    ],
    'ldap_delete' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'?array'],
    ],
    'ldap_delete_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'?array'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array'],
      'new' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
    ],
    'ldap_list' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_rename_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'?array'],
    ],
    'ldap_mod_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_del' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_del_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_replace' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_mod_replace_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_modify' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'?array'],
    ],
    'ldap_modify_batch' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array', 'controls='=>'?array'],
    ],
    'ldap_read' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
      'new' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_rename' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'?array'],
    ],
    'ldap_search' => [
      'old' => ['resource[]|resource|false', 'ldap'=>'resource|resource[]', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
      'new' => ['resource[]|resource|false', 'ldap'=>'resource|resource[]', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'?array'],
    ],
    'ldap_set_rebind_proc' => [
      'old' => ['bool', 'ldap'=>'resource', 'callback'=>'callable'],
      'new' => ['bool', 'ldap'=>'resource', 'callback'=>'?callable'],
    ],
    'ldap_sasl_bind' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn='=>'string', 'password='=>'string', 'mech='=>'string', 'realm='=>'string', 'authc_id='=>'string', 'authz_id='=>'string', 'props='=>'string'],
      'new' => ['bool', 'ldap'=>'resource', 'dn='=>'?string', 'password='=>'?string', 'mech='=>'?string', 'realm='=>'?string', 'authc_id='=>'?string', 'authz_id='=>'?string', 'props='=>'?string'],
    ],
    'libxml_use_internal_errors' => [
      'old' => ['bool', 'use_errors='=>'bool'],
      'new' => ['bool', 'use_errors='=>'?bool'],
    ],
    'locale_get_display_language' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'locale_get_display_name' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'locale_get_display_region' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'locale_get_display_script' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'locale_get_display_variant' => [
      'old' => ['string', 'locale'=>'string', 'displayLocale='=>'string'],
      'new' => ['string', 'locale'=>'string', 'displayLocale='=>'?string'],
    ],
    'localtime' => [
      'old' => ['array', 'timestamp='=>'int', 'associative='=>'bool'],
      'new' => ['array', 'timestamp='=>'?int', 'associative='=>'bool'],
    ],
    'mb_check_encoding' => [
      'old' => ['bool', 'value='=>'array|string', 'encoding='=>'string'],
      'new' => ['bool', 'value='=>'array|string|null', 'encoding='=>'string|null'],
    ],
    'mb_chr' => [
      'old' => ['non-empty-string|false', 'codepoint'=>'int', 'encoding='=>'string'],
      'new' => ['non-empty-string|false', 'codepoint'=>'int', 'encoding='=>'string|null'],
    ],
    'mb_convert_case' => [
      'old' => ['string', 'string'=>'string', 'mode'=>'int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'mode'=>'int', 'encoding='=>'string|null'],
    ],
    'mb_convert_encoding' => [
      'old' => ['string|false', 'string'=>'string', 'to_encoding'=>'string', 'from_encoding='=>'mixed'],
      'new' => ['string|false', 'string'=>'string', 'to_encoding'=>'string', 'from_encoding='=>'array|string|null'],
    ],
    'mb_convert_encoding\'1' => [
      'old' => ['array', 'string'=>'array', 'to_encoding'=>'string', 'from_encoding='=>'mixed'],
      'new' => ['array', 'string'=>'array', 'to_encoding'=>'string', 'from_encoding='=>'array|string|null'],
    ],
    'mb_convert_kana' => [
      'old' => ['string', 'string'=>'string', 'mode='=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'mode='=>'string', 'encoding='=>'string|null'],
    ],
    'mb_decode_numericentity' => [
      'old' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string|null'],
    ],
    'mb_detect_encoding' => [
      'old' => ['string|false', 'string'=>'string', 'encodings='=>'mixed', 'strict='=>'bool'],
      'new' => ['string|false', 'string'=>'string', 'encodings='=>'array|string|null', 'strict='=>'bool'],
    ],
    'mb_detect_order' => [
      'old' => ['bool|list<string>', 'encoding='=>'mixed'],
      'new' => ['bool|list<string>', 'encoding='=>'array|string|null'],
    ],
    'mb_encode_mimeheader' => [
      'old' => ['string', 'string'=>'string', 'charset='=>'string', 'transfer_encoding='=>'string', 'newline='=>'string', 'indent='=>'int'],
      'new' => ['string', 'string'=>'string', 'charset='=>'string|null', 'transfer_encoding='=>'string|null', 'newline='=>'string', 'indent='=>'int'],
    ],
    'mb_encode_numericentity' => [
      'old' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string', 'hex='=>'bool'],
      'new' => ['string', 'string'=>'string', 'map'=>'array', 'encoding='=>'string|null', 'hex='=>'bool'],
    ],
    'mb_encoding_aliases' => [
      'old' => ['list<string>|false', 'encoding'=>'string'],
      'new' => ['list<string>', 'encoding'=>'string'],
    ],
    'mb_ereg' => [
      'old' => ['int|false', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
    ],
    'mb_ereg_match' => [
      'old' => ['bool', 'pattern'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_replace' => [
      'old' => ['string|false', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_replace_callback' => [
      'old' => ['string|false|null', 'pattern'=>'string', 'callback'=>'callable', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'callback'=>'callable', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_ereg_search' => [
      'old' => ['bool', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['bool', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_init' => [
      'old' => ['bool', 'string'=>'string', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['bool', 'string'=>'string', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_pos' => [
      'old' => ['int[]|false', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['int[]|false', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_ereg_search_regs' => [
      'old' => ['string[]|false', 'pattern='=>'string', 'options='=>'string'],
      'new' => ['string[]|false', 'pattern='=>'string|null', 'options='=>'string|null'],
    ],
    'mb_eregi' => [
      'old' => ['int|false', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array'],
      'new' => ['bool', 'pattern'=>'string', 'string'=>'string', '&w_matches='=>'array|null'],
    ],
    'mb_eregi_replace' => [
      'old' => ['string|false', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string'],
      'new' => ['string|false|null', 'pattern'=>'string', 'replacement'=>'string', 'string'=>'string', 'options='=>'string|null'],
    ],
    'mb_http_input' => [
      'old' => ['string|false', 'type='=>'string'],
      'new' => ['array|string|false', 'type='=>'string|null'],
    ],
    'mb_http_output' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_internal_encoding' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_language' => [
      'old' => ['string|bool', 'language='=>'string'],
      'new' => ['string|bool', 'language='=>'string|null'],
    ],
    'mb_ord' => [
      'old' => ['int|false', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['int|false', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_parse_str' => [
      'old' => ['bool', 'string'=>'string', '&w_result='=>'array'],
      'new' => ['bool', 'string'=>'string', '&w_result'=>'array'],
    ],
    'mb_regex_encoding' => [
      'old' => ['string|bool', 'encoding='=>'string'],
      'new' => ['string|bool', 'encoding='=>'string|null'],
    ],
    'mb_regex_set_options' => [
      'old' => ['string', 'options='=>'string'],
      'new' => ['string', 'options='=>'string|null'],
    ],
    'mb_scrub' => [
      'old' => ['string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_send_mail' => [
      'old' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'string|array', 'additional_params='=>'string'],
      'new' => ['bool', 'to'=>'string', 'subject'=>'string', 'message'=>'string', 'additional_headers='=>'string|array', 'additional_params='=>'string|null'],
    ],
    'mb_str_split' => [
      'old' => ['list<string>|false', 'string'=>'string', 'length='=>'positive-int', 'encoding='=>'string'],
      'new' => ['list<string>', 'string'=>'string', 'length='=>'positive-int', 'encoding='=>'string|null'],
    ],
    'mb_strcut' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string|null'],
    ],
    'mb_strimwidth' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'width'=>'int', 'trim_marker='=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'width'=>'int', 'trim_marker='=>'string', 'encoding='=>'string|null'],
    ],
    'mb_stripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_stristr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strlen' => [
      'old' => ['0|positive-int', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['0|positive-int', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strrchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strrichr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strrpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'encoding='=>'string|null'],
    ],
    'mb_strstr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool', 'encoding='=>'string|null'],
    ],
    'mb_strtolower' => [
      'old' => ['lowercase-string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['lowercase-string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strtoupper' => [
      'old' => ['string', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_strwidth' => [
      'old' => ['int', 'string'=>'string', 'encoding='=>'string'],
      'new' => ['int', 'string'=>'string', 'encoding='=>'string|null'],
    ],
    'mb_substitute_character' => [
      'old' => ['bool|int|string', 'substitute_character='=>'mixed'],
      'new' => ['bool|int|string', 'substitute_character='=>'int|string|null'],
    ],
    'mb_substr' => [
      'old' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'start'=>'int', 'length='=>'?int', 'encoding='=>'string|null'],
    ],
    'mb_substr_count' => [
      'old' => ['int', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'string'],
      'new' => ['int', 'haystack'=>'string', 'needle'=>'string', 'encoding='=>'string|null'],
    ],
    'metaphone' => [
      'old' => ['string|false', 'string'=>'string', 'max_phonemes='=>'int'],
      'new' => ['string', 'string'=>'string', 'max_phonemes='=>'int'],
    ],
    'mhash' => [
      'old' => ['string', 'algo'=>'int', 'data'=>'string', 'key='=>'string'],
      'new' => ['string', 'algo'=>'int', 'data'=>'string', 'key='=>'?string'],
    ],
    'mktime' => [
      'old' => ['int|false', 'hour='=>'int', 'minute='=>'int', 'second='=>'int', 'month='=>'int', 'day='=>'int', 'year='=>'int'],
      'new' => ['int|false', 'hour'=>'int', 'minute='=>'int|null', 'second='=>'int|null', 'month='=>'int|null', 'day='=>'int|null', 'year='=>'int|null'],
    ],
    'msg_get_queue' => [
      'old' => ['resource|false', 'key'=>'int', 'permissions='=>'int'],
      'new' => ['SysvMessageQueue|false', 'key'=>'int', 'permissions='=>'int'],
    ],
    'msg_receive' => [
      'old' => ['bool', 'queue'=>'resource', 'desired_message_type'=>'int', '&w_received_message_type'=>'int', 'max_message_size'=>'int', '&w_message'=>'mixed', 'unserialize='=>'bool', 'flags='=>'int', '&w_error_code='=>'int'],
      'new' => ['bool', 'queue'=>'SysvMessageQueue', 'desired_message_type'=>'int', '&w_received_message_type'=>'int', 'max_message_size'=>'int', '&w_message'=>'mixed', 'unserialize='=>'bool', 'flags='=>'int', '&w_error_code='=>'int'],
    ],
    'msg_remove_queue' => [
      'old' => ['bool', 'queue'=>'resource'],
      'new' => ['bool', 'queue'=>'SysvMessageQueue'],
    ],
    'msg_send' => [
      'old' => ['bool', 'queue'=>'resource', 'message_type'=>'int', 'message'=>'mixed', 'serialize='=>'bool', 'blocking='=>'bool', '&w_error_code='=>'int'],
      'new' => ['bool', 'queue'=>'SysvMessageQueue', 'message_type'=>'int', 'message'=>'mixed', 'serialize='=>'bool', 'blocking='=>'bool', '&w_error_code='=>'int'],
    ],
    'msg_set_queue' => [
      'old' => ['bool', 'queue'=>'resource', 'data'=>'array'],
      'new' => ['bool', 'queue'=>'SysvMessageQueue', 'data'=>'array'],
    ],
    'msg_stat_queue' => [
      'old' => ['array', 'queue'=>'resource'],
      'new' => ['array', 'queue'=>'SysvMessageQueue'],
    ],
    'mysqli::__construct' => [
      'old' => ['void', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['void', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli::begin_transaction' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli::commit' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli::connect' => [
      'old' => ['null|false', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['null|false', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli::rollback' => [
      'old' => ['bool', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_begin_transaction' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_commit' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'mysqli_connect' => [
      'old' => ['mysqli|false', 'hostname='=>'string', 'username='=>'string', 'password='=>'string', 'database='=>'string', 'port='=>'int', 'socket='=>'string'],
      'new' => ['mysqli|false', 'hostname='=>'string|null', 'username='=>'string|null', 'password='=>'string|null', 'database='=>'string|null', 'port='=>'int|null', 'socket='=>'string|null'],
    ],
    'mysqli_rollback' => [
      'old' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'string'],
      'new' => ['bool', 'mysql'=>'mysqli', 'flags='=>'int', 'name='=>'?string'],
    ],
    'number_format' => [
      'old' => ['string', 'num'=>'float', 'decimals='=>'int'],
      'new' => ['string', 'num'=>'float', 'decimals='=>'int', 'decimal_separator='=>'?string', 'thousands_separator='=>'?string'],
    ],
    'numfmt_create' => [
      'old' => ['NumberFormatter|null', 'locale'=>'string', 'style'=>'int', 'pattern='=>'string'],
      'new' => ['NumberFormatter|null', 'locale'=>'string', 'style'=>'int', 'pattern='=>'?string'],
    ],
    'ob_implicit_flush' => [
      'old' => ['void', 'enable='=>'int'],
      'new' => ['void', 'enable='=>'bool'],
    ],
    'odbc_exec' => [
      'old' => ['resource', 'odbc'=>'resource', 'query'=>'string', 'flags='=>'int'],
      'new' => ['resource', 'odbc'=>'resource', 'query'=>'string'],
    ],
    'odbc_fetch_row' => [
      'old' => ['bool', 'statement'=>'resource', 'row='=>'int'],
      'new' => ['bool', 'statement'=>'resource', 'row='=>'?int'],
    ],
    'odbc_do' => [
      'old' => ['resource', 'odbc'=>'resource', 'query'=>'string', 'flags='=>'int'],
      'new' => ['resource', 'odbc'=>'resource', 'query'=>'string'],
    ],
    'odbc_tables' => [
      'old' => ['resource|false', 'odbc'=>'resource', 'catalog='=>'?string', 'schema='=>'string', 'table='=>'string', 'types='=>'string'],
      'new' => ['resource|false', 'odbc'=>'resource', 'catalog='=>'?string', 'schema='=>'?string', 'table='=>'?string', 'types='=>'?string'],
    ],
    'openssl_csr_export' => [
      'old' => ['bool', 'csr'=>'string|resource', '&w_output'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'csr'=>'OpenSSLCertificateSigningRequest|string', '&w_output'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_csr_export_to_file' => [
      'old' => ['bool', 'csr'=>'string|resource', 'output_filename'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'output_filename'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_csr_get_public_key' => [
      'old' => ['resource|false', 'csr'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['OpenSSLAsymmetricKey|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'short_names='=>'bool'],
    ],
    'openssl_csr_get_subject' => [
      'old' => ['array|false', 'csr'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['array|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'short_names='=>'bool'],
    ],
    'openssl_csr_new' => [
      'old' => ['resource|false', 'distinguished_names'=>'array', '&w_private_key'=>'resource', 'options='=>'array', 'extra_attributes='=>'array'],
      'new' => ['OpenSSLCertificateSigningRequest|false', 'distinguished_names'=>'array', '&w_private_key'=>'OpenSSLAsymmetricKey', 'options='=>'array|null', 'extra_attributes='=>'array|null'],
    ],
    'openssl_csr_sign' => [
        'old' =>  ['resource|false', 'csr'=>'string|resource', 'ca_certificate'=>'string|resource|null', 'private_key'=>'string|resource|array', 'days'=>'int', 'options='=>'array', 'serial='=>'int'],
        'new' => ['OpenSSLCertificate|false', 'csr'=>'OpenSSLCertificateSigningRequest|string', 'ca_certificate'=>'OpenSSLCertificate|string|null', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'days'=>'int', 'options='=>'array|null', 'serial='=>'int'],
    ],
    'openssl_dh_compute_key' => [
      'old' => ['string|false', 'public_key'=>'string', 'private_key'=>'resource'],
      'new' => ['string|false', 'public_key'=>'string', 'private_key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_free_key' => [
      'old' => ['void', 'key'=>'resource'],
      'new' => ['void', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_get_privatekey' => [
      'old' => ['resource|false', 'private_key'=>'string', 'passphrase='=>'string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase='=>'?string'],
    ],
    'openssl_get_publickey' => [
      'old' => ['resource|false', 'public_key'=>'resource|string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_open' => [
      'old' => ['bool', 'data'=>'string', '&w_output'=>'string', 'encrypted_key'=>'string', 'private_key'=>'string|array|resource', 'cipher_algo='=>'string', 'iv='=>'string'],
      'new' => ['bool', 'data'=>'string', '&w_output'=>'string', 'encrypted_key'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'cipher_algo'=>'string', 'iv='=>'string|null'],
    ],
    'openssl_pkcs12_export' => [
      'old' => ['bool', 'certificate'=>'string|resource', '&w_output'=>'string', 'private_key'=>'string|array|resource', 'passphrase'=>'string', 'options='=>'array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', '&w_output'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase'=>'string', 'options='=>'array'],
    ],
    'openssl_pkcs12_export_to_file' => [
      'old' => ['bool', 'certificate'=>'string|resource', 'output_filename'=>'string', 'private_key'=>'string|array|resource', 'passphrase'=>'string', 'options='=>'array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'output_filename'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'passphrase'=>'string', 'options='=>'array'],
    ],
    'openssl_pkcs7_decrypt' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource', 'private_key='=>'string|resource|array'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|string', 'private_key='=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string|null'],
    ],
    'openssl_pkcs7_encrypt' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource|array', 'headers'=>'array', 'flags='=>'int', 'cipher_algo='=>'int'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|list<OpenSSLCertificate|string>|string', 'headers'=>'array|null', 'flags='=>'int', 'cipher_algo='=>'int'],
    ],
    'openssl_pkcs7_sign' => [
      'old' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'string|resource', 'private_key'=>'string|resource|array', 'headers'=>'array', 'flags='=>'int', 'untrusted_certificates_filename='=>'string'],
      'new' => ['bool', 'input_filename'=>'string', 'output_filename'=>'string', 'certificate'=>'OpenSSLCertificate|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'headers'=>'array|null', 'flags='=>'int', 'untrusted_certificates_filename='=>'string|null'],
    ],
    'openssl_pkcs7_verify' => [
      'old' => ['bool|int', 'input_filename'=>'string', 'flags'=>'int', 'signers_certificates_filename='=>'string', 'ca_info='=>'array', 'untrusted_certificates_filename='=>'string', 'content='=>'string', 'output_filename='=>'string'],
      'new' => ['bool|int', 'input_filename'=>'string', 'flags'=>'int', 'signers_certificates_filename='=>'?string', 'ca_info='=>'array', 'untrusted_certificates_filename='=>'?string', 'content='=>'?string', 'output_filename='=>'?string'],
    ],
    'openssl_pkey_derive' => [
      'old' => ['string|false', 'public_key'=>'mixed', 'private_key'=>'mixed', 'key_length='=>'?int'],
      'new' => ['string|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'key_length='=>'int'],
    ],
    'openssl_pkey_export' => [
      'old' => ['bool', 'key'=>'resource', '&w_output'=>'string', 'passphrase='=>'string|null', 'options='=>'array'],
      'new' => ['bool', 'key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', '&w_output'=>'string', 'passphrase='=>'string|null', 'options='=>'array|null'],
    ],
    'openssl_pkey_export_to_file' => [
      'old' => ['bool', 'key'=>'resource|string|array', 'output_filename'=>'string', 'passphrase='=>'string|null', 'options='=>'array'],
      'new' => ['bool', 'key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'output_filename'=>'string', 'passphrase='=>'string|null', 'options='=>'array|null'],
    ],
    'openssl_pkey_free' => [
      'old' => ['void', 'key'=>'resource'],
      'new' => ['void', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_pkey_get_details' => [
      'old' => ['array|false', 'key'=>'resource'],
      'new' => ['array|false', 'key'=>'OpenSSLAsymmetricKey'],
    ],
    'openssl_pkey_get_private' => [
      'old' => ['resource|false', 'private_key'=>'string', 'passphrase='=>'string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array|string', 'passphrase='=>'?string'],
    ],
    'openssl_pkey_get_public' => [
      'old' => ['resource|false', 'public_key'=>'resource|string'],
      'new' => ['OpenSSLAsymmetricKey|false', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_pkey_new' => [
      'old' => ['resource|false', 'options='=>'array'],
      'new' => ['OpenSSLAsymmetricKey|false', 'options='=>'array|null'],
    ],
    'openssl_private_decrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'private_key'=>'string|resource|array', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_private_encrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'private_key'=>'string|resource|array', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_public_decrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'public_key'=>'string|resource', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_decrypted_data'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_public_encrypt' => [
      'old' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'public_key'=>'string|resource', 'padding='=>'int'],
      'new' => ['bool', 'data'=>'string', '&w_encrypted_data'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'padding='=>'int'],
    ],
    'openssl_seal' => [
      'old' => ['int|false', 'data'=>'string', '&w_sealed_data'=>'string', '&w_encrypted_keys'=>'array', 'public_key'=>'array', 'cipher_algo='=>'string', '&rw_iv='=>'string'],
      'new' => ['int|false', 'data'=>'string', '&w_sealed_data'=>'string', '&w_encrypted_keys'=>'array', 'public_key'=>'list<OpenSSLAsymmetricKey>', 'cipher_algo'=>'string', '&rw_iv='=>'string'],
    ],
    'openssl_sign' => [
      'old' => ['bool', 'data'=>'string', '&w_signature'=>'string', 'private_key'=>'resource|string', 'algorithm='=>'int|string'],
      'new' => ['bool', 'data'=>'string', '&w_signature'=>'string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'algorithm='=>'int|string'],
    ],
    'openssl_spki_new' => [
      'old' => ['?string', 'private_key'=>'resource', 'challenge'=>'string', 'digest_algo='=>'int'],
      'new' => ['string|false', 'private_key'=>'OpenSSLAsymmetricKey', 'challenge'=>'string', 'digest_algo='=>'int'],
    ],
    'openssl_verify' => [
      'old' => ['-1|0|1', 'data'=>'string', 'signature'=>'string', 'public_key'=>'resource|string', 'algorithm='=>'int|string'],
      'new' => ['-1|0|1|false', 'data'=>'string', 'signature'=>'string', 'public_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string', 'algorithm='=>'int|string'],
    ],
    'openssl_x509_check_private_key' => [
      'old' => ['bool', 'certificate'=>'string|resource', 'private_key'=>'string|resource|array'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'private_key'=>'OpenSSLAsymmetricKey|OpenSSLCertificate|array{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string'],
    ],
    'openssl_x509_checkpurpose' => [
      'old' => ['bool|int', 'certificate'=>'string|resource', 'purpose'=>'int', 'ca_info='=>'array', 'untrusted_certificates_file='=>'string'],
      'new' => ['bool|int', 'certificate'=>'OpenSSLCertificate|string', 'purpose'=>'int', 'ca_info='=>'array', 'untrusted_certificates_file='=>'string|null'],
    ],
    'openssl_x509_export' => [
      'old' => ['bool', 'certificate'=>'string|resource', '&w_output'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', '&w_output'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_x509_export_to_file' => [
      'old' =>  ['bool', 'certificate'=>'string|resource', 'output_filename'=>'string', 'no_text='=>'bool'],
      'new' => ['bool', 'certificate'=>'OpenSSLCertificate|string', 'output_filename'=>'string', 'no_text='=>'bool'],
    ],
    'openssl_x509_fingerprint' => [
      'old' => ['string|false', 'certificate'=>'string|resource', 'digest_algo='=>'string', 'binary='=>'bool'],
      'new' => ['string|false', 'certificate'=>'OpenSSLCertificate|string', 'digest_algo='=>'string', 'binary='=>'bool'],
    ],
    'openssl_x509_free' => [
      'old' => ['void', 'certificate'=>'resource'],
      'new' => ['void', 'certificate'=>'OpenSSLCertificate'],
    ],
    'openssl_x509_parse' => [
      'old' => ['array|false', 'certificate'=>'string|resource', 'short_names='=>'bool'],
      'new' => ['array|false', 'certificate'=>'OpenSSLCertificate|string', 'short_names='=>'bool'],
    ],
    'openssl_x509_read' => [
      'old' => ['resource|false', 'certificate'=>'string|resource'],
      'new' => ['OpenSSLCertificate|false', 'certificate'=>'OpenSSLCertificate|string'],
    ],
    'openssl_x509_verify' => [
      'old' => ['int', 'certificate'=>'string|resource', 'public_key'=>'string|array|resource'],
      'new' => ['int', 'certificate'=>'string|OpenSSLCertificate', 'public_key'=>'string|OpenSSLCertificate|OpenSSLAsymmetricKey|array'],
    ],
    'pack' => [
      'old' => ['string|false', 'format'=>'string', '...values='=>'mixed'],
      'new' => ['string', 'format'=>'string', '...values='=>'mixed'],
    ],
    'parse_str' => [
      'old' => ['void', 'string'=>'string', '&w_result='=>'array'],
      'new' => ['void', 'string'=>'string', '&w_result'=>'array'],
    ],
    'password_hash' => [
      'old' => ['string|false', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
      'new' => ['string', 'password'=>'string', 'algo'=>'int|string|null', 'options='=>'array'],
    ],
    'pcntl_async_signals' => [
      'old' => ['bool', 'enable='=>'bool'],
      'new' => ['bool', 'enable='=>'?bool'],
    ],
    'pcntl_exec' => [
      'old' => ['null|false', 'path'=>'string', 'args='=>'array', 'env_vars='=>'array'],
      'new' => ['false', 'path'=>'string', 'args='=>'array', 'env_vars='=>'array'],
    ],
    'pcntl_getpriority' => [
      'old' => ['int', 'process_id='=>'int', 'mode='=>'int'],
      'new' => ['int', 'process_id='=>'?int', 'mode='=>'int'],
    ],
    'pcntl_setpriority' => [
      'old' => ['bool', 'priority'=>'int', 'process_id='=>'int', 'mode='=>'int'],
      'new' => ['bool', 'priority'=>'int', 'process_id='=>'?int', 'mode='=>'int'],
    ],
    'pfsockopen' => [
      'old' => ['resource|false', 'hostname'=>'string', 'port='=>'int', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'float'],
      'new' => ['resource|false', 'hostname'=>'string', 'port='=>'int', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'?float'],
    ],
    'pg_client_encoding' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_close' => [
      'old' => ['bool', 'connection='=>'resource'],
      'new' => ['bool', 'connection='=>'?resource'],
    ],
    'pg_dbname' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_end_copy' => [
      'old' => ['bool', 'connection='=>'resource'],
      'new' => ['bool', 'connection='=>'?resource'],
    ],
    'pg_last_error' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_lo_write' => [
      'old' => ['int|false', 'lob'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'lob'=>'resource', 'data'=>'string', 'length='=>'?int'],
    ],
    'pg_options' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_ping' => [
      'old' => ['bool', 'connection='=>'resource'],
      'new' => ['bool', 'connection='=>'?resource'],
    ],
    'pg_port' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_trace' => [
      'old' => ['bool', 'filename'=>'string', 'mode='=>'string', 'connection='=>'resource'],
      'new' => ['bool', 'filename'=>'string', 'mode='=>'string', 'connection='=>'?resource'],
    ],
    'pg_tty' => [
      'old' => ['string', 'connection='=>'resource'],
      'new' => ['string', 'connection='=>'?resource'],
    ],
    'pg_untrace' => [
      'old' => ['bool', 'connection='=>'resource'],
      'new' => ['bool', 'connection='=>'?resource'],
    ],
    'pg_version' => [
      'old' => ['array', 'connection='=>'resource'],
      'new' => ['array', 'connection='=>'?resource'],
    ],
    'phpversion' => [
      'old' => ['string|false', 'extension='=>'string'],
      'new' => ['string|false', 'extension='=>'?string'],
    ],
    'proc_get_status' => [
      'old' => ['array{command: string, pid: int, running: bool, signaled: bool, stopped: bool, exitcode: int, termsig: int, stopsig: int}|false', 'process'=>'resource'],
      'new' => ['array{command: string, pid: int, running: bool, signaled: bool, stopped: bool, exitcode: int, termsig: int, stopsig: int}', 'process'=>'resource'],
    ],
    'readline_info' => [
      'old' => ['mixed', 'var_name='=>'string', 'value='=>'string|int|bool'],
      'new' => ['mixed', 'var_name='=>'?string', 'value='=>'string|int|bool|null'],
    ],
    'readline_read_history' => [
      'old'=> ['bool', 'filename='=>'string'],
      'new'=> ['bool', 'filename='=>'?string'],
    ],
    'readline_write_history' => [
      'old' => ['bool', 'filename='=>'string'],
      'new' => ['bool', 'filename='=>'?string'],
    ],
    'sapi_windows_vt100_support' => [
      'old' => ['bool', 'stream'=>'resource', 'enable='=>'bool'],
      'new' => ['bool', 'stream'=>'resource', 'enable='=>'?bool'],
    ],
    'sem_acquire' => [
      'old' => ['bool', 'semaphore'=>'resource', 'non_blocking='=>'bool'],
      'new' => ['bool', 'semaphore'=>'SysvSemaphore', 'non_blocking='=>'bool'],
    ],
    'sem_get' => [
      'old' => ['resource|false', 'key'=>'int', 'max_acquire='=>'int', 'permissions='=>'int', 'auto_release='=>'bool'],
      'new' => ['SysvSemaphore|false', 'key'=>'int', 'max_acquire='=>'int', 'permissions='=>'int', 'auto_release='=>'bool'],
    ],
    'sem_release' => [
      'old' => ['bool', 'semaphore'=>'resource'],
      'new' => ['bool', 'semaphore'=>'SysvSemaphore'],
    ],
    'sem_remove' => [
      'old' => ['bool', 'semaphore'=>'resource'],
      'new' => ['bool', 'semaphore'=>'SysvSemaphore'],
    ],
    'session_cache_expire' => [
      'old' => ['int|false', 'value='=>'int'],
      'new' => ['int|false', 'value='=>'?int'],
    ],
    'session_cache_limiter' => [
      'old' => ['string|false', 'value='=>'string'],
      'new' => ['string|false', 'value='=>'?string'],
    ],
    'session_id' => [
      'old' => ['string|false', 'id='=>'string'],
      'new' => ['string|false', 'id='=>'?string'],
    ],
    'session_module_name' => [
      'old' => ['string|false', 'module='=>'string'],
      'new' => ['string|false', 'module='=>'?string'],
    ],
    'session_name' => [
      'old' => ['string|false', 'name='=>'string'],
      'new' => ['string|false', 'name='=>'?string'],
    ],
    'session_save_path' => [
      'old' => ['string|false', 'path='=>'string'],
      'new' => ['string|false', 'path='=>'?string'],
    ],
    'session_set_cookie_params' => [
      'old' => ['bool', 'lifetime'=>'int', 'path='=>'string', 'domain='=>'string', 'secure='=>'bool', 'httponly='=>'bool'],
      'new' => ['bool', 'lifetime'=>'int', 'path='=>'?string', 'domain='=>'?string', 'secure='=>'?bool', 'httponly='=>'?bool'],
    ],
    'shm_attach' => [
      'old' => ['resource|false', 'key'=>'int', 'size='=>'int', 'permissions='=>'int'],
      'new' => ['SysvSharedMemory|false', 'key'=>'int', 'size='=>'?int', 'permissions='=>'int'],
    ],
    'shm_detach' => [
      'old' => ['bool', 'shm'=>'resource'],
      'new' => ['bool', 'shm'=>'SysvSharedMemory'],
    ],
    'shm_get_var' => [
      'old' => ['mixed', 'shm'=>'resource', 'key'=>'int'],
      'new' => ['mixed', 'shm'=>'SysvSharedMemory', 'key'=>'int'],
    ],
    'shm_has_var' => [
      'old' => ['bool', 'shm'=>'resource', 'key'=>'int'],
      'new' => ['bool', 'shm'=>'SysvSharedMemory', 'key'=>'int'],
    ],
    'shm_put_var' => [
      'old' => ['bool', 'shm'=>'resource', 'key'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'shm'=>'SysvSharedMemory', 'key'=>'int', 'value'=>'mixed'],
    ],
    'shm_remove' => [
      'old' => ['bool', 'shm'=>'resource'],
      'new' => ['bool', 'shm'=>'SysvSharedMemory'],
    ],
    'shm_remove_var' => [
      'old' => ['bool', 'shm'=>'resource', 'key'=>'int'],
      'new' => ['bool', 'shm'=>'SysvSharedMemory', 'key'=>'int'],
    ],
    'shmop_close' => [
      'old' => ['void', 'shmop'=>'resource'],
      'new' => ['void', 'shmop'=>'Shmop'],
    ],
    'shmop_delete' => [
      'old' => ['bool', 'shmop'=>'resource'],
      'new' => ['bool', 'shmop'=>'Shmop'],
    ],
    'shmop_open' => [
      'old' => ['resource|false', 'key'=>'int', 'mode'=>'string', 'permissions'=>'int', 'size'=>'int'],
      'new' => ['Shmop|false', 'key'=>'int', 'mode'=>'string', 'permissions'=>'int', 'size'=>'int'],
    ],
    'shmop_read' => [
      'old' => ['string|false', 'shmop'=>'resource', 'offset'=>'int', 'size'=>'int'],
      'new' => ['string', 'shmop'=>'Shmop', 'offset'=>'int', 'size'=>'int'],
    ],
    'shmop_size' => [
      'old' => ['int', 'shmop'=>'resource'],
      'new' => ['int', 'shmop'=>'Shmop'],
    ],
    'shmop_write' => [
      'old' => ['int|false', 'shmop'=>'resource', 'data'=>'string', 'offset'=>'int'],
      'new' => ['int', 'shmop'=>'Shmop', 'data'=>'string', 'offset'=>'int'],
    ],
    'sleep' => [
      'old' => ['int|false', 'seconds'=>'0|positive-int'],
      'new' => ['int', 'seconds'=>'0|positive-int'],
    ],
    'socket_accept' => [
      'old' => ['resource|false', 'socket'=>'resource'],
      'new' => ['Socket|false', 'socket'=>'Socket'],
    ],
    'socket_addrinfo_bind' => [
      'old' => ['?resource', 'addrinfo'=>'resource'],
      'new' => ['Socket|false', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_connect' => [
      'old' => ['resource', 'addrinfo'=>'resource'],
      'new' => ['Socket|false', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_explain' => [
      'old' => ['array', 'addrinfo'=>'resource'],
      'new' => ['array', 'address'=>'AddressInfo'],
    ],
    'socket_addrinfo_lookup' => [
      'old' => ['resource[]', 'host'=>'string', 'service='=>'string', 'hints='=>'array'],
      'new' => ['false|AddressInfo[]', 'host'=>'string', 'service='=>'?string', 'hints='=>'array'],
    ],
    'socket_bind' => [
      'old' => ['bool', 'socket'=>'resource', 'address'=>'string', 'port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'address'=>'string', 'port='=>'int'],
    ],
    'socket_clear_error' => [
      'old' => ['void', 'socket='=>'resource'],
      'new' => ['void', 'socket='=>'?Socket'],
    ],
    'socket_close' => [
      'old' => ['void', 'socket'=>'resource'],
      'new' => ['void', 'socket'=>'Socket'],
    ],
    'socket_connect' => [
      'old' => ['bool', 'socket'=>'resource', 'address'=>'string', 'port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'address'=>'string', 'port='=>'?int'],
    ],
    'socket_create' => [
      'old' => ['resource|false', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int'],
      'new' => ['Socket|false', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int'],
    ],
    'socket_create_listen' => [
      'old' => ['resource|false', 'port'=>'int', 'backlog='=>'int'],
      'new' => ['Socket|false', 'port'=>'int', 'backlog='=>'int'],
    ],
    'socket_create_pair' => [
      'old' => ['bool', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int', '&w_pair'=>'resource[]'],
      'new' => ['bool', 'domain'=>'int', 'type'=>'int', 'protocol'=>'int', '&w_pair'=>'Socket[]'],
    ],
    'socket_export_stream' => [
      'old' => ['resource|false', 'socket'=>'resource'],
      'new' => ['resource|false', 'socket'=>'Socket'],
    ],
    'socket_get_option' => [
      'old' => ['array|int|false', 'socket'=>'resource', 'level'=>'int', 'option'=>'int'],
      'new' => ['array|int|false', 'socket'=>'Socket', 'level'=>'int', 'option'=>'int'],
    ],
    'socket_get_status' => [
      'old' => ['array', 'stream'=>'resource'],
      'new' => ['array', 'stream'=>'Socket'],
    ],
    'socket_getopt' => [
      'old' => ['array|int|false', 'socket'=>'resource', 'level'=>'int', 'option'=>'int'],
      'new' => ['array|int|false', 'socket'=>'Socket', 'level'=>'int', 'option'=>'int'],
    ],
    'socket_getpeername' => [
      'old' => ['bool', 'socket'=>'resource', '&w_address'=>'string', '&w_port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', '&w_address'=>'string', '&w_port='=>'int'],
    ],
    'socket_getsockname' => [
      'old' => ['bool', 'socket'=>'resource', '&w_address'=>'string', '&w_port='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', '&w_address'=>'string', '&w_port='=>'int'],
    ],
    'socket_import_stream' => [
      'old' => ['resource|false', 'stream'=>'resource'],
      'new' => ['Socket|false', 'stream'=>'resource'],
    ],
    'socket_last_error' => [
      'old' => ['int', 'socket='=>'resource'],
      'new' => ['int', 'socket='=>'?Socket'],
    ],
    'socket_listen' => [
      'old' => ['bool', 'socket'=>'resource', 'backlog='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'backlog='=>'int'],
    ],
    'socket_read' => [
      'old' => ['string|false', 'socket'=>'resource', 'length'=>'int', 'mode='=>'int'],
      'new' => ['string|false', 'socket'=>'Socket', 'length'=>'int', 'mode='=>'int'],
    ],
    'socket_recv' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_data'=>'string', 'length'=>'int', 'flags'=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_data'=>'string', 'length'=>'int', 'flags'=>'int'],
    ],
    'socket_recvfrom' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_data'=>'string', 'length'=>'int', 'flags'=>'int', '&w_address'=>'string', '&w_port='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_data'=>'string', 'length'=>'int', 'flags'=>'int', '&w_address'=>'string', '&w_port='=>'int'],
    ],
    'socket_recvmsg' => [
      'old' => ['int|false', 'socket'=>'resource', '&w_message'=>'array', 'flags='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', '&w_message'=>'array', 'flags='=>'int'],
    ],
    'socket_select' => [
      'old' => ['int|false', '&rw_read'=>'resource[]|null', '&rw_write'=>'resource[]|null', '&rw_except'=>'resource[]|null', 'seconds'=>'int|null', 'microseconds='=>'int'],
      'new' => ['int|false', '&rw_read'=>'Socket[]|null', '&rw_write'=>'Socket[]|null', '&rw_except'=>'Socket[]|null', 'seconds'=>'int|null', 'microseconds='=>'int'],
    ],
    'socket_send' => [
      'old' => ['int|false', 'socket'=>'resource', 'data'=>'string', 'length'=>'int', 'flags'=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'data'=>'string', 'length'=>'int', 'flags'=>'int'],
    ],
    'socket_sendmsg' => [
      'old' => ['int|false', 'socket'=>'resource', 'message'=>'array', 'flags='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'message'=>'array', 'flags='=>'int'],
    ],
    'socket_sendto' => [
      'old' => ['int|false', 'socket'=>'resource', 'data'=>'string', 'length'=>'int', 'flags'=>'int', 'address'=>'string', 'port='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'data'=>'string', 'length'=>'int', 'flags'=>'int', 'address'=>'string', 'port='=>'?int'],
    ],
    'socket_set_block' => [
      'old' => ['bool', 'socket'=>'resource'],
      'new' => ['bool', 'socket'=>'Socket'],
    ],
    'socket_set_blocking' => [
      'old' => ['bool', 'stream'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'stream'=>'Socket', 'enable'=>'bool'],
    ],
    'socket_set_nonblock' => [
      'old' => ['bool', 'socket'=>'resource'],
      'new' => ['bool', 'socket'=>'Socket'],
    ],
    'socket_set_option' => [
      'old' => ['bool', 'socket'=>'resource', 'level'=>'int', 'option'=>'int', 'value'=>'int|string|array'],
      'new' => ['bool', 'socket'=>'Socket', 'level'=>'int', 'option'=>'int', 'value'=>'int|string|array'],
    ],
    'socket_set_timeout' => [
      'old' => ['bool', 'stream'=>'resource', 'seconds'=>'int', 'microseconds='=>'int'],
      'new' => ['bool', 'stream'=>'resource', 'seconds'=>'int', 'microseconds='=>'int'],
    ],
    'socket_setopt' => [
      'old' => ['bool', 'socket'=>'resource', 'level'=>'int', 'option'=>'int', 'value'=>'int|string|array'],
      'new' => ['bool', 'socket'=>'Socket', 'level'=>'int', 'option'=>'int', 'value'=>'int|string|array'],
    ],
    'socket_shutdown' => [
      'old' => ['bool', 'socket'=>'resource', 'mode='=>'int'],
      'new' => ['bool', 'socket'=>'Socket', 'mode='=>'int'],
    ],
    'socket_write' => [
      'old' => ['int|false', 'socket'=>'resource', 'data'=>'string', 'length='=>'int'],
      'new' => ['int|false', 'socket'=>'Socket', 'data'=>'string', 'length='=>'int|null'],
    ],
    'socket_wsaprotocol_info_export' => [
      'old' => ['string|false', 'socket'=>'resource', 'process_id'=>'int'],
      'new' => ['string|false', 'socket'=>'Socket', 'process_id'=>'int'],
    ],
    'socket_wsaprotocol_info_import' => [
      'old' => ['resource|false', 'info_id'=>'string'],
      'new' => ['Socket|false', 'info_id'=>'string'],
    ],
    'spl_autoload' => [
      'old' => ['void', 'class'=>'string', 'file_extensions='=>'string'],
      'new' => ['void', 'class'=>'string', 'file_extensions='=>'?string'],
    ],
    'spl_autoload_extensions' => [
      'old' => ['string', 'file_extensions='=>'string'],
      'new' => ['string', 'file_extensions='=>'?string'],
    ],
    'spl_autoload_functions' => [
      'old' => ['false|list<callable(string):void>'],
      'new' => ['list<callable(string):void>'],
    ],
    'spl_autoload_register' => [
      'old' => ['bool', 'callback='=>'callable(string):void', 'throw='=>'bool', 'prepend='=>'bool'],
      'new' => ['bool', 'callback='=>'callable(string):void|null', 'throw='=>'bool', 'prepend='=>'bool'],
    ],
    'str_word_count' => [
      'old' => ['array<int, string>|int', 'string'=>'string', 'format='=>'int', 'characters='=>'string'],
      'new' => ['array<int, string>|int', 'string'=>'string', 'format='=>'int', 'characters='=>'?string'],
    ],
    'strchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'strcspn' => [
      'old' => ['int', 'string'=>'string', 'characters'=>'string', 'offset='=>'int', 'length='=>'int'],
      'new' => ['int', 'string'=>'string', 'characters'=>'string', 'offset='=>'int', 'length='=>'?int'],
    ],
    'stream_context_create' => [
      'old' => ['resource', 'options='=>'array', 'params='=>'array'],
      'new' => ['resource', 'options='=>'?array', 'params='=>'?array'],
    ],
    'stream_context_get_default' => [
      'old' => ['resource', 'options='=>'array'],
      'new' => ['resource', 'options='=>'?array'],
    ],
    'stream_copy_to_stream' => [
      'old' => ['int|false', 'from'=>'resource', 'to'=>'resource', 'length='=>'int', 'offset='=>'int'],
      'new' => ['int|false', 'from'=>'resource', 'to'=>'resource', 'length='=>'?int', 'offset='=>'int'],
    ],
    'stream_get_contents' => [
      'old' => ['string|false', 'stream'=>'resource', 'length='=>'int', 'offset='=>'int'],
      'new' => ['string|false', 'stream'=>'resource', 'length='=>'?int', 'offset='=>'int'],
    ],
    'stream_set_chunk_size' => [
      'old' => ['int|false', 'stream'=>'resource', 'size'=>'int'],
      'new' => ['int', 'stream'=>'resource', 'size'=>'int'],
    ],
    'stream_socket_accept' => [
      'old' => ['resource|false', 'socket'=>'resource', 'timeout='=>'float', '&w_peer_name='=>'string'],
      'new' => ['resource|false', 'socket'=>'resource', 'timeout='=>'?float', '&w_peer_name='=>'string'],
    ],
    'stream_socket_client' => [
      'old' => ['resource|false', 'address'=>'string', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'float', 'flags='=>'int', 'context='=>'resource'],
      'new' => ['resource|false', 'address'=>'string', '&w_error_code='=>'int', '&w_error_message='=>'string', 'timeout='=>'?float', 'flags='=>'int', 'context='=>'?resource'],
    ],
    'stream_socket_enable_crypto' => [
      'old' => ['int|bool', 'stream'=>'resource', 'enable'=>'bool', 'crypto_method='=>'?int', 'session_stream='=>'resource'],
      'new' => ['int|bool', 'stream'=>'resource', 'enable'=>'bool', 'crypto_method='=>'?int', 'session_stream='=>'?resource'],
    ],
    'strftime' => [
      'old' => ['string|false', 'format'=>'string', 'timestamp='=>'int'],
      'new' => ['string|false', 'format'=>'string', 'timestamp='=>'?int'],
    ],
    'strip_tags' => [
      'old' => ['string', 'string'=>'string', 'allowed_tags='=>'string|list<non-empty-string>'],
      'new' => ['string', 'string'=>'string', 'allowed_tags='=>'string|list<non-empty-string>|null'],
    ],
    'stripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'stristr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'strpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strrchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string'],
    ],
    'strripos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strrpos' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string|int', 'offset='=>'int'],
      'new' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int'],
    ],
    'strspn' => [
      'old' => ['int', 'string'=>'string', 'characters'=>'string', 'offset='=>'int', 'length='=>'int'],
      'new' => ['int', 'string'=>'string', 'characters'=>'string', 'offset='=>'int', 'length='=>'?int'],
    ],
    'strstr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string|int', 'before_needle='=>'bool'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'strtotime' => [
      'old' => ['int|false', 'datetime'=>'string', 'baseTimestamp='=>'int'],
      'new' => ['int|false', 'datetime'=>'string', 'baseTimestamp='=>'?int'],
    ],
    'substr_compare' => [
      'old' => ['int|false', 'haystack'=>'string', 'needle'=>'string', 'offset'=>'int', 'length='=>'int', 'case_insensitive='=>'bool'],
      'new' => ['int', 'haystack'=>'string', 'needle'=>'string', 'offset'=>'int', 'length='=>'?int', 'case_insensitive='=>'bool'],
    ],
    'substr_count' => [
      'old' => ['int', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'length='=>'int'],
      'new' => ['int', 'haystack'=>'string', 'needle'=>'string', 'offset='=>'int', 'length='=>'?int'],
    ],
    'substr' => [
      'old' => ['string|false', 'string'=>'string', 'offset'=>'int', 'length='=>'int'],
      'new' => ['string', 'string'=>'string', 'offset'=>'int', 'length='=>'?int'],
    ],
    'substr_replace' => [
      'old' => ['string', 'string'=>'string', 'replace'=>'string|string[]', 'offset'=>'int|int[]', 'length='=>'int|int[]'],
      'new' => ['string', 'string'=>'string', 'replace'=>'string|string[]', 'offset'=>'int|int[]', 'length='=>'int|int[]|null'],
    ],
    'substr_replace\'1' => [
      'old' => ['string[]', 'string'=>'string[]', 'replace'=>'string|string[]', 'offset'=>'int|int[]', 'length='=>'int|int[]'],
      'new' => ['string[]', 'string'=>'string[]', 'replace'=>'string|string[]', 'offset'=>'int|int[]', 'length='=>'int|int[]|null'],
    ],
    'tidy_parse_file' => [
      'old' => ['tidy', 'filename'=>'string', 'config='=>'array|string', 'encoding='=>'string', 'useIncludePath='=>'bool'],
      'new' => ['tidy', 'filename'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string', 'useIncludePath='=>'bool'],
    ],
    'tidy_parse_string' => [
      'old' => ['tidy', 'string'=>'string', 'config='=>'array|string', 'encoding='=>'string'],
      'new' => ['tidy', 'string'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string'],
    ],
    'tidy_repair_file' => [
      'old' => ['string', 'filename'=>'string', 'config='=>'array|string', 'encoding='=>'string', 'useIncludePath='=>'bool'],
      'new' => ['string', 'filename'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string', 'useIncludePath='=>'bool'],
    ],
    'tidy_repair_string' => [
      'old' => ['string', 'string'=>'string', 'config='=>'array|string', 'encoding='=>'string'],
      'new' => ['string', 'string'=>'string', 'config='=>'array|string|null', 'encoding='=>'?string'],
    ],
    'timezone_identifiers_list' => [
      'old' => ['list<string>|false', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
      'new' => ['list<string>', 'timezoneGroup='=>'int', 'countryCode='=>'?string'],
    ],
    'timezone_offset_get' => [
      'old' => ['int|false', 'object'=>'DateTimeZone', 'datetime'=>'DateTimeInterface'],
      'new' => ['int', 'object'=>'DateTimeZone', 'datetime'=>'DateTimeInterface'],
    ],
    'touch' => [
      'old' => ['bool', 'filename'=>'string', 'mtime='=>'int', 'atime='=>'int'],
      'new' => ['bool', 'filename'=>'string', 'mtime='=>'?int', 'atime='=>'?int'],
    ],
    'umask' => [
      'old' => ['int', 'mask='=>'int'],
      'new' => ['int', 'mask='=>'?int'],
    ],
    'unixtojd' => [
      'old' => ['int|false', 'timestamp='=>'int'],
      'new' => ['int|false', 'timestamp='=>'?int'],
    ],
    'xml_get_current_byte_index' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int', 'parser'=>'XMLParser'],
    ],
    'xml_get_current_column_number' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int', 'parser'=>'XMLParser'],
    ],
    'xml_get_current_line_number' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int', 'parser'=>'XMLParser'],
    ],
    'xml_get_error_code' => [
      'old' => ['int|false', 'parser'=>'resource'],
      'new' => ['int', 'parser'=>'XMLParser'],
    ],
    'xml_parse' => [
      'old' => ['int', 'parser'=>'resource', 'data'=>'string', 'is_final='=>'bool'],
      'new' => ['int', 'parser'=>'XMLParser', 'data'=>'string', 'is_final='=>'bool'],
    ],
    'xml_parse_into_struct' => [
      'old' => ['int', 'parser'=>'resource', 'data'=>'string', '&w_values'=>'array', '&w_index='=>'array'],
      'new' => ['int', 'parser'=>'XMLParser', 'data'=>'string', '&w_values'=>'array', '&w_index='=>'array'],
    ],
    'xml_parser_create' => [
      'old' => ['resource', 'encoding='=>'string'],
      'new' => ['XMLParser', 'encoding='=>'?string'],
    ],
    'xml_parser_create_ns' => [
      'old' => ['resource', 'encoding='=>'string', 'separator='=>'string'],
      'new' => ['XMLParser', 'encoding='=>'?string', 'separator='=>'string'],
    ],
    'xml_parser_free' => [
      'old' => ['bool', 'parser'=>'resource'],
      'new' => ['bool', 'parser'=>'XMLParser'],
    ],
    'xml_parser_get_option' => [
      'old' => ['string|int', 'parser'=>'resource', 'option'=>'int'],
      'new' => ['string|int', 'parser'=>'XMLParser', 'option'=>'int'],
    ],
    'xml_parser_set_option' => [
      'old' => ['bool', 'parser'=>'resource', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'parser'=>'XMLParser', 'option'=>'int', 'value'=>'mixed'],
    ],
    'xml_set_character_data_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_default_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_element_handler' => [
      'old' => ['true', 'parser'=>'resource', 'start_handler'=>'callable', 'end_handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'start_handler'=>'callable', 'end_handler'=>'callable'],
    ],
    'xml_set_end_namespace_decl_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_external_entity_ref_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_notation_decl_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_object' => [
      'old' => ['true', 'parser'=>'resource', 'object'=>'object'],
      'new' => ['true', 'parser'=>'XMLParser', 'object'=>'object'],
    ],
    'xml_set_processing_instruction_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_start_namespace_decl_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xml_set_unparsed_entity_decl_handler' => [
      'old' => ['true', 'parser'=>'resource', 'handler'=>'callable'],
      'new' => ['true', 'parser'=>'XMLParser', 'handler'=>'callable'],
    ],
    'xmlwriter_end_attribute' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_cdata' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_comment' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_document' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_end_pi' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_flush' => [
      'old' => ['string|int|false', 'writer'=>'resource', 'empty='=>'bool'],
      'new' => ['string|int', 'writer'=>'XMLWriter', 'empty='=>'bool'],
    ],
    'xmlwriter_full_end_element' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_open_memory' => [
      'old' => ['resource|false'],
      'new' => ['XMLWriter|false'],
    ],
    'xmlwriter_open_uri' => [
      'old' => ['resource|false', 'uri'=>'string'],
      'new' => ['XMLWriter|false', 'uri'=>'string'],
    ],
    'xmlwriter_output_memory' => [
      'old' => ['string', 'writer'=>'resource', 'flush='=>'bool'],
      'new' => ['string', 'writer'=>'XMLWriter', 'flush='=>'bool'],
    ],
    'xmlwriter_set_indent' => [
      'old' => ['bool', 'writer'=>'resource', 'enable'=>'bool'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'enable'=>'bool'],
    ],
    'xmlwriter_set_indent_string' => [
      'old' => ['bool', 'writer'=>'resource', 'indentation'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'indentation'=>'string'],
    ],
    'xmlwriter_start_attribute' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_attribute_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'xmlwriter_start_cdata' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_start_comment' => [
      'old' => ['bool', 'writer'=>'resource'],
      'new' => ['bool', 'writer'=>'XMLWriter'],
    ],
    'xmlwriter_start_document' => [
      'old' => ['bool', 'writer'=>'resource', 'version='=>'?string', 'encoding='=>'?string', 'standalone='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'version='=>'?string', 'encoding='=>'?string', 'standalone='=>'?string'],
    ],
    'xmlwriter_start_dtd' => [
      'old' => ['bool', 'writer'=>'resource', 'qualifiedName'=>'string', 'publicId='=>'?string', 'systemId='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'qualifiedName'=>'string', 'publicId='=>'?string', 'systemId='=>'?string'],
    ],
    'xmlwriter_start_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource', 'qualifiedName'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'qualifiedName'=>'string'],
    ],
    'xmlwriter_start_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'isParam'=>'bool'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'isParam'=>'bool'],
    ],
    'xmlwriter_start_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string'],
    ],
    'xmlwriter_start_element_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string'],
    ],
    'xmlwriter_start_pi' => [
      'old' => ['bool', 'writer'=>'resource', 'target'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'target'=>'string'],
    ],
    'xmlwriter_text' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_attribute' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'value'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'value'=>'string'],
    ],
    'xmlwriter_write_attribute_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'value'=>'string'],
    ],
    'xmlwriter_write_cdata' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_comment' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'publicId='=>'?string', 'systemId='=>'?string', 'content='=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'publicId='=>'?string', 'systemId='=>'?string', 'content='=>'?string'],
    ],
    'xmlwriter_write_dtd_attlist' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_dtd_entity' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'string', 'isParam'=>'bool', 'publicId'=>'string', 'systemId'=>'string', 'notationData'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content'=>'string', 'isParam='=>'bool', 'publicId='=>'?string', 'systemId='=>'?string', 'notationData='=>'?string'],
    ],
    'xmlwriter_write_element' => [
      'old' => ['bool', 'writer'=>'resource', 'name'=>'string', 'content'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'name'=>'string', 'content='=>'?string'],
    ],
    'xmlwriter_write_element_ns' => [
      'old' => ['bool', 'writer'=>'resource', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'string', 'content'=>'?string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'prefix'=>'?string', 'name'=>'string', 'namespace'=>'?string', 'content='=>'?string'],
    ],
    'xmlwriter_write_pi' => [
      'old' => ['bool', 'writer'=>'resource', 'target'=>'string', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'target'=>'string', 'content'=>'string'],
    ],
    'xmlwriter_write_raw' => [
      'old' => ['bool', 'writer'=>'resource', 'content'=>'string'],
      'new' => ['bool', 'writer'=>'XMLWriter', 'content'=>'string'],
    ],
    'ZipArchive::addEmptyDir' => [
      'old' => ['bool', 'dirname'=>'string'],
      'new' => ['bool', 'dirname'=>'string', 'flags='=>'int'],
    ],
    'ZipArchive::addFile' => [
      'old' => ['bool', 'filepath'=>'string', 'entryname='=>'string', 'start='=>'int', 'length='=>'int'],
      'new' => ['bool', 'filepath'=>'string', 'entryname='=>'string', 'start='=>'int', 'length='=>'int', 'flags='=>'int'],
    ],
    'ZipArchive::addFromString' => [
      'old' => ['bool', 'name'=>'string', 'content'=>'string'],
      'new' => ['bool', 'name'=>'string', 'content'=>'string', 'flags='=>'int'],
    ],
  ],
  'removed' => [
    'PDOStatement::setFetchMode\'1' => ['bool', 'fetch_column'=>'int', 'colno'=>'int'],
    'PDOStatement::setFetchMode\'2' => ['bool', 'fetch_class'=>'int', 'classname'=>'string', 'ctorargs'=>'array'],
    'PDOStatement::setFetchMode\'3' => ['bool', 'fetch_into'=>'int', 'object'=>'object'],
    'ReflectionType::isBuiltin' => ['bool'],
    'SplFileObject::fgetss' => ['string|false', 'allowable_tags='=>'string'],
    'create_function' => ['string', 'args'=>'string', 'code'=>'string'],
    'each' => ['array{0:int|string,key:int|string,1:mixed,value:mixed}', '&r_arr'=>'array'],
    'fgetss' => ['string|false', 'fp'=>'resource', 'length='=>'int', 'allowable_tags='=>'string'],
    'gmp_random' => ['GMP', 'limiter='=>'int'],
    'gzgetss' => ['string|false', 'zp'=>'resource', 'length'=>'int', 'allowable_tags='=>'string'],
    'image2wbmp' => ['bool', 'im'=>'resource', 'filename='=>'?string', 'threshold='=>'int'],
    'jpeg2wbmp' => ['bool', 'jpegname'=>'string', 'wbmpname'=>'string', 'dest_height'=>'int', 'dest_width'=>'int', 'threshold'=>'int'],
    'ldap_control_paged_result' => ['bool', 'link_identifier'=>'resource', 'pagesize'=>'int', 'iscritical='=>'bool', 'cookie='=>'string'],
    'ldap_control_paged_result_response' => ['bool', 'link_identifier'=>'resource', 'result_identifier'=>'resource', '&w_cookie'=>'string', '&w_estimated'=>'int'],
    'ldap_sort' => ['bool', 'link_identifier'=>'resource', 'result_identifier'=>'resource', 'sortfilter'=>'string'],
    'number_format\'1' => ['string', 'num'=>'float', 'decimals'=>'int', 'decimal_separator'=>'?string', 'thousands_separator'=>'?string'],
    'png2wbmp' => ['bool', 'pngname'=>'string', 'wbmpname'=>'string', 'dest_height'=>'int', 'dest_width'=>'int', 'threshold'=>'int'],
    'read_exif_data' => ['array', 'filename'=>'string', 'sections_needed='=>'string', 'sub_arrays='=>'bool', 'read_thumbnail='=>'bool'],
    'Reflection::export' => ['?string', 'r'=>'reflector', 'return='=>'bool'],
    'ReflectionClass::export' => ['?string', 'argument'=>'string|object', 'return='=>'bool'],
    'ReflectionClassConstant::export' => ['string', 'class'=>'mixed', 'name'=>'string', 'return='=>'bool'],
    'ReflectionExtension::export' => ['?string', 'name'=>'string', 'return='=>'bool'],
    'ReflectionFunction::export' => ['?string', 'name'=>'string', 'return='=>'bool'],
    'ReflectionFunctionAbstract::export' => ['?string'],
    'ReflectionMethod::export' => ['?string', 'class'=>'string', 'name'=>'string', 'return='=>'bool'],
    'ReflectionObject::export' => ['?string', 'argument'=>'object', 'return='=>'bool'],
    'ReflectionParameter::export' => ['?string', 'function'=>'string', 'parameter'=>'string', 'return='=>'bool'],
    'ReflectionProperty::export' => ['?string', 'class'=>'mixed', 'name'=>'string', 'return='=>'bool'],
    'ReflectionZendExtension::export' => ['?string', 'name'=>'string', 'return='=>'bool'],
    'SimpleXMLIterator::rewind' => ['void'],
    'SimpleXMLIterator::valid' => ['bool'],
    'SimpleXMLIterator::current' => ['?SimpleXMLIterator'],
    'SimpleXMLIterator::key' => ['string|false'],
    'SimpleXMLIterator::next' => ['void'],
    'SimpleXMLIterator::hasChildren' => ['bool'],
    'SimpleXMLIterator::getChildren' => ['?SimpleXMLIterator'],
    'SplFixedArray::current' => ['mixed'],
    'SplFixedArray::key' => ['int'],
    'SplFixedArray::next' => ['void'],
    'SplFixedArray::rewind' => ['void'],
    'SplFixedArray::valid' => ['bool'],
    'SplTempFileObject::fgetss' => ['string', 'allowable_tags='=>'string'],
    'xmlrpc_decode' => ['mixed', 'xml'=>'string', 'encoding='=>'string'],
    'xmlrpc_decode_request' => ['?array', 'xml'=>'string', '&w_method'=>'string', 'encoding='=>'string'],
    'xmlrpc_encode' => ['string', 'value'=>'mixed'],
    'xmlrpc_encode_request' => ['string', 'method'=>'string', 'params'=>'mixed', 'output_options='=>'array'],
    'xmlrpc_get_type' => ['string', 'value'=>'mixed'],
    'xmlrpc_is_fault' => ['bool', 'arg'=>'array'],
    'xmlrpc_parse_method_descriptions' => ['array', 'xml'=>'string'],
    'xmlrpc_server_add_introspection_data' => ['int', 'server'=>'resource', 'desc'=>'array'],
    'xmlrpc_server_call_method' => ['string', 'server'=>'resource', 'xml'=>'string', 'user_data'=>'mixed', 'output_options='=>'array'],
    'xmlrpc_server_create' => ['resource'],
    'xmlrpc_server_destroy' => ['int', 'server'=>'resource'],
    'xmlrpc_server_register_introspection_callback' => ['bool', 'server'=>'resource', 'function'=>'string'],
    'xmlrpc_server_register_method' => ['bool', 'server'=>'resource', 'method_name'=>'string', 'function'=>'string'],
    'xmlrpc_set_type' => ['bool', '&rw_value'=>'string|DateTime', 'type'=>'string'],
  ],
];
