# WeakHash

This is a **security issue**: it flags a potential vulnerability.

Emitted when data is hashed with an algorithm that collisions break: `md5()`, `sha1()`, or `md2`, `md4`, `md5` and `sha1` given to `hash()`, `hash_file()`, `hash_init()` or `openssl_digest()`.

```php
<?php

function fingerprint(string $document): string {
    return md5($document); // WeakHash
}

hash('sha1', 'data'); // WeakHash
hash('sha256', 'data'); // no issue
hash('xxh3', 'data'); // no issue: a fast hash for cache keys and checksums, not meant to resist attacks
hash_hmac('md5', 'data', 'key'); // no issue: HMAC doesn't rely on collision resistance
```

## Why this is bad

An attacker can make two inputs with the same MD5 or SHA-1 hash, so the hash can't prove that data wasn't changed, and it is fast enough to guess passwords from.

## How to fix

Use a SHA-2 or SHA-3 algorithm (`hash('sha256', ...)`), and `password_hash()` for passwords. Where only speed matters (cache keys, checksums), use `hash('xxh3', ...)`, which is faster than MD5.

## Further resources

- [CWE-328](https://cwe.mitre.org/data/definitions/328.html)
