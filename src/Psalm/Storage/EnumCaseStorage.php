<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

final class EnumCaseStorage
{
    public TLiteralString|TLiteralInt|null $value = null;

    public CodeLocation $stmt_location;

    public bool $deprecated = false;

    public function __construct(
        TLiteralString|TLiteralInt|null $value,
        CodeLocation $location,
    ) {
        $this->value = $value;
        $this->stmt_location = $location;
    }
}
