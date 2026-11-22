<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * @internal
 */
final class MbStrtolowerReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['mb_strtolower'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (!$call_args) {
            return null;
        }

        $node_type_provider = $event->getStatementsSource()->getNodeTypeProvider();
        $string_arg_type = $node_type_provider->getType($call_args[0]->value);
        $is_non_empty = $string_arg_type !== null && $string_arg_type->isNonEmptyString();

        if (isset($call_args[1])) {
            $encoding_arg_type = $node_type_provider->getType($call_args[1]->value);

            if (!$encoding_arg_type || !$encoding_arg_type->isNull()) {
                return $is_non_empty ? Type::getNonEmptyString() : Type::getString();
            }
        }

        if ($string_arg_type !== null && !$call_args[0]->unpack) {
            $literals = StrtolowerReturnTypeProvider::lowercaseLiterals($string_arg_type, true);

            if ($literals !== null) {
                return $literals;
            }
        }

        return $is_non_empty ? Type::getNonEmptyLowercaseString() : Type::getLowercaseString();
    }
}
