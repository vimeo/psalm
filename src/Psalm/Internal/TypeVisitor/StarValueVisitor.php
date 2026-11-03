<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\StarProjection;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Reads each `*` that stands as a value in a type built from type arguments outside the template replacers (a
 * return type provider wrapping the receiver's `*` argument in `list<…>`) like the projection of a member's type
 * (see StarProjectionVisitor): where the type is read, it is what is known of it, stays `*` as the argument of a
 * generic object (`Box<*>`) and makes an invariant argument mentioning it `*` (see
 * StarProjection::projectInvariantArgument()); where a callable in it takes it, it is nothing, also inside a type
 * argument (`Closure(Box<*>): void` takes a `Box<never>`).
 *
 * @internal
 */
final class StarValueVisitor extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly ?Codebase $codebase,
        private readonly bool $contravariant,
    ) {
    }

    public static function read(Union $type, ?Codebase $codebase): Union
    {
        (new self($codebase, false))->traverse($type);

        return $type;
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TStarProjection) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TClosure || $type instanceof TCallable) {
            $params = $type->params;

            if ($params !== null) {
                (new self($this->codebase, !$this->contravariant))->traverseArray($params);
            }

            $return_type = $type->return_type;

            if ($return_type) {
                $this->traverse($return_type);
            }

            $type = $type->replace($params, $return_type);

            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TGenericObject && !$this->contravariant) {
            $type_params = $type->type_params;

            foreach ($type_params as $offset => $type_param) {
                if (StarProjection::isStar($type_param)) {
                    continue;
                }

                $read = $type_param;
                $this->traverse($read);

                $projected = StarProjection::projectInvariantArgument($type, $offset, null, $this->codebase);
                $type_params[$offset] = $projected === null
                    ? $read
                    : StarProjection::create($type_param->from_docblock, $read);
            }

            $type = $type->setTypeParams($type_params);

            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof Union && $type->hasType(StarProjection::NAME)) {
            $atomic_types = [];

            foreach ($type->getAtomicTypes() as $atomic_type) {
                if (!$atomic_type instanceof TStarProjection) {
                    $atomic_types[] = $atomic_type;
                } elseif (!$this->contravariant) {
                    foreach (($atomic_type->as ?? Type::getMixed())->getAtomicTypes() as $known_atomic_type) {
                        $atomic_types[] = $known_atomic_type;
                    }
                }
            }

            $type = $type->getBuilder()->setTypes(
                $atomic_types === []
                    ? [new TNever()]
                    : TypeCombiner::combine($atomic_types, $this->codebase)->getAtomicTypes(),
            )->freeze();
        }

        return null;
    }
}
