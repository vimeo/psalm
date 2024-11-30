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
    'IntlRuleBasedBreakIterator::setText' => 
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
    'IntlCodePointBreakIterator::setText' => 
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
    'IntlDateFormatter::setTimeZone' => 
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
    'IntlChar::enumCharNames' => 
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
    'IntlBreakIterator::setText' => 
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
  ),
  'removed' => 
  array (
    'OutOfBoundsException::__clone' => 
    array (
      0 => 'void',
    ),
    'ArgumentCountError::__clone' => 
    array (
      0 => 'void',
    ),
    'ArithmeticError::__clone' => 
    array (
      0 => 'void',
    ),
    'BadFunctionCallException::__clone' => 
    array (
      0 => 'void',
    ),
    'BadMethodCallException::__clone' => 
    array (
      0 => 'void',
    ),
    'ClosedGeneratorException::__clone' => 
    array (
      0 => 'void',
    ),
    'DomainException::__clone' => 
    array (
      0 => 'void',
    ),
    'ErrorException::__clone' => 
    array (
      0 => 'void',
    ),
    'IntlException::__clone' => 
    array (
      0 => 'void',
    ),
    'InvalidArgumentException::__clone' => 
    array (
      0 => 'void',
    ),
    'JsonException::__clone' => 
    array (
      0 => 'void',
    ),
    'LengthException::__clone' => 
    array (
      0 => 'void',
    ),
    'LogicException::__clone' => 
    array (
      0 => 'void',
    ),
    'OutOfRangeException::__clone' => 
    array (
      0 => 'void',
    ),
    'OverflowException::__clone' => 
    array (
      0 => 'void',
    ),
    'ParseError::__clone' => 
    array (
      0 => 'void',
    ),
    'RangeException::__clone' => 
    array (
      0 => 'void',
    ),
    'ReflectionNamedType::__clone' => 
    array (
      0 => 'void',
    ),
    'ReflectionObject::__clone' => 
    array (
      0 => 'void',
    ),
    'RuntimeException::__clone' => 
    array (
      0 => 'void',
    ),
    'TypeError::__clone' => 
    array (
      0 => 'void',
    ),
    'UnderflowException::__clone' => 
    array (
      0 => 'void',
    ),
    'UnexpectedValueException::__clone' => 
    array (
      0 => 'void',
    ),
    'IntlCodePointBreakIterator::__construct' => 
    array (
      0 => 'void',
    ),
  ),
);