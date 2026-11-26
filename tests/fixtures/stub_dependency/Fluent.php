<?php

namespace StubDependency;

class FluentDependency {}

/** @template T */
class Fluent
{
    public function alter()
    {
        return $this;
    }
}
