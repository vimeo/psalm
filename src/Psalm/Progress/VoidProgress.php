<?php

declare(strict_types=1);

namespace Psalm\Progress;

final class VoidProgress extends Progress
{
    public function write(string $message): void
    {
    }
}
