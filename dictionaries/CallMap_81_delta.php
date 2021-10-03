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
  ],

  'removed' => [],
];
