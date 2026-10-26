<?php

namespace StubDependency;

final class Lib
{
    public function __call(string $name, array $args): mixed
    {
        throw new \BadMethodCallException($name);
    }
}

class Lib2
{
    public function __call(string $name, array $args): mixed
    {
        throw new \BadMethodCallException($name);
    }
}
