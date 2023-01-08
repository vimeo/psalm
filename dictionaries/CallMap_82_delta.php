<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.2 to php 8.1 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.1
 * The 'removed' section contains the signatures that were removed in php 8.2
 * The 'changed' section contains functions for which the signature has changed for php 8.2.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.1 and in PHP 8.2, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'mysqli_execute_query' => ['mysqli_result|bool', 'mysqli'=>'mysqli', 'query'=>'non-empty-string', 'params='=>'list<mixed>|null'],
    'mysqli::execute_query' => ['mysqli_result|bool', 'query'=>'non-empty-string', 'params='=>'list<mixed>|null'],
    'openssl_cipher_key_length' => ['positive-int|false', 'cipher_algo'=>'non-empty-string'],
    'curl_upkeep' => ['bool', 'handle'=>'CurlHandle'],
    'imap_is_open' => ['bool', 'imap'=>'IMAP\Connection'],
    'ini_parse_quantity' => ['int', 'shorthand'=>'non-empty-string'],
    'libxml_get_external_entity_loader' => ['(callable(string,string,array{directory:?string,intSubName:?string,extSubURI:?string,extSubSystem:?string}):(resource|string|null))|null'],
    'memory_reset_peak_usage' => ['void'],
    'sodium_crypto_stream_xchacha20_xor_ic' => ['string', 'message'=>'string', 'nonce'=>'non-empty-string', 'counter'=>'int', 'key'=>'non-empty-string'],
    'ZipArchive::clearError' => ['void'],
    'ZipArchive::getStreamIndex' => ['resource|false', 'index'=>'int', 'flags='=>'int'],
    'ZipArchive::getStreamName' => ['resource|false', 'name'=>'string', 'flags='=>'int'],
    'DateTimeInterface::__serialize' => ['array'],
    'DateTimeInterface::__unserialize' => ['void', 'data'=>'array'],
  ],

  'changed' => [
    'str_split' => [
       'old' => ['non-empty-list<string>', 'string'=>'string', 'length='=>'positive-int'],
       'new' => ['list<string>', 'string'=>'string', 'length='=>'positive-int'],
    ],
    'ReflectionUnionType::getTypes' => [
        'old' => ['non-empty-list<ReflectionNamedType>'],
        'new' => ['non-empty-list<ReflectionNamedType|ReflectionIntersectionType>'],
    ],
  ],

  'removed' => [
  ],
];
