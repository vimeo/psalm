<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ImmutableAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'immutableClassGenerating' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function setA(int $a) : self {
                            return new self($a, $this->b);
                        }
                    }',
            ],
            'callInternalClassMethod' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var string */
                        public $a;

                        public function __construct(string $a) {
                            $this->a = $a;
                        }

                        public function getA() : string {
                            return $this->a;
                        }

                        public function getHelloA() : string {
                            return "hello" . $this->getA();
                        }
                    }',
            ],
            'addToCart' => [
                '<?php
                    /** @psalm-immutable */
                    class Cart {
                        /** @var CartItem[] */
                        public array $items;

                        /** @param CartItem[] $items */
                        public function __construct(array $items) {
                            $this->items = $items;
                        }

                        public function addItem(CartItem $item) : self {
                            $items = $this->items;
                            $items[] = $item;
                            return new Cart($items);
                        }
                    }

                    /** @psalm-immutable */
                    class CartItem {
                        public string $name;
                        public float $price;

                        public function __construct(string $name, float $price) {
                            $this->name = $name;
                            $this->price = $price;
                        }
                    }

                    /** @psalm-pure */
                    function addItemToCart(Cart $c, string $name, float $price) : Cart {
                        return $c->addItem(new CartItem($name, $price));
                    }',
            ],
            'allowImpureStaticMethod' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    final class ClientId
                    {
                        public string $id;

                        private function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        public static function fromString(string $id): self
                        {
                            return new self($id . rand(0, 1));
                        }
                    }'
            ],
            'allowPropertySetOnNewInstance' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Foo {
                        protected string $bar;

                        public function __construct(string $bar) {
                            $this->bar = $bar;
                        }

                        public function withBar(string $bar): self {
                            $new = new Foo("hello");
                            $new->bar = $bar;

                            return $new;
                        }
                    }'
            ],
            'allowArrayMapCallable' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Address
                    {
                        private $line1;
                        private $line2;
                        private $city;

                        public function __construct(
                            string $line1,
                            ?string $line2,
                            string $city
                        ) {
                            $this->line1 = $line1;
                            $this->line2 = $line2;
                            $this->city = $city;
                        }

                        public function __toString()
                        {
                            $parts = [
                                $this->line1,
                                $this->line2 ?? "",
                                $this->city,
                            ];

                            // Remove empty parts
                            $parts = \array_map("trim", $parts);
                            $parts = \array_filter($parts, "strlen");
                            $parts = \array_map(function(string $s) { return $s;}, $parts);

                            return \implode(", ", $parts);
                        }
                    }'
            ],
            'allowPropertyAssignmentInUnserialize' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Foo implements \Serializable {
                        /** @var string */
                        private $data;

                        public function __construct() {
                            $this->data = "Foo";
                        }

                        public function serialize() {
                            return $this->data;
                        }

                        public function unserialize($data) {
                            $this->data = $data;
                        }

                        public function getData(): string {
                            return $this->data;
                        }
                    }'
            ],
            'allowPropertyAssignmentInMagicUnserialize' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Foo {
                        /** @var string */
                        private $data;

                        public function __construct() {
                            $this->data = "Foo";
                        }

                        public function __serialize(): array {
                            return ["data" => $this->data];
                        }

                        /** @param array{data: string} $data */
                        public function __unserialize(array $data): void {
                            $this->data = $data["data"];
                        }

                        public function getData(): string {
                            return $this->data;
                        }
                    }'
            ],
            'allowMethodOverriding' => [
                '<?php
                    class A {
                        private string $a;

                        public function __construct(string $a) {
                            $this->a = $a;
                        }

                        public function getA() : string {
                            return $this->a;
                        }
                    }

                    /** @method string getA() */
                    class B extends A {}',
            ],
            'immutableClassWithCloneAndPropertyChange' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Foo {
                        protected string $bar;

                        public function __construct(string $bar) {
                            $this->bar = $bar;
                        }

                        public function withBar(string $bar): self {
                            $new = clone $this;
                            $new->bar = $bar;
                            return $new;
                        }
                    }',
            ],
            'immutableClassWithCloneAndPropertyAppend' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Foo {
                        protected string $bar;

                        public function __construct(string $bar) {
                            $this->bar = $bar;
                        }

                        public function withBar(string $bar): self {
                            $new = clone $this;
                            $new->bar .= $bar;
                            return $new;
                        }
                    }',
            ],
            'memoizeImmutableCalls' => [
                '<?php
                    function takesString(string $s) : void {}

                    /**
                     * @psalm-immutable
                     */
                    class DTO {
                        /** @var string|null */
                        private $error;

                        public function __construct(?string $error) {
                            $this->error = $error;
                        }

                        public function getError(): ?string {
                            return $this->error;
                        }
                    }

                    $dto = new DTO("BOOM!");

                    if ($dto->getError()) {
                        takesString($dto->getError());
                    }'
            ],
            'allowConstructorPrivateUnusedMethods' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class PaymentShared
                    {
                        /** @var int */
                        private $commission;

                        public function __construct()
                        {
                            $this->test();
                            $this->commission = 1;
                        }

                        private function test(): void {}
                    }'
            ],
            'canPassImmutableIntoImmutable' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Item {
                        private int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        /** @psalm-mutation-free */
                        public function get(): int {
                            return $this->i;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    class Immutable {
                        private $item;

                        public function __construct(Item $item) {
                            $this->item = $item;
                        }

                        public function get(): int {
                            return $this->item->get();
                        }
                    }

                    $item = new Item(5);
                    new Immutable($item);',
            ],
            'preventNonImmutableTraitInImmutableClass' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    trait ImmutableTrait {
                        public int $i = 0;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    final class NotReallyImmutableClass {
                        use ImmutableTrait;
                    }',
            ],
            'preventImmutableClassInheritingMutableParent' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class ImmutableParent {
                        public int $i = 0;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    final class ImmutableClass extends ImmutableParent {}',
            ],
            'passDateTimeZone' => [
                '<?php
                    echo (new DateTimeImmutable("now", new DateTimeZone("UTC")))->format("Y-m-d");'
            ],
            'allowPassingCloneOfMutableIntoImmutable' => [
                '<?php
                    class Item {
                        private int $i = 0;

                        public function mutate(): void {
                            $this->i++;
                        }

                        /** @psalm-mutation-free */
                        public function get(): int {
                            return $this->i;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    class Immutable {
                        private Item $item;

                        public function __construct(Item $item) {
                            $this->item = clone $item;
                        }

                        public function get(): int {
                            return $this->item->get();
                        }
                    }

                    $item = new Item();
                    new Immutable($item);',
            ],
            'noCrashWhenCheckingValueTwice' => [
                '<?php
                    /**
                     * @psalm-template T
                     * @psalm-immutable
                     */
                    abstract class Enum {
                        /** @var T */
                        private $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /**
                         * @return mixed
                         * @psalm-return T
                         */
                        public function getValue() {
                            return $this->value;
                        }
                    }

                    /**
                     * @extends Enum<string>
                     * @psalm-immutable
                     */
                    class TestEnum extends Enum {
                        public const TEST = "test";
                    }

                    function foo(TestEnum $e): void {
                        if ($e->getValue() === TestEnum::TEST
                            && $e->getValue() === TestEnum::TEST
                        ) {}
                    }'
            ],
            'allowMutablePropertyFetch' => [
                '<?php
                    class B {
                        public int $j = 5;
                    }

                    /**
                     * @psalm-immutable
                     */
                    class A {
                        public int $i;

                        public function __construct(int $i) {
                            $this->i = $i;
                        }

                        public function getPlusOther(B $b) : int {
                            return $this->i + $b->j;
                        }
                    }',
            ],
            'allowPassingMutableIntoImmutable' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Immutable {
                        private $item;

                        public function __construct(Item $item) {
                            $this->item = $item;
                        }

                        public function get(): int {
                            return $this->item->get();
                        }
                    }

                    class Item {
                        private int $i = 0;

                        public function mutate(): void {
                            $this->i++;
                        }

                        /** @psalm-mutation-free */
                        public function get(): int {
                            return $this->i;
                        }
                    }',
            ],
            'allowMutationFreeCallInMutationFreeContext' => [
                '<?php

                    /**
                     * @psalm-mutation-free
                     */
                    function getData(): array {
                        /** @var mixed $arr */
                        $arr = $GLOBALS["cachedData"] ?? [];

                        return is_array($arr) ? $arr : [];
                    }

                    /**
                     * @psalm-mutation-free
                     * @return mixed
                     */
                    function getDataItem(string $key) {
                        return getData()[$key] ?? null;
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'immutablePropertyAssignmentInternally' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function setA(int $a): void {
                            $this->a = $a;
                        }
                    }',
                'error_message' => 'InaccessibleProperty',
            ],
            'immutablePropertyAssignmentExternally' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }
                    }

                    $a = new A(4, "hello");

                    $a->b = "goodbye";',
                'error_message' => 'InaccessibleProperty',
            ],
            'callImpureFunction' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var int */
                        private $a;

                        /** @var string */
                        public $b;

                        public function __construct(int $a, string $b) {
                            $this->a = $a;
                            $this->b = $b;
                        }

                        public function bar() : void {
                            header("Location: https://vimeo.com");
                        }
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'callExternalClassMethod' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var string */
                        public $a;

                        public function __construct(string $a) {
                            $this->a = $a;
                        }

                        public function getA() : string {
                            return $this->a;
                        }

                        public function redirectToA() : void {
                            B::setRedirectHeader($this->getA());
                        }
                    }

                    class B {
                        public static function setRedirectHeader(string $s) : void {
                            header("Location: $s");
                        }
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'mustBeImmutableLikeInterfaces' => [
                '<?php
                    /** @psalm-immutable */
                    interface SomethingImmutable {
                        public function someInteger() : int;
                    }

                    class MutableImplementation implements SomethingImmutable {
                        private int $counter = 0;
                        public function someInteger() : int {
                            return ++$this->counter;
                        }
                    }',
                'error_message' => 'MissingImmutableAnnotation',
            ],
            'inheritImmutabilityFromParent' => [
                '<?php
                    /** @psalm-immutable */
                    abstract class SomethingImmutable {
                        abstract public function someInteger() : int;
                    }

                    class MutableImplementation extends SomethingImmutable {
                        private int $counter = 0;
                        public function someInteger() : int {
                            return ++$this->counter;
                        }
                    }',
                'error_message' => 'MissingImmutableAnnotation',
            ],
            'preventNonImmutableTraitInImmutableClass' => [
                '<?php
                    trait MutableTrait {
                        public int $i = 0;

                        public function increment() : void {
                            $this->i++;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    final class NotReallyImmutableClass {
                        use MutableTrait;
                    }',
                'error_message' => 'MutableDependency'
            ],
            'preventImmutableClassInheritingMutableParent' => [
                '<?php
                    class MutableParent {
                        public int $i = 0;

                        public function increment() : void {
                            $this->i++;
                        }
                    }

                    /**
                     * @psalm-immutable
                     */
                    final class NotReallyImmutableClass extends MutableParent {}',
                'error_message' => 'MutableDependency'
            ],
            'mutationInPropertyAssignment' => [
                '<?php
                    class D {
                        private string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function getShort() : string {
                            return substr($this->s, 0, 5);
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function getShortMutating() : string {
                            $this->s = "hello";
                            return substr($this->s, 0, 5);
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'mutationInPropertyConcat' => [
                '<?php
                    class D {
                        private string $s;

                        public function __construct(string $s) {
                            $this->s = $s;
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function getShort() : string {
                            return substr($this->s, 0, 5);
                        }

                        /**
                         * @psalm-mutation-free
                         */
                        public function getShortMutating() : string {
                            $this->s .= "hello";
                            return substr($this->s, 0, 5);
                        }
                    }',
                'error_message' => 'ImpurePropertyAssignment',
            ],
            'preventUnset' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A {
                        /** @var string */
                        public $b;

                        public function __construct(string $b) {
                            $this->b = $b;
                        }
                    }

                    $a = new A("hello");
                    unset($a->b);',
                'error_message' => 'InaccessibleProperty',
            ],
        ];
    }
}
