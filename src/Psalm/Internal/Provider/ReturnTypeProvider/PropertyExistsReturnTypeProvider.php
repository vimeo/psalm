<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * property_exists() is true for a property the class of the object declares or inherits
 *
 * @internal
 */
final class PropertyExistsReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return ['property_exists'];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        $source = $event->getStatementsSource();

        if (!isset($call_args[0]) || !isset($call_args[1])) {
            return null;
        }

        $object_type = $source->getNodeTypeProvider()->getType($call_args[0]->value);
        $property_type = $source->getNodeTypeProvider()->getType($call_args[1]->value);

        if ($object_type === null
            || $property_type === null
            || !$object_type->isSingle()
            || !$property_type->isSingleStringLiteral()
        ) {
            return null;
        }

        $object_atomic = $object_type->getSingleAtomic();

        if (!$object_atomic instanceof TNamedObject || $object_atomic->extra_types) {
            return null;
        }

        $codebase = $source->getCodebase();

        if (!$codebase->classlikes->classExists($object_atomic->value)) {
            return null;
        }

        $storage = $codebase->classlike_storage_provider->get($object_atomic->value);

        if (!isset($storage->declaring_property_ids[$property_type->getSingleStringLiteral()->value])) {
            return null;
        }

        $true = Type::getTrue();
        /** @psalm-suppress InaccessibleProperty We just created this type */
        $true->from_docblock = $object_type->from_docblock;

        return $true;
    }
}
