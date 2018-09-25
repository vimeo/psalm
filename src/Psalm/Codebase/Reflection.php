<?php
namespace Psalm\Codebase;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\CommentChecker;
use Psalm\Codebase;
use Psalm\Provider\ClassLikeStorageProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;

/**
 * @internal
 *
 * Handles information gleaned from class and function reflection
 */
class Reflection
{
    /**
     * @var ClassLikeStorageProvider
     */
    private $storage_provider;

    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var array<string, FunctionLikeStorage>
     */
    private static $builtin_functions = [];

    public function __construct(ClassLikeStorageProvider $storage_provider, Codebase $codebase)
    {
        $this->storage_provider = $storage_provider;
        $this->codebase = $codebase;
        self::$builtin_functions = [];
    }

    /**
     * @return void
     */
    public function registerClass(\ReflectionClass $reflected_class)
    {
        $class_name = $reflected_class->name;

        if ($class_name === 'LibXMLError') {
            $class_name = 'libXMLError';
        }

        $class_name_lower = strtolower($class_name);

        try {
            $this->storage_provider->get($class_name_lower);

            return;
        } catch (\Exception $e) {
            // this is fine
        }

        $reflected_parent_class = $reflected_class->getParentClass();

        $storage = $this->storage_provider->create($class_name);
        $storage->abstract = $reflected_class->isAbstract();

        $storage->potential_declaring_method_ids['__construct'][$class_name_lower . '::__construct'] = true;

        if ($reflected_parent_class) {
            $parent_class_name = $reflected_parent_class->getName();
            $this->registerClass($reflected_parent_class);

            $parent_storage = $this->storage_provider->get($parent_class_name);

            $this->registerInheritedMethods($class_name, $parent_class_name);
            $this->registerInheritedProperties($class_name, $parent_class_name);

            $storage->class_implements = $parent_storage->class_implements;

            $storage->public_class_constants = $parent_storage->public_class_constants;
            $storage->protected_class_constants = $parent_storage->protected_class_constants;
            $parent_class_name_lc = strtolower($parent_class_name);
            $storage->parent_classes = array_merge(
                [$parent_class_name_lc => $parent_class_name_lc],
                $parent_storage->parent_classes
            );

            $storage->used_traits = $parent_storage->used_traits;
        }

        $class_properties = $reflected_class->getProperties();

        $public_mapped_properties = PropertyMap::inPropertyMap($class_name)
            ? PropertyMap::getPropertyMap()[strtolower($class_name)]
            : [];

        /** @var \ReflectionProperty $class_property */
        foreach ($class_properties as $class_property) {
            $property_name = $class_property->getName();
            $storage->properties[$property_name] = new PropertyStorage();

            $storage->properties[$property_name]->type = Type::getMixed();

            if ($class_property->isStatic()) {
                $storage->properties[$property_name]->is_static = true;
            }

            if ($class_property->isPublic()) {
                $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;
            } elseif ($class_property->isProtected()) {
                $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PROTECTED;
            } elseif ($class_property->isPrivate()) {
                $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PRIVATE;
            }

            $property_id = (string)$class_property->class . '::$' . $property_name;

            $storage->declaring_property_ids[$property_name] = (string)$class_property->class;
            $storage->appearing_property_ids[$property_name] = $property_id;

            if (!$class_property->isPrivate()) {
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }
        }

        // have to do this separately as there can be new properties here
        foreach ($public_mapped_properties as $property_name => $type) {
            if (!isset($storage->properties[$property_name])) {
                $storage->properties[$property_name] = new PropertyStorage();
                $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;

                $property_id = $class_name . '::$' . $property_name;

                $storage->declaring_property_ids[$property_name] = $class_name;
                $storage->appearing_property_ids[$property_name] = $property_id;
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }

            $storage->properties[$property_name]->type = Type::parseString($type);
        }

        /** @var array<string, int|string|float|null|array> */
        $class_constants = $reflected_class->getConstants();

        foreach ($class_constants as $name => $value) {
            $storage->public_class_constants[$name] = ClassLikeChecker::getTypeFromValue($value);
        }

        if ($reflected_class->isInterface()) {
            $this->codebase->classlikes->addFullyQualifiedInterfaceName($class_name);
        } elseif ($reflected_class->isTrait()) {
            $this->codebase->classlikes->addFullyQualifiedTraitName($class_name);
        } else {
            $this->codebase->classlikes->addFullyQualifiedClassName($class_name);
        }

        $reflection_methods = $reflected_class->getMethods(
            (int) (\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED)
        );

        if ($class_name_lower === 'generator') {
            $storage->template_types = ['TKey' => Type::getMixed(), 'TValue' => Type::getMixed()];
        }

        $interfaces = $reflected_class->getInterfaces();

        /** @var \ReflectionClass $interface */
        foreach ($interfaces as $interface) {
            $interface_name = $interface->getName();
            $this->registerClass($interface);

            if ($reflected_class->isInterface()) {
                $storage->parent_interfaces[strtolower($interface_name)] = $interface_name;
            } else {
                $storage->class_implements[strtolower($interface_name)] = $interface_name;
            }
        }

        /** @var \ReflectionMethod $reflection_method */
        foreach ($reflection_methods as $reflection_method) {
            $method_reflection_class = $reflection_method->getDeclaringClass();

            $this->registerClass($method_reflection_class);

            $this->extractReflectionMethodInfo($reflection_method);

            if ($reflection_method->class !== $class_name) {
                $this->codebase->methods->setDeclaringMethodId(
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                $this->codebase->methods->setAppearingMethodId(
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                continue;
            }
        }
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return void
     */
    public function extractReflectionMethodInfo(\ReflectionMethod $method)
    {
        $method_name = strtolower($method->getName());

        $class_storage = $this->storage_provider->get($method->class);

        if (isset($class_storage->methods[strtolower($method_name)])) {
            return;
        }

        $method_id = $method->class . '::' . $method_name;

        $storage = $class_storage->methods[strtolower($method_name)] = new MethodStorage();

        $storage->cased_name = $method->name;

        if (strtolower((string)$method->name) === strtolower((string)$method->class)) {
            $this->codebase->methods->setDeclaringMethodId(
                $method->class . '::__construct',
                $method->class . '::' . $method_name
            );
            $this->codebase->methods->setAppearingMethodId(
                $method->class . '::__construct',
                $method->class . '::' . $method_name
            );
        }

        $declaring_class = $method->getDeclaringClass();

        $storage->is_static = $method->isStatic();
        $storage->abstract = $method->isAbstract();

        $class_storage->declaring_method_ids[$method_name] =
            $declaring_class->name . '::' . strtolower((string)$method->getName());

        $class_storage->inheritable_method_ids[$method_name] = $class_storage->declaring_method_ids[$method_name];
        $class_storage->appearing_method_ids[$method_name] = $class_storage->declaring_method_ids[$method_name];
        $class_storage->overridden_method_ids[$method_name] = [];

        try {
            $storage->return_type = CallMap::getReturnTypeFromCallMap($method_id);
            $storage->return_type->queueClassLikesForScanning($this->codebase);
        } catch (\InvalidArgumentException $e) {
            // do nothing
        }

        $storage->visibility = $method->isPrivate()
            ? ClassLikeChecker::VISIBILITY_PRIVATE
            : ($method->isProtected() ? ClassLikeChecker::VISIBILITY_PROTECTED : ClassLikeChecker::VISIBILITY_PUBLIC);

        $possible_params = CallMap::getParamsFromCallMap($method_id);

        if ($possible_params === null) {
            $params = $method->getParameters();

            $storage->params = [];

            /** @var \ReflectionParameter $param */
            foreach ($params as $param) {
                $param_array = $this->getReflectionParamData($param);
                $storage->params[] = $param_array;
                $storage->param_types[$param->name] = $param_array->type;
            }
        } else {
            foreach ($possible_params[0] as $param) {
                if ($param->type) {
                    $param->type->queueClassLikesForScanning($this->codebase);
                }
            }

            $storage->params = $possible_params[0];
        }

        $storage->required_param_count = 0;

        foreach ($storage->params as $i => $param) {
            if (!$param->is_optional) {
                $storage->required_param_count = $i + 1;
            }
        }
    }

    /**
     * @param  \ReflectionParameter $param
     *
     * @return FunctionLikeParameter
     */
    private function getReflectionParamData(\ReflectionParameter $param)
    {
        $param_type_string = null;

        if ($param->isArray()) {
            $param_type_string = 'array';
        } else {
            try {
                $param_class = $param->getClass();
            } catch (\ReflectionException $e) {
                $param_class = null;
            }

            if ($param_class) {
                $param_type_string = (string)$param_class->getName();
            }
        }

        $is_nullable = false;

        $is_optional = (bool)$param->isOptional();

        try {
            $is_nullable = $param->getDefaultValue() === null;

            if ($param_type_string && $is_nullable) {
                $param_type_string .= '|null';
            }
        } catch (\ReflectionException $e) {
            // do nothing
        }

        $param_name = (string)$param->getName();
        $param_type = $param_type_string ? Type::parseString($param_type_string) : Type::getMixed();

        return new FunctionLikeParameter(
            $param_name,
            (bool)$param->isPassedByReference(),
            $param_type,
            null,
            null,
            $is_optional,
            $is_nullable,
            $param->isVariadic()
        );
    }

    /**
     * @param  string $function_id
     *
     * @return false|null
     */
    public function registerFunction($function_id)
    {
        try {
            $reflection_function = new \ReflectionFunction($function_id);

            $storage = self::$builtin_functions[$function_id] = new FunctionLikeStorage();

            $reflection_params = $reflection_function->getParameters();

            /** @var \ReflectionParameter $param */
            foreach ($reflection_params as $param) {
                $param_obj = $this->getReflectionParamData($param);
                $storage->params[] = $param_obj;
            }

            $storage->required_param_count = 0;

            foreach ($storage->params as $i => $param) {
                if (!$param->is_optional) {
                    $storage->required_param_count = $i + 1;
                }
            }

            $storage->cased_name = $reflection_function->getName();

            if (version_compare(PHP_VERSION, '7.0.0dev', '>=')
                && $reflection_return_type = $reflection_function->getReturnType()
            ) {
                $storage->return_type = Type::parseString((string)$reflection_return_type);
            }
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    private function registerInheritedMethods(
        $fq_class_name,
        $parent_class
    ) {
        $parent_storage = $this->storage_provider->get($parent_class);
        $storage = $this->storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            $storage->declaring_method_ids[$method_name] = $declaring_method_id;
            $storage->inheritable_method_ids[$method_name] = $declaring_method_id;

            $storage->overridden_method_ids[$method_name][] = $declaring_method_id;
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    private function registerInheritedProperties(
        $fq_class_name,
        $parent_class
    ) {
        $parent_storage = $this->storage_provider->get($parent_class);
        $storage = $this->storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_class) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_class;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    /**
     * @param  string  $function_id
     *
     * @return bool
     */
    public function hasFunction($function_id)
    {
        return isset(self::$builtin_functions[$function_id]);
    }

    /**
     * @param  string  $function_id
     *
     * @return FunctionLikeStorage
     */
    public function getFunctionStorage($function_id)
    {
        if (isset(self::$builtin_functions[$function_id])) {
            return self::$builtin_functions[$function_id];
        }

        throw new \UnexpectedValueException('Expecting to have a function for ' . $function_id);
    }
}
