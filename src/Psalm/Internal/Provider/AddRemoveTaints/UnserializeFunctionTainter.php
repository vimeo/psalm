<?php

namespace Psalm\Internal\Provider\AddRemoveTaints;

use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;
use stdClass;

use function array_diff;
use function count;
use function is_array;

/**
 * @internal
 */
class UnserializeFunctionTainter implements RemoveTaintsInterface
{
    private const ALLOWED_CLASSES = [
        stdClass::class
    ];

    /**
     * @return list<string>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): array
    {
        $expr = $event->getExpr();
        $statements_analyzer = $event->getStatementsSource();

        if (!$statements_analyzer instanceof StatementsAnalyzer
            || !$expr instanceof FuncCall
            || !$expr->name instanceof Name
            || (string)$expr->name !== 'unserialize'
            || $expr->isFirstClassCallable()
            || (count($expr->getArgs()) < 2)
        ) {
            return [];
        }

        $allowedClasses = self::resolveAllowedClasses($expr);
        if ($allowedClasses === false
            || is_array($allowedClasses) && array_diff($allowedClasses, self::ALLOWED_CLASSES) === []
        ) {
            return [TaintKind::INPUT_UNSERIALIZE];
        }

        return [];
    }

    /**
     * @param FuncCall $expr
     * @return list<class-string|string>|false|null
     */
    private static function resolveAllowedClasses(FuncCall $expr)
    {
        $args = $expr->getArgs();
        $options = $args[1]->value;
        if (!$options instanceof Array_
            || !isset($options->items[0]->key->value)
            || !$options->items[0] instanceof ArrayItem
            || !$options->items[0]->key->value === 'allowed_classes'
        ) {
            return null;
        }

        $value = $options->items[0]->value;
        if ($value instanceof ConstFetch && (string)$value->name === 'false') {
            return false;
        }

        if ($value instanceof Array_) {
            $allowedClasses = [];
            foreach ($value->items as $item) {
                if ($item->value instanceof ClassConstFetch) {
                    $allowedClasses[] = $item->value->class->getAttribute('resolvedName');
                } elseif ($item->value instanceof String_) {
                    $allowedClasses[] = $item->value->value;
                }
            }
            return $allowedClasses;
        }
        return null;
    }
}
