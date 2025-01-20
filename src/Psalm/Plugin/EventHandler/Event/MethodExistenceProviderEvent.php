<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

final class MethodExistenceProviderEvent
{
    /**
     * Use this hook for informing whether or not a method exists on a given object. If you know the method does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the method actually exists.
     *
     * @internal
     */
    public function __construct(
        private readonly string $fq_classlike_name,
        private readonly string $method_name_lowercase,
        private readonly ?StatementsSource $source = null,
        private readonly ?CodeLocation $code_location = null,
    ) {
    }

    public function getFqClasslikeName(): string
    {
        return $this->fq_classlike_name;
    }

    public function getMethodNameLowercase(): string
    {
        return $this->method_name_lowercase;
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
