<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Type;

/**
 * @internal
 */
final class IssetAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Isset_ $stmt,
        Context $context
    ): void {
        foreach ($stmt->vars as $isset_var) {
            if ($isset_var instanceof PhpParser\Node\Expr\PropertyFetch
                && $isset_var->var instanceof PhpParser\Node\Expr\Variable
                && $isset_var->var->name === 'this'
                && $isset_var->name instanceof PhpParser\Node\Identifier
            ) {
                $var_id = '$this->' . $isset_var->name->name;

                if (!isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_id] = true;
                }
            } elseif (!self::isValidStatement($isset_var)) {
                IssueBuffer::maybeAdd(
                    new InvalidArgument(
                        'Isset only works with variables and array elements',
                        new CodeLocation($statements_analyzer->getSource(), $isset_var),
                        'empty',
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            self::analyzeIssetVar($statements_analyzer, $isset_var, $context);
        }

        $statements_analyzer->node_data->setType($stmt, Type::getBool());
    }

    public static function analyzeIssetVar(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ): void {
        $context->inside_isset = true;

        ExpressionAnalyzer::analyze($statements_analyzer, $stmt, $context);

        $context->inside_isset = false;
    }

    private static function isValidStatement(PhpParser\Node\Expr $stmt): bool
    {
        return $stmt instanceof PhpParser\Node\Expr\Variable
            || $stmt instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $stmt instanceof PhpParser\Node\Expr\PropertyFetch
            || $stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch
            || $stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $stmt instanceof PhpParser\Node\Expr\ClassConstFetch
            || $stmt instanceof PhpParser\Node\Expr\AssignRef;
    }
}
