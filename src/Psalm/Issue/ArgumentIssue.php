<?php

declare(strict_types=1);

namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Internal\MethodIdentifier;
use Psalm\Interner;

/**
 * @api
 */
abstract class ArgumentIssue extends CodeIssue
{
    /**
     * Interned lowercase function id, or method id with a lowercase class name
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
        $this->function_id = self::normalizeFunctionId($function_id);
    }

    /**
     * @psalm-pure
     */
    protected static function normalizeFunctionId(int|MethodIdentifier|null $function_id): int|MethodIdentifier|null
    {
        if ($function_id === null) {
            return null;
        }

        if ($function_id instanceof MethodIdentifier) {
            return new MethodIdentifier(Interner::lower($function_id->fq_class_name), $function_id->method_name);
        }

        return Interner::lower($function_id);
    }
}
