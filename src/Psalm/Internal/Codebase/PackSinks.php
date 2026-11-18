<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function count;
use function ctype_digit;
use function min;
use function strlen;
use function strpos;

/**
 * pack() writes an integer in a fixed size (`c`, `s`, `l`, `N`, ...): one out of its range wraps around. Each argument
 * a literal format packs as such an integer is an `int_overflow` sink, unless its type fits.
 *
 * @internal
 */
final class PackSinks
{
    /** The codes packing an integer in fewer than 64 bits => the range of the values they keep */
    private const RANGES = [
        'c' => [-128, 127],
        'C' => [0, 255],
        's' => [-32_768, 32_767],
        'S' => [0, 65_535],
        'n' => [0, 65_535],
        'v' => [0, 65_535],
        'l' => [-2_147_483_648, 2_147_483_647],
        'i' => [-2_147_483_648, 2_147_483_647],
        'L' => [0, 4_294_967_295],
        'N' => [0, 4_294_967_295],
        'V' => [0, 4_294_967_295],
        'I' => [0, 4_294_967_295],
    ];

    /** The codes that take an argument for each repetition; the others take one argument (a string) or none */
    private const NUMBER_CODES = 'cCsSnvlLNViIqQJPfgGdeE';

    /** The codes that take no argument */
    private const NO_ARGUMENT_CODES = 'xX@';

    /**
     * @param array<array-key, Arg> $args
     */
    public static function addSinks(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        array $args,
        CodeLocation $code_location,
    ): void {
        $format_type = isset($args[0]) && !$args[0]->unpack && $args[0]->name === null
            ? $statements_analyzer->node_data->getType($args[0]->value)
            : null;

        if ($format_type === null || !$format_type->isSingleStringLiteral()) {
            return;
        }

        $codes = self::getArgumentCodes($format_type->getSingleStringLiteral()->value, count($args) - 1);

        foreach ($codes as $offset => $code) {
            $arg = $args[$offset + 1] ?? null;
            $range = self::RANGES[$code] ?? null;

            if ($arg === null || $arg->unpack || $arg->name !== null || $range === null) {
                continue;
            }

            $arg_type = $statements_analyzer->node_data->getType($arg->value);

            if ($arg_type === null || $arg_type->parent_nodes === [] || self::fits($arg_type, $range)) {
                continue;
            }

            $sink = DataFlowNode::getForCallableArg(
                'builtin',
                'pack[' . $code . ']',
                $offset + 1,
                $code_location,
                TaintKind::INPUT_INT_OVERFLOW,
            );
            $graph->addSink($sink);

            foreach ($arg_type->parent_nodes as $parent_node) {
                $graph->addPath($parent_node, $sink, 'arg');
            }
        }
    }

    /**
     * The code each argument after the format is packed with, by offset
     *
     * @return array<int, string>
     * @psalm-pure
     */
    private static function getArgumentCodes(string $format, int $arg_count): array
    {
        $codes = [];
        $offset = 0;
        $length = strlen($format);

        for ($position = 0; $position < $length && $offset < $arg_count; $position++) {
            $code = $format[$position];
            $count = '';

            while ($position + 1 < $length
                && (ctype_digit($format[$position + 1]) || $format[$position + 1] === '*')
            ) {
                $count .= $format[++$position];
            }

            if (strpos(self::NO_ARGUMENT_CODES, $code) !== false) {
                continue;
            }

            if (strpos(self::NUMBER_CODES, $code) === false) {
                // a string, whatever its length
                $offset++;
                continue;
            }

            // what is left of the arguments, whatever the count says
            $repetitions = $count === '*' || strlen($count) > 9 ? $arg_count - $offset : ($count === '' ? 1 : (int) $count);
            $repetitions = min($repetitions, $arg_count - $offset);

            for ($repetition = 0; $repetition < $repetitions; $repetition++) {
                $codes[$offset++] = $code;
            }
        }

        return $codes;
    }

    /**
     * Whether every value of the type is in the range
     *
     * @param array{int, int} $range
     * @psalm-pure
     */
    private static function fits(Union $type, array $range): bool
    {
        foreach ($type->getAtomicTypes() as $atomic) {
            if ($atomic instanceof TLiteralInt) {
                if ($atomic->value < $range[0] || $atomic->value > $range[1]) {
                    return false;
                }
            } elseif (!$atomic instanceof TIntRange
                || $atomic->min_bound === null
                || $atomic->max_bound === null
                || $atomic->min_bound < $range[0]
                || $atomic->max_bound > $range[1]
            ) {
                return false;
            }
        }

        return true;
    }
}
