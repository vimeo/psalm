<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class IntersectionTypeTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'methodOfAnIntersectionReturnsAnArrayOfStringCastableItems' => [
                'code' => '<?php
                    interface A {
                        /** @return array<int, Stringable|int> */
                        public function get(): array;
                        /** @return list<Stringable|int> */
                        public function getList(): array;
                        /** @return array<int, Stringable> */
                        public function getIterable(): iterable;
                    }

                    interface B {
                        /** @return array<int, string|int> */
                        public function get(): array;
                        /** @return list<string|int> */
                        public function getList(): array;
                        /** @return iterable<int, string> */
                        public function getIterable(): iterable;
                    }

                    function make(): A&B {
                        throw new RuntimeException();
                    }

                    $o = make();
                    $array = $o->get();
                    $list = $o->getList();
                    $iterable = $o->getIterable();',
                'assertions' => [
                    '$array===' => 'array<int, Stringable|int>',
                    '$list===' => 'list<Stringable|int>',
                    '$iterable===' => 'array<int, Stringable>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'shapeIntersectionWithArraysOfStringCastableItems' => [
                'code' => '<?php
                    /** @return array{v: array<int, Stringable|int>}&array{v: array<int, int|string>} */
                    function f(): array {
                        return ["v" => [1]];
                    }

                    $x = f();',
                'assertions' => [
                    '$x===' => 'array{v: array<int, Stringable|int>}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'shapeIntersectionWithAnOptionalKeyOfStringableAndString' => [
                'code' => '<?php
                    /** @return array{a: int, b?: Stringable}&array{b?: string} */
                    function f(): array {
                        return ["a" => 1];
                    }

                    $x = f();',
                'assertions' => [
                    '$x===' => 'array{a: int, b?: never}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyFetchOfTheSecondClassOfAnIntersectionWithAnInterface' => [
                'code' => '<?php
                    interface I {}

                    abstract class A {}

                    abstract class B extends A {
                        public int $number = 1;
                    }

                    function test(I $i): int {
                        if (!$i instanceof A) {
                            return 0;
                        }

                        if (!$i instanceof B) {
                            return 0;
                        }

                        return $i->number + 1;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfTheSecondClassOfAnIntersectionWithAnInterface' => [
                'code' => '<?php
                    interface I {}

                    abstract class A {}

                    abstract class B extends A {
                        public int $number = 1;
                    }

                    function test(I $i): int {
                        if (!$i instanceof A) {
                            return 0;
                        }

                        if (!$i instanceof B) {
                            return 0;
                        }

                        $i->number = 2;
                        ++$i->number;

                        return $i->number;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'methodOfIntersectedInterfaceIsNotRoutedThroughMagicCall' => [
                'code' => '<?php
                    interface MockObject {
                        public function expects(): string;
                    }

                    class Client {
                        /**
                         * @deprecated
                         * @param list<mixed> $arguments
                         */
                        public function __call(string $method, array $arguments): int {
                            return 1;
                        }
                    }

                    /** @param Client&MockObject $client */
                    function test($client): string {
                        $result = $client->expects();
                        return $result;
                    }',
            ],
            'methodOfTheSecondInterfaceOfAnIntersectionBoundOfATemplate' => [
                'code' => '<?php
                    interface HasName {}

                    class Base {
                        public function custom(): int {
                            return 0;
                        }
                    }

                    /**
                     * @template T of HasName&Base
                     * @param T $param
                     */
                    function test($param): int {
                        return $param->custom();
                    }

                    /** @template T of HasName&Base */
                    class Holder {
                        /** @param T $param */
                        public function call($param): int {
                            return $param->custom();
                        }
                    }',
            ],
            'staticMethodOfTheSecondTypeOfAnIntersectionBoundOfATemplate' => [
                'code' => '<?php
                    interface HasName {}

                    class Base {
                        public static function custom(): int {
                            return 0;
                        }
                    }

                    /**
                     * @template T of HasName&Base
                     * @param T $param
                     */
                    function test($param): int {
                        return $param::custom();
                    }',
            ],
            'methodOfAnIntersectionBoundOfATemplateIntersectedWithAnotherType' => [
                'code' => '<?php
                    interface HasName {}

                    class Base {
                        public function custom(): int {
                            return 0;
                        }
                    }

                    /**
                     * @template T of HasName&Base
                     * @param T&Countable $param
                     */
                    function test($param): int {
                        return $param->custom() + $param->count();
                    }',
            ],
            'intersectionElementOfAnArrayKeepsItsInterfacesInAnArrayMapClosure' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    final class Wrapper {
                        public function __construct(public B $input) {}
                    }

                    /**
                     * @param array<int, A&B> $filtered
                     * @return array<int, Wrapper>
                     */
                    function wrap(array $filtered): array {
                        return array_map(fn ($x) => new Wrapper($x), $filtered);
                    }',
            ],
            'intersectionBoundOfATemplateKeepsItsInterfacesInAClosureParam' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    /**
                     * @template T
                     * @param T $value
                     * @param callable(T):void $callback
                     */
                    function with($value, callable $callback): void {
                        $callback($value);
                    }

                    function takesB(B $b): void {}

                    /** @param A&B $value */
                    function test($value): void {
                        with($value, function ($x) {
                            takesB($x);
                        });
                    }',
            ],
            'callableObject' => [
                'code' => '<?php
                    /**
                     * @param object&callable():void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object();
                    }
                ',
            ],
            'classStringOfCallableObject' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable():void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object("foo");
                    }
                ',
            ],
            'classStringOfCallableObjectWithRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object("foo");
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithReturnType' => [
                'code' => '<?php
                    /**
                     * @param object&callable():int $object
                     */
                    function takesCallableObject(object $object): int {
                        return $object();
                    }
                ',
            ],
            'classStringOfCallableObjectWithReturnType' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable():int> $className
                     */
                    function takesCallableObject(string $className): int {
                        $object = new $className();
                        return $object();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfCallableObjectEqualsObjectWithCallableIntersection' => [
                'code' => '<?php
                    /**
                     * @param class-string<callable-object> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }

                    class Foo
                    {
                        public function __invoke(): void
                        {
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'assertions' => [],
                'ignored_issues' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfImportedCallableTypeIntersection' => [
                'code' => '<?php
                    /** @psalm-type CallableType = callable */
                    class Bar
                    {

                    }

                    /** @psalm-import-type CallableType from Bar */
                    class Foo
                    {
                        /**
                         * @param class-string<object&CallableType> $className
                         */
                        function takesCallableObject(string $className): void {}
                    }
                    ',
                'assertions' => [],
                'ignored_issues' => [],
            ],
            'objectShapePropertyOnInterfaceIntersection' => [
                'code' => '<?php
                    interface Foo {}

                    /** @var Foo&object{id: int} $x */
                    $id = $x->id;',
                'assertions' => [
                    '$id===' => 'int',
                ],
            ],
            'objectShapePropertyOnTwoInterfacesIntersection' => [
                'code' => '<?php
                    interface Foo {}
                    interface Bar {}

                    /** @var Foo&Bar&object{id: int} $x */
                    $id = $x->id;',
                'assertions' => [
                    '$id===' => 'int',
                ],
            ],
            'objectShapePropertyOnFinalClassWithMagicGetter' => [
                'code' => '<?php
                    final class Row {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    /** @var Row&object{id: int} $row */
                    $id = $row->id;',
                'assertions' => [
                    '$id' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'objectShapePropertyIsNotHiddenByUnrelatedMixin' => [
                'code' => '<?php
                    class Helper {
                        public int $fromMixin = 1;
                    }

                    /** @mixin Helper */
                    class WithMixin {}

                    /** @var WithMixin&object{fromShape: string} $x */
                    $fromShape = $x->fromShape;
                    $fromMixin = $x->fromMixin;',
                'assertions' => [
                    '$fromShape' => 'string',
                    '$fromMixin' => 'int',
                ],
            ],
            'objectShapePropertyRefinesMixinProperty' => [
                'code' => '<?php
                    class Helper {
                        public string $name = "";
                    }

                    /** @mixin Helper */
                    class Row {}

                    /** @var Row&object{name: non-empty-string} $row */
                    $name = $row->name;',
                'assertions' => [
                    '$name===' => 'non-empty-string',
                ],
            ],
            'objectShapePropertyBeforeUnrelatedNamedIntersection' => [
                'code' => '<?php
                    class Row {}
                    class Other {}

                    /** @var Row&Other&object{id: int} $row */
                    $id = $row->id;',
                'assertions' => [
                    '$id' => 'int',
                ],
            ],
            'objectShapePropertyOnFinalClassWithInheritedMagicGetter' => [
                'code' => '<?php
                    class BaseRow {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    final class Row extends BaseRow {}

                    /** @var Row&object{id: int} $row */
                    $id = $row->id;',
                'assertions' => [
                    '$id' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'objectShapePropertyFromTemplatedIntersectionReturn' => [
                'code' => '<?php
                    class Builder {}

                    /** @mixin Builder */
                    class Model {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    final class Pivot {
                        public int $id = 1;
                    }

                    /**
                     * @template TRelated of object
                     * @template TPivot of object
                     */
                    final class Relation {
                        /** @return (TRelated&object{pivot: TPivot})|null */
                        public function first(): ?object {
                            return null;
                        }
                    }

                    /** @var Relation<Model, Pivot> $relation */
                    $related = $relation->first();
                    $pivotId = $related?->pivot->id;',
                'assertions' => [
                    '$pivotId' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinPropertyIsNotLookedUpOnOtherIntersectedTypes' => [
                'code' => '<?php
                    class Helper {
                        public int $fromMixin = 1;
                    }

                    /** @mixin Helper */
                    class WithMixin {}

                    interface Marker {}

                    /** @var WithMixin&Marker $x */
                    $fromMixin = $x->fromMixin;',
                'assertions' => [
                    '$fromMixin' => 'int',
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
            'missingPropertyOfAnIntersectionWithAnInterface' => [
                'code' => '<?php
                    interface I {}

                    abstract class A {}

                    abstract class B extends A {}

                    function test(I $i): int {
                        if (!$i instanceof A) {
                            return 0;
                        }

                        if (!$i instanceof B) {
                            return 0;
                        }

                        $i->number = 2;

                        return 1;
                    }',
                'error_message' => 'UndefinedPropertyAssignment',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'methodMissingInAllIntersectedTypesGoesThroughMagicCall' => [
                'code' => '<?php
                    interface MockObject {
                        public function expects(): string;
                    }

                    class Client {
                        /**
                         * @deprecated
                         * @param list<mixed> $arguments
                         */
                        public function __call(string $method, array $arguments): int {
                            return 1;
                        }
                    }

                    /** @param Client&MockObject $client */
                    function test($client): int {
                        return $client->other();
                    }',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'methodOfAnEarlierUnionArmDoesNotHideMagicCallOfAnIntersection' => [
                'code' => '<?php
                    interface HasFoo {
                        public function foo(): string;
                    }

                    interface Other {
                        public function other(): string;
                    }

                    class FirstClient {
                        /** @param list<mixed> $arguments */
                        public function __call(string $method, array $arguments): int {
                            return 1;
                        }
                    }

                    class SecondClient {
                        /** @param list<mixed> $arguments */
                        public function __call(string $method, array $arguments): int {
                            return 1;
                        }
                    }

                    /** @param (HasFoo&FirstClient)|(SecondClient&Other) $client */
                    function test($client): void {
                        echo $client->foo();
                    }',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'interfaceIntersectedWithShapeRejectsOtherProperty' => [
                'code' => '<?php
                    interface Foo {}

                    /** @var Foo&object{id: int} $x */
                    $other = $x->other;',
                'error_message' => 'NoInterfaceProperties',
            ],
            'callableObjectWithMissingStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object();
                    }
                ',
                'error_message' => 'TooFewArguments',
            ],
            'classStringOfCallableObjectWithMissingRequiredStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object();
                    }',
                'error_message' => 'TooFewArguments',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'callableObjectWithInvalidStringArgument' => [
                'code' => '<?php
                    /**
                     * @param object&callable(string):void $object
                     */
                    function takesCallableObject(object $object): void {
                        $object(true);
                    }
                ',
                'error_message' => 'InvalidArgument',
            ],
            'classStringOfCallableObjectWithInvalidStringArgument' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable(string):void> $className
                     */
                    function takesCallableObject(string $className): void {
                        $object = new $className();
                        $object(0);
                    }',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'classStringOfCallableObjectWillTriggerMixedMethodCall' => [
                'code' => '<?php
                    /**
                     * @param class-string<object&callable> $className
                     */
                    function takesCallableObject(string $className): void {
                        new $className();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'error_message' => 'MixedMethodCall',
            ],
            'classStringOfCallableIsNotAllowed' => [
                # Ref: https://github.com/phpstan/phpstan/issues/9148
                'code' => '<?php
                    /**
                     * @param class-string<callable():int> $className
                     */
                    function takesCallableObject(string $className): int {
                        $object = new $className();
                        return $object();
                    }

                    class Foo
                    {
                        public function __invoke(): int
                        {
                            return 0;
                        }
                    }

                    takesCallableObject(Foo::class);
                    ',
                'error_message' => 'class-string param can only target',
                'error_levels' => ['UnsafeInstantiation', 'MixedMethodCall'],
            ],
            'objectShapePropertyOnFinalClassWithoutMagicGetter' => [
                'code' => '<?php
                    final class Row {}

                    /** @var Row&object{id: int} $row */
                    $id = $row->id;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'objectShapePropertyOnFinalClassWithOnlyMixinMagicGetter' => [
                'code' => '<?php
                    class Helper {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    /** @mixin Helper */
                    final class Row {}

                    /** @var Row&object{id: int} $row */
                    $id = $row->id;',
                'error_message' => 'UndefinedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
