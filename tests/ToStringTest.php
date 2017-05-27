<?php
namespace Psalm\Tests;

class ToStringTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'validToString' => [
                '<?php
                    class A {
                        function __toString() : string {
                            return "hello";
                        }
                    }
                    echo (new A);',
            ],
            'validInferredToStringType' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        function __toString() {
                            return "hello";
                        }
                    }
                    echo (new A);',
            ],
            'goodCast' => [
                '<?php
                    class A {
                        public function __toString() : string
                        {
                            return "hello";
                        }
                    }
            
                    /** @param string|A $b */
                    function fooFoo($b) : void {}
            
                    /** @param A|string $b */
                    function barBar($b) : void {}
            
                    fooFoo(new A());
                    barBar(new A());',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'echoClass' => [
                '<?php
                    class A {}
                    echo (new A);',
                'error_message' => 'InvalidArgument',
            ],
            'invalidToStringReturnType' => [
                '<?php
                    class A {
                        function __toString() : void { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'invalidInferredToStringReturnType' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        function __toString() { }
                    }',
                'error_message' => 'InvalidToString',
            ],
            'implicitCost' => [
                '<?php
                    class A {
                        public function __toString() : string
                        {
                            return "hello";
                        }
                    }
            
                    function fooFoo(string $b) : void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast',
            ],
        ];
    }
}
