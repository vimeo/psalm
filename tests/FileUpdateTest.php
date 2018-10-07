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
     * @dataProvider providerTestValidUpdates
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
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $this->project_checker->cache_results = true;

        $codebase = $this->project_checker->getCodebase();

        $config = $codebase->config;

        foreach ($error_levels as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        foreach ($start_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $this->assertSame([], $codebase->analyzer->getCorrectMethods());

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);

        $this->assertSame(
            $initial_correct_methods,
            $codebase->analyzer->getCorrectMethods()
        );

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_checker, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();
        $codebase->analyzer->loadCachedResults($this->project_checker);

        $this->assertSame(
            $unaffected_correct_methods,
            $codebase->analyzer->getCorrectMethods()
        );
    }

    /**
     * @dataProvider providerTestInvalidUpdates
     *
     * @param array<int, array<string, string>> $file_stages
     * @param array<string, string> $error_levels
     *
     * @return void
     */
    public function testErrorAfterUpdate(
        array $file_stages,
        string $error_message,
        array $error_levels = []
    ) {
        $this->project_checker->cache_results = true;

        $codebase = $this->project_checker->getCodebase();

        $config = $codebase->config;

        foreach ($error_levels as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        $end_files = array_pop($file_stages);

        foreach ($file_stages as $files) {
            foreach ($files as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
            }

            $codebase->reloadFiles($this->project_checker, array_keys($files));

            foreach ($files as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
                $codebase->addFilesToAnalyze([$file_path => $file_path]);
            }

            $codebase->scanFiles();

            $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);
        }

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_checker, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase->analyzer->analyzeFiles($this->project_checker, 1, false);
    }

    /**
     * @return array
     */
    public function providerTestValidUpdates()
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

                            public function noReturnType() {}
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

                            public function noReturnType() {}
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => true,
                        'foo\a::barbar' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                        'foo\b::noreturntype' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar' => true
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                        'foo\b::noreturntype' => true,
                    ],
                ],
                [
                    'MissingReturnType' => \Psalm\Config::REPORT_INFO,
                ]
            ],
            'invalidateAfterPropertyChange' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo = "bar";
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return (new A)->foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var int */
                            public $foo = 5;
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return (new A)->foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                    ],
                ]
            ],
            'invalidateAfterStaticPropertyChange' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public static $foo = "bar";
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::$foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var int */
                            public static $foo = 5;
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::$foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                    ],
                ]
            ],
            'invalidateAfterStaticFlipPropertyChange' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public static $foo = "bar";
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::$foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo = "bar";
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::$foo;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                    ],
                ]
            ],
            'invalidateAfterConstantChange' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public const FOO = "bar";
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::FOO;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public const FOO = 5;
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo() : string {
                                return A::FOO;
                            }

                            public function bar() : void {
                                $a = new A();
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                    ],
                ]
            ],
            'dontInvalidateTraitMethods' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
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

                            public function noReturnType() {}
                        }',
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(?string $foo = null): void { }
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

                            public function noReturnType() {}
                        }',
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                        'foo\a::foofoo' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                        'foo\b::noreturntype' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                        'foo\b::noreturntype' => true,
                    ],
                ],
                [
                    'MissingReturnType' => \Psalm\Config::REPORT_INFO,
                ]
            ],
            'invalidateTraitMethodsWhenTraitRemoved' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function fooFoo(?string $foo = null): void { }
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                        'foo\a::foofoo' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true, // this doesn't exist, so we don't care
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ]
            ],
            'invalidateTraitMethodsWhenTraitReplaced' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function fooFoo(?string $foo = null): void { }

                            public function barBar(): int {
                                return 5;
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                        'foo\a::foofoo' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ]
            ],
            'invalidateTraitMethodsWhenMethodChanged' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }

                            public function bat(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(?string $foo = null): void { }
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
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): int {
                                return 5;
                            }

                            public function bat(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                        'foo\a::bat&foo\t::bat' => true,
                        'foo\a::foofoo' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => true,
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::bat&foo\t::bat' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ]
            ],
            'invalidateTraitMethodsWhenMethodSuperimposed' => [
                'start_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function bar() : string {
                                return (new A)->barBar();
                            }
                        }',
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function barBar(): int {
                                return 5;
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function bar() : string {
                                return (new A)->barBar();
                            }
                        }',
                     getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar&foo\t::barbar' => true,
                    ],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => true,
                    ],
                ],
                'unaffected_correct_methods' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerTestInvalidUpdates()
    {
        return [
            'invalidateParentCaller' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : void {
                                    (new B)->foo();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : void {
                                    (new B)->foo();
                                }
                            }',
                    ],
                ],
                'error_message' => 'UndefinedMethod',
            ],
            'invalidateAfterPropertyTypeChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var int */
                                public $foo = 5;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement'
            ],
            'invalidateAfterConstantChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = 5;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement'
            ],
            'invalidateAfterSkippedAnalysis' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }

                                public function bat() : void {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : ?string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }
                            }',
                    ],
                ],
                'error_message' => 'NullableReturnStatement'
            ],
            'invalidateAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;
                            }',
                    ],
                ],
                'error_message' => 'MissingConstructor'
            ],
        ];
    }
}
