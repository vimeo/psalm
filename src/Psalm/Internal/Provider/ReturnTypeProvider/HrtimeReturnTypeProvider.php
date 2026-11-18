<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

/**
 * @internal
 */
final class HrtimeReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['hrtime'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        // hrtime() was added in PHP 7.3
        if ($event->getStatementsSource()->getCodebase()->analysis_php_version_id < 7_03_00) {
            return null;
        }

        $keyed_array = TKeyedArray::make([
            Type::getInt(),
            Type::getInt(),
        ], null, null, true);

        $call_args = $event->getCallArgs();
        if (!$call_args) {
            return new Union([$keyed_array]);
        }

        $first_arg_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);
        if (!$first_arg_type || (string) $first_arg_type === 'true') {
            return Type::getInt(true);
        }

        if ((string) $first_arg_type === 'false') {
            return new Union([$keyed_array]);
        }

        return new Union([
            $keyed_array,
            new TInt(),
        ]);
    }
}
