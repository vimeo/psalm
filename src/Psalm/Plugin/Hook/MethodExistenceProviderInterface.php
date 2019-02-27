<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
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
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        Context $context,
        CodeLocation $code_location
    );
}
