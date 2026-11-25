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
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeArgumentProjection;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\Type\TypeParser;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\TemplateVariance;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TGlobalConstant;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntersection;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;
use ReflectionProperty;

use function array_any;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function spl_object_id;
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
     * Whether the result of the check holds wherever the type is: not when a phantom class (a class_exists() check
     * around the code) let a class pass, or the type holds a template with a variance
     */
    private bool $context_free = true;

    /**
     * The types this checker marked as checked, for check() to unmark when the result can't be kept
     *
     * @var list<Atomic|Union|MutableUnion>
     */
    private array $checked_types = [];

    /**
     * The positions each type this checker marked was in, by object id: a type it meets again in another position
     * (the bound of a method's template, one type for all its occurrences) is checked again
     *
     * @var array<int, array{?TemplateVariance, ?TemplateVariance}>
     */
    private array $checked_positions = [];

    /**
     * In the bound of a method's template, the position a contravariant template is checked in: Hack checks the
     * bound (`@template U of T`) as an input position of the method, wherever the method's template is used.
     * A covariant template is still checked where the method's template is used, as it always was
     */
    private ?TemplateVariance $bound_position = null;

    /**
     * @param array<string>    $suppressed_issues
     * @param array<string, bool> $phantom_classes
     * @param ?TemplateVariance $position the variance a template must have to be where the type is: covariant for
     *                                    an output position (a method's return type, a property only the class
     *                                    writes), contravariant for an input one (a method's parameter), invariant
     *                                    for one that is both (a writable property). Null where templates are not
     *                                    checked (a constructor's parameters, a private property, what isn't the
     *                                    signature of a member)
     * @param bool $only_contravariant whether only contravariant templates are checked: a covariant one may be in
     *                                 a property's type, and was never checked in `@param-out`, by-ref output and
     *                                 assertion types
     * @param bool $from_overridden_method whether the type is one a method takes from the method it overrides,
     *                                     where its templates are checked
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly CodeLocation $code_location,
        private readonly array $suppressed_issues,
        private array $phantom_classes = [],
        private readonly bool $inferred = true,
        private readonly bool $inherited = false,
        private ?TemplateVariance $position = null,
        private readonly ?Context $context = null,
        private readonly bool $only_contravariant = false,
        private readonly bool $from_overridden_method = false,
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

        if ($type->checked
            && (!array_key_exists($id = spl_object_id($type), $this->checked_positions)
                || $this->checked_positions[$id] === [$this->position, $this->bound_position])
        ) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof TNamedObject) {
            $param_variances = $this->checkNamedObject($type);

            if ($type instanceof TGenericObject && $param_variances !== null) {
                // each type param is checked with its own variance, and the siblings with the outer one
                $outer_position = $this->position;
                $outer_bound_position = $this->bound_position;

                foreach ($type->extra_types as $extra_type) {
                    $this->traverse($extra_type);
                }

                foreach ($type->type_params as $i => $type_param) {
                    // what a `*` holds is the bound of its template, checked where the template is declared
                    if ($type->getProjection($i) === TypeArgumentProjection::Star) {
                        continue;
                    }

                    $this->position = $this->getTypeParamPosition($outer_position, $param_variances[$i]);
                    $this->bound_position = $outer_bound_position === null
                        ? null
                        : $this->getTypeParamPosition($outer_bound_position, $param_variances[$i]);
                    $this->traverse($type_param);
                }

                $this->position = $outer_position;
                $this->bound_position = $outer_bound_position;

                $this->markChecked($type);

                return self::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($type instanceof TClassConstant) {
            $this->checkScalarClassConstant($type);
        } elseif ($type instanceof TGlobalConstant) {
            $this->checkGlobalConstant($type);
        } elseif ($type instanceof TIntMask) {
            $this->checkIntMaskConstants($type);
        } elseif ($type instanceof TTemplateParam) {
            $this->checkTemplateParam($type);

            if (str_starts_with($type->defining_class, 'fn-')) {
                $this->markChecked($type);

                foreach ($type->extra_types as $extra_type) {
                    $this->traverse($extra_type);
                }

                $outer_bound_position = $this->bound_position;
                $this->bound_position = TemplateVariance::Contravariant;
                $this->traverse($type->as);
                $this->bound_position = $outer_bound_position;

                return self::DONT_TRAVERSE_CHILDREN;
            }
        } elseif ($type instanceof TResource) {
            $this->checkResource($type);
        } elseif ($type instanceof TIntersection) {
            $this->checkIntersection($type);
        } elseif ($type instanceof TKeyOf || $type instanceof TValueOf) {
            $this->checkKeyOrValueOfAlias($type);
        }

        $this->markChecked($type);

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

        // a callable's parameter types are in the opposite position
        $outer_position = $this->position;
        $outer_bound_position = $this->bound_position;
        $this->position = self::getOppositePosition($outer_position);
        $this->bound_position = self::getOppositePosition($outer_bound_position);

        $result = parent::traverse($node);

        $this->position = $outer_position;
        $this->bound_position = $outer_bound_position;

        return $result;
    }

    /**
     * Variance composes: a covariant type param is in the position of the type, a contravariant one in the
     * opposite position, and an invariant one in both an input and an output position. In a method's signature
     * that holds wherever the type is.
     *
     * @psalm-capabilities read-props
     */
    private function getTypeParamPosition(
        ?TemplateVariance $outer_position,
        TemplateVariance $variance,
    ): ?TemplateVariance {
        return match ($variance) {
            TemplateVariance::Covariant => $outer_position,
            TemplateVariance::Contravariant => self::getOppositePosition($outer_position),
            TemplateVariance::Invariant => $outer_position !== null
                || ($this->source instanceof MethodAnalyzer
                    && $this->source->getMethodName() !== '__construct'
                    && !$this->from_overridden_method)
                    ? TemplateVariance::Invariant
                    : null,
        };
    }

    /**
     * @psalm-pure
     */
    private static function getOppositePosition(?TemplateVariance $position): ?TemplateVariance
    {
        return match ($position) {
            TemplateVariance::Covariant => TemplateVariance::Contravariant,
            TemplateVariance::Contravariant => TemplateVariance::Covariant,
            default => $position,
        };
    }

    /**
     * Checks the type, and marks it and the types in it as checked only if that raised no issue (a suppressed one
     * included) and holds wherever the type is, so that checking it again elsewhere reports the same issues
     *
     * @return bool whether the type has no errors
     * @psalm-external-mutation-free
     */
    public function check(Union|MutableUnion $type): bool
    {
        $issue_count = IssueBuffer::getAcceptedIssueCount();

        $this->traverse($type);

        if ($this->context_free && IssueBuffer::getAcceptedIssueCount() === $issue_count) {
            return true;
        }

        foreach ($this->checked_types as $checked_type) {
            /** @psalm-suppress InaccessibleProperty, ImpurePropertyAssignment `checked` only memoises the check */
            $checked_type->checked = false;
        }

        return !$this->has_errors;
    }

    private function markChecked(Atomic|Union|MutableUnion $type): void
    {
        /** @psalm-suppress InaccessibleProperty Doesn't affect anything else */
        $type->checked = true;
        $this->checked_types[] = $type;
        $this->checked_positions[spl_object_id($type)] = [$this->position, $this->bound_position];
    }

    /**
     * @return list<TemplateVariance>|null the variance of each type param of a generic object, null if its type
     *                                     params weren't checked
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
        } else {
            $this->context_free = false;
        }

        $fq_class_name_lc = strtolower($atomic->value);

        if (!$this->inherited
            && $codebase->classlike_storage_provider->has($fq_class_name_lc)
            && $this->source->getFQCLN() !== $atomic->value
            // a method of a trait is checked as a method of the class that uses it
            && $this->context?->self !== $atomic->value
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
     * @return list<TemplateVariance>|null the variance of each type param, null for an unknown class
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

        $template_type_count = count($expected_type_params);
        $template_param_count = count($atomic->type_params);

        // trailing templates with a default need not be given
        if ($class_storage->getRequiredTemplateParamCount() > $template_param_count) {
            IssueBuffer::maybeAdd(
                new MissingTemplateParam(
                    $atomic->value . ' has missing template params, expecting '
                        . TemplateDefaults::getExpectedParamCount($class_storage),
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
      
        $param_variances = [];
        foreach ($atomic->type_params as $i => $type_param) {
            $param_variances[] = $class_storage->getTemplateVariance($i);

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

                    // `*` fits any bound, and stands for the bound in those of the other templates
                    if ($atomic->getProjection($i) === TypeArgumentProjection::Star) {
                        $template_result->lower_bounds[$expected_template_name][$defining_class][]
                            = new TemplateBound($expected_type_param);

                        continue;
                    }

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

        return $param_variances;
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

    /**
     * An imported type alias is only known once the class is populated, so what the parser accepted of it is
     * checked here, as it is for a type the parser could resolve itself
     */
    private function checkKeyOrValueOfAlias(TKeyOf|TValueOf $atomic): void
    {
        if (!array_any(
            $atomic->type->getAtomicTypes(),
            static fn(Atomic $part): bool => $part instanceof TTypeAlias,
        )) {
            return;
        }

        $expanded_type = TypeExpander::expandUnion($this->source->getCodebase(), $atomic->type, null, null, null);

        if ($atomic instanceof TKeyOf
            ? TKeyOf::isViableTemplateType($expanded_type)
            : TValueOf::isViableTemplateType($expanded_type)
        ) {
            return;
        }

        IssueBuffer::maybeAdd(
            new InvalidDocblock(
                'Untemplated ' . ($atomic instanceof TKeyOf ? 'key-of' : 'value-of') . ' param '
                    . $expanded_type->getId(false) . ' should be an array',
                $this->code_location,
            ),
            $this->suppressed_issues,
        );

        $this->has_errors = true;
    }

    /**
     * A constant of an `int-mask<...>` that is resolved with the codebase (see TypeParser) must be an int, as one
     * that is resolved when the type is parsed
     */
    private function checkIntMaskConstants(TIntMask $atomic): void
    {
        $codebase = $this->source->getCodebase();

        foreach ($atomic->values as $value) {
            if (!$value instanceof TGlobalConstant) {
                continue;
            }

            $const_types = ConstFetchAnalyzer::getGlobalConstTypesByWildcard($codebase, $value->const_name);

            if ($const_types === [] && $value->fallback_const_name !== null) {
                $const_types = ConstFetchAnalyzer::getGlobalConstTypesByWildcard(
                    $codebase,
                    $value->fallback_const_name,
                );
            }

            foreach ($const_types as $const_type) {
                if (!$const_type->isInt()) {
                    $this->has_errors = true;

                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'int-mask types must all be integer values',
                            $this->code_location,
                        ),
                        $this->suppressed_issues,
                    );

                    return;
                }
            }
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
        if (str_starts_with($atomic->defining_class, 'fn-')
            || $atomic->defining_class === 'class-string-map'
        ) {
            return;
        }

        $codebase = $this->source->getCodebase();

        if (!$codebase->classlike_storage_provider->has($atomic->defining_class)) {
            return;
        }

        $class_storage = $codebase->classlike_storage_provider->get($atomic->defining_class);

        $template_offset = $class_storage->template_types
            ? array_search($atomic->param_name, array_keys($class_storage->template_types), true)
            : false;

        if ($template_offset === false) {
            return;
        }

        $variance = $class_storage->getTemplateVariance($template_offset);

        if ($variance === TemplateVariance::Invariant) {
            return;
        }

        // whether the template may be here depends on the position, so the result doesn't hold for the same type
        // elsewhere: the bound of a method's template is one type for all of the method's params and its return type
        $this->context_free = false;

        $position = $variance === TemplateVariance::Contravariant && $this->bound_position !== null
            ? $this->bound_position
            : $this->position;

        if ($this->position === null || $variance === $position) {
            return;
        }

        if ($variance === TemplateVariance::Covariant) {
            if ($this->only_contravariant) {
                return;
            }

            $method_storage = $this->source instanceof MethodAnalyzer
                ? $this->source->getFunctionLikeStorage()
                : null;

            // a method that changes nothing may take a covariant template
            if ($method_storage instanceof MethodStorage
                && $method_storage->isMutationFree()
                && !$method_storage->mutation_free_assumed
            ) {
                return;
            }
        }

        IssueBuffer::maybeAdd(
            new InvalidTemplateParam(
                'Template param ' . $atomic->param_name . ' of ' . $atomic->defining_class . ' is marked '
                    . ($variance === TemplateVariance::Covariant ? 'covariant' : 'contravariant')
                    . ' and cannot be used here',
                $this->code_location,
            ),
            $this->source->getSuppressedIssues(),
        );
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
            (new self($source, $code_location, $suppressed_issues, [], false, false, null, $context))
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
