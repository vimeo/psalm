<?php

namespace Psalm\Internal\Codebase;

use function dirname;
use function strtolower;

/**
 * @internal
 */
class PropertyMap
{
    /**
     * @var array<lowercase-string, array<string, string>>|null
     */
    private static ?array $property_map = null;

    /**
     * Gets the method/function call map
     *
     * @return array<lowercase-string, array<string, string>>
     */
    public static function getPropertyMap(): array
    {
        if (self::$property_map !== null) {
            return self::$property_map;
        }

        /** @var array<lowercase-string, array<string, string>> */
        $property_map = require(dirname(__DIR__, 4) . '/dictionaries/PropertyMap.php');

        self::$property_map = $property_map;

        return self::$property_map;
    }

    public static function inPropertyMap(string $class_name): bool
    {
        return isset(self::getPropertyMap()[strtolower($class_name)]);
    }
}
