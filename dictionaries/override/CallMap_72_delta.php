<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'domnamednodemap::count' => 
    array (
      0 => 'int',
    ),
    'domnodelist::count' => 
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
      '&w_response_data=' => 'string',
      '&w_response_oid=' => 'string',
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
      '&w_response_data=' => 'string',
      '&w_response_oid=' => 'string',
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
    'mongodb\\bson\\document::fromphp' => 
    array (
      0 => 'MongoDB\\BSON\\Document',
      'value' => 'array<array-key, mixed>|object',
    ),
    'mongodb\\bson\\document::tophp' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'typeMap=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\bson\\document::unserialize' => 
    array (
      0 => 'void',
      'serialized' => 'string',
    ),
    'mongodb\\bson\\iterator::key' => 
    array (
      0 => 'int|string',
    ),
    'mongodb\\bson\\packedarray::tophp' => 
    array (
      0 => 'array<array-key, mixed>|object',
      'typeMap=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\bson\\packedarray::unserialize' => 
    array (
      0 => 'void',
      'serialized' => 'string',
    ),
    'mongodb\\driver\\clientencryption::encryptexpression' => 
    array (
      0 => 'object',
      'expr' => 'array<array-key, mixed>|object',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\clientencryption::rewrapmanydatakey' => 
    array (
      0 => 'object',
      'filter' => 'array<array-key, mixed>|object',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'mongodb\\driver\\manager::getencryptedfieldsmap' => 
    array (
      0 => 'array<array-key, mixed>|null|object',
    ),
    'oci_register_taf_callback' => 
    array (
      0 => 'bool',
      'connection' => 'resource',
      'callback=' => 'callable',
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
      '&w_certs' => 'array<array-key, mixed>',
    ),
    'reflectionclass::isiterable' => 
    array (
      0 => 'bool',
    ),
    'reflectionobject::isiterable' => 
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
      '&r_state' => 'string',
      'string=' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_push' => 
    array (
      0 => 'string',
      '&w_state' => 'string',
      'string=' => 'string',
      'long=' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_rekey' => 
    array (
      0 => 'void',
      '&w_state' => 'string',
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
      '&w_reference' => 'string',
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
    'ziparchive::count' => 
    array (
      0 => 'int',
    ),
    'ziparchive::setencryptionindex' => 
    array (
      0 => 'bool',
      'index' => 'int',
      'method' => 'int',
      'password=' => 'string',
    ),
    'ziparchive::setencryptionname' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'method' => 'int',
      'password=' => 'string',
    ),
  ),
  'changed' => 
  array (
    'arrayiterator::__construct' => 
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
    'mongodb\\bson\\binary::__construct' => 
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
    'mongodb\\bson\\int64::__construct' => 
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
    'mongodb\\bson\\javascript::__construct' => 
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
    'mongodb\\bson\\tophp' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|object',
        'bson' => 'string',
        'typemap=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|object',
        'bson' => 'string',
        'typemap=' => 'array<array-key, mixed>|null',
      ),
    ),
    'mongodb\\driver\\bulkwrite::delete' => 
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
    'mongodb\\driver\\bulkwrite::update' => 
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
    'mongodb\\driver\\command::__construct' => 
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
    'mongodb\\driver\\exception\\runtimeexception::haserrorlabel' => 
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
    'mongodb\\driver\\manager::__construct' => 
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
    'mongodb\\driver\\manager::executebulkwrite' => 
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
        'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
      ),
    ),
    'mongodb\\driver\\manager::executequery' => 
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
        'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
      ),
    ),
    'mongodb\\driver\\manager::executereadcommand' => 
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
    'mongodb\\driver\\manager::executewritecommand' => 
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
    'mongodb\\driver\\query::__construct' => 
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
    'mongodb\\driver\\server::executebulkwrite' => 
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
        'options=' => 'MongoDB\\Driver\\WriteConcern|array<array-key, mixed>|null',
      ),
    ),
    'mongodb\\driver\\server::executequery' => 
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
        'options=' => 'MongoDB\\Driver\\ReadPreference|array<array-key, mixed>|null',
      ),
    ),
    'mongodb\\driver\\server::executereadcommand' => 
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
    'mongodb\\driver\\server::executereadwritecommand' => 
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
    'mongodb\\driver\\server::executewritecommand' => 
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
    'mongodb\\driver\\session::advanceoperationtime' => 
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
    'recursivearrayiterator::__construct' => 
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
    'redis::auth' => 
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
    'redis::bitcount' => 
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
    'redis::bitop' => 
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
    'redis::bitpos' => 
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
    'redis::blpop' => 
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
    'redis::brpop' => 
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
    'redis::client' => 
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
    'redis::config' => 
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
    'redis::connect' => 
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
    'redis::decr' => 
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
    'redis::echo' => 
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
    'redis::evalsha' => 
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
    'redis::expire' => 
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
    'redis::expireat' => 
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
    'redis::flushall' => 
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
    'redis::flushdb' => 
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
    'redis::geoadd' => 
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
    'redis::georadius' => 
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
    'redis::georadiusbymember' => 
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
    'redis::getbit' => 
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
    'redis::hdel' => 
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
    'redis::hexists' => 
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
    'redis::hincrby' => 
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
    'redis::hincrbyfloat' => 
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
    'redis::hmget' => 
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
    'redis::hmset' => 
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
    'redis::hscan' => 
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
    'redis::hsetnx' => 
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
    'redis::incr' => 
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
    'redis::info' => 
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
    'redis::linsert' => 
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
    'redis::lpop' => 
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
    'redis::lpush' => 
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
    'redis::lrem' => 
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
    'redis::ltrim' => 
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
    'redis::migrate' => 
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
    'redis::move' => 
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
    'redis::mset' => 
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
    'redis::msetnx' => 
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
    'redis::multi' => 
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
    'redis::object' => 
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
    'redis::open' => 
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
    'redis::pconnect' => 
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
    'redis::pexpire' => 
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
    'redis::pexpireat' => 
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
    'redis::pfcount' => 
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
    'redis::pfmerge' => 
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
    'redis::ping' => 
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
    'redis::popen' => 
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
    'redis::psubscribe' => 
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
    'redis::pubsub' => 
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
    'redis::punsubscribe' => 
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
    'redis::rawcommand' => 
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
    'redis::rename' => 
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
    'redis::renamenx' => 
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
    'redis::restore' => 
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
    'redis::rpop' => 
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
    'redis::rpoplpush' => 
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
    'redis::rpush' => 
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
    'redis::sadd' => 
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
    'redis::saddarray' => 
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
    'redis::scan' => 
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
    'redis::script' => 
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
    'redis::select' => 
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
    'redis::set' => 
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
    'redis::setbit' => 
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
    'redis::setrange' => 
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
    'redis::sinterstore' => 
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
    'redis::slowlog' => 
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
    'redis::sortasc' => 
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
    'redis::sortascalpha' => 
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
    'redis::sortdesc' => 
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
    'redis::sortdescalpha' => 
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
    'redis::spop' => 
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
    'redis::srem' => 
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
    'redis::sscan' => 
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
    'redis::subscribe' => 
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
    'redis::swapdb' => 
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
    'redis::unsubscribe' => 
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
    'redis::wait' => 
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
    'redis::xack' => 
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
    'redis::xadd' => 
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
    'redis::xclaim' => 
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
    'redis::xdel' => 
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
    'redis::xgroup' => 
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
    'redis::xinfo' => 
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
    'redis::xpending' => 
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
    'redis::xrange' => 
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
    'redis::xread' => 
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
    'redis::xreadgroup' => 
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
    'redis::xrevrange' => 
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
    'redis::xtrim' => 
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
    'redis::zadd' => 
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
    'redis::zcount' => 
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
    'redis::zinter' => 
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
    'redis::zinterstore' => 
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
    'redis::zrange' => 
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
    'redis::zrangebylex' => 
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
    'redis::zremrangebyscore' => 
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
    'redis::zrevrangebylex' => 
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
    'redis::zrevrangebyscore' => 
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
    'redis::zscan' => 
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
    'redis::zunion' => 
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
    'redis::zunionstore' => 
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
    'redisarray::__construct' => 
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
    'redisarray::_rehash' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'callable=' => 'callable',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'fn=' => 'callable',
      ),
    ),
    'redisarray::del' => 
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
    'redisarray::flushall' => 
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
    'redisarray::flushdb' => 
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
    'redisarray::unlink' => 
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
    'rediscluster::__construct' => 
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
    'rediscluster::bitcount' => 
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
    'rediscluster::bitop' => 
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
    'rediscluster::bitpos' => 
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
    'rediscluster::brpoplpush' => 
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
    'rediscluster::client' => 
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
    'rediscluster::cluster' => 
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
    'rediscluster::command' => 
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
    'rediscluster::config' => 
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
    'rediscluster::decr' => 
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
    'rediscluster::echo' => 
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
    'rediscluster::exists' => 
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
    'rediscluster::expire' => 
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
    'rediscluster::expireat' => 
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
    'rediscluster::geoadd' => 
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
    'rediscluster::geodist' => 
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
    'rediscluster::georadius' => 
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
    'rediscluster::georadiusbymember' => 
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
    'rediscluster::getbit' => 
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
    'rediscluster::hmset' => 
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
    'rediscluster::hscan' => 
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
    'rediscluster::hstrlen' => 
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
    'rediscluster::incr' => 
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
    'rediscluster::info' => 
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
    'rediscluster::linsert' => 
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
    'rediscluster::lpop' => 
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
    'rediscluster::lpush' => 
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
    'rediscluster::lrem' => 
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
    'rediscluster::ltrim' => 
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
    'rediscluster::mset' => 
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
    'rediscluster::msetnx' => 
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
    'rediscluster::multi' => 
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
    'rediscluster::object' => 
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
    'rediscluster::pexpire' => 
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
    'rediscluster::pexpireat' => 
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
    'rediscluster::pfmerge' => 
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
    'rediscluster::ping' => 
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
    'rediscluster::psetex' => 
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
    'rediscluster::pubsub' => 
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
    'rediscluster::rawcommand' => 
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
    'rediscluster::rename' => 
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
    'rediscluster::restore' => 
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
    'rediscluster::role' => 
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
    'rediscluster::rpop' => 
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
    'rediscluster::rpush' => 
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
    'rediscluster::sadd' => 
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
    'rediscluster::saddarray' => 
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
    'rediscluster::scan' => 
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
    'rediscluster::script' => 
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
    'rediscluster::set' => 
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
    'rediscluster::setbit' => 
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
    'rediscluster::sinterstore' => 
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
    'rediscluster::slowlog' => 
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
    'rediscluster::smove' => 
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
    'rediscluster::spop' => 
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
    'rediscluster::srem' => 
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
    'rediscluster::sscan' => 
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
    'rediscluster::subscribe' => 
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
    'rediscluster::time' => 
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
    'rediscluster::xack' => 
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
    'rediscluster::xadd' => 
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
    'rediscluster::xclaim' => 
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
    'rediscluster::xdel' => 
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
    'rediscluster::xgroup' => 
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
    'rediscluster::xinfo' => 
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
    'rediscluster::xpending' => 
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
    'rediscluster::xrange' => 
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
    'rediscluster::xread' => 
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
    'rediscluster::xreadgroup' => 
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
    'rediscluster::xrevrange' => 
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
    'rediscluster::xtrim' => 
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
    'rediscluster::zadd' => 
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
    'rediscluster::zcount' => 
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
    'rediscluster::zinterstore' => 
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
    'rediscluster::zrange' => 
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
    'rediscluster::zrangebylex' => 
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
    'rediscluster::zrem' => 
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
    'rediscluster::zrevrange' => 
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
    'rediscluster::zrevrangebylex' => 
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
    'rediscluster::zrevrangebyscore' => 
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
    'rediscluster::zscan' => 
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
    'rediscluster::zunionstore' => 
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
    'reflectionclass::getmethods' => 
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
    'reflectionclass::getproperties' => 
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
    'reflectionobject::getmethods' => 
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
    'reflectionobject::getproperties' => 
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
    'sqlite3::openblob' => 
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
    'swoole\\connection\\iterator::next' => 
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
    'swoole\\http\\response::header' => 
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
    'swoole\\server::task' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'worker_id=' => 'int',
        'finish_callback=' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'task_worker_index=' => 'int',
        'finish_callback=' => 'callable|null',
      ),
    ),
    'swoole\\server::taskwait' => 
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
    'swoole\\table::next' => 
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
    'redis::evaluate' => 
    array (
      0 => 'mixed',
      'script' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'redis::evaluatesha' => 
    array (
      0 => 'mixed',
      'script_sha' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'redis::getkeys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'redis::getmultiple' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'redis::lget' => 
    array (
      0 => 'string',
      'key' => 'string',
      'index' => 'int',
    ),
    'redis::lgetrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::listtrim' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'start' => 'int',
      'stop' => 'int',
    ),
    'redis::lremove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      'count' => 'int',
    ),
    'redis::lsize' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::renamekey' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'newkey' => 'string',
    ),
    'redis::scontains' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::sendecho' => 
    array (
      0 => 'string',
      'msg' => 'string',
    ),
    'redis::settimeout' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'timeout' => 'int',
    ),
    'redis::sgetmembers' => 
    array (
      0 => 'mixed',
      'key' => 'string',
    ),
    'redis::sremove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::ssize' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::substr' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::zdelete' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::zdeleterangebyrank' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'min' => 'int',
      'max' => 'int',
    ),
    'redis::zdeleterangebyscore' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'min' => 'float',
      'max' => 'float',
    ),
    'redis::zremove' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::zremoverangebyscore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'float|string',
      'max' => 'float|string',
    ),
    'redis::zreverserange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'scores=' => 'bool',
    ),
    'redis::zsize' => 
    array (
      0 => 'mixed',
      'key' => 'string',
    ),
    'redisarray::delete' => 
    array (
      0 => 'bool',
      'keys' => 'string',
    ),
    'sodium\\add' => 
    array (
      0 => 'void',
      '&left' => 'string',
      'right' => 'string',
    ),
    'sodium\\bin2hex' => 
    array (
      0 => 'string',
      'binary' => 'string',
    ),
    'sodium\\compare' => 
    array (
      0 => 'int',
      'left' => 'string',
      'right' => 'string',
    ),
    'sodium\\crypto_aead_aes256gcm_decrypt' => 
    array (
      0 => 'false|string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'sodium\\crypto_aead_aes256gcm_encrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'sodium\\crypto_aead_aes256gcm_is_available' => 
    array (
      0 => 'bool',
    ),
    'sodium\\crypto_aead_chacha20poly1305_decrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'sodium\\crypto_aead_chacha20poly1305_encrypt' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'key' => 'string',
      'ad=' => 'string',
    ),
    'sodium\\crypto_auth' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_auth_verify' => 
    array (
      0 => 'bool',
      'mac' => 'string',
      'msg' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_box' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_box_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium\\crypto_box_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
    ),
    'sodium\\crypto_box_open' => 
    array (
      0 => 'string',
      'msg' => 'string',
      'nonce' => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_box_publickey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_box_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
    ),
    'sodium\\crypto_box_seal' => 
    array (
      0 => 'string',
      'message' => 'string',
      'publickey' => 'string',
    ),
    'sodium\\crypto_box_seal_open' => 
    array (
      0 => 'string',
      'encrypted' => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_box_secretkey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_box_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'sodium\\crypto_generichash' => 
    array (
      0 => 'string',
      'input' => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'sodium\\crypto_generichash_final' => 
    array (
      0 => 'string',
      'state' => 'string',
      'length=' => 'int',
    ),
    'sodium\\crypto_generichash_init' => 
    array (
      0 => 'string',
      'key=' => 'string',
      'length=' => 'int',
    ),
    'sodium\\crypto_generichash_update' => 
    array (
      0 => 'bool',
      '&hashState' => 'string',
      'append' => 'string',
    ),
    'sodium\\crypto_kx' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
      'client_publickey' => 'string',
      'server_publickey' => 'string',
    ),
    'sodium\\crypto_pwhash' => 
    array (
      0 => 'string',
      'out_len' => 'int',
      'passwd' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium\\crypto_pwhash_scryptsalsa208sha256' => 
    array (
      0 => 'string',
      'out_len' => 'int',
      'passwd' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium\\crypto_pwhash_scryptsalsa208sha256_str' => 
    array (
      0 => 'string',
      'passwd' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium\\crypto_pwhash_scryptsalsa208sha256_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'passwd' => 'string',
    ),
    'sodium\\crypto_pwhash_str' => 
    array (
      0 => 'string',
      'passwd' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
    ),
    'sodium\\crypto_pwhash_str_verify' => 
    array (
      0 => 'bool',
      'hash' => 'string',
      'passwd' => 'string',
    ),
    'sodium\\crypto_scalarmult' => 
    array (
      0 => 'string',
      'ecdhA' => 'string',
      'ecdhB' => 'string',
    ),
    'sodium\\crypto_scalarmult_base' => 
    array (
      0 => 'string',
      'sk' => 'string',
    ),
    'sodium\\crypto_secretbox' => 
    array (
      0 => 'string',
      'plaintext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_secretbox_open' => 
    array (
      0 => 'string',
      'ciphertext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_shorthash' => 
    array (
      0 => 'string',
      'message' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_sign' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secretkey' => 'string',
    ),
    'sodium\\crypto_sign_detached' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secretkey' => 'string',
    ),
    'sodium\\crypto_sign_ed25519_pk_to_curve25519' => 
    array (
      0 => 'string',
      'sign_pk' => 'string',
    ),
    'sodium\\crypto_sign_ed25519_sk_to_curve25519' => 
    array (
      0 => 'string',
      'sign_sk' => 'string',
    ),
    'sodium\\crypto_sign_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium\\crypto_sign_keypair_from_secretkey_and_publickey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
      'publickey' => 'string',
    ),
    'sodium\\crypto_sign_open' => 
    array (
      0 => 'false|string',
      'signed_message' => 'string',
      'publickey' => 'string',
    ),
    'sodium\\crypto_sign_publickey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_sign_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secretkey' => 'string',
    ),
    'sodium\\crypto_sign_secretkey' => 
    array (
      0 => 'string',
      'keypair' => 'string',
    ),
    'sodium\\crypto_sign_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'sodium\\crypto_sign_verify_detached' => 
    array (
      0 => 'bool',
      'signature' => 'string',
      'msg' => 'string',
      'publickey' => 'string',
    ),
    'sodium\\crypto_stream' => 
    array (
      0 => 'string',
      'length' => 'int',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium\\crypto_stream_xor' => 
    array (
      0 => 'string',
      'plaintext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium\\hex2bin' => 
    array (
      0 => 'string',
      'hex' => 'string',
    ),
    'sodium\\increment' => 
    array (
      0 => 'string',
      '&nonce' => 'string',
    ),
    'sodium\\library_version_major' => 
    array (
      0 => 'int',
    ),
    'sodium\\library_version_minor' => 
    array (
      0 => 'int',
    ),
    'sodium\\memcmp' => 
    array (
      0 => 'int',
      'left' => 'string',
      'right' => 'string',
    ),
    'sodium\\memzero' => 
    array (
      0 => 'void',
      '&target' => 'string',
    ),
    'sodium\\randombytes_buf' => 
    array (
      0 => 'string',
      'length' => 'int',
    ),
    'sodium\\randombytes_random16' => 
    array (
      0 => 'int|string',
    ),
    'sodium\\randombytes_uniform' => 
    array (
      0 => 'int',
      'upperBoundNonInclusive' => 'int',
    ),
    'sodium\\version_string' => 
    array (
      0 => 'string',
    ),
  ),
);