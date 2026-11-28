# LegacySyntax

Emitted when a docblock uses a syntax whose meaning changes in Psalm 8. `--alter --issues=LegacySyntax` writes the replacement.

```php
<?php

/**
 * @param list<empty> $ids // LegacySyntax: array{}
 * @return array<string, empty> // LegacySyntax: array{}
 */
function none(array $ids): array
{
    return $ids;
}
```

`empty` in an `array`, `associative-array` or `list` type means `never`, so only the empty array is of that type. `empty` changes meaning in Psalm 8.

```php
<?php

/**
 * @param array<empty, empty> $a // LegacySyntax: array{}
 * @param array<string, list<empty>> $b // LegacySyntax: array<string, array{}>
 * @param array{} $c // no issue
 * @param mixed $d
 * @psalm-assert list<empty> $d // no issue: assertions are left out
 */
function check(array $a, array $b, array $c, $d): void {}
```

## How to fix

Write `array{}`.
