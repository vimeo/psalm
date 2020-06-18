<?php
namespace Psalm\Tests;

class ConstantTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'constantInFunction' => [
                '<?php
                    useTest();
                    const TEST = 2;

                    function useTest(): int {
                        return TEST;
                    }',
            ],
            'constantInClosure' => [
                '<?php
                    const TEST = 2;

                    $useTest = function(): int {
                        return TEST;
                    };
                    $useTest();',
            ],
            'constantDefinedInFunction' => [
                '<?php
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
                '<?php
                    $a = __LINE__;
                    $b = __file__;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'getClassConstantValue' => [
                '<?php
                    class A {
                        const B = [0, 1, 2];
                    }

                    $a = A::B[1];',
            ],
            'staticConstEval' => [
                '<?php
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
                'error_levels' => ['MixedArgument'],
            ],
            'undefinedConstant' => [
                '<?php
                    switch (rand(0, 50)) {
                        case FORTY: // Observed a valid UndeclaredConstant warning
                            $x = "value";
                            break;
                        default:
                            $x = "other";
                        }

                        echo $x;',
                'assertions' => [],
                'error_levels' => ['UndefinedConstant'],
            ],
            'suppressUndefinedClassConstant' => [
                '<?php
                    class C {}

                    /** @psalm-suppress UndefinedConstant */
                    $a = POTATO;

                    /** @psalm-suppress UndefinedConstant */
                    $a = C::POTATO;',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'hardToDefineClassConstant' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                'error_levels' => ['MixedArgument', 'MixedArrayOffset', 'MixedAssignment'],
            ],
            'lateConstantResolution' => [
                '<?php
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
            'allowConstCheckForDifferentPlatforms' => [
                '<?php
                    if ("phpdbg" === \PHP_SAPI) {}',
            ],
            'stdinout' => [
                '<?php
                    echo fread(STDIN, 100);
                    fwrite(STDOUT, "asd");
                    fwrite(STDERR, "zcx");',
            ],
            'classStringArrayOffset' => [
                '<?php
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
                '<?php
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

                        public function getA(): array {
                            return self::C;
                        }
                    }',
            ],
            'resolveCalculatedConstant' => [
                '<?php
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
                'error_levels' => ['MixedArgument'],
            ],
            'arrayAccessAfterIsset' => [
                '<?php
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
                '<?php
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
                '<?php
                    namespace Foo;
                    echo DIRECTORY_SEPARATOR;',
            ],
            'constantDefinedInRootNamespace' => [
                '<?php
                    namespace {
                        define("ns1\\cons1", 0);

                        echo \ns1\cons1;
                        echo ns1\cons1;
                    }',
            ],
            'constantDynamicallyDefinedInNamespaceReferencedInSame' => [
                '<?php
                    namespace ns2 {
                        define(__NAMESPACE__."\\cons2", 0);

                        echo \ns2\cons2;
                        echo cons2;
                    }',
            ],
            'constantDynamicallyDefinedInNamespaceReferencedInRoot' => [
                '<?php
                    namespace ns2 {
                        define(__NAMESPACE__."\\cons2", 0);
                    }
                    namespace {
                        echo \ns2\cons2;
                        echo ns2\cons2;
                    }',
            ],
            'constantExplicitlyDefinedInNamespaceReferencedInSame' => [
                '<?php
                    namespace ns2 {
                        define("ns2\\cons2", 0);

                        echo \ns2\cons2;
                        echo cons2;
                    }',
            ],
            'constantExplicitlyDefinedInNamespaceReferencedInRoot' => [
                '<?php
                    namespace ns2 {
                        define("ns2\\cons2", 0);
                    }
                    namespace {
                        echo \ns2\cons2;
                        echo ns2\cons2;
                    }',
            ],
            'allowConstantToBeDefinedInNamespaceNadReferenced' => [
                '<?php
                    namespace ns;
                    function func(): void {}
                    define(__NAMESPACE__."\\cons", 0);
                    cons;',
            ],
            'staticConstantInsideFinalClass' => [
                '<?php
                    final class A {
                        public const STRING = "1,2,3";
                        public static function foo(): void {
                            print_r(explode(",", static::STRING));
                        }
                    }'
            ],
            'allowChecksAfterDefined' => [
                '<?php
                    class A {
                        private const STRING = "x";

                        public static function bar() : bool {
                            return !defined("FOO") && strpos("x", self::STRING) === 0;
                        }
                    }'
            ],
            'resolveOutOfOrderClassConstants' => [
                '<?php
                    const cons1 = 0;

                    class Clazz {
                        const cons2 = cons1;
                        const cons3 = 0;
                    }

                    echo cons1;
                    echo Clazz::cons2;
                    echo Clazz::cons3;'
            ],
            'evenMoreOutOfOrderConstants' => [
                '<?php
                    class A {
                        const X = self::Y;
                        const Y = 3;
                    }

                    class C extends B {
                    }

                    const Z = C::X;

                    class B extends A {
                        const Z = self::X;
                    }'
            ],
            'supportTernaries' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'resolveConstArrayAsList' => [
                '<?php
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
                    test(Test2::VALUES);'
            ],
            'resolveConstantFetchViaFunction' => [
                '<?php
                    const FOO = 1;
                    echo \constant("FOO");'
            ],
            'tooLongClassConstArray' => [
                '<?php
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
                    }'
            ],
            'keyOf' => [
                '<?php
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
            'valueOf' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                    }'
            ],
            'lowercaseStringAccessClassConstant' => [
                '<?php
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
                    }'
            ],
            'arrayKeyExistsWithClassConst' => [
                '<?php
                    class C {}
                    class D {}

                    class A {
                        const FLAGS = [
                            0 => [C::class => "foo"],
                            1 => [D::class => "bar"],
                        ];

                        private function foo(int $i) : void {
                            if (array_key_exists(C::class, self::FLAGS[$i])) {
                                echo self::FLAGS[$i][C::class];
                            }
                        }
                    }'
            ],
            'getClassConstantOffset' => [
                '<?php
                    class C {
                        private const A = [ 0 => "string" ];
                        private const B = self::A[0];

                        public function foo(): string {
                            return self::B;
                        }
                    }'
            ],
            'bitwiseOrClassConstant' => [
                '<?php
                    class X {
                        public const A = 1;
                        public const B = 2;
                        public const C = self::A | self::B;
                    }

                    $c = X::C;',
                [
                    '$c' => 'int',
                ]
            ],
            'protectedClassConstantAccessibilitySameNameInChild' => [
                '<?php
                    class A {
                        protected const A = 1;

                        public static function test(): void {
                            echo B::A;
                        }
                    }

                    class B extends A {
                        protected const A = 2;
                    }

                    A::test();'
            ],
            'referenceClassConstantWithSelf' => [
                '<?php
                    abstract class A {
                        public const KEYS = [];
                        public const VALUES = [];
                    }

                    class B extends A {
                        public const VALUES = [\'there\' => self::KEYS[\'hi\']];
                        public const KEYS = [\'hi\' => CONSTANTS::THERE];
                    }

                    class CONSTANTS {
                        public const THERE = \'there\';
                    }

                    echo B::VALUES["there"];'
            ],
            'constantArrayKeyExistsWithClassConstant' => [
                '<?php
                    class Foo {
                        public const F = "key";
                    }

                    /** @param array{key?: string} $a */
                    function one(array $a): void {
                        if (array_key_exists(Foo::F, $a)) {
                            echo $a[Foo::F];
                        }
                    }'
            ],
            'internalConstWildcard' => [
                '<?php
                    /**
                     * @psalm-param \PDO::PARAM_* $type
                     */
                    function param(int $type): void {}'
            ],
            'templatedConstantInType' => [
                '<?php
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
                    }'
            ],
            'dirAndFileInConstInitializersAreNonEmptyString' => [
                '<?php
                    class C {
                        const DIR = __DIR__;
                        const FILE = __FILE__;
                    }
                    $dir = C::DIR;
                    $file = C::FILE;
                ',
                [
                    '$dir===' => 'non-empty-string',
                    '$file===' => 'non-empty-string',
                ]
            ],
            'lineInConstInitializersIsInt' => [
                '<?php
                    class C {
                        const LINE = __LINE__;
                    }
                    $line = C::LINE;
                ',
                [
                    '$line' => 'int',
                ]
            ],
            'classMethodTraitAndFunctionInConstInitializersAreStrings' => [
                '<?php
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
                [
                    '$cls' => 'string',
                    '$mtd' => 'string',
                    '$trt' => 'string',
                    '$fcn' => 'string',
                ]
            ],
            'concatWithMagicInConstInitializersIsNoEmptyString' => [
                '<?php
                    class C {
                        const DIR = __DIR__ . " - dir";
                        const FILE = "file:" . __FILE__;
                    }
                    $dir = C::DIR;
                    $file = C::FILE;
                ',
                [
                    '$dir===' => 'non-empty-string',
                    '$file===' => 'non-empty-string',
                ]
            ]
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'constantDefinedInFunctionButNotCalled' => [
                '<?php
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
                '<?php
                    class A {
                        public function doSomething(int $howManyTimes = self::DEFAULT_TIMES): void {}
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'nonMatchingConstantOffset' => [
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        const B = 1;
                        const C = [B];
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'resolveConstToCurrentClassWithBadReturn' => [
                '<?php
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
            ],
            'outOfScopeDefinedConstant' => [
                '<?php
                    namespace {
                        define("A\\B", 0);
                    }
                    namespace C {
                        echo A\B;
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'preventStaticClassConstWithoutRef' => [
                '<?php
                    class Foo {
                        public const CONST = 1;

                        public function x() : void {
                            echo static::CON;
                        }
                    }',
                'error_message' => 'UndefinedConstant',
            ],
            'noCyclicConstReferences' => [
                '<?php
                    class A {
                        const FOO = B::FOO;
                    }

                    class B {
                        const FOO = C::FOO;
                    }

                    class C {
                        const FOO = A::FOO;
                    }',
                'error_message' => 'CircularReference'
            ],
            'keyOfBadValue' => [
                '<?php
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
                '<?php
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
                '<?php
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
                'error_message' => 'InvalidArgument'
            ],
            'wildcardEnumAnyTemplateExtendConstantBadValue' => [
                '<?php
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
                'error_message' => 'InvalidArgument'
            ],
        ];
    }
}
