<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use Error;
use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\PartialApplication;
use Psalm\Node\Expr\VirtualIsset;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Type;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function bin2hex;
use function hex2bin;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function substr;

/**
 * @internal
 */
final class CoalesceAnalyzer
{
    private const OFFSET_MARKER = 'left operand of ?? at offset ';

    /**
     * The code of the operand on one line; the pretty printer has no method for the nodes Psalm makes up (a partial
     * application), which are named by the operand's position only
     */
    private static function printOperand(PhpParser\Node\Expr $operand): string
    {
        try {
            return (string) preg_replace(
                '/\s+/',
                ' ',
                (new PhpParser\PrettyPrinter\Standard())->prettyPrintExpr($operand),
            );
        } catch (Error) {
            return 'left operand of ??';
        }
    }

    /**
     * The name of a variable as issue messages show it: the temporary variable of a left operand that is not a
     * variable is shown as the code of the operand
     *
     * @psalm-pure
     */
    public static function getDisplayName(string $var_id): string
    {
        $pattern = '/^\$<' . preg_quote(self::OFFSET_MARKER, '/') . '\d+ ([0-9a-f]+)>$/';

        if (preg_match($pattern, $var_id, $matches) === 1) {
            return (string) hex2bin($matches[1] ?? '');
        }

        return $var_id;
    }

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp\Coalesce $stmt,
        Context $context,
    ): bool {
        $left_expr = $stmt->left;

        $root_expr = $left_expr;

        while ($root_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $root_expr instanceof PhpParser\Node\Expr\PropertyFetch
        ) {
            $root_expr = $root_expr->var;
        }

        $has_call_root = $root_expr instanceof PhpParser\Node\Expr\FuncCall
            || $root_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_expr instanceof PhpParser\Node\Expr\Cast
            || $root_expr instanceof PhpParser\Node\Expr\Match_
            || $root_expr instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            || $root_expr instanceof PhpParser\Node\Expr\NullsafeMethodCall
            || $root_expr instanceof PhpParser\Node\Expr\Ternary
            || $root_expr instanceof PhpParser\Node\Expr\BinaryOp\Pipe
            || $root_expr instanceof PartialApplication;

        $has_untracked_offset = !$has_call_root
            && $left_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            && ExpressionIdentifier::getExtendedVarId(
                $left_expr,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            ) === null;

        if ($has_call_root || $has_untracked_offset) {
            // the offset makes it unique; issue messages show the code of the operand ({@see self::getDisplayName()}),
            // hex encoded as the name of a variable has no `->`, `[` or `(` that would be read as an offset
            $left_var_id = '$<' . self::OFFSET_MARKER . (int) $left_expr->getAttribute('startFilePos')
                . ' ' . bin2hex(self::printOperand($left_expr)) . '>';

            // an untracked offset is evaluated unconditionally, so its assignments belong to the real context
            $cloned = $has_untracked_offset ? $context : clone $context;
            $was_inside_isset = $cloned->inside_isset;
            $was_inside_general_use = $cloned->inside_general_use;
            $cloned->inside_isset = true;
            // the value of the left operand decides which side is taken
            $cloned->inside_general_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $left_expr, $cloned);

            if ($has_untracked_offset) {
                $context->inside_isset = $was_inside_isset;
                $context->inside_general_use = $was_inside_general_use;
            }

            if ($root_expr !== $left_expr) {
                $condition_type = $statements_analyzer->node_data->getType($left_expr);
                if ($condition_type) {
                    $condition_type = $condition_type->setPossiblyUndefined(true);
                } else {
                    $condition_type = new Union([new TMixed()], ['possibly_undefined' => true]);
                }
            } else {
                $condition_type = $statements_analyzer->node_data->getType($left_expr) ?? Type::getMixed();
            }

            $context->vars_in_scope[$left_var_id] = $condition_type;

            $left_expr = new VirtualVariable(
                substr($left_var_id, 1),
                $left_expr->getAttributes(),
            );
        }

        $ternary = new VirtualTernary(
            new VirtualIsset(
                [$left_expr],
                $stmt->left->getAttributes(),
            ),
            $left_expr,
            $stmt->right,
            $stmt->getAttributes(),
        );

        $old_node_data = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary) ?? Type::getMixed();

        $statements_analyzer->node_data = $old_node_data;

        $statements_analyzer->node_data->setType($stmt, $ternary_type);

        // `$a ?? throw …` ends unless $a is set, so it is set afterwards
        if ($stmt->right instanceof PhpParser\Node\Expr\Throw_) {
            $coalesced_var_id = ExpressionIdentifier::getExtendedVarId(
                $stmt->left,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if ($coalesced_var_id !== null && isset($context->vars_in_scope[$coalesced_var_id])) {
                $changed_var_ids = [];

                [$context->vars_in_scope, $context->references_in_scope] = Reconciler::reconcileKeyedTypes(
                    [$coalesced_var_id => [[new IsIsset()]]],
                    [],
                    $context->vars_in_scope,
                    $context->references_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                );

                foreach ($changed_var_ids as $changed_var_id => $_) {
                    $context->removeVarFromConflictingClauses($changed_var_id);
                }
            }
        }

        return true;
    }
}
