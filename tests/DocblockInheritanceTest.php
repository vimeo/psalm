<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class DocblockInheritanceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'inheritParentReturnDocbblock' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return int[]
                         */
                        public function doFoo() {
                            return [1, 2, 3];
                        }
                    }

                    class Bar extends Foo {
                        public function doFoo(): array {
                            return [4, 5, 6];
                        }
                    }

                    $b = (new Bar)->doFoo();',
                'assertions' => [
                    '$b' => 'array<array-key, int>',
                ],
            ],
            'inheritedSelfAnnotation' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @param self $i
                         * @return self
                         */
                        function foo(self $i) : self;
                    }

                    class C implements I {
                        public function foo(I $i) : I {
                            return $i;
                        }
                    }

                    function takeI(I $i) : I {
                        return (new C)->foo($i);
                    }',
            ],
            'inheritTwice' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return string[]
                         */
                        public function aa() {
                            return [];
                        }
                    }

                    class Bar extends Foo {
                        public function aa() {
                            return [];
                        }
                    }

                    class Baz extends Bar {
                        public function aa() {
                            return [];
                        }
                    }',
            ],
            'inheritTwiceWithArrayType' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return string[]
                         */
                        public function aa() : array {
                            return [];
                        }
                    }

                    class Bar extends Foo {
                        public function aa() : array {
                            return [];
                        }
                    }

                    class Baz extends Bar {
                        public function aa() : array {
                            return [];
                        }
                    }',
            ],
            'inheritCorrectReturnTypeOnInterface' => [
                'code' => '<?php
                    interface A {
                        /**
                         * @return A
                         */
                        public function map(): A;
                    }

                    interface B extends A {
                        /**
                         * @return B
                         */
                        public function map(): A;
                    }

                    function takesB(B $f) : B {
                        return $f->map();
                    }',
            ],
            'inheritCorrectReturnTypeOnClass' => [
                'code' => '<?php
                    interface A {
                        /**
                         * @return A
                         */
                        public function map(): A;
                    }

                    interface B extends A {
                        /**
                         * @return B
                         */
                        public function map(): A;
                    }

                    class F implements B {
                        public function map(): A {
                            return new F();
                        }
                    }

                    function takesF(F $f) : B {
                        return $f->map();
                    }',
            ],
            'inheritCorrectParamOnTypeChange' => [
                'code' => '<?php
                    class A
                    {
                        /** @param array<int, int>|int $className */
                        public function a(array|int $className): int
                        {
                            return 0;
                        }
                    }

                    class B extends A
                    {
                        /** @param array<int, int>|int|bool $className */
                        public function a(array|int|bool $className): int
                        {
                            return 0;
                        }
                    }

                    print_r((new A)->a(1));
                    print_r((new B)->a(true));
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritUndocumentedParamsWhenOverrideDocumentsOthers' => [
                'code' => '<?php
                    class ParentClass {
                        /**
                         * @param string|null $key
                         * @param bool $default
                         * @param bool $strict
                         */
                        public function boolean($key = null, $default = false, $strict = false): bool {
                            return true;
                        }
                    }

                    final class ChildClass extends ParentClass {
                        /**
                         * {@inheritDoc}
                         * @param bool $strict
                         */
                        public function boolean($key = null, $default = false, $strict = false): bool {
                            return $key === null ? $default : $strict;
                        }
                    }',
            ],
            'inheritParamTypesWhenOverrideDocumentsOnlyReturn' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param list<string> $a
                         * @return list<string>
                         */
                        public function f(array $a): array { return $a; }
                    }

                    final class C extends P {
                        /** @return list<string> */
                        public function f(array $a): array { return $a; }
                    }',
            ],
            'inheritReturnTypeWhenOverrideDocumentsOnlyParams' => [
                'code' => '<?php
                    class P {
                        /** @return list<int> */
                        public function f(int $a): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $a */
                        public function f(int $a): array { return [$a]; }
                    }

                    $r = (new C)->f(1);',
                'assertions' => [
                    '$r===' => 'list<int>',
                ],
            ],
            'inheritParamTypeByPositionWhenOverrideRenamesIt' => [
                'code' => '<?php
                    class P {
                        /** @param list<string> $items */
                        public function f(array $items): int { return 0; }
                    }

                    final class C extends P {
                        /** @param int|null $extra */
                        public function f(array $list, $extra = null): int {
                            return $list ? strlen($list[0]) : 0;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['ParamNameMismatch'],
            ],
            'inheritParamUsingParentMethodTemplate' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param positive-int $b
                         * @return T
                         */
                        public function f($a, int $b) { return $a; }
                    }

                    final class C extends P {
                        /** @param int $b */
                        public function f($a, int $b) { return $a; }
                    }

                    $r = (new C)->f("x", 1);',
                'assertions' => [
                    '$r===' => '\'x\'',
                ],
            ],
            'inheritParentTemplateParamNextToOverrideTemplates' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param int $b
                         * @return T
                         */
                        public function f($a, $b) { return $a; }
                    }

                    final class C extends P {
                        /**
                         * @template U of int
                         * @param U $b
                         */
                        public function f($a, $b) { return $a; }
                    }

                    $r = (new C)->f("s", 1);',
                'assertions' => [
                    '$r===' => '\'s\'',
                ],
            ],
            'undocumentedOverrideWithWiderNativeParamAcceptsIt' => [
                'code' => '<?php
                    class P {
                        /** @param string[] $a */
                        public function f(array $a): void {}
                    }

                    final class C extends P {
                        public function f(iterable $a): void {}
                    }

                    (new C)->f(new ArrayIterator([]));',
            ],
            'inheritReturnFromInterfaceWhenParentClassDocumentsOnlyParams' => [
                'code' => '<?php
                    interface I {
                        /** @return list<int> */
                        public function f(int $a): array;
                    }

                    class P implements I {
                        /** @param int $a */
                        public function f(int $a): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $a */
                        public function f(int $a): array { return []; }
                    }

                    $r = (new C)->f(1);',
                'assertions' => [
                    '$r===' => 'list<int>',
                ],
            ],
            'partialOverrideKeepsOwnParamTypeAndAcceptsWiderNative' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param string[] $a
                         * @param positive-int $b
                         */
                        public function f(array $a, int $b): void {}
                    }

                    final class C extends P {
                        /** @param int $b */
                        public function f(iterable $a, int $b): void {}
                    }

                    (new C)->f(new ArrayIterator([]), 0);',
            ],
            'overrideTemplatesStillInferredWhenParamsInherited' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param int $b
                         * @return T
                         */
                        public function f($a, $b) { return $a; }
                    }

                    final class C extends P {
                        /**
                         * @template U
                         * @param U $a
                         * @return U
                         */
                        public function f($a, $b) { return $a; }
                    }

                    $r = (new C)->f(5, 1);',
                'assertions' => [
                    '$r===' => '5',
                ],
            ],
            'narrowerNativeReturnKeptWhenParamsDocumented' => [
                'code' => '<?php
                    interface Id {}
                    final class UserId implements Id {}

                    interface Entity {
                        /**
                         * @param int $a
                         * @return Id
                         */
                        function id(int $a): Id;
                    }

                    final class User implements Entity {
                        /** @param int $a */
                        public function id(int $a): UserId { return new UserId(); }
                    }

                    $u = (new User)->id(1);',
                'assertions' => [
                    '$u===' => 'UserId',
                ],
            ],
            'undocumentedOverrideNotComparedWithReturnOfOtherAncestor' => [
                'code' => '<?php
                    class GP {
                        /**
                         * @param list<string> $a
                         * @param int $b
                         * @return list<int>
                         */
                        public function f(array $a, $b): array { return []; }
                    }

                    class P extends GP {
                        /** @param int $b */
                        public function f(array $a, $b): array { return []; }
                    }

                    final class D extends P {
                        public function f(array $a, $b): array { return []; }
                    }',
            ],
            'undocumentedOverrideNotComparedWithInterfaceReturn' => [
                'code' => '<?php
                    interface I {
                        /** @return list<int> */
                        public function g(array $a): array;
                    }

                    class Q {
                        /** @param list<string> $a */
                        public function g(array $a): array { return []; }
                    }

                    final class E extends Q implements I {
                        public function g(array $a): array { return []; }
                    }',
            ],
            'inheritedReturnTemplateUsedByNoParamIsResolved' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param positive-int $n
                         * @return list<T>
                         */
                        public function empty(int $n): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $n */
                        public function empty(int $n): array { return []; }
                    }

                    $c = (new C)->empty(1);',
                'assertions' => [
                    '$c===' => 'array<never, never>',
                ],
            ],
            'returnUsingTemplateOfRedocumentedParamNotInherited' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @return T
                         */
                        abstract public function make(string $id): object;
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make(string $id): object { return new stdClass(); }
                    }

                    $x = (new MyContainer)->make(stdClass::class);',
                'assertions' => [
                    '$x===' => 'object',
                ],
                'ignored_issues' => ['ImplementedReturnTypeMismatch'],
            ],
            'ownDocblockReturnUsedAtCallSiteWhenParamsInherited' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param list<int> $x
                         * @return list<int>
                         */
                        public function f(array $x): array { return $x; }
                    }

                    final class C extends P {
                        /** @return array<int> */
                        public function f(array $x): array { return $x; }
                    }

                    $r = (new C)->f([]);',
                'assertions' => [
                    '$r===' => 'array<array-key, int>',
                ],
                'ignored_issues' => ['LessSpecificImplementedReturnType'],
            ],
            'overrideAndInheritedTemplatesInferredAtStaticCalls' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param int $b
                         * @return T
                         */
                        public static function f($a, $b) { return $a; }
                    }

                    final class C extends P {
                        /**
                         * @template U
                         * @param U $a
                         * @return U
                         */
                        public static function f($a, $b) { return $a; }

                        public static function g(): int { return self::f(5, 1); }
                    }

                    final class D extends P {
                        /**
                         * @template U of int
                         * @param U $b
                         */
                        public static function f($a, $b) { return $a; }
                    }

                    $r = C::f(5, 1);
                    $s = D::f("s", 1);',
                'assertions' => [
                    '$r===' => '5',
                    '$s===' => '\'s\'',
                ],
            ],
            'overrideTemplatesInferredAtParentCall' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param int $b
                         * @return T
                         */
                        public function f($a, $b) { return $a; }
                    }

                    class C extends P {
                        /**
                         * @template U
                         * @param U $a
                         * @return U
                         */
                        public function f($a, $b) { return $a; }
                    }

                    final class E extends C {
                        public function g(): int { return parent::f(5, 1); }
                    }',
            ],
            'inheritParentTemplateParamsWithoutOwnTemplates' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param list<T> $b
                         * @return T
                         */
                        public function f($a, array $b, int $c) { return $a; }
                    }

                    final class C extends P {
                        /** @param int $c */
                        public function f($a, array $b, int $c) { return $a; }
                    }

                    $r = (new C)->f(1, [2], 3);',
                'assertions' => [
                    '$r===' => '1|2',
                ],
            ],
            'namedArgumentsFollowTheOverrideWhenParamsInherited' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param positive-int $b
                         * @return T
                         * @no-named-arguments
                         */
                        public function f($a, int $b) { return $a; }
                    }

                    final class C extends P {
                        /** @param int $b */
                        public function f($a, int $b) { return $a; }
                    }

                    $r = (new C)->f(a: "x", b: 0);',
                'assertions' => [
                    '$r===' => '\'x\'',
                ],
            ],
            'namedArgumentsFollowTheOverrideDocumentingOnlyReturn' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param positive-int $b
                         * @return T
                         * @no-named-arguments
                         */
                        public function f($a, int $b) { return $a; }
                    }

                    final class C extends P {
                        /** @return mixed */
                        public function f($a, int $b) { return $a; }
                    }

                    (new C)->f(a: "x", b: 1);',
            ],
            'nativeTypeSpelledDifferentlyDoesNotWidenInheritedType' => [
                'code' => '<?php
                    class P {
                        /** @param iterable<string> $a */
                        public function f(iterable $a): int { return 0; }
                    }

                    final class C extends P {
                        public function f(array|Traversable $a): int {
                            $n = 0;
                            foreach ($a as $s) {
                                $n += strlen($s);
                            }
                            return $n;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritReturnFromInterfaceWhenParentClassDocumentsOtherParams' => [
                'code' => '<?php
                    interface I {
                        /** @return list<int> */
                        public function f(int $a, int $b): array;
                    }

                    class P implements I {
                        /** @param int $a */
                        public function f(int $a, int $b): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $a */
                        public function f(int $a, int $b): array { return []; }
                    }

                    $r = (new C)->f(1, 2);',
                'assertions' => [
                    '$r===' => 'list<int>',
                ],
            ],
            'conflictingInterfaceDocblocksInheritNothing' => [
                'code' => '<?php
                    interface A {
                        /** @param list<string> $a */
                        public function f(array $a, int $b): void;
                    }

                    interface B {
                        /** @param array<int> $a */
                        public function f(array $a, int $b): void;
                    }

                    final class C implements A, B {
                        /** @param int $b */
                        public function f(array $a, int $b): void {}
                    }

                    (new C)->f([true], 1);',
            ],
            'arrayAccessOverrideDocumentingSomeParams' => [
                'code' => '<?php
                    /** @implements ArrayAccess<int, string> */
                    final class C implements ArrayAccess {
                        /** @param string $value */
                        public function offsetSet($offset, $value): void {}
                        public function offsetExists($offset): bool { return true; }
                        /** @param int $offset */
                        public function offsetGet($offset) { return ""; }
                        public function offsetUnset($offset): void {}
                    }

                    $v = (new C)[1];',
                'assertions' => [
                    '$v===' => 'null|string',
                ],
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'automaticInheritDoc' => [
                'code' => '<?php
                    class Y {
                        /**
                         * @param string[] $arr
                         */
                        public function boo(array $arr) : void {}
                    }

                    class X extends Y {
                        public function boo(array $arr) : void {}
                    }

                    (new X())->boo([1, 2]);',
                'error_message' => 'InvalidArgument',
            ],
            'callSiteUsesParamTypesInheritedNextToDocumentedOnes' => [
                'code' => '<?php
                    class ParentClass {
                        /**
                         * @param string|null $key
                         * @param bool $default
                         * @param bool $strict
                         */
                        public function boolean($key = null, $default = false, $strict = false): bool {
                            return true;
                        }
                    }

                    final class ChildClass extends ParentClass {
                        /** @param bool $strict */
                        public function boolean($key = null, $default = false, $strict = false): bool {
                            return false;
                        }
                    }

                    (new ChildClass)->boolean(1);',
                'error_message' => 'Argument 1 of ChildClass::boolean expects null|string, but 1 provided',
            ],
            'overrideParamDocblockStillComparedWithParent' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param int|string $a
                         * @param list<string> $b
                         */
                        public function f($a, array $b): void {}
                    }

                    final class C extends P {
                        /** @param int $a */
                        public function f($a, array $b): void {}
                    }',
                'error_message' => 'MoreSpecificImplementedParamType',
            ],
            'inheritVariadicParamType' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param positive-int $n
                         * @param non-empty-string ...$xs
                         */
                        public function f(int $n, string ...$xs): void {}
                    }

                    final class C extends P {
                        /** @param int $n */
                        public function f(int $n, string ...$xs): void {}
                    }

                    (new C)->f(1, "");',
                'error_message' => 'Argument 2 of C::f expects non-empty-string',
            ],
            'inheritIntoPromotedConstructorParam' => [
                'code' => '<?php
                    /** @psalm-consistent-constructor */
                    class P {
                        /**
                         * @param list<string> $items
                         * @param positive-int $n
                         */
                        public function __construct(array $items, int $n) {}
                    }

                    final class C extends P {
                        /** @param int $n */
                        public function __construct(public array $items, int $n) {
                            parent::__construct([], 1);
                        }
                    }

                    new C([1], 1);',
                'error_message' => 'Argument 1 of C::__construct expects list<string>, but',
                'error_levels' => ['ImmutableDependency'],
                'php_version' => '8.0',
            ],
            'nullableNativeParamAddsNullToInheritedType' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param string[] $a
                         * @param positive-int $b
                         */
                        public function f(array $a, int $b): void {}
                    }

                    final class C extends P {
                        /** @param int $b */
                        public function f(?array $a, int $b): void {}
                    }

                    (new C)->f([1], 1);',
                'error_message' => 'Argument 1 of C::f expects array<array-key, string>|null',
            ],
            'inheritClassTemplateParamLocalized' => [
                'code' => '<?php
                    /** @template T */
                    class P {
                        /**
                         * @param T $v
                         * @param positive-int $n
                         */
                        public function set($v, int $n): void {}
                    }

                    /** @extends P<string> */
                    final class C extends P {
                        /** @param int $n */
                        public function set($v, int $n): void {}
                    }

                    (new C)->set(1, 1);',
                'error_message' => 'Argument 1 of C::set expects string',
            ],
            'parentClassDocblockPreferredOverInterfaceForPartialOverride' => [
                'code' => '<?php
                    interface I {
                        /** @param list<string> $a */
                        public function f(array $a, int $b): void;
                    }

                    class P implements I {
                        /** @param array<string> $a */
                        public function f(array $a, int $b): void {}
                    }

                    final class C extends P implements I {
                        /** @param int $b */
                        public function f(array $a, int $b): void {}
                    }

                    (new C)->f([1], 1);',
                'error_message' => 'Argument 1 of C::f expects array<array-key, string>, but',
            ],
            'overrideDocblockReturnStillComparedWhenParamsInherited' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param int $x
                         * @return list<int>
                         */
                        public function f($x): array { return []; }
                    }

                    final class C extends P {
                        /** @return array<int> */
                        public function f($x): array { return []; }
                    }',
                'error_message' => 'LessSpecificImplementedReturnType',
            ],
            'returnStatementsCheckedAgainstInheritedReturnType' => [
                'code' => '<?php
                    class P {
                        /** @return list<int> */
                        public function f(int $a): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $a */
                        public function f(int $a): array { return ["x"]; }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'codeAfterCallReturningTemplateOfRedocumentedParamIsAnalyzed' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @return T
                         */
                        abstract public function make(string $id): object;
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make(string $id): object { return new stdClass(); }
                    }

                    function test(MyContainer $c): void {
                        $c->make(stdClass::class);
                        echo strlen([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:37',
                'error_levels' => ['ImplementedReturnTypeMismatch'],
            ],
            'codeAfterCallsReturningTemplateOfRedocumentedParamIsAnalyzedWithoutNativeReturn' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @param array<string, mixed> $options
                         * @return T
                         */
                        abstract public function make($id, array $options = []);

                        /**
                         * @template T
                         * @param callable(): T $factory
                         * @param positive-int $n
                         * @return T
                         */
                        abstract public function call(callable $factory, int $n);
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make($id, array $options = []) { throw new RuntimeException(); }

                        /** @param callable $factory */
                        public function call(callable $factory, int $n) { throw new RuntimeException(); }
                    }

                    function test(MyContainer $c): void {
                        $c->make(stdClass::class);
                        $c->call(fn() => 1, 1);
                        echo strlen([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:37',
            ],
            'mixedNativeParamCollapsesInheritedType' => [
                'code' => '<?php
                    class P {
                        /**
                         * @param list<string> $a
                         * @param positive-int $b
                         */
                        public function f(array $a, int $b): void {}
                    }

                    final class C extends P {
                        /** @param int $b */
                        public function f(mixed $a, int $b): void {
                            echo strlen($a);
                        }
                    }',
                'error_message' => 'MixedArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:41 - Argument 1 of strlen cannot be mixed, expecting string',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
