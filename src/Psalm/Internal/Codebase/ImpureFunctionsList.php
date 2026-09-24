<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Storage\Capabilities;

use function dirname;
use function strtolower;

/**
 * The capabilities of the builtin functions with side effects (dictionaries/ImpureFunctionsList.php).
 *
 * @internal
 * @psalm-external-mutation-free
 */
final class ImpureFunctionsList
{
    /** @var null|array<string, int> */
    private static ?array $capabilities = null;

    /**
     * @psalm-assert !null self::$capabilities
     * @psalm-external-mutation-free
     */
    private static function load(): void
    {
        if (self::$capabilities !== null) {
            return;
        }

        /** @var array<string, int> */
        self::$capabilities = require(dirname(__DIR__, 4) . '/dictionaries/ImpureFunctionsList.php');
    }

    /**
     * The capabilities a builtin function requires: none for a function that is not listed.
     *
     * @psalm-external-mutation-free
     */
    public static function getCapabilities(string $function_id): int
    {
        self::load();

        return self::$capabilities[strtolower($function_id)] ?? Capabilities::NONE;
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function isImpure(string $function_id): bool
    {
        return self::getCapabilities($function_id) !== Capabilities::NONE;
    }
}
