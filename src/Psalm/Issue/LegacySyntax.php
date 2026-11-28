<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

/**
 * @api
 */
final class LegacySyntax extends CodeIssue
{
    public const ERROR_LEVEL = 2;
    public const SHORTCODE = 393;

    /**
     * @param string $replacement what Psalter writes in place of the code at $code_location
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public readonly string $replacement,
    ) {
        parent::__construct($message, $code_location);
    }
}
