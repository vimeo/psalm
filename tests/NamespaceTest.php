<?php
namespace Psalm\Tests;

class NamespaceTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'empty-namespace' => [
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
                    }'
            ],
            'constant-reference' => [
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
                    }'
            ]
        ];
    }
}
