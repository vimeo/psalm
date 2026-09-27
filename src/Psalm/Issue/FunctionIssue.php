<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;

/**
 * @api
 */
abstract class FunctionIssue extends CodeIssue
{
    /**
     * Interned function id, as written (case-sensitive)
     */
    public int $function_id;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        int $function_id,
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = $function_id;
    }
}
