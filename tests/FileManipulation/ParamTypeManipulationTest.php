<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Tests\Internal\Provider;

class ParamTypeManipulationTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'fixMismatchingDocblockParamType70' => [
                '<?php
                    /**
                     * @param int $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @param string $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'fixNamespacedMismatchingDocblockParamsType70' => [
                '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param \B $b
                             * @param \C $c
                             */
                            function foo(B $b, C $c): string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param B $b
                             * @param C $c
                             */
                            function foo(B $b, C $c): string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'noStringParamType' => [
                '<?php
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                    }',
                '<?php
                    /**
                     * @param string $a
                     */
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'stringParamTypeAndConcatNoop' => [
                '<?php
                    function fooFoo(string $a): void {
                        echo $a . "foo";
                    }',
                '<?php
                    function fooFoo(string $a): void {
                        echo $a . "foo";
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButConcat' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a . "foo";
                    }',
                '<?php
                    /**
                     * @param string|int $a
                     */
                    function fooFoo($a): void {
                        echo $a . "foo";
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButConcatAndStringUsage' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a . "foo";
                        echo substr($a, 4, 2);
                    }',
                '<?php
                    /**
                     * @param string $a
                     */
                    function fooFoo($a): void {
                        echo $a . "foo";
                        echo substr($a, 4, 2);
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButConcatAndStringUsageReversed' => [
                '<?php
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                        echo $a . "foo";
                    }',
                '<?php
                    /**
                     * @param string $a
                     */
                    function fooFoo($a): void {
                        echo substr($a, 4, 2);
                        echo $a . "foo";
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButAddition' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a + 5;
                    }',
                '<?php
                    /**
                     * @param int|float $a
                     */
                    function fooFoo($a): void {
                        echo $a + 5;
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButAdditionAndDefault' => [
                '<?php
                    function fooFoo($a = 5): void {
                        takesInt($a);
                    }

                    function takesInt(int $i) {}',
                '<?php
                    /**
                     * @param int $a
                     */
                    function fooFoo($a = 5): void {
                        takesInt($a);
                    }

                    function takesInt(int $i) {}',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButIntUseAndNullCheck' => [
                '<?php
                    function fooFoo($a): void {
                        if ($a === null) {
                            return;
                        }
                        takesInt($a);
                    }

                    function takesInt(int $i) {}',
                '<?php
                    /**
                     * @param null|int $a
                     */
                    function fooFoo($a): void {
                        if ($a === null) {
                            return;
                        }
                        takesInt($a);
                    }

                    function takesInt(int $i) {}',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButDivision' => [
                '<?php
                    function fooFoo($a): void {
                        echo $a / 5;
                    }',
                '<?php
                    /**
                     * @param int|float $a
                     */
                    function fooFoo($a): void {
                        echo $a / 5;
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButTemplatedString' => [
                '<?php
                    function fooFoo($a): void {
                        echo "$a";
                    }',
                '<?php
                    /**
                     * @param string|int|float $a
                     */
                    function fooFoo($a): void {
                        echo "$a";
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noParamTypeButTemplatedStringAntStringUsage' => [
                '<?php
                    function fooFoo($a): void {
                        echo "$a";
                        echo substr($a, 4, 2);
                    }',
                '<?php
                    /**
                     * @param string $a
                     */
                    function fooFoo($a): void {
                        echo "$a";
                        echo substr($a, 4, 2);
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noStringIntParamType' => [
                '<?php
                    function fooFoo($a): void {
                        if (is_string($a)) {
                            echo substr($a, 4, 2);
                        } else {
                            echo substr("hello", $a, 2);
                        }
                    }',
                '<?php
                    /**
                     * @param int|string $a
                     */
                    function fooFoo($a): void {
                        if (is_string($a)) {
                            echo substr($a, 4, 2);
                        } else {
                            echo substr("hello", $a, 2);
                        }
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'alreadyHasCheck' => [
                '<?php
                    function takesString(string $s): void {}

                    function shouldTakeString($s): void {
                        if (is_string($s)) {
                            takesString($s);
                        }
                    }',
                '<?php
                    function takesString(string $s): void {}

                    function shouldTakeString($s): void {
                        if (is_string($s)) {
                            takesString($s);
                        }
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'isSetBeforeInferrence' => [
                '<?php
                    function takesString(string $s): void {}

                    /** @return mixed */
                    function returnsMixed() {}

                    function shouldTakeString($s): void {
                        $s = returnsMixed();
                        takesString($s);
                    }',
                '<?php
                    function takesString(string $s): void {}

                    /** @return mixed */
                    function returnsMixed() {}

                    function shouldTakeString($s): void {
                        $s = returnsMixed();
                        takesString($s);
                    }',
                '7.1',
                ['MissingParamType'],
                true,
            ],
        ];
    }
}
