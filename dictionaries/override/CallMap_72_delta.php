<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'DOMNamedNodeMap::count' => 
    array (
      0 => 'int',
    ),
    'DOMNodeList::count' => 
    array (
      0 => 'int',
    ),
    'ftp_append' => 
    array (
      0 => 'bool',
      'ftp' => 'resource',
      'remote_file' => 'string',
      'local_file' => 'string',
      'mode' => 'int',
    ),
    'hash_hmac_algos' => 
    array (
      0 => 'list<string>',
    ),
    'imagebmp' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'to=' => 'null|resource|string',
      'compressed=' => 'int',
    ),
    'imagecreatefrombmp' => 
    array (
      0 => 'false|resource',
      'filename' => 'string',
    ),
    'imagegetclip' => 
    array (
      0 => 'array<int, int>|false',
      'im' => 'resource',
    ),
    'imageopenpolygon' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'points' => 'array<array-key, mixed>',
      'num_pos' => 'int',
      'col' => 'int',
    ),
    'imageresolution' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'im' => 'resource',
      'res_x=' => 'int',
      'res_y=' => 'int',
    ),
    'imagesetclip' => 
    array (
      0 => 'bool',
      'im' => 'resource',
      'x1' => 'int',
      'y1' => 'int',
      'x2' => 'int',
      'y2' => 'int',
    ),
    'inflate_get_read_len' => 
    array (
      0 => 'int',
      'resource' => 'resource',
    ),
    'inflate_get_status' => 
    array (
      0 => 'int',
      'resource' => 'resource',
    ),
    'ldap_exop' => 
    array (
      0 => 'bool|resource',
      'ldap' => 'resource',
      'request_oid' => 'string',
      'request_data=' => 'null|string',
      'controls=' => 'array<array-key, mixed>|null',
      '&w response_data=' => 'string',
      '&w response_oid=' => 'string',
    ),
    'ldap_exop_passwd' => 
    array (
      0 => 'bool|string',
      'ldap' => 'resource',
      'user=' => 'string',
      'old_password=' => 'string',
      'new_password=' => 'string',
    ),
    'ldap_exop_refresh' => 
    array (
      0 => 'false|int',
      'ldap' => 'resource',
      'dn' => 'string',
      'ttl' => 'int',
    ),
    'ldap_exop_whoami' => 
    array (
      0 => 'false|string',
      'ldap' => 'resource',
    ),
    'ldap_parse_exop' => 
    array (
      0 => 'bool',
      'ldap' => 'resource',
      'result' => 'resource',
      '&w response_data=' => 'string',
      '&w response_oid=' => 'string',
    ),
    'mb_chr' => 
    array (
      0 => 'false|non-empty-string',
      'cp' => 'int',
      'encoding=' => 'string',
    ),
    'mb_convert_encoding\'1' => 
    array (
      0 => 'array<array-key, mixed>',
      'string' => 'array<array-key, mixed>',
      'to_encoding' => 'string',
      'from_encoding=' => 'mixed',
    ),
    'mb_ord' => 
    array (
      0 => 'false|int',
      'str' => 'string',
      'encoding=' => 'string',
    ),
    'mb_scrub' => 
    array (
      0 => 'string',
      'str' => 'string',
      'encoding=' => 'string',
    ),
    'MongoDB\\BSON\\Document::fromPHP' => 
    array (
      0 => 'MongoDB\\BSON\\Document',
      'value' => 'array<array-key, mixed>|object',
    ),
    'MongoDB\\BSON\\Document::toPHP' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'typeMap=' => 'array<array-key, mixed>|null',
    ),
    'MongoDB\\BSON\\Document::unserialize' => 
    array (
      0 => 'void',
      'serialized' => 'string',
    ),
    'MongoDB\\BSON\\Iterator::key' => 
    array (
      0 => 'int|string',
    ),
    'MongoDB\\BSON\\PackedArray::toPHP' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'typeMap=' => 'array<array-key, mixed>|null',
    ),
    'MongoDB\\BSON\\PackedArray::unserialize' => 
    array (
      0 => 'void',
      'serialized' => 'string',
    ),
    'MongoDB\\Driver\\ClientEncryption::encryptExpression' => 
    array (
      0 => 'object',
      'expr' => 'array<array-key, mixed>|object',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'MongoDB\\Driver\\ClientEncryption::rewrapManyDataKey' => 
    array (
      0 => 'object',
      'filter' => 'array<array-key, mixed>|object',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'MongoDB\\Driver\\Manager::getEncryptedFieldsMap' => 
    array (
      0 => 'array<array-key, mixed>|null|object',
    ),
    'oci_register_taf_callback' => 
    array (
      0 => 'bool',
      'connection' => 'resource',
      'callback=' => 'impure-callable',
    ),
    'oci_unregister_taf_callback' => 
    array (
      0 => 'bool',
      'connection' => 'resource',
    ),
    'opcache_compile_file' => 
    array (
      0 => 'bool',
      'file' => 'string',
    ),
    'opcache_get_configuration' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'opcache_get_status' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'fetch_scripts=' => 'bool',
    ),
    'opcache_invalidate' => 
    array (
      0 => 'bool',
      'script' => 'string',
      'force=' => 'bool',
    ),
    'opcache_is_script_cached' => 
    array (
      0 => 'bool',
      'script' => 'string',
    ),
    'opcache_reset' => 
    array (
      0 => 'bool',
    ),
    'openssl_pkcs7_read' => 
    array (
      0 => 'bool',
      'infilename' => 'string',
      '&w certs' => 'array<array-key, mixed>',
    ),
    'ReflectionClass::isIterable' => 
    array (
      0 => 'bool',
    ),
    'ReflectionObject::isIterable' => 
    array (
      0 => 'bool',
    ),
    'sapi_windows_vt100_support' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
      'enable=' => 'bool',
    ),
    'socket_addrinfo_bind' => 
    array (
      0 => 'null|resource',
      'addrinfo' => 'resource',
    ),
    'socket_addrinfo_connect' => 
    array (
      0 => 'resource',
      'addrinfo' => 'resource',
    ),
    'socket_addrinfo_explain' => 
    array (
      0 => 'array<array-key, mixed>',
      'addrinfo' => 'resource',
    ),
    'socket_addrinfo_lookup' => 
    array (
      0 => 'array<array-key, resource>',
      'host' => 'string',
      'service=' => 'string',
      'hints=' => 'array<array-key, mixed>',
    ),
    'sodium_add' => 
    array (
      0 => 'void',
      '&string_1' => 'string',
      'string_2' => 'string',
    ),
    'sodium_base642bin' => 
    array (
      0 => 'string',
      'string_1' => 'string',
      'id' => 'int',
      'string_2=' => 'string',
    ),
    'sodium_bin2base64' => 
    array (
      0 => 'string',
      'string' => 'string',
      'id' => 'int',
    ),
    'sodium_bin2hex' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'sodium_compare' => 
    array (
      0 => 'int',
      'string_1' => 'string',
      'string_2' => 'string',
    ),
    'sodium_crypto_aead_aes256gcm_is_available' => 
    array (
      0 => 'bool',
    ),
    'sodium_crypto_aead_chacha20poly1305_decrypt' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_encrypt' => 
    array (
      0 => 'string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_encrypt' => 
    array (
      0 => 'string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_aead_chacha20poly1305_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => 
    array (
      0 => 'string',
      'string' => 'string',
      'ad' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_auth' => 
    array (
      0 => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_auth_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_auth_verify' => 
    array (
      0 => 'bool',
      'signature' => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box' => 
    array (
      0 => 'string',
      'string' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium_crypto_box_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
      'public_key' => 'string',
    ),
    'sodium_crypto_box_open' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_publickey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_seal' => 
    array (
      0 => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_seal_open' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_secretkey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box_seed_keypair' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_generichash' => 
    array (
      0 => 'string',
      'string' => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'sodium_crypto_generichash_final' => 
    array (
      0 => 'string',
      '&state' => 'string',
      'length=' => 'int',
    ),
    'sodium_crypto_generichash_init' => 
    array (
      0 => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'sodium_crypto_generichash_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_generichash_update' => 
    array (
      0 => 'true',
      '&state' => 'string',
      'string' => 'string',
    ),
    'sodium_crypto_kdf_derive_from_key' => 
    array (
      0 => 'string',
      'subkey_len' => 'int',
      'subkey_id' => 'int',
      'context' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_kdf_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_kx_client_session_keys' => 
    array (
      0 => 'array<int, string>',
      'client_keypair' => 'string',
      'server_key' => 'string',
    ),
    'sodium_crypto_kx_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium_crypto_kx_publickey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_kx_secretkey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_kx_seed_keypair' => 
    array (
      0 => 'string',
      'string' => 'string',
    ),
    'sodium_crypto_kx_server_session_keys' => 
    array (
      0 => 'array<int, string>',
      'server_keypair' => 'string',
      'client_key' => 'string',
    ),
    'sodium_crypto_pwhash' => 
    array (
      0 => 'string',
      'length' => 'int',
      'password' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
      'alg=' => 'int',
    ),
    'sodium_crypto_pwhash_scryptsalsa208sha256' => 
    array (
      0 => 'string',
      'length' => 'int',
      'password' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
      'alg=' => 'mixed',
    ),
    'sodium_crypto_pwhash_scryptsalsa208sha256_str' => 
    array (
      0 => 'string',
      'password' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium_crypto_pwhash_scryptsalsa208sha256_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'password' => 'string',
    ),
    'sodium_crypto_pwhash_str' => 
    array (
      0 => 'string',
      'password' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium_crypto_pwhash_str_needs_rehash' => 
    array (
      0 => 'bool',
      'password' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium_crypto_pwhash_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'password' => 'string',
    ),
    'sodium_crypto_scalarmult' => 
    array (
      0 => 'string',
      'string_1' => 'string',
      'string_2' => 'string',
    ),
    'sodium_crypto_scalarmult_base' => 
    array (
      0 => 'string',
      'string_1' => 'string',
      'string_2' => 'mixed',
    ),
    'sodium_crypto_secretbox' => 
    array (
      0 => 'string',
      'string' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_secretbox_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_secretbox_open' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => 
    array (
      0 => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_init_push' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_pull' => 
    array (
      0 => 'array<array-key, mixed>|false',
      '&r state' => 'string',
      'string=' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_push' => 
    array (
      0 => 'string',
      '&w state' => 'string',
      'string=' => 'string',
      'long=' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_rekey' => 
    array (
      0 => 'void',
      '&w state' => 'string',
    ),
    'sodium_crypto_shorthash' => 
    array (
      0 => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_shorthash_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_sign' => 
    array (
      0 => 'string',
      'string' => 'string',
      'keypair' => 'string',
    ),
    'sodium_crypto_sign_detached' => 
    array (
      0 => 'string',
      'string' => 'string',
      'keypair' => 'string',
    ),
    'sodium_crypto_sign_ed25519_pk_to_curve25519' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_ed25519_sk_to_curve25519' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium_crypto_sign_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
      'public_key' => 'string',
    ),
    'sodium_crypto_sign_open' => 
    array (
      0 => 'false|string',
      'string' => 'string',
      'keypair' => 'string',
    ),
    'sodium_crypto_sign_publickey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_secretkey' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_seed_keypair' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_sign_verify_detached' => 
    array (
      0 => 'bool',
      'signature' => 'string',
      'string' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_stream' => 
    array (
      0 => 'string',
      'length' => 'int',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_stream_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_stream_xor' => 
    array (
      0 => 'string',
      'string' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_hex2bin' => 
    array (
      0 => 'string',
      'string_1' => 'string',
      'string_2=' => 'string',
    ),
    'sodium_increment' => 
    array (
      0 => 'void',
      '&string' => 'string',
    ),
    'sodium_memcmp' => 
    array (
      0 => 'int',
      'string_1' => 'string',
      'string_2' => 'string',
    ),
    'sodium_memzero' => 
    array (
      0 => 'void',
      '&w reference' => 'string',
    ),
    'sodium_pad' => 
    array (
      0 => 'string',
      'string' => 'string',
      'length' => 'int',
    ),
    'sodium_unpad' => 
    array (
      0 => 'string',
      'string' => 'string',
      'length' => 'int',
    ),
    'spl_object_id' => 
    array (
      0 => 'int',
      'obj' => 'object',
    ),
    'stream_isatty' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
    ),
    'xdebug_info' => 
    array (
      0 => 'mixed',
      'category=' => 'string',
    ),
    'ZipArchive::count' => 
    array (
      0 => 'int',
    ),
    'ZipArchive::setEncryptionIndex' => 
    array (
      0 => 'bool',
      'index' => 'int',
      'method' => 'int',
      'password=' => 'string',
    ),
    'ZipArchive::setEncryptionName' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'method' => 'int',
      'password=' => 'string',
    ),
  ),
  'changed' => 
  array (
    'ArrayIterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'input=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
        'iterator_class=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'ar_flags=' => 'int',
      ),
    ),
    'bcmod' => 
    array (
      'old' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
      ),
      'new' => 
      array (
        0 => 'numeric-string',
        'left_operand' => 'numeric-string',
        'right_operand' => 'numeric-string',
        'scale=' => 'int',
      ),
    ),
    'hash_copy' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'context' => 'resource',
      ),
      'new' => 
      array (
        0 => 'HashContext',
        'context' => 'HashContext',
      ),
    ),
    'hash_final' => 
    array (
      'old' => 
      array (
        0 => 'non-empty-string',
        'context' => 'resource',
        'raw_output=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'non-empty-string',
        'context' => 'HashContext',
        'raw_output=' => 'bool',
      ),
    ),
    'hash_init' => 
    array (
      'old' => 
      array (
        0 => 'resource',
        'algo' => 'string',
        'options=' => 'int',
        'key=' => 'string',
      ),
      'new' => 
      array (
        0 => 'HashContext|false',
        'algo' => 'string',
        'options=' => 'int',
        'key=' => 'string',
      ),
    ),
    'hash_update' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'data' => 'string',
      ),
    ),
    'hash_update_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'context' => 'resource',
        'filename' => 'string',
        'stream_context=' => 'resource',
      ),
      'new' => 
      array (
        0 => 'bool',
        'context' => 'HashContext',
        'filename' => 'string',
        'stream_context=' => 'resource',
      ),
    ),
    'hash_update_stream' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'context' => 'resource',
        'handle' => 'resource',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'context' => 'HashContext',
        'handle' => 'resource',
        'length=' => 'int',
      ),
    ),
    'json_decode' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'assoc=' => 'bool',
        'depth=' => 'int',
        'options=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'assoc=' => 'bool|null',
        'depth=' => 'int',
        'options=' => 'int',
      ),
    ),
    'mb_check_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'var=' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'var=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
    ),
    'mb_decode_numericentity' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'string' => 'string',
        'convmap' => 'array<array-key, mixed>',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'string' => 'string',
        'convmap' => 'array<array-key, mixed>',
        'encoding=' => 'string',
        'is_hex=' => 'mixed',
      ),
    ),
    'MongoDB\\BSON\\Binary::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'string',
        'type' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
        'type=' => 'int',
      ),
    ),
    'MongoDB\\BSON\\Int64::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'void',
        'value' => 'int|string',
      ),
    ),
    'MongoDB\\BSON\\Javascript::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'javascript' => 'string',
        'scope=' => 'array<array-key, mixed>|null|object',
      ),
      'new' => 
      array (
        0 => 'void',
        'code' => 'string',
        'scope=' => 'array<array-key, mixed>|null|object',
      ),
    ),
    'MongoDB\\Driver\\BulkWrite::delete' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'query' => 'array<array-key, mixed>|object',
        'deleteOptions=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'filter' => 'array<array-key, mixed>|object',
        'deleteOptions=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\BulkWrite::update' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'query' => 'array<array-key, mixed>|object',
        'newObj' => 'array<array-key, mixed>|object',
        'updateOptions=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'filter' => 'array<array-key, mixed>|object',
        'newObj' => 'array<array-key, mixed>|object',
        'updateOptions=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Command::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'document' => 'array<array-key, mixed>|object',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'document' => 'array<array-key, mixed>|object',
        'commandOptions=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Exception\\RuntimeException::hasErrorLabel' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'label' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'errorLabel' => 'string',
      ),
    ),
    'MongoDB\\Driver\\Manager::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'uri=' => 'null|string',
        'options=' => 'array<array-key, mixed>',
        'driverOptions=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'uri=' => 'null|string',
        'uriOptions=' => 'array<array-key, mixed>|null',
        'driverOptions=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Manager::executeBulkWrite' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\WriteResult',
        'namespace' => 'string',
        'zbulk' => 'MongoDB\\Driver\\BulkWrite',
        'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\WriteResult',
        'namespace' => 'string',
        'bulk' => 'MongoDB\\Driver\\BulkWrite',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Manager::executeQuery' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'namespace' => 'string',
        'zquery' => 'MongoDB\\Driver\\Query',
        'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'namespace' => 'string',
        'query' => 'MongoDB\\Driver\\Query',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Manager::executeReadCommand' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Manager::executeWriteCommand' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Query::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'filter' => 'array<array-key, mixed>|object',
        'options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'filter' => 'array<array-key, mixed>|object',
        'queryOptions=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Server::executeBulkWrite' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\WriteResult',
        'namespace' => 'string',
        'zbulk' => 'MongoDB\\Driver\\BulkWrite',
        'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\WriteResult',
        'namespace' => 'string',
        'bulkWrite' => 'MongoDB\\Driver\\BulkWrite',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Server::executeQuery' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'namespace' => 'string',
        'zquery' => 'MongoDB\\Driver\\Query',
        'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'namespace' => 'string',
        'query' => 'MongoDB\\Driver\\Query',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Server::executeReadCommand' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Server::executeReadWriteCommand' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Server::executeWriteCommand' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\Cursor',
        'db' => 'string',
        'command' => 'MongoDB\\Driver\\Command',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'MongoDB\\Driver\\Session::advanceOperationTime' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'timestamp' => 'MongoDB\\BSON\\TimestampInterface',
      ),
      'new' => 
      array (
        0 => 'void',
        'operationTime' => 'MongoDB\\BSON\\TimestampInterface',
      ),
    ),
    'MongoDB\\Driver\\WriteResult::getDeletedCount' => 
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
    'MongoDB\\Driver\\WriteResult::getInsertedCount' => 
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
    'MongoDB\\Driver\\WriteResult::getMatchedCount' => 
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
    'MongoDB\\Driver\\WriteResult::getModifiedCount' => 
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
    'MongoDB\\Driver\\WriteResult::getUpsertedCount' => 
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
    'openssl_pkcs7_verify' => 
    array (
      'old' => 
      array (
        0 => 'bool|int',
        'filename' => 'string',
        'flags' => 'int',
        'signerscerts=' => 'string',
        'cainfo=' => 'array<array-key, mixed>',
        'extracerts=' => 'string',
        'content=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|int',
        'filename' => 'string',
        'flags' => 'int',
        'signerscerts=' => 'string',
        'cainfo=' => 'array<array-key, mixed>',
        'extracerts=' => 'string',
        'content=' => 'string',
        'pk7=' => 'string',
      ),
    ),
    'preg_quote' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delim_char=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delim_char=' => 'null|string',
      ),
    ),
    'RecursiveArrayIterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'input=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
        'iterator_class=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'ar_flags=' => 'int',
      ),
    ),
    'Redis::auth' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'auth' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'credentials' => 'string',
      ),
    ),
    'Redis::bitcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'bybit=' => 'mixed',
      ),
    ),
    'Redis::bitop' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'operation' => 'string',
        'ret_key' => 'string',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'operation' => 'string',
        'deskey' => 'string',
        'srckey' => 'string',
        '...other_keys=' => 'string',
      ),
    ),
    'Redis::bitpos' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'mixed',
      ),
    ),
    'Redis::blPop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'Redis::brPop' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_keys' => 'array<array-key, string>',
        'timeout_or_key' => 'int',
        '...extra_args=' => 'mixed',
      ),
    ),
    'Redis::client' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cmd' => 'string',
        '...args=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'opt' => 'string',
        '...args=' => 'string',
      ),
    ),
    'Redis::config' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'cmd' => 'string',
        'key' => 'string',
        'value=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'operation' => 'string',
        'key_or_settings=' => 'string',
        'value=' => 'string',
      ),
    ),
    'Redis::connect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'retry_interval=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'float',
        'context=' => 'mixed',
      ),
    ),
    'Redis::decr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
    ),
    'Redis::echo' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'msg' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
      ),
    ),
    'Redis::evalsha' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'script_sha' => 'string',
        'args=' => 'array<array-key, mixed>',
        'num_keys=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'sha1' => 'string',
        'args=' => 'array<array-key, mixed>',
        'num_keys=' => 'int',
      ),
    ),
    'Redis::expire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'Redis::expireAt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'Redis::flushAll' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'async=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'sync=' => 'bool',
      ),
    ),
    'Redis::flushDB' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'async=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'sync=' => 'bool',
      ),
    ),
    'Redis::geoadd' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'member' => 'string',
        '...other_triples=' => 'float|int|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'member' => 'string',
        '...other_triples_and_options=' => 'float|int|string',
      ),
    ),
    'Redis::georadius' => 
    array (
      'old' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'lng' => 'float',
        'lan' => 'float',
        'radius' => 'float',
        'unit' => 'float',
        'opts=' => 'array<string, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'radius' => 'float',
        'unit' => 'float',
        'options=' => 'array<string, mixed>',
      ),
    ),
    'Redis::georadiusbymember' => 
    array (
      'old' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'member' => 'string',
        'radius' => 'float',
        'unit' => 'string',
        'opts=' => 'array<string, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<int, mixed>|int',
        'key' => 'string',
        'member' => 'string',
        'radius' => 'float',
        'unit' => 'string',
        'options=' => 'array<string, mixed>',
      ),
    ),
    'Redis::getBit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'idx' => 'int',
      ),
    ),
    'Redis::hDel' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'member' => 'string',
        '...other_members=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'field' => 'string',
        '...other_fields=' => 'string',
      ),
    ),
    'Redis::hExists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'member' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'field' => 'string',
      ),
    ),
    'Redis::hIncrBy' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'member' => 'string',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'field' => 'string',
        'value' => 'int',
      ),
    ),
    'Redis::hIncrByFloat' => 
    array (
      'old' => 
      array (
        0 => 'float',
        'key' => 'string',
        'member' => 'string',
        'value' => 'float',
      ),
      'new' => 
      array (
        0 => 'float',
        'key' => 'string',
        'field' => 'string',
        'value' => 'float',
      ),
    ),
    'Redis::hMget' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'fields' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::hMset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'fieldvals' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::hscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'Redis::hSetNx' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'member' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'field' => 'string',
        'value' => 'string',
      ),
    ),
    'Redis::incr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
    ),
    'Redis::info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '...sections=' => 'string',
      ),
    ),
    'Redis::lInsert' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'position' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
    ),
    'Redis::lPop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'Redis::lPush' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        '...elements=' => 'string',
      ),
    ),
    'Redis::lrem' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        'count' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        'count=' => 'int',
      ),
    ),
    'Redis::ltrim' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'stop' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
    ),
    'Redis::migrate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port' => 'int',
        'key' => 'array<array-key, string>|string',
        'db' => 'int',
        'timeout' => 'int',
        'copy=' => 'bool',
        'replace=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port' => 'int',
        'key' => 'array<array-key, string>|string',
        'dstdb' => 'int',
        'timeout' => 'int',
        'copy=' => 'bool',
        'replace=' => 'bool',
        'credentials=' => 'mixed',
      ),
    ),
    'Redis::move' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'dbindex' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'index' => 'int',
      ),
    ),
    'Redis::mset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::msetnx' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::multi' => 
    array (
      'old' => 
      array (
        0 => 'Redis',
        'mode=' => 'int',
      ),
      'new' => 
      array (
        0 => 'Redis',
        'value=' => 'int',
      ),
    ),
    'Redis::object' => 
    array (
      'old' => 
      array (
        0 => 'false|long|string',
        'field' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|long|string',
        'subcommand' => 'string',
        'key' => 'string',
      ),
    ),
    'Redis::open' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'retry_interval=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'null',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'float',
        'context=' => 'mixed',
      ),
    ),
    'Redis::pconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'string',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'mixed',
        'context=' => 'mixed',
      ),
    ),
    'Redis::pexpire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'Redis::pexpireAt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'Redis::pfcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key_or_keys' => 'array<array-key, mixed>|string',
      ),
    ),
    'Redis::pfmerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dstkey' => 'string',
        'keys' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dst' => 'string',
        'srckeys' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::ping' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'message=' => 'mixed',
      ),
    ),
    'Redis::popen' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'host' => 'string',
        'port=' => 'int',
        'timeout=' => 'float',
        'persistent_id=' => 'string',
        'retry_interval=' => 'int|null',
        'read_timeout=' => 'mixed',
        'context=' => 'mixed',
      ),
    ),
    'Redis::psubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'patterns' => 'array<array-key, mixed>',
        'callback' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'patterns' => 'array<array-key, mixed>',
        'cb' => 'array<array-key, mixed>|string',
      ),
    ),
    'Redis::pubsub' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'cmd' => 'string',
        '...args=' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|int',
        'command' => 'string',
        'arg=' => 'array<array-key, mixed>|string',
      ),
    ),
    'Redis::punsubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'pattern' => 'string',
        '...other_patterns=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'patterns' => 'string',
      ),
    ),
    'Redis::rawcommand' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cmd' => 'string',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'command' => 'string',
        '...args=' => 'mixed',
      ),
    ),
    'Redis::rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'newkey' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'old_name' => 'string',
        'new_name' => 'string',
      ),
    ),
    'Redis::renameNx' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'newkey' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key_src' => 'string',
        'key_dst' => 'string',
      ),
    ),
    'Redis::restore' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ttl' => 'int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'ttl' => 'int',
        'value' => 'string',
        'options=' => 'mixed',
      ),
    ),
    'Redis::rPop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'Redis::rpoplpush' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'src' => 'string',
        'dst' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'srckey' => 'string',
        'dstkey' => 'string',
      ),
    ),
    'Redis::rPush' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        '...elements=' => 'string',
      ),
    ),
    'Redis::sAdd' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'Redis::sAddArray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::scan' => 
    array (
      'old' => 
      array (
        0 => 'array<int, string>|false',
        '&i_iterator' => 'int|null',
        'str_pattern=' => 'null|string',
        'i_count=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'array<int, string>|false',
        '&iterator' => 'int|null',
        'pattern=' => 'null|string',
        'count=' => 'int|null',
        'type=' => 'mixed',
      ),
    ),
    'Redis::script' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cmd' => 'string',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'command' => 'string',
        '...args=' => 'mixed',
      ),
    ),
    'Redis::select' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dbindex' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'db' => 'int',
      ),
    ),
    'Redis::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'mixed',
        'opts=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'mixed',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::setBit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'idx' => 'int',
        'value' => 'int',
      ),
    ),
    'Redis::setRange' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'value' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'index' => 'int',
        'value' => 'int',
      ),
    ),
    'Redis::sInterStore' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'dst' => 'string',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
    ),
    'Redis::slowlog' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'arg' => 'string',
        'option=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'length=' => 'int',
      ),
    ),
    'Redis::sortAsc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'string',
        'get=' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'getList=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'string',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
    ),
    'Redis::sortAscAlpha' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'getList=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
    ),
    'Redis::sortDesc' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'getList=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
    ),
    'Redis::sortDescAlpha' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'start=' => 'int',
        'end=' => 'int',
        'getList=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'pattern=' => 'mixed',
        'get=' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
        'store=' => 'bool',
      ),
    ),
    'Redis::sPop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'Redis::srem' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'member' => 'string',
        '...other_members=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'Redis::sscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'Redis::subscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed|null',
        'channels' => 'array<array-key, mixed>',
        'callback' => 'array<array-key, mixed>|string',
      ),
      'new' => 
      array (
        0 => 'mixed|null',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'array<array-key, mixed>|string',
      ),
    ),
    'Redis::swapdb' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'srcdb' => 'int',
        'dstdb' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'src' => 'int',
        'dst' => 'int',
      ),
    ),
    'Redis::unsubscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'channel' => 'string',
        '...other_channels=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'channels' => 'string',
      ),
    ),
    'Redis::wait' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'numslaves' => 'int',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'numreplicas' => 'int',
        'timeout' => 'int',
      ),
    ),
    'Redis::xack' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'arr_ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::xadd' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_id' => 'string',
        'arr_fields' => 'array<array-key, mixed>',
        'i_maxlen=' => 'mixed',
        'boo_approximate=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'mixed',
        'approx=' => 'mixed',
        'nomkstream=' => 'mixed',
      ),
    ),
    'Redis::xclaim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'str_consumer' => 'string',
        'i_min_idle' => 'mixed',
        'arr_ids' => 'array<array-key, mixed>',
        'arr_opts=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_idle' => 'mixed',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::xdel' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'arr_ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::xgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_operation' => 'string',
        'str_key=' => 'string',
        'str_arg1=' => 'mixed',
        'str_arg2=' => 'mixed',
        'str_arg3=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'string',
        'group=' => 'mixed',
        'id_or_consumer=' => 'mixed',
        'mkstream=' => 'mixed',
        'entries_read=' => 'mixed',
      ),
    ),
    'Redis::xinfo' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_cmd' => 'string',
        'str_key=' => 'string',
        'str_group=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'string',
        'arg2=' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'Redis::xpending' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'str_start=' => 'mixed',
        'str_end=' => 'mixed',
        'i_count=' => 'mixed',
        'str_consumer=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'count=' => 'mixed',
        'consumer=' => 'string',
      ),
    ),
    'Redis::xrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_start' => 'mixed',
        'str_end' => 'mixed',
        'i_count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
    ),
    'Redis::xread' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'arr_streams' => 'array<array-key, mixed>',
        'i_count=' => 'mixed',
        'i_block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
    ),
    'Redis::xreadgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_group' => 'string',
        'str_consumer' => 'string',
        'arr_streams' => 'array<array-key, mixed>',
        'i_count=' => 'mixed',
        'i_block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
    ),
    'Redis::xrevrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_start' => 'mixed',
        'str_end' => 'mixed',
        'i_count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'end' => 'mixed',
        'start' => 'mixed',
        'count=' => 'mixed',
      ),
    ),
    'Redis::xtrim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'i_maxlen' => 'mixed',
        'boo_approximate=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'threshold' => 'mixed',
        'approx=' => 'mixed',
        'minid=' => 'mixed',
        'limit=' => 'mixed',
      ),
    ),
    'Redis::zAdd' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'score' => 'float',
        'value' => 'string',
        '...extra_args=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'score_or_options' => 'float',
        '...more_scores_and_mems=' => 'string',
      ),
    ),
    'Redis::zCount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
      ),
    ),
    'Redis::zinter' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'keys' => 'string',
        'weights=' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'Redis::zinterstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
    ),
    'Redis::zRange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'scores=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool',
      ),
    ),
    'Redis::zRangeByLex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'limit=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'count=' => 'int',
      ),
    ),
    'Redis::zRemRangeByScore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'float|string',
        'max' => 'float|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'float|string',
        'end' => 'float|string',
      ),
    ),
    'Redis::zRevRangeByLex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
        'offset=' => 'int',
        'limit=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'max' => 'string',
        'min' => 'string',
        'offset=' => 'int',
        'count=' => 'int',
      ),
    ),
    'Redis::zRevRangeByScore' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'max' => 'string',
        'min' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'Redis::zscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'Redis::zunion' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'keys' => 'string',
        'weights=' => 'array<array-key, mixed>',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'Redis::zunionstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
    ),
    'RedisArray::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name_or_hosts' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'name_or_hosts' => 'string',
        'options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'RedisArray::_rehash' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'callable=' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'fn=' => 'impure-callable',
      ),
    ),
    'RedisArray::del' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'keys' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        '...otherkeys=' => 'string',
      ),
    ),
    'RedisArray::flushall' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'async=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'RedisArray::flushdb' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'async=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'RedisArray::unlink' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        '...otherkeys=' => 'string',
      ),
    ),
    'RedisCluster::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'null|string',
        'seeds=' => 'array<array-key, string>',
        'timeout=' => 'float',
        'read_timeout=' => 'float',
        'persistent=' => 'bool',
        'auth=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'null|string',
        'seeds=' => 'array<array-key, string>',
        'timeout=' => 'float',
        'read_timeout=' => 'float',
        'persistent=' => 'bool',
        'auth=' => 'null|string',
        'context=' => 'mixed',
      ),
    ),
    'RedisCluster::bitcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'bybit=' => 'mixed',
      ),
    ),
    'RedisCluster::bitop' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'operation' => 'string',
        'ret_key' => 'string',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'operation' => 'string',
        'deskey' => 'string',
        'srckey' => 'string',
        '...otherkeys=' => 'string',
      ),
    ),
    'RedisCluster::bitpos' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'bit' => 'int',
        'start=' => 'int',
        'end=' => 'int',
        'bybit=' => 'mixed',
      ),
    ),
    'RedisCluster::brpoplpush' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'src' => 'string',
        'dst' => 'string',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'srckey' => 'string',
        'deskey' => 'string',
        'timeout' => 'int',
      ),
    ),
    'RedisCluster::client' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'arg=' => 'string',
        '...other_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'subcommand' => 'string',
        'arg=' => 'mixed',
      ),
    ),
    'RedisCluster::cluster' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'arg=' => 'string',
        '...other_args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'command' => 'string',
        '...extra_args=' => 'mixed',
      ),
    ),
    'RedisCluster::command' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        '...args=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        '...extra_args=' => 'mixed',
      ),
    ),
    'RedisCluster::config' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'arg=' => 'string',
        '...other_args=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'subcommand' => 'string',
        '...extra_args=' => 'string',
      ),
    ),
    'RedisCluster::decr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
    ),
    'RedisCluster::echo' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'msg' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'msg' => 'string',
      ),
    ),
    'RedisCluster::exists' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        '...other_keys=' => 'mixed',
      ),
    ),
    'RedisCluster::expire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'RedisCluster::expireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'RedisCluster::geoadd' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'member' => 'string',
        '...other_triples=' => 'float|string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'member' => 'string',
        '...other_triples_and_options=' => 'float|string',
      ),
    ),
    'RedisCluster::geodist' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'src' => 'string',
        'dst' => 'string',
        'unit=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'src' => 'string',
        'dest' => 'string',
        'unit=' => 'string',
      ),
    ),
    'RedisCluster::georadius' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'lng' => 'float',
        'lan' => 'float',
        'radius' => 'float',
        'unit' => 'string',
        'opts=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'lng' => 'float',
        'lat' => 'float',
        'radius' => 'float',
        'unit' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::georadiusbymember' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>',
        'key' => 'string',
        'member' => 'string',
        'radius' => 'float',
        'unit' => 'string',
        'opts=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>',
        'key' => 'string',
        'member' => 'string',
        'radius' => 'float',
        'unit' => 'string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::getbit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'value' => 'int',
      ),
    ),
    'RedisCluster::hmset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::hscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::hstrlen' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'member' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'field' => 'string',
      ),
    ),
    'RedisCluster::incr' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'by=' => 'mixed',
      ),
    ),
    'RedisCluster::info' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'option=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'array{0: string, 1: int}|string',
        '...sections=' => 'string',
      ),
    ),
    'RedisCluster::linsert' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'position' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'pos' => 'int',
        'pivot' => 'string',
        'value' => 'string',
      ),
    ),
    'RedisCluster::lpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::lpush' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'RedisCluster::lrem' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::ltrim' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'stop' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
      ),
    ),
    'RedisCluster::mset' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::msetnx' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'pairs' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'int',
        'key_values' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::multi' => 
    array (
      'old' => 
      array (
        0 => 'Redis',
      ),
      'new' => 
      array (
        0 => 'Redis',
        'value=' => 'int',
      ),
    ),
    'RedisCluster::object' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'field' => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'subcommand' => 'string',
        'key' => 'string',
      ),
    ),
    'RedisCluster::pexpire' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'RedisCluster::pexpireat' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timestamp' => 'int',
        'mode=' => 'mixed',
      ),
    ),
    'RedisCluster::pfmerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dstkey' => 'string',
        'keys' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::ping' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key_or_address' => 'array{0: string, 1: int}|string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'message=' => 'mixed',
      ),
    ),
    'RedisCluster::psetex' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'expire' => 'int',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'value' => 'string',
      ),
    ),
    'RedisCluster::pubsub' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'string',
        'arg=' => 'string',
        '...other_args=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'string',
        '...values=' => 'string',
      ),
    ),
    'RedisCluster::rawcommand' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cmd' => 'array{0: string, 1: int}|string',
        '...args=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'command' => 'string',
        '...args=' => 'mixed',
      ),
    ),
    'RedisCluster::rename' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'newkey' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key_src' => 'string',
        'key_dst' => 'string',
      ),
    ),
    'RedisCluster::restore' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'ttl' => 'int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'timeout' => 'int',
        'value' => 'string',
        'options=' => 'mixed',
      ),
    ),
    'RedisCluster::role' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'mixed',
      ),
    ),
    'RedisCluster::rpop' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::rpush' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        '...elements=' => 'string',
      ),
    ),
    'RedisCluster::sadd' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'RedisCluster::saddarray' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'options' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'values' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::scan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&i_iterator' => 'int',
        'str_node' => 'array{0: string, 1: int}|string',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        '&iterator' => 'int',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::script' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'arg=' => 'string',
        '...other_args=' => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool|string',
        'key_or_address' => 'array{0: string, 1: int}|string',
        '...args=' => 'string',
      ),
    ),
    'RedisCluster::set' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'string',
        'opts=' => 'array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'string',
        'options=' => 'array<array-key, mixed>|int',
      ),
    ),
    'RedisCluster::setbit' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'value' => 'bool|int',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'offset' => 'int',
        'onoff' => 'bool|int',
      ),
    ),
    'RedisCluster::sinterstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        '...other_keys=' => 'string',
      ),
    ),
    'RedisCluster::slowlog' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool|int',
        'key_or_address' => 'array{0: string, 1: int}|string',
        'arg=' => 'string',
        '...other_args=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|bool|int',
        'key_or_address' => 'array{0: string, 1: int}|string',
        '...args=' => 'string',
      ),
    ),
    'RedisCluster::smove' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'src' => 'string',
        'dst' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'src' => 'string',
        'dst' => 'string',
        'member' => 'string',
      ),
    ),
    'RedisCluster::spop' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::srem' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'RedisCluster::sscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'null',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'null',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::subscribe' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'channels' => 'array<array-key, mixed>',
        'callback' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'channels' => 'array<array-key, mixed>',
        'cb' => 'string',
      ),
    ),
    'RedisCluster::time' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key_or_address' => 'mixed',
      ),
    ),
    'RedisCluster::xack' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'arr_ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::xadd' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_id' => 'string',
        'arr_fields' => 'array<array-key, mixed>',
        'i_maxlen=' => 'mixed',
        'boo_approximate=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'id' => 'string',
        'values' => 'array<array-key, mixed>',
        'maxlen=' => 'mixed',
        'approx=' => 'mixed',
      ),
    ),
    'RedisCluster::xclaim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'str_consumer' => 'string',
        'i_min_idle' => 'mixed',
        'arr_ids' => 'array<array-key, mixed>',
        'arr_opts=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'consumer' => 'string',
        'min_iddle' => 'mixed',
        'ids' => 'array<array-key, mixed>',
        'options' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::xdel' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'arr_ids' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'ids' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::xgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_operation' => 'string',
        'str_key=' => 'string',
        'str_arg1=' => 'mixed',
        'str_arg2=' => 'mixed',
        'str_arg3=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'key=' => 'string',
        'group=' => 'mixed',
        'id_or_consumer=' => 'mixed',
        'mkstream=' => 'mixed',
        'entries_read=' => 'mixed',
      ),
    ),
    'RedisCluster::xinfo' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_cmd' => 'string',
        'str_key=' => 'string',
        'str_group=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'operation' => 'string',
        'arg1=' => 'string',
        'arg2=' => 'string',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::xpending' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_group' => 'string',
        'str_start=' => 'mixed',
        'str_end=' => 'mixed',
        'i_count=' => 'mixed',
        'str_consumer=' => 'string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'group' => 'string',
        'start=' => 'mixed',
        'end=' => 'mixed',
        'count=' => 'mixed',
        'consumer=' => 'string',
      ),
    ),
    'RedisCluster::xrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_start' => 'mixed',
        'str_end' => 'mixed',
        'i_count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::xread' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'arr_streams' => 'array<array-key, mixed>',
        'i_count=' => 'mixed',
        'i_block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
    ),
    'RedisCluster::xreadgroup' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_group' => 'string',
        'str_consumer' => 'string',
        'arr_streams' => 'array<array-key, mixed>',
        'i_count=' => 'mixed',
        'i_block=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'group' => 'string',
        'consumer' => 'string',
        'streams' => 'array<array-key, mixed>',
        'count=' => 'mixed',
        'block=' => 'mixed',
      ),
    ),
    'RedisCluster::xrevrange' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'str_start' => 'mixed',
        'str_end' => 'mixed',
        'i_count=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'start' => 'mixed',
        'end' => 'mixed',
        'count=' => 'mixed',
      ),
    ),
    'RedisCluster::xtrim' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'str_key' => 'string',
        'i_maxlen' => 'mixed',
        'boo_approximate=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'key' => 'string',
        'maxlen' => 'mixed',
        'approx=' => 'mixed',
        'minid=' => 'mixed',
        'limit=' => 'mixed',
      ),
    ),
    'RedisCluster::zadd' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'score' => 'float',
        'value' => 'string',
        '...extra_args=' => 'float',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'score_or_options' => 'float',
        '...more_scores_and_mems=' => 'string',
      ),
    ),
    'RedisCluster::zcount' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'min' => 'string',
        'max' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'start' => 'string',
        'end' => 'string',
      ),
    ),
    'RedisCluster::zinterstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
    ),
    'RedisCluster::zrange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'scores=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'bool',
      ),
    ),
    'RedisCluster::zrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'limit=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::zrem' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'member' => 'string',
        '...other_members=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'key' => 'string',
        'value' => 'string',
        '...other_values=' => 'string',
      ),
    ),
    'RedisCluster::zrevrange' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'scores=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'bool',
      ),
    ),
    'RedisCluster::zrevrangebylex' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'offset=' => 'int',
        'limit=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'int',
      ),
    ),
    'RedisCluster::zrevrangebyscore' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'start' => 'int',
        'end' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'key' => 'string',
        'min' => 'int',
        'max' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'RedisCluster::zscan' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'str_key' => 'string',
        '&i_iterator' => 'int',
        'str_pattern=' => 'string',
        'i_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'key' => 'string',
        '&iterator' => 'int',
        'pattern=' => 'string',
        'count=' => 'int',
      ),
    ),
    'RedisCluster::zunionstore' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'key' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'dst' => 'string',
        'keys' => 'array<array-key, mixed>',
        'weights=' => 'array<array-key, mixed>|null',
        'aggregate=' => 'string',
      ),
    ),
    'ReflectionClass::getMethods' => 
    array (
      'old' => 
      array (
        0 => 'list<ReflectionMethod>',
        'filter=' => 'int',
      ),
      'new' => 
      array (
        0 => 'list<ReflectionMethod>',
        'filter=' => 'int|null',
      ),
    ),
    'ReflectionClass::getProperties' => 
    array (
      'old' => 
      array (
        0 => 'list<ReflectionProperty>',
        'filter=' => 'int',
      ),
      'new' => 
      array (
        0 => 'list<ReflectionProperty>',
        'filter=' => 'int|null',
      ),
    ),
    'ReflectionObject::getMethods' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, ReflectionMethod>',
        'filter=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, ReflectionMethod>',
        'filter=' => 'int|null',
      ),
    ),
    'ReflectionObject::getProperties' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, ReflectionProperty>',
        'filter=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, ReflectionProperty>',
        'filter=' => 'int|null',
      ),
    ),
    'SQLite3::openBlob' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'table' => 'string',
        'column' => 'string',
        'rowid' => 'int',
        'dbname=' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'table' => 'string',
        'column' => 'string',
        'rowid' => 'int',
        'dbname=' => 'string',
        'flags=' => 'int',
      ),
    ),
    'Swoole\\Connection\\Iterator::next' => 
    array (
      'old' => 
      array (
        0 => 'Connection',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'Swoole\\Http\\Response::header' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
        'ucwords=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
        'format=' => 'string',
      ),
    ),
    'Swoole\\Server::task' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'worker_id=' => 'int',
        'finish_callback=' => 'impure-callable|null',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'task_worker_index=' => 'int',
        'finish_callback=' => 'impure-callable|null',
      ),
    ),
    'Swoole\\Server::taskwait' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'data' => 'string',
        'timeout=' => 'float',
        'worker_id=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'data' => 'string',
        'timeout=' => 'float',
        'task_worker_index=' => 'int',
      ),
    ),
    'Swoole\\Table::next' => 
    array (
      'old' => 
      array (
        0 => 'ReturnType',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
  ),
  'removed' => 
  array (
    'MongoDB\\BSON\\toPHP' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'bson' => 'string',
      'typemap=' => 'array<array-key, mixed>',
    ),
    'Redis::evaluate' => 
    array (
      0 => 'mixed',
      'script' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'Redis::evaluateSha' => 
    array (
      0 => 'mixed',
      'script_sha' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'Redis::getKeys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'Redis::getMultiple' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'Redis::lGet' => 
    array (
      0 => 'string',
      'key' => 'string',
      'index' => 'int',
    ),
    'Redis::lGetRange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::listTrim' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'start' => 'int',
      'stop' => 'int',
    ),
    'Redis::lRemove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      'count' => 'int',
    ),
    'Redis::lSize' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::renameKey' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'newkey' => 'string',
    ),
    'Redis::sContains' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::sendEcho' => 
    array (
      0 => 'string',
      'msg' => 'string',
    ),
    'Redis::setTimeout' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'timeout' => 'int',
    ),
    'Redis::sGetMembers' => 
    array (
      0 => 'mixed',
      'key' => 'string',
    ),
    'Redis::sRemove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::sSize' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::substr' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::zDelete' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::zDeleteRangeByRank' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'min' => 'int',
      'max' => 'int',
    ),
    'Redis::zDeleteRangeByScore' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'min' => 'float',
      'max' => 'float',
    ),
    'Redis::zRemove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::zRemoveRangeByScore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'float|string',
      'max' => 'float|string',
    ),
    'Redis::zReverseRange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'scores=' => 'bool',
    ),
    'Redis::zSize' => 
    array (
      0 => 'mixed',
      'key' => 'string',
    ),
    'RedisArray::delete' => 
    array (
      0 => 'bool',
      'keys' => 'string',
    ),
    'Sodium\\add' => 
    array (
      0 => 'void',
      '&left' => 'string',
      'right' => 'string',
    ),
    'Sodium\\bin2hex' => 
    array (
      0 => 'string',
      'binary' => 'string',
    ),
    'Sodium\\compare' => 
    array (
      0 => 'int',
      'left' => 'string',
      'right' => 'string',
    ),
    'Sodium\\crypto_aead_aes256gcm_decrypt' => 
    array (
      0 => 'false|string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'Sodium\\crypto_aead_aes256gcm_encrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'Sodium\\crypto_aead_aes256gcm_is_available' => 
    array (
      0 => 'bool',
    ),
    'Sodium\\crypto_aead_chacha20poly1305_decrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'Sodium\\crypto_aead_chacha20poly1305_encrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'Sodium\\crypto_auth' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_auth_verify' => 
    array (
      0 => 'bool',
      'mac' => 'string',
      'msg' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_box' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_box_keypair' => 
    array (
      0 => 'string',
    ),
    'Sodium\\crypto_box_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
    ),
    'Sodium\\crypto_box_open' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_box_publickey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_box_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
    ),
    'Sodium\\crypto_box_seal' => 
    array (
      0 => 'string',
      'message' => 'string',
      'publickey' => 'string',
    ),
    'Sodium\\crypto_box_seal_open' => 
    array (
      0 => 'string',
      'encrypted' => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_box_secretkey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_box_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'Sodium\\crypto_generichash' => 
    array (
      0 => 'string',
      'input' => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'Sodium\\crypto_generichash_final' => 
    array (
      0 => 'string',
      'state' => 'string',
      'length=' => 'int',
    ),
    'Sodium\\crypto_generichash_init' => 
    array (
      0 => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'Sodium\\crypto_generichash_update' => 
    array (
      0 => 'bool',
      '&hashState' => 'string',
      'append' => 'string',
    ),
    'Sodium\\crypto_kx' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
      'client_publickey' => 'string',
      'server_publickey' => 'string',
    ),
    'Sodium\\crypto_pwhash' => 
    array (
      0 => 'string',
      'out_len' => 'int',
      'passwd' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'Sodium\\crypto_pwhash_scryptsalsa208sha256' => 
    array (
      0 => 'string',
      'out_len' => 'int',
      'passwd' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'Sodium\\crypto_pwhash_scryptsalsa208sha256_str' => 
    array (
      0 => 'string',
      'passwd' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'Sodium\\crypto_pwhash_scryptsalsa208sha256_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'passwd' => 'string',
    ),
    'Sodium\\crypto_pwhash_str' => 
    array (
      0 => 'string',
      'passwd' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'Sodium\\crypto_pwhash_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'passwd' => 'string',
    ),
    'Sodium\\crypto_scalarmult' => 
    array (
      0 => 'string',
      'ecdhA' => 'string',
      'ecdhB' => 'string',
    ),
    'Sodium\\crypto_scalarmult_base' => 
    array (
      0 => 'string',
      'sk' => 'string',
    ),
    'Sodium\\crypto_secretbox' => 
    array (
      0 => 'string',
      'plaintext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_secretbox_open' => 
    array (
      0 => 'string',
      'ciphertext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_shorthash' => 
    array (
      0 => 'string',
      'message' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_sign' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secretkey' => 'string',
    ),
    'Sodium\\crypto_sign_detached' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secretkey' => 'string',
    ),
    'Sodium\\crypto_sign_ed25519_pk_to_curve25519' => 
    array (
      0 => 'string',
      'sign_pk' => 'string',
    ),
    'Sodium\\crypto_sign_ed25519_sk_to_curve25519' => 
    array (
      0 => 'string',
      'sign_sk' => 'string',
    ),
    'Sodium\\crypto_sign_keypair' => 
    array (
      0 => 'string',
    ),
    'Sodium\\crypto_sign_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
    ),
    'Sodium\\crypto_sign_open' => 
    array (
      0 => 'false|string',
      'signed_message' => 'string',
      'publickey' => 'string',
    ),
    'Sodium\\crypto_sign_publickey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_sign_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
    ),
    'Sodium\\crypto_sign_secretkey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'Sodium\\crypto_sign_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'Sodium\\crypto_sign_verify_detached' => 
    array (
      0 => 'bool',
      'signature' => 'string',
      'msg' => 'string',
      'publickey' => 'string',
    ),
    'Sodium\\crypto_stream' => 
    array (
      0 => 'string',
      'length' => 'int',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'Sodium\\crypto_stream_xor' => 
    array (
      0 => 'string',
      'plaintext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'Sodium\\hex2bin' => 
    array (
      0 => 'string',
      'hex' => 'string',
    ),
    'Sodium\\increment' => 
    array (
      0 => 'string',
      '&nonce' => 'string',
    ),
    'Sodium\\library_version_major' => 
    array (
      0 => 'int',
    ),
    'Sodium\\library_version_minor' => 
    array (
      0 => 'int',
    ),
    'Sodium\\memcmp' => 
    array (
      0 => 'int',
      'left' => 'string',
      'right' => 'string',
    ),
    'Sodium\\memzero' => 
    array (
      0 => 'void',
      '&target' => 'string',
    ),
    'Sodium\\randombytes_buf' => 
    array (
      0 => 'string',
      'length' => 'int',
    ),
    'Sodium\\randombytes_random16' => 
    array (
      0 => 'int|string',
    ),
    'Sodium\\randombytes_uniform' => 
    array (
      0 => 'int',
      'upperBoundNonInclusive' => 'int',
    ),
    'Sodium\\version_string' => 
    array (
      0 => 'string',
    ),
  ),
);