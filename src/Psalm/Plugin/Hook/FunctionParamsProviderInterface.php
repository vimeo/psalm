<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

interface FunctionParamsProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array;

    /**
     * @return ?array<\Psalm\Storage\FunctionLikeParameter>
     */
    public static function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        Context $context,
        CodeLocation $code_location
    );
}
