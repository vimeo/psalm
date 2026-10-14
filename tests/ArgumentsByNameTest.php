<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ArgumentsByNameTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testStableParameterNamesReportRenamesWhenNamedArgumentCallsAreDisallowed(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ParamNameMismatch');

        $this->project_analyzer->getConfig()->allow_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    /** @stable-parameter-names */
                    public function foo(int $a): void {}
                }

                final class B extends A {
                    public function foo(int $b): void {}
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testOptionalArgumentsByNameReportRenamedOptionalParametersWhenNamedArgumentCallsAreDisallowed(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ParamNameMismatch');

        $this->project_analyzer->getConfig()->allow_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    /** @optional-arguments-by-name */
                    public function foo(int $a, int $b = 0): void {}
                }

                final class B extends A {
                    public function foo(int $a, int $c = 0): void {}
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testOptionalArgumentsByNameAllowRenamedRequiredParametersWhenNamedArgumentCallsAreDisallowed(): void
    {
        $this->project_analyzer->getConfig()->allow_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    /** @optional-arguments-by-name */
                    public function foo(int $a, int $b = 0): void {}
                }

                final class B extends A {
                    public function foo(int $c, int $b = 0): void {}
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testOptionalArgumentsByNameCheckTheOptionalParametersOfTheParentMethod(): void
    {
        $this->project_analyzer->getConfig()->allow_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                class A {
                    /** @optional-arguments-by-name */
                    public function foo(int $a): void {}
                }

                final class B extends A {
                    public function foo(int $c = 0): void {}
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testInterfaceTagReportsRenamesWhenNamedArgumentCallsAreDisallowed(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('ParamNameMismatch');

        $this->project_analyzer->getConfig()->allow_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @stable-parameter-names */
                interface I {
                    public function foo(int $a): void;
                }

                final class C implements I {
                    public function foo(int $b): void {}
                }',
        );

        $this->analyzeFile($file_path, new Context());
    }

    public function testTagsAllowNamedArgumentsOfInternalMethods(): void
    {
        $this->project_analyzer->getConfig()->allow_internal_named_arg_calls = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace Foo;

                /** @internal */
                final class A {
                    /** @arguments-by-name */
                    public function foo(int $a): void {}
                }

                /**
                 * @internal
                 * @stable-parameter-names
                 */
                final class B {
                    /** @return void */
                    public function foo(int $a) {}
                }

                (new A())->foo(a: 1);
                (new B())->foo(a: 1);',
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'optionalArgumentsByName' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int $b = 0, int $c = 0): void {}

                    foo(1);
                    foo(1, c: 2);
                    foo(a: 1, b: 2);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'argumentsByName' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a, int $b = 0): void {}

                    foo(a: 1);
                    foo(b: 2, a: 1);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'stableParameterNames' => [
                'code' => '<?php
                    /** @stable-parameter-names */
                    function foo(int $a, int $b = 0): void {}

                    foo(1, 2);
                    foo(a: 1, b: 2);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'variadicParametersAreNotAffected' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int ...$rest): void {}

                    /** @arguments-by-name */
                    function bar(int ...$rest): void {}

                    foo(1, 2, 3);
                    bar(1, 2, 3);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unpackedStringKeysAreNamed' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a, int $b = 0): void {}

                    foo(...["a" => 1, "b" => 2]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'unpackedUnknownKeysAreNotReported' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a = 0, int $b = 0): void {}

                    /**
                     * @param array<array-key, int> $array
                     * @param array<string, int> $map
                     */
                    function bar(array $array, array $map): void {
                        foo(...$array);
                        foo(...$map);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'methodTagOverridesClassTag' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    final class Foo {
                        /** @stable-parameter-names */
                        public function bar(int $a): void {}
                    }

                    (new Foo())->bar(1);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayMapCallbackIsNotReported' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function process(string $item): string {
                        return strtoupper($item);
                    }

                    array_map("process", ["a", "b"]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classNoNamedArgumentsWithArgumentsByNameOnConstructor' => [
                'code' => '<?php
                    /** @no-named-arguments */
                    final class Foo {
                        /** @arguments-by-name */
                        public function __construct(public string $name, public int $age) {}

                        public function bar(int $a, int $b): int {
                            return $a + $b;
                        }
                    }

                    $foo = new Foo(name: "Alice", age: 30);
                    $foo->bar(1, 2);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'constructorRenamesAreNotReported' => [
                'code' => '<?php
                    class A {
                        /** @arguments-by-name */
                        public function __construct(int $a) {}
                    }

                    final class B extends A {
                        public function __construct(int $b) {
                            parent::__construct(a: $b);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnusedParam'],
                'php_version' => '8.0',
            ],
            'positionalArgumentsBeforePhp80' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a): void {}

                    foo(1);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'optionalArgumentByPosition' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int $b = 0): void {}

                    foo(1, 2);',
                'error_message' => 'PositionalArgumentNotAllowed',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'optionalArgumentByPositionMessage' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int $b = 0): void {}

                    foo(1, 2);',
                'error_message' => 'Argument for optional parameter $b of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'requiredArgumentByPosition' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a): void {}

                    foo(1);',
                'error_message' => 'Argument for parameter $a of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'constructor' => [
                'code' => '<?php
                    final class Configuration {
                        /** @optional-arguments-by-name */
                        public function __construct(
                            string $dialect,
                            array $groups = [],
                            bool $allowExtraAttributes = true,
                        ) {}
                    }

                    new Configuration("openapi-3.1", ["read"]);',
                'error_message' => 'Argument for optional parameter $groups of Configuration::__construct must be passed',
                'error_levels' => ['UnusedParam'],
                'php_version' => '8.0',
            ],
            'staticMethod' => [
                'code' => '<?php
                    final class Foo {
                        /** @arguments-by-name */
                        public static function create(int $a): void {}
                    }

                    Foo::create(1);',
                'error_message' => 'Argument for parameter $a of Foo::create must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'classTag' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    final class Foo {
                        public function bar(int $a): void {}
                    }

                    (new Foo())->bar(1);',
                'error_message' => 'Argument for parameter $a of Foo::bar must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'traitTag' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    trait T {
                        public function bar(int $a): void {}
                    }

                    final class Foo {
                        use T;
                    }

                    (new Foo())->bar(1);',
                'error_message' => 'PositionalArgumentNotAllowed',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'enumTag' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    enum E {
                        case A;

                        public static function make(int $a): self {
                            return self::A;
                        }
                    }

                    E::make(1);',
                'error_message' => 'Argument for parameter $a of E::make must be passed by name',
                'error_levels' => ['UnusedParam'],
                'php_version' => '8.1',
            ],
            'interfaceTagIsInherited' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    interface I {
                        public function foo(int $a, int $b = 0): void;
                    }

                    final class C implements I {
                        public function foo(int $a, int $b = 0): void {}
                    }

                    (new C())->foo(1, 2);',
                'error_message' => 'Argument for optional parameter $b of C::foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'methodTagIsInherited' => [
                'code' => '<?php
                    class A {
                        /** @arguments-by-name */
                        public function foo(int $a): void {}
                    }

                    class B extends A {
                        public function foo(int $a): void {}
                    }

                    final class C extends B {
                        public function foo(int $a): void {}
                    }

                    (new C())->foo(1);',
                'error_message' => 'Argument for parameter $a of C::foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unpackedIntKeysArePositional' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int $b = 0): void {}

                    foo(...[1, 2]);',
                'error_message' => 'Argument for optional parameter $b of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unpackedAfterPositionalArgument' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a, int $b = 0, int $c = 0): void {}

                    foo(1, ...[2]);',
                'error_message' => 'Argument for optional parameter $b of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unpackedIntAndStringKeys' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a = 0, int $b = 0): void {}

                    foo(...[1, "b" => 2]);',
                'error_message' => 'Argument for optional parameter $a of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'unpackedListIsPositional' => [
                'code' => '<?php
                    /** @arguments-by-name */
                    function foo(int $a = 0, int $b = 0): void {}

                    /** @param list<int> $list */
                    function bar(array $list): void {
                        foo(...$list);
                    }',
                'error_message' => 'Argument for optional parameter $a of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'unpackedIterableWithIntKeysIsPositional' => [
                'code' => '<?php
                    /** @optional-arguments-by-name */
                    function foo(int $a = 0, int $b = 0): void {}

                    /** @param iterable<int, int> $values */
                    function bar(iterable $values): void {
                        foo(...$values);
                    }',
                'error_message' => 'Argument for optional parameter $a of foo must be passed by name',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'classNoNamedArguments' => [
                'code' => '<?php
                    /** @no-named-arguments */
                    final class Foo {
                        public function bar(int $a, int $b): int {
                            return $a + $b;
                        }
                    }

                    (new Foo())->bar(a: 1, b: 2);',
                'error_message' => 'NamedArgumentNotAllowed',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'classNoNamedArgumentsWithArgumentsByNameOnConstructor' => [
                'code' => '<?php
                    /** @no-named-arguments */
                    final class Foo {
                        /** @arguments-by-name */
                        public function __construct(public string $name, public int $age) {}
                    }

                    new Foo("Alice", 30);',
                'error_message' => 'Argument for parameter $name of Foo::__construct must be passed by name',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'noNamedArgumentsOverridingATaggedMethod' => [
                'code' => '<?php
                    class A {
                        /** @arguments-by-name */
                        public function foo(int $a): void {}
                    }

                    final class B extends A {
                        /** @no-named-arguments */
                        public function foo(int $a): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'conflictsWithNoNamedArguments' => [
                'code' => '<?php
                    /**
                     * @no-named-arguments
                     * @arguments-by-name
                     */
                    function foo(int $a): void {}',
                'error_message' => 'InvalidDocblock',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'conflictingClassTags' => [
                'code' => '<?php
                    /**
                     * @arguments-by-name
                     * @stable-parameter-names
                     */
                    final class Foo {}',
                'error_message' => 'InvalidDocblock',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
