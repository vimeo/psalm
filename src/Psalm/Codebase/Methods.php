<?php
namespace Psalm\Codebase;

use PhpParser;
use Psalm\Checker\MethodChecker;
use Psalm\CodeLocation;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Storage\MethodStorage;
use Psalm\Type;

/**
 * @internal
 *
 * Handles information about class methods
 */
class Methods
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $classlike_storage_provider;

    /**
     * @var \Psalm\Config
     */
    private $config;

    /**
     * @var bool
     */
    public $collect_references = false;

    /**
     * @param ClassLikeStorageProvider $storage_provider
     */
    public function __construct(
        \Psalm\Config $config,
        ClassLikeStorageProvider $storage_provider
    ) {
        $this->classlike_storage_provider = $storage_provider;
        $this->config = $config;
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string       $method_id
     * @param  CodeLocation|null $code_location
     *
     * @return bool
     */
    public function methodExists(
        string $method_id,
        ?string $calling_method_id = null,
        CodeLocation $code_location = null
    ) {
        // remove trailing backslash if it exists
        $method_id = preg_replace('/^\\\\/', '', $method_id);
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $method_name = strtolower($method_name);
        $method_id = $fq_class_name . '::' . $method_name;

        $old_method_id = null;

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            $declaring_method_id = $class_storage->declaring_method_ids[$method_name];

            $declaring_method_id_lc = strtolower($declaring_method_id);

            if ($calling_method_id === $declaring_method_id_lc) {
                return true;
            }

            if ($calling_method_id) {
                $method_id_lc = strtolower($method_id);

                if ($method_id_lc !== $declaring_method_id_lc
                    && $class_storage->user_defined
                    && isset($class_storage->potential_declaring_method_ids[$method_name])
                ) {
                    foreach ($class_storage->potential_declaring_method_ids[$method_name] as $potential_id => $_) {
                        \Psalm\Provider\FileReferenceProvider::addReferenceToClassMethod(
                            $calling_method_id,
                            $potential_id
                        );
                    }
                } else {
                    \Psalm\Provider\FileReferenceProvider::addReferenceToClassMethod(
                        $calling_method_id,
                        $declaring_method_id_lc
                    );
                }
            }

            if ($this->collect_references && $code_location) {
                list($declaring_method_class, $declaring_method_name) = explode('::', $declaring_method_id);

                $declaring_class_storage = $this->classlike_storage_provider->get($declaring_method_class);
                $declaring_method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];
                if ($declaring_method_storage->referencing_locations === null) {
                    $declaring_method_storage->referencing_locations = [];
                }
                $declaring_method_storage->referencing_locations[$code_location->file_path][] = $code_location;

                foreach ($class_storage->class_implements as $fq_interface_name) {
                    $interface_storage = $this->classlike_storage_provider->get($fq_interface_name);
                    if (isset($interface_storage->methods[$method_name])) {
                        $interface_method_storage = $interface_storage->methods[$method_name];
                        if (!isset($interface_method_storage->referencing_locations)) {
                            $interface_method_storage->referencing_locations = [];
                        }
                        $interface_method_storage->referencing_locations[$code_location->file_path][] = $code_location;
                    }
                }

                if (isset($declaring_class_storage->overridden_method_ids[$declaring_method_name])) {
                    $overridden_method_ids = $declaring_class_storage->overridden_method_ids[$declaring_method_name];

                    foreach ($overridden_method_ids as $overridden_method_id) {
                        list($overridden_method_class, $overridden_method_name) = explode('::', $overridden_method_id);

                        $class_storage = $this->classlike_storage_provider->get($overridden_method_class);
                        $method_storage = $class_storage->methods[strtolower($overridden_method_name)];
                        if ($method_storage->referencing_locations === null) {
                            $method_storage->referencing_locations = [];
                        }
                        $method_storage->referencing_locations[$code_location->file_path][] = $code_location;
                    }
                }
            }

            return true;
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return true;
        }

        // support checking oldstyle constructors
        if ($method_name === '__construct') {
            $method_name_parts = explode('\\', $fq_class_name);
            $old_constructor_name = array_pop($method_name_parts);
            $old_method_id = $fq_class_name . '::' . $old_constructor_name;
        }

        if (!$class_storage->user_defined
            && (CallMap::inCallMap($method_id) || ($old_method_id && CallMap::inCallMap($method_id)))
        ) {
            return true;
        }

        if ($calling_method_id) {
            // also store failures in case the method is added later
            \Psalm\Provider\FileReferenceProvider::addReferenceToClassMethod(
                $calling_method_id,
                strtolower($method_id)
            );
        }

        return false;
    }

    /**
     * @param  string $method_id
     *
     * @return array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public function getMethodParams($method_id)
    {
        if ($method_id = $this->getDeclaringMethodId($method_id)) {
            $storage = $this->getStorage($method_id);

            return $storage->params;
        }

        throw new \UnexpectedValueException('Cannot get method params for ' . $method_id);
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public function isVariadic($method_id)
    {
        $method_id = (string) $this->getDeclaringMethodId($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        return $this->classlike_storage_provider->get($fq_class_name)->methods[$method_name]->variadic;
    }

    /**
     * @param  string $method_id
     * @param  string $self_class
     * @param  array<int, PhpParser\Node\Arg>|null $args
     *
     * @return Type\Union|null
     */
    public function getMethodReturnType($method_id, &$self_class, array $args = null)
    {
        if ($this->config->use_phpdoc_methods_without_call) {
            list($original_fq_class_name, $original_method_name) = explode('::', $method_id);

            $original_class_storage = $this->classlike_storage_provider->get($original_fq_class_name);

            if (isset($original_class_storage->pseudo_methods[strtolower($original_method_name)])) {
                return $original_class_storage->pseudo_methods[strtolower($original_method_name)]->return_type;
            }
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            return null;
        }

        $appearing_method_id = $this->getAppearingMethodId($method_id);

        if (!$appearing_method_id) {
            return null;
        }

        list($appearing_fq_class_name, $appearing_method_name) = explode('::', $appearing_method_id);

        $appearing_fq_class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if (!$appearing_fq_class_storage->user_defined && CallMap::inCallMap($appearing_method_id)) {
            if ($appearing_method_id === 'Closure::fromcallable'
                && isset($args[0]->value->inferredType)
                && $args[0]->value->inferredType->isSingle()
            ) {
                foreach ($args[0]->value->inferredType->getTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TCallable || $atomic_type instanceof Type\Atomic\Fn) {
                        $callable_type = clone $atomic_type;

                        return new Type\Union([new Type\Atomic\Fn(
                            'Closure',
                            $callable_type->params,
                            $callable_type->return_type
                        )]);
                    }
                }
            }
            return CallMap::getReturnTypeFromCallMap($appearing_method_id);
        }

        $storage = $this->getStorage($declaring_method_id);

        if ($storage->return_type) {
            $self_class = $appearing_fq_class_name;

            return clone $storage->return_type;
        }

        $class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if (!isset($class_storage->overridden_method_ids[$appearing_method_name])) {
            return null;
        }

        foreach ($class_storage->overridden_method_ids[$appearing_method_name] as $overridden_method_id) {
            $overridden_storage = $this->getStorage($overridden_method_id);

            if ($overridden_storage->return_type) {
                if ($overridden_storage->return_type->isNull()) {
                    return Type::getVoid();
                }

                list($fq_overridden_class) = explode('::', $overridden_method_id);

                $overridden_class_storage =
                    $this->classlike_storage_provider->get($fq_overridden_class);

                $overridden_return_type = clone $overridden_storage->return_type;

                if ($overridden_class_storage->template_types) {
                    $generic_types = [];
                    $overridden_return_type->replaceTemplateTypesWithStandins(
                        $overridden_class_storage->template_types,
                        $generic_types
                    );
                }

                $self_class = $overridden_class_storage->name;

                return $overridden_return_type;
            }
        }

        return null;
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public function getMethodReturnsByRef($method_id)
    {
        $method_id = $this->getDeclaringMethodId($method_id);

        if (!$method_id) {
            return false;
        }

        list($fq_class_name) = explode('::', $method_id);

        $fq_class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (!$fq_class_storage->user_defined && CallMap::inCallMap($method_id)) {
            return false;
        }

        $storage = $this->getStorage($method_id);

        return $storage->returns_by_ref;
    }

    /**
     * @param  string               $method_id
     * @param  CodeLocation|null    $defined_location
     *
     * @return CodeLocation|null
     */
    public function getMethodReturnTypeLocation(
        $method_id,
        CodeLocation &$defined_location = null
    ) {
        $method_id = $this->getDeclaringMethodId($method_id);

        if ($method_id === null) {
            return null;
        }

        $storage = $this->getStorage($method_id);

        if (!$storage->return_type_location) {
            $overridden_method_ids = $this->getOverriddenMethodIds($method_id);

            foreach ($overridden_method_ids as $overridden_method_id) {
                $overridden_storage = $this->getStorage($overridden_method_id);

                if ($overridden_storage->return_type_location) {
                    $defined_location = $overridden_storage->return_type_location;
                    break;
                }
            }
        }

        return $storage->return_type_location;
    }

    /**
     * @param string $method_id
     * @param string $declaring_method_id
     *
     * @return void
     */
    public function setDeclaringMethodId(
        $method_id,
        $declaring_method_id
    ) {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $class_storage->declaring_method_ids[$method_name] = $declaring_method_id;
    }

    /**
     * @param string $method_id
     * @param string $appearing_method_id
     *
     * @return void
     */
    public function setAppearingMethodId(
        $method_id,
        $appearing_method_id
    ) {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $class_storage->appearing_method_ids[$method_name] = $appearing_method_id;
    }

    /**
     * @param  string $method_id
     *
     * @return string|null
     */
    public function getDeclaringMethodId($method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            return $class_storage->declaring_method_ids[$method_name];
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return $class_storage->overridden_method_ids[$method_name][0];
        }
    }

    /**
     * Get the class this method appears in (vs is declared in, which could give a trait)
     *
     * @param  string $method_id
     *
     * @return string|null
     */
    public function getAppearingMethodId($method_id)
    {
        $method_id = strtolower($method_id);

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->appearing_method_ids[$method_name])) {
            return $class_storage->appearing_method_ids[$method_name];
        }
    }

    /**
     * @param  string $method_id
     *
     * @return array<string>
     */
    public function getOverriddenMethodIds($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->overridden_method_ids[$method_name])) {
            return $class_storage->overridden_method_ids[$method_name];
        }

        return [];
    }

    /**
     * @param  string $original_method_id
     *
     * @return string
     */
    public function getCasedMethodId($original_method_id)
    {
        $method_id = $this->getDeclaringMethodId($original_method_id);

        if ($method_id === null) {
            throw new \UnexpectedValueException('Cannot get declaring method id for ' . $original_method_id);
        }

        $storage = $this->getStorage($method_id);

        list($fq_class_name) = explode('::', $method_id);

        return $fq_class_name . '::' . $storage->cased_name;
    }

    /**
     * @param  string $method_id
     *
     * @return MethodStorage
     */
    public function getUserMethodStorage($method_id)
    {
        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        $storage = $this->getStorage($declaring_method_id);

        if (!$storage->location) {
            throw new \UnexpectedValueException('Storage for ' . $method_id . ' is not user-defined');
        }

        return $storage;
    }

    /**
     * @param  string $method_id
     *
     * @return MethodStorage
     */
    public function getStorage($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        $method_name_lc = strtolower($method_name);

        if (!isset($class_storage->methods[$method_name_lc])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        return $class_storage->methods[$method_name_lc];
    }
}
