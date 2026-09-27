<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Interner;

/**
 * @api
 */
abstract class ClassConstantIssue extends CodeIssue
{
    /**
     * @param int $fq_classlike_name interned class name
     * @param int $const_name interned constant name
     * @psalm-mutation-free
     */
    public function __construct(
        string $message,
        CodeLocation $code_location,
        public int $fq_classlike_name,
        public int $const_name,
    ) {
        parent::__construct($message, $code_location);
    }

    /**
     * Returns the `Foo::BAR` constant id, e.g. for matching against config patterns.
     *
     * @psalm-mutation-free
     */
    public function getConstId(): string
    {
        return Interner::str($this->fq_classlike_name) . '::' . Interner::str($this->const_name);
    }
}
