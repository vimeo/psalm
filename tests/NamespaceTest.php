<?php
namespace Psalm\Tests;

class NamespaceTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'emptyNamespace' => [
                '<?php
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
                '<?php
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
                '<?php
                    namespace Foo;

                    $a = $argv;
                    $b = $argc;',
            ],
            'argvReferenceInFunction' => [
                '<?php
                    namespace Foo;

                    function foo() : void {
                        global $argv;

                        $c = $argv;
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'callNamespacedFunctionFromEmptyNamespace' => [
                '<?php
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
                '<?php
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
