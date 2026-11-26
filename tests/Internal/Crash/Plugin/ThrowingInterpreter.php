<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash\Plugin;

use LogicException;
use Override;
use Psalm\Plugin\EventHandler\Event\StringInterpreterEvent;
use Psalm\Plugin\EventHandler\StringInterpreterInterface;
use Psalm\Type\Atomic\TLiteralString;

/**
 * @internal
 */
final class ThrowingInterpreter implements StringInterpreterInterface
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function getTypeFromValue(StringInterpreterEvent $event): ?TLiteralString
    {
        throw new LogicException('the interpreter failed');
    }
}
