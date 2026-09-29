<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer;

use Psalm\Context;

/**
 * @internal
 * @psalm-immutable
 */
final class ClassLikeNameOptions
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public bool $inferred = false,
        public bool $allow_trait = false,
        public bool $allow_interface = true,
        public bool $allow_enum = true,
        public bool $from_docblock = false,
        public bool $from_attribute = false,
        /**
         * The branch the reference is analysed in, whose guards (see
         * Codebase::getGuardedPhpVersionId()) can make a newer native class available.
         */
        public ?Context $context = null,
    ) {
    }
}
