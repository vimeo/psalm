<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Exception;
use LogicException;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Interner;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Mutations;
use Psalm\Storage\PropertyStorage;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Union;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use UnexpectedValueException;

use function array_map;
use function array_replace;
use function implode;

use const PHP_VERSION_ID;

/**
 * @internal
 *
 * Handles information gleaned from class and function reflection
 */
final class Reflection
{
    /**
     * @var array<int, FunctionStorage> function id => storage
     */
    private static array $builtin_functions = [];

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly ClassLikeStorageProvider $storage_provider,
        private readonly Codebase $codebase,
    ) {
        self::$builtin_functions = [];
    }

    public function registerClass(ReflectionClass $reflected_class): void
    {
        $class_name_string = $reflected_class->name;
        $class_name = Interner::intern($class_name_string);

        try {
            $this->storage_provider->get($class_name);

            return;
        } catch (Exception) {
            // this is fine
        }

        $reflected_parent_class = $reflected_class->getParentClass();

        $storage = $this->storage_provider->create($class_name);
        $storage->abstract = $reflected_class->isAbstract();
        $storage->is_interface = $reflected_class->isInterface();

        $storage->potential_declaring_method_ids[StrId::__construct][$class_name][StrId::__construct] = true;

        if ($reflected_parent_class) {
            $parent_class_name = Interner::intern($reflected_parent_class->getName());
            $this->registerClass($reflected_parent_class);

            $parent_storage = $this->storage_provider->get($parent_class_name);

            $this->registerInheritedMethods($class_name, $parent_class_name);
            $this->registerInheritedProperties($class_name, $parent_class_name);

            $storage->class_implements = $parent_storage->class_implements;

            $storage->constants = $parent_storage->constants;

            $storage->parent_classes = array_replace(
                [$parent_class_name => $parent_class_name],
                $parent_storage->parent_classes,
            );

            $storage->used_traits = $parent_storage->used_traits;
        }

        $class_properties = $reflected_class->getProperties();

        $public_mapped_properties = PropertyMap::getPropertiesForClass($class_name) ?? [];

        foreach ($class_properties as $class_property) {
            $property_name = Interner::intern($class_property->getName());
            $storage->properties[$property_name] = new PropertyStorage();

            $storage->properties[$property_name]->type = Type::getMixed();

            if ($class_property->isStatic()) {
                $storage->properties[$property_name]->is_static = true;
            }

            if ($class_property->isPublic()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;
            } elseif ($class_property->isProtected()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PROTECTED;
            } elseif ($class_property->isPrivate()) {
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PRIVATE;
            }

            $property_class = Interner::intern($class_property->class);

            $storage->declaring_property_ids[$property_name] = $property_class;
            $storage->appearing_property_ids[$property_name] = $property_class;

            if (!$class_property->isPrivate()) {
                $storage->inheritable_property_ids[$property_name] = $property_class;
            }
        }

        // have to do this separately as there can be new properties here
        foreach ($public_mapped_properties as $property_name => $type_string) {
            if (!isset($storage->properties[$property_name])) {
                $storage->properties[$property_name] = new PropertyStorage();
                $storage->properties[$property_name]->visibility = ClassLikeAnalyzer::VISIBILITY_PUBLIC;

                $storage->declaring_property_ids[$property_name] = $class_name;
                $storage->appearing_property_ids[$property_name] = $class_name;
                $storage->inheritable_property_ids[$property_name] = $class_name;
            }

            $type = Type::parseString($type_string);

            if ($class_name === StrId::DateInterval && $property_name === StrId::days) {
                /** @psalm-suppress InaccessibleProperty We just parsed this type */
                $type->ignore_falsable_issues = true;
            }

            $storage->properties[$property_name]->type = $type;
        }

        /** @var array<string, int|string|float|null|array> */
        $class_constants = $reflected_class->getConstants();

        foreach ($class_constants as $name => $value) {
            $storage->constants[Interner::intern($name)] = new ClassConstantStorage(
                ClassLikeAnalyzer::getTypeFromValue($value),
                new Union([ConstantTypeResolver::getLiteralTypeFromScalarValue($value)]),
                ClassLikeAnalyzer::VISIBILITY_PUBLIC,
                null,
            );
        }

        if ($reflected_class->isInterface()) {
            $this->codebase->classlikes->addFullyQualifiedInterfaceName($class_name);
        } elseif ($reflected_class->isTrait()) {
            $this->codebase->classlikes->addFullyQualifiedTraitName($class_name);
        } else {
            $this->codebase->classlikes->addFullyQualifiedClassName($class_name);
        }

        $reflection_methods = $reflected_class->getMethods(
            (ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED),
        );

        if ($class_name === StrId::Generator) {
            $storage->template_types = [
                StrId::TKey => [StrId::Generator => Type::getMixed()],
                StrId::TValue => [StrId::Generator => Type::getMixed()],
            ];
        }

        $interfaces = $reflected_class->getInterfaces();

        foreach ($interfaces as $interface) {
            $interface_name = Interner::intern($interface->getName());
            $this->registerClass($interface);

            if ($reflected_class->isInterface()) {
                $storage->parent_interfaces[$interface_name] = $interface_name;
            } else {
                $storage->class_implements[$interface_name] = $interface_name;
            }
        }

        foreach ($reflection_methods as $reflection_method) {
            $method_reflection_class = $reflection_method->getDeclaringClass();

            $this->registerClass($method_reflection_class);

            $this->extractReflectionMethodInfo($reflection_method);

            if ($reflection_method->class !== $class_name_string
                && ($class_name_string !== 'SoapFault' || $reflection_method->name !== '__construct')
            ) {
                $reflection_method_name = Interner::intern($reflection_method->name);
                $reflection_method_class = Interner::intern($reflection_method->class);

                $this->codebase->methods->setDeclaringMethodId(
                    $class_name,
                    $reflection_method_name,
                    $reflection_method_class,
                    $reflection_method_name,
                );

                $this->codebase->methods->setAppearingMethodId(
                    $class_name,
                    $reflection_method_name,
                    $reflection_method_class,
                    $reflection_method_name,
                );
            }
        }
    }

    public function extractReflectionMethodInfo(ReflectionMethod $method): void
    {
        $method_name = Interner::intern($method->getName());

        $fq_class_name = Interner::intern($method->class);

        $class_storage = $this->storage_provider->get($fq_class_name);

        if (isset($class_storage->methods[$method_name])) {
            return;
        }

        $method_id = new MethodIdentifier($fq_class_name, $method_name);

        $storage = $class_storage->methods[$method_name] = new MethodStorage();

        $storage->cased_name = Interner::intern($method->name);
        $storage->defining_fqcln = $fq_class_name;

        if ($method_name === $fq_class_name) {
            $this->codebase->methods->setDeclaringMethodId(
                $fq_class_name,
                StrId::__construct,
                $fq_class_name,
                $method_name,
            );
            $this->codebase->methods->setAppearingMethodId(
                $fq_class_name,
                StrId::__construct,
                $fq_class_name,
                $method_name,
            );
        }

        $declaring_class = $method->getDeclaringClass();

        $storage->is_static = $method->isStatic();
        $storage->abstract = $method->isAbstract();

        if ($method_name === StrId::__construct && $fq_class_name === StrId::DateTimeZone) {
            $storage->allowed_mutations = Mutations::LEVEL_NONE;
        } else {
            $storage->allowed_mutations = Mutations::LEVEL_ALL;
        }

        $class_storage->declaring_method_ids[$method_name] = new MethodIdentifier(
            Interner::intern($declaring_class->name),
            $method_name,
        );

        $class_storage->inheritable_method_ids[$method_name]
            = $class_storage->declaring_method_ids[$method_name];
        $class_storage->appearing_method_ids[$method_name]
            = $class_storage->declaring_method_ids[$method_name];
        $class_storage->overridden_method_ids[$method_name] = [];

        $storage->visibility = $method->isPrivate()
            ? ClassLikeAnalyzer::VISIBILITY_PRIVATE
            : ($method->isProtected() ? ClassLikeAnalyzer::VISIBILITY_PROTECTED : ClassLikeAnalyzer::VISIBILITY_PUBLIC);

        $callables = InternalCallMapHandler::getCallablesFromCallMap($method_id);

        if ($callables && $callables[0]->params !== null && $callables[0]->return_type !== null) {
            $storage->setParams([]);

            foreach ($callables[0]->params as $param) {
                if ($param->type) {
                    /** @psalm-suppress UnusedMethodCall */
                    $param->type->queueClassLikesForScanning($this->codebase);
                }
            }

            $storage->setParams($callables[0]->params);

            $storage->return_type = $callables[0]->return_type;
            /** @psalm-suppress UnusedMethodCall */
            $storage->return_type->queueClassLikesForScanning($this->codebase);
        } else {
            $params = $method->getParameters();

            $storage->setParams([]);

            foreach ($params as $param) {
                $param_array = $this->getReflectionParamData($param);
                $storage->addParam($param_array);
            }
        }

        $storage->required_param_count = 0;

        foreach ($storage->params as $i => $param) {
            if (!$param->is_optional && !$param->is_variadic) {
                $storage->required_param_count = $i + 1;
            }
        }
    }

    private function getReflectionParamData(ReflectionParameter $param): FunctionLikeParameter
    {
        $param_type = self::getPsalmTypeFromReflectionType($param->getType());
        $param_name = Interner::intern($param->getName());

        $is_optional = $param->isOptional();

        $parameter = new FunctionLikeParameter(
            $param_name,
            $param->isPassedByReference(),
            $param_type,
            $param_type,
            null,
            null,
            $is_optional,
            $param_type->isNullable(),
            $param->isVariadic(),
        );

        $parameter->signature_type = Type::getMixed();

        return $parameter;
    }

    /**
     * @param int $function_id function id
     * @return false|null
     */
    public function registerFunction(int $function_id): ?bool
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection_function = new ReflectionFunction(Interner::str($function_id));

            // function names are case-sensitive, reflection isn't
            if ($reflection_function->getName() !== Interner::$strings[$function_id]) {
                return false;
            }

            $callmap_callable = null;

            if (isset(self::$builtin_functions[$function_id])) {
                return null;
            }

            $storage = self::$builtin_functions[$function_id] = new FunctionStorage();

            if (InternalCallMapHandler::inCallMap($function_id)) {
                $callmap_callable = InternalCallMapHandler::getCallableFromCallMapById(
                    $this->codebase,
                    $function_id,
                    [],
                    null,
                );
            }

            if ($callmap_callable !== null
                && $callmap_callable->params !== null
                && $callmap_callable->return_type !== null
            ) {
                $storage->setParams($callmap_callable->params);
                $storage->return_type = $callmap_callable->return_type;
            } else {
                $reflection_params = $reflection_function->getParameters();

                foreach ($reflection_params as $param) {
                    $param_obj = $this->getReflectionParamData($param);
                    $storage->addParam($param_obj);
                }

                if ($reflection_return_type = (PHP_VERSION_ID >= 8_01_00 ? (
                    ($reflection_function->getTentativeReturnType()
                        ?? $reflection_function->getReturnType()
                    )
                ) : $reflection_function->getReturnType())) {
                    $storage->return_type = self::getPsalmTypeFromReflectionType($reflection_return_type);
                }
            }

            $storage->allowed_mutations = Mutations::LEVEL_NONE;

            $storage->required_param_count = 0;

            foreach ($storage->params as $i => $param) {
                if (!$param->is_optional && !$param->is_variadic) {
                    $storage->required_param_count = $i + 1;
                }
            }

            $storage->cased_name = Interner::intern($reflection_function->getName());
        } catch (ReflectionException) {
            return false;
        }

        return null;
    }

    /** @psalm-suppress UnusedPsalmSuppress,UndefinedClass,TypeDoesNotContainType 7.4 has no ReflectionUnionType */
    public static function getPsalmTypeFromReflectionType(?ReflectionType $reflection_type = null): Union
    {
        if (!$reflection_type) {
            return Type::getMixed();
        }

        if ($reflection_type instanceof ReflectionNamedType) {
            $type = $reflection_type->getName();
        } elseif ($reflection_type instanceof ReflectionUnionType) {
            $type = implode(
                '|',
                array_map(
                    static fn(ReflectionNamedType $reflection): string => $reflection->getName(),
                    $reflection_type->getTypes(),
                ),
            );
        } else {
            throw new LogicException('Unexpected reflection class ' . $reflection_type::class . ' found.');
        }

        if ($reflection_type->allowsNull()) {
            $type .= '|null';
        }

        return Type::parseString($type);
    }

    /**
     * @param int $fq_class_name class name id
     * @param int $parent_class class name id
     */
    private function registerInheritedMethods(
        int $fq_class_name,
        int $parent_class,
    ): void {
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

            $storage->overridden_method_ids[$method_name][$declaring_method_id->fq_class_name]
                = $declaring_method_id;
        }
    }

    /**
     * @param int $fq_class_name class name id
     * @param int $parent_class class name id
     */
    private function registerInheritedProperties(
        int $fq_class_name,
        int $parent_class,
    ): void {
        $parent_storage = $this->storage_provider->get($parent_class);
        $storage = $this->storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_class) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_class;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function hasFunction(int $function_id): bool
    {
        return isset(self::$builtin_functions[$function_id]);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function getFunctionStorage(int $function_id): FunctionStorage
    {
        if (isset(self::$builtin_functions[$function_id])) {
            return self::$builtin_functions[$function_id];
        }

        throw new UnexpectedValueException('Expecting to have a function for ' . Interner::str($function_id));
    }

    /**
     * @return array<int, FunctionStorage> function id => storage
     * @psalm-external-mutation-free
     */
    public function getFunctions(): array
    {
        return self::$builtin_functions;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function clearCache(): void
    {
        self::$builtin_functions = [];
    }
}
