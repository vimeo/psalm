<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

/**
 * @api
 */
final class ImmutableDependency extends ClassIssue
{
    public const ERROR_LEVEL = 1;
    public const SHORTCODE = 366;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        string $fq_classlike_name = '',
    ) {
        parent::__construct($message, $code_location, $fq_classlike_name);
    }
}
