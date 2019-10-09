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
 * @template T
 *
 * @param array<mixed,T> $arr
 * @param callable(T,T):int $callback
 * @param-out array<int,T> $arr
 * @psalm-pure
 */
function usort(array &$arr, callable $callback): bool
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
 * @psalm-template T
 *
 * @param array<array-key, T> $arr
 *
 * @return array<int, array<array-key, T>>
 * @psalm-pure
 */
function array_chunk(array $arr, int $size, bool $preserve_keys = false)
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
