<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use UnexpectedValueException;

use function ltrim;
use function strpos;
use function strtolower;
use function substr;

/**
 * Turns a callable string (`'helper'`, `'\Ns\helper'`, `'A::m'`) into the function or method it names.
 *
 * @internal
 */
final class CallableStringResolver
{
    /**
     * @return non-empty-lowercase-string|MethodIdentifier|null
     * @psalm-capabilities read-props
     */
    public static function getId(Codebase $codebase, string $callable): string|MethodIdentifier|null
    {
        $callable = ltrim($callable, '\\');

        if ($callable === '') {
            return null;
        }

        $method_separator = strpos($callable, '::');

        if ($method_separator === false || $method_separator === 0) {
            return strtolower($callable);
        }

        return new MethodIdentifier(
            $codebase->classlikes->getUnAliasedName(substr($callable, 0, $method_separator)),
            strtolower(substr($callable, $method_separator + 2)),
        );
    }

    /**
     * Resolves a callable string used (passed as a callable or called) at $location, and records the
     * reference to the function or method it names.
     *
     * @return non-empty-lowercase-string|MethodIdentifier|null
     */
    public static function resolveUse(
        StatementsAnalyzer $statements_analyzer,
        string $callable,
        Context $context,
        CodeLocation $location,
    ): string|MethodIdentifier|null {
        $codebase = $statements_analyzer->getCodebase();

        $id = self::getId($codebase, $callable);

        if ($id instanceof MethodIdentifier) {
            $codebase->methodExists(
                $id,
                $context->calling_method_id,
                $location,
                $statements_analyzer,
                $statements_analyzer->getFilePath(),
                true,
                $context->insideUse(),
            );

            return $id;
        }

        if ($id === null
            || !$codebase->collect_locations
            || !$codebase->functions->functionExists($statements_analyzer, $id)
        ) {
            return $id;
        }

        try {
            $function_storage = $codebase->functions->getStorage($statements_analyzer, $id);
        } catch (UnexpectedValueException) {
            return $id;
        }

        // only the functions of the code are referenced, as for direct calls
        if ($function_storage->location !== null) {
            $codebase->addReferenceToFunctionLike(
                $id,
                $location,
                $context,
                $context->insideUse(),
                $statements_analyzer->getFilePath(),
            );
        }

        return $id;
    }
}
