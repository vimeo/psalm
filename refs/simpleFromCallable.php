<?php

/**
 * @template T
 * @param T $param
 * @return T
 */
function debug($param)
{
    return $param;
}


$anonymous = Closure::fromCallable('debug');
$actualResult = $anonymous('x');

/** @psalm-trace $anonymous, $actualResult */

$boom = $actualResult/2;

/** @psalm-suppress ForbiddenCode */
var_dump($boom);

