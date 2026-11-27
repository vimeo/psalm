<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use AssertionError;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\AtomicMethodCallAnalysisResult;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\AtomicMethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodCallPurityAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\NullsafeChainState;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Internal\TypeVisitor\TypeVariableResolver;
use Psalm\Issue\DirectConstructorCall;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\NullReference;
use Psalm\Issue\PossiblyFalseReference;
use Psalm\Issue\PossiblyInvalidMethodCall;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\PossiblyUndefinedMethod;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\UndefinedInterfaceMethod;
use Psalm\Issue\UndefinedMagicMethod;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_values;
use function count;
use function implode;
use function is_string;
use function strrpos;
use function strtolower;
use function substr;

/**
 * @internal
 */
final class MethodCallAnalyzer extends CallAnalyzer
{
    /**
     * @param bool $receiver_analyzed the receiver was analysed already (offsetGet() for `$a[0]`): it is not
     *      evaluated again
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        bool $real_method_call = true,
        ?TemplateResult $template_result = null,
        bool $receiver_analyzed = false,
    ): bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        NullsafeChainState::reset($stmt);

        $existing_stmt_var_type = null;

        if (!$real_method_call || $receiver_analyzed) {
            $existing_stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if ($existing_stmt_var_type) {
            $statements_analyzer->node_data->setType($stmt->var, $existing_stmt_var_type);

            // the call uses its receiver, which may have been analysed only as the target of an assignment
            // (offsetSet() for `$a[] = $v`)
            if ($receiver_analyzed && $statements_analyzer->data_flow_graph) {
                foreach ($existing_stmt_var_type->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        DataFlowNode::getForVariableUse(),
                        'use-inside-call',
                    );
                }
            }
        } elseif (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            $context->inside_call = true;
            // the name is read even in isset mode
            $was_inside_isset = $context->inside_isset;
            $context->inside_isset = false;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                $context->inside_call = $was_inside_call;
                $context->inside_isset = $was_inside_isset;

                return false;
            }

            $context->inside_isset = $was_inside_isset;

            self::addMethodNameSink($statements_analyzer, $stmt, $stmt->name, $context);
        }

        $context->inside_call = $was_inside_call;

        // `$object->$name()` with `$name` one of known strings calls each of those methods
        $literal_method_names = self::getLiteralMethodNames($statements_analyzer, $stmt);

        $called_method_name = $stmt->name instanceof PhpParser\Node\Identifier
            ? $stmt->name->name
            : ($literal_method_names !== null
                ? implode('|', array_map(
                    static fn(PhpParser\Node\Identifier $name): string => $name->name,
                    $literal_method_names,
                ))
                : null);

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_analyzer->getFQCLN()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                )) {
                    return false;
                }
            }

            $source = $statements_analyzer->getSource();

            if ($stmt->name instanceof PhpParser\Node\Identifier
                && strtolower($stmt->name->name) === '__construct'
                // a lazy ghost initializer constructs the object it is given
                && !($source instanceof ClosureAnalyzer && $source->getInitializedObjectParam() === $stmt->var->name)
            ) {
                IssueBuffer::maybeAdd(
                    new DirectConstructorCall(
                        'Constructors should not be called directly',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        $receiver_state = NullsafeChainState::of($stmt->var);

        $lhs_var_id = ExpressionIdentifier::getExtendedVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        $class_type = $lhs_var_id && $context->hasVariable($lhs_var_id)
            ? $context->vars_in_scope[$lhs_var_id]
            : null;

        if ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var)) {
            $class_type = $stmt_var_type;
        } elseif (!$class_type) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        $receiver_type = $class_type
            ? TypeExpander::expandTemplateKeyOfAndValueOf($statements_analyzer->getCodebase(), $class_type)
            : null;

        if ($class_type
            && $called_method_name !== null
            && ($class_type->isNull() || $class_type->isVoid())
        ) {
            return !IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $called_method_name . ' on null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($receiver_type
            && $called_method_name !== null
            && $receiver_type->isNullable()
            && !$receiver_type->ignore_nullable_issues
            && !($called_method_name === 'offsetGet' && $context->inside_isset)
            && !$receiver_state->isNullOnlyByShortCircuit()
        ) {
            IssueBuffer::maybeAdd(
                new PossiblyNullReference(
                    'Cannot call method ' . $called_method_name . ' on possibly null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($receiver_type
            && $called_method_name !== null
            && $receiver_type->isFalsable()
            && !$receiver_type->ignore_falsable_issues
        ) {
            IssueBuffer::maybeAdd(
                new PossiblyFalseReference(
                    'Cannot call method ' . $called_method_name . ' on possibly false value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $codebase = $statements_analyzer->getCodebase();

        $source = $statements_analyzer->getSource();

        if (!$class_type) {
            $class_type = Type::getMixed();
        }

        // A bare type variable minted for a class template resolves to its
        // construction-site shape: the concrete object it stands for is what a
        // method is actually being called on. Only top-level variables resolve —
        // one nested in a generic object (e.g. `Box<`_0>`) stays live so later
        // calls can still constrain it.
        $class_type = TypeVariableResolver::resolveTopLevel($class_type, $codebase);

        $lhs_types = TypeExpander::expandTemplateKeyOfAndValueOf($codebase, $class_type)->getAtomicTypes();

        foreach ($lhs_types as $k => $lhs_type_part) {
            if ($lhs_type_part instanceof TConditional) {
                $lhs_types = array_merge(
                    $lhs_types,
                    $lhs_type_part->if_type->getAtomicTypes(),
                    $lhs_type_part->else_type->getAtomicTypes(),
                );
                unset($lhs_types[$k]);
            }
        }

        $result = new AtomicMethodCallAnalysisResult();

        $method_var_id = !$stmt->isFirstClassCallable()
            && !$stmt->getArgs()
            && $lhs_var_id
            && $stmt->name instanceof PhpParser\Node\Identifier
            ? $lhs_var_id . '->' . strtolower($stmt->name->name) . '()'
            : null;

        // a memoized call returns what the same call returned before, even if it may change what it returns next
        $memoized_type = $method_var_id !== null ? $context->vars_in_scope[$method_var_id] ?? null : null;

        $possible_new_class_types = self::analyzeAtomicCalls(
            $statements_analyzer,
            $stmt,
            $codebase,
            $context,
            $class_type,
            $lhs_types,
            $lhs_var_id,
            $result,
            $template_result,
            $literal_method_names,
        );

        MethodCallPurityAnalyzer::keepPropertiesNotWrittenForEveryReceiverType($codebase, $context, $result);

        if ($method_var_id !== null) {
            // the initialization pass skips the purity analysis that decides it,
            // so it relies on what the normal pass of this call found
            $was_memoizable = $context->collect_initializations
                && $stmt->getAttribute('memoizable', false);

            if ($codebase->config->memoize_method_calls || $result->can_memoize || $was_memoizable) {
                if (isset($context->vars_in_scope[$method_var_id])) {
                    $result->return_type = $context->vars_in_scope[$method_var_id];
                } elseif ($memoized_type !== null) {
                    // forgotten by the call itself
                    $result->return_type = $memoized_type;
                    $context->vars_in_scope[$method_var_id] = $memoized_type;
                } elseif ($result->return_type !== null) {
                    // forgotten by calls that may mutate the receiver, unless the class is immutable
                    $context->vars_in_scope[$method_var_id] = $result->return_type->setProperties([
                        'has_mutations' => $result->can_memoize && $result->memoized_result_has_mutations,
                    ]);
                }

                if ($result->can_memoize) {
                    $stmt->setAttribute('memoizable', true);
                }
            }
        }

        if (count($possible_new_class_types) > 0) {
            $class_type = array_reduce(
                $possible_new_class_types,
                static fn(?Union $type_1, Union $type_2): Union => Type::combineUnionTypes($type_1, $type_2, $codebase),
            );
        }

        $magic_args_analyzed = null;

        // issues from the arguments precede the issues of the call itself
        if ($result->non_existent_magic_method_ids && !$result->existent_method_ids && !$stmt->isFirstClassCallable()) {
            $magic_args_analyzed = self::checkMethodArgs(
                null,
                $stmt->getArgs(),
                new TemplateResult([], []),
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            );
        }

        if ($result->invalid_method_call_types) {
            $invalid_class_type = $result->invalid_method_call_types[0];

            if ($result->has_valid_method_call_type || $result->has_mixed_method_call) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidMethodCall(
                        'Cannot call method on possible ' . $invalid_class_type . ' variable '
                            . ExpressionIdentifier::getNameForMessage($lhs_var_id, $stmt->var),
                        new CodeLocation($source, $stmt->name),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidMethodCall(
                        'Cannot call method on ' . $invalid_class_type . ' variable '
                            . ExpressionIdentifier::getNameForMessage($lhs_var_id, $stmt->var),
                        new CodeLocation($source, $stmt->name),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if ($result->non_existent_magic_method_ids) {
            if ($context->check_methods) {
                IssueBuffer::maybeAdd(
                    new UndefinedMagicMethod(
                        'Magic method ' . $result->non_existent_magic_method_ids[0] . ' does not exist',
                        new CodeLocation($source, $stmt->name),
                        $result->non_existent_magic_method_ids[0],
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if ($result->non_existent_class_method_ids) {
            $possibly_undefined = $result->existent_method_ids || $result->has_mixed_method_call;

            if ($context->check_methods) {
                if ($possibly_undefined) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0],
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new UndefinedMethod(
                            'Method ' . $result->non_existent_class_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_class_method_ids[0],
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }

            // the methods that exist still give the call its type
            if (!$possibly_undefined) {
                return true;
            }
        }

        if ($result->non_existent_interface_method_ids) {
            $possibly_undefined = $result->existent_method_ids || $result->has_mixed_method_call;

            if ($context->check_methods) {
                if ($possibly_undefined) {
                    IssueBuffer::maybeAdd(
                        new PossiblyUndefinedMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0],
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new UndefinedInterfaceMethod(
                            'Method ' . $result->non_existent_interface_method_ids[0] . ' does not exist',
                            new CodeLocation($source, $stmt->name),
                            $result->non_existent_interface_method_ids[0],
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }

            // the methods that exist still give the call its type
            if (!$possibly_undefined) {
                return true;
            }
        }

        if ($result->too_many_arguments && $result->too_many_arguments_method_ids) {
            $error_method_id = $result->too_many_arguments_method_ids[0];

            IssueBuffer::maybeAdd(
                new TooManyArguments(
                    'Too many arguments for method ' . $error_method_id . ' - saw ' . count($stmt->getArgs()),
                    new CodeLocation($source, $stmt->name),
                    (string) $error_method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($result->too_few_arguments && $result->too_few_arguments_method_ids) {
            $error_method_id = $result->too_few_arguments_method_ids[0];

            IssueBuffer::maybeAdd(
                new TooFewArguments(
                    'Too few arguments for method ' . $error_method_id . ' saw ' . count($stmt->getArgs()),
                    new CodeLocation($source, $stmt->name),
                    (string) $error_method_id,
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        $stmt_type = $result->return_type;

        // `$a?->b->c()` short-circuits the whole chain, so null from the nullsafe hop survives the call
        if ($receiver_state !== NullsafeChainState::None
            && TypeExpander::expandTemplateKeyOfAndValueOf($codebase, $class_type)->isNullable()
        ) {
            $receiver_state->afterLink($stmt_type)->markOn($stmt);

            if ($stmt_type && !$stmt_type->isNullable()) {
                $stmt_type = Type::combineUnionTypes($stmt_type, Type::getNull());
            }
        }

        if ($result->returns_by_ref) {
            $stmt_type = ($stmt_type ?? Type::getMixed())->setByRef(true);
        }

        if ($stmt_type) {
            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            // a method of a receiver that is never is not known: the call is never, but nothing says it exits
            if ($stmt_type->isNever() && !$class_type->isNever()) {
                $context->has_returned = true;
            }
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && $stmt_type
        ) {
            $codebase->analyzer->addNodeType(
                $statements_analyzer->getFilePath(),
                $stmt->name,
                $stmt_type->getId(),
                $stmt,
            );
        }

        if (!$result->existent_method_ids) {
            return $stmt->isFirstClassCallable() || ($magic_args_analyzed ?? self::checkMethodArgs(
                null,
                $stmt->getArgs(),
                new TemplateResult([], []),
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            ));
        }

        // if we called a method on this nullable variable, remove the nullable status here
        // because any further calls must have worked
        if ($lhs_var_id
            && !$class_type->isMixed()
            && $result->has_valid_method_call_type
            && !$result->has_mixed_method_call
            && !$result->invalid_method_call_types
            && ($class_type->from_docblock || $class_type->isNullable())
            && $real_method_call
        ) {
            // the method call may have written a bare type variable back into
            // scope; resolve it again so the narrowed type keeps a concrete
            // object
            $class_type = TypeVariableResolver::resolveTopLevel($class_type, $codebase);

            $types = $class_type->getAtomicTypes();

            foreach ($types as $key => &$type) {
                // A type variable that survived here (one with no bounds to
                // resolve it through) is a valid method-call receiver — the
                // call landed on it — so it belongs with the concrete object
                // types rather than being stripped as a non-object, which
                // would leave nothing behind.
                if (!$type instanceof TNamedObject
                    && !$type instanceof TObject
                    && !$type instanceof TConditional
                    && !$type instanceof TTypeVariable
                    && !$type instanceof TTemplateValueOf
                ) {
                    unset($types[$key]);
                } else {
                    $type = $type->setFromDocblock(false);
                }
            }
            unset($type);

            if (!$types) {
                throw new AssertionError("We must have some types here!");
            }

            // narrowed, not written: the enclosing scopes keep their clauses about it
            $context->clauses = Context::filterClauses(
                $lhs_var_id,
                $context->clauses,
                null,
                $statements_analyzer,
                value_changed: false,
            );

            $class_type = $class_type->getBuilder()->setTypes($types);
            $class_type->from_docblock = false;
            $context->vars_in_scope[$lhs_var_id] = $class_type->freeze();
        }

        return true;
    }

    /**
     * Analyses the call on each type of the receiver, with each method its name can be
     *
     * @param array<Atomic> $lhs_types
     * @param non-empty-list<PhpParser\Node\Identifier>|null $literal_method_names
     * @return list<Union> the types the receiver may have after the call
     */
    private static function analyzeAtomicCalls(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Codebase $codebase,
        Context $context,
        Union $class_type,
        array $lhs_types,
        ?string $lhs_var_id,
        AtomicMethodCallAnalysisResult $result,
        ?TemplateResult $template_result,
        ?array $literal_method_names,
    ): array {
        $name = $stmt->name;
        $calls = [];
        $call_count = 0;

        foreach ($lhs_types as $lhs_type_part) {
            // the method name matters only on an object whose methods are known
            $method_names = $literal_method_names !== null
                && ($lhs_type_part instanceof TNamedObject
                    || $lhs_type_part instanceof TTemplateParam
                    || $lhs_type_part instanceof TObjectWithProperties)
                ? $literal_method_names
                : [$name];

            $calls[] = [$lhs_type_part, $method_names];
            $call_count += count($method_names);
        }

        // a call of one of several methods asserts only what each of them asserts
        $targets_if_true_assertions = [];
        $targets_if_false_assertions = [];

        $possible_new_class_types = [];

        foreach ($calls as [$lhs_type_part, $method_names]) {
            foreach ($method_names as $method_name) {
                $stmt->name = $method_name;

                try {
                    AtomicMethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $stmt,
                        $codebase,
                        $context,
                        $class_type,
                        $lhs_type_part,
                        $lhs_type_part instanceof TNamedObject
                            || $lhs_type_part instanceof TTemplateParam
                            ? $lhs_type_part
                            : null,
                        false,
                        $lhs_var_id,
                        $result,
                        $template_result,
                    );
                } finally {
                    $stmt->name = $name;
                }

                if ($call_count > 1) {
                    $targets_if_true_assertions[] = $statements_analyzer->node_data->getIfTrueAssertions($stmt) ?? [];
                    $targets_if_false_assertions[] = $statements_analyzer->node_data->getIfFalseAssertions($stmt)
                        ?? [];

                    $statements_analyzer->node_data->setIfTrueAssertions($stmt, []);
                    $statements_analyzer->node_data->setIfFalseAssertions($stmt, []);
                }
            }

            if ($lhs_var_id !== null && isset($context->vars_in_scope[$lhs_var_id])
                && ($possible_new_class_type = $context->vars_in_scope[$lhs_var_id]) instanceof Union
                && !$possible_new_class_type->equals($class_type)) {
                $possible_new_class_types[] = $context->vars_in_scope[$lhs_var_id];
            }
        }

        if ($targets_if_true_assertions && $targets_if_false_assertions) {
            $statements_analyzer->node_data->setIfTrueAssertions(
                $stmt,
                self::getCommonAssertions($targets_if_true_assertions),
            );
            $statements_analyzer->node_data->setIfFalseAssertions(
                $stmt,
                self::getCommonAssertions($targets_if_false_assertions),
            );
        }

        if ($literal_method_names !== null && count($literal_method_names) > 1 && count($lhs_types) > 1) {
            // `$r instanceof A ? 'a' : 'b'` names a method of each receiver: a method is missing when no receiver
            // has it
            $existent_method_names = [];

            foreach ($result->existent_method_ids as $method_id => $_) {
                $existent_method_names[self::getMethodNameLc($method_id)] = true;
            }

            $is_missing = static fn(string $method_id): bool
                => !isset($existent_method_names[self::getMethodNameLc($method_id)]);

            $result->non_existent_class_method_ids = array_values(
                array_filter($result->non_existent_class_method_ids, $is_missing),
            );
            $result->non_existent_interface_method_ids = array_values(
                array_filter($result->non_existent_interface_method_ids, $is_missing),
            );
            $result->non_existent_magic_method_ids = array_values(
                array_filter($result->non_existent_magic_method_ids, $is_missing),
            );
        }

        return $possible_new_class_types;
    }

    /**
     * @psalm-pure
     */
    private static function getMethodNameLc(string $method_id): string
    {
        return strtolower(substr($method_id, (int) strrpos($method_id, ':') + 1));
    }
}
