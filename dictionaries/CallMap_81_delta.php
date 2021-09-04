<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 8.1 to php 8.0 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 8.0
 * The 'removed' section contains the signatures that were removed in php 8.1
 * The 'changed' section contains functions for which the signature has changed for php 8.1.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 8.0 and in PHP 8.1, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'array_is_list' => ['bool', 'array' => 'array'],
  ],

  'changed' => [
    'ftp_connect' => [
      'old' => ['resource|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
      'new' => ['FTP\Connection|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
    ],
    'ftp_ssl_connect' => [
      'old' => ['resource|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
      'new' => ['FTP\Connection|false', 'hostname' => 'string', 'port=' => 'int', 'timeout=' => 'int'],
    ],
    'ftp_login' => [
      'old' => ['bool', 'ftp' => 'resource', 'username' => 'string', 'password' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'username' => 'string', 'password' => 'string'],
    ],
    'ftp_pwd' => [
      'old' => ['string|false', 'ftp' => 'resource'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_cdup' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_chdir' => [
      'old' => ['bool', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_exec' => [
      'old' => ['bool', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_raw' => [
      'old' => ['array', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['array', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_mkdir' => [
      'old' => ['string|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_rmdir' => [
      'old' => ['bool', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_chmod' => [
      'old' => ['int|false', 'ftp' => 'resource', 'permissions' => 'int', 'filename' => 'string'],
      'new' => ['int|false', 'ftp' => 'FTP\Connection', 'permissions' => 'int', 'filename' => 'string'],
    ],
    'ftp_alloc' => [
      'old' => ['bool', 'ftp' => 'resource', 'size' => 'int', '&w_response=' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'size' => 'int', '&w_response=' => 'string'],
    ],
    'ftp_nlist' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_rawlist' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string', 'recursive=' => 'bool'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string', 'recursive=' => 'bool'],
    ],
    'ftp_mlsd' => [
      'old' => ['array|false', 'ftp' => 'resource', 'directory' => 'string'],
      'new' => ['array|false', 'ftp' => 'FTP\Connection', 'directory' => 'string'],
    ],
    'ftp_systype' => [
      'old' => ['string|false', 'ftp' => 'resource'],
      'new' => ['string|false', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_fget' => [
      'old' => ['bool', 'ftp' => 'resource', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'stream' => 'FTP\Connection', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_fget' => [
      'old' => ['int', 'ftp' => 'resource', 'stream' => 'resource', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'stream' => 'FTP\Connection', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_pasv' => [
      'old' => ['bool', 'ftp' => 'resource', 'enable' => 'bool'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'enable' => 'bool'],
    ],
    'ftp_get' => [
      'old' => ['bool', 'ftp' => 'resource', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_get' => [
      'old' => ['int', 'ftp' => 'resource', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'local_filename' => 'string', 'remote_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_continue' => [
      'old' => ['int', 'ftp' => 'resource'],
      'new' => ['int', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_fput' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'stream' => 'FTP\Connection', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_nb_fput' => [
      'old' => ['int', 'ftp' => 'resource', 'remote_filename' => 'string', 'stream' => 'resource', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'stream' => 'FTP\Connection', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_put' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_append' => [
      'old' => ['bool', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int'],
    ],
    'ftp_nb_put' => [
      'old' => ['int', 'ftp' => 'resource', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'remote_filename' => 'string', 'local_filename' => 'string', 'mode=' => 'int', 'offset=' => 'int'],
    ],
    'ftp_size' => [
      'old' => ['int', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_mdtm' => [
      'old' => ['int', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['int', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_rename' => [
      'old' => ['bool', 'ftp' => 'resource', 'from' => 'string', 'to' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'from' => 'string', 'to' => 'string'],
    ],
    'ftp_delete' => [
      'old' => ['bool', 'ftp' => 'resource', 'filename' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'filename' => 'string'],
    ],
    'ftp_site' => [
      'old' => ['bool', 'ftp' => 'resource', 'command' => 'string'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'command' => 'string'],
    ],
    'ftp_close' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_quit' => [
      'old' => ['bool', 'ftp' => 'resource'],
      'new' => ['bool', 'ftp' => 'FTP\Connection'],
    ],
    'ftp_set_option' => [
      'old' => ['bool', 'ftp' => 'resource', 'option' => 'int', 'value' => 'mixed'],
      'new' => ['bool', 'ftp' => 'FTP\Connection', 'option' => 'int', 'value' => 'mixed'],
    ],
    'ftp_get_option' => [
      'old' => ['mixed|false', 'ftp' => 'resource', 'option' => 'int'],
      'new' => ['mixed|false', 'ftp' => 'FTP\Connection', 'option' => 'int'],
    ],
  ],

  'removed' => [],
];
