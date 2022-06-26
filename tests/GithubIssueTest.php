<?php

namespace Psalm\Tests\ReturnTypeProvider;

use PHPUnit\Framework\Constraint\ExceptionMessageRegularExpression;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Config\PluginTest;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Throwable;

use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

/**
 * Test GitHub issues to see if they're still failing. This allows us to
 * easily notice when a GitHub issue is either fixed or partially improved.
 */
class GithubIssueTest extends TestCase
{
    use ValidCodeAnalysisTestTrait {
        testValidCode as _testValidCode;
    }
    use InvalidCodeAnalysisTestTrait {
        testInvalidCode as _testInvalidCode;
    }

    /**
     * This test is used to see if any GitHub issues have been fixed, so it's an inverted test. If this test is failing
     * for some case, that's a good thing! It means previously non-working code is now working, and you may have fixed
     * a GitHub issue. Double check the issue to make sure it's actually fixed, then move the test to another test class
     * so that it will continue to be tested in the future. Include "fixes #[issue number]" in your pull request.
     *
     * If you're not sure if the issue is actually fixed and you suspect the test may not be accurately testing the
     * GitHub issue, mention it in your pull request so that the reviewers can decide if the test needs to be skipped,
     * updated, or if it actually is fixed.
     *
     * @dataProvider providerValidCodeParse
     *
     * @param array<string, string> $assertions
     * @param array<array-key, string> $error_levels
     */
    public function testValidCode(
        string $code,
        array $assertions = [],
        array $error_levels = [],
        string $php_version = "7.3"
    ): void {
        $caught = null;
        try {
            /** @psalm-suppress IncorrectFunctionCasing #8170 */
            $this->_testValidCode($code, $assertions, $error_levels, $php_version);
        } catch (Throwable $e) {
            $caught = $e;
        }

        if ($caught === null) {
            $this->fail("Test now passes, github issue may be fixed.");
        }
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch intentionally includes all keys to make merging added tests to master easier
     * @return iterable<string, array{code: string, assertions?: array<string, string>, error_levels?: list<string>, php_version?: string}>
     */
    public function providerValidCodeParse(): iterable
    {
        foreach ($this->providerGithubReproducers() as $issue_number => ["valid" => $reproducers]) {
            foreach ($reproducers as $name => $reproducer) {
                yield "#$issue_number-$name" => $reproducer;
            }
        }
    }

    /**
     * This test is used to see if any GitHub issues have been fixed, so it's an inverted test. If this test is failing
     * for some case, that's a good thing! It means previously non-working code is now working, and you may have fixed
     * a GitHub issue. Double check the issue to make sure it's actually fixed, then move the test to another test class
     * so that it will continue to be tested in the future. Include "fixes #[issue number]" in your pull request.
     *
     * If you're not sure if the issue is actually fixed and you suspect the test may not be accurately testing the
     * GitHub issue, mention it in your pull request so that the reviewers can decide if the test needs to be skipped,
     * updated, or if it actually is fixed.
     *
     * @dataProvider providerInvalidCodeParse
     *
     * @param array<array-key, string> $error_levels
     */
    public function testInvalidCode(
        string $code,
        string $error_message,
        array $error_levels = [],
        bool $strict_mode = false,
        string $php_version = "7.3"
    ): void {
        $dummy_message = "dummy";
        try {
            /** @psalm-suppress IncorrectFunctionCasing #8170 */
            $this->_testInvalidCode($code, $dummy_message, $error_levels, $strict_mode, $php_version);
        } catch (Throwable $e) {
            if ($e instanceof CodeException
                && (new ExceptionMessageRegularExpression(self::convertErrorMessageToRegex($error_message)))
                    ->evaluate($e, "", true)
            ) {
                $this->fail("Test now passes, github issue may be fixed.");
            }
        }

        // No way to cancel expected exception
        throw new CodeException($dummy_message);
    }

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch intentionally includes all keys to make merging added tests to master easier
     * @return iterable<string, array{code: string, error_message: string, ignored_issues?: list<string>, strict_mode?: bool, php_version?: string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        foreach ($this->providerGithubReproducers() as $issue_number => ["invalid" => $reproducers]) {
            foreach ($reproducers as $name => $reproducer) {
                yield "#$issue_number-$name" => $reproducer;
            }
        }
    }

    /**
     * test_is_complete is purely informational, if set to true then if all of the tests get fixed it should mean the
     * GitHub issue is fixed, if it's set to false then the tests may be incomplete and fixing all of them won't
     * necessarily mean the GitHub issue can be closed.
     *
     * @return iterable<
     *     int,
     *     array{
     *         valid: iterable<
     *             string,
     *             array{code: string, assertions?: array<string, string>, error_levels?: list<string>, php_version?: string}
     *         >,
     *         invalid: iterable<
     *             string,
     *             array{code: string, error_message: string, ignored_issues?: list<string>, strict_mode?: bool, php_version?: string}
     *         >,
     *         test_is_complete: bool,
     *     }
     * >
     */
    public function providerGithubReproducers(): iterable
    {
        yield 5886 => [
            "valid" => [
                "nonThrowableInterfaceCanBeCaught" => [
                    "code" => '<?php
                        interface ExampleNotThrowable {}

                        class ExampleException extends RuntimeException implements ExampleNotThrowable {}

                        try {
                            throw new ExampleException();
                        } catch (ExampleNotThrowable $e) {
                            echo "Caught not throwable!";
                        }

                        /** @throws ExampleNotThrowable */
                        function foo(): void {}
                    ',
                ]
            ],
            "invalid" => [],
            "test_is_complete" => true,
        ];
        yield 8122 => [
            "valid" => [],
            "invalid" => [
                "covariantTemplateCannotBeUsedAsTemplateInMethodParam" => [
                    "code" => '<?php
                        /**
                         * @template-covariant T
                         */
                        class Collection
                        {
                            /** @var list<T> */
                            public $items;

                            /** @param list<T> $items */
                            public function __construct(array $items)
                            {
                                $this->items = $items;
                            }

                            /**
                             * @param Collection<T> $other
                             */
                            public function concatenate(Collection $other): void
                            {
                                $this->items = [...$this->items, ...$other->items];
                            }
                        }
                    ',
                    "error_message" => "InvalidTemplateParam",
                ],
            ],
            "test_is_complete" => true,
        ];
        yield 8169 => [
            "valid" => [],
            "invalid" => [
                "catchUndefinedMethodInUnusedTraitAlias" => [
                    "code" => '<?php
                        trait Foo
                        {
                            public function foo(): void {}
                        }

                        class Bar
                        {
                            use Foo {
                                bar as baz;
                            }
                        }
                    ',
                    "error_message" => "UndefinedMethod",
                ],
            ],
            "test_is_complete" => true,
        ];
        yield 8171 => [
            "valid" => [
                "trailingCommaInTemplatedType" => [
                    "code" => '<?php
                        /**
                         * @var iterable<
                         *     string,
                         *     array{
                         *         foo: int,
                         *         bar: int,
                         *         baz: int,
                         *     },
                         * >
                         */
                        $foobar = [];
                    ',
                ],
            ],
            "invalid" => [],
            "test_is_complete" => true,
        ];
    }

    /**
     * If this test fails then #8170 is fixed, and this test should be tweaked as commented and moved to PluginTest.
     */
    public function testFunctionCasingCheckerPluginWithTraitAlias(): void
    {
        $this->project_analyzer = PluginTest::getProjectAnalyzerWithConfig(
            $this->file_provider,
            TestConfig::loadFromXML(
                dirname(__DIR__, 1) . DIRECTORY_SEPARATOR, // NOTE: change to 2 when moving to PluginTest
                '<?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                >
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                    <plugins>
                        <plugin filename="examples/plugins/FunctionCasingChecker.php" />
                    </plugins>
                </psalm>'
            )
        );

        $this->project_analyzer->getCodebase()->config->initializePlugins($this->project_analyzer);

        $file_path = getcwd() . '/src/somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                trait Foo
                {
                    public function fooBarBaz(): void {}
                }

                class Bar
                {
                    use Foo {
                        fooBarBaz as bazBarFoo;
                    }

                    public function bar(): void
                    {
                        $this->bazBarFoo();
                    }
                }
            '
        );

        // NOTE: remove the exception and message check when moving to PluginTest
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage(
            "IncorrectFunctionCasing - src"
                . DIRECTORY_SEPARATOR
                . "somefile.php:15:32 - Function is incorrectly cased, expecting fooBarBaz"
        );

        $this->analyzeFile($file_path, new Context());
    }
}
