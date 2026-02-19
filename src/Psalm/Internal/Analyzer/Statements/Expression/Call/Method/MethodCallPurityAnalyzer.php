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
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\UnusedMethodCall;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\Mutations;
use Psalm\Type;

/**
 * @internal
 */
final class MethodCallPurityAnalyzer
{
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
            && (
                // Already checked in isPureCompatible below
                // $stmt->var->getAttribute('pure', false)

                $statements_analyzer->node_data->isPureCompatible($var)

                || $var->getAttribute('external_mutation_free', false)

                || $method_id->fq_class_name === $context->self
            )
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
    ): void {
        $method_allowed_mutations = self::getMethodAllowedMutations(
            $statements_analyzer,
            $stmt->var,
            $method_id,
            $method_storage,
            $context,
        );

        $statements_analyzer->signalMutation(
            $method_allowed_mutations,
            $context,
            'method ' . $cased_method_id,
            ImpureMethodCall::class,
            $stmt,
            $method_storage->allowed_mutations,
            false,
            $method_storage,
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
