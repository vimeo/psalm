<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;

/**
 * @api
 */
abstract class MethodIssue extends CodeIssue
{
    /**
     * The method id
     */
    public MethodIdentifier $method_id;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        MethodIdentifier $method_id,
    ) {
        parent::__construct($message, $code_location);
        $this->method_id = $method_id;
    }
}
