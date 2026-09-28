<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeVariableBounds;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes a type variable: a placeholder (e.g. `` `_0 ``) minted for a class template
 * at a construction site, where PHP has no type arguments that could pin it. Constraints
 * accumulate against it in the surrounding function-like's TypeVariableTracker while the
 * variable flows through the body, and are reconciled with each other when the
 * function-like has been analyzed.
 *
 * @psalm-immutable
 */
final class TTypeVariable extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public readonly string $name,
        public readonly ?TypeVariableBounds $bounds = null,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return $this->name;
    }

    /**
     * Renders the variable through the bounds recorded against it so far: the
     * union of its lower bounds, or the constraint it was minted with when
     * nothing has bound it from below.
     *
     * The inexact (display) form renders as the bare bound, so ordinary output
     * matches Hack's eager `new Foo<_>(...)` inference and the old pre-variable
     * pinning. The exact form additionally names the variable (`` `_0:bound ``),
     * mirroring how {@see TTemplateParam::getId()} exposes `T:Class as ...`: a
     * variable that survives into an exact rendering — a `@psalm-trace`, an
     * issue message, a type-identity key — is then visible as an unreconciled
     * variable rather than silently masquerading as its resolved bound, which is
     * what let one leak unnoticed into array access (`$var[0]` on a variable
     * that prints like an array but was never resolved to one).
     *
     * @psalm-suppress ImpureMethodCall the bounds accumulate while the variable
     *      flows through the function body, and the variable always displays
     *      their current state
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        $bound = null;

        if ($this->bounds) {
            if ($this->bounds->lower_bounds) {
                $bound = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                    $this->bounds->lower_bounds,
                    null,
                )->getId($exact);
            } elseif ($this->bounds->upper_bounds) {
                $bound = $this->bounds->upper_bounds[0]->type->getId($exact);
            }
        }

        if ($bound === null) {
            return $this->name;
        }

        return $exact ? $this->name . ':' . $bound : $bound;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
