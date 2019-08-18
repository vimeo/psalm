<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class IssueSuppressionTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testIssueSuppressedOnFunction()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /**
                     * @psalm-suppress UndefinedClass
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MissingReturnType
                     * @psalm-suppress UnusedVariable
                     */
                    public function b() {
                        B::fooFoo()->barBar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return void
     */
    public function testIssueSuppressedOnStatement()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress InvalidArgument */
                echo strpos("hello", "e");'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'undefinedClassSimple' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UndefinedClass
                         * @psalm-suppress MixedMethodCall
                         * @psalm-suppress MissingReturnType
                         */
                        public function b() {
                            B::fooFoo()->barBar()->bat()->baz()->bam()->bas()->bee()->bet()->bes()->bis();
                        }
                    }',
            ],
            'undefinedClassOneLine' => [
                '<?php
                    class A {
                        public function b(): void {
                            /**
                             * @psalm-suppress UndefinedClass
                             */
                            new B();
                        }
                    }',
            ],
            'undefinedClassOneLineInFile' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();',
            ],
            'excludeIssue' => [
                '<?php
                    fooFoo();',
                'assertions' => [],
                'error_levels' => ['UndefinedFunction'],
            ],
            'suppressWithNewlineAfterComment' => [
                '<?php
                    function foo() : void {
                        /**
                         * @psalm-suppress TooManyArguments
                         * here
                         */
                        strlen("a", "b");
                    }',
            ],
            'suppressUndefinedFunction' => [
                '<?php
                    function verify_return_type(): DateTime {
                        /** @psalm-suppress UndefinedFunction */
                        unknown_function_call();

                        return new DateTime();
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'undefinedClassOneLineWithLineAfter' => [
                '<?php
                    class A {
                        public function b() {
                            /**
                             * @psalm-suppress UndefinedClass
                             */
                            new B();
                            new C();
                        }
                    }',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:33 - Class or interface C',
            ],
            'undefinedClassOneLineInFileAfter' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();
                    new C();',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:25 - Class or interface C',
            ],
            'missingParamTypeShouldntPreventUndefinedClassError' => [
                '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($s = Foo::BAR) : void {}',
                'error_message' => 'UndefinedClass',
            ],
        ];
    }
}
