<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\Hook;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Storage\FunctionLikeParameter;

/**
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class NullFunctionParamsProvider implements FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['strlen', 'strtr', 'nullparamsfunction'];
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        return null;
    }
}
