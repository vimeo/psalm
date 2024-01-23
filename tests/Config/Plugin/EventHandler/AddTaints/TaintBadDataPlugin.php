<?php

namespace Psalm\Example\Plugin;

use PhpParser\Node\Expr\Variable;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKindGroup;

/**
 * Add input taints to all variables named 'bad_data'
 */
class TaintBadDataPlugin implements AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    public static function addTaints(AddRemoveTaintsEvent $event): array
    {
        $expr = $event->getExpr();

        if (!$expr instanceof Variable) {
            return [];
        }

        return $expr->name === 'bad_data' ? TaintKindGroup::ALL_INPUT : [];
    }
}
