<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Interner;

use function dirname;

/**
 * @internal
 * @psalm-external-mutation-free
 */
final class ImpureFunctionsList
{
    /** @var null|array<string, true> */
    private static ?array $impure_functions_list = null;

    /** @var array<int, bool> function id => impure */
    private static array $cache = [];

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
    public static function isImpure(int $function_id): bool
    {
        if (isset(self::$cache[$function_id])) {
            return self::$cache[$function_id];
        }

        self::load();

        return self::$cache[$function_id]
            = isset(self::$impure_functions_list[Interner::str(Interner::lower($function_id))]);
    }
}
