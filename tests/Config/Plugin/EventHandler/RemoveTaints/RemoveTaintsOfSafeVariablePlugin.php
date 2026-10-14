<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

use Override;
use PhpParser\Node\Expr\Variable;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

/**
 * Removes the input taints of what a variable named `$safe` gives, e.g. the result of the methods called on it
 *
 * @psalm-suppress UnusedClass
 * @psalm-immutable
 */
final class RemoveTaintsOfSafeVariablePlugin implements RemoveTaintsInterface
{
    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
    {
        $expr = $event->getExpr();

        return $expr instanceof Variable && $expr->name === 'safe' ? TaintKind::ALL_INPUT : 0;
    }
}
