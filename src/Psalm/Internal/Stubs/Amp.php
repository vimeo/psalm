<?php

namespace Amp;

/**
 * @template TReturn
 * @param callable():\Generator<mixed, mixed, mixed, TReturn> $gen
 * @return callable():Promise<TReturn>
 */
function coroutine(callable $gen) : callable {}

/**
 * @template TReturn
 * @param callable():(\Generator<mixed, mixed, mixed, TReturn>|TReturn) $gen
 * @return Promise<TReturn>
 */
function call(callable $gen) : Promise {}


/**
 * @template TReturn
 */
interface Promise {
    /**
     * @param callable(\Throwable|null $exception, TReturn|null $result):void
     * @return void
     */
    function onResolve(callable $onResolved);
}

/**
 * @template TReturn
 *
 * @template-implements Promise<TReturn>
 */
class Success implements Promise {
    /**
     * @param TReturn|null $value
     */
    public function __construct($value = null) {}
    /**
     * @param callable(\Throwable|null $exception, TReturn|null $result):void
     * @return void
     */
    function onResolve(callable $onResolved) {}
}
