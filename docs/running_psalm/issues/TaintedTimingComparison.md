# TaintedTimingComparison

This is a **security issue**, reported by [security analysis](https://psalm.dev/docs/security_analysis/): it flags a potential vulnerability.

Emitted when user input is compared with a secret by `==`, `===`, `!=`, `!==`, `strcmp()`, `strcasecmp()`,
`strncmp()`, `strncasecmp()` or `substr_compare()`. These stop at the first byte that differs, so the time a
comparison takes tells an attacker how much of the secret their input matches.

```php
<?php // --taint-analysis

/** @psalm-taint-source secret_key */
function newToken(): string {
    return bin2hex(random_bytes(16));
}

$_SESSION['csrf'] = newToken();

function check(string $given): bool {
    $expected = (string) $_SESSION['csrf'];

    $expected === $given;                     // TaintedTimingComparison
    strcmp($expected, $given) === 0;          // TaintedTimingComparison
    hash_equals($expected, $given);           // no issue
    return hash('sha256', $given) === hash('sha256', $expected); // no issue: neither hash is a secret or input
}

check((string) $_POST['csrf']);
```

The secrets are values with the `secret_key` or `system_secret` taint:

```php
<?php // --taint-analysis

final class Signer {
    /** @psalm-taint-source secret_key */
    private const SALT = 'MIASfjaspijf0';

    public static function sign(string $data): string {
        return hash('sha256', $data . self::SALT);
    }
}

/** @psalm-taint-source system_secret */
function apiKey(): string { return ''; }

$given = (string) $_GET['sign'];

Signer::sign('id=1') === $given;                       // TaintedTimingComparison
hash_hmac('sha256', 'id=1', apiKey()) === $given;      // no issue: hashes keep secret_key only
apiKey() === $given;                                   // TaintedTimingComparison
```

`md5()`, `sha1()`, `hash()`, `hash_hmac()`, `hash_hkdf()`, `hash_pbkdf2()` and `crypt()` keep a `secret_key`, and
`bin2hex()` and `base64_encode()` keep both. A value read from a configuration takes the taint from a conditional
source:

```php
<?php // --taint-analysis

final class Config {
    /** @psalm-taint-source ($key is 'salt' ? 'secret_key' : null) */
    public static function get(string $key): string { return ''; }
}

$given = (string) $_GET['hash'];

hash('sha256', 'id=1' . Config::get('salt')) === $given; // TaintedTimingComparison
hash('sha256', 'id=1' . Config::get('name')) === $given; // no issue
```

A parameter with `@psalm-taint-sink secret_key` compares what it is given in a time that depends on it, such as a
database lookup by token, and reports this issue for a `secret_key` it is given.

## Risk

An attacker who can measure the response time guesses the secret byte by byte: a signature, a CSRF token or an API
key, and with it, the requests it protects.

## Mitigations

- Compare with `hash_equals($secret, $input)`: it takes the same time whatever the strings are.
- Compare hashes of both values, so that neither side is the secret or the input.

## Further resources

- [CWE-208](https://cwe.mitre.org/data/definitions/208.html)
- [`hash_equals()`](https://www.php.net/manual/en/function.hash-equals.php)
