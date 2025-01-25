<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'json_validate' => 
    array (
      0 => 'bool',
      'json' => 'string',
      'depth=' => 'int<1, max>',
      'flags=' => 'int',
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
    'imagerotate' => 
    array (
      'old' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'angle' => 'float',
        'background_color' => 'int',
        'ignore_transparent=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'GdImage|false',
        'image' => 'GdImage',
        'angle' => 'float',
        'background_color' => 'int',
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
    'intlcal_clear' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'field=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'field=' => 'int|null',
      ),
    ),
    'intlcal_set_first_day_of_week' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'dayOfWeek' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'dayOfWeek' => 'int',
      ),
    ),
    'intlcal_set_lenient' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'lenient' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'calendar' => 'IntlCalendar',
        'lenient' => 'bool',
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
    'mt_srand' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'seed=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'seed=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'natcasesort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'natsort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
      ),
    ),
    'pg_trace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'PgSql\\Connection|null',
        'trace_mode=' => 'int',
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
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'srand' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'seed=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'seed=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'strrchr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'before_needle=' => 'bool',
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