<?php
namespace Psalm\Tests;

class SwitchTypeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'get-class-arg' => [
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public function fooFoo() {
            
                        }
                    }
            
                    class B {
                        /**
                         * @return void
                         */
                        public function barBar() {
            
                        }
                    }
            
                    $a = rand(0, 10) ? new A() : new B();
            
                    switch (get_class($a)) {
                        case "A":
                            $a->fooFoo();
                            break;
            
                        case "B":
                            $a->barBar();
                            break;
                    }'
            ],
            'get-type-arg' => [
                '<?php
                    function testInt(int $var) : void {
            
                    }
            
                    function testString(string $var) : void {
            
                    }
            
                    $a = rand(0, 10) ? 1 : "two";
            
                    switch (gettype($a)) {
                        case "string":
                            testString($a);
                            break;
            
                        case "int":
                            testInt($a);
                            break;
                    }'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'get-class-arg-wrong-class' => [
                '<?php
                    class A {
                        /** @return void */
                        public function fooFoo() {
            
                        }
                    }
            
                    class B {
                        /** @return void */
                        public function barBar() {
            
                        }
                    }
            
                    $a = rand(0, 10) ? new A() : new B();
            
                    switch (get_class($a)) {
                        case "A":
                            $a->barBar();
                            break;
                    }',
                'error_message' => 'UndefinedMethod'
            ],
            'get-type-arg-wrong-args' => [
                '<?php
                    function testInt(int $var) : void {
            
                    }
            
                    function testString(string $var) : void {
            
                    }
            
                    $a = rand(0, 10) ? 1 : "two";
            
                    switch (gettype($a)) {
                        case "string":
                            testInt($a);
            
                        case "int":
                            testString($a);
                    }',
                'error_message' => 'InvalidScalarArgument'
            ]
        ];
    }
}
