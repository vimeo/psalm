<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\EnumCaseStorage;

use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;

/**
 * @internal
 * @psalm-immutable
 */
final class StorageByPatternResolver
{
    public const RESOLVE_CONSTANTS = 1;
    public const RESOLVE_ENUMS = 2;

    /**
     * @return array<string,ClassConstantStorage>
     * @psalm-mutation-free
     */
    public function resolveConstants(
        ClassLikeStorage $class_like_storage,
        string $pattern,
    ): array {
        return self::filterByPattern($class_like_storage->constants, $pattern);
    }

    /**
     * @return array<string,EnumCaseStorage>
     * @psalm-mutation-free
     */
    public function resolveEnums(
        ClassLikeStorage $class_like_storage,
        string $pattern,
    ): array {
        return self::filterByPattern($class_like_storage->enum_cases, $pattern);
    }

    /**
     * The items whose name matches the pattern, where `*` matches anything
     *
     * @template T
     * @param array<string, T> $items
     * @return array<string, T>
     * @psalm-pure
     */
    private static function filterByPattern(array $items, string $pattern): array
    {
        if (!str_contains($pattern, '*')) {
            if (isset($items[$pattern])) {
                return [$pattern => $items[$pattern]];
            }

            return [];
        } elseif ($pattern === '*') {
            return $items;
        }

        $regex_pattern = sprintf('#^%s$#', str_replace('*', '.*?', $pattern));
        $matched_items = [];

        foreach ($items as $name => $item) {
            if (preg_match($regex_pattern, $name) === 0) {
                continue;
            }

            $matched_items[$name] = $item;
        }

        return $matched_items;
    }
}
