<?php
namespace Psalm\Plugin\Hook;

use Psalm\CodeLocation;
use Psalm\StatementsSource;

interface MethodExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * @return ?bool
     */
    public static function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lowercase,
        StatementsSource $source = null,
        CodeLocation $code_location = null
    );
}
