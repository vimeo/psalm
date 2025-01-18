<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'DOMNodeList::count' => 
    array (
      0 => 'int',
    ),
    'ReflectionClass::isIterable' => 
    array (
      0 => 'bool',
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
    'ftp_append' => 
    array (
      0 => 'bool',
      'ftp' => 'resource',
      'remote_filename' => 'string',
      'local_filename' => 'string',
      'mode=' => 'int',
    ),
    'hash_hmac_algos' => 
    array (
      0 => 'list<string>',
    ),
    'imagebmp' => 
    array (
      0 => 'bool',
      'image' => 'resource',
      'file=' => 'null|resource|string',
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
      'image' => 'resource',
      'points' => 'array<array-key, mixed>',
      'num_points' => 'int',
      'color' => 'int',
    ),
    'imageresolution' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'image' => 'resource',
      'resolution_x=' => 'int',
      'resolution_y=' => 'int',
    ),
    'imagesetclip' => 
    array (
      0 => 'bool',
      'image' => 'resource',
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
      'codepoint' => 'int',
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
      'string' => 'string',
      'encoding=' => 'string',
    ),
    'mb_scrub' => 
    array (
      0 => 'string',
      'string' => 'string',
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
      '&rw_string1' => 'string',
      'string2' => 'string',
    ),
    'sodium_base642bin' => 
    array (
      0 => 'string',
      'string' => 'string',
      'id' => 'int',
      'ignore=' => 'string',
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
      'string1' => 'string',
      'string2' => 'string',
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
      'ciphertext' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_encrypt' => 
    array (
      0 => 'string',
      'message' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => 
    array (
      0 => 'false|string',
      'ciphertext' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_chacha20poly1305_ietf_encrypt' => 
    array (
      0 => 'string',
      'message' => 'string',
      'additional_data' => 'string',
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
      'ciphertext' => 'string',
      'additional_data' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => 
    array (
      0 => 'string',
      'message' => 'string',
      'additional_data' => 'string',
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
      'message' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_auth_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_auth_verify' => 
    array (
      0 => 'bool',
      'mac' => 'string',
      'message' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_box' => 
    array (
      0 => 'string',
      'message' => 'string',
      'nonce' => 'string',
      'key_pair' => 'string',
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
      'ciphertext' => 'string',
      'nonce' => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_box_publickey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_box_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
    ),
    'sodium_crypto_box_seal' => 
    array (
      0 => 'string',
      'message' => 'string',
      'public_key' => 'string',
    ),
    'sodium_crypto_box_seal_open' => 
    array (
      0 => 'false|string',
      'ciphertext' => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_box_secretkey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_box_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'sodium_crypto_generichash' => 
    array (
      0 => 'string',
      'message' => 'string',
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
      '&rw_state' => 'string',
      'message' => 'string',
    ),
    'sodium_crypto_kdf_derive_from_key' => 
    array (
      0 => 'string',
      'subkey_length' => 'int',
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
      'client_key_pair' => 'string',
      'server_key' => 'string',
    ),
    'sodium_crypto_kx_keypair' => 
    array (
      0 => 'string',
    ),
    'sodium_crypto_kx_publickey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_kx_secretkey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_kx_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'sodium_crypto_kx_server_session_keys' => 
    array (
      0 => 'array<int, string>',
      'server_key_pair' => 'string',
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
      'algo=' => 'int',
    ),
    'sodium_crypto_pwhash_scryptsalsa208sha256' => 
    array (
      0 => 'string',
      'length' => 'int',
      'password' => 'string',
      'salt' => 'string',
      'opslimit' => 'int',
      'memlimit' => 'int',
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
      'n' => 'string',
      'p' => 'string',
    ),
    'sodium_crypto_scalarmult_base' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
    ),
    'sodium_crypto_secretbox' => 
    array (
      0 => 'string',
      'message' => 'string',
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
      'ciphertext' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => 
    array (
      0 => 'string',
      'header' => 'string',
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
      'ciphertext' => 'string',
      'additional_data=' => 'string',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_push' => 
    array (
      0 => 'string',
      '&w_state' => 'string',
      'message' => 'string',
      'additional_data=' => 'string',
      'tag=' => 'int',
    ),
    'sodium_crypto_secretstream_xchacha20poly1305_rekey' => 
    array (
      0 => 'void',
      '&w_state' => 'string',
    ),
    'sodium_crypto_shorthash' => 
    array (
      0 => 'string',
      'message' => 'string',
      'key' => 'string',
    ),
    'sodium_crypto_shorthash_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
    'sodium_crypto_sign' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secret_key' => 'string',
    ),
    'sodium_crypto_sign_detached' => 
    array (
      0 => 'string',
      'message' => 'string',
      'secret_key' => 'string',
    ),
    'sodium_crypto_sign_ed25519_pk_to_curve25519' => 
    array (
      0 => 'string',
      'public_key' => 'string',
    ),
    'sodium_crypto_sign_ed25519_sk_to_curve25519' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
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
      'signed_message' => 'string',
      'public_key' => 'string',
    ),
    'sodium_crypto_sign_publickey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_sign_publickey_from_secretkey' => 
    array (
      0 => 'string',
      'secret_key' => 'string',
    ),
    'sodium_crypto_sign_secretkey' => 
    array (
      0 => 'string',
      'key_pair' => 'string',
    ),
    'sodium_crypto_sign_seed_keypair' => 
    array (
      0 => 'string',
      'seed' => 'string',
    ),
    'sodium_crypto_sign_verify_detached' => 
    array (
      0 => 'bool',
      'signature' => 'string',
      'message' => 'string',
      'public_key' => 'string',
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
      'message' => 'string',
      'nonce' => 'string',
      'key' => 'string',
    ),
    'sodium_hex2bin' => 
    array (
      0 => 'string',
      'string' => 'string',
      'ignore=' => 'string',
    ),
    'sodium_increment' => 
    array (
      0 => 'void',
      '&rw_string' => 'string',
    ),
    'sodium_memcmp' => 
    array (
      0 => 'int',
      'string1' => 'string',
      'string2' => 'string',
    ),
    'sodium_memzero' => 
    array (
      0 => 'void',
      '&w_string' => 'string',
    ),
    'sodium_pad' => 
    array (
      0 => 'string',
      'string' => 'string',
      'block_size' => 'int',
    ),
    'sodium_unpad' => 
    array (
      0 => 'string',
      'string' => 'string',
      'block_size' => 'int',
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
  ),
  'changed' => 
  array (
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
        'database=' => 'string',
        'flags=' => 'int',
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
        'binary=' => 'bool',
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
        'flags=' => 'int',
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
        'hcontext' => 'resource',
        'filename' => 'string',
        'scontext=' => 'resource',
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
        'stream' => 'resource',
        'length=' => 'int',
      ),
    ),
    'json_decode' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'associative=' => 'bool',
        'depth=' => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'json' => 'string',
        'associative=' => 'bool|null',
        'depth=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'mb_check_encoding' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'value=' => 'string',
        'encoding=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'value=' => 'array<array-key, mixed>|string',
        'encoding=' => 'string',
      ),
    ),
    'preg_quote' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delimiter=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
        'delimiter=' => 'null|string',
      ),
    ),
  ),
  'removed' => 
  array (
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