<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Arg;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type\TaintKind;

use function array_key_exists;

/**
 * What the code that only runs from the command line prints goes to a terminal, not to an HTML response: in the
 * function-likes with `@psalm-cli-only`, the methods of the classes with it, and the closures in their bodies, what
 * echo, print, printf(), vprintf(), var_dump(), debug_zval_dump(), and print_r() and var_export() not returning it
 * print isn't an HTML sink. They stay sinks of the other taints (secrets), and the values stay tainted elsewhere.
 *
 * @internal
 */
final class CliOutput
{
    /** The taints that aren't reported for what such code prints */
    public const HTML_TAINTS = TaintKind::INPUT_HTML | TaintKind::INPUT_HAS_QUOTES;

    /** The attribute of the arguments of a call printing them from code only running from the command line */
    public const PRINTED_ATTRIBUTE = 'cliOutputPrinted';

    /**
     * The builtin functions printing their arguments => the offset of the argument making them return it instead
     */
    private const PRINTING_FUNCTIONS = [
        'printf' => null,
        'vprintf' => null,
        'var_dump' => null,
        'debug_zval_dump' => null,
        'print_r' => 1,
        'var_export' => 1,
    ];

    /**
     * Whether the code being analyzed only runs from the command line: that of a function-like with `@psalm-cli-only`,
     * of a method of a class with it, or of a closure in their bodies
     */
    public static function isInCliOnlyCode(StatementsAnalyzer $statements_analyzer): bool
    {
        $source = $statements_analyzer->getSource();

        // a closure runs in the process of the code creating it
        while ($source instanceof ClosureAnalyzer) {
            if ($source->getFunctionLikeStorage($statements_analyzer)->cli_only) {
                return true;
            }

            $source = $source->getSource();
            if ($source instanceof StatementsAnalyzer) {
                $source = $source->getSource();
            }
        }

        if (!$source instanceof FunctionLikeAnalyzer) {
            return false;
        }

        if ($source->getFunctionLikeStorage($statements_analyzer)->cli_only) {
            return true;
        }

        $codebase = $statements_analyzer->getCodebase();
        $class = $source instanceof MethodAnalyzer ? $source->getFQCLN() : null;

        return $class !== null
            && $codebase->classlike_storage_provider->has($class)
            && $codebase->classlike_storage_provider->get($class)->cli_only;
    }

    /**
     * Marks the arguments of a call of a builtin function printing them, in code only running from the command line
     *
     * @param array<array-key, Arg> $args
     */
    public static function markPrintedArgs(
        StatementsAnalyzer $statements_analyzer,
        ?string $function_id,
        array $args,
    ): void {
        if ($function_id === null
            || !array_key_exists($function_id, self::PRINTING_FUNCTIONS)
            || !$statements_analyzer->taint_flow_graph
        ) {
            return;
        }

        $return_offset = self::PRINTING_FUNCTIONS[$function_id];
        if ($return_offset !== null && isset($args[$return_offset])) {
            // what it returns may go anywhere: only a literal false makes it print
            $return_type = $statements_analyzer->node_data->getType($args[$return_offset]->value);
            if ($return_type === null || !$return_type->isFalse()) {
                return;
            }
        }

        if (!self::isInCliOnlyCode($statements_analyzer)) {
            return;
        }

        foreach ($args as $offset => $arg) {
            if ($offset !== $return_offset && $arg->name === null && !$arg->unpack) {
                $arg->value->setAttribute(self::PRINTED_ATTRIBUTE, true);
            }
        }
    }
}
