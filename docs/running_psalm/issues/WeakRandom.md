# WeakRandom

This is a **security issue**: it flags a potential vulnerability.

Emitted when the Mersenne Twister behind `mt_rand()`, `rand()`, `shuffle()`, `array_rand()`... is seeded, by `mt_srand()` or `srand()` given a seed, or `Random\Engine\Mt19937` constructed with one: it then generates the same values for the same seed.

```php
<?php

function token(int $seed): int {
    mt_srand($seed); // WeakRandom
    return mt_rand();
}

mt_srand(); // no issue: seeded randomly
```

## Why this is bad

Whoever knows or guesses the seed (a timestamp, a user id, a value from the request) can compute every value generated after it. Even unseeded, the Mersenne Twister isn't meant for secrets: its state can be recovered from its output.

## How to fix

Use `random_int()` or `random_bytes()` for tokens, passwords and anything that must stay secret.

## Further resources

- [CWE-335](https://cwe.mitre.org/data/definitions/335.html)
- [CWE-330](https://cwe.mitre.org/data/definitions/330.html)
