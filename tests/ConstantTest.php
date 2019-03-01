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
                    fwrite(STDERR, "zcx");'
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
        ];
    }
}
