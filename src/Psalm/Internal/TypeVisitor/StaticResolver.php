<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Marks the `static` at any depth of a type as the one of the code the type comes from: where a template is bound to
 * it, `static` stays that of the caller and isn't resolved to the class the called method is declared in.
 *
 * @internal
 */
final class StaticResolver extends MutableTypeVisitor
{
    /**
     * @psalm-impure
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
