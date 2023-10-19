<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

/**
 * @psalm-immutable
 */
final class AttributeArg
{
    use ImmutableNonCloneableTrait;

    public function __construct(
        /**
         * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
         */
        public ?string $name,
        public Union|UnresolvedConstantComponent $type,
        /**
         * @psalm-suppress PossiblyUnusedProperty It's part of the public API for now
         */
        public CodeLocation $location,
    ) {
    }
}
