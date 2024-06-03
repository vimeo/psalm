<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.3 to php 8.2 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.2
 * The 'removed' section contains the signatures that were removed in php 8.3
 * The 'changed' section contains functions for which the signature has changed for php 8.3.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.2 and in PHP 8.3, respectively
 *
 * @see CallMap.php
 * @see https://php.watch/versions/8.3
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'json_validate' => ['bool', 'json'=>'string', 'depth='=>'positive-int', 'flags='=>'int'],
  ],

  'changed' => [
    'gc_status' => [
      'old' => ['array{runs:int,collected:int,threshold:int,roots:int}'],
      'new' => ['array{runs:int,collected:int,threshold:int,roots:int,running:bool,protected:bool,full:bool,buffer_size:int,application_time:float,collector_time:float,destructor_time:float,free_time:float}'],
    ],
    'srand' => [
      'old' => ['void', 'seed='=>'int', 'mode='=>'int'],
      'new' => ['void', 'seed='=>'?int', 'mode='=>'int'],
    ],
    'mt_srand' => [
      'old' => ['void', 'seed='=>'int', 'mode='=>'int'],
      'new' =>['void', 'seed='=>'?int', 'mode='=>'int'],
    ],
    'posix_getrlimit' => [
      'old' => ['array{"soft core": string, "hard core": string, "soft data": string, "hard data": string, "soft stack": integer, "hard stack": string, "soft totalmem": string, "hard totalmem": string, "soft rss": string, "hard rss": string, "soft maxproc": integer, "hard maxproc": integer, "soft memlock": integer, "hard memlock": integer, "soft cpu": string, "hard cpu": string, "soft filesize": string, "hard filesize": string, "soft openfiles": integer, "hard openfiles": integer}|false'],
      'new' => ['array{"soft core": string, "hard core": string, "soft data": string, "hard data": string, "soft stack": integer, "hard stack": string, "soft totalmem": string, "hard totalmem": string, "soft rss": string, "hard rss": string, "soft maxproc": integer, "hard maxproc": integer, "soft memlock": integer, "hard memlock": integer, "soft cpu": string, "hard cpu": string, "soft filesize": string, "hard filesize": string, "soft openfiles": integer, "hard openfiles": integer}|false', 'resource=' => '?int'],
    ],
    'natcasesort' => [
      'old' => ['bool', '&rw_array'=>'array'],
      'new' => ['true', '&rw_array'=>'array'],
    ],
    'natsort' => [
      'old' => ['bool', '&rw_array'=>'array'],
      'new' => ['true', '&rw_array'=>'array'],
    ],
    'rsort' => [
      'old' => ['bool', '&rw_array'=>'array', 'flags='=>'int'],
      'new' => ['true', '&rw_array'=>'array', 'flags='=>'int'],
    ],
    'imap_setflag_full' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_expunge' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection'],
      'new' => ['true', 'imap'=>'IMAP\Connection'],
    ],
    'imap_gc' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'flags'=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'flags'=>'int'],
    ],
    'imap_undelete' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
    ],
    'imap_delete' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'flags='=>'int'],
    ],
    'imap_clearflag_full' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_close' => [
      'old' => ['bool', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
      'new' => ['true', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
    ],
    'intlcal_clear' => [
      'old' => ['bool', 'calendar'=>'IntlCalendar', 'field='=>'?int'],
      'new' => ['true', 'calendar'=>'IntlCalendar', 'field='=>'?int'],
    ],
    'intlcal_set_lenient' => [
      'old' => ['bool', 'calendar'=>'IntlCalendar', 'lenient'=>'bool'],
      'new' => ['true', 'calendar'=>'IntlCalendar', 'lenient'=>'bool'],
    ],
    'intlcal_set_first_day_of_week' => [
      'old' => ['bool', 'calendar'=>'IntlCalendar', 'dayOfWeek'=>'int'],
      'new' => ['true', 'calendar'=>'IntlCalendar', 'dayOfWeek'=>'int'],
    ],
    'datefmt_set_timezone' => [
      'old' => ['false|null', 'formatter'=>'IntlDateFormatter', 'timezone'=>'IntlTimeZone|DateTimeZone|string|null'],
      'new' => ['bool', 'formatter'=>'IntlDateFormatter', 'timezone'=>'IntlTimeZone|DateTimeZone|string|null'],
    ],
    'IntlRuleBasedBreakIterator::setText' => [
      'old' => ['?bool', 'text'=>'string'],
      'new' => ['bool', 'text'=>'string'],
    ],
    'IntlCodePointBreakIterator::setText' => [
      'old' => ['?bool', 'text'=>'string'],
      'new' => ['bool', 'text'=>'string'],
    ],
    'IntlDateFormatter::setTimeZone' => [
      'old' => ['null|false', 'timezone'=>'IntlTimeZone|DateTimeZone|string|null'],
      'new' => ['bool', 'timezone'=>'IntlTimeZone|DateTimeZone|string|null'],
    ],
    'IntlChar::enumCharNames' => [
      'old' => ['?bool', 'start'=>'string|int', 'end'=>'string|int', 'callback'=>'callable(int,int,int):void', 'type='=>'int'],
      'new' => ['bool', 'start'=>'string|int', 'end'=>'string|int', 'callback'=>'callable(int,int,int):void', 'type='=>'int'],
    ],
    'IntlBreakIterator::setText' => [
      'old' => ['?bool', 'text'=>'string'],
      'new' => ['bool', 'text'=>'string'],
    ],
    'strrchr' => [
      'old' => ['string|false', 'haystack'=>'string', 'needle'=>'string'],
      'new' => ['string|false', 'haystack'=>'string', 'needle'=>'string', 'before_needle='=>'bool'],
    ],
    'get_class' => [
        'old' => ['class-string', 'object='=>'object'],
        'new' => ['class-string', 'object'=>'object'],
    ],
    'get_parent_class' => [
        'old' => ['class-string|false', 'object_or_class='=>'object|class-string'],
        'new' => ['class-string|false', 'object_or_class'=>'object|class-string'],
    ],
  ],

  'removed' => [
    'OutOfBoundsException::__clone' => ['void'],
    'ArgumentCountError::__clone' => ['void'],
    'ArithmeticError::__clone' => ['void'],
    'BadFunctionCallException::__clone' => ['void'],
    'BadMethodCallException::__clone' => ['void'],
    'ClosedGeneratorException::__clone' => ['void'],
    'DomainException::__clone' => ['void'],
    'ErrorException::__clone' => ['void'],
    'IntlException::__clone' => ['void'],
    'InvalidArgumentException::__clone' => ['void'],
    'JsonException::__clone' => ['void'],
    'LengthException::__clone' => ['void'],
    'LogicException::__clone' => ['void'],
    'OutOfRangeException::__clone' => ['void'],
    'OverflowException::__clone' => ['void'],
    'ParseError::__clone' => ['void'],
    'RangeException::__clone' => ['void'],
    'ReflectionNamedType::__clone' => ['void'],
    'ReflectionObject::__clone' => ['void'],
    'RuntimeException::__clone' => ['void'],
    'TypeError::__clone' => ['void'],
    'UnderflowException::__clone' => ['void'],
    'UnexpectedValueException::__clone' => ['void'],
    'IntlCodePointBreakIterator::__construct' => ['void'],
  ],
];
