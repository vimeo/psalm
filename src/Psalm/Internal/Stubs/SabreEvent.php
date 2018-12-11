<?php

namespace Sabre\Event;

/**
 * @template TReturn
 * @param callable():\Generator<mixed, mixed, mixed, TReturn> $gen
 * @return Promise<TReturn>
 */
function coroutine(callable $gen) : Promise {}

/**
 * @template TReturn
 */
class Promise {
    /**
     * @return TReturn
     */
    function wait() {}
}
