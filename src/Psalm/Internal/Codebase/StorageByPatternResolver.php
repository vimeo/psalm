<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\EnumCaseStorage;

use function preg_match;
use function sprintf;
use function str_replace;
use function strpos;

/**
 * @internal
 */
final class StorageByPatternResolver
{
    public const RESOLVE_CONSTANTS = 1;
    public const RESOLVE_ENUMS = 2;

    /**
     * @return array<string,ClassConstantStorage>
     */
    public function resolveConstants(
        ClassLikeStorage $class_like_storage,
        string $pattern
    ): array {
        $constants = $class_like_storage->constants;

        if (strpos($pattern, '*') === false) {
            if (isset($constants[$pattern])) {
                return [$pattern => $constants[$pattern]];
            }

            return [];
        } elseif ($pattern === '*') {
            return $constants;
        }

        $regex_pattern = sprintf('#^%s$#', str_replace('*', '.*?', $pattern));
        $matched_constants = [];

        foreach ($constants as $constant => $class_constant_storage) {
            if (preg_match($regex_pattern, $constant) === 0) {
                continue;
            }

            $matched_constants[$constant] = $class_constant_storage;
        }

        return $matched_constants;
    }

    /**
     * @return array<string,EnumCaseStorage>
     */
    public function resolveEnums(
        ClassLikeStorage $class_like_storage,
        string $pattern
    ): array {
        $enum_cases = $class_like_storage->enum_cases;
        if (strpos($pattern, '*') === false) {
            if (isset($enum_cases[$pattern])) {
                return [$pattern => $enum_cases[$pattern]];
            }

            return [];
        } elseif ($pattern === '*') {
            return $enum_cases;
        }

        $regex_pattern = sprintf('#^%s$#', str_replace('*', '.*?', $pattern));
        $matched_enums = [];
        foreach ($enum_cases as $enum_case_name => $enum_case_storage) {
            if (preg_match($regex_pattern, $enum_case_name) === 0) {
                continue;
            }

            $matched_enums[$enum_case_name] = $enum_case_storage;
        }

        return $matched_enums;
    }
}
