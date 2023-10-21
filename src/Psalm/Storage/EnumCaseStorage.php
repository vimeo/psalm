<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\CodeLocation;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;

final class EnumCaseStorage
{
    public bool $deprecated = false;

    public function __construct(public TLiteralString|TLiteralInt|null $value, public CodeLocation $stmt_location)
    {
    }
}
