<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use PhpParser\Node\Expr\Variable;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

use function in_array;
use function is_string;

/**
 * The by-reference effect on the argument is handled by ArrayFunctionArgumentsAnalyzer.
 *
 * @internal
 */
final class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['array_pop', 'array_shift'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();

        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $call_args = $event->getCallArgs();

        // The by-reference adjustment is only reliable for plain variables that take no part in a
        // reference: it doesn't track array offsets or unpacked arguments, a property may be shared
        // with an object alias, and a reference may have been changed through another name. For
        // anything else, keep the historical inference (the first element of a list for array_shift,
        // the generic value type otherwise).
        $first_arg = $call_args[0] ?? null;
        $is_tracked = $first_arg
            && !$first_arg->unpack
            && $first_arg->value instanceof Variable
            && is_string($first_arg->value->name)
            && !self::isReferenced('$' . $first_arg->value->name, $event->getContext(), $statements_source);

        return ArrayFirstLastReturnTypeProvider::getElementType(
            $statements_source,
            $call_args,
            $event->getFunctionId() === 'array_shift',
            $is_tracked,
        ) ?? Type::getMixed();
    }

    private static function isReferenced(
        string $var_id,
        Context $context,
        StatementsAnalyzer $statements_analyzer,
    ): bool {
        return isset($context->references_in_scope[$var_id])
            || in_array($var_id, $context->references_in_scope, true)
            || ($context->referenced_counts[$var_id] ?? 0) > 0
            || isset($context->references_to_external_scope[$var_id])
            || isset($context->references_possibly_from_confusing_scope[$var_id])
            || isset($context->referenced_globals[$var_id])
            || isset($context->byref_constraints[$var_id])
            || isset($statements_analyzer->byref_uses[$var_id])
            || ($context->vars_in_scope[$var_id] ?? null)?->by_ref === true;
    }
}
