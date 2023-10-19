<?php

namespace Psalm\Tests\Progress;

use Psalm\Progress\DefaultProgress;

class EchoProgress extends DefaultProgress
{
    public function write(string $message): void
    {
        echo $message;
    }
}
