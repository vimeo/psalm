<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler\Event;

use Psalm\Context;
use Psalm\StatementsSource;

final class PropertyTypeProviderEvent
{
    /** @internal */
    public function __construct(
        public readonly string $fq_classlike_name,
        public readonly string $property_name,
        public readonly bool $read_mode,
        public readonly ?StatementsSource $source = null,
        public readonly ?Context $context = null,
    ) {
    }
}