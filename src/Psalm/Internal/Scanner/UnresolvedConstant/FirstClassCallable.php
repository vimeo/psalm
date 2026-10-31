<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * `name(...)` (when $fqcln is null) or `Foo::name(...)`
 *
 * @psalm-immutable
 * @internal
 */
final class FirstClassCallable extends UnresolvedConstantComponent
{
    /**
     * @param ?string $global_fallback_name global function used when an unqualified $name
     *                                      does not exist in the current namespace
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $fqcln = null,
        public readonly ?string $global_fallback_name = null,
    ) {
    }
}
