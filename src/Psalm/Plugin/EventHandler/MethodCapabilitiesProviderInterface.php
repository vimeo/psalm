<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodCapabilitiesProviderEvent;

/**
 * @api
 */
interface MethodCapabilitiesProviderInterface
{
    /**
     * The classes declaring the methods; calls on their descendants are given to the hook too
     *
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * Use this hook to give the capabilities (a mask of `Psalm\Storage\Capabilities` constants) a call
     * of a method requires, when they depend on the call: e.g. a `get(string $key)` that calls the
     * subclass's `get_{$key}()`. They replace the method's own capabilities for this call, before the
     * receiver is accounted for (a write of the callee's `$this` costs the caller write-this-props or
     * write-props). Return null to keep the method's own capabilities.
     */
    public static function getMethodCapabilities(MethodCapabilitiesProviderEvent $event): ?int;
}
