<?php
namespace Psalm\Internal\Codebase;

use PhpParser;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedParam;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\UnusedClass;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedProperty;
use Psalm\IssueBuffer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use ReflectionProperty;

/**
 * @internal
 *
 * Handles information about classes, interfaces and traits
 */
class ClassLikes
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var array<string, bool>
     */
    private $existing_classlikes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classes_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_classes = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_interfaces = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits_lc = [];

    /**
     * @var array<string, bool>
     */
    private $existing_traits = [];

    /**
     * @var array<string, PhpParser\Node\Stmt\Trait_>
     */
    private $trait_nodes = [];

    /**
     * @var array<string, Aliases>
     */
    private $trait_aliases = [];

    /**
     * @var array<string, int>
     */
    private $classlike_references = [];

    /**
     * @var array<string, string>
     */
    private $classlike_aliases = [];

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * @param bool $debug_output
     */
    public function __construct(
        Config $config,
        ClassLikeStorageProvider $storage_provider,
        Scanner $scanner
    ) {
        $this->config = $config;
        $this->classlike_storage_provider = $storage_provider;
        $this->scanner = $scanner;

        $this->collectPredefinedClassLikes();
    }

    /**
     * @return void
     */
    private function collectPredefinedClassLikes()
    {
        /** @var array<int, string> */
        $predefined_classes = get_declared_classes();

        foreach ($predefined_classes as $predefined_class) {
            $predefined_class = preg_replace('/^\\\/', '', $predefined_class);
            $reflection_class = new \ReflectionClass($predefined_class);

            if (!$reflection_class->isUserDefined()) {
                $predefined_class_lc = strtolower($predefined_class);
                $this->existing_classlikes_lc[$predefined_class_lc] = true;
                $this->existing_classes_lc[$predefined_class_lc] = true;
            }
        }

        /** @var array<int, string> */
        $predefined_interfaces = get_declared_interfaces();

        foreach ($predefined_interfaces as $predefined_interface) {
            $predefined_interface = preg_replace('/^\\\/', '', $predefined_interface);
            $reflection_class = new \ReflectionClass($predefined_interface);

            if (!$reflection_class->isUserDefined()) {
                $predefined_interface_lc = strtolower($predefined_interface);
                $this->existing_classlikes_lc[$predefined_interface_lc] = true;
                $this->existing_interfaces_lc[$predefined_interface_lc] = true;
            }
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedClassName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_classes[$fq_class_name] = true;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedInterfaceName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_interfaces_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_traits_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces[$fq_class_name] = true;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedTraitName($fq_class_name, $file_path = null)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;
        $this->existing_traits_lc[$fq_class_name_lc] = true;
        $this->existing_classes_lc[$fq_class_name_lc] = false;
        $this->existing_interfaces_lc[$fq_class_name_lc] = false;
        $this->existing_traits[$fq_class_name] = true;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param string        $fq_class_name_lc
     * @param string|null   $file_path
     *
     * @return void
     */
    public function addFullyQualifiedClassLikeName($fq_class_name_lc, $file_path = null)
    {
        $this->existing_classlikes_lc[$fq_class_name_lc] = true;

        if ($file_path) {
            $this->scanner->setClassLikeFilePath($fq_class_name_lc, $file_path);
        }
    }

    /**
     * @param string        $fq_class_name_lc
     *
     * @return bool
     */
    public function hasFullyQualifiedClassLikeName($fq_class_name_lc)
    {
        return isset($this->existing_classlikes_lc[$fq_class_name_lc]);
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedClassName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if (!isset($this->existing_classes_lc[$fq_class_name_lc])
            || !$this->existing_classes_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc] === true
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_classes_lc[$fq_class_name_lc])) {
                    $this->existing_classes_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_classes_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedInterfaceName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])
            || !$this->existing_interfaces_lc[$fq_class_name_lc]
            || !$this->classlike_storage_provider->has($fq_class_name_lc)
        ) {
            if ((
                !isset($this->existing_classes_lc[$fq_class_name_lc])
                    || $this->existing_classes_lc[$fq_class_name_lc] === true
                )
                && !$this->classlike_storage_provider->has($fq_class_name_lc)
            ) {
                if (!isset($this->existing_interfaces_lc[$fq_class_name_lc])) {
                    $this->existing_interfaces_lc[$fq_class_name_lc] = false;

                    return false;
                }

                return $this->existing_interfaces_lc[$fq_class_name_lc];
            }

            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     *
     * @return bool
     */
    public function hasFullyQualifiedTraitName($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_aliases[$fq_class_name_lc])) {
            $fq_class_name_lc = strtolower($this->classlike_aliases[$fq_class_name_lc]);
        }

        if (!isset($this->existing_traits_lc[$fq_class_name_lc]) ||
            !$this->existing_traits_lc[$fq_class_name_lc]
        ) {
            return false;
        }

        if ($this->collect_references) {
            if (!isset($this->classlike_references[$fq_class_name_lc])) {
                $this->classlike_references[$fq_class_name_lc] = 0;
            }

            ++$this->classlike_references[$fq_class_name_lc];
        }

        return true;
    }

    /**
     * Check whether a class/interface exists
     *
     * @param  string          $fq_class_name
     * @param  CodeLocation $code_location
     *
     * @return bool
     */
    public function classOrInterfaceExists(
        $fq_class_name,
        CodeLocation $code_location = null
    ) {
        if (!$this->classExists($fq_class_name) && !$this->interfaceExists($fq_class_name)) {
            return false;
        }

        if ($this->collect_references && $code_location) {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$code_location->file_path][] = $code_location;
        }

        return true;
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string       $fq_class_name
     *
     * @return bool
     */
    public function classExists($fq_class_name)
    {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        return $this->hasFullyQualifiedClassName($fq_class_name);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public function classExtends($fq_class_name, $possible_parent)
    {
        $fq_class_name = strtolower($fq_class_name);

        if ($fq_class_name === 'generator') {
            return false;
        }

        $fq_class_name = $this->classlike_aliases[$fq_class_name] ?? $fq_class_name;

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        return isset($class_storage->parent_classes[strtolower($possible_parent)]);
    }

    /**
     * Check whether a class implements an interface
     *
     * @param  string       $fq_class_name
     * @param  string       $interface
     *
     * @return bool
     */
    public function classImplements($fq_class_name, $interface)
    {
        $interface_id = strtolower($interface);

        $fq_class_name = strtolower($fq_class_name);

        if ($interface_id === 'callable' && $fq_class_name === 'closure') {
            return true;
        }

        if ($interface_id === 'traversable' && $fq_class_name === 'generator') {
            return true;
        }

        if ($interface_id === 'arrayaccess' && $fq_class_name === 'domnodelist') {
            return true;
        }

        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[$interface_id])
            || isset(ClassLikeAnalyzer::SPECIAL_TYPES[$fq_class_name])
        ) {
            return false;
        }

        if (isset($this->classlike_aliases[$fq_class_name])) {
            $fq_class_name = $this->classlike_aliases[$fq_class_name];
        }

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        return isset($class_storage->class_implements[$interface_id]);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return bool
     */
    public function interfaceExists($fq_interface_name)
    {
        if (isset(ClassLikeAnalyzer::SPECIAL_TYPES[strtolower($fq_interface_name)])) {
            return false;
        }

        return $this->hasFullyQualifiedInterfaceName($fq_interface_name);
    }

    /**
     * @param  string         $interface_name
     * @param  string         $possible_parent
     *
     * @return bool
     */
    public function interfaceExtends($interface_name, $possible_parent)
    {
        return isset($this->getParentInterfaces($interface_name)[strtolower($possible_parent)]);
    }

    /**
     * @param  string         $fq_interface_name
     *
     * @return array<string, string>   all interfaces extended by $interface_name
     */
    public function getParentInterfaces($fq_interface_name)
    {
        $fq_interface_name = strtolower($fq_interface_name);

        $storage = $this->classlike_storage_provider->get($fq_interface_name);

        return $storage->parent_interfaces;
    }

    /**
     * @param  string         $fq_trait_name
     *
     * @return bool
     */
    public function traitExists($fq_trait_name)
    {
        return $this->hasFullyQualifiedTraitName($fq_trait_name);
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    public function classHasCorrectCasing($fq_class_name)
    {
        if ($fq_class_name === 'Generator') {
            return true;
        }

        if (isset($this->classlike_aliases[strtolower($fq_class_name)])) {
            return true;
        }

        return isset($this->existing_classes[$fq_class_name]);
    }

    /**
     * @param  string $fq_interface_name
     *
     * @return bool
     */
    public function interfaceHasCorrectCasing($fq_interface_name)
    {
        if (isset($this->classlike_aliases[strtolower($fq_interface_name)])) {
            return true;
        }

        if (isset($this->classlike_aliases[strtolower($fq_interface_name)])) {
            return true;
        }

        return isset($this->existing_interfaces[$fq_interface_name]);
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return bool
     */
    public function traitHasCorrectCase($fq_trait_name)
    {
        if (isset($this->classlike_aliases[strtolower($fq_trait_name)])) {
            return true;
        }

        return isset($this->existing_traits[$fq_trait_name]);
    }

    /**
     * @param  string  $fq_class_name
     *
     * @return bool
     */
    public function isUserDefined($fq_class_name)
    {
        return $this->classlike_storage_provider->get($fq_class_name)->user_defined;
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return void
     */
    public function addTraitNode($fq_trait_name, PhpParser\Node\Stmt\Trait_ $node, Aliases $aliases)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);
        $this->trait_nodes[$fq_trait_name_lc] = $node;
        $this->trait_aliases[$fq_trait_name_lc] = $aliases;
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return PhpParser\Node\Stmt\Trait_
     */
    public function getTraitNode($fq_trait_name)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);

        if (isset($this->trait_nodes[$fq_trait_name_lc])) {
            return $this->trait_nodes[$fq_trait_name_lc];
        }

        throw new \UnexpectedValueException(
            'Expecting trait statements to exist for ' . $fq_trait_name
        );
    }

    /**
     * @param  string $fq_trait_name
     *
     * @return Aliases
     */
    public function getTraitAliases($fq_trait_name)
    {
        $fq_trait_name_lc = strtolower($fq_trait_name);

        if (isset($this->trait_aliases[$fq_trait_name_lc])) {
            return $this->trait_aliases[$fq_trait_name_lc];
        }

        throw new \UnexpectedValueException(
            'Expecting trait aliases to exist for ' . $fq_trait_name
        );
    }

    /**
     * @return void
     */
    public function addClassAlias(string $fq_class_name, string $alias_name)
    {
        $this->classlike_aliases[strtolower($alias_name)] = $fq_class_name;
    }

    /**
     * @return string
     */
    public function getUnAliasedName(string $alias_name)
    {
        return $this->classlike_aliases[strtolower($alias_name)] ?? $alias_name;
    }

    /**
     * @return void
     */
    public function checkClassReferences(Methods $methods)
    {
        foreach ($this->existing_classlikes_lc as $fq_class_name_lc => $_) {
            try {
                $classlike_storage = $this->classlike_storage_provider->get($fq_class_name_lc);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            if ($classlike_storage->location
                && $this->config->isInProjectDirs($classlike_storage->location->file_path)
                && !$classlike_storage->is_trait
            ) {
                if (!isset($this->classlike_references[$fq_class_name_lc])) {
                    if (IssueBuffer::accepts(
                        new UnusedClass(
                            'Class ' . $classlike_storage->name . ' is never used',
                            $classlike_storage->location
                        )
                    )) {
                        // fall through
                    }
                } else {
                    $this->checkMethodReferences($classlike_storage, $methods);
                }
            }
        }
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     *
     * @return array<string,Type\Union>
     */
    public function getConstantsForClass($class_name, $visibility)
    {
        $class_name = strtolower($class_name);

        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return $storage->public_class_constants;
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants
            );
        }

        if ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants,
                $storage->private_class_constants
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    /**
     * @param   string      $class_name
     * @param   string      $const_name
     * @param   Type\Union  $type
     * @param   int         $visibility
     *
     * @return  void
     */
    public function setConstantType(
        $class_name,
        $const_name,
        Type\Union $type,
        $visibility
    ) {
        $storage = $this->classlike_storage_provider->get($class_name);

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            $storage->public_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            $storage->protected_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            $storage->private_class_constants[$const_name] = $type;
        }
    }

    /**
     * @return void
     */
    private function checkMethodReferences(ClassLikeStorage $classlike_storage, Methods $methods)
    {
        foreach ($classlike_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            list($appearing_fq_classlike_name) = explode('::', $appearing_method_id);

            if ($appearing_fq_classlike_name !== $classlike_storage->name) {
                continue;
            }

            if (isset($classlike_storage->methods[$method_name])) {
                $method_storage = $classlike_storage->methods[$method_name];
            } else {
                $declaring_method_id = $classlike_storage->declaring_method_ids[$method_name];

                list($declaring_fq_classlike_name) = explode('::', $declaring_method_id);

                try {
                    $declaring_classlike_storage = $this->classlike_storage_provider->get($declaring_fq_classlike_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                $method_storage = $declaring_classlike_storage->methods[$method_name];
            }

            if (($method_storage->referencing_locations === null
                    || count($method_storage->referencing_locations) === 0)
                && (substr($method_name, 0, 2) !== '__' || $method_name === '__construct')
                && $method_storage->location
            ) {
                $method_location = $method_storage->location;

                $method_id = $classlike_storage->name . '::' . $method_storage->cased_name;

                if ($method_storage->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE) {
                    $method_name_lc = strtolower($method_name);

                    $has_parent_references = false;

                    if (isset($classlike_storage->overridden_method_ids[$method_name_lc])) {
                        foreach ($classlike_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                            $parent_method_storage = $methods->getStorage($parent_method_id);

                            if (!$parent_method_storage->abstract || $parent_method_storage->referencing_locations) {
                                $has_parent_references = true;
                                break;
                            }
                        }
                    }

                    foreach ($classlike_storage->class_implements as $fq_interface_name) {
                        $interface_storage = $this->classlike_storage_provider->get($fq_interface_name);
                        if (isset($interface_storage->methods[$method_name])) {
                            $interface_method_storage = $interface_storage->methods[$method_name];

                            if ($interface_method_storage->referencing_locations) {
                                $has_parent_references = true;
                                break;
                            }
                        }
                    }

                    if (!$has_parent_references) {
                        if (IssueBuffer::accepts(
                            new PossiblyUnusedMethod(
                                'Cannot find public calls to method ' . $method_id,
                                $method_storage->location,
                                $method_id
                            ),
                            $method_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__call'])) {
                    if (IssueBuffer::accepts(
                        new UnusedMethod(
                            'Method ' . $method_id . ' is never used',
                            $method_location,
                            $method_id
                        ),
                        $method_storage->suppressed_issues
                    )) {
                        // fall through
                    }
                }
            } else {
                foreach ($method_storage->unused_params as $offset => $code_location) {
                    $has_parent_references = false;

                    $method_name_lc = strtolower($method_name);

                    if (isset($classlike_storage->overridden_method_ids[$method_name_lc])) {
                        foreach ($classlike_storage->overridden_method_ids[$method_name_lc] as $parent_method_id) {
                            $parent_method_storage = $methods->getStorage($parent_method_id);

                            if (!$parent_method_storage->abstract
                                && isset($parent_method_storage->used_params[$offset])
                            ) {
                                $has_parent_references = true;
                                break;
                            }
                        }
                    }

                    if (!$has_parent_references && !isset($method_storage->used_params[$offset])) {
                        if (IssueBuffer::accepts(
                            new PossiblyUnusedParam(
                                'Param #' . $offset . ' is never referenced in this method',
                                $code_location
                            ),
                            $method_storage->suppressed_issues
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        foreach ($classlike_storage->properties as $property_name => $property_storage) {
            if (($property_storage->referencing_locations === null
                    || count($property_storage->referencing_locations) === 0)
                && (substr($property_name, 0, 2) !== '__' || $property_name === '__construct')
                && $property_storage->location
            ) {
                $property_id = $classlike_storage->name . '::$' . $property_name;

                if ($property_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PUBLIC) {
                    if (IssueBuffer::accepts(
                        new PossiblyUnusedProperty(
                            'Cannot find uses of public property ' . $property_id,
                            $property_storage->location
                        ),
                        $classlike_storage->suppressed_issues
                    )) {
                        // fall through
                    }
                } elseif (!isset($classlike_storage->declaring_method_ids['__get'])) {
                    if (IssueBuffer::accepts(
                        new UnusedProperty(
                            'Property ' . $property_id . ' is never used',
                            $property_storage->location
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param  string $fq_classlike_name_lc
     *
     * @return void
     */
    public function registerMissingClassLike($fq_classlike_name_lc)
    {
        $this->existing_classlikes_lc[$fq_classlike_name_lc] = false;
    }

    /**
     * @param  string $fq_classlike_name_lc
     *
     * @return bool
     */
    public function isMissingClassLike($fq_classlike_name_lc)
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc] === false;
    }

    /**
     * @param  string $fq_classlike_name_lc
     *
     * @return bool
     */
    public function doesClassLikeExist($fq_classlike_name_lc)
    {
        return isset($this->existing_classlikes_lc[$fq_classlike_name_lc])
            && $this->existing_classlikes_lc[$fq_classlike_name_lc];
    }

    /**
     * @param string $fq_class_name
     *
     * @return void
     */
    public function removeClassLike($fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);
        unset(
            $this->existing_classlikes_lc[$fq_class_name_lc],
            $this->existing_classes_lc[$fq_class_name_lc],
            $this->existing_traits_lc[$fq_class_name_lc],
            $this->existing_traits[$fq_class_name],
            $this->existing_interfaces_lc[$fq_class_name_lc],
            $this->existing_interfaces[$fq_class_name],
            $this->existing_classes[$fq_class_name],
            $this->trait_nodes[$fq_class_name_lc],
            $this->trait_aliases[$fq_class_name_lc],
            $this->classlike_references[$fq_class_name_lc]
        );

        $this->scanner->removeClassLike($fq_class_name_lc);
    }

    /**
     * @return array{
     *     0: array<string, bool>,
     *     1: array<string, bool>,
     *     2: array<string, bool>,
     *     3: array<string, bool>,
     *     4: array<string, bool>,
     *     5: array<string, bool>,
     *     6: array<string, bool>,
     *     7: array<string, \PhpParser\Node\Stmt\Trait_>,
     *     8: array<string, \Psalm\Aliases>,
     *     9: array<string, int>
     * }
     */
    public function getThreadData()
    {
        return [
            $this->existing_classlikes_lc,
            $this->existing_classes_lc,
            $this->existing_traits_lc,
            $this->existing_traits,
            $this->existing_interfaces_lc,
            $this->existing_interfaces,
            $this->existing_classes,
            $this->trait_nodes,
            $this->trait_aliases,
            $this->classlike_references
        ];
    }

    /**
     * @param array{
     *     0: array<string, bool>,
     *     1: array<string, bool>,
     *     2: array<string, bool>,
     *     3: array<string, bool>,
     *     4: array<string, bool>,
     *     5: array<string, bool>,
     *     6: array<string, bool>,
     *     7: array<string, \PhpParser\Node\Stmt\Trait_>,
     *     8: array<string, \Psalm\Aliases>,
     *     9: array<string, int>
     * } $thread_data
     *
     * @return void
     */
    public function addThreadData(array $thread_data)
    {
        list (
            $existing_classlikes_lc,
            $existing_classes_lc,
            $existing_traits_lc,
            $existing_traits,
            $existing_interfaces_lc,
            $existing_interfaces,
            $existing_classes,
            $trait_nodes,
            $trait_aliases,
            $classlike_references
        ) = $thread_data;

        $this->existing_classlikes_lc = array_merge($existing_classlikes_lc, $this->existing_classlikes_lc);
        $this->existing_classes_lc = array_merge($existing_classes_lc, $this->existing_classes_lc);
        $this->existing_traits_lc = array_merge($existing_traits_lc, $this->existing_traits_lc);
        $this->existing_traits = array_merge($existing_traits, $this->existing_traits);
        $this->existing_interfaces_lc = array_merge($existing_interfaces_lc, $this->existing_interfaces_lc);
        $this->existing_interfaces = array_merge($existing_interfaces, $this->existing_interfaces);
        $this->existing_classes = array_merge($existing_classes, $this->existing_classes);
        $this->trait_nodes = array_merge($trait_nodes, $this->trait_nodes);
        $this->trait_aliases = array_merge($trait_aliases, $this->trait_aliases);
        $this->classlike_references = array_merge($classlike_references, $this->classlike_references);
    }
}
