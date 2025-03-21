<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class PropertyExistenceProviderEvent
{
    /**
     * Use this hook for informing whether or not a property exists on a given object. If you know the property does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the property actually exists.
     *
     * @internal
     */
    public function __construct(
        public readonly string $fq_classlike_name,
        public readonly string $property_name,
        public readonly bool $read_mode,
        public readonly ?StatementsSource $source = null,
        public readonly ?Context $context = null,
        public readonly ?CodeLocation $code_location = null,
    ) {
    }
}