<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\Type\Union;

final class ConstantWithLocation
{
    public function __construct(
        public readonly Union $type,
        public readonly string $location,
    ) {
    }
}
