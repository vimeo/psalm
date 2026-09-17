<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class TypeVariableTest extends TestCase
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
            'unboundConstructorTemplate' => [
                'code' => '<?php
                    /** @template T of int|string */
                    class Box {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    function good(): void {
                        $box = new Box();
                        $box->add(1);
                        $box->add("two");
                    }

                    /** @template T */
                    class Holder {
                        public function __construct() {}
                    }

                    function passesThrough(): Holder {
                        return new Holder();
                    }',
            ],
            'constructorBoundWidening' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    function good(): Box {
                        $box = new Box(1);
                        $box->set("two");
                        return $box;
                    }',
            ],
            'earlierInvariantArgumentPinStaysSilent' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }

                    /** @param Box<int> $box */
                    function takesIntBox(Box $box): int {
                        return $box->value;
                    }

                    function inspect(): void {
                        $box = new Box(1);
                        takesIntBox($box);
                    }',
            ],
            'boundViolationSuppressed' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    /** @psalm-suppress IncompatibleTypeParameters */
                    function probe(): void {
                        $box = new IntBox();
                        $box->add("nope");
                    }',
            ],
            'untypedClosureParamResolvesInferredArrayContext' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TContext as array
                     */
                    class Table {
                        /**
                         * @template TChunkData
                         * @param iterable<array-key, TValue> $data
                         * @param (callable(TValue, TChunkData): TContext)|null $row_context
                         */
                        public function __construct(iterable $data, $row_context = null) {}

                        /**
                         * @param (callable(TValue): string)|(callable(TValue, TContext): string) $content
                         */
                        public function column($content): void {}
                    }

                    class Row {}

                    function render(): void {
                        $table = new Table(
                            [new Row()],
                            /** @return array{ids: list<int>} */
                            static function (Row $r, $chunk): array {
                                return ["ids" => [1, 2]];
                            }
                        );
                        $table->column(static function (Row $r, $context): string {
                            $ids = $context["ids"];
                            return implode(",", $ids);
                        });
                    }',
            ],
            'untypedClosureParamResolvesInferredObjectElement' => [
                'code' => '<?php
                    /** @template TValue */
                    class Collection {
                        /** @param iterable<TValue> $data */
                        public function __construct(iterable $data) {}

                        /** @param callable(TValue): string $cb */
                        public function each($cb): void {}
                    }

                    class Item {
                        public int $id = 0;
                    }

                    function process(): void {
                        $c = new Collection([new Item()]);
                        $c->each(static function ($item): string {
                            return (string) $item->id;
                        });
                    }',
            ],
            'selfReferentialBoundFromClosureParamDoesNotRecurse' => [
                'code' => '<?php
                    interface Item {}
                    final class Stat implements Item {}

                    /**
                     * @template TKey as array-key
                     * @template TValue as Item
                     * @implements Iterator<TKey, TValue>
                     */
                    abstract class Collection implements Countable, Iterator {
                        /**
                         * @template TMapped
                         * @template TCallback as (Closure(TValue): TMapped)|null
                         * @param TCallback $callback
                         * @return list<(TCallback is null ? TValue : TMapped)>
                         */
                        public function map(?Closure $callback = null): array {
                            return [];
                        }

                        public function isEmpty(): bool {
                            foreach ($this as $_) {
                                return false;
                            }
                            return true;
                        }

                        /** @return self<TKey, TValue> */
                        public function limit(int $length): self {
                            return $this;
                        }
                    }

                    /**
                     * @template TTKey as array-key
                     * @template TTValue as Item
                     * @extends Collection<TTKey, TTValue>
                     */
                    class ArrayCollection extends Collection {
                        /** @var array<TTKey, TTValue> */
                        protected array $items = [];

                        /** @param array<TTKey, TTValue> $items */
                        public function __construct(array $items = []) {
                            $this->items = $items;
                        }

                        public function rewind(): void { reset($this->items); }
                        /** @return TTValue|null */
                        public function current() { return current($this->items) ?: null; }
                        /** @return TTKey|null */
                        public function key() { return key($this->items); }
                        public function next(): void { next($this->items); }
                        public function valid(): bool { return key($this->items) !== null; }
                        public function count(): int { return count($this->items); }

                        /** @param TTValue $item */
                        public function add($item): void {
                            echo count([$item]);
                        }

                        /**
                         * @template TNew as Item
                         * @param iterable<array-key, TNew> $items
                         * @psalm-self-out ArrayCollection<TTKey|int, TTValue|TNew>
                         * @return self<TTKey|int, TTValue|TNew>
                         */
                        public function addAll(iterable $items): self {
                            return $this;
                        }
                    }

                    /** @return ArrayCollection<int, Stat> */
                    function find(): ArrayCollection {
                        return new ArrayCollection([]);
                    }

                    function run(bool $fresh, int $limit): void {
                        $calls = new ArrayCollection([]);
                        if ($fresh) {
                            $calls = $calls->addAll(find()->limit($limit));
                        }
                        $rest = $limit - count($calls);
                        if ($rest > 0) {
                            $calls = $calls->addAll(find()->limit($rest));
                        }
                        if (!$calls->isEmpty()) {
                            $ids = $calls->map(static function ($obj): int {
                                return 1;
                            });
                            echo count($ids);
                        }
                    }',
            ],
            'mixedConstructorArgumentKeepsPlainMixedParam' => [
                'code' => '<?php
                    abstract class Base {}

                    /**
                     * @param mixed $class
                     * @return list<Base>
                     */
                    function fromMixed($class): array {
                        $rc = new ReflectionClass($class);
                        $name = $rc->getName();

                        if (!$rc->isSubclassOf(Base::class)) {
                            return [];
                        }

                        $o = $rc->newInstanceArgs([]);

                        return [$o];
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgument'],
            ],
            'mixedBoundTemplateIsClampedToConstraintOnLaterCalls' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TContext as array
                     */
                    class Table {
                        /**
                         * @param iterable<array-key, TValue> $data
                         * @param (callable(TValue): TContext)|null $row_context
                         */
                        public function __construct(iterable $data, $row_context = null) {}

                        /**
                         * @param (callable(TValue): string)|(callable(TValue, TContext): string) $content
                         */
                        public function column($content): void {}
                    }

                    final class Row {
                        public function getIdStr(): string { return "x"; }
                    }

                    /**
                     * @param iterable<array-key, Row> $data
                     * @param array $stat
                     */
                    function build(iterable $data, array $stat): void {
                        $table = new Table(
                            $data,
                            static function (Row $item) use ($stat) {
                                if (isset($stat[$item->getIdStr()])) {
                                    return $stat[$item->getIdStr()];
                                }
                                return [];
                            },
                        );
                        $table->column(static function (Row $item, $context): string {
                            return (string) ($context["shows"] ?? 0);
                        });
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArgumentTypeCoercion', 'MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'contravariantClosureParamIsAnUpperBound' => [
                // the closure must accept whatever the collection holds: that
                // bounds the element variable from above, not from below
                'code' => '<?php
                    interface Base {}
                    interface Marker {}
                    class Thing implements Base, Marker {}

                    /** @template T as Base */
                    class Coll {
                        /** @param list<T> $items */
                        public function __construct(array $items) {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    /** @template T as Base */
                    class Table {
                        /** @param Coll<T> $c */
                        public function __construct(Coll $c) {}

                        /** @param T $item */
                        public function add($item): void {}

                        /** @param callable(T): void $cb */
                        public function each(callable $cb): void {}
                    }

                    function f(): void {
                        $t = new Table(new Coll([new Thing()]));
                        $t->each(static function (Marker $x): void {});
                    }',
            ],
            'emptyConstructionBindsNestedTemplateToNever' => [
                'code' => '<?php
                    /** @template T as object */
                    class Coll {
                        /** @param list<T> $items */
                        public function __construct(array $items = []) {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    /** @template T as object */
                    class Box {
                        /** @param Coll<T> $c */
                        public function __construct(Coll $c) {}

                        /** @param T $t */
                        public function put($t): void {}
                    }

                    /** @return Box<never> */
                    function f(): Box {
                        return new Box(new Coll([]));
                    }',
            ],
            'emptyConstructionIsAbsorbedByLaterWidening' => [
                // `new Coll()` infers `never` for T; once something else is
                // added the variable adds nothing to the element type
                'code' => '<?php
                    /** @template T */
                    class Coll {
                        /** @param array<T> $items */
                        public function __construct(array $items = []) {}

                        /**
                         * @template U
                         * @param U $item
                         * @psalm-self-out Coll<T|U>
                         */
                        public function add($item): void {}

                        /** @param callable(T): void $cb */
                        public function each(callable $cb): void {}

                        /** @return T|null */
                        public function first() {
                            return null;
                        }

                        /** @return list<T> */
                        public function toList(): array {
                            return [];
                        }
                    }

                    class Node {
                        public int $v = 0;
                    }

                    function f(): void {
                        $c = new Coll();
                        $c->add(new Node());
                        $c->each(static function ($n): void {
                            echo $n->v;
                        });
                        $c->each(static function (Node $n): void {
                            echo $n->v;
                        });
                        $f = $c->first();
                        if ($f !== null) {
                            echo $f->v;
                        }
                        foreach ($c->toList() as $n) {
                            echo $n->v;
                        }
                    }',
            ],
            'unionAlternativesConstrainOnlyOneOfThem' => [
                // a declared union of generic alternatives requires the value
                // to satisfy one of them, not all of them at once
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class Coll {
                        /** @param array<TKey, TValue> $items */
                        public function __construct(array $items = []) {}

                        /**
                         * @param TKey $k
                         * @param TValue $v
                         */
                        public function set($k, $v): void {}
                    }

                    class Item {}

                    /** @return Coll<string, Item>|Coll<int, Item> */
                    function emptyColl(): Coll {
                        return new Coll();
                    }

                    /**
                     * @param list<Item> $items
                     * @return Coll<string, Item>|Coll<int, Item>
                     */
                    function fromList(array $items): Coll {
                        return new Coll($items);
                    }

                    /**
                     * @param list<Item> $items
                     * @return Coll<array-key, Item>|Coll<never, never>
                     */
                    function emptyOrItems(bool $b, array $items): Coll {
                        if ($b) {
                            return new Coll();
                        }
                        return new Coll($items);
                    }

                    /** @param Coll<string, Item>|Coll<int, Item> $c */
                    function take(Coll $c): void {}

                    /** @param list<Item> $items */
                    function pass(array $items): void {
                        take(new Coll($items));
                        take(new Coll());
                    }',
            ],
            'resolvedListFromTypeVariableStaysPossiblyEmpty' => [
                'code' => '<?php
                    /**
                     * @template-covariant TKey as array-key
                     * @template T
                     */
                    final class Box {
                        /** @var array<TKey, T> */
                        private array $items;

                        /** @param array<TKey, T> $items */
                        public function __construct(array $items) {
                            $this->items = $items;
                        }

                        /** @param T $item */
                        public function add($item): void {}

                        /** @return list<T> */
                        public function toList(): array {
                            return array_values($this->items);
                        }
                    }

                    final class Picker {
                        /**
                         * @template T
                         * @param array<T> $array
                         * @return ($array is non-empty-array<T> ? non-empty-list<T> : list<T>)
                         */
                        public function pick(array $array): array {
                            return array_slice(array_values($array), 0, 1);
                        }
                    }

                    /**
                     * @param list<array{a: int}> $in
                     * @return list<array{a: int}>
                     */
                    function run(array $in): array {
                        return (new Picker())->pick((new Box($in))->toList());
                    }',
            ],
            'typedClosureParamNarrowsThroughNestedTypeVariable' => [
                'code' => '<?php
                    /** @template T */
                    class Inner {
                        /** @param list<T> $items */
                        public function __construct(array $items) {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    /** @template T */
                    class Outer {
                        /** @param Inner<T> $inner */
                        public function __construct(Inner $inner) {}

                        /** @param callable(T): string $cb */
                        public function each($cb): void {}
                    }

                    class Base {}
                    class Row extends Base {
                        public function name(): string { return ""; }
                    }

                    function render(): void {
                        $outer = new Outer(new Inner([new Row()]));
                        $outer->each(static function (Base $row): string {
                            return $row->name();
                        });
                    }',
            ],
            'castsResolveTypeVariableFromForeach' => [
                'code' => '<?php
                    /**
                     * @template K as array-key
                     * @template V
                     */
                    class Map {
                        /** @param array<K, V> $data */
                        public function __construct(array $data) {}

                        /**
                         * @param K $k
                         * @param V $v
                         */
                        public function set($k, $v): void {}

                        /** @return array<K, V> */
                        public function all(): array { return []; }
                    }

                    function dump(): void {
                        $map = new Map(["a" => 1.5]);
                        foreach ($map->all() as $k => $v) {
                            echo (string) $k;
                            echo "key: {$k}";
                            echo (int) $v;
                            echo (float) $v;
                        }
                    }',
            ],
            'mixedConstructorArgumentResolvesToConstraint' => [
                'code' => '<?php
                    interface Entity {
                        public function id(): int;
                    }

                    /** @template T as Entity */
                    class Repo {
                        /** @param array<T> $entities */
                        public function __construct(array $entities) {}

                        /** @param T $entity */
                        public function add($entity): void {}

                        /** @return T|null */
                        public function first(): ?Entity { return null; }

                        /** @param callable(T): void $cb */
                        public function each($cb): void {}
                    }

                    function ids(array $untyped): void {
                        /** @psalm-suppress MixedArgumentTypeCoercion */
                        $repo = new Repo($untyped);
                        $first = $repo->first();
                        if ($first !== null) {
                            echo $first->id();
                        }
                        /** @psalm-suppress MixedArgumentTypeCoercion */
                        $repo->each(static function ($entity): void {
                            echo $entity->id();
                        });
                    }',
            ],
            'mixedConstructionCallableParamRequirementSuppressed' => [
                // the suppression on the call statement covers the coercion
                // reported when the variable's bounds reconcile
                'code' => '<?php
                    /** @template T */
                    final class Collection {
                        /** @param iterable<array-key, T> $items */
                        public function __construct(private iterable $items) {}

                        /** @param T $item */
                        public function add($item): void {}

                        /** @param callable(T): string $cb */
                        public function map(callable $cb): void {
                            foreach ($this->items as $item) {
                                $cb($item);
                            }
                        }
                    }

                    final class Foo {
                        public int $x = 0;
                    }

                    /** @param iterable<array-key, mixed> $items */
                    function mapMixed(iterable $items): void {
                        $c = new Collection($items);
                        /** @psalm-suppress MixedArgumentTypeCoercion */
                        $c->map(static fn (Foo $foo): string => (string) $foo->x);
                    }

                    /** @param iterable<array-key, Foo> $items */
                    function mapTyped(iterable $items): void {
                        $c = new Collection($items);
                        $c->map(static fn (Foo $foo): string => (string) $foo->x);
                    }',
            ],
            'unboundConstructionSatisfiesTypedProperty' => [
                // nothing bound the variable from below, so the property type
                // pins it without a mixed coercion
                'code' => '<?php
                    final class Holder {
                        /** @var SplFixedArray<int> */
                        public SplFixedArray $fixed;

                        public function __construct() {
                            $this->fixed = new SplFixedArray(5);
                            $this->fixed[0] = 1;
                        }
                    }',
            ],
            'readSitesResolveTypeVariableFromForeach' => [
                'code' => '<?php
                    /** @template T */
                    class Stream {
                        /** @param list<T> $data */
                        public function __construct(array $data) {}

                        /** @param T $item */
                        public function add($item): void {}

                        /** @return list<T> */
                        public function toList(): array {
                            return [];
                        }
                    }

                    class Node {
                        public int $v = 0;
                        public function touch(): void {}
                    }

                    /** @param list<Node> $nodes */
                    function walk(array $nodes): void {
                        $stream = new Stream($nodes);
                        foreach ($stream->toList() as $node) {
                            echo $node->v;
                            $node->touch();
                        }
                    }',
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
            'mixedConstructionCallableParamRequirement' => [
                // a value that entered the variable as mixed is then required to
                // be a Foo by the callable's parameter: reported at the call, as
                // the eager `Collection<mixed>` model did
                'code' => '<?php
                    /** @template T */
                    final class Collection {
                        /** @param iterable<array-key, T> $items */
                        public function __construct(private iterable $items) {}

                        /** @param T $item */
                        public function add($item): void {}

                        /** @param callable(T): string $cb */
                        public function map(callable $cb): void {
                            foreach ($this->items as $item) {
                                $cb($item);
                            }
                        }
                    }

                    final class Foo {
                        public int $x = 0;
                    }

                    /** @param iterable<array-key, mixed> $items */
                    function mapMixed(iterable $items): void {
                        $c = new Collection($items);
                        $c->map(static fn (Foo $foo): string => (string) $foo->x);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:25:29 - Type mixed should be a subtype of Foo',
            ],
            'mixedConstructionReturnedAsTypedGeneric' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param list<T> $items */
                        public function __construct(public array $items) {}

                        /** @param T $item */
                        public function add($item): void {
                            $this->items[] = $item;
                        }
                    }

                    final class Foo {}

                    /**
                     * @param list<mixed> $items
                     * @return Box<Foo>
                     */
                    function boxMixed(array $items): Box {
                        return new Box($items);
                    }',
                'error_message' => 'MixedReturnTypeCoercion - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:20:32 - The type 'Box<mixed>' is more general than the declared return type 'Box<Foo>' for boxMixed",
            ],
            'mixedConstructionAssignedToTypedProperty' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param list<T> $items */
                        public function __construct(public array $items) {}

                        /** @param T $item */
                        public function add($item): void {
                            $this->items[] = $item;
                        }
                    }

                    final class Foo {}

                    final class Holder {
                        /** @var Box<Foo> */
                        public Box $box;

                        /** @param list<mixed> $items */
                        public function __construct(array $items) {
                            $this->box = new Box($items);
                        }
                    }',
                'error_message' => 'MixedPropertyTypeCoercion - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:21:42 - \$this->box expects 'Box<Foo>',  parent type `Box<mixed>` provided",
            ],
            'unionAlternativesAllViolated' => [
                // what every alternative of the declared union requires is
                // still enforced
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class Coll {
                        /** @param array<TKey, TValue> $items */
                        public function __construct(array $items = []) {}

                        /**
                         * @param TKey $k
                         * @param TValue $v
                         */
                        public function set($k, $v): void {}
                    }

                    class Item {}
                    class Other {}

                    /**
                     * @param list<Other> $items
                     * @return Coll<string, Item>|Coll<int, Item>
                     */
                    function fromList(array $items): Coll {
                        return new Coll($items);
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:25:32 - Type Other should be a subtype of Item',
            ],
            'coercedArgumentRequirementIsArgumentTypeCoercion' => [
                // a construction whose element type is only a parent of what a
                // call requires is reported as the eager model reported it
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param list<T> $items */
                        public function __construct(public array $items) {}

                        /** @param T $item */
                        public function add($item): void { $this->items[] = $item; }
                    }

                    class Base {}
                    final class Child extends Base {}

                    /** @param Box<Child> $b */
                    function takesChildBox(Box $b): void {}

                    /** @param list<Base> $items */
                    function pass(array $items): void {
                        takesChildBox(new Box($items));
                    }',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:19:39 - Type Base should be a subtype of Child',
            ],
            'coercedPropertyRequirementIsPropertyTypeCoercion' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param list<T> $items */
                        public function __construct(public array $items) {}

                        /** @param T $item */
                        public function add($item): void { $this->items[] = $item; }
                    }

                    class Base {}
                    final class Child extends Base {}

                    final class Holder {
                        /** @var Box<Child> */
                        public Box $a;

                        /** @param list<Base> $x */
                        public function __construct(array $x) {
                            $this->a = new Box($x);
                        }
                    }',
                'error_message' => 'PropertyTypeCoercion - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:20:40 - Type Base should be a subtype of Child',
            ],
            'typeVariableBoundViolation' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    function probe(): void {
                        $box = new IntBox();
                        $box->add("nope");
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:12:35 - Type 'nope' should be a subtype of int",
            ],
            'constructorBoundConflictsWithDeclaredReturn' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    /** @return Box<string> */
                    function bad(): Box {
                        $box = new Box(1);
                        $box->set("two");
                        return $box;
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:16:32 - Type 1 should be a subtype of string",
            ],
            'constructorBoundWideningBeyondConstraint' => [
                'code' => '<?php
                    /**
                     * @template T of int|string
                     */
                    class Box {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param T $item */
                        public function set($item): void {
                            $this->t = $item;
                        }
                    }

                    function bad(): void {
                        $box = new Box(1);
                        $box->set(new DateTime());
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:16:35 - Type DateTime should be a subtype of int|string",
            ],
            'laterInvariantArgumentPinDoesNotBlameEarlierValidCall' => [
                // vimeo/psalm#11937: the invalid takesStringBox call is reported
                // at its call site; the valid takesIntBox call stays silent.
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public mixed $value) {}
                    }

                    /** @param Box<int> $box */
                    function takesIntBox(Box $box): int {
                        return $box->value;
                    }

                    /** @param Box<string> $box */
                    function takesStringBox(Box $box): string {
                        return $box->value;
                    }

                    function inspect(): void {
                        $box = new Box(1);
                        takesIntBox($box);
                        takesStringBox($box);
                    }',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:21:40 - Type 1 should be a subtype of string',
            ],
            'unboundVariablePassedToConflictingInvariantParams' => [
                // with no content, two incompatible invariant requirements are
                // still caught (mirror bounds stand in).
                'code' => '<?php
                    /** @template T */
                    class Box {
                        public function __construct() {}
                        /** @param T $v */
                        public function set($v): void {}
                    }

                    /** @param Box<int> $b */
                    function takesInt(Box $b): void {}

                    /** @param Box<string> $b */
                    function takesStr(Box $b): void {}

                    function f(): void {
                        $box = new Box();
                        takesInt($box);
                        takesStr($box);
                    }',
                'error_message' => 'IncompatibleTypeParameters',
            ],
            'globalScopeBoundViolation' => [
                'code' => '<?php
                    /** @template T of int */
                    class IntBox {
                        public function __construct() {}

                        /** @param T $item */
                        public function add($item): void {}
                    }

                    $box = new IntBox();
                    $box->add("nope");',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . "somefile.php:11:31 - Type 'nope' should be a subtype of int",
            ],
        ];
    }
}
