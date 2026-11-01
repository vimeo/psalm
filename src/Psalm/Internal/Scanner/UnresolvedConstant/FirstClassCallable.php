<?php

declare(strict_types=1);

namespace Psalm\Internal\Scanner\UnresolvedConstant;

use Psalm\Internal\Scanner\UnresolvedConstantComponent;

/**
 * `name(...)` (when $fqcln is null) or `Foo::name(...)` in a constant expression
 *
 * @internal
 * @psalm-immutable
 */
final class FirstClassCallable extends UnresolvedConstantComponent
{
    /**
     * @param non-empty-string $name a function name or a method name
     * @param ?string $fqcln the class of a static method
     * @param ?non-empty-string $global_fallback_name the global function an unqualified, not imported function
     *                                                name falls back to when the namespaced function does not exist
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $fqcln = null,
        public readonly ?string $global_fallback_name = null,
    ) {
    }
}
