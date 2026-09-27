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
        private readonly int $method_name_lowercase,
        private readonly Context $context,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    public function getFqClasslikeName(): int
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): int
    {
        return $this->method_name_lowercase;
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
