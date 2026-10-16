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
use Psalm\Internal\Analyzer\PurityWildcardInference;
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
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_pop;
use function array_values;

/**
 * @internal
 * @psalm-import-type CapabilitySet from Capabilities
 */
final class MethodCallPurityAnalyzer
{
    /** Set on a method call that gives back the caller's own `$this` */
    private const RETURNS_THIS = 'returns_this';

    /**
     * Whether mutations of the receiver's own state are fine for the caller: the receiver is
     * pure-compatible or external-mutation-free, so nobody else can see it change. `$this` never
     * is, even where its type is reference-free: mutating it mutates the caller's own instance.
     */
    public static function receiverAllowsInternalMutations(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
    ): bool {
        if (self::isReceiverThis($var)) {
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
     * `$this` or state of the closure being analysed and write-props otherwise, as writing its
     * properties directly would. Callers that know the receiver to be fresh by other means say so
     * with $receiver_is_fresh, and those that know the callee's capabilities for this call with
     * $callee_capabilities.
     * @return CapabilitySet
     */
    public static function getMethodCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        MethodStorage $method_storage,
        bool $receiver_is_fresh = false,
        ?int $callee_capabilities = null,
    ): int {
        return self::getReceiverCallCapabilities(
            $statements_analyzer,
            $var,
            $callee_capabilities ?? $method_storage->capabilities,
            $receiver_is_fresh,
        );
    }

    /**
     * The capabilities a call of a method of the class of $class_storage whose name isn't known
     * (`$obj->{$name}()`) requires from its caller, given the receiver: any of its methods may run,
     * `__call()` too, which only its class contract caps. On an intersection, the method may be one
     * of any of the other parts, $intersection_types, each capped by its own contract. Without one,
     * or with the contract of a marker interface, which caps no implementer, the call may do anything.
     *
     * @param array<string, Atomic> $intersection_types
     */
    public static function getUnknownMethodCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        ClassLikeStorage $class_storage,
        array $intersection_types = [],
    ): int {
        if (!$class_storage->has_mutations_annotation || $class_storage->is_marker_interface) {
            return Capabilities::ALL;
        }

        $codebase = $statements_analyzer->getCodebase();
        $capabilities = $class_storage->capabilities;

        foreach ($intersection_types as $intersection_type) {
            $capabilities |= self::getClassContractCapabilities($codebase, $intersection_type);
        }

        return self::getReceiverCallCapabilities($statements_analyzer, $var, $capabilities);
    }

    /**
     * What any method of an object of the type $type may do, as the class contract of each part of
     * it caps them: the parts of an intersection, and the bound of a template. A method may be one
     * of any part, so it may do what any of them allows; a part that isn't a class, one without a
     * contract, or a marker interface, whose contract caps no implementer, allows anything.
     */
    public static function getClassContractCapabilities(Codebase $codebase, Atomic $type): int
    {
        $capabilities = Capabilities::NONE;
        $types = [$type];

        while (($type = array_pop($types)) !== null) {
            if ($type instanceof TTemplateParam) {
                $types = [
                    ...$types,
                    ...array_values($type->as->getAtomicTypes()),
                    ...array_values($type->getIntersectionTypes()),
                ];
                continue;
            }

            if (!$type instanceof TNamedObject
                || !$codebase->classlikes->classOrInterfaceExists($type->value)
            ) {
                return Capabilities::ALL;
            }

            $class_storage = $codebase->classlike_storage_provider->get($type->value);

            if (!$class_storage->has_mutations_annotation || $class_storage->is_marker_interface) {
                return Capabilities::ALL;
            }

            $capabilities |= $class_storage->capabilities;
            $types = [...$types, ...array_values($type->getIntersectionTypes())];
        }

        return $capabilities;
    }

    /**
     * The capabilities a call of a method that may use $method_capabilities requires from its
     * caller, given the receiver (see getMethodCapabilities())
     */
    public static function getReceiverCallCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Expr $var,
        int $method_capabilities,
        bool $receiver_is_fresh = false,
    ): int {
        $capabilities = $method_capabilities & ~Capabilities::READ_PROPS;

        if (!self::isFromGlobalState($statements_analyzer, $var)
            && ($receiver_is_fresh || self::receiverAllowsInternalMutations($statements_analyzer, $var))
        ) {
            return $capabilities & ~Capabilities::RECEIVER_LOCAL;
        }

        return self::getCapabilitiesForReceiver(
            $capabilities,
            self::isReceiverThis($var)
                || $statements_analyzer->node_data->getType($var)?->closure_state === true,
            self::isFromGlobalState($statements_analyzer, $var),
        );
    }

    /**
     * The capabilities a plugin (MethodCapabilitiesProviderInterface) registered for the class
     * declaring the method gives this call of it
     */
    private static function getProvidedCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\MethodCall $stmt,
        MethodIdentifier $method_id,
        Context $context,
    ): ?int {
        $provider = $codebase->methods->capabilities_provider;
        $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

        if ($declaring_method_id === null || !$provider->has($declaring_method_id->fq_class_name)) {
            return null;
        }

        // implicit calls (__get, ...) have no location of their own, see analyze()
        $location_node = $stmt->getAttribute('startFilePos') !== null ? $stmt : $stmt->name;

        return $provider->getMethodCapabilities(
            $statements_analyzer,
            $declaring_method_id->fq_class_name,
            $declaring_method_id->method_name,
            $method_id->fq_class_name,
            $stmt,
            $context,
            new CodeLocation($statements_analyzer, $location_node),
        );
    }

    /**
     * What a callee's writes to its own `$this` cost a caller that holds it as the receiver:
     * write-this-props when the receiver is the caller's `$this`, write-props otherwise, plus
     * write-globals when the receiver was reached from global state.
     *
     * @param CapabilitySet $capabilities
     * @return CapabilitySet
     * @psalm-pure
     */
    public static function getCapabilitiesForReceiver(
        int $capabilities,
        bool $receiver_is_this,
        bool $receiver_from_global_state,
    ): int {
        // an impure callee may do anything, to the caller's `$this` as well
        if ($capabilities === Capabilities::ALL) {
            return $capabilities;
        }

        if (($capabilities & Capabilities::WRITE_THIS_PROPS) !== 0 && !$receiver_is_this) {
            // the callee's `$this` is not the caller's
            $capabilities = ($capabilities & ~Capabilities::WRITE_THIS_PROPS) | Capabilities::WRITE_PROPS;
        }

        if (($capabilities & Capabilities::WRITE_PROPS) !== 0 && $receiver_from_global_state) {
            // mutating an object reached from global state mutates global state
            $capabilities |= Capabilities::WRITE_GLOBALS;
        }

        return $capabilities;
    }

    /**
     * @psalm-capabilities read-props
     */
    public static function isFromGlobalState(StatementsAnalyzer $statements_analyzer, Expr $var): bool
    {
        $receiver_type = $statements_analyzer->node_data->getType($var);

        return $receiver_type !== null && $receiver_type->from_global_state;
    }

    /** @psalm-capabilities read-props */
    public static function isThis(Expr $var): bool
    {
        return $var instanceof Expr\Variable && $var->name === 'this';
    }

    /**
     * Whether the receiver is the caller's own `$this`: `$this` itself, or a call on it of a method
     * returning its receiver, as in the fluent chain `$this->a()->b()`.
     */
    public static function isReceiverThis(Expr $var): bool
    {
        return self::isThis($var)
            || ($var instanceof Expr\MethodCall && $var->getAttribute(self::RETURNS_THIS, false) === true);
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
        // a plugin may know what this call of the method requires, e.g. from its arguments
        $provided_capabilities = self::getProvidedCapabilities(
            $statements_analyzer,
            $codebase,
            $stmt,
            $method_id,
            $context,
        );
        $callee_capabilities = $provided_capabilities
            ?? CallPurityResolver::getOwnCapabilities($codebase, $method_storage, $template_result);

        $method_capabilities = self::getMethodCapabilities(
            $statements_analyzer,
            $stmt->var,
            $method_storage,
            false,
            $callee_capabilities,
        );

        // with a receiver of several types, the call gives back `$this` only if every method called does
        $stmt->setAttribute(
            self::RETURNS_THIS,
            $stmt->getAttribute(self::RETURNS_THIS, true) === true
                && $method_storage->returns_this
                && !$stmt->isFirstClassCallable()
                && self::isReceiverThis($stmt->var),
        );

        $receiver_capabilities = $method_capabilities;

        // @psalm-purity-from-template: the call also needs the capabilities of the closures the
        // templates are bound to here; this can only make the call less pure, never more
        $template_capabilities = CallPurityResolver::getCallCapabilities(
            $statements_analyzer,
            $codebase,
            $method_storage,
            Capabilities::NONE,
            $template_result,
            $class_template_params,
            self::isReceiverThis($stmt->var),
            self::isFromGlobalState($statements_analyzer, $stmt->var),
        );
        $method_capabilities |= $template_capabilities;

        $inferred_template_capabilities = self::getInferredTemplateCapabilities(
            $statements_analyzer,
            $codebase,
            $stmt->var,
            $method_storage,
            $class_storage,
            $template_result,
            $class_template_params,
            $template_capabilities,
        );

        // whether the result may come from global state depends on what this call reads,
        // not on what it writes through its by-reference arguments
        $reads_globals = ($method_capabilities & Capabilities::READ_GLOBALS) !== 0;

        // a mutation-free method called with closures that are not is not mutation-free here
        $call_is_mutation_free = Capabilities::allowsAnyOf(Capabilities::MUTATION_FREE, $method_capabilities);

        $args = $stmt->isFirstClassCallable() ? [] : $stmt->getArgs();

        $method_capabilities = ByRefArgumentAnalyzer::adjustCapabilities(
            $statements_analyzer,
            $context,
            $method_capabilities,
            $method_storage->params,
            $args,
        );

        // the same for the inferred purity, which some template capabilities may be left out of
        $inferred_capabilities = $inferred_template_capabilities === $template_capabilities
            ? $method_capabilities
            : ByRefArgumentAnalyzer::adjustCapabilities(
                $statements_analyzer,
                $context,
                $receiver_capabilities | $inferred_template_capabilities,
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
            $callee_capabilities | ($inferred_capabilities & Capabilities::WRITE_PROPS),
            false,
            // the level of an unannotated method is inferred from its body, which only
            // describes the method actually called if it can't be overridden elsewhere
            // (and not this call, if a plugin gave its level)
            $provided_capabilities === null
                && ($lhs_var_id === '$this'
                    || $method_storage->final
                    || $class_storage->final
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE)
                ? $method_storage
                : null,
            self::receiverAllowsInternalMutations($statements_analyzer, $stmt->var),
        );

        // the callee's level does not include what its purity templates are bound to here
        if ($inferred_template_capabilities !== Capabilities::NONE) {
            $statements_analyzer->signalMutationOnlyInferred($inferred_template_capabilities);
        }

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
            && $call_is_mutation_free
        ) {
            if ((!$method_storage->mutation_free_assumed
                    || $method_storage->final
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE)
                // the class is immutable, or pure
                && (Capabilities::allowsAnyOf(
                    Capabilities::MUTATION_FREE,
                    $method_storage->containing_class_capabilities,
                )
                    || $config->remember_property_assignments_after_call
                )
            ) {
                if ($context->inside_conditional
                    && !$method_storage->assertions
                    && !$method_storage->if_true_assertions
                ) {
                    $stmt->setAttribute('memoizable', true);

                    if (Capabilities::allowsAnyOf(
                        Capabilities::MUTATION_FREE,
                        $method_storage->containing_class_capabilities,
                    )) {
                        $stmt->setAttribute('pure', true);
                    }
                }

                $result->can_memoize = true;

                // the class is neither immutable nor pure
                if (!Capabilities::allowsAnyOf(
                    Capabilities::MUTATION_FREE,
                    $method_storage->containing_class_capabilities,
                )) {
                    $result->memoized_result_has_mutations = true;
                }
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
                    && !self::isCalledForItsTemplatesEffects($method_storage)
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
        } else {
            if (!$config->remember_property_assignments_after_call) {
                // the method cannot write properties, but may still write static ones
                $context->removeMutableObjectVars(false, $method_capabilities);
            }

            if (($callee_capabilities & (Capabilities::WRITE_PROPS | Capabilities::WRITE_THIS_PROPS)) !== 0
                || self::isCalledForItsTemplatesEffects($method_storage)
            ) {
                // the method may write properties, but not which ones (a stub, a body writing `$this`
                // by reference, or a method that may do anything), or moves engine state (the position
                // of an iterator): what its receiver's methods returned before may have changed, even
                // when the receiver is fresh and the caller is not charged
                $context->removeMutableObjectVars(true);
            }
        }
    }

    /**
     * The capabilities the purity templates of the call are bound to, without what depends on a
     * purity argument of a receiver found in a parameter: the parameter's `_` would charge it to
     * the callers, so it is left out of the inferred purity.
     *
     * @param array<string, array<string, Union>> $class_template_params
     */
    private static function getInferredTemplateCapabilities(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Expr $receiver,
        MethodStorage $method_storage,
        ClassLikeStorage $class_storage,
        ?TemplateResult $template_result,
        array $class_template_params,
        int $template_capabilities,
    ): int {
        $marked_class_template_params = PurityWildcardInference::markReceiverTemplates(
            $statements_analyzer,
            $codebase,
            $receiver,
            $class_storage->name,
            $class_template_params,
        );

        if ($marked_class_template_params === null) {
            return $template_capabilities;
        }

        return CallPurityResolver::getCallCapabilities(
            $statements_analyzer,
            $codebase,
            $method_storage,
            Capabilities::NONE,
            $template_result,
            $marked_class_template_params,
            self::isThis($receiver),
            self::isFromGlobalState($statements_analyzer, $receiver),
        );
    }

    /**
     * A void method whose purity comes from purity templates (`Iterator::next()`) is called for the
     * effects of what they are bound to, or of its own engine state (the cursor of an iterator):
     * its call is not unused even when they turn out to be none.
     *
     * @psalm-mutation-free
     */
    public static function isCalledForItsTemplatesEffects(MethodStorage $method_storage): bool
    {
        return $method_storage->purity_from_templates !== []
            && ($method_storage->return_type?->isVoid() === true
                || $method_storage->signature_return_type?->isVoid() === true);
    }
}
