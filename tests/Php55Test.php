<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php55Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'finally' => [
                '<?php
                    try {
                    }
                    catch (\Exception $e) {
                    }
                    finally {
                    }',
            ],
            'foreachList' => [
                '<?php
                    $array = [
                        [1, 2],
                        [3, 4],
                    ];

                    foreach ($array as list($a, $b)) {
                        echo "A: $a; B: $b\n";
                    }',
            ],
            'arrayStringDereferencing' => [
                '<?php
                    $a = [1, 2, 3][0];
                    $b = "PHP"[0];',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'classString' => [
                '<?php
                    class ClassName {}

                    $a = ClassName::class;',
                'assertions' => [
                    '$a' => 'class-string',
                ],
            ],
        ];
    }
}
