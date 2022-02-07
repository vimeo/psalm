<?php // phpcs:ignoreFile

/**
 * This contains the information needed to convert the function signatures for php 7.2 to php 7.1 (and vice versa)
 *
 * This file has three sections.
 * The 'added' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php 7.1
 * The 'removed' section contains the signatures that were removed in php 7.2.
 * The 'changed' section contains functions for which the signature has changed for php 7.2.
 *     Each function in the 'changed' section has an 'old' and a 'new' section,
 *     representing the function as it was in PHP 7.1 and in PHP 7.2, respectively
 *
 * @see CallMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
  'added' => [
    'ReflectionClass::isIterable' => ['bool'],
    'ZipArchive::count' => ['int'],
    'ZipArchive::setEncryptionIndex' => ['bool', 'index'=>'int', 'method'=>'string', 'password='=>'string'],
    'ZipArchive::setEncryptionName' => ['bool', 'name'=>'string', 'method'=>'int', 'password='=>'string'],
    'ftp_append' => ['bool', 'ftp'=>'resource', 'remote_filename'=>'string', 'local_filename'=>'string', 'mode='=>'int'],
    'hash_hmac_algos' => ['list<string>'],
    'imagebmp' => ['bool', 'image'=>'resource', 'file='=>'resource|string|null', 'compressed='=>'int'],
    'imagecreatefrombmp' => ['resource|false', 'filename'=>'string'],
    'imageopenpolygon' => ['bool', 'image'=>'resource', 'points'=>'array', 'num_points'=>'int', 'color'=>'int'],
    'imageresolution' => ['array|bool', 'image'=>'resource', 'resolution_x='=>'int', 'resolution_y='=>'int'],
    'imagesetclip' => ['bool', 'image'=>'resource', 'x1'=>'int', 'y1'=>'int', 'x2'=>'int', 'y2'=>'int'],
    'ldap_exop' => ['mixed', 'ldap'=>'resource', 'reqoid'=>'string', 'reqdata='=>'string', 'serverctrls='=>'array|null', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    'ldap_exop_passwd' => ['bool|string', 'ldap'=>'resource', 'user='=>'string', 'old_password='=>'string', 'new_password='=>'string'],
    'ldap_exop_refresh' => ['int|false', 'ldap'=>'resource', 'dn'=>'string', 'ttl'=>'int'],
    'ldap_exop_whoami' => ['string|false', 'ldap'=>'resource'],
    'ldap_parse_exop' => ['bool', 'ldap'=>'resource', 'result'=>'resource', '&w_response_data='=>'string', '&w_response_oid='=>'string'],
    'mb_chr' => ['string|false', 'codepoint'=>'int', 'encoding='=>'string'],
    'mb_convert_encoding\'1' => ['array', 'string'=>'array', 'to_encoding'=>'string', 'from_encoding='=>'mixed'],
    'mb_ord' => ['int|false', 'string'=>'string', 'encoding='=>'string'],
    'mb_scrub' => ['string', 'string'=>'string', 'encoding='=>'string'],
    'oci_register_taf_callback' => ['bool', 'connection'=>'resource', 'callback='=>'callable'],
    'oci_unregister_taf_callback' => ['bool', 'connection'=>'resource'],
    'sapi_windows_vt100_support' => ['bool', 'stream'=>'resource', 'enable='=>'bool'],
    'socket_addrinfo_bind' => ['?resource', 'addrinfo'=>'resource'],
    'socket_addrinfo_connect' => ['resource', 'addrinfo'=>'resource'],
    'socket_addrinfo_explain' => ['array', 'addrinfo'=>'resource'],
    'socket_addrinfo_lookup' => ['resource[]', 'node'=>'string', 'service='=>'mixed', 'hints='=>'array'],
    'sodium_add' => ['void', '&rw_string1'=>'string', 'string2'=>'string'],
    'sodium_base642bin' => ['string', 'string'=>'string', 'id'=>'int', 'ignore='=>'string'],
    'sodium_bin2base64' => ['string', 'string'=>'string', 'id'=>'int'],
    'sodium_bin2hex' => ['string', 'string'=>'string'],
    'sodium_compare' => ['int', 'string1'=>'string', 'string2'=>'string'],
    'sodium_crypto_aead_aes256gcm_decrypt' => ['string|false', 'ciphertext'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_aes256gcm_encrypt' => ['string', 'message'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_aes256gcm_is_available' => ['bool'],
    'sodium_crypto_aead_aes256gcm_keygen' => ['string'],
    'sodium_crypto_aead_chacha20poly1305_decrypt' => ['string|false', 'ciphertext'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_chacha20poly1305_encrypt' => ['string|false', 'message'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_chacha20poly1305_ietf_decrypt' => ['string|false', 'ciphertext'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_chacha20poly1305_ietf_encrypt' => ['string|false', 'message'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_chacha20poly1305_ietf_keygen' => ['string'],
    'sodium_crypto_aead_chacha20poly1305_keygen' => ['string'],
    'sodium_crypto_aead_xchacha20poly1305_ietf_decrypt' => ['string|false', 'ciphertext'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_xchacha20poly1305_ietf_encrypt' => ['string|false', 'message'=>'string', 'additional_data'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_aead_xchacha20poly1305_ietf_keygen' => ['string'],
    'sodium_crypto_auth' => ['string', 'message'=>'string', 'key'=>'string'],
    'sodium_crypto_auth_keygen' => ['string'],
    'sodium_crypto_auth_verify' => ['bool', 'mac'=>'string', 'message'=>'string', 'key'=>'string'],
    'sodium_crypto_box' => ['string', 'message'=>'string', 'nonce'=>'string', 'key_pair'=>'string'],
    'sodium_crypto_box_keypair' => ['string'],
    'sodium_crypto_box_keypair_from_secretkey_and_publickey' => ['string', 'secret_key'=>'string', 'public_key'=>'string'],
    'sodium_crypto_box_open' => ['string|false', 'ciphertext'=>'string', 'nonce'=>'string', 'key_pair'=>'string'],
    'sodium_crypto_box_publickey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_box_publickey_from_secretkey' => ['string', 'secret_key'=>'string'],
    'sodium_crypto_box_seal' => ['string', 'message'=>'string', 'public_key'=>'string'],
    'sodium_crypto_box_seal_open' => ['string|false', 'ciphertext'=>'string', 'key_pair'=>'string'],
    'sodium_crypto_box_secretkey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_box_seed_keypair' => ['string', 'seed'=>'string'],
    'sodium_crypto_generichash' => ['string', 'message'=>'string', 'key='=>'?string', 'length='=>'?int'],
    'sodium_crypto_generichash_final' => ['string', '&state'=>'string', 'length='=>'?int'],
    'sodium_crypto_generichash_init' => ['string', 'key='=>'?string', 'length='=>'?int'],
    'sodium_crypto_generichash_keygen' => ['string'],
    'sodium_crypto_generichash_update' => ['bool', '&rw_state'=>'string', 'string'=>'string'],
    'sodium_crypto_kdf_derive_from_key' => ['string', 'subkey_length'=>'int', 'subkey_id'=>'int', 'context'=>'string', 'key'=>'string'],
    'sodium_crypto_kdf_keygen' => ['string'],
    'sodium_crypto_kx_client_session_keys' => ['array<int,string>', 'client_keypair'=>'string', 'server_key'=>'string'],
    'sodium_crypto_kx_keypair' => ['string'],
    'sodium_crypto_kx_publickey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_kx_secretkey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_kx_seed_keypair' => ['string', 'seed'=>'string'],
    'sodium_crypto_kx_server_session_keys' => ['array<int,string>', 'server_key_pair'=>'string', 'client_key'=>'string'],
    'sodium_crypto_pwhash' => ['string', 'length'=>'int', 'password'=>'string', 'salt'=>'string', 'opslimit'=>'int', 'memlimit'=>'int', 'algo='=>'int'],
    'sodium_crypto_pwhash_scryptsalsa208sha256' => ['string', 'length'=>'int', 'password'=>'string', 'salt'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'sodium_crypto_pwhash_scryptsalsa208sha256_str' => ['string', 'password'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'sodium_crypto_pwhash_scryptsalsa208sha256_str_verify' => ['bool', 'hash'=>'string', 'password'=>'string'],
    'sodium_crypto_pwhash_str' => ['string', 'password'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'sodium_crypto_pwhash_str_needs_rehash' => ['bool', 'password'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'sodium_crypto_pwhash_str_verify' => ['bool', 'hash'=>'string', 'password'=>'string'],
    'sodium_crypto_scalarmult' => ['string', 'n'=>'string', 'p'=>'string'],
    'sodium_crypto_scalarmult_base' => ['string', 'secret_key'=>'string'],
    'sodium_crypto_secretbox' => ['string', 'message'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_secretbox_keygen' => ['string'],
    'sodium_crypto_secretbox_open' => ['string|false', 'ciphertext'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_secretstream_xchacha20poly1305_init_pull' => ['string', 'header'=>'string', 'key'=>'string'],
    'sodium_crypto_secretstream_xchacha20poly1305_init_push' => ['array', 'key'=>'string'],
    'sodium_crypto_secretstream_xchacha20poly1305_keygen' => ['string'],
    'sodium_crypto_secretstream_xchacha20poly1305_pull' => ['array', '&r_state'=>'string', 'ciphertext'=>'string', 'additional_data='=>'string'],
    'sodium_crypto_secretstream_xchacha20poly1305_push' => ['string', '&w_state'=>'string', 'message'=>'string', 'additional_data='=>'string', 'tag='=>'int'],
    'sodium_crypto_secretstream_xchacha20poly1305_rekey' => ['void', 'state'=>'string'],
    'sodium_crypto_shorthash' => ['string', 'message'=>'string', 'key'=>'string'],
    'sodium_crypto_shorthash_keygen' => ['string'],
    'sodium_crypto_sign' => ['string', 'message'=>'string', 'secret_key'=>'string'],
    'sodium_crypto_sign_detached' => ['string', 'message'=>'string', 'secret_key'=>'string'],
    'sodium_crypto_sign_ed25519_pk_to_curve25519' => ['string', 'public_key'=>'string'],
    'sodium_crypto_sign_ed25519_sk_to_curve25519' => ['string', 'secret_key'=>'string'],
    'sodium_crypto_sign_keypair' => ['string'],
    'sodium_crypto_sign_keypair_from_secretkey_and_publickey' => ['string', 'secret_key'=>'string', 'public_key'=>'string'],
    'sodium_crypto_sign_open' => ['string|false', 'signed_message'=>'string', 'public_key'=>'string'],
    'sodium_crypto_sign_publickey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_sign_publickey_from_secretkey' => ['string', 'secret_key'=>'string'],
    'sodium_crypto_sign_secretkey' => ['string', 'key_pair'=>'string'],
    'sodium_crypto_sign_seed_keypair' => ['string', 'seed'=>'string'],
    'sodium_crypto_sign_verify_detached' => ['bool', 'signature'=>'string', 'message'=>'string', 'public_key'=>'string'],
    'sodium_crypto_stream' => ['string', 'length'=>'int', 'nonce'=>'string', 'key'=>'string'],
    'sodium_crypto_stream_keygen' => ['string'],
    'sodium_crypto_stream_xor' => ['string', 'message'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'sodium_hex2bin' => ['string', 'string'=>'string', 'ignore='=>'string'],
    'sodium_increment' => ['void', '&rw_string'=>'string'],
    'sodium_memcmp' => ['int', 'string1'=>'string', 'string2'=>'string'],
    'sodium_memzero' => ['void', '&w_string'=>'string'],
    'sodium_pad' => ['string', 'string'=>'string', 'block_size'=>'int'],
    'sodium_unpad' => ['string', 'string'=>'string', 'block_size'=>'int'],
    'stream_isatty' => ['bool', 'stream'=>'resource'],
  ],
  'changed' => [
    'SQLite3::openBlob' => [
      'old' => ['resource|false', 'table'=>'string', 'column'=>'string', 'rowid'=>'int', 'dbname='=>'string'],
      'new' => ['resource|false', 'table'=>'string', 'column'=>'string', 'rowid'=>'int', 'database='=>'string', 'flags='=>'int'],
    ],
    'hash_copy' => [
      'old' => ['resource', 'context'=>'resource'],
      'new' => ['HashContext', 'context'=>'HashContext'],
    ],
    'hash_final' => [
      'old' => ['string', 'context'=>'resource', 'raw_output='=>'bool'],
      'new' => ['string', 'context'=>'HashContext', 'binary='=>'bool'],
    ],
    'hash_init' => [
      'old' => ['resource', 'algo'=>'string', 'options='=>'int', 'key='=>'string'],
      'new' => ['HashContext|false', 'algo'=>'string', 'flags='=>'int', 'key='=>'string'],
    ],
    'hash_update' => [
      'old' => ['bool', 'context'=>'resource', 'data'=>'string'],
      'new' => ['bool', 'context'=>'HashContext', 'data'=>'string'],
    ],
    'hash_update_file' => [
      'old' => ['bool', 'hcontext'=>'resource', 'filename'=>'string', 'scontext='=>'resource'],
      'new' => ['bool', 'context'=>'HashContext', 'filename'=>'string', 'stream_context='=>'resource'],
    ],
    'hash_update_stream' => [
      'old' => ['int', 'context'=>'resource', 'handle'=>'resource', 'length='=>'int'],
      'new' => ['int', 'context'=>'HashContext', 'stream'=>'resource', 'length='=>'int'],
    ],
    'mb_check_encoding' => [
      'old' => ['bool', 'value='=>'string', 'encoding='=>'string'],
      'new' => ['bool', 'value='=>'array|string', 'encoding='=>'string'],
    ],
  ],
  'removed' => [
    'Sodium\add' => ['void', '&left'=>'string', 'right'=>'string'],
    'Sodium\bin2hex' => ['string', 'binary'=>'string'],
    'Sodium\compare' => ['int', 'left'=>'string', 'right'=>'string'],
    'Sodium\crypto_aead_aes256gcm_decrypt' => ['string|false', 'msg'=>'string', 'nonce'=>'string', 'key'=>'string', 'ad='=>'string'],
    'Sodium\crypto_aead_aes256gcm_encrypt' => ['string', 'msg'=>'string', 'nonce'=>'string', 'key'=>'string', 'ad='=>'string'],
    'Sodium\crypto_aead_aes256gcm_is_available' => ['bool'],
    'Sodium\crypto_aead_chacha20poly1305_decrypt' => ['string', 'msg'=>'string', 'nonce'=>'string', 'key'=>'string', 'ad='=>'string'],
    'Sodium\crypto_aead_chacha20poly1305_encrypt' => ['string', 'msg'=>'string', 'nonce'=>'string', 'key'=>'string', 'ad='=>'string'],
    'Sodium\crypto_auth' => ['string', 'msg'=>'string', 'key'=>'string'],
    'Sodium\crypto_auth_verify' => ['bool', 'mac'=>'string', 'msg'=>'string', 'key'=>'string'],
    'Sodium\crypto_box' => ['string', 'msg'=>'string', 'nonce'=>'string', 'keypair'=>'string'],
    'Sodium\crypto_box_keypair' => ['string'],
    'Sodium\crypto_box_keypair_from_secretkey_and_publickey' => ['string', 'secretkey'=>'string', 'publickey'=>'string'],
    'Sodium\crypto_box_open' => ['string', 'msg'=>'string', 'nonce'=>'string', 'keypair'=>'string'],
    'Sodium\crypto_box_publickey' => ['string', 'keypair'=>'string'],
    'Sodium\crypto_box_publickey_from_secretkey' => ['string', 'secretkey'=>'string'],
    'Sodium\crypto_box_seal' => ['string', 'message'=>'string', 'publickey'=>'string'],
    'Sodium\crypto_box_seal_open' => ['string', 'encrypted'=>'string', 'keypair'=>'string'],
    'Sodium\crypto_box_secretkey' => ['string', 'keypair'=>'string'],
    'Sodium\crypto_box_seed_keypair' => ['string', 'seed'=>'string'],
    'Sodium\crypto_generichash' => ['string', 'input'=>'string', 'key='=>'string', 'length='=>'int'],
    'Sodium\crypto_generichash_final' => ['string', 'state'=>'string', 'length='=>'int'],
    'Sodium\crypto_generichash_init' => ['string', 'key='=>'string', 'length='=>'int'],
    'Sodium\crypto_generichash_update' => ['bool', '&hashState'=>'string', 'append'=>'string'],
    'Sodium\crypto_kx' => ['string', 'secretkey'=>'string', 'publickey'=>'string', 'client_publickey'=>'string', 'server_publickey'=>'string'],
    'Sodium\crypto_pwhash' => ['string', 'out_len'=>'int', 'passwd'=>'string', 'salt'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'Sodium\crypto_pwhash_scryptsalsa208sha256' => ['string', 'out_len'=>'int', 'passwd'=>'string', 'salt'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'Sodium\crypto_pwhash_scryptsalsa208sha256_str' => ['string', 'passwd'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'Sodium\crypto_pwhash_scryptsalsa208sha256_str_verify' => ['bool', 'hash'=>'string', 'passwd'=>'string'],
    'Sodium\crypto_pwhash_str' => ['string', 'passwd'=>'string', 'opslimit'=>'int', 'memlimit'=>'int'],
    'Sodium\crypto_pwhash_str_verify' => ['bool', 'hash'=>'string', 'passwd'=>'string'],
    'Sodium\crypto_scalarmult' => ['string', 'ecdhA'=>'string', 'ecdhB'=>'string'],
    'Sodium\crypto_scalarmult_base' => ['string', 'sk'=>'string'],
    'Sodium\crypto_secretbox' => ['string', 'plaintext'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'Sodium\crypto_secretbox_open' => ['string', 'ciphertext'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'Sodium\crypto_shorthash' => ['string', 'message'=>'string', 'key'=>'string'],
    'Sodium\crypto_sign' => ['string', 'message'=>'string', 'secretkey'=>'string'],
    'Sodium\crypto_sign_detached' => ['string', 'message'=>'string', 'secretkey'=>'string'],
    'Sodium\crypto_sign_ed25519_pk_to_curve25519' => ['string', 'sign_pk'=>'string'],
    'Sodium\crypto_sign_ed25519_sk_to_curve25519' => ['string', 'sign_sk'=>'string'],
    'Sodium\crypto_sign_keypair' => ['string'],
    'Sodium\crypto_sign_keypair_from_secretkey_and_publickey' => ['string', 'secretkey'=>'string', 'publickey'=>'string'],
    'Sodium\crypto_sign_open' => ['string|false', 'signed_message'=>'string', 'publickey'=>'string'],
    'Sodium\crypto_sign_publickey' => ['string', 'keypair'=>'string'],
    'Sodium\crypto_sign_publickey_from_secretkey' => ['string', 'secretkey'=>'string'],
    'Sodium\crypto_sign_secretkey' => ['string', 'keypair'=>'string'],
    'Sodium\crypto_sign_seed_keypair' => ['string', 'seed'=>'string'],
    'Sodium\crypto_sign_verify_detached' => ['bool', 'signature'=>'string', 'msg'=>'string', 'publickey'=>'string'],
    'Sodium\crypto_stream' => ['string', 'length'=>'int', 'nonce'=>'string', 'key'=>'string'],
    'Sodium\crypto_stream_xor' => ['string', 'plaintext'=>'string', 'nonce'=>'string', 'key'=>'string'],
    'Sodium\hex2bin' => ['string', 'hex'=>'string'],
    'Sodium\increment' => ['string', '&nonce'=>'string'],
    'Sodium\library_version_major' => ['int'],
    'Sodium\library_version_minor' => ['int'],
    'Sodium\memcmp' => ['int', 'left'=>'string', 'right'=>'string'],
    'Sodium\memzero' => ['void', '&target'=>'string'],
    'Sodium\randombytes_buf' => ['string', 'length'=>'int'],
    'Sodium\randombytes_random16' => ['int|string'],
    'Sodium\randombytes_uniform' => ['int', 'upperBoundNonInclusive'=>'int'],
    'Sodium\version_string' => ['string'],
  ],
];
