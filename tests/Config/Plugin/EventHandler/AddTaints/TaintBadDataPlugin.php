<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use PhpParser\Node\Expr\Variable;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKind;
use Psalm\Type\TaintKindGroup;

/**
 * Add input taints to all variables named 'bad_data'
 *
 * @psalm-suppress UnusedClass
 */
final class TaintBadDataPlugin implements AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return list<string>
     */
    #[\Override]
    public static function addTaints(AddRemoveTaintsEvent $event): array
    {
        $expr = $event->getExpr();

        if (!$expr instanceof Variable) {
            return [];
        }

        switch ($expr->name) {
            case 'bad_data':
                return TaintKindGroup::ALL_INPUT;
            case 'bad_sql':
                return [TaintKind::INPUT_SQL];
            case 'bad_html':
                return [TaintKind::INPUT_HTML];
            case 'bad_eval':
                return [TaintKind::INPUT_EVAL];
            case 'bad_file':
                return [TaintKind::INPUT_FILE];
        }

        return [];
    }
}
