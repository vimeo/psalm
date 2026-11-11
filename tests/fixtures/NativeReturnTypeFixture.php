<?php

declare(strict_types=1);

namespace Psalm\Tests\Fixtures;

final class NativeReturnTypeFixture
{
    public function nativeReturn(): int
    {
        return 1;
    }

    public function mixedReturn(): mixed
    {
        return null;
    }

    public function nullableReturn(): ?int
    {
        return null;
    }

    public function nullReturn(): null
    {
        return null;
    }

    /** @return positive-int */
    public function documentaryReturn()
    {
        return 1;
    }

    public function noReturn(): void
    {
    }
}
