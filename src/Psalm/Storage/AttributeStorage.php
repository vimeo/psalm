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

    /**
     * @param list<AttributeArg> $args
     */
    public function __construct(
        public string $fq_class_name,
        public array $args,
        public CodeLocation $location,
        public CodeLocation $name_location,
    ) {
    }
}
