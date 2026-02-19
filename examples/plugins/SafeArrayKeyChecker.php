<?php

namespace Psalm\Example\Plugin;

use PhpParser\Node\ArrayItem;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

final class SafeArrayKeyChecker implements RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return int
     */
    #[\Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
    {
        $item = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();
        if (!($item instanceof ArrayItem) || !($statements_analyzer instanceof StatementsAnalyzer)) {
            return 0;
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
            return TaintKind::INPUT_HTML;
        }
        return 0;
    }
}
