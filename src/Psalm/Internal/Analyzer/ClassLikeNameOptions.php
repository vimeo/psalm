<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

/**
 * @internal
 */
final class ClassLikeNameOptions
{
    public function __construct(
        public bool $inferred = false,
        public bool $allow_trait = false,
        public bool $allow_interface = true,
        public bool $allow_enum = true,
        public bool $from_docblock = false,
        public bool $from_attribute = false,
    ) {
    }
}
