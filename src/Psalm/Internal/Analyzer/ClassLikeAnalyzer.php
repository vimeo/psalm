<?php

namespace Psalm\Internal\Analyzer;

use InvalidArgumentException;
use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\InvalidTemplateParam;
use Psalm\Issue\MissingDependency;
use Psalm\Issue\MissingTemplateParam;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\TooManyTemplateParams;
use Psalm\Issue\UndefinedAttributeClass;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeExistenceCheckEvent;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_keys;
use function array_pop;
use function array_search;
use function count;
use function explode;
use function gettype;
use function in_array;
use function preg_match;
use function preg_replace;
use function strtolower;

/**
 * @internal
 */
abstract class ClassLikeAnalyzer extends SourceAnalyzer
{
    public const VISIBILITY_PUBLIC = 1;
    public const VISIBILITY_PROTECTED = 2;
    public const VISIBILITY_PRIVATE = 3;

    public const SPECIAL_TYPES = [
        'int' => 'int',
        'string' => 'string',
        'float' => 'float',
        'bool' => 'bool',
        'false' => 'false',
        'object' => 'object',
        'never' => 'never',
        'callable' => 'callable',
        'array' => 'array',
        'iterable' => 'iterable',
        'null' => 'null',
        'mixed' => 'mixed',
    ];

    public const GETTYPE_TYPES = [
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'resource' => true,
        'resource (closed)' => true,
        'NULL' => true,
        'unknown type' => true,
    ];

    protected PhpParser\Node\Stmt\ClassLike $class;

    public FileAnalyzer $file_analyzer;

    protected string $fq_class_name;

    /**
     * The parent class
     */
    protected ?string $parent_fq_class_name = null;

    protected ClassLikeStorage $storage;

    public function __construct(PhpParser\Node\Stmt\ClassLike $class, SourceAnalyzer $source, string $fq_class_name)
    {
        $this->class = $class;
        $this->source = $source;
        $this->file_analyzer = $source->getFileAnalyzer();
        $this->fq_class_name = $fq_class_name;
        $codebase = $source->getCodebase();
        $this->storage = $codebase->classlike_storage_provider->get($fq_class_name);
    }

    public function __destruct()
    {
        unset($this->source);
        unset($this->file_analyzer);
    }

    public function getMethodMutations(
        string $method_name,
        Context $context
    ): void {
        $project_analyzer = $this->getFileAnalyzer()->project_analyzer;
        $codebase = $project_analyzer->getCodebase();

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $method_analyzer->analyze($context, new NodeDataProvider(), null, true);

                $context->clauses = [];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases(),
                    );

                    $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name);
                    $trait_aliases = $trait_storage->aliases;

                    if ($trait_aliases === null) {
                        continue;
                    }

                    $trait_analyzer = new TraitAnalyzer(
                        $trait_node,
                        $trait_file_analyzer,
                        $fq_trait_name,
                        $trait_aliases,
                    );

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                            strtolower($trait_stmt->name->name) === strtolower($method_name)
                        ) {
                            $method_analyzer = new MethodAnalyzer($trait_stmt, $trait_analyzer);

                            $actual_method_id = $method_analyzer->getMethodId();

                            if ($context->self && $context->self !== $this->fq_class_name) {
                                $analyzed_method_id = $method_analyzer->getMethodId($context->self);
                                $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

                                if ((string) $actual_method_id !== (string) $declaring_method_id) {
                                    break;
                                }
                            }

                            $method_analyzer->analyze(
                                $context,
                                new NodeDataProvider(),
                                null,
                                true,
                            );
                        }
                    }

                    $trait_file_analyzer->clearSourceBeforeDestruction();
                }
            }
        }
    }

    public function getFunctionLikeAnalyzer(string $method_name): ?MethodAnalyzer
    {
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                return new MethodAnalyzer($stmt, $this);
            }
        }

        return null;
    }

    /**
     * @param  array<string>    $suppressed_issues
     */
    public static function checkFullyQualifiedClassLikeName(
        StatementsSource $statements_source,
        string $fq_class_name,
        CodeLocation $code_location,
        ?string $calling_fq_class_name,
        ?string $calling_method_id,
        array $suppressed_issues,
        ?ClassLikeNameOptions $options = null,
        bool $check_classes = true
    ): ?bool {
        if ($options === null) {
            $options = new ClassLikeNameOptions();
        }

        $codebase = $statements_source->getCodebase();
        if ($fq_class_name === '') {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class or interface <empty string> does not exist',
                    $code_location,
                    'empty string',
                ),
                $suppressed_issues,
            )) {
                return false;
            }

            return null;
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name, 1);

        if (in_array($fq_class_name, ['callable', 'iterable', 'self', 'static', 'parent'], true)) {
            return true;
        }

        if (preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|object|mixed)$/i',
            $fq_class_name,
        ) || strtolower($fq_class_name) === 'resource'
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            IssueBuffer::maybeAdd(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    $code_location,
                    $class_name,
                ),
                $suppressed_issues,
            );

            return null;
        }

        $class_exists = $codebase->classlikes->classExists(
            $fq_class_name,
            !$options->inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id,
        );

        $interface_exists = $codebase->classlikes->interfaceExists(
            $fq_class_name,
            !$options->inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id,
        );

        $enum_exists = $codebase->classlikes->enumExists(
            $fq_class_name,
            !$options->inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id,
        );

        if (!$class_exists
            && !($interface_exists && $options->allow_interface)
            && !($enum_exists && $options->allow_enum)
        ) {
            if (!$check_classes) {
                return null;
            }
            if (!$options->allow_trait || !$codebase->classlikes->traitExists($fq_class_name, $code_location)) {
                if ($options->from_docblock) {
                    if (IssueBuffer::accepts(
                        new UndefinedDocblockClass(
                            'Docblock-defined class, interface or enum named ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name,
                        ),
                        $suppressed_issues,
                    )) {
                        return false;
                    }
                } elseif ($options->from_attribute) {
                    if (IssueBuffer::accepts(
                        new UndefinedAttributeClass(
                            'Attribute class ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name,
                        ),
                        $suppressed_issues,
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Class, interface or enum named ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name,
                        ),
                        $suppressed_issues,
                    )) {
                        return false;
                    }
                }
            }

            return null;
        }

        $aliased_name = $codebase->classlikes->getUnAliasedName(
            $fq_class_name,
        );

        try {
            $class_storage = $codebase->classlike_storage_provider->get($aliased_name);
        } catch (InvalidArgumentException $e) {
            if (!$options->inferred) {
                throw $e;
            }

            return null;
        }

        foreach ($class_storage->invalid_dependencies as $dependency_class_name => $_) {
            // if the implemented/extended class is stubbed, it may not yet have
            // been hydrated
            if ($codebase->classlike_storage_provider->has($dependency_class_name)) {
                continue;
            }

            if (IssueBuffer::accepts(
                new MissingDependency(
                    $fq_class_name . ' depends on class or interface '
                        . $dependency_class_name . ' that does not exist',
                    $code_location,
                    $fq_class_name,
                ),
                $suppressed_issues,
            )) {
                return false;
            }
        }

        if (!$options->inferred) {
            if (($class_exists && !$codebase->classHasCorrectCasing($fq_class_name))
                || ($interface_exists && !$codebase->interfaceHasCorrectCasing($fq_class_name))
                || ($enum_exists && !$codebase->classlikes->enumHasCorrectCasing($fq_class_name))
            ) {
                IssueBuffer::maybeAdd(
                    new InvalidClass(
                        'Class, interface or enum ' . $fq_class_name . ' has wrong casing',
                        $code_location,
                        $fq_class_name,
                    ),
                    $suppressed_issues,
                );
            }
        }

        if (!$options->inferred) {
            $event = new AfterClassLikeExistenceCheckEvent(
                $fq_class_name,
                $code_location,
                $statements_source,
                $codebase,
                [],
            );

            $codebase->config->eventDispatcher->dispatchAfterClassLikeExistenceCheck($event);

            $file_manipulations = $event->getFileReplacements();
            if ($file_manipulations) {
                FileManipulationBuffer::add($code_location->file_path, $file_manipulations);
            }
        }

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     */
    public static function getFQCLNFromNameObject(
        PhpParser\Node\Name $class_name,
        Aliases $aliases
    ): string {
        /** @var string|null */
        $resolved_name = $class_name->getAttribute('resolvedName');

        if ($resolved_name) {
            return $resolved_name;
        }

        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return $class_name->toString();
        }

        if (in_array($class_name->getFirst(), ['self', 'static', 'parent'], true)) {
            return $class_name->getFirst();
        }

        return Type::getFQCLNFromString(
            $class_name->toString(),
            $aliases,
        );
    }

    /**
     * @psalm-mutation-free
     * @return array<lowercase-string, string>
     */
    public function getAliasedClassesFlipped(): array
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @psalm-mutation-free
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable(): array
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlippedReplaceable();
        }

        return [];
    }

    /** @psalm-mutation-free */
    public function getFQCLN(): string
    {
        return $this->fq_class_name;
    }

    /** @psalm-mutation-free */
    public function getClassName(): ?string
    {
        return $this->class->name->name ?? null;
    }

    /**
     * @psalm-mutation-free
     * @return array<string, array<string, Union>>|null
     */
    public function getTemplateTypeMap(): ?array
    {
        return $this->storage->template_types;
    }

    /** @psalm-mutation-free */
    public function getParentFQCLN(): ?string
    {
        return $this->parent_fq_class_name;
    }

    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * Gets the Psalm type from a particular value
     *
     * @param  mixed $value
     */
    public static function getTypeFromValue($value): Union
    {
        switch (gettype($value)) {
            case 'boolean':
                if ($value) {
                    return Type::getTrue();
                }

                return Type::getFalse();

            case 'integer':
                return Type::getInt(false, $value);

            case 'double':
                return Type::getFloat($value);

            case 'string':
                return Type::getString($value);

            case 'array':
                return Type::getArray();

            case 'NULL':
                return Type::getNull();

            default:
                return Type::getMixed();
        }
    }

    /**
     * @param  string[]         $suppressed_issues
     */
    public static function checkPropertyVisibility(
        string $property_id,
        Context $context,
        SourceAnalyzer $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        bool $emit_issues = true
    ): ?bool {
        [$fq_class_name, $property_name] = explode('::$', $property_id);

        $codebase = $source->getCodebase();

        if ($codebase->properties->property_visibility_provider->has($fq_class_name)) {
            $property_visible = $codebase->properties->property_visibility_provider->isPropertyVisible(
                $source,
                $fq_class_name,
                $property_name,
                true,
                $context,
                $code_location,
            );

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true,
        );
        $appearing_property_class = $codebase->properties->getAppearingClassForProperty(
            $property_id,
            true,
        );

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id,
            );
        }

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $context->self) {
            return $emit_issues ? null : true;
        }

        if ($source->getSource() instanceof TraitAnalyzer
            && strtolower($declaring_property_class) === strtolower((string) $source->getFQCLN())
        ) {
            return $emit_issues ? null : true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($declaring_property_class);

        if (!isset($class_storage->properties[$property_name])) {
            throw new UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        $storage = $class_storage->properties[$property_name];

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return $emit_issues ? null : true;

            case self::VISIBILITY_PRIVATE:
                if ($emit_issues) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $context->self,
                            $code_location,
                        ),
                        $suppressed_issues,
                    );
                }

                return null;
            case self::VISIBILITY_PROTECTED:
                if (!$context->self) {
                    if ($emit_issues) {
                        IssueBuffer::maybeAdd(
                            new InaccessibleProperty(
                                'Cannot access protected property ' . $property_id,
                                $code_location,
                            ),
                            $suppressed_issues,
                        );
                    }

                    return null;
                }

                if ($codebase->classExtends($appearing_property_class, $context->self)) {
                    return $emit_issues ? null : true;
                }

                if (!$codebase->classExtends($context->self, $appearing_property_class)) {
                    if ($emit_issues) {
                        IssueBuffer::maybeAdd(
                            new InaccessibleProperty(
                                'Cannot access protected property ' . $property_id . ' from context ' . $context->self,
                                $code_location,
                            ),
                            $suppressed_issues,
                        );
                    }

                    return null;
                }
        }

        return $emit_issues ? null : true;
    }

    protected function checkTemplateParams(
        Codebase $codebase,
        ClassLikeStorage $storage,
        ClassLikeStorage $parent_storage,
        CodeLocation $code_location,
        int $given_param_count
    ): void {
        $expected_param_count = $parent_storage->template_types === null
            ? 0
            : count($parent_storage->template_types);

        if ($expected_param_count > $given_param_count) {
            IssueBuffer::maybeAdd(
                new MissingTemplateParam(
                    $storage->name . ' has missing template params when extending ' . $parent_storage->name
                        . ', expecting ' . $expected_param_count,
                    $code_location,
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues(),
            );
        } elseif ($expected_param_count < $given_param_count) {
            IssueBuffer::maybeAdd(
                new TooManyTemplateParams(
                    $storage->name . ' has too many template params when extending ' . $parent_storage->name
                        . ', expecting ' . $expected_param_count,
                    $code_location,
                ),
                $storage->suppressed_issues + $this->getSuppressedIssues(),
            );
        }

        $storage_param_count = ($storage->template_types ? count($storage->template_types) : 0);

        if ($parent_storage->enforce_template_inheritance
            && $expected_param_count !== $storage_param_count
        ) {
            if ($expected_param_count > $storage_param_count) {
                IssueBuffer::maybeAdd(
                    new MissingTemplateParam(
                        $storage->name . ' requires the same number of template params as ' . $parent_storage->name
                            . ' but saw ' . $storage_param_count,
                        $code_location,
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new TooManyTemplateParams(
                        $storage->name . ' requires the same number of template params as ' . $parent_storage->name
                            . ' but saw ' . $storage_param_count,
                        $code_location,
                    ),
                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                );
            }
        }

        if ($parent_storage->template_types && $storage->template_extended_params) {
            $i = 0;

            $previous_extended = [];

            foreach ($parent_storage->template_types as $template_name => $type_map) {
                // declares the variables
                foreach ($type_map as $declaring_class => $template_type) {
                }

                if (isset($storage->template_extended_params[$parent_storage->name][$template_name])) {
                    $extended_type = $storage->template_extended_params[$parent_storage->name][$template_name];

                    if (isset($parent_storage->template_covariants[$i])
                        && !$parent_storage->template_covariants[$i]
                    ) {
                        foreach ($extended_type->getAtomicTypes() as $t) {
                            if ($t instanceof TTemplateParam
                                && $storage->template_types
                                && $storage->template_covariants
                                && ($local_offset
                                    = array_search($t->param_name, array_keys($storage->template_types), true))
                                    !== false
                                && !empty($storage->template_covariants[$local_offset])
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend an invariant template param ' . $template_name
                                            . ' into a covariant context',
                                        $code_location,
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                                );
                            }
                        }
                    }

                    if ($parent_storage->enforce_template_inheritance) {
                        foreach ($extended_type->getAtomicTypes() as $t) {
                            if (!$t instanceof TTemplateParam
                                || !isset($storage->template_types[$t->param_name])
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend a strictly-enforced parent template param '
                                            . $template_name
                                            . ' with a non-template type',
                                        $code_location,
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                                );
                            } elseif ($storage->template_types[$t->param_name][$storage->name]->getId()
                                !== $template_type->getId()
                            ) {
                                IssueBuffer::maybeAdd(
                                    new InvalidTemplateParam(
                                        'Cannot extend a strictly-enforced parent template param '
                                            . $template_name
                                            . ' with constraint ' . $template_type->getId()
                                            . ' with a child template param ' . $t->param_name
                                            . ' with different constraint '
                                            . $storage->template_types[$t->param_name][$storage->name]->getId(),
                                        $code_location,
                                    ),
                                    $storage->suppressed_issues + $this->getSuppressedIssues(),
                                );
                            }
                        }
                    }

                    if (!$template_type->isMixed()) {
                        $template_result = new TemplateResult(
                            $previous_extended ?: [],
                            [],
                        );

                        $template_type_copy = TemplateStandinTypeReplacer::replace(
                            $template_type,
                            $template_result,
                            $codebase,
                            null,
                            $extended_type,
                            null,
                            null,
                        );

                        if (!UnionTypeComparator::isContainedBy($codebase, $extended_type, $template_type_copy)) {
                            IssueBuffer::maybeAdd(
                                new InvalidTemplateParam(
                                    'Extended template param ' . $template_name
                                        . ' expects type ' . $template_type_copy->getId()
                                        . ', type ' . $extended_type->getId() . ' given',
                                    $code_location,
                                ),
                                $storage->suppressed_issues + $this->getSuppressedIssues(),
                            );
                        } else {
                            $previous_extended[$template_name] = [
                                $declaring_class => $extended_type,
                            ];
                        }
                    } else {
                        $previous_extended[$template_name] = [
                            $declaring_class => $extended_type,
                        ];
                    }
                }

                $i++;
            }
        }
    }

    /**
     * @return  array<string, string>
     */
    public static function getClassesForFile(Codebase $codebase, string $file_path): array
    {
        try {
            return $codebase->file_storage_provider->get($file_path)->classlikes_in_file;
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    public function getFileAnalyzer(): FileAnalyzer
    {
        return $this->file_analyzer;
    }
}
