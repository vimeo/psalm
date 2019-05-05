<?php
/**
 * @psalm-template T as array-key
 *
 * @param array<T, mixed> $arr
 * @param mixed           $search_value
 * @param bool            $strict
 * @return array<int, T>
 */
function array_keys(array $arr, $search_value = null, bool $strict = false) {}

/**
 * @psalm-template T
 *
 * @param array<mixed, T> $arr
 * @return array<int, T>
 */
function array_values(array $arr) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param int $sort_flags
 * @return array<TKey, TValue>
 */
function array_unique(array $arr, int $sort_flags = 0) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_intersect(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_intersect_key(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 * @return array<TKey, TValue>|false
 */
function array_combine(array $arr, array $arr2) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_diff(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_diff_key(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @return array<TValue, TKey>
 */
function array_flip(array $arr) {}

/**
 * @psalm-template TKey as array-key
 *
 * @param array<TKey, mixed> $arr
 * @return TKey|null
 * @psalm-ignore-nullable-return
 */
function key($arr) {}

/**
 * @psalm-template TValue
 *
 * @param TValue $value
 * @return array<int, TValue>
 */
function array_fill( int $start_index, int $num, $value) : array {}

/**
 * @psalm-template T
 *
 * @param mixed           $needle
 * @param array<T, mixed> $haystack
 * @param bool            $strict
 * @return T|false
 */
function array_search($needle, array $haystack, bool $strict = false) {}

/**
 * @template T
 * @param array<mixed,T> $arr
 * @param callable(T,T):int $callback
 * @param-out array<int,T> $arr
 */
function usort(array &$arr, callable $callback): bool {}

/**
 * @psalm-template T
 *
 * @param array<string, T> $arr
 * @return array<string, T>
 */
function array_change_key_case(array $arr, int $case = CASE_LOWER) {}

/**
 * @psalm-template T
 *
 * @param array<array-key, T> $arr
 *
 * @return array<int, array<array-key, T>>
 */
function array_chunk(array $arr, int $size, bool $preserve_keys = false) {}

/**
 * @psalm-template TKey as array-key
 * @param TKey $key
 * @param array<TKey, mixed> $search
 * @return bool
 */
function array_key_exists($key, array $search) : bool { }

/**
 * @psalm-template TKey as array-key
 * @psalm-template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array<TKey, TValue> ...$arr2
 * @return array<TKey, TValue>
 */
function array_merge_recursive(array $arr, array ...$arr2) {}
