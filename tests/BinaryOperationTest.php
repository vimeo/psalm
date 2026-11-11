<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;
use function implode;
use function range;
use function sort;

use const DIRECTORY_SEPARATOR;
use const SORT_STRING;

final class BinaryOperationTest extends TestCase
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
        $config->allow_bool_to_literal_bool_comparison = false;

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
        $config->allow_bool_to_literal_bool_comparison = false;

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

    public function testPowOfDifferingNumericLiteralTypesInStrictMode(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    $a = 1.5 ** 10;
                    $b = 2 ** 0.5;
                    $c = 2 ** 3;',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPowOfDifferingNumericTypesInStrictMode(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    /**
                     * @param int|float $number
                     * @param numeric $numeric
                     */
                    function f(int $int, float $float, $number, $numeric): void {
                        echo $int ** $float;
                        echo $float ** $int;
                        echo $number ** $int;
                        echo $numeric ** $int;
                        $int **= $float;
                        $float **= $int;
                    }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPowOfNonNumericTypesInStrictModeIsStillReported(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    function f(string $s): void {
                        echo $s ** 2;
                    }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testDifferingNumericTypesMultiplicationInStrictModeIsStillReported(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    function f(int $int, float $float): void {
                        echo $int * $float;
                    }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithIntInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    $a = "hi" . 5;',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithArrayKeyInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    interface I {
                        /** @return array-key */
                        public function t();
                    }

                    function test(I $i): void {
                        $a = "hi" . $i->t();
                    }
                    ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithAllowedUnionTypeInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    interface I {
                        /** @return int|string */
                        public function t();
                    }

                    function test(I $i): void {
                        $a = "hi" . $i->t();
                    }
                    ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithBadUnionTypeInStrictMode(): void
    {
        $config = Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                    interface I {
                        /** @return int|float */
                        public function t();
                    }

                    function test(I $i): void {
                        $a = "hi" . $i->t();
                    }
                    ',
        );
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testConcatenationWithIgnoredNullOrFalseInStrictMode(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @return int|string|null
                 * @psalm-ignore-nullable-return
                 */
                function nullableUnion() {
                    return rand(0, 1) ? 80 : (rand(0, 1) ? "http" : null);
                }

                /**
                 * @return int|string|false
                 * @psalm-ignore-falsable-return
                 */
                function falsableUnion() {
                    return rand(0, 1) ? 80 : (rand(0, 1) ? "http" : false);
                }

                /**
                 * @return int|null
                 * @psalm-ignore-nullable-return
                 */
                function nullableInt() {
                    return rand(0, 1) ? 80 : null;
                }

                echo "a: " . nullableUnion();
                echo nullableUnion() . " is a";
                echo "b: " . falsableUnion();
                echo falsableUnion() . " is b";
                echo "c: " . nullableInt();',
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->addToAssertionCount(1);
    }

    public function testConcatenationWithNullOrFalseOnlyReportsNullAndFalseOperand(): void
    {
        $this->testConfig->throw_exception = false;
        $this->testConfig->strict_binary_operands = true;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /**
                 * @param int|string|null $b
                 * @param int|false $c
                 * @param int|string|false $d
                 */
                function test(?int $a, $b, $c, $d): void {
                    echo "a: " . $a;
                    echo "b: " . $b;
                    echo "c: " . $c;
                    echo "d: " . $d;
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [8, 'PossiblyNullOperand'],
                [9, 'PossiblyNullOperand'],
                [10, 'PossiblyFalseOperand'],
                [11, 'PossiblyFalseOperand'],
            ],
            array_map(
                static fn(IssueData $issue): array => [$issue->line_from, $issue->type],
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testConcatenationWithIgnoredNullableFloatInStrictMode(): void
    {
        Config::getInstance()->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @return float|null
                 * @psalm-ignore-nullable-return
                 */
                function ratio() {
                    return rand(0, 1) ? 0.5 : null;
                }

                echo "ratio: " . ratio();',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage(
            'InvalidOperand - somefile.php:10:34 - Cannot concatenate with a float|null',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullableLeftOperandOfSmallerThan(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i): bool {
                    return $i < 10;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullOperand - somefile.php:3:28 - Left operand cannot be nullable, got int|null');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullableRightOperandOfGreaterThan(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i): bool {
                    return 10 > $i;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullOperand - somefile.php:3:33 - Right operand cannot be nullable, got int|null');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullableOperandOfSmallerOrEqual(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i): bool {
                    return $i <= 10;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullableOperandOfGreaterOrEqual(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i): bool {
                    return $i >= 10;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('PossiblyNullOperand');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullOperandOfComparison(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(): bool {
                    return null < 10;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NullOperand - somefile.php:3:28 - Left operand cannot be null');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullRightOperandOfComparison(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(): bool {
                    return 10 < null;
                }',
        );

        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('NullOperand - somefile.php:3:33 - Right operand cannot be null');

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNullableComparisonIsAllowedByDefault(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i): bool {
                    return $i < 10 || 20 <= $i;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->addToAssertionCount(1);
    }

    public function testNarrowedComparisonInStrictNullableMode(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i, int $j): bool {
                    if ($i === null) {
                        return false;
                    }
                    return $i < 10 && $j >= 5 && $i == null;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->addToAssertionCount(1);
    }

    public function testNullEqualityIsAllowedInStrictNullableMode(): void
    {
        Config::getInstance()->strict_nullable_comparison = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function f(?int $i, ?int $j): bool {
                    return $i == $j || $i === null || $i != null || $i !== $j;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->addToAssertionCount(1);
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

    /**
     * @param list<int> $suffixes
     * @psalm-pure
     */
    private static function sortedLiteralUnion(string $prefix, array $suffixes): string
    {
        $literals = array_map(static fn(int $suffix): string => $prefix . $suffix . "'", $suffixes);
        sort($literals, SORT_STRING);

        return implode('|', $literals);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'assignmentInTheRightOperandOfAndMayNotHaveRun' => [
                'code' => '<?php
                    function fetch(): ?float {
                        return rand(0, 1) ? 1.5 : null;
                    }

                    function check(bool $enabled): ?float {
                        $value = 0.0;
                        $matched = $enabled && null !== ($value = fetch());

                        return $value;
                    }

                    $result = check(true);',
                'assertions' => [
                    '$result===' => 'float|null',
                ],
            ],
            'assignmentInTheRightOperandOfAndKeepsTheTypeBeforeIt' => [
                'code' => '<?php
                    function fetch(): ?float {
                        return rand(0, 1) ? 1.5 : null;
                    }

                    function check(bool $enabled): void {
                        $value = 0;
                        $matched = $enabled && null !== ($value = fetch());
                        /** @psalm-check-type-exact $value = 0|float|null */;
                    }',
            ],
            'coalesceOfAStaticAndASelfPropertyOffset' => [
                'code' => '<?php
                    class Test1
                    {
                        protected static array $var = ["item" => "Test1"];

                        public static function testmethod(string $item): string
                        {
                            return (string) (static::$var[$item] ?? self::$var[$item] ?? "none");
                        }
                    }

                    class Test2 extends Test1
                    {
                        protected static array $var = ["item" => "Test2"];
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'issetOfAStaticPropertyOffsetNarrowsTheSameFetch' => [
                'code' => '<?php
                    class Cache
                    {
                        protected static array $items = [];

                        public static function get(string $key): string
                        {
                            if (isset(static::$items[$key])) {
                                return (string) static::$items[$key];
                            }

                            return "none";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'offsetsOfAStaticPropertyAreKeptWhenAnotherOffsetOfItIsAssigned' => [
                'code' => '<?php
                    final class Registry
                    {
                        /** @var array<string, array<string, int>> */
                        private static array $cache = [];

                        public static function get(string $class): int
                        {
                            self::$cache[$class] = ["x" => 1];
                            self::$cache[$class]["y"] = 2;
                            $entry = self::$cache[$class];
                            /** @psalm-check-type-exact $entry = array{x: 1, y: 2} */
                            $value = self::$cache[$class]["x"];
                            /** @psalm-check-type-exact $value = 1 */

                            return $value;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'staticPropertyOfATraitIsIndexedByStaticClassInTheClassUsingIt' => [
                'code' => '<?php
                    interface Coll {}

                    trait HasCollection
                    {
                        /** @var class-string<Coll> */
                        protected static string $collectionClass;

                        /** @var array<class-string<static>, class-string<Coll>> */
                        protected static array $resolved = [];

                        public function newCollection(): Coll
                        {
                            static::$resolved[static::class] ??= static::$collectionClass;

                            return new static::$resolved[static::class]();
                        }
                    }

                    abstract class Model
                    {
                        use HasCollection;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPropertyOfATraitCheckedBeforeItIsCalled' => [
                'code' => '<?php
                    trait ResolvesDumpSource
                    {
                        /** @var callable|null|false */
                        protected static $resolver;

                        public function resolve(): mixed
                        {
                            if (static::$resolver === false) {
                                return null;
                            }

                            if (static::$resolver) {
                                return call_user_func(static::$resolver);
                            }

                            return null;
                        }
                    }

                    abstract class Model
                    {
                        use ResolvesDumpSource;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'staticPropertyGuardedByANullCheckIsNotNullAfterIt' => [
                'code' => '<?php
                    interface Container {}

                    class Locator
                    {
                        /** @var Container|null */
                        protected static $container;

                        public static function getContainer(): Container
                        {
                            if (static::$container === null) {
                                throw new RuntimeException("not initialized");
                            }

                            return static::$container;
                        }

                        public static function getSelfContainer(): Container
                        {
                            if (self::$container === null) {
                                throw new RuntimeException("not initialized");
                            }

                            return self::$container;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'interpolatedStringWithAnIntIsNotALiteralUnion' => [
                'code' => '<?php
                    /** @var "a"|"b" $lit */
                    $lit = "a";
                    /** @var int $n */
                    $n = 5;
                    $g = "x$lit$n";',
                'assertions' => [
                    '$g===' => "non-falsy-string",
                ],
            ],
            'interpolatedStringWithALiteralInt' => [
                'code' => '<?php
                    /** @var "a"|"b" $lit */
                    $lit = "a";
                    $g = "x$lit" . 5;',
                'assertions' => [
                    '$g===' => "'xa5'|'xb5'",
                ],
            ],
            'interpolatedStringWithSeveralUnions' => [
                'code' => '<?php
                    /** @var "a"|"b" $lit */
                    $lit = "a";
                    $g = "x{$lit}y$lit";',
                'assertions' => [
                    '$g===' => "'xaya'|'xayb'|'xbya'|'xbyb'",
                ],
            ],
            'interpolatedStringKeepsTheLiteralsOfAUnion' => [
                'code' => '<?php
                    /** @var "a"|"b" $lit */
                    $lit = "a";
                    $g = "x$lit";
                    $h = "x" . $lit;',
                'assertions' => [
                    '$g===' => "'xa'|'xb'",
                    '$h===' => "'xa'|'xb'",
                ],
            ],
            'zeroToANegativePowerIsAFloat' => [
                'code' => '<?php
                    $a = 0 ** -1;
                    $b = 0.0 ** -2;
                    $c = 0 ** -INF;
                    echo $a, $b, $c;',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'float',
                    '$c' => 'float',
                ],
            ],
            'floatOperandsOutsideTheRangeOfAnIntAreNotFolded' => [
                'code' => '<?php
                    $notANumberModulo = NAN % 3;
                    $infiniteModulo = 3 % INF;
                    $modulo = 7.0 % 4;
                    $bigOr = 1e30 | 1;
                    $infiniteAnd = INF & 1;
                    $notANumberXor = 1 ^ NAN;
                    $bigShiftLeft = 1 << 1e30;
                    $bigShiftRight = -1e30 >> 1;
                    $or = 6.0 | 1;
                    $notANumberNot = ~NAN;
                    $bigNot = ~1e30;
                    $infiniteNot = ~-INF;
                    $not = ~5.0;',
                'assertions' => [
                    '$notANumberModulo===' => 'int',
                    '$infiniteModulo===' => 'int',
                    '$modulo===' => '3',
                    '$bigOr===' => 'int',
                    '$infiniteAnd===' => 'int',
                    '$notANumberXor===' => 'int',
                    '$bigShiftLeft===' => 'int',
                    '$bigShiftRight===' => 'int',
                    '$or===' => '7',
                    '$notANumberNot===' => 'int',
                    '$bigNot===' => 'int',
                    '$infiniteNot===' => 'int',
                    '$not===' => '-6',
                ],
            ],

            'integerOperatorsGiveIntsWhateverTheOperandsAre' => [
                'code' => '<?php
                    /** @var int $int */
                    $int = 1;
                    /** @var float $float */
                    $float = 1.5;
                    /** @var int|float $number */
                    $number = 1;

                    $shiftedByFloat = $int << 1.0;
                    $orWithFloat = $int | 1.0;
                    $andOfFloatAndInt = $float & $int;
                    $xorOfFloats = $float ^ $float;
                    $shiftedFloat = $float >> $float;
                    $orWithNumber = $number | 1;
                    $shiftedNumber = $number << $int;
                    $moduloOfFloat = $int % 1.0;
                    $shiftedBig = (int) 1e30 << 1.0;
                    $orOfBig = (1e30 | 1) | 1.0;',
                'assertions' => [
                    '$shiftedByFloat===' => 'int',
                    '$orWithFloat===' => 'int',
                    '$andOfFloatAndInt===' => 'int',
                    '$xorOfFloats===' => 'int',
                    '$shiftedFloat===' => 'int',
                    '$orWithNumber===' => 'int',
                    '$shiftedNumber===' => 'int',
                    '$moduloOfFloat===' => 'int',
                    '$shiftedBig===' => 'int',
                    '$orOfBig===' => 'int',
                ],
            ],

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
            'concatenationNonFalsyLiteralStringWithString' => [
                'code' => '<?php
                    function foo(): string {}
                    $a = "Hey " . foo();',
                'assertions' => [
                    '$a===' => 'non-falsy-string',
                ],
                'ignored_issues' => ['InvalidReturnType'],
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
            'concatenateStringableUsesDeclaredStringReturn' => [
                'code' => '<?php
                    final class Text {
                        public function __toString(): string {
                            return "text";
                        }
                    }

                    /** @var lowercase-string $lowercase */
                    $lowercase = "lowercase";
                    $prefixed = "abc" . new Text();
                    $lowercasePrefixed = $lowercase . new Text();',
                'assertions' => [
                    '$prefixed===' => 'non-falsy-string',
                    '$lowercasePrefixed===' => 'string',
                ],
                'ignored_issues' => ['ImplicitToStringCast'],
                'php_version' => '8.1',
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
            'bitwiseOperationsOfMasksInALoopStayMasks' => [
                'code' => '<?php
                    final class Flags {
                        public const A = 1;
                        public const B = 2;
                        public const C = 4;
                        public const D = 8;
                    }

                    /**
                     * @param list<int-mask-of<Flags::*>> $masks
                     * @return int-mask-of<Flags::*>
                     */
                    function combine(array $masks): int {
                        $combined = 0;
                        foreach ($masks as $mask) {
                            if ($mask === 0) {
                                $combined |= Flags::A | Flags::B;
                                continue;
                            }

                            $combined |= $mask;
                            $combined &= Flags::A | Flags::C | Flags::D;
                            $combined ^= Flags::B;
                        }

                        return $combined;
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
            'intArithmeticAssignmentAdditionFromFunction' => [
                'code' => '<?php
                    function intFunc(): int {
                        return 5;
                    }
                    $a = 1;
                    $a += intFunc();',
                'assertions' => [
                    '$a===' => 'int',
                ],
            ],
            'intArithmeticAssignmentAddition' => [
                'code' => '<?php
                    $a = 1;
                    $a += 5;',
                'assertions' => [
                    '$a===' => '6',
                ],
            ],
            'intArithmeticAssignmentSubtractionFromFunction' => [
                'code' => '<?php
                    function intFunc(): int {
                        return 5;
                    }
                    $a = 8;
                    $a -= intFunc();',
                'assertions' => [
                    '$a===' => 'int',
                ],
            ],
            'intArithmeticAssignmentSubtraction' => [
                'code' => '<?php
                    $a = 8;
                    $a -= 5;',
                'assertions' => [
                    '$a===' => '3',
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
            'interpolatedStringIntAndHeredocParts' => [
                'code' => '<?php
                    /**
                     * @param positive-int $pos
                     * @param negative-int $neg
                     */
                    function foo(int $pos, int $neg, int $any, string $any_string) : void {
                        $positive = "$pos$any_string";
                        $negative = "{$neg}x";
                        $two_ints = "{$any}{$any}";
                        $one_int = "{$any}$any_string";
                        $heredoc = <<<TXT
                        -$any_string
                        TXT;
                        echo $positive, $negative, $two_ints, $one_int, $heredoc;

                        /** @psalm-check-type-exact $positive = non-falsy-string */
                        /** @psalm-check-type-exact $negative = non-falsy-string */
                        /** @psalm-check-type-exact $two_ints = non-falsy-string */
                        /** @psalm-check-type-exact $one_int = non-empty-string */
                        /** @psalm-check-type-exact $heredoc = non-falsy-string */
                    }',
            ],
            'interpolatedStringNonFalsyParts' => [
                'code' => '<?php
                    /**
                     * @param non-falsy-string $falsy
                     * @param non-empty-string $empty
                     */
                    function foo(string $falsy, string $empty, string $any) : void {
                        $dashed = "-$falsy";
                        $joined = "$falsy$any";
                        $two = "$empty$empty";
                        $zero = "0$any";
                        $suffixed = "$any-";
                        $single = "$empty";
                        echo $dashed, $joined, $two, $zero, $suffixed, $single;

                        /** @psalm-check-type-exact $dashed = non-falsy-string */
                        /** @psalm-check-type-exact $joined = non-falsy-string */
                        /** @psalm-check-type-exact $two = non-falsy-string */
                        /** @psalm-check-type-exact $zero = non-empty-string */
                        /** @psalm-check-type-exact $suffixed = non-falsy-string */
                        /** @psalm-check-type-exact $single = non-empty-string */
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
                'assertions' => ['$interpolated===' => "''|'bar'|'foo'|'foobar'"],
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
            'incrementInLoop' => [
                'code' => '<?php
                    for ($i = 0; $i < 10; $i++) {
                        if (rand(0,1)) {
                            break;
                        }
                    }
                    for ($j = 100; $j < 110; $j++) {
                        if (rand(0,1)) {
                            break;
                        }
                    }',
                'assertions' => [
                    '$i' => 'int<0, 10>',
                    '$j' => 'int<100, 110>',
                ],
            ],
            'decrementInLoop' => [
                'code' => '<?php
                    for ($i = 10; $i > 0; $i--) {
                        if (rand(0,1)) {
                            break;
                        }
                    }
                    for ($j = 110; $j > 100; $j--) {
                        if (rand(0,1)) {
                            break;
                        }
                    }',
                'assertions' => [
                    '$i' => 'int<0, 10>',
                    '$j' => 'int<100, 110>',
                ],
            ],
            'coalesceStatementWithThrowOrExitNarrowsTheLeftOperand' => [
                'code' => '<?php
                    /** @var ?string $a */
                    $a = null;
                    $a ?? throw new RuntimeException();
                    $afterThrow = $a;

                    /** @var ?string $b */
                    $b = null;
                    $b ?? exit(1);
                    $afterExit = $b;',
                'assertions' => [
                    '$afterThrow===' => 'string',
                    '$afterExit===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'ternaryWithAThrowingBranchNarrowsByTheOtherBranch' => [
                'code' => '<?php
                    /** @var ?string $a */
                    $a = null;
                    $a !== null ? 1 : throw new RuntimeException();
                    $afterElseThrows = $a;

                    /** @var ?string $b */
                    $b = null;
                    isset($b) ? 1 : throw new RuntimeException();
                    $afterIsset = $b;

                    /** @var ?string $c */
                    $c = null;
                    $c === null ? throw new RuntimeException() : 1;
                    $afterIfThrows = $c;',
                'assertions' => [
                    '$afterElseThrows===' => 'string',
                    '$afterIsset===' => 'string',
                    '$afterIfThrows===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'coalesceWithThrowNarrowsAnArrayOffsetAndACallArgument' => [
                'code' => '<?php
                    /** @var array{k?: string} $arr */
                    $arr = [];
                    $arr["k"] ?? throw new RuntimeException();
                    $offset = $arr["k"];

                    /** @var ?int $n */
                    $n = null;
                    $length = abs($n ?? throw new RuntimeException());
                    $afterCall = $n;',
                'assertions' => [
                    '$offset===' => 'string',
                    '$afterCall===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'ternaryWithAThrowingBranchKeepsTheAssignmentOfTheOtherBranch' => [
                'code' => '<?php
                    /** @var bool $flag */
                    $flag = true;
                    $flag ? ($made = 5) : throw new RuntimeException();
                    $afterAssignment = $made;',
                'assertions' => [
                    '$afterAssignment===' => '5',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'elvisWithThrowNarrowsByTheTypeCheck' => [
                'code' => '<?php
                    /** @var mixed $n */
                    $n = 1;
                    is_numeric($n) ?: throw new RuntimeException();
                    $afterElvis = $n;',
                'assertions' => [
                    '$afterElvis===' => 'numeric',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'ternaryWithoutAThrowingBranchKeepsTheOriginalType' => [
                'code' => '<?php
                    /** @var ?string $a */
                    $a = null;
                    $a !== null ? 1 : 2;
                    $unchanged = $a;',
                'assertions' => [
                    '$unchanged===' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
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
            'concatMixedWithNonFalsyLiteralReturnsNonFalsyString' => [
                'code' => '<?php
                    /** @var mixed $m */
                    $m = 1;

                    $a = "<?xml encoding=\"UTF-8\">" . $m;
                    $b = $m . "<?xml encoding=\"UTF-8\">";',
                'assertions' => [
                    '$a===' => 'non-falsy-string',
                    '$b===' => 'non-falsy-string',
                ],
                'ignored_issues' => ['MixedOperand'],
            ],
            'concatMixedWithNonFalsyStringReturnsNonFalsyString' => [
                'code' => '<?php
                    /** @var mixed $m */
                    $m = 1;
                    /** @var non-falsy-string $s */
                    $s = "ab";

                    $a = $s . $m;
                    $b = $m . $s;',
                'assertions' => [
                    '$a===' => 'non-falsy-string',
                    '$b===' => 'non-falsy-string',
                ],
                'ignored_issues' => ['MixedOperand'],
            ],
            'concatMixedWithNonEmptyStringReturnsNonEmptyString' => [
                'code' => '<?php
                    /** @var mixed $m */
                    $m = 1;
                    /** @var non-empty-string $s */
                    $s = "0";

                    $a = $s . $m;
                    $b = $m . $s;
                    $c = "0" . $m;',
                'assertions' => [
                    '$a===' => 'non-empty-string',
                    '$b===' => 'non-empty-string',
                    '$c===' => 'non-empty-string',
                ],
                'ignored_issues' => ['MixedOperand'],
            ],
            'concatMixedWithPossiblyEmptyStringReturnsString' => [
                'code' => '<?php
                    /** @var mixed $m */
                    $m = 1;
                    /** @var string $s */
                    $s = "";

                    $a = $s . $m;
                    $b = "" . $m;
                    $c = $m . $m;',
                'assertions' => [
                    '$a===' => 'string',
                    '$b===' => 'string',
                    '$c===' => 'string',
                ],
                'ignored_issues' => ['MixedOperand'],
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
            'concatenatesLiteralsWithSmallIntRanges' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @var 'a'|'b' $a
                     * @var int<0, 2> $c
                     * @var int<-1, 1> $negative
                     * @var int<0, 1> $bit
                     * @var int<0, 62> $widest
                     * @var int<0, 63> $tooWide
                     * @var int<0, max> $unbounded
                     * @var int<-9223372036854775808, 9223372036854775807> $everything
                     */
                    $right = $a . $c;
                    $left = $c . $a;
                    $both = $bit . $bit;
                    $minus = $a . $negative;
                    $fits = '.' . $widest;
                    $wide = '.' . $tooWide;
                    $open = $a . $unbounded;
                    $all = $a . $everything;
                    PHP,
                'assertions' => [
                    '$right===' => "'a0'|'a1'|'a2'|'b0'|'b1'|'b2'",
                    '$left===' => "'0a'|'0b'|'1a'|'1b'|'2a'|'2b'",
                    '$both===' => "'00'|'01'|'10'|'11'",
                    '$minus===' => "'a-1'|'a0'|'a1'|'b-1'|'b0'|'b1'",
                    '$fits===' => self::sortedLiteralUnion("'.", range(0, 62)),
                    '$wide===' => 'non-empty-lowercase-string',
                    '$open===' => 'non-empty-lowercase-string',
                    '$all===' => 'non-empty-lowercase-string',
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
            'firstClassCallableInSubstrComparison' => [
                'code' => '<?php
                    if (substr(...) === "x") {}
                    if (substr(...) != "x") {}',
                'assertions' => [],
                'ignored_issues' => ['TypeDoesNotContainType', 'RedundantCondition'],
                'php_version' => '8.1',
            ],
            'arithmeticOnATemplateBoundedByIntOrFloat' => [
                'code' => '<?php
                    /**
                     * @template TNumeric as int|float
                     * @param TNumeric $n
                     * @return float|int
                     */
                    function sum(int|float $n) {
                        return 0 + $n;
                    }

                    /**
                     * @template TNumeric as int|float
                     * @param TNumeric $n
                     * @return float
                     */
                    function multiplyByAFloat(int|float $n): float {
                        return $n * 2.5;
                    }

                    /**
                     * @template TNumeric as int|float
                     * @param TNumeric $n
                     * @return float
                     */
                    function subtractFromAFloat(int|float $n): float {
                        return 1.5 - $n;
                    }

                    /**
                     * @template TNumeric as int|float
                     * @param TNumeric $n
                     * @return int
                     */
                    function remainder(int|float $n): int {
                        return $n % 2;
                    }

                    /**
                     * @template A as int|float
                     * @template B as int|float
                     * @param A $a
                     * @param B $b
                     * @return float|int
                     */
                    function sumOfTwo(int|float $a, int|float $b) {
                        return $a + $b;
                    }

                    /**
                     * @template T as int
                     * @param T $n
                     * @return int
                     */
                    function addToAnInt(int $n): int {
                        return $n + 1;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'interpolatedStringEndingInZeroIsNotNonFalsy' => [
                'code' => '<?php
                    /** @return non-falsy-string */
                    function foo(string $s) : string {
                        return "{$s}0";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'assignmentToSelfStaticPropertyForgetsTheNarrowingOfStaticOne' => [
                'code' => '<?php
                    interface Container {}

                    class Locator
                    {
                        /** @var Container|null */
                        protected static $container;

                        public static function getContainer(): Container
                        {
                            if (static::$container === null) {
                                throw new RuntimeException("not initialized");
                            }

                            self::$container = null;

                            return static::$container;
                        }
                    }',
                'error_message' => 'NullableReturnStatement',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'bcMathNumberPlusNonNumericString' => [
                'code' => '<?php
                    namespace BcMath {
                        final class Number {
                            public function __construct(string $number) {}
                        }
                    }

                    namespace {
                        $a = new \\BcMath\\Number("2");
                        $b = $a + "abc";
                    }',
                'error_message' => 'InvalidOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:30 - Cannot add BcMath\\Number to',
            ],
            'bcMathNumberPlusFloat' => [
                'code' => '<?php
                    namespace BcMath {
                        final class Number {
                            public function __construct(string $number) {}
                        }
                    }

                    namespace {
                        $a = new \\BcMath\\Number("2");
                        $b = $a + 0.5;
                    }',
                'error_message' => 'InvalidOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:30 - Cannot add BcMath\\Number to float',
            ],
            'shiftLeftByANegativeNumber' => [
                'code' => '<?php
                    $a = 1 << -1;
                    echo $a;',
                'error_message' => 'NoValue',
            ],
            'shiftRightByANegativeNumber' => [
                'code' => '<?php
                    $a = 1 >> -2;
                    echo $a;',
                'error_message' => 'NoValue',
            ],
            'moduloOfABigFloatLiteralByZeroIsNever' => [
                'code' => '<?php
                    $a = 1e30 % 0;',
                'error_message' => 'NoValue',
            ],

            'moduloOfNotANumberByAFractionIsNever' => [
                'code' => '<?php
                    $a = NAN % 0.5;',
                'error_message' => 'NoValue',
            ],

            'badAddition' => [
                'code' => '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand',
            ],
            'shiftLeftByANegativeLiteralThrows' => [
                'code' => '<?php
                    $a = 1 << -1;',
                'error_message' => 'NoValue',
            ],
            'shiftRightByANegativeLiteralThrows' => [
                'code' => '<?php
                    $a = 8 >> -2;',
                'error_message' => 'NoValue',
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
                'error_message' => 'StringIncrement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - Incrementing a non-numeric string is deprecated since PHP 8.5, use str_increment() instead',
            ],
            'stringIncrementOnPhp85' => [
                'code' => '<?php
                    function next_id(string $id): string {
                        return ++$id;
                    }',
                'error_message' => 'StringIncrement - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34 - Incrementing a non-numeric string is deprecated since PHP 8.5, use str_increment() instead',
                'error_levels' => [],
                'php_version' => '8.5',
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
            'arithmeticOnATemplateBoundedByAStringIsStillMixed' => [
                'code' => '<?php
                    /**
                     * @template T as int|string
                     * @param T $n
                     */
                    function f(int|string $n): void {
                        echo 1 + $n;
                    }',
                'error_message' => 'MixedOperand',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function testBcMathNumberOperations(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
                namespace BcMath {
                    final class Number {
                        public function __construct(string $number) {}
                    }
                }

                namespace {
                    $a = new \BcMath\Number("2");
                    $b = new \BcMath\Number("4");
                    $c = $a + $b;
                    $d = $a - 1;
                    $e = 2 * $b;
                    $f = $a / "3";
                    $g = $a % $b;
                    $h = $a ** 2;
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach (['$c', '$d', '$e', '$f', '$g', '$h'] as $var) {
            $actual_vars[$var] = (string) $context->vars_in_scope[$var];
        }

        $this->assertSame(
            ['$c' => 'BcMath\\Number', '$d' => 'BcMath\\Number', '$e' => 'BcMath\\Number',
                '$f' => 'BcMath\\Number', '$g' => 'BcMath\\Number', '$h' => 'BcMath\\Number'],
            $actual_vars,
        );
    }
}
