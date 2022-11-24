<?php

namespace Psalm\Internal;

use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Storage\Assertion;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Tests\TypeReconciliation\ConditionalTest;
use UnexpectedValueException;

use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_values;
use function assert;
use function count;
use function in_array;
use function mt_rand;
use function reset;

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

    /**
     * @psalm-pure
     */
    public static function combineOrredClauses(
        ?ClauseConjunction $left_clauses,
        ?ClauseConjunction $right_clauses,
        int $conditional_object_id
    ): ?ClauseConjunction {
        if (!$left_clauses && !$right_clauses) {
            return new ClauseConjunction(
                [new Clause([], $conditional_object_id, $conditional_object_id, true)]
            );
        }
        if (!$left_clauses) {
            return $right_clauses;
        }
        if (!$right_clauses) {
            return $left_clauses;
        }
        return $left_clauses->combineOrredClauses($right_clauses, $conditional_object_id);
    }
}
