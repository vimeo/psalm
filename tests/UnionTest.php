<?php

namespace Psalm\Tests;

use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

class UnionTest extends TestCase
{
    private const KNOWN_ATOMICS = [
        'int',
        'float',
        'string',
        'bool',
        'void',
        'array-key',
        'iterable',
        'never-return',
        'object',
        'callable',
        'pure-callable',
        'array',
        'non-empty-array',
        'callable-array',
        'list',
        'non-empty-list',
        'non-empty-string',
        'non-falsy-string',
        'lowercase-string',
        'non-empty-lowercase-string',
        'resource',
        'closed-resource',
        'positive-int',
        'numeric',
        'true',
        'false',
        'empty',
        'scalar',
        'null',
        'mixed',
        'callable-object',
        'class-string',
        'trait-string',
        'callable-string',
        'numeric-string',
        'html-escaped-string',
        'false-y',
        '$this',

    ];

    public function testUnionsAreCommutative(): void
    {
        foreach (self::KNOWN_ATOMICS as $a) {
            foreach (self::KNOWN_ATOMICS as $b) {
                $this->assertSame(
                    (new Union([
                        Atomic::create($a),
                        Atomic::create($b)
                    ]))->getId(),
                    (new Union([
                        Atomic::create($b),
                        Atomic::create($a)
                    ]))->getId(),
                    "$a|$b != $b|$a",
                );
            }
        }
    }

    public function testUnionsFromTypeCombinerAreCommutative(): void
    {
        foreach (self::KNOWN_ATOMICS as $a) {
            $a = Atomic::create($a);
            foreach (self::KNOWN_ATOMICS as $b) {
                $b = Atomic::create($b);
                $this->assertSame(
                    TypeCombiner::combine([$a, $b])->getId(),
                    TypeCombiner::combine([$b, $a])->getId(),
                    "$a|$b != $b|$a"
                );
            }
        }
    }
}
