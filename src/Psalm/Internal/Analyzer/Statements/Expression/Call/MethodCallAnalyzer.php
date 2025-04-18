<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use AssertionError;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\AtomicMethodCallAnalysisResult;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\AtomicMethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateResult;
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
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_merge;
use function array_reduce;
use function count;
use function is_string;
use function strtolower;

/**
 * @internal
 */
final class MethodCallAnalyzer extends CallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context,
        bool $real_method_call = true,
        ?TemplateResult $template_result = null,
    ): bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        $existing_stmt_var_type = null;

        if (!$real_method_call) {
            $existing_stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);
        }

        if ($existing_stmt_var_type) {
            $statements_analyzer->node_data->setType($stmt->var, $existing_stmt_var_type);
        } elseif (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if (!$stmt->name instanceof PhpParser\Node\Identifier) {
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context) === false) {
                $context->inside_call = $was_inside_call;

                return false;
            }
        }

        $context->inside_call = $was_inside_call;

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

            if ($stmt->name instanceof PhpParser\Node\Identifier
                && strtolower($stmt->name->name) === '__construct'
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

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && ($class_type->isNull() || $class_type->isVoid())
        ) {
            return !IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name->name . ' on null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isNullable()
            && !$class_type->ignore_nullable_issues
            && !($stmt->name->name === 'offsetGet' && $context->inside_isset)
            && !self::hasNullsafe($stmt->var)
        ) {
            IssueBuffer::maybeAdd(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly null value',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->name),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

        if ($class_type
            && $stmt->name instanceof PhpParser\Node\Identifier
            && $class_type->isFalsable()
            && !$class_type->ignore_falsable_issues
        ) {
            IssueBuffer::maybeAdd(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name->name . ' on possibly false value',
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

        $lhs_types = $class_type->getAtomicTypes();

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

        $possible_new_class_types = [];
        foreach ($lhs_types as $lhs_type_part) {
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
            if (isset($context->vars_in_scope[$lhs_var_id])
                && ($possible_new_class_type = $context->vars_in_scope[$lhs_var_id]) instanceof Union
                && !$possible_new_class_type->equals($class_type)) {
                $possible_new_class_types[] = $context->vars_in_scope[$lhs_var_id];
            }
        }
        if (!$stmt->isFirstClassCallable()
            && !$stmt->getArgs()
            && $lhs_var_id && $stmt->name instanceof PhpParser\Node\Identifier
        ) {
            if ($codebase->config->memoize_method_calls || $result->can_memoize) {
                $method_var_id = $lhs_var_id . '->' . strtolower($stmt->name->name) . '()';

                if (isset($context->vars_in_scope[$method_var_id])) {
                    $result->return_type = $context->vars_in_scope[$method_var_id];
                } elseif ($result->return_type !== null) {
                    $context->vars_in_scope[$method_var_id] = $result->return_type->setProperties([
                        'has_mutations' => false,
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

        if ($result->invalid_method_call_types) {
            $invalid_class_type = $result->invalid_method_call_types[0];

            if ($result->has_valid_method_call_type || $result->has_mixed_method_call) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidMethodCall(
                        'Cannot call method on possible ' . $invalid_class_type . ' variable ' . $lhs_var_id,
                        new CodeLocation($source, $stmt->name),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidMethodCall(
                        'Cannot call method on ' . $invalid_class_type . ' variable ' . $lhs_var_id,
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
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
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

            return true;
        }

        if ($result->non_existent_interface_method_ids) {
            if ($context->check_methods) {
                if ($result->existent_method_ids || $result->has_mixed_method_call) {
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

            return true;
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

        if ($stmt_type) {
            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if ($stmt_type->isNever()) {
                $context->has_returned = true;
            }
        }

        if ($result->returns_by_ref) {
            if (!$stmt_type) {
                $stmt_type = Type::getMixed();
                $statements_analyzer->node_data->setType($stmt, $stmt_type);
            }

            $stmt_type = $stmt_type->setByRef($result->returns_by_ref);
        }

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
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
            return $stmt->isFirstClassCallable() || self::checkMethodArgs(
                null,
                $stmt->getArgs(),
                new TemplateResult([], []),
                $context,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer,
            );
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
            $types = $class_type->getAtomicTypes();

            foreach ($types as $key => &$type) {
                if (!$type instanceof TNamedObject && !$type instanceof TObject && !$type instanceof TConditional) {
                    unset($types[$key]);
                } else {
                    $type = $type->setFromDocblock(false);
                }
            }
            if (!$types) {
                throw new AssertionError("We must have some types here!");
            }

            $context->removeVarFromConflictingClauses($lhs_var_id, null, $statements_analyzer);

            $class_type = $class_type->getBuilder()->setTypes($types);
            $class_type->from_docblock = false;
            $context->vars_in_scope[$lhs_var_id] = $class_type->freeze();
        }

        return true;
    }

    public static function hasNullsafe(PhpParser\Node\Expr $expr): bool
    {
        if ($expr instanceof PhpParser\Node\Expr\MethodCall
            || $expr instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            return self::hasNullsafe($expr->var);
        }

        return $expr instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch;
    }
}
