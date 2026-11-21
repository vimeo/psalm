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
            ',
            'assertions' => [
                '$a===' => 'float|int',
                '$b===' => 'float',
                '$c===' => 'float',
                '$d===' => 'float(INF)',
                '$e===' => '0',
                '$f===' => '1',
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
    }
}
