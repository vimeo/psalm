<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;
use Psalm\Config;
use Psalm\Context;

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
     * @return void
     */
    public function testUnusedSuppressAllOnFunction()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress all */
                function foo(): string {
                    return "foo";
                }'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return void
     */
    public function testUnusedSuppressAllOnStatement()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress all */
                print("foo");'
        );

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return void
     */
    public function testMissingThrowsDocblockSuppressed()
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function example1 (): void {
                    /** @psalm-suppress MissingThrowsDocblock */
                    throw new Exception();
                }

                /** @psalm-suppress MissingThrowsDocblock */
                if (rand(0, 1)) {
                    function example2 (): void {
                        throw new Exception();
                    }
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testMissingThrowsDocblockSuppressedWithoutThrow()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                if (rand(0, 1)) {
                    function example (): void {}
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testMissingThrowsDocblockSuppressedDuplicate()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                function example1 (): void {
                    /** @psalm-suppress MissingThrowsDocblock */
                    throw new Exception();
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testUncaughtThrowInGlobalScopeSuppressed()
    {
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress UncaughtThrowInGlobalScope */
                throw new Exception();

                if (rand(0, 1)) {
                    /** @psalm-suppress UncaughtThrowInGlobalScope */
                    throw new Exception();
                }

                /** @psalm-suppress UncaughtThrowInGlobalScope */
                if (rand(0, 1)) {
                    throw new Exception();
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return void
     */
    public function testUncaughtThrowInGlobalScopeSuppressedWithoutThrow()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress UncaughtThrowInGlobalScope */
                strlen("a");'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
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
            'suppressAllStatementIssues' => [
                '<?php
                    /** @psalm-suppress all */
                    strlen(123, 456, 789);',
            ],
            'suppressAllFunctionIssues' => [
                '<?php
                    /** @psalm-suppress all */
                    function foo($a)
                    {
                        strlen(123, 456, 789);
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
