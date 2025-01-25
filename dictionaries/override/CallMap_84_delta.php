<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'pcntl_wifcontinued' => 
    array (
      0 => 'bool',
      'status' => 'int',
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
    'sodium_crypto_aead_aes256gcm_keygen' => 
    array (
      0 => 'non-empty-string',
    ),
  ),
  'changed' => 
  array (
    'exit' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'status' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'status=' => 'int|string',
      ),
    ),
    'imagickkernel::frommatrix' => 
    array (
      'old' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'ImagickKernel',
        'matrix' => 'list<list<float>>',
        'origin=' => 'array<array-key, mixed>',
      ),
    ),
    'openssl_csr_sign' => 
    array (
      'old' => 
      array (
        0 => 'OpenSSLCertificate|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'ca_certificate' => 'OpenSSLCertificate|null|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'days' => 'int',
        'options=' => 'array<array-key, mixed>|null',
        'serial=' => 'int',
      ),
      'new' => 
      array (
        0 => 'OpenSSLCertificate|false',
        'csr' => 'OpenSSLCertificateSigningRequest|string',
        'ca_certificate' => 'OpenSSLCertificate|null|string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'days' => 'int',
        'options=' => 'array<array-key, mixed>|null',
        'serial=' => 'int',
        'serial_hex=' => 'null|string',
      ),
    ),
    'pg_select' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|false|string',
        'connection' => 'PgSql\\Connection',
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
        'conditions=' => 'array<array-key, mixed>',
        'flags=' => 'int',
        'mode=' => 'int',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);