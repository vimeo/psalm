<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Override;
use Psalm\Progress\DefaultProgress;

final class EchoProgress extends DefaultProgress
{
    #[Override]
    public function write(string $message): void
    {
        echo $message;
    }
}
