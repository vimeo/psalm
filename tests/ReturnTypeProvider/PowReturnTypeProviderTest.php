<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class PowReturnTypeProviderTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'test' => [
            'code' => '<?php
                function getInt(): int {
                    return 1;
                }
                function getFloat(): float {
                    return 1.0;
                }
                $int = getInt();
                $float = getFloat();

                $a = pow($int, $int);
                $b = pow($int, $float);
                $c = pow($float, $int);
                $d = pow(1000, 1000);
                $e = pow(0, 1000);
                $f = pow(1000, 0);
                $g = pow(2, 3);
                $h = pow(2, -1);
                $i = pow(2, 3.0);
            ',
            'assertions' => [
                '$a===' => 'float|int',
                '$b===' => 'float',
                '$c===' => 'float',
                '$d===' => 'float(INF)',
                '$e===' => '0',
                '$f===' => '1',
                '$g===' => '8',
                '$h===' => 'float(0.5)',
                '$i===' => 'float(8)',
            ],
        ];
        yield 'powOfAUnionOfIntLiterals' => [
            'code' => '<?php
                $bits = rand(0, 1) ? 8 : 11;

                $a = pow(rand(0, 1) ? 2 : 3, 2);
                $b = pow(2, $bits - 1) - 1;
                $c = pow(2, rand(0, 1) ? -1 : 2);
            ',
            'assertions' => [
                '$a===' => '4|9',
                '$b===' => '127|1023',
                '$c===' => '4|float(0.5)',
            ],
        ];
        yield 'intPowerAgreesWithThePowOperator' => [
            'code' => '<?php
                function getInt(): int {
                    return 1;
                }
                /** @return positive-int */
                function getPositiveInt(): int {
                    return 1;
                }
                /** @return non-negative-int */
                function getNonNegativeInt(): int {
                    return 0;
                }
                /** @return negative-int */
                function getNegativeInt(): int {
                    return -1;
                }
                /** @return int<-1, 1> */
                function getMixedSignInt(): int {
                    return 0;
                }
                $int = getInt();
                $positive = getPositiveInt();

                $a = pow($int, $int);
                $b = $int ** $int;
                $c = pow($int, -1);
                $d = $int ** -1;
                $e = pow($int, $positive);
                $f = pow($int, getNonNegativeInt());
                $g = pow($int, getNegativeInt());
                $h = pow($int, getMixedSignInt());
            ',
            'assertions' => [
                '$a===' => 'float|int',
                '$b===' => 'float|int',
                '$c===' => 'float',
                '$d===' => 'float',
                '$e===' => 'int',
                '$f===' => 'int',
                '$g===' => 'float',
                '$h===' => 'float|int',
            ],
        ];
        yield 'zeroToANegativePowerIsAFloat' => [
            'code' => '<?php
                $a = pow(0, -1);
            ',
            'assertions' => [
                '$a===' => 'float',
            ],
            'ignored_issues' => ['DeprecatedSyntax'],
        ];
        yield 'zeroOperandWithAFloatIsAFloat' => [
            'code' => '<?php
                function getFloat(): float {
                    return 1.0;
                }
                $float = getFloat();

                $a = pow(2.5, 0);
                $b = pow(0, 2.5);
                $c = pow(0, $float);
                $d = pow($float, 0);
                $e = 2.5 ** 0;
                $f = 0 ** 2.5;
            ',
            'assertions' => [
                '$a===' => 'float(1)',
                '$b===' => 'float(0)',
                '$c===' => 'float',
                '$d===' => 'float(1)',
                '$e===' => 'float(1)',
                '$f===' => 'float(0)',
            ],
        ];
        yield 'zeroToAZeroOrUnknownNonNegativePower' => [
            'code' => '<?php
                /** @return non-negative-int */
                function getNonNegativeInt(): int { return 0; }

                $a = pow(0, 0);
                $b = pow(0, getNonNegativeInt());
                $c = pow(0, 3);
                $d = 0 ** 0;
            ',
            'assertions' => [
                '$a===' => '1',
                '$b===' => 'int',
                '$c===' => '0',
                '$d===' => '1',
            ],
        ];
    }
}
