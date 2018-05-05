<?php
namespace Psalm\Tests;

class IssetTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;
    use Traits\FileCheckerInvalidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'isset' => [
                '<?php
                    $a = isset($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesce' => [
                '<?php
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'nullCoalesceWithGoodVariable' => [
                '<?php
                    $b = rand(0, 10) > 5 ? "hello" : null;
                    $a = $b ?? null;',
                'assertions' => [
                    '$a' => 'string|null',
                ],
            ],
            'issetKeyedOffset' => [
                '<?php
                    if (!isset($foo["a"])) {
                        $foo["a"] = "hello";
                    }',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed',
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray(),
                ],
            ],
            'issetKeyedOffsetORFalse' => [
                '<?php
                    /** @return void */
                    function takesString(string $str) {}

                    $bar = rand(0, 1) ? ["foo" => "bar"] : false;

                    if (isset($bar["foo"])) {
                        takesString($bar["foo"]);
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyInvalidArrayAccess'],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray(),
                ],
            ],
            'nullCoalesceKeyedOffset' => [
                '<?php
                    $foo["a"] = $foo["a"] ?? "hello";',
                'assertions' => [
                    '$foo[\'a\']' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
                'scope_vars' => [
                    '$foo' => \Psalm\Type::getArray(),
                ],
            ],
            'noRedundantConditionOnMixed' => [
                '<?php
                    function testarray(array $data): void {
                        foreach ($data as $item) {
                            if (isset($item["a"]) && isset($item["b"]) && isset($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'testUnset' => [
                '<?php
                    $foo = ["a", "b", "c"];
                    foreach ($foo as $bar) {}
                    unset($foo, $bar);

                    function foo(): void {
                        $foo = ["a", "b", "c"];
                        foreach ($foo as $bar) {}
                        unset($foo, $bar);
                    }',
            ],
            'issetObjectLike' => [
                '<?php
                    $arr = [
                        "profile" => [
                            "foo" => "bar",
                        ],
                        "groups" => [
                            "foo" => "bar",
                            "hide"  => rand() % 2 > 0,
                        ],
                    ];

                    foreach ($arr as $item) {
                        if (!isset($item["hide"]) || !$item["hide"]) {}
                    }',
            ],
            'issetPropertyAffirmsObject' => [
                '<?php
                    class A {
                        /** @var ?int */
                        public $id;
                    }

                    function takesA(?A $a): A {
                        if (isset($a->id)) {
                            return $a;
                        }

                        return new A();
                    }',
            ],
            'issetVariableKeysWithoutChange' => [
                '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = 2;
                    $c = 0;
                    if (isset($arr[$b][$c])) {
                        echo $arr[$b][$c];
                    }',
            ],
            'issetNonNullArrayKey' => [
                '<?php
                    /**
                     * @param  array<int, int> $arr
                     */
                    function foo(array $arr) : int {
                        $b = rand(0, 3);
                        if (!isset($arr[$b])) {
                            throw new \Exception("bad");
                        }
                        return $arr[$b];
                    }',
            ],
            'issetArrayOffsetConditionalCreationWithInt' => [
                '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : string {
                        if (!isset($arr[0])) {
                            $arr[0] = "hello";
                        }

                        return $arr[0];
                    }',
            ],
            'issetArrayOffsetConditionalCreationWithVariable' => [
                '<?php
                    /** @param array<int, string> $arr */
                    function foo(array $arr) : string {
                        $b = 5;

                        if (!isset($arr[$b])) {
                            $arr[$b] = "hello";
                        }

                        return $arr[$b];
                    }',
            ],
            'noExceptionOnBracketString' => [
                '<?php
                    if (isset($foo["bar[]"])) {}',
            ],
            'issetArrayOffsetAndProperty' => [
                '<?php
                    class A {
                        /** @var ?B */
                        public $b;
                    }
                    class B {}

                    /**
                     * @param A[] $arr
                     */
                    function takesAList(array $arr) : B {
                        if (isset($arr[1]->b)) {
                            return $arr[1]->b;
                        }
                        throw new \Exception("bad");
                    }',
            ],
            'allowUnknownAdditionToInt' => [
                '<?php
                    $arr = [1, 1, 1, 1, 2, 5, 3, 2];
                    $cumulative = [];

                    foreach ($arr as $val) {
                        if (isset($cumulative[$val])) {
                            $cumulative[$val] = $cumulative[$val] + 1;
                        } else {
                            $cumulative[$val] = 1;
                        }
                    }',
            ],
            'allowUnknownArrayMergeToInt' => [
                '<?php
                    $arr = [1, 1, 1, 1, 2, 5, 3, 2];
                    $cumulative = [];

                    foreach ($arr as $val) {
                        if (isset($cumulative[$val])) {
                            $cumulative[$val] = array_merge($cumulative[$val], [$val]);
                        } else {
                            $cumulative[$val] = [$val];
                        }
                    }

                    foreach ($cumulative as $arr) {
                        foreach ($arr as $val) {
                            takesInt($val);
                        }
                    }

                    function takesInt(int $i) : void {}',
            ],
            'returnArrayWithDefinedKeys' => [
                '<?php
                    /**
                     * @param array{bar?: int, foo: int|string} $arr
                     * @return array{bar: int, foo: string}|null
                     */
                    function foo(array $arr) : ?array {
                        if (!isset($arr["bar"])) {
                            return null;
                        }

                        if (is_int($arr["foo"])) {
                            return null;
                        }

                        return $arr;
                    }',
            ],
            'arrayAccessAfterTwoIssets' => [
                '<?php
                    $arr = [];

                    foreach ([1, 2, 3] as $foo) {
                        if (!isset($arr["foo"])) {
                            $arr["foo"] = 0;
                        }

                        if (!isset($arr["bar"])) {
                            $arr["bar"] = 0;
                        }

                        echo $arr["bar"];
                    }',
            ],
            'issetAdditionalVar' => [
                '<?php
                    class Example {
                        const FOO = "foo";
                        /**
                         * @param array{bar:string} $params
                         */
                        public function test(array $params) : bool {
                            if (isset($params[self::FOO])) {
                                return true;
                            }

                            if (isset($params["bat"])) {
                                return true;
                            }

                            return false;
                        }
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'complainAboutBadCallInIsset' => [
                '<?php
                    class A {}
                    $a = isset(A::foo()[0]);',
                'error_message' => 'UndefinedMethod',
            ],
            'issetVariableKeysWithChange' => [
                '<?php
                    $arr = [[1, 2, 3], null, [1, 2, 3], null];
                    $b = 2;
                    $c = 0;
                    if (isset($arr[$b][$c])) {
                        $b = 1;
                        echo $arr[$b][$c];
                    }',
                'error_message' => 'NullArrayAccess',
            ],
            'issetAdditionalVarWithSealedObjectLike' => [
                '<?php
                    class Example {
                        const FOO = "foo";
                        public function test() : bool {
                            $params = ["bar" => "bat"];

                            if (isset($params[self::FOO])) {
                                return true;
                            }

                            return false;
                        }
                    }',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }
}
