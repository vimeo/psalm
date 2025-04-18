<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class NamespaceTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'emptyNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @return void */
                        function foo() {

                        }

                        class Bar {

                        }
                    }
                    namespace {
                        A\foo();
                        \A\foo();

                        (new A\Bar);
                    }',
            ],
            'constantReference' => [
                'code' => '<?php
                    namespace Aye\Bee {
                        const HELLO = "hello";
                    }
                    namespace Aye\Bee {
                        /** @return void */
                        function foo() {
                            echo \Aye\Bee\HELLO;
                        }

                        class Bar {
                            /** @return void */
                            public function foo() {
                                echo \Aye\Bee\HELLO;
                            }
                        }
                    }',
            ],
            'argvReference' => [
                'code' => '<?php
                    namespace Foo;

                    $a = $argv;
                    $b = $argc;',
            ],
            'argvReferenceInFunction' => [
                'code' => '<?php
                    namespace Foo;

                    function foo() : void {
                        global $argv;

                        $c = $argv;
                    }',
            ],
            'varsAreNotScoped' => [
                'code' => '<?php
                    namespace A {
                        $a = "1";
                    }
                    namespace B\C {
                        $bc = "2";
                    }
                    namespace {
                        echo $a . PHP_EOL;
                        echo $bc . PHP_EOL;
                    }
                ',
                'assertions' => [
                    '$a===' => "'1'",
                    '$bc===' => "'2'",
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'callNamespacedFunctionFromEmptyNamespace' => [
                'code' => '<?php
                    namespace A {
                        /** @return void */
                        function foo() {

                        }
                    }
                    namespace {
                        foo();
                    }',
                'error_message' => 'UndefinedFunction',
            ],
            'callRootFunctionFromNamespace' => [
                'code' => '<?php
                    namespace {
                        /** @return void */
                        function foo() {

                        }
                    }
                    namespace A {
                        \A\foo();
                    }',
                'error_message' => 'UndefinedFunction',
            ],
        ];
    }
}
