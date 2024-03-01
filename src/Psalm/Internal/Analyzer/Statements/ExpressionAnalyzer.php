<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BitwiseNotAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BooleanNotAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NewAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ClassConstAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CloneAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EmptyAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EncapsulatedStringAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EvalAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExitAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\InstancePropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\StaticPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncDecExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\InstanceofAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IssetAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\MagicConstAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\MatchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\NullsafeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\PrintAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\TernaryAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\UnaryPlusMinusAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\YieldAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\YieldFromAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\RiskyTruthyFalsyComparison;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\Issue\UnsupportedReferenceUsage;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Scalar\VirtualEncapsed;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualName;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;

use function count;
use function get_class;
use function in_array;
use function strtolower;

/**
 * @internal
 */
final class ExpressionAnalyzer
{
    /**
     * @param bool $assigned_to_reference This is set to true when the expression being analyzed
     *                                    here is being assigned to another variable by reference.
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment = false,
        ?Context $global_context = null,
        bool $from_stmt = false,
        ?TemplateResult $template_result = null,
        bool $assigned_to_reference = false
    ): bool {
        if (self::dispatchBeforeExpressionAnalysis($stmt, $context, $statements_analyzer) === false) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (self::handleExpression(
            $statements_analyzer,
            $stmt,
            $context,
            $array_assignment,
            $global_context,
            $from_stmt,
            $template_result,
            $assigned_to_reference,
        ) === false) {
            return false;
        }

        if (!$context->inside_conditional
            && ($stmt instanceof PhpParser\Node\Expr\BinaryOp
                || $stmt instanceof PhpParser\Node\Expr\Instanceof_
                || $stmt instanceof PhpParser\Node\Expr\Assign
                || $stmt instanceof PhpParser\Node\Expr\BooleanNot
                || $stmt instanceof PhpParser\Node\Expr\Empty_
                || $stmt instanceof PhpParser\Node\Expr\Isset_
                || $stmt instanceof PhpParser\Node\Expr\FuncCall)
        ) {
            $assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($assertions === null) {
                $negate = $context->inside_negation;

                while ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
                    $stmt = $stmt->expr;
                    $negate = !$negate;
                }

                AssertionFinder::scrapeAssertions(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    $negate,
                    true,
                    false,
                );
            }
        }

        if (self::dispatchAfterExpressionAnalysis($stmt, $context, $statements_analyzer) === false) {
            return false;
        }

        return true;
    }

    public static function checkRiskyTruthyFalsyComparison(
        Type\Union $type,
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt
    ): void {
        if (count($type->getAtomicTypes()) > 1) {
            $has_truthy_or_falsy_exclusive_type = false;
            $both_types = $type->getBuilder();
            foreach ($both_types->getAtomicTypes() as $key => $atomic_type) {
                if ($atomic_type->isTruthy()
                    || $atomic_type->isFalsy()
                    || $atomic_type instanceof TBool) {
                    $both_types->removeType($key);
                    $has_truthy_or_falsy_exclusive_type = true;
                }
            }

            if (count($both_types->getAtomicTypes()) > 0 && $has_truthy_or_falsy_exclusive_type) {
                $both_types = $both_types->freeze();
                IssueBuffer::maybeAdd(
                    new RiskyTruthyFalsyComparison(
                        'Operand of type ' . $type->getId() . ' contains ' .
                        'type' . (count($both_types->getAtomicTypes()) > 1 ? 's' : '') . ' ' .
                        $both_types->getId() . ', which can be falsy and truthy. ' .
                        'This can cause possibly unexpected behavior. Use strict comparison instead.',
                        new CodeLocation($statements_analyzer, $stmt),
                        $type->getId(),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }

    /**
     * @param bool $assigned_to_reference This is set to true when the expression being analyzed
     *                                    here is being assigned to another variable by reference.
     */
    private static function handleExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment,
        ?Context $global_context,
        bool $from_stmt,
        ?TemplateResult $template_result = null,
        bool $assigned_to_reference = false
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            return VariableFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                false,
                null,
                $array_assignment,
                false,
                $assigned_to_reference,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            return self::analyzeAssignment($statements_analyzer, $stmt, $context, $from_stmt);
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            return AssignmentAnalyzer::analyzeAssignmentOperation($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            return MethodCallAnalyzer::analyze($statements_analyzer, $stmt, $context, true, $template_result);
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            return StaticCallAnalyzer::analyze($statements_analyzer, $stmt, $context, $template_result);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            ConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $statements_analyzer->node_data->setType($stmt, Type::getString($stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            MagicConstAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt(false, $stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getFloat($stmt->value));

            return true;
        }


        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return UnaryPlusMinusAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            IssetAnalyzer::analyze($statements_analyzer, $stmt, $context);
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            return ClassConstAnalyzer::analyzeFetch($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            return InstancePropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $array_assignment,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            return StaticPropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            return BitwiseNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return BinaryOpAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                0,
                $from_stmt,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\PostInc
            || $stmt instanceof PhpParser\Node\Expr\PostDec
            || $stmt instanceof PhpParser\Node\Expr\PreInc
            || $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            return IncDecExpressionAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\New_) {
            return NewAnalyzer::analyze($statements_analyzer, $stmt, $context, $template_result);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return ArrayAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            return EncapsulatedStringAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            return FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $template_result,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            return TernaryAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return BooleanNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            EmptyAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            return ClosureAnalyzer::analyzeExpression($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return ArrayFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast) {
            return CastAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            return CloneAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            return InstanceofAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            return ExitAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Include_) {
            return IncludeAnalyzer::analyze($statements_analyzer, $stmt, $context, $global_context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            EvalAnalyzer::analyze($statements_analyzer, $stmt, $context);
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            if (!AssignmentAnalyzer::analyzeAssignmentRef($statements_analyzer, $stmt, $context)) {
                IssueBuffer::maybeAdd(
                    new UnsupportedReferenceUsage(
                        "This reference cannot be analyzed by Psalm",
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                // Analyze as if it were a normal assignent and just pretend the reference doesn't exist
                return self::analyzeAssignment($statements_analyzer, $stmt, $context, $from_stmt);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            $context->error_suppressing = true;
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->error_suppressing = false;

            $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($expr_type) {
                $statements_analyzer->node_data->setType($stmt, $expr_type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            $concat = new VirtualEncapsed($stmt->parts, $stmt->getAttributes());
            $virtual_call = new VirtualFuncCall(new VirtualName(['shell_exec']), [
                new VirtualArg($concat),
            ], $stmt->getAttributes());
            return self::handleExpression(
                $statements_analyzer,
                $virtual_call,
                $context,
                $array_assignment,
                $global_context,
                $from_stmt,
                $template_result,
                $assigned_to_reference,
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Print_) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            if (PrintAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                $context->inside_call = $was_inside_call;

                return false;
            }
            $context->inside_call = $was_inside_call;

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            return YieldAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            return YieldFromAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        $codebase = $statements_analyzer->getCodebase();
        $analysis_php_version_id = $codebase->analysis_php_version_id;

        if ($stmt instanceof PhpParser\Node\Expr\Match_ && $analysis_php_version_id >= 8_00_00) {
            return MatchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Throw_ && $analysis_php_version_id >= 8_00_00) {
            return ThrowAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if (($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                || $stmt instanceof PhpParser\Node\Expr\NullsafeMethodCall)
            && $analysis_php_version_id >= 8_00_00
        ) {
            return NullsafeAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Error) {
            // do nothing
            return true;
        }

        IssueBuffer::maybeAdd(
            new UnrecognizedExpression(
                'Psalm does not understand ' . get_class($stmt) . ' for PHP ' .
                $codebase->getMajorAnalysisPhpVersion() . '.' . $codebase->getMinorAnalysisPhpVersion(),
                new CodeLocation($statements_analyzer->getSource(), $stmt),
            ),
            $statements_analyzer->getSuppressedIssues(),
        );

        return false;
    }

    public static function isMock(string $fq_class_name): bool
    {
        return in_array(strtolower($fq_class_name), Config::getInstance()->getMockClasses(), true);
    }

    /**
     * @param PhpParser\Node\Expr\Assign|PhpParser\Node\Expr\AssignRef $stmt
     */
    private static function analyzeAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $from_stmt
    ): bool {
        $assignment_type = AssignmentAnalyzer::analyze(
            $statements_analyzer,
            $stmt->var,
            $stmt->expr,
            null,
            $context,
            $stmt->getDocComment(),
            [],
            !$from_stmt ? $stmt : null,
        );

        if ($assignment_type === false) {
            return false;
        }

        if (!$from_stmt) {
            $statements_analyzer->node_data->setType($stmt, $assignment_type);
        }

        return true;
    }

    private static function dispatchBeforeExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $event = new BeforeExpressionAnalysisEvent(
            $expr,
            $context,
            $statements_analyzer,
            $codebase,
            [],
        );

        if ($codebase->config->eventDispatcher->dispatchBeforeExpressionAnalysis($event) === false) {
            return false;
        }

        $file_manipulations = $event->getFileReplacements();

        if ($file_manipulations !== []) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
        }

        return null;
    }

    private static function dispatchAfterExpressionAnalysis(
        PhpParser\Node\Expr $expr,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $event = new AfterExpressionAnalysisEvent(
            $expr,
            $context,
            $statements_analyzer,
            $codebase,
            [],
        );

        if ($codebase->config->eventDispatcher->dispatchAfterExpressionAnalysis($event) === false) {
            return false;
        }

        $file_manipulations = $event->getFileReplacements();

        if ($file_manipulations !== []) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
        }

        return null;
    }
}
