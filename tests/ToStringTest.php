<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

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
            'valid-to-string' => [
                '<?php
                    class A {
                        function __toString() : string {
                            return "hello";
                        }
                    }
                    echo (new A);'
            ],
            'valid-inferred-to-string-type' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        function __toString() {
                            return "hello";
                        }
                    }
                    echo (new A);'
            ],
            'good-cast' => [
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
                    barBar(new A());'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'echo-class' => [
                '<?php
                    class A {}
                    echo (new A);',
                'error_message' => 'InvalidArgument'
            ],
            'invalid-to-string-return-type' => [
                '<?php
                    class A {
                        function __toString() : void { }
                    }',
                'error_message' => 'InvalidToString'
            ],
            'invalid-inferred-to-string-return-type' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress MissingReturnType
                         */
                        function __toString() { }
                    }',
                'error_message' => 'InvalidToString'
            ],
            'implicit-cost' => [
                '<?php
                    class A {
                        public function __toString() : string
                        {
                            return "hello";
                        }
                    }
            
                    function fooFoo(string $b) : void {}
                    fooFoo(new A());',
                'error_message' => 'ImplicitToStringCast'
            ]
        ];
    }
}
