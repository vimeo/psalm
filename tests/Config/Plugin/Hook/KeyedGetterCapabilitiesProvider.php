<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use PhpParser\Node\Scalar\String_;
use Psalm\Plugin\EventHandler\Event\MethodCapabilitiesProviderEvent;
use Psalm\Plugin\EventHandler\MethodCapabilitiesProviderInterface;
use Psalm\Storage\Capabilities;

/**
 * `Ns\Model::get($key)` with the literal key `name` only reads the model's properties
 *
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class KeyedGetterCapabilitiesProvider implements MethodCapabilitiesProviderInterface
{
    /**
     * @return array<string>
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Ns\Model'];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function getMethodCapabilities(MethodCapabilitiesProviderEvent $event): ?int
    {
        $args = $event->getCallArgs();
        if ($event->getMethodNameLowercase() === 'get'
            && isset($args[0])
            && $args[0]->value instanceof String_
            && $args[0]->value->value === 'name'
        ) {
            return Capabilities::READ_PROPS;
        }

        return null;
    }
}
