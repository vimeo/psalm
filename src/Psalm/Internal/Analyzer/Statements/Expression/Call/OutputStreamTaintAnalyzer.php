<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOp\ConcatAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\TaintKind;
use Psalm\Type\Union;

use function array_slice;
use function strtolower;

/**
 * A stream opened on php://output writes to the response in every SAPI, and one opened on php://stdout does under
 * the CGI SAPI: what is written to them is output as echo outputs it, so it must not be given what echo must not.
 *
 * The streams opened on a literal php://output or php://stdout path (fopen(), new SplFileObject()) are followed
 * through the variables they are assigned to in the function-like opening them (see TaintFlowGraph::addOutputStream()),
 * not through properties, parameters or return values: a stream held by an object or passed around may as well be a
 * file in the other calls. Writing to a stream does not otherwise make what is written a sink, as most streams are
 * files.
 *
 * @internal
 */
final class OutputStreamTaintAnalyzer
{
    /** What echo must not be given (see EchoAnalyzer) */
    private const SINKS = TaintKind::INPUT_HTML
        | TaintKind::INPUT_HAS_QUOTES
        | TaintKind::USER_SECRET
        | TaintKind::SYSTEM_SECRET;

    /**
     * The functions writing to a stream or a path => the offset and the name of the parameter given that stream or
     * path: what the other parameters are given is written
     */
    private const WRITING_FUNCTIONS = [
        'file_put_contents' => [0, 'filename'],
        'fprintf' => [0, 'stream'],
        'fputcsv' => [0, 'stream'],
        'fputs' => [0, 'stream'],
        'fwrite' => [0, 'stream'],
        'stream_copy_to_stream' => [1, 'to'],
        'vfprintf' => [0, 'stream'],
    ];

    /** The methods writing what they are given to the file of the object they are called on */
    private const WRITING_METHODS = [
        'splfileobject::fputcsv' => true,
        'splfileobject::fwrite' => true,
    ];

    /**
     * The stream $function_id returns (fopen()) or the object it builds (SplFileObject::__construct()) when given
     * $args: when its path is a literal php://output or php://stdout, it gets a parent node marking it as a stream
     * writing to the response.
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function taintOpenedStream(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
        Union $stream_type,
        CodeLocation $location,
    ): Union {
        $function_id = strtolower($function_id);

        if ($function_id !== 'fopen' && $function_id !== 'splfileobject::__construct') {
            return $stream_type;
        }

        $graph = $statements_analyzer->getTaintFlowGraphWithSuppressed();
        $path_type = self::getArgType($statements_analyzer, $args, 0, 'filename');

        if (!$graph || !$path_type || !self::isOutputPath($path_type)) {
            return $stream_type;
        }

        $stream_node = DataFlowNode::getForAssignment($function_id . '(' . $path_type->getId() . ')', $location);

        $graph->addNode($stream_node);
        $graph->addOutputStream($stream_node);

        return $stream_type->addParentNodes([$stream_node->id => $stream_node]);
    }

    /**
     * Makes what the function writing to a stream or a path is given an output sink when it writes to the response
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function taintFunctionWrite(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
    ): void {
        $function_id = strtolower($function_id);

        if (!isset(self::WRITING_FUNCTIONS[$function_id])
            || !$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()
        ) {
            return;
        }

        [$stream_offset, $stream_name] = self::WRITING_FUNCTIONS[$function_id];

        $stream_type = self::getArgType($statements_analyzer, $args, $stream_offset, $stream_name);

        if (!$stream_type || (!self::isOutputPath($stream_type) && !$graph->isOutputStream($stream_type))) {
            return;
        }

        $removed_taints = self::getTaintsRemovedByFormat($statements_analyzer, $function_id, $args);

        foreach ($args as $offset => $arg) {
            if ($arg->name !== null ? $arg->name->name !== $stream_name : $offset !== $stream_offset) {
                self::addSink($statements_analyzer, $graph, $function_id, $arg, $removed_taints[$offset] ?? 0);
            }
        }
    }

    /**
     * The taints the values fprintf() or vfprintf() is given can't write, by offset in $args: those of a string, for
     * the values a literal format only formats as numbers, as for printf() and vprintf() (see ArgumentsAnalyzer)
     *
     * @param list<PhpParser\Node\Arg> $args
     * @return array<int, int>
     */
    private static function getTaintsRemovedByFormat(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $args,
    ): array {
        if (($function_id !== 'fprintf' && $function_id !== 'vfprintf') || !isset($args[1])) {
            return [];
        }

        $format_type = $statements_analyzer->node_data->getType($args[1]->value);

        if (!$format_type || !$format_type->allStringLiterals()) {
            return [];
        }

        $removed_taints = [];

        // the format and the values are given after the stream, so offsets are one past those of printf()
        foreach (FunctionCallReturnTypeFetcher::getTaintsRemovedBySprintfFormats(
            ConcatAnalyzer::getLiteralValues($format_type),
            array_slice($args, 1),
            $function_id === 'vfprintf',
        ) as $offset => $taints) {
            $removed_taints[$offset + 1] = $taints;
        }

        return $removed_taints;
    }

    /**
     * Makes what the method writing to the file of an SplFileObject is given an output sink when the object writes
     * to the response
     *
     * @param list<PhpParser\Node\Arg> $args
     */
    public static function taintMethodWrite(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\MethodCall $stmt,
        string $method_id,
        array $args,
    ): void {
        $method_id = strtolower($method_id);

        if (!isset(self::WRITING_METHODS[$method_id])
            || !$graph = $statements_analyzer->getTaintFlowGraphWithSuppressed()
        ) {
            return;
        }

        $object_type = $statements_analyzer->node_data->getType($stmt->var);

        if (!$object_type || !$graph->isOutputStream($object_type)) {
            return;
        }

        foreach ($args as $arg) {
            self::addSink($statements_analyzer, $graph, $method_id, $arg);
        }
    }

    /**
     * Whether a value of $type is the path of a stream writing to the response
     *
     * @psalm-pure
     */
    private static function isOutputPath(Union $type): bool
    {
        if (!$type->isSingleStringLiteral()) {
            return false;
        }

        $path = strtolower($type->getSingleStringLiteral()->value);

        return $path === 'php://output' || $path === 'php://stdout';
    }

    /**
     * @param list<PhpParser\Node\Arg> $args
     */
    private static function getArgType(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        int $offset,
        string $name,
    ): ?Union {
        foreach ($args as $arg_offset => $arg) {
            if ($arg->name !== null ? $arg->name->name === $name : $arg_offset === $offset) {
                return $statements_analyzer->node_data->getType($arg->value);
            }
        }

        return null;
    }

    private static function addSink(
        StatementsAnalyzer $statements_analyzer,
        TaintFlowGraph $graph,
        string $function_id,
        PhpParser\Node\Arg $arg,
        int $removed_taints = 0,
    ): void {
        $arg_type = $statements_analyzer->node_data->getType($arg->value);

        if (!$arg_type) {
            return;
        }

        $location = new CodeLocation($statements_analyzer, $arg->value);

        $sink = DataFlowNode::getForTaint(
            $function_id . ' writing to the response',
            $location,
            self::SINKS & ~$removed_taints,
        );

        $graph->addSink($sink);

        foreach ($arg_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $sink, 'arg', 0, $arg_type->getTaintsToRemove());
        }
    }
}
