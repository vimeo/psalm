<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

/**
 * @psalm-immutable
 */
final class TypeDoesNotContainType extends CodeIssue
{
    public const ERROR_LEVEL = 4;
    public const SHORTCODE = 56;

    /**
     * @psalm-mutation-free
     */
    public function __construct(string $message, CodeLocation $code_location, ?string $dupe_key)
    {
        parent::__construct($message, $code_location);
        $this->dupe_key = $dupe_key;
    }
}
