<?php

namespace StubDependency;

class TrivialConstructor
{
    public int $n = 0;

    public function __construct(int $n)
    {
        $this->n = $n;
    }
}
