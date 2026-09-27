<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Interner;
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
     * @param int $pattern interned constant name, or a constant name pattern containing `*`
     * @return array<int, ClassConstantStorage> constant name id => storage
     * @psalm-mutation-free
     */
    public function resolveConstants(
        ClassLikeStorage $class_like_storage,
        int $pattern,
    ): array {
        $constants = $class_like_storage->constants;

        if (isset($constants[$pattern])) {
            return [$pattern => $constants[$pattern]];
        }

        $pattern_str = Interner::str($pattern);

        if (!str_contains($pattern_str, '*')) {
            return [];
        } elseif ($pattern_str === '*') {
            return $constants;
        }

        $regex_pattern = sprintf('#^%s$#', str_replace('*', '.*?', $pattern_str));
        $matched_constants = [];

        foreach ($constants as $constant => $class_constant_storage) {
            if (preg_match($regex_pattern, Interner::str($constant)) === 0) {
                continue;
            }

            $matched_constants[$constant] = $class_constant_storage;
        }

        return $matched_constants;
    }

    /**
     * @param int $pattern interned enum case name, or a case name pattern containing `*`
     * @return array<int, EnumCaseStorage> case name id => storage
     * @psalm-mutation-free
     */
    public function resolveEnums(
        ClassLikeStorage $class_like_storage,
        int $pattern,
    ): array {
        $enum_cases = $class_like_storage->enum_cases;

        if (isset($enum_cases[$pattern])) {
            return [$pattern => $enum_cases[$pattern]];
        }

        $pattern_str = Interner::str($pattern);

        if (!str_contains($pattern_str, '*')) {
            return [];
        } elseif ($pattern_str === '*') {
            return $enum_cases;
        }

        $regex_pattern = sprintf('#^%s$#', str_replace('*', '.*?', $pattern_str));
        $matched_enums = [];
        foreach ($enum_cases as $enum_case_name => $enum_case_storage) {
            if (preg_match($regex_pattern, Interner::str($enum_case_name)) === 0) {
                continue;
            }

            $matched_enums[$enum_case_name] = $enum_case_storage;
        }

        return $matched_enums;
    }
}
