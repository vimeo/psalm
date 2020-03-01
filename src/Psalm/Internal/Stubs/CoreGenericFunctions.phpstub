<?php
/**
 * @psalm-template T as array-key
 *
 * @param array<T, mixed> $arr
 * @param mixed           $search_value
 * @param bool            $strict
 *
 * @return list<T>
 * @psalm-pure
 */
function array_keys(array $arr, $search_value = null, bool $strict = false)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array ...$arr3
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_intersect(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array ...$arr3
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_intersect_key(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_intersect_assoc(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 *
 * @return array<TKey, TValue>|false
 * @psalm-ignore-falsable-return
 * @psalm-pure
 */
function array_combine(array $arr, array $arr2)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array ...$arr3
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_diff(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array ...$arr3
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_diff_key(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array ...$arr3
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_diff_assoc(array $arr, array $arr2, array ...$arr3)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 *
 * @return array<TValue, TKey>
 * @psalm-pure
 */
function array_flip(array $arr)
{
}

/**
 * @psalm-template TKey as array-key
 *
 * @param array<TKey, mixed> $arr
 *
 * @return TKey|null
 * @psalm-ignore-nullable-return
 * @psalm-pure
 */
function key($arr)
{
}

/**
 * @psalm-template TKey as array-key
 *
 * @param array<TKey, mixed> $arr
 *
 * @return TKey|null
 * @psalm-ignore-nullable-return
 * @psalm-pure
 */
function array_key_first($arr)
{
}

/**
 * @psalm-template TKey as array-key
 *
 * @param array<TKey, mixed> $arr
 *
 * @return TKey|null
 * @psalm-ignore-nullable-return
 * @psalm-pure
 */
function array_key_last($arr)
{
}

/**
 * @psalm-template T
 *
 * @param mixed           $needle
 * @param array<T, mixed> $haystack
 * @param bool            $strict
 *
 * @return T|false
 * @psalm-pure
 */
function array_search($needle, array $haystack, bool $strict = false)
{
}

/**
 * @psalm-template T
 *
 * @param T[] $arr
 * @param-out list<T> $arr
 */
function shuffle(array &$arr): bool
{
}

/**
 * @psalm-template T
 *
 * @param T[] $arr
 * @param-out list<T> $arr
 */
function sort(array &$arr, int $sort_flags = SORT_REGULAR): bool
{
}

/**
 * @psalm-template T
 *
 * @param T[] $arr
 * @param-out list<T> $arr
 */
function rsort(array &$arr, int $sort_flags = SORT_REGULAR): bool
{
}

/**
 * @psalm-template T
 *
 * @param T[] $arr
 * @param callable(T,T):int $callback
 * @param-out list<T> $arr
 */
function usort(array &$arr, callable $callback): bool
{
}

/**
 * @psalm-template TKey
 * @psalm-template T
 *
 * @param array<TKey,T> $arr
 * @param callable(T,T):int $callback
 * @param-out array<TKey,T> $arr
 */
function uasort(array &$arr, callable $callback): bool
{
}

/**
 * @psalm-template TKey
 * @psalm-template T
 *
 * @param array<TKey,T> $arr
 * @param callable(TKey,TKey):int $callback
 * @param-out array<TKey,T> $arr
 */
function uksort(array &$arr, callable $callback): bool
{
}

/**
 * @psalm-template T
 *
 * @param array<string, T> $arr
 *
 * @return array<string, T>
 * @psalm-pure
 */
function array_change_key_case(array $arr, int $case = CASE_LOWER)
{
}

/**
 * @psalm-template TKey as array-key
 *
 * @param TKey $key
 * @param array<TKey, mixed> $search
 *
 * @return bool
 * @psalm-pure
 */
function array_key_exists($key, array $search) : bool
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array<TKey, TValue> ...$arr2
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_merge_recursive(array $arr, array ...$arr2)
{
}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey> $keys
 * @param TValue $value
 *
 * @return array<TKey, TValue>
 * @psalm-pure
 */
function array_fill_keys(array $keys, $value): array
{
}

/**
 * @psalm-template TKey
 *
 * @param string $pattern
 * @param array<TKey,string> $input
 * @param 0|1 $flags 1=PREG_GREP_INVERT
 * @return array<TKey,string>
 */
function preg_grep($pattern, array $input, $flags = 0)
{
}

/**
 * @param resource $handle
 * @param-out closed-resource $handle
 */
function fclose(&$handle) : bool
{
}
