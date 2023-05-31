<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Union;

use function array_fill;
use function count;
use function sprintf;

/**
 * @internal
 */
class SprintfReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'sprintf',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $node_type_provider = $statements_source->getNodeTypeProvider();

        $call_args = $event->getCallArgs();
        foreach ($call_args as $index => $call_arg) {
            $type = $node_type_provider->getType($call_arg->value);
            if ($type === null) {
                continue;
            }

            if ($index === 0 && $type->isSingleStringLiteral()) {
                // use empty string dummies to check if the format itself produces a non-empty return value
                // faster than validating the pattern and checking all args separately
                $dummy = array_fill(0, count($call_args) - 1, '');
                if (sprintf($type->getSingleStringLiteral()->value, ...$dummy) !== '') {
                    return Type::getNonEmptyString();
                }
            }

            if ($index === 0) {
                continue;
            }

            // if the function has more arguments than the pattern has placeholders, this could be a false positive
            // if the param is not used in the pattern
            // however we would need to analyze the format arg to check that
            // can be done eventually to also implement https://github.com/vimeo/psalm/issues/9818
            // and https://github.com/vimeo/psalm/issues/9817
            if ($type->isNonEmptyString() || $type->isInt() || $type->isFloat()) {
                return Type::getNonEmptyString();
            }

            // check for unions of either
            $atomic_types = $type->getAtomicTypes();
            if ($atomic_types === []) {
                continue;
            }

            foreach ($atomic_types as $atomic_type) {
                if ($atomic_type instanceof TNonEmptyString
                    || $atomic_type instanceof TClassString
                    || ($atomic_type instanceof TLiteralString && $atomic_type->value !== '')
                    || $atomic_type instanceof TInt
                    || $atomic_type instanceof TFloat
                    || $atomic_type instanceof TNumeric) {
                    // valid non-empty types, potentially there are more though
                    continue;
                }

                // empty or generic string
                // or other unhandled type
                continue 2;
            }

            return Type::getNonEmptyString();
        }

        return Type::getString();
    }
}
