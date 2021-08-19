<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;

use const DIRECTORY_SEPARATOR;

class IssueSuppressionTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    public function testIssueSuppressedOnFunction(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
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

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new \Psalm\Context());
    }

    public function testIssueSuppressedOnStatement(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress InvalidArgument */
                echo strlen("hello");'
        );

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new \Psalm\Context());
    }

    public function testUnusedSuppressAllOnFunction(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');


        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress all */
                function foo(): string {
                    return "foo";
                }'
        );

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new \Psalm\Context());
    }

    public function testUnusedSuppressAllOnStatement(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress all */
                print("foo");'
        );
        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new \Psalm\Context());
    }

    public function testMissingThrowsDocblockSuppressed(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
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

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testMissingThrowsDocblockSuppressedWithoutThrow(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                if (rand(0, 1)) {
                    function example (): void {}
                }'
        );

        $context = new Context();

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testMissingThrowsDocblockSuppressedDuplicate(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                function example1 (): void {
                    /** @psalm-suppress MissingThrowsDocblock */
                    throw new Exception();
                }'
        );

        $context = new Context();

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testUncaughtThrowInGlobalScopeSuppressed(): void
    {
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
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

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testUncaughtThrowInGlobalScopeSuppressedWithoutThrow(): void
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress UncaughtThrowInGlobalScope */
                echo "hello";'
        );

        $context = new Context();

        $this->analyzeFile(getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
            'multipleIssues' => [
                '<?php
                    class A {
                        /**
                         * @psalm-suppress UndefinedClass, MixedMethodCall,MissingReturnType because reasons
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
            'possiblyNullSuppressedAtClassLevel' => [
                '<?php
                    /** @psalm-suppress PossiblyNullReference */
                    class C {
                        private ?DateTime $mightBeNull = null;

                        public function m(): string {
                            return $this->mightBeNull->format("");
                        }
                    }
                ',
            ],
            'methodSignatureMismatchSuppressedAtClassLevel' => [
                '<?php
                    class ParentClass {
                        /**
                         * @psalm-suppress MissingParamType
                         * @return mixed
                         */
                        public function func($var) {
                            return $var;
                        }
                    }

                    /** @psalm-suppress MethodSignatureMismatch */
                    class MismatchMethod extends ParentClass {
                        /** @return mixed */
                        public function func(string $var) {
                            return $var;
                        }
                    }
                ',
            ]
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:33 - Class, interface or enum named C',
            ],
            'undefinedClassOneLineInFileAfter' => [
                '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();
                    new C();',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:25 - Class, interface or enum named C',
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
