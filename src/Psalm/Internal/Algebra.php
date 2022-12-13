<?php

namespace Psalm\Internal;

use Psalm\Storage\Assertion;

use function assert;
use function count;

/**
 * @internal
 */
class Algebra
{
    /**
     * @param array<string, non-empty-list<non-empty-list<Assertion>>>  $all_types
     *
     * @return array<string, non-empty-list<non-empty-list<Assertion>>>
     *
     * @psalm-pure
     */
    public static function negateTypes(array $all_types): array
    {
        $negated_types = [];

        foreach ($all_types as $key => $anded_types) {
            if (count($anded_types) > 1) {
                $new_anded_types = [];

                foreach ($anded_types as $orred_types) {
                    if (count($orred_types) === 1) {
                        $new_anded_types[] = $orred_types[0]->getNegation();
                    } else {
                        continue 2;
                    }
                }

                assert($new_anded_types !== []);

                $negated_types[$key] = [$new_anded_types];
                continue;
            }

            $new_orred_types = [];

            foreach ($anded_types[0] as $orred_type) {
                $new_orred_types[] = [$orred_type->getNegation()];
            }

            $negated_types[$key] = $new_orred_types;
        }

        return $negated_types;
    }
}
