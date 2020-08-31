<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\MissingDependency;
use Psalm\Issue\PsalmInternalError;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedDocblockClass;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use function strtolower;
use function preg_replace;
use function in_array;
use function preg_match;
use function explode;
use function array_pop;
use function implode;
use function gettype;

/**
 * @internal
 */
abstract class ClassLikeAnalyzer extends SourceAnalyzer implements StatementsSource
{
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PRIVATE = 3;

    const SPECIAL_TYPES = [
        'int' => 'int',
        'string' => 'string',
        'float' => 'float',
        'bool' => 'bool',
        'false' => 'false',
        'object' => 'object',
        'empty' => 'empty',
        'callable' => 'callable',
        'array' => 'array',
        'iterable' => 'iterable',
        'null' => 'null',
        'mixed' => 'mixed',
    ];

    const GETTYPE_TYPES = [
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

    /**
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

    /**
     * @var StatementsSource
     */
    protected $source;

    /** @var FileAnalyzer */
    public $file_analyzer;

    /**
     * @var string
     */
    protected $fq_class_name;

    /**
     * The parent class
     *
     * @var string|null
     */
    protected $parent_fq_class_name;

    /**
     * @var PhpParser\Node\Stmt[]
     */
    protected $leftover_stmts = [];

    /** @var ClassLikeStorage */
    protected $storage;

    /**
     * @param PhpParser\Node\Stmt\ClassLike $class
     * @param SourceAnalyzer                $source
     * @param string                        $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, SourceAnalyzer $source, $fq_class_name)
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
        $this->source = null;
        $this->file_analyzer = null;
    }

    /**
     * @param  string       $method_name
     * @param  Context      $context
     *
     * @return void
     */
    public function getMethodMutations(
        $method_name,
        Context $context
    ) {
        $project_analyzer = $this->getFileAnalyzer()->project_analyzer;
        $codebase = $project_analyzer->getCodebase();

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                $method_analyzer = new MethodAnalyzer($stmt, $this);

                $method_analyzer->analyze($context, new \Psalm\Internal\Provider\NodeDataProvider(), null, true);

                $context->clauses = [];
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
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
                        $trait_aliases
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
                                new \Psalm\Internal\Provider\NodeDataProvider(),
                                null,
                                true
                            );
                        }
                    }

                    $trait_file_analyzer->clearSourceBeforeDestruction();
                }
            }
        }
    }

    public function getFunctionLikeAnalyzer(string $method_name) : ?FunctionLikeAnalyzer
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
     * @param  string           $fq_class_name
     * @param  array<string>    $suppressed_issues
     * @param  bool             $inferred - whether or not the type was inferred
     *
     * @return bool|null
     */
    public static function checkFullyQualifiedClassLikeName(
        StatementsSource $statements_source,
        string $fq_class_name,
        CodeLocation $code_location,
        ?string $calling_fq_class_name,
        ?string $calling_method_id,
        array $suppressed_issues,
        bool $inferred = true,
        bool $allow_trait = false,
        bool $allow_interface = true,
        bool $from_docblock = false
    ) {
        $codebase = $statements_source->getCodebase();
        if (empty($fq_class_name)) {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class or interface <empty string> does not exist',
                    $code_location,
                    'empty string'
                ),
                $suppressed_issues
            )) {
                return false;
            }

            return;
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable', 'self', 'static', 'parent'], true)) {
            return true;
        }

        if (preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|object|mixed)$/i',
            $fq_class_name
        ) || strtolower($fq_class_name) === 'resource'
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (IssueBuffer::accepts(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    $code_location,
                    $class_name
                ),
                $suppressed_issues
            )) {
                // fall through
            }

            return null;
        }

        $class_exists = $codebase->classlikes->classExists(
            $fq_class_name,
            !$inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id
        );
        $interface_exists = $codebase->classlikes->interfaceExists(
            $fq_class_name,
            !$inferred ? $code_location : null,
            $calling_fq_class_name,
            $calling_method_id
        );

        if (!$class_exists && !$interface_exists) {
            if (!$allow_trait || !$codebase->classlikes->traitExists($fq_class_name, $code_location)) {
                if ($from_docblock) {
                    if (IssueBuffer::accepts(
                        new UndefinedDocblockClass(
                            'Docblock-defined class or interface ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedClass(
                            'Class or interface ' . $fq_class_name . ' does not exist',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
            }

            return null;
        } elseif ($interface_exists && !$allow_interface) {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class ' . $fq_class_name . ' does not exist',
                    $code_location,
                    $fq_class_name
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        $aliased_name = $codebase->classlikes->getUnAliasedName(
            $fq_class_name
        );

        try {
            $class_storage = $codebase->classlike_storage_provider->get($aliased_name);
        } catch (\InvalidArgumentException $e) {
            if (!$inferred) {
                throw $e;
            }

            return null;
        }

        foreach ($class_storage->invalid_dependencies as $dependency_class_name) {
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
                    $fq_class_name
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        if (!$inferred) {
            if (($class_exists && !$codebase->classHasCorrectCasing($fq_class_name)) ||
                ($interface_exists && !$codebase->interfaceHasCorrectCasing($fq_class_name))
            ) {
                if ($codebase->classlikes->isUserDefined(strtolower($aliased_name))) {
                    if (IssueBuffer::accepts(
                        new InvalidClass(
                            'Class or interface ' . $fq_class_name . ' has wrong casing',
                            $code_location,
                            $fq_class_name
                        ),
                        $suppressed_issues
                    )) {
                        // fall through here
                    }
                }
            }
        }

        if (!$inferred) {
            $plugin_classes = $codebase->config->after_classlike_exists_checks;

            if ($plugin_classes) {
                $file_manipulations = [];

                foreach ($plugin_classes as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterClassLikeExistenceCheck(
                        $fq_class_name,
                        $code_location,
                        $statements_source,
                        $codebase,
                        $file_manipulations
                    );
                }

                if ($file_manipulations) {
                    FileManipulationBuffer::add($code_location->file_path, $file_manipulations);
                }
            }
        }

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name      $class_name
     * @param  Aliases                  $aliases
     *
     * @return string
     */
    public static function getFQCLNFromNameObject(
        PhpParser\Node\Name $class_name,
        Aliases $aliases
    ) {
        /** @var string|null */
        $resolved_name = $class_name->getAttribute('resolvedName');

        if ($resolved_name) {
            return $resolved_name;
        }

        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        if (in_array($class_name->parts[0], ['self', 'static', 'parent'], true)) {
            return $class_name->parts[0];
        }

        return Type::getFQCLNFromString(
            implode('\\', $class_name->parts),
            $aliases
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable()
    {
        if ($this->source instanceof NamespaceAnalyzer || $this->source instanceof FileAnalyzer) {
            return $this->source->getAliasedClassesFlippedReplaceable();
        }

        return [];
    }

    /**
     * @return string
     */
    public function getFQCLN()
    {
        return $this->fq_class_name;
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return $this->class->name ? $this->class->name->name : null;
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap()
    {
        return $this->storage->template_types;
    }

    /**
     * @return string|null
     */
    public function getParentFQCLN()
    {
        return $this->parent_fq_class_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * Gets the Psalm type from a particular value
     *
     * @param  mixed $value
     *
     * @return Type\Union
     */
    public static function getTypeFromValue($value)
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
     * @param  string           $property_id
     * @param  string|null      $calling_context
     * @param  SourceAnalyzer   $source
     * @param  CodeLocation     $code_location
     * @param  string[]         $suppressed_issues
     * @param  bool             $emit_issues
     *
     * @return bool|null
     */
    public static function checkPropertyVisibility(
        $property_id,
        Context $context,
        SourceAnalyzer $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        $emit_issues = true
    ) {
        list($fq_class_name, $property_name) = explode('::$', (string)$property_id);

        $codebase = $source->getCodebase();

        if ($codebase->properties->property_visibility_provider->has($fq_class_name)) {
            $property_visible = $codebase->properties->property_visibility_provider->isPropertyVisible(
                $source,
                $fq_class_name,
                $property_name,
                true,
                $context,
                $code_location
            );

            if ($property_visible !== null) {
                return $property_visible;
            }
        }

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
            $property_id,
            true
        );
        $appearing_property_class = $codebase->properties->getAppearingClassForProperty(
            $property_id,
            true
        );

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new \UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id
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
            throw new \UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        $storage = $class_storage->properties[$property_name];

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return $emit_issues ? null : true;

            case self::VISIBILITY_PRIVATE:
                if (!$context->self || $appearing_property_class !== $context->self) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }

                return $emit_issues ? null : true;

            case self::VISIBILITY_PROTECTED:
                if ($appearing_property_class === $context->self) {
                    return null;
                }

                if (!$context->self) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }

                if ($codebase->classExtends($appearing_property_class, $context->self)) {
                    return $emit_issues ? null : true;
                }

                if (!$codebase->classExtends($context->self, $appearing_property_class)) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id . ' from context ' . $context->self,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        // fall through
                    }

                    return null;
                }
        }

        return $emit_issues ? null : true;
    }

    /**
     * @param   string $file_path
     *
     * @return  array<string, string>
     */
    public static function getClassesForFile(Codebase $codebase, $file_path)
    {
        try {
            return $codebase->file_storage_provider->get($file_path)->classlikes_in_file;
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this->file_analyzer;
    }
}
