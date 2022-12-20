<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\PropertyTypeProvider;

use Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent;
use Psalm\Plugin\EventHandler\PropertyTypeProviderInterface;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function strtolower;

/**
 * @internal
 */
class DomDocumentPropertyTypeProvider implements PropertyTypeProviderInterface
{
    private static ?Union $cache = null;
    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Union
    {
        if (strtolower($event->getPropertyName()) === 'documentelement') {
            self::$cache ??= new Union([new TNamedObject('DOMElement'), new TNull()], [
                'ignore_nullable_issues' => true,
            ]);

            return self::$cache;
        }

        return null;
    }

    public static function getClassLikeNames(): array
    {
        return ['domdocument'];
    }
}
