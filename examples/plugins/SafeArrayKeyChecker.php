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
use Psalm\Plugin\EventHandler\Event\ShouldTaintEvent;
use Psalm\Plugin\EventHandler\ShouldTaintInterface;
use Psalm\Type;

class SafeArrayKeyChecker implements ShouldTaintInterface
{
    /**
     * Called to see if a statement should be tainted.
     *
     * @return bool
     */
    public static function shouldTaint(ShouldTaintEvent $event): bool {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();
        if (!($item instanceof ArrayItem) || (!$statements_analyzer instanceof StatementsAnalyzer)) {
            return true;
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
            return false;
        }
        return true;
    }
}
