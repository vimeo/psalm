<?php declare(strict_types = 1);

class X {
    /**
     * @template T
     * @param T $param
     * @return T
     */
    public function debug($param)
    {
        return $param;
    }
}


$firstClass = (new X)->debug(...);
$actualResult = $firstClass('x');

/** @psalm-trace $firstClass, $actualResult */

$boom = $actualResult/2;

/** @psalm-suppress ForbiddenCode */
var_dump($boom);


