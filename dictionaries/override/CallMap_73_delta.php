<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'array_key_first' => 
    array (
      0 => 'int|null|string',
      'arg' => 'array<array-key, mixed>',
    ),
    'array_key_last' => 
    array (
      0 => 'int|null|string',
      'arg' => 'array<array-key, mixed>',
    ),
    'datetime::createfromimmutable' => 
    array (
      0 => 'static',
      'DateTimeImmutable' => 'DateTimeImmutable',
    ),
    'fpm_get_status' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'gc_status' => 
    array (
      0 => 'array{collected: int, roots: int, runs: int, threshold: int}',
    ),
    'gmp_binomial' => 
    array (
      0 => 'GMP|false',
      'a' => 'GMP|int|string',
      'b' => 'int',
    ),
    'gmp_kronecker' => 
    array (
      0 => 'int',
      'a' => 'GMP|int|string',
      'b' => 'GMP|int|string',
    ),
    'gmp_lcm' => 
    array (
      0 => 'GMP',
      'a' => 'GMP|int|string',
      'b' => 'GMP|int|string',
    ),
    'gmp_perfect_power' => 
    array (
      0 => 'bool',
      'a' => 'GMP|int|string',
    ),
    'hrtime' => 
    array (
      0 => 'array{0: int, 1: int}|false',
      'get_as_number' => 'false',
    ),
    'hrtime\'1' => 
    array (
      0 => 'false|float|int',
      'as_number=' => 'true',
    ),
    'is_countable' => 
    array (
      0 => 'bool',
      'var' => 'mixed',
    ),
    'jsonexception::__clone' => 
    array (
      0 => 'void',
    ),
    'jsonexception::__construct' => 
    array (
      0 => 'void',
      'message=' => 'string',
      'code=' => 'int',
      'previous=' => 'Throwable|null',
    ),
    'jsonexception::__tostring' => 
    array (
      0 => 'string',
    ),
    'jsonexception::__wakeup' => 
    array (
      0 => 'void',
    ),
    'jsonexception::getcode' => 
    array (
      0 => 'int',
    ),
    'jsonexception::getfile' => 
    array (
      0 => 'string',
    ),
    'jsonexception::getline' => 
    array (
      0 => 'int',
    ),
    'jsonexception::getmessage' => 
    array (
      0 => 'string',
    ),
    'jsonexception::getprevious' => 
    array (
      0 => 'Throwable|null',
    ),
    'jsonexception::gettrace' => 
    array (
      0 => 'list<array{args?: array<array-key, mixed>, class?: class-string, file?: string, function: string, line?: int, type?: \'->\'|\'::\'}>',
    ),
    'jsonexception::gettraceasstring' => 
    array (
      0 => 'string',
    ),
    'net_get_interfaces' => 
    array (
      0 => 'array<string, array<string, mixed>>|false',
    ),
    'normalizer::getrawdecomposition' => 
    array (
      0 => 'null|string',
      'input' => 'string',
    ),
    'normalizer_get_raw_decomposition' => 
    array (
      0 => 'null|string',
      'input' => 'string',
    ),
    'openssl_pkey_derive' => 
    array (
      0 => 'false|string',
      'peer_pub_key' => 'mixed',
      'priv_key' => 'mixed',
      'keylen=' => 'int|null',
    ),
    'readline_list_history' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'session_set_cookie_params\'1' => 
    array (
      0 => 'bool',
      'options' => 'array{domain?: null|string, httponly?: bool|null, lifetime?: int|null, path?: null|string, samesite?: null|string, secure?: bool|null}',
    ),
    'setcookie\'1' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value=' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'setrawcookie\'1' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value=' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'socket_wsaprotocol_info_export' => 
    array (
      0 => 'false|string',
      'socket' => 'resource',
      'process_id' => 'int',
    ),
    'socket_wsaprotocol_info_import' => 
    array (
      0 => 'false|resource',
      'info_id' => 'string',
    ),
    'socket_wsaprotocol_info_release' => 
    array (
      0 => 'bool',
      'info_id' => 'string',
    ),
    'splpriorityqueue::iscorrupted' => 
    array (
      0 => 'bool',
    ),
    'spoofchecker::setrestrictionlevel' => 
    array (
      0 => 'void',
      'level' => 'int',
    ),
  ),
  'changed' => 
  array (
    'array_push' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars=' => 'mixed',
      ),
    ),
    'array_unshift' => 
    array (
      'old' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        '&stack' => 'array<array-key, mixed>',
        '...vars=' => 'mixed',
      ),
    ),
    'bcscale' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'scale' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'scale=' => 'int',
      ),
    ),
    'dateinterval::__set_state' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'datetimezone::__set_state' => 
    array (
      'old' => 
      array (
        0 => 'DateTimeZone',
      ),
      'new' => 
      array (
        0 => 'DateTimeZone',
        'array' => 'array<array-key, mixed>',
      ),
    ),
    'define' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'constant_name' => 'string',
        'value' => 'array<array-key, mixed>|null|scalar',
        'case_insensitive=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'constant_name' => 'string',
        'value' => 'array<array-key, mixed>|null|scalar',
        'case_insensitive=' => 'false',
      ),
    ),
    'ftp_append' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
      ),
    ),
    'ftp_fget' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode' => 'int',
        'resumepos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resumepos=' => 'int',
      ),
    ),
    'ftp_fput' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
    ),
    'ftp_get' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode' => 'int',
        'resume_pos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resume_pos=' => 'int',
      ),
    ),
    'ftp_nb_fget' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode' => 'int',
        'resumepos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'fp' => 'resource',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resumepos=' => 'int',
      ),
    ),
    'ftp_nb_fput' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'fp' => 'resource',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
    ),
    'ftp_nb_get' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode' => 'int',
        'resume_pos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'local_file' => 'string',
        'remote_file' => 'string',
        'mode=' => 'int',
        'resume_pos=' => 'int',
      ),
    ),
    'ftp_nb_put' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
    ),
    'ftp_put' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode' => 'int',
        'startpos=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ftp' => 'resource',
        'remote_file' => 'string',
        'local_file' => 'string',
        'mode=' => 'int',
        'startpos=' => 'int',
      ),
    ),
    'image2wbmp' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'filename=' => 'null|string',
        'threshold=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'im' => 'resource',
        'filename=' => 'null|string',
        'foreground=' => 'int',
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
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'ldap' => 'resource',
        'dn' => 'string',
        'attribute' => 'string',
        'value' => 'string',
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'ldap_delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool|string',
        'ldap' => 'resource',
        'user=' => 'string',
        'old_password=' => 'string',
        'new_password=' => 'string',
        '&w_controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
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
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'entry' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'modifications_info' => 'array<array-key, mixed>',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
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
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'ldap' => 'resource',
        'dn' => 'string',
        'new_rdn' => 'string',
        'new_parent' => 'string',
        'delete_old_rdn' => 'bool',
        'controls=' => 'array<array-key, mixed>',
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
      ),
      'new' => 
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
        'controls=' => 'array<array-key, mixed>',
      ),
    ),
    'mkdir' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pathname' => 'string',
        'mode=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'pathname' => 'string',
        'mode=' => 'int',
        'recursive=' => 'bool',
        'context=' => 'null|resource',
      ),
    ),
    'recursivetreeiterator::setpostfix' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'void',
        'postfix' => 'string',
      ),
    ),
    'session_get_cookie_params' => 
    array (
      'old' => 
      array (
        0 => 'array{domain: null|string, httponly: bool|null, lifetime: int|null, path: null|string, secure: bool|null}',
      ),
      'new' => 
      array (
        0 => 'array{domain: null|string, httponly: bool|null, lifetime: int|null, path: null|string, samesite: null|string, secure: bool|null}',
      ),
    ),
    'session_set_cookie_params' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'lifetime' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'lifetime_or_options' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
    ),
    'setcookie' => 
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires_or_options=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
    ),
    'setrawcookie' => 
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
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
        'value=' => 'string',
        'expires_or_options=' => 'int',
        'path=' => 'string',
        'domain=' => 'string',
        'secure=' => 'bool',
        'httponly=' => 'bool',
      ),
    ),
    'soapserver::setclass' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'class_name' => 'string',
        'args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'class_name' => 'string',
        '...args=' => 'mixed',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);