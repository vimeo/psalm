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
        ];
    }
}
