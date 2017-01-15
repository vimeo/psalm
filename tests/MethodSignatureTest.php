<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;

class MethodSignatureTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;
    use Traits\FileCheckerInvalidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testExtendDocblockParamType()
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                class A extends SoapClient
                {
                   /**
                     * @param string $function_name
                     * @param array<mixed> $arguments
                     * @param array<mixed> $options default null
                     * @param array<mixed> $input_headers default null
                     * @param array<mixed> $output_headers default null
                     * @return mixed
                     */
                    public function __soapCall(
                        $function_name,
                        $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {

                    }
                }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MethodSignatureMismatch
     *
     * @return                   void
     */
    public function testExtendDocblockParamTypeWithWrongParam()
    {
        if (class_exists('SoapClient') === false) {
            $this->markTestSkipped('Cannot run test, base class "SoapClient" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                class A extends SoapClient
                {
                   /**
                     * @param string $function_name
                     * @param string $arguments
                     * @param array<mixed> $options default null
                     * @param array<mixed> $input_headers default null
                     * @param array<mixed> $output_headers default null
                     * @return mixed
                     */
                    public function __soapCall(
                        $function_name,
                        string $arguments,
                        $options = [],
                        $input_headers = [],
                        &$output_headers = []
                    ) {

                    }
                }'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
        $this->project_checker->checkClassReferences();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'privateArgs' => [
                '<?php
                    class A {
                        private function foo() : void {}
                    }
                    class B extends A {
                        private function foo(int $arg) : void {}
                    }',
            ],
            'nullableSubclassParamWithDefault' => [
                '<?php
                    class A {
                        public function foo(string $s) : string {
                            return $s;
                        }
                    }

                    class B extends A {
                        public function foo(string $s = null) : string {
                            return $s ?: "hello";
                        }
                    }

                    echo (new B)->foo();',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'moreArguments' => [
                '<?php
                    class A {
                        public function fooFoo(int $a, bool $b) : void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a, bool $b, array $c) : void {

                        }
                    }',
                'error_message' => 'Method B::fooFoo has more arguments than parent method A::fooFoo',
            ],
            'fewerArguments' => [
                '<?php
                    class A {
                        public function fooFoo(int $a, bool $b) : void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(int $a) : void {

                        }
                    }',
                'error_message' => 'Method B::fooFoo has fewer arguments than parent method A::fooFoo',
            ],
            'differentArguments' => [
                '<?php
                    class A {
                        public function fooFoo(int $a, bool $b) : void {

                        }
                    }

                    class B extends A {
                        public function fooFoo(bool $b, int $a) : void {

                        }
                    }',
                'error_message' => 'Argument 1 of B::fooFoo has wrong type \'bool\', expecting \'int\' as defined ' .
                    'by A::foo',
            ],
            'nonNullableSubclassParam' => [
                '<?php
                    class A {
                        public function foo(string $s = null) : string {
                            return $s ?: "hello";
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $s;
                        }
                    }',
                'error_message' => 'Argument 1 of B::foo has wrong type \'string\', expecting \'string|null\'',
            ],
            'mismatchingCovariantReturn' => [
                '<?php
                    class A {
                        function foo(): C {
                            return new C();
                        }
                    }
                    class B extends A {
                        function foo(): D {
                            return new D();
                        }
                    }
                    class C {}
                    class D extends C {}',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingCovariantReturnWithSelf' => [
                '<?php
                    class A {
                        function foo(): self {
                            return new A();
                        }
                    }
                    class B extends A {
                        function foo(): self {
                            return new B();
                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
        ];
    }
}
