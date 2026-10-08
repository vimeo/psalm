<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\Union;

use function dirname;
use function strtolower;

/**
 * The taint sources of the builtin functions that read from outside the program
 * (dictionaries/InternalTaintSourceMap.php).
 *
 * @internal
 */
final class InternalTaintSourceMap
{
    /** @var null|array<lowercase-string, non-empty-array<string, int>> */
    private static ?array $sources = null;

    /**
     * The taints of what $function_id returns (`return`) or writes to its by-reference parameter
     * named $target: none if it is not a source.
     *
     * @psalm-capabilities read-globals|write-globals
     */
    public static function getTaints(string $function_id, string $target): int
    {
        if (self::$sources === null) {
            /** @var array<lowercase-string, non-empty-array<string, int>> */
            self::$sources = require(dirname(__DIR__, 4) . '/dictionaries/InternalTaintSourceMap.php');
        }

        return self::$sources[strtolower($function_id)][$target] ?? 0;
    }

    /**
     * Adds the source of a builtin call reading $taints from outside the program, after the plugins
     * have added and removed taints, to $type: unchanged when none are left.
     */
    public static function addSource(
        TaintFlowGraph $graph,
        StatementsAnalyzer $statements_analyzer,
        Expr $stmt,
        string $source_id,
        int $taints,
        Context $context,
        Union $type,
    ): Union {
        $codebase = $statements_analyzer->getCodebase();
        $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

        $taints |= $codebase->config->eventDispatcher->dispatchAddTaints($event);
        $taints &= ~$codebase->config->eventDispatcher->dispatchRemoveTaints($event);

        if ($taints === 0) {
            return $type;
        }

        $source = DataFlowNode::getForTaint(
            strtolower($source_id),
            new CodeLocation($statements_analyzer->getSource(), $stmt),
            $taints,
        );

        $graph->addSource($source);

        return $type->addParentNodes([$source->id => $source]);
    }
}
