<?php
namespace Psalm\Tests;

class Php55Test extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'generator' => [
                '<?php
                    /**
                     * @param  int  $start
                     * @param  int  $limit
                     * @param  int  $step
                     * @return Generator<int>
                     */
                    function xrange($start, $limit, $step = 1) {
                        for ($i = $start; $i <= $limit; $i += $step) {
                            yield $i;
                        }
                    }
            
                    $a = null;
            
                    /*
                     * Note that an array is never created or returned,
                     * which saves memory.
                     */
                    foreach (xrange(1, 9, 2) as $number) {
                        $a = $number;
                    }',
                'assertions' => [
                    ['null|int' => '$a']
                ]
            ],
            'finally' => [
                '<?php
                    try {
                    }
                    catch (\Exception $e) {
                    }
                    finally {
                    }'
            ],
            'foreachList' => [
                '<?php
                    $array = [
                        [1, 2],
                        [3, 4],
                    ];
            
                    foreach ($array as list($a, $b)) {
                        echo "A: $a; B: $b\n";
                    }'
            ],
            'arrayStringDereferencing' => [
                '<?php
                    $a = [1, 2, 3][0];
                    $b = "PHP"[0];',
                'assertions' => [
                    ['int' => '$a'],
                    ['string' => '$b']
                ]
            ],
            'classString' => [
                '<?php
                    class ClassName {}
            
                    $a = ClassName::class;',
                'assertions' => [
                    ['string' => '$a']
                ]
            ]
        ];
    }
}
