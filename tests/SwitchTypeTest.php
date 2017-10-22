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

                        case "integer":
                            testInt($a);
                            break;
                    }',
            ],
            'switchTruthy' => [
                '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      switch (true) {
                        case $obj->a !== null:
                          return $obj->a; // definitely not null
                        case !is_null($obj->b):
                          return $obj->b; // definitely not null
                        default:
                          throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'switchMoTruthy' => [
                '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      switch (true) {
                        case $obj->a:
                          return $obj->a; // definitely not null
                        case $obj->b:
                          return $obj->b; // definitely not null
                        default:
                          throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
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
            'getClassMissingClass' => [
                '<?php
                    class A {}
                    class B {}

                    $a = rand(0, 10) ? new A() : new B();

                    switch (get_class($a)) {
                        case "C":
                            break;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'getTypeNotAType' => [
                '<?php
                    $a = rand(0, 10) ? 1 : "two";

                    switch (gettype($a)) {
                        case "int":
                            break;
                    }',
                'error_message' => 'UnevaluatedCode',
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

                        case "integer":
                            testString($a);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
