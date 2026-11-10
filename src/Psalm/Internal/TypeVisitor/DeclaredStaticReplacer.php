<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

use function array_shift;
use function is_string;
use function strtolower;

/**
 * Replaces `static` and `$this` as declared (with DeclaredStaticIntersectionMarker, also `static&I`) with the `static`
 * of a call. Late static types already resolved (those of the caller's scope) and the type put in place of `static`
 * are left as they are.
 *
 * @internal
 */
final class DeclaredStaticReplacer extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly string|TNamedObject $static_class_type,
        private readonly bool $final,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TNamedObject) {
            return null;
        }

        $value = strtolower($type->value);

        if ($value !== 'static' && $value !== '$this') {
            return null;
        }

        if ($type instanceof TGenericObject) {
            // `static<A, B>` keeps the type arguments it is declared with
            $type = $type->setValueIsStatic(
                is_string($this->static_class_type) ? $this->static_class_type : $this->static_class_type->value,
                !$this->final,
                true,
            );

            return null;
        }

        if ($type->extra_types) {
            // `static&I` as DeclaredStaticIntersectionMarker gives it: expanded as the parsed `I` marked static is
            $extra_types = $type->extra_types;
            $intersected = array_shift($extra_types);

            if (!$intersected instanceof TNamedObject) {
                return null;
            }

            $intersected = $intersected->setIntersectionTypes($extra_types)->setIsStatic(true, false);
            TypeExpander::expandAtomic(
                $this->codebase,
                $intersected,
                null,
                $this->static_class_type,
                null,
                true,
                false,
                $this->final,
            );
            $type = $intersected;

            return self::DONT_TRAVERSE_CHILDREN;
        }

        $type = is_string($this->static_class_type)
            ? new TNamedObject($this->static_class_type, !$this->final)
            : $this->static_class_type;
        $type = $type->setIsStatic(!$this->final, true);

        return self::DONT_TRAVERSE_CHILDREN;
    }
}
