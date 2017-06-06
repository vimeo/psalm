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
    public function testBasicRequire(array $files, array $files_to_check)
    {
        foreach ($files as $filename => $contents) {
            $this->project_checker->registerFile($filename, $contents);
        }

        foreach ($files_to_check as $filename) {
            $contents = $files[$filename];

            $file_checker = new FileChecker($filename, $this->project_checker);
            $file_checker->visitAndAnalyzeMethods();
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
                            public function foo() : void {
                                (new A);
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{}',
                ],
                'files_to_check' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                ],
            ],
            'nestedRequire' => [
                'files' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo() : void {

                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        class B extends A{
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file3.php' => '<?php
                        require("file2.php");

                        class C extends B {
                            public function doFoo() : void {
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
                            public function foo() : void {
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
                        function fooFoo() : void {

                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");

                        fooFoo();',
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
                            public function foo() : void {
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
                            public function doFoo() : void {
                                $this->fooFoo();
                            }
                        }

                        class C {}

                        new D();',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require_once("file3.php");

                        class A{
                            public function fooFoo() : void { }
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
        ];
    }
}
