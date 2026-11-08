<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Marks the late static types of a call's caller (`$this`, `static::class`) as bound: they reach the return type
 * through the templates, and the receiver of the call binds only the `static` of its own signature.
 *
 * @internal
 */
final class BoundStaticMarker extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TNamedObject && $type->is_static && !$type->is_static_resolved) {
            $type = $type->setIsStatic(true, true);
        }

        return null;
    }
}
