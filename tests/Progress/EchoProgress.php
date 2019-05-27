<?php
namespace Psalm\Tests\Progress;

use Psalm\Progress\DefaultProgress;

class EchoProgress extends DefaultProgress
{
    protected function write(string $message): void
    {
        echo $message;
    }
}
