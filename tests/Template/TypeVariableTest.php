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
                     * @return XIteratorOnArray<array-key, Foo>|XIteratorOnArray<never, never>
                     */
                    function filter(array $users): XIteratorOnArray {
                        return new XIteratorOnArray($users);
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
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'inhabitedVariableNotContainedByNeverReturn' => [
                // returning `a<int>` where `@return a<never>` is declared is still
                // rejected: an inhabited type variable is not a subtype of `never`,
                // so the containment fails (Hack reports the invariant `nothing`
                // mismatch: "Expected nothing ... But got int"). Guards the
                // UnionTypeComparator never-arm fix against hiding this — the
                // single-arm case must error even though the union arm in
                // `possiblyEmptyArray` is allowed to fall through to its other arm.
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
                'error_message' => 'InvalidReturnStatement',
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
