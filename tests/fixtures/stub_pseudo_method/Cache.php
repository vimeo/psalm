<?php

namespace StubPseudoMethod;

abstract class Store
{
    /** @param array<mixed> $arguments */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return null;
    }
}

/**
 * @method static mixed remember(\Closure $closure)
 */
class Cache extends Store
{
}
