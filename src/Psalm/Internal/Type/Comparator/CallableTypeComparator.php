<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Exception;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\Capabilities;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_slice;
use function assert;
use function count;
use function end;
use function is_numeric;
use function ltrim;
use function str_starts_with;
use function strpos;
use function strtolower;
use function substr;

/**
 * @internal
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class CallableTypeComparator
{
    /**
     * @param  TCallable|TClosure   $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {
        if ($container_type_part instanceof TClosure) {
            if ($input_type_part->isCallableType()
                && !$input_type_part instanceof TCallable // it has stricter checks below
                && !$input_type_part instanceof TClosure // it has stricter checks below
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
                return false;
            }
        }
        if ($input_type_part->isCallableType()
            && !$input_type_part instanceof TCallable // it has stricter checks below
            && !$input_type_part instanceof TClosure // it has stricter checks below
        ) {
            return true;
        }
        assert($input_type_part instanceof TClosure || $input_type_part instanceof TCallable);

        // a callable needing fewer capabilities fits where more are allowed, never the reverse
        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $input_type_part->purity,
            $container_type_part->purity,
        )) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part->params !== null && $input_type_part->params === null) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            }

            return false;
        }

        $input_variadic_param_idx = null;

        if ($input_type_part->params !== null && $container_type_part->params !== null) {
            foreach ($input_type_part->params as $i => $input_param) {
                $container_param = null;

                if (isset($container_type_part->params[$i])) {
                    $container_param = $container_type_part->params[$i];
                } elseif ($container_type_part->params) {
                    $last_param = end($container_type_part->params);

                    if ($last_param->is_variadic) {
                        $container_param = $last_param;
                    }
                }

                if ($input_param->is_variadic) {
                    $input_variadic_param_idx = $i;
                }

                if (!$container_param) {
                    if ($input_param->is_variadic) {
                        break;
                    }

                    if ($input_param->is_optional) {
                        break;
                    }

                    return false;
                }

                // a callee that takes the argument by reference can't be called with a value: it is a subtype all the
                // same, and reported where the type is checked
                if ($input_param->by_ref && !$container_param->by_ref && $atomic_comparison_result) {
                    $atomic_comparison_result->callable_by_reference_mismatch = true;
                }

                // a callable that may be called without the parameter must not require it
                if ($codebase->config->strict_optional_callable_params
                    && $container_param->is_optional
                    && !$input_param->is_optional
                    && !$input_param->is_variadic
                ) {
                    return false;
                }

                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !self::isParamContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
                        $atomic_comparison_result,
                    )
                ) {
                    return false;
                }
            }
        }

        if ($input_variadic_param_idx && isset($input_type_part->params[$input_variadic_param_idx])) {
            $input_param = $input_type_part->params[$input_variadic_param_idx];

            foreach (array_slice($container_type_part->params ?? [], $input_variadic_param_idx) as $container_param) {
                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !self::isParamContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
                        $atomic_comparison_result,
                    )
                ) {
                    return false;
                }
            }
        }

        if (isset($container_type_part->return_type)) {
            if (!isset($input_type_part->return_type)) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            $input_return = $input_type_part->return_type;

            if ($input_return->isVoid() && $container_type_part->return_type->isNullable()) {
                return true;
            }

            if (!$container_type_part->return_type->isVoid()
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_return,
                    $container_type_part->return_type,
                    false,
                    false,
                    $atomic_comparison_result,
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares a contravariant parameter position: the container's parameter
     * type must be contained by the input's. Any type-variable bound recorded
     * by the comparison is already in the correct direction (see the note in
     * the body).
     */
    private static function isParamContainedBy(
        Codebase $codebase,
        Union $container_param_type,
        Union $input_param_type,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {
        // Contravariant position: the container's parameter type must be
        // contained by the input's. Passing them to isContainedBy in this
        // order already records any type-variable bound in the correct
        // direction — a variable in the container's parameter is in input
        // position here, so `callable(`_0):_` against `callable(Item):_`
        // records `_0 <: Item`, which is exactly the constraint the value
        // imposes. No post-hoc flip is needed.
        if (!$atomic_comparison_result) {
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $container_param_type,
                $input_param_type,
            );
        }

        return UnionTypeComparator::isContainedBy(
            $codebase,
            $container_param_type,
            $input_param_type,
            false,
            false,
            $atomic_comparison_result,
        );
    }

    public static function isNotExplicitlyCallableTypeCallable(
        Codebase $codebase,
        Atomic $input_type_part,
        TCallable $container_type_part,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {
        // the name of a function is neither empty nor numeric
        if ($input_type_part instanceof TLiteralString
            && ($input_type_part->value === '' || is_numeric($input_type_part->value))
        ) {
            return false;
        }

        if ($input_type_part instanceof TArray) {
            if ($input_type_part->type_params[1]->isMixed()
                || $input_type_part->type_params[1]->hasScalar()
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if (!$input_type_part->type_params[1]->hasString()) {
                return false;
            }
        } elseif ($input_type_part instanceof TKeyedArray) {
            $method_id = self::getCallableMethodIdFromTKeyedArray($input_type_part, $codebase);

            if ($method_id === 'not-callable') {
                return false;
            }

            if (!$method_id) {
                return true;
            }

            try {
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$declaring_method_id) {
                    // a method declared by `@method` is called through __call() or __callStatic()
                    $class_storage = $codebase->classlike_storage_provider->get($method_id->fq_class_name);
                    $pseudo_method = $class_storage->pseudo_methods[$method_id->method_name]
                        ?? $class_storage->pseudo_static_methods[$method_id->method_name]
                        ?? null;

                    if ($pseudo_method === null) {
                        return false;
                    }

                    return self::isContainedBy(
                        $codebase,
                        new TCallable(
                            $pseudo_method->params,
                            $pseudo_method->return_type,
                            self::getCallableCapabilities($pseudo_method, []),
                        ),
                        $container_type_part,
                        $atomic_comparison_result,
                    );
                }

                $method_id = $declaring_method_id;

                if (!$codebase->methods->hasStorage($method_id)) {
                    return false;
                }
            } catch (Exception) {
                return false;
            }
        }

        $input_callable = self::getCallableFromAtomic(
            $codebase,
            $input_type_part,
            $container_type_part,
            null,
            null,
            true,
        );

        if ($input_callable) {
            if (self::isContainedBy(
                $codebase,
                $input_callable,
                $container_type_part,
                $atomic_comparison_result,
            ) === false
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * The callable of a method of an object (`[$box, 'set']`, an invokable `Box<int>`) with the class templates
     * bound by the object: by its type arguments, and by the arguments it gives the generic classes it extends
     * (`@extends Box<int>`). A class extending a generic class without saying so binds them to `mixed`. A bare
     * class with templates of its own leaves them to the callable, where no argument binds them.
     */
    private static function bindReceiverTemplates(
        Codebase $codebase,
        TCallable $callable,
        TNamedObject $receiver,
        MethodIdentifier $declaring_method_id,
    ): TCallable {
        if (!$codebase->classOrInterfaceExists($receiver->value)) {
            return $callable;
        }

        $receiver_storage = $codebase->classlike_storage_provider->get($receiver->value);

        $receiver_template_types = ClassTemplateParamCollector::collect(
            $codebase,
            $codebase->methods->getClassLikeStorageForMethod($declaring_method_id),
            $receiver_storage,
            $declaring_method_id->method_name,
            $receiver,
            !$receiver instanceof TGenericObject && ($receiver_storage->template_types ?? []) !== [],
        );

        if ($receiver_template_types === null) {
            return $callable;
        }

        // what the method returns from a `Box<*>` is as unknown as the receiver, and the parameters of the callable,
        // which the replacement reads from the other side, are never
        $receiver_template_result = new TemplateResult([], $receiver_template_types);
        ClassTemplateParamCollector::markStarBindings(
            $receiver_template_result,
            $codebase,
            $receiver_storage,
            $receiver,
            $receiver_template_types,
        );

        $bound_callable = TemplateInferredTypeReplacer::replace(
            new Union([$callable]),
            $receiver_template_result,
            $codebase,
        )->getSingleAtomic();

        return $bound_callable instanceof TCallable ? $bound_callable : $callable;
    }

    private static function getCallableFromMethodId(
        Codebase $codebase,
        MethodIdentifier $method_id,
        ?TCallable $container_type_part,
        ?Atomic $callable_object = null,
        bool $exact_static = false,
    ): ?TCallable {
        if (!$codebase->classlike_storage_provider->has($method_id->fq_class_name)) {
            return null;
        }

        try {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id) ?? $method_id;
            $method_storage = $codebase->methods->getStorage($declaring_method_id);
            $method_fqcln = $method_id->fq_class_name;

            $converted_return_type = null;

            if ($method_storage->return_type) {
                $converted_return_type = TypeExpander::expandUnion(
                    $codebase,
                    $method_storage->return_type,
                    // `self` is the class the method appears in: the one using the trait, for a trait method
                    ($codebase->methods->getAppearingMethodId($method_id) ?? $declaring_method_id)->fq_class_name,
                    $method_fqcln,
                    null,
                    true,
                    false,
                    $exact_static || $codebase->classlike_storage_provider->get($method_fqcln)->final,
                );
            }

            $class_template_types = $codebase->methods->getClassLikeStorageForMethod($declaring_method_id)
                ->template_types ?? [];

            $callable = self::withoutPurityTemplates(
                new TCallable(
                    // the parameters an override leaves undocumented are those of the method it overrides
                    $codebase->methods->getMethodParams($method_id),
                    $converted_return_type,
                    self::getCallableCapabilities($method_storage, $class_template_types),
                    callable_id: strtolower((string) $declaring_method_id),
                ),
                ($method_storage->template_types ?? []) + $class_template_types,
                $codebase,
            );

            // a receiver of a template type is the type it is bound by
            if ($callable_object instanceof TTemplateParam && $callable_object->as->isSingle()) {
                $callable_object = $callable_object->as->getSingleAtomic();
            }

            if ($callable_object instanceof TNamedObject) {
                $receiver = $callable_object;

                // of an intersection, the receiver is the member that has the method. Where several do, their
                // signatures may differ, so only a generic first member binds
                if ($callable_object->getIntersectionTypes() !== []) {
                    $suppliers = [];

                    foreach ([$callable_object, ...$callable_object->getIntersectionTypes()] as $member) {
                        if ($member instanceof TNamedObject
                            && self::classHasMethod($codebase, $member->value, $method_id->method_name)
                        ) {
                            $suppliers[] = $member;
                        }
                    }

                    if (count($suppliers) === 1) {
                        $receiver = $suppliers[0];
                    } elseif (count($suppliers) > 1 && !$callable_object instanceof TGenericObject) {
                        $receiver = null;
                    }
                }

                if ($receiver !== null) {
                    $callable = self::bindReceiverTemplates($codebase, $callable, $receiver, $declaring_method_id);
                }
            }

            // Resolve method-level templates against the expected callable shape, so
            // `[Id::class, 'id']` with `@template B` matches `callable(int): int` etc.
            if ($method_storage->template_types !== null && $container_type_part !== null) {
                $template_result = new TemplateResult($method_storage->template_types, []);

                TemplateStandinTypeReplacer::fillTemplateResult(
                    new Union([$callable]),
                    $template_result,
                    $codebase,
                    null,
                    new Union([$container_type_part]),
                );

                $replaced = TemplateInferredTypeReplacer::replace(
                    new Union([$callable]),
                    $template_result,
                    $codebase,
                )->getSingleAtomic();

                if ($replaced instanceof TCallable) {
                    $callable = $replaced;
                }
            }

            // the templates generated for the parameters are resolved, those of the caller are not the method's
            $generated_templates = [];

            foreach ($method_storage->template_types ?? [] as $template_name => $defining_types) {
                if (str_starts_with($template_name, 'TGeneratedFromParam')) {
                    $generated_templates[$template_name] = $defining_types;
                }
            }

            if ($generated_templates !== []) {
                $replaced = TemplateInferredTypeReplacer::replace(
                    new Union([$callable]),
                    new TemplateResult([], $generated_templates),
                    $codebase,
                )->getSingleAtomic();

                if ($replaced instanceof TCallable) {
                    $callable = $replaced;
                }
            }

            $closure = self::getClosureOfMethod(
                $codebase,
                $callable->params ?? [],
                $callable->return_type,
                $callable->getCapabilities(),
                // the class the method appears in: the one using the trait, for a trait method
                ($codebase->methods->getAppearingMethodId($method_id) ?? $declaring_method_id)->fq_class_name,
                $method_fqcln,
                $exact_static || $codebase->classlike_storage_provider->get($method_fqcln)->final,
            );

            $callable = $callable->replace($closure->params, $closure->return_type);

            return $callable;
        } catch (UnexpectedValueException) {
            return null;
        }
    }

    /**
     * The closure a first-class callable of a method is: its signature with `self`, `static` and `parent`, the
     * conditional return type, and the templates generated for its parameters resolved, as for a function
     *
     * @param array<int, FunctionLikeParameter> $method_params
     * @param CapabilitySet $capabilities
     * @param bool $exact_static whether `static` is the class itself, as for `C::m(...)`, and not a subclass of it
     * @param bool $evaluate_conditional_types whether a conditional type is evaluated: not where the arguments of
     *     the call that resolve it are not known yet, as for an invokable object
     */
    private static function getClosureOfMethod(
        Codebase $codebase,
        array $method_params,
        ?Union $return_type,
        int $capabilities,
        ?string $self_class,
        string $static_class,
        bool $exact_static = false,
        bool $evaluate_conditional_types = true,
    ): TClosure {
        $parent_class = $self_class !== null && $codebase->classlike_storage_provider->has($self_class)
            ? $codebase->classlike_storage_provider->get($self_class)->parent_class
            : null;

        $params = [];

        foreach ($method_params as $param) {
            if ($param->type) {
                $param = $param->setType(
                    self::expandForClosure(
                        $codebase,
                        $param->type,
                        $self_class,
                        $static_class,
                        $parent_class,
                        $exact_static,
                        $evaluate_conditional_types,
                    ),
                );
            }

            if ($param->out_type) {
                $param = $param->setOutType(
                    self::expandForClosure(
                        $codebase,
                        $param->out_type,
                        $self_class,
                        $static_class,
                        $parent_class,
                        $exact_static,
                        $evaluate_conditional_types,
                    ),
                );
            }

            $params[] = $param;
        }

        return new TClosure(
            $params,
            $return_type
                ? self::expandForClosure(
                    $codebase,
                    $return_type,
                    $self_class,
                    $static_class,
                    $parent_class,
                    $exact_static,
                    $evaluate_conditional_types,
                )
                : null,
            $capabilities,
        );
    }

    private static function expandForClosure(
        Codebase $codebase,
        Union $type,
        ?string $self_class,
        string $static_class,
        ?string $parent_class,
        bool $exact_static,
        bool $evaluate_conditional_types,
    ): Union {
        return TypeExpander::expandUnion(
            $codebase,
            $type,
            $self_class,
            $static_class,
            $parent_class,
            true,
            $evaluate_conditional_types,
            $exact_static,
            false,
            false,
        );
    }

    /**
     * @return TCallable|TClosure|null
     */
    public static function getCallableFromAtomic(
        Codebase $codebase,
        Atomic $input_type_part,
        ?TCallable $container_type_part = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Context $context = null,
        bool $expand_callable = false,
    ): ?Atomic {

        if ($input_type_part instanceof TCallable || $input_type_part instanceof TClosure) {
            return $input_type_part;
        }

        if ($input_type_part instanceof TLiteralString && $input_type_part->value) {
            $method_separator = strpos($input_type_part->value, '::');

            if ($method_separator !== false) {
                $callable = self::getCallableFromMethodId(
                    $codebase,
                    new MethodIdentifier(
                        $codebase->classlikes->getUnAliasedName(
                            ltrim(substr($input_type_part->value, 0, $method_separator), '\\'),
                        ),
                        strtolower(substr($input_type_part->value, $method_separator + 2)),
                    ),
                    $container_type_part,
                    null,
                    true,
                );

                if ($callable) {
                    return $callable;
                }
            }

            try {
                $function_storage = $codebase->functions->getStorage(
                    $statements_analyzer,
                    strtolower($input_type_part->value),
                );

                if ($expand_callable) {
                    $params = [];

                    foreach ($function_storage->params as $param) {
                        if ($param->type) {
                            $param = $param->setType(
                                TypeExpander::expandUnion(
                                    $codebase,
                                    $param->type,
                                    null,
                                    null,
                                    null,
                                    true,
                                    true,
                                    false,
                                    false,
                                    true,
                                ),
                            );
                        }

                        $params[] = $param;
                    }

                    $return_type = null;

                    if ($function_storage->return_type) {
                        $return_type = TypeExpander::expandUnion(
                            $codebase,
                            $function_storage->return_type,
                            null,
                            null,
                            null,
                            true,
                            true,
                            false,
                            false,
                            true,
                        );
                    }
                } else {
                    $return_type = $function_storage->return_type;
                    $params = $function_storage->params;
                }

                $function_id = strtolower(ltrim($input_type_part->value, '\\'));

                // builtin functions are pure unless listed as impure, whatever their reflected or
                // stubbed storage says: the same rule direct calls follow
                $purity = InternalCallMapHandler::inCallMap($input_type_part->value)
                    ? $codebase->functions->getCallMapFunctionCapabilities(
                        $statements_analyzer,
                        $context,
                        $codebase,
                        $input_type_part->value,
                        null,
                    )
                    : self::getCallableCapabilities($function_storage);

                return self::withoutPurityTemplates(
                    new TCallable(
                        $params,
                        $return_type,
                        $purity,
                        callable_id: $function_id === '' ? null : $function_id,
                    ),
                    $function_storage->template_types ?? [],
                    $codebase,
                );
            } catch (UnexpectedValueException) {
                if (InternalCallMapHandler::inCallMap($input_type_part->value)) {
                    $args = [];

                    $nodes = new NodeDataProvider();

                    if ($container_type_part && $container_type_part->params) {
                        foreach ($container_type_part->params as $i => $param) {
                            $arg = new Arg(
                                new Variable('_' . $i),
                            );

                            if ($param->type) {
                                $nodes->setType($arg->value, $param->type);
                            }

                            $args[] = $arg;
                        }
                    }

                    $matching_callable = InternalCallMapHandler::getCallableFromCallMapById(
                        $codebase,
                        $input_type_part->value,
                        $args,
                        $nodes,
                    );

                    $must_use = false;

                    $matching_callable = $matching_callable->setPurity(
                        $codebase->functions->getCallMapFunctionCapabilities(
                            $statements_analyzer,
                            $context,
                            $codebase,
                            $input_type_part->value,
                            null,
                            $must_use,
                        ),
                    );

                    return $matching_callable;
                }
            }
        } elseif ($input_type_part instanceof TKeyedArray) {
            $method_id = self::getCallableMethodIdFromTKeyedArray($input_type_part, $codebase);
            if ($method_id && $method_id !== 'not-callable') {
                $callable_object = isset($input_type_part->properties[0])
                    && $input_type_part->properties[0]->isSingle()
                    ? $input_type_part->properties[0]->getSingleAtomic()
                    : null;

                $callable = self::getCallableFromMethodId(
                    $codebase,
                    $method_id,
                    $container_type_part,
                    $callable_object,
                    $callable_object instanceof TLiteralClassString,
                );

                if ($callable) {
                    return $callable;
                }
            }
        } elseif ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'Closure'
        ) {
            return new TClosure();
        } elseif ($input_type_part instanceof TNamedObject
            && ($invokable = self::getInvokeMember($codebase, $input_type_part)) !== null
            && ($codebase->classExists($invokable->value, null, $context)
                || $codebase->classlikes->enumExists($invokable->value))
        ) {
            $invoke_id = new MethodIdentifier(
                $invokable->value,
                '__invoke',
            );

            if ($codebase->methodExists($invoke_id)) {
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($invoke_id);
                $template_result = null;

                if ($invokable instanceof Atomic\TGenericObject) {
                    $invokable_storage = $codebase->methods->getClassLikeStorageForMethod(
                        $declaring_method_id ?? $invoke_id,
                    );
                    $type_params = [];

                    foreach ($invokable_storage->template_types ?? [] as $template => $for_class) {
                        foreach ($for_class as $type) {
                            $type_params[] = new Type\Union([
                                new TTemplateParam($template, $type, $invokable->value),
                            ]);
                        }
                    }

                    if (!empty($type_params)) {
                        $input_with_templates = new Atomic\TGenericObject($invokable->value, $type_params);
                        $template_result = new TemplateResult($invokable_storage->template_types ?? [], []);

                        TemplateStandinTypeReplacer::fillTemplateResult(
                            new Type\Union([$input_with_templates]),
                            $template_result,
                            $codebase,
                            null,
                            new Type\Union([$invokable]),
                        );
                    }
                }

                if ($declaring_method_id) {
                    $method_storage = $codebase->methods->getStorage($declaring_method_id);
                    $method_fqcln = $invoke_id->fq_class_name;
                    $converted_return_type = null;
                    if ($method_storage->return_type) {
                        $converted_return_type = TypeExpander::expandUnion(
                            $codebase,
                            $method_storage->return_type,
                            $method_fqcln,
                            $method_fqcln,
                            null,
                        );
                    }

                    $callable = new TCallable(
                        $method_storage->params,
                        $converted_return_type,
                        self::getCallableCapabilities(
                            $method_storage,
                            $codebase->methods->getClassLikeStorageForMethod($declaring_method_id)
                                ->template_types ?? [],
                            $template_result,
                        ),
                        callable_id: strtolower((string) $declaring_method_id),
                    );

                    $callable = self::bindReceiverTemplates(
                        $codebase,
                        $callable,
                        $invokable,
                        $declaring_method_id,
                    );

                    if ($template_result) {
                        $callable = TemplateInferredTypeReplacer::replace(
                            new Union([$callable]),
                            $template_result,
                            $codebase,
                        )->getSingleAtomic();
                    }

                    // the types the templates were bound to may name `self` or `static`
                    if ($callable instanceof TCallable) {
                        $closure = self::getClosureOfMethod(
                            $codebase,
                            $callable->params ?? [],
                            $callable->return_type,
                            $callable->getCapabilities(),
                            ($codebase->methods->getAppearingMethodId($invoke_id) ?? $declaring_method_id)
                                ->fq_class_name,
                            $method_fqcln,
                            $codebase->classlike_storage_provider->get($method_fqcln)->final,
                            false,
                        );

                        $callable = $callable->replace($closure->params, $closure->return_type);
                    }

                    /** @psalm-suppress LessSpecificReturnStatement */
                    return $callable;
                }
            }
        }

        return null;
    }

    /**
     * What calling a function-like through a callable value may do: its own capabilities, plus
     * what its purity templates are bound to by the value's type (the class template params of an
     * invokable object), or their bounds when nothing binds them: no call binds them any more.
     *
     * @param array<string, non-empty-array<string, Union>> $class_template_types
     * @return CapabilitySet
     * @psalm-mutation-free
     */
    private static function getCallableCapabilities(
        FunctionLikeStorage $storage,
        array $class_template_types = [],
        ?TemplateResult $template_result = null,
    ): int {
        $capabilities = $storage->capabilities;

        foreach ($storage->purity_from_templates as $template_name) {
            if (isset($template_result->lower_bounds[$template_name])) {
                foreach ($template_result->lower_bounds[$template_name] as $bounds) {
                    foreach ($bounds as $bound) {
                        $capabilities |= Capabilities::fromType($bound->type);
                    }
                }

                continue;
            }

            $bounds = $storage->template_types[$template_name] ?? $class_template_types[$template_name] ?? [];

            foreach ($bounds as $bound) {
                $capabilities |= Capabilities::fromType($bound);
            }
        }

        return $capabilities;
    }

    /**
     * A callable value made from a function-like is called without binding its purity templates,
     * which would otherwise leak into the types of its params (`Closure[P](int): int`): they are
     * replaced by their bounds, which is what the callable's own purity assumes.
     *
     * @param array<string, non-empty-array<string, Union>> $template_types
     */
    private static function withoutPurityTemplates(
        TCallable $callable,
        array $template_types,
        Codebase $codebase,
    ): TCallable {
        $lower_bounds = [];

        foreach ($template_types as $template_name => $bounds) {
            foreach ($bounds as $defining_class => $bound) {
                if (Capabilities::isPurityType($bound)) {
                    $lower_bounds[$template_name][$defining_class] = $bound;
                }
            }
        }

        if ($lower_bounds === []) {
            return $callable;
        }

        $replaced = TemplateInferredTypeReplacer::replace(
            new Union([$callable]),
            new TemplateResult([], $lower_bounds),
            $codebase,
        )->getSingleAtomic();

        return $replaced instanceof TCallable ? $replaced : $callable;
    }

    /**
     * @psalm-mutation-free
     */
    private static function classHasMethod(Codebase $codebase, string $class_name, string $method_name): bool
    {
        return $codebase->classlike_storage_provider->has($class_name)
            && isset(
                $codebase->classlike_storage_provider->get($class_name)->declaring_method_ids[strtolower($method_name)],
            );
    }

    /**
     * The classes of an intersection, through template bounds and nested intersections
     *
     * @param array<Atomic> $types
     * @return list<string>
     * @psalm-pure
     */
    public static function getIntersectionClassNames(array $types): array
    {
        $class_names = [];

        foreach ($types as $type) {
            if ($type instanceof TTemplateParam) {
                $class_names = [...$class_names, ...self::getIntersectionClassNames($type->as->getAtomicTypes())];
            } elseif ($type instanceof TNamedObject) {
                $class_names[] = $type->value;
            } else {
                continue;
            }

            $class_names = [...$class_names, ...self::getIntersectionClassNames($type->extra_types)];
        }

        return $class_names;
    }

    /**
     * The member of an object type whose `__invoke` it has: the type itself, or else the first class of those it is
     * intersected with
     */
    public static function getInvokeMember(Codebase $codebase, TNamedObject $object): ?TNamedObject
    {
        if ($codebase->classOrInterfaceOrEnumExists($object->value)
            && $codebase->methodExists(new MethodIdentifier($object->value, '__invoke'))
        ) {
            return $object;
        }

        foreach ($object->getIntersectionTypes() as $member) {
            if ($member instanceof TNamedObject
                && ($codebase->classExists($member->value) || $codebase->classlikes->enumExists($member->value))
                && $codebase->methodExists(new MethodIdentifier($member->value, '__invoke'))
            ) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @return null|'not-callable'|MethodIdentifier
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function getCallableMethodIdFromTKeyedArray(
        TKeyedArray $input_type_part,
        ?Codebase $codebase = null,
        ?string $calling_method_id = null,
        ?string $file_name = null,
    ): string|MethodIdentifier|null {
        if (!isset($input_type_part->properties[0])
            || !isset($input_type_part->properties[1])
            || count($input_type_part->properties) > 2
        ) {
            return 'not-callable';
        }

        [$lhs, $rhs] = $input_type_part->properties;

        $rhs_low_info = $rhs->hasMixed() || $rhs->hasScalar();

        if ($rhs_low_info || !$rhs->isSingleStringLiteral()) {
            if (!$rhs_low_info && !$rhs->hasString()) {
                return 'not-callable';
            }

            if ($codebase && ($calling_method_id || $file_name)) {
                foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($lhs_atomic_type->value) . '::',
                            $calling_method_id ?: $file_name,
                        );
                    } elseif ($lhs_atomic_type instanceof TTemplateParam) {
                        $lhs_template_type = $lhs_atomic_type->as;
                        if ($lhs_template_type->isSingle()) {
                            $lhs_template_atomic_type = $lhs_template_type->getSingleAtomic();
                            $member_id = null;
                            if ($lhs_template_atomic_type instanceof TNamedObject) {
                                $member_id = $lhs_template_atomic_type->value;
                            } elseif ($lhs_template_atomic_type instanceof TClassString) {
                                $member_id = $lhs_template_atomic_type->as;
                            }

                            if ($member_id) {
                                $codebase->analyzer->addMixedMemberName(
                                    strtolower($member_id) . '::',
                                    $calling_method_id ?: $file_name,
                                );
                            }
                        }
                    }
                }
            }

            return null;
        }

        $method_name = $rhs->getSingleStringLiteral()->value;

        $class_name = null;

        if ($lhs->isSingleStringLiteral()) {
            $class_name = ltrim($lhs->getSingleStringLiteral()->value, '\\');

            // names no class
            if ($class_name === '') {
                return 'not-callable';
            }

            if ($codebase) {
                $class_name = $codebase->classlikes->getUnAliasedName($class_name);
            }
        } elseif ($lhs->isSingle()) {
            foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_name = $lhs_atomic_type->value;

                    // of an intersection, the method is the one of the type that has it
                    if ($codebase && !self::classHasMethod($codebase, $class_name, $method_name)) {
                        foreach (self::getIntersectionClassNames([$lhs_atomic_type]) as $intersection_class) {
                            if (self::classHasMethod($codebase, $intersection_class, $method_name)) {
                                $class_name = $intersection_class;
                                break;
                            }
                        }
                    }
                } elseif ($lhs_atomic_type instanceof TTemplateParam) {
                    $lhs_template_type = $lhs_atomic_type->as;
                    if ($lhs_template_type->isSingle()) {
                        $lhs_template_atomic_type = $lhs_template_type->getSingleAtomic();
                        if ($lhs_template_atomic_type instanceof TNamedObject) {
                            $class_name = $lhs_template_atomic_type->value;
                        } elseif ($lhs_template_atomic_type instanceof TClassString) {
                            $class_name = $lhs_template_atomic_type->as;
                        }
                    }

                    if ($codebase
                        && ($class_name === null || !self::classHasMethod($codebase, $class_name, $method_name))
                    ) {
                        foreach (self::getIntersectionClassNames([$lhs_atomic_type]) as $intersection_class) {
                            if (self::classHasMethod($codebase, $intersection_class, $method_name)) {
                                $class_name = $intersection_class;
                                break;
                            }
                        }
                    }
                } elseif ($lhs_atomic_type instanceof TClassString
                    && $lhs_atomic_type->as
                ) {
                    $class_name = $lhs_atomic_type->as;
                }
            }
        }

        if ($class_name === 'self'
            || $class_name === 'static'
            || $class_name === 'parent'
        ) {
            return null;
        }

        if (!$class_name) {
            if ($codebase && ($calling_method_id || $file_name)) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($method_name),
                    $calling_method_id ?: $file_name,
                );
            }

            return null;
        }

        return new MethodIdentifier(
            $class_name,
            strtolower($method_name),
        );
    }
}
