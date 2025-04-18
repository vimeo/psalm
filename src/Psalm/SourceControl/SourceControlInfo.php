<?php

declare(strict_types=1);

namespace Psalm\SourceControl;

abstract class SourceControlInfo
{
    abstract public function toArray(): array;
}
