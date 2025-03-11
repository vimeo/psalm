<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

final class PropertyVisibilityProviderEvent
{
    /** @internal */
    public function __construct(
        public readonly StatementsSource $source,
        public readonly string $fq_classlike_name,
        public readonly string $property_name,
        public readonly bool $read_mode,
        public readonly Context $context,
        public readonly CodeLocation $code_location,
    ) {
    }
}