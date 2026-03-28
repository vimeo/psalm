<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php55Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'finally' => [
                'code' => '<?php
                    try {
                    }
                    catch (\Exception $e) {
                    }
                    finally {
                    }',
            ],
            'foreachList' => [
                'code' => '<?php
                    $array = [
                        [1, 2],
                        [3, 4],
                    ];

                    foreach ($array as list($a, $b)) {
                        echo "A: $a; B: $b\n";
                    }',
            ],
            'arrayStringDereferencing' => [
                'code' => '<?php
                    $a = [1, 2, 3][0];
                    $b = "PHP"[0];',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'classString' => [
                'code' => '<?php
                    class ClassName {}

                    $a = ClassName::class;',
                'assertions' => [
                    '$a' => 'class-string',
                ],
            ],
        ];
    }
}
