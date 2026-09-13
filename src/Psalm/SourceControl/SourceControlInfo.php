<?php

declare(strict_types=1);

namespace Psalm\SourceControl;

/**
 * @psalm-immutable
 * @api
 */
abstract class SourceControlInfo
{
    /** @psalm-mutation-free */
    abstract public function toArray(): array;
}
