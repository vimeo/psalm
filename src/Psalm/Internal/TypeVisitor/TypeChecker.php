<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use InvalidArgumentException;
use Override;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableUnion;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;
use ReflectionProperty;

use function array_keys;
use function array_search;
use function count;
use function md5;
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
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly CodeLocation $code_location,
        private readonly array $suppressed_issues,
        private array $phantom_classes = [],
        private readonly bool $inferred = true,
        private readonly bool $inherited = false,
        private bool $prevent_template_covariance = false,
        private readonly ?string $calling_method_id = null,
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
            $this->checkNamedObject($type);
        } elseif ($type instanceof TClassConstant) {
            $this->checkScalarClassConstant($type);
        } elseif ($type instanceof TTemplateParam) {
            $this->checkTemplateParam($type);
        } elseif ($type instanceof TResource) {
            $this->checkResource($type);
        }

        /** @psalm-suppress InaccessibleProperty Doesn't affect anything else */
        $type->checked = true;

        return null;
    }

    public function hasErrors(): bool
    {
        return $this->has_errors;
    }

    private function checkNamedObject(TNamedObject $atomic): void
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

        if ($this->calling_method_id
            && $atomic->text !== null
        ) {
            $codebase->file_reference_provider->addMethodReferenceToClassMember(
                $this->calling_method_id,
                'use:' . $atomic->text . ':' . md5($this->source->getFilePath()),
                false,
            );
        }

        if (!isset($this->phantom_classes[strtolower($atomic->value)]) &&
            ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $this->source,
                $atomic->value,
                $this->code_location,
                $this->source->getFQCLN(),
                $this->calling_method_id,
                $this->suppressed_issues,
                new ClassLikeNameOptions($this->inferred, false, true, true, $atomic->from_docblock),
            ) === false
        ) {
            $this->has_errors = true;
            return;
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
            $this->checkGenericParams($atomic);
        }
    }

    private function checkGenericParams(TGenericObject $atomic): void
    {
        $codebase = $this->source->getCodebase();

        try {
            $class_storage = $codebase->classlike_storage_provider->get(strtolower($atomic->value));
        } catch (InvalidArgumentException) {
            return;
        }

        $expected_type_params = $class_storage->template_types ?: [];
        $expected_param_covariants = $class_storage->template_covariants;

        $template_type_count = count($expected_type_params);
        $template_param_count = count($atomic->type_params);

        if ($template_type_count > $template_param_count) {
            IssueBuffer::maybeAdd(
                new MissingTemplateParam(
                    $atomic->value . ' has missing template params, expecting '
                        . $template_type_count,
                    $this->code_location,
                ),
                $this->suppressed_issues,
            );
        } elseif ($template_type_count < $template_param_count) {
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

        foreach ($atomic->type_params as $i => $type_param) {
            $this->prevent_template_covariance = $this->source instanceof MethodAnalyzer
                && $this->source->getMethodName() !== '__construct'
                && empty($expected_param_covariants[$i]);

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
                    } else {
                        $template_result->lower_bounds[$expected_template_name][$defining_class][]
                            = new TemplateBound($type_param);
                    }
                }
            }
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
            null,
            null,
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
                    && $method_storage->mutation_free
                    && !$method_storage->mutation_free_inferred
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
