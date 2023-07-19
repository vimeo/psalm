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
      'new' => ['array{runs:int,collected:int,threshold:int,roots:int,running:bool,protected:bool,full:bool,buffer_size:int}'],
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
    'hash_pbkdf2' => [
      'old' => ['non-empty-string', 'algo'=>'string', 'password'=>'string', 'salt'=>'string', 'iterations'=>'int', 'length='=>'int', 'binary='=>'bool'],
      'new' => ['non-empty-string', 'algo'=>'string', 'password'=>'string', 'salt'=>'string', 'iterations'=>'int', 'length='=>'int', 'binary='=>'bool', 'options=' => 'array'],
    ],
  ],

  'removed' => [
  ],
];
