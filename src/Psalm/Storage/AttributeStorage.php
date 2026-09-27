<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

/**
 * @psalm-immutable
 * @api
 */
final class AttributeStorage
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param int $fq_class_name interned attribute class name
     * @param list<AttributeArg> $args
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly int $fq_class_name,
        public readonly array $args,
        public readonly CodeLocation $location,
        public readonly CodeLocation $name_location,
    ) {
    }
}
