<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class RoundTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'roundWithStrictReturnType' => [
            '<?php
                /**
                 * @param float $a
                 * @return int
                 */
                function f(float $a): int
                {
                    return round($a);
                }

                /**
                 * @param float $a
                 * @return positive-int
                 */
                function g(float $a): int
                {
                    return round($a, -1);
                }

                /**
                 * @param float $a
                 * @return float
                 */
                function h(float $a): int
                {
                    return round($a, 2);
                }
            ',
        ];
    }
}
