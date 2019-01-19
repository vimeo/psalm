<?php

namespace Amp;

/**
 * @template TReturn
 * @param callable():\Generator<mixed, mixed, mixed, TReturn> $gen
 * @return Promise<TReturn>
 */
function coroutine(callable $gen) : Promise {}

/**
 * @template TReturn
 * @param callable():(\Generator<mixed, mixed, mixed, TReturn>|null) $gen
 * @return Promise<TReturn>
 */
function call(callable $gen) : Promise {}

/**
 * @template TReturn
 */
class Promise {
    /**
     * @return TReturn
     */
    function wait() {}
}
