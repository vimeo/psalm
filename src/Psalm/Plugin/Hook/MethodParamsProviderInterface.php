<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

interface MethodParamsProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * @return ?array<\Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(
        StatementsSource $statements_srouce,
        string $fq_classlike_name,
        string $method_name,
        Context $context,
        CodeLocation $code_location
    );
}
