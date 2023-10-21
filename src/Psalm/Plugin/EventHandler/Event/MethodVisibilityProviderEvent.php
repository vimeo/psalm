<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class MethodVisibilityProviderEvent
{
    /** @internal */
    public function __construct(
        private readonly StatementsSource $source,
        private readonly string $fq_classlike_name,
        private readonly string $method_name_lowercase,
        private readonly Context $context,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    public function getSource(): StatementsSource
    {
        return $this->source;
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): string
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
