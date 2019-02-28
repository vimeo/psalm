<?php

namespace Psalm\Test\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\{
    PropertyExistenceProviderInterface,
    PropertyVisibilityProviderInterface,
    PropertyTypeProviderInterface
};

class FooPropertyProvider
    implements PropertyExistenceProviderInterface, PropertyVisibilityProviderInterface, PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array
    {
        return ['Ns\Foo'];
    }

    /**
     * @return ?bool
     */
    public static function doesPropertyExist(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        return $property_name === 'magic_property';
    }

    /**
     * @return ?bool
     */
    public static function isPropertyVisible(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    ) {
        return true;
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getPropertyType(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context
    ) {
        return Type::getString();
    }
}
