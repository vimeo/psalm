<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\LocationlessNode;

use Override;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Psalm\CodeLocation;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\TaintKind;

/**
 * Passes what is given to relay() to the output of deliver(), through a node with no location: like a template
 * engine does with the variables of a template and its output.
 */
final class RelayPlugin implements AfterExpressionAnalysisInterface
{
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        $graph = $event->getCodebase()->taint_flow_graph;

        if ($graph === null || !$expr instanceof FuncCall || !$expr->name instanceof Name) {
            return null;
        }

        $relay = DataFlowNode::getForPropertyFetch('relay');
        $graph->addNode($relay);

        if ($expr->name->toLowerString() === 'relay' && isset($expr->getArgs()[0])) {
            $type = $event->getStatementsSource()->getNodeTypeProvider()->getType($expr->getArgs()[0]->value);
            foreach ($type?->parent_nodes ?? [] as $parent_node) {
                $graph->addPath($parent_node, $relay, 'arg');
            }
        } elseif ($expr->name->toLowerString() === 'deliver') {
            $sink = DataFlowNode::getForTaint(
                'deliver',
                new CodeLocation($event->getStatementsSource(), $expr),
                TaintKind::INPUT_HTML,
            );
            $graph->addNode($sink);
            $graph->addSink($sink);
            $graph->addPath($relay, $sink, 'arg');
        }

        return null;
    }
}
