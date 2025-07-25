<?php

declare(strict_types=1);

namespace Psalm\Progress;

use Override;

final class VoidProgress extends Progress
{
    #[Override]
    public function write(string $message): void
    {
    }
}
