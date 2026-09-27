<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

/**
 * @psalm-immutable
 * @api
 */
final class MethodExistenceProviderEvent
{
    /**
     * Use this hook for informing whether or not a method exists on a given object. If you know the method does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the method actually exists.
     *
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly int $fq_classlike_name,
        private readonly int $method_name,
        private readonly ?StatementsSource $source = null,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    /**
     * Interned class name id, as declared (class names are case-sensitive).
     */
    public function getFqClasslikeName(): int
    {
        return $this->fq_classlike_name;
    }

    /**
     * Interned method name id, as written (method names are case-sensitive, no case folding is applied).
     */
    public function getMethodName(): int
    {
        return $this->method_name;
    }

    public function getSource(): ?StatementsSource
    {
        return $this->source;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
