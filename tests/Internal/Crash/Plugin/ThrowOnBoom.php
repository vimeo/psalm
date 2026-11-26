<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash\Plugin;

use LogicException;
use Override;
use PhpParser\Node\Scalar\String_;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

/**
 * Throws on the string 'boom'
 *
 * @internal
 */
final class ThrowOnBoom implements AfterExpressionAnalysisInterface
{
    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        if ($expr instanceof String_ && $expr->value === 'boom') {
            throw new LogicException('boom');
        }

        return null;
    }
}
