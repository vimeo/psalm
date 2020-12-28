<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\PropertyExistenceProviderInterface;
use Psalm\Plugin\Hook\PropertyTypeProviderInterface;
use Psalm\Plugin\Hook\PropertyVisibilityProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;

class FooPropertyProvider implements
    PropertyExistenceProviderInterface,
    PropertyVisibilityProviderInterface,
    PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array
    {
        return ['Ns\Foo'];
    }

    public static function doesPropertyExist(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        return $property_name === 'magic_property';
    }

    public static function isPropertyVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        return true;
    }

    public static function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null
    ): ?Type\Union {
        return Type::getString();
    }
}
