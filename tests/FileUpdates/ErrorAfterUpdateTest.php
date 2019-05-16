<?php
namespace Psalm\Tests\FileUpdates;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;

class ErrorAfterUpdateTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new \Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider(),
            null,
            null,
            new Provider\FakeFileReferenceCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false
        );
        $this->project_analyzer->setPhpVersion('7.3');
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
        $this->project_analyzer->getCodebase()->diff_methods = true;
        $this->project_analyzer->getCodebase()->reportUnusedCode();

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        foreach ($error_levels as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        if (!$file_stages) {
            throw new \UnexpectedValueException('$file_stages should not be empty');
        }

        $end_files = array_pop($file_stages);

        foreach ($file_stages as $files) {
            foreach ($files as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
            }

            $codebase->reloadFiles($this->project_analyzer, array_keys($files));

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

            $this->project_analyzer->checkClassReferences();
        }

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase->reloadFiles($this->project_analyzer, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

        $this->project_analyzer->checkClassReferences();
    }

    /**
     * @return array<string,array{file_stages:array<int,array<string,string>>,error_message:string}>
     */
    public function providerTestInvalidUpdates()
    {
        return [
            'invalidateParentCaller' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : void {
                                    (new B)->foo();
                                }
                            }

                            (new C())->bar();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : void {
                                    (new B)->foo();
                                }
                            }

                            (new C())->bar();',
                    ],
                ],
                'error_message' => 'UndefinedMethod',
            ],
            'invalidateAfterPropertyTypeChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }

                            (new B)->foo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var int */
                                public $foo = 5;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }

                            (new B)->foo();',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidateAfterConstantChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }

                            (new B)->foo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = 5;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }

                            (new B)->foo();',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidateAfterSkippedAnalysis' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }
                            }

                            (new C)->bar();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }

                                public function bat() : void {}
                            }

                            (new C)->bar();
                            (new C)->bat();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : ?string {
                                    return "foo";
                                }
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : string {
                                    return (new A)->getB()->getString();
                                }
                            }

                            (new C)->bar();',
                    ],
                ],
                'error_message' => 'NullableReturnStatement',
            ],
            'invalidateMissingConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;
                            }

                            echo (new A)->foo;',
                    ],
                ],
                'error_message' => 'MissingConstructor',
            ],
            'invalidateEmptyConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }

                            echo (new A)->foo;',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'invalidateEmptyTraitConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo = "bar";
                            }

                            echo (new A)->foo;',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }

                            echo (new A)->foo;',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'invalidateEmptyTraitConstructorAfterTraitPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                            }

                            echo (new A)->foo;',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }

                            echo (new A)->foo;',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'invalidateSetInPrivateMethodConstructorCheck' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {
                                    $this->setFoo();
                                }

                                private function setFoo() : void {
                                    $this->foo = "bar";
                                }
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {
                                    $this->setFoo();
                                }

                                private function setFoo() : void {
                                }
                            }

                            echo (new A)->foo;',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'invalidateMissingConstructorAfterParentPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {}

                            echo (new B)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {}

                            echo (new B)->foo;',
                    ],
                ],
                'error_message' => 'MissingConstructor',
            ],
            'invalidateNotSetInConstructorAfterParentPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }

                            class C extends A {}

                            new C();',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {
                                public function __construct() {}
                            }

                            echo (new B)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {
                                public function __construct() {}
                            }

                            echo (new B)->foo;',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'duplicateClass' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}

                            new A();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}
                            class A {}',
                    ],
                ],
                'error_message' => 'DuplicateClass',
            ],
            'duplicateMethod' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            (new A)->foo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                                public function foo() : void {}
                            }',
                    ],
                ],
                'error_message' => 'DuplicateMethod',
            ],
            'unusedClassReferencedInFile' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}

                            $a = new A();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                    ],
                ],
                'error_message' => 'UnusedClass',
            ],
            'unusedMethodReferencedInFile' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            (new A)->foo();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            $a = new A();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedStaticMethodReferencedInFile' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public static function foo() : void {}
                                public static function bar() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            \Foo\A::foo();
                            \Foo\A::bar();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public static function foo() : void {}
                                public static function bar() : void {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            \Foo\A::bar();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedParamReferencedInFile' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo(string $s) : void {}
                            }

                            class B extends A {
                                public function foo(string $s) : void {
                                    echo $s;
                                }
                            }

                            (new B)->foo("hello");',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo(string $s) : void {}
                            }

                            class B extends A {
                            }

                            (new B)->foo("hello");',
                    ],
                ],
                'error_message' => 'PossiblyUnusedParam',
            ],
            'unusedMethodReferencedInMethod' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            class B {
                                public function bar() : void {
                                    (new A)->foo();
                                }
                            }

                            (new B)->bar();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            class B {
                                public function bar() : void {
                                    new A();
                                }
                            }

                            (new B)->bar();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedPropertyReferencedInFile' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            $a = new A();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'unusedPropertyReferencedInMethod' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            class B {
                                public function bar() : void {
                                    echo (new A)->foo;
                                }
                            }

                            (new B)->bar();',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            class B {
                                public function bar() : void {
                                    new A();
                                }
                            }

                            (new B)->bar();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedProperty',
            ],
        ];
    }
}
