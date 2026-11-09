<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class StarProjectionTest extends TestCase
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
            'starProjectionInAUnionParamLetsTheOtherArmBindTheTemplate' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Box {}

                    /**
                     * @template TFirst
                     * @template TSecond
                     */
                    final class Pair {}

                    final class Factory {
                        /**
                         * @template T
                         * @param Box<T>|Pair<T, *> $subject
                         * @return Box<T>
                         */
                        public static function make(Box|Pair $subject): Box {
                            throw new Exception();
                        }
                    }

                    /** @var Box<int> $box */
                    $box = new Box();
                    $result = Factory::make($box);',
                'assertions' => [
                    '$result===' => 'Box<int>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAcceptsAnyArgumentInItsPosition' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    class Pair {}

                    /**
                     * @template TChild of object
                     * @extends Pair<int, TChild>
                     */
                    final class Child extends Pair {}

                    /**
                     * @template TRelated
                     * @template TParent
                     * @template TResult
                     */
                    final class Relation {}

                    final class Model {}

                    /** @template-covariant T */
                    interface Reader {}

                    /**
                     * @template T of object
                     * @implements Reader<T>
                     */
                    final class ObjectReader implements Reader {}

                    /** @param Pair<int, *> $p */
                    function takesStar(Pair $p): void {}

                    /** @param Pair<*, *> $p */
                    function takesStars(Pair $p): void {}

                    function takesNative(Pair $p): void {}

                    /** @param Pair $p */
                    function takesWithoutArguments(Pair $p): void {}

                    /**
                     * @template TModel
                     * @param Relation<Model, TModel, *> $r
                     * @return TModel
                     */
                    function parentOf(Relation $r) {
                        throw new Exception();
                    }

                    /** @param Relation<*, *, *> $r */
                    function takesAnyRelation(Relation $r): void {}

                    /** @param Reader<object> $r */
                    function takesObjectReader(Reader $r): void {}

                    /**
                     * @param Pair<int, stdClass> $concrete
                     * @param Pair<int, object> $bound
                     * @param Child<stdClass> $child
                     * @param Child<*> $starred_child
                     * @param Pair<int, *> $starred
                     * @param Relation<Model, string, int> $relation
                     * @param ObjectReader<*> $reader
                     */
                    function f(
                        Pair $concrete,
                        Pair $bound,
                        Child $child,
                        Child $starred_child,
                        Pair $starred,
                        Relation $relation,
                        ObjectReader $reader
                    ): string {
                        takesStar($concrete);
                        takesStar($bound);
                        takesStar($child);
                        takesStar($starred_child);
                        takesStar($starred);
                        takesStars($concrete);
                        takesStars($starred);
                        takesNative($starred);
                        takesWithoutArguments($starred);
                        takesAnyRelation($relation);
                        takesObjectReader($reader);

                        return parentOf($relation);
                    }',
            ],
            'starProjectionLetsTheOtherArgumentsBindTemplates' => [
                'code' => '<?php
                    abstract class Model {}
                    final class User extends Model {}
                    final class Post extends Model {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {}

                    /** @template T */
                    final class Box {}

                    /** @template TModel of Model */
                    final class Builder {}

                    /**
                     * @template TRelated of Model
                     * @template TParent of Model
                     * @template TResult
                     */
                    class Relation {}

                    /**
                     * @template TRelated of Model
                     * @template TParent of Model
                     * @extends Relation<TRelated, TParent, list<TRelated>>
                     */
                    final class HasMany extends Relation {}

                    /** @template TModel of Model */
                    class QueryBuilder {
                        /**
                         * @template T of Model
                         * @param Builder<T>|Relation<T, *, *>|class-string<T> $subject
                         * @return static<T>
                         */
                        public static function for($subject): static {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T
                     * @param Pair<T, *> $p
                     * @return T
                     */
                    function first(Pair $p) {
                        throw new Exception();
                    }

                    /**
                     * @template T
                     * @param Box<T>|Pair<T, *> $x
                     * @return T
                     */
                    function either(object $x) {
                        throw new Exception();
                    }

                    /** @var Pair<int, stdClass> $pair */
                    $pair = new Pair();
                    /** @var Box<string> $box */
                    $box = new Box();
                    /** @var Builder<User> $builder */
                    $builder = new Builder();
                    /** @var HasMany<Post, User> $has_many */
                    $has_many = new HasMany();
                    /** @var Relation<User, *, *> $starred_relation */
                    $starred_relation = new HasMany();

                    $first = first($pair);
                    $from_pair = either($pair);
                    $from_box = either($box);
                    $for_builder = QueryBuilder::for($builder);
                    $for_relation = QueryBuilder::for($has_many);
                    $for_starred_relation = QueryBuilder::for($starred_relation);
                    $for_class = QueryBuilder::for(User::class);',
                'assertions' => [
                    '$first===' => 'int',
                    '$from_pair===' => 'int',
                    '$from_box===' => 'string',
                    '$for_builder===' => 'QueryBuilder<User>',
                    '$for_relation===' => 'QueryBuilder<Post>',
                    '$for_starred_relation===' => 'QueryBuilder<User>',
                    '$for_class===' => 'QueryBuilder<User>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadsTheBound' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @var list<TSecond> */
                        public array $seconds = [];

                        /** @return TFirst */
                        public function first() {
                            throw new Exception();
                        }

                        /** @return TSecond */
                        public function second(): object {
                            throw new Exception();
                        }

                        /** @return list<TSecond> */
                        public function seconds(): array {
                            return [];
                        }

                        /** @return Closure(): TSecond */
                        public function producer(): Closure {
                            throw new Exception();
                        }

                        /** @return Box<TSecond> */
                        public function box(): Box {
                            throw new Exception();
                        }

                        /** @return Countable&Box<TSecond> */
                        public function countedBox(): Box {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @implements IteratorAggregate<TKey, TValue>
                     */
                    final class Collection implements IteratorAggregate {
                        /** @return Iterator<TKey, TValue> */
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }
                    }

                    /** @template TModel of Model */
                    final class Builder {
                        /** @return TModel */
                        public function getModel(): Model {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TModel of Model
                     * @mixin Builder<TModel>
                     */
                    final class Relation {}

                    /** @template T */
                    abstract class Base {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TModel of Model
                     * @extends Base<list<TModel>>
                     */
                    final class Listed extends Base {}

                    /** @return Pair<int, *> */
                    function pair(): Pair {
                        throw new Exception();
                    }

                    /** @return Collection<*, *> */
                    function collection(): Collection {
                        throw new Exception();
                    }

                    /** @return Relation<*> */
                    function relation(): Relation {
                        throw new Exception();
                    }

                    /** @return Listed<*> */
                    function listed(): Listed {
                        throw new Exception();
                    }

                    $pair = pair();
                    $first = $pair->first();
                    $second = $pair->second();
                    $seconds = $pair->seconds();
                    $seconds_property = $pair->seconds;
                    $producer = $pair->producer();
                    $box = $pair->box();
                    $counted_box = $pair->countedBox();
                    $model = relation()->getModel();
                    $models = listed()->get();

                    $last_key = null;
                    $last_value = null;
                    foreach (collection() as $key => $value) {
                        $last_key = $key;
                        $last_value = $value;
                    }',
                'assertions' => [
                    '$pair===' => 'Pair<int, *>',
                    '$first===' => 'int',
                    '$second===' => 'object',
                    '$seconds===' => 'list<object>',
                    '$seconds_property===' => 'list<object>',
                    '$producer===' => 'Closure[impure]():object',
                    '$box===' => 'Box<object>',
                    '$counted_box===' => 'Countable&Box<object>',
                    '$model===' => 'Model',
                    '$models===' => 'list<Model>',
                    '$last_key===' => 'array-key|null',
                    '$last_value===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'starProjectionInDocblockPositions' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return T */
                        public function get(): object {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TRelated
                     * @template TParent
                     * @template TResult
                     */
                    final class Relation {}

                    /** @mixin Box<*> */
                    final class BoxProxy {}

                    final class Holder {
                        /** @var Box<*>|null */
                        public ?Box $box = null;
                    }

                    /** @return class-string<*> */
                    function anyClass(): string {
                        throw new Exception();
                    }

                    /** @return class-string<Box<*>> */
                    function boxClass(): string {
                        throw new Exception();
                    }

                    /** @return array{relation: Relation<*, *, *>} */
                    function shape(): array {
                        throw new Exception();
                    }

                    /** @return array<array-key, array|(callable(Relation<*, *, *>): mixed)|string> */
                    function relations(): array {
                        return [];
                    }

                    /** @return list<*> */
                    function anyList(): array {
                        return [];
                    }

                    $any_class = anyClass();
                    $box_class = boxClass();
                    $shape = shape();
                    $relations = relations();
                    $any_list = anyList();
                    $holder_box = (new Holder())->box;
                    /** @var Box<*> $var_box */
                    $var_box = new Box();
                    $proxied = (new BoxProxy())->get();',
                'assertions' => [
                    '$any_class===' => 'class-string',
                    '$box_class===' => 'class-string<Box<*>>',
                    '$shape===' => 'array{relation: Relation<*, *, *>}',
                    '$relations===' => 'array<array-key, array<array-key, mixed>|callable[impure](Relation<*, *, *>):mixed|string>',
                    '$any_list===' => 'list<mixed>',
                    '$holder_box===' => 'Box<*>|null',
                    '$var_box===' => 'Box<*>',
                    '$proxied===' => 'object',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadsTheBoundOfTheReceiversTemplate' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template TValue */
                    trait BuildsQueries {
                        /** @return TValue|null */
                        public function first(): mixed {
                            return null;
                        }
                    }

                    /** @template TModel of Model */
                    final class Builder {
                        /** @use BuildsQueries<TModel> */
                        use BuildsQueries;

                        /** @return class-string<TModel> */
                        public function modelClass(): string {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template A of object
                     * @template B of A
                     */
                    final class Dependent {
                        /** @return B */
                        public function get(): object {
                            throw new Exception();
                        }
                    }

                    /** @return Builder<*> */
                    function builder(): Builder {
                        throw new Exception();
                    }

                    /** @return Dependent<DateTimeImmutable, *> */
                    function dependent(): Dependent {
                        throw new Exception();
                    }

                    $first = builder()->first();
                    $model_class = builder()->modelClass();
                    $date = dependent()->get();',
                'assertions' => [
                    '$first===' => 'Model|null',
                    '$model_class===' => 'class-string<Model>',
                    '$date===' => 'DateTimeImmutable',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionTemplatedMixinIsItsBound' => [
                'code' => '<?php
                    abstract class Model {
                        public function save(): bool {
                            return true;
                        }
                    }

                    /**
                     * @template TModel of Model
                     * @mixin TModel
                     */
                    final class Proxy {}

                    /** @return Proxy<*> */
                    function proxy(): Proxy {
                        throw new Exception();
                    }

                    $saved = proxy()->save();',
                'assertions' => [
                    '$saved===' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionIsNoValueOfAnIteration' => [
                'code' => '<?php
                    /** @return IteratorAggregate<int, *> */
                    function aggregate(): IteratorAggregate {
                        throw new Exception();
                    }

                    /** @return Traversable<*, *> */
                    function traversable(): Traversable {
                        throw new Exception();
                    }

                    /** @return Generator<*, *, *, *> */
                    function generator(): Generator {
                        throw new Exception();
                    }

                    /**
                     * @param Generator<int, int, mixed, *> $inner
                     * @return Generator<int, int, mixed, int>
                     */
                    function delegate(Generator $inner): Generator {
                        $result = yield from $inner;

                        return is_int($result) ? $result : 0;
                    }

                    /** @return Generator<int, int, *, int> */
                    function receive(): Generator {
                        $sent = yield 1;

                        return is_int($sent) ? $sent : 0;
                    }

                    $spread = [...aggregate()];
                    $iterable = rand(0, 1) ? traversable() : [];
                    $traversable_key = null;
                    $traversable_value = null;
                    foreach (traversable() as $key => $value) {
                        $traversable_key = $key;
                        $traversable_value = $value;
                    }
                    foreach (generator() as $_) {}',
                'assertions' => [
                    '$spread===' => 'list<mixed>',
                    '$iterable===' => 'iterable<mixed, mixed>',
                    '$traversable_key===' => 'mixed',
                    '$traversable_value===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'starProjectionReadsKeyOfValueOfAndPropertiesOfTheBound' => [
                'code' => '<?php
                    final class Model {
                        public int $id = 0;
                    }

                    /**
                     * @template TArray of array{foo: int, bar: string}
                     * @template TModel of Model
                     */
                    final class Holder {
                        /** @return key-of<TArray> */
                        public function key(): string {
                            throw new Exception();
                        }

                        /** @return value-of<TArray> */
                        public function value(): mixed {
                            throw new Exception();
                        }

                        /** @return properties-of<TModel> */
                        public function properties() {
                            throw new Exception();
                        }
                    }

                    /** @return Holder<*, *> */
                    function holder(): Holder {
                        throw new Exception();
                    }

                    $key = holder()->key();
                    $value = holder()->value();
                    $properties = holder()->properties();',
                'assertions' => [
                    '$key===' => '\'bar\'|\'foo\'',
                    '$value===' => 'int|string',
                    '$properties===' => 'array{id: int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadsAnIndexedAccessOfTheBound' => [
                'code' => '<?php
                    /**
                     * @template TArray of array{a: object}
                     * @template TKey of key-of<TArray>
                     */
                    final class Lookup {
                        /** @return TArray[TKey] */
                        public function get() {
                            throw new Exception();
                        }
                    }

                    /** @return Lookup<*, "a"> */
                    function lookup(): Lookup {
                        throw new Exception();
                    }

                    $value = lookup()->get();',
                'assertions' => [
                    '$value===' => 'object',
                ],
            ],
            'starProjectionDependentBoundFollowsTheOtherArguments' => [
                'code' => '<?php
                    /**
                     * @template A of object
                     * @template B of A
                     */
                    final class Dep {
                        /** @return B */
                        public function b(): object {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @param Dep<T, *> $dep
                     * @return Dep<T, *>
                     */
                    function passThrough(Dep $dep): Dep {
                        return $dep;
                    }

                    /** @template X of object */
                    final class Holder {
                        /** @param Dep<X, *> $dep */
                        public function __construct(public Dep $dep) {}
                    }

                    /** @return Dep<DateTime, *> */
                    function dep(): Dep {
                        throw new Exception();
                    }

                    /** @return Holder<DateTime> */
                    function holder(): Holder {
                        throw new Exception();
                    }

                    $from_function = passThrough(dep())->b();
                    $from_property = holder()->dep->b();',
                'assertions' => [
                    '$from_function===' => 'DateTime',
                    '$from_property===' => 'DateTime',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionParamDoesNotRetypeAnEmptyValue' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /** @param Box<*> $box */
                    function takesAnyBox(Box $box): void {}

                    /** @param Box<int> $box */
                    function takesIntBox(Box $box): void {}

                    /** @var Box<never> $box */
                    $box = new Box();
                    takesAnyBox($box);
                    takesIntBox($box);',
            ],
            'starProjectionBindsAFunctionTemplateAsTheBound' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /** @template-covariant T of Countable */
                    interface Reader {
                        /** @return T */
                        public function read(): Countable;
                    }

                    /**
                     * @template T of Countable
                     * @param list<Reader<T>> $readers
                     * @return T
                     */
                    function readAll(array $readers): Countable {
                        throw new Exception();
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @param Closure(Box<T>): void $callback
                     * @return T
                     */
                    function tap(mixed $value, Closure $callback): mixed {
                        return $value;
                    }

                    /**
                     * @param Reader<*> $any
                     * @param Reader<ArrayObject<int, int>> $array_object
                     * @param Closure(Box<*>): void $any_box
                     */
                    function f(Reader $any, Reader $array_object, Closure $any_box): void {
                        $read = readAll([$array_object, $any]);
                        /** @psalm-check-type-exact $read = ArrayObject<int, int>|Countable */
                        $tapped = tap(1, $any_box);
                        /** @psalm-check-type-exact $tapped = 1 */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadsAComposedParentArgument' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    abstract class Base {}

                    /**
                     * @template TModel of Model
                     * @extends Base<list<TModel>>
                     */
                    final class Listed extends Base {}

                    /**
                     * @template T
                     * @param Base<T> $base
                     * @return T
                     */
                    function getOf(Base $base): mixed {
                        throw new Exception();
                    }

                    /** @param Base<list<Model>> $base */
                    function takesModels(Base $base): void {}

                    /** @param Listed<*> $listed */
                    function f(Listed $listed): void {
                        takesModels($listed);

                        foreach (getOf($listed) as $item) {
                            /** @psalm-check-type-exact $item = Model */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionStaysUnknownWhenNarrowedToItsClass' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /** @return Box<*>|string */
                    function boxOrString(): Box|string {
                        throw new Exception();
                    }

                    $item = boxOrString();
                    $box = $item instanceof Box ? $item : null;',
                'assertions' => [
                    '$box===' => 'Box<*>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionStaysUnknownWhenNarrowedToAChild' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    class Pair {}

                    /**
                     * @template TChild of object
                     * @extends Pair<int, TChild>
                     */
                    final class Child extends Pair {}

                    /**
                     * @template TChild of object
                     * @template TOwn
                     * @extends Pair<int, TChild>
                     */
                    final class ChildWithItsOwnTemplate extends Pair {}

                    /** @return Pair<int, *> */
                    function pair(): Pair {
                        throw new Exception();
                    }

                    $pair = pair();
                    $child = $pair instanceof Child ? $pair : null;
                    $with_own = $pair instanceof ChildWithItsOwnTemplate ? $pair : null;',
                'assertions' => [
                    '$child===' => 'Child<*>|null',
                    '$with_own===' => 'ChildWithItsOwnTemplate<*, *>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
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
            'starProjectionStillChecksTheOtherArguments' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {}

                    /** @param Pair<int, *> $p */
                    function takesStar(Pair $p): void {}

                    /** @param Pair<string, stdClass> $p */
                    function f(Pair $p): void {
                        takesStar($p);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:35 - Argument 1 of takesStar expects Pair<int, *>, but Pair<string, stdClass> provided',
            ],
            'starProjectionIsNotTheConcreteArgument' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {}

                    /** @param Pair<int, stdClass> $p */
                    function takesConcrete(Pair $p): void {}

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        takesConcrete($p);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:39 - Argument 1 of takesConcrete expects Pair<int, stdClass>, but Pair<int, *> provided',
            ],
            'starProjectionIsNotTheBoundArgument' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {}

                    /** @param Pair<int, object> $p */
                    function takesBound(Pair $p): void {}

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        takesBound($p);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:36 - Argument 1 of takesBound expects Pair<int, object>, but Pair<int, *> provided',
            ],
            'starProjectionIsNotTheMixedArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /** @param Box<mixed> $b */
                    function takesMixed(Box $b): void {}

                    /** @param Box<*> $b */
                    function f(Box $b): void {
                        takesMixed($b);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:36 - Argument 1 of takesMixed expects Box<mixed>, but Box<*> provided',
            ],
            'starProjectionFitsOnlyACovariantArgumentHoldingTheBound' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Reader {}

                    /**
                     * @template T of object
                     * @implements Reader<T>
                     */
                    final class ObjectReader implements Reader {}

                    /** @param Reader<stdClass> $r */
                    function takesStdClassReader(Reader $r): void {}

                    /** @param ObjectReader<*> $r */
                    function f(ObjectReader $r): void {
                        takesStdClassReader($r);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:45 - Argument 1 of takesStdClassReader expects Reader<stdClass>, but ObjectReader<*> provided',
            ],
            'starProjectionDoesNotBindATemplateArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template T
                     * @param Box<T> $b
                     */
                    function takesTemplated(Box $b): void {}

                    /** @param Box<*> $b */
                    function f(Box $b): void {
                        takesTemplated($b);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:40 - Argument 1 of takesTemplated expects Box<mixed>, but Box<*> provided',
            ],
            'starProjectionDoesNotBindATemplateArgumentNextToStars' => [
                'code' => '<?php
                    /**
                     * @template TRelated
                     * @template TParent
                     * @template TResult
                     */
                    final class Relation {}

                    /**
                     * @template T
                     * @param Relation<T, *, *> $r
                     */
                    function takesTemplated(Relation $r): void {}

                    /** @param Relation<*, *, *> $r */
                    function f(Relation $r): void {
                        takesTemplated($r);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40 - Argument 1 of takesTemplated expects Relation<mixed, *, *>, but Relation<*, *, *> provided',
            ],
            'starProjectionInExtends' => [
                'code' => '<?php
                    /** @template T */
                    class Box {}

                    /** @extends Box<*> */
                    final class StarBox extends Box {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The * type argument of Box<*> is not allowed in @extends',
            ],
            'starProjectionInImplements' => [
                'code' => '<?php
                    /** @template T */
                    interface Box {}

                    /** @implements Box<*> */
                    final class StarBox implements Box {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The * type argument of Box<*> is not allowed in @implements',
            ],
            'starProjectionInUse' => [
                'code' => '<?php
                    /** @template T */
                    trait Box {}

                    final class StarBox {
                        /** @use Box<*> */
                        use Box;
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - The * type argument of Box<*> is not allowed in @use',
            ],
            'starProjectionIsNoClassStringMapValue' => [
                'code' => '<?php
                    /** @param class-string-map<T, *> $map */
                    function f(array $map): void {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32 - class-string-map cannot take a * value',
            ],
        ];
    }
}
