<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class IntMaskComparisonTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    private const FLAG = '
        final class Flag {
            public const A = 1;
            public const B = 2;
            public const C = 4;
            public const AB = self::A | self::B;
            public const ALL = self::A | self::B | self::C;
        }
    ';

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'compareWithEmptySet' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isEmpty(int $m): bool {
                        return $m === 0;
                    }

                    /** @param int-mask-of<Flag::*> $m */
                    function isNotEmpty(int $m): bool {
                        return $m !== 0;
                    }

                    /** @param int-mask-of<Flag::*> $m */
                    function isLooselyNotEmpty(int $m): bool {
                        return $m != 0;
                    }

                    /** @param int-mask-of<Flag::*> $m */
                    function isPositive(int $m): bool {
                        return $m > 0;
                    }

                    /** @param int-mask-of<Flag::*> $m */
                    function isPositiveToo(int $m): bool {
                        return 0 < $m;
                    }',
            ],
            'compareWithFullSet' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isFull(int $m): bool {
                        return $m === Flag::ALL;
                    }

                    /** @param int-mask-of<Flag::*> $m */
                    function isNotFull(int $m): bool {
                        return 7 !== $m;
                    }',
            ],
            'testAllBitsOfConstant' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasAB(int $m): bool {
                        return ($m & Flag::AB) === Flag::AB;
                    }',
            ],
            'testAnyBitOfConstant' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasAOrB(int $m): bool {
                        return ($m & Flag::AB) !== 0;
                    }',
            ],
            'testSubsetOfConstant' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isWithinAB(int $m): bool {
                        return ($m & ~Flag::AB) === 0;
                    }',
            ],
            'testSubsetOfVariable' => [
                'code' => '<?php' . self::FLAG . '
                    /**
                     * @param int-mask-of<Flag::*> $available
                     * @param int-mask-of<Flag::*> $required
                     */
                    function allows(int $available, int $required): bool {
                        return ($available & $required) === $required;
                    }

                    /**
                     * @param int-mask-of<Flag::*> $available
                     * @param int-mask-of<Flag::*> $required
                     */
                    function allowsToo(int $available, int $required): bool {
                        return ($required & ~$available) === 0;
                    }',
            ],
            'narrowedMaskInVariable' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasA(int $m): bool {
                        $a = $m & Flag::A;

                        return $a === Flag::A;
                    }',
            ],
            'bitwiseOrKeepsMask' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isFullWithA(int $m): bool {
                        return ($m | Flag::A) === Flag::ALL;
                    }',
            ],
            'combineMasksInLoop' => [
                'code' => '<?php' . self::FLAG . '
                    /**
                     * @param list<int-mask-of<Flag::*>> $masks
                     * @return int-mask-of<Flag::*>
                     */
                    function union(array $masks): int {
                        $all = 0;

                        foreach ($masks as $mask) {
                            $all |= $mask;
                        }

                        return $all;
                    }',
            ],
            'plainIntIsNotAMask' => [
                'code' => '<?php' . self::FLAG . '
                    function check(int $flags): bool {
                        return $flags === Flag::A
                            || ($flags & Flag::AB) === Flag::B
                            || in_array($flags, [Flag::B, Flag::C], true)
                            || match ($flags) {
                                Flag::AB => true,
                                default => false,
                            };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchOnEmptyAndFullSets' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function describe(int $m): string {
                        return match ($m) {
                            0 => "none",
                            Flag::ALL => "all",
                            default => "some",
                        };
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'searchEmptyAndFullSets' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isEmptyOrFull(int $m): bool {
                        return in_array($m, [0, Flag::ALL], true);
                    }',
            ],
            'compareNullableMaskWithNull' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*>|null $m */
                    function isNull(?int $m): bool {
                        return $m === null;
                    }',
            ],
            'intMaskOfLiterals' => [
                'code' => '<?php
                    /** @param int-mask<1, 2, 4> $m */
                    function isEmptyOrFull(int $m): bool {
                        return $m === 0 || $m === 7 || ($m & 2) === 2;
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'identical' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasA(int $m): bool {
                        return $m === Flag::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'notIdentical' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function lacksAB(int $m): bool {
                        return Flag::AB !== $m;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'equal' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasA(int $m): bool {
                        return $m == Flag::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'notEqual' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function lacksA(int $m): bool {
                        return $m <> Flag::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'greaterOrEqual' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasMoreThanA(int $m): bool {
                        return $m >= Flag::B;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'smallerThanFullSet' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isNotFull(int $m): bool {
                        return $m < Flag::ALL;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'spaceship' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isA(int $m): bool {
                        return ($m <=> Flag::A) === 0;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'twoMasks' => [
                'code' => '<?php' . self::FLAG . '
                    /**
                     * @param int-mask-of<Flag::*> $a
                     * @param int-mask-of<Flag::*> $b
                     */
                    function same(int $a, int $b): bool {
                        return $a !== $b;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'maskAndPlainInt' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function same(int $m, int $n): bool {
                        return $m === $n;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'narrowedMaskWithOtherConstant' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasAButNotB(int $m): bool {
                        return ($m & Flag::AB) === Flag::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'narrowedMaskInVariableWithOtherConstant' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function hasBButNotA(int $m): bool {
                        $ab = $m & Flag::AB;

                        return $ab === Flag::B;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'narrowedMaskWithOtherVariable' => [
                'code' => '<?php' . self::FLAG . '
                    /**
                     * @param int-mask-of<Flag::*> $a
                     * @param int-mask-of<Flag::*> $b
                     * @param int-mask-of<Flag::*> $c
                     */
                    function check(int $a, int $b, int $c): bool {
                        return ($a & $b) === $c;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'inArray' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isAOrB(int $m): bool {
                        return in_array($m, [Flag::A, Flag::B], true);
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'inArrayOfMasks' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param list<int-mask-of<Flag::*>> $masks */
                    function hasA(array $masks): bool {
                        return in_array(Flag::A, $masks, true);
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'arraySearch' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isAOrB(int $m): bool {
                        return array_search($m, [Flag::A, Flag::B], true) !== false;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'arrayKeysWithSearchValue' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function isAOrB(int $m): bool {
                        return array_keys([Flag::A, Flag::B], $m) !== [];
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'matchArm' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function describe(int $m): string {
                        return match ($m) {
                            Flag::A => "a",
                            0 => "none",
                            default => "other",
                        };
                    }',
                'error_message' => 'IntMaskComparison',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'matchArmWithSeveralConditions' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function describe(int $m): string {
                        return match ($m) {
                            Flag::A, Flag::B => "a or b",
                            default => "other",
                        };
                    }',
                'error_message' => 'IntMaskComparison',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'switchCase' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask-of<Flag::*> $m */
                    function describe(int $m): string {
                        switch ($m) {
                            case Flag::A:
                                return "a";
                            default:
                                return "other";
                        }
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'intMaskOfLiterals' => [
                'code' => '<?php
                    /** @param int-mask<1, 2, 4> $m */
                    function isTwo(int $m): bool {
                        return $m === 2;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'nullableIntMaskOfLiterals' => [
                'code' => '<?php
                    /** @param int-mask<1, 2, 4>|null $m */
                    function isTwo(?int $m): bool {
                        return $m === 2;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'intMaskOfClassConstants' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask<Flag::A, Flag::B, Flag::C> $m */
                    function isB(int $m): bool {
                        return $m === Flag::B;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'importedTypeAlias' => [
                'code' => '<?php
                    /** @psalm-type FlagSet = int-mask<Flag::A, Flag::B> */
                    final class Flag {
                        public const A = 1;
                        public const B = 2;
                    }

                    /** @psalm-import-type FlagSet from Flag */
                    final class User {
                        /** @param FlagSet $m */
                        public static function isA(int $m): bool {
                            return $m === Flag::A;
                        }
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'byReferenceParameter' => [
                'code' => '<?php' . self::FLAG . '
                    /** @param int-mask<Flag::A, Flag::B> $m */
                    function takesFlags(int &$m): void {}

                    function isA(): bool {
                        $m = 0;
                        takesFlags($m);

                        return $m === Flag::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
            'property' => [
                'code' => '<?php' . self::FLAG . '
                    final class Holder {
                        /** @param int-mask-of<Flag::*> $flags */
                        public function __construct(private readonly int $flags) {}

                        public function isA(): bool {
                            return $this->flags === Flag::A;
                        }
                    }',
                'error_message' => 'IntMaskComparison',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'masksTooLargeToCombinePairwise' => [
                'code' => '<?php
                    final class Bit {
                        public const A = 1;
                        public const B = 2;
                        public const C = 4;
                        public const D = 8;
                        public const E = 16;
                    }

                    /**
                     * @param int-mask-of<Bit::*> $a
                     * @param int-mask-of<Bit::*> $b
                     */
                    function check(int $a, int $b): bool {
                        $c = $a & $b;

                        return $c === Bit::A;
                    }',
                'error_message' => 'IntMaskComparison',
            ],
        ];
    }
}
