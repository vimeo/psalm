<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'datetimeinterface::__serialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'datetimeinterface::__unserialize' => 
    array (
      0 => 'void',
      'data' => 'array<array-key, mixed>',
    ),
    'ftp_append' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'remote_filename' => 'string',
      'local_filename' => 'string',
      'mode=' => 'int',
    ),
    'ftp_cdup' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
    ),
    'ftp_chdir' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
    ),
    'ftp_chmod' => 
    array (
      0 => 'false|int',
      'ftp' => 'FTP\\Connection',
      'permissions' => 'int',
      'filename' => 'string',
    ),
    'ftp_close' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
    ),
    'ftp_connect' => 
    array (
      0 => 'FTP\\Connection|false',
      'hostname' => 'string',
      'port=' => 'int',
      'timeout=' => 'int',
    ),
    'ftp_delete' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'filename' => 'string',
    ),
    'ftp_exec' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'command' => 'string',
    ),
    'ftp_get' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'local_filename' => 'string',
      'remote_filename' => 'string',
      'mode=' => 'int',
      'offset=' => 'int',
    ),
    'ftp_login' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'username' => 'string',
      'password' => 'string',
    ),
    'ftp_mdtm' => 
    array (
      0 => 'int',
      'ftp' => 'FTP\\Connection',
      'filename' => 'string',
    ),
    'ftp_mkdir' => 
    array (
      0 => 'false|string',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
    ),
    'ftp_mlsd' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
    ),
    'ftp_nb_continue' => 
    array (
      0 => 'int',
      'ftp' => 'FTP\\Connection',
    ),
    'ftp_nlist' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
    ),
    'ftp_pasv' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'enable' => 'bool',
    ),
    'ftp_put' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'remote_filename' => 'string',
      'local_filename' => 'string',
      'mode=' => 'int',
      'offset=' => 'int',
    ),
    'ftp_pwd' => 
    array (
      0 => 'false|string',
      'ftp' => 'FTP\\Connection',
    ),
    'ftp_quit' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
    ),
    'ftp_raw' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'ftp' => 'FTP\\Connection',
      'command' => 'string',
    ),
    'ftp_rawlist' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
      'recursive=' => 'bool',
    ),
    'ftp_rename' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'from' => 'string',
      'to' => 'string',
    ),
    'ftp_rmdir' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'directory' => 'string',
    ),
    'ftp_site' => 
    array (
      0 => 'bool',
      'ftp' => 'FTP\\Connection',
      'command' => 'string',
    ),
    'ftp_size' => 
    array (
      0 => 'int',
      'ftp' => 'FTP\\Connection',
      'filename' => 'string',
    ),
    'ftp_ssl_connect' => 
    array (
      0 => 'FTP\\Connection|false',
      'hostname' => 'string',
      'port=' => 'int',
      'timeout=' => 'int',
    ),
    'ftp_systype' => 
    array (
      0 => 'false|string',
      'ftp' => 'FTP\\Connection',
    ),
    'imap_is_open' => 
    array (
      0 => 'bool',
      'imap' => 'IMAP\\Connection',
    ),
    'ini_parse_quantity' => 
    array (
      0 => 'int',
      'shorthand' => 'non-empty-string',
    ),
    'libxml_get_external_entity_loader' => 
    array (
      0 => 'callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
    ),
    'mysqli::execute_query' => 
    array (
      0 => 'bool|mysqli_result',
      'query' => 'non-empty-string',
      'params=' => 'list<mixed>|null',
    ),
    'mysqli_execute_query' => 
    array (
      0 => 'bool|mysqli_result',
      'mysql' => 'mysqli',
      'query' => 'non-empty-string',
      'params=' => 'list<mixed>|null',
    ),
    'openssl_cipher_key_length' => 
    array (
      0 => 'false|int<1, max>',
      'cipher_algo' => 'non-empty-string',
    ),
    'reflectionenum::getbackingtype' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'sodium_crypto_stream_xchacha20_xor_ic' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'non-empty-string',
      'counter' => 'int',
      'key' => 'non-empty-string',
    ),
    'strcasecmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
    ),
    'strcmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
    ),
    'strnatcasecmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
    ),
    'strnatcmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
    ),
    'strncasecmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
      'length' => 'int<0, max>',
    ),
    'strncmp' => 
    array (
      0 => 'int<-1, 1>',
      'string1' => 'string',
      'string2' => 'string',
      'length' => 'int<0, max>',
    ),
    'ziparchive::getstreamindex' => 
    array (
      0 => 'false|resource',
      'index' => 'int',
      'flags=' => 'int',
    ),
    'ziparchive::getstreamname' => 
    array (
      0 => 'false|resource',
      'name' => 'string',
      'flags=' => 'int',
    ),
  ),
  'changed' => 
  array (
    'dba_open' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'string',
        '...handler_params=' => 'string',
      ),
      'new' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'dba_popen' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'string',
        '...handler_params=' => 'string',
      ),
      'new' => 
      array (
        0 => 'resource',
        'path' => 'string',
        'mode' => 'string',
        'handler=' => 'null|string',
        'permission=' => 'int',
        'map_size=' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'iterator_count' => 
    array (
      'old' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable',
      ),
      'new' => 
      array (
        0 => 'int<0, max>',
        'iterator' => 'Traversable|array<array-key, mixed>',
      ),
    ),
    'str_split' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-list<string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
      ),
      'new' => 
      array (
        0 => 'list<non-empty-string>',
        'string' => 'string',
        'length=' => 'int<1, max>',
      ),
    ),
  ),
  'removed' => 
  array (
    'closelog' => 
    array (
      0 => 'true',
    ),
    'imagecolorset' => 
    array (
      0 => 'false|null',
      'image' => 'GdImage',
      'color' => 'int',
      'red' => 'int',
      'green' => 'int',
      'blue' => 'int',
      'alpha=' => 'int',
    ),
    'openlog' => 
    array (
      0 => 'true',
      'prefix' => 'string',
      'flags' => 'int',
      'facility' => 'int',
    ),
    'phpcredits' => 
    array (
      0 => 'true',
      'flags=' => 'int',
    ),
    'phpinfo' => 
    array (
      0 => 'true',
      'flags=' => 'int',
    ),
    'restore_error_handler' => 
    array (
      0 => 'true',
    ),
    'restore_exception_handler' => 
    array (
      0 => 'true',
    ),
    'syslog' => 
    array (
      0 => 'true',
      'priority' => 'int',
      'message' => 'string',
    ),
    'xml_set_object' => 
    array (
      0 => 'true',
      'parser' => 'XMLParser',
      'object' => 'object',
    ),
  ),
);