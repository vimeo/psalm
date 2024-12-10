<?php

declare(strict_types=1);

namespace Psalm\Tests\ReturnTypeProvider;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MinMaxReturnTypeProviderTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        yield 'literalInt' => [
            'code' => '<?php
                $min = min(1, 2);
                $max = max(3, 4);
            ',
            'assertions' => [
                '$min' => 'int',
                '$max' => 'int',
            ],
        ];
        yield 'nonInt' => [
            'code' => '<?php
                $min = min("a", "b");
                $max = max("x", "y");
            ',
            'assertions' => [
                '$min' => 'string',
                '$max' => 'string',
            ],
        ];
        yield 'maxIntRange' => [
            'code' => '<?php
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
            'assertions' => [
                '$min' => 'int<0, max>',
                '$max' => 'int<0, max>',
            ],
        ];
    }
}
