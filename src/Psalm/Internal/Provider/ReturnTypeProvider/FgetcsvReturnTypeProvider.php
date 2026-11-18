<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * @internal
 */
final class FgetcsvReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['fgetcsv'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        if (!$event->getCallArgs()) {
            return null;
        }

        $config = $event->getStatementsSource()->getCodebase()->config;

        return new Union([
            Type::getNonEmptyListAtomic(
                new Union([new TString, new TNull], ['ignore_nullable_issues' => true]),
            ),
            new TFalse,
            new TNull,
        ], [
            'ignore_nullable_issues' => $config->ignore_internal_nullable_issues,
            'ignore_falsable_issues' => $config->ignore_internal_falsable_issues,
        ]);
    }
}
