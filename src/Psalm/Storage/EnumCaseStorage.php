<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;

final class EnumCaseStorage
{
    /**
     * @var int|string|null
     */
    public $value;

    /** @var CodeLocation */
    public $stmt_location;

    /**
     * @var bool
     */
    public $deprecated = false;

    public function __construct(
        int|string|null $value,
        CodeLocation $location,
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }
}
