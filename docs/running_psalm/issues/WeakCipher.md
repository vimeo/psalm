# WeakCipher

This is a **security issue**: it flags a potential vulnerability.

Emitted when `openssl_encrypt()` or `openssl_decrypt()` use a cipher with a key or a block too small (DES, triple DES, RC2, RC4, Blowfish, CAST5, IDEA) or the ECB mode.

```php
<?php

function encrypt(string $data, string $key): string {
    return (string) openssl_encrypt($data, 'des-ecb', $key); // WeakCipher
}

openssl_encrypt('data', 'aes-256-ecb', 'key'); // WeakCipher: ECB encrypts equal blocks the same way
openssl_encrypt('data', 'aes-256-gcm', 'key', 0, random_bytes(12), $tag); // no issue
```

## How to fix

Use an authenticated cipher such as `aes-256-gcm` or `chacha20-poly1305`, or `sodium_crypto_secretbox()`.

## Further resources

- [CWE-327](https://cwe.mitre.org/data/definitions/327.html)
