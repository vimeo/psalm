<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class MethodSignatureTest extends TestCase
{
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

        $this->project_checker->registerFile(
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

        $this->project_checker->registerFile(
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
        ];
    }
}
