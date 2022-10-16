<?php

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MinMaxReturnTypeProviderTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'literalInt' => [
            '<?php
                $min = min(1, 2);
                $max = max(3, 4);
            ',
            [
                '$min' => 'int',
                '$max' => 'int',
            ],
        ];
        yield 'nonInt' => [
            '<?php
                $min = min("a", "b");
                $max = max("x", "y");
            ',
            [
                '$min' => 'string',
                '$max' => 'string',
            ],
        ];
        yield 'maxIntRange' => [
            '<?php
                $headers = fgetcsv(fopen("test.txt", "r"));
                $h0 = $h1 = null;
                foreach($headers as $i => $v) {
                    if ($v === "") $h0 = $i;
                    if ($v === "") $h1 = $i;
                }
                if ($h0 === null || $h1 === null) throw new \Exception();

                $min = min($h0, $h1);
                $max = max($h0, $h1);
            ',
            [
                '$min' => 'int<0, max>',
                '$max' => 'int<0, max>',
            ],
        ];
    }
}
