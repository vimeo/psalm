<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\PurityArguments;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeArgumentProjection;
use Psalm\Internal\TypeVisitor\TemplateTypeCollector;
use Psalm\Storage\TemplateVariance;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_diff_key;
use function array_values;
use function count;
use function implode;
use function in_array;
use function sort;
use function substr;

/**
 * Denotes an object type that has generic parameters e.g. `ArrayObject<string, Foo\Bar>`
 *
 * The arguments of the purity templates of the class, if any, are the last type parameters, like
 * the templates in the class storage; they are written in brackets before the others:
 * `Traversable[pure]<int, string>` has the type parameters `int`, `string` and `pure`.
 *
 * @psalm-immutable
 * @api
 */
final class TGenericObject extends TNamedObject
{
    /**
     * @use GenericTrait<non-empty-list<Union>>
     */
    use GenericTrait;

    /**
     * @var non-empty-list<Union>
     */
    public array $type_params;

    /**
     * What each type argument stands for besides its type, by offset: the type of a `*` or a left out argument is
     * the bound of its template, with the other arguments applied
     *
     * @var array<int, TypeArgumentProjection>
     */
    private array $projections = [];

    /**
     * @param string                $value the name of the object
     * @param non-empty-list<Union> $type_params
     * @param array<string, TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject> $extra_types
     */
    public function __construct(
        string $value,
        array $type_params,
        /** @var bool if the parameters have been remapped to another class */
        public bool $remapped_params = false,
        bool $is_static = false,
        array $extra_types = [],
        bool $from_docblock = false,
    ) {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->type_params = $type_params;
        parent::__construct(
            $value,
            $is_static,
            false,
            $extra_types,
            $from_docblock,
        );
    }

    /**
     * @return array{list<Union>, list<Union>}
     */
    public function getTypeParamsAndPurityArgs(): array
    {
        return PurityArguments::split($this->type_params);
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        return $this->value . $this->getTypeParamsId($exact) . $this->getIntersectionTypesId($exact)
            . ($this->is_static ? '&static' : '');
    }

    /**
     * @internal
     * @psalm-mutation-free
     */
    public function getProjection(int $offset): ?TypeArgumentProjection
    {
        return $this->projections[$offset] ?? null;
    }

    /**
     * @internal
     * @return array<int, TypeArgumentProjection>
     * @psalm-mutation-free
     */
    public function getProjections(): array
    {
        return $this->projections;
    }

    /**
     * @internal
     * @psalm-mutation-free
     */
    public function hasStarProjection(): bool
    {
        return in_array(TypeArgumentProjection::Star, $this->projections, true);
    }

    /**
     * @internal
     * @param array<int, TypeArgumentProjection> $projections
     */
    public function setProjections(array $projections): self
    {
        if ($this->projections === $projections) {
            return $this;
        }

        $cloned = clone $this;
        $cloned->projections = $projections;

        return $cloned;
    }

    /**
     * The object with other type arguments. An argument that is replaced by a different type is no `*` or left out
     * argument any more: a plugin that rebuilds an argument (`Relation<User, *>` with a pivot class) means that type,
     * while one that rebuilds an equal type keeps the marker.
     *
     * @param non-empty-list<Union> $type_params
     * @return static
     */
    public function setTypeParams(array $type_params): self
    {
        if ($this->type_params === $type_params) {
            return $this;
        }

        $cloned = clone $this;
        $cloned->type_params = $type_params;

        foreach ($cloned->projections as $offset => $_) {
            if (!isset($type_params[$offset], $this->type_params[$offset])
                || !$type_params[$offset]->equals($this->type_params[$offset])
            ) {
                unset($cloned->projections[$offset]);
            }
        }

        return $cloned;
    }

    /**
     * The object with the same projections holding other types, for what only refines the type a `*` holds
     *
     * @internal
     * @param non-empty-list<Union> $type_params
     * @return static
     */
    public function setHeldTypes(array $type_params): self
    {
        if ($this->type_params === $type_params) {
            return $this;
        }

        $cloned = clone $this;
        $cloned->type_params = $type_params;

        return $cloned;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        [$type_params, $purity_args] = $this->getTypeParamsAndPurityArgs();

        return $this->value
            . self::formatTypeParams($type_params, $purity_args, self::getTypeParamKey(...), $this)
            . ($include_extra ? $this->getIntersectionTypesId(true) : '');
    }

    /**
     * TypeCombiner merges the generic objects of the same key: those whose type arguments only differ by their
     * literals or by what their atomic types' keys leave out (`Foo<5>` and `Foo<6>`, `Foo<array{a: int}>` and
     * `Foo<list<int>>`)
     *
     * @psalm-pure
     */
    private static function getTypeParamKey(Union $type_param): string
    {
        $keys = [];

        foreach ($type_param->getAtomicTypes() as $atomic_type) {
            $key = match (true) {
                $atomic_type instanceof TLiteralInt => 'int',
                $atomic_type instanceof TLiteralFloat => 'float',
                $atomic_type instanceof TLiteralString => 'string',
                default => $atomic_type->getKey(),
            };
            $keys[$key] = $key;
        }

        sort($keys);

        return implode('|', $keys);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if ($this->value !== $other_type->value
            || $this->is_static !== $other_type->is_static
            || !$this->intersectionTypesEqual($other_type->extra_types, $ensure_source_equality)
        ) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            $is_star = $this->getProjection($i) === TypeArgumentProjection::Star;

            // the type of a `*` is a function of the class and the other arguments
            if ($is_star !== ($other_type->getProjection($i) === TypeArgumentProjection::Star)
                || (!$is_star && !$type_param->equals($other_type->type_params[$i], $ensure_source_equality, false))
            ) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return $this->value;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return [...parent::getChildNodeKeys(), 'type_params'];
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0,
    ): self {
        $types = $this->replaceTypeParamsTemplateTypesWithStandins(
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $depth,
        );
        $intersection = $this->replaceIntersectionTemplateTypesWithStandins(
            $template_result,
            $codebase,
            $statements_analyzer,
            $input_type,
            $input_arg_offset,
            $calling_class,
            $calling_function,
            $replace,
            $add_lower_bound,
            $depth,
        );
        if (!$types && !$intersection) {
            return $this;
        }
        return (new static(
            $this->value,
            $types ?? $this->type_params,
            $this->remapped_params,
            $this->is_static,
            $intersection ?? $this->extra_types,
        ))->setIsStatic($this->is_static, $this->is_static_resolved)->setProjections($this->projections);
    }

    /**
     * @return static
     */
    #[Override]
    public function replaceTemplateTypesWithArgTypes(TemplateResult $template_result, ?Codebase $codebase): self
    {
        $type_params = $this->replaceTypeParamsTemplateTypesWithArgTypes(
            $template_result,
            $codebase,
        );
        $intersection = $this->replaceIntersectionTemplateTypesWithArgTypes(
            $template_result,
            $codebase,
        );
        if (!$type_params && !$intersection) {
            return $this;
        }

        $projections = $this->projections + $this->getStarBoundProjections($template_result, $codebase);
        $type_params ??= $this->type_params;

        // a new `*` holds the bound of the template of this class, which resolve() computes from `mixed`
        foreach (array_diff_key($projections, $this->projections) as $offset => $_) {
            $type_params[$offset] = Type::getMixed();
        }

        return (new static(
            $this->value,
            array_values($type_params),
            true,
            $this->is_static,
            $intersection ?? $this->extra_types,
        ))
            ->setIsStatic($this->is_static, $this->is_static_resolved)
            ->setProjections($projections);
    }

    /**
     * The invariant arguments that mention a template the receiver bound to a `*` (`Box<T>`, `Box<Box<T>>` read from
     * a `Box<*>`): what is read from the receiver is as unknown as the receiver, at the outermost such argument. A
     * covariant argument reads the bound.
     *
     * @return array<int, TypeArgumentProjection>
     * @psalm-mutation-free
     */
    public function getStarBoundProjections(TemplateResult $template_result, ?Codebase $codebase): array
    {
        if ($codebase === null || $template_result->star_bindings === []) {
            return [];
        }

        // `static<T>` names no class here, so it is read as an invariant argument list
        $class_storage = $codebase->classlike_storage_provider->has($this->value)
            ? $codebase->classlike_storage_provider->get($this->value)
            : null;
        $projections = [];

        foreach ($this->type_params as $offset => $type_param) {
            if ($class_storage?->getTemplateVariance($offset) === TemplateVariance::Covariant) {
                continue;
            }

            $collector = new TemplateTypeCollector(true);
            $collector->traverseArray($type_param->getAtomicTypes());

            foreach ($collector->getTemplateTypes() as $template) {
                if (isset($template_result->star_bindings[$template->param_name][$template->defining_class])) {
                    $projections[$offset] = TypeArgumentProjection::Star;

                    break;
                }
            }
        }

        return $projections;
    }
}
