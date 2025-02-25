<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'fdatasync' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
    ),
    'fiber::resume' => 
    array (
      0 => 'mixed',
      'value=' => 'mixed|null',
    ),
    'fiber::suspend' => 
    array (
      0 => 'mixed',
      'value=' => 'mixed|null',
    ),
    'fsync' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
    ),
    'imageavif' => 
    array (
      0 => 'bool',
      'image' => 'GdImage',
      'file=' => 'null|resource|string',
      'quality=' => 'int',
      'speed=' => 'int',
    ),
    'mysqli_fetch_column' => 
    array (
      0 => 'false|float|int|null|string',
      'result' => 'mysqli_result',
      'column=' => 'int',
    ),
    'mysqli_result::fetch_column' => 
    array (
      0 => 'false|float|int|null|string',
      'column=' => 'int',
    ),
    'reflectionenum::getbackingtype' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'reflectionenum::getcases' => 
    array (
      0 => 'list<ReflectionEnumUnitCase>',
    ),
    'sodium_crypto_stream_xchacha20' => 
    array (
      0 => 'non-empty-string',
      'length' => 'int<1, max>',
      'nonce' => 'non-empty-string',
      'key' => 'non-empty-string',
    ),
    'sodium_crypto_stream_xchacha20_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_stream_xchacha20_xor' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'non-empty-string',
      'key' => 'non-empty-string',
    ),
  ),
  'changed' => 
  array (
    'appenditerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'appenditerator::getiteratorindex' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'cachingiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'callbackfilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'ctype_alnum' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_alpha' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_cntrl' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_digit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_graph' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_lower' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_print' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_punct' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_space' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_upper' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'ctype_xdigit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'text' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'text' => 'string',
      ),
    ),
    'current' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        'array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'datefmt_create' => 
    array (
      'old' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType=' => 'int',
        'timeType=' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'domdocument::createcomment' => 
    array (
      'old' => 
      array (
        0 => 'DOMComment|false',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMComment',
        'data' => 'string',
      ),
    ),
    'domdocument::createdocumentfragment' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocumentFragment|false',
      ),
      'new' => 
      array (
        0 => 'DOMDocumentFragment',
      ),
    ),
    'domdocument::createtextnode' => 
    array (
      'old' => 
      array (
        0 => 'DOMText|false',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMText',
        'data' => 'string',
      ),
    ),
    'filteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'finfo_buffer' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'string' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'finfo' => 'finfo',
        'string' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'finfo_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'finfo' => 'finfo',
      ),
    ),
    'finfo_file' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'finfo' => 'resource',
        'filename' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'finfo' => 'finfo',
        'filename' => 'string',
        'flags=' => 'int',
        'context=' => 'resource',
      ),
    ),
    'finfo_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'flags=' => 'int',
        'magic_database=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'false|finfo',
        'flags=' => 'int',
        'magic_database=' => 'null|string',
      ),
    ),
    'finfo_set_flags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'resource',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'finfo' => 'finfo',
        'flags' => 'int',
      ),
    ),
    'fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'stream' => 'resource',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
        'eol=' => 'string',
      ),
    ),
    'ftp_alloc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'size' => 'int',
        '&w_response=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'size' => 'int',
        '&w_response=' => 'string',
      ),
    ),
    'ftp_append' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
      ),
    ),
    'ftp_cdup' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'ftp_chdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
      ),
    ),
    'ftp_chmod' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'ftp' => 'resource',
        'permissions' => 'int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ftp' => 'FTP\\Connection',
        'permissions' => 'int',
        'filename' => 'string',
      ),
    ),
    'ftp_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'ftp_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
      'new' => 
      array (
        0 => 'FTP\\Connection|false',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
    ),
    'ftp_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'filename' => 'string',
      ),
    ),
    'ftp_exec' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'command' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'command' => 'string',
      ),
    ),
    'ftp_fget' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_fput' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_get' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_get_option' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'ftp' => 'resource',
        'option' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ftp' => 'FTP\\Connection',
        'option' => 'int',
      ),
    ),
    'ftp_login' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'username' => 'string',
        'password' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'username' => 'string',
        'password' => 'string',
      ),
    ),
    'ftp_mdtm' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'filename' => 'string',
      ),
    ),
    'ftp_mkdir' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ftp' => 'resource',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
      ),
    ),
    'ftp_mlsd' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'resource',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
      ),
    ),
    'ftp_nb_continue' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'ftp_nb_fget' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'stream' => 'resource',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_fput' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'remote_filename' => 'string',
        'stream' => 'resource',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_get' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'local_filename' => 'string',
        'remote_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nb_put' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_nlist' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'resource',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
      ),
    ),
    'ftp_pasv' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'enable' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'enable' => 'bool',
      ),
    ),
    'ftp_put' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'remote_filename' => 'string',
        'local_filename' => 'string',
        'mode=' => 'int',
        'offset=' => 'int',
      ),
    ),
    'ftp_pwd' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'ftp_quit' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'ftp_raw' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'ftp' => 'resource',
        'command' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'ftp' => 'FTP\\Connection',
        'command' => 'string',
      ),
    ),
    'ftp_rawlist' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'resource',
        'directory' => 'string',
        'recursive=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
        'recursive=' => 'bool',
      ),
    ),
    'ftp_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'ftp_rmdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'directory' => 'string',
      ),
    ),
    'ftp_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'option' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'option' => 'int',
        'value' => 'mixed',
      ),
    ),
    'ftp_site' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'command' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'FTP\\Connection',
        'command' => 'string',
      ),
    ),
    'ftp_size' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'FTP\\Connection',
        'filename' => 'string',
      ),
    ),
    'ftp_ssl_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
      'new' => 
      array (
        0 => 'FTP\\Connection|false',
        'hostname' => 'string',
        'port=' => 'int',
        'timeout=' => 'int',
      ),
    ),
    'ftp_systype' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ftp' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ftp' => 'FTP\\Connection',
      ),
    ),
    'hash' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'binary=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'data' => 'string',
        'binary=' => 'bool',
        'options=' => 'array{seed: scalar}',
      ),
    ),
    'hash_file' => 
    array (
      'old' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'binary=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|non-empty-string',
        'algo' => 'string',
        'filename' => 'string',
        'binary=' => 'bool',
        'options=' => 'array{seed: scalar}',
      ),
    ),
    'hash_init' => 
    array (
      'old' => 
      array (
        0 => 'HashContext',
        'algo' => 'string',
        'flags=' => 'int',
        'key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'HashContext',
        'algo' => 'string',
        'flags=' => 'int',
        'key=' => 'string',
        'options=' => 'array{seed: scalar}',
      ),
    ),
    'hash_pbkdf2' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'password' => 'string',
        'salt' => 'string',
        'iterations' => 'int',
        'length=' => 'int',
        'binary=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'algo' => 'string',
        'password' => 'string',
        'salt' => 'string',
        'iterations' => 'int',
        'length=' => 'int',
        'binary=' => 'bool',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'imageloadfont' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'GdFont|false',
        'filename' => 'string',
      ),
    ),
    'imagickpixel::setcolorvaluequantum' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'int',
        'value' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color' => 'int',
        'value' => 'IMAGICK_QUANTUM_TYPE',
      ),
    ),
    'imagickpixel::setindex' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'index' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index' => 'IMAGICK_QUANTUM_TYPE',
      ),
    ),
    'imap_append' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'folder' => 'string',
        'message' => 'string',
        'options=' => 'null|string',
        'internal_date=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'folder' => 'string',
        'message' => 'string',
        'options=' => 'null|string',
        'internal_date=' => 'null|string',
      ),
    ),
    'imap_body' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'imap' => 'resource',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
    ),
    'imap_bodystruct' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'message_num' => 'int',
        'section' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'section' => 'string',
      ),
    ),
    'imap_check' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_clearflag_full' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
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
        'imap' => 'resource',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'flags=' => 'int',
      ),
    ),
    'imap_create' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_createmailbox' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_deletemailbox' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_expunge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_fetch_overview' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'sequence' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_fetchbody' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'imap' => 'resource',
        'message_num' => 'int',
        'section' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'section' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_fetchheader' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'imap' => 'resource',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
    ),
    'imap_fetchmime' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'imap' => 'resource',
        'message_num' => 'int',
        'section' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'section' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_fetchstructure' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
    ),
    'imap_fetchtext' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'imap' => 'resource',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'flags=' => 'int',
      ),
    ),
    'imap_gc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'flags' => 'int',
      ),
    ),
    'imap_get_quota' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'quota_root' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'quota_root' => 'string',
      ),
    ),
    'imap_get_quotaroot' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_getacl' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_getmailboxes' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_getsubscribed' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_headerinfo' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'message_num' => 'int',
        'from_length=' => 'int',
        'subject_length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
        'from_length=' => 'int',
        'subject_length=' => 'int',
      ),
    ),
    'imap_headers' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_list' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_listmailbox' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_listscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
    ),
    'imap_listsubscribed' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_lsub' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
      ),
    ),
    'imap_mail_copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'message_nums' => 'string',
        'mailbox' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'mailbox' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_mail_move' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'message_nums' => 'string',
        'mailbox' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'mailbox' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_mailboxmsginfo' => 
    array (
      'old' => 
      array (
        0 => 'stdClass',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'stdClass',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_msgno' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'imap' => 'resource',
        'message_uid' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'imap' => 'IMAP\\Connection',
        'message_uid' => 'int',
      ),
    ),
    'imap_num_msg' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_num_recent' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'mailbox' => 'string',
        'user' => 'string',
        'password' => 'string',
        'flags=' => 'int',
        'retries=' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'IMAP\\Connection|false',
        'mailbox' => 'string',
        'user' => 'string',
        'password' => 'string',
        'flags=' => 'int',
        'retries=' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'imap_ping' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
      ),
    ),
    'imap_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'imap_renamemailbox' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'from' => 'string',
        'to' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'from' => 'string',
        'to' => 'string',
      ),
    ),
    'imap_reopen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
        'flags=' => 'int',
        'retries=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
        'flags=' => 'int',
        'retries=' => 'int',
      ),
    ),
    'imap_savebody' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'file' => 'resource|string',
        'message_num' => 'int',
        'section=' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'file' => 'resource|string',
        'message_num' => 'int',
        'section=' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_scan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
    ),
    'imap_scanmailbox' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'reference' => 'string',
        'pattern' => 'string',
        'content' => 'string',
      ),
    ),
    'imap_search' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'criteria' => 'string',
        'flags=' => 'int',
        'charset=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'criteria' => 'string',
        'flags=' => 'int',
        'charset=' => 'string',
      ),
    ),
    'imap_set_quota' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'quota_root' => 'string',
        'mailbox_size' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'quota_root' => 'string',
        'mailbox_size' => 'int',
      ),
    ),
    'imap_setacl' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
        'user_id' => 'string',
        'rights' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
        'user_id' => 'string',
        'rights' => 'string',
      ),
    ),
    'imap_setflag_full' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'sequence' => 'string',
        'flag' => 'string',
        'options=' => 'int',
      ),
    ),
    'imap_sort' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'criteria' => 'int',
        'reverse' => 'bool',
        'flags=' => 'int',
        'search_criteria=' => 'null|string',
        'charset=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'criteria' => 'int',
        'reverse' => 'bool',
        'flags=' => 'int',
        'search_criteria=' => 'null|string',
        'charset=' => 'null|string',
      ),
    ),
    'imap_status' => 
    array (
      'old' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'resource',
        'mailbox' => 'string',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|stdClass',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
        'flags' => 'int',
      ),
    ),
    'imap_subscribe' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'imap_thread' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'resource',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'imap' => 'IMAP\\Connection',
        'flags=' => 'int',
      ),
    ),
    'imap_uid' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'imap' => 'resource',
        'message_num' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'imap' => 'IMAP\\Connection',
        'message_num' => 'int',
      ),
    ),
    'imap_undelete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'message_nums' => 'string',
        'flags=' => 'int',
      ),
    ),
    'imap_unsubscribe' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'imap' => 'resource',
        'mailbox' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'imap' => 'IMAP\\Connection',
        'mailbox' => 'string',
      ),
    ),
    'infiniteiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'ini_alter' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'null|scalar',
      ),
    ),
    'ini_set' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'option' => 'string',
        'value' => 'null|scalar',
      ),
    ),
    'intldateformatter::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'locale' => 'null|string',
        'dateType=' => 'int',
        'timeType=' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'intldateformatter::create' => 
    array (
      'old' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType' => 'int',
        'timeType' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlDateFormatter|null',
        'locale' => 'null|string',
        'dateType=' => 'int',
        'timeType=' => 'int',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'calendar=' => 'IntlCalendar|int|null',
        'pattern=' => 'null|string',
      ),
    ),
    'iteratoriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'key' => 
    array (
      'old' => 
      array (
        0 => 'int|null|string',
        'array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'int|null|string',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_add_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn=' => 'null|string',
        'password=' => 'null|string',
      ),
    ),
    'ldap_bind_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'ldap_compare' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'uri=' => 'null|string',
        'port=' => 'int',
        'wallet=' => 'string',
        'password=' => 'string',
        'auth_mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Connection|false',
        'uri=' => 'null|string',
        'port=' => 'int',
        'wallet=' => 'string',
        'password=' => 'string',
        'auth_mode=' => 'int',
      ),
    ),
    'ldap_count_entries' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ldap' => 'resource',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
      ),
    ),
    'ldap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_delete_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_errno' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'ldap_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'ldap_exop' => 
    array (
      'old' => 
      array (
        0 => 'bool|resource',
        'ldap' => 'resource',
        'request_oid' => 'string',
        'request_data=' => 'null|string',
        'controls=' => 'array<array-key, mixed>|null',
        '&w_response_data=' => 'string',
        '&w_response_oid=' => 'string',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|bool',
        'ldap' => 'LDAP\\Connection',
        'request_oid' => 'string',
        'request_data=' => 'null|string',
        'controls=' => 'array<array-key, mixed>|null',
        '&w_response_data=' => 'string',
        '&w_response_oid=' => 'string',
      ),
    ),
    'ldap_exop_passwd' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'ldap' => 'LDAP\\Connection',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_exop_refresh' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'ttl' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'ttl' => 'int',
      ),
    ),
    'ldap_exop_whoami' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'ldap_first_attribute' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_first_entry' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'LDAP\\ResultEntry|false',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
      ),
    ),
    'ldap_first_reference' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'LDAP\\ResultEntry|false',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
      ),
    ),
    'ldap_free_result' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'result' => 'LDAP\\Result',
      ),
    ),
    'ldap_get_attributes' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_get_dn' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_get_entries' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'resource',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
      ),
    ),
    'ldap_get_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'option' => 'int',
        '&w_value=' => 'array<array-key, mixed>|int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'option' => 'int',
        '&w_value=' => 'array<array-key, mixed>|int|string',
      ),
    ),
    'ldap_get_values' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'resource',
        'entry' => 'resource',
        'attribute' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
        'attribute' => 'string',
      ),
    ),
    'ldap_get_values_len' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'resource',
        'entry' => 'resource',
        'attribute' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
        'attribute' => 'string',
      ),
    ),
    'ldap_list' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|array<array-key, LDAP\\Result>|false',
        'ldap' => 'LDAP\\Connection|array<array-key, LDAP\\Connection>',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_add' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_add_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_del' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_del_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_replace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_mod_replace_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_modify' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_modify_batch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_next_attribute' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_next_entry' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'LDAP\\ResultEntry|false',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_next_reference' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'entry' => 'resource',
      ),
      'new' => 
      array (
        0 => 'LDAP\\ResultEntry|false',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
      ),
    ),
    'ldap_parse_exop' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'result' => 'resource',
        '&w_response_data=' => 'string',
        '&w_response_oid=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
        '&w_response_data=' => 'string',
        '&w_response_oid=' => 'string',
      ),
    ),
    'ldap_parse_reference' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'entry' => 'resource',
        '&w_referrals' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'entry' => 'LDAP\\ResultEntry',
        '&w_referrals' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_parse_result' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'result' => 'resource',
        '&w_error_code' => 'int',
        '&w_matched_dn=' => 'string',
        '&w_error_message=' => 'string',
        '&w_referrals=' => 'array<array-key, mixed>',
        '&w_controls=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'result' => 'LDAP\\Result',
        '&w_error_code' => 'int',
        '&w_matched_dn=' => 'string',
        '&w_error_message=' => 'string',
        '&w_referrals=' => 'array<array-key, mixed>',
        '&w_controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_read' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'array<array-key, mixed>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|array<array-key, LDAP\\Result>|false',
        'ldap' => 'LDAP\\Connection|array<array-key, LDAP\\Connection>',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_rename_ext' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|false',
        'ldap' => 'LDAP\\Connection',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_sasl_bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'mech=' => 'null|string',
        'realm=' => 'null|string',
        'authc_id=' => 'null|string',
        'authz_id=' => 'null|string',
        'props=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'dn=' => 'null|string',
        'password=' => 'null|string',
        'mech=' => 'null|string',
        'realm=' => 'null|string',
        'authc_id=' => 'null|string',
        'authz_id=' => 'null|string',
        'props=' => 'null|string',
      ),
    ),
    'ldap_search' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, resource>|false|resource',
        'ldap' => 'array<array-key, resource>|resource',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'LDAP\\Result|array<array-key, LDAP\\Result>|false',
        'ldap' => 'LDAP\\Connection|array<array-key, LDAP\\Connection>',
        'base' => 'array<array-key, mixed>|string',
        'filter' => 'array<array-key, mixed>|string',
        'attributes=' => 'array<array-key, mixed>',
        'attributes_only=' => 'int',
        'sizelimit=' => 'int',
        'timelimit=' => 'int',
        'deref=' => 'int',
        'controls=' => 'array<array-key, mixed>|null',
      ),
    ),
    'ldap_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'null|resource',
        'option' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection|null',
        'option' => 'int',
        'value' => 'mixed',
      ),
    ),
    'ldap_set_rebind_proc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'callback' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
        'callback' => 'callable|null',
      ),
    ),
    'ldap_start_tls' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'ldap_unbind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'LDAP\\Connection',
      ),
    ),
    'limititerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'locale::getallvariants' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'locale::getkeywords' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|null',
        'locale' => 'string',
      ),
    ),
    'locale::getprimarylanguage' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::getregion' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::getscript' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'locale' => 'string',
      ),
    ),
    'locale::parselocale' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
        'locale' => 'string',
      ),
    ),
    'messageformatter::create' => 
    array (
      'old' => 
      array (
        0 => 'MessageFormatter',
        'locale' => 'string',
        'pattern' => 'string',
      ),
      'new' => 
      array (
        0 => 'MessageFormatter|null',
        'locale' => 'string',
        'pattern' => 'string',
      ),
    ),
    'multipleiterator::current' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'mysqli::connect' => 
    array (
      'old' => 
      array (
        0 => 'false|null',
        'hostname=' => 'null|string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'database=' => 'null|string',
        'port=' => 'int|null',
        'socket=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hostname=' => 'null|string',
        'username=' => 'null|string',
        'password=' => 'null|string',
        'database=' => 'null|string',
        'port=' => 'int|null',
        'socket=' => 'null|string',
      ),
    ),
    'mysqli_execute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'mysqli_stmt',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'mysqli_stmt',
        'params=' => 'list<mixed>|null',
      ),
    ),
    'mysqli_fetch_field' => 
    array (
      'old' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'result' => 'mysqli_result',
      ),
      'new' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'result' => 'mysqli_result',
      ),
    ),
    'mysqli_fetch_field_direct' => 
    array (
      'old' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'result' => 'mysqli_result',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'result' => 'mysqli_result',
        'index' => 'int',
      ),
    ),
    'mysqli_fetch_fields' => 
    array (
      'old' => 
      array (
        0 => 'list<object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}>',
        'result' => 'mysqli_result',
      ),
      'new' => 
      array (
        0 => 'list<object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}>',
        'result' => 'mysqli_result',
      ),
    ),
    'mysqli_result::fetch_field' => 
    array (
      'old' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
      ),
      'new' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
      ),
    ),
    'mysqli_result::fetch_field_direct' => 
    array (
      'old' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}',
        'index' => 'int',
      ),
    ),
    'mysqli_result::fetch_fields' => 
    array (
      'old' => 
      array (
        0 => 'list<object{name:string, orgname:string, table:string, orgtable:string, max_length:int, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}>',
      ),
      'new' => 
      array (
        0 => 'list<object{name:string, orgname:string, table:string, orgtable:string, max_length:0, length:int, charsetnr:int, flags:int, type:int, decimals:int, db:string, def:\'\', catalog:\'def\'}>',
      ),
    ),
    'mysqli_stmt::execute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'params=' => 'list<mixed>|null',
      ),
    ),
    'mysqli_stmt_execute' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'statement' => 'mysqli_stmt',
      ),
      'new' => 
      array (
        0 => 'bool',
        'statement' => 'mysqli_stmt',
        'params=' => 'list<mixed>|null',
      ),
    ),
    'next' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>',
      ),
    ),
    'norewinditerator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'openssl_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        'tag=' => 'string',
        'aad=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'data' => 'string',
        'cipher_algo' => 'string',
        'passphrase' => 'string',
        'options=' => 'int',
        'iv=' => 'string',
        'tag=' => 'null|string',
        'aad=' => 'string',
      ),
    ),
    'pg_affected_rows' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_cancel_query' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_client_encoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_connect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Connection|false',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
    ),
    'pg_connect_poll' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_connection_busy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_connection_reset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_connection_status' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_consume_input' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_convert' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_copy_from' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'table_name' => 'string',
        'rows' => 'array<array-key, mixed>',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'rows' => 'array<array-key, mixed>',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
    ),
    'pg_copy_to' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'separator=' => 'string',
        'null_as=' => 'string',
      ),
    ),
    'pg_dbname' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_end_copy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_escape_bytea' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection' => 'PgSql\\Connection',
        'string=' => 'string',
      ),
    ),
    'pg_escape_identifier' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'PgSql\\Connection',
        'string=' => 'string',
      ),
    ),
    'pg_escape_literal' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'PgSql\\Connection',
        'string=' => 'string',
      ),
    ),
    'pg_escape_string' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection' => 'resource',
        'string=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection' => 'PgSql\\Connection',
        'string=' => 'string',
      ),
    ),
    'pg_exec' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
        'query=' => 'string',
      ),
    ),
    'pg_exec\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'string',
      ),
    ),
    'pg_execute' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'statement_name' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
        'statement_name' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_execute\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'string',
        'statement_name' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'string',
        'statement_name' => 'array<array-key, mixed>',
      ),
    ),
    'pg_fetch_all' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'resource',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, array<array-key, mixed>>',
        'result' => 'PgSql\\Result',
        'mode=' => 'int',
      ),
    ),
    'pg_fetch_all_columns' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'result' => 'resource',
        'field=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'result' => 'PgSql\\Result',
        'field=' => 'int',
      ),
    ),
    'pg_fetch_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, null|string>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, null|string>|false',
        'result' => 'PgSql\\Result',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pg_fetch_assoc' => 
    array (
      'old' => 
      array (
        0 => 'array<string, mixed>|false',
        'result' => 'resource',
        'row=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'array<string, mixed>|false',
        'result' => 'PgSql\\Result',
        'row=' => 'int|null',
      ),
    ),
    'pg_fetch_object' => 
    array (
      'old' => 
      array (
        0 => 'false|object',
        'result' => 'resource',
        'row=' => 'int|null',
        'class=' => 'string',
        'constructor_args=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|object',
        'result' => 'PgSql\\Result',
        'row=' => 'int|null',
        'class=' => 'string',
        'constructor_args=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_fetch_result' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'result' => 'PgSql\\Result',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_fetch_result\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'row' => 'int|null',
        'field' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'result' => 'PgSql\\Result',
        'row' => 'int|null',
        'field' => 'int|string',
      ),
    ),
    'pg_fetch_row' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'result' => 'resource',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'result' => 'PgSql\\Result',
        'row=' => 'int|null',
        'mode=' => 'int',
      ),
    ),
    'pg_field_is_null' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'PgSql\\Result',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_field_is_null\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int',
        'field' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'PgSql\\Result',
        'row' => 'int',
        'field' => 'int|string',
      ),
    ),
    'pg_field_name' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'result' => 'PgSql\\Result',
        'field' => 'int',
      ),
    ),
    'pg_field_num' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'PgSql\\Result',
        'field' => 'string',
      ),
    ),
    'pg_field_prtlen' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'PgSql\\Result',
        'row' => 'int|string',
        'field=' => 'int|string',
      ),
    ),
    'pg_field_prtlen\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'result' => 'resource',
        'row' => 'int',
        'field' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'result' => 'PgSql\\Result',
        'row' => 'int',
        'field' => 'int|string',
      ),
    ),
    'pg_field_size' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'PgSql\\Result',
        'field' => 'int',
      ),
    ),
    'pg_field_table' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'result' => 'resource',
        'field' => 'int',
        'oid_only=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'result' => 'PgSql\\Result',
        'field' => 'int',
        'oid_only=' => 'bool',
      ),
    ),
    'pg_field_type' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'result' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'string',
        'result' => 'PgSql\\Result',
        'field' => 'int',
      ),
    ),
    'pg_field_type_oid' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'field' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'result' => 'PgSql\\Result',
        'field' => 'int',
      ),
    ),
    'pg_flush' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_free_result' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_get_notify' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'PgSql\\Connection',
        'mode=' => 'int',
      ),
    ),
    'pg_get_pid' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_get_result' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_host' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_insert' => 
    array (
      'old' => 
      array (
        0 => 'false|resource|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_last_error' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_last_notice' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'connection' => 'resource',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'connection' => 'PgSql\\Connection',
        'mode=' => 'int',
      ),
    ),
    'pg_last_oid' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_lo_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'PgSql\\Lob',
      ),
    ),
    'pg_lo_create' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'connection=' => 'resource',
        'oid=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'connection=' => 'PgSql\\Connection',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_export' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'oid=' => 'int|string',
        'filename=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
        'oid=' => 'int|string',
        'filename=' => 'string',
      ),
    ),
    'pg_lo_import' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'connection' => 'resource',
        'filename=' => 'string',
        'oid=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'connection' => 'PgSql\\Connection',
        'filename=' => 'string',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'oid=' => 'int|string',
        'mode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Lob|false',
        'connection' => 'PgSql\\Connection',
        'oid=' => 'int|string',
        'mode=' => 'string',
      ),
    ),
    'pg_lo_open\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'int|string',
        'oid' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Lob|false',
        'connection' => 'int|string',
        'oid' => 'string',
      ),
    ),
    'pg_lo_read' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'lob' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'lob' => 'PgSql\\Lob',
        'length=' => 'int',
      ),
    ),
    'pg_lo_read_all' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'lob' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'lob' => 'PgSql\\Lob',
      ),
    ),
    'pg_lo_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
        'offset' => 'int',
        'whence=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'PgSql\\Lob',
        'offset' => 'int',
        'whence=' => 'int',
      ),
    ),
    'pg_lo_tell' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'lob' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'lob' => 'PgSql\\Lob',
      ),
    ),
    'pg_lo_truncate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lob' => 'resource',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lob' => 'PgSql\\Lob',
        'size' => 'int',
      ),
    ),
    'pg_lo_unlink' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'oid=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
        'oid=' => 'int|string',
      ),
    ),
    'pg_lo_write' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'lob' => 'resource',
        'data' => 'string',
        'length=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'lob' => 'PgSql\\Lob',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'pg_meta_data' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'resource',
        'table_name' => 'string',
        'extended=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'extended=' => 'bool',
      ),
    ),
    'pg_num_fields' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_num_rows' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_options' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_parameter_status' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'connection' => 'resource',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'connection' => 'PgSql\\Connection',
        'name=' => 'string',
      ),
    ),
    'pg_pconnect' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Connection|false',
        'connection_string' => 'string',
        'flags=' => 'int',
      ),
    ),
    'pg_ping' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_port' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_prepare' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'statement_name' => 'string',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
        'statement_name' => 'string',
        'query=' => 'string',
      ),
    ),
    'pg_prepare\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'string',
        'statement_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'string',
        'statement_name' => 'string',
      ),
    ),
    'pg_put_line' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection' => 'PgSql\\Connection',
        'query=' => 'string',
      ),
    ),
    'pg_query' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
        'query=' => 'string',
      ),
    ),
    'pg_query\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'string',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'string',
      ),
    ),
    'pg_query_params' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
        'query' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'PgSql\\Connection',
        'query' => 'string',
        'params=' => 'array<array-key, mixed>',
      ),
    ),
    'pg_query_params\'1' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'string',
        'query' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'PgSql\\Result|false',
        'connection' => 'string',
        'query' => 'array<array-key, mixed>',
      ),
    ),
    'pg_result_error' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'result' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'result' => 'PgSql\\Result',
      ),
    ),
    'pg_result_error_field' => 
    array (
      'old' => 
      array (
        0 => 'false|null|string',
        'result' => 'resource',
        'field_code' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null|string',
        'result' => 'PgSql\\Result',
        'field_code' => 'int',
      ),
    ),
    'pg_result_seek' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'result' => 'resource',
        'row' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'result' => 'PgSql\\Result',
        'row' => 'int',
      ),
    ),
    'pg_result_status' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
        'result' => 'resource',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'result' => 'PgSql\\Result',
        'mode=' => 'int',
      ),
    ),
    'pg_select' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
    ),
    'pg_send_execute' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'statement_name' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'PgSql\\Connection',
        'statement_name' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
    ),
    'pg_send_prepare' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'statement_name' => 'string',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'PgSql\\Connection',
        'statement_name' => 'string',
        'query' => 'string',
      ),
    ),
    'pg_send_query' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'query' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'PgSql\\Connection',
        'query' => 'string',
      ),
    ),
    'pg_send_query_params' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'connection' => 'resource',
        'query' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'connection' => 'PgSql\\Connection',
        'query' => 'string',
        'params' => 'array<array-key, mixed>',
      ),
    ),
    'pg_set_client_encoding' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection' => 'resource',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'PgSql\\Connection',
        'encoding=' => 'string',
      ),
    ),
    'pg_set_error_verbosity' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'connection' => 'resource',
        'verbosity=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'connection' => 'PgSql\\Connection',
        'verbosity=' => 'int',
      ),
    ),
    'pg_socket' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_trace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'mode=' => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_transaction_status' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'connection' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'connection' => 'PgSql\\Connection',
      ),
    ),
    'pg_tty' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'string',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_untrace' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'pg_update' => 
    array (
      'old' => 
      array (
        0 => 'bool|string',
        'connection' => 'resource',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'connection' => 'PgSql\\Connection',
        'table_name' => 'string',
        'values' => 'array<array-key, mixed>',
        'conditions' => 'array<array-key, mixed>',
        'flags=' => 'int',
      ),
    ),
    'pg_version' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'connection=' => 'null|resource',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'connection=' => 'PgSql\\Connection|null',
      ),
    ),
    'phar::buildfromdirectory' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
    ),
    'phar::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
    ),
    'phardata::buildfromdirectory' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'directory' => 'string',
        'pattern=' => 'string',
      ),
    ),
    'phardata::buildfromiterator' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'baseDirectory=' => 'null|string',
      ),
    ),
    'prev' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'mixed',
        '&r_array' => 'array<array-key, mixed>',
      ),
    ),
    'pspell_add_to_personal' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
        'word' => 'string',
      ),
    ),
    'pspell_add_to_session' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
        'word' => 'string',
      ),
    ),
    'pspell_check' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
        'word' => 'string',
      ),
    ),
    'pspell_clear_session' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
      ),
    ),
    'pspell_config_create' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'PSpell\\Config',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
      ),
    ),
    'pspell_config_data_dir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'directory' => 'string',
      ),
    ),
    'pspell_config_dict_dir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'directory' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'directory' => 'string',
      ),
    ),
    'pspell_config_ignore' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'min_length' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'min_length' => 'int',
      ),
    ),
    'pspell_config_mode' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'mode' => 'int',
      ),
    ),
    'pspell_config_personal' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'filename' => 'string',
      ),
    ),
    'pspell_config_repl' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'filename' => 'string',
      ),
    ),
    'pspell_config_runtogether' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'allow' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'allow' => 'bool',
      ),
    ),
    'pspell_config_save_repl' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'config' => 'int',
        'save' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'config' => 'PSpell\\Config',
        'save' => 'bool',
      ),
    ),
    'pspell_new' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'PSpell\\Dictionary|false',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
        'mode=' => 'int',
      ),
    ),
    'pspell_new_config' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'config' => 'int',
      ),
      'new' => 
      array (
        0 => 'PSpell\\Dictionary|false',
        'config' => 'PSpell\\Config',
      ),
    ),
    'pspell_new_personal' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'PSpell\\Dictionary|false',
        'filename' => 'string',
        'language' => 'string',
        'spelling=' => 'string',
        'jargon=' => 'string',
        'encoding=' => 'string',
        'mode=' => 'int',
      ),
    ),
    'pspell_save_wordlist' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
      ),
    ),
    'pspell_store_replacement' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dictionary' => 'int',
        'misspelled' => 'string',
        'correct' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dictionary' => 'PSpell\\Dictionary',
        'misspelled' => 'string',
        'correct' => 'string',
      ),
    ),
    'pspell_suggest' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'int',
        'word' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'dictionary' => 'PSpell\\Dictionary',
        'word' => 'string',
      ),
    ),
    'recursivecachingiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursivecallbackfilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursivefilteriterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'recursiveregexiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'reflectionclass::getstaticproperties' => 
    array (
      'old' => 
      array (
        0 => 'array<string, ReflectionProperty>',
      ),
      'new' => 
      array (
        0 => 'array<string, ReflectionProperty>|null',
      ),
    ),
    'reflectionclass::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
      'new' => 
      array (
        0 => 'null|object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'reflectionfunction::getclosurescopeclass' => 
    array (
      'old' => 
      array (
        0 => 'ReflectionClass',
      ),
      'new' => 
      array (
        0 => 'ReflectionClass|null',
      ),
    ),
    'reflectionfunction::getclosurethis' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'null|object',
      ),
    ),
    'reflectionmethod::getclosurescopeclass' => 
    array (
      'old' => 
      array (
        0 => 'ReflectionClass',
      ),
      'new' => 
      array (
        0 => 'ReflectionClass|null',
      ),
    ),
    'reflectionmethod::getclosurethis' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'null|object',
      ),
    ),
    'reflectionobject::getstaticproperties' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, ReflectionProperty>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, ReflectionProperty>|null',
      ),
    ),
    'reflectionobject::newinstanceargs' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
      'new' => 
      array (
        0 => 'null|object',
        'args=' => 'array<int<0, max>|string, mixed>',
      ),
    ),
    'regexiterator::getinneriterator' => 
    array (
      'old' => 
      array (
        0 => 'Iterator',
      ),
      'new' => 
      array (
        0 => 'Iterator|null',
      ),
    ),
    'reset' => 
    array (
      'old' => 
      array (
        0 => 'false|mixed',
        '&r_array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'false|mixed',
        '&r_array' => 'array<array-key, mixed>',
      ),
    ),
    'soapclient::__setcookie' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'name' => 'string',
        'value=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'string',
        'value=' => 'null|string',
      ),
    ),
    'soapclient::__setlocation' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'location=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'location=' => 'null|string',
      ),
    ),
    'splfileobject::fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
        'eol=' => 'string',
      ),
    ),
    'splfileobject::fscanf' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'format' => 'string',
        '&...vars=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int|null',
        'format' => 'string',
        '&...vars=' => 'float|int|string',
      ),
    ),
    'spltempfileobject::fputcsv' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'fields' => 'array<array-key, Stringable|null|scalar>',
        'separator=' => 'string',
        'enclosure=' => 'string',
        'escape=' => 'string',
        'eol=' => 'string',
      ),
    ),
    'spltempfileobject::fscanf' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'format' => 'string',
        '&...vars=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int|null',
        'format' => 'string',
        '&...vars=' => 'float|int|string',
      ),
    ),
    'stream_select' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        '&read' => 'array<array-key, resource>|null',
        '&write' => 'array<array-key, resource>|null',
        '&except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        '&read' => 'array<array-key, resource>|null',
        '&write' => 'array<array-key, resource>|null',
        '&except' => 'array<array-key, resource>|null',
        'seconds' => 'int|null',
        'microseconds=' => 'int|null',
      ),
    ),
    'swoole\\http\\response::cookie' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name_or_object' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
        'partitioned=' => 'bool',
      ),
    ),
    'swoole\\http\\response::rawcookie' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name_or_object' => 'string',
        'value=' => 'string',
        'expires=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
        'samesite=' => 'string',
        'priority=' => 'string',
        'partitioned=' => 'bool',
      ),
    ),
    'swoole\\server::addprocess' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'process' => 'Swoole\\Process',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'process' => 'Swoole\\Process',
      ),
    ),
    'swoole_async_set' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'settings' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'settings' => 'array<array-key, mixed>',
      ),
    ),
    'swoole_client_select' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&read_array' => 'array<array-key, mixed>',
        '&write_array' => 'array<array-key, mixed>',
        '&error_array' => 'array<array-key, mixed>',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        '&read' => 'array<array-key, mixed>|null',
        '&write' => 'array<array-key, mixed>|null',
        '&except' => 'array<array-key, mixed>|null',
        'timeout=' => 'float|null',
      ),
    ),
    'swoole_select' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&read_array' => 'array<array-key, mixed>',
        '&write_array' => 'array<array-key, mixed>',
        '&error_array' => 'array<array-key, mixed>',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        '&read' => 'array<array-key, mixed>|null',
        '&write' => 'array<array-key, mixed>|null',
        '&except' => 'array<array-key, mixed>|null',
        'timeout=' => 'float|null',
      ),
    ),
  ),
  'removed' => 
  array (
    'reflectionmethod::isstatic' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\mysql::__destruct' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\mysql::close' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\mysql::connect' => 
    array (
      0 => 'ReturnType',
      'server_config=' => 'array<array-key, mixed>',
    ),
    'swoole\\coroutine\\mysql::getdefer' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\mysql::query' => 
    array (
      0 => 'ReturnType',
      'sql' => 'mixed',
      'timeout=' => 'mixed',
    ),
    'swoole\\coroutine\\mysql::recv' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\mysql::setdefer' => 
    array (
      0 => 'ReturnType',
      'defer=' => 'mixed',
    ),
  ),
);