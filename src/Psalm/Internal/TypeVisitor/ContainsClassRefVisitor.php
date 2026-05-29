<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Detects whether a type tree contains a named class reference (TNamedObject and
 * its descendants like TGenericObject) or TIterable. Used to decide whether a
 * subtype check involving the type can be performed safely at scan time, before
 * Populator has resolved transitive inheritance chains.
 *
 * @internal
 */
final class ContainsClassRefVisitor extends TypeVisitor
{
    private bool $contains_class_ref = false;

    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject || $type instanceof TIterable) {
            $this->contains_class_ref = true;

            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_class_ref;
    }
}
