<?php
namespace Psalm\Tests\FileUpdates;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\TestConfig;
use Psalm\Tests\Internal\Provider;

class ErrorAfterUpdateTest extends \Psalm\Tests\TestCase
{
    /**
     * @return void
     */
    public function setUp()
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

        $this->project_analyzer->getCodebase()->infer_types_from_usage = true;
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

            foreach ($files as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
                $codebase->addFilesToAnalyze([$file_path => $file_path]);
            }

            $codebase->scanFiles();

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        }

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase->reloadFiles($this->project_analyzer, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
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
            'invalidateMissingConstructorAfterPropertyChange' => [
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
            'invalidateEmptyConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'invalidateEmptyTraitConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'invalidateEmptyTraitConstructorAfterTraitPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'invalidateSetInPrivateMethodConstructorCheck' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {
                                    $this->setFoo();
                                }

                                private function setFoo() : void {
                                }
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'invalidateMissingConstructorAfterParentPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {}',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {}',
                    ],
                ],
                'error_message' => 'MissingConstructor'
            ],
            'invalidateNotSetInConstructorAfterParentPropertyChange' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {
                                public function __construct() {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                        getcwd() . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {
                                public function __construct() {}
                            }',
                    ],
                ],
                'error_message' => 'PropertyNotSetInConstructor'
            ],
            'duplicateClass' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}
                            class A {}',
                    ],
                ],
                'error_message' => 'DuplicateClass'
            ],
            'duplicateMethod' => [
                'file_stages' => [
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }',
                    ],
                    [
                        getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                                public function foo() : void {}
                            }',
                    ],
                ],
                'error_message' => 'DuplicateMethod'
            ],
        ];
    }
}
