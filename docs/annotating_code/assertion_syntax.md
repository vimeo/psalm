# Assertion syntax

Psalm’s [assertion annotation](supported_annotations.md/#psalm-assert-psalm-assert-if-true-and-psalm-assert-if-false) supports a number of different assertion types:

Most `is_xxx` PHP functions have companion assertions:
- `int`
- `float`
- `string`
- `bool`
- `scalar`
- `callable`
- `countable`
- `array`
- `iterable`
- `numeric`
- `resource`
- `object`
- `null`

So a custom version `is_int` could be annotated in Psalm as

```php
/** @psalm-assert-if-true int $x */
function custom_is_int($x) {
  return is_int($x);
}
```



