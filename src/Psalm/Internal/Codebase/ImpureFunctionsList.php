<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use function dirname;
use function strtolower;

/** @internal */
final class ImpureFunctionsList
{
    /** @var null|array<string, true> */
    private static ?array $impure_functions_list = null;

    /**
     * @psalm-assert !null self::$impure_functions_list
     * @psalm-external-mutation-free
     */
    private static function load(): void
    {
        if (self::$impure_functions_list !== null) {
            return;
        }

        /** @var array<string, true> */
        self::$impure_functions_list = require(dirname(__DIR__, 4) . '/dictionaries/ImpureFunctionsList.php');
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function isImpure(string $function_id): bool
    {
        self::load();

        return isset(self::$impure_functions_list[strtolower($function_id)]);
    }
}
