<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use PhpParser\Node\Expr\ArrayItem;
use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type;

class SafeArrayKeyChecker implements RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return list<string>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): array {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();
        if (!($item instanceof ArrayItem) || (!$statements_analyzer instanceof StatementsAnalyzer)) {
            return [];
        }
        $item_key_value = '';
        if ($item->key) {
            if ($item_key_type = $statements_analyzer->node_data->getType($item->key)) {
                $key_type = $item_key_type;

                if ($key_type->isSingleStringLiteral()) {
                    $item_key_value = $key_type->getSingleStringLiteral()->value;
                }
            }
        }

        if ($item_key_value === 'safe_key') {
            return ['html'];
        }
        return [];
    }
}
