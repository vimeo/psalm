<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call\Method;

use PhpParser;
use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\InstancePropertyAssignmentAnalyzer as AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NoDiscardAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\UnusedMethodCall;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Mutations;
use Psalm\Type;
use Psalm\Type\Union;

use function max;

/**
 * @internal
 */
final class MethodCallPurityAnalyzer
{
    /**
     * Whether mutations of the receiver's own state are fine for the caller:
     * the receiver is pure, free from references or external mutations, or $this.
     */
    public static function receiverAllowsInternalMutations(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        MethodIdentifier $method_id,
        Context $context,
    ): bool {
        // Already checked in isPureCompatible below
        // $stmt->var->getAttribute('pure', false)
        return $statements_analyzer->node_data->isPureCompatible($var)
            || $var->getAttribute('external_mutation_free', false)
            || $method_id->fq_class_name === $context->self;
    }

    /**
     * @return Mutations::LEVEL_*
     */
    public static function getMethodAllowedMutations(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        MethodIdentifier $method_id,
        MethodStorage $method_storage,
        Context $context,
    ): int {
        $method_allowed_mutations = $method_storage->allowed_mutations;
        
        if ($method_allowed_mutations === Mutations::LEVEL_INTERNAL_READ_WRITE
            && self::receiverAllowsInternalMutations($statements_analyzer, $var, $method_id, $context)
        ) {
            // If the method allows internal mutations,
            // and either:
            //
            // - The receiver is pure
            // - The receiver is free from references (pureCompatible)
            // - The receiver is free from external mutations
            // - The method is called on $this or self
            //
            // then we must treat the method as if it was pure.
            $method_allowed_mutations = Mutations::LEVEL_NONE;
        } elseif ($method_allowed_mutations === Mutations::LEVEL_INTERNAL_READ) {
            // If the method allows internal reads,
            // then we must treat the method as if it was pure,
            // (in a way, the receiver is "passed as an argument" to the method)
            $method_allowed_mutations = Mutations::LEVEL_NONE;
        }

        return $method_allowed_mutations;
    }

    /**
     * Worst (highest) mutation level implied by the closures actually supplied to the
     * callee's `@psalm-purity-from` params and `@psalm-purity-from-template` templates.
     * Params use the argument's closure type; templates are resolved from the call's
     * method-level bindings or the receiver's class-level template params.
     *
     * @param list<PhpParser\Node\Arg> $args
     * @param array<string, array<string, Union>> $class_template_params
     * @return Mutations::LEVEL_*
     */
    private static function getPurityFromParamsLevel(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        array $args,
        FunctionLikeStorage $storage,
        ?TemplateResult $template_result,
        array $class_template_params,
    ): int {
        $level = Mutations::LEVEL_NONE;

        foreach ($storage->purity_from_params as $param_name) {
            $type = self::getArgTypeForParam($statements_analyzer, $args, $storage, $param_name);

            if ($type !== null) {
                $level = max($level, Mutations::getClosureLevel($type));
            }
        }

        foreach ($storage->purity_from_templates as $template_name) {
            $type = self::resolveTemplateType($template_name, $template_result, $class_template_params, $codebase);

            if ($type !== null) {
                $level = max($level, Mutations::getClosureLevel($type));
            }
        }

        return $level;
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function getArgTypeForParam(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        FunctionLikeStorage $storage,
        string $param_name,
    ): ?Union {
        $param_offset = null;
        foreach ($storage->params as $offset => $param) {
            if ($param->name === $param_name) {
                $param_offset = $offset;
                break;
            }
        }

        foreach ($args as $offset => $arg) {
            $matches = $arg->name !== null
                ? $arg->name->name === $param_name
                : $offset === $param_offset;

            if ($matches) {
                return $statements_analyzer->node_data->getType($arg->value);
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, Union>> $class_template_params
     * @psalm-external-mutation-free
     */
    private static function resolveTemplateType(
        string $template_name,
        ?TemplateResult $template_result,
        array $class_template_params,
        Codebase $codebase,
    ): ?Union {
        if ($template_result !== null && isset($template_result->lower_bounds[$template_name])) {
            $bounds = [];
            foreach ($template_result->lower_bounds[$template_name] as $bound_list) {
                foreach ($bound_list as $bound) {
                    $bounds[] = $bound;
                }
            }

            if ($bounds !== []) {
                return TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds($bounds, $codebase);
            }
        }

        if (isset($class_template_params[$template_name])) {
            $type = null;
            foreach ($class_template_params[$template_name] as $bound_type) {
                $type = $type === null
                    ? $bound_type
                    : Type::combineUnionTypes($type, $bound_type, $codebase);
            }

            return $type;
        }

        return null;
    }

    /**
     * @param array<string, array<string, Union>> $class_template_params
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        ?string $lhs_var_id,
        string $cased_method_id,
        MethodIdentifier $method_id,
        MethodStorage $method_storage,
        ClassLikeStorage $class_storage,
        Context $context,
        Config $config,
        AtomicMethodCallAnalysisResult $result,
        ?TemplateResult $template_result = null,
        array $class_template_params = [],
    ): void {
        $method_allowed_mutations = self::getMethodAllowedMutations(
            $statements_analyzer,
            $stmt->var,
            $method_id,
            $method_storage,
            $context,
        );

        if ($method_storage->purity_from_params !== [] || $method_storage->purity_from_templates !== []) {
            // @psalm-purity-from(-template): the effective level is the worst of the declared
            // level and the levels of the closures actually passed to those params/templates.
            // It can only make the call *less* pure, never more.
            $method_allowed_mutations = max(
                $method_allowed_mutations,
                self::getPurityFromParamsLevel(
                    $statements_analyzer,
                    $codebase,
                    $stmt->getArgs(),
                    $method_storage,
                    $template_result,
                    $class_template_params,
                ),
            );
        }

        $statements_analyzer->signalMutation(
            $method_allowed_mutations,
            $context,
            'method ' . $cased_method_id,
            ImpureMethodCall::class,
            $stmt,
            $method_storage->allowed_mutations,
            false,
            // the level of an unannotated method is inferred from its body, which only
            // describes the method actually called if it can't be overridden elsewhere
            $lhs_var_id === '$this'
                || $method_storage->final
                || $class_storage->final
                || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                ? $method_storage
                : null,
            self::receiverAllowsInternalMutations($statements_analyzer, $stmt->var, $method_id, $context),
        );
        
        if (!$context->inside_unset
            && $method_storage->isMutationFree()
        ) {
            if ((!$method_storage->mutation_free_assumed
                    || $method_storage->final
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE)
                && ($method_storage->containing_class_allowed_mutations === Mutations::LEVEL_INTERNAL_READ
                    || $config->remember_property_assignments_after_call
                )
            ) {
                if ($context->inside_conditional
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                ) {
                    $stmt->setAttribute('memoizable', true);

                    if ($method_storage->containing_class_allowed_mutations === Mutations::LEVEL_INTERNAL_READ) {
                        $stmt->setAttribute('pure', true);
                    }
                }

                $result->can_memoize = true;
            }

            if ($codebase->find_unused_variables
                && !$context->inside_conditional
                && !$context->inside_general_use
                && !$context->inside_throw
            ) {
                if (!$context->inside_assignment
                    && !$context->inside_call
                    && !$context->inside_return
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                    && !$method_storage->if_false_assertions
                    && !$method_storage->throws
                    && !$method_storage->return_type?->isNever()
                    && !$method_storage->signature_return_type?->isNever()
                ) {
                    IssueBuffer::maybeAdd(
                        new UnusedMethodCall(
                            'The call to ' . $cased_method_id . ' is not used',
                            new CodeLocation($statements_analyzer, $stmt->name),
                            (string) $method_id,
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif (!$method_storage->mutation_free_assumed) {
                    $stmt->setAttribute('pure', true);
                }
            }
        }

        if (NoDiscardAnalyzer::isDiscardReported(
            $codebase,
            $context,
            $method_storage,
            $stmt->isFirstClassCallable(),
            $class_storage,
        )) {
            IssueBuffer::maybeAdd(
                new UnusedMethodCall(
                    'The call to ' . $cased_method_id . ' is not used',
                    new CodeLocation($statements_analyzer, $stmt->name),
                    (string) $method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if (!$config->remember_property_assignments_after_call
            && $method_allowed_mutations >= Mutations::LEVEL_INTERNAL_READ_WRITE
        ) {
            $context->removeMutableObjectVars();
        } elseif ($method_storage->this_property_mutations) {
            if ($method_allowed_mutations >= Mutations::LEVEL_INTERNAL_READ) {
                $context->removeMutableObjectVars(true);
            }

            foreach ($method_storage->this_property_mutations as $name => $_) {
                $mutation_var_id = $lhs_var_id . '->' . $name;

                $this_property_didnt_exist = $lhs_var_id === '$this'
                    && isset($context->vars_in_scope[$mutation_var_id])
                    && !isset($class_storage->declaring_property_ids[$name]);

                if ($this_property_didnt_exist) {
                    unset($context->vars_in_scope[$mutation_var_id]);
                } else {
                    $new_type = AssignmentAnalyzer::getExpandedPropertyType(
                        $codebase,
                        $class_storage->name,
                        $name,
                        $class_storage,
                    ) ?? Type::getMixed();

                    $context->vars_in_scope[$mutation_var_id] = $new_type;
                    $context->possibly_assigned_var_ids[$mutation_var_id] = true;
                }
            }
        }
    }
}
