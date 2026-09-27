<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;

/**
 * @api
 */
abstract class ArgumentIssue extends CodeIssue
{
    /**
     * Interned function id (as written, case-sensitive), or method id
     */
    public int|MethodIdentifier|null $function_id = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        int|MethodIdentifier|null $function_id = null,
    ) {
        parent::__construct($message, $code_location);
        $this->function_id = $function_id;
    }
}
