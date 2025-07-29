<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Context;
use Psalm\StatementsSource;

final class PropertyTypeProviderEvent
{
    /** @internal */
    public function __construct(
        private readonly string $fq_classlike_name,
        private readonly string $property_name,
        private readonly bool $read_mode,
        private readonly ?StatementsSource $source = null,
        private readonly ?Context $context = null,
    ) {
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getPropertyName(): string
    {
        return $this->property_name;
    }

    public function isReadMode(): bool
    {
        return $this->read_mode;
    }

    public function getSource(): ?StatementsSource
    {
        return $this->source;
    }

    public function getContext(): ?Context
    {
        return $this->context;
    }
}
