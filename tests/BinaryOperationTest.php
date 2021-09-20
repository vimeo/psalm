<?php
namespace Psalm\Tests;

use function class_exists;

use const DIRECTORY_SEPARATOR;

class BinaryOperationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testGMPOperations()
    {
        if (class_exists('GMP') === false) {
            $this->markTestSkipped('Cannot run test, base class "GMP" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                $a = gmp_init(2);
                $b = gmp_init(4);
                $c = $a + $b;
                $d = $c + 3;
                echo $d;
                $f = $a / $b;
                $g = $a ** $b;
                $h = $a % $b;

                $i = 6 + $b;
                $j = 6 - $b;
                $k = 6 * $b;
                $l = 6 / $b;
                $m = 6 ** $b;
                $n = 6 % $b;

                $o = $a + 6;
                $p = $a - 6;
                $q = $a * 6;
                $r = $a / 6;
                $s = $a ** 6;
                $t = $a % 6;'
        );

        $assertions = [
            '$a' => 'GMP',
            '$b' => 'GMP',
            '$c' => 'GMP',
            '$d' => 'GMP',
            '$f' => 'GMP',
            '$g' => 'GMP',
            '$h' => 'GMP',
            '$i' => 'GMP',
            '$j' => 'GMP',
            '$k' => 'GMP',
            '$l' => 'GMP',
            '$m' => 'GMP',
            '$n' => 'GMP',
            '$o' => 'GMP',
            '$p' => 'GMP',
            '$q' => 'GMP',
            '$r' => 'GMP',
            '$s' => 'GMP',
            '$t' => 'GMP',
        ];

        $context = new \Psalm\Context();

        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = (string)$context->vars_in_scope[$var];
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }

    public function testStrictTrueEquivalence(): void
    {
        $config = \Psalm\Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function returnsABool(): bool {
                    return rand(1, 2) === 1;
                }

                if (returnsABool() === true) {
                    echo "hi!";
                }'
        );

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('RedundantIdentityWithTrue');

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    public function testStringFalseInequivalence(): void
    {
        $config = \Psalm\Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function returnsABool(): bool {
                    return rand(1, 2) === 1;
                }

                if (returnsABool() !== false) {
                    echo "hi!";
                }'
        );

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('RedundantIdentityWithTrue');

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'regularAddition' => [
                '<?php
                    $a = 5 + 4;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'differingNumericTypesAdditionInWeakMode' => [
                '<?php
                    $a = 5 + 4.1;',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'modulo' => [
                '<?php
                    $a = 25 % 2;
                    $b = 25.4 % 2;
                    $c = 25 % 2.5;
                    $d = 25.5 % 2.5;
                    $e = 25 % 1;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'int',
                    '$e' => 'int',
                ],
            ],
            'numericAddition' => [
                '<?php
                    $a = "5";

                    if (is_numeric($a)) {
                        $b = $a + 4;
                    }',
            ],
            'concatenation' => [
                '<?php
                    $a = "Hey " . "Jude,";',
            ],
            'concatenationWithNumberInWeakMode' => [
                '<?php
                    $a = "hi" . 5;',
            ],
            'concatenationWithTwoLiteralInt' => [
                '<?php
                    $a = 7 . 5;',
                'assertions' => [
                    '$a' => 'string',//will contain "75"
                ]
            ],
            'concatenationWithTwoInt' => [
                '<?php
                    /**
                     * @param positive-int|0 $b
                     * @return numeric-string
                     */
                    function scope(int $a, int $b): string{
                        return $a . $b;
                    }',
            ],
            'concatenateUnion' => [
                '<?php
                    $arr = ["foobar" => false, "foobaz" => true, "barbaz" => true];
                    $foo = random_int(0, 1) ? "foo" : "bar";
                    $foo .= "baz";
                    $val = $arr[$foo];
                ',
                'assertions' => ['$val' => 'true'],
            ],
            'concatenateLiteralIntAndString' => [
                '<?php
                    $arr = ["foobar" => false, "foo123" => true];
                    $foo = "foo";
                    $foo .= 123;
                    $val = $arr[$foo];
                ',
                'assertions' => ['$val' => 'true'],
            ],
            'concatenateNonEmptyResultsInNonEmpty' => [
                '<?php
                    /** @param non-empty-lowercase-string $arg */
                    function foobar($arg): string
                    {
                        return $arg;
                    }

                    $foo = rand(0, 1) ? "a" : "b";
                    $bar = rand(0, 1) ? "c" : "d";
                    $baz = $foo . $bar;
                    foobar($baz);
                ',
            ],
            'concatenateEmptyWithNonemptyCast' => [
                '<?php
                    class A
                    {
                        /** @psalm-return non-empty-lowercase-string */
                        public function __toString(): string
                        {
                            return "foo";
                        }
                    }

                    /** @param non-empty-lowercase-string $arg */
                    function foo($arg): string
                    {
                        return $arg;
                    }

                    $bar = new A();
                    foo("" . $bar);
                ',
            ],
            'concatenateNegativeIntLeftSideIsNumeric' => [
                '<?php
                    /**
                     * @param numeric-string $bar
                     * @return int
                     */
                    function foo(string $bar): int
                    {
                        return (int) $bar;
                    }

                    foo(foo("-123") . 456);
                ',
            ],
            'castToIntPreserveNarrowerIntType' => [
                '<?php
                    /**
                     * @param positive-int $i
                     * @return positive-int
                     */
                    function takesAnInt(int $i) {
                        /** @psalm-suppress RedundantCast */
                        return (int)$i;
                    }
                ',
            ],
            'concatenateFloatWithInt' => [
                '<?php
                    /**
                     * @param numeric-string $bar
                     * @return numeric-string
                     */
                    function foo(string $bar): string
                    {
                        return $bar;
                    }

                    foo(-123.456 . 789);
                ',
            ],
            'concatenateIntIsLowercase' => [
                '<?php
                    /**
                     * @param non-empty-lowercase-string $bar
                     * @return non-empty-lowercase-string
                     */
                    function foobar(string $bar): string
                    {
                        return $bar;
                    }

                    /** @var lowercase-string */
                    $foo = "abc";
                    /** @var int */
                    $bar = 123;
                    foobar($foo . $bar);
                ',
            ],
            'possiblyInvalidAdditionOnBothSides' => [
                '<?php
                    function foo(string $s) : int {
                        return strpos($s, "a") + strpos($s, "b");
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyFalseOperand'],
            ],
            'bitwiseoperations' => [
                '<?php
                    $a = 4 & 5;
                    $b = 2 | 3;
                    $c = 4 ^ 3;
                    $d = 1 << 2;
                    $e = 15 >> 2;
                    $f = "a" & "b";',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'int',
                    '$e' => 'int',
                    '$f' => 'string',
                ],
            ],
            'ComplexLiteralBitwise' => [
                '<?php
                    /**
                     * @return 7
                     */
                    function scope(){
                        return 1 | 2 | 4 | (1 & 0);
                    }',
            ],
            'booleanXor' => [
                '<?php
                    $a = 4 ^ 1;
                    $b = 3 ^ 1;
                    $c = (true xor false);
                    $d = (false xor false);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'bool',
                    '$d' => 'bool',
                ],
            ],
            'ternaryAssignment' => [
                '<?php
                    rand(0, 1) ? $a = 1 : $a = 2;
                    echo $a;',
            ],
            'assignmentInRHS' => [
                '<?php
                    $name = rand(0, 1) ? "hello" : null;
                    if ($name !== null || ($name = rand(0, 1) ? "hello" : null) !== null) {}',
            ],
            'floatIncrement' => [
                '<?php
                    $a = 1.1;
                    $a++;
                    $b = 1.1;
                    $b += 1;',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'float',
                ],
            ],
            'exponent' => [
                '<?php
                    $b = 4 ** 5;',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'bitwiseNot' => [
                '<?php
                    $a = ~4;
                    $b = ~4.0;
                    $c = ~4.4;
                    $d = ~"a";',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'string',
                ],
            ],
            'stringIncrementSuppressed' => [
                '<?php
                    $a = "hello";
                    /** @psalm-suppress StringIncrement */
                    $a++;',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'stringIncrementWithCheck' => [
                '<?php
                    /** @psalm-suppress StringIncrement */
                    for($a = "a"; $a != "z"; $a++){
                        if($a === "b"){
                            echo "b reached";
                        }
                    }',
                'assertions' => [
                    '$a===' => 'non-empty-string',
                ],
            ],
            'nullCoalescingAssignment' => [
                '<?php
                    function foo(?string $s): string {
                        $s ??= "Hello";
                        return $s;
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'nullCoalescingArrayAssignment' => [
                '<?php
                    /**
                     * @param array<string> $arr
                     */
                    function foo(array $arr) : void {
                        $b = [];

                        foreach ($arr as $a) {
                            $b[0] ??= $a;
                        }
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'addArrays' => [
                '<?php
                    /**
                     * @param array{host?:string} $opts
                     * @return array{host:string|int}
                     */
                    function a(array $opts): array {
                        return $opts + ["host" => 5];
                    }'
            ],
            'addIntToZero' => [
                '<?php
                    $tick = 0;

                    test($tick + 1);

                    $tick++;

                    test($tick);

                    /**
                     * @psalm-param positive-int $tickedTimes
                     */
                    function test(int $tickedTimes): void {}'
            ],
            'numericPlusIntegerIsIntOrFloat' => [
                '<?php
                    /** @param numeric-string $s */
                    function foo(string $s) : void {
                        $s = $s + 1;
                        if (is_int($s)) {}
                    }'
            ],
            'interpolatedStringNotEmpty' => [
                '<?php
                    /**
                     * @psalm-param non-empty-string $i
                     */
                    function func($i): string
                    {
                        return $i;
                    }

                    function foo(string $a) : void {
                        func("asdasdasd $a");
                    }'
            ],
            'spaceshipOpIsLiteralUnionType' => [
                '<?php
                    /**
                     * @psalm-param -1|0|1 $i
                     */
                     function onlyZeroOrPlusMinusOne(int $i): int {
                         return $i;
                     }

                     /**
                      * @psalm-param mixed $a
                      * @psalm-param mixed $b
                      */
                     function foo($a, $b): void {
                         onlyZeroOrPlusMinusOne($a <=> $b);
                     }'
            ],
            'notAlwaysPositiveBitOperations' => [
                '<?php

                    $a = 1;
                    $b = 1;
                    $c = 32;
                    $d = 64;
                    $e = 2;

                    if (0 === ($a ^ $b)) {
                        echo "Actually, zero\n";
                    }

                    if (0 === ($a & $e)) {
                        echo "Actually, zero\n";
                    }

                    if (0 === ($a >> $b)) {
                        echo "Actually, zero\n";
                    }

                    if (8 === PHP_INT_SIZE) {
                        if (0 === ($a << $d)) {
                            echo "Actually, zero\n";
                        }
                    }'
            ],
            'IntOverflowMul' => [
                '<?php
                    $a = (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);',
                'assertions' => [
                    '$a' => 'float'
                ],
            ],
            'IntOverflowPow' => [
                '<?php
                    $a = 2 ** 80;',
                'assertions' => [
                    '$a' => 'float'
                ],
            ],
            'IntOverflowPlus' => [
                '<?php
                    $a = 2**62 - 1 + 2**62;
                    $b = 2**62 + 2**62 - 1; // plus results in a float',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'float',
                ],
            ],
            'IntOverflowPowSub' => [
                '<?php
                    $a = 2 ** 63;',
                'assertions' => [
                    '$a' => 'float'
                ],
            ],
            'IntOverflowSub' => [
                '<?php
                    $a = (1 << 63) - (1 << 20);',
                'assertions' => [
                    '$a' => 'float'
                ],
            ],
            'literalConcatCreatesLiteral' => [
                '<?php
                    /**
                     * @param  literal-string $s1
                     * @param  literal-string $s2
                     * @return literal-string
                     */
                    function foo(string $s1, string $s2): string {
                        return $s1 . $s2;
                    }',
            ],
            'literalConcatCreatesLiteral2' => [
                '<?php
                    /**
                     * @param  literal-string $s1
                     * @return literal-string
                     */
                    function foo(string $s1): string {
                        return $s1 . 2;
                    }',
            ],
            'encapsedStringIncludingLiterals' => [
                '<?php
                    /**
                     * @param  literal-string $s1
                     * @param  literal-string $s2
                     * @return literal-string
                     */
                    function foo(string $s1, string $s2): string {
                        return "Hello $s1 $s2";
                    }',
            ],
            'encapsedStringIncludingLiterals2' => [
                '<?php
                    /**
                     * @param  literal-string $s1
                     * @return literal-string
                     */
                    function foo(string $s1): string {
                        $s2 = 2;
                        return "Hello $s1 $s2";
                    }',
            ],
            'literalIntConcatCreatesLiteral' => [
                '<?php
                    /**
                     * @param  literal-string $s1
                     * @param  literal-int $s2
                     * @return literal-string
                     */
                    function foo(string $s1, int $s2): string {
                        return $s1 . $s2;
                    }',
            ],
            'literalIntConcatCreatesLiteral2' => [
                '<?php
                    /**
                     * @param  literal-int $s1
                     * @return literal-string
                     */
                    function foo(int $s1): string {
                        return "foo" . $s1;
                    }',
            ],
            'numericWithInt' => [
                '<?php
                    /** @return numeric */
                    function getNumeric(){
                        return 1;
                    }
                    $a = getNumeric();
                    $a++;
                    $b = getNumeric() * 2;
                    $c = 1 - getNumeric();
                    $d = 2;
                    $d -= getNumeric();
                    ',
                'assertions' => [
                    '$a' => 'float|int',
                    '$b' => 'float|int',
                    '$c' => 'float|int',
                    '$d' => 'float|int',
                ],
            ],
            'encapsedStringWithIntIncludingLiterals' => [
                '<?php
                    /**
                     * @param  literal-int $s1
                     * @param  literal-int $s2
                     * @return literal-string
                     */
                    function foo(int $s1, int $s2): string {
                        return "Hello $s1 $s2";
                    }',
            ],
            'encapsedStringWithIntIncludingLiterals2' => [
                '<?php
                    /**
                     * @param  literal-int $s1
                     * @return literal-string
                     */
                    function foo(int $s1): string {
                        $s2 = "foo";
                        return "Hello $s1 $s2";
                    }',
            ],
            'NumericStringIncrement' => [
                '<?php
                    function scope(array $a): int|float {
                        $offset = array_search("foo", $a);
                        if(is_numeric($offset)){
                            return $offset++;
                        }
                        else{
                            return 0;
                        }
                    }',
            ],
            'NumericStringIncrementLiteral' => [
                '<?php
                    $a = "123";
                    $b = "123";
                    $a++;
                    ++$b;
                    ',
                'assertions' => [
                    '$a' => 'float|int',
                    '$b' => 'float|int',
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
            'badAddition' => [
                '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand',
            ],
            'differingNumericTypesAdditionInStrictMode' => [
                '<?php
                    $a = 5 + 4.1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'concatenationWithNumberInStrictMode' => [
                '<?php
                    $a = "hi" . 5;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'concatenateNegativeIntRightSideIsNotNumeric' => [
                '<?php
                    /**
                     * @param numeric-string $bar
                     * @return int
                     */
                    function foo(string $bar): int
                    {
                        return (int) $bar;
                    }

                    foo(foo("123") . foo("-456"));
                ',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'addArrayToNumber' => [
                '<?php
                    $a = [1] + 1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'additionWithClassInWeakMode' => [
                '<?php
                    $a = "hi" + (new stdClass);',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidOperand' => [
                '<?php
                    $b = rand(0, 1) ? [] : 4;
                    echo $b + 5;',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'possiblyInvalidConcat' => [
                '<?php
                    $b = rand(0, 1) ? [] : "hello";
                    echo $b . "goodbye";',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidGMPOperation' => [
                '<?php
                    $a = gmp_init(2);
                    $b = "a" + $a;',
                'error_message' => 'InvalidOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot add GMP to non-numeric type',
            ],
            'stringIncrement' => [
                '<?php
                    $a = "hello";
                    $a++;',
                'error_message' => 'StringIncrement',
            ],
            'falseIncrement' => [
                '<?php
                    $a = false;
                    $a++;',
                'error_message' => 'FalseOperand',
            ],
            'trueIncrement' => [
                '<?php
                    $a = true;
                    $a++;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyDivByZero' => [
                '<?php
                    $a = 5 / (rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyNullOperand',
            ],
            'invalidExponent' => [
                '<?php
                    $a = "x" ^ 1;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseOr' => [
                '<?php
                    $a = "x" | new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseNot' => [
                '<?php
                    $a = ~new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidBitwiseNot' => [
                '<?php
                    $a = ~(rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidBooleanBitwiseNot' => [
                '<?php
                    $a = ~true;',
                'error_message' => 'InvalidOperand',
            ],
            'substrImpossible' => [
                '<?php
                    class HelloWorld
                    {
                        public function sayHello(string $s): void
                        {
                            if (substr($s, 0, 6) === "abc") {}
                        }
                    }',
                    'error_message' => 'TypeDoesNotContainType',
            ],
            'literalConcatWithStringCreatesString' => [
                '<?php
                    /**
                     * @param  literal-string $s2
                     * @return literal-string
                     */
                    function foo(string $s1, string $s2): string {
                        return $s1 . $s2;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'encapsedConcatWithStringCreatesString' => [
                '<?php
                    /**
                     * @param  literal-string $s2
                     * @return literal-string
                     */
                    function foo(string $s1, string $s2): string {
                        return "hello $s1 $s2";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
        ];
    }
}
