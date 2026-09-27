<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_slice;

/**
 * Replaces type variables, however deeply nested, with the type they were
 * inferred to be at their construction site: the bounds the constructor
 * arguments implied, or the declared constraint when nothing bound them.
 *
 * Used where a comparison needs a concrete structural shape and recording a
 * bound for later reconciliation would not be meaningful — bounds recorded by
 * later (possibly conflicting) uses already reconcile on their own.
 *
 * @internal
 */
final class TypeVariableResolver extends MutableTypeVisitor
{
    public bool $resolved_a_variable = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly ?Codebase $codebase,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof Union) {
            return null;
        }

        $resolved_types = [];
        $changed = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $resolved = null;

            if ($atomic_type instanceof TTypeVariable && $atomic_type->bounds) {
                $initial_lower_bounds = array_slice(
                    $atomic_type->bounds->lower_bounds,
                    0,
                    $atomic_type->bounds->initial_lower_bound_count,
                );

                if ($initial_lower_bounds) {
                    $resolved = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $initial_lower_bounds,
                        $this->codebase,
                    );
                } elseif ($atomic_type->bounds->upper_bounds) {
                    $resolved = $atomic_type->bounds->upper_bounds[0]->type;
                }
            }

            if ($resolved) {
                $changed = true;

                foreach ($resolved->getAtomicTypes() as $resolved_atomic_type) {
                    $resolved_types[] = $resolved_atomic_type;
                }
            } else {
                $resolved_types[] = $atomic_type;
            }
        }

        if ($changed) {
            $this->resolved_a_variable = true;
            $type = new Union($resolved_types);
        }

        return null;
    }
}
