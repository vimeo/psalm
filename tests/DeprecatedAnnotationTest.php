<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function getcwd;

use const DIRECTORY_SEPARATOR;

final class DeprecatedAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'deprecatedMethod' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }',
            ],
            'deprecatedCloneMethod' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public function __clone() {
                        }
                    }',
            ],
            'deprecatedClassUsedInsideClass' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public static function barBar(): void {
                            new Foo();
                        }
                    }',
            ],
            'annotationOnStatement' => [
                'code' => '<?php
                    /** @deprecated */
                    $a = "A";',
            ],
            'noNoticeOnInheritance' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {}

                    interface Iface {
                        /**
                         * @psalm-suppress DeprecatedClass
                         * @return Foo[]
                         */
                        public function getFoos();

                        /**
                         * @psalm-suppress DeprecatedClass
                         * @return Foo[]
                         */
                        public function getDifferentFoos();
                    }

                    class Impl implements Iface {
                        public function getFoos(): array {
                            return [];
                        }

                        public function getDifferentFoos() {
                            return [];
                        }
                    }',
            ],
            'suppressDeprecatedClassOnMember' => [
                    'code' => '<?php

                        /**
                         * @deprecated
                         */
                        class TheDeprecatedClass {}

                        /**
                         * @psalm-suppress MissingConstructor
                         */
                        class A {
                            /**
                             * @psalm-suppress DeprecatedClass
                             * @var TheDeprecatedClass
                             */
                            public $property;
                        }
                '],
            'suppressDeprecatedClassOnTemplateType' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class TheDeprecatedClass {}

                    /**
                     * @template T
                     */
                    class TheParentClass {}

                    /**
                     * @extends TheParentClass<TheDeprecatedClass>
                     * @psalm-suppress DeprecatedClass
                     */
                    class TheChildClass extends TheParentClass {}
                '],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'deprecatedMethodWithCall' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public static function barBar(): void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedMethod',
            ],
            'deprecatedMethodWithCallAttr' => [
                'code' => '<?php
                    class Foo {
                        #[\Deprecated]
                        public static function barBar(): void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedMethod',
            ],
            'deprecatedCloneMethodWithCall' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @deprecated
                         */
                        public function __clone() {
                        }
                    }

                    $a = new Foo;
                    $aa = clone $a;',
                'error_message' => 'DeprecatedMethod',
            ],
            'deprecatedClassWithStaticCall' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public static function barBar(): void {
                        }
                    }

                    Foo::barBar();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithNew' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    $a = new Foo();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithNewAttr' => [
                'code' => '<?php
                    #[\Deprecated]
                    class Foo { }

                    $a = new Foo();',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassWithExtends' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo { }

                    class Bar extends Foo {}',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedPropertyGet' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                    }
                    echo (new A)->foo;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertyGetAttr' => [
                'code' => '<?php
                    class A{
                        /**
                         * @var ?int
                         */
                        #[\Deprecated]
                        public $foo;
                    }
                    echo (new A)->foo;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertySet' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                    }
                    $a = new A;
                    $a->foo = 5;',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertyGetFromInsideTheClass' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                        public function bar(): void
                        {
                            echo $this->foo;
                        }
                    }
                ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedPropertySetFromInsideTheClass' => [
                'code' => '<?php
                    class A{
                        /**
                         * @deprecated
                         * @var ?int
                         */
                        public $foo;
                        public function bar(int $p): void
                        {
                            $this->foo = $p;
                        }
                    }
                ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedClassConstant' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {
                        public const FOO = 5;
                    }

                    echo Foo::FOO;',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassStringConstant' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class Foo {}

                    echo Foo::class;',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedClassAsParam' => [
                'code' => '<?php
                    /**
                     * @deprecated
                     */
                    class DeprecatedClass{}

                    function foo(DeprecatedClass $deprecatedClass): void {}',
                'error_message' => 'DeprecatedClass',
            ],
            'deprecatedStaticPropertyFetch' => [
                'code' => '<?php

                    class Bar
                    {
                        /**
                         * @deprecated
                         */
                        public static bool $deprecatedProperty = false;
                    }

                    Bar::$deprecatedProperty;
                    ',
                'error_message' => 'DeprecatedProperty',
            ],
            'deprecatedEnumCaseFetch' => [
                'code' => '<?php
                    enum Foo {
                        case A;

                        /** @deprecated */
                        case B;
                    }

                    Foo::B;
                ',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'deprecatedEnumCaseFetchAttr' => [
                'code' => '<?php
                    enum Foo {
                        case A;

                        #[\Deprecated]
                        case B;
                    }

                    Foo::B;
                ',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'deprecatedClassConstFetch' => [
                'code' => '<?php
                    class Foo {
                        const A = 1;

                        /** @deprecated */
                        const B = 2;
                    }
                    Foo::B;
                ',
                'error_message' => 'DeprecatedConstant',
            ],
            'deprecatedClassConstFetchAttr' => [
                'code' => '<?php
                    class Foo {
                        const A = 1;

                        #[\Deprecated]
                        const B = 2;
                    }

                    Foo::B;
                ',
                'error_message' => 'DeprecatedConstant',
            ],
            'deprecatedInterfaceInGenerics' => [
                'code' => '<?php
                    /** @deprecated */
                    interface MyInterface {}

                    /**
                     * @extends ArrayObject<array-key, MyInterface>
                     * @psalm-capabilities read-props|write-this-props
                     */
                    class MyClass extends ArrayObject {}
                ',
                'error_message' => 'DeprecatedInterface',
            ],
            'deprecatedTrait' => [
                'code' => '<?php
                    /** @deprecated */
                    trait T {}

                    class C {
                        use T;
                    }
                ',
                'error_message' => 'DeprecatedTrait',
            ],
            'deprecatedFunction' => [
                'code' => '<?php
                    /** @deprecated */
                    function a(): void {}
                    a();
                ',
                'error_message' => 'DeprecatedFunction',
            ],
            'deprecatedFunctionAttr' => [
                'code' => '<?php
                    #[\Deprecated]
                    function a(): void {}
                    a();
                ',
                'error_message' => 'DeprecatedFunction',
            ],
        ];
    }

    public function testDeprecatedClassInTemplateExtendsIsReportedAfterExternalMethodCall(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^DeprecatedClass - tests[\\\\\/]somefile\.php:6:/');

        $this->analyzeCallerBeforeDeclaration(
            '<?php
                /** @template T */
                abstract class ExternalRepository {
                    /** @return T */
                    public function find(): mixed {
                        throw new RuntimeException();
                    }
                }',
            '<?php
                /** @deprecated */
                final class TheDeprecatedClass {}

                /** @extends ExternalRepository<TheDeprecatedClass> */
                final class TheRepository extends ExternalRepository {}',
            '<?php
                function findDeprecated(TheRepository $repository): void {
                    /** @psalm-suppress DeprecatedClass */
                    $repository->find();
                }',
        );
    }

    public function testDeprecatedClassInTemplateExtendsIsReportedAfterExternalStaticMethodCall(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^DeprecatedClass - tests[\\\\\/]somefile\.php:6:/');

        $this->analyzeCallerBeforeDeclaration(
            '<?php
                /** @template T */
                abstract class ExternalRepository {
                    /** @return T */
                    public static function findStatic(): mixed {
                        throw new RuntimeException();
                    }
                }',
            '<?php
                /** @deprecated */
                final class TheDeprecatedClass {}

                /** @extends ExternalRepository<TheDeprecatedClass> */
                final class TheRepository extends ExternalRepository {}',
            '<?php
                function findDeprecated(): void {
                    /** @psalm-suppress DeprecatedClass */
                    TheRepository::findStatic();
                }',
        );
    }

    public function testDeprecatedClassInTemplateExtendsIsReportedAfterSuppressingChildClass(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^DeprecatedClass - tests[\\\\\/]somefile\.php:6:/');

        $this->analyzeCallerBeforeDeclaration(
            '<?php
                /** @template T */
                abstract class ExternalRepository {}',
            '<?php
                /** @deprecated */
                final class TheDeprecatedClass {}

                /** @extends ExternalRepository<TheDeprecatedClass> */
                class TheRepository extends ExternalRepository {}',
            '<?php
                /** @psalm-suppress DeprecatedClass */
                final class TheChildRepository extends TheRepository {}',
        );
    }

    public function testDeprecatedClassInMixinIsReportedAfterExternalMethodCall(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^DeprecatedClass - tests[\\\\\/]somefile\.php:6:/');

        $this->analyzeCallerBeforeDeclaration(
            '<?php
                /** @template T */
                abstract class ExternalRepository {
                    /** @return T */
                    public function find(): mixed {
                        throw new RuntimeException();
                    }
                }',
            '<?php
                /** @deprecated */
                final class TheDeprecatedClass {}

                /** @mixin ExternalRepository<TheDeprecatedClass> */
                final class TheRepository {
                    public function __call(string $name, array $args) {
                        return null;
                    }
                }',
            '<?php
                function findDeprecated(TheRepository $repository): void {
                    /** @psalm-suppress DeprecatedClass */
                    $repository->find();
                }',
        );
    }

    public function testDeprecatedClassInPropertyTypeIsReportedAfterSuppressingChildClass(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessageMatches('/^DeprecatedClass - tests[\\\\\/]somefile\.php:6:/');

        $this->analyzeCallerBeforeDeclaration(
            '<?php',
            '<?php
                /** @deprecated */
                final class TheDeprecatedClass {}

                abstract class TheBase {
                    /** @var TheDeprecatedClass|null */
                    public $deprecated;
                }',
            '<?php
                /** @psalm-suppress DeprecatedClass */
                final class TheChild extends TheBase {}',
        );
    }

    /**
     * Analyzes both project files in one run, so they share class storages.
     * Files are analyzed in path order, so the caller (src/) goes before the declaration (tests/).
     */
    private function analyzeCallerBeforeDeclaration(
        string $external_code,
        string $declaring_code,
        string $caller_code,
    ): void {
        $base_dir = (string) getcwd() . DIRECTORY_SEPARATOR;
        $external_file_path = $base_dir . 'vendor' . DIRECTORY_SEPARATOR . 'somefile.php';
        $caller_file_path = $base_dir . 'src' . DIRECTORY_SEPARATOR . 'somefile.php';
        $declaring_file_path = $base_dir . 'tests' . DIRECTORY_SEPARATOR . 'somefile.php';

        $this->addFile($external_file_path, $external_code);
        $this->addFile($declaring_file_path, $declaring_code);
        $this->addFile($caller_file_path, $caller_code);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([
            $caller_file_path => $caller_file_path,
            $declaring_file_path => $declaring_file_path,
        ]);
        $codebase->scanFiles();
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
    }
}
