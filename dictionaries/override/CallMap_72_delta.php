<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
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
    'reflectionclass::isiterable' => 
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
    'sodium_crypto_aead_aes256gcm_decrypt' => 
    array (
      0 => 'false|string',
      'ciphertext' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_aes256gcm_encrypt' => 
    array (
      0 => 'string',
      'message' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_aes256gcm_is_available' => 
    array (
      0 => 'bool',
    ),
    'sodium_crypto_aead_aes256gcm_keygen' => 
    array (
      0 => 'non-empty-string',
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
    'inflate_get_read_len' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'context' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'resource' => 'resource',
      ),
    ),
    'inflate_get_status' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'context' => 'resource',
      ),
      'new' => 
      array (
        0 => 'int',
        'resource' => 'resource',
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
    'opcache_compile_file' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'file' => 'string',
      ),
    ),
    'opcache_get_status' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'include_scripts=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|false',
        'fetch_scripts=' => 'bool',
      ),
    ),
    'opcache_invalidate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
        'force=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'script' => 'string',
        'force=' => 'bool',
      ),
    ),
    'opcache_is_script_cached' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'filename' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'script' => 'string',
      ),
    ),
    'openssl_pkcs7_read' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w_certificates' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'infilename' => 'string',
        '&w_certs' => 'array<array-key, mixed>',
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
    'spl_object_id' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'object' => 'object',
      ),
      'new' => 
      array (
        0 => 'int',
        'obj' => 'object',
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
        'database=' => 'string',
        'flags=' => 'int',
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
        'finish_callback=' => 'callable',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'data' => 'string',
        'task_worker_index=' => 'int',
        'finish_callback=' => 'callable',
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
  ),
  'removed' => 
  array (
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