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
use Psalm\Internal\Analyzer\Statements\Expression\Call\ByRefArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\CallPurityResolver;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NoDiscardAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\GlobalStateAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\UnusedMethodCall;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * @internal
 */
final class MethodCallPurityAnalyzer
{
    /**
     * Whether mutations of the receiver's own state are fine for the caller: the receiver is
     * pure-compatible or external-mutation-free, so nobody else can see it change. `$this` never
     * is, even where its type is reference-free: mutating it mutates the caller's own instance.
     */
    public static function receiverAllowsInternalMutations(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
    ): bool {
        if (self::isThis($var)) {
            return false;
        }

        // Already checked in isPureCompatible below
        // $stmt->var->getAttribute('pure', false)
        return $statements_analyzer->node_data->isPureCompatible($var)
            || $var->getAttribute('external_mutation_free', false);
    }

    /**
     * The capabilities a call of a method requires from its caller, given the receiver: reading
     * the receiver's own state is like passing it as an argument. Mutating it is free when the
     * receiver is pure-compatible or external-mutation-free, needs write-this-props when it is
     * `$this` and write-props otherwise, as writing its properties directly would. Callers that
     * know the receiver to be fresh by other means say so with $receiver_is_fresh.
     */
    public static function getMethodCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        MethodStorage $method_storage,
        bool $receiver_is_fresh = false,
    ): int {
        $capabilities = $method_storage->capabilities & ~Capabilities::READ_PROPS;

        $receiver_type = $statements_analyzer->node_data->getType($var);

        if ($receiver_type !== null
            && $receiver_type->from_global_state
            && ($method_storage->capabilities & (Capabilities::WRITE_THIS_PROPS | Capabilities::WRITE_PROPS)) !== 0
        ) {
            // mutating an object reached from global state mutates global state
            return $capabilities | Capabilities::WRITE_GLOBALS;
        }

        if ($receiver_is_fresh || self::receiverAllowsInternalMutations($statements_analyzer, $var)) {
            $capabilities &= ~Capabilities::RECEIVER_LOCAL;
        } elseif (($capabilities & Capabilities::WRITE_THIS_PROPS) !== 0 && !self::isThis($var)) {
            // the callee's `$this` is not the caller's
            $capabilities |= Capabilities::WRITE_PROPS;
        }

        return $capabilities;
    }

    /** @psalm-pure */
    private static function isThis(Expr $var): bool
    {
        return $var instanceof Expr\Variable && $var->name === 'this';
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
        $method_capabilities = self::getMethodCapabilities(
            $statements_analyzer,
            $stmt->var,
            $method_storage,
        );

        // @psalm-purity-from-template: the call also needs the capabilities of the closures the
        // templates are bound to here; this can only make the call less pure, never more
        $method_capabilities = CallPurityResolver::getCallCapabilities(
            $statements_analyzer,
            $codebase,
            $method_storage,
            $method_capabilities,
            $template_result,
            $class_template_params,
        );

        // whether the result may come from global state depends on what this call reads,
        // not on what it writes through its by-reference arguments
        $reads_globals = ($method_capabilities & Capabilities::READ_GLOBALS) !== 0;

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        $method_capabilities = ByRefArgumentAnalyzer::adjustCapabilities(
            $statements_analyzer,
            $context,
            $method_capabilities,
            $method_storage->params,
            $args,
        );

        $statements_analyzer->signalMutation(
            $method_capabilities,
            $context,
            'method ' . $cased_method_id,
            ImpureMethodCall::class,
            // implicit calls (__get, __invoke, offsetGet, ...) are virtual nodes without a
            // location of their own, but their name points at the expression that triggers them
            $stmt->getAttribute('startFilePos') !== null ? $stmt : $stmt->name,
            // mutating a receiver other than `$this` writes another object's properties
            $method_storage->capabilities | ($method_capabilities & Capabilities::WRITE_PROPS),
            false,
            // the level of an unannotated method is inferred from its body, which only
            // describes the method actually called if it can't be overridden elsewhere
            $lhs_var_id === '$this'
                || $method_storage->final
                || $class_storage->final
                || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                ? $method_storage
                : null,
            self::receiverAllowsInternalMutations($statements_analyzer, $stmt->var),
        );

        if ($reads_globals) {
            $stmt->setAttribute(GlobalStateAnalyzer::ATTRIBUTE, true);
        }

        GlobalStateAnalyzer::checkArguments(
            $statements_analyzer,
            $context,
            $args,
            $method_capabilities,
            ImpureMethodCall::class,
            'method ' . $cased_method_id,
        );
        
        if (!$context->inside_unset
            && $method_storage->isMutationFree()
        ) {
            if ((!$method_storage->mutation_free_assumed
                    || $method_storage->final
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE)
                && ($method_storage->containing_class_capabilities === Capabilities::MUTATION_FREE
                    || $config->remember_property_assignments_after_call
                )
            ) {
                if ($context->inside_conditional
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                ) {
                    $stmt->setAttribute('memoizable', true);

                    if ($method_storage->containing_class_capabilities === Capabilities::MUTATION_FREE) {
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
            && ($method_capabilities & (Capabilities::WRITE_PROPS | Capabilities::WRITE_THIS_PROPS)) !== 0
        ) {
            $context->removeMutableObjectVars(false, $method_capabilities);
        } elseif ($method_storage->this_property_mutations) {
            if (!$config->remember_property_assignments_after_call) {
                // the method cannot write properties here, but may still write static ones
                $context->removeMutableObjectVars(false, $method_capabilities);
            }

            if ($method_capabilities !== Capabilities::NONE) {
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
