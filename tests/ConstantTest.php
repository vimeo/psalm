<?php

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function getcwd;

use const DIRECTORY_SEPARATOR;

class ConstantTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    // TODO: Waiting for https://github.com/vimeo/psalm/issues/7125
    // public function testKeyofSelfConstDoesntImplyKeyofStaticConst(): void
    // {
    //     $this->expectException(CodeException::class);
    //     $this->expectExceptionMessage("PossiblyUndefinedIntArrayOffset");

    //     $this->testConfig->ensure_array_int_offsets_exist = true;

    //     $file_path = getcwd() . '/src/somefile.php';

    //     $this->addFile(
    //         $file_path,
    //         '<?php
    //             class Foo
    //             {
    //                 /** @var array<int, int> */
    //                 public const CONST = [1, 2, 3];

    //                 /**
    //                  * @param key-of<self::CONST> $key
    //                  */
    //                 public function bar(int $key): int
    //                 {
    //                     return static::CONST[$key];
    //                 }
    //             }
    //         '
    //     );

    //     $this->analyzeFile($file_path, new Context());
    // }

    public function testUseObjectConstant(): void
    {
        $file1 = getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'file1.php';
        $file2 = getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'file2.php';

        $this->addFile(
            $file1,
            '<?php
                namespace Foo;

                final class Bar {}
                const bar = new Bar();
            ',
        );

        $this->addFile(
            $file2,
            '<?php
                namespace Baz;

                use Foo\Bar;
                use const Foo\bar;

                require("tests/file1.php");

                function bar(): Bar
                {
                    return bar;
                }
            ',
        );

        $this->analyzeFile($file1, new Context());
        $this->analyzeFile($file2, new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'constantInFunction' => [
                'code' => '<?php
                    useTest();
                    const TEST = 2;

                    function useTest(): int {
                        return TEST;
                    }',
            ],
            'constantInClosure' => [
                'code' => '<?php
                    const TEST = 2;

                    $useTest = function(): int {
                        return TEST;
                    };
                    $useTest();',
            ],
            'constantDefinedInFunction' => [
                'code' => '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    defineConstant();

                    echo CONSTANT;',
            ],
            'magicConstant' => [
                'code' => '<?php
                    $a = __LINE__;
                    $b = __file__;',
                'assertions' => [
                    '$a' => 'int<1, max>',
                    '$b' => 'string',
                ],
            ],
            'getClassConstantValue' => [
                'code' => '<?php
                    class A {
                        const B = [0, 1, 2];
                    }

                    $a = A::B[1];',
            ],
            'staticConstEval' => [
                'code' => '<?php
                    abstract class Enum {
                        /**
                         * @var string[]
                         */
                        protected const VALUES = [];
                        public static function export(): string
                        {
                            assert(!empty(static::VALUES));
                            $values = array_map(
                                function(string $val): string {
                                    return "\'" . $val . "\'";
                                },
                                static::VALUES
                            );
                            return join(",", $values);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'undefinedConstant' => [
                'code' => '<?php
                    switch (rand(0, 50)) {
                        case FORTY: // Observed a valid UndeclaredConstant warning
                            $x = "value";
                            break;
                        default:
                            $x = "other";
                        }

                        echo $x;',
                'assertions' => [],
                'ignored_issues' => ['UndefinedConstant'],
            ],
            'suppressUndefinedClassConstant' => [
                'code' => '<?php
                    class C {}

                    /** @psalm-suppress UndefinedConstant */
                    $a = POTATO;

                    /** @psalm-suppress UndefinedConstant */
                    $a = C::POTATO;',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'hardToDefineClassConstant' => [
                'code' => '<?php
                    class A {
                        const C = [
                            self::B => 4,
                            "name" => 3
                        ];

                        const B = 4;
                    }

                    echo A::C[4];',
            ],
            'sameNamedConstInOtherClass' => [
                'code' => '<?php
                    class B {
                        const B = 4;
                    }
                    class A {
                        const B = "four";
                        const C = [
                            B::B => "one",
                        ];
                    }

                    echo A::C[4];',
            ],
            'onlyMatchingConstantOffset' => [
                'code' => '<?php
                    class A {
                        const KEYS = ["one", "two", "three"];
                        const ARR = [
                            "one" => 1,
                            "two" => 2
                        ];
                    }

                    foreach (A::KEYS as $key) {
                        if (isset(A::ARR[$key])) {
                            echo A::ARR[$key];
                        }
                    }',
            ],
            'stringArrayOffset' => [
                'code' => '<?php
                    class A {
                        const C = [
                            "a" => 1,
                            "b" => 2,
                        ];
                    }

                    function foo(string $s) : void {
                        if (!isset(A::C[$s])) {
                            return;
                        }

                        if ($s === "Hello") {}
                    }',
            ],
            'noExceptionsOnMixedArrayKey' => [
                'code' => '<?php
                    function finder(string $id) : ?object {
                      if (rand(0, 1)) {
                        return new A();
                      }

                      if (rand(0, 1)) {
                        return new B();
                      }

                      return null;
                    }
                    class A {}
                    class B {}
                    class Foo
                    {
                        private const TYPES = [
                            "type1" => A::class,
                            "type2" => B::class,
                        ];

                        public function bar(array $data): void
                        {
                            if (!isset(self::TYPES[$data["type"]])) {
                                throw new \InvalidArgumentException("Unknown type");
                            }

                            $class = self::TYPES[$data["type"]];

                            $ret = finder($data["id"]);

                            if (!$ret || !$ret instanceof $class) {
                                throw new \InvalidArgumentException;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument', 'MixedArrayOffset', 'MixedAssignment'],
            ],
            'lateConstantResolution' => [
                'code' => '<?php
                    class A {
                        const FOO = "foo";
                    }

                    class B {
                        const BAR = [
                            A::FOO
                        ];
                        const BAR2 = A::FOO;
                    }

                    $a = B::BAR[0];
                    $b = B::BAR2;',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                ],
            ],
            'lateConstantResolutionParentArrayPlus' => [
                'code' => '<?php
                    class A {
                        /** @var array{a: true, ...} */
                        public const ARR = ["a" => true];
                    }

                    class B extends A {
                        /** @var array{a: true, b: true, ...} */
                        public const ARR = parent::ARR + ["b" => true];
                    }

                    class C extends B {
                        public const ARR = parent::ARR + ["c" => true];
                    }

                    /** @param array{a: true, b: true, c: true} $arg */
                    function foo(array $arg): void {}
                    foo(C::ARR);
                ',
            ],
            'lateConstantResolutionParentArraySpread' => [
                'code' => '<?php
                    class A {
                        /** @var list{"a", ...} */
                        public const ARR = ["a"];
                    }

                    class B extends A {
                        /** @var list{"a", "b", ...} */
                        public const ARR = [...parent::ARR, "b"];
                    }

                    class C extends B {
                        public const ARR = [...parent::ARR, "c"];
                    }

                    /** @param array{"a", "b", "c"} $arg */
                    function foo(array $arg): void {}
                    foo(C::ARR);
                ',
            ],
            'lateConstantResolutionParentStringConcat' => [
                'code' => '<?php
                    class A {
                        /** @var non-empty-string */
                        public const STR = "a";
                    }

                    class B extends A {
                        /** @var non-empty-string */
                        public const STR = parent::STR . "b";
                    }

                    class C extends B {
                        /** @var non-empty-string */
                        public const STR = parent::STR . "c";
                    }

                    /** @param "abc" $foo */
                    function foo(string $foo): void {}
                    foo(C::STR);
                ',
            ],
            'lateConstantResolutionSpreadEmptyArray' => [
                'code' => '<?php
                    class A {
                        public const ARR = [];
                    }

                    class B extends A {
                        public const ARR = [...parent::ARR];
                    }

                    class C extends B {
                        public const ARR = [...parent::ARR];
                    }

                    /** @param array<never, never> $arg */
                    function foo(array $arg): void {}
                    foo(C::ARR);
                ',
            ],
            'classConstConcatEol' => [
                'code' => '<?php
                    class Foo {
                        public const BAR = "bar" . PHP_EOL;
                    }

                    $foo = Foo::BAR;
                ',
                'assertions' => ['$foo' => 'string'],
            ],
            'dynamicClassConstFetch' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = "bar";
                    }

                    $foo = new Foo();
                    $_trace = $foo::BAR;',
                'assertions' => ['$_trace===' => "'bar'"],
            ],
            'unsafeInferenceClassConstFetch' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = "bar";
                    }

                    /** @var Foo $foo */
                    $foo = new stdClass();
                    $_trace = $foo::BAR;',
                'assertions' => ['$_trace' => 'mixed'],
            ],
            'FinalInferenceClassConstFetch' => [
                'code' => '<?php
                    final class Foo
                    {
                        public const BAR = "bar";
                    }

                    /** @var Foo $foo */
                    $foo = new stdClass();
                    $_trace = $foo::BAR;',
                'assertions' => ['$_trace===' => "'bar'"],
            ],
            'dynamicClassConstFetchClassString' => [
                'code' => '<?php
                    class C {
                        public const CC = 1;
                    }

                    $c = C::class;
                    $d = $c::CC;',
                'assertions' => ['$d===' => '1'],
            ],
            'allowConstCheckForDifferentPlatforms' => [
                'code' => '<?php
                    if ("phpdbg" === \PHP_SAPI) {}',
            ],
            'stdinout' => [
                'code' => '<?php
                    echo fread(STDIN, 100);
                    fwrite(STDOUT, "asd");
                    fwrite(STDERR, "zcx");',
            ],
            'classStringArrayOffset' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    const C = [
                        A::class => 1,
                        B::class => 2,
                    ];

                    /**
                     * @param class-string $s
                     */
                    function foo(string $s) : void {
                        if (isset(C[$s])) {}
                    }',
            ],
            'resolveClassConstToCurrentClass' => [
                'code' => '<?php
                    interface I {
                        /** @var string|array */
                        public const C = "a";

                        public function getC(): string;
                    }

                    class A implements I {
                        public function getC(): string {
                            return self::C;
                        }
                    }

                    class B extends A {
                        public const C = [5];

                        public function getA(): array {
                            return self::C;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'resolveCalculatedConstant' => [
                'code' => '<?php
                    interface Types {
                        public const TWO = "two";
                    }

                    interface A {
                       public const TYPE_ONE = "one";
                       public const TYPE_TWO = Types::TWO;
                    }

                    class B implements A {
                        public function __construct()
                        {
                            echo self::TYPE_ONE;
                            echo self::TYPE_TWO;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'arrayAccessAfterIsset' => [
                'code' => '<?php
                    class C {
                        const A = [
                            "b" => ["c" => false],
                            "c" => ["c" => true],
                            "d" => ["c" => true]
                        ];
                    }

                    /** @var string */
                    $s = "b";

                    if (isset(C::A[$s]["c"]) && C::A[$s]["c"] === false) {}',
            ],
            'namespacedConstantInsideClosure' => [
                'code' => '<?php
                    namespace Foo;

                    const FOO_BAR = 1;

                    function foo(): \Closure {
                        return function (): int {
                            return FOO_BAR;
                        };
                    }

                    function foo2(): int {
                        return FOO_BAR;
                    }

                    $a = function (): \Closure {
                        return function (): int {
                            return FOO_BAR;
                        };
                    };

                    $b = function (): int {
                        return FOO_BAR;
                    };',
            ],
            'rootConstantReferencedInNamespace' => [
                'code' => '<?php
                    namespace Foo;
                    echo DIRECTORY_SEPARATOR;',
            ],
            'constantDefinedInRootNamespace' => [
                'code' => '<?php
                    namespace {
                        define("ns1\\cons1", 0);

                        echo \ns1\cons1;
                        echo ns1\cons1;
                    }',
            ],
            'constantDynamicallyDefinedInNamespaceReferencedInSame' => [
                'code' => '<?php
                    namespace ns2 {
                        define(__NAMESPACE__."\\cons2", 0);

                        echo \ns2\cons2;
                        echo cons2;
                    }',
            ],
            'constantDynamicallyDefinedInNamespaceReferencedInRoot' => [
                'code' => '<?php
                    namespace ns2 {
                        define(__NAMESPACE__."\\cons2", 0);
                    }
                    namespace {
                        echo \ns2\cons2;
                        echo ns2\cons2;
                    }',
            ],
            'constantExplicitlyDefinedInNamespaceReferencedInSame' => [
                'code' => '<?php
                    namespace ns2 {
                        define("ns2\\cons2", 0);

                        echo \ns2\cons2;
                        echo cons2;
                    }',
            ],
            'constantExplicitlyDefinedInNamespaceReferencedInRoot' => [
                'code' => '<?php
                    namespace ns2 {
                        define("ns2\\cons2", 0);
                    }
                    namespace {
                        echo \ns2\cons2;
                        echo ns2\cons2;
                    }',
            ],
            'allowConstantToBeDefinedInNamespaceNadReferenced' => [
                'code' => '<?php
                    namespace ns;
                    function func(): void {}
                    define(__NAMESPACE__."\\cons", 0);
                    cons;',
            ],
            'staticConstantInsideFinalClass' => [
                'code' => '<?php
                    final class A {
                        public const STRING = "1,2,3";
                        public static function foo(): void {
                            print_r(explode(",", static::STRING));
                        }
                    }',
            ],
            'allowChecksAfterDefined' => [
                'code' => '<?php
                    class A {
                        private const STRING = "x";

                        public static function bar(string $s) : bool {
                            return !defined("FOO") && strpos($s, self::STRING) === 0;
                        }
                    }',
            ],
            'resolveOutOfOrderClassConstants' => [
                'code' => '<?php
                    const cons1 = 0;

                    class Clazz {
                        const cons2 = cons1;
                        const cons3 = 0;
                    }

                    echo cons1;
                    echo Clazz::cons2;
                    echo Clazz::cons3;',
            ],
            'evenMoreOutOfOrderConstants' => [
                'code' => '<?php
                    class A {
                        const X = self::Y;
                        const Y = 3;
                    }

                    class C extends B {
                    }

                    const Z = C::X;

                    class B extends A {
                        const Z = self::X;
                    }',
            ],
            'supportTernaries' => [
                'code' => '<?php
                    const cons1 = true;

                    class Clazz {
                        /**
                         * @psalm-suppress RedundantCondition
                         */
                        const cons2 = (cons1) ? 1 : 0;
                    }

                    echo Clazz::cons2;',
            ],
            'classConstantClassReferencedLazily' => [
                'code' => '<?php
                    /** @return array<string, int> */
                    function getMap(): array {
                        return Mapper::MAP;
                    }

                    class Mapper {
                        public const MAP = [
                            Foo::class => self::A,
                            Foo::BAR => self::A,
                        ];

                        private const A = 5;
                    }

                    class Foo {
                        public const BAR = "bar";
                    }',
            ],
            'resolveConstArrayAsList' => [
                'code' => '<?php
                    class Test1 {
                        const VALUES = [
                            "all",
                            "own"
                        ];
                    }

                    class Credentials {
                        const ALL  = "all";
                        const OWN  = "own";
                        const NONE = "none";
                    }

                    class Test2 {
                        const VALUES = [
                            Credentials::ALL,
                            Credentials::OWN
                        ];
                    }

                    /**
                     * @psalm-param list<"all"|"own"|"mine"> $value
                     */
                    function test($value): void {
                        print_r($value);
                    }

                    test(Test1::VALUES);
                    test(Test2::VALUES);',
            ],
            'resolveConstantFetchViaFunction' => [
                'code' => '<?php
                    const FOO = 1;
                    echo \constant("FOO");',
            ],
            'tooLongClassConstArray' => [
                'code' => '<?php
                    class MyTest {
                        const LOOKUP = [
                            "A00" => null,
                            "A01" => null,
                            "A02" => null,
                            "A03" => null,
                            "A04" => null,
                            "A05" => null,
                            "A06" => null,
                            "A07" => null,
                            "A010" => null,
                            "A011" => null,
                            "A012" => null,
                            "A013" => null,
                            "A014" => null,
                            "A015" => null,
                            "A016" => null,
                            "A017" => null,
                            "A020" => null,
                            "A021" => null,
                            "A022" => null,
                            "A023" => null,
                            "A024" => null,
                            "A025" => null,
                            "A026" => null,
                            "A027" => null,
                            "A030" => null,
                            "A031" => null,
                            "A032" => null,
                            "A033" => null,
                            "A034" => null,
                            "A035" => null,
                            "A036" => null,
                            "A037" => null,
                            "A040" => null,
                            "A041" => null,
                            "A042" => null,
                            "A043" => null,
                            "A044" => null,
                            "A045" => null,
                            "A046" => null,
                            "A047" => null,
                            "A050" => null,
                            "A051" => null,
                            "A052" => null,
                            "A053" => null,
                            "A054" => null,
                            "A055" => null,
                            "A056" => null,
                            "A057" => null,
                            "A060" => null,
                            "A061" => null,
                            "A062" => null,
                            "A063" => null,
                            "A064" => self::SUCCEED,
                            "A065" => self::FAIL,
                        ];

                        const SUCCEED = "SUCCEED";
                        const FAIL = "FAIL";

                        /**
                         * @param string $code
                         */
                        public static function will_succeed($code) : bool {
                            // False positive TypeDoesNotContainType - string(SUCCEED) cannot be identical to null
                            // This seems to happen because the array has a lot of entries.
                            return (self::LOOKUP[strtoupper($code)] ?? null) === self::SUCCEED;
                        }
                    }',
            ],
            'keyOf' => [
                'code' => '<?php
                    class A {
                        const C = [
                            1 => "a",
                            2 => "b",
                            3 => "c"
                        ];

                        /**
                         * @param key-of<A::C> $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(1);
                    A::foo(2);
                    A::foo(3);',
            ],
            'tooLongArrayInvalidConstantAssignmentValueFalsePositiveWithArray' => [
                'code' => '<?php
                    class TestInvalidConstantAssignmentValueFalsePositiveWithArray {
                        const LOOKUP = [
                            "00" => null,
                            "01" => null,
                            "02" => null,
                            "03" => null,
                            "04" => null,
                            "05" => null,
                            "06" => null,
                            "07" => null,
                            "08" => null,
                            "09" => null,
                            "10" => null,
                            "11" => null,
                            "12" => null,
                            "13" => null,
                            "14" => null,
                            "15" => null,
                            "16" => null,
                            "17" => null,
                            "18" => null,
                            "19" => null,
                            "20" => null,
                            "21" => null,
                            "22" => null,
                            "23" => null,
                            "24" => null,
                            "25" => null,
                            "26" => null,
                            "27" => null,
                            "28" => null,
                            "29" => null,
                            "30" => null,
                            "31" => null,
                            "32" => null,
                            "33" => null,
                            "34" => null,
                            "35" => null,
                            "36" => null,
                            "37" => null,
                            "38" => null,
                            "39" => null,
                            "40" => null,
                            "41" => null,
                            "42" => null,
                            "43" => null,
                            "44" => null,
                            "45" => null,
                            "46" => null,
                            "47" => null,
                            "48" => null,
                            "49" => null,
                            "50" => null,
                            "51" => null,
                            "52" => null,
                            "53" => null,
                            "54" => null,
                            "55" => null,
                            "56" => null,
                            "57" => null,
                            "58" => null,
                            "59" => null,
                            "60" => null,
                            "61" => null,
                            "62" => null,
                            "63" => null,
                            "64" => null,
                            "65" => null,
                            "66" => null,
                            "67" => null,
                            "68" => null,
                            "69" => null,
                            "70" => self::SUCCEED,
                            "71" => self::FAIL,
                            "72" => null,
                            "73" => null,
                            "74" => null,
                            "75" => null,
                            "76" => null,
                            "77" => null,
                            "78" => null,
                            "79" => null,
                            "80" => null,
                            "81" => null,
                            "82" => null,
                            "83" => null,
                            "84" => null,
                            "85" => null,
                            "86" => null,
                            "87" => null,
                            "88" => null,
                            "89" => null,
                            "90" => null,
                            "91" => null,
                            "92" => null,
                            "93" => null,
                            "94" => null,
                            "95" => null,
                            "96" => null,
                            "97" => null,
                            "98" => null,
                            "99" => null,
                            "100" => null,
                            "101" => null,
                        ];

                        const SUCCEED = "SUCCEED";
                        const FAIL = "FAIL";

                        public static function will_succeed(string $code) : bool {
                            // Seems to fail when the array has over 100+ entries, and at least one value references
                            // another constant from the same class (even nested)
                            return (self::LOOKUP[$code] ?? null) === self::SUCCEED;
                        }
                    }',
            ],
            'tooLongArrayInvalidConstantAssignmentValueFalsePositiveWithList' => [
                'code' => '<?php
                    class TestInvalidConstantAssignmentValueFalsePositiveWithList {
                        const LOOKUP = [
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            self::SUCCEED,
                            self::FAIL,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                        ];

                        const SUCCEED = "SUCCEED";
                        const FAIL = "FAIL";

                        public static function will_succeed(int $code) : bool {
                            // Seems to fail when the array has over 100+ entries, and at least one value references
                            // another constant from the same class (even nested)
                            return (self::LOOKUP[$code] ?? null) === self::SUCCEED;
                        }
                    }',
            ],
            'valueOf' => [
                'code' => '<?php
                    class A {
                        const C = [
                            1 => "a",
                            2 => "b",
                            3 => "c"
                        ];

                        /**
                         * @param value-of<A::C> $j
                         */
                        public static function bar(string $j) : void {}
                    }

                    A::bar("a");
                    A::bar("b");
                    A::bar("c");',
            ],
            'valueOfDefault' => [
                'code' => '<?php
                    class A {
                        const C = [
                            1 => "a",
                            2 => "b",
                            3 => "c"
                        ];

                        /**
                         * @var value-of<self::C>
                         */
                        public $foo = "a";
                    }',
            ],
            'wildcardEnum' => [
                'code' => '<?php
                    class A {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;

                        /**
                         * @param self::C_* $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(1);
                    A::foo(2);
                    A::foo(3);',
            ],
            'wildcardEnumAnyConstant' => [
                'code' => '<?php
                    class A {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;
                        const D_4 = 4;

                        /**
                         * @param self::* $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(1);
                    A::foo(2);
                    A::foo(3);
                    A::foo(A::D_4);',
            ],
            'wildcardEnumAnyTemplateExtendConstant' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface AInterface
                    {
                        /**
                         * @param T $i
                         * @return T
                         */
                        public function foo($i);
                    }

                    /**
                     * @implements AInterface<A::*>
                     */
                    class A implements AInterface {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;
                        const D_4 = 4;

                        public function foo($i)
                        {
                            return $i;
                        }
                    }

                    $a = new A();
                    $a->foo(1);
                    $a->foo(2);
                    $a->foo(3);
                    $a->foo(A::D_4);',
            ],
            'wildcardVarAndReturn' => [
                'code' => '<?php
                    class Numbers {
                        public const ONE = 1;
                        public const TWO = 2;
                    }

                    class Number {
                        /**
                         * @var Numbers::*
                         */
                        private $number;

                        /**
                         * @param Numbers::* $number
                         */
                        public function __construct($number) {
                            $this->number = $number;
                        }

                        /**
                         * @return Numbers::*
                         */
                        public function get(): int {
                            return $this->number;
                        }
                    }',
            ],
            'lowercaseStringAccessClassConstant' => [
                'code' => '<?php
                    class A {
                        const C = [
                            "a" => 1,
                            "b" => 2,
                            "c" => 3
                        ];
                    }

                    /**
                     * @param lowercase-string $s
                     */
                    function foo(string $s, string $t) : void {
                        echo A::C[$t];
                        echo A::C[$s];
                    }',
            ],
            'getClassConstantOffset' => [
                'code' => '<?php
                    class C {
                        private const A = [ 0 => "string" ];
                        private const B = self::A[0];

                        public function foo(): string {
                            return self::B;
                        }
                    }',
            ],
            'bitwiseOrClassConstant' => [
                'code' => '<?php
                    class X {
                        public const A = 1;
                        public const B = 2;
                        public const C = self::A | self::B;
                    }

                    $c = X::C;',
                'assertions' => [
                    '$c' => 'int',
                ],
            ],
            'bitwiseAndClassConstant' => [
                'code' => '<?php
                    class X {
                        public const A = 1;
                        public const B = 2;
                        public const C = self::A & self::B;
                    }

                    $c = X::C;',
                'assertions' => [
                    '$c' => 'int',
                ],
            ],
            'bitwiseXorClassConstant' => [
                'code' => '<?php
                    class X {
                        public const A = 1;
                        public const B = 2;
                        public const C = self::A ^ self::B;
                    }

                    $c = X::C;',
                'assertions' => [
                    '$c' => 'int',
                ],
            ],
            'bitwiseNotClassConstant' => [
                'code' => '<?php
                    class X {
                        public const A = ~0;
                        public const B = ~"aa";
                    }

                    $a = X::A;
                    $b = X::B;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'booleanNotClassConstant' => [
                'code' => '<?php
                    class X {
                        public const A = !true;
                        public const B = !false;
                    }

                    $a = X::A;
                    $b = X::B;',
                'assertions' => [
                    '$a' => 'false',
                    '$b' => 'true',
                ],
            ],
            'protectedClassConstantAccessibilitySameNameInChild' => [
                'code' => '<?php
                    class A {
                        /** @var int<1,max> */
                        protected const A = 1;

                        public static function test(): void {
                            echo B::A;
                        }
                    }

                    class B extends A {
                        protected const A = 2;
                    }

                    A::test();',
            ],
            'referenceClassConstantWithSelf' => [
                'code' => '<?php
                    abstract class A {
                        /** @var array<non-empty-string, non-empty-string> */
                        public const KEYS = [];
                        /** @var array<non-empty-string, non-empty-string> */
                        public const VALUES = [];
                    }

                    class B extends A {
                        public const VALUES = [\'there\' => self::KEYS[\'hi\']];
                        public const KEYS = [\'hi\' => CONSTANTS::THERE];
                    }

                    class CONSTANTS {
                        public const THERE = \'there\';
                    }

                    echo B::VALUES["there"];',
            ],
            'internalConstWildcard' => [
                'code' => '<?php
                    /**
                     * @psalm-param \PDO::PARAM_* $type
                     */
                    function param(int $type): void {}',
            ],
            'templatedConstantInType' => [
                'code' => '<?php
                    /**
                     * @template T of (self::READ_UNCOMMITTED|self::READ_COMMITTED|self::REPEATABLE_READ|self::SERIALIZABLE)
                     */
                    final class TransactionIsolationLevel {
                        private const READ_UNCOMMITTED = \'read uncommitted\';
                        private const READ_COMMITTED = \'read committed\';
                        private const REPEATABLE_READ = \'repeatable read\';
                        private const SERIALIZABLE = \'serializable\';

                        /**
                         * @psalm-var T
                         */
                        private string $level;

                        /**
                         * @psalm-param T $level
                         */
                        private function __construct(string $level)
                        {
                            $this->level = $level;
                        }

                        /**
                         * @psalm-return self<self::READ_UNCOMMITTED>
                         */
                        public static function readUncommitted(): self
                        {
                            return new self(self::READ_UNCOMMITTED);
                        }

                        /**
                         * @psalm-return self<self::READ_COMMITTED>
                         */
                        public static function readCommitted(): self
                        {
                            return new self(self::READ_COMMITTED);
                        }

                        /**
                         * @psalm-return self<self::REPEATABLE_READ>
                         */
                        public static function repeatableRead(): self
                        {
                            return new self(self::REPEATABLE_READ);
                        }

                        /**
                         * @psalm-return self<self::SERIALIZABLE>
                         */
                        public static function serializable(): self
                        {
                            return new self(self::SERIALIZABLE);
                        }

                        /**
                         * @psalm-return T
                         */
                        public function toString(): string
                        {
                            return $this->level;
                        }
                    }',
            ],
            'dirAndFileInConstInitializersAreNonEmptyString' => [
                'code' => '<?php
                    class C {
                        const DIR = __DIR__;
                        const FILE = __FILE__;
                    }
                    $dir = C::DIR;
                    $file = C::FILE;
                ',
                'assertions' => [
                    '$dir===' => 'non-empty-string',
                    '$file===' => 'non-empty-string',
                ],
            ],
            'lineInConstInitializersIsInt' => [
                'code' => '<?php
                    class C {
                        const LINE = __LINE__;
                    }
                    $line = C::LINE;
                ',
                'assertions' => [
                    '$line' => 'int<1, max>',
                ],
            ],
            'classMethodTraitAndFunctionInConstInitializersAreStrings' => [
                'code' => '<?php
                    class C {
                        const CLS = __CLASS__;
                        const MTD = __METHOD__;
                        const TRT = __TRAIT__;
                        const FCN = __FUNCTION__;
                    }
                    $cls = C::CLS;
                    $mtd = C::MTD;
                    $trt = C::TRT;
                    $fcn = C::FCN;
                ',
                'assertions' => [
                    '$cls' => 'string',
                    '$mtd' => 'string',
                    '$trt' => 'string',
                    '$fcn' => 'string',
                ],
            ],
            'concatWithMagicInConstInitializersIsNoEmptyString' => [
                'code' => '<?php
                    class C {
                        const DIR = __DIR__ . " - dir";
                        const FILE = "file:" . __FILE__;
                    }
                    $dir = C::DIR;
                    $file = C::FILE;
                ',
                'assertions' => [
                    '$dir===' => 'non-empty-string',
                    '$file===' => 'non-empty-string',
                ],
            ],
            'noCrashWithStaticInDocblock' => [
                'code' => '<?php
                    class Test {
                        const CONST1 = 1;

                        public function test(): void
                        {
                            /** @var static::CONST1 */
                            $a = static::CONST1;
                        }
                    }',
            ],
            'FuncAndMethInAllContexts' => [
                'code' => '<?php
                    /** @return \'getMethInFunc\' */
                    function getMethInFunc(): string{
                        return __METHOD__;
                    }

                    /** @return \'getFuncInFunc\' */
                    function getFuncInFunc(): string{
                        return __FUNCTION__;
                    }

                    class A{
                        /** @return \'A::getMethInMeth\' */
                        function getMethInMeth(): string{
                            return __METHOD__;
                        }

                        /** @return \'getFuncInMeth\' */
                        function getFuncInMeth(): string{
                            return __FUNCTION__;
                        }
                    }',
            ],
            'arrayUnpack' => [
                'code' => '<?php
                    class C {
                        const A = [...[...[1]], ...[2]];
                    }
                    $arr = C::A;
                ',
                'assertions' => [
                    '$arr===' => 'list{1, 2}',
                ],
            ],
            'keysInUnpackedArrayAreReset' => [
                'code' => '<?php
                    class C {
                        const A = [...[11 => 2]];
                    }
                    $arr = C::A;
                ',
                'assertions' => [
                    '$arr===' => 'list{2}',
                ],
            ],
            'arrayKeysSequenceContinuesAfterExplicitIntKey' => [
                'code' => '<?php
                    class C {
                        const A = [5 => "a", "z", 10 => "aa", "zz"];
                    }
                    $arr = C::A;
                ',
                'assertions' => [
                    '$arr===' => "array{10: 'aa', 11: 'zz', 5: 'a', 6: 'z'}",
                ],
            ],
            'arrayKeysSequenceContinuesAfterNonIntKey' => [
                'code' => '<?php
                    class C {
                        const A = [5 => "a", "zz" => "z", "aa"];
                    }
                    $arr = C::A;
                ',
                'assertions' => [
                    '$arr===' => "array{5: 'a', 6: 'aa', zz: 'z'}",
                ],
            ],
            'unresolvedConstWithUnaryMinus' => [
                'code' => '<?php
                    const K = 5;

                    abstract class C6 {

                      public const M = [
                        1 => -1,
                        K => 6,
                      ];

                      /**
                       * @param int $k
                       */
                      public static function f(int $k): void {
                          $a = self::M;
                          print_r($a);
                      }

                    }',
            ],
            'classConstantReferencingEnumCase' => [
                'code' => '<?php
                    enum E {
                        case Z;
                    }
                    class C {
                        public const CC = E::Z;
                    }
                    $c = C::CC;
                ',
                'assertions' => [
                    '$c===' => 'enum(E::Z)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'classConstantArrayWithEnumCaseKey' => [
                'code' => '<?php
                    enum E {
                        case K1;
                        case K2;
                    }
                    enum BEI: int {
                        case K3 = 1;
                        case K4 = 2;
                    }
                    enum BES: string {
                        case K5 = "a";
                        case K6 = "b";
                    }
                    class A {
                        public const C = [
                            BEI::K3->value => "e",
                            BEI::K4->value => 5,
                            E::K1->name => "c",
                            E::K2->name => 3,
                            BEI::K3->name => "d",
                            BEI::K4->name => 4,
                            BES::K5->name => "f",
                            BES::K6->name => 6,
                            BES::K5->value => "g",
                            BES::K6->value => 7,
                        ];
                    }
                    $c = A::C;
                ',
                'assertions' => [
                    '$c===' => "array{1: 'e', 2: 5, K1: 'c', K2: 3, K3: 'd', K4: 4, K5: 'f', K6: 6, a: 'g', b: 7}",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classConstantArrayWithEnumCaseKeyEnumDefinedAfterClass' => [
                'code' => '<?php
                    class A {
                        public const C = [
                            BEI::K3->value => "e",
                            BEI::K4->value => 5,
                            E::K1->name => "c",
                            E::K2->name => 3,
                            BEI::K3->name => "d",
                            BEI::K4->name => 4,
                            BES::K5->name => "f",
                            BES::K6->name => 6,
                            BES::K5->value => "g",
                            BES::K6->value => 7,
                        ];
                    }
                    enum E {
                        case K1;
                        case K2;
                    }
                    enum BEI: int {
                        case K3 = 1;
                        case K4 = 2;
                    }
                    enum BES: string {
                        case K5 = "a";
                        case K6 = "b";
                    }
                    $c = A::C;
                ',
                'assertions' => [
                    '$c===' => "array{1: 'e', 2: 5, K1: 'c', K2: 3, K3: 'd', K4: 4, K5: 'f', K6: 6, a: 'g', b: 7}",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classConstantArrayWithEnumCaseKeyNamespaced' => [
                'code' => '<?php
                    namespace OtherNamespace;
                    enum E: int {
                        case K1 = 1;
                        case K2 = 2;
                    }

                    namespace UsedNamespace;
                    enum E: int {
                        case K3 = 3;
                        case K4 = 4;
                    }

                    namespace AliasedNamespace;
                    enum E: int {
                        case K5 = 5;
                        case K6 = 6;
                    }

                    namespace SameNamespace;
                    use UsedNamespace\E;
                    use AliasedNamespace\E as E2;

                    enum E3: int {
                        case K7 = 7;
                        case K8 = 8;
                    }
                    class A {
                        public const C = [
                            \OtherNamespace\E::K1->name => "a",
                            \OtherNamespace\E::K2->name => 10,
                            \OtherNamespace\E::K1->value => "b",
                            \OtherNamespace\E::K2->value => 11,
                            E::K3->name => "c",
                            E::K4->name => 12,
                            E::K3->value => "d",
                            E::K4->value => 13,
                            E2::K5->name => "e",
                            E2::K6->name => 14,
                            E2::K5->value => "f",
                            E2::K6->value => 15,
                            E3::K7->name => "g",
                            E3::K8->name => 16,
                            E3::K7->value => "h",
                            E3::K8->value => 17,
                        ];
                    }
                    $c = A::C;
                ',
                'assertions' => [
                    '$c===' => "array{1: 'b', 2: 11, 3: 'd', 4: 13, 5: 'f', 6: 15, 7: 'h', 8: 17, K1: 'a', K2: 10, K3: 'c', K4: 12, K5: 'e', K6: 14, K7: 'g', K8: 16}",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classConstantArrayWithEnumCaseKeyDirectAccess' => [
                'code' => '<?php
                    enum E {
                        case K1;
                        case K2;
                    }
                    enum BEI: int {
                        case K3 = 1;
                        case K4 = 2;
                    }
                    enum BES: string {
                        case K5 = "a";
                        case K6 = "b";
                    }
                    class A {
                        public const C = [
                            E::K1->name => "c",
                            E::K2->name => 3,
                            BEI::K3->name => "d",
                            BEI::K4->name => 4,
                            BEI::K3->value => "e",
                            BEI::K4->value => 5,
                            BES::K5->name => "f",
                            BES::K6->name => 6,
                            BES::K5->value => "g",
                            BES::K6->value => 7,
                        ];
                    }
                    $a = A::C[E::K1->name];
                    $b = A::C[E::K2->name];
                    $c = A::C[BEI::K3->name];
                    $d = A::C[BEI::K4->name];
                    $e = A::C[BEI::K3->value];
                    $f = A::C[BEI::K4->value];
                    $g = A::C[BES::K5->name];
                    $h = A::C[BES::K6->name];
                    $i = A::C[BES::K5->value];
                    $j = A::C[BES::K6->value];
                    $k = A::C["K1"];
                    $l = A::C["K2"];
                    $m = A::C["K3"];
                    $n = A::C["K4"];
                    $o = A::C[1];
                    $p = A::C[2];
                    $q = A::C["K5"];
                    $r = A::C["K6"];
                    $s = A::C["a"];
                    $t = A::C["b"];
                ',
                'assertions' => [
                    '$a===' => "'c'",
                    '$b===' => '3',
                    '$c===' => "'d'",
                    '$d===' => '4',
                    '$e===' => "'e'",
                    '$f===' => '5',
                    '$g===' => "'f'",
                    '$h===' => '6',
                    '$i===' => "'g'",
                    '$j===' => '7',
                    '$k===' => "'c'",
                    '$l===' => '3',
                    '$m===' => "'d'",
                    '$n===' => '4',
                    '$o===' => "'e'",
                    '$p===' => '5',
                    '$q===' => "'f'",
                    '$r===' => '6',
                    '$s===' => "'g'",
                    '$t===' => '7',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classConstantNestedArrayWithEnumCaseKey' => [
                'code' => '<?php
                    enum E: string {
                        case K1 = "a";
                        case K2 = "b";
                        case K3 = "c";
                        case K4 = "d";
                        case K5 = "e";
                        case K6 = "f";
                        case K7 = "g";
                    }
                    class A {
                        public const C = [
                            E::K1->name => [
                                E::K2->name => [
                                    E::K3->name => "h",
                                    E::K4->name => "i",
                                ],
                                E::K5->name => [
                                    E::K6->name => "j",
                                    E::K7->name => "k",
                                ],
                            ],
                            E::K1->value => [
                                E::K2->value => [
                                    E::K3->value => "l",
                                    E::K4->value => "m",
                                ],
                                E::K5->value => [
                                    E::K6->value => "n",
                                    E::K7->value => "o",
                                ],
                            ]
                        ];
                    }
                    $c = A::C;
                ',
                'assertions' => [
                    '$c===' => "array{K1: array{K2: array{K3: 'h', K4: 'i'}, K5: array{K6: 'j', K7: 'k'}}, a: array{b: array{c: 'l', d: 'm'}, e: array{f: 'n', g: 'o'}}}",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'constantArrayWithEnumCaseKey' => [
                'code' => '<?php
                    enum E {
                        case K1;
                        case K2;
                    }
                    enum BEI: int {
                        case K3 = 1;
                        case K4 = 2;
                    }
                    enum BES: string {
                        case K5 = "a";
                        case K6 = "b";
                    }
                    const C = [
                        E::K1->name => "c",
                        E::K2->name => 3,
                        BEI::K3->name => "d",
                        BEI::K4->name => 4,
                        BEI::K3->value => "e",
                        BEI::K4->value => 5,
                        BES::K5->name => "f",
                        BES::K6->name => 6,
                        BES::K5->value => "g",
                        BES::K6->value => 7,
                    ];
                    $a = C[E::K1->name];
                    $b = C[E::K2->name];
                    $c = C[BEI::K3->name];
                    $d = C[BEI::K4->name];
                    $e = C[BEI::K3->value];
                    $f = C[BEI::K4->value];
                    $g = C[BES::K5->name];
                    $h = C[BES::K6->name];
                    $i = C[BES::K5->value];
                    $j = C[BES::K6->value];
                    $k = C["K1"];
                    $l = C["K2"];
                    $m = C["K3"];
                    $n = C["K4"];
                    $o = C[1];
                    $p = C[2];
                    $q = C["K5"];
                    $r = C["K6"];
                    $s = C["a"];
                    $t = C["b"];
                ',
                'assertions' => [
                    '$a===' => "'c'",
                    '$b===' => '3',
                    '$c===' => "'d'",
                    '$d===' => '4',
                    '$e===' => "'e'",
                    '$f===' => '5',
                    '$g===' => "'f'",
                    '$h===' => '6',
                    '$i===' => "'g'",
                    '$j===' => '7',
                    '$k===' => "'c'",
                    '$l===' => '3',
                    '$m===' => "'d'",
                    '$n===' => '4',
                    '$o===' => "'e'",
                    '$p===' => '5',
                    '$q===' => "'f'",
                    '$r===' => '6',
                    '$s===' => "'g'",
                    '$t===' => '7',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'constantEnumSelfReference' => [
                'code' => '<?php
                    enum Bar: string {
                        case A = "a";
                        case B = "b";
                        public const STR = self::A->value . self::B->value;
                    }

                    class Foo {
                        public const CONCAT_STR = "a" . Bar::STR . "e";
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'classConstWithParamOut' => [
                'code' => '<?php

                    class Reconciler
                    {
                        public const RECONCILIATION_OK = 0;
                        public const RECONCILIATION_EMPTY = 1;

                        public static function reconcileKeyedTypes(): void
                        {

                            $failed_reconciliation = 0;

                            self::boo($failed_reconciliation);

                            if ($failed_reconciliation === self::RECONCILIATION_EMPTY) {
                                echo "ici";
                            }
                        }

                        /** @param-out Reconciler::RECONCILIATION_* $f */
                        public static function boo(
                            ?int &$f = self::RECONCILIATION_OK
                        ): void {
                            $f = self::RECONCILIATION_EMPTY;
                        }
                    }
                    Reconciler::reconcileKeyedTypes();
                ',
            ],
            'selfConstUsesInferredType' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var string */
                        public const BAR = "bar";

                        /**
                         * @return "bar"
                         */
                        public function bar(): string
                        {
                            return self::BAR;
                        }
                    }
                ',
            ],
            'typedClassConst' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var string */
                        public const BAR = "bar";

                        public function bar(): string
                        {
                            return static::BAR;
                        }
                    }
                ',
            ],
            'classConstSuppress' => [
                'code' => '<?php
                    class Foo
                    {
                        /**
                         * @psalm-suppress InvalidConstantAssignmentValue
                         *
                         * @var int
                         */
                        public const BAR = "bar";
                    }
                ',
            ],
            'spreadEmptyArray' => [
                'code' => '<?php
                    class A {
                        public const ARR = [];
                    }

                    /** @param array<never, never> $arg */
                    function foo(array $arg): void {}
                    foo([...A::ARR]);
                ',
            ],
            'classConstCovariant' => [
                'code' => '<?php
                    abstract class A {
                        /** @var string */
                        public const COVARIANT = "";

                        /** @var string */
                        public const INVARIANT = "";
                    }

                    abstract class B extends A {}

                    abstract class C extends B {
                        /** @var non-empty-string */
                        public const COVARIANT = "foo";

                        /** @var string */
                        public const INVARIANT = "";
                    }
                ',
            ],
            'overrideClassConstFromInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="baz";
                    }

                    interface Bar extends Foo {}

                    class Baz implements Bar
                    {
                        /** @var non-empty-string */
                        public const BAR="foobar";
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inheritedConstDoesNotOverride' => [
                'code' => '<?php
                    interface Foo
                    {
                        public const BAR="baz";
                    }

                    interface Bar extends Foo {}
                ',
            ],
            'classConstsUsingFutureFloatDeclarationWithMultipleLevels' => [
                'code' => '<?php
                    class Foo {
                        public const BAZ = self::BAR + 1.0;
                        public const BAR = self::FOO + 1.0;
                        public const FOO = 1.0;
                    }
                ',
            ],
            'finalConst' => [
                'code' => '<?php
                    class Foo
                    {
                        final public const BAR="baz";
                    }

                    class Baz extends Foo
                    {
                    }

                    $a = Baz::BAR;
                ',
                'assertions' => [
                    '$a===' => "'baz'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'finalConstInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        final public const BAR="baz";
                    }

                    class Baz implements Foo
                    {
                    }

                    $a = Baz::BAR;
                ',
                'assertions' => [
                    '$a===' => "'baz'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'constantTypeRespectsLiteralStringLimit' => [
                'code' => <<<'PHP'
                    <?php

                    class A {
                        const T = 'TEXT';
                    }

                    class B
                    {
                        const ARRAY = [
                            'a' => A::T,
                            'b' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
                        ];
                    }
                    $z = B::ARRAY['b'];
                    PHP,
            ],
            'maxIntegerInArrayKey' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        // PHP_INT_MAX
                        public const S = ['9223372036854775807' => 1];
                        public const I = [9223372036854775807 => 1];

                        // PHP_INT_MAX + 1
                        public const SO = ['9223372036854775808' => 1];
                    }
                    $s = A::S;
                    $i = A::I;
                    $so = A::SO;
                    PHP,
                'assertions' => [
                    '$s===' => 'array{9223372036854775807: 1}',
                    '$i===' => 'array{9223372036854775807: 1}',
                    '$so===' => "array{'9223372036854775808': 1}",
                ],
            ],
            'autoincrementAlmostOverflow' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const I = [
                            9223372036854775806 => 0,
                            1, // expected key = PHP_INT_MAX
                        ];
                    }
                    $s = A::I;
                    PHP,
                'assertions' => [
                    '$s===' => 'array{9223372036854775806: 0, 9223372036854775807: 1}',
                ],
            ],
            'inheritedConstantIsNotAmbiguous' => [
                'code' => <<<'PHP'
                    <?php
                    interface MainInterface {
                        public const TEST = 'test';
                    }

                    interface FooInterface extends MainInterface {}
                    interface BarInterface extends MainInterface {}

                    class FooBar implements FooInterface, BarInterface {}
                    PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'constantDefinedInFunctionButNotCalled' => [
                'code' => '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    echo CONSTANT;',
                'error_message' => 'UndefinedConstant',
            ],
            'undefinedClassConstantInParamDefault' => [
                'code' => '<?php
                    class A {
                        public function doSomething(int $howManyTimes = self::DEFAULT_TIMES): void {}
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'nonMatchingConstantOffset' => [
                'code' => '<?php
                    class A {
                        const KEYS = ["one", "two", "three", "four"];
                        const ARR = [
                            "one" => 1,
                            "two" => 2
                        ];

                        const ARR2 = [
                            "three" => 3,
                            "four" => 4
                        ];
                    }

                    foreach (A::KEYS as $key) {
                        if (isset(A::ARR[$key])) {
                            echo A::ARR2[$key];
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
            'objectLikeConstArrays' => [
                'code' => '<?php
                    class C {
                        const A = 0;
                        const B = 1;

                        const ARR = [
                            self::A => "zero",
                            self::B => "two",
                        ];
                    }

                    if (C::ARR[C::A] === "two") {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'missingClassConstInArray' => [
                'code' => '<?php
                    class A {
                        const B = 1;
                        const C = [B];
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'resolveConstToCurrentClassWithBadReturn' => [
                'code' => '<?php
                    interface I {
                        public const C = "a";

                        public function getC(): string;
                    }

                    class A implements I {
                        public function getC(): string {
                            return self::C;
                        }
                    }

                    class B extends A {
                        public const C = [5];

                        public function getC(): string {
                            return self::C;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'outOfScopeDefinedConstant' => [
                'code' => '<?php
                    namespace {
                        define("A\\B", 0);
                    }
                    namespace C {
                        echo A\B;
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'preventStaticClassConstWithoutRef' => [
                'code' => '<?php
                    class Foo {
                        public const CONST = 1;

                        public function x() : void {
                            echo static::CON;
                        }
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'noCyclicConstReferences' => [
                'code' => '<?php
                    class A {
                        const FOO = B::FOO;
                    }

                    class B {
                        const FOO = C::FOO;
                    }

                    class C {
                        const FOO = A::FOO;
                    }',
                'error_message' => 'CircularReference',
            ],
            'keyOfBadValue' => [
                'code' => '<?php
                    class A {
                        const C = [
                            1 => "a",
                            2 => "b",
                            3 => "c"
                        ];

                        /**
                         * @param key-of<A::C> $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(4);',
                'error_message' => 'InvalidArgument',
            ],
            'valueOfBadValue' => [
                'code' => '<?php
                    class A {
                        const C = [
                            1 => "a",
                            2 => "b",
                            3 => "c"
                        ];

                        /**
                         * @param value-of<A::C> $j
                         */
                        public static function bar(string $j) : void {}
                    }

                    A::bar("d");',
                'error_message' => 'InvalidArgument',
            ],
            'wildcardEnumBadValue' => [
                'code' => '<?php
                    class A {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;
                        const D_4 = 4;

                        /**
                         * @param self::C_* $i
                         */
                        public static function foo(int $i) : void {}
                    }

                    A::foo(A::D_4);',
                'error_message' => 'InvalidArgument',
            ],
            'wildcardEnumAnyTemplateExtendConstantBadValue' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface AInterface
                    {
                        /**
                         * @param T $i
                         * @return T
                         */
                        public function foo($i);
                    }

                    /**
                     * @implements AInterface<A::*>
                     */
                    class A implements AInterface {
                        const C_1 = 1;
                        const C_2 = 2;
                        const C_3 = 3;
                        const D_4 = 4;

                        public function foo($i)
                        {
                            return $i;
                        }
                    }

                    $a = new A();
                    $a->foo(5);
                    ',
                'error_message' => 'InvalidArgument',
            ],
            'correctMessage' => [
                'code' => '<?php
                    class S {
                        public const ZERO = 0;
                        public const ONE  = 1;
                    }

                    /**
                     * @param S::* $s
                     */
                    function foo(int $s): string {
                        return [1 => "a", 2 => "b"][$s];
                    }',
                'error_message' => "offset value of '0|1",
            ],
            'constantWithMissingClass' => [
                'code' => '<?php
                    class Subject
                    {
                        public const DATA = [
                            MissingClass::TAG_DATA,
                        ];

                        public function execute(): void
                        {
                            /** @psalm-suppress InvalidArrayOffset */
                            if (self::DATA["a"]);
                        }
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'duplicateConstants' => [
                'code' => '<?php
                    class A {
                        public const B = 1;
                        public const B = 2;
                    }
                ',
                'error_message' => 'DuplicateConstant',
            ],
            'constantDuplicatesEnumCase' => [
                'code' => '<?php
                    enum State {
                        case Open;
                        public const Open = 1;
                    }
                ',
                'error_message' => 'DuplicateConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'enumCaseDuplicatesConstant' => [
                'code' => '<?php
                    enum State {
                        public const Open = 1;
                        case Open;
                    }
                ',
                'error_message' => 'DuplicateConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'returnValueofNonExistentConstant' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = ["bar"];

                        /**
                         * @return value-of<self::BAT>
                         */
                        public function bar(): string
                        {
                            return self::BAR[0];
                        }
                    }
                ',
                'error_message' => 'UnresolvableConstant',
            ],
            'returnValueofStaticConstant' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = ["bar"];

                        /**
                         * @return value-of<static::BAR>
                         */
                        public function bar(): string
                        {
                            return static::BAR[0];
                        }
                    }
                ',
                'error_message' => 'UnresolvableConstant',
            ],
            'takeKeyofNonExistentConstant' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = ["bar"];

                        /**
                         * @param key-of<self::BAT> $key
                         */
                        public function bar(int $key): string
                        {
                            return static::BAR[$key];
                        }
                    }
                ',
                'error_message' => 'UnresolvableConstant',
            ],
            'takeKeyofStaticConstant' => [
                'code' => '<?php
                    class Foo
                    {
                        public const BAR = ["bar"];

                        /**
                         * @param key-of<static::BAR> $key
                         */
                        public function bar(int $key): string
                        {
                            return static::BAR[$key];
                        }
                    }
                ',
                'error_message' => 'UnresolvableConstant',
            ],
            'invalidConstantAssignmentType' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        public const BAR = "bar";
                    }
                ',
                'error_message' => "InvalidConstantAssignmentValue",
            ],
            'invalidConstantAssignmentTypeResolvedLate' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int */
                        public const BAR = "bar" . self::BAZ;
                        public const BAZ = "baz";
                        public const BARBAZ = self::BAR . self::BAZ;
                    }
                ',
                'error_message' => "InvalidConstantAssignmentValue",
            ],
            'classConstContravariant' => [
                'code' => '<?php
                    abstract class A {
                        /** @var non-empty-string */
                        public const CONTRAVARIANT = "foo";
                    }

                    abstract class B extends A {}

                    abstract class C extends B {
                        /** @var string */
                        public const CONTRAVARIANT = "";
                    }
                ',
                'error_message' => "LessSpecificClassConstantType",
            ],
            'classConstAmbiguousInherit' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="baz";
                    }

                    interface Bar extends Foo {}

                    class Baz
                    {
                        /** @var non-empty-string */
                        public const BAR="foobar";
                    }

                    class BarBaz extends Baz implements Bar
                    {
                    }
                ',
                'error_message' => 'AmbiguousConstantInheritance',
            ],
            'overrideClassConstFromInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="baz";
                    }

                    interface Bar extends Foo {}

                    class Baz implements Bar
                    {
                        /** @var non-empty-string */
                        public const BAR="foobar";
                    }
                ',
                'error_message' => 'OverriddenInterfaceConstant',
            ],
            'overrideClassConstFromInterfaceWithInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="baz";
                    }

                    interface Bar extends Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="bar";
                    }
                ',
                'error_message' => 'OverriddenInterfaceConstant',
            ],
            'overrideClassConstFromInterfaceWithExtraIrrelevantInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var non-empty-string */
                        public const BAR="baz";
                    }

                    interface Bar {}

                    class Baz implements Foo, Bar
                    {
                        public const BAR="";
                    }
                ',
                'error_message' => "InvalidClassConstantType",
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'overrideFinalClassConstFromExtendedClass' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var string */
                        final public const BAR="baz";
                    }

                    class Baz extends Foo
                    {
                        /** @var string */
                        public const BAR="foobar";
                    }
                ',
                'error_message' => "OverriddenFinalConstant",
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'overrideFinalClassConstFromImplementedInterface' => [
                'code' => '<?php
                    interface Foo
                    {
                        /** @var string */
                        final public const BAR="baz";
                    }

                    class Baz implements Foo
                    {
                        /** @var string */
                        public const BAR="foobar";
                    }
                ',
                'error_message' => "OverriddenFinalConstant",
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'finalConstantIsIllegalBefore8.1' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var string */
                        final public const BAR="baz";
                    }
                ',
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:44',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classStringIsRequiredToAccessClassConstant' => [
                'code' => '<?php
                    class Foo {
                        public const BAR = "bar";
                    }

                    $class = "Foo";

                    $class::BAR;
                ',
                'error_message' => 'InvalidStringClass',
            ],
            'integerOverflowInArrayKey' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        // PHP_INT_MAX + 1
                        public const IO = [9223372036854775808 => 1];
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
            ],
            'autoincrementOverflow' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const I = [
                            9223372036854775807 => 0,
                            1, // this is a fatal error
                        ];
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
            ],
            'autoincrementOverflowWithUnpack' => [
                'code' => <<<'PHP'
                    <?php
                    class A {
                        public const I = [
                            9223372036854775807 => 0,
                            ...[1], // this is a fatal error
                        ];
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
            ],
            'unsupportedDynamicFetch' => [
                'code' => '<?php
                    class C {
                        const A = 0;
                    }

                    $a = C::{"A"};
                ',
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
        ];
    }
}
