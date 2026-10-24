<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use InvalidArgumentException;
use Override;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Context;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\PurityArguments;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TypeParser;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\RedundantCallSiteVariance;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TGlobalConstant;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;
use Psalm\Type\Variance;
use ReflectionProperty;

use function array_any;
use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function str_contains;
use function str_starts_with;
use function strtolower;

/**
 * @internal
 */
final class TypeChecker extends TypeVisitor
{
    private bool $has_errors = false;

    /**
     * Whether the node being visited is in an invariant type param of a generic object, where entering a
     * callable's parameter types doesn't change the position
     */
    private bool $in_invariant_position = false;

    /**
     * @param array<string>    $suppressed_issues
     * @param array<string, bool> $phantom_classes
     * @param bool $prevent_template_covariance whether the type is in an input position, a method's parameter
     * @param bool $in_output_position whether the type is in an output position, a method's return type, or one
     *                                 an input position turns into: a covariant template may be there (a method's
     *                                 `Closure(T): void` parameter hands T out), but the parameter types of a
     *                                 callable in there are an input position
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly CodeLocation $code_location,
        private readonly array $suppressed_issues,
        private array $phantom_classes = [],
        private readonly bool $inferred = true,
        private readonly bool $inherited = false,
        private bool $prevent_template_covariance = false,
        private readonly ?Context $context = null,
        private bool $in_output_position = false,
    ) {
    }

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if (!$type instanceof Atomic && !$type instanceof Union && !$type instanceof MutableUnion) {
            return null;
        }

        if ($type->checked) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TNamedObject) {
            $prevents_covariance = $this->checkNamedObject($type);

            if ($type instanceof TGenericObject && $prevents_covariance !== null) {
                // each type param is checked with its own variance, and the siblings with the outer one. Variance
                // composes: a covariant type param is in the position of the type, an invariant one in neither
                $outer_prevents_covariance = $this->prevent_template_covariance;
                $outer_in_invariant_position = $this->in_invariant_position;
                $outer_in_output_position = $this->in_output_position;

                foreach ($type->extra_types as $extra_type) {
                    $this->traverse($extra_type);
                }

                foreach ($type->type_params as $i => $type_param) {
                    $this->prevent_template_covariance = $outer_prevents_covariance;
                    $this->in_invariant_position = $outer_in_invariant_position;
                    $this->in_output_position = $outer_in_output_position;

                    switch ($prevents_covariance[$i] ?? GenericParamPosition::Same) {
                        case GenericParamPosition::Invariant:
                            $this->prevent_template_covariance = true;
                            $this->in_invariant_position = true;
                            break;

                        case GenericParamPosition::None:
                            $this->prevent_template_covariance = false;
                            break;

                        case GenericParamPosition::Opposite:
                            $this->flipPosition();
                            break;

                        case GenericParamPosition::Same:
                            break;
                    }

                    $this->traverse($type_param);
                }

                $this->prevent_template_covariance = $outer_prevents_covariance;
                $this->in_invariant_position = $outer_in_invariant_position;
                $this->in_output_position = $outer_in_output_position;

                /** @psalm-suppress InaccessibleProperty Doesn't affect anything else */
                $type->checked = true;

                return self::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($type instanceof TClassConstant) {
            $this->checkScalarClassConstant($type);
        } elseif ($type instanceof TGlobalConstant) {
            $this->checkGlobalConstant($type);
        } elseif ($type instanceof TTemplateParam) {
            $this->checkTemplateParam($type);
        } elseif ($type instanceof TResource) {
            $this->checkResource($type);
        } elseif ($type instanceof TIntersection) {
            $this->checkIntersection($type);
        }

        /** @psalm-suppress InaccessibleProperty Doesn't affect anything else */
        $type->checked = true;

        return null;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function traverse(TypeNode $node): bool
    {
        if (!$node instanceof FunctionLikeParameter) {
            return parent::traverse($node);
        }

        // a callable's parameter types are in the opposite position. Positions that are neither (constructor
        // parameters, and what isn't a method's signature) stay unchecked
        $outer_prevents_covariance = $this->prevent_template_covariance;
        $outer_in_output_position = $this->in_output_position;

        $this->flipPosition();

        $result = parent::traverse($node);

        $this->prevent_template_covariance = $outer_prevents_covariance;
        $this->in_output_position = $outer_in_output_position;

        return $result;
    }

    /**
     * Turns an input position into an output one and the other way round. Neither is left alone, as is what is in
     * an invariant type param
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private function flipPosition(): void
    {
        if ($this->in_invariant_position) {
            return;
        }

        if ($this->prevent_template_covariance) {
            $this->prevent_template_covariance = false;
            $this->in_output_position = true;
        } elseif ($this->in_output_position) {
            $this->prevent_template_covariance = true;
            $this->in_output_position = false;
        }
    }

    public function hasErrors(): bool
    {
        return $this->has_errors;
    }

    /**
     * @return array<int, GenericParamPosition>|null the position of each type param of a generic object, or of all
     *                                                if its type params weren't checked
     */
    private function checkNamedObject(TNamedObject $atomic): ?array
    {
        $codebase = $this->source->getCodebase();

        if ($this->code_location instanceof DocblockTypeLocation
            && $codebase->store_node_types
            && $atomic->offset_start !== null
            && $atomic->offset_end !== null
        ) {
            $codebase->analyzer->addOffsetReference(
                $this->source->getFilePath(),
                $this->code_location->raw_file_start + $atomic->offset_start,
                $this->code_location->raw_file_start + $atomic->offset_end,
                $atomic->value,
            );
        }

        if ($this->context?->calling_method_id !== null
            && $atomic->text !== null
        ) {
            $codebase->addReferenceToClass(
                strtolower($atomic->value),
                $this->code_location,
                $this->context,
            );
            // the type was written using an import alias: re-analyse if the import changes
            $codebase->addReferenceToUseAlias(
                explode('\\', $atomic->text, 2)[0],
                $this->source->getFilePath(),
                $this->context,
            );
        }

        if (!isset($this->phantom_classes[strtolower($atomic->value)])) {
            if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $this->source,
                $atomic->value,
                $this->code_location,
                $this->context,
                $this->suppressed_issues,
                new ClassLikeNameOptions($this->inferred, false, true, true, $atomic->from_docblock),
            ) === false) {
                $this->has_errors = true;
                return null;
            }
        }

        $fq_class_name_lc = strtolower($atomic->value);

        if (!$this->inherited
            && $codebase->classlike_storage_provider->has($fq_class_name_lc)
            && $this->source->getFQCLN() !== $atomic->value
        ) {
            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name_lc);

            if ($class_storage->deprecated) {
                if ($class_storage->is_interface) {
                    IssueBuffer::maybeAdd(
                        new DeprecatedInterface(
                            'Interface ' . $atomic->value . ' is marked as deprecated',
                            $this->code_location,
                            $atomic->value,
                        ),
                        $this->source->getSuppressedIssues() + $this->suppressed_issues,
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new DeprecatedClass(
                            'Class ' . $atomic->value . ' is marked as deprecated',
                            $this->code_location,
                            $atomic->value,
                        ),
                        $this->source->getSuppressedIssues() + $this->suppressed_issues,
                    );
                }
            }
        }

        if ($atomic instanceof TGenericObject) {
            return $this->checkGenericParams($atomic);
        }

        return null;
    }

    /**
     * @return array<int, GenericParamPosition>|null the position of each type param, or of all for an unknown class
     */
    private function checkGenericParams(TGenericObject $atomic): ?array
    {
        $codebase = $this->source->getCodebase();

        try {
            $class_storage = $codebase->classlike_storage_provider->get(strtolower($atomic->value));
        } catch (InvalidArgumentException) {
            return null;
        }

        $expected_type_params = $class_storage->template_types ?: [];
        $expected_param_covariants = $class_storage->template_covariants;

        $template_type_count = count($expected_type_params);
        $template_param_count = count($atomic->type_params);

        // trailing purity templates with a default need not be given
        if ($class_storage->getRequiredTemplateParamCount() > $template_param_count) {
            IssueBuffer::maybeAdd(
                new MissingTemplateParam(
                    $atomic->value . ' has missing template params, expecting '
                        . $template_type_count,
                    $this->code_location,
                ),
                $this->suppressed_issues,
            );
        } elseif ($template_type_count < $template_param_count
            || !PurityArguments::fit($atomic->type_params, $class_storage)
        ) {
            IssueBuffer::maybeAdd(
                new TooManyTemplateParams(
                    $atomic->getId(). ' has too many template params, expecting '
                        . $template_type_count,
                    $this->code_location,
                ),
                $this->suppressed_issues,
            );
        }

        $expected_type_param_keys = array_keys($expected_type_params);
        $template_result = new TemplateResult($expected_type_params, []);

        // the bounds of type templates may use purity templates, which come after them
        foreach ($atomic->type_params as $i => $type_param) {
            $expected_template_name = $expected_type_param_keys[$i] ?? null;

            if ($expected_template_name === null || !Capabilities::isPurityType($type_param)) {
                continue;
            }

            foreach ($expected_type_params[$expected_template_name] as $defining_class => $expected_type_param) {
                if (Capabilities::isPurityType($expected_type_param)) {
                    $template_result->lower_bounds[$expected_template_name][$defining_class][]
                        = new TemplateBound($type_param);
                }
            }
        }

        // an omitted trailing purity template takes its default, also in the bounds of the type templates
        foreach (array_slice($expected_type_param_keys, $template_param_count) as $expected_template_name) {
            $default = $class_storage->template_defaults[$expected_template_name] ?? null;

            if ($default === null || !Capabilities::isPurityType($default)) {
                continue;
            }

            foreach ($expected_type_params[$expected_template_name] as $defining_class => $_) {
                $template_result->lower_bounds[$expected_template_name][$defining_class][]
                    = new TemplateBound($default);
            }
        }
      
        $prevents_covariance = [];
        foreach ($atomic->type_params as $i => $type_param) {
            $variance = $atomic->type_param_variances[$i] ?? null;

            if ($variance !== null) {
                $this->checkCallSiteVariance($atomic, $i, $variance, $class_storage, $expected_type_params);
            }

            // a covariant type param is in the position of the type: an invariant one is in neither
            $prevents_covariance[$i] = match (true) {
                $variance === Variance::Contravariant => GenericParamPosition::Opposite,
                $variance === Variance::Bivariant => GenericParamPosition::None,
                $variance === Variance::Covariant || !empty($expected_param_covariants[$i])
                    => GenericParamPosition::Same,
                $this->source instanceof MethodAnalyzer && $this->source->getMethodName() !== '__construct'
                    => GenericParamPosition::Invariant,
                default => GenericParamPosition::None,
            };

            // a `*` stands for any type, so it is not held to the bound of the template
            if ($variance === Variance::Bivariant) {
                continue;
            }

            if (isset($expected_type_param_keys[$i])) {
                $expected_template_name = $expected_type_param_keys[$i];

                foreach ($expected_type_params[$expected_template_name] as $defining_class => $expected_type_param) {
                    $expected_type_param = TemplateInferredTypeReplacer::replace(
                        TypeExpander::expandUnion(
                            $codebase,
                            $expected_type_param,
                            $defining_class,
                            null,
                            null,
                        ),
                        $template_result,
                        $codebase,
                    );

                    $type_param = TypeExpander::expandUnion(
                        $codebase,
                        $type_param,
                        $defining_class,
                        null,
                        null,
                    );

                    $lower_bound = $class_storage->template_lower_bounds[$expected_template_name] ?? null;

                    if (!UnionTypeComparator::isContainedBy($codebase, $type_param, $expected_type_param)) {
                        IssueBuffer::maybeAdd(
                            new InvalidTemplateParam(
                                'Extended template param ' . $expected_template_name
                                    . ' of ' . $atomic->getId()
                                    . ' expects type '
                                    . $expected_type_param->getId()
                                    . ', type ' . $type_param->getId() . ' given',
                                $this->code_location,
                            ),
                            $this->suppressed_issues,
                        );
                    } elseif ($lower_bound !== null
                        && Capabilities::isPurityType($type_param)
                        && !Capabilities::allowsAnyOf(Capabilities::fromType($type_param), $lower_bound)
                    ) {
                        // a purity template with a lower bound: the value must require at least that
                        IssueBuffer::maybeAdd(
                            new InvalidTemplateParam(
                                'Extended template param ' . $expected_template_name
                                    . ' of ' . $atomic->getId()
                                    . ' must include at least '
                                    . Capabilities::toString($lower_bound)
                                    . ', ' . $type_param->getId() . ' given',
                                $this->code_location,
                            ),
                            $this->suppressed_issues,
                        );
                    } else {
                        $template_result->lower_bounds[$expected_template_name][$defining_class][]
                            = new TemplateBound($type_param);
                    }
                }
            }
        }

        return $prevents_covariance;
    }

    /**
     * @param array<string, non-empty-array<string, Union>> $expected_type_params
     */
    private function checkCallSiteVariance(
        TGenericObject $atomic,
        int $offset,
        Variance $variance,
        ClassLikeStorage $class_storage,
        array $expected_type_params,
    ): void {
        $template_name = array_keys($expected_type_params)[$offset] ?? null;

        // a type param the class has no template for is reported as too many
        if ($template_name === null
            || $variance === Variance::Bivariant
            || empty($class_storage->template_covariants[$offset])
        ) {
            return;
        }

        $argument = $variance->value . ' ' . $atomic->type_params[$offset]->getId();

        if ($variance === Variance::Contravariant) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock(
                    'Call-site variance of ' . $argument . ' in generic type ' . $atomic->getId()
                        . ' is in conflict with covariant template type ' . $template_name
                        . ' of class ' . $class_storage->name . '.',
                    $this->code_location,
                ),
                $this->suppressed_issues,
            );
        } elseif (strtolower($class_storage->name) !== 'reflectionclass') {
            // ReflectionClass<T> is covariant, and code written for several PHP versions needs the annotation
            IssueBuffer::maybeAdd(
                new RedundantCallSiteVariance(
                    'Call-site variance of ' . $argument . ' in generic type ' . $atomic->getId()
                        . ' is redundant, template type ' . $template_name
                        . ' of class ' . $class_storage->name . ' has the same variance.',
                    $this->code_location,
                ),
                $this->suppressed_issues,
            );
        }
    }

    public function checkScalarClassConstant(TClassConstant $atomic): void
    {
        $fq_classlike_name = $atomic->fq_classlike_name === 'self'
            ? $this->source->getClassName()
            : $atomic->fq_classlike_name;

        if (!$fq_classlike_name) {
            return;
        }

        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
            $this->source,
            $fq_classlike_name,
            $this->code_location,
            $this->context,
            $this->suppressed_issues,
            new ClassLikeNameOptions($this->inferred, false, true, true, $atomic->from_docblock),
        ) === false
        ) {
            $this->has_errors = true;
            return;
        }

        $const_name = $atomic->const_name;
        if (str_contains($const_name, '*')) {
            TypeExpander::expandAtomic(
                $this->source->getCodebase(),
                $atomic,
                $fq_classlike_name,
                $fq_classlike_name,
                null,
                true,
                true,
            );

            $is_defined = true;
        } else {
            $class_constant_type = $this->source->getCodebase()->classlikes->getClassConstantType(
                $fq_classlike_name,
                $atomic->const_name,
                ReflectionProperty::IS_PRIVATE,
                null,
            );

            $is_defined = null !== $class_constant_type;
        }

        if (!$is_defined) {
            IssueBuffer::maybeAdd(
                new UndefinedConstant(
                    'Constant ' . $fq_classlike_name . '::' . $const_name . ' is not defined',
                    $this->code_location,
                ),
                $this->source->getSuppressedIssues(),
            );
        }
    }

    private function checkGlobalConstant(TGlobalConstant $atomic): void
    {
        $codebase = $this->source->getCodebase();

        if (ConstFetchAnalyzer::getGlobalConstTypesByWildcard($codebase, $atomic->const_name) !== []
            || ($atomic->fallback_const_name !== null
                && ConstFetchAnalyzer::getGlobalConstTypesByWildcard($codebase, $atomic->fallback_const_name) !== [])
        ) {
            return;
        }

        $this->has_errors = true;

        IssueBuffer::maybeAdd(
            new UndefinedConstant(
                'No constant matches ' . ($atomic->fallback_const_name ?? $atomic->const_name),
                $this->code_location,
            ),
            $this->source->getSuppressedIssues(),
        );
    }

    public function checkTemplateParam(TTemplateParam $atomic): void
    {
        if ($this->prevent_template_covariance
            && !str_starts_with($atomic->defining_class, 'fn-')
            && $atomic->defining_class !== 'class-string-map'
        ) {
            $codebase = $this->source->getCodebase();

            $class_storage = $codebase->classlike_storage_provider->get($atomic->defining_class);

            $template_offset = $class_storage->template_types
                ? array_search($atomic->param_name, array_keys($class_storage->template_types), true)
                : false;

            if ($template_offset !== false
                && isset($class_storage->template_covariants[$template_offset])
                && $class_storage->template_covariants[$template_offset]
            ) {
                $method_storage = $this->source instanceof MethodAnalyzer
                    ? $this->source->getFunctionLikeStorage()
                    : null;

                if ($method_storage instanceof MethodStorage
                    && $method_storage->isMutationFree()
                    && !$method_storage->mutation_free_assumed
                ) {
                    // do nothing
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidTemplateParam(
                            'Template param ' . $atomic->param_name . ' of '
                                . $atomic->defining_class . ' is marked covariant and cannot be used here',
                            $this->code_location,
                        ),
                        $this->source->getSuppressedIssues(),
                    );
                }
            }
        }
    }

    /**
     * Checks the intersections of a declared type that were deferred until the class constants and type aliases they
     * refer to are known: expanding the type replaces them, so that checking the expanded type doesn't check them
     *
     * @param array<string> $suppressed_issues
     * @psalm-capabilities read-props
     */
    public static function checkDeferredIntersections(
        Union $type,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        ?Context $context = null,
    ): void {
        $collector = new DeferredIntersectionCollector();
        $collector->traverseArray($type->getAtomicTypes());

        foreach ($collector->getIntersections() as $intersection) {
            (new self($source, $code_location, $suppressed_issues, [], false, false, false, $context))
                ->traverse($intersection);
        }
    }

    /**
     * Reports an intersection deferred until the class constants and type aliases it refers to are known, when
     * Psalm has no type for it or no value has all of its types, as for one Psalm intersects when parsing it
     */
    public function checkIntersection(TIntersection $atomic): void
    {
        $codebase = $this->source->getCodebase();
        $self_class = $this->source->getFQCLN();

        $types = [];

        foreach ($atomic->types as $type) {
            $type = TypeExpander::expandUnion(
                $codebase,
                $type,
                $self_class,
                $self_class,
                $this->source->getParentFQCLN(),
            );

            if (array_any($type->getAtomicTypes(), TypeParser::hasUnresolvedReference(...))) {
                // an undefined class constant or type alias, reported on its own
                return;
            }

            $types[] = $type;
        }

        try {
            $atomic_types = TypeParser::intersectTypes(
                array_map(static fn(Union $type): array => array_values($type->getAtomicTypes()), $types),
                $codebase,
                $atomic->from_docblock,
            );
        } catch (TypeParseTreeException $e) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock($e->getMessage(), $this->code_location),
                $this->suppressed_issues,
            );

            return;
        }

        if ($atomic_types === []) {
            IssueBuffer::maybeAdd(
                new InvalidDocblock('No value has all the types of the intersection', $this->code_location),
                $this->suppressed_issues,
            );
        }
    }

    public function checkResource(TResource $atomic): void
    {
        if (!$atomic->from_docblock) {
            IssueBuffer::maybeAdd(
                new ReservedWord(
                    '\'resource\' is a reserved word',
                    $this->code_location,
                    'resource',
                ),
                $this->source->getSuppressedIssues(),
            );
        }
    }
}
