<?php
/**
 * @template T
 *
 * @param array<T, mixed> $arr
 * @return array<int, T>
 */
function array_keys(array $arr) {}

/**
 * @template T
 *
 * @param array<mixed, T> $arr
 * @return array<int, T>
 */
function array_values(array $arr) {}

/**
 * @template T
 *
 * @param array<mixed, T> $arr
 * @return array<int, T>
 */
function array_unique(array $arr) {}

/**
 * @template T
 *
 * @param array<mixed, T> $arr
 * @return array<int, T>
 */
function array_slice(array $arr) {}

/**
 * @template TKey
 * @template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_intersect(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @template TKey
 * @template TValue
 *
 * @param array<mixed, TKey> $arr
 * @param array<mixed, TValue> $arr2
 * @return array<TKey, TValue>
 */
function array_combine(array $arr, array $arr2) {}

/**
 * @template TKey
 * @template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_diff(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @template TKey
 * @template TValue
 *
 * @param array<TKey, TValue> $arr
 * @param array $arr2
 * @param array|null $arr3
 * @param array|null $arr4
 * @return array<TKey, TValue>
 */
function array_diff_key(array $arr, array $arr2, array $arr3 = null, array $arr4 = null) {}

/**
 * @template TValue
 *
 * @param array<mixed, TValue> $arr
 * @return TValue
 */
function array_shift(array $arr) {}

/**
 * @template TValue
 *
 * @param array<mixed, TValue> $arr
 * @return TValue
 */
function array_pop(array $arr) {}
