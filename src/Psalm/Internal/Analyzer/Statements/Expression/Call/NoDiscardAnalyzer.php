<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\Codebase;
use Psalm\Context;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Storage\MethodStorage;

/**
 * @internal
 */
final class NoDiscardAnalyzer
{
    /**
     * Whether discarding this call's return value should be reported.
     *
     * PHP 8.5's `#[\NoDiscard]` requires callers to use the return value. Unlike Psalm's
     * pure-call check this is independent of purity and of `findUnusedVariables`, matching the
     * runtime behaviour. It is gated on PHP 8.5, where the attribute is enforced and the
     * `(void)` escape hatch parses; below that the attribute is inert and `@psalm-suppress`
     * stays the only way to silence a report.
     *
     * `(void)` needs no special case here: it analyses its operand with `inside_general_use`,
     * so `$context->insideUse()` is already true by the time the call is reached.
     *
     * @param ?ClassLikeStorage $class_storage the class the call resolved through, for methods
     */
    public static function isDiscardReported(
        Codebase $codebase,
        Context $context,
        FunctionLikeStorage $storage,
        bool $is_first_class_callable,
        ?ClassLikeStorage $class_storage = null,
    ): bool {
        if (!$storage->no_discard
            || $codebase->analysis_php_version_id < 8_05_00
            || $is_first_class_callable
            || $context->collect_initializations
            || $context->collect_mutations
            || $context->inside_unset
            || $context->insideUse()
        ) {
            return false;
        }

        // PHP does not inherit #[\NoDiscard] onto an implementation, so a call that resolves to
        // an interface or abstract declaration does not report; the implementation must carry
        // the attribute itself.
        if ($class_storage?->is_interface === true
            || ($storage instanceof MethodStorage && $storage->abstract)
        ) {
            return false;
        }

        // A void/never native return has nothing to discard. PHP rejects such a declaration
        // outright (reported as InvalidAttribute), so this is only a defensive guard.
        $return_type = $storage->signature_return_type;

        return $return_type === null || (!$return_type->isVoid() && !$return_type->isNever());
    }
}
