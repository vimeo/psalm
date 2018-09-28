<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Provider\Providers;

class FileUpdateTest extends TestCase
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

        $providers = new Providers(
            $this->file_provider,
            new \Psalm\Tests\Provider\ParserInstanceCacheProvider()
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
     * @dataProvider providerTestValidIncludes
     *
     * @param array<string, string> $start_files
     * @param array<string, string> $end_files
     * @param array<string, string> $error_levels
     *
     * @return void
     */
    public function testValidInclude(
        array $start_files,
        array $end_files,
        array $initial_correct_methods,
        array $unaffected_correct_methods,
        array $error_levels = []
    ) {
        $this->project_checker->cache_results = true;

        $codebase = $this->project_checker->getCodebase();

        $config = $codebase->config;

        foreach ($error_levels as $error_level) {
            $config->setCustomErrorLevel($error_level, \Psalm\Config::REPORT_SUPPRESS);
        }

        foreach ($start_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $this->assertSame([], $codebase->analyzer->getCorrectMethods());

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $previous_correct_methods = $codebase->analyzer->getCorrectMethods();

        $this->assertSame(
            $initial_correct_methods,
            $previous_correct_methods
        );

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_checker, array_keys($end_files));
        $codebase->analyzer->setCorrectMethods($previous_correct_methods);

        $this->assertSame($previous_correct_methods, $codebase->analyzer->getCorrectMethods());

        $codebase->scanFiles();
        $codebase->analyzer->loadCachedResults($this->project_checker);

        $this->assertSame(
            $unaffected_correct_methods,
            $codebase->analyzer->getCorrectMethods()
        );
    }

    /**
     * @return array
     */
    public function providerTestValidIncludes()
    {
        return [
            'basicRequire' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A{
                            public function fooFoo(): void {

                            }

                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A{
                            public function fooFoo(?string $foo = null): void {

                            }

                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => true,
                        'foo\a::barbar' => true
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar' => true
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true
                    ],
                ],
            ],
        ];
    }
}
