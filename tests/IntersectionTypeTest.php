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
            'methodOfTheClassOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    final class Card {
                        public function brand(): string {
                            return "visa";
                        }
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): string {
                        return $card->brand();
                    }',
            ],
            'methodOfTheClassAnObjectShapeIsNarrowedTo' => [
                'code' => '<?php
                    class Entity {
                        public function get(string $key): int {
                            return 1;
                        }
                    }

                    /**
                     * @property null|(object{brand: string}&Entity) $card
                     */
                    final class PaymentMethod extends Entity {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    function test(PaymentMethod $paymentMethod): int {
                        $card = $paymentMethod->card;
                        if (!$card instanceof Entity) {
                            return 0;
                        }

                        return $card->get("brand");
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticReturnOfAMethodOfAnObjectShapeIntersectionKeepsTheShape' => [
                'code' => '<?php
                    class Card {
                        /** @return static */
                        public function fluent(): self {
                            return $this;
                        }
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): string {
                        return $card->fluent()->last4;
                    }',
            ],
            'methodOfTheSecondClassOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    interface Card {}

                    interface Expirable {
                        public function expiresAt(): string;
                    }

                    /** @param object{last4: string}&Card&Expirable $card */
                    function test(object $card): string {
                        return $card->expiresAt();
                    }',
            ],
            'templatedMethodOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(private mixed $value) {}

                        /** @return T */
                        public function get(): mixed {
                            return $this->value;
                        }
                    }

                    /** @param object{label: string}&Box<int> $box */
                    function test(object $box): int {
                        return $box->get();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfTheClassOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    final class Card {
                        public int $expiryYear = 2030;
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): int {
                        return $card->expiryYear;
                    }',
            ],
            'propertyOfTheClassAnObjectShapeIsNarrowedTo' => [
                'code' => '<?php
                    class Entity {
                        public int $livemode = 0;
                    }

                    /**
                     * @property null|(object{brand: string}&Entity) $card
                     */
                    final class PaymentMethod extends Entity {
                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    function test(PaymentMethod $paymentMethod): int {
                        $card = $paymentMethod->card;
                        if (!$card instanceof Entity) {
                            return 0;
                        }

                        return $card->livemode;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfTheShapeOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    class Card {
                        public int $expiryYear = 2030;
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): string {
                        return $card->last4;
                    }',
            ],
            'propertyAssignmentOfTheClassOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    final class Card {
                        public int $expiryYear = 2030;
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): void {
                        $card->expiryYear = 2031;
                    }',
            ],
            'staticMethodOfTheClassOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    class Card {
                        public static function network(): string {
                            return "visa";
                        }
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): string {
                        return $card::network();
                    }',
            ],
            'methodOfATemplateAnObjectShapeIsIntersectedWith' => [
                'code' => '<?php
                    class Card {
                        public function brand(): string {
                            return "visa";
                        }
                    }

                    /**
                     * @template T of Card
                     * @param object{last4: string}&T $card
                     */
                    function test(object $card): string {
                        return $card->brand();
                    }',
            ],
            'objectShapeIntersectionIsAnArgumentOfItsObjectShape' => [
                'code' => '<?php
                    class Card {}

                    /** @param object{last4: string} $shape */
                    function takesShape(object $shape): void {}

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): void {
                        takesShape($card);
                    }',
            ],
            'objectShapeIntersectionIsWrittenClassFirst' => [
                'code' => '<?php
                    interface Expirable {}
                    interface Card {}

                    /** @var object{last4: string}&Card&Expirable $card */
                    $type = $card;',
                'assertions' => [
                    '$type===' => 'Card&Expirable&object{last4:string}',
                ],
            ],
            'objectShapeIntersectionInferredFromATemplateIsWrittenClassFirst' => [
                'code' => '<?php
                    class Card {}

                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(private mixed $value) {}

                        /** @return T&Card */
                        public function get(): mixed {
                            return $this->value;
                        }
                    }

                    /** @var object{last4: string} $shape */
                    $type = (new Box($shape))->get();',
                'assertions' => [
                    '$type===' => 'Card&object{last4:string}',
                ],
                'ignored_issues' => ['MixedReturnTypeCoercion'],
                'php_version' => '8.0',
            ],
            'templateOfThePropertyOfAShapeIntersectedWithAClassIsInferred' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param object{value: T}&stdClass $o
                     * @return T
                     */
                    function value(object $o): mixed {
                        return $o->value;
                    }

                    $value = value((object) ["value" => 1]);',
                'assertions' => [
                    '$value===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templatedPropertyOfAShapeIntersectedWithAClassKeepsItsTemplate' => [
                'code' => '<?php
                    class Box {}

                    /**
                     * @template T
                     * @param object{value: T}&Box $box
                     * @return T
                     */
                    function value(object $box): mixed {
                        return $box->value;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'shapesOfATemplateAndOfItsShapeIntersectionAreMerged' => [
                'code' => '<?php
                    final class Card {}

                    /**
                     * @template T of object
                     * @param T $card
                     * @return T&Card&object{brand: string}
                     */
                    function withBrand(object $card): object {
                        throw new RuntimeException();
                    }

                    /**
                     * @template T of object
                     * @param T $card
                     * @return T&object{last4: string}
                     */
                    function withStringLast4(object $card): object {
                        throw new RuntimeException();
                    }

                    /** @return Card&object{last4: string} */
                    function card(): object {
                        throw new RuntimeException();
                    }

                    /** @return Card&object{last4: string|int} */
                    function cardWithAnyLast4(): object {
                        throw new RuntimeException();
                    }

                    $branded = withBrand(card());
                    $narrowed = withStringLast4(cardWithAnyLast4());',
                'assertions' => [
                    '$branded===' => 'Card&object{brand:string, last4:string}',
                    '$narrowed===' => 'Card&object{last4:string}',
                ],
            ],
            'narrowedPropertyOfAShapeIntersectedWithAClassNarrowsTheObject' => [
                'code' => '<?php
                    class Card {}

                    /** @param object{last4: string} $shape */
                    function takesShape(object $shape): void {}

                    /** @param Card&object{last4: string|int} $card */
                    function test(object $card): void {
                        if (is_string($card->last4)) {
                            takesShape($card);
                        }
                    }',
            ],
            'propertyMissingFromTheShapeOfAnIntersectionIsDeclaredByItsClass' => [
                'code' => '<?php
                    class Card {
                        public string $brand = "visa";
                    }

                    /** @param object{brand: string} $shape */
                    function takesShape(object $shape): void {}

                    /** @param Card&object{last4: string} $card */
                    function test(object $card): void {
                        takesShape($card);
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
            'objectShapeIntersectionOfATemplateParamIsWrittenClassFirst' => [
                'code' => '<?php
                    class Card {}
                    interface Other {}

                    /**
                     * @template T of object{last4: string}
                     * @param T&Card $card
                     * @return T
                     */
                    function identity(object $card): object {
                        return $card;
                    }

                    function test(Other $other): void {
                        identity($other);
                    }',
                'error_message' => 'Argument 1 of identity expects Card&object{last4:string}, but Other',
            ],
            'objectShapeIntersectionIsNotAnArgumentOfAnotherObjectShape' => [
                'code' => '<?php
                    class Card {}

                    /** @param object{expiry: int} $shape */
                    function takesShape(object $shape): void {}

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): void {
                        takesShape($card);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'undefinedPropertyOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    final class Card {
                        public int $expiryYear = 2030;
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): int {
                        return $card->missing;
                    }',
                'error_message' => 'UndefinedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'invalidPropertyAssignmentOfAnObjectShapeIntersection' => [
                'code' => '<?php
                    final class Card {
                        public int $expiryYear = 2030;
                    }

                    /** @param object{last4: string}&Card $card */
                    function test(object $card): void {
                        $card->expiryYear = "2031";
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'shapeIntersectionMatchedByNeitherItsShapeNorItsClassIsInvalid' => [
                'code' => '<?php
                    class Card {
                        public string $brand = "visa";
                    }

                    /** @param object{brand: string, last4: string} $shape */
                    function takesShape(object $shape): void {}

                    /** @param Card&object{brand: mixed} $card */
                    function test(object $card): void {
                        takesShape($card);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'objectCastIsNotAnArgumentOfAShapeWithAnotherPropertyType' => [
                'code' => '<?php
                    /** @param object{a: string} $shape */
                    function takesShape(object $shape): void {}

                    takesShape((object) ["a" => 1]);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
