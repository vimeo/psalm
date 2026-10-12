<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\IntMask;
use Psalm\Issue\IntMaskComparison;
use Psalm\IssueBuffer;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function strtolower;

/**
 * Reports bit sets (see Union::$int_mask_bits) compared by value with a set that is neither the
 * empty nor the full one: `$flags === A` only holds when exactly A is set, where
 * `($flags & A) === A` (all of A is set) or `($flags & A) !== 0` (some of A is set) is usually meant.
 *
 * @internal
 */
final class IntMaskComparisonAnalyzer
{
    /**
     * The functions that compare a search value with the values of an array, with the position and
     * the name of their search value and array parameters.
     */
    private const SEARCH_FUNCTIONS = [
        'in_array' => [0, 'needle', 1, 'haystack'],
        'array_search' => [0, 'needle', 1, 'haystack'],
        'array_keys' => [1, 'filter_value', 0, 'array'],
    ];

    /**
     * Checks `===`, `!==`, `==`, `!=`, `<`, `<=`, `>`, `>=` and `<=>`, including the comparisons
     * made by `match` arms and `switch` cases.
     */
    public static function analyzeBinaryOp(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BinaryOp $stmt,
    ): void {
        $left_type = $statements_analyzer->node_data->getType($stmt->left);
        $right_type = $statements_analyzer->node_data->getType($stmt->right);

        if (!$left_type
            || !$right_type
            || ($left_type->int_mask_bits === null && $right_type->int_mask_bits === null)
        ) {
            return;
        }

        // ordering a set against an int only tells whether it is empty, e.g. `$mask > 0`
        $is_equality = $stmt instanceof PhpParser\Node\Expr\BinaryOp\Identical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotIdentical
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\Equal
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\NotEqual;

        if ($is_equality
            && (self::isSubsetTest($statements_analyzer, $stmt->left, $stmt->right)
                || self::isSubsetTest($statements_analyzer, $stmt->right, $stmt->left)
            )
        ) {
            return;
        }

        foreach ([[$left_type, $right_type], [$right_type, $left_type]] as [$mask_type, $other_type]) {
            if ($mask_type->int_mask_bits !== null && self::isAmbiguous($mask_type, $other_type, $is_equality)) {
                self::report(
                    $statements_analyzer,
                    $mask_type->int_mask_bits,
                    $is_equality,
                    new CodeLocation($statements_analyzer, $stmt),
                );

                return;
            }
        }
    }

    /**
     * Checks the search value of `in_array()`, `array_search()` and `array_keys()`, including the
     * search made by `match` arms with several conditions.
     */
    public static function analyzeSearch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\FuncCall $stmt,
        string $function_id,
        CodeLocation $code_location,
    ): void {
        $function_id = strtolower($function_id);

        if (!isset(self::SEARCH_FUNCTIONS[$function_id])) {
            return;
        }

        [$needle_position, $needle_name, $haystack_position, $haystack_name] = self::SEARCH_FUNCTIONS[$function_id];

        $needle = self::getArg($stmt, $needle_position, $needle_name);
        $haystack = self::getArg($stmt, $haystack_position, $haystack_name);

        $needle_type = $needle ? $statements_analyzer->node_data->getType($needle) : null;
        $haystack_type = $haystack ? $statements_analyzer->node_data->getType($haystack) : null;

        if (!$needle_type || !$haystack_type) {
            return;
        }

        foreach ($haystack_type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TKeyedArray) {
                $value_type = $atomic->getGenericValueType();
            } elseif ($atomic instanceof TArray) {
                $value_type = $atomic->type_params[1];
            } else {
                continue;
            }

            foreach ([[$needle_type, $value_type], [$value_type, $needle_type]] as [$mask_type, $other_type]) {
                if ($mask_type->int_mask_bits !== null && self::isAmbiguous($mask_type, $other_type, true)) {
                    self::report($statements_analyzer, $mask_type->int_mask_bits, true, $code_location);

                    return;
                }
            }
        }
    }

    /**
     * Whether comparing a bit set with a value may test for a set that is neither the empty set
     * nor, when $allow_full, the full set: those are the only sets that tell whether all or none
     * of the bits are set.
     *
     * @psalm-pure
     */
    private static function isAmbiguous(Union $mask_type, Union $other_type, bool $allow_full): bool
    {
        // a type that was a bit set before it got narrowed to something else entirely
        if (!$mask_type->hasInt()) {
            return false;
        }

        foreach ($other_type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralInt) {
                if ($atomic->value !== 0 && (!$allow_full || $atomic->value !== $mask_type->int_mask_bits)) {
                    return true;
                }
            } elseif (IntMask::isNonLiteralInt($atomic)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether `$a & $b` is compared with $a or $b, i.e. a subset test like
     * `($available & $required) === $required`.
     */
    private static function isSubsetTest(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $mask,
        PhpParser\Node\Expr $other,
    ): bool {
        if (!$mask instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
            return false;
        }

        $fq_class_name = $statements_analyzer->getFQCLN();

        $other_id = ExpressionIdentifier::getExtendedVarId($other, $fq_class_name, $statements_analyzer);

        if ($other_id === null) {
            return false;
        }

        foreach ([$mask->left, $mask->right] as $operand) {
            if ($other_id === ExpressionIdentifier::getExtendedVarId($operand, $fq_class_name, $statements_analyzer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-mutation-free
     */
    private static function getArg(
        PhpParser\Node\Expr\FuncCall $stmt,
        int $position,
        string $name,
    ): ?PhpParser\Node\Expr {
        foreach ($stmt->getArgs() as $i => $arg) {
            if ($arg->unpack) {
                return null;
            }

            if ($arg->name !== null ? $arg->name->name === $name : $i === $position) {
                return $arg->value;
            }
        }

        return null;
    }

    private static function report(
        StatementsAnalyzer $statements_analyzer,
        int $bits,
        bool $is_equality,
        CodeLocation $code_location,
    ): void {
        $how_to_test = 'to test some bits B, use ($mask & B) === B (all of B are set)'
            . ' or ($mask & B) !== 0 (any of B is set)';

        IssueBuffer::maybeAdd(
            new IntMaskComparison(
                $is_equality
                    ? 'An int-mask of bits ' . $bits . ' is compared by value with a value other than 0 (no bit set)'
                        . ' or ' . $bits . ' (every bit set), which only matches that exact set of bits: '
                        . $how_to_test
                    : 'An int-mask of bits ' . $bits . ' is ordered against a value other than 0, which does not'
                        . ' test its bits: ' . $how_to_test,
                $code_location,
            ),
            $statements_analyzer->getSuppressedIssues(),
        );
    }
}
