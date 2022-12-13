<?php

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class PropertyExistenceProviderEvent
{
    private string $fq_classlike_name;
    private string $property_name;
    private bool $read_mode;
    private ?StatementsSource $source;
    private ?Context $context;
    private ?CodeLocation $code_location;

    /**
     * Use this hook for informing whether or not a property exists on a given object. If you know the property does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the property actually exists.
     *
     * @internal
     */
    public function __construct(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ) {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->property_name = $property_name;
        $this->read_mode = $read_mode;
        $this->source = $source;
        $this->context = $context;
        $this->code_location = $code_location;
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

    public function getCodeLocation(): ?CodeLocation
    {
        return $this->code_location;
    }
}
