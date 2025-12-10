<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type\Atomic\TIntMaskVerifier;

final class TIntMaskVerifierTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testIsValidValueWithZero(): void
    {
        $verifier = new TIntMaskVerifier([1, 2, 4]);
        $this->assertTrue($verifier->isValidValue(0));
    }

    public function testIsValidValueWithValidCombinations(): void
    {
        $verifier = new TIntMaskVerifier([1, 2, 4]);
        
        // Test individual bits
        $this->assertTrue($verifier->isValidValue(1));
        $this->assertTrue($verifier->isValidValue(2));
        $this->assertTrue($verifier->isValidValue(4));
        
        // Test combinations
        $this->assertTrue($verifier->isValidValue(3)); // 1 | 2
        $this->assertTrue($verifier->isValidValue(5)); // 1 | 4
        $this->assertTrue($verifier->isValidValue(6)); // 2 | 4
        $this->assertTrue($verifier->isValidValue(7)); // 1 | 2 | 4
    }

    public function testIsValidValueWithInvalidValue(): void
    {
        $verifier = new TIntMaskVerifier([1, 2, 4]);
        
        // Test invalid values
        $this->assertFalse($verifier->isValidValue(8));  // Not in mask
        $this->assertFalse($verifier->isValidValue(9));  // 1 | 8, but 8 not in mask
        $this->assertFalse($verifier->isValidValue(16)); // Not in mask
    }

    public function testIsSupersetOf(): void
    {
        $superset = new TIntMaskVerifier([1, 2, 4, 8]);
        $subset = new TIntMaskVerifier([1, 2]);
        
        $this->assertTrue($superset->isSupersetOf($subset));
        $this->assertFalse($subset->isSupersetOf($superset));
    }

    public function testIsSupersetOfSameSet(): void
    {
        $verifier1 = new TIntMaskVerifier([1, 2, 4]);
        $verifier2 = new TIntMaskVerifier([1, 2, 4]);
        
        $this->assertTrue($verifier1->isSupersetOf($verifier2));
        $this->assertTrue($verifier2->isSupersetOf($verifier1));
    }

    public function testGetPossibleInts(): void
    {
        $verifier = new TIntMaskVerifier([1, 2]);
        $possibleInts = $verifier->getPossibleInts();
        
        $expected = [0, 1, 2, 3]; // All combinations of 1|2
        $this->assertSame($expected, $possibleInts);
    }

    public function testGetPossibleIntsWithThreeBits(): void
    {
        $verifier = new TIntMaskVerifier([1, 2, 4]);
        $possibleInts = $verifier->getPossibleInts();
        
        $expected = [0, 1, 2, 3, 4, 5, 6, 7]; // All combinations
        $this->assertSame($expected, $possibleInts);
    }

    public function testMaskCalculation(): void
    {
        $verifier = new TIntMaskVerifier([1, 2, 4]);
        $this->assertSame(7, $verifier->mask); // 1 | 2 | 4 = 7
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'intMaskBasicUsage' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     * @return int
                     */
                    function processFlags(int $flags): int {
                        return $flags;
                    }

                    processFlags(0);
                    processFlags(1);
                    processFlags(3); // 1 | 2
                    processFlags(7); // 1 | 2 | 4',
            ],
            'intMaskCombination' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2> $flags1
                     * @param int-mask<4, 8> $flags2
                     * @return int-mask<1, 2, 4, 8>
                     */
                    function combineFlags(int $flags1, int $flags2): int {
                        return $flags1 | $flags2;
                    }',
            ],
            'intMaskIntersection' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags1
                     * @param int-mask<2, 4, 8> $flags2
                     * @return int-mask<2, 4>
                     */
                    function getCommonFlags(int $flags1, int $flags2): int {
                        return $flags1 & $flags2;
                    }',
            ],
            'intMaskZeroValue' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     */
                    function isNoFlags(int $flags): bool {
                        return $flags === 0;
                    }

                    isNoFlags(0);',
            ],
            'intMaskWithClassConstants' => [
                'code' => '<?php
                    class Permissions {
                        const int READ = 1;
                        const int WRITE = 2;
                        const int EXECUTE = 4;
                        const int DELETE = 8;

                        /**
                         * @param int-mask-of<self::*> $perms
                         */
                        public static function check(int $perms): void {
                            if ($perms & self::READ) {
                                echo "Can read";
                            }
                        }
                    }

                    Permissions::check(Permissions::READ | Permissions::WRITE);',
            ],
            'intMaskReturnsSpecificType' => [
                'code' => '<?php
                    /**
                     * @return int-mask<1, 2, 4>
                     */
                    function getFlags(): int {
                        return 1 | 2;
                    }

                    $flags = getFlags();',
                'assertions' => [
                    '$flags===' => 'int-mask-verifier<0,1,2,4>',
                ],
            ],
            'intMaskBitwiseAND' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4, 8> $flags1
                     * @param int-mask<2, 4, 8, 16> $flags2
                     * @return int-mask<2, 4, 8>
                     */
                    function andFlags(int $flags1, int $flags2): int {
                        return $flags1 & $flags2;
                    }

                    $result = andFlags(7, 6);',
                'assertions' => [
                    '$result===' => 'int-mask-verifier<0,2,4,8>',
                ],
            ],
            'intMaskBitwiseOR' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2> $flags1
                     * @param int-mask<4, 8> $flags2
                     * @return int-mask<1, 2, 4, 8>
                     */
                    function orFlags(int $flags1, int $flags2): int {
                        return $flags1 | $flags2;
                    }

                    $result = orFlags(3, 12); // (1|2) | (4|8) = 15',
                'assertions' => [
                    '$result===' => 'int-mask-verifier<0,1,2,4,8>',
                ],
            ],
            'intMaskBitwiseXOR' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4, 8> $flags1
                     * @param int-mask<2, 4, 8, 16> $flags2
                     * @return int-mask<1, 2, 4, 8, 16>
                     */
                    function xorFlags(int $flags1, int $flags2): int {
                        return $flags1 ^ $flags2;
                    }

                    $result = xorFlags(7, 6); // (1|2|4) ^ (2|4) = 1',
                'assertions' => [
                    '$result===' => 'int-mask-verifier<0,1,2,4,8,16>',
                ],
            ],
            'intMaskShiftLeft' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     * @return int-mask<2, 4, 8>
                     */
                    function shiftLeft(int $flags): int {
                        return $flags << 1;
                    }

                    $result = shiftLeft(3); // (1|2) << 1 = 6',
                'assertions' => [
                    '$result===' => 'int-mask-verifier<0,2,4,8>',
                ],
            ],
            'intMaskShiftRight' => [
                'code' => '<?php
                    /**
                     * @param int-mask<2, 4, 8> $flags
                     * @return int-mask<1, 2, 4>
                     * */
                    function shiftRight(int $flags): int {
                        return $flags >> 1;
                    }
                    $result = shiftRight(12); // (4|8) >> 1 = 6',
                'assertions' => [
                    '$result===' => 'int-mask-verifier<0,1,2,4>',
                ],
            ],
            'intMaskSameMaskBitwiseOperations' => [
                'code' => '<?php
                    /** @var int-mask<1, 2, 4> $flags1 */
                    $flags1 = 0;
                    /** @var int-mask<1, 2, 4> $flags2 */
                    $flags2 = 0;
                    /** @var int-mask<2, 4, 8> $flags3 */
                    $flags3 = 0;
                    
                    $and = $flags1 & $flags2;  // int-mask<1, 2, 4>
                    $or = $flags1 | $flags2;   // int-mask<1, 2, 4>
                    $xor = $flags1 ^ $flags2;  // int-mask<1, 2, 4>
                    $shiftLeft = $flags1 << 1; // int-mask<2, 4, 8>
                    $shiftRight = $flags3 >> 1; // int-mask<1, 2, 4>',
                'assertions' => [
                    '$and===' => 'int-mask-verifier<0,1,2,4>',
                    '$or===' => 'int-mask-verifier<0,1,2,4>',
                    '$xor===' => 'int-mask-verifier<0,1,2,4>',
                    '$shiftLeft===' => 'int-mask-verifier<0,2,4,8>',
                    '$shiftRight===' => 'int-mask-verifier<0,1,2,4>',
                ],
            ],
            'intMaskComplexBitwiseExpressions' => [
                'code' => '<?php
                    /** @var int-mask<1, 2, 4> $maskA */
                    $maskA = 0;
                    /** @var int-mask<8, 16> $maskB */
                    $maskB = 0;
                    /** @var int-mask<4, 8, 32> $maskC */
                    $maskC = 0;

                    $orAnd = ($maskA | $maskB) & $maskC;
                    $xorOrAnd = ($maskA ^ $maskB) | ($maskC & $maskA);
                    $shiftLeftOr = ($maskA << 3) | $maskB;     // (int-mask<8, 16, 32>) | int-mask<8, 16>
                    $orShiftRight = ($maskA | $maskB) >> 2;     // int-mask<1, 2, 4, 8, 16> >> 2',
                'assertions' => [
                    '$orAnd===' => 'int-mask-verifier<0,4,8>',
                    '$xorOrAnd===' => 'int-mask-verifier<0,1,2,4,8,16>',
                    '$shiftLeftOr===' => 'int-mask-verifier<0,8,16,32>',
                    '$orShiftRight===' => 'int-mask-verifier<0,1,2,4>',
                ],
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'intMaskInvalidValue' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     */
                    function processFlags(int $flags): void {}

                    processFlags(8); // Invalid: 8 is not in mask',
                'error_message' => 'InvalidArgument',
            ],
            'intMaskInvalidCombination' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     */
                    function processFlags(int $flags): void {}

                    processFlags(9); // Invalid: 9 = 1|8, but 8 is not in mask',
                'error_message' => 'InvalidArgument',
            ],
            'intMaskNegativeValue' => [
                'code' => '<?php
                    /**
                     * @param int-mask<1, 2, 4> $flags
                     */
                    function processFlags(int $flags): void {}

                    processFlags(-1); // Invalid: negative values not allowed',
                'error_message' => 'InvalidArgument',
            ],
            'intMaskReturnTypeViolation' => [
                'code' => '<?php
                    /**
                     * @return int-mask<1, 2, 4>
                     */
                    function getFlags(): int {
                        return 16; // Invalid: 16 is not in mask
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
