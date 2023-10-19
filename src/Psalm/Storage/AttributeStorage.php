<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

/**
 * @psalm-immutable
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
        /**
         * @psalm-suppress PossiblyUnusedProperty part of public API
         */
        public CodeLocation $location,
        /**
         * @psalm-suppress PossiblyUnusedProperty part of public API
         */
        public CodeLocation $name_location,
    ) {
    }
}
