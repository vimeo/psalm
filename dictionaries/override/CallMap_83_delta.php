<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'imagerotate' => 
    array (
      0 => 'GdImage|false',
      'image' => 'GdImage',
      'angle' => 'float',
      'background_color' => 'int',
      'ignore_transparent=' => 'bool',
    ),
    'imagick::setimageblueprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimagegreenprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimageredprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagick::setimagewhitepoint' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'json_validate' => 
    array (
      0 => 'bool',
      'json' => 'string',
      'depth=' => 'int<1, max>',
      'flags=' => 'int',
    ),
    'pg_close' => 
    array (
      0 => 'bool',
      'connection=' => 'PgSql\\Connection|null',
    ),
    'pg_trace' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'mode=' => 'string',
      'connection=' => 'PgSql\\Connection|null',
    ),
    'pg_untrace' => 
    array (
      0 => 'bool',
      'connection=' => 'PgSql\\Connection|null',
    ),
    'xml_parser_get_option' => 
    array (
      0 => 'int|string',
      'parser' => 'XMLParser',
      'option' => 'int',
    ),
  ),
  'changed' => 
  array (
    'datefmt_set_timezone' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'formatter' => 'IntlDateFormatter',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'formatter' => 'IntlDateFormatter',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'gc_status' => 
    array (
      'old' => 
      array (
        0 => 'array{collected: int, roots: int, runs: int, threshold: int}',
      ),
      'new' => 
      array (
        0 => 'array{application_time: float, buffer_size: int, collected: int, collector_time: float, destructor_time: float, free_time: float, full: bool, protected: bool, roots: int, running: bool, runs: int, threshold: int}',
      ),
    ),
    'get_class' => 
    array (
      'old' => 
      array (
        0 => 'class-string',
        'object=' => 'object',
      ),
      'new' => 
      array (
        0 => 'class-string',
        'object' => 'object',
      ),
    ),
    'get_parent_class' => 
    array (
      'old' => 
      array (
        0 => 'class-string|false',
        'object_or_class=' => 'class-string|object',
      ),
      'new' => 
      array (
        0 => 'class-string|false',
        'object_or_class' => 'class-string|object',
      ),
    ),
    'imap_clearflag_full' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
    ),
    'imap_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'flags=' => 'int',
      ),
    ),
    'imap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_expunge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_gc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'flags' => 'int',
      ),
    ),
    'imap_setflag_full' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
    ),
    'imap_undelete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
    ),
    'intlbreakiterator::settext' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'text' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'intlchar::enumcharnames' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'start' => 'int|string',
        'end' => 'int|string',
        'callback' => 'callable(int, int, int):void',
        'type=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'start' => 'int|string',
        'end' => 'int|string',
        'callback' => 'callable(int, int, int):void',
        'type=' => 'int',
      ),
    ),
    'intlcodepointbreakiterator::settext' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'text' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'intldateformatter::settimezone' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'intlrulebasedbreakiterator::settext' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'text' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'natcasesort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&rw_array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        '&rw_array' => 'array<array-key, mixed>',
      ),
    ),
    'natsort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&rw_array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        '&rw_array' => 'array<array-key, mixed>',
      ),
    ),
    'posix_getrlimit' => 
    array (
      'old' => 
      array (
        0 => 'array{\'hard core\': string, \'hard cpu\': string, \'hard data\': string, \'hard filesize\': string, \'hard maxproc\': int, \'hard memlock\': int, \'hard openfiles\': int, \'hard rss\': string, \'hard stack\': string, \'hard totalmem\': string, \'soft core\': string, \'soft cpu\': string, \'soft data\': string, \'soft filesize\': string, \'soft maxproc\': int, \'soft memlock\': int, \'soft openfiles\': int, \'soft rss\': string, \'soft stack\': int, \'soft totalmem\': string}|false',
      ),
      'new' => 
      array (
        0 => 'array{\'hard core\': string, \'hard cpu\': string, \'hard data\': string, \'hard filesize\': string, \'hard maxproc\': int, \'hard memlock\': int, \'hard openfiles\': int, \'hard rss\': string, \'hard stack\': string, \'hard totalmem\': string, \'soft core\': string, \'soft cpu\': string, \'soft data\': string, \'soft filesize\': string, \'soft maxproc\': int, \'soft memlock\': int, \'soft openfiles\': int, \'soft rss\': string, \'soft stack\': int, \'soft totalmem\': string}|false',
        'resource=' => 'int|null',
      ),
    ),
    'rsort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&rw_array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&rw_array' => 'array<array-key, mixed>',
        'flags=' => 'int',
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
    'domainexception::__clone' => 
    array (
      0 => 'void',
    ),
    'errorexception::__clone' => 
    array (
      0 => 'void',
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
    'intlcodepointbreakiterator::__construct' => 
    array (
      0 => 'void',
    ),
    'intlexception::__clone' => 
    array (
      0 => 'void',
    ),
    'invalidargumentexception::__clone' => 
    array (
      0 => 'void',
    ),
    'jsonexception::__clone' => 
    array (
      0 => 'void',
    ),
    'lengthexception::__clone' => 
    array (
      0 => 'void',
    ),
    'logicexception::__clone' => 
    array (
      0 => 'void',
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
    'rangeexception::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionnamedtype::__clone' => 
    array (
      0 => 'void',
    ),
    'reflectionobject::__clone' => 
    array (
      0 => 'void',
    ),
    'runtimeexception::__clone' => 
    array (
      0 => 'void',
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
  ),
);