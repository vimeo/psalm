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
      0 => 'impure-callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
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
    'sodium_crypto_stream_xchacha20_xor_ic' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'non-empty-string',
      'counter' => 'int',
      'key' => 'non-empty-string',
    ),
    'ssh2_auth_agent' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
    ),
    'ssh2_auth_hostbased_file' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'hostname' => 'string',
      'pubkeyfile' => 'string',
      'privkeyfile' => 'string',
      'passphrase=' => 'null|string',
      'local_username=' => 'null|string',
    ),
    'ssh2_auth_none' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
    ),
    'ssh2_auth_password' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'password' => 'string',
    ),
    'ssh2_auth_pubkey_file' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'pubkeyfile' => 'string',
      'privkeyfile' => 'string',
      'passphrase=' => 'null|string',
    ),
    'ssh2_connect' => 
    array (
      0 => 'false|resource',
      'host' => 'string',
      'port=' => 'int',
      'methods=' => 'array<array-key, mixed>|null',
      'callbacks=' => 'array<array-key, mixed>|null',
    ),
    'ssh2_disconnect' => 
    array (
      0 => 'bool',
      'session' => 'resource',
    ),
    'ssh2_exec' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'command' => 'string',
      'pty=' => 'bool',
      'env=' => 'array<array-key, mixed>|null',
      'width=' => 'int',
      'height=' => 'int',
      'width_height_type=' => 'int',
    ),
    'ssh2_fetch_stream' => 
    array (
      0 => 'false|resource',
      'channel' => 'resource',
      'streamid' => 'int',
    ),
    'ssh2_fingerprint' => 
    array (
      0 => 'false|string',
      'session' => 'resource',
      'flags=' => 'int',
    ),
    'ssh2_forward_listen' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'port' => 'int',
      'host=' => 'string',
      'max_connections=' => 'int',
    ),
    'ssh2_methods_negotiated' => 
    array (
      0 => 'array<array-key, mixed>',
      'session' => 'resource',
    ),
    'ssh2_publickey_add' => 
    array (
      0 => 'bool',
      'pkey' => 'resource',
      'algoname' => 'string',
      'blob' => 'string',
      'overwrite=' => 'bool',
      'attributes=' => 'array<array-key, mixed>|null',
    ),
    'ssh2_publickey_init' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
    ),
    'ssh2_publickey_list' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'pkey' => 'resource',
    ),
    'ssh2_publickey_remove' => 
    array (
      0 => 'bool',
      'pkey' => 'resource',
      'algoname' => 'string',
      'blob' => 'string',
    ),
    'ssh2_scp_recv' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'remote_file' => 'string',
      'local_file' => 'string',
    ),
    'ssh2_scp_send' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'local_file' => 'string',
      'remote_file' => 'string',
      'create_mode=' => 'int',
    ),
    'ssh2_sftp' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
    ),
    'ssh2_sftp_chmod' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'filename' => 'string',
      'mode' => 'int',
    ),
    'ssh2_sftp_lstat' => 
    array (
      0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
      'sftp' => 'resource',
      'path' => 'string',
    ),
    'ssh2_sftp_mkdir' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'dirname' => 'string',
      'mode=' => 'int',
      'recursive=' => 'bool',
    ),
    'ssh2_sftp_readlink' => 
    array (
      0 => 'false|non-falsy-string',
      'sftp' => 'resource',
      'link' => 'string',
    ),
    'ssh2_sftp_realpath' => 
    array (
      0 => 'false|non-falsy-string',
      'sftp' => 'resource',
      'filename' => 'string',
    ),
    'ssh2_sftp_rename' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'from' => 'string',
      'to' => 'string',
    ),
    'ssh2_sftp_rmdir' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'dirname' => 'string',
    ),
    'ssh2_sftp_stat' => 
    array (
      0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
      'sftp' => 'resource',
      'path' => 'string',
    ),
    'ssh2_sftp_symlink' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'target' => 'string',
      'link' => 'string',
    ),
    'ssh2_sftp_unlink' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'filename' => 'string',
    ),
    'ssh2_shell' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'termtype=' => 'string',
      'env=' => 'array<array-key, mixed>|null',
      'width=' => 'int',
      'height=' => 'int',
      'width_height_type=' => 'int',
    ),
    'ssh2_tunnel' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'host' => 'string',
      'port' => 'int',
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
    'array_walk' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'impure-callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'impure-callable',
        'arg=' => 'mixed',
      ),
    ),
    'array_walk_recursive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'impure-callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        'callback' => 'impure-callable',
        'arg=' => 'mixed',
      ),
    ),
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
    'iterator_to_array' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable',
        'preserve_keys=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'iterator' => 'Traversable|array<array-key, mixed>',
        'preserve_keys=' => 'bool',
      ),
    ),
    'mb_get_info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|int|string',
        'type=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false|int|null|string',
        'type=' => 'string',
      ),
    ),
    'passthru' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'command' => 'string',
        '&w result_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'command' => 'string',
        '&w result_code=' => 'int',
      ),
    ),
    'register_shutdown_function' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
        'callback' => 'impure-callable',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback' => 'impure-callable',
        '...args=' => 'mixed',
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
    'strcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strnatcasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strnatcmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
      ),
    ),
    'strncasecmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int<0, max>',
      ),
    ),
    'strncmp' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'int<-1, 1>',
        'string1' => 'string',
        'string2' => 'string',
        'length' => 'int<0, max>',
      ),
    ),
    'swoole_get_local_ip' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'family=' => 'int',
      ),
    ),
  ),
  'removed' => 
  array (
    'ds\\deque::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\deque::apply' => 
    array (
      0 => 'void',
      'callback' => 'impure-callable',
    ),
    'ds\\deque::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\deque::get' => 
    array (
      0 => 'void',
      'index' => 'int',
    ),
    'ds\\deque::insert' => 
    array (
      0 => 'void',
      'index' => 'int',
      '...values=' => 'mixed',
    ),
    'ds\\deque::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\deque::push' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\deque::reverse' => 
    array (
      0 => 'void',
    ),
    'ds\\deque::rotate' => 
    array (
      0 => 'void',
      'rotations' => 'int',
    ),
    'ds\\deque::set' => 
    array (
      0 => 'void',
      'index' => 'int',
      'value' => 'mixed',
    ),
    'ds\\deque::sort' => 
    array (
      0 => 'void',
      'comparator=' => 'impure-callable|null',
    ),
    'ds\\deque::sum' => 
    array (
      0 => 'float|int',
    ),
    'ds\\deque::unshift' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\priorityqueue::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\priorityqueue::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\priorityqueue::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\priorityqueue::push' => 
    array (
      0 => 'void',
      'value' => 'mixed',
      'priority' => 'int',
    ),
    'ds\\queue::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\queue::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\queue::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\queue::push' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\stack::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\stack::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\stack::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\stack::push' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\vector::allocate' => 
    array (
      0 => 'void',
      'capacity' => 'int',
    ),
    'ds\\vector::apply' => 
    array (
      0 => 'void',
      'callback' => 'impure-callable',
    ),
    'ds\\vector::clear' => 
    array (
      0 => 'void',
    ),
    'ds\\vector::insert' => 
    array (
      0 => 'void',
      'index' => 'int',
      '...values=' => 'mixed',
    ),
    'ds\\vector::jsonserialize' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ds\\vector::push' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
    'ds\\vector::reverse' => 
    array (
      0 => 'void',
    ),
    'ds\\vector::rotate' => 
    array (
      0 => 'void',
      'rotations' => 'int',
    ),
    'ds\\vector::set' => 
    array (
      0 => 'void',
      'index' => 'int',
      'value' => 'mixed',
    ),
    'ds\\vector::sort' => 
    array (
      0 => 'void',
      'comparator=' => 'impure-callable|null',
    ),
    'ds\\vector::sum' => 
    array (
      0 => 'float|int',
    ),
    'ds\\vector::unshift' => 
    array (
      0 => 'void',
      '...values=' => 'mixed',
    ),
  ),
);