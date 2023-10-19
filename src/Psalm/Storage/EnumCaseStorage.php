<?php

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

final class EnumCaseStorage
{
    /**
     * @var TLiteralString|TLiteralInt|null
     */
    public $value;

    /** @var CodeLocation */
    public $stmt_location;

    /**
     * @var bool
     */
    public $deprecated = false;

    public function __construct(
        TLiteralString|TLiteralInt|null $value,
        CodeLocation $location,
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }
}
