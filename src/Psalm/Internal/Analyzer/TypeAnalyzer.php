<?php

namespace Psalm\Internal\Analyzer;

use Psalm\Type;
use Psalm\Type\Union;

use function array_keys;
use function array_unique;

/**
 * @internal
 */
final class TypeAnalyzer
{
    /**
     * Takes two arrays of types and merges them
     *
     * @param  array<string, Union>  $new_types
     * @param  array<string, Union>  $existing_types
     * @return array<string, Union>
     */
    public static function combineKeyedTypes(array $new_types, array $existing_types): array
    {
        $keys = [...array_keys($new_types), ...array_keys($existing_types)];
        $keys = array_unique($keys);

        $result_types = [];

        if (empty($new_types)) {
            return $existing_types;
        }

        if (empty($existing_types)) {
            return $new_types;
        }

        foreach ($keys as $key) {
            if (!isset($existing_types[$key])) {
                $result_types[$key] = $new_types[$key];
                continue;
            }

            if (!isset($new_types[$key])) {
                $result_types[$key] = $existing_types[$key];
                continue;
            }

            $existing_var_types = $existing_types[$key];
            $new_var_types = $new_types[$key];

            if ($new_var_types->getId() === $existing_var_types->getId()) {
                $result_types[$key] = $new_var_types;
            } else {
                $result_types[$key] = Type::combineUnionTypes($new_var_types, $existing_var_types);
            }
        }

        return $result_types;
    }
}
