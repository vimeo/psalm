<?php

declare(strict_types=1);

namespace Psalm\Tests\Progress;

use Psalm\Progress\DefaultProgress;

class EchoProgress extends DefaultProgress
{
    public function write(string $message): void
    {
        echo $message;
    }
}
