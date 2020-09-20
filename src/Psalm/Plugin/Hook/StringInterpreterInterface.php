<?php

declare(strict_types=1);

namespace Psalm\Plugin\Hook;

use Psalm\Type;

interface StringInterpreterInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function getTypeFromValue(
        string $value
    ) : ?Type\Atomic\TLiteralString;
}
