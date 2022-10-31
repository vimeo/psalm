<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
interface DependentType
{
    public function getVarId(): string;

    /**
     * This returns a replacement type for when the dependent data is invalidated
     */
    public function getReplacement(): Atomic;
}
