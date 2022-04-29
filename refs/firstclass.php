<?php declare(strict_types = 1);

/**
 * @template T
 * @param T $param
 * @return T
 */
function debug($param)
{
    return $param;
}

$firstClass = debug(...);
$actualResult = $firstClass('x');

/** @psalm-trace $firstClass, $actualResult */

$boom = $actualResult/2;

/** @psalm-suppress ForbiddenCode */
var_dump($boom);


