<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\CallPurityResolver;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ClassTemplateParamCollector;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualArrayItem;
use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function array_map;
use function array_merge;
use function array_reverse;

/**
 * @internal
 */
final class MissingMethodCallHandler
{
    public static function handleMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        ClassLikeStorage $class_storage,
        Context $context,
        Config $config,
        ?Union $all_intersection_return_type,
        AtomicMethodCallAnalysisResult $result,
        ?Atomic $lhs_type_part,
        ?string $lhs_var_id,
    ): ?AtomicCallContext {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        if ($stmt->isFirstClassCallable()) {
            if (isset($class_storage->pseudo_methods[$method_name_lc])) {
                $result->has_valid_method_call_type = true;
                $result->existent_method_ids[$method_id->__toString()] = true;
                $result->return_type = self::createFirstClassCallableReturnType(
                    $class_storage->pseudo_methods[$method_name_lc],
                );
            } else {
                $result->non_existent_magic_method_ids[] = $method_id->__toString();
                $result->return_type = self::createFirstClassCallableReturnType();
            }

            return null;
        }

        $found_method_and_class_storage = self::findPseudoMethodAndClassStorages(
            $codebase,
            $class_storage,
            $method_name_lc,
        );

        // Merged before the return type provider short-circuit, which would skip it otherwise
        if ($found_method_and_class_storage && !$context->isSuppressingExceptions($statements_analyzer)) {
            $context->mergeFunctionExceptions(
                $found_method_and_class_storage[0],
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
            );
        }

        if ($codebase->methods->return_type_provider->has($fq_class_name)) {
            $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                $statements_analyzer,
                $method_id->fq_class_name,
                $method_id->method_name,
                $stmt,
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt->name),
            );

            if ($return_type_candidate) {
                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase,
                    ) ?? Type::getMixed();
                }

                $result->return_type = Type::combineUnionTypes(
                    $return_type_candidate,
                    $result->return_type,
                    $codebase,
                );

                CallAnalyzer::checkMethodArgs(
                    $method_id,
                    $stmt->getArgs(),
                    new TemplateResult([], []),
                    $context,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $statements_analyzer,
                );

                return null;
            }
        }

        if ($found_method_and_class_storage) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[$method_id->__toString()] = true;

            [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

            $found_generic_params = ClassTemplateParamCollector::collect(
                $codebase,
                $defining_class_storage,
                $class_storage,
                $method_name_lc,
                $lhs_type_part,
                !$statements_analyzer->isStatic() && $method_id->fq_class_name === $context->self,
            );

            // the pseudo-method's own templates are the purity templates of its `_` params
            $template_result = new TemplateResult(
                $pseudo_method_storage->template_types ?? [],
                $found_generic_params ?? [],
            );

            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context,
                $template_result->template_types !== [] || $template_result->lower_bounds !== []
                    ? $template_result
                    : null,
            );

            ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                $template_result,
                new CodeLocation($statements_analyzer, $stmt),
                $context,
            );

            self::analyzePseudoMethodPurity(
                $statements_analyzer,
                $codebase,
                $stmt,
                $lhs_var_id,
                $method_id,
                $pseudo_method_storage,
                $class_storage,
                $context,
                $config,
                $result,
                $template_result,
                $found_generic_params ?? [],
            );

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = $pseudo_method_storage->return_type;

                if ($found_generic_params) {
                    $return_type_candidate = TemplateInferredTypeReplacer::replace(
                        $return_type_candidate,
                        new TemplateResult([], $found_generic_params),
                        $codebase,
                    );
                }

                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $defining_class_storage->name,
                    $lhs_type_part instanceof Atomic\TNamedObject ? $lhs_type_part : $fq_class_name,
                    $defining_class_storage->parent_class,
                );

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase,
                    ) ?? Type::getMixed();
                }

                $result->return_type = Type::combineUnionTypes(
                    $return_type_candidate,
                    $result->return_type,
                    $codebase,
                );

                return null;
            }
        } elseif ($all_intersection_return_type === null) {
            ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                null,
                true,
                $context,
            );

            if ($class_storage->hasSealedMethods($config)) {
                $result->non_existent_magic_method_ids[] = $method_id->__toString();

                return null;
            }
        }

        $result->has_valid_method_call_type = true;
        $result->existent_method_ids[$method_id->__toString()] = true;

        $array_values = array_map(
            static fn(PhpParser\Node\Arg $arg): PhpParser\Node\ArrayItem => new VirtualArrayItem(
                $arg->value,
                null,
                false,
                $arg->getAttributes(),
            ),
            $stmt->getArgs(),
        );

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        return new AtomicCallContext(
            new MethodIdentifier($fq_class_name, '__call'),
            [
                new VirtualArg(
                    new VirtualString($method_name_lc),
                    false,
                    false,
                    $stmt->getAttributes(),
                ),
                new VirtualArg(
                    new VirtualArray(
                        $array_values,
                        $stmt->getAttributes(),
                    ),
                    false,
                    false,
                    $stmt->getAttributes(),
                ),
            ],
        );
    }

    /**
     * A pseudo-method is implemented by `__call`: a call to it needs what `__call` does, and what
     * its purity templates (`@method int run(Closure[_](): int $f)`) are bound to by the arguments.
     * Without a return type, the call is then analysed as a call to `__call`, which is charged for
     * `__call` itself.
     *
     * @param array<string, array<string, Union>> $found_generic_params
     */
    private static function analyzePseudoMethodPurity(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        ?string $lhs_var_id,
        MethodIdentifier $method_id,
        MethodStorage $pseudo_method_storage,
        ClassLikeStorage $class_storage,
        Context $context,
        Config $config,
        AtomicMethodCallAnalysisResult $result,
        TemplateResult $template_result,
        array $found_generic_params,
    ): void {
        $cased_method_id = $class_storage->name . '::'
            . ($pseudo_method_storage->cased_name ?? $method_id->method_name);

        if (!$pseudo_method_storage->return_type) {
            $statements_analyzer->signalMutation(
                CallPurityResolver::getCallCapabilities(
                    $statements_analyzer,
                    $codebase,
                    $pseudo_method_storage,
                    Capabilities::NONE,
                    $template_result,
                ),
                $context,
                'method ' . $cased_method_id,
                ImpureMethodCall::class,
                $stmt->name,
            );

            return;
        }

        $magic_method_id = $codebase->methods->getDeclaringMethodId(
            new MethodIdentifier($class_storage->name, '__call'),
        );

        if ($magic_method_id === null) {
            return;
        }

        $call_storage = clone $codebase->methods->getStorage($magic_method_id);
        $call_storage->setParams($pseudo_method_storage->params);
        $call_storage->purity_from_templates = $pseudo_method_storage->purity_from_templates;

        MethodCallPurityAnalyzer::analyze(
            $statements_analyzer,
            $codebase,
            $stmt,
            $lhs_var_id,
            $cased_method_id,
            $method_id,
            $call_storage,
            $class_storage,
            $context,
            $config,
            $result,
            $template_result,
            $found_generic_params,
        );
    }

    /**
     * @param array<string, bool> $all_intersection_existent_method_ids
     */
    public static function handleMissingOrMagicMethod(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        bool $is_interface,
        Context $context,
        Config $config,
        ?Union $all_intersection_return_type,
        array $all_intersection_existent_method_ids,
        ?string $intersection_method_id,
        string $cased_method_id,
        AtomicMethodCallAnalysisResult $result,
        ?Atomic $lhs_type_part,
    ): void {
        $fq_class_name = $method_id->fq_class_name;
        $method_name_lc = $method_id->method_name;

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $found_method_and_class_storage = self::findPseudoMethodAndClassStorages(
            $codebase,
            $class_storage,
            $method_name_lc,
        );

        if (($is_interface || $config->use_phpdoc_method_without_magic_or_parent)
            && $found_method_and_class_storage
        ) {
            $result->has_valid_method_call_type = true;
            $result->existent_method_ids[$method_id->__toString()] = true;

            [$pseudo_method_storage, $defining_class_storage] = $found_method_and_class_storage;

            if ($stmt->isFirstClassCallable()) {
                $result->return_type = self::createFirstClassCallableReturnType($pseudo_method_storage);
                return;
            }

            if (!$context->isSuppressingExceptions($statements_analyzer)) {
                $context->mergeFunctionExceptions(
                    $pseudo_method_storage,
                    new CodeLocation($statements_analyzer, $stmt->name),
                );
            }

            $found_generic_params = ClassTemplateParamCollector::collect(
                $codebase,
                $defining_class_storage,
                $class_storage,
                $method_name_lc,
                $lhs_type_part,
                !$statements_analyzer->isStatic() && $method_id->fq_class_name === $context->self,
            );

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->getArgs(),
                $pseudo_method_storage->params,
                (string) $method_id,
                true,
                $context,
                $found_generic_params ? new TemplateResult([], $found_generic_params) : null,
            ) === false) {
                return;
            }

            if (ArgumentsAnalyzer::checkArgumentsMatch(
                $statements_analyzer,
                $stmt->getArgs(),
                null,
                $pseudo_method_storage->params,
                $pseudo_method_storage,
                null,
                new TemplateResult([], $found_generic_params ?: []),
                new CodeLocation($statements_analyzer, $stmt->name),
                $context,
            ) === false) {
                return;
            }

            if ($pseudo_method_storage->return_type) {
                $return_type_candidate = $pseudo_method_storage->return_type;

                if ($found_generic_params) {
                    $return_type_candidate = TemplateInferredTypeReplacer::replace(
                        $return_type_candidate,
                        new TemplateResult([], $found_generic_params),
                        $codebase,
                    );
                }

                if ($all_intersection_return_type) {
                    $return_type_candidate = Type::intersectUnionTypes(
                        $all_intersection_return_type,
                        $return_type_candidate,
                        $codebase,
                    ) ?? Type::getMixed();
                }

                $return_type_candidate = TypeExpander::expandUnion(
                    $codebase,
                    $return_type_candidate,
                    $defining_class_storage->name,
                    $lhs_type_part instanceof Atomic\TNamedObject ? $lhs_type_part : $fq_class_name,
                    $defining_class_storage->parent_class,
                    true,
                    false,
                    $class_storage->final,
                );

                $result->return_type = Type::combineUnionTypes($return_type_candidate, $result->return_type);

                return;
            }

            $result->return_type = Type::getMixed();

            return;
        }

        if ($stmt->isFirstClassCallable()) {
            $result->non_existent_class_method_ids[] = $method_id->__toString();
            $result->return_type = self::createFirstClassCallableReturnType();
            return;
        }

        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $stmt->getArgs(),
            null,
            null,
            true,
            $context,
        ) === false) {
            return;
        }

        if ($all_intersection_return_type && $all_intersection_existent_method_ids) {
            $result->existent_method_ids = array_merge(
                $result->existent_method_ids,
                $all_intersection_existent_method_ids,
            );

            $result->return_type = Type::combineUnionTypes($all_intersection_return_type, $result->return_type);

            return;
        }

        if ((!$is_interface && !$config->use_phpdoc_method_without_magic_or_parent)
            || !isset($class_storage->pseudo_methods[$method_name_lc])
        ) {
            if ($is_interface) {
                $result->non_existent_interface_method_ids[] = $intersection_method_id ?: $cased_method_id;
            } else {
                $result->non_existent_class_method_ids[] = $intersection_method_id ?: $cased_method_id;
            }
        }
    }

    /**
     * @psalm-mutation-free
     */
    private static function createFirstClassCallableReturnType(?MethodStorage $method_storage = null): Union
    {
        if ($method_storage) {
            return new Union([new TClosure(
                $method_storage->params,
                $method_storage->return_type,
                $method_storage->capabilities,
            )]);
        }

        return Type::getClosure();
    }

    /**
     * Try to find matching pseudo method over ancestors (including interfaces).
     *
     * Returns the pseudo method if exists, with its defining class storage.
     * If the method is not declared, null is returned.
     *
     * @param ClassLikeStorage $static_class_storage The called class
     * @param lowercase-string $method_name_lc
     * @return array{MethodStorage, ClassLikeStorage}
     * @psalm-mutation-free
     */
    private static function findPseudoMethodAndClassStorages(
        Codebase $codebase,
        ClassLikeStorage $static_class_storage,
        string $method_name_lc,
    ): ?array {
        if (isset($static_class_storage->declaring_pseudo_method_ids[$method_name_lc])) {
            $method_id = $static_class_storage->declaring_pseudo_method_ids[$method_name_lc];
            $class_storage = $codebase->classlikes->getStorageFor($method_id->fq_class_name);

            if ($class_storage && isset($class_storage->pseudo_methods[$method_name_lc])) {
                return [$class_storage->pseudo_methods[$method_name_lc], $class_storage];
            }
        }

        if ($pseudo_method_storage = $static_class_storage->pseudo_methods[$method_name_lc] ?? null) {
            return [$pseudo_method_storage, $static_class_storage];
        }

        $ancestors = $static_class_storage->class_implements;
        // First match wins here, so the nearest mixins come first.
        $named_mixins = [
            ...$static_class_storage->namedMixins,
            ...array_reverse($static_class_storage->transitiveNamedMixins),
        ];
        foreach ($named_mixins as $namedObject) {
            $type = $namedObject->value;
            if ($type) {
                $ancestors[$type] = true;
            }
        }

        foreach ($ancestors as $fq_class_name => $_) {
            $class_storage = $codebase->classlikes->getStorageFor($fq_class_name);

            if ($class_storage && isset($class_storage->pseudo_methods[$method_name_lc])) {
                return [
                    $class_storage->pseudo_methods[$method_name_lc],
                    $class_storage,
                ];
            }
        }

        return null;
    }
}
