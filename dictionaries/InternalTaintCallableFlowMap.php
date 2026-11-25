<?php

declare(strict_types=1);

/**
 * The builtin functions and methods only declared by the call map that call the callables given to a parameter with
 * data given to others, or return what the callables return, as `@psalm-flow ($subject) -> $callback()` and
 * `@psalm-flow ($callback()) -> return` say for the builtins of the stubs (see TaintFlowAnnotation). The parameters
 * are named as in the callmap of the newest PHP version; a flow naming a parameter a variant doesn't have is skipped
 * for it. An array given to a callable whole (`arg`) gives it both its keys and its values.
 *
 * @var array<lowercase-string, non-empty-list<non-empty-string>>
 */
return [
    // standard: arrays, which give their values, keys and the extra argument to the callback
    'array_walk' => ['($array, $arg) -> $callback()'],
    'array_walk_recursive' => ['($array, $arg) -> $callback()'],
    // the callback comparing the values or keys is the last of the variadic arguments
    'array_diff_uassoc' => ['($array, $rest) -> $rest()'],
    'array_diff_ukey' => ['($array, $rest) -> $rest()'],
    'array_intersect_uassoc' => ['($array, $rest) -> $rest()'],
    'array_intersect_ukey' => ['($array, $rest) -> $rest()'],
    'array_udiff' => ['($array, $rest) -> $rest()'],
    'array_udiff_assoc' => ['($array, $rest) -> $rest()'],
    'array_uintersect' => ['($array, $rest) -> $rest()'],
    'array_uintersect_assoc' => ['($array, $rest) -> $rest()'],
    'array_udiff_uassoc' => [
        '($array, $arr2, $rest) -> $rest()',
        '($array, $arr2) -> $value_compare_func()',
        '($array, $arr2) -> $key_compare_func()',
    ],
    'array_uintersect_uassoc' => [
        '($array, $arr2, $rest) -> $rest()',
        '($array, $arr2) -> $value_compare_func()',
        '($array, $arr2) -> $key_compare_func()',
    ],
    // mbstring
    'mb_ereg_replace_callback' => ['($string) -> $callback()', '($callback()) -> return'],
    // spl: the callback filters the elements of the iterator
    'callbackfilteriterator::__construct' => ['($iterator) -> $callback()'],
    'recursivecallbackfilteriterator::__construct' => ['($iterator) -> $callback()'],
];
