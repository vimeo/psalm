<?php

declare(strict_types=1);

namespace Psalm\Tests\FileUpdates;

use Psalm\Exception\CodeException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;
use UnexpectedValueException;

use function array_keys;
use function array_pop;
use function getcwd;
use function preg_quote;

use const DIRECTORY_SEPARATOR;

class ErrorAfterUpdateTest extends TestCase
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
     * @dataProvider providerTestInvalidUpdates
     * @param array<int, array<string, string>> $file_stages
     * @param array<string, string> $ignored_issues
     */
    public function testErrorAfterUpdate(
        array $file_stages,
        string $error_message,
        array $ignored_issues = [],
    ): void {
        $this->project_analyzer->getCodebase()->diff_methods = true;
        $this->project_analyzer->getCodebase()->reportUnusedCode();

        $codebase = $this->project_analyzer->getCodebase();

        $config = $codebase->config;

        foreach ($ignored_issues as $error_type => $error_level) {
            $config->setCustomErrorLevel($error_type, $error_level);
        }

        if (!$file_stages) {
            throw new UnexpectedValueException('$file_stages should not be empty');
        }

        $end_files = array_pop($file_stages);

        foreach ($file_stages as $files) {
            foreach ($files as $file_path => $contents) {
                $this->file_provider->registerFile($file_path, $contents);
            }

            $codebase->reloadFiles($this->project_analyzer, array_keys($files));

            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);
        }

        foreach ($end_files as $file_path => $contents) {
            $this->file_provider->registerFile($file_path, $contents);
        }

        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase->reloadFiles($this->project_analyzer, array_keys($end_files));

        foreach ($end_files as $file_path => $_) {
            $codebase->addFilesToAnalyze([$file_path => $file_path]);
        }

        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false, true);
    }

    /**
     * @return array<string,array{file_stages:array<int,array<string,string>>,error_message:string}>
     */
    public function providerTestInvalidUpdates(): array
    {
        return [
            'invalidateParentCaller' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function bar() : void {
                                    (new B)->foo();
                                }
                            }

                            (new C())->bar();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A { }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A { }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }

                            echo (new B)->foo();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var int */
                                public $foo = 5;
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return (new A)->foo;
                                }
                            }

                            echo (new B)->foo();',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidateAfterConstantChange' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = "bar";
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }

                            echo (new B)->foo();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public const FOO = 5;
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function foo() : string {
                                    return A::FOO;
                                }
                            }

                            echo (new B)->foo();',
                    ],
                ],
                'error_message' => 'InvalidReturnStatement',
            ],
            'invalidateAfterSkippedAnalysis' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function existingMethod() : string {
                                    return (new A)->getB()->getString();
                                }
                            }

                            echo (new C)->existingMethod();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : string {
                                    return "foo";
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function existingMethod() : string {
                                    return (new A)->getB()->getString();
                                }

                                public function newMethod() : void {}
                            }

                            echo (new C)->existingMethod();
                            // newly-added call, removed in the next code block
                            (new C)->newMethod();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function getB() : B {
                                    return new B;
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B {
                                public function getString() : ?string {
                                    return "foo";
                                }
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'C.php' => '<?php
                            namespace Foo;

                            class C {
                                public function existingMethod() : string {
                                    return (new A)->getB()->getString();
                                }
                            }

                            echo (new C)->existingMethod();',
                    ],
                ],
                'error_message' => 'NullableReturnStatement',
            ],
            'invalidateMissingConstructorAfterPropertyChange' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo = "bar";
                            }

                            echo (new A)->foo;',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                public function __construct() {}
                            }',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }

                            echo (new A)->foo;',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;
                            }

                            echo (new A)->foo;',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
                            namespace Foo;

                            trait T {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                use T;

                                /** @var string */
                                public $foo;
                            }

                            echo (new A)->foo;',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'T.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {}

                            echo (new B)->foo;',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo = "bar";

                                public function __construct() {}
                            }

                            class C extends A {}

                            new C();',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            namespace Foo;

                            class B extends A {
                                public function __construct() {}
                            }

                            echo (new B)->foo;',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            abstract class A {
                                /** @var string */
                                public $foo;

                                public function __construct() {}
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}

                            new A();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            (new A)->foo();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}

                            $a = new A();
                            print_r($a);',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {}',
                    ],
                ],
                'error_message' => 'UnusedClass',
            ],
            'unusedMethodReferencedInFile' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            (new A)->foo();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo() : void {}
                            }

                            $a = new A();
                            print_r($a);',
                    ],
                ],
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedStaticMethodReferencedInFile' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public static function foo() : void {}
                                public static function bar() : void {}
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            \Foo\A::foo();
                            \Foo\A::bar();',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public static function foo() : void {}
                                public static function bar() : void {}
                            }',
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'B.php' => '<?php
                            \Foo\A::bar();',
                    ],
                ],
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'unusedParamReferencedInFile' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            echo (new A)->foo;',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                /** @var string */
                                public $foo = "hello";
                            }

                            print_r(new A());',
                    ],
                ],
                'error_message' => 'PossiblyUnusedProperty',
            ],
            'unusedPropertyReferencedInMethod' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'A.php' => '<?php
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
            'uninitialisedChildProperty' => [
                'file_stages' => [
                    [
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
                    [
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
                ],
                'error_message' => 'PropertyNotSetInConstructor',
            ],
            'invalidateChildMethodWhenSignatureChanges' => [
                'file_stages' => [
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo(string $s) : void {
                                    echo $s;
                                }
                            }

                            class AChild extends A {
                                public function foo(string $s) : void {
                                    echo $s;
                                }
                            }',
                    ],
                    [
                        (string) getcwd() . DIRECTORY_SEPARATOR . 'A.php' => '<?php
                            namespace Foo;

                            class A {
                                public function foo(string $s = "") : void {
                                    echo $s;
                                }
                            }

                            class AChild extends A {
                                public function foo(string $s) : void {
                                    echo $s;
                                }
                            }',
                    ],
                ],
                'error_message' => 'MethodSignatureMismatch',
            ],
        ];
    }
}
