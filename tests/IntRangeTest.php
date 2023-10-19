<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Internal\Type\TypeCombiner;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;

class IntRangeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testCombineIntRangeDoesntAffectOriginal(): void
    {
        $range = new TIntRange(5, 10);
        TypeCombiner::combine([new TLiteralInt(1), new TLiteralInt(50), $range]);

        $this->assertEquals(5, $range->min_bound);
        $this->assertEquals(10, $range->max_bound);
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'intRangeContained' => [
                'code' => '<?php
                    /**
                     * @param int<1,12> $a
                     * @return int<-1, max>
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'positiveIntRange' => [
                'code' => '<?php
                    /**
                     * @param int<1,12> $a
                     * @return positive-int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'nonNegativeIntRange' => [
                'code' => '<?php
                    /**
                     * @param int<0,12> $a
                     * @return non-negative-int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'negativeIntRange' => [
                'code' => '<?php
                    /**
                     * @param int<-12,-1> $a
                     * @return negative-int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'nonPositiveIntRange' => [
                'code' => '<?php
                    /**
                     * @param int<-12,0> $a
                     * @return non-positive-int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'intRangeToInt' => [
                'code' => '<?php
                    /**
                     * @param int<1,12> $a
                     * @return int
                     */
                    function scope(int $a){
                        return $a;
                    }',
            ],
            'intReduced' => [
                'code' => '<?php
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
                ],
            ],
            'complexAssertions' => [
                'code' => '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    assert($a >= 495 + 5);
                    $b = 5000;
                    assert($a < $b);
                    ',
                'assertions' => [
                    '$a===' => 'int<500, 4999>',
                ],
            ],
            'negatedAssertions' => [
                'code' => '<?php
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
                ],
            ],
            'intOperations' => [
                'code' => '<?php
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
                    '$e===' => 'int<500, 4999>',
                ],
            ],
            'mod' => [
                'code' => '<?php
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
                    /** @psalm-suppress NoValue */
                    $k = -3 % $c;
                    $l = -3 % $d;
                    $m = 3 % $a;
                    $n = 3 % $b;
                    /** @psalm-suppress NoValue */
                    $o = 3 % $c;
                    $p = 3 % $d;
                    /** @psalm-suppress NoValue */
                    $q = $a % 0;
                    $r = $a % 3;
                    $s = $a % -3;
                    /** @psalm-suppress NoValue */
                    $t = $b % 0;
                    $u = $b % 3;
                    $v = $b % -3;
                    /** @psalm-suppress NoValue */
                    $w = $c % 0;
                    $x = $c % 3;
                    $y = $c % -3;
                    /** @psalm-suppress NoValue */
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
                    '$k===' => 'never',
                    '$l===' => 'int',
                    '$m===' => 'int<0, max>',
                    '$n===' => 'int<min, 0>',
                    '$o===' => 'never',
                    '$p===' => 'int',
                    '$q===' => 'never',
                    '$r===' => 'int<0, 2>',
                    '$s===' => 'int<-2, 0>',
                    '$t===' => 'never',
                    '$u===' => 'int<-2, 0>',
                    '$v===' => 'int<2, 0>',
                    '$w===' => 'never',
                    '$x===' => 'int<0, 2>',
                    '$y===' => 'int<-2, 0>',
                    '$z===' => 'never',
                    '$aa===' => 'int<-2, 2>',
                    '$ab===' => 'int<-2, 2>',
                ],
            ],
            'pow' => [
                'code' => '<?php
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
                    '$f===' => 'float',
                    '$g===' => '1',
                    '$h===' => '0|1|float',
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
                    '$y===' => 'float',
                    '$z===' => '1',
                    '$aa===' => 'int<1, max>',
                    '$ab===' => 'float',
                ],
            ],
            'multiplications' => [
                'code' => '<?php
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
                ],
            ],
            'SKIPPED-intLoopPositive' => [
                'code' => '<?php
                    //skipped, int range in loops not supported yet
                    for($i = 0; $i < 10; $i++){

                    }',
                'assertions' => [
                    '$i===' => 'int<0, 9>',
                ],
            ],
            'SKIPPED-intLoopNegative' => [
                'code' => '<?php
                    //skipped, int range in loops not supported yet
                    for($i = 10; $i > 1; $i--){

                    }',
                'assertions' => [
                    '$i===' => 'int<2, 10>',
                ],
            ],
            'integrateExistingArrayPositive' => [
                'code' => '<?php
                    /** @return int<5, max> */
                    function getInt()
                    {
                        return 7;
                    }

                    $_arr = ["a", "b", "c"];
                    $a = getInt();
                    $_arr[$a] = 12;',
                'assertions' => [
                    '$_arr===' => "non-empty-array<int<0, max>, 'a'|'b'|'c'|12>",
                ],
            ],
            'integrateExistingArrayNegative' => [
                'code' => '<?php
                    /** @return int<min, -1> */
                    function getInt()
                    {
                        return -2;
                    }

                    $_arr = ["a", "b", "c"];
                    $a = getInt();
                    $_arr[$a] = 12;',
                'assertions' => [
                    '$_arr===' => "non-empty-array<int<min, 2>, 'a'|'b'|'c'|12>",
                ],
            ],
            'SKIPPED-statementsInLoopAffectsEverything' => [
                'code' => '<?php
                    //skipped, int range in loops not supported yet
                    $remainder = 1;
                    for ($i = 0; $i < 5; $i++) {
                        if ($remainder) {
                            $remainder--;
                        }
                    }',
                'assertions' => [
                    '$remainder===' => 'int<min, 1>',
                ],
            ],
            'IntRangeRestrictWhenUntouched' => [
                'code' => '<?php
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
            'intRangeExpandedByLoop' => [
                'code' => '<?php
                    for ($i = 0; $i < 10; $i++) {
                        takesInt($i);
                    }
                    for (; $i < 20; $i++) {
                        takesInt($i);
                    }

                    /** @psalm-param int<0, 20> $i */
                    function takesInt(int $i): void{
                        return;
                    }',
            ],
            'statementsInLoopPreserveNonNegativeIntRange' => [
                'code' => '<?php
                    $sum = 0;
                    foreach ([-6, 0, 2] as $i) {
                        if ($i > 0) {
                            $sum += $i;
                        }
                    }
                    takesNonNegativeInt($sum);

                    /** @psalm-param int<0, max> $i */
                    function takesNonNegativeInt(int $i): void{
                        return;
                    }',
            ],
            'wrongLoopAssertion' => [
                'code' => '<?php
                    function a(): array {
                        $type_tokens = getArray();

                        for ($i = 0, $l = rand(0,100); $i < $l; ++$i) {
                            if ($i > 0 && rand(0,1)) {
                                continue;
                            }

                            $type_tokens[$i] = "";

                            if ($i > 1) {
                                $type_tokens[$i - 2];
                            }
                        }

                        return [];
                    }

                    /** @return array<int, string> */
                    function getArray(): array {
                        return [];
                    }',
            ],
            'IntRangeContainedInMultipleInt' => [
                'code' => '<?php
                    $_arr = [];

                    foreach ([0, 1] as $i) {
                        $_arr[$i] = 1;
                    }

                    /** @var int<0,1> $j */
                    $j = 0;

                    echo $_arr[$j];',
            ],
            'modulo' => [
                'code' => '<?php
                    function getInt(): int{return 0;}
                    $a = getInt();
                    $b = $a % 10;
                    assert($a > 0);
                    $c = $a % 10;
                    $d = $a % $a;',
                'assertions' => [
                    '$b===' => 'int<-9, 9>',
                    '$c===' => 'int<0, 9>',
                    '$d===' => 'int<0, max>',
                ],
            ],
            'minus' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @return int<0, 10> */
                    function getInt(): int{ return rand(0, 10); }

                    $a = getInt();
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
                'code' => '<?php
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
                    $l = min($a, ...[$b, $c], $d);
                    $m = max(...[$a, ...[$b, $c]], $d);
                    ',
                'assertions' => [
                    '$f===' => 'int<min, -16>',
                    '$g===' => 'int<min, -16>',
                    '$h===' => 'int<1, 20>',
                    '$i===' => 'int<20, max>',
                    '$j===' => 'int<20, max>',
                    '$k===' => 'int<40, max>',
                    '$l===' => 'int<min, -16>',
                    '$m===' => 'int<20, max>',
                ],
            ],
            'dontCrashOnFalsy' => [
                'code' => '<?php

                    function doAnalysis(): void
                    {
                        /** @var int<3, max> $shuffle_count */
                        $shuffle_count = 1;

                        /** @var list<string> $file_paths */
                        $file_paths = [];

                        /** @var int<0, max> $count */
                        $count = 1;
                        /** @var int<0, max> $middle */
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
                'code' => '<?php
                    /** @var positive-int $length */
                    $length = 0;

                    if ($length < 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<8, max>',
                ],
            ],
            'nonNegativeIntToRangeWithInferior' => [
                'code' => '<?php
                    /** @var non-negative-int $length */
                    $length = 0;

                    if ($length < 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<8, max>',
                ],
            ],
            'negativeIntToRangeWithSuperior' => [
                'code' => '<?php
                    /** @var negative-int $length */
                    $length = -8;

                    if ($length > -8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<min, -8>',
                ],
            ],
            'nonPositiveIntToRangeWithSuperior' => [
                'code' => '<?php
                    /** @var non-positive-int $length */
                    $length = -8;

                    if ($length > -8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<min, -8>',
                ],
            ],
            'positiveIntToRangeWithSuperiorOrEqual' => [
                'code' => '<?php
                    /** @var positive-int $length */
                    $length = 0;

                    if ($length >= 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<1, 7>',
                ],
            ],
            'nonNegativeIntToRangeWithSuperiorOrEqual' => [
                'code' => '<?php
                    /** @var non-negative-int $length */
                    $length = 0;

                    if ($length >= 8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<0, 7>',
                ],
            ],
            'negativeIntToRangeWithInferiorOrEqual' => [
                'code' => '<?php
                    /** @var negative-int $length */
                    $length = -1;

                    if ($length <= -8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<-7, -1>',
                ],
            ],
            'nonPositiveIntToRangeWithInferiorOrEqual' => [
                'code' => '<?php
                    /** @var non-positive-int $length */
                    $length = -1;

                    if ($length <= -8) {
                        throw new \RuntimeException();
                    }',
                'assertions' => [
                    '$length===' => 'int<-7, 0>',
                ],
            ],
            'literalEquality' => [
                'code' => '<?php

                    /** @var string $secret */
                    $length = strlen($secret);
                    if ($length > 16) {
                        throw new Exception("");
                    }

                    assert($length === 1);
                    ',
                'assertions' => [
                    '$length===' => '1',
                ],
            ],
            'PositiveIntCombinedWithIntRange' => [
                'code' => '<?php
                    /** @var positive-int */
                    $int = 1;
                    /** @var array<int<0, max>, int<0, max>> */
                    $_arr = [];

                    $_arr[1] = $int;
                    $_arr[$int] = 2;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<0, max>, int<0, max>>',
                ],
            ],
            'nonNegativeIntCombinedWithIntRange' => [
                'code' => '<?php
                    /** @var non-negative-int */
                    $int = 1;
                    /** @var array<int<0, max>, int<0, max>> */
                    $_arr = [];

                    $_arr[0] = 0;
                    $_arr[1] = $int;
                    $_arr[$int] = 2;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<0, max>, int<0, max>>',
                ],
            ],
            'negativeIntCombinedWithIntRange' => [
                'code' => '<?php
                    /** @var negative-int */
                    $int = -1;
                    /** @var array<int<min, -1>, int<min, -1>> */
                    $_arr = [];

                    $_arr[-1] = $int;
                    $_arr[$int] = -2;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<min, -1>, int<min, -1>>',
                ],
            ],
            'nonPositiveIntCombinedWithIntRange' => [
                'code' => '<?php
                    /** @var non-positive-int */
                    $int = -1;
                    /** @var array<int<min, 0>, int<min, 0>> */
                    $_arr = [];

                    $_arr[0] = 0;
                    $_arr[-1] = $int;
                    $_arr[$int] = -2;',
                'assertions' => [
                    '$_arr===' => 'non-empty-array<int<min, 0>, int<min, 0>>',
                ],
            ],
            'noErrorPushingBigShapeIntoConstant' => [
                'code' => '<?php
                        class DocComment
                            {
                                private const PSALM_ANNOTATIONS = [
                                    "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "",
                                    "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""
                                ];
                            }',
                'assertions' => [
                ],
            ],
            'assertionsAndNegationsOnRanges' => [
                'code' => '<?php
                    /** @var int $int */
                    $int = 1;
                    $a = $b = $c = $d = $e = $f = $g = $h = $int;

                    if ($a < 1) {
                        $res1 = $a; //should be int<min, 0>
                        throw new Exception();
                    }

                    $res2 = $a; //should be int<1, max>

                    if ($b > 1) {
                        $res3 = $b; //should be int<2, max>
                        throw new Exception();
                    }

                    $res4 = $b; //should be int<min, 1>

                    if ($c <= 1) {
                        $res5 = $c; //should be int<min, 1>
                        throw new Exception();
                    }

                    $res6 = $c; //should be int<2, max>

                    if ($d >= 1) {
                        $res7 = $d; //should be int<1, max>
                        throw new Exception();
                    }

                    $res8 = $d; //should be int<min, 0>



                    if (1 < $e) {
                        $res9 = $e; //should be int<2, max>
                        throw new Exception();
                    }

                    $res10 = $e; //should be int<min, 1>

                    if (1 > $f) {
                        $res11 = $f; //should be int<min, 0>
                        throw new Exception();
                    }

                    $res12 = $f; //should be int<1, max>

                    if (1 <= $g) {
                        $res13 = $g; //should be int<1, max>
                        throw new Exception();
                    }

                    $res14 = $g; //should be int<min, 0>

                    if (1 >= $h) {
                        $res15 = $h; //should be int<min, 1>
                        throw new Exception();
                    }

                    $res16 = $h; //should be int<2, max>',
                'assertions' => [
                    //'$res1' => 'int<min, 0>',
                    '$res2' => 'int<1, max>',
                    //'$res3' => 'int<2, max>',
                    '$res4' => 'int<min, 1>',
                    //'$res5' => 'int<min, 1>',
                    '$res6' => 'int<2, max>',
                    //'$res7' => 'int<1, max>',
                    '$res8' => 'int<min, 0>',

                    //'$res9' => 'int<2, max>',
                    '$res10' => 'int<min, 1>',
                    //'$res11' => 'int<min, 0>',
                    '$res12' => 'int<1, max>',
                    //'$res13' => 'int<1, max>',
                    '$res14' => 'int<min, 0>',
                    //'$res15' => 'int<min, 1>',
                    '$res16' => 'int<2, max>',

                ],
            ],
            'arraykeyCanBeRange' => [
                'code' => '<?php
                    /**
                     * @param array-key $key
                     * @param positive-int|non-negative-int|negative-int|non-positive-int $expected
                     */
                    function matches($key, int $expected): bool {
                        if ($key !== $expected) {
                            return false;
                        }

                        return true;
                    }',
            ],
            'literalArrayUnpack' => [
                'code' => '<?php
                    /** @var int<0, 5> */
                    $a = 2;
                    /** @var int<6, 10> */
                    $b = 9;

                    /**
                     * @param int<0, 5> $_a
                     * @param int<6, 10> $_b
                     */
                    function foo(int $_a, int $_b): void {}

                    foo(...[$a, $b]);
                ',
            ],
            'minMaxInNamespace' => [
                'code' => '<?php
                    namespace Foo {
                        /**
                         * @param int<0, max> $_a
                         * @param int<min, 0> $_b
                         */
                        function bar(int $_a, int $_b): void {}
                    }
                ',
            ],
            'rangeOverflow' => [
                'code' => '<?php
                    $i = (int)ceil(1);
                    if ($i <= 9223372036854775807) {
                        $z = $i;
                    } else {
                        $z = null;
                    }
                ',
                'assertions' => [
                    '$z' => 'int<min, 9223372036854775807>|null',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'intRangeNotContained' => [
                'code' => '<?php
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
            'assertOutOfRange' => [
                'code' => '<?php
                    /**
                     * @param int<1, 5> $a
                     */
                    function scope(int $a): void{
                        assert($a === 0);
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'assertRedundantInferior' => [
                'code' => '<?php
                    /**
                     * @param int<min, 5> $a
                     */
                    function scope(int $a): void{
                        assert($a < 10);
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'assertImpossibleInferior' => [
                'code' => '<?php
                    /**
                     * @param int<5, max> $a
                     */
                    function scope(int $a): void{
                        assert($a < 4);
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'maxSpecifiedAsFirst' => [
                'code' => '<?php
                    /**
                     * @param int<max, 0> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'minSpecifiedAsSecond' => [
                'code' => '<?php
                    /**
                     * @param int<0, min> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'unknownConstant' => [
                'code' => '<?php
                    /**
                     * @param int<0, FOO> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'floatAsABoundary' => [
                'code' => '<?php
                    /**
                     * @param int<0, 5.5> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'stringAsABoundary' => [
                'code' => '<?php
                    /**
                     * @param int<0, "bar"> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'minGreaterThanMax' => [
                'code' => '<?php
                    /**
                     * @param int<4, 3> $a
                     */
                    function scope(int $a){
                        return $a;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }
}
