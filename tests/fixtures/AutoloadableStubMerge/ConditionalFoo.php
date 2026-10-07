<?php

declare(strict_types=1);

namespace AutoloadableStubMerge;

class ConditionalFoo
{
    public function stubbed(): int
    {
        return 1;
    }

    public function notStubbed(): string
    {
        return 'a';
    }
}
