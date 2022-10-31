<?php

namespace Psalm\Progress;

final class VoidProgress extends Progress
{
    public function write(string $message): void
    {
    }
}
