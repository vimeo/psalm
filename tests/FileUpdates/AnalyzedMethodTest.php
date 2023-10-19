<?php

declare(strict_types=1);

namespace Psalm\Tests\FileUpdates;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function array_keys;
use function getcwd;
use function strpos;

use const DIRECTORY_SEPARATOR;

class AnalyzedMethodTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new ParserInstanceCacheProvider(),
            null,
            null,
            new FakeFileReferenceCacheProvider(),
            new ProjectCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
        );
        $this->project_analyzer->setPhpVersion('7.3', 'tests');
    }

    /**
     * @dataProvider providerTestValidUpdates
     * @param array<string, string> $start_files
     * @param array<string, string> $end_files
     * @param array<string, string> $ignored_issues
     */
    public function testValidInclude(
        array $start_files,
        array $end_files,
        array $initial_analyzed_methods,
        array $unaffected_analyzed_methods,
        array $ignored_issues = [],
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $this->project_analyzer->getCodebase()->diff_methods = true;

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;
        $config->throw_exception = false;

        foreach ($ignored_issues as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        foreach ($start_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $this->assertSame([], $codebase->analyzer->getAnalyzedMethods());

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->assertSame(
            $initial_analyzed_methods,
            $codebase->analyzer->getAnalyzedMethods(),
            'initial analyzed methods are not the same',
        );

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $codebase->reloadFiles($this->project_analyzer, array_keys($end_files));

        $codebase->analyzer->loadCachedResults($this->project_analyzer);

        $this->assertSame(
            $unaffected_analyzed_methods,
            $codebase->analyzer->getAnalyzedMethods(),
            'unaffected analyzed methods are not the same',
        );
    }

    /**
     * @return array<string,array{start_files:array<string,string>,end_files:array<string,string>,initial_analyzed_methods:array<string,array<string,int>>,unaffected_analyzed_methods:array<string,array<string,int>>,0?:array<string,string>}>
     */
    public function providerTestValidUpdates(): array
    {
        return [
            'basicRequire' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A{
                            public function fooFoo(): void {

                            }

                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A{
                            public function fooFoo(?string $foo = null): void {

                            }

                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => 1,
                        'foo\a::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                        'foo\b::noreturntype' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                        'foo\b::noreturntype' => 1,
                    ],
                ],
                [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
            ],
            'invalidateAfterPropertyChange' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo = "bar";
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var int */
                            public $foo = 5;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                    ],
                ],
            ],
            'invalidateAfterStaticPropertyChange' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public static $foo = "bar";
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var int */
                            public static $foo = 5;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                    ],
                ],
            ],
            'invalidateAfterStaticFlipPropertyChange' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public static $foo = "bar";
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo = "bar";
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                    ],
                ],
            ],
            'invalidateAfterConstantChange' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public const FOO = "bar";
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public const FOO = 5;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                    ],
                ],
            ],
            'dontInvalidateTraitMethods' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(?string $foo = null): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                        'foo\b::noreturntype' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                        'foo\b::noreturntype' => 1,
                    ],
                ],
                [
                    'MissingReturnType' => Config::REPORT_INFO,
                ],
            ],
            'invalidateTraitMethodsWhenTraitRemoved' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(?string $foo = null): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function fooFoo(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ],
            ],
            'invalidateTraitMethodsWhenTraitReplaced' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            public function fooFoo(?string $foo = null): void { }

                            public function barBar(): int {
                                return 5;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ],
            ],
            'invalidateTraitMethodsWhenMethodChanged' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
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
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function fooFoo(?string $foo = null): void { }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }

                            public function bar() : void {
                                echo (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
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
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                        'foo\a::bat&foo\t::bat' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::foofoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::foo' => 1,
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::bat&foo\t::bat' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ],
            ],
            'invalidateTraitMethodsWhenMethodSuperimposed' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function bar() : string {
                                return (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            public function barBar(): int {
                                return 5;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                        namespace Foo;

                        class B {
                            public function bar() : string {
                                return (new A)->barBar();
                            }
                        }',
                     (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            public function barBar(): string {
                                return "hello";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::barbar&foo\t::barbar' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [
                        'foo\b::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'B.php' => [],
                ],
            ],
            'dontInvalidateConstructor' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            private function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            private function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                ],

                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::setfoo' => 1,
                        'foo\a::reallysetfoo' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::setfoo' => 1,
                        'foo\a::reallysetfoo' => 1,
                    ],
                ],
            ],
            'invalidateConstructorWhenDependentMethodChanges' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            private function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            private function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                //$this->foo = "bar";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::setfoo' => 1,
                        'foo\a::reallysetfoo' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::setfoo' => 1,
                    ],
                ],
            ],
            'invalidateConstructorWhenDependentMethodInSubclassChanges' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        abstract class A {
                            public function __construct() {
                                $this->setFoo();
                            }

                            abstract protected function setFoo() : void;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            /** @var string */
                            public $foo;

                            protected function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        abstract class A {
                            public function __construct() {
                                $this->setFoo();
                            }

                            abstract protected function setFoo() : void;
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            /** @var string */
                            public $foo;

                            protected function setFoo() : void {
                                $this->reallySetFoo();
                            }

                            private function reallySetFoo() : void {
                                //$this->foo = "bar";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 1,
                        'foo\a::setfoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [
                        'foo\achild::setfoo' => 1,
                        'foo\achild::reallysetfoo' => 1,
                        'foo\achild::__construct' => 2,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 1,
                        'foo\a::setfoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [
                        'foo\achild::setfoo' => 1,
                    ],
                ],
            ],
            'invalidateConstructorWhenDependentMethodInSubclassChanges2' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            protected function setFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            public function __construct() {
                                parent::__construct();
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }

                            protected function setFoo() : void {
                                $this->foo = "baz";
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            public function __construct() {
                                parent::__construct();
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::setfoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [
                        'foo\achild::__construct' => 2,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [],
                ],
            ],
            'invalidateConstructorWhenDependentTraitMethodChanges' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            private function setFoo() : void {
                                $this->foo = "bar";
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            use T;

                            /** @var string */
                            public $foo;

                            public function __construct() {
                                $this->setFoo();
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                        namespace Foo;

                        trait T {
                            private function setFoo() : void {
                                //$this->foo = "bar";
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [
                        'foo\a::setfoo&foo\t::setfoo' => 1,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'T.php' => [],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [],
                ],
            ],
            'rescanPropertyAssertingMethod' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string */
                            private $foo;

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            /** @var string|null */
                            private $foo;

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                    ],
                ],
                [
                    'PropertyNotSetInConstructor' => Config::REPORT_INFO,
                    'DocblockTypeContradiction' => Config::REPORT_INFO,
                    'RedundantConditionGivenDocblockType' => Config::REPORT_INFO,
                ],
            ],
            'noChangeAfterSyntaxError' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            private ?string $foo;

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            private ?string $foo

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::bar' => 1,
                    ],
                ],
            ],
            'nothingBeforeSyntaxError' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            private ?string $foo

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        class A {
                            private ?string $foo;

                            public function __construct() {}

                            public function bar() : void {
                                if ($this->foo === null) {}
                            }
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::bar' => 1,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                        'foo\a::bar' => 1,
                    ],
                ],
            ],
            'modifyPropertyOfChildClass' => [
                'start_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        abstract class A {
                            protected $arr = [1, 2, 3];

                            protected string $b;

                            public function __construct(int $a, string $b) {
                                echo $this->arr[$a];
                                $this->b = $b;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            public $arr = [1, 2, 3, 4];
                        }',
                ],
                'end_files' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                        namespace Foo;

                        abstract class A {
                            protected $arr = [1, 2, 3];

                            protected string $b;

                            public function __construct(int $a, string $b) {
                                echo $this->arr[$a];
                                $this->b = $b;
                            }
                        }',
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => '<?php
                        namespace Foo;

                        class AChild extends A {
                            protected $arr;
                        }',
                ],
                'initial_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [
                        'foo\achild::__construct' => 2,
                    ],
                ],
                'unaffected_analyzed_methods' => [
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => [
                        'foo\a::__construct' => 2,
                    ],
                    (string) getcwd() . DIRECTORY_SEPARATOR . 'AChild.php' => [],
                ],
            ],
        ];
    }

    public function testFileMapsUpdated(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;
        $config->throw_exception = false;

        $this->file_provider->registerFile('somefile.php', '
            <?php

            function foo() : void {
            }

            foo();
        ');

        $codebase->addFilesToAnalyze(['somefile.php' => 'somefile.php']);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $maps = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertNotEmpty($maps[0]);

        $this->file_provider->setOpenContents('somefile.php', '');

        $codebase->reloadFiles($this->project_analyzer, ['somefile.php']);
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $updated_maps = $codebase->analyzer->getMapsForFile('somefile.php');

        $this->assertSame([], $updated_maps[0]);
        $this->assertSame([], $updated_maps[1]);
        $this->assertSame([], $updated_maps[2]);
    }
}
