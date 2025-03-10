<?php

declare(strict_types=1);

namespace Psalm\Example\Plugin;

use Override;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use Psalm\Plugin\EventHandler\AddTaintsInterface;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TaintKind;
use Psalm\Type\TaintKindGroup;
use Psalm\Type\Union;

use function strpos;

/**
 * Marks all property fetches of models inside namespace \app\models as tainted.
 * ActiveRecords are model-representation of database entries, which can always
 * contain user-input and therefor should be tainted.
 */
final class TaintActiveRecords implements AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return int
     */
    #[Override]
    public static function addTaints(AddRemoveTaintsEvent $event): int
    {
        $expr = $event->getExpr();

        // Model properties are accessed by property fetch, so abort here
        if ($expr instanceof ArrayItem) {
            return 0;
        }

        $statements_source = $event->getStatementsSource();

        // For all property fetch expressions, walk through the full fetch path
        // (e.g. `$model->property->subproperty`) and check if it contains
        // any class of namespace \app\models\
        do {
            $expr_type = $statements_source->getNodeTypeProvider()->getType($expr);
            if (!$expr_type) {
                continue;
            }

            if (self::containsActiveRecord($expr_type)) {
                return TaintKind::ALL_INPUT;
            }
        } while ($expr = self::getParentNode($expr));

        return 0;
    }

    /**
     * @return bool `true` if union contains a type of model
     */
    private static function containsActiveRecord(Union $union_type): bool
    {
        foreach ($union_type->getAtomicTypes() as $type) {
            if (self::isActiveRecord($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool `true` if namespace of type is in namespace `app\models`
     */
    private static function isActiveRecord(Atomic $type): bool
    {
        if (!$type instanceof TNamedObject) {
            return false;
        }

        return strpos($type->value, 'app\models\\') === 0;
    }


    /**
     * Return next node that should be followed for active record search
     */
    private static function getParentNode(ArrayItem|Expr $expr): ?Expr
    {
        // Model properties are always accessed by a property fetch
        if ($expr instanceof PropertyFetch) {
            return $expr->var;
        }

        return null;
    }
}
