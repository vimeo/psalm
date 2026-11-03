<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\StarProjection;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Storage\Possibilities;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function count;

/**
 * Projects the type of a member (a method's return type, a property's type) read or written through
 * a receiver that binds class templates to `*` (`Pair<int, *>`) by the position of each template: in
 * a position the caller writes to (the member's type when written, a parameter of a callable read
 * from it) the template accepts nothing, also inside a type argument (`Box<never>`); where the caller
 * reads, an invariant type argument mentioning it is `*` (`Box<list<TSecond>>` is `Box<*>`) and the
 * rest is read by the template replacer as the template's bound.
 *
 * @internal
 */
final class StarProjectionVisitor extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    private function __construct(
        private readonly TemplateResult $template_result,
        private readonly Codebase $codebase,
        private readonly bool $contravariant,
    ) {
    }

    public static function project(
        Union $type,
        TemplateResult $template_result,
        Codebase $codebase,
        bool $for_write,
    ): Union {
        if (StarProjection::bindsStar($template_result)) {
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
                    ? self::project(new Union([$assertion_type]), $template_result, $codebase, false)
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
        // what is known of a `*` is read, not written, whatever the position: the template replacer reads it
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
                $projected = StarProjection::projectInvariantArgument(
                    $type,
                    $offset,
                    $this->template_result,
                    $this->codebase,
                );

                if ($projected === null) {
                    continue;
                }

                $projected_star = $projected->getSingleAtomic();

                // the `*` knows what reading the argument gives (`Box<list<TSecond>>` is a `Box<*>` of `list<object>`)
                $type_params[$offset] = $projected_star instanceof TStarProjection && $projected_star->as === null
                    ? StarProjection::createForArgument($type_param, $this->template_result, $this->codebase)
                    : $projected;
            }

            $type = $type->setTypeParams($type_params);

            return null;
        }

        if ($this->contravariant && $type instanceof Union) {
            $kept_types = [];

            foreach ($type->getAtomicTypes() as $atomic_type) {
                if (!StarProjection::readsTemplateBoundToStar($atomic_type, $this->template_result)) {
                    $kept_types[] = $atomic_type;
                }
            }

            if ($kept_types === []) {
                $type = Type::getNever();
            } elseif (count($kept_types) !== count($type->getAtomicTypes())) {
                $type = $type->setTypes($kept_types);
            }
        }

        return null;
    }
}
