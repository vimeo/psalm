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
            'getClassConstArg' => [
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
                        case A::class:
                            $a->fooFoo();
                            break;

                        case B::class:
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
                    }',
            ],
            'switchGetClassVar' => [
                '<?php
                    class A {}
                    class B extends A {
                      public function foo() : void {}
                    }

                    function takesA(A $a) : void {
                      $class = get_class($a);
                      switch ($class) {
                        case B::class:
                          $a->foo();
                          break;
                      }
                    }',
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
            'switchWithBadBreak' => [
                '<?php
                    class A {}

                    function foo(): A {
                        switch (rand(0,1)) {
                            case true:
                                return new A;
                                break;
                            default:
                                return new A;
                        }
                    }',
            ],
            'switchCaseExpression' => [
                '<?php
                    switch (true) {
                        case preg_match("/(d)ata/", "some data in subject string", $matches):
                            return $matches[1];
                        default:
                            throw new RuntimeException("none found");
                    }',
            ],
            'switchBools' => [
                '<?php
                    $x = false;
                    $y = false;

                    foreach ([1, 2, 3] as $v)  {
                        switch($v) {
                            case 3:
                                $y = true;
                                break;
                            case 2:
                                $x = true;
                                break;
                            default:
                                break;
                        }
                    }',
                'assertions' => [
                    '$x' => 'bool',
                    '$y' => 'bool',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'switchReturnTypeWithFallthroughAndBreak' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    break;
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
            'switchReturnTypeWithFallthroughAndConditionalBreak' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                    if (rand(0,10) === 5) {
                                        break;
                                    }
                                default:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
            'switchReturnTypeWithNoDefault' => [
                '<?php
                    class A {
                        /** @return bool */
                        public function fooFoo() {
                            switch (rand(0,10)) {
                                case 1:
                                case 2:
                                    return true;
                            }
                        }
                    }',
                'error_message' => 'InvalidNullableReturnType',
            ],
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
            'switchBadMethodCallInCase' => [
                '<?php
                    function f(string $p) : void { }

                    switch (true) {
                        case $q = (bool) rand(0,1):
                            f($q); // this type problem is not detected
                            break;
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
