<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function getcwd;

use const DIRECTORY_SEPARATOR;

class IssueSuppressionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->project_analyzer->getCodebase()->find_unused_variables = true;
    }

    public function testIssueSuppressedOnFunction(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
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
                }',
        );

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new Context());
    }

    public function testIssueSuppressedOnStatement(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress InvalidArgument */
                echo strlen("hello");',
        );

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new Context());
    }

    public function testUnusedSuppressAllOnFunction(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');


        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress all */
                function foo(): string {
                    return "foo";
                }',
        );

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new Context());
    }

    public function testUnusedSuppressAllOnStatement(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress all */
                print("foo");',
        );
        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', new Context());
    }

    public function testMissingThrowsDocblockSuppressed(): void
    {
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
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
                }',
        );

        $context = new Context();

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testMissingThrowsDocblockSuppressedWithoutThrow(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                if (rand(0, 1)) {
                    function example (): void {}
                }',
        );

        $context = new Context();

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testMissingThrowsDocblockSuppressedDuplicate(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_docblock = true;

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress MissingThrowsDocblock */
                function example1 (): void {
                    /** @psalm-suppress MissingThrowsDocblock */
                    throw new Exception();
                }',
        );

        $context = new Context();

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testUncaughtThrowInGlobalScopeSuppressed(): void
    {
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php

                if (rand(0, 1)) {
                    /** @psalm-suppress UncaughtThrowInGlobalScope */
                    throw new Exception();
                }

                /** @psalm-suppress UncaughtThrowInGlobalScope */
                if (rand(0, 1)) {
                    throw new Exception();
                }

                /** @psalm-suppress UncaughtThrowInGlobalScope */
                throw new Exception();',
        );

        $context = new Context();

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testUncaughtThrowInGlobalScopeSuppressedWithoutThrow(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnusedPsalmSuppress');
        Config::getInstance()->check_for_throws_in_global_scope = true;

        $this->addFile(
            (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php',
            '<?php
                /** @psalm-suppress UncaughtThrowInGlobalScope */
                echo "hello";',
        );

        $context = new Context();

        $this->analyzeFile((string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php', $context);
    }

    public function testPossiblyUnusedPropertySuppressedOnClass(): void
    {
        $this->project_analyzer->getCodebase()->find_unused_code = "always";

        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                /** @psalm-suppress PossiblyUnusedProperty */
                class Foo {
                    public string $bar = "baz";
                }

                $_foo = new Foo();
            ',
        );

        $this->analyzeFile($file_path, new Context(), false);
        $this->project_analyzer->consolidateAnalyzedData();
        IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    public function testPossiblyUnusedPropertySuppressedOnProperty(): void
    {
        $this->project_analyzer->getCodebase()->find_unused_code = "always";

        $file_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                class Foo {
                    /** @psalm-suppress PossiblyUnusedProperty */
                    public string $bar = "baz";
                }

                $_foo = new Foo();
            ',
        );

        $this->analyzeFile($file_path, new Context(), false);
        $this->project_analyzer->consolidateAnalyzedData();
        IssueBuffer::processUnusedSuppressions($this->project_analyzer->getCodebase()->file_provider);
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'undefinedClassSimple' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();',
            ],
            'excludeIssue' => [
                'code' => '<?php
                    fooFoo();',
                'assertions' => [],
                'ignored_issues' => ['UndefinedFunction'],
            ],
            'suppressWithNewlineAfterComment' => [
                'code' => '<?php
                    function foo() : void {
                        /**
                         * @psalm-suppress TooManyArguments
                         * here
                         */
                        echo strlen("a", "b");
                    }',
            ],
            'suppressUndefinedFunction' => [
                'code' => '<?php
                    function verify_return_type(): DateTime {
                        /** @psalm-suppress UndefinedFunction */
                        unknown_function_call();

                        return new DateTime();
                    }',
            ],
            'suppressAllStatementIssues' => [
                'code' => '<?php
                    /** @psalm-suppress all */
                    echo strlen(123, 456, 789);',
            ],
            'suppressAllFunctionIssues' => [
                'code' => '<?php
                    /** @psalm-suppress all */
                    function foo($a)
                    {
                        echo strlen(123, 456, 789);
                    }',
            ],
            'possiblyNullSuppressedAtClassLevel' => [
                'code' => '<?php
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
                'code' => '<?php
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
            ],
            'missingPropertyTypeAtPropertyLevel' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-suppress MissingPropertyType
                         */
                        public $bar = "baz";
                    }
                ',
            ],
            'suppressUnusedSuppression' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-suppress UnusedPsalmSuppress, MissingPropertyType
                         */
                        public string $bar = "baz";

                        /**
                         * @psalm-suppress UnusedPsalmSuppress, MissingReturnType
                         */
                        public function foobar(): string
                        {
                            return "foobar";
                        }
                    }
                ',
            ],
            'suppressUnevaluatedCode' => [
                'code' => '<?php
                    die();
                    /**
                     * @psalm-suppress UnevaluatedCode
                     */
                    break;
                ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedClassOneLineWithLineAfter' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedClass
                     */
                    new B();
                    new C();',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:25 - Class, interface or enum named C',
            ],
            'missingParamTypeShouldntPreventUndefinedClassError' => [
                'code' => '<?php
                    /** @psalm-suppress MissingParamType */
                    function foo($s = Foo::BAR) : void {}',
                'error_message' => 'UndefinedClass',
            ],
            'suppressUnusedSuppressionByItselfIsNotSuppressed' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @psalm-suppress UnusedPsalmSuppress
                         */
                        public string $bar = "baz";
                    }
                ',
                'error_message' => 'UnusedPsalmSuppress',
            ],
        ];
    }
}
