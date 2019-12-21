<?php
namespace Psalm\Plugin\Hook;

use Psalm\StatementsSource;

interface FunctionExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array;

    /**
     * @return ?bool
     */
    public static function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id
    );
}
