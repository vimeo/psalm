<?php
namespace Psalm\Tests;

class AssertTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'assertArrayReturnTypeNarrowed' => [
                '<?php
                    /** @return array{0:Exception} */
                    function f(array $a): array {
                        if ($a[0] instanceof Exception) {
                            return $a;
                        }

                        return [new Exception("bad")];
                    }',
            ],
            'assertTypeNarrowedByAssert' => [
                '<?php
                    /** @return array{0:Exception,1:Exception} */
                    function f(array $ret): array {
                        assert($ret[0] instanceof Exception);
                        assert($ret[1] instanceof Exception);
                        return $ret;
                    }',
            ],
            'assertTypeNarrowedByButOtherFetchesAreMixed' => [
                '<?php
                    /**
                     * @return array{0:Exception}
                     * @psalm-suppress MixedArgument
                     */
                    function f(array $ret): array {
                        assert($ret[0] instanceof Exception);
                        echo strlen($ret[1]);
                        return $ret;
                    }',
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
            'assertCheckOnNonZeroArrayOffset' => [
                '<?php
                    /**
                     * @param array{string,array|null} $a
                     * @return string
                     */
                    function f(array $a) {
                        assert(is_array($a[1]));
                        return $a[0];
                    }',
            ],
            'assertOnParseUrlOutput' => [
                '<?php
                    /**
                     * @param array<"a"|"b"|"c", mixed> $arr
                     */
                    function uriToPath(array $arr) : string {
                        if (!isset($arr["a"]) || $arr["b"] !== "foo") {
                            throw new \InvalidArgumentException("bad");
                        }

                        return (string) $arr["c"];
                    }',
            ],
            'combineAfterLoopAssert' => [
                '<?php
                    function foo(array $array) : void {
                        $c = 0;

                        if ($array["a"] === "a") {
                            foreach ([rand(0, 1), rand(0, 1)] as $i) {
                                if ($array["b"] === "c") {}
                                $c++;
                            }
                        }
                    }',
            ],
            'assertOnXml' => [
                '<?php
                    function f(array $array) : void {
                        if ($array["foo"] === "ok") {
                            if ($array["bar"] === "a") {}
                            if ($array["bar"] === "b") {}
                        }
                    }',
            ],
            'assertOnBacktrace' => [
                '<?php
                    function _validProperty(array $c, array $arr) : void {
                        if (empty($arr["a"])) {}

                        if ($c && $c["a"] !== "b") {}
                    }',
            ],
            'assertOnRemainderOfArray' => [
                '<?php
                    /**
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function foo(string $file_name) : int {
                        while ($data = getData()) {
                            if (is_numeric($data[0])) {
                                for ($i = 1; $i < count($data); $i++) {
                                    return $data[$i];
                                }
                            }
                        }

                        return 5;
                    }

                    function getData() : ?array {
                        return rand(0, 1) ? ["a", "b", "c"] : null;
                    }',
            ],
            'notEmptyCheck' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     */
                    function load(string $objectName, array $config = []) : void {
                        if (isset($config["className"])) {
                            $name = $objectName;
                            $objectName = $config["className"];
                        }
                        if (!empty($config)) {}
                    }',
            ],
            'unsetAfterIssetCheck' => [
                '<?php
                    function checkbox(array $options = []) : void {
                        if ($options["a"]) {}

                        unset($options["a"], $options["b"]);
                    }',
            ],
            'dontCrashWhenGettingEmptyCountAssertions' => [
                '<?php
                    function foo() : bool {
                        /** @psalm-suppress TooFewArguments */
                        return count() > 0;
                    }',
            ],
            'assertHasArrayAccess' => [
                '<?php
                    /**
                     * @return array|ArrayAccess
                     */
                    function getBar(array $array) {
                        if (isset($array[\'foo\'][\'bar\'])) {
                            return $array[\'foo\'];
                        }

                        return [];
                    }',
            ],
            'assertHasArrayAccessWithType' => [
                '<?php
                    /**
                     * @param array<string, array<string, string>> $array
                     * @return array<string, string>
                     */
                    function getBar(array $array) : array {
                        if (isset($array[\'foo\'][\'bar\'])) {
                            return $array[\'foo\'];
                        }

                        return [];
                    }',
            ],
            'assertHasArrayAccessOnSimpleXMLElement' => [
                '<?php
                    function getBar(SimpleXMLElement $e, string $s) : void {
                        if (isset($e[$s])) {
                            echo (string) $e[$s];
                        }

                        if (isset($e[\'foo\'])) {
                            echo (string) $e[\'foo\'];
                        }

                        if (isset($e->bar)) {}
                    }',
            ],
            'assertArrayOffsetToTraversable' => [
                '<?php
                    function render(array $data): ?Traversable {
                        if ($data["o"] instanceof Traversable) {
                            return $data["o"];
                        }

                        return null;
                    }'
            ],
            'assertOnArrayShouldNotChangeType' => [
                '<?php
                    /** @return array|string|false */
                    function foo(string $a, string $b) {
                        $options = getopt($a, [$b]);

                        if (isset($options["config"])) {
                            $options["c"] = $options["config"];
                        }

                        if (isset($options["root"])) {
                            return $options["root"];
                        }

                        return false;
                    }'
            ],
            'assertOnArrayInTernary' => [
                '<?php
                    function foo(string $a, string $b) : void {
                        $o = getopt($a, [$b]);

                        $a = isset($o["a"]) && is_string($o["a"]) ? $o["a"] : "foo";
                        $a = isset($o["a"]) && is_string($o["a"]) ? $o["a"] : "foo";
                        echo $a;
                    }'
            ],
            'nonEmptyArrayAfterIsset' => [
                '<?php
                    /**
                     * @param array<string, int> $arr
                     * @return non-empty-array<string, int>
                     */
                    function foo(array $arr) : array {
                        if (isset($arr["a"])) {
                            return $arr;
                        }

                        return ["b" => 1];
                    }'
            ],
            'setArrayConstantOffset' => [
                '<?php
                    class S {
                        const A = 0;
                        const B = 1;
                        const C = 2;
                    }

                    function foo(array $arr) : void {
                        switch ($arr[S::A]) {
                            case S::B:
                            case S::C:
                            break;
                        }
                    }',
            ],
            'assertArrayWithPropertyOffset' => [
                '<?php
                    class A {
                        public int $id = 0;
                    }
                    class B {
                        public function foo() : void {}
                    }

                    /**
                     * @param array<int, B> $arr
                     */
                    function foo(A $a, array $arr): void {
                        if (!isset($arr[$a->id])) {
                            $arr[$a->id] = new B();
                        }
                        $arr[$a->id]->foo();
                    }'
            ],
            'assertAfterNotEmptyArrayCheck' => [
                '<?php
                    function foo(array $c): void {
                        if (!empty($c["d"])) {}

                        foreach (["a", "b", "c"] as $k) {
                            /** @psalm-suppress MixedAssignment */
                            foreach ($c[$k] as $d) {}
                        }
                    }',
            ],
            'assertNotEmptyTwiceOnInstancePropertyArray' => [
                '<?php
                    class A {
                        private array $c = [];

                        public function bar(string $s, string $t): void {
                            if (empty($this->c[$s]) && empty($this->c[$t])) {}
                        }
                    }'
            ],
            'assertNotEmptyTwiceOnStaticPropertyArray' => [
                '<?php
                    class A {
                        private static array $c = [];

                        public static function bar(string $s, string $t): void {
                            if (empty(self::$c[$s]) && empty(self::$c[$t])) {}
                        }
                    }'
            ],
            'assertConstantArrayOffsetTwice' => [
                '<?php
                    class A {
                        const FOO = "foo";
                        const BAR = "bar";

                        /** @psalm-suppress MixedArgument */
                        public function bar(array $args) : void {
                            if ($args[self::FOO]) {
                                echo $args[self::FOO];
                            }
                            if ($args[self::BAR]) {
                                echo $args[self::BAR];
                            }
                        }
                    }'
            ],
            'assertNotEmptyOnArray' => [
                '<?php
                    function foo(bool $c, array $arr) : void {
                        if ($c && !empty($arr["b"])) {
                            return;
                        }

                        if ($c && rand(0, 1)) {}
                    }'
            ],
            'assertIssetOnArray' => [
                '<?php
                    function foo(bool $c, array $arr) : void {
                        if ($c && $arr && isset($arr["b"]) && $arr["b"]) {
                            return;
                        }

                        if ($c && rand(0, 1)) {}
                    }'
            ],
            'assertMixedOffsetExists' => [
                '<?php
                    class A {
                        /** @var mixed */
                        private $arr;

                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress MixedReturnStatement
                         * @psalm-suppress MixedInferredReturnType
                         */
                        public function foo() : stdClass {
                            if (isset($this->arr[0])) {
                                return $this->arr[0];
                            }

                            $this->arr[0] = new stdClass;
                            return $this->arr[0];
                        }
                    }'
            ],
        ];
    }
}
