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
    'fsync' => ['bool', 'stream' => 'resource'],
    'fdatasync' => ['bool', 'stream' => 'resource'],
    'imageavif' => ['bool', 'image'=>'GdImage', 'file='=>'resource|string|null', 'quality='=>'int', 'speed='=>'int'],
    'imagecreatefromavif' => ['false|GdImage', 'filename'=>'string'],
    'mysqli_fetch_column' => ['null|int|float|string|false', 'result'=>'mysqli_result', 'column='=>'int'],
    'mysqli_result::fetch_column' => ['null|int|float|string|false', 'column='=>'int'],
    'CURLStringFile::__construct' => ['void', 'data'=>'string', 'postname'=>'string', 'mime='=>'string'],
    'Fiber::__construct' => ['void', 'callback'=>'callable'],
    'Fiber::start' => ['mixed', '...args'=>'mixed'],
    'Fiber::resume' => ['mixed', 'value='=>'null|mixed'],
    'Fiber::throw' => ['mixed', 'exception'=>'Throwable'],
    'Fiber::isStarted' => ['bool'],
    'Fiber::isSuspended' => ['bool'],
    'Fiber::isRunning' => ['bool'],
    'Fiber::isTerminated' => ['bool'],
    'Fiber::getReturn' => ['mixed'],
    'Fiber::getCurrent' => ['?self'],
    'Fiber::suspend' => ['mixed', 'value='=>'null|mixed'],
    'FiberError::__construct' => ['void'],
  ],

  'changed' => [
    'finfo_buffer' => [
       'old' => ['string|false', 'finfo'=>'resource', 'string'=>'string', 'flags='=>'int', 'context='=>'resource'],
       'new' => ['string|false', 'finfo'=>'finfo', 'string'=>'string', 'flags='=>'int', 'context='=>'resource'],
    ],
    'finfo_close' => [
        'old' => ['bool', 'finfo'=>'resource'],
        'new' => ['bool', 'finfo'=>'finfo'],
    ],
    'finfo_file' => [
        'old' => ['string|false', 'finfo'=>'resource', 'filename'=>'string', 'flags='=>'int', 'context='=>'resource'],
        'new' => ['string|false', 'finfo'=>'finfo', 'filename'=>'string', 'flags='=>'int', 'context='=>'resource'],
    ],
    'finfo_open' => [
        'old' => ['resource|false', 'flags='=>'int', 'magic_database='=>'string'],
        'new' => ['finfo|false', 'flags='=>'int', 'magic_database='=>'string'],
    ],
    'finfo_set_flags' => [
        'old' => ['bool', 'finfo'=>'resource', 'flags'=>'int'],
        'new' => ['bool', 'finfo'=>'finfo', 'flags'=>'int'],
    ],
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
    'hash' => [
      'old' => ['string|false', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool'],
      'new' => ['string|false', 'algo'=>'string', 'data'=>'string', 'binary='=>'bool', 'options='=>'array'],
    ],
    'hash_file' => [
      'old' => ['string|false', 'algo'=>'string', 'filename'=>'string', 'binary='=>'bool'],
      'new' => ['string|false', 'algo'=>'string', 'filename'=>'string', 'binary='=>'bool', 'options='=>'array'],
    ],
    'hash_init' => [
      'old' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
      'new' => ['HashContext', 'algo'=>'string', 'flags='=>'int', 'key='=>'string', 'options='=>'array'],
    ],
    'imap_append' => [
        'old' => ['bool', 'imap'=>'resource', 'folder'=>'string', 'message'=>'string', 'options='=>'string', 'internal_date='=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'folder'=>'string', 'message'=>'string', 'options='=>'string', 'internal_date='=>'string'],
    ],
    'imap_body' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_bodystruct' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string'],
    ],
    'imap_check' => [
        'old' => ['stdClass|false', 'imap'=>'resource'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_clearflag_full' => [
        'old' => ['bool', 'imap'=>'resource', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_close' => [
        'old' => ['bool', 'imap'=>'resource', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
    ],
    'imap_create' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_createmailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_delete' => [
        'old' => ['bool', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_deletemailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_expunge' => [
        'old' => ['bool', 'imap'=>'resource'],
        'new' => ['bool', 'imap'=>'IMAP\Connection'],
    ],
    'imap_fetch_overview' => [
        'old' => ['array|false', 'imap'=>'resource', 'sequence'=>'string', 'flags='=>'int'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchbody' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchheader' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_fetchmime' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'section'=>'string', 'flags='=>'int'],
    ],
    'imap_fetchstructure' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_fetchtext' => [
        'old' => ['string|false', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['string|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_gc' => [
        'old' => ['bool', 'imap'=>'resource', 'flags'=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'flags'=>'int'],
    ],
    'imap_get_quota' => [
        'old' => ['array|false', 'imap'=>'resource', 'quota_root'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'quota_root'=>'string'],
    ],
    'imap_get_quotaroot' => [
        'old' => ['array|false', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_getacl' => [
        'old' => ['array|false', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_getmailboxes' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_getsubscribed' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_headerinfo' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int', 'default_host='=>'string|null'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'from_length='=>'int', 'subject_length='=>'int', 'default_host='=>'string|null'],
    ],
    'imap_headers' => [
        'old' => ['array|false', 'imap'=>'resource'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_list' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_listmailbox' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_listscan' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_listsubscribed' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_lsub' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string'],
    ],
    'imap_mail_copy' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
    ],
    'imap_mail_move' => [
        'old' => ['bool', 'imap'=>'resource', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_nums'=>'string', 'mailbox'=>'string', 'flags='=>'int'],
    ],
    'imap_mailboxmsginfo' => [
        'old' => ['stdClass|false', 'imap'=>'resource'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_msgno' => [
        'old' => ['int|false', 'imap'=>'resource', 'message_uid'=>'int'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection', 'message_uid'=>'int'],
    ],
    'imap_num_msg' => [
        'old' => ['int|false', 'imap'=>'resource'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_num_recent' => [
        'old' => ['int|false', 'imap'=>'resource'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection'],
    ],
    'imap_open' => [
        'old' => ['resource|false', 'mailbox'=>'string', 'user'=>'string', 'password'=>'string', 'flags='=>'int', 'retries='=>'int', 'options='=>'?array'],
        'new' => ['IMAP\Connection|false', 'mailbox'=>'string', 'user'=>'string', 'password'=>'string', 'flags='=>'int', 'retries='=>'int', 'options='=>'?array'],
    ],
    'imap_ping' => [
        'old' => ['bool', 'imap'=>'resource'],
        'new' => ['bool', 'imap'=>'IMAP\Connection'],
    ],
    'imap_rename' => [
        'old' => ['bool', 'imap'=>'resource', 'from'=>'string', 'to'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'from'=>'string', 'to'=>'string'],
    ],
    'imap_renamemailbox' => [
        'old' => ['bool', 'imap'=>'resource', 'from'=>'string', 'to'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'from'=>'string', 'to'=>'string'],
    ],
    'imap_reopen' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string', 'flags='=>'int', 'retries='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'flags='=>'int', 'retries='=>'int'],
    ],
    'imap_savebody' => [
        'old' => ['bool', 'imap'=>'resource', 'file'=>'string|resource', 'message_num'=>'int', 'section='=>'string', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'file'=>'string|resource', 'message_num'=>'int', 'section='=>'string', 'flags='=>'int'],
    ],
    'imap_scan' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_scanmailbox' => [
        'old' => ['array|false', 'imap'=>'resource', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'reference'=>'string', 'pattern'=>'string', 'content'=>'string'],
    ],
    'imap_search' => [
        'old' => ['array|false', 'imap'=>'resource', 'criteria'=>'string', 'flags='=>'int', 'charset='=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'criteria'=>'string', 'flags='=>'int', 'charset='=>'string'],
    ],
    'imap_set_quota' => [
        'old' => ['bool', 'imap'=>'resource', 'quota_root'=>'string', 'mailbox_size'=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'quota_root'=>'string', 'mailbox_size'=>'int'],
    ],
    'imap_setacl' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string', 'user_id'=>'string', 'rights'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'user_id'=>'string', 'rights'=>'string'],
    ],
    'imap_setflag_full' => [
        'old' => ['bool', 'imap'=>'resource', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'sequence'=>'string', 'flag'=>'string', 'options='=>'int'],
    ],
    'imap_sort' => [
        'old' => ['array|false', 'imap'=>'resource', 'criteria'=>'int', 'reverse'=>'int', 'flags='=>'int', 'search_criteria='=>'string', 'charset='=>'string'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'criteria'=>'int', 'reverse'=>'int', 'flags='=>'int', 'search_criteria='=>'string', 'charset='=>'string'],
    ],
    'imap_status' => [
        'old' => ['stdClass|false', 'imap'=>'resource', 'mailbox'=>'string', 'flags'=>'int'],
        'new' => ['stdClass|false', 'imap'=>'IMAP\Connection', 'mailbox'=>'string', 'flags'=>'int'],
    ],
    'imap_subscribe' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'imap_thread' => [
        'old' => ['array|false', 'imap'=>'resource', 'flags='=>'int'],
        'new' => ['array|false', 'imap'=>'IMAP\Connection', 'flags='=>'int'],
    ],
    'imap_uid' => [
        'old' => ['int|false', 'imap'=>'resource', 'message_num'=>'int'],
        'new' => ['int|false', 'imap'=>'IMAP\Connection', 'message_num'=>'int'],
    ],
    'imap_undelete' => [
        'old' => ['bool', 'imap'=>'resource', 'message_num'=>'int', 'flags='=>'int'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'message_num'=>'int', 'flags='=>'int'],
    ],
    'imap_unsubscribe' => [
        'old' => ['bool', 'imap'=>'resource', 'mailbox'=>'string'],
        'new' => ['bool', 'imap'=>'IMAP\Connection', 'mailbox'=>'string'],
    ],
    'ldap_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_bind' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn='=>'string|null', 'password='=>'string|null'],
    ],
    'ldap_bind_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn='=>'string|null', 'password='=>'string|null', 'controls='=>'array'],
    ],
    'ldap_close' => [
      'old' => ['bool', 'ldap'=>'resource'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_compare' => [
      'old' => ['bool|int', 'ldap'=>'resource', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string'],
      'new' => ['bool|int', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'attribute'=>'string', 'value'=>'string'],
    ],
    'ldap_connect' => [
      'old' => ['resource|false', 'uri='=>'string', 'port='=>'int', 'wallet='=>'string', 'password='=>'string', 'auth_mode='=>'int'],
      'new' => ['LDAP\Connection|false', 'uri='=>'string', 'port='=>'int', 'wallet='=>'string', 'password='=>'string', 'auth_mode='=>'int'],
    ],
    'ldap_count_entries' => [
      'old' => ['int|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['int|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_delete' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string'],
    ],
    'ldap_delete_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'controls='=>'array'],
    ],
    'ldap_errno' => [
      'old' => ['int', 'ldap'=>'resource'],
      'new' => ['int', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_error' => [
      'old' => ['string', 'ldap'=>'resource'],
      'new' => ['string', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_exop' => [
      'old' => ['mixed', 'ldap'=>'resource', 'reqoid'=>'string', 'reqdata='=>'string', 'serverctrls='=>'array|null', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
      'new' => ['mixed', 'ldap'=>'LDAP\Connection', 'reqoid'=>'string', 'reqdata='=>'string', 'serverctrls='=>'array|null', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    ],
    'ldap_exop_passwd' => [
      'old' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
      'new' => ['bool|string', 'ldap'=>'LDAP\Connection', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string', '&w_controls='=>'array|null'],
    ],
    'ldap_exop_refresh' => [
      'old' => ['int|false', 'ldap'=>'resource', 'dn'=>'string', 'ttl'=>'int'],
      'new' => ['int|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'ttl'=>'int'],
    ],
    'ldap_exop_whoami' => [
      'old' => ['string|false', 'ldap'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_first_attribute' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_first_entry' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_first_reference' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_free_result' => [
      'old' => ['bool', 'ldap'=>'resource'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection'],
    ],
    'ldap_get_attributes' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_get_dn' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_get_entries' => [
      'old' => ['array|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_get_option' => [
      'old' => ['bool', 'ldap'=>'resource', 'option'=>'int', '&w_value'=>'mixed'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'option'=>'int', '&w_value'=>'mixed'],
    ],
    'ldap_get_values' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource', 'attribute'=>'string'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', 'attribute'=>'string'],
    ],
    'ldap_get_values_len' => [
      'old' => ['array|false', 'ldap'=>'resource', 'entry'=>'resource', 'attribute'=>'string'],
      'new' => ['array|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', 'attribute'=>'string'],
    ],
    'ldap_list' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'resource|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
    ],
    'ldap_mod_add' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array'],
    ],
    'ldap_mod_add_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_mod_del' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array'],
    ],
    'ldap_mod_del_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_mod_replace' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array'],
    ],
    'ldap_mod_replace_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array', 'controls='=>'array'],
    ],
    'ldap_modify' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'entry'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'entry'=>'array'],
    ],
    'ldap_modify_batch' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'modifications_info'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'modifications_info'=>'array'],
    ],
    'ldap_next_attribute' => [
      'old' => ['string|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['string|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_next_entry' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'result'=>'resource'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result'],
    ],
    'ldap_next_reference' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'entry'=>'resource'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry'],
    ],
    'ldap_parse_exop' => [
      'old' => ['bool', 'ldap'=>'resource', 'result'=>'resource', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    ],
    'ldap_parse_reference' => [
      'old' => ['bool', 'ldap'=>'resource', 'entry'=>'resource', 'referrals'=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'entry'=>'LDAP\ResultEntry', 'referrals'=>'array'],
    ],
    'ldap_parse_result' => [
      'old' => ['bool', 'ldap'=>'resource', 'result'=>'resource', '&w_error_code'=>'int', '&w_matched_dn='=>'string', '&w_error_message='=>'string', '&w_referrals='=>'array', '&w_controls='=>'array'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'result'=>'LDAP\Result', '&w_error_code'=>'int', '&w_matched_dn='=>'string', '&w_error_message='=>'string', '&w_referrals='=>'array', '&w_controls='=>'array'],
    ],
    'ldap_read' => [
      'old' => ['resource|false', 'ldap'=>'resource|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection|array', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
    ],
    'ldap_rename' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool'],
    ],
    'ldap_rename_ext' => [
      'old' => ['resource|false', 'ldap'=>'resource', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection', 'dn'=>'string', 'new_rdn'=>'string', 'new_parent'=>'string', 'delete_old_rdn'=>'bool', 'controls='=>'array'],
    ],
    'ldap_sasl_bind' => [
      'old' => ['bool', 'ldap'=>'resource', 'dn='=>'string', 'password='=>'string', 'mech='=>'string', 'realm='=>'string', 'authc_id='=>'string', 'authz_id='=>'string', 'props='=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'dn='=>'string', 'password='=>'string', 'mech='=>'string', 'realm='=>'string', 'authc_id='=>'string', 'authz_id='=>'string', 'props='=>'string'],
    ],
    'ldap_search' => [
      'old' => ['resource|false', 'ldap'=>'resource|resource[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
      'new' => ['LDAP\Connection|false', 'ldap'=>'LDAP\Connection|LDAP\Connection[]', 'base'=>'string', 'filter'=>'string', 'attributes='=>'array', 'attributes_only='=>'int', 'sizelimit='=>'int', 'timelimit='=>'int', 'deref='=>'int'],
    ],
    'ldap_set_option' => [
      'old' => ['bool', 'ldap'=>'resource|null', 'option'=>'int', 'value'=>'mixed'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection|null', 'option'=>'int', 'value'=>'mixed'],
    ],
    'ldap_set_rebind_proc' => [
      'old' => ['bool', 'ldap'=>'resource', 'callback'=>'string'],
      'new' => ['bool', 'ldap'=>'LDAP\Connection', 'callback'=>'string'],
    ],
    'mysqli_execute' => [
      'old' => ['bool', 'statement' => 'mysqli_stmt'],
      'new' => ['bool', 'statement' => 'mysqli_stmt', 'params=' => 'list<mixed>|null'],
    ],
    'mysqli_stmt_execute' => [
      'old' => ['bool', 'statement' => 'mysqli_stmt'],
      'new' => ['bool', 'statement' => 'mysqli_stmt', 'params=' => 'list<mixed>|null'],
    ],
    'mysqli_stmt::execute' => [
      'old' => ['bool'],
      'new' => ['bool', 'params=' => 'list<mixed>|null'],
    ],
    'pspell_add_to_personal' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_add_to_session' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_check' => [
      'old' => ['bool', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
    'pspell_clear_session' => [
      'old' => ['bool', 'dictionary'=>'int'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary'],
    ],
    'pspell_config_data_dir' => [
      'old' => ['bool', 'config'=>'int', 'directory'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'directory'=>'string'],
    ],
    'pspell_config_dict_dir' => [
      'old' => ['bool', 'config'=>'int', 'directory'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'directory'=>'string'],
    ],
    'pspell_config_ignore' => [
      'old' => ['bool', 'config'=>'int', 'min_length'=>'int'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'min_length'=>'int'],
    ],
    'pspell_config_mode' => [
      'old' => ['bool', 'config'=>'int', 'mode'=>'int'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'mode'=>'int'],
    ],
    'pspell_config_personal' => [
      'old' => ['bool', 'config'=>'int', 'filename'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'filename'=>'string'],
    ],
    'pspell_config_repl' => [
      'old' => ['bool', 'config'=>'int', 'filename'=>'string'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'filename'=>'string'],
    ],
    'pspell_config_runtogether' => [
      'old' => ['bool', 'config'=>'int', 'allow'=>'bool'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'allow'=>'bool'],
    ],
    'pspell_config_save_repl' => [
      'old' => ['bool', 'config'=>'int', 'save'=>'bool'],
      'new' => ['bool', 'config'=>'PSpell\Config', 'save'=>'bool'],
    ],
    'pspell_new_config' => [
      'old' => ['int|false', 'config'=>'int'],
      'new' => ['int|false', 'config'=>'PSpell\Config'],
    ],
    'pspell_save_wordlist' => [
      'old' => ['bool', 'dictionary'=>'int'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary'],
    ],
    'pspell_store_replacement' => [
      'old' => ['bool', 'dictionary'=>'int', 'misspelled'=>'string', 'correct'=>'string'],
      'new' => ['bool', 'dictionary'=>'PSpell\Dictionary', 'misspelled'=>'string', 'correct'=>'string'],
    ],
    'pspell_suggest' => [
      'old' => ['array', 'dictionary'=>'int', 'word'=>'string'],
      'new' => ['array', 'dictionary'=>'PSpell\Dictionary', 'word'=>'string'],
    ],
  ],

  'removed' => [],
];
