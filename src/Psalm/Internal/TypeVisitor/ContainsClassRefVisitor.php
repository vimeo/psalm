<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Detects whether a type tree contains a named class reference (TNamedObject and
 * its descendants like TGenericObject), TIterable, or a type derived from a class
 * constant or a class's properties (TClassConstant, TKeyOf, TValueOf, TPropertiesOf).
 * Used to decide whether a subtype check involving the type can be performed safely
 * at scan time, before Populator has resolved transitive inheritance chains and
 * class constants have been evaluated.
 *
 * @internal
 */
final class ContainsClassRefVisitor extends TypeVisitor
{
    private bool $contains_class_ref = false;
    private bool $contains_unresolved_derived_type = false;

    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TNamedObject || $type instanceof TIterable) {
            $this->contains_class_ref = true;

            return self::STOP_TRAVERSAL;
        }

        if ($type instanceof TClassConstant
            || $type instanceof TKeyOf
            || $type instanceof TValueOf
            || $type instanceof TPropertiesOf
        ) {
            $this->contains_unresolved_derived_type = true;

            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_class_ref;
    }

    /**
     * True for a type derived from a class constant or class properties
     * (TClassConstant, TKeyOf, TValueOf, TPropertiesOf), which scan time can't
     * resolve: class constants aren't evaluated yet.
     */
    public function matchesUnresolvedDerivedType(): bool
    {
        return $this->contains_unresolved_derived_type;
    }
}
