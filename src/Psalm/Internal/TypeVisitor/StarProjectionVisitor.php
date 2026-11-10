<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\StarProjection;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Storage\Possibilities;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function array_values;

/**
 * Projects a type read or written through a `*` by the position of each unknown type in it: in a position the caller
 * writes to (the type when written, a parameter of a callable read from it) the unknown type accepts nothing, also
 * inside a type argument (`Box<never>`); where the caller reads, an invariant type argument mentioning it is `*`
 * (`Box<list<TSecond>>` is `Box<*>`) and the rest is what is known of it.
 *
 * The unknown types are the templates a template result binds to `*`, for the type of a member read or written
 * through a receiver like `Pair<int, *>` (the template replacer then reads the rest), or else the `*` the type holds
 * as values already, for a type built from the receiver's type arguments outside the template replacers (a return
 * type provider wrapping the receiver's `*` argument in `list<…>`).
 *
 * @internal
 */
final class StarProjectionVisitor extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly ?TemplateResult $template_result,
        private readonly ?Codebase $codebase,
        private readonly bool $contravariant,
    ) {
    }

    public static function project(
        Union $type,
        ?TemplateResult $template_result,
        ?Codebase $codebase,
        bool $for_write = false,
    ): Union {
        if ($template_result === null ? StarProjection::contains($type, [TStarProjection::class])
            : StarProjection::bindsStar($template_result)
        ) {
            (new self($template_result, $codebase, $for_write))->traverse($type);
        }

        return $type;
    }

    /**
     * The assertions of a method called on a receiver binding templates to `*`, each asserted type projected like
     * a return type: the caller reads it from the receiver (`@psalm-assert Closure(T): void` asserts
     * `Closure(never): void` on a `Guard<*>`)
     *
     * @param array<int, Possibilities> $assertions
     * @return array<int, Possibilities>
     */
    public static function projectAssertions(
        array $assertions,
        TemplateResult $template_result,
        Codebase $codebase,
    ): array {
        if (!StarProjection::bindsStar($template_result)) {
            return $assertions;
        }

        foreach ($assertions as $offset => $possibilities) {
            $rules = [];

            foreach ($possibilities->rule as $assertion) {
                $assertion_type = $assertion->getAtomicType();
                $projected = $assertion_type
                    ? self::project(new Union([$assertion_type]), $template_result, $codebase)
                    : null;

                $rules[] = $projected !== null && $projected->isSingle()
                    ? $assertion->setAtomicType($projected->getSingleAtomic())
                    : $assertion;
            }

            $assertions[$offset] = new Possibilities($possibilities->var_id, $rules);
        }

        return $assertions;
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        // what is known of a `*` is read, not written, whatever the position
        if ($type instanceof TStarProjection) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TClosure || $type instanceof TCallable) {
            $params = $type->params;

            if ($params !== null) {
                (new self($this->template_result, $this->codebase, !$this->contravariant))->traverseArray($params);
            }

            $return_type = $type->return_type;

            if ($return_type) {
                $this->traverse($return_type);
            }

            $type = $type->replace($params, $return_type);

            return self::DONT_TRAVERSE_CHILDREN;
        }

        if (!$this->contravariant && $type instanceof TGenericObject) {
            $type_params = $type->type_params;

            foreach ($type_params as $offset => $type_param) {
                // a `*` argument stays one where it is read
                if (StarProjection::isStar($type_param)) {
                    continue;
                }

                $projected = StarProjection::projectInvariantArgument(
                    $type,
                    $offset,
                    $this->template_result,
                    $this->codebase,
                );

                if ($projected === null) {
                    $this->traverse($type_params[$offset]);

                    continue;
                }

                $projected_star = $projected->getSingleAtomic();

                if (!$projected_star instanceof TStarProjection || $projected_star->as !== null) {
                    $type_params[$offset] = $projected;
                } elseif ($this->template_result !== null) {
                    // the `*` knows what reading the argument gives (`Box<list<TSecond>>` is a `Box<*>` of
                    // `list<object>`)
                    $type_params[$offset] = StarProjection::createForArgument(
                        $type_param,
                        $this->template_result,
                        $this->codebase,
                    );
                } else {
                    $this->traverse($type_params[$offset]);
                    $type_params[$offset] = StarProjection::create($type_param->from_docblock, $type_params[$offset]);
                }
            }

            // the intersected types (`Marker<int>&Holder<list<*>>`) are read alike
            $extra_types = $type->extra_types;
            $this->traverseArray($extra_types);

            $type = $type->setTypeParams($type_params)->setIntersectionTypes($extra_types);

            return self::DONT_TRAVERSE_CHILDREN;
        }

        // where the caller reads, the template replacer reads the templates bound to `*`
        if (!$type instanceof Union || (!$this->contravariant && $this->template_result !== null)) {
            return null;
        }

        $atomic_types = [];

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($this->contravariant) {
                if (!StarProjection::acceptsNothing($atomic_type, $this->template_result)) {
                    $atomic_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TStarProjection) {
                $atomic_types = [
                    ...$atomic_types,
                    ...array_values(StarProjection::read(new Union([$atomic_type]))->getAtomicTypes()),
                ];
            } else {
                $atomic_types[] = $atomic_type;
            }
        }

        if ($atomic_types === []) {
            $type = $type->setTypes([new TNever()]);
        } elseif ($atomic_types !== array_values($type->getAtomicTypes())) {
            $type = $type->setTypes(TypeCombiner::combine($atomic_types, $this->codebase)->getAtomicTypes());
        }

        return null;
    }
}
