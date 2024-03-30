<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.3 to php 7.2 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.2
 * The 'removed' section contains the signatures that were removed in php 7.3.
 * The 'changed' section contains functions for which the signature has changed for php 7.3.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.2 and in PHP 7.3, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'DateTime::createFromImmutable' => ['static', 'object'=>'DateTimeImmutable'],
    'JsonException::__clone' => ['void'],
    'JsonException::__construct' => ['void', "message="=>"string", 'code='=>'int', 'previous='=>'?Throwable'],
    'JsonException::__toString' => ['string'],
    'JsonException::__wakeup' => ['void'],
    'JsonException::getCode' => ['int'],
    'JsonException::getFile' => ['string'],
    'JsonException::getLine' => ['int'],
    'JsonException::getMessage' => ['string'],
    'JsonException::getPrevious' => ['?Throwable'],
    'JsonException::getTrace' => ['list<array{file?:string,line?:int,function:string,class?:class-string,type?:\'::\'|\'->\',args?:array<mixed>}>'],
    'JsonException::getTraceAsString' => ['string'],
    'Normalizer::getRawDecomposition' => ['?string', 'string'=>'string', 'form='=>'int'],
    'SplPriorityQueue::isCorrupted' => ['bool'],
    'array_key_first' => ['int|string|null', 'array'=>'array'],
    'array_key_last' => ['int|string|null', 'array'=>'array'],
    'fpm_get_status' => ['array|false'],
    'gc_status' => ['array{runs:int,collected:int,threshold:int,roots:int}'],
    'gmp_binomial' => ['GMP|false', 'n'=>'GMP|string|int', 'k'=>'int'],
    'gmp_kronecker' => ['int', 'num1'=>'GMP|string|int', 'num2'=>'GMP|string|int'],
    'gmp_lcm' => ['GMP', 'num1'=>'GMP|string|int', 'num2'=>'GMP|string|int'],
    'gmp_perfect_power' => ['bool', 'num'=>'GMP|string|int'],
    'hrtime' => ['array{0:int,1:int}|false', 'as_number='=>'false'],
    'hrtime\'1' => ['int|float|false', 'as_number='=>'true'],
    'is_countable' => ['bool', 'value'=>'mixed'],
    'normalizer_get_raw_decomposition' => ['string|null', 'string'=>'string', 'form='=>'int'],
    'net_get_interfaces' => ['array<string,array<string,mixed>>|false'],
    'openssl_pkey_derive' => ['string|false', 'public_key'=>'mixed', 'private_key'=>'mixed', 'key_length='=>'?int'],
    'session_set_cookie_params\'1' => ['bool', 'options'=>'array{lifetime?:?int,path?:?string,domain?:?string,secure?:?bool,httponly?:?bool,samesite?:?string}'],
    'setcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array'],
    'setrawcookie\'1' => ['bool', 'name'=>'string', 'value='=>'string', 'options='=>'array'],
    'socket_wsaprotocol_info_export' => ['string|false', 'socket'=>'resource', 'process_id'=>'int'],
    'socket_wsaprotocol_info_import' => ['resource|false', 'info_id'=>'string'],
    'socket_wsaprotocol_info_release' => ['bool', 'info_id'=>'string'],
  ],
  'changed' => [
    'array_push' => [
        'old' => ['int', '&rw_array'=>'array', '...values'=>'mixed'],
        'new' => ['int', '&rw_array'=>'array', '...values='=>'mixed'],
    ],
    'array_unshift' => [
        'old' => ['int', '&rw_array'=>'array', '...values'=>'mixed'],
        'new' => ['int', '&rw_array'=>'array', '...values='=>'mixed'],
    ],
    'bcscale' => [
      'old' => ['int', 'scale'=>'int'],
      'new' => ['int', 'scale='=>'int'],
    ],
    'define' => [
        'old' => ['bool', 'constant_name'=>'string', 'value'=>'array|scalar|null', 'case_insensitive='=>'bool'],
        'new' => ['bool', 'constant_name'=>'string', 'value'=>'array|scalar|null', 'case_insensitive='=>'false'],
    ],
    'ldap_compare' => [
      'old' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string'],
      'new' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string', 'controls='=>'array'],
    ],
    'ldap_delete' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'array'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string'],
      'new' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array'],
    ],
    'ldap_list' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
    ],
    'ldap_mod_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_mod_del' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_mod_replace' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_modify' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_modify_batch' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array', 'controls='=>'array'],
    ],
    'ldap_read' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
    ],
    'ldap_rename' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool'],
      'new' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
    ],
    'ldap_search' => [
      'old' => ['resource[]|resource|false', 'ldap'=>'resource|resource[]', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['resource[]|resource|false', 'ldap'=>'resource|resource[]', 'base'=>'array|string', 'filter'=>'array|string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int', 'controls='=>'array'],
    ],
    'mkdir' => [
        'old' => ['bool', 'directory'=>'string', 'permissions='=>'int', 'recursive='=>'bool', 'context='=>'resource'],
        'new' => ['bool', 'directory'=>'string', 'permissions='=>'int', 'recursive='=>'bool', 'context='=>'null|resource'],
    ],
  ],
  'removed' => [
  ],
];
