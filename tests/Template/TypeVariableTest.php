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
            'possiblyEmptyArray' => [
                'code' => '<?php
                    /**
                     * @template TTKey as array-key
                     * @template TTValue
                     */
                    final class XIteratorOnArray {
                        /** @param array<TTKey, TTValue> $array */
                        public function __construct(array $array = []) {}
                        /** @param callable(TTValue, TTKey): mixed $func */
                        public function sortBy(callable $func): void {}
                    }

                    class Foo {}

                    /**
                     * @param array<Foo> $users
                     * @return XIteratorOnArray<array-key, Foo>
                     */
                    function filter(array $users): XIteratorOnArray {
                        return new XIteratorOnArray($users);
                    }',
            ],
            'multipleReturnTypes' => [
                'code' => '<?php
                    /** @template-covariant TKey */
                    class It {
                        /** @param array<TKey, mixed> $array */
                        public function __construct(array $array = []) {}
                        /**
                         * @param callable(TKey): mixed $func
                         * @psalm-suppress InvalidTemplateParam
                         */
                        public function sortBy(callable $func): void {}
                    }

                    /** @return It<string>|It<int> */
                    function takeNew(): It {
                        return random_int(0, 1) === 0 ? new It([0]) : new It(["hello"]);
                    }',
            ],
            'multipleReturnTypesNotCovariant' => [
                'code' => '<?php
                    /** @template TKey */
                    class It {
                        /** @param array<TKey, mixed> $array */
                        public function __construct(array $array = []) {}
                        /**
                         * @param callable(TKey): mixed $func
                         */
                        public function sortBy(callable $func): void {}
                    }

                    /** @return It<string>|It<int> */
                    function takeNew(): It {
                        return random_int(0, 1) === 0 ? new It([0]) : new It(["hello"]);
                    }',
            ],
            'emptyConstructionAgainstUnionKeyReturn' => [
                // an empty `new It()` returned where the declared type is a
                // union of two key instantiations (It<string>|It<int>). Hack
                // accepts this: it localizes the declared union to
                // It<string|int> (covariant), so the variable only gains an
                // upper bound.
                'code' => '<?php
                    /** @template-covariant TKey */
                    class It {
                        /** @param array<TKey, mixed> $array */
                        public function __construct(array $array = []) {}
                        /**
                         * @param callable(TKey): mixed $func
                         * @psalm-suppress InvalidTemplateParam
                         */
                        public function sortBy(callable $func): void {}
                    }

                    /** @return It<string>|It<int> */
                    function takeNew(): It {
                        return new It();
                    }',
            ],
            'methodCallOnIteratorElement' => [
                'code' => '<?php
                    final class User {
                        public function getId(): ?int { return null; }
                    }

                    /** @template TTValue */
                    final class XIteratorOnArray {
                        /** @param array<TTValue> $array */
                        public function __construct(array $array = []) {}
                        /** @param callable(TTValue, TTValue): mixed $func */
                        public function sortBy(callable $func): void {}
                        /** @return list<TTValue> */
                        public function toArray(): array { throw new \Exception("stub"); }
                    }

                    /** @param array<User> $users */
                    function prepareData(array $users): void {
                        $users = (new XIteratorOnArray($users))->toArray();
                        foreach ($users as $user) {
                            $user->getId();
                        }
                    }',
            ],
            'arrayAccess' => [
                'code' => '<?php
                    /** @template TTValue */
                    final class a {
                        /** @param non-empty-array<TTValue> $array */
                        public function __construct(array $array = [0]) {}
                        /** @param callable(TTValue, TTValue): mixed $func */
                        public function sortBy(callable $func): void {}
                        /** @return non-empty-list<TTValue> */
                        public function toArray(): array { throw new \Exception("stub"); }
                    }

                    function match2(): void {
                        $r = (new a([[1, 2]]))->toArray();
                        echo (string) $r[0][0];
                    }',
            ],
            'arrayAccessOnNestedTypeVariableElement' => [
                // a type variable whose bound is itself another type variable
                // (the element of a `list<TValue>` whose TValue was inferred
                // from an `array<string, TValue>` that already held a variable)
                // must resolve through the whole chain to its concrete array
                // bound, not stop one level short and be rejected as a non-array
                // (InvalidArrayAccess).
                'code' => '<?php
                    /** @template TValue */
                    final class XIter {
                        /** @param array<TValue> $array */
                        public function __construct(array $array = []) {}
                        /** @param callable(TValue, TValue): mixed $func */
                        public function sortBy(callable $func): void {}
                        /** @return array<string, TValue> */
                        public function toAssoc(): array { throw new \Exception("stub"); }
                        /** @return list<TValue> */
                        public function toList(): array { throw new \Exception("stub"); }
                    }

                    /** @param array<array{id: int}> $rows */
                    function run(array $rows): void {
                        $assoc = (new XIter($rows))->toAssoc();
                        $list = (new XIter($assoc))->toList();
                        foreach ($list as $row) {
                            echo $row["id"];
                        }
                    }',
            ],
            'methodCallOnTypeVariableArrayElement' => [
                // a type variable that surfaces as an array value (here through
                // a conditional `@return`) and is then read out and used as a
                // method-call receiver must resolve to its object bound rather
                // than crash the nullability-stripping that follows a call on a
                // from-docblock receiver ("We must have some types here!").
                'code' => '<?php
                    /** @template TValue */
                    final class XIter {
                        /** @param array<TValue> $array */
                        public function __construct(array $array = []) {}
                        /**
                         * @template TCallback as (callable(TValue): mixed)|null
                         * @param TCallback $callback
                         * @return array<string, (TCallback is null ? TValue : mixed)>
                         */
                        public function toAssocArray(?callable $callback = null): array {
                            throw new \Exception("stub");
                        }
                    }

                    final class OrgFilter {
                        public function getIdStr(): string { return ""; }
                    }

                    /**
                     * @param array<OrgFilter> $orgs
                     * @return array<string, list<OrgFilter>>
                     */
                    function groupThem(array $orgs): array { throw new \Exception("stub"); }

                    /**
                     * @param array<OrgFilter> $orgs
                     * @return list<string>
                     */
                    function run(array $orgs): array {
                        $byType = groupThem($orgs);
                        foreach ($byType as $type => $grp) {
                            $byType[$type] = (new XIter($grp))->toAssocArray();
                        }
                        $out = [];
                        foreach ($byType as $inFilter) {
                            if (array_key_exists("x", $inFilter)) {
                                $out[] = $inFilter["x"]->getIdStr();
                            }
                        }
                        return $out;
                    }',
            ],
            'castTypeVariableToString' => [
                // a type variable read out of a `list<TValue>` element is
                // castable through the bound its construction inferred;
                // `(string) $var` must resolve it rather than reject the bare
                // variable (InvalidCast "`_N cannot be cast to string").
                'code' => '<?php
                    /** @template TValue */
                    final class XIter {
                        /** @param array<TValue> $array */
                        public function __construct(array $array) {}
                        /** @param callable(TValue, TValue): mixed $func */
                        public function sortBy(callable $func): void {}
                        /** @return list<TValue> */
                        public function toArray(): array { throw new \Exception("stub"); }
                    }

                    function run(): void {
                        foreach ((new XIter([1]))->toArray() as $v) {
                            echo (string) $v;
                        }
                    }',
            ],
            'propertyFetchThenMethodCallOnElement' => [
                // a type variable reached through a property fetch is the object
                // it was inferred to be; the method call resolves through its
                // bounds (Hack: no errors).
                'code' => '<?php
                    final class User { public function getId(): int { return 0; } }

                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public $value) {}
                        /** @param callable(T, T): mixed $func */
                        public function sortBy(callable $func): void {}
                    }

                    function pchain(): void {
                        $b = new Box(new User());
                        echo $b->value->getId();
                    }',
            ],
            'nestedConstructionElementArithmetic' => [
                // nested `new Box(new Box(5))`: the inner element resolves to int
                // and supports arithmetic (Hack: no errors).
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @param T $value */
                        public function __construct(public $value) {}
                        /** @param callable(T, T): mixed $func */
                        public function sortBy(callable $func): void {}
                    }

                    function nested(): void {
                        $bb = new Box(new Box(5));
                        $inner = $bb->value;
                        echo $inner->value + 1;
                    }',
            ],
            'propertyFetchOnTypeVariableIteratorElement' => [
                // reading an element out of a `list<TValue>` return yields a
                // bare type variable; used as a property-fetch receiver it must
                // resolve through its object bound — as a method call already
                // does — rather than being rejected as a non-object.
                'code' => '<?php
                    final class User { public int $id = 0; }

                    /** @template TValue */
                    final class XIter {
                        /** @param array<TValue> $array */
                        public function __construct(array $array = []) {}
                        /** @param callable(TValue, TValue): mixed $func */
                        public function sortBy(callable $func): void {}
                        /** @return list<TValue> */
                        public function toArray(): array { throw new \Exception("stub"); }
                    }

                    /** @param array<User> $users */
                    function run(array $users): void {
                        $items = (new XIter($users))->toArray();
                        foreach ($items as $user) {
                            echo $user->id;
                        }
                    }',
            ],
            'unboundTemplateSolvesToClosureParam' => [
                // an unbound `new Box()` never gets a lower bound, so the
                // closure parameter only constrains the variable from above
                // (`T <: Item`); that is satisfiable, so no error — as Hack
                // reports (it solves the variable)
                'code' => '<?php
                    class Item {
                        public int $id = 0;
                    }

                    /** @template T */
                    class Box {
                        public function __construct() {}

                        /** @param callable(T): mixed $cb */
                        public function each($cb): void {}
                    }

                    function process(): void {
                        $box = new Box();
                        $box->each(static function (Item $item): int {
                            return $item->id;
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
            'closureRet' => [
                'code' => '<?php

                    /**
                     * @template TContext as array
                     */
                    class a {
                        
                        /**
                         * @param (callable(): TContext)|null $row_context
                         */
                        public function __construct(
                            protected $row_context = null,
                        ) {
                        }
                        
                        /**
                         * @psalm-type TReturn = string|null|Stringable|int|float|list<Stringable|string>
                         *
                         * @param (callable(TContext): TReturn) $content
                         */
                        public function column($content): void {
                        }
                    }

                    $table = new a(
                        /**
                         * @return array{a: array<int, int>, b: array<string, string>}
                         */
                        static function (): array {
                            throw new AssertionError;
                        },
                    );

                    $table->column(static function (array $ctx): string {
                        /** @psalm-check-type-exact $ctx = array{a: array<int, int>, b: array<string, string>} */;
                        return "";
                    });',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
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
            'constructedGenericObjectInfersCalleeTemplate' => [
                // vimeo/psalm#11963: a type variable in an argument must still
                // bind the callee's template, rather than leaving it at its
                // constraint.
                'code' => '<?php
                    interface ObjectFactoryInterface
                    {
                        /**
                         * @template T of object
                         * @param ReflectionClass<T> $reflectionClass
                         * @return T
                         */
                        public function create(ReflectionClass $reflectionClass): object;
                    }

                    final class Hydrator
                    {
                        public function __construct(private ObjectFactoryInterface $objectFactory) {}

                        /**
                         * @template T of object
                         * @param class-string<T> $class
                         * @return T
                         */
                        public function create(string $class): object
                        {
                            return $this->objectFactory->create(new ReflectionClass($class));
                        }
                    }',
            ],
            'constructedGenericObjectInfersCalleeTemplateThroughVariable' => [
                'code' => '<?php
                    interface ObjectFactoryInterface
                    {
                        /**
                         * @template T of object
                         * @param ReflectionClass<T> $reflectionClass
                         * @return T
                         */
                        public function create(ReflectionClass $reflectionClass): object;
                    }

                    final class Hydrator
                    {
                        public function __construct(private ObjectFactoryInterface $objectFactory) {}

                        /**
                         * @template T of object
                         * @param class-string<T> $class
                         * @return T
                         */
                        public function create(string $class): object
                        {
                            $reflectionClass = new ReflectionClass($class);

                            return $this->objectFactory->create($reflectionClass);
                        }
                    }',
            ],
            'propertyInitialisedFromTemplatedMethodCallInConstructor' => [
                // vimeo/psalm#11963: the constructor initialisation pass must see
                // `$this` as the generic self type, so a class-string<T> property
                // still pins the type variable to T.
                'code' => '<?php
                    /**
                     * @template T of object
                     */
                    final class ReflectionStubber
                    {
                        /** @var ReflectionClass<T> */
                        private readonly ReflectionClass $reflectionStub;

                        /** @param class-string<T> $stubbedClass */
                        public function __construct(
                            private readonly string $stubbedClass,
                        ) {
                            $this->reflectionStub = $this->getReflectionStub();
                        }

                        /** @return ReflectionClass<T> */
                        private function getReflectionStub(): ReflectionClass
                        {
                            return new ReflectionClass($this->stubbedClass);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'inhabitedVariableNotContainedByNeverReturn' => [
                // returning `a<int>` where `@return a<never>` is declared must be
                // rejected: an inhabited type variable is not a subtype of `never`
                // (Hack reports the invariant `nothing` mismatch, "Expected nothing
                // ... But got int"). The exact Psalm issue is not important — this
                // guards only that it stays an error, not silently accepted.
                'code' => '<?php
                    /** @template T */
                    final class a {
                        /** @param T $t */
                        public function __construct(public $t) {}
                        /** @param callable(T, T): mixed $func */
                        public function sortBy(callable $func): void {}
                    }

                    /** @return a<never> */
                    function f(): a { return new a(5); }',
                'error_message' => 'IncompatibleTypeParameters',
            ],
            'mixedConstructorInferenceCoercesClosureParam' => [
                // the constructor infers TValue as mixed (lower bound mixed); the
                // closure parameter constrains it from above (TValue <: Item),
                // and mixed does not coerce to Item, so MixedArgumentTypeCoercion
                // is reported at reconciliation — as it is for a non-`new`
                // Table<mixed>, and as Hack reports ("Expected Item but got mixed")
                'code' => '<?php
                    class Item {
                        public int $id = 0;
                    }

                    /** @template TValue */
                    class Table {
                        /** @param iterable<array-key, TValue> $data */
                        public function __construct(iterable $data) {}

                        /** @param callable(TValue): mixed $content */
                        public function column($content): void {}
                    }

                    /** @param iterable<array-key, mixed> $items */
                    function prepareTable(iterable $items): void {
                        $table = new Table($items);
                        $table->column(static function (Item $item): int {
                            return $item->id;
                        });
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'constructorBoundThenClosureParamConflict' => [
                // an unbound variable gains a lower bound (int, via set) and an
                // upper bound (Item, via the closure parameter); they cannot
                // hold together, as Hack reports ("Expected Item but got int")
                'code' => '<?php
                    class Item {
                        public int $id = 0;
                    }

                    /** @template T */
                    class Box {
                        public function __construct() {}

                        /** @param T $v */
                        public function set($v): void {}

                        /** @param callable(T): mixed $cb */
                        public function each($cb): void {}
                    }

                    function process(): void {
                        $box = new Box();
                        $box->set(5);
                        $box->each(static function (Item $item): int {
                            return $item->id;
                        });
                    }',
                'error_message' => 'IncompatibleTypeParameters',
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
