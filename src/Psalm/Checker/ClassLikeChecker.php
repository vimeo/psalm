<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\MissingDependency;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Provider\FileReferenceProvider;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;

abstract class ClassLikeChecker extends SourceChecker implements StatementsSource
{
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PRIVATE = 3;

    /**
     * @var array
     */
    public static $SPECIAL_TYPES = [
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

    /**
     * @var array
     */
    public static $GETTYPE_TYPES = [
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'resource' => true,
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

    /** @var FileChecker */
    public $file_checker;

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
     * @param StatementsSource              $source
     * @param string                        $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        $this->class = $class;
        $this->source = $source;
        $this->file_checker = $source->getFileChecker();
        $this->fq_class_name = $fq_class_name;

        $this->storage = $this->file_checker->project_checker->classlike_storage_provider->get($fq_class_name);

        if ($this->storage->location) {
            $storage_file_path = $this->storage->location->file_path;
            $source_file_path = $this->source->getCheckedFilePath();

            if (!Config::getInstance()->use_case_sensitive_file_names) {
                $storage_file_path = strtolower($storage_file_path);
                $source_file_path = strtolower($source_file_path);
            }
        }
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
        $project_checker = $this->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name->name) === strtolower($method_name)
            ) {
                $method_id = $this->fq_class_name . '::' . $stmt->name->name;

                if ($project_checker->canCacheCheckers()) {
                    $method_checker = $codebase->methods->getCachedChecker($method_id);

                    if (!$method_checker) {
                        $method_checker = new MethodChecker($stmt, $this);
                        $codebase->methods->cacheChecker($method_id, $method_checker);
                    }
                } else {
                    $method_checker = new MethodChecker($stmt, $this);
                }

                $method_checker->analyze($context, null, true);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    $trait_file_checker = $project_checker->getFileCheckerForClassLike($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_aliases = $codebase->classlikes->getTraitAliases($fq_trait_name);
                    $trait_checker = new TraitChecker(
                        $trait_node,
                        $trait_file_checker,
                        $fq_trait_name,
                        $trait_aliases
                    );

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                            strtolower($trait_stmt->name->name) === strtolower($method_name)
                        ) {
                            $method_checker = new MethodChecker($trait_stmt, $trait_checker);

                            $actual_method_id = (string)$method_checker->getMethodId();

                            if ($context->self && $context->self !== $this->fq_class_name) {
                                $analyzed_method_id = (string)$method_checker->getMethodId($context->self);
                                $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

                                if ($actual_method_id !== $declaring_method_id) {
                                    break;
                                }
                            }

                            $method_checker->analyze($context, null, true);
                        }
                    }
                }
            }
        }
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
        $fq_class_name,
        CodeLocation $code_location,
        array $suppressed_issues,
        $inferred = true
    ) {
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

        $project_checker = $statements_source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable'], true)) {
            return true;
        }

        if (preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|resource|object|numeric|mixed)$/i',
            $fq_class_name
        )
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

        $class_exists = $codebase->classExists($fq_class_name);
        $interface_exists = $codebase->interfaceExists($fq_class_name);

        if (!$class_exists && !$interface_exists) {
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

            return null;
        }

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        foreach ($class_storage->invalid_dependencies as $dependency_class_name) {
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

        if ($project_checker->getCodeBase()->collect_references && !$inferred) {
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$code_location->file_path][] = $code_location;
        }

        if (($class_exists && !$codebase->classHasCorrectCasing($fq_class_name)) ||
            ($interface_exists && !$codebase->interfaceHasCorrectCasing($fq_class_name))
        ) {
            if ($codebase->classlikes->isUserDefined($fq_class_name)) {
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

        FileReferenceProvider::addFileReferenceToClass(
            $code_location->file_path,
            strtolower($fq_class_name)
        );

        if (!$inferred) {
            $plugin_classes = $codebase->config->after_classlike_exists_checks;

            if ($plugin_classes) {
                $file_manipulations = [];

                foreach ($plugin_classes as $plugin_fq_class_name) {
                    $plugin_fq_class_name::afterClassLikeExistsCheck(
                        $statements_source,
                        $fq_class_name,
                        $code_location,
                        $file_manipulations
                    );
                }

                if ($file_manipulations) {
                    /** @psalm-suppress MixedTypeCoercion */
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
    public static function getFQCLNFromNameObject(PhpParser\Node\Name $class_name, Aliases $aliases)
    {
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
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->source->getNamespace();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source instanceof NamespaceChecker || $this->source instanceof FileChecker) {
            return $this->source->getAliasedClassesFlipped();
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
                return Type::getBool();

            case 'integer':
                return Type::getInt();

            case 'double':
                return Type::getFloat();

            case 'string':
                return Type::getString();

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
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     * @param  bool             $emit_issues
     *
     * @return bool|null
     */
    public static function checkPropertyVisibility(
        $property_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        $emit_issues = true
    ) {
        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $declaring_property_class = $codebase->properties->getDeclaringClassForProperty($property_id);
        $appearing_property_class = $codebase->properties->getAppearingClassForProperty($property_id);

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new \UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id
            );
        }

        list(, $property_name) = explode('::$', (string)$property_id);

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $calling_context) {
            return $emit_issues ? null : true;
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_property_class === $source->getFQCLN()) {
            return $emit_issues ? null : true;
        }

        $class_storage = $project_checker->classlike_storage_provider->get($declaring_property_class);

        if (!isset($class_storage->properties[$property_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        $storage = $class_storage->properties[$property_name];

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return $emit_issues ? null : true;

            case self::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_property_class !== $calling_context) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                return $emit_issues ? null : true;

            case self::VISIBILITY_PROTECTED:
                if ($appearing_property_class === $calling_context) {
                    return null;
                }

                if (!$calling_context) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                if ($codebase->classExtends($appearing_property_class, $calling_context)) {
                    return $emit_issues ? null : true;
                }

                if (!$codebase->classExtends($calling_context, $appearing_property_class)) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
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
    public static function getClassesForFile(ProjectChecker $project_checker, $file_path)
    {
        try {
            return $project_checker->file_storage_provider->get($file_path)->classlikes_in_file;
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this->file_checker;
    }
}
