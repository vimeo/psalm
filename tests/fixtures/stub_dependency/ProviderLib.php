<?php

namespace StubDependency;

final class ProviderLib
{
    public function __call(string $name, array $args): mixed
    {
        throw new \BadMethodCallException($name);
    }
}

class ProviderLib2
{
    public function __call(string $name, array $args): mixed
    {
        throw new \BadMethodCallException($name);
    }
}
