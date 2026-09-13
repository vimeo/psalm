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
     * @psalm-suppress ImpureMethodCall the bounds accumulate while the variable
     *      flows through the function body, and the variable always displays
     *      their current state
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->bounds) {
            if ($this->bounds->lower_bounds) {
                return TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                    $this->bounds->lower_bounds,
                    null,
                )->getId($exact);
            }

            if ($this->bounds->upper_bounds) {
                return $this->bounds->upper_bounds[0]->type->getId($exact);
            }
        }

        return $this->name;
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
