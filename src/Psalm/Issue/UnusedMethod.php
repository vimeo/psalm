<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;

use function strtolower;

/**
 * @api
 */
final class UnusedMethod extends MethodIssue
{
    public const ERROR_LEVEL = -2;
    public const SHORTCODE = 76;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        MethodIdentifier $method_id,
    ) {
        parent::__construct($message, $code_location, $method_id);
        $this->dupe_key = strtolower((string) $method_id);
    }
}
