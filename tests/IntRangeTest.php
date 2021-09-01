<?php
namespace Psalm\Tests;

class IntRangeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'intRangeContained' => [
                '<?php
                    /**
                     * @param int<1,12> $a
                     * @return int<-1, max>
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'positiveIntRange' => [
                '<?php
                    /**
                     * @param int<1,12> $a
                     * @return positive-int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'intRangeToInt' => [
                '<?php
                    /**
                     * @param int<1,12> $a
                     * @return int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'intReduced' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = getInt();
                    assert($a >= 500);
                    assert($a < 5000);
                    assert($b >= -5000);
                    assert($b < -501);
                    assert(-60 > $c);
                    assert(-500 < $c);',
                'assertions' => [
                    '$a===' => 'int<500, 4999>',
                    '$b===' => 'int<-5000, -502>',
                    '$c===' => 'int<-499, -61>',
                ]
            ],
            'intOperations' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    assert($a >= 500);
                    assert($a < 5000);
                    $b = $a % 10;
                    $c = $a ** 2;
                    $d = $a - 5;
                    $e = $a * 1;',
                'assertions' => [
                    '$b===' => 'int<0, 9>',
                    '$c===' => 'int<250000, 24990001>',
                    '$d===' => 'int<495, 4994>',
                    '$e===' => 'int<500, 4999>'
                ]
            ],
            'SKIPPED-intLoopPositive' => [
                '<?php
                    //skipped, int range in loops not supported yet
                    for($i = 0; $i < 10; $i++){

                    }',
                'assertions' => [
                    '$i===' => 'int<0, 9>'
                ]
            ],
            'SKIPPED-intLoopNegative' => [
                '<?php
                    //skipped, int range in loops not supported yet
                    for($i = 10; $i > 1; $i--){

                    }',
                'assertions' => [
                    '$i===' => 'int<2, 10>'
                ]
            ],
            'integrateExistingArrayPositive' => [
                '<?php
                    /** @return int<5, max> */
                    function getInt()
                    {
                        return 7;
                    }

                    $_arr = ["a", "b", "c"];
                    $a = getInt();
                    $_arr[$a] = 12;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<0, max>, "a"|"b"|"c"|12>'
                ]
            ],
            'integrateExistingArrayNegative' => [
                '<?php
                    /** @return int<min, -1> */
                    function getInt()
                    {
                        return -2;
                    }

                    $_arr = ["a", "b", "c"];
                    $a = getInt();
                    $_arr[$a] = 12;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<min, 2>, "a"|"b"|"c"|12>'
                ]
            ],
            'SKIPPED-statementsInLoopAffectsEverything' => [
                '<?php
                    //skipped, int range in loops not supported yet
                    $remainder = 1;
                    for ($i = 0; $i < 5; $i++) {
                        if ($remainder) {
                            $remainder--;
                        }
                    }',
                'assertions' => [
                    '$remainder===' => 'int<min, 1>'
                ]
            ],
            'SKIPPED-IntRangeRestrictWhenUntouched' => [
                '<?php
                    //skipped, int range in loops not supported yet
                    foreach ([1, 2, 3] as $i) {
                        if ($i > 1) {
                            takesInt($i);
                        }
                    }

                    /** @psalm-param int<2, 3> $i */
                    function takesInt(int $i): void{
                        return;
                    }',
            ],
            'SKIPPED-wrongLoopAssertion' => [
                '<?php
                    //skipped, int range in loops not supported yet
                    function a(): array {
                        $type_tokens = getArray();

                        for ($i = 0, $l = rand(0,100); $i < $l; ++$i) {

                            /** @psalm-trace $i */;
                            if ($i > 0 && rand(0,1)) {
                                continue;
                            }
                            /** @psalm-trace $i  */;


                            $type_tokens[$i] = "";

                            /** @psalm-trace $type_tokens */;

                            if($i > 1){
                                $type_tokens[$i - 2];
                            }
                        }

                        return [];
                    }


                    /** @return array<int, string> */
                    function getArray(): array{
                        return [];
                    }'
            ]
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'intRangeNotContained' => [
                '<?php
                    /**
                     * @param int<1,12> $a
                     * @return int<-1, 11>
                     * @psalm-suppress InvalidReturnStatement
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
        ];
    }
}
