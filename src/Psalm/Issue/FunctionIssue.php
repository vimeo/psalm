<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Interner;

/**
 * @api
 */
abstract class FunctionIssue extends CodeIssue
{
    /**
     * Interned lowercase function id
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
        $this->function_id = Interner::lower($function_id);
    }
}
