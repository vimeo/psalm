<?php

namespace StubPseudoMethod;

abstract class Facade
{
    /** @param array<mixed> $arguments */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return null;
    }

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed
    {
        return null;
    }
}
