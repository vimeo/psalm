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
            'getClassArg' => [
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
                    }',
            ],
            'getClassExteriorArgClassConsts' => [
                '<?php
                    /** @return void */
                    function foo(Exception $e) {
                        switch (get_class($e)) {
                            case InvalidArgumentException::class:
                                $e->getMessage();
                                break;

                            case LogicException::class:
                                $e->getMessage();
                                break;
                        }
                    }

                    ',
            ],
            'getClassExteriorArg' => [
                '<?php
                    /** @return void */
                    function foo(Exception $e) {
                        switch (get_class($e)) {
                            case "InvalidArgumentException":
                                $e->getMessage();
                                break;

                            case "LogicException":
                                $e->getMessage();
                                break;
                        }
                    }

                    ',
            ],
            'getTypeArg' => [
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
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'getClassArgWrongClass' => [
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
                'error_message' => 'UndefinedMethod',
            ],
            'getTypeArgWrongArgs' => [
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
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
