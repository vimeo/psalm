<?php
namespace Psalm\Tests\FileUpdates;

use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Provider\Providers;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Provider;

class ErrorFixTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        FileChecker::clearCache();

        $this->file_provider = new \Psalm\Tests\Provider\FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;

        $providers = new Providers(
            $this->file_provider,
            new \Psalm\Tests\Provider\ParserInstanceCacheProvider(),
            null,
            null,
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_checker = new ProjectChecker(
            $config,
            $providers,
            false,
            true,
            ProjectChecker::TYPE_CONSOLE,
            1,
            false
        );

        $this->project_checker->infer_types_from_usage = true;
    }

    /**
     * @dataProvider providerTestErrorFix
     *
     * @param array<string, string> $start_files
     * @param array<string, string> $middle_files
     * @param array<string, string> $end_files
     * @param array<int, int> $error_counts
     * @param array<string, string> $error_levels
     *
     * @return void
     */
    public function testErrorFix(
        array $start_files,
        array $middle_files,
        array $end_files,
        array $error_counts,
        array $error_levels = []
    ) {
        $this->project_checker->diff_methods = true;

        $codebase = $this->project_checker->getCodebase();

        $config = $codebase->config;

        foreach ($error_levels as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        // first batch
        foreach ($start_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $data = \Psalm\IssueBuffer::clear();

        $this->assertSame($error_counts[0], count($data));

        // second batch
        foreach ($middle_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_checker, array_keys($middle_files));

        foreach ($middle_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $data = \Psalm\IssueBuffer::clear();

        $this->assertSame($error_counts[1], count($data));

        // third batch
        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_checker, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $data = \Psalm\IssueBuffer::clear();

        $this->assertSame($error_counts[2], count($data));
    }

    /**
     * @return array
     */
    public function providerTestErrorFix()
    {
        return [
            'fixMissingColonSyntaxError' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function foo() : void {
                                $a = 5;
                                echo $a;
                            }
                        }',
                ],
                'middle_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function foo() : void {
                                $a = 5
                                echo $a;
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function foo() : void {
                                $a = 5;
                                echo $a;
                            }
                        }',
                ],
                'error_counts' => [0, 1, 0],
            ],
            'addReturnTypesToSingleMethod' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                'middle_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                'error_counts' => [2, 1, 0],
                [
                    'MissingReturnType' => \Psalm\Config::REPORT_INFO,
                ]
            ],
        ];
    }
}
