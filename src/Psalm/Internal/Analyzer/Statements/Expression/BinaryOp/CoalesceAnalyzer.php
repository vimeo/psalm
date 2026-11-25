<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use Error;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\VirtualIsset;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Storage\Assertion\IsIsset;
use Psalm\Type;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function preg_replace;
use function str_starts_with;
use function strlen;
use function strpbrk;
use function substr;

/**
 * @internal
 */
final class CoalesceAnalyzer
{
    private const TEMPORARY_VAR_PREFIX = '$<tmp coalesce var>';

    /**
     * The code of the left operands of ?? that are not variables, by file and offset: the temporary variable of such
     * an operand is `$<tmp coalesce var>` followed by the offset, issue messages show the code instead
     *
     * @var array<string, array<int, string>>
     */
    private static array $operand_names = [];

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
     * @return ($var_id is null ? null : string)
     * @psalm-capabilities read-globals
     */
    public static function getDisplayName(?string $var_id, CodeLocation $code_location): ?string
    {
        if ($var_id === null || !str_starts_with($var_id, self::TEMPORARY_VAR_PREFIX)) {
            return $var_id;
        }

        $offset = (int) substr($var_id, strlen(self::TEMPORARY_VAR_PREFIX));

        return self::$operand_names[$code_location->file_path][$offset] ?? $var_id;
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

        // isset() only takes a variable, or an item or a property of one, of a static property or of a constant: any
        // other expression (a call, a constant itself, a literal, an operation, an assignment, ...) is evaluated once
        // into a temporary variable
        $has_call_root = !$root_expr instanceof PhpParser\Node\Expr\Variable
            && !$root_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && !(($root_expr instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $root_expr instanceof PhpParser\Node\Expr\ConstFetch)
                && $root_expr !== $left_expr);

        $has_untracked_offset = !$has_call_root
            && $left_expr instanceof PhpParser\Node\Expr\ArrayDimFetch
            && ExpressionIdentifier::getExtendedVarId(
                $left_expr,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            ) === null;

        if ($has_call_root || $has_untracked_offset) {
            // the offset makes it unique; issue messages show the code of the operand ({@see self::getDisplayName()})
            $left_offset = (int) $left_expr->getAttribute('startFilePos');
            $left_var_id = self::TEMPORARY_VAR_PREFIX . $left_offset;
            self::$operand_names[$statements_analyzer->getFilePath()][$left_offset] = self::printOperand($left_expr);

            // the left operand is evaluated unconditionally, so what it assigns (and the clauses and the types of
            // properties and offsets that an assignment drops) belongs to the real context
            $was_inside_isset = $context->inside_isset;
            $was_inside_general_use = $context->inside_general_use;
            // `??` gives isset semantics only to the fetch chain above the root, a nullsafe fetch included
            $fetch_root = $left_expr;

            while ($fetch_root instanceof PhpParser\Node\Expr\ArrayDimFetch
                || $fetch_root instanceof PhpParser\Node\Expr\PropertyFetch
                || $fetch_root instanceof PhpParser\Node\Expr\NullsafePropertyFetch
            ) {
                $fetch_root = $fetch_root->var;
            }

            $has_fetch_chain = $fetch_root !== $left_expr;
            $context->inside_isset = $was_inside_isset || $has_fetch_chain;
            // the value of the left operand decides which side is taken
            $context->inside_general_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $left_expr, $context);

            $context->inside_isset = $was_inside_isset;
            $context->inside_general_use = $was_inside_general_use;

            if ($has_fetch_chain) {
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

        $left_operand_var_id = ExpressionIdentifier::getExtendedVarId(
            $left_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );
        // a property may be uninitialized whatever its type says
        $left_operand_type = $left_operand_var_id !== null && strpbrk($left_operand_var_id, '->:') === false
            ? $context->vars_in_scope[$left_operand_var_id] ?? null
            : null;

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

        // the left side is only the result when it isn't null, even when isset() can't narrow it (ArrayAccess)
        $left_type = $statements_analyzer->node_data->getType($left_expr);
        $right_type = $statements_analyzer->node_data->getType($stmt->right);
        if ($left_type && $right_type && $left_type->isNullable() && !$left_type->isNull()) {
            $left_not_null = $left_type->getBuilder();
            $left_not_null->removeType('null');
            $ternary_type = Type::combineUnionTypes($left_not_null->freeze(), $right_type);
        }

        // a left side known to be set and not null is the result: the right side is never evaluated
        if ($left_type
            && $left_operand_type
            && !$left_operand_type->possibly_undefined
            && !$left_operand_type->possibly_undefined_from_try
            && !$left_operand_type->isNullable()
            && !$left_operand_type->hasMixed()
        ) {
            $ternary_type = $left_type;
        }

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
                    // narrowed, not written: the enclosing scopes keep their clauses about it
                    $context->clauses = Context::filterClauses(
                        $changed_var_id,
                        $context->clauses,
                        value_changed: false,
                    );
                }
            }
        }

        return true;
    }
}
