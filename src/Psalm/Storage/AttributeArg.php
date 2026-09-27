<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 * @api
 */
final class AttributeArg
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param ?int $name interned argument name, for named arguments
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly ?int $name,
        public readonly Union|UnresolvedConstantComponent $type,
        public readonly CodeLocation $location,
    ) {
    }
}
