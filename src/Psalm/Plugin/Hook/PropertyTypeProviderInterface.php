<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

interface PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getPropertyType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        Context $context,
        CodeLocation $code_location
    );
}
