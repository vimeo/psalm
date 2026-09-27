<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use function dirname;

/**
 * @internal
 * @psalm-external-mutation-free
 */
final class ImpureFunctionsList
{
    /**
     * Function name id => true, see dictionaries/ImpureFunctionsList.php
     *
     * @var null|array<int, true>
     */
    private static ?array $impure_functions_list = null;

    /**
     * @psalm-external-mutation-free
     * @psalm-suppress UnresolvableInclude
     */
    public static function isImpure(int $function_id): bool
    {
        if (self::$impure_functions_list === null) {
            /** @var array<int, true> $list */
            $list = require(dirname(__DIR__, 4) . '/dictionaries/ImpureFunctionsList.php');
            self::$impure_functions_list = $list;
        }

        return isset(self::$impure_functions_list[$function_id]);
    }
}
