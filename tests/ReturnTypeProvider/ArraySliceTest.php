<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ArraySliceTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'arraySliceWithTemplatedArrayParameter' => [
            'code' => '<?php
                /**
                 * @template T as string[]
                 * @param T $a
                 * @return string[]
                 */
                function f(array $a): array
                {
                    return array_slice($a, 1);
                }
            ',
        ];
    }
}
