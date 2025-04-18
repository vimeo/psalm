<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
abstract class Scalar extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return true;
    }
}
