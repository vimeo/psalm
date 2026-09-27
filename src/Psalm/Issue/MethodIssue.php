<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;
use Psalm\Interner;

/**
 * @api
 */
abstract class MethodIssue extends CodeIssue
{
    /**
     * The method id, with a lowercase class name
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
        $this->method_id = new MethodIdentifier(Interner::lower($method_id->fq_class_name), $method_id->method_name);
    }
}
