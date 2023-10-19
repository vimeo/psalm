<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

final class EnumCaseStorage
{
    public bool $deprecated = false;

    public function __construct(
        public int|string|null $value,
        public CodeLocation $location,
    ) {
    }
}
