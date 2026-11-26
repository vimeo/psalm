<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

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
            'objectShapesOfAnIntersectionAreMerged' => [
                'code' => '<?php
                    class Card {}

                    /** @param Card&object{last4: string}&object{brand: string} $card */
                    function describe(object $card): string {
                        return $card->last4 . $card->brand;
                    }

                    /** @param object{id: int|string}&object{id: int} $o */
                    function id(object $o): int {
                        return $o->id;
                    }',
            ],
            'objectShapesIntersectedByAnAssertionAreMerged' => [
                'code' => '<?php
                    class Card {}

                    /** @psalm-assert Card&object{brand: string} $c */
                    function assertBrand(object $c): void {}

                    /** @psalm-assert object{brand: string} $c */
                    function assertBareBrand(object $c): void {}

                    /** @param Card&object{last4: string} $card */
                    function named(object $card): string {
                        assertBrand($card);
                        return $card->last4 . $card->brand;
                    }

                    /** @param object{last4: string} $card */
                    function bare(object $card): string {
                        assertBareBrand($card);
                        return $card->last4 . $card->brand;
                    }

                    /** @param Card&object{last4: string} $card */
                    function namedThenBare(object $card): string {
                        assertBareBrand($card);
                        return $card->last4 . $card->brand;
                    }',
            ],
            'objectShapeAssertedOnABareShapeIsMergedIntoIt' => [
                'code' => '<?php
                    /** @psalm-assert object{id: int, b: string} $c */
                    function assertIdB(object $c): void {}

                    /** @return object{id: int|null} */
                    function o(): object {
                        return (object) ["id" => null];
                    }

                    $o = o();
                    assertIdB($o);
                    $id = $o->id;
                    $b = $o->b;',
                'assertions' => [
                    '$o===' => 'object{id:int, b:string}',
                    '$id===' => 'int',
                    '$b===' => 'string',
                ],
            ],
            'objectShapeAssertedWithAClassNameInAnotherCaseIsMerged' => [
                'code' => '<?php
                    class Card {}

                    /** @psalm-assert card&object{brand: string} $c */
                    function assertBrand(object $c): void {}

                    /** @return Card&object{last4: string} */
                    function card(): object {
                        throw new RuntimeException();
                    }

                    $card = card();
                    assertBrand($card);
                    $last4 = $card->last4;
                    $brand = $card->brand;',
                'assertions' => [
                    '$last4===' => 'string',
                    '$brand===' => 'string',
                ],
            ],
            'iterableIntersectionAcceptsObjectsOfEachType' => [
                'code' => '<?php
                    /** @param iterable<int, string>&Countable $x */
                    function takesCountable($x): void {}

                    /**
                     * @param ArrayObject<int, string> $a
                     * @param Iterator<int, string>&Countable $i
                     */
                    function f(ArrayObject $a, Iterator&Countable $i): void {
                        takesCountable($a);
                        takesCountable($i);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'propertyOfAnObjectIntersectedWithAShapeHasTheTypeOfBoth' => [
                'code' => '<?php
                    final class V {
                        public ?string $x = null;
                    }

                    /**
                     * @param V&object{x: string} $v
                     */
                    function narrowed(V $v): string {
                        return $v->x;
                    }

                    /**
                     * @param V&object{x: mixed} $v
                     */
                    function notWidened(V $v): ?string {
                        return $v->x;
                    }

                    /**
                     * @param V&object{x: string} $v
                     */
                    function read(V $v): void {
                        $x = $v->x;
                        echo $x;
                    }',
            ],
            'genericWithoutArgumentsInIntersectionGetsItsDefaultsLikeAlone' => [
                'code' => '<?php
                    interface I {}

                    /** @template T of object */
                    interface Coll {}

                    abstract class B {
                        /** @return Coll */
                        abstract public function alone(): Coll;

                        /** @return I&Coll */
                        abstract public function both(): I;
                    }

                    function b(): B {
                        throw new Exception();
                    }

                    $alone = b()->alone();
                    $both = b()->both();',
                'assertions' => [
                    '$alone===' => 'Coll<object>',
                    '$both===' => 'I&Coll<object>',
                ],
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
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
            'objectShapePartIsNotAMixedReceiverNarrowsNull' => [
                'code' => '<?php
                    class Foo {
                        public function get(): int {
                            return 1;
                        }
                    }

                    /** @var null|(Foo&object{a: int}) $x */
                    $x = null;
                    $x->get();
                    $after = $x;',
                'assertions' => [
                    '$after===' => 'Foo&object{a:int}',
                ],
                'ignored_issues' => ['PossiblyNullReference'],
            ],
            'objectShapePartIsNotAMixedReceiverByRefArgument' => [
                'code' => '<?php
                    class Foo {
                        public function fill(?int &$out): void {
                            $out = 1;
                        }
                    }

                    /** @var Foo&object{a: int} $x */
                    $x->fill($filled);
                    echo $filled;',
            ],
            'callableObjectPartOfAnIntersectionWithAShapeKeepsInvoke' => [
                'code' => '<?php
                    interface Marker {}

                    /** @psalm-assert callable-object $value */
                    function requireCallable(object $value): void {
                        if (!is_callable($value)) {
                            throw new InvalidArgumentException();
                        }
                    }

                    /** @param Marker&object{a: int} $value */
                    function invoke(object $value): void {
                        requireCallable($value);
                        $value->__invoke();
                    }',
            ],
            'methodOfAnObjectShapeDeclaredByMethodExistsIsCaseInsensitive' => [
                'code' => '<?php
                    final class Card {}

                    function charge(Card $card): void {
                        if (method_exists($card, "Charge")) {
                            $card->charge();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedMethodCall'],
            ],
            'propertyOfAnArrayWithFewerKeysThanAListKeepsItsType' => [
                'code' => '<?php
                    final class C {
                        /** @var array<0, string> */
                        public array $x = [];
                    }

                    /**
                     * @param C&object{x: list<string>} $v
                     * @return array<0, string>
                     */
                    function f(object $v): array {
                        return $v->x;
                    }',
            ],
            'argumentOfAMethodDeclaredByEveryIntersectionMemberFitsAnyDeclaration' => [
                'code' => '<?php
                    interface IntConsumer {
                        public function consume(int $value): void;
                    }
                    interface StringConsumer {
                        public function consume(string $value): void;
                    }
                    interface BoolConsumer {
                        public function consume(?bool $value): void;
                    }
                    interface AnyConsumer {
                        /** @param mixed $value */
                        public function consume($value): void;
                    }
                    class IntBase {
                        public function consume(int $value): void {}
                    }

                    /** @param IntConsumer&StringConsumer $consumer */
                    function feed($consumer, int|string $value): void {
                        $consumer->consume(1);
                        $consumer->consume("a");
                        $consumer->consume($value);
                        $consumer->consume(value: "a");
                    }

                    /** @param IntConsumer&StringConsumer&BoolConsumer $consumer */
                    function feedThree($consumer): void {
                        $consumer->consume(1);
                        $consumer->consume("a");
                        $consumer->consume(null);
                    }

                    /** @param IntConsumer&AnyConsumer $consumer */
                    function feedUntyped($consumer): void {
                        $consumer->consume([1]);
                    }

                    /** @param IntBase&StringConsumer $consumer */
                    function feedClassAndInterface($consumer): void {
                        $consumer->consume("a");
                    }

                    /**
                     * @template T of IntConsumer&StringConsumer
                     * @param T $consumer
                     */
                    function feedTemplate($consumer): void {
                        $consumer->consume("a");
                    }

                    function feedNarrowed(IntConsumer $consumer): void {
                        if ($consumer instanceof StringConsumer) {
                            $consumer->consume("a");
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'arrayArgumentOfAMethodDeclaredByEveryIntersectionMemberFitsAnyDeclaration' => [
                'code' => '<?php
                    interface StringKeys {
                        /** @param array<string, int> $p */
                        public function map(array $p): void;
                        /** @param list<int> $p */
                        public function list(array $p): void;
                        /** @param array{a: int} $p */
                        public function shape(array $p): void;
                    }
                    interface IntKeys {
                        /** @param array<int, string> $p */
                        public function map(array $p): void;
                        /** @param list<string> $p */
                        public function list(array $p): void;
                        /** @param array{b: string} $p */
                        public function shape(array $p): void;
                    }

                    /** @param StringKeys&IntKeys $x */
                    function feed($x): void {
                        $x->map(["k" => 1]);
                        $x->map([1 => "v"]);
                        $x->list([1, 2]);
                        $x->list(["a"]);
                        $x->shape(["a" => 1]);
                        $x->shape(["b" => "x"]);
                    }',
            ],
            'closureArgumentOfAMethodDeclaredByEveryIntersectionMemberFitsAnyDeclaration' => [
                'code' => '<?php
                    interface IntRunner {
                        /** @param Closure(int):void $c */
                        public function run(Closure $c): void;
                        /** @param callable(int):void $c */
                        public function call(callable $c): void;
                    }
                    interface StringRunner {
                        /** @param Closure(string):void $c */
                        public function run(Closure $c): void;
                        /** @param callable(string):void $c */
                        public function call(callable $c): void;
                    }

                    /** @param IntRunner&StringRunner $x */
                    function feed($x): void {
                        $x->run(fn(int $v) => null);
                        $x->run(function (string $v): void {});
                        $x->run(function ($v): void {});
                        $x->call(function (int $v): void {});
                        $x->call(function (string $v): void {});
                    }',
            ],
            'namedArgumentOfAMethodDeclaredByEveryIntersectionMemberMatchesByName' => [
                'code' => '<?php
                    interface A {
                        public function m(int $a = 0, string $b = ""): void;
                    }
                    interface B {
                        public function m(string $b = "", int $a = 0): void;
                    }

                    /** @param A&B $x */
                    function f($x): void {
                        $x->m(b: "s");
                        $x->m(a: 1);
                        $x->m(1, 2);
                        $x->m("s");
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'selfParamOfAMethodDeclaredByEveryIntersectionMemberIsTheirOwnClass' => [
                'code' => '<?php
                    interface First {
                        public function with(self $other): void;
                    }
                    interface Second {
                        public function with(self $other): void;
                    }

                    /** @param First&Second $both */
                    function combine($both, First $first, Second $second): void {
                        $both->with($first);
                        $both->with($second);
                    }',
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
            'objectShapesOfAnIntersectionGivingAPropertyIncompatibleTypes' => [
                'code' => '<?php
                    /** @param object{id: int}&object{id: string} $o */
                    function id(object $o): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'iterableIntersectionDoesNotAcceptTraversable' => [
                'code' => '<?php
                    /** @param iterable&Countable $x */
                    function takesCountable($x): void {}

                    function f(Traversable $t): void {
                        takesCountable($t);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'iterableIntersectionDoesNotAcceptGenerator' => [
                'code' => '<?php
                    /** @param iterable&Countable $x */
                    function takesCountable($x): void {}

                    function f(Generator $g): void {
                        takesCountable($g);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'objectIntersectedWithTemplateShowsTemplateInParentheses' => [
                'code' => '<?php
                    interface I {}

                    /**
                     * @template T of object
                     * @param I&T $x
                     */
                    function f(object $x): int {
                        return strlen($x);
                    }',
                'error_message' => 'but I&(T:fn-f as object) provided',
            ],
            'genericObjectIntersectedWithTemplateShowsTemplateInParentheses' => [
                'code' => '<?php
                    /** @template V */
                    interface I {}

                    /**
                     * @template T of object
                     * @param I<int>&T $x
                     */
                    function f(object $x): int {
                        return strlen($x);
                    }',
                'error_message' => 'but I<int>&(T:fn-f as object) provided',
            ],
            'iterableIntersectedWithTemplateShowsTemplateInParentheses' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @param iterable<int>&T $x
                     */
                    function f(object $x): int {
                        return strlen($x);
                    }',
                'error_message' => 'but iterable<mixed, int>&(T:fn-f as object) provided',
            ],
            'shapeIntersectedWithTemplateShowsTemplateInParentheses' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @param object{a: int}&T $x
                     */
                    function f(object $x): int {
                        return strlen($x);
                    }',
                'error_message' => 'but object{a:int}&(T:fn-f as object) provided',
            ],
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
            'objectShapePartDoesNotMakeUndefinedMagicMethodPossible' => [
                'code' => '<?php
                    /** @psalm-seal-methods */
                    class Foo {
                        public function __call(string $name, array $args): mixed {
                            return null;
                        }

                        /** @psalm-pure */
                        public function known(): int {
                            return 1;
                        }
                    }

                    /** @var Foo&object{a: int} $x */
                    $x->notmagic();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'objectShapePartDoesNotMakeUndefinedMethodPossible' => [
                'code' => '<?php
                    final class Card {
                        public function known(): int {
                            return 1;
                        }
                    }

                    /** @var Card&object{a: int} $x */
                    $x->nope();',
                'error_message' => 'UndefinedMethod',
            ],
            'loneObjectShapeReceiverIsAMixedMethodCall' => [
                'code' => '<?php
                    /** @param object{a: int} $x */
                    function test(object $x): void {
                        $x->anything();
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'objectShapePartDeclaringMagicCallKeepsAnUndeclaredMethodPossible' => [
                'code' => '<?php
                    class Card {}

                    function charge(Card $card): void {
                        if (method_exists($card, "__call")) {
                            $card->charge();
                        }
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'argumentFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface IntConsumer {
                        public function consume(int $value): void;
                    }
                    interface StringConsumer {
                        public function consume(string $value): void;
                    }

                    /** @param IntConsumer&StringConsumer $consumer */
                    function feed($consumer): void {
                        $consumer->consume(1.5);
                    }',
                'error_message' => 'Argument 1 of StringConsumer::consume expects string, but float(1.5) provided',
            ],
            'objectFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface First {
                        public function with(self $other): void;
                    }
                    interface Second {
                        public function with(self $other): void;
                    }

                    /** @param First&Second $both */
                    function combine($both): void {
                        $both->with(new stdClass());
                    }',
                'error_message' => 'Argument 1 of Second::with expects Second, but stdClass provided',
            ],
            'mapFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface StringKeys {
                        /** @param array<string, int> $p */
                        public function map(array $p): void;
                        /** @param list<int> $p */
                        public function list(array $p): void;
                        /** @param array{a: int} $p */
                        public function shape(array $p): void;
                        /** @param Closure(int):void $c */
                        public function run(Closure $c): void;
                    }
                    interface IntKeys {
                        /** @param array<int, string> $p */
                        public function map(array $p): void;
                        /** @param list<string> $p */
                        public function list(array $p): void;
                        /** @param array{b: string} $p */
                        public function shape(array $p): void;
                        /** @param Closure(string):void $c */
                        public function run(Closure $c): void;
                    }

                    /** @param StringKeys&IntKeys $x */
                    function feed($x): void {
                        $x->map(["k" => "v"]);
                    }',
                'error_message' => 'Argument 1 of IntKeys::map expects array<int, string>, but array{k: \'v\'} provided',
            ],
            'listFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface StringKeys {
                        /** @param array<string, int> $p */
                        public function map(array $p): void;
                        /** @param list<int> $p */
                        public function list(array $p): void;
                        /** @param array{a: int} $p */
                        public function shape(array $p): void;
                        /** @param Closure(int):void $c */
                        public function run(Closure $c): void;
                    }
                    interface IntKeys {
                        /** @param array<int, string> $p */
                        public function map(array $p): void;
                        /** @param list<string> $p */
                        public function list(array $p): void;
                        /** @param array{b: string} $p */
                        public function shape(array $p): void;
                        /** @param Closure(string):void $c */
                        public function run(Closure $c): void;
                    }

                    /** @param StringKeys&IntKeys $x */
                    function feed($x): void {
                        $x->list([1, "a"]);
                    }',
                'error_message' => 'Argument 1 of IntKeys::list expects list<string>, but list{1, \'a\'} provided',
            ],
            'shapeFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface StringKeys {
                        /** @param array<string, int> $p */
                        public function map(array $p): void;
                        /** @param list<int> $p */
                        public function list(array $p): void;
                        /** @param array{a: int} $p */
                        public function shape(array $p): void;
                        /** @param Closure(int):void $c */
                        public function run(Closure $c): void;
                    }
                    interface IntKeys {
                        /** @param array<int, string> $p */
                        public function map(array $p): void;
                        /** @param list<string> $p */
                        public function list(array $p): void;
                        /** @param array{b: string} $p */
                        public function shape(array $p): void;
                        /** @param Closure(string):void $c */
                        public function run(Closure $c): void;
                    }

                    /** @param StringKeys&IntKeys $x */
                    function feed($x): void {
                        $x->shape([]);
                    }',
                'error_message' => 'Argument 1 of IntKeys::shape expects array{b: string}, but array<never, never> provided',
            ],
            'closureFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface StringKeys {
                        /** @param array<string, int> $p */
                        public function map(array $p): void;
                        /** @param list<int> $p */
                        public function list(array $p): void;
                        /** @param array{a: int} $p */
                        public function shape(array $p): void;
                        /** @param Closure(int):void $c */
                        public function run(Closure $c): void;
                    }
                    interface IntKeys {
                        /** @param array<int, string> $p */
                        public function map(array $p): void;
                        /** @param list<string> $p */
                        public function list(array $p): void;
                        /** @param array{b: string} $p */
                        public function shape(array $p): void;
                        /** @param Closure(string):void $c */
                        public function run(Closure $c): void;
                    }

                    /** @param StringKeys&IntKeys $x */
                    function feed($x): void {
                        $x->run(function (array $v): void {});
                    }',
                'error_message' => 'Argument 1 of IntKeys::run expects Closure[impure](string):void',
            ],
            'namedArgumentFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface A {
                        public function m(int $a = 0, string $b = ""): void;
                    }
                    interface B {
                        public function m(string $b = "", int $a = 0): void;
                    }

                    /** @param A&B $x */
                    function f($x): void {
                        $x->m(b: 1);
                    }',
                'error_message' => 'Argument 1 of B::m expects string, but 1 provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'otherNamedArgumentFittingNoDeclarationOfAnIntersectionMethodIsReported' => [
                'code' => '<?php
                    interface A {
                        public function m(int $a = 0, string $b = ""): void;
                    }
                    interface B {
                        public function m(string $b = "", int $a = 0): void;
                    }

                    /** @param A&B $x */
                    function f($x): void {
                        $x->m(a: "s");
                    }',
                'error_message' => 'Argument 2 of B::m expects int, but \'s\' provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'namedArgumentRepeatingAPositionalOneOfAnIntersectionMethodKeepsTheDeclaredType' => [
                'code' => '<?php
                    interface A {
                        public function m(int $a = 0, string $b = ""): void;
                    }
                    interface B {
                        public function m(string $b = "", int $a = 0): void;
                    }

                    /** @param A&B $x */
                    function f($x): void {
                        $x->m(1, b: "s");
                    }',
                'error_message' => 'InvalidScalarArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:31 - Argument 1 of B::m expects string, but 1 provided',
                'error_levels' => ['InvalidNamedArgument'],
                'php_version' => '8.0',
            ],
            'argumentOfAMethodDeclaredByOneIntersectionMemberIsCheckedAgainstIt' => [
                'code' => '<?php
                    interface IntConsumer {
                        public function consume(int $value): void;
                    }
                    interface Other {
                        public function other(): void;
                    }

                    /** @param IntConsumer&Other $consumer */
                    function feed($consumer): void {
                        $consumer->consume("a");
                    }',
                'error_message' => 'Argument 1 of IntConsumer::consume expects int, but \'a\' provided',
            ],
        ];
    }
}
