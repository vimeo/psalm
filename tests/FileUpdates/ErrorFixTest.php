<?php

namespace Psalm\Tests\FileUpdates;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function array_keys;
use function count;
use function getcwd;

use const DIRECTORY_SEPARATOR;

class ErrorFixTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;

        $providers = new Providers(
            $this->file_provider,
            new ParserInstanceCacheProvider(),
            null,
            null,
            new FakeFileReferenceCacheProvider(),
            new ProjectCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @dataProvider providerTestErrorFix
     *
     * @param array<int, array<string, string>> $files
     * @param array<int, int> $error_counts
     * @param array<string, string> $ignored_issues
     *
     */
    public function testErrorFix(
        array $files,
        array $error_counts,
        array $ignored_issues = []
    ): void {
        $this->project_analyzer->getCodebase()->diff_methods = true;

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        foreach ($ignored_issues as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        $analyzed_files = [];

        for ($i = 0; $i < count($files); ++$i) {
            $batch = $files[$i];

            foreach ($batch as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);

                if (!isset($analyzed_files[$file_path])) {
                    $codebase->addFilesToAnalyze([$file_path => $file_path]);
                    $analyzed_files[$file_path] = true;
                }
            }

            if ($i === 0) {
                $codebase->scanFiles();
            } else {
                $codebase->reloadFiles($this->project_analyzer, array_keys($batch));
            }

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

            $expected_count = 0;

            $data = IssueBuffer::clear();

            foreach ($data as $file_issues) {
                $expected_count += count($file_issues);
            }

            $this->assertSame($error_counts[$i], $expected_count);
        }
    }

    /**
     * @return array<string,array{files: array<int, array<string,string>>,error_counts:array<int,int>,ignored_issues?:array<string,string>}>
     */
    public function providerTestErrorFix(): array
    {
        return [
            'fixMissingColonSyntaxError' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5;
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5;
                                    echo $a;
                                }
                            }',
                    ],
                ],
                'error_counts' => [0, 1, 0],
            ],
            'addReturnTypesToSingleMethod' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() {
                                    return 5;
                                }

                                public function bar() {
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return 5;
                                }

                                public function bar() {
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return 5;
                                }

                                public function bar() : int {
                                    return $this->foo();
                                }
                            }',
                    ],
                ],
                'error_counts' => [2, 1, 0],
                'ignored_issues' => [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
            ],
            'traitMethodRenameFirstCorrect' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_counts' => [0, 2, 0],
            ],
            'traitMethodRenameFirstError' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_counts' => [2, 0, 0],
            ],
            'addSuppressions' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class C {
                                public function foo(array $a) : void {
                                    foreach ($a as $b) {
                                        $b->bar();
                                    }
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class C {
                                public function foo(array $a) : void {
                                    /**
                                     * @psalm-suppress MixedAssignment
                                     */
                                    foreach ($a as $b) {
                                        $b->bar();
                                    }
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class C {
                                public function foo(array $a) : void {
                                    /**
                                     * @psalm-suppress MixedAssignment
                                     */
                                    foreach ($a as $b) {
                                        /**
                                         * @psalm-suppress MixedMethodCall
                                         */
                                        $b->bar();
                                    }
                                }
                            }',
                    ],
                ],
                'error_counts' => [2, 1, 0],
            ],
            'fixDefault' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class C {
                                /** @var string */
                                public $foo = 5;
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class C {
                                /** @var string */
                                public $foo = "hello";
                            }',
                    ],
                ],
                'error_counts' => [1, 0],
            ],
            'changeContent' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            function add(int $a, int $b): int {
                                return $a + $b;
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            function hasMethod(object $input, string $method): bool {
                                return (new ReflectionClass($input))
                                    ->hasMethod($method);
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            function add(int $a, int $b): int {
                                return $a + $b;
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'D.php' => '<?php
                            function hasMethod(object $input, string $method): bool {
                                return (new ReflectionClass($input))
                                    ->hasMethod($method);
                            }',
                    ],
                ],
                'error_counts' => [0, 0, 0, 0],
            ],
            'missingConstructorForTwoVars' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                protected int $x;
                                protected int $y;
                            }'
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                protected int $x = 0;
                                protected int $y;
                            }'
                    ],
                ],
                'error_counts' => [2, 1],
            ],
            'missingConstructorForInheritedProperties' => [
                'files' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            abstract class A {
                                public int $x;
                                public int $y;
                            }

                            class B extends A {
                                public function __construct() {}
                            }'
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            abstract class A {
                                public int $x = 0;
                                public int $y;
                            }

                            class B extends A {
                                public function __construct() {}
                            }'
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            abstract class A {
                                public int $x = 0;
                                public int $y = 0;
                            }

                            class B extends A {
                                public function __construct() {}
                            }'
                    ],
                ],
                'error_counts' => [2, 1, 0],
            ],
        ];
    }
}
