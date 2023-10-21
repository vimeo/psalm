<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class BinaryOperationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testGMPOperations(): void
    {
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
                $t = $a % 6;',
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

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = (string)$context->vars_in_scope[$var];
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }

    public function testDecimalOperations(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                $a = new \Decimal\Decimal(2);
                $b = new \Decimal\Decimal(4);
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
                $t = $a % 6;',
        );

        $assertions = [
            '$a' => 'Decimal\\Decimal',
            '$b' => 'Decimal\\Decimal',
            '$c' => 'Decimal\\Decimal',
            '$d' => 'Decimal\\Decimal',
            '$f' => 'Decimal\\Decimal',
            '$g' => 'Decimal\\Decimal',
            '$h' => 'Decimal\\Decimal',
            '$i' => 'Decimal\\Decimal',
            '$j' => 'Decimal\\Decimal',
            '$k' => 'Decimal\\Decimal',
            '$l' => 'Decimal\\Decimal',
            '$m' => 'Decimal\\Decimal',
            '$n' => 'Decimal\\Decimal',
            '$o' => 'Decimal\\Decimal',
            '$p' => 'Decimal\\Decimal',
            '$q' => 'Decimal\\Decimal',
            '$r' => 'Decimal\\Decimal',
            '$s' => 'Decimal\\Decimal',
            '$t' => 'Decimal\\Decimal',
        ];

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = (string)$context->vars_in_scope[$var];
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }

    public function testMatchOnBoolean(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class a {}
                class b {}
                /** @var a|b */
                $obj = new a;

                $result1 = match (true) {
                    $obj instanceof a => 123,
                    $obj instanceof b => 321,
                };
                $result2 = match (false) {
                    $obj instanceof a => 123,
                    $obj instanceof b => 321,
                };
            ',
        );

        $assertions = [
            '$obj' => 'a|b',
            '$result1' => '123|321',
            '$result2' => '123|321',
        ];

        $context = new Context();

        $this->project_analyzer->setPhpVersion('8.0', 'tests');
        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = $context->vars_in_scope[$var]->getId(true);
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }

    public function testStrictTrueEquivalence(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function returnsABool(): bool {
                    return rand(1, 2) === 1;
                }

                if (returnsABool() === true) {
                    echo "hi!";
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('RedundantIdentityWithTrue');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testStringFalseInequivalence(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function returnsABool(): bool {
                    return rand(1, 2) === 1;
                }

                if (returnsABool() !== false) {
                    echo "hi!";
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('RedundantIdentityWithTrue');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDifferingNumericLiteralTypesAdditionInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    $a = 5 + 4.1;',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDifferingNumericTypesAdditionInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @var float */
                $b = 4.1;
                $a = 5 + $b;',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithNumberInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    $a = "hi" . 5;',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testImplicitStringConcatenation(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    interface I {
                        public function __toString();
                    }

                    function takesI(I $i): void
                    {
                        $a = $i . "hello";
                    }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ImplicitToStringCast');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'regularAddition' => [
                'code' => '<?php
                    $a = 5 + 4;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'differingNumericTypesAdditionInWeakMode' => [
                'code' => '<?php
                    $a = 5 + 4.1;',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'modulo' => [
                'code' => '<?php
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
                'code' => '<?php
                    $a = "5";

                    if (is_numeric($a)) {
                        $b = $a + 4;
                    }',
            ],
            'concatenation' => [
                'code' => '<?php
                    $a = "Hey " . "Jude,";',
            ],
            'concatenationWithNumberInWeakMode' => [
                'code' => '<?php
                    $a = "hi" . 5;',
            ],
            'concatenationWithTwoLiteralInt' => [
                'code' => '<?php
                    $a = 7 . 5;',
                'assertions' => [
                    '$a' => 'string',//will contain "75"
                ],
            ],
            'concatenationWithTwoInt' => [
                'code' => '<?php
                    /**
                     * @param positive-int|0 $b
                     * @return numeric-string
                     */
                    function scope(int $a, int $b): string{
                        return $a . $b;
                    }',
            ],
            'concatenateUnion' => [
                'code' => '<?php
                    $arr = ["foobar" => false, "foobaz" => true, "barbaz" => true];
                    $foo = random_int(0, 1) ? "foo" : "bar";
                    $foo .= "baz";
                    $val = $arr[$foo];
                ',
                'assertions' => ['$val' => 'true'],
            ],
            'concatenateLiteralIntAndString' => [
                'code' => '<?php
                    $arr = ["foobar" => false, "foo123" => true];
                    $foo = "foo";
                    $foo .= 123;
                    $val = $arr[$foo];
                ',
                'assertions' => ['$val' => 'true'],
            ],
            'concatenateNonEmptyResultsInNonEmpty' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'concatenateNonFalsyStringWithUndefinedConstant' => [
                'code' => '<?php
                    /**
                     * @param non-falsy-string $arg
                     * @return non-falsy-string
                     */
                    function foo( $arg ) {
                        /** @psalm-suppress UndefinedConstant */
                        return FOO . $arg;
                    }
                ',
            ],
            'concatenateNonEmptyStringWithUndefinedConstant' => [
                'code' => '<?php
                    /**
                     * @param non-empty-string $arg
                     * @return non-empty-string
                     */
                    function foo( $arg ) {
                        /** @psalm-suppress UndefinedConstant */
                        return FOO . $arg;
                    }
                ',
            ],
            'possiblyInvalidAdditionOnBothSides' => [
                'code' => '<?php
                    function foo(string $s) : int {
                        return strpos($s, "a") + strpos($s, "b");
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyFalseOperand'],
            ],
            'bitwiseoperations' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @return 7
                     */
                    function scope(){
                        return 1 | 2 | 4 | (1 & 0);
                    }',
            ],
            'booleanXor' => [
                'code' => '<?php
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
                'code' => '<?php
                    rand(0, 1) ? $a = 1 : $a = 2;
                    echo $a;',
            ],
            'assignmentInRHS' => [
                'code' => '<?php
                    $name = rand(0, 1) ? "hello" : null;
                    if ($name !== null || ($name = rand(0, 1) ? "hello" : null) !== null) {}',
            ],
            'floatIncrement' => [
                'code' => '<?php
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
                'code' => '<?php
                    $b = 4 ** 5;',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'bitwiseNot' => [
                'code' => '<?php
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
                'code' => '<?php
                    $a = "hello";
                    /** @psalm-suppress StringIncrement */
                    $a++;',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'stringIncrementWithCheck' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(?string $s): string {
                        $s ??= "Hello";
                        return $s;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'nullCoalescingArrayAssignment' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'addArrays' => [
                'code' => '<?php
                    /**
                     * @param array{host?:string} $opts
                     * @return array{host:string|int}
                     */
                    function a(array $opts): array {
                        return $opts + ["host" => 5];
                    }',
            ],
            'addIntToZero' => [
                'code' => '<?php
                    $tick = 0;

                    test($tick + 1);

                    $tick++;

                    test($tick);

                    /**
                     * @psalm-param positive-int $tickedTimes
                     */
                    function test(int $tickedTimes): void {}',
            ],
            'numericPlusIntegerIsIntOrFloat' => [
                'code' => '<?php
                    /** @param numeric-string $s */
                    function foo(string $s) : void {
                        $s = $s + 1;
                        if (is_int($s)) {}
                    }',
            ],
            'interpolatedStringNotEmpty' => [
                'code' => '<?php
                    /**
                     * @psalm-param non-empty-string $i
                     */
                    function func($i): string
                    {
                        return $i;
                    }

                    function foo(string $a) : void {
                        func("asdasdasd $a");
                    }',
            ],
            'spaceshipOpIsLiteralUnionType' => [
                'code' => '<?php
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
                     }',
            ],
            'notAlwaysPositiveBitOperations' => [
                'code' => '<?php

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
                    }',
            ],
            'IntOverflowMul' => [
                'code' => '<?php
                    $a = (1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024);',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'IntOverflowPow' => [
                'code' => '<?php
                    $a = 2 ** 80;',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'IntOverflowPlus' => [
                'code' => '<?php
                    $a = 2**62 - 1 + 2**62;
                    $b = 2**62 + 2**62 - 1; // plus results in a float',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'float',
                ],
            ],
            'IntOverflowPowSub' => [
                'code' => '<?php
                    $a = 2 ** 63;',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'IntOverflowSub' => [
                'code' => '<?php
                    $a = (1 << 63) - (1 << 20);',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'literalConcatCreatesLiteral' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param  literal-string $s1
                     * @return literal-string
                     */
                    function foo(string $s1): string {
                        return $s1 . 2;
                    }',
            ],
            'encapsedStringIncludingLiterals' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param  literal-string $s1
                     * @return literal-string
                     */
                    function foo(string $s1): string {
                        $s2 = 2;
                        return "Hello $s1 $s2";
                    }',
            ],
            'encapsedStringIsInferredAsLiteral' => [
                'code' => '<?php
                    $int = 1;
                    $float = 2.3;
                    $string = "foobar";
                    $interpolated = "{$int}{$float}{$string}";
                ',
                'assertions' => ['$interpolated===' => "'12.3foobar'"],
            ],
            'concatenatedStringIsInferredAsLiteral' => [
                'code' => '<?php
                    $int = 1;
                    $float = 2.3;
                    $string = "foobar";
                    $concatenated = $int . $float . $string;
                ',
                'assertions' => ['$concatenated===' => "'12.3foobar'"],
            ],
            'encapsedNonEmptyNonSpecificLiteralString' => [
                'code' => '<?php
                    /** @var non-empty-literal-string */
                    $string = "foobar";
                    $interpolated = "$string";
                ',
                'assertions' => ['$interpolated===' => 'non-empty-literal-string'],
            ],
            'concatenatedNonEmptyNonSpecificLiteralString' => [
                'code' => '<?php
                    /** @var non-empty-literal-string */
                    $string = "foobar";
                    $concatenated = $string . "";
                ',
                'assertions' => ['$concatenated===' => 'non-empty-literal-string'],
            ],
            'encapsedPossiblyEmptyLiteralString' => [
                'code' => '<?php
                    /** @var "foo"|"" */
                    $foo = "";
                    /** @var "bar"|"" */
                    $bar = "";
                    $interpolated = "{$foo}{$bar}";
                ',
                'assertions' => ['$interpolated===' => 'literal-string'],
            ],
            'literalIntConcatCreatesLiteral' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param  literal-int $s1
                     * @return literal-string
                     */
                    function foo(int $s1): string {
                        return "foo" . $s1;
                    }',
            ],
            'numericWithInt' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function scope(array $a): int|float {
                        $offset = array_search("foo", $a);
                        if(is_numeric($offset)){
                            return $offset++;
                        }
                        else{
                            return 0;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'NumericStringIncrementLiteral' => [
                'code' => '<?php
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
            'coalesceFilterOutNullEvenWithTernary' => [
                'code' => '<?php

                    interface FooInterface
                    {
                        public function toString(): ?string;
                    }

                    function example(object $foo): string
                    {
                        return ($foo instanceof FooInterface ? $foo->toString() : null) ?? "Not a stringable foo";
                    }',
            ],
            'handleLiteralInequalityWithInts' => [
                'code' => '<?php

                    /**
                     * @param int<0, max> $i
                     * @return int<1, max>
                     */
                    function toPositiveInt(int $i): int
                    {
                        if ($i !== 0) {
                            return $i;
                        }
                        return 1;
                    }',
            ],
            'calculateLiteralResultForFloats' => [
                'code' => '<?php
                    $foo = 1.0 + 2.0;
                ',
                'assertions' => ['$foo===' => 'float(3)'],
            ],
            'concatNonEmptyReturnNonFalsyString' => [
                'code' => '<?php
                    /** @var  non-empty-string $s1 */
                    $s1 = "0";
                    /** @var  non-empty-string $s1 */
                    $s2 = "0";

                    $a = $s1.$s2;',
                'assertions' => [
                    '$a===' => 'non-falsy-string',
                ],
            ],
            'concatNumericWithNonEmptyReturnNonFalsyString' => [
                'code' => '<?php
                    /** @var  numeric-string $s1 */
                    $s1 = "1";
                    /** @var  non-empty-string $s2 */
                    $s2 = "0";

                    $a = $s1.$s2;
                    $b = $s2.$s1;',
                'assertions' => [
                    '$a===' => 'non-falsy-string',
                    '$b===' => 'non-falsy-string',
                ],
            ],
            'unaryMinusOverflows' => [
                'code' => <<<'PHP'
                    <?php
                    $a = -(1 << 63);
                    PHP,
                'assertions' => [
                    '$a===' => 'float(9.2233720368548E+18)',
                ],
            ],
            'invalidArrayOperations' => [
                'code' => <<<'PHP'
                    <?php

                    $a1 = 1 + [];
                    $a2 = [] + 1;
                    // This is the one exception to this rule
                    $a3 = [] + [];

                    $b1 = 1 - [];
                    $b2 = [] - 1;
                    $b3 = [] - [];

                    $c1 = 1 * [];
                    $c2 = [] * 1;
                    $c3 = [] * [];

                    $d1 = 1 / [];
                    $d2 = [] / 1;
                    $d3 = [] / [];

                    $e1 = 1 ** [];
                    $e2 = [] ** 1;
                    $e3 = [] ** [];

                    $f1 = 1 % [];
                    $f2 = [] % 1;
                    $f3 = [] % [];

                    PHP,
                'assertions' => [
                    '$a1' => 'float|int',
                    '$a2' => 'float|int',
                    '$a3' => 'array<never, never>',
                    '$b1' => 'float|int',
                    '$b2' => 'float|int',
                    '$b3' => 'float|int',
                    '$c1' => 'float|int',
                    '$c2' => 'float|int',
                    '$c3' => 'float|int',
                    '$d1' => 'float|int',
                    '$d2' => 'float|int',
                    '$d3' => 'float|int',
                    '$e1' => 'float|int',
                    '$e2' => 'float|int',
                    '$e3' => 'float|int',
                    '$f1' => 'float|int',
                    '$f2' => 'float|int',
                    '$f3' => 'float|int',
                ],
                'ignored_issues' => ['InvalidOperand'],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'badAddition' => [
                'code' => '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand',
            ],
            'addArrayToNumber' => [
                'code' => '<?php
                    $a = [1] + 1;',
                'error_message' => 'InvalidOperand',
            ],
            'concatenateNegativeIntRightSideIsNotNumeric' => [
                'code' => '<?php
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
            'additionWithClassInWeakMode' => [
                'code' => '<?php
                    $a = "hi" + (new stdClass);',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidOperand' => [
                'code' => '<?php
                    $b = rand(0, 1) ? [] : 4;
                    echo $b + 5;',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'possiblyInvalidConcat' => [
                'code' => '<?php
                    $b = rand(0, 1) ? [] : "hello";
                    echo $b . "goodbye";',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidGMPOperation' => [
                'code' => '<?php
                    $a = gmp_init(2);
                    $b = "a" + $a;',
                'error_message' => 'InvalidOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot add GMP to non-numeric type',
            ],
            'stringIncrement' => [
                'code' => '<?php
                    $a = "hello";
                    $a++;',
                'error_message' => 'StringIncrement',
            ],
            'falseIncrement' => [
                'code' => '<?php
                    $a = false;
                    $a++;',
                'error_message' => 'FalseOperand',
            ],
            'trueIncrement' => [
                'code' => '<?php
                    $a = true;
                    $a++;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyDivByZero' => [
                'code' => '<?php
                    $a = 5 / (rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyNullOperand',
            ],
            'invalidExponent' => [
                'code' => '<?php
                    $a = "x" ^ 1;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseOr' => [
                'code' => '<?php
                    $a = "x" | new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseNot' => [
                'code' => '<?php
                    $a = ~new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidBitwiseNot' => [
                'code' => '<?php
                    $a = ~(rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidBooleanBitwiseNot' => [
                'code' => '<?php
                    $a = ~true;',
                'error_message' => 'InvalidOperand',
            ],
            'substrImpossible' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
