<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;

class IncludeTest extends TestCase
{
    /**
     * @dataProvider providerTestValidIncludes
     * @param string $file
     * @param string $code
     * @param array<string,string> $includes
     * @return void
     */
    public function testBasicRequire($file, $code, $includes = [])
    {
        foreach ($includes as $filename => $contents) {
            $this->project_checker->registerFile($filename, $contents);
        }

        $file_checker = new FileChecker(
            $file,
            $this->project_checker,
            self::$parser->parse($code)
        );

        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return array
     */
    public function providerTestValidIncludes()
    {
        return [
            'basicRequire' => [
                'file' => getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                'code' => '<?php
                    require("file1.php");
                                
                    class B {
                        public function foo() : void {
                            (new A);
                        }
                    }',
                'includes' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                        }',
                ],
            ],
            'nestedRequire' => [
                'file' => getcwd() . DIRECTORY_SEPARATOR . 'file3.php',
                'code' => '<?php
                    require("file2.php");
        
                    class C extends B {
                        public function doFoo() : void {
                            $this->fooFoo();
                        }
                    }',
                'includes' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        class A{
                            public function fooFoo() : void {
            
                            }
                        }',
                    getcwd() . DIRECTORY_SEPARATOR . 'file2.php' => '<?php
                        require("file1.php");
            
                        class B extends A{
                        }',
                ],
            ],
            'requireNamespace' => [
                'file' => getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                'code' => '<?php
                    require("file1.php");
        
                    class B {
                        public function foo() : void {
                            (new Foo\A);
                        }
                    }',
                'includes' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;
            
                        class A{
                        }',
                ],
            ],
            'requireFunction' => [
                'file' => getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                'code' => '<?php
                    require("file1.php");
        
                    fooFoo();',
                'includes' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        function fooFoo() : void {
            
                        }',
                ],
            ],
            'requireNamespacedWithUse' => [
                'file' => getcwd() . DIRECTORY_SEPARATOR . 'file2.php',
                'code' => '<?php
                    require("file1.php");
        
                    use Foo\A;
        
                    class B {
                        public function foo() : void {
                            (new A);
                        }
                    }',
                'includes' => [
                    getcwd() . DIRECTORY_SEPARATOR . 'file1.php' => '<?php
                        namespace Foo;
            
                        class A{
                        }',
                ],
            ],
        ];
    }
}
