<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Internal\Type\StarProjection;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;

use function array_column;
use function array_unique;
use function array_values;
use function basename;
use function dirname;
use function glob;
use function property_exists;
use function sort;
use function sprintf;
use function str_ends_with;

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
                    '$box===' => 'Box<*>',
                    '$counted_box===' => 'Countable&Box<*>',
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
            'starProjectionReadsByPosition' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @var Closure(TSecond): void */
                        public Closure $callback;

                        /** @param Closure(TSecond): void $callback */
                        public function __construct(Closure $callback) {
                            $this->callback = $callback;
                        }

                        /** @return Closure(TSecond): void */
                        public function consumer(): Closure {
                            throw new Exception();
                        }

                        /** @return Closure(Closure(TSecond): void): void */
                        public function consumerOfConsumer(): Closure {
                            throw new Exception();
                        }

                        /** @return Pair<TFirst, TSecond> */
                        public function self(): Pair {
                            return $this;
                        }
                    }

                    /** @return Pair<int, *> */
                    function pair(): Pair {
                        throw new Exception();
                    }

                    $pair = pair();
                    $callback_property = $pair->callback;
                    $consumer = $pair->consumer();
                    $consumer_of_consumer = $pair->consumerOfConsumer();
                    $self = $pair->self();',
                'assertions' => [
                    '$callback_property===' => 'Closure[impure](never):void',
                    '$consumer===' => 'Closure[impure](never):void',
                    '$consumer_of_consumer===' => 'Closure[impure](Closure[impure](object):void):void',
                    '$self===' => 'Pair<int, *>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInsideAnInvariantArgumentKeepsTheArgumentUnknown' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @return Box<list<TSecond>> */
                        public function listBox(): Box {
                            throw new Exception();
                        }

                        /** @return Box<Closure(TSecond): void> */
                        public function consumerBox(): Box {
                            throw new Exception();
                        }
                    }

                    /** @return Pair<int, *> */
                    function pair(): Pair {
                        throw new Exception();
                    }

                    $list_box = pair()->listBox();
                    $list = $list_box->get();
                    $consumer = pair()->consumerBox()->get();',
                'assertions' => [
                    '$list_box===' => 'Box<*>',
                    '$list===' => 'list<object>',
                    '$consumer===' => 'Closure[impure](never):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInsideANestedInvariantArgumentKeepsTheArgumentUnknown' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @template V */
                    class Base {
                        /** @param V $value */
                        public function __construct(public mixed $value) {}

                        /** @return V */
                        public function inherited(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<Box<Box<Closure(T): void>>>
                     */
                    final class Source extends Base {
                        /**
                         * @param Box<Box<T>> $nested
                         * @param Box<Box<Closure(T): void>> $value
                         */
                        public function __construct(public Box $nested, Box $value) {
                            parent::__construct($value);
                        }

                        /** @return Box<Box<T>> */
                        public function boxes(): Box {
                            throw new Exception();
                        }

                        /** @return Box<Box<Closure(T): void>> */
                        public function callbacks(): Box {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<Box<Box<T>>>
                     */
                    final class Boxes extends Base {}

                    /** @return Source<*> */
                    function source(): Source {
                        throw new Exception();
                    }

                    /** @return Boxes<*> */
                    function boxes(): Boxes {
                        throw new Exception();
                    }

                    $boxes = source()->boxes();
                    $box = $boxes->get();
                    $value = $box->get();
                    $callbacks = source()->callbacks();
                    $callback = $callbacks->get()->get();
                    $inherited = source()->inherited();
                    $inherited_callback = $inherited->get()->get();
                    $inherited_boxes = boxes()->inherited();
                    $inherited_value = $inherited_boxes->get()->get();
                    $property = source()->nested;
                    $property_value = $property->get()->get();
                    $inherited_property = boxes()->value;
                    $inherited_property_value = $inherited_property->get()->get();',
                'assertions' => [
                    '$boxes===' => 'Box<*>',
                    '$box===' => 'Box<*>',
                    '$value===' => 'object',
                    '$callbacks===' => 'Box<*>',
                    '$callback===' => 'Closure[impure](never):void',
                    '$inherited===' => 'Box<*>',
                    '$inherited_callback===' => 'Closure[impure](never):void',
                    '$inherited_boxes===' => 'Box<*>',
                    '$inherited_value===' => 'object',
                    '$property===' => 'Box<*>',
                    '$property_value===' => 'object',
                    '$inherited_property===' => 'Box<*>',
                    '$inherited_property_value===' => 'object',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInsideANestedInvariantArgumentOfACallbackIsRead' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @template T of Model */
                    abstract class Source {
                        /** @param Closure(Box<Box<T>>): void $callback */
                        abstract public function visit(Closure $callback): void;

                        /** @param Closure(Box<list<T>>): void $callback */
                        abstract public function visitList(Closure $callback): void;
                    }

                    function takesModel(Model $value): void {}

                    /** @param Source<*> $source */
                    function f(Source $source): void {
                        $source->visit(function ($outer): void {
                            /** @psalm-check-type-exact $outer = Box<*> */
                            $inner = $outer->get();
                            /** @psalm-check-type-exact $inner = Box<*> */
                            takesModel($inner->get());
                        });
                        $source->visitList(function ($outer): void {
                            foreach ($outer->get() as $value) {
                                takesModel($value);
                            }
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNextToConcreteArgumentsReadsTheCallbackOfAGenericMethod' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TUnknown of Model
                     * @template TKnown
                     */
                    abstract class Source {
                        /**
                         * @template TResult
                         * @param TResult $seed
                         * @param Closure(Box<array{unknown: TUnknown, known: TKnown, seed: TResult}>): TResult $callback
                         * @return TResult
                         */
                        abstract public function map(mixed $seed, Closure $callback): mixed;
                    }

                    /** @return Source<*, int> */
                    function source(): Source {
                        throw new Exception();
                    }

                    $source = source();
                    $first = $source->map("ok", function ($box) {
                        $data = $box->get();
                        /** @psalm-check-type-exact $data = array{known: int, seed: "ok", unknown: Model} */
                        return $data["seed"];
                    });
                    $second = $source->map(123, function ($box) {
                        $data = $box->get();
                        /** @psalm-check-type-exact $data = array{known: int, seed: 123, unknown: Model} */
                        return $data["seed"];
                    });',
                'assertions' => [
                    '$first===' => '\'ok\'',
                    '$second===' => '123',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedCallableArgumentIsReadByPosition' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @extends IteratorAggregate<int, Closure(T): void>
                     */
                    interface Consumers extends IteratorAggregate {}

                    /** @return Consumers<*> */
                    function consumers(): Consumers {
                        throw new Exception();
                    }

                    $last_consumer = null;
                    foreach (consumers() as $consumer) {
                        $last_consumer = $consumer;
                    }',
                'assertions' => [
                    '$last_consumer===' => 'Closure[impure](never):void|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedNullableArgumentKeepsNull' => [
                'code' => '<?php
                    /** @template-covariant T */
                    class Reader {}

                    /**
                     * @template TChild of object
                     * @extends Reader<TChild|null>
                     */
                    final class NullableReader extends Reader {}

                    /**
                     * @template T
                     * @param Reader<T> $reader
                     * @return T
                     */
                    function read(Reader $reader) {
                        throw new Exception();
                    }

                    /** @return NullableReader<*> */
                    function reader(): NullableReader {
                        throw new Exception();
                    }

                    $read = read(reader());',
                'assertions' => [
                    '$read===' => 'null|object',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionArrayCallableBindsTheReceiver' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param T $value */
                        public function set(object $value): void {}
                    }

                    /** @return Box<*> */
                    function anyBox(): Box {
                        throw new Exception();
                    }

                    $any_set = Closure::fromCallable([anyBox(), "set"]);',
                'assertions' => [
                    '$any_set===' => 'Closure[impure](never):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionUnknownMixedReadInAUnionIsMixed' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Collection {
                        /**
                         * @template TDefault
                         * @param TDefault $default
                         * @return TValue|TDefault
                         */
                        public function first($default = null) {
                            throw new Exception();
                        }
                    }

                    /** @return Collection<*> */
                    function collection(): Collection {
                        throw new Exception();
                    }

                    $first = collection()->first();',
                'assertions' => [
                    '$first===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'starProjectionHandsOutCallablesThatAcceptNothing' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @template TArr of array<string, int>
                     */
                    final class Source {
                        /**
                         * @param mixed $value
                         * @param-out Closure(T): void $value
                         */
                        public function consumer(&$value): void {
                            throw new Exception();
                        }

                        /** @psalm-assert Closure(T): void $value */
                        public function assertConsumer(mixed $value): void {}

                        /** @psalm-assert-if-true Closure(T): void $value */
                        public function isConsumer(mixed $value): bool {
                            throw new Exception();
                        }

                        /** @return Closure(key-of<TArr>): void */
                        public function keyConsumer(): Closure {
                            throw new Exception();
                        }
                    }

                    /** @return Source<*, *> */
                    function source(): Source {
                        throw new Exception();
                    }

                    source()->consumer($out_consumer);
                    $asserted_consumer = json_decode("");
                    source()->assertConsumer($asserted_consumer);
                    $checked_consumer = json_decode("");
                    if (!source()->isConsumer($checked_consumer)) {
                        throw new Exception();
                    }
                    $key_consumer = source()->keyConsumer();',
                'assertions' => [
                    '$out_consumer===' => 'Closure[impure](never):void',
                    '$asserted_consumer===' => 'Closure[impure](never):void',
                    '$checked_consumer===' => 'Closure[impure](never):void',
                    '$key_consumer===' => 'Closure[impure](never):void',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'starProjectionUnknownMixedHandedToACallbackIsMixed' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Collection {
                        /** @param callable(TValue|null): void $callback */
                        public function each(callable $callback): void {}
                    }

                    /** @return Collection<*> */
                    function collection(): Collection {
                        throw new Exception();
                    }

                    collection()->each(function ($value): void {
                        echo strlen($value);
                    });',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'starProjectionUnionReadsEveryKnownBound' => [
                'code' => '<?php
                    /** @template V */
                    final class Box {
                        /** @return V */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @template T of object */
                    interface Holder {
                        /** @return Box<list<T>> */
                        public function box(): Box;
                    }

                    /** @return Holder<*> */
                    function holder(): Holder {
                        throw new Exception();
                    }

                    /** @return Box<*> */
                    function anyBox(): Box {
                        throw new Exception();
                    }

                    /** @return Box<int> */
                    function intBox(): Box {
                        throw new Exception();
                    }

                    $objects = holder()->box()->get();
                    $narrow_first = (rand(0, 1) ? holder()->box() : anyBox())->get();
                    $wide_first = (rand(0, 1) ? anyBox() : holder()->box())->get();
                    $star_first = (rand(0, 1) ? holder()->box() : intBox())->get();
                    $concrete_first = (rand(0, 1) ? intBox() : holder()->box())->get();',
                'assertions' => [
                    '$objects===' => 'list<object>',
                    '$narrow_first===' => 'mixed',
                    '$wide_first===' => 'mixed',
                    '$star_first===' => 'int|list<object>',
                    '$concrete_first===' => 'int|list<object>',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedCallableListIsReadByPosition' => [
                'code' => '<?php
                    /** @template V */
                    class Base {
                        /** @return V */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<list<Closure(T): void>>
                     */
                    final class Callbacks extends Base {}

                    /** @return Callbacks<*> */
                    function callbacks(): Callbacks {
                        throw new Exception();
                    }

                    $callbacks = callbacks()->get();',
                'assertions' => [
                    '$callbacks===' => 'list<Closure[impure](never):void>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedObjectShapeIsReadByPosition' => [
                'code' => '<?php
                    /** @template V */
                    class Base {
                        /** @return V */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<object{callback: Closure(T): void}>
                     */
                    final class Callbacks extends Base {}

                    /** @return Callbacks<*> */
                    function callbacks(): Callbacks {
                        throw new Exception();
                    }

                    $callbacks = callbacks()->get();',
                'assertions' => [
                    '$callbacks===' => 'object{callback:Closure[impure](never):void}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedComposedPropertyIsReadLikeAMethod' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {}

                    /** @template T */
                    abstract class Base {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @return T */
                        public function get(): mixed {
                            return $this->value;
                        }
                    }

                    /**
                     * @template TModel of Model
                     * @extends Base<Box<TModel>>
                     */
                    final class Composed extends Base {}

                    /**
                     * @template TModel of Model
                     * @extends Base<list<TModel>>
                     */
                    final class Listed extends Base {}

                    /** @return Composed<*> */
                    function composed(): Composed {
                        throw new Exception();
                    }

                    /** @return Listed<*> */
                    function listed(): Listed {
                        throw new Exception();
                    }

                    $box_property = composed()->value;
                    $box_method = composed()->get();
                    $list_property = listed()->value;',
                'assertions' => [
                    '$box_property===' => 'Box<*>',
                    '$box_method===' => 'Box<*>',
                    '$list_property===' => 'list<Model>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionComposedCovariantAncestorFitsAnUnknownArgument' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Reader {
                        /** @return T */
                        public function read(): mixed;
                    }

                    /** @template T */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements Reader<Box<T>>
                     */
                    final class BoxReader implements Reader {
                        /** @return Box<T> */
                        public function read(): Box {
                            throw new Exception();
                        }
                    }

                    /** @param Reader<Box<*>> $reader */
                    function takesAnyBoxReader(Reader $reader): void {}

                    /** @param Reader<object> $reader */
                    function takesObjectReader(Reader $reader): void {}

                    /** @param BoxReader<*> $reader */
                    function f(BoxReader $reader): void {
                        takesAnyBoxReader($reader);
                        takesObjectReader($reader);
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
            'starProjectionNestedIntersectionWithACallbackKeepsItsReadableStructure' => [
                'code' => '<?php
                    /** @template T of object */
                    abstract class Box {
                        /** @return T */
                        abstract public function get(): object;
                    }

                    /** @template T of object */
                    abstract class Source {
                        /** @return Box<Countable&Box<Closure(T): void>> */
                        abstract public function boxes(): Box;
                    }

                    /** @param Source<*> $source */
                    function read(Source $source): int {
                        return count($source->boxes()->get());
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallbackGivenABoxOfACallableReadsTheCallable' => [
                'code' => '<?php
                    /** @template T of object */
                    abstract class Box {
                        /** @return T */
                        abstract public function get(): object;
                    }

                    /** @template T of object */
                    abstract class Source {
                        /** @param Closure(Box<Closure(T): void>): void $callback */
                        abstract public function each(Closure $callback): void;
                    }

                    function takesClosure(Closure $callback): void {}

                    /** @param Source<*> $source */
                    function read(Source $source): void {
                        $source->each(function (Box $box): void {
                            takesClosure($box->get());
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedClassStringReadsTheNarrowerBound' => [
                'code' => '<?php
                    abstract class Model {}
                    final class User extends Model {}

                    /** @template T of Model */
                    abstract class Base {
                        /** @return class-string<T> */
                        abstract public function className(): string;

                        /** @param Closure(class-string<T>): void $callback */
                        abstract public function eachClass(Closure $callback): void;
                    }

                    /**
                     * @template T of User
                     * @extends Base<T>
                     */
                    abstract class Child extends Base {}

                    /** @param class-string<User> $class */
                    function takesUserClass(string $class): void {}

                    /** @param Child<*> $child */
                    function f(Child $child): void {
                        takesUserClass($child->className());
                        $child->eachClass(function ($class): void {
                            takesUserClass($class);
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionKeyOfInACallbackOfAGenericMethodIsRead' => [
                'code' => '<?php
                    /** @template TArr of array<string, int> */
                    final class Holder {
                        /**
                         * @template R
                         * @param Closure(key-of<TArr>): R $cb
                         * @return list<R>
                         */
                        public function mapKeys(Closure $cb): array {
                            return [];
                        }
                    }

                    /** @return Holder<*> */
                    function holder(): Holder {
                        return new Holder();
                    }

                    $keys = holder()->mapKeys(fn($k) => $k);',
                'assertions' => [
                    '$keys===' => 'list<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallbackReadsKeyOfAndValueOfTheBound' => [
                'code' => '<?php
                    /** @template TArr of array<string, int> */
                    final class Holder {
                        /** @param Closure(key-of<TArr>, value-of<TArr>): void $cb */
                        public function each(Closure $cb): void {}
                    }

                    function takesString(string $s): void {}

                    function takesInt(int $i): void {}

                    /** @param array<string, int> $a */
                    function takesArray(array $a): void {}

                    /** @param Holder<*> $h */
                    function f(Holder $h): void {
                        $h->each(function (string $k, int $v): void {
                            echo $k . "x", $v + 1;
                            takesArray([$k => $v]);
                        });
                        $h->each(function ($k, $v): void {
                            takesString($k);
                            takesInt($v + 1);
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallbackReadsPropertiesOfAndAnIndexedAccessOfTheBound' => [
                'code' => '<?php
                    final class Model {
                        public int $id = 0;
                    }

                    /** @template TModel of Model */
                    final class MHolder {
                        /** @param Closure(properties-of<TModel>): void $cb */
                        public function withProps(Closure $cb): void {}
                    }

                    /**
                     * @template TArr of array{a: DateTime, b: stdClass}
                     * @template TKey of key-of<TArr>
                     */
                    final class Lookup {
                        /** @param Closure(TArr[TKey]): void $cb */
                        public function with(Closure $cb): void {}
                    }

                    /**
                     * @param MHolder<*> $m
                     * @param Lookup<*, "a"> $l
                     * @param Lookup<array{a: DateTime, b: stdClass}, *> $l2
                     */
                    function f(MHolder $m, Lookup $l, Lookup $l2): void {
                        $m->withProps(function (array $_p): void {});
                        $l->with(function (object $_v): void {});
                        $l2->with(function (object $_v): void {});
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionShapeSlotAcceptingNothingMakesTheShapeAcceptNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @return Box<*> */
                    function box(): Box {
                        throw new Exception();
                    }

                    $shape = box()->shape;
                    $consumer = box()->consumer();
                    box()->setOptional([]);
                    box()->setPossiblyEmptyList([]);',
                'assertions' => [
                    '$shape===' => 'array{value: object}',
                    '$consumer===' => 'Closure[impure](never):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionObjectShapeIsReadLikeAProperty' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Holder {
                        /** @param Closure(T): void $callback */
                        public function __construct(public Closure $callback) {}
                    }

                    /**
                     * @template X
                     * @param object{callback: X} $object
                     * @return X
                     */
                    function callbackOf(object $object): mixed {
                        return $object->callback;
                    }

                    /** @return Holder<*> */
                    function holder(): Holder {
                        throw new Exception();
                    }

                    $callback = callbackOf(holder());',
                'assertions' => [
                    '$callback===' => 'Closure[impure](never):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallbackReadsTheExpandedBound' => [
                'code' => '<?php
                    final class Consts {
                        public const MAP = ["a" => 1, "b" => 2];
                        public const STATE_ON = "on";
                        public const STATE_OFF = "off";
                    }

                    /** @template TState of Consts::STATE_* */
                    final class States {
                        /** @param Closure(TState): void $callback */
                        public function each(Closure $callback): void {}
                    }

                    /** @template TValue of value-of<Consts::MAP> */
                    final class Values {
                        /** @param Closure(TValue): void $callback */
                        public function each(Closure $callback): void {}
                    }

                    /**
                     * @param States<*> $states
                     * @param Values<*> $values
                     */
                    function f(States $states, Values $values): void {
                        $states->each(function ($state): void {
                            /** @psalm-check-type-exact $state = "off"|"on" */
                        });
                        $states->each(function (string $_state): void {});
                        $values->each(function ($value): void {
                            /** @psalm-check-type-exact $value = 1|2 */
                        });
                        $values->each(function (int $_value): void {});
                    }',
                'assertions' => [],
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
            'starProjectionParamAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @param TSecond $second */
                        public function setSecond(object $second): void {}
                    }

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        $p->setSecond(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:39 - Argument 1 of Pair::setSecond expects never, but stdClass provided',
            ],
            'starProjectionInvariantArgumentParamAcceptsNoConcreteArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @param Box<TSecond> $box */
                        public function setBox(Box $box): void {}
                    }

                    /**
                     * @param Pair<int, *> $p
                     * @param Box<stdClass> $box
                     */
                    function f(Pair $p, Box $box): void {
                        $p->setBox($box);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:36 - Argument 1 of Pair::setBox expects Box<never>, but Box<stdClass> provided',
            ],
            'starProjectionInvariantArgumentParamAcceptsNoStarArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @param Box<TSecond> $box */
                        public function setBox(Box $box): void {}
                    }

                    /**
                     * @param Pair<int, *> $p
                     * @param Box<*> $box
                     */
                    function f(Pair $p, Box $box): void {
                        $p->setBox($box);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:36 - Argument 1 of Pair::setBox expects Box<never>, but Box<*> provided',
            ],
            'starProjectionInvariantArgumentPropertyAcceptsNoArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @var Box<TSecond>|null */
                        public $box;
                    }

                    /**
                     * @param Pair<int, *> $p
                     * @param Box<stdClass> $box
                     */
                    function f(Pair $p, Box $box): void {
                        $p->box = $box;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:35 - $p->box with declared type \'Box<never>|null\' cannot be assigned type \'Box<stdClass',
            ],
            'starProjectionPropertyAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @var TSecond|null */
                        public $second;
                    }

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        $p->second = new stdClass();
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:38 - $p->second with declared type \'null\' cannot be assigned type \'stdClass',
            ],
            'starProjectionReadCallableAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @return Closure(TSecond): void */
                        public function consumer(): Closure {
                            throw new Exception();
                        }
                    }

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        $consumer = $p->consumer();
                        $consumer(new stdClass());
                    }',
                'error_message' => 'expects never, but stdClass provided',
            ],
            'starProjectionParamOutCallableAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Source {
                        /**
                         * @param mixed $value
                         * @param-out Closure(T): void $value
                         */
                        public function consumer(&$value): void {
                            throw new Exception();
                        }
                    }

                    /** @param Source<*> $source */
                    function f(Source $source): void {
                        $source->consumer($consumer);
                        $consumer(new stdClass());
                    }',
                'error_message' => 'expects never, but stdClass provided',
            ],
            'starProjectionFirstClassCallableAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function set($value): void {}
                    }

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $set = $box->set(...);
                        $set(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:30 - Argument 1 expects never, but stdClass provided',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'starProjectionInvokableClosureAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Consumer {
                        /** @param T $value */
                        public function __invoke(object $value): void {}
                    }

                    /** @param Consumer<*> $consumer */
                    function f(Consumer $consumer): void {
                        $closure = Closure::fromCallable($consumer);
                        $closure(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:34 - Argument 1 expects never, but stdClass provided',
            ],
            'starProjectionClassStringParamAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Factory {
                        /** @param class-string<T> $class */
                        public function setClass(string $class): void {}
                    }

                    /** @param Factory<*> $factory */
                    function f(Factory $factory): void {
                        $factory->setClass(stdClass::class);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:44 - Argument 1 of Factory::setClass expects never, but stdClass::class provided',
            ],
            'starProjectionClassStringPropertyAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Factory {
                        /** @var class-string<T>|null */
                        public $class;
                    }

                    /** @param Factory<*> $factory */
                    function f(Factory $factory): void {
                        $factory->class = stdClass::class;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:43 - $factory->class with declared type \'null\' cannot be assigned type \'stdClass::class',
            ],
            'starProjectionInsideAnInvariantArgumentAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function set($value): void {}
                    }

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @return Box<list<TSecond>> */
                        public function listBox(): Box {
                            throw new Exception();
                        }
                    }

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        $p->listBox()->set([new stdClass()]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:44 - Argument 1 of Box::set expects never, but list{stdClass} provided',
            ],
            'starProjectionInsideANestedInvariantArgumentAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @template T of object */
                    final class Source {
                        /** @return Box<Box<T>> */
                        public function boxes(): Box {
                            throw new Exception();
                        }
                    }

                    /** @param Source<*> $s */
                    function f(Source $s): void {
                        $s->boxes()->set(new Box(new stdClass()));
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:42 - Argument 1 of Box::set expects never',
                'php_version' => '8.0',
            ],
            'starProjectionInsideANestedInvariantPropertyAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @template T of object */
                    final class Source {
                        /** @param Box<Box<T>> $nested */
                        public function __construct(public Box $nested) {}
                    }

                    /** @param Source<*> $s */
                    function f(Source $s): void {
                        $s->nested->set(new Box(new stdClass()));
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:41 - Argument 1 of Box::set expects never',
                'php_version' => '8.0',
            ],
            'starProjectionInsideANestedInvariantArgumentOfACallbackAcceptsNothing' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @template T of Model */
                    abstract class Source {
                        /** @param Closure(Box<Box<T>>): void $callback */
                        abstract public function visit(Closure $callback): void;
                    }

                    /** @param Source<*> $source */
                    function f(Source $source): void {
                        $source->visit(function ($outer): void {
                            $outer->set(new Box(new stdClass()));
                        });
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:22:41 - Argument 1 of Box::set expects never',
                'php_version' => '8.0',
            ],
            'starProjectionNextToConcreteArgumentsCallbackOfAGenericMethodAcceptsNothing' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /**
                     * @template TUnknown of Model
                     * @template TKnown
                     */
                    abstract class Source {
                        /**
                         * @template TResult
                         * @param TResult $seed
                         * @param Closure(Box<array{unknown: TUnknown, known: TKnown, seed: TResult}>): TResult $callback
                         * @return TResult
                         */
                        abstract public function map(mixed $seed, Closure $callback): mixed;
                    }

                    /** @param Source<*, int> $source */
                    function f(Source $source): void {
                        $source->map(1, function ($box): int {
                            $box->set(["unknown" => new stdClass(), "known" => 1, "seed" => 1]);
                            return 1;
                        });
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:27:39 - Argument 1 of Box::set expects never',
                'php_version' => '8.0',
            ],
            'starProjectionInheritedIntersectionAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    interface Holder {
                        /** @param T $value */
                        public function put(mixed $value): void;
                    }

                    /** @template T */
                    interface Box {}

                    /** @template V */
                    class Base {
                        /** @return V */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<Box<T>&Holder<T>>
                     */
                    final class Inter extends Base {}

                    /** @param Inter<*> $in */
                    function f(Inter $in): void {
                        $in->get()->put(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:27:41 - Argument 1 of Holder::put expects never',
                'php_version' => '8.0',
            ],
            'starProjectionInheritedNestedInvariantArgumentAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}

                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @template V */
                    class Base {
                        /** @return V */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @extends Base<Box<Box<Closure(T): void>>>
                     */
                    final class Callbacks extends Base {}

                    /** @param Callbacks<*> $c */
                    function f(Callbacks $c): void {
                        $c->get()->set(new Box(static function (stdClass $_value): void {}));
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:27:40 - Argument 1 of Box::set expects never',
                'php_version' => '8.0',
            ],
            'starProjectionComposedParentArgumentIsNotConcrete' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T */
                    abstract class Base {}

                    /**
                     * @template TModel of Model
                     * @extends Base<list<TModel>>
                     */
                    final class Listed extends Base {}

                    /** @param Base<list<Model>> $base */
                    function takesModels(Base $base): void {}

                    /** @param Listed<*> $listed */
                    function f(Listed $listed): void {
                        takesModels($listed);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:37 - Argument 1 of takesModels expects Base<list<Model>>, but Listed<*> provided',
            ],
            'starProjectionInheritedComposedArgumentAcceptsNothing' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @param T $value */
                        public function set($value): void {}
                    }

                    /**
                     * @template TChild of object
                     * @extends Box<list<TChild>>
                     */
                    final class ListBox extends Box {}

                    /** @param ListBox<*> $box */
                    function f(ListBox $box): void {
                        $box->set([new stdClass()]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:35 - Argument 1 of ListBox::set expects never, but list{stdClass} provided',
            ],
            'starProjectionNarrowedToAChildAcceptsNothing' => [
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

                    /** @param Child<object> $child */
                    function takesChildOfObject(Child $child): void {}

                    /** @param Pair<int, *> $p */
                    function f(Pair $p): void {
                        if ($p instanceof Child) {
                            takesChildOfObject($p);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:48 - Argument 1 of takesChildOfObject expects Child<object>, but Child<*> provided',
            ],
            'starProjectionOwnClassReceiverAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Node {
                        /** @var Node<*>|null */
                        public ?Node $next = null;

                        /** @param T $value */
                        public function __construct(public object $value) {}

                        /** @param T $value */
                        public function set(object $value): void {
                            $this->value = $value;
                        }

                        public function link(): void {
                            $this->set($this->value);
                            $this->next?->set($this->value);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:47 - Argument 1 of Node::set expects never, but T:Node as object provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOwnClassReceiverClassStringAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Node {
                        /** @var Node<*>|null */
                        public ?Node $next = null;

                        /** @param T $value */
                        public function __construct(public object $value) {}

                        /** @param class-string<T> $class */
                        public function setClass(string $class): void {}

                        public function link(): void {
                            $this->setClass($this->value::class);
                            $this->next?->setClass($this->value::class);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:52 - Argument 1 of Node::setClass expects never, but class-string<T:Node as object> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedComposedPropertyAcceptsNothing' => [
                'code' => '<?php
                    abstract class Model {}

                    final class User extends Model {}

                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @template T */
                    abstract class Base {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }

                    /**
                     * @template TModel of Model
                     * @extends Base<Box<TModel>>
                     */
                    final class Composed extends Base {}

                    /** @param Composed<*> $composed */
                    function f(Composed $composed): void {
                        $composed->value->set(new User());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:26:47 - Argument 1 of Box::set expects never, but User provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionComposedCovariantAncestorKeepsTheArgumentUnknown' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Reader {
                        /** @return T */
                        public function read(): mixed;
                    }

                    /** @template T */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements Reader<Box<T>>
                     */
                    final class BoxReader implements Reader {
                        /** @return Box<T> */
                        public function read(): Box {
                            throw new Exception();
                        }
                    }

                    /** @param Reader<Box<object>> $reader */
                    function takesObjectBoxReader(Reader $reader): void {}

                    /** @param BoxReader<*> $reader */
                    function f(BoxReader $reader): void {
                        takesObjectBoxReader($reader);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:27:46 - Argument 1 of takesObjectBoxReader expects Reader<Box<object>>, but BoxReader<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionComposedIterableAncestorKeepsTheArgumentUnknown' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements IteratorAggregate<int, Box<T>>
                     */
                    final class Boxes implements IteratorAggregate {
                        /** @return Iterator<int, Box<T>> */
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }
                    }

                    /** @param iterable<int, Box<object>> $boxes */
                    function takesObjectBoxes(iterable $boxes): void {}

                    /** @param Boxes<*> $boxes */
                    function f(Boxes $boxes): void {
                        takesObjectBoxes($boxes);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:42 - Argument 1 of takesObjectBoxes expects iterable<int, Box<object>>, but Boxes<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionConditionalReturnAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param T $value */
                        public function set(object $value): void {}
                    }

                    /** @template T of object */
                    final class Source {
                        /**
                         * @template TArg
                         * @param TArg $arg
                         * @return (TArg is int ? Box<T> : Closure(T): void)
                         */
                        public function get(mixed $arg): mixed {
                            throw new Exception();
                        }
                    }

                    /** @param Source<*> $source */
                    function f(Source $source): void {
                        $source->get(1)->set(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:22:46 - Argument 1 of Box::set expects never, but stdClass provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionSelfOutKeepsTheReceiverUnknown' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Source {
                        /** @psalm-self-out Source<T> */
                        public function reset(): void {}

                        /** @param T $value */
                        public function set(object $value): void {}
                    }

                    /** @param Source<*> $source */
                    function f(Source $source): void {
                        $source->reset();
                        $source->set(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:38 - Argument 1 of Source::set expects never, but stdClass provided',
            ],
            'starProjectionCallbackNarrowerThanTheBoundIsCoerced' => [
                'code' => '<?php
                    /** @template T */
                    interface Mapped {
                        /** @param callable(T): void $mapper */
                        public function mapIn(callable $mapper): void;
                    }

                    /** @param Mapped<*> $mapped */
                    function f(Mapped $mapped): void {
                        $mapped->mapIn(fn(int $value) => null);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:34 - Type mixed should be a subtype of int',
            ],
            'starProjectionPhpstanBoxPackAcceptsNothing' => [
                'code' => '<?php
                    class A {}

                    /** @template T */
                    interface Box {
                        /** @param T $item */
                        public function pack(mixed $item): void;
                    }

                    /** @param Box<*> $box */
                    function testStar(Box $box, A $a): void {
                        $box->pack($a);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:36 - Argument 1 of Box::pack expects never, but A provided',
            ],
            'starProjectionPhpstanMappedOutAcceptsNothing' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends B {}

                    /** @template T */
                    interface Mapped {
                        /** @param callable(): T $mapper */
                        public function mapOut(callable $mapper): void;
                    }

                    /** @param Mapped<*> $mapped */
                    function testStarMapped(Mapped $mapped): void {
                        $mapped->mapOut(fn() => new A);
                        $mapped->mapOut(fn() => new B);
                        $mapped->mapOut(fn() => new C);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:41 - Argument 1 of Mapped::mapOut expects callable[impure]():never, but Closure[pure]():A provided',
            ],
            'starProjectionOtherInstanceKeyOfAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TArr of array<string, int>
                     * @template TKey of key-of<TArr>
                     * @template TModel of object
                     */
                    abstract class Node {
                        /** @param key-of<TArr> $key */
                        abstract public function setKey(mixed $key): void;

                        /**
                         * @param Node<*, *, *> $next
                         * @param key-of<TArr> $key
                         */
                        public function transfer(Node $next, mixed $key): void {
                            $next->setKey($key);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:43 - Argument 1 of Node::setKey expects never',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOtherInstanceValueOfAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TArr of array<string, int>
                     * @template TKey of key-of<TArr>
                     * @template TModel of object
                     */
                    abstract class Node {
                        /** @param value-of<TArr> $value */
                        abstract public function setValue(mixed $value): void;

                        /**
                         * @param Node<*, *, *> $next
                         * @param value-of<TArr> $value
                         */
                        public function transfer(Node $next, mixed $value): void {
                            $next->setValue($value);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:45 - Argument 1 of Node::setValue expects never',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOtherInstanceIndexedAccessAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TArr of array<string, int>
                     * @template TKey of key-of<TArr>
                     * @template TModel of object
                     */
                    abstract class Node {
                        /** @param TArr[TKey] $value */
                        abstract public function setIndexed(mixed $value): void;

                        /**
                         * @param Node<*, *, *> $next
                         * @param TArr[TKey] $value
                         */
                        public function transfer(Node $next, mixed $value): void {
                            $next->setIndexed($value);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:47 - Argument 1 of Node::setIndexed expects never',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOtherInstancePropertiesOfAcceptsNothing' => [
                'code' => '<?php
                    /**
                     * @template TArr of array<string, int>
                     * @template TKey of key-of<TArr>
                     * @template TModel of object
                     */
                    abstract class Node {
                        /** @param properties-of<TModel> $properties */
                        abstract public function setProperties(mixed $properties): void;

                        /**
                         * @param Node<*, *, *> $next
                         * @param properties-of<TModel> $properties
                         */
                        public function transfer(Node $next, mixed $properties): void {
                            $next->setProperties($properties);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:50 - Argument 1 of Node::setProperties expects never',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNarrowedByInstanceofAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param T $value */
                        public function set(object $value): void {}
                    }

                    /** @param Box<*>|stdClass $any */
                    function f(object $any): void {
                        if ($any instanceof Box) {
                            $any->set(new stdClass());
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:39 - Argument 1 of Box::set expects never, but stdClass provided',
            ],
            'starProjectionShapeParamAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->setShape([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:40 - Argument 1 of Box::setShape expects never, but array<never, never> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionSealedListParamAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->setList([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:39 - Argument 1 of Box::setList expects never, but array<never, never> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNonEmptyListParamAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->setNonEmptyList([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:47 - Argument 1 of Box::setNonEmptyList expects never, but array<never, never> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionShapePropertyAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->shape = [];
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:39 - $box->shape with declared type \'never\' cannot be assigned type \'array',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNonEmptyListReturnedByACallbackAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->setFactory(fn(): array => []);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:42 - Argument 1 of Box::setFactory expects Closure[impure]():never, but Closure[pure]():array<never, never> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionShapeHandedToACallableAcceptsNothing' => [
                'code' => self::SHAPE_BOX . '
                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->consumer()([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:42 - Argument 1 expects never, but array<never, never> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionObjectShapeOfAClassStringMapOfCallablesAcceptsNothing' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T of object */
                    final class Holder {
                        /** @var class-string-map<K of Model, Closure(T): K> */
                        public array $map = [];
                    }

                    /** @param object{map: class-string-map<K of Model, Closure(stdClass): K>} $object */
                    function call(object $object): void {}

                    /** @param Holder<*> $holder */
                    function f(Holder $holder): void {
                        call($holder);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:30 - Argument 1 of call expects object{map:class-string-map<K as Model, Closure[impure](stdClass):K:class-string-map as Model>}, but parent type Holder<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionObjectShapeOfACallableAcceptsNothing' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Holder {
                        /** @param Closure(T): void $callback */
                        public function __construct(public Closure $callback) {}
                    }

                    /** @param object{callback: Closure(stdClass): void} $object */
                    function call(object $object): void {
                        ($object->callback)(new stdClass());
                    }

                    /** @param Holder<*> $holder */
                    function f(Holder $holder): void {
                        call($holder);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:30 - Argument 1 of call expects object{callback:Closure[impure](stdClass):void}, but parent type Holder<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    private const SHAPE_BOX = '<?php
                    /** @template T of object */
                    abstract class Box {
                        /** @var array{value: T} */
                        public array $shape;

                        /** @param array{value: T} $shape */
                        abstract public function setShape(array $shape): void;

                        /** @param array{value?: T} $shape */
                        abstract public function setOptional(array $shape): void;

                        /** @param list{T} $list */
                        abstract public function setList(array $list): void;

                        /** @param non-empty-list<T> $list */
                        abstract public function setNonEmptyList(array $list): void;

                        /** @param list<T> $list */
                        abstract public function setPossiblyEmptyList(array $list): void;

                        /** @param Closure(): non-empty-list<T> $factory */
                        abstract public function setFactory(Closure $factory): void;

                        /** @return Closure(array{value: T}): void */
                        abstract public function consumer(): Closure;
                    }
';

    private const TEMPLATE_ATOMIC_CODE = '<?php
        final class Model {
            public int $id = 0;
        }

        /** @template T */
        final class Box {}

        /**
         * @template T of object
         * @template TArr of array{a: DateTime, b: Model}
         * @template TKey of key-of<TArr>
         * @template TModel of Model
         */
        abstract class Holder {
            /** @return %1$s */
            abstract public function read(): mixed;

            /** @param %1$s $value */
            abstract public function write(mixed $value): void;

            /** @return Box<%1$s> */
            abstract public function box(): Box;

            /**
             * @template R
             * @param Closure(%1$s): R $callback
             * @return R
             */
            abstract public function map(Closure $callback): mixed;
        }

        /** @return Holder<*, *, *, *> */
        function holder(): Holder {
            throw new Exception();
        }

        %2$s';

    /**
     * Each template atomic of a `*` receiver's member where a value is handed out (`read`: the return type,
     * `callback`: what a callback is given), where one is passed in (`write`: a parameter) and as an invariant
     * argument (`nested`: a `Box` of it returned). Every template atomic reads the unknown type alike: through what
     * is known of it where it is handed out, as nothing where it is passed in, and `*` when nested. An expectation
     * ending in ` provided` is the issue reported.
     *
     * @return array<string, array{class-string<Atomic>, string, string}>
     * @psalm-pure
     */
    public static function providerTemplateAtomicByPosition(): array
    {
        $rows = [
            [TTemplateParam::class, 'T', 'new stdClass()', 'object', 'stdClass'],
            [TTemplateParamClass::class, 'class-string<T>', 'stdClass::class', 'class-string', 'stdClass::class'],
            [TTemplateKeyOf::class, 'key-of<TArr>', '"a"', "'a'|'b'", "'a'"],
            [TTemplateValueOf::class, 'value-of<TArr>', 'new DateTime()', 'DateTime|Model', 'DateTime'],
            [TTemplatePropertiesOf::class, 'properties-of<TModel>', '["id" => 1]', 'array{id: int}', 'array{id: 1}'],
            [TTemplateIndexedAccess::class, 'TArr[TKey]', 'new DateTime()', 'DateTime|Model', 'DateTime'],
            [TConditional::class, '(T is DateTime ? int : string)', '1', 'int|string', '1'],
        ];

        $cases = [];

        foreach ($rows as [$atomic_class, $type, $written, $read, $written_type]) {
            $positions = [
                'read' => ['$result = holder()->read();', $read],
                'callback' => ['$result = holder()->map(fn($value) => $value);', $read],
                'write' => [
                    'holder()->write(' . $written . ');',
                    'Argument 1 of Holder::write expects never, but ' . $written_type . ' provided',
                ],
                'nested' => ['$result = holder()->box();', 'Box<*>'],
            ];

            foreach ($positions as $position => [$code, $expected]) {
                $cases[$type . ' ' . $position] = [
                    $atomic_class,
                    sprintf(self::TEMPLATE_ATOMIC_CODE, $type, $code),
                    $expected,
                ];
            }
        }

        return $cases;
    }

    /**
     * @param class-string<Atomic> $_atomic_class
     */
    #[DataProvider('providerTemplateAtomicByPosition')]
    public function testTemplateAtomicByPosition(string $_atomic_class, string $code, string $expected): void
    {
        $this->project_analyzer->setPhpVersion('8.0', 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);

        if (str_ends_with($expected, ' provided')) {
            $this->expectException(CodeException::class);
            $this->expectExceptionMessage($expected);
        }

        $context = new Context();
        $this->analyzeFile($file_path, $context);

        $this->assertSame($expected, $context->vars_in_scope['$result']->getId(true));
    }

    /**
     * A template atomic (an atomic type with a defining class) missing from StarProjection::TEMPLATE_ATOMICS has no
     * rule for `*`; one missing from the table above has none checked
     */
    public function testEveryTemplateAtomicIsEnumerated(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/src/Psalm/Type/Atomic/*.php');
        $this->assertNotFalse($files);
        $template_atomics = [];

        foreach ($files as $file) {
            $class = 'Psalm\\Type\\Atomic\\' . basename($file, '.php');

            if (property_exists($class, 'defining_class')) {
                $template_atomics[] = $class;
            }
        }

        $tabled = array_values(array_unique(array_column(self::providerTemplateAtomicByPosition(), 0)));
        $enumerated = StarProjection::TEMPLATE_ATOMICS;

        sort($template_atomics);
        sort($tabled);
        sort($enumerated);

        $this->assertSame($template_atomics, $enumerated);
        $this->assertSame($template_atomics, $tabled);
    }
}
