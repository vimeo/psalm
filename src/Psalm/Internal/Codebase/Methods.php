<?php
namespace Psalm\Internal\Codebase;

use function array_pop;
use function assert;
use function count;
use function explode;
use PhpParser;
use function preg_replace;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\MethodExistenceProvider;
use Psalm\Internal\Provider\MethodParamsProvider;
use Psalm\Internal\Provider\MethodReturnTypeProvider;
use Psalm\Internal\Provider\MethodVisibilityProvider;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use function reset;
use function strtolower;

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
     * @var bool
     */
    public $collect_locations = false;

    /**
     * @var FileReferenceProvider
     */
    public $file_reference_provider;

    /**
     * @var ClassLikes
     */
    private $classlikes;

    /** @var MethodReturnTypeProvider */
    public $return_type_provider;

    /** @var MethodParamsProvider */
    public $params_provider;

    /** @var MethodExistenceProvider */
    public $existence_provider;

    /** @var MethodVisibilityProvider */
    public $visibility_provider;

    /**
     * @param ClassLikeStorageProvider $storage_provider
     */
    public function __construct(
        ClassLikeStorageProvider $storage_provider,
        FileReferenceProvider $file_reference_provider,
        ClassLikes $classlikes
    ) {
        $this->classlike_storage_provider = $storage_provider;
        $this->file_reference_provider = $file_reference_provider;
        $this->classlikes = $classlikes;
        $this->return_type_provider = new MethodReturnTypeProvider();
        $this->existence_provider = new MethodExistenceProvider();
        $this->visibility_provider = new MethodVisibilityProvider();
        $this->params_provider = new MethodParamsProvider();
    }

    /**
     * Whether or not a given method exists
     *
     * @param  string       $method_id
     * @param  ?string      $calling_method_id
     * @param  CodeLocation|null $code_location
     *
     * @return bool
     */
    public function methodExists(
        $method_id,
        $calling_method_id = null,
        CodeLocation $code_location = null,
        StatementsSource $source = null,
        string $file_path = null
    ) {
        // remove trailing backslash if it exists
        $method_id = preg_replace('/^\\\\/', '', $method_id);
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $method_name = strtolower($method_name);
        $method_id = $fq_class_name . '::' . $method_name;

        if ($this->existence_provider->has($fq_class_name)) {
            $method_exists = $this->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_name,
                $source,
                $code_location
            );

            if ($method_exists !== null) {
                return $method_exists;
            }
        }

        if ($calling_method_id) {
            $calling_method_id = strtolower($calling_method_id);
        }

        $old_method_id = null;

        $fq_class_name = $this->classlikes->getUnAliasedName($fq_class_name);

        try {
            $class_storage = $this->classlike_storage_provider->get($fq_class_name);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            $declaring_method_id = $class_storage->declaring_method_ids[$method_name];

            $declaring_method_id_lc = strtolower($declaring_method_id);

            if ($calling_method_id === $declaring_method_id_lc) {
                return true;
            }

            $method_id_lc = strtolower($method_id);

            if ($method_id_lc !== $declaring_method_id_lc
                && $class_storage->user_defined
                && isset($class_storage->potential_declaring_method_ids[$method_name])
            ) {
                foreach ($class_storage->potential_declaring_method_ids[$method_name] as $potential_id => $_) {
                    if ($calling_method_id) {
                        $this->file_reference_provider->addMethodReferenceToClassMember(
                            $calling_method_id,
                            $potential_id
                        );
                    } elseif ($file_path) {
                        $this->file_reference_provider->addFileReferenceToClassMember(
                            $file_path,
                            $potential_id
                        );
                    }
                }
            } else {
                if ($calling_method_id) {
                    $this->file_reference_provider->addMethodReferenceToClassMember(
                        $calling_method_id,
                        $declaring_method_id_lc
                    );
                } elseif ($file_path) {
                    $this->file_reference_provider->addFileReferenceToClassMember(
                        $file_path,
                        $declaring_method_id_lc
                    );
                }
            }

            if ($this->collect_locations && $code_location) {
                $this->file_reference_provider->addCallingLocationForClassMethod(
                    $code_location,
                    $declaring_method_id_lc
                );
            }

            foreach ($class_storage->class_implements as $fq_interface_name) {
                $interface_method_id_lc = strtolower($fq_interface_name . '::' . $method_name);

                if ($this->collect_locations && $code_location) {
                    $this->file_reference_provider->addCallingLocationForClassMethod(
                        $code_location,
                        $interface_method_id_lc
                    );
                }

                if ($calling_method_id) {
                    $this->file_reference_provider->addMethodReferenceToClassMember(
                        $calling_method_id,
                        $interface_method_id_lc
                    );
                } elseif ($file_path) {
                    $this->file_reference_provider->addFileReferenceToClassMember(
                        $file_path,
                        $interface_method_id_lc
                    );
                }
            }

            list($declaring_method_class, $declaring_method_name) = explode('::', $declaring_method_id);

            $declaring_class_storage = $this->classlike_storage_provider->get($declaring_method_class);

            if (isset($declaring_class_storage->overridden_method_ids[$declaring_method_name])) {
                $overridden_method_ids = $declaring_class_storage->overridden_method_ids[$declaring_method_name];

                foreach ($overridden_method_ids as $overridden_method_id) {
                    if ($this->collect_locations && $code_location) {
                        $this->file_reference_provider->addCallingLocationForClassMethod(
                            $code_location,
                            strtolower($overridden_method_id)
                        );
                    }

                    if ($calling_method_id) {
                        // also store failures in case the method is added later
                        $this->file_reference_provider->addMethodReferenceToClassMember(
                            $calling_method_id,
                            strtolower($overridden_method_id)
                        );
                    } elseif ($file_path) {
                        $this->file_reference_provider->addFileReferenceToClassMember(
                            $file_path,
                            strtolower($overridden_method_id)
                        );
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

        foreach ($class_storage->parent_classes + $class_storage->used_traits as $potential_future_declaring_fqcln) {
            $potential_id = strtolower($potential_future_declaring_fqcln) . '::' . $method_name;

            if ($calling_method_id) {
                // also store failures in case the method is added later
                $this->file_reference_provider->addMethodReferenceToMissingClassMember(
                    $calling_method_id,
                    $potential_id
                );
            } elseif ($file_path) {
                $this->file_reference_provider->addFileReferenceToMissingClassMember(
                    $file_path,
                    $potential_id
                );
            }
        }

        if ($calling_method_id) {
            // also store failures in case the method is added later
            $this->file_reference_provider->addMethodReferenceToMissingClassMember(
                $calling_method_id,
                strtolower($method_id)
            );
        } elseif ($file_path) {
            $this->file_reference_provider->addFileReferenceToMissingClassMember(
                $file_path,
                strtolower($method_id)
            );
        }

        return false;
    }

    /**
     * @param  string $method_id
     * @param  array<int, PhpParser\Node\Arg> $args
     *
     * @return array<int, FunctionLikeParameter>
     */
    public function getMethodParams(
        $method_id,
        StatementsSource $source = null,
        array $args = null,
        Context $context = null
    ) : array {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        if ($this->params_provider->has($fq_class_name)) {
            $method_params = $this->params_provider->getMethodParams(
                $fq_class_name,
                $method_name,
                $args,
                $source,
                $context
            );

            if ($method_params !== null) {
                return $method_params;
            }
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        // functions
        if (CallMap::inCallMap($declaring_method_id ?: $method_id)) {
            $declaring_fq_class_name = explode('::', $declaring_method_id ?: $method_id)[0];

            $class_storage = $this->classlike_storage_provider->get($declaring_fq_class_name);

            if (!$class_storage->stubbed) {
                $function_callables = CallMap::getCallablesFromCallMap($declaring_method_id ?: $method_id);

                if ($function_callables === null) {
                    throw new \UnexpectedValueException(
                        'Not expecting $function_callables to be null for ' . $declaring_method_id
                    );
                }

                if (!$source || $args === null || count($function_callables) === 1) {
                    assert($function_callables[0]->params !== null);

                    return $function_callables[0]->params;
                }

                if ($context && $source instanceof \Psalm\Internal\Analyzer\StatementsAnalyzer) {
                    foreach ($args as $arg) {
                        \Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer::analyze(
                            $source,
                            $arg->value,
                            $context
                        );
                    }
                }

                $matching_callable = CallMap::getMatchingCallableFromCallMapOptions(
                    $source->getCodebase(),
                    $function_callables,
                    $args
                );

                assert($matching_callable->params !== null);

                return $matching_callable->params;
            }
        }

        if ($declaring_method_id) {
            $storage = $this->getStorage($declaring_method_id);

            $params = $storage->params;

            if ($storage->has_docblock_param_types) {
                return $params;
            }

            $appearing_method_id = $this->getAppearingMethodId($declaring_method_id);

            if (!$appearing_method_id) {
                return $params;
            }

            list($appearing_fq_class_name, $appearing_method_name) = explode('::', $appearing_method_id);

            $class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

            if (!isset($class_storage->overridden_method_ids[$appearing_method_name])) {
                return $params;
            }

            if (!isset($class_storage->documenting_method_ids[$appearing_method_name])) {
                return $params;
            }

            $overridden_method_id = $class_storage->documenting_method_ids[$appearing_method_name];
            $overridden_storage = $this->getStorage($overridden_method_id);

            list($overriding_fq_class_name) = explode('::', $overridden_method_id);

            foreach ($params as $i => $param) {
                if (isset($overridden_storage->params[$i]->type)
                    && $overridden_storage->params[$i]->has_docblock_type
                    && $overridden_storage->params[$i]->name === $param->name
                ) {
                    $params[$i] = clone $param;
                    /** @var Type\Union $params[$i]->type */
                    $params[$i]->type = clone $overridden_storage->params[$i]->type;

                    if ($source) {
                        $overridden_class_storage = $this->classlike_storage_provider->get($overriding_fq_class_name);
                        $params[$i]->type = self::localizeParamType(
                            $source->getCodebase(),
                            $params[$i]->type,
                            $appearing_fq_class_name,
                            $overridden_class_storage->name
                        );
                    }

                    if ($params[$i]->signature_type
                        && $params[$i]->signature_type->isNullable()
                    ) {
                        $params[$i]->type->addType(new Type\Atomic\TNull);
                    }

                    $params[$i]->type_location = $overridden_storage->params[$i]->type_location;
                }
            }

            return $params;
        }

        throw new \UnexpectedValueException('Cannot get method params for ' . $method_id);
    }

    private static function localizeParamType(
        Codebase $codebase,
        Type\Union $type,
        string $appearing_fq_class_name,
        string $base_fq_class_name
    ) : Type\Union {
        $class_storage = $codebase->classlike_storage_provider->get($appearing_fq_class_name);
        $extends = $class_storage->template_type_extends;

        if (!$extends) {
            return $type;
        }

        $type = clone $type;

        foreach ($type->getTypes() as $key => $atomic_type) {
            if ($atomic_type instanceof Type\Atomic\TTemplateParam
                || $atomic_type instanceof Type\Atomic\TTemplateParamClass
            ) {
                if ($atomic_type->defining_class === $base_fq_class_name) {
                    if (isset($extends[$base_fq_class_name][$atomic_type->param_name])) {
                        $extended_param = $extends[$base_fq_class_name][$atomic_type->param_name];

                        $type->removeType($key);
                        $type = Type::combineUnionTypes(
                            $type,
                            $extended_param,
                            $codebase
                        );
                    }
                }
            }

            if ($atomic_type instanceof Type\Atomic\TArray
                || $atomic_type instanceof Type\Atomic\TIterable
                || $atomic_type instanceof Type\Atomic\TGenericObject
            ) {
                foreach ($atomic_type->type_params as &$type_param) {
                    $type_param = self::localizeParamType(
                        $codebase,
                        $type_param,
                        $appearing_fq_class_name,
                        $base_fq_class_name
                    );
                }
            }

            if ($atomic_type instanceof Type\Atomic\TCallable
                || $atomic_type instanceof Type\Atomic\TFn
            ) {
                if ($atomic_type->params) {
                    foreach ($atomic_type->params as $param) {
                        if ($param->type) {
                            $param->type = self::localizeParamType(
                                $codebase,
                                $param->type,
                                $appearing_fq_class_name,
                                $base_fq_class_name
                            );
                        }
                    }
                }

                if ($atomic_type->return_type) {
                    $atomic_type->return_type = self::localizeParamType(
                        $codebase,
                        $atomic_type->return_type,
                        $appearing_fq_class_name,
                        $base_fq_class_name
                    );
                }
            }
        }

        return $type;
    }

    /**
     * @param  string $method_id
     *
     * @return bool
     */
    public function isVariadic($method_id)
    {
        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            return false;
        }

        $method_id = $declaring_method_id;

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
        list($original_fq_class_name, $original_method_name) = explode('::', $method_id);

        $original_fq_class_name = $this->classlikes->getUnAliasedName($original_fq_class_name);

        $original_class_storage = $this->classlike_storage_provider->get($original_fq_class_name);

        if (isset($original_class_storage->pseudo_methods[strtolower($original_method_name)])) {
            return $original_class_storage->pseudo_methods[strtolower($original_method_name)]->return_type;
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            return null;
        }

        $appearing_method_id = $this->getAppearingMethodId($method_id);

        if (!$appearing_method_id) {
            list($fq_class_name, $method_name) = explode('::', $method_id);

            $fq_class_name = $this->classlikes->getUnAliasedName($fq_class_name);

            $class_storage = $this->classlike_storage_provider->get($fq_class_name);

            if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
                $appearing_method_id = reset($class_storage->overridden_method_ids[$method_name]);
            } else {
                return null;
            }
        }

        list($appearing_fq_class_name, $appearing_method_name) = explode('::', $appearing_method_id);

        $appearing_fq_class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if (!$appearing_fq_class_storage->user_defined
            && !$appearing_fq_class_storage->stubbed
            && CallMap::inCallMap($appearing_method_id)
        ) {
            if ($appearing_method_id === 'Closure::fromcallable'
                && isset($args[0]->value->inferredType)
                && $args[0]->value->inferredType->isSingle()
            ) {
                foreach ($args[0]->value->inferredType->getTypes() as $atomic_type) {
                    if ($atomic_type instanceof Type\Atomic\TCallable
                        || $atomic_type instanceof Type\Atomic\TFn
                    ) {
                        $callable_type = clone $atomic_type;

                        return new Type\Union([new Type\Atomic\TFn(
                            'Closure',
                            $callable_type->params,
                            $callable_type->return_type
                        )]);
                    }

                    if ($atomic_type instanceof Type\Atomic\TNamedObject
                        && $this->methodExists($atomic_type->value . '::__invoke')
                    ) {
                        $invokable_storage = $this->getStorage($atomic_type->value . '::__invoke');

                        return new Type\Union([new Type\Atomic\TFn(
                            'Closure',
                            $invokable_storage->params,
                            $invokable_storage->return_type
                        )]);
                    }
                }
            }

            $callmap_callables = CallMap::getCallablesFromCallMap($appearing_method_id);

            if (!$callmap_callables || $callmap_callables[0]->return_type === null) {
                throw new \UnexpectedValueException('Shouldn’t get here');
            }

            $return_type_candidate = $callmap_callables[0]->return_type;

            if ($return_type_candidate->isFalsable()) {
                $return_type_candidate->ignore_falsable_issues = true;
            }

            return $return_type_candidate;
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

        $fq_class_name = $this->classlikes->getUnAliasedName($fq_class_name);

        $class_storage = $this->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            return $class_storage->declaring_method_ids[$method_name];
        }

        if ($class_storage->abstract && isset($class_storage->overridden_method_ids[$method_name])) {
            return reset($class_storage->overridden_method_ids[$method_name]);
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

        $fq_class_name = $this->classlikes->getUnAliasedName($fq_class_name);

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
            return $original_method_id;
        }

        $storage = $this->getStorage($method_id);

        list($fq_class_name) = explode('::', $method_id);

        return $fq_class_name . '::' . $storage->cased_name;
    }

    /**
     * @param  string $method_id
     *
     * @return ?MethodStorage
     */
    public function getUserMethodStorage($method_id)
    {
        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
        }

        $storage = $this->getStorage($declaring_method_id);

        if (!$storage->location) {
            return null;
        }

        return $storage;
    }

    /**
     * @param  string $method_id
     *
     * @return ClassLikeStorage
     */
    public function getClassLikeStorageForMethod($method_id)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);

        if ($this->existence_provider->has($fq_class_name)) {
            if ($this->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_name,
                null,
                null
            )) {
                return $this->classlike_storage_provider->get($fq_class_name);
            }
        }

        $declaring_method_id = $this->getDeclaringMethodId($method_id);

        if (!$declaring_method_id) {
            if (CallMap::inCallMap($method_id)) {
                $declaring_method_id = $method_id;
            } else {
                throw new \UnexpectedValueException('$storage should not be null for ' . $method_id);
            }
        }

        list($declaring_fq_class_name) = explode('::', $declaring_method_id);

        return $this->classlike_storage_provider->get($declaring_fq_class_name);
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
