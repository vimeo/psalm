<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class IntRangeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

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
            'complexAssertions' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    assert($a >= 495 + 5);
                    $b = 5000;
                    assert($a < $b);
                    ',
                'assertions' => [
                    '$a===' => 'int<500, 4999>',
                ]
            ],
            'negatedAssertions' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = $d = $e = $f = $g = $h = $i = $j = $k = $l = $m = $n = $o = $p = getInt();
                    //>
                    if($a > 10){
                        die();
                    }

                    if($b > -10){
                        die();
                    }

                    //<
                    if($c < 500){
                        die();
                    }

                    if($d < -500){
                        die();
                    }

                    //>=
                    if($e >= 10){
                        die();
                    }

                    if($f >= -10){
                        die();
                    }

                    //<=
                    if($g <= 500){
                        die();
                    }

                    if($h <= -500){
                        die();
                    }

                    //>
                    if(10 > $i){
                        die();
                    }

                    if(-10 > $j){
                        die();
                    }

                    //<
                    if(500 < $k){
                        die();
                    }

                    if(-500 < $l){
                        die();
                    }

                    //>=
                    if(10 >= $m){
                        die();
                    }

                    if(-10 >= $n){
                        die();
                    }

                    //<=
                    if(500 <= $o){
                        die();
                    }

                    if(-500 <= $p){
                        die();
                    }
                    //inverse
                    ',
                'assertions' => [
                    '$a===' => 'int<min, 10>',
                    '$b===' => 'int<min, -10>',
                    '$c===' => 'int<500, max>',
                    '$d===' => 'int<-500, max>',
                    '$e===' => 'int<min, 9>',
                    '$f===' => 'int<min, -11>',
                    '$g===' => 'int<501, max>',
                    '$h===' => 'int<-499, max>',
                    '$i===' => 'int<10, max>',
                    '$j===' => 'int<-10, max>',
                    '$k===' => 'int<min, 500>',
                    '$l===' => 'int<min, -500>',
                    '$m===' => 'int<11, max>',
                    '$n===' => 'int<-9, max>',
                    '$o===' => 'int<min, 499>',
                    '$p===' => 'int<min, -501>',
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
                    '$c===' => 'int<1, max>',
                    '$d===' => 'int<495, 4994>',
                    '$e===' => 'int<500, 4999>'
                ]
            ],
            'mod' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = $d = getInt();
                    assert($a >= 20);//positive range
                    assert($b <= -20);//negative range
                    /** @var int<0, 0> $c */; // 0 range
                    assert($d >= -100);// mixed range
                    assert($d <= 100);// mixed range
                    /** @var int<5, 5> $e */; // 5 range

                    $f = $a % $e;
                    $g = $b % $e;
                    $h = $d % $e;
                    $i = -3 % $a;
                    $j = -3 % $b;
                    $k = -3 % $c;
                    $l = -3 % $d;
                    $m = 3 % $a;
                    $n = 3 % $b;
                    $o = 3 % $c;
                    $p = 3 % $d;
                    $q = $a % 0;
                    $r = $a % 3;
                    $s = $a % -3;
                    $t = $b % 0;
                    $u = $b % 3;
                    $v = $b % -3;
                    $w = $c % 0;
                    $x = $c % 3;
                    $y = $c % -3;
                    $z = $d % 0;
                    $aa = $d % 3;
                    $ab = $d % -3;
                ',
                'assertions' => [
                    '$f===' => 'int<0, 4>',
                    '$g===' => 'int<-4, 0>',
                    '$h===' => 'int<-4, 4>',
                    '$i===' => 'int<min, 0>',
                    '$j===' => 'int<min, 0>',
                    '$k===' => 'empty',
                    '$l===' => 'int',
                    '$m===' => 'int<0, max>',
                    '$n===' => 'int<min, 0>',
                    '$o===' => 'empty',
                    '$p===' => 'int',
                    '$q===' => 'empty',
                    '$r===' => 'int<0, 2>',
                    '$s===' => 'int<-2, 0>',
                    '$t===' => 'empty',
                    '$u===' => 'int<-2, 0>',
                    '$v===' => 'int<2, 0>',
                    '$w===' => 'empty',
                    '$x===' => 'int<0, 2>',
                    '$y===' => 'int<-2, 0>',
                    '$z===' => 'empty',
                    '$aa===' => 'int<-2, 2>',
                    '$ab===' => 'int<-2, 2>',
                ]
            ],
            'pow' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = $d = getInt();
                    assert($a >= 2);//positive range
                    assert($b <= -2);//negative range
                    /** @var int<0, 0> $c */; // 0 range
                    assert($d >= -100);// mixed range
                    assert($d <= 100);// mixed range

                    $e = 0 ** $a;
                    $f = 0 ** $b;
                    $g = 0 ** $c;
                    $h = 0 ** $d;
                    $i = (-2) ** $a;
                    $j = (-2) ** $b;
                    $k = (-2) ** $c;
                    $l = (-2) ** $d;
                    $m = 2 ** $a;
                    $n = 2 ** $b;
                    $o = 2 ** $c;
                    $p = 2 ** $d;
                    $q = $a ** 0;
                    $r = $a ** 2;
                    $s = $a ** -2;
                    $t = $b ** 0;
                    $u = $b ** 2;
                    $v = $b ** -2;
                    $w = $c ** 0;
                    $x = $c ** 2;
                    $y = $c ** -2;
                    $z = $d ** 0;
                    $aa = $d ** 2;
                    $ab = $d ** -2;
                ',
                'assertions' => [
                    '$e===' => '0',
                    '$f===' => 'empty',
                    '$g===' => '1',
                    '$h===' => 'empty',
                    '$i===' => 'int',
                    '$j===' => 'float',
                    '$k===' => '-1',
                    '$l===' => 'float|int',
                    '$m===' => 'int<1, max>',
                    '$n===' => 'float',
                    '$o===' => '1',
                    '$p===' => 'float|int',
                    '$q===' => '1',
                    '$r===' => 'int<1, max>',
                    '$s===' => 'float',
                    '$t===' => '-1',
                    '$u===' => 'int<1, max>',
                    '$v===' => 'float',
                    '$w===' => '1',
                    '$x===' => '0',
                    '$y===' => 'empty',
                    '$z===' => '1',
                    '$aa===' => 'int<1, max>',
                    '$ab===' => 'float',
                ]
            ],
            'multiplications' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = $d = $e = $f = $g = $h = $i = $j = $k = $l = $m = $n = $o = $p = getInt();
                    assert($b <= -2);
                    assert($c <= 2);
                    assert($d >= -2);
                    assert($e >= 2);
                    assert($f >= -2);
                    assert($f <= 2);

                    $g = $a * $b;
                    $h = $a * $c;
                    $i = $a * $d;
                    $j = $a * $e;
                    $k = $a * $f;
                    $l = $b * $b;
                    $m = $b * $c;
                    $n = $b * $d;
                    $o = $b * $e;
                    $p = $b * $f;
                    $q = $c * $c;
                    $r = $c * $d;
                    $s = $c * $e;
                    $t = $c * $f;
                    $u = $d * $d;
                    $v = $d * $e;
                    $w = $d * $f;
                    $x = $e * $e;
                    $y = $d * $f;
                    $z = $f * $f;
                    ',
                'assertions' => [
                    '$g===' => 'int',
                    '$h===' => 'int',
                    '$i===' => 'int',
                    '$j===' => 'int',
                    '$k===' => 'int',
                    '$l===' => 'int<4, max>',
                    '$m===' => 'int',
                    '$n===' => 'int',
                    '$o===' => 'int<min, -4>',
                    '$p===' => 'int',
                    '$q===' => 'int',
                    '$r===' => 'int',
                    '$s===' => 'int',
                    '$t===' => 'int',
                    '$u===' => 'int',
                    '$v===' => 'int',
                    '$w===' => 'int',
                    '$x===' => 'int<4, max>',
                    '$y===' => 'int',
                    '$z===' => 'int<-4, 4>',
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
            ],
            'IntRangeContainedInMultipleInt' => [
                '<?php
                    $_arr = [];

                    foreach ([0, 1] as $i) {
                        $_arr[$i] = 1;
                    }

                    /** @var int<0,1> $j */
                    $j = 0;

                    echo $_arr[$j];'
            ],
            'modulo' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    $b = $a % 10;
                    assert($a > 0);
                    $c = $a % 10;
                    $d = $a % $a;',
                'assertions' => [
                    '$b===' => 'int<-9, 9>',
                    '$c===' => 'int<0, 9>',
                    '$d===' => '0|positive-int'
                ],
            ],
            'minus' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $d = $e = getInt();
                    assert($a > 5);
                    assert($a <= 10);
                    assert($b > -10);
                    assert($b <= 100);
                    $c = $a - $b;
                    $f = $a - $d;
                    assert($e > 0);
                    $g = $a - $e;
                    ',
                'assertions' => [
                    '$c===' => 'int<-94, 19>',
                    '$f===' => 'int<min, max>',
                    '$g===' => 'int<min, 9>',
                ],
            ],
            'bits' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = getInt();
                    assert($a > 5);
                    assert($b <= 6);
                    $d = $a ^ $b;
                    $e = $a & $b;
                    $f = $a | $b;
                    $g = $a << $b;
                    $h = $a >> $b;
                    ',
                'assertions' => [
                    '$d===' => 'int',
                    '$e===' => 'int',
                    '$f===' => 'int',
                    '$g===' => 'int',
                    '$h===' => 'int',
                ],
            ],
            'UnaryMinus' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $c = $e = getInt();
                    assert($a > 5);
                    $b = -$a;
                    assert($c > 0);
                    $d = -$c;
                    assert($e > 5);
                    assert($e < 10);
                    $f = -$e;
                    ',
                'assertions' => [
                    '$b===' => 'int<min, -6>',
                    '$d===' => 'int<min, -1>',
                    '$f===' => 'int<-9, -6>',
                ],
            ],
            'countOnKeyedArray' => [
                '<?php
                    $conf   = [
                       "K",
                       "M",
                       "G",
                    ];
                    $i = count($conf) - 1;
                ',
                'assertions' => [
                    '$i===' => '2',
                ],
            ],
            'intersections' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    /** @var int<0, 10> $a */
                    $b = -$a;
                    $c = null;
                    if($b === $a){
                        //$b and $a should intersect at 0, so $c should be 0
                        $c = $b;
                    }
                    ',
                'assertions' => [
                    '$c===' => 'int<0, 0>|null',
                ],
            ],
            'minMax' => [
                '<?php
                    function getInt(): int{return 0;}
                    $a = $b = $c = $d = $e = getInt();
                    assert($b > 10);
                    assert($c < -15);
                    assert($d === 20);
                    assert($e > 0);
                    $f = min($a, $b, $c, $d);
                    $g = min($b, $c, $d);
                    $h = min($d, $e);
                    $i = max($b, $c, $d);
                    $j = max($d, $e);
                    $k = max($e, 40);
                    ',
                'assertions' => [
                    '$f===' => 'int<min, -16>',
                    '$g===' => 'int<min, -16>',
                    '$h===' => 'int<1, 20>',
                    '$i===' => 'int<20, max>',
                    '$j===' => 'int<20, max>',
                    '$k===' => 'int<40, max>',
                ],
            ],
            'dontCrashOnFalsy' => [
                '<?php

                    function doAnalysis(): void
                    {
                        /** @var int<3, max> $shuffle_count */
                        $shuffle_count = 1;

                        /** @var list<string> $file_paths */
                        $file_paths = [];

                        /** @var int<0, max> $count */
                        $count = 1;
                        /** @var int $middle */
                        $middle = 1;
                        /** @var int<0, max> $remainder */
                        $remainder = 1;


                        for ($i = 0; $i < $shuffle_count; $i++) {
                            for ($j = 0; $j < $middle; $j++) {
                                if ($j * $shuffle_count + $i < $count) {
                                    echo $file_paths[$j * $shuffle_count + $i];
                                }
                            }

                            if ($remainder) {
                                echo $file_paths[$middle * $shuffle_count + $remainder - 1];
                                $remainder--;
                            }
                        }
                    }',
            ],
            'positiveIntToRangeWithInferior' => [
                '<?php
                    /** @var positive-int $length */
                    $length = 0;

                    if ($length < 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<8, max>',
                ],
            ],
            'positiveIntToRangeWithSuperiorOrEqual' => [
                '<?php
                    /** @var positive-int $length */
                    $length = 0;

                    if ($length >= 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<1, 7>',
                ],
            ],
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
