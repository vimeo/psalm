<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Union;

use function array_values;

/**
 * @internal
 */
final class GetParentClassReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['get_parent_class'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        // with an argument, the result depends on which child class the object or class-string really is
        if ($event->getCallArgs()) {
            return null;
        }

        $context = $event->getContext();
        $codebase = $event->getStatementsSource()->getCodebase();

        if ($context->self === null || !$codebase->classExists($context->self, null, $context)) {
            return null;
        }

        $parent_classes = $codebase->classlike_storage_provider->get($context->self)->parent_classes;
        if (!$parent_classes) {
            return null;
        }

        return new Union([new TClassString(array_values($parent_classes)[0])]);
    }
}
