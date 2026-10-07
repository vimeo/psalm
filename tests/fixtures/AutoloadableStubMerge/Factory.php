<?php

declare(strict_types=1);

namespace AutoloadableStubMerge;

class Factory
{
    public static function make(): object
    {
        return new Foo();
    }

    public static function makeConditional(): object
    {
        return new ConditionalFoo();
    }
}
