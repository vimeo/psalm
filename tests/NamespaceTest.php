<?php
namespace Psalm\Tests;

class NamespaceTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
