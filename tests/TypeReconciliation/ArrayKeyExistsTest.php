<?php
namespace Psalm\Tests\TypeReconciliation;

class ArrayKeyExistsTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'arrayKeyExistsOnStringArrayShouldInformArrayness' => [
                '<?php
                    /**
                     * @param string[] $a
                     * @return array{b: string}
                     */
                    function foo(array $a) {
                        if (array_key_exists("b", $a)) {
                            return $a;
                        }

                        throw new \Exception("bad");
                    }'
            ],
             'arrayKeyExistsThrice' => [
                '<?php
                    function three(array $a): void {
                        if (!array_key_exists("a", $a)
                            || !array_key_exists("b", $a)
                            || !array_key_exists("c", $a)
                            || (!is_string($a["a"]) && !is_int($a["a"]))
                            || (!is_string($a["b"]) && !is_int($a["b"]))
                            || (!is_string($a["c"]) && !is_int($a["c"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }'
            ],
            'arrayKeyExistsTwice' => [
                '<?php
                    function two(array $a): void {
                        if (!array_key_exists("a", $a) || !(is_string($a["a"]) || is_int($a["a"])) ||
                            !array_key_exists("b", $a) || !(is_string($a["b"]) || is_int($a["b"]))
                        ) {
                            throw new \Exception();
                        }

                        echo $a["a"];
                        echo $a["b"];
                    }'
            ],
            'assertConstantOffsetsInMethod' => [
                '<?php
                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(string $key): bool {
                            if (!array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'assertSelfClassConstantOffsetsInFunction' => [
                '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => []
                        ];

                        public function bar(?string $key): bool {
                            if ($key === null || !array_key_exists($key, self::ARR) || !array_key_exists("foo", self::ARR[$key])) {
                                return false;
                            }

                            return self::ARR[$key]["foo"];
                        }
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'assertNamedClassConstantOffsetsInFunction' => [
                '<?php
                    namespace Ns;

                    class C {
                        public const ARR = [
                            "a" => ["foo" => true],
                            "b" => [],
                        ];
                    }

                    function bar(?string $key): bool {
                        if ($key === null || !array_key_exists($key, C::ARR) || !array_key_exists("foo", C::ARR[$key])) {
                            return false;
                        }

                        return C::ARR[$key]["foo"];
                    }',
                [],
                ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'possiblyUndefinedArrayAccessWithArrayKeyExists' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists(0, $a)) {
                        echo $a[0];
                    }',
            ],
            'arrayKeyExistsShoudldNotModifyIntType' => [
                '<?php
                    class HttpError {
                        const ERRS = [
                            403 => "a",
                            404 => "b",
                            500 => "c"
                        ];
                    }

                    function init(string $code) : string {
                        if (array_key_exists($code, HttpError::ERRS)) {
                            return $code;
                        }

                        return "";
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
            'assertTypeNarrowedByNestedIsset' => [
                '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MixedArgument
                     */
                    function foo(array $array = []): void {
                        if (array_key_exists("a", $array)) {
                            echo $array["a"];
                        }

                        if (array_key_exists("b", $array)) {
                            echo $array["b"]->format("Y-m-d");
                        }
                    }',
            ],
            'assertArrayKeyExistsRefinesType' => [
                '<?php
                    class Foo {
                        /** @var array<int,string> */
                        public const DAYS = [
                            1 => "mon",
                            2 => "tue",
                            3 => "wed",
                            4 => "thu",
                            5 => "fri",
                            6 => "sat",
                            7 => "sun",
                        ];

                        /** @param key-of<self::DAYS> $dayNum*/
                        private static function doGetDayName(int $dayNum): string {
                            return self::DAYS[$dayNum];
                        }

                        /** @throws LogicException */
                        public static function getDayName(int $dayNum): string {
                            if (! array_key_exists($dayNum, self::DAYS)) {
                                throw new \LogicException();
                            }
                            return self::doGetDayName($dayNum);
                        }
                    }'
            ],
            'arrayKeyExistsInferString' => [
                '<?php
                    function foo(mixed $file) : string {
                        /** @psalm-suppress MixedArgument */
                        if (array_key_exists($file, ["a" => 1, "b" => 2])) {
                            return $file;
                        }

                        return "";
                    }',
                [],
                [],
                '8.0'
            ],
            'arrayKeyExistsComplex' => [
                '<?php
                    class A {
                        private const MAP = [
                            "a" => 1,
                            "b" => 2,
                            "c" => 3,
                            "d" => 4,
                            "e" => 5,
                            "f" => 6,
                            "g" => 7,
                            "h" => 8,
                            "i" => 9,
                            "j" => 10,
                            "k" => 11,
                        ];

                        public function doWork(string $a): void {
                            if (!array_key_exists($a, self::MAP)) {}
                        }
                    }'
            ],
            'arrayKeyExistsAccess' => [
                '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : void {
                        if (array_key_exists(1, $arr)) {
                            $a = ($arr[1] === "b") ? true : false;
                        }
                    }',
            ],
            'noCrashOnArrayKeyExistsBracket' => [
                '<?php
                    class MyCollection {
                        /**
                         * @param int $commenter
                         * @param int $numToGet
                         * @return int[]
                         */
                        public function getPosters($commenter, $numToGet=10) {
                            $posters = array();
                            $count = 0;
                            $a = new ArrayObject([[1234]]);
                            $iter = $a->getIterator();
                            while ($iter->valid() && $count < $numToGet) {
                                $value = $iter->current();
                                if ($value[0] != $commenter) {
                                    if (!array_key_exists($value[0], $posters)) {
                                        $posters[$value[0]] = 1;
                                        $count++;
                                    }
                                }
                                $iter->next();
                            }
                            return array_keys($posters);
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset',
                    'MixedArgument',
                ],
            ],
            'arrayKeyExistsTwoVars' => [
                '<?php
                    /**
                     * @param array{a: string, b: string, c?: string} $info
                     */
                    function getReason(array $info, string $key, string $value): bool {
                        if (array_key_exists($key, $info) && $info[$key] === $value) {
                            return true;
                        }

                        return false;
                    }'
            ],
            'allowIntKeysToo' => [
                '<?php
                    /**
                     * @param array<1|2|3, string> $arr
                     * @return 1|2|3
                     */
                    function checkArrayKeyExistsInt(array $arr, int $int): int
                    {
                        if (array_key_exists($int, $arr)) {
                            return $int;
                        }

                        return 1;
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnWrongKey' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("a", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'possiblyUndefinedArrayAccessWithArrayKeyExistsOnMissingKey' => [
                '<?php
                    if (rand(0,1)) {
                      $a = ["a" => 1];
                    } else {
                      $a = [2, 3];
                    }

                    if (array_key_exists("b", $a)) {
                        echo $a[0];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
        ];
    }
}
