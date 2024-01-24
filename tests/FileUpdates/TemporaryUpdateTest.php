<?php

namespace Psalm\Tests\FileUpdates;

use Psalm\Codebase;
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
use UnexpectedValueException;

use function array_keys;
use function array_shift;
use function count;
use function end;
use function getcwd;

use const DIRECTORY_SEPARATOR;

class TemporaryUpdateTest extends TestCase
{
    protected Codebase $codebase;

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
            new ProjectCacheProvider(),
        );

        $this->codebase = new Codebase($config, $providers);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            null,
            [],
            1,
            null,
            $this->codebase,
        );

        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @dataProvider providerTestErrorFix
     * @param array<int, array<string, string>> $file_stages
     * @param array<int, array<int>> $error_positions
     * @param array<string, string> $ignored_issues
     */
    public function testErrorFix(
        array $file_stages,
        array $error_positions,
        array $ignored_issues = [],
        bool $test_save = true,
        bool $check_unused_code = false
    ): void {
        $codebase = $this->codebase;
        $codebase->diff_methods = true;

        if ($check_unused_code) {
            $codebase->reportUnusedCode();
        }

        $config = $codebase->config;

        foreach ($ignored_issues as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        if (!$file_stages) {
            throw new UnexpectedValueException('$file_stages should not be empty');
        }

        $start_files = array_shift($file_stages);

        // first batch
        foreach ($start_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);

        $data = IssueBuffer::clear();

        $found_positions = [];

        foreach ($data as $file_issues) {
            foreach ($file_issues as $issue_data) {
                $found_positions[] = $issue_data->from;
            }
        }

        $this->assertSame($error_positions[0], $found_positions);

        $i = 0;

        foreach ($file_stages as $i => $file_stage) {
            foreach ($file_stage as $file_path => $contents) {
                $codebase->addTemporaryFileChanges(
                    $file_path,
                    $contents,
                );
            }

            $codebase->reloadFiles($this->project_analyzer, array_keys($file_stage));

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);

            $data = IssueBuffer::clear();

            $found_positions = [];

            foreach ($data as $file_issues) {
                foreach ($file_issues as $issue_data) {
                    $found_positions[] = $issue_data->from;
                }
            }

            $this->assertSame($error_positions[$i + 1], $found_positions, 'stage ' . ($i + 2));
        }

        if ($test_save && $file_stages) {
            $last_file_stage = end($file_stages);

            foreach ($last_file_stage as $file_path => $_) {
                $codebase->removeTemporaryFileChanges($file_path);
            }

            foreach ($last_file_stage as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
            }

            $codebase->reloadFiles($this->project_analyzer, array_keys($last_file_stage));

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);

            $data = IssueBuffer::clear();

            $found_positions = [];

            foreach ($data as $file_issues) {
                foreach ($file_issues as $issue_data) {
                    $found_positions[] = $issue_data->from;
                }
            }

            $this->assertSame($error_positions[count($file_stages)], $found_positions, 'stage ' . ($i + 2));
        }
    }

    /**
     * @return array<string,array{0: array<int, array<string, string>>,error_positions:array<int, array<int>>, ignored_issues?:array<string, string>, test_save?:bool, check_unused_code?: bool}>
     */
    public function providerTestErrorFix(): array
    {
        return [
            'fixMissingColonSyntaxError' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5;
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    $a = 5;
                                    echo $a;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [230], []],
            ],
            'addReturnTypesToSingleMethod' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() {
                                    return 5;
                                }

                                public function bar() {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return 5;
                                }

                                public function bar() {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return 5;
                                }

                                public function bar() : int {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[136, 317, 273], [323, 279], [329]],
            ],
            'addSpaceAffectingOffsets' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    $a = 5;
                                    return 5;
                                }

                                public function bar() : int {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    $a = 5;

                                    return 5;
                                }

                                public function bar() : int {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    $a = 5;


                                    return 5;
                                }

                                public function bar() : int {
                                    $a = $GLOBALS["foo"];
                                    return $this->foo();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[373], [374], [375]],
                'ignored_issues' => [
                    'MixedAssignment' => Config::REPORT_INFO,
                ],
            ],
            'fixReturnType' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : string {
                                    return 5;
                                }

                                public function bar() : int {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : string {
                                    return "hello";
                                }

                                public function bar() : int {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : string {
                                    return "hello";
                                }

                                public function bar() : int {
                                    return 5;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[196, 144, 339, 290], [345, 296], []],
                'ignored_issues' => [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
            ],
            'resolveNamesInDifferentFunction' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /**
                                 * @param string (A::class | B::class)
                                 * @return string
                                 */
                                public function foo($a) {
                                    return A::class;
                                }

                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /**
                                 * @param string $a - one of (A::class | B::class)
                                 * @return string
                                 */
                                public function foo($a) {
                                    return A::class;
                                }

                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[333], []],
                'ignored_issues' => [
                    'InvalidDocblock' => Config::REPORT_INFO,
                ],
            ],
            'bridgeStatements' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() {
                                    return 5;
                                }

                                public function bar() {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return 5;
                                }

                                public function bar() {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : int {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[136, 273], [279], [193, 144]],
                'ignored_issues' => [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
            ],
            'colonReturnType' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() {
                                    return 5;
                                }

                                public function bar() {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : {
                                    return 5;
                                }

                                public function bar() {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[136, 273], [144, 136, 275]],
                'ignored_issues' => [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
                'test_save' => false,
            ],
            'noChangeJustWeirdDocblocks' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public $aB = 5;

                                const F = 1;

                                public function bat() : void {
                                    $a = 1;
                                }

                                /*
                                 * another
                                 */
                                /**
                                 * @return void
                                 */
                                public function foo() {
                                    $a = 1;
                                }

                                // this is one line
                                // this is another
                                public function bar() : void {
                                    $b = 1;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public $aB = 5;

                                const F = 1;

                                public function bat() : void {
                                    $a = 1;
                                    $b = 1;
                                }

                                /*
                                 * another
                                 */
                                /**
                                 * @return void
                                 */
                                public function foo() {
                                    $a = 1;
                                }

                                // this is one line
                                // this is another
                                public function bar() : void {
                                    $b = 1;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[127], [127]],
            ],
            'removeUseShouldInvalidate' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use Exception;

                            class A {
                                public function foo() : void {
                                    throw new Exception();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new Exception();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'removeGroupUseShouldInvalidate' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use PhpParser\{Error};

                            class A {
                                public function foo() : void {
                                    throw new Error("bad", []);
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new Error("bad", []);
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'removeUseWithAliasShouldInvalidate' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use Exception as E;

                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'removeGroupUseWithAliasShouldInvalidate' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use PhpParser\{Error as E};

                            class A {
                                public function foo() : void {
                                    throw new E("bad", []);
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new E("bad", []);
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'removeUseShouldInvalidateNoNamespace' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            use PhpParser\Node\Name;

                            class A {
                                public function foo() : void {
                                    new Name("Martin");
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public function foo() : void {
                                    new Name("Martin");
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [147]],
            ],
            'removeGroupUseShouldInvalidateNoNamespace' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use PhpParser\{Error};

                            class A {
                                public function foo() : void {
                                    throw new Error("bad", []);
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new Error("bad", []);
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'removeUseWithAliasShouldInvalidateNoNamespace' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            use Exception as E;

                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [153]],
            ],
            'removeGroupUseWithAliasShouldInvalidateNoNamespace' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            use PhpParser\{Error as E};

                            class A {
                                public function foo() : void {
                                    throw new E("bad", []);
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new E("bad", []);
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [197]],
            ],
            'addUseShouldValidate' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'E.php' => '<?php
                            namespace Bar;

                            class E extends \Exception {}',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use Bar\E;

                            class A {
                                public function foo() : void {
                                    throw new E();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'E.php' => '<?php
                            namespace Bar;

                            class E extends \Exception {}',
                    ],
                ],
                'error_positions' => [[197], []],
            ],
            'changeUseShouldInvalidateBadNew' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A {
                                    public function foo() : void {
                                        new B();
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A {
                                    public function foo() : void {
                                        new B();
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[247], []],
            ],
            'changeUseShouldInvalidateBadReturn' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A {
                                    public function foo() : ?B {
                                        return null;
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A {
                                    public function foo() : ?B {
                                        return null;
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[196], []],
            ],
            'changeUseShouldInvalidateBadDocblockReturn' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A {
                                    /** @return ?B */
                                    public function foo() {
                                        return null;
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A {
                                    /** @return ?B */
                                    public function foo() {
                                        return null;
                                    }
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[184], []],
            ],
            'changeUseShouldInvalidateBadParam' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A {
                                    public function foo(B $b) : void {}
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A {
                                    public function foo(B $b) : void {}
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[192], []],
            ],
            'changeUseShouldInvalidateBadDocblockParam' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A {
                                    /** @param B $b */
                                    public function foo($b) : void {}
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A {
                                    /** @param B $b */
                                    public function foo($b) : void {}
                                }
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[183], []],
            ],
            'changeUseShouldInvalidateBadExtends' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Baz\B;

                                class A extends B {}
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo {
                                use Bar\B;

                                class A extends B {}
                            }

                            namespace Bar {
                                class B {}
                            }',
                    ],
                ],
                'error_positions' => [[142], []],
            ],
            'fixMissingProperty' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                    echo $this->bar;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                private $bar = "hello";
                                public function foo() : void {
                                    echo $this->bar;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[192, 192], []],
            ],
            'traitMethodRenameDifferentFiles' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bat();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bat();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [238, 231], [], [238, 231], []],
            ],
            'traitMethodRenameSameFile' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bat();
                                }
                            }

                            trait T {
                                public function bat() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bat();
                                }
                            }

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                                public function foo() : void {
                                    echo $this->bar();
                                }
                            }

                            trait T {
                                public function bar() : string {
                                    return "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [238, 231], [], [238, 231], []],
            ],
            'duplicateMethodThenRemove' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                ],
                'error_positions' => [[], [381], []],
            ],
            'classCopiesUse' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            use B\A;

                            class A {}',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                    ],
                ],
                'error_positions' => [[], [122], []],
            ],
            'addMissingArgs' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            function variadic_arguments(string $_foo, ...$bars ) : void {}

                            function foo() : void {
                                variadic_arguments(
                                    $baz,
                                    $qux
                                );
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            function variadic_arguments(string $_foo, string ...$bars ) : void {}

                            function foo(string $baz, string $qux) : void {
                                variadic_arguments(
                                    $baz,
                                    $qux
                                );
                            }',
                    ],
                ],
                'error_positions' => [[79, 238, 280, 238], []],
            ],
            'fixClassRef' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class SomeClass {}

                            class B {
                                /** @var ?string */
                                private static $s = null;

                                public function foo() : void {
                                    new SomeClas();
                                }

                                public function bar() : void {
                                    self::$s = "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class SomeClass {}

                            class B {
                                /** @var ?string */
                                private static $s = null;

                                public function foo() : void {
                                    new SomeClas();
                                }

                                public function bar() : void {
                                    self::$si = "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[306], [306, 452, 452]],
            ],
            'addPropertyDocblock' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {}

                            class B
                            {
                                private $bar = [];
                                private $baz = [];

                                public static function get() : A
                                {
                                    return new A();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {}

                            class B
                            {
                                /**
                                 * @var array<string, string>
                                 */
                                private $bar = [];
                                private $baz = [];

                                public static function get() : A
                                {
                                    return new A();
                                }
                            }',
                    ],
                ],
                'error_positions' => [[152, 203], [337]],
            ],
            'fixNotNullProperty' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string|null
                                 */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                ],
                'error_positions' => [[230], []],
            ],
            'dontFixNotNullProperty' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                ],
                'error_positions' => [[230], [230]],
            ],
            'requiredFileWithConstructorInitialisation' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            require_once("B.php");',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                public function __construct() {
                                    //$this->foo = "hello";
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            require_once("B.php");',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            class B
                            {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                public function __construct() {
                                    $this->foo = "hello";
                                }
                            }',
                    ],
                ],
                'error_positions' => [[230], []],
            ],
            'updatePropertyInitialization' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                /**
                                 * @var int
                                 */
                                public $bar;

                                public function __construct(string $foo, int $bar) {
                                    $this->foo = $foo;
                                    $this->bar = $bar;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                /**
                                 * @var int
                                 */
                                public $bar;

                                public function __construct(string $foo, int $bar) {
                                    // $this->foo = $foo;
                                    $this->bar = $bar;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class B {
                                /**
                                 * @var string
                                 */
                                public $foo;

                                /**
                                 * @var int
                                 */
                                public $bar;

                                public function __construct(string $foo, int $bar) {
                                    $this->foo = $foo;
                                    $this->bar = $bar;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], [202], []],
            ],
            'addPartialMethodWithSyntaxError' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                public function baz() : void {
                                    if (rand(0, 1)) {}
                                }

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                public function baz() : void {
                                    if (rand(0, 1)) {
                                }

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                /**
                                 * @return void
                                 */
                                public static function foo() {}

                                public function baz() : void {
                                    if (rand(0, 1)) {}
                                }

                                /**
                                 * @return void
                                 */
                                public static function bar(
                                    string $function_id
                                ) {}
                            }',
                    ],
                ],
                'error_positions' => [[], [381], []],
            ],
            'reformat' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public function b(): void {

                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public function b(): void
                                {
                                }
                            }',
                    ],
                ],
                'error_positions' => [[], []],
            ],
            'dontForgetErrorInTraitMethod' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                            }

                            (new A)->foo();',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function foo() : void {
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                            }

                            (new A)->foo();',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function foo() : void {
                                    echo $a;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[192, 192], [192, 192]],
            ],
            'stillUnusedClass' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {}',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            new B();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {}',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            new B();',
                    ],
                ],
                'error_positions' => [[84], [84]],
                'ignored_issues' => [],
                'test_save' => false,
                'check_unused_code' => true,
            ],
            'stillUnusedMethod' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}

                                public function bar() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            (new A())->foo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {
                                }

                                public function bar() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            (new A())->foo();',
                    ],
                ],
                'error_positions' => [[201], [234]],
                'ignored_issues' => [],
                'test_save' => false,
                'check_unused_code' => true,
            ],
            'usedMethodWithNoAffectedConstantChanges' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class Z {
                                const ONE = "1";
                                const TWO = "2";

                                public static function foo() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function doFoo() : void {
                                    echo Z::ONE;
                                    Z::foo();
                                    echo Z::TWO;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            (new B())->doFoo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class Z {
                                const ONE = "1";
                                const TWO = "2";
                                const THREE = "3";

                                public static function foo() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function doFoo() : void {
                                    echo Z::ONE;
                                    Z::foo();
                                    echo Z::TWO;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'user.php' => '<?php
                            namespace Foo;

                            (new B())->doFoo();',
                    ],
                ],
                'error_positions' => [[], []],
                'ignored_issues' => [],
                'test_save' => false,
                'check_unused_code' => true,
            ],
            'syntaxErrorFixed' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public static function foo() : void {
                                    $a = 5;
                                    //foreach ([1, 2, 3] as $b) {
                                        echo $b;
                                    }
                                    echo $a;
                                }

                                public static function bar() : void {
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public static function foo() : void {
                                    $a = 5;
                                    foreach ([1, 2, 3] as $b) {
                                        echo $b;
                                    }
                                    echo $a;
                                }

                                public static function bar() : void {
                                    echo $a;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[347, 452, 538], [500, 500]],
            ],
            'updateExampleWithSyntaxErrorThen' => [
                [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public static function foo() : void {
                                    $a = 5;
                                    foreach ([1, 2, 3] as $b) {
                                        echo $b;
                                    }
                                    echo $a;
                                }

                                public static function bar() : void {
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public static function foo() : void {
                                    $a = 5;
                                    //foreach ([1, 2, 3] as $b) {
                                        echo $b;
                                    }
                                    echo $a;
                                }

                                public static function bar() : void {
                                    echo $a;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            class A {
                                public static function foo() : void {
                                    $a = 5;
                                    foreach ([1, 2, 3] as $b) {
                                        echo $b;
                                    }
                                    echo $a;
                                }

                                public static function bar() : void {
                                    echo $a;
                                }
                            }',
                    ],
                ],
                'error_positions' => [[500, 500], [347, 452, 538], [500, 500]],
            ],
        ];
    }
}
