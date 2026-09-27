<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

/**
 * @psalm-immutable
 * @api
 */
final class MethodVisibilityProviderEvent
{
    /**
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly int $fq_classlike_name,
        private readonly int $method_name,
        private readonly Context $context,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
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

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
