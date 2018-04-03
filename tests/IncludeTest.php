<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;

class IncludeTest extends TestCase
{
    /**
     * @dataProvider providerTestValidIncludes
     *
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     *
     * @return void
     */
    public function testValidInclude(array $files, array $files_to_check)
    {
        $codebase = $this->project_checker->getCodebase();

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($files_to_check as $file_path) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $config = $codebase->config;

        foreach ($files_to_check as $file_path) {
            $file_checker = new FileChecker($this->project_checker, $file_path, $config->shortenFileName($file_path));
            $file_checker->analyze();
        }
    }

    /**
     * @dataProvider providerTestInvalidIncludes
     *
     * @param array<int, string> $files_to_check
     * @param array<string, string> $files
     * @param mixed $error_message
     *
     * @return void
     */
    public function testInvalidInclude(array $files, array $files_to_check, $error_message)
    {
        $codebase = $this->project_checker->getCodebase();

        foreach ($files as $file_path => $contents) {
            $this->addFile($file_path, $contents);
            $codebase->scanner->addFilesToShallowScan([$file_path => $file_path]);
        }

        foreach ($files_to_check as $file_path) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->scanFiles();

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $config = $codebase->config;

        foreach ($files_to_check as $file_path) {
            $file_checker = new FileChecker($this->project_checker, $file_path, $config->shortenFileName($file_path));
            $file_checker->analyze();
        }
    }

    /**
     * @return array
     */
    public function providerTestValidIncludes()
    {
        return [
            'basicRequire' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new A)->fooFoo();
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'nestedRequire' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B extends A{
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require("file2.php");

                        class C extends B {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
            'requireNamespace' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new Foo\A);
                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireFunction' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(): void {

                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        fooFoo();',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'namespacedRequireFunction' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo(): void {

                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        namespace Foo;

                        require("file1.php");

                        fooFoo();',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireConstant' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        const FOO = 5;
                        define("BAR", "bat");',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        echo FOO;
                        echo BAR;',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'requireNamespacedWithUse' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        use Foo\A;

                        class B {
                            public function foo(): void {
                                (new A);
                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'noInfiniteRequireLoop' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        require_once("file3.php");

                        class B extends A {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }

                        class C {}

                        new D();',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");

                        class A{
                            public function fooFoo(): void { }
                        }

                        new C();',

                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require_once("file1.php");

                        class D{ }

                        new C();',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
            'analyzeAllClasses' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        class B extends A {
                            public function doFoo(): void {
                                $this->fooFoo();
                            }
                        }
                        class C {
                            public function barBar(): void { }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file1.php");
                        class A{
                            public function fooFoo(): void { }
                        }
                        class D extends C {
                            public function doBar(): void {
                                $this->barBar();
                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'loopWithInterdependencies' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        class A {}
                        class D extends C {}
                        new B();',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file1.php");
                        class C {}
                        class B extends A {}
                        new D();',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'variadicArgs' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        require_once("file2.php");
                        variadicArgs(5, 2, "hello");',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        function variadicArgs() {
                            $args = func_get_args();
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php',
                ],
            ],
            'returnNamespacedFunctionCallType' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;

                        class A{
                            function doThing() : void {}
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        namespace Bar;

                        use Foo\A;

                        /** @return A */
                        function getThing() {
                            return new A;
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require("file2.php");

                        namespace Bat;

                        class C {
                            function boop() : void {
                                \Bar\getThing()->doThing();
                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerTestInvalidIncludes()
    {
        return [
            'undefinedMethodInRequire' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B {
                            public function foo(): void {
                                (new A)->fooFo();
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo(): void {

                            }
                        }',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
                'error_message' => 'UndefinedMethod',
            ],
        ];
    }
}
