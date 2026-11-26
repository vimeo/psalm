<?php

declare(strict_types=1);

namespace Psalm\Tests\Shards;

/**
 * Splits the cases of a data provider between test classes, so that its tests run on several CI cores
 * instead of keeping one busy long after the others are done.
 *
 * The cases stay in the class that holds them (FooTest, with the number of shards in its SHARDS constant); the
 * shard classes FooCases1Test to FooCases<SHARDS>Test run the cases at their own positions, and FooTest no longer
 * runs them. Cases keep their names, so a case is still selected with `--filter '@caseName'`.
 *
 * To add a shard, add its class and raise SHARDS: each case then moves to the shard its position now falls to, so
 * every case still runs once. Adding or removing a case does the same to the cases after it.
 *
 * @psalm-pure
 */
final class ProviderShard
{
    /**
     * The cases at positions $shard, $shard + $shards, $shard + 2 * $shards, ...
     *
     * @template TKey of array-key
     * @template TCase
     * @param iterable[_]<TKey, TCase> $cases
     * @param positive-int $shards
     * @return array<TKey, TCase>
     * @psalm-pure
     */
    public static function of(iterable $cases, int $shard, int $shards): array
    {
        $result = [];
        $position = 0;
        foreach ($cases as $name => $case) {
            if ($position % $shards === $shard) {
                $result[$name] = $case;
            }
            $position++;
        }

        return $result;
    }
}
