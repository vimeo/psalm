<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PowReturnTypeProviderTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
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
                '$a===' => 'int',
                '$b===' => 'float',
                '$c===' => 'float',
                '$d===' => 'float(INF)',
                '$e===' => '0',
                '$f===' => '1',
            ],
        ];
    }
}
