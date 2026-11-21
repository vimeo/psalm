<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_column;

use const DIRECTORY_SEPARATOR;

final class StarProjectionTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testStarProjectionReportsAClosureOfTheWrongTypeOnce(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @template T of Countable */
                final class Box {
                    /** @param Closure(T): void $f */
                    public function each(Closure $f): void {}
                }

                /** @param Box<*> $box */
                function f(Box $box): void {
                    $box->each(function (int $x): void {
                        echo $x;
                    });
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            ['InvalidArgument'],
            array_column(IssueBuffer::getIssuesDataForFile($file_path), 'type'),
        );
    }

    public function testStarProjectionAddsNoLowerBoundToAnInvariantTemplate(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @template T of object */
                final class Box {}

                /**
                 * @template T of object
                 * @param Box<T> $first
                 * @param Box<T> $second
                 * @return T
                 */
                function both(Box $first, Box $second): object {
                    throw new Exception();
                }

                /**
                 * @param Box<*> $any
                 * @param Box<DateTime> $date
                 */
                function f(Box $any, Box $date): void {
                    $result = both($any, $date);
                    /** @psalm-check-type-exact $result = DateTime */
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            ['ArgumentTypeCoercion'],
            array_column(IssueBuffer::getIssuesDataForFile($file_path), 'type'),
        );
    }

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
            'starProjectionFitsAClassWithoutArgumentsInAUnion' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B of object
                     */
                    final class Pair {}

                    function nullable(?Pair $p): void {}

                    function nativeUnion(Pair|int $p): void {}

                    /** @param list<Pair|null> $l */
                    function nestedNullable(array $l): void {}

                    function optional(Pair $p = null): void {}

                    function nullableGenerator(?Generator $g): void {}

                    /**
                     * @param Pair<int, *> $s
                     * @param Generator<*, *, *, *> $gen
                     */
                    function f(Pair $s, Generator $gen): void {
                        nullable($s);
                        nativeUnion($s);
                        nestedNullable([$s]);
                        optional($s);
                        nullableGenerator($gen);
                    }',
                'assertions' => [],
                'ignored_issues' => ['DeprecatedSyntax'],
                'php_version' => '8.0',
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
            'starProjectionComposedBoundFollowsTheOtherArguments' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B of list<A>
                     */
                    final class P {
                        /** @return B */
                        public function b(): array {
                            throw new Exception();
                        }
                    }

                    /** @return P<int, *> */
                    function p(): P {
                        throw new Exception();
                    }

                    $b = p()->b();',
                'assertions' => [
                    '$b===' => 'list<int>',
                ],
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
                // known gap: `@extends Base<list<TModel>>` is read as a `Base<list<Model>>`, which the write-restriction
                // PR restricts
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
            'starProjectionNarrowedToAChildKnowsTheChildsBound' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {
                        /** @return T */
                        public function get(): object {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of DateTime
                     * @extends Box<T>
                     */
                    final class DateBox extends Box {}

                    /** @return Box<*> */
                    function source(): Box {
                        throw new Exception();
                    }

                    $box = source();
                    $value = $box instanceof DateBox ? $box->get() : null;',
                'assertions' => [
                    '$value===' => 'DateTime|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNarrowedToAChildLeavesItsPurityTemplateToItsBound' => [
                'code' => '<?php
                    /** @template T */
                    class ParentBox {}

                    /**
                     * @template T
                     * @psalm-purity-template P
                     * @extends ParentBox<T>
                     */
                    final class ChildBox extends ParentBox {}

                    /** @return ParentBox<*> */
                    function source(): ParentBox {
                        throw new Exception();
                    }

                    $box = source();
                    $child = $box instanceof ChildBox ? $box : null;',
                'assertions' => [
                    '$child===' => 'ChildBox[impure]<*>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedIfTrueHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $r */
                    function takes(Reader $r): void {}

                    /** @psalm-assert-if-true Reader<*> $x */
                    function isReader(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isReader($a)) {
                            /** @psalm-check-type-exact $a = Reader<*> */
                            takes($a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedIfFalseHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $r */
                    function takes(Reader $r): void {}

                    /** @psalm-assert-if-false Reader<*> $x */
                    function isNotReader(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (!isNotReader($a)) {
                            /** @psalm-check-type-exact $a = Reader<*> */
                            takes($a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $r */
                    function takes(Reader $r): void {}

                    /** @psalm-assert Reader<*> $x */
                    function assertReader(mixed $x): void {}

                    function f(mixed $a): void {
                        assertReader($a);
                        /** @psalm-check-type-exact $a = Reader<*> */
                        takes($a);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedOnAPropertyHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $r */
                    function takes(Reader $r): void {}

                    final class Holder {
                        public mixed $prop = null;

                        /** @psalm-assert-if-true Reader<*> $this->prop */
                        public function hasReader(): bool {
                            return true;
                        }

                        /** @psalm-assert Reader<*> $this->prop */
                        public function assertReader(): void {}

                        public function ifTrue(): void {
                            if ($this->hasReader()) {
                                takes($this->prop);
                            }
                        }

                        public function asserted(): void {
                            $this->assertReader();
                            takes($this->prop);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionBindsACovariantTemplateAsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /**
                     * @template T of Countable
                     * @param Reader<T> $reader
                     * @return T
                     */
                    function readOf(Reader $reader): Countable {
                        throw new Exception();
                    }

                    /** @param Reader<*> $any */
                    function f(Reader $any): void {
                        $read = readOf($any);
                        /** @psalm-check-type-exact $read = Countable */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadKeepsTheStarOfAWholeGenericArgument' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {
                        /** @return Box<T> */
                        public function same(): Box {
                            return $this;
                        }

                        /** @return self<T> */
                        public function selfOf(): self {
                            return $this;
                        }

                        /** @return static<T> */
                        public function staticOf(): static {
                            return $this;
                        }

                        /** @return Other<T> */
                        public function other(): Other {
                            throw new Exception();
                        }

                        /** @return list<Box<T>> */
                        public function boxes(): array {
                            return [];
                        }

                        /** @return T */
                        public function get(): object {
                            throw new Exception();
                        }

                        /** @return list<T> */
                        public function all(): array {
                            return [];
                        }

                        /** @var Box<T>|null */
                        public ?Box $property = null;
                    }

                    /** @template U of object */
                    final class Other {}

                    /** @var Box<*> $b */
                    $b = new Box();
                    $same = $b->same();
                    $self = $b->selfOf();
                    $static = $b->staticOf();
                    $other = $b->other();
                    $boxes = $b->boxes();
                    $get = $b->get();
                    $all = $b->all();
                    $property = $b->property;',
                'assertions' => [
                    '$same===' => 'Box<*>',
                    '$self===' => 'Box<*>',
                    '$static===' => 'Box<*>&static',
                    '$other===' => 'Other<*>',
                    '$boxes===' => 'list<Box<*>>',
                    '$get===' => 'object',
                    '$all===' => 'list<object>',
                    '$property===' => 'Box<*>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionWritesAreNotRestrictedYet' => [
                // known gap: the write-restriction PR makes `set()` expect `never` and `sink()` a `Closure(never): void`
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param T $value */
                        public function set(object $value): void {}

                        /** @return Closure(T): void */
                        public function sink(): Closure {
                            throw new Exception();
                        }

                        /** @return Closure(T): void */
                        public function setter(): Closure {
                            throw new Exception();
                        }
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    $b->set(new stdClass());
                    $sink = $b->sink();
                    ($b->setter())(new DateTime());',
                'assertions' => [
                    '$sink===' => 'Closure[impure](object):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionFitsAParamWithoutArguments' => [
                // `Box<stdClass>` does not (see starProjectionBareParamRejectsAConcreteArgument): the bare class
                // taking any argument is a separate later change (see `Omitted`)
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    function takesBare(Box $b): void {}

                    /** @param Box<*> $b */
                    function f(Box $b): void {
                        takesBare($b);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNestedInAnArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get() {
                            throw new Exception();
                        }
                    }

                    /** @param Box<Box<*>> $b */
                    function takesNested(Box $b): void {}

                    /** @param Box<Box<*>> $b */
                    function f(Box $b): void {
                        takesNested($b);
                        $inner = $b->get();
                        /** @psalm-check-type-exact $inner = Box<*> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInParamOutSelfOutAndThisOut' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Cell {
                        /** @psalm-this-out Cell<*> */
                        public function wipeThis(): void {}

                        /** @psalm-self-out Cell<*> */
                        public function wipeSelf(): void {}
                    }

                    /** @param-out Cell<*> $out */
                    function fill(mixed &$out): void {
                        $out = new Cell();
                    }

                    fill($filled);
                    /** @var Cell<DateTime> $this_out */
                    $this_out = new Cell();
                    $this_out->wipeThis();
                    /** @var Cell<DateTime> $self_out */
                    $self_out = new Cell();
                    $self_out->wipeSelf();',
                'assertions' => [
                    '$filled===' => 'Cell<*>',
                    '$this_out===' => 'Cell<*>',
                    '$self_out===' => 'Cell<*>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCombinedWithAnArgumentKeepsBoth' => [
                // `Box<*>|Box<DateTime>` stays two members: the arguments print differently, so the types differ
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    function takesBare(Box $b): void {}

                    /** @var Box<*> $star */
                    $star = new Box();
                    /** @var Box $bare */
                    $bare = new Box();
                    /** @var Box<DateTime> $date */
                    $date = new Box();
                    $with_bare = rand(0, 1) ? $star : $bare;
                    $with_date = rand(0, 1) ? $star : $date;
                    $with_itself = rand(0, 1) ? $star : $star;
                    takesBare($with_bare);',
                'assertions' => [
                    '$with_bare===' => 'Box|Box<*>',
                    '$with_date===' => 'Box<*>|Box<DateTime>',
                    '$with_itself===' => 'Box<*>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadsOfAnObjectsMembersKeepTheStar' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @method Box<T> magic()
                     */
                    class Box {
                        /** @var Box<T> */
                        public $prop;

                        public function __construct() {
                            $this->prop = $this;
                        }

                        public function __call(string $name, array $args): mixed {
                            return null;
                        }
                    }

                    /** @return properties-of<Box<*>> */
                    function props() {
                        throw new Exception();
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    $vars = get_object_vars($b);
                    $object_var = $vars["prop"];
                    $properties = props();
                    $properties_of = $properties["prop"];
                    $magic = $b->magic();
                    $nested = $b->prop->prop;',
                'assertions' => [
                    '$object_var===' => 'Box<*>',
                    '$properties_of===' => 'Box<*>',
                    '$magic===' => 'Box<*>',
                    '$nested===' => 'Box<*>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInAClosureParamOfAReadTypeIsTheBound' => [
                // known gap: a callable parameter is a write, which the write-restriction PR restricts to `never`
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Closure(Closure(): Box<T>): void */
                        public function deep(): Closure {
                            throw new Exception();
                        }
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    $deep = $b->deep();',
                'assertions' => [
                    '$deep===' => 'Closure[impure](Closure[impure]():Box<object>):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionParamOutIsNotAStarYet' => [
                // known gap: the out type is read with the arguments, before the receiver's `*` is marked, so it is
                // not a `Box<*>` yet (the write-restriction PR flips it)
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param-out Box<T> $out */
                        public function fill(mixed &$out): void {
                            $out = $this;
                        }
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    $b->fill($out);',
                'assertions' => [
                    '$out===' => 'Box<object>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNestedReadIsWrittenToAsTheBoundYet' => [
                // known gap: `set()` on the `Box<*>` of `nested()` expects `never` once the write-restriction PR lands
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<Box<T>> */
                        public function nested(): Box {
                            throw new Exception();
                        }

                        /** @param T $value */
                        public function set(object $value): void {}
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    /** @var Box<stdClass> $box_of_std_class */
                    $box_of_std_class = new Box();
                    $b->nested()->set($box_of_std_class);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadHoldsTheBoundOfTheClassItIsReadInto' => [
                'code' => '<?php
                    /** @template T */
                    final class Free {
                        /** @return T */
                        public function get() {
                            throw new Exception();
                        }
                    }

                    /** @template T of object */
                    final class Box {
                        /** @var Free<T> */
                        public $prop;

                        public function __construct() {
                            $this->prop = new Free();
                        }

                        /** @return Free<T> */
                        public function free(): Free {
                            return $this->prop;
                        }
                    }

                    /** @var Box<*> $b */
                    $b = new Box();
                    $from_method = $b->free()->get();
                    $from_property = $b->prop->get();
                    $either = rand(0, 1) ? $b->free() : $b->prop;
                    $from_either = $either->get();',
                'assertions' => [
                    '$from_method===' => 'mixed',
                    '$from_property===' => 'mixed',
                    '$from_either===' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment', 'MixedPropertyTypeCoercion'],
                'php_version' => '8.0',
            ],
            'starProjectionOfAConditionalAssertionIsIteratedWithItsBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue of object
                     * @implements IteratorAggregate<TKey, TValue>
                     */
                    final class Collection implements IteratorAggregate {
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }
                    }

                    /** @psalm-assert-if-true Collection<*, *> $x */
                    function isCollection(mixed $x): bool {
                        return true;
                    }

                    /** @psalm-assert-if-false Collection<*, *> $x */
                    function isNotCollection(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a, mixed $b): void {
                        if (isCollection($a)) {
                            [$first] = [...$a];
                            /** @psalm-check-type-exact $first = object */
                        }

                        if (!isNotCollection($b)) {
                            [$second] = [...$b];
                            /** @psalm-check-type-exact $second = object */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'starProjectionReadThroughATemplatedMixinKeepsTheStar' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template T of object */
                    final class Box {}

                    /** @template TModel of Model */
                    final class Builder {
                        /** @return TModel */
                        public function first(): Model {
                            throw new Exception();
                        }

                        /** @return Builder<TModel> */
                        public function self(): Builder {
                            return $this;
                        }

                        /** @return Box<TModel> */
                        public function boxed(): Box {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template TModel of Model
                     * @mixin Builder<TModel>
                     */
                    final class Relation {}

                    /** @var Relation<*> $relation */
                    $relation = new Relation();
                    $first = $relation->first();
                    $self = $relation->self();
                    $boxed = $relation->boxed();',
                'assertions' => [
                    '$first===' => 'Model',
                    '$self===' => 'Builder<*>',
                    '$boxed===' => 'Box<*>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionReadThroughOffsetGetKeepsTheStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements ArrayAccess<int, Box<T>>
                     */
                    final class Offsets implements ArrayAccess {
                        public function offsetExists(mixed $offset): bool {
                            return true;
                        }

                        /** @return Box<T> */
                        public function offsetGet(mixed $offset): mixed {
                            throw new Exception();
                        }

                        public function offsetSet(mixed $offset, mixed $value): void {}

                        public function offsetUnset(mixed $offset): void {}
                    }

                    /** @var Offsets<*> $offsets */
                    $offsets = new Offsets();
                    $box = $offsets[0];',
                'assertions' => [
                    '$box===' => 'Box<*>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionIterationOfAWrappedArgumentIsAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements IteratorAggregate<int, Box<T>>
                     */
                    final class Boxes implements IteratorAggregate {
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }
                    }

                    /** @var Boxes<*> $boxes */
                    $boxes = new Boxes();
                    $item = null;

                    foreach ($boxes as $item) {
                    }',
                'assertions' => [
                    '$item===' => 'Box<*>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInheritedThroughAWrappedImplementsKeepsTheStar' => [
                'code' => '<?php
                    /** @template T of object */
                    interface Getter {
                        /** @return T */
                        public function get(): object;
                    }

                    /**
                     * @template T of object
                     * @implements Getter<Box<T>>
                     */
                    final class Box implements Getter {
                        public function get(): object {
                            return $this;
                        }
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<stdClass> $concrete
                     */
                    function f(Box $any, Box $concrete): void {
                        $inherited = $any->get();
                        /** @psalm-check-type-exact $inherited = Box<*> */
                        $known = $concrete->get();
                        /** @psalm-check-type-exact $known = Box<stdClass> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionSpreadOfAWrappedArgumentIsAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /**
                     * @template T of object
                     * @implements IteratorAggregate<int, Box<T>>
                     */
                    final class Boxes implements IteratorAggregate {
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }
                    }

                    /** @param Boxes<*> $boxes */
                    function f(Boxes $boxes): void {
                        $all = [...$boxes];
                        /** @psalm-check-type-exact $all = list<Box<*>> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOfAnInterfaceMethodTagKeepsTheStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /**
                     * @template T of object
                     * @method Box<T> boxed()
                     */
                    interface Shelf {}

                    /** @param Shelf<*> $shelf */
                    function f(Shelf $shelf): void {
                        $boxed = $shelf->boxed();
                        /** @psalm-check-type-exact $boxed = Box<*> */
                        $factory = $shelf->boxed(...);
                        /** @psalm-check-type-exact $factory = Closure():Box<*> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOfAStaticCallThroughTheInstanceKeepsTheStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<T> */
                        public static function make(): Box {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template T of object
                     * @method static Box<T> magic()
                     */
                    class Shelf {
                        public static function __callStatic(string $name, array $args): mixed {
                            return null;
                        }
                    }

                    /**
                     * @param Box<*> $any
                     * @param Shelf<*> $shelf
                     */
                    function f(Box $any, Shelf $shelf): void {
                        $made = $any::make();
                        /** @psalm-check-type-exact $made = Box<*> */
                        $factory = $any::make(...);
                        /** @psalm-check-type-exact $factory = Closure():Box<*> */
                        $magic = $shelf::magic();
                        /** @psalm-check-type-exact $magic = Box<*> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOfABoundNamingItsOwnClassIsExpandedOnce' => [
                'code' => '<?php
                    /** @template T of Node<*> */
                    class Node {
                        /** @return T */
                        public function child() {
                            throw new Exception();
                        }
                    }

                    /** @param Node<*> $node */
                    function f(Node $node): void {
                        $child = $node->child();
                        /** @psalm-check-type-exact $child = Node<*> */
                        $grandchild = $child->child();
                        /** @psalm-check-type-exact $grandchild = Node<*> */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedIntersectionKeepsTheStar' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {}

                    /** @psalm-assert Box&Countable $x */
                    function assertCountableBox(mixed $x): void {}

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        assertCountableBox($box);
                        /** @psalm-check-type-exact $box = Box<*>&Countable */
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNarrowedToAGrandchildKeepsTheStarAndAppliesTheChildsBound' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {}

                    /**
                     * @template U of object
                     * @extends Box<U>
                     */
                    class Middle extends Box {}

                    /**
                     * @template V of DateTimeInterface
                     * @extends Middle<V>
                     */
                    final class Child extends Middle {
                        /** @return V */
                        public function get() {
                            throw new Exception();
                        }
                    }

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        if ($box instanceof Child) {
                            /** @psalm-check-type-exact $box = Child<*> */
                            $date = $box->get();
                            /** @psalm-check-type-exact $date = DateTimeInterface */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionNestedInAnAssertedListHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    final class Reader {}

                    /** @param list<Reader<Countable>> $list */
                    function takes(array $list): void {}

                    /** @psalm-assert-if-true list<Reader<*>> $x */
                    function isReaderList(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isReaderList($a)) {
                            takes($a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInANamespaceResolvesTheClassName' => [
                'code' => '<?php
                    namespace App\Models {
                        /**
                         * @template TFirst
                         * @template TSecond of object
                         */
                        final class Pair {
                            /** @return TSecond */
                            public function second(): object {
                                throw new \Exception();
                            }
                        }
                    }

                    namespace App {
                        use App\Models\Pair;
                        use App\Models\Pair as Couple;

                        /**
                         * @param \App\Models\Pair<int, *> $qualified
                         * @param Pair<int, *> $imported
                         * @param Couple<int, *> $aliased
                         */
                        function f(Models\Pair $qualified, Pair $imported, Couple $aliased): void {
                            /** @psalm-check-type-exact $qualified = \App\Models\Pair<int, *> */
                            /** @psalm-check-type-exact $imported = \App\Models\Pair<int, *> */
                            /** @psalm-check-type-exact $aliased = \App\Models\Pair<int, *> */
                            $second = $imported->second();
                            /** @psalm-check-type-exact $second = object */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInAnAssertedClassStringHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $reader */
                    function takes(Reader $reader): void {}

                    /** @psalm-assert-if-true class-string<Reader<*>> $x */
                    function isReaderClass(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isReaderClass($a)) {
                            takes(new $a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInAnAssertedClassStringWhenFalseHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /** @param Reader<Countable> $reader */
                    function takes(Reader $reader): void {}

                    /** @psalm-assert-if-false class-string<Reader<*>> $x */
                    function isNotReaderClass(mixed $x): bool {
                        return false;
                    }

                    function f(mixed $a): void {
                        if (isNotReaderClass($a)) {
                            return;
                        }

                        takes(new $a);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'starProjectionInAnAssertedClosureParameterHoldsTheBound' => [
                'code' => '<?php
                    /** @template-covariant T of Countable */
                    interface Reader {}

                    /**
                     * @template T
                     * @param Closure(Reader<T>): void $closure
                     * @return T
                     */
                    function readClosure(Closure $closure): mixed {
                        throw new Exception();
                    }

                    /** @psalm-assert-if-true Closure(Reader<*>): void $x */
                    function isReaderClosure(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isReaderClosure($a)) {
                            $read = readClosure($a);
                            /** @psalm-check-type-exact $read = Countable */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidTemplateParam'],
                'php_version' => '8.0',
            ],
            'starProjectionCallableOfASetterIsNotRestrictedYet' => [
                // known gap: `Closure::fromCallable([$any, 'set'])` takes the argument of the setter as a `Box<*>`, which
                // accepts any `Box`, until the write-restriction PR makes it `never`
                'code' => '<?php
                    /** @template T of object */
                    interface Setter {
                        /** @param T $value */
                        public function set(object $value): void;
                    }

                    /**
                     * @template T of object
                     * @implements Setter<Box<T>>
                     */
                    final class Box implements Setter {
                        public function set(object $value): void {}
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        Closure::fromCallable([$any, "set"])($date);
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:39 - Argument 1 of takesConcrete expects Pair<int, stdClass>, but parent type Pair<int, *> provided',
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:36 - Argument 1 of takesBound expects Pair<int, object>, but parent type Pair<int, *> provided',
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:36 - Argument 1 of takesMixed expects Box<mixed>, but parent type Box<*> provided',
            ],
            'starProjectionKeepsTheBoundOfAMethodTemplate' => [
                'code' => '<?php
                    /** @template T of Countable */
                    final class Box {
                        /**
                         * @template U of T
                         * @param U $value
                         */
                        public function sub(object $value): void {}
                    }

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        $box->sub(new stdClass());
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:35 - Argument 1 of Box::sub expects Countable, but stdClass provided',
            ],
            'starProjectionOfAnUnboundedTemplateIsNotAMixedValue' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /** @param Box<int> $b */
                    function takesInt(Box $b): void {}

                    /** @param Box<*> $b */
                    function f(Box $b): void {
                        takesInt($b);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:34 - Argument 1 of takesInt expects Box<int>, but parent type Box<*> provided',
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:45 - Argument 1 of takesStdClassReader expects Reader<stdClass>, but parent type ObjectReader<*> provided',
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:40 - Argument 1 of takesTemplated expects Box<mixed>, but parent type Box<*> provided',
            ],
            'starProjectionDoesNotFitATemplateBoundByAnotherArgument' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /**
                     * @template T
                     * @param T $value
                     * @param Box<T> $box
                     */
                    function pair(mixed $value, Box $box): void {}

                    /** @param Box<*> $box */
                    function f(Box $box): void {
                        pair(1, $box);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:33 - Argument 2 of pair expects Box<mixed>, but parent type Box<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
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
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:40 - Argument 1 of takesTemplated expects Relation<mixed, *, *>, but parent type Relation<*, *, *> provided',
            ],
            'omittedArgumentStaysMixedThroughAFunctionTemplate' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template TValue
                     */
                    final class Page {}

                    /** @return Page */
                    function page(): Page {
                        return new Page();
                    }

                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    function same($value) {
                        return $value;
                    }

                    /** @return Page<int, stdClass> */
                    function f(): Page {
                        return same(page());
                    }',
                'error_message' => 'MixedReturnTypeCoercion',
            ],
            'starProjectionInExtends' => [
                'code' => '<?php
                    /** @template T */
                    class Box {}

                    /** @extends Box<*> */
                    final class StarBox extends Box {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The * type argument of Box<*> is not allowed in @template-extends',
            ],
            'starProjectionInImplements' => [
                'code' => '<?php
                    /** @template T */
                    interface Box {}

                    /** @implements Box<*> */
                    final class StarBox implements Box {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - The * type argument of Box<*> is not allowed in @template-implements',
            ],
            'starProjectionInUse' => [
                'code' => '<?php
                    /** @template T */
                    trait Box {}

                    final class StarBox {
                        /** @use Box<*> */
                        use Box;
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:25 - The * type argument of Box<*> is not allowed in @template-use',
            ],
            'starProjectionIsNoClassStringMapValue' => [
                'code' => '<?php
                    /** @param class-string-map<T, *> $map */
                    function f(array $map): void {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:32 - class-string-map cannot take a * value',
            ],
            'starProjectionReadOfAWholeGenericArgumentIsStillAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<T> */
                        public function same(): Box {
                            return $this;
                        }
                    }

                    /** @param Box<object> $b */
                    function takesBoxOfObject(Box $b): void {}

                    /** @param Box<*> $b */
                    function f(Box $b): void {
                        takesBoxOfObject($b->same());
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:42 - Argument 1 of takesBoxOfObject expects Box<object>, but parent type Box<*> provided',
            ],
            'starProjectionParamOfAReceiverKeepsTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param Box<T> $other */
                        public function merge(Box $other): void {}
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->merge($date);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:37 - Argument 1 of Box::merge expects Box<object>, but Box<DateTime> provided',
            ],
            'starProjectionBareParamRejectsAConcreteArgument' => [
                // a `*` and a bare `Box` both leave the argument unknown, a `Box<stdClass>` is not a bare `Box`: the
                // bare class taking any argument is a separate later change (see `Omitted`)
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    function takesBare(Box $b): void {}

                    /** @param Box<stdClass> $b */
                    function f(Box $b): void {
                        takesBare($b);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:35 - Argument 1 of takesBare expects Box<object>, but Box<stdClass> provided',
            ],
            'starProjectionNestedIsNotAnyNestedArgument' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /** @param Box<Box<*>> $b */
                    function takesNested(Box $b): void {}

                    /** @param Box<Box<DateTime>> $b */
                    function f(Box $b): void {
                        takesNested($b);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:37 - Argument 1 of takesNested expects Box<Box<*>>, but Box<Box<DateTime>> provided',
            ],
            'starProjectionOverridingReturnIsLessSpecific' => [
                'code' => '<?php
                    abstract class Model {}

                    /** @template TModel of Model */
                    final class Builder {}

                    /** @template TRelatedModel of Model */
                    abstract class Relation {
                        /** @return Builder<TRelatedModel> */
                        abstract public function builder(): Builder;
                    }

                    /**
                     * @template TRelatedModel of Model
                     * @extends Relation<TRelatedModel>
                     */
                    final class HasMany extends Relation {
                        /** @return Builder<*> */
                        public function builder(): Builder {
                            throw new Exception();
                        }
                    }',
                'error_message' => 'LessSpecificImplementedReturnType',
            ],
            'starProjectionPropertyAssignmentKeepsTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @var Box<T>|null */
                        public ?Box $prop = null;
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->prop = $date;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:38 - $any->prop with declared type \'Box<object>|null\' cannot be assigned type',
            ],
            'starProjectionListPropertyAppendKeepsTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @var list<Box<T>> */
                        public array $boxes = [];
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->boxes[] = $date;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:25 - $any->boxes with declared type \'list<Box<object>>\' cannot be assigned type',
            ],
            'starProjectionFirstClassCallableKeepsTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @param Box<T> $other */
                        public function merge(Box $other): void {}
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $merge = $any->merge(...);
                        $merge($date);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:32 - Argument 1 expects Box<object>, but Box<DateTime> provided',
            ],
            'starProjectionClosureParamKeepsTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Closure(Box<T>): void */
                        public function sinkBox(): Closure {
                            throw new Exception();
                        }
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->sinkBox()($date);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:41 - Argument 1 expects Box<object>, but Box<DateTime> provided',
            ],
            'starProjectionCombinedWithABareClassIsNotTheBound' => [
                // `Box<*>|Box<object>` stays two members: the arguments print differently, so the types differ
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /** @param Box<DateTime> $date */
                    function takesDate(Box $date): void {}

                    /**
                     * @param Box<*> $any
                     * @param Box $bare
                     */
                    function f(Box $any, Box $bare, bool $flag): void {
                        takesDate($flag ? $any : $bare);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:35 - Argument 1 of takesDate expects Box<DateTime>, but parent type Box<*>|Box<object> provided',
            ],
            'starProjectionFirstClassCallableReturnIsStillAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<T> */
                        public function same(): Box {
                            return $this;
                        }
                    }

                    /** @param Box<object> $b */
                    function takesBoxOfObject(Box $b): void {}

                    /** @param Box<*> $any */
                    function f(Box $any): void {
                        $same = $any->same(...);
                        takesBoxOfObject($same());
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:42 - Argument 1 of takesBoxOfObject expects Box<object>, but parent type Box<*> provided',
            ],
            'starProjectionReadThroughTwoLevelsOfInheritanceIsStillAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {}

                    /** @template T of object */
                    class ParentBox {
                        /** @return Box<T> */
                        public function value(): Box {
                            throw new Exception();
                        }
                    }

                    /**
                     * @template U of object
                     * @extends ParentBox<U>
                     */
                    class Middle extends ParentBox {}

                    /**
                     * @template V of object
                     * @extends Middle<V>
                     */
                    final class Child extends Middle {}

                    /** @param Box<object> $b */
                    function takes(Box $b): void {}

                    /** @param Child<*> $c */
                    function f(Child $c): void {
                        takes($c->value());
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:30:31 - Argument 1 of takes expects Box<object>, but parent type Box<*> provided',
            ],
            'starProjectionPropertyThroughTwoLevelsOfInheritanceIsStillAStar' => [
                'code' => '<?php
                    /** @template T of object */
                    class Box {}

                    /** @template T of object */
                    class ParentBox {
                        /** @var Box<T>|null */
                        public ?Box $prop = null;
                    }

                    /**
                     * @template U of object
                     * @extends ParentBox<U>
                     */
                    class Middle extends ParentBox {}

                    /**
                     * @template V of object
                     * @extends Middle<V>
                     */
                    final class Child extends Middle {}

                    /** @param Box<object> $b */
                    function takes(Box $b): void {}

                    /** @param Child<*> $c */
                    function f(Child $c): void {
                        if ($c->prop !== null) {
                            takes($c->prop);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:35 - Argument 1 of takes expects Box<object>, but parent type Box<*> provided',
            ],
            'starProjectionAssertedIfTrueOfAnInvariantClassIsStillAStar' => [
                'code' => '<?php
                    /** @template T of Countable */
                    final class Inv {}

                    /** @param Inv<Countable> $i */
                    function takes(Inv $i): void {}

                    /** @psalm-assert-if-true Inv<*> $x */
                    function isInv(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isInv($a)) {
                            takes($a);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:35 - Argument 1 of takes expects Inv<Countable>, but parent type Inv<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionAssertedIfFalseOfAnInvariantClassIsStillAStar' => [
                'code' => '<?php
                    /** @template T of Countable */
                    final class Inv {}

                    /** @param Inv<Countable> $i */
                    function takes(Inv $i): void {}

                    /** @psalm-assert-if-false Inv<*> $x */
                    function isNotInv(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (!isNotInv($a)) {
                            takes($a);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:35 - Argument 1 of takes expects Inv<Countable>, but parent type Inv<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOfAnExplicitAndABareClassIsNotTheBareClass' => [
                // `Box<object>|Box` leaves the argument out of only one of its members: it is not a bare `Box`
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /** @param Box<object>|Box $b */
                    function explicitThenBare(Box $b): void {}

                    /** @param Box<object> $b */
                    function explicitOnly(Box $b): void {}

                    /** @param Box<*> $star */
                    function f(Box $star): void {
                        explicitThenBare($star);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:42 - Argument 1 of explicitThenBare expects Box<object>, but parent type Box<*> provided',
            ],
            'starProjectionNestedReadIsAStarAtTheOutermostArgument' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<Box<T>> */
                        public function nested(): Box {
                            throw new Exception();
                        }
                    }

                    /** @param Box<Box<DateTime>> $b */
                    function takesNestedDates(Box $b): void {}

                    /** @param Box<*> $any */
                    function f(Box $any): void {
                        takesNestedDates($any->nested());
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:42 - Argument 1 of takesNestedDates expects Box<Box<DateTime>>, but parent type Box<*> provided',
            ],
            'starProjectionNestedInAnAssertedListIsStillAStar' => [
                'code' => '<?php
                    /** @template T of Countable */
                    final class Inv {}

                    /** @param list<Inv<Countable>> $list */
                    function takes(array $list): void {}

                    /** @psalm-assert-if-true list<Inv<*>> $x */
                    function isInvList(mixed $x): bool {
                        return true;
                    }

                    function f(mixed $a): void {
                        if (isInvList($a)) {
                            takes($a);
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:35 - Argument 1 of takes expects list<Inv<Countable>>, but parent type list<Inv<*>> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallableOfAMethodReturningTheTemplateIsNotTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {
                        /** @return Box<T> */
                        public function same(): Box {
                            return $this;
                        }
                    }

                    /** @param callable(): Box<*> $factory */
                    function takesStarFactory(callable $factory): void {}

                    /** @param callable(): Box<object> $factory */
                    function takesFactory(callable $factory): void {}

                    /** @param Box<*> $any */
                    function f(Box $any): void {
                        takesStarFactory([$any, "same"]);
                        takesStarFactory($any->same(...));
                        takesFactory([$any, "same"]);
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:38 - Argument 1 of takesFactory expects callable[impure]():Box<object>, but parent type callable[impure]():Box<*> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionOffsetSetOfAWrappedArgumentStillTakesTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    final class Box {}

                    /**
                     * @template T of object
                     * @extends ArrayAccess<int, Box<T>>
                     */
                    interface Shelf extends ArrayAccess {}

                    /**
                     * @param Shelf<*> $shelf
                     * @param Box<DateTime> $date
                     */
                    function f(Shelf $shelf, Box $date): void {
                        $shelf[0] = $date;
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:37 - Argument 2 of Shelf::offsetSet expects Box<object>, but Box<DateTime> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionFirstClassSetterOfAWrappedArgumentStillTakesTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    interface Setter {
                        /** @param T $value */
                        public function set(object $value): void;
                    }

                    /**
                     * @template T of object
                     * @implements Setter<Box<T>>
                     */
                    final class Box implements Setter {
                        public function set(object $value): void {}
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->set(...)($date);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:40 - Argument 1 expects Box<object>, but Box<DateTime> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'starProjectionCallableParameterOfAWrappedArgumentStillTakesTheBound' => [
                'code' => '<?php
                    /** @template T of object */
                    interface Sink {
                        /** @return Closure(T): void */
                        public function sink(): Closure;
                    }

                    /**
                     * @template T of object
                     * @implements Sink<Box<T>>
                     */
                    final class Box implements Sink {
                        public function sink(): Closure {
                            return static function (object $value): void {};
                        }
                    }

                    /**
                     * @param Box<*> $any
                     * @param Box<DateTime> $date
                     */
                    function f(Box $any, Box $date): void {
                        $any->sink()($date);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:38 - Argument 1 expects Box<object>, but Box<DateTime> provided',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
