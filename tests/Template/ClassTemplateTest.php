<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ClassTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testImportAliasNamedLikeClassTemplateInDocblockMethodParamDefault(): void
    {
        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                namespace Vendor {
                    class Defaults {
                        public const NONE = null;
                    }
                }

                namespace App\Ns {
                    use Vendor\Defaults as TValue;

                    /**
                     * @template TValue
                     * @method void label(string $label = TValue::NONE)
                     */
                    interface Box {}

                    /** @param Box<int> $box */
                    function f(Box $box): void {
                        $box->label(null);
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'staticReturnKeepsTheTypeArgumentsOfTheCalledClass' => [
                'code' => '<?php
                    /** @template T of object */
                    class Coll {
                        /** @param list<T> $items */
                        public function __construct(public array $items) {}

                        public function copy(): static {
                            return clone $this;
                        }
                    }

                    /** @extends Coll<stdClass> */
                    final class StdColl extends Coll {}

                    /** @return Coll<stdClass> */
                    function getColl(): Coll {
                        return new Coll([]);
                    }

                    $copy = getColl()->copy();
                    $std_copy = (new StdColl([]))->copy();',
                'assertions' => [
                    '$copy===' => 'Coll<stdClass>&static',
                    '$std_copy===' => 'StdColl',
                ],
            ],
            'templateIntersection' => [
                'code' => '<?php
                    interface EntityInterface
                    {
                        public function getId(): string;
                    }

                    /**
                     * @phpstan-template T of EntityInterface
                     */
                    interface RepositoryInterface
                    {
                        /**
                         * @return T|null
                         */
                    public function byId(string $id);
                    }

                    final class Foo implements EntityInterface
                    {
                        public function getId(): string
                        {
                            return "42";
                        }
                    }

                    /**
                     * @phpstan-implements RepositoryInterface<Foo>
                     */
                    final class FooRepository implements RepositoryInterface
                    {
                        /**
                         * @var Foo[]
                         */
                        public array $elements = [];

                        public function byId(string $id): ?Foo
                        {
                            return $this->elements[$id] ?? null;
                        }
                    }
                ',
            ],
            'cachingIterator' => [
                'code' => '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new CachingIterator($arrayIterator);
                    $next = $decoratorIterator->hasNext();
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int<0, max>|null',
                    '$value' => 'null|string',
                    '$next' => 'bool',
                ],
            ],
            'infiniteIterator' => [
                'code' => '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new InfiniteIterator($arrayIterator);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int<0, max>|null',
                    '$value' => 'null|string',
                ],
            ],
            'limitIterator' => [
                'code' => '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new LimitIterator($arrayIterator, 1, 1);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int<0, max>|null',
                    '$value' => 'null|string',
                ],
            ],
            'callbackFilterIterator' => [
                'code' => '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new CallbackFilterIterator(
                        $arrayIterator,
                        static function (string $value): bool {return "a" === $value;}
                    );
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int<0, max>|null',
                    '$value' => 'null|string',
                ],
            ],
            'noRewindIterator' => [
                'code' => '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new NoRewindIterator($arrayIterator);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int<0, max>|null',
                    '$value' => 'null|string',
                ],
            ],
            'classTemplate' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}

                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var T::class */
                        public $T;

                        /**
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $at = "A";

                    /**
                     * @var Foo<A>
                     * @psalm-suppress ArgumentTypeCoercion
                     */
                    $afoo = new Foo($at);
                    $afoo_bar = $afoo->bar();

                    $bfoo = new Foo(B::class);
                    $bfoo_bar = $bfoo->bar();

                    // this shouldn’t cause a problem as it’s a docbblock type
                    if (!($bfoo_bar instanceof B)) {}

                    $c = C::class;
                    $cfoo = new Foo($c);
                    $cfoo_bar = $cfoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',

                    '$bfoo' => 'Foo<B>',
                    '$bfoo_bar' => 'B',

                    '$cfoo' => 'Foo<C>',
                    '$cfoo_bar' => 'C',
                ],
                'ignored_issues' => [
                    'MixedReturnStatement',
                    'LessSpecificReturnStatement',
                    'DocblockTypeContradiction',
                ],
            ],
            'classTemplateSelf' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var class-string<T> */
                        public $T;

                        /**
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    class E {
                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo() {
                            return new Foo(__CLASS__);
                        }

                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo2() {
                            return new Foo(self::class);
                        }

                        /**
                         * @return Foo<static>
                         */
                        public static function getFoo3() {
                            return new Foo(static::class);
                        }
                    }

                    class G extends E {}

                    $efoo = E::getFoo();
                    $efoo2 = E::getFoo2();
                    $efoo3 = E::getFoo3();

                    $gfoo = G::getFoo();
                    $gfoo2 = G::getFoo2();
                    $gfoo3 = G::getFoo3();',
                'assertions' => [
                    '$efoo' => 'Foo<E>',
                    '$efoo2' => 'Foo<E>',
                    '$efoo3' => 'Foo<E>',
                    '$gfoo' => 'Foo<E>',
                    '$gfoo2' => 'Foo<E>',
                    '$gfoo3' => 'Foo<G>',
                ],
                'ignored_issues' => [
                    'LessSpecificReturnStatement',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'classTemplateExternalClasses' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var T::class */
                        public $T;

                        /**
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $efoo = new Foo(\Exception::class);
                    $efoo_bar = $efoo->bar();

                    $ffoo = new Foo(\LogicException::class);
                    $ffoo_bar = $ffoo->bar();',
                'assertions' => [
                    '$efoo' => 'Foo<Exception>',
                    '$efoo_bar' => 'Exception',

                    '$ffoo' => 'Foo<LogicException>',
                    '$ffoo_bar' => 'LogicException',
                ],
                'ignored_issues' => ['LessSpecificReturnStatement'],
            ],
            'classTemplateContainerSimpleCall' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @template T
                     */
                    class Foo {
                        /** @var T */
                        public $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct($obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            return $this->obj;
                        }
                    }

                    $afoo = new Foo(new A());
                    $afoo_bar = $afoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',
                ],
            ],
            'classTemplateContainerThisCall' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Foo {
                        /** @var T */
                        public $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct($obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            return $this->obj;
                        }

                        /**
                         * @return T
                         */
                        public function bat() {
                            return $this->bar();
                        }

                        public function __toString(): string {
                            return "hello " . $this->obj;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedOperand'],
            ],
            'validPsalmTemplatedClassType' => [
                'code' => '<?php
                    class A {}

                    /**
                     * @psalm-template T
                     */
                    class Foo {
                        /**
                         * @param T $x
                         */
                        public function bar($x): void { }
                    }

                    $afoo = new Foo();
                    $afoo->bar(new A());',
            ],
            'intersectionTemplatedTypes' => [
                'code' => '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data) {
                            $this->data = $data;
                        }
                    }

                    class Item {}
                    /** @psalm-param Collection<Item> $c */
                    function takesCollectionOfItems(Collection $c): void {}

                    /** @psalm-var iterable<Item> $data2 */
                    $data2 = [];
                    takesCollectionOfItems(new Collection($data2));

                    /** @psalm-var iterable<Item>&Countable $data */
                    $data = [];
                    takesCollectionOfItems(new Collection($data));',
            ],
            'repeatedCall' => [
                'code' => '<?php
                    namespace NS;

                    use Closure;

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ArrayCollection {
                        /** @var array<TKey,TValue> */
                        private $data;

                        /** @param array<TKey,TValue> $data */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template T
                         * @param Closure(TValue):T $func
                         * @return ArrayCollection<TKey,T>
                         */
                        public function map(Closure $func) {
                            return new static(array_map($func, $this->data));
                        }
                    }

                    class Item {}
                    /**
                     * @param ArrayCollection<array-key,Item> $i
                     */
                    function takesCollectionOfItems(ArrayCollection $i): void {}

                    $c = new ArrayCollection([ new Item ]);
                    takesCollectionOfItems($c);
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));',
            ],
            'noRepeatedTypeException' => [
                'code' => '<?php
                    /** @template T as object */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string<T>
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }

                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class FooFacade
                    {
                        /**
                         * @template T as object
                         * @param  T $item
                         */
                        public function add(object $item): void
                        {
                            $foo = $this->ensureFoo([$item]);
                            $foo->add($item);
                        }

                        /**
                         * @template T as object
                         * @param  array<mixed,T> $items
                         * @return Foo<T>
                         */
                        private function ensureFoo(array $items): Foo
                        {
                            /** @var class-string<T> */
                            $type = $items[0] instanceof A ? A::class : B::class;
                            return new Foo($type);
                        }
                    }

                    class A {}
                    class B {}',
            ],
            'collectionOfClosure' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Collection {
                        /**
                         * @param Closure(TValue):bool $p
                         * @return Collection<TKey,TValue>
                         */
                        public function filter(Closure $p) {
                            return $this;
                        }
                    }
                    class I {}

                    /** @var Collection<mixed,Collection<mixed,I>> $c */
                    $c = new Collection;

                    $c->filter(
                        /** @param Collection<mixed,I> $elt */
                        function(Collection $elt): bool { return (bool) rand(0,1); }
                    );

                    $c->filter(
                        /** @param Collection<mixed,I> $elt */
                        function(Collection $elt): bool { return true; }
                    );',
            ],
            'templatedInterfaceIteration' => [
                'code' => '<?php
                    namespace NS;

                    /**
                     * @template TKey
                     * @template TValue
                     *
                     * @extends \IteratorAggregate<TKey, TValue>
                     */
                    interface ICollection extends \IteratorAggregate {
                        /** @return \Traversable<TKey,TValue> */
                        public function getIterator();
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @implements ICollection<TKey, TValue>
                     */
                    class Collection implements ICollection {
                        /** @var array<TKey, TValue> */
                        private $data;
                        /**
                         * @param array<TKey, TValue> $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }
                        /**
                         * @return \Traversable<TKey, TValue>
                         */
                        public function getIterator(): \Traversable {
                            return new \ArrayIterator($this->data);
                        }
                    }

                    $c = new Collection(["a" => 1]);

                    foreach ($c as $k => $v) { atan($v); strlen($k); }',
            ],
            'allowTemplatedIntersectionToExtend' => [
                'code' => '<?php
                    interface Foo {}

                    interface AlmostFoo {
                        /**
                         * @return Foo
                         */
                        public function makeFoo();
                    }

                    /**
                     * @template T
                     */
                    final class AlmostFooMap implements AlmostFoo {
                        /** @var T&Foo */
                        private $bar;

                        /**
                         * @param T&Foo $bar
                         */
                        public function __construct(Foo $bar)
                        {
                            $this->bar = $bar;
                        }

                        /**
                         * @return T&Foo
                         */
                        public function makeFoo()
                        {
                            return $this->bar;
                        }
                    }',
            ],
            'restrictTemplateInputWithTClassGoodInput' => [
                'code' => '<?php
                    namespace Bar;

                    /** @template T as object */
                    class Foo
                    {
                        /**
                         * @psalm-var T::class
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new A);',
            ],
            'classTemplateFunctionImplementsInterface' => [
                'code' => '<?php
                    namespace A\B;

                    interface Foo {}

                    interface IFooGetter {
                        /**
                         * @return Foo
                         */
                        public function getFoo();
                    }

                    /**
                     * @template T as Foo
                     */
                    class FooGetter implements IFooGetter {
                        /** @var T */
                        private $t;

                        /**
                         * @param T $t
                         */
                        public function __construct(Foo $t)
                        {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         */
                        public function getFoo()
                        {
                            return $this->t;
                        }
                    }

                    function passFoo(Foo $f) : Foo {
                        return (new FooGetter($f))->getFoo();
                    }',
            ],
            'getPropertyOnClass' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        public $id = 0;
                    }

                    /**
                     * @template T as Foo
                     */
                    class Collection {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }

                        /**
                         * @return class-string<T>
                         */
                        public function getType()
                        {
                           return $this->type;
                        }

                        /**
                         * @param T $object
                         */
                        public function bar(Foo $object) : void
                        {
                            if ($this->getType() !== get_class($object)) {
                                return;
                            }

                            echo $object->id;
                        }
                    }

                    class FooChild extends Foo {}

                    /** @param Collection<Foo> $c */
                    function handleCollectionOfFoo(Collection $c) : void {
                        if ($c->getType() === FooChild::class) {}
                    }',
            ],
            'getMagicPropertyOnClass' => [
                'code' => '<?php
                   class A {}

                   /**
                    * @template T as A
                    * @property ?T $x
                    */
                   class B {
                       /** @var ?T */
                       public $y;

                       public function __get() {}
                   }

                   $b = new B();
                   $b_x = $b->x;
                   $b_y = $b->y;
                ',
                'assertions' => [
                    '$b_x' => 'A|null',
                    '$b_y' => 'A|null',
                ],
            ],
            'getMagicPropertyOnThis' => [
                'code' => '<?php
                   abstract class A {}

                   class X extends A {}

                   /**
                    * @template T as A
                    * @property ?T $x
                    */
                   class B {
                       /** @var ?T */
                       public $y;

                       public function __get() {}

                       public function test(): void {
                           if ($this->x instanceof X) {}
                           if ($this->y instanceof X) {}
                       }
                   }
                ',
            ],
            'getEquateClass' => [
                'code' => '<?php
                    class Foo {
                        /** @var int */
                        public $id = 0;
                    }

                    /**
                     * @template T as Foo
                     */
                    class Container {
                        /**
                         * @var T
                         */
                        private $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct(Foo $obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @param T $object
                         */
                        public function bar(Foo $object) : void
                        {
                            if ($this->obj === $object) {}
                        }
                    }',
            ],
            'allowComparisonGetTypeResult' => [
                'code' => '<?php
                    class Foo {}

                    /**
                     * @template T as Foo
                     */
                    class Collection {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }

                        /**
                         * @return class-string<T>|null
                         */
                        public function getType()
                        {
                           return $this->type;
                        }
                    }

                    function foo(Collection $c) : void {
                        $val = $c->getType();
                        if (!$val) {}
                        if ($val) {}
                    }',
            ],
            'mixedTemplatedParamOutWithNoExtendedTemplate' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     */
                    class ValueContainer
                    {
                        /**
                         * @var TValue
                         */
                        private $v;
                        /**
                         * @param TValue $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return TValue
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     * @template TKey
                     * @template TValue
                     */
                    class KeyValueContainer extends ValueContainer
                    {
                        /**
                         * @var TKey
                         */
                        private $k;
                        /**
                         * @param TKey $k
                         * @param TValue $v
                         */
                        public function __construct($k, $v)
                        {
                            $this->k = $k;
                            parent::__construct($v);
                        }
                        /**
                         * @return TKey
                         */
                        public function getKey()
                        {
                            return $this->k;
                        }
                    }
                    $a = new KeyValueContainer("hello", 15);
                    $b = $a->getValue();',
                'assertions' => [
                    '$a' => 'KeyValueContainer<string, int>',
                    '$b' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'mixedTemplatedParamOutDifferentParamName' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     */
                    class ValueContainer
                    {
                        /**
                         * @var TValue
                         */
                        private $v;
                        /**
                         * @param TValue $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return TValue
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template TKey
                     * @template Tv
                     *
                     * @psalm-suppress MissingTemplateParam
                     */
                    class KeyValueContainer extends ValueContainer
                    {
                        /**
                         * @var TKey
                         */
                        private $k;
                        /**
                         * @param TKey $k
                         * @param Tv $v
                         */
                        public function __construct($k, $v)
                        {
                            $this->k = $k;
                            parent::__construct($v);
                        }
                        /**
                         * @return TKey
                         */
                        public function getKey()
                        {
                            return $this->k;
                        }
                    }
                    $a = new KeyValueContainer("hello", 15);
                    $b = $a->getValue();',
                'assertions' => [
                    '$a' => 'KeyValueContainer<string, int>',
                    '$b' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'doesntExtendTemplateAndDoesNotOverride' => [
                'code' => '<?php
                    /**
                     * @template T as array-key
                     */
                    abstract class User
                    {
                        /**
                         * @var T
                         */
                        private $id;
                        /**
                         * @param T $id
                         */
                        public function __construct($id)
                        {
                            $this->id = $id;
                        }
                        /**
                         * @return T
                         */
                        public function getID()
                        {
                            return $this->id;
                        }
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class AppUser extends User {}

                    $au = new AppUser(-1);
                    $id = $au->getId();',
                'assertions' => [
                    '$au' => 'AppUser',
                    '$id' => 'array-key',
                ],
            ],
            'templateTKeyedArrayValues' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Collection {
                        /**
                         * @return array{0:Collection<TKey,TValue>,1:Collection<TKey,TValue>}
                         * @psalm-suppress InvalidReturnType
                         */
                        public function partition() {}
                    }

                    /** @var Collection<int,string> $c */
                    $c = new Collection;
                    [$partA, $partB] = $c->partition();',
                'assertions' => [
                    '$partA' => 'Collection<int, string>',
                    '$partB' => 'Collection<int, string>',
                ],
            ],
            'doublyLinkedListConstructor' => [
                'code' => '<?php
                    $list = new SplDoublyLinkedList();
                    $list->add(5, "hello");
                    $list->add(5, 1);

                    /** @var SplDoublyLinkedList<string> */
                    $templated_list = new SplDoublyLinkedList();
                    $templated_list->add(5, "hello");
                    $a = $templated_list->bottom();',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'templateDefaultSimpleString' => [
                'code' => '<?php
                    /**
                     * @template T as string
                     */
                    class C {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = "hello") {
                            $this->t = $t;
                        }
                    }

                    $c = new C();',
                'assertions' => [
                    // `$c holds an unreconciled type variable minted for T at the
                    // construction site; the exact `===` form reveals it (the plain
                    // display form still renders through the bound as C<'hello'>).
                    '$c===' => "C<`_0:'hello'>",
                ],
            ],
            'SKIPPED-templateDefaultConstant' => [
                'code' => '<?php
                    const FOO = "bar";

                    /**
                     * @template T as string
                     */
                    class E {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = FOO) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    '$e===' => 'E<string(bar)>',
                ],
            ],
            'SKIPPED-templateDefaultClassMemberConstant' => [
                'code' => '<?php
                    class D {
                        const FOO = "bar";
                    }

                    /**
                     * @template T as string
                     */
                    class E {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = D::FOO) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    '$e===' => 'E<string(bar)>',
                ],
            ],
            'templateDefaultClassConstant' => [
                'code' => '<?php
                    class D {}

                    /**
                     * @template T as object
                     */
                    class E {
                        /** @var class-string<T> */
                        public $t;

                        /**
                         * @param class-string<T> $t
                         */
                        function __construct(string $t = D::class) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    // exact form reveals the construction-site type variable for T
                    '$e===' => 'E<`_0:D>',
                ],
            ],
            'allowNullablePropertyAssignment' => [
                'code' => '<?php
                    /**
                     * @template T1
                     */
                    interface I {
                        /**
                         * @return T1
                         */
                        public function get();
                    }

                    /**
                     * @template T2
                     */
                    class C {
                        /**
                         * @var T2|null
                         */
                        private $bar;

                        /**
                         * @param I<T2> $foo
                         */
                        public function __construct(I $foo) {
                            $this->bar = $foo->get();
                        }
                    }',
            ],
            'reflectionClass' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     *
                     * @property-read class-string<T> $name
                     */
                    class CustomReflectionClass {
                        /**
                         * @var class-string<T>
                         */
                        public $name;

                        /**
                         * @param T|class-string<T> $argument
                         */
                        public function __construct($argument) {
                            if (is_object($argument)) {
                                $this->name = get_class($argument);
                            } else {
                                $this->name = $argument;
                            }
                        }
                    }

                    /**
                     * @template T as object
                     * @param class-string<T> $className
                     * @return CustomReflectionClass<T>
                     */
                    function getTypeOf(string $className) {
                        return new CustomReflectionClass($className);
                    }',
            ],
            'psalmReflectionClass' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     *
                     * @psalm-property-read class-string<T> $name
                     */
                    class CustomReflectionClass {
                        /**
                         * @var class-string<T>
                         */
                        public $name;

                        /**
                         * @param T|class-string<T> $argument
                         */
                        public function __construct($argument) {
                            if (is_object($argument)) {
                                $this->name = get_class($argument);
                            } else {
                                $this->name = $argument;
                            }
                        }
                    }

                    /**
                     * @template T as object
                     * @param class-string<T> $className
                     * @return CustomReflectionClass<T>
                     */
                    function getTypeOf(string $className) {
                        return new CustomReflectionClass($className);
                    }',
            ],
            'ignoreTooManyGenericObjectArgs' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class C {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /** @param C<int> $c */
                    function takesC(C $c) : void {}

                    /**
                     * @psalm-suppress TooManyTemplateParams
                     * @var C<int, int>
                     */
                    $c = new C(5);
                    takesC($c);',
            ],
            'classTemplateUnionType' => [
                'code' => '<?php
                    /**
                     * @template T0 as int|string
                     */
                    class C {
                        /**
                         * @param T0 $t
                         */
                        public function foo($t) : void {}
                    }

                    /** @param C<int> $c */
                    function foo(C $c) : void {}

                    /** @param C<string> $c */
                    function bar(C $c) : void {}',
            ],
            'unionAsTypeReturnType' => [
                'code' => '<?php
                    /**
                     * @template TKey of ?array-key
                     * @template T
                     */
                    interface Collection
                    {
                        /**
                         * @param Closure(T=):bool $p
                         * @return Collection<TKey, T>
                         */
                        public function filter(Closure $p);
                    }',
            ],
            'converterObject' => [
                'code' => '<?php
                    /**
                     * @template I as array-key
                     * @template V
                     */
                    class Converter
                    {
                        /**
                         * @var array<I, V> $records
                         */
                        public $records;

                        /**
                         * @param array<I, V> $records
                         */
                        public function __construct(array $records) {
                            $this->records = $records;
                        }

                        /**
                         * @template Q2 as object
                         *
                         * @param Q2 $obj2
                         *
                         * @return array<I, V|Q2>
                         */
                        private function appender(object $obj2): array
                        {
                            $arr = [];
                            foreach ($this->records as $key => $obj) {
                                if (rand(0, 1)) {
                                    $obj = $obj2;
                                }
                                $arr[$key] = $obj;
                            }

                            return $arr;
                        }

                        /**
                         * @template Q1 as object
                         *
                         * @param Q1 $obj
                         *
                         * @return array<I, V|Q1>
                         */
                        public function appendProperty(object $obj): array
                        {
                            return $this->appender($obj);
                        }
                    }',
            ],
            'converterClassString' => [
                'code' => '<?php
                    /**
                     * @template I as array-key
                     * @template V
                     */
                    class Converter
                    {
                       /**
                        * @var array<I, V> $records
                        */
                       public $records;

                       /**
                        * @param array<I, V> $records
                        */
                       public function __construct(array $records) {
                           $this->records = $records;
                       }

                       /**
                         * @template Q as object
                         *
                         * @param class-string<Q> $obj
                         *
                         * @return array<I, V|Q>
                         */
                        public function appendProperty(string $obj): array
                        {
                            return $this->appender($obj);
                        }

                        /**
                         * @template Q as object
                         *
                         * @param class-string<Q> $obj2
                         *
                         * @return array<I, V|Q>
                         *
                         * @psalm-suppress MixedMethodCall
                         */
                        private function appender(string $obj2): array
                        {
                            $arr = [];
                            foreach ($this->records as $key => $obj) {
                                if (rand(0, 1)) {
                                    $obj = new $obj2;
                                }
                                $arr[$key] = $obj;
                            }

                            return $arr;
                        }
                    }',
            ],
            'allowTemplateReconciliation' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    abstract class C {
                        /** @param T $t */
                        public function foo($t): void {
                            if (!$t) {}
                            if ($t) {}
                         }
                    }',
            ],
            'allowTemplateParamsToCoerceToMinimumTypes' => [
                'code' => '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * @var array<TKey,T>
                         */
                        private $elements;

                        /**
                         * @param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }
                    }

                    /** @psalm-suppress MixedArgument */
                    $c = new ArrayCollection($GLOBALS["a"]);',
                'assertions' => [
                    '$c' => 'ArrayCollection<array-key, mixed>',
                ],
            ],
            'doNotCombineTypes' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T
                     */
                    class C {
                        /**
                         * @var T
                         */
                        private $t;

                        /**
                         * @param T $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         */
                        public function get() {
                            return $this->t;
                        }
                    }

                    /**
                     * @param C<A> $a
                     * @param C<B> $b
                     * @return C<A>|C<B>
                     */
                    function randomCollection(C $a, C $b) : C {
                        if (rand(0, 1)) {
                            return $a;
                        }

                        return $b;
                    }

                    $random_collection = randomCollection(new C(new A), new C(new B));

                    $a_or_b = $random_collection->get();',
                'assertions' => [
                    '$random_collection' => 'C<A>|C<B>',
                    '$a_or_b' => 'A|B',
                ],
            ],
            'doNotCombineTypesWhenMemoized' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T
                     */
                    class C {
                        /**
                         * @var T
                         */
                        private $t;

                        /**
                         * @param T $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         * @psalm-mutation-free
                         */
                        public function get() {
                            return $this->t;
                        }
                    }

                    /** @var C<A>|C<B> $random_collection **/
                    $a_or_b = $random_collection->get();',
                'assertions' => [
                    '$random_collection' => 'C<A>|C<B>',
                    '$a_or_b' => 'A|B',
                ],
            ],
            'inferClosureParamTypeFromContext' => [
                'code' => '<?php
                    /**
                     * @template E
                     */
                    interface Collection {
                        /**
                         * @template R
                         * @param callable(E):R $action
                         * @return Collection<R>
                         */
                        function map(callable $action): self;
                    }

                    /**
                     * @template T
                     */
                    interface Optional {
                        /**
                         * @return T
                         */
                        function get();
                    }

                    /**
                     * @param Collection<Optional<string>> $collection
                     * @return Collection<string>
                     */
                    function expandOptions(Collection $collection) : Collection {
                        return $collection->map(
                            function ($optional) {
                                return $optional->get();
                            }
                        );
                    }',
            ],
            'templateEmptyParamCoercion' => [
                'code' => '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data = []) {
                            $this->data = $data;
                        }
                    }

                    class Item {}
                    /** @psalm-param Collection<Item> $c */
                    function takesCollectionOfItems(Collection $c): void {}

                    takesCollectionOfItems(new Collection());
                    takesCollectionOfItems(new Collection([]));',
            ],
            'templatedGet' => [
                'code' => '<?php
                    /**
                     * @template P as string
                     * @template V as mixed
                     * 
                     * @psalm-no-seal-properties
                     */
                    class PropertyBag {
                        /** @var array<P,V> */
                        protected $data = [];

                        /** @param array<P,V> $data */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /** @param P $name */
                        public function __isset(string $name): bool {
                            return isset($this->data[$name]);
                        }

                        /**
                         * @param P $name
                         * @return V
                         */
                        public function __get(string $name) {
                            return $this->data[$name];
                        }
                    }

                    $p = new PropertyBag(["a" => "data for a", "b" => "data for b"]);

                    $a = $p->a;',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'templateAsArray' => [
                'code' => '<?php
                    /**
                     * @template DATA as array<string, scalar|array|object|null>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @return scalar|array|object|null
                         */
                        public function __get(string $property) {
                            return isset($this->data[$property]) ? $this->data[$property] : null;
                        }
                    }',
            ],
            'keyOfClassTemplateAcceptingIndexedAccess' => [
                'code' => '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class DataBag {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         * @param TData[K] $value
                         */
                        public function __set(string $property, $value) {
                            $this->data[$property] = $value;
                        }
                    }',
            ],
            'keyOfClassTemplateReturningIndexedAccess' => [
                'code' => '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class DataBag {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         *
                         * @return TData[K]
                         */
                        public function __get(string $property) {
                            return $this->data[$property];
                        }
                    }',
            ],
            'SKIPPED-templatedInterfaceIntersectionFirst' => [
                'code' => '<?php
                    /** @psalm-template T */
                    interface IParent {
                        /** @psalm-return T */
                        function foo();
                    }

                    interface IChild extends IParent {}

                    class C {}

                    /** @psalm-return IParent<C>&IChild */
                    function makeConcrete() : IChild {
                        return new class() implements IChild {
                            public function foo() {
                                return new C();
                            }
                        };
                    }

                    $a = makeConcrete()->foo();',
                'assertions' => [
                    '$a' => 'C',
                ],
            ],
            'templatedInterfaceIntersectionSecond' => [
                'code' => '<?php
                    /** @psalm-template T */
                    interface IParent {
                        /** @psalm-return T */
                        function foo();
                    }

                    /** @psalm-suppress MissingTemplateParam */
                    interface IChild extends IParent {}

                    class C {}

                    /** @psalm-return IChild&IParent<C> */
                    function makeConcrete() : IChild {
                        return new class() implements IChild {
                            public function foo() {
                                return new C();
                            }
                        };
                    }

                    $a = makeConcrete()->foo();',
                'assertions' => [
                    '$a' => 'C',
                ],
            ],
            'returnTemplateIntersectionGenericObjectAndTemplate' => [
                'code' => '<?php
                    /** @psalm-template Tp */
                    interface I {
                        /** @psalm-return Tp */
                        function getMe();
                    }

                    class C {}

                    /**
                     * @psalm-template T as object
                     *
                     * @psalm-param class-string<T> $className
                     *
                     * @psalm-return T&I<T>
                     *
                     * @psalm-suppress MissingTemplateParam
                     */
                    function makeConcrete(string $className) : object
                    {
                        /** @var T&I<T> */
                        return new class() extends C implements I {
                            public function getMe() {
                                return $this;
                            }
                        };
                    }

                    $a = makeConcrete(C::class);',
                'assertions' => [
                    '$a' => 'C&I<C>',
                ],
            ],
            'keyOfArrayGet' => [
                'code' => '<?php
                    /**
                     * @template DATA as array<string, int|bool>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<DATA>
                         *
                         * @param K $property
                         *
                         * @return DATA[K]
                         */
                        public function __get(string $property) {
                            return $this->data[$property];
                        }
                    }',
            ],
            'keyOfArrayRandomKey' => [
                'code' => '<?php
                    /**
                     * @template DATA as array<string, int|bool>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @return key-of<DATA>
                         */
                        abstract public function getRandomKey() : string;
                    }',
            ],
            'allowBoolTemplateCoercion' => [
                'code' => '<?php
                    /** @template T */
                    class TestPromise {
                        /** @psalm-param T $value */
                        public function __construct($value) {}
                    }

                    /** @return TestPromise<bool> */
                    function test(): TestPromise {
                        return new TestPromise(true);
                    }',
            ],
            'classTemplatedPropertyEmptyAssignment' => [
                'code' => '<?php
                    /** @template T */
                    class Foo {
                        /** @param \Closure():T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar {
                        /** @var Foo<array> */
                        private $FooArray;

                        public function __construct() {
                            $this->FooArray = new Foo(function(): array { return []; });
                        }
                    }',
            ],
            'classTemplatedPropertyAssignmentWithMoreSpecificArray' => [
                'code' => '<?php
                    /** @template T */
                    class Foo {
                        /** @param \Closure():T $closure */
                        public function __construct($closure) {}
                    }
                    class Bar {
                        /** @var Foo<array> */
                        private $FooArray;
                        public function __construct() {
                            $this->FooArray = new Foo(function(): array { return []; });
                        }
                    }',
            ],
            'insideClosureVarTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     */
                    class Foo {
                        /**
                         * @psalm-return callable(T): ?T
                         */
                        public function bar() {
                            return
                                /**
                                 * @param T $data
                                 * @return ?T
                                 */
                                function($data) {
                                    $data = rand(0, 1) ? $data : null;
                                    return $data;
                                };
                        }
                    }',
            ],
            'reflectTemplatedClass' => [
                'code' => '<?php
                    /** @template T1 of object */
                    class Foo {
                        /**
                         * @param class-string<T1> $a
                         * @psalm-return ReflectionClass<T1>
                         */
                        public function reflection(string $a) {
                            return new ReflectionClass($a);
                        }
                    }',
            ],
            'anonymousClassMustNotBreakParentTemplate' => [
                'code' => '<?php
                    /** @template T */
                    class Foo {
                        /** @psalm-var ?T */
                        private $value;

                        /** @psalm-param T $val */
                        public function set($val) : void {
                            $this->value = $val;
                            /** @psalm-suppress MissingTemplateParam */
                            new class extends Foo {};
                        }

                        /** @psalm-return ?T */
                        public function get() {
                            return $this->value;
                        }
                    }',
            ],
            'templatedInvoke' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class Foo {
                        /** @var T */
                        private $value;

                        /** @param T $val */
                        public function __construct($val) {
                            $this->value = $val;
                        }

                        /** @return T */
                        public function get() {
                            return $this->value;
                        }

                        /**
                         * @param T $val
                         * @return Foo<T>
                         */
                        public function __invoke($val) {
                            return new static($val);
                        }

                        /**
                         * @param T $val
                         * @return Foo<T>
                         */
                        public function create($val) {
                            return new static($val);
                        }
                    }

                    function bar(string $s) : string {
                        $foo = new Foo($s);
                        $bar = $foo($s);
                        return $bar->get();
                    }',
            ],
            'templatedLiteralStringReplacement' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Value {
                        /**
                         * @psalm-var T
                         */
                        private $value;

                        /**
                         * @psalm-param T $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function value() {
                            return $this->value;
                        }
                    }

                    /**
                     * @template T
                     * @psalm-param T $value
                     * @psalm-return Value<T>
                     */
                    function value($value): Value {
                        return new Value($value);
                    }

                    /**
                     * @psalm-param Value<string> $value
                     */
                    function client($value): void {}
                    client(value("awdawd"));',
            ],
            'yieldFromGenericObjectNotExtendingIterator' => [
                'code' => '<?php
                    /**
                     * @extends \ArrayObject<int, int>
                     * @psalm-capabilities read-props|write-this-props
                     */
                    class Foo extends \ArrayObject {}

                    class A {
                        /**
                         * @var Foo<string>
                         */
                        public Foo $vector;

                        /**
                         * @param Foo<string> $v
                         */
                        public function __construct(Foo $v) {
                            $this->vector = $v;
                        }

                        public function getIterator(): Iterator
                        {
                            yield from $this->vector;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['TooManyTemplateParams'],
            ],
            'coerceEmptyArrayToGeneral' => [
                'code' => '<?php
                    /** @template-covariant T */
                    class Foo
                    {
                        /** @param \Closure(string):T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar
                    {
                        /** @var Foo<array> */
                        private $FooArray;

                        public function __construct() {
                            $this->FooArray = new Foo(function(string $s): array {
                                /** @psalm-suppress MixedAssignment */
                                $json = \json_decode($s, true);

                                if (! \is_array($json)) {
                                    return [];
                                }

                                return $json;
                            });

                            takesFooArray($this->FooArray);
                        }
                    }

                    /** @param Foo<array> $_ */
                    function takesFooArray($_): void {}',
            ],
            'allowListAcceptance' => [
                'code' => '<?php
                    /** @template T */
                    class Collection
                    {
                        /** @var list<T> */
                        public $values;

                        /** @param list<T> $values */
                        function __construct(array $values)
                        {
                            $this->values = $values;
                        }
                    }

                    /** @return Collection<string> */
                    function makeStringCollection()
                    {
                        return new Collection(getStringList()); // gets typed as Collection<mixed> for some reason
                    }

                    /** @return list<string> */
                    function getStringList(): array
                    {
                        return ["foo", "baz"];
                    }',
            ],
            'allowListAcceptanceIntoArray' => [
                'code' => '<?php
                    /** @template T */
                    class Collection
                    {
                        /** @var array<T> */
                        public $values;

                        /** @param array<T> $values */
                        function __construct(array $values)
                        {
                            $this->values = $values;
                        }
                    }

                    /** @return Collection<string> */
                    function makeStringCollection()
                    {
                        return new Collection(getStringList()); // gets typed as Collection<mixed> for some reason
                    }

                    /** @return list<string> */
                    function getStringList(): array
                    {
                        return ["foo", "baz"];
                    }',
            ],
            'allowInternalNullCheck' => [
                'code' => '<?php
                    /**
                     * @template TP as ?scalar
                     */
                    class Entity
                    {
                        /**
                         * @var TP
                         */
                        private $parent;

                        /** @param TP $parent */
                        public function __construct($parent) {
                            $this->parent = $parent;
                        }

                        public function hasNoParent() : bool
                        {
                            return $this->parent === null; // So TP does contain null
                        }
                    }',
            ],
            'useMethodWithExistingGenericParam' => [
                'code' => '<?php
                    class Bar {
                        public function getFoo(): string {
                            return "foo";
                        }
                    }

                    /**
                     * @template TKey
                     * @template T
                     */
                    interface Collection {
                        /**
                         * @param Closure(T=):bool $p
                         * @return Collection<TKey, T>
                         */
                        public function filter(Closure $p);
                    }

                    /**
                     * @param Collection<int, Bar> $c
                     * @psalm-return Collection<int, Bar>
                     */
                    function filter(Collection $c, string $name) {
                        return $c->filter(
                            function (Bar $f) use ($name) {
                                return $f->getFoo() === "foo";
                            }
                        );
                    }',
            ],
            'unboundVariableIsEmptyInInstanceMethod' => [
                'code' => '<?php
                    class A {
                        /**
                         * @template TE
                         * @template TR
                         *
                         * @param TE $elt
                         * @param TR ...$elts
                         *
                         * @return TE|TR
                         */
                        public function collectInstance($elt, ...$elts) {
                            $ret = $elt;
                            foreach ($elts as $item) {
                                if (rand(0, 1)) {
                                    $ret = $item;
                                }
                            }
                            return $ret;
                        }
                    }

                    echo (new A)->collectInstance("a");',
            ],
            'unboundVariableIsEmptyInStaticMethod' => [
                'code' => '<?php
                    class A {
                        /**
                         * @template TE
                         * @template TR
                         *
                         * @param TE $elt
                         * @param TR ...$elts
                         *
                         * @return TE|TR
                         */
                        public static function collectStatic($elt, ...$elts) {
                            $ret = $elt;
                            foreach ($elts as $item) {
                                if (rand(0, 1)) {
                                    $ret = $item;
                                }
                            }
                            return $ret;
                        }
                    }

                    echo A::collectStatic("a");',
            ],
            'traversableToIterable' => [
                'code' => '<?php
                    /**
                     * @template T1 as array-key
                     * @template T2
                     *
                     * @param iterable<T1,T2> $x
                     *
                     * @return array<T1,T2>
                     */
                    function iterableToArray (iterable $x): array {
                        if (is_array($x)) {
                            return $x;
                        }
                        else {
                            return iterator_to_array($x);
                        }
                    }

                    /**
                     * @param Traversable<int, int> $t
                     * @return array<int, int>
                     */
                    function withParams(Traversable $t) : array {
                        return iterableToArray($t);
                    }',
            ],
            'templateStaticWithParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ArrayCollection {
                        /** @var list<T> */
                        private $elements;

                        /**
                         * @param list<T> $elements
                         */
                        public function __construct(array $elements) {
                            $this->elements = $elements;
                        }

                        /**
                         * @template U
                         * @param callable(T):U $callback
                         * @return static<U>
                         */
                        public function map(callable $callback) {
                            /** @psalm-suppress RedundantFunctionCall */
                            return new static(array_values(array_map($callback, $this->elements)));
                        }
                    }

                    /** @param ArrayCollection<int<0, max>> $ints */
                    function takesInts(ArrayCollection $ints) :void {}

                    /** @param ArrayCollection<int|string> $ints */
                    function takesIntsOrStrings(ArrayCollection $ints) :void {}

                    /** @return list<string> */
                    function getList() :array {return [];}

                    takesInts((new ArrayCollection(getList()))->map("strlen"));

                    /** @return ($s is "string" ? string : int) */
                    function foo(string $s) {
                        if ($s === "string") {
                            return "hello";
                        }
                        return 5;
                    }

                    takesIntsOrStrings((new ArrayCollection(getList()))->map("foo"));

                    /**
                     * @template T
                     * @extends ArrayCollection<T>
                     */
                    class LazyArrayCollection extends ArrayCollection {}',
            ],
            'weakReferenceIsTyped' => [
                'code' => '<?php
                    $e = new Exception;
                    $r = WeakReference::create($e);
                    $ex = $r->get();
                ',
                'assertions' => [ '$ex' => 'Exception|null' ],
            ],
            'weakReferenceIsCovariant' => [
                'code' => '<?php
                    /** @param WeakReference<Throwable> $_ref */
                    function acceptsThrowableRef(WeakReference $_ref): void {}

                    acceptsThrowableRef(WeakReference::create(new Exception));
                ',
            ],
            'mapTypeParams' => [
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class Map {
                        /** @var array<TKey, TValue> */
                        public $arr;

                        /** @param array<TKey, TValue> $arr */
                        function __construct(array $arr) {
                            $this->arr = $arr;
                        }
                    }

                    /**
                     * @template TInputKey as array-key
                     * @template TInputValue
                     * @param Map<TInputKey, TInputValue> $map
                     * @return Map<TInputKey, TInputValue>
                     */
                    function copyMapUsingProperty(Map $map): Map {
                        return new Map($map->arr);
                    }',
            ],
            'mapStaticClassTemplatedFromClassString' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class Base {
                        /** @return static */
                        public static function factory(): self {
                            return new static();
                        }
                    }

                    /**
                     * @template T of Base
                     * @param class-string<T> $t
                     * @return T
                     */
                    function f(string $t) {
                        return $t::factory();
                    }

                    /** @template T of Base */
                    class C {
                        /** @var class-string<T> */
                        private string $t;

                        /** @param class-string<T> $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /** @return T */
                        public function f(): Base {
                            $t = $this->t;
                            return $t::factory();
                        }
                    }',
            ],
            'uasortCallableInMethod' => [
                'code' => '<?php
                    class C {
                        /**
                         * @template T of object
                         * @psalm-param array<T> $collection
                         * @psalm-param callable(T, T): int $sorter
                         * @psalm-return array<T>
                         */
                        function order(array $collection, callable $sorter): array {
                            usort($collection, $sorter);

                            return $collection;
                        }
                    }',
            ],
            'intersectOnTOfObject' => [
                'code' => '<?php
                    /**
                     * @psalm-template TO of object
                     */
                    interface A {
                        /**
                         * @psalm-param Closure(TO&A):mixed $c
                         */
                        public function setClosure(Closure $c): void;
                    }

                    function foo(A $i) : void {
                        $i->setClosure(
                            function(A $i) : string {
                                return "hello";
                            }
                        );
                    }',
            ],
            'assertionOnTemplatedClassString' => [
                'code' => '<?php
                    class TEM {
                        /**
                         * @template Entity as object
                         * @psalm-param class-string<Entity> $type
                         * @psalm-return EQB<Entity>
                         */
                        public function createEQB(string $type) {
                            if (!class_exists($type)) {
                                throw new InvalidArgumentException();
                            }
                            return new EQB($type);
                        }
                    }

                    /**
                     * @template Entity as object
                     */
                    class EQB {
                        /**
                         * @psalm-var class-string<Entity>
                         */
                        protected $type;

                        /**
                         * @psalm-param class-string<Entity> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }
                    }',
            ],
            'createEmptyArrayCollection' => [
                'code' => '<?php
                    $a = new ArrayCollection([]);

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }',
                'assertions' => [
                    '$a' => 'ArrayCollection<never, never>',
                ],
            ],
            'newGenericBecomesPropertyTypeValidArg' => [
                'code' => '<?php
                    class B {}

                    class A {
                        /** @var ArrayCollection<int, B> */
                        public ArrayCollection $b_collection;

                        public function __construct() {
                            $this->b_collection = new ArrayCollection([]);
                            $this->b_collection->add(5, new B());
                        }
                    }

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }',
            ],
            'allowPropertyCoercion' => [
                'code' => '<?php
                    class Test
                    {
                        /**
                         * @var ArrayCollection<int, DateTime>
                         */
                        private $c;

                        public function __construct()
                        {
                            $this->c = new ArrayCollection([new DateTime()]);
                            $this->c->filter(function (DateTime $dt): bool {
                                return $dt === $dt;
                            });
                        }
                    }

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements;

                        /**
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @psalm-param Closure(T=):bool $p
                         * @psalm-return self<TKey, T>
                         */
                        public function filter(Closure $p)
                        {
                            return $this;
                        }
                    }',
            ],
            'unionClassStringInferenceAndDefaultEmptyArray' => [
                'code' => '<?php
                    class A{}

                    $packages = Collection::fromClassString(A::class);

                    /**
                     * @template T
                     */
                    class Collection{
                        /** @var array<T> $items */
                        protected $items = [];

                        /**
                         * @param array<string, T> $items
                         */
                        public function __construct(array $items = [])
                        {
                            $this->items = $items;
                        }

                        /**
                         * @template C as object
                         * @param class-string<C> $classString
                         * @param array<string, C> $elements
                         * @return Collection<C>
                         */
                        public static function fromClassString(string $classString, array $elements = []) : Collection
                        {
                            return new Collection($elements);
                        }
                    }',
                'assertions' => [
                    '$packages' => 'Collection<A>',
                ],
            ],
            'assertSameOnTemplatedProperty' => [
                'code' => '<?php
                    /** @template E as object */
                    final class Box
                    {
                        /** @var E */
                        private $contents;

                        /** @param E $contents */
                        public function __construct(object $contents)
                        {
                            $this->contents = $contents;
                        }

                        /** @param E $thing */
                        public function contains(object $thing) : bool
                        {
                            if ($this->contents !== $thing) {
                                return false;
                            }

                            return true;
                        }
                    }',
            ],
            'assertNotNullOnTemplatedProperty' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     */
                    final class A {
                        /**
                         * @psalm-var ?callable(T): bool
                         */
                        public $filter;
                    }

                    /** @psalm-var A<A> */
                    $a = new A();

                    if (null !== $a->filter) {}',
            ],
            'setTemplatedPropertyOutsideClass' => [
                'code' => '<?php
                    /**
                     * @template TValue as scalar
                     */
                    class Watcher {
                        /**
                         * @psalm-var TValue
                         */
                        public $value;

                        /**
                         * @psalm-param TValue $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /** @psalm-var Watcher<int> $watcher */
                    $watcher = new Watcher(0);
                    $watcher->value = 0;',
            ],
            'callableAsClassStringArray' => [
                'code' => '<?php
                    abstract class Id
                    {
                        protected string $id;

                        final protected function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        /**
                         * @return static
                         */
                        final public static function fromString(string $id): self
                        {
                            return new static($id);
                        }
                    }

                    /**
                     * @template T of Id
                     */
                    final class Ids
                    {
                        /**
                         * @psalm-var list<T>
                         */
                        private array $ids;

                        /**
                         * @psalm-param list<T> $ids
                         */
                        private function __construct(array $ids)
                        {
                            $this->ids = $ids;
                        }

                        /**
                         * @template T1 of Id
                         * @psalm-param T1 $class
                         * @psalm-param list<string> $ids
                         * @psalm-return self<T1>
                         */
                        public static function fromObjects(Id $class, array $ids): self
                        {
                            return new self(array_map([$class, "fromString"], $ids));
                        }

                        /**
                         * @template T1 of Id
                         * @psalm-param class-string<T1> $class
                         * @psalm-param list<string> $ids
                         * @psalm-return self<T1>
                         */
                        public static function fromStrings(string $class, array $ids): self
                        {
                            return new self(array_map([$class, "fromString"], $ids));
                        }
                    }',
            ],
            'doNotForgetAssertion' => [
                'code' => '<?php

                class a {
                    public ?int $expr = null;
                }

                /**
                 * @template T as int
                 */
                class b
                {
                    public function test(
                        a $_
                    ): void {
                    }
                }

                class c
                {
                    public static function analyze(
                        a $container,
                        b $test,
                    ): int {
                        $test->test($container);

                        if ($container->expr) {
                            if (random_int(0, 1)) {
                                self::test(
                                    $container,
                                );
                            }
                            return $container->expr;
                        }
                        return 0;
                    }

                    private static function test(
                        a $_,
                    ): void {
                    }
                }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'noCrashTemplateInsideGenerator' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @template T
                     */
                    final class Set
                    {
                        /** @var \Iterator<T> */
                        private \Iterator $values;

                        /**
                         * @param \Iterator<T> $values
                         */
                        public function __construct(\Iterator $values)
                        {
                            $this->values = $values;
                        }

                        /**
                         * @param T $element
                         *
                         * @return self<T>
                         */
                        public function __invoke($element): self
                        {
                            return new self(
                                (
                                    function($values, $element): \Generator {
                                        /** @var T $value */
                                        foreach ($values as $value) {
                                            yield $value;
                                        }

                                        yield $element;
                                    }
                                )($this->values, $element),
                            );
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingClosureParamType'],
            ],
            'templatedPropertyAllowsNull' => [
                'code' => '<?php
                    /**
                     * @template TKey as string|null
                     */
                    class A {
                        /** @var TKey  */
                        public $key;

                        /**
                         * @param TKey $key
                         */
                        public function __construct(?string $key)
                        {
                            $this->key = $key;
                        }
                    }',
            ],
            'templatePropertyWithoutParams' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     */
                    class Batch {
                        /**
                         * @var iterable<T>
                         */
                        public iterable $objects = [];

                        /**
                         * @var callable(T): void
                         */
                        public $onEach;

                        public function __construct() {
                            $this->onEach = function (): void {};
                        }
                    }

                    function handle(Batch $message, object $o): void {
                        $fn = $message->onEach;
                        $fn($o);
                    }',
            ],
            'changePropertyTypeOfTemplate' => [
                'code' => '<?php
                    class A {
                        public int $x = 0;
                    }

                    /**
                     * @template T as A
                     * @param T $obj
                     * @param-out T $obj
                     */
                    function foo(A &$obj): void {
                        $obj->x = 1;
                    }',
            ],
            'multipleMatchingObjectsInUnion' => [
                'code' => '<?php
                    /** @template-covariant T */
                    interface Container {
                        /** @return T */
                        public function get();
                    }

                    /**
                     * @template T
                     * @param array<Container<T>> $containers
                     * @return T
                     */
                    function unwrap(array $containers) {
                        return array_map(
                            fn($container) => $container->get(),
                            $containers
                        )[0];
                    }

                    /**
                     * @param array<Container<int>|Container<string>> $typed_containers
                     */
                    function takesDifferentTypes(array $typed_containers) : void {
                        $ret = unwrap($typed_containers);

                        if (is_string($ret)) {}
                        if (is_int($ret)) {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'templateWithLateResolvedType' => [
                'code' => '<?php
                    /**
                     * @template A of Enum::TYPE_*
                     */
                    class Foo {}

                    class Enum
                    {
                        const TYPE_ONE = 1;
                        const TYPE_TWO = 2;
                    }

                    /** @var Foo<Enum::TYPE_ONE> $foo */
                    $foo = new Foo();',
            ],
            'SKIPPED-extendedPropertyTypeParameterised' => [
                'code' => '<?php
                    namespace App;

                    use DateTimeImmutable;
                    use Ds\Map;

                    abstract class Z
                    {
                        public function test(): void
                        {
                            $map = $this->createMap();

                            $date = $map->get("test");

                            echo $date->format("Y");
                        }

                        /**
                         * @return Map<string, DateTimeImmutable>
                         */
                        abstract protected function createMap(): Map;
                    }',
            ],
            'looseEquality' => [
                'code' => '<?php

                    /**
                     * @psalm-immutable
                     * @template T of self::READ_UNCOMMITTED|self::READ_COMMITTED|self::REPEATABLE_READ|self::SERIALIZABLE
                     */
                    final class TransactionIsolationLevel
                    {
                        private const READ_UNCOMMITTED = "read uncommitted";
                        private const READ_COMMITTED = "read committed";
                        private const REPEATABLE_READ = "repeatable read";
                        private const SERIALIZABLE = "serializable";

                        /**
                         * @psalm-var T $level
                         */
                        private string $level;

                        /**
                         * @psalm-param T $level
                         */
                        private function __construct(string $level)
                        {
                            $this->level = $level;
                        }

                        /**
                         * @psalm-return self<self::READ_UNCOMMITTED>
                         */
                        public static function readUncommitted(): self
                        {
                            return new self(self::READ_UNCOMMITTED);
                        }

                        /**
                         * @psalm-return T
                         */
                        public function toString(): string
                        {
                            return $this->level;
                        }

                        /**
                         * @psalm-template TResult
                         * @psalm-param pure-callable(self::READ_UNCOMMITTED): TResult $readUncommitted
                         * @psalm-return TResult
                         */
                        public function resolve(callable $readUncommitted) {
                            if ($this->level == self::READ_UNCOMMITTED) {
                                return $readUncommitted($this->level);
                            }

                            throw new \LogicException("bad");
                        }
                    }',
            ],
            'narrowTemplateTypeWithInstanceof' => [
                'code' => '<?php
                    class Foo {}
                    class Bar {}

                    /** @template FooOrBarOrNull of Foo|Bar|null */
                    class Resolved
                    {
                        /**
                         * @var FooOrBarOrNull
                         */
                        private $entity = null;

                        /**
                         * @psalm-param FooOrBarOrNull $qux
                         */
                        public function __construct(?object $qux)
                        {
                            if ($qux instanceof Foo) {
                                $this->entity = $qux;
                            }
                        }
                    }',
            ],
            'flippedParamsMethodInside' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @return Traversable<A, B> */
                        public abstract function getTraversable() : Traversable;

                        /**
                         * @param Foo<B, A> $flipped
                         * @return Traversable<B, A>
                         */
                        public function getFlippedTraversable(Foo $flipped): Traversable
                        {
                            return $flipped->getTraversable();
                        }
                    }',
            ],
            'flippedParamsMethodOutside' => [
                'code' => '<?php
                    /**
                     * @template B
                     * @template A
                     * @param Foo<B, A> $flipped
                     * @return Traversable<B, A>
                     */
                    function getFlippedTraversable(Foo $flipped): Traversable {
                        return $flipped->getTraversable();
                    }

                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @return Traversable<A, B> */
                        public abstract function getTraversable() : Traversable;
                    }',
            ],
            'flippedParamsPropertyInside' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @var Traversable<A, B> */
                        public $traversable;

                        /**
                         * @param Foo<B, A> $flipped
                         * @return Traversable<B, A>
                         */
                        public function getFlippedTraversable(Foo $flipped): Traversable
                        {
                            return $flipped->traversable;
                        }
                    }',
            ],
            'flippedParamsPropertyOutside' => [
                'code' => '<?php
                    /**
                     * @template B
                     * @template A
                     * @param Foo<B, A> $flipped
                     * @return Traversable<B, A>
                     */
                    function getFlippedTraversable(Foo $flipped): Traversable {
                        return $flipped->traversable;
                    }

                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @var Traversable<A, B> */
                        public $traversable;
                    }',
            ],
            'simpleTemplate' => [
                'code' => '<?php
                    /** @template T */
                    interface F {}

                    /** @param F<mixed> $f */
                    function takesFMixed(F $f) : void {}

                    function sendsF(F $f) : void {
                        takesFMixed($f);
                    }',
            ],
            'arrayCollectionMapInternal' => [
                'code' => '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ArrayCollection
                    {
                        /** @psalm-var array<TKey,T> */
                        private $elements;

                        /** @psalm-param array<TKey,T> $elements */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @template TNewKey of array-key
                         * @template TNew
                         * @psalm-param array<TNewKey, TNew> $elements
                         * @psalm-return static<TNewKey, TNew>
                         */
                        protected static function createFrom(array $elements)
                        {
                            return new static($elements);
                        }

                        /**
                         * @psalm-template U
                         * @psalm-param Closure(T=):U $func
                         * @psalm-return static<TKey, U>
                         */
                        public function map(Closure $func)
                        {
                            $new_elements = array_map($func, $this->elements);
                            return self::createFrom($new_elements);
                        }
                    }',
            ],
            'arrayCollectionMapExternal' => [
                'code' => '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     * @psalm-consistent-constructor
                     */
                    class ArrayCollection
                    {
                        /** @psalm-var array<TKey,T> */
                        private $elements;

                        /** @psalm-param array<TKey,T> $elements */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @psalm-template U
                         * @psalm-param Closure(T=):U $func
                         * @psalm-return ArrayCollection<TKey, U>
                         */
                        public function map(Closure $func)
                        {
                            $new_elements = array_map($func, $this->elements);
                            return Creator::createFrom($new_elements);
                        }
                    }

                    class Creator {
                        /**
                         * @template TNewKey of array-key
                         * @template TNew
                         * @psalm-param array<TNewKey, TNew> $elements
                         * @psalm-return ArrayCollection<TNewKey, TNew>
                         */
                        public static function createFrom(array $elements) {
                            return new ArrayCollection($elements);
                        }
                    }',
            ],
            'templateWithClassConstants' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T of self::A|self::B|self::C
                     */
                    final class Foo
                    {
                        public const A = "aa";
                        public const B = "bb";
                        public const C = "cc";

                        /**
                         * @psalm-var T $level
                         */
                        private string $level;

                        /**
                         * @psalm-param T $level
                         */
                        public function __construct(string $level)
                        {
                            $this->level = $level;
                        }
                    }

                    /**
                     * @psalm-return Foo<Foo::A>
                     */
                    function getFooA(): Foo {
                        return new Foo(Foo::A);
                    }',
            ],
            'callTemplatedMethodOnSameClass' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    class Mapper {
                        /**
                         * @param T $e
                         * @return T
                         */
                        public function foo($e) {
                            return $e;
                        }

                        /**
                         * @param T $e
                         * @return T
                         */
                        public function passthru($e) {
                            return $this->foo($e);
                        }
                    }',
            ],
            'templatedStaticUnion' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @psalm-consistent-templates
                     */
                    abstract class A {
                        /**
                          * @var T
                          */
                        private $v;

                        /**
                          * @param T $v
                          */
                        final public function __construct($v) {
                            $this->v = $v;
                        }

                        /**
                          * @return static<T>
                          */
                        public function foo(): A {
                            if (rand(0, 1)) {
                                return new static($this->v);
                            } else {
                                return new static($this->v);
                            }
                        }
                    }',
            ],
            'templatedTypeWithLimitGoesIntoTemplatedType' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    function takesA(A $a) : void {}

                    function foo(A $a) : void {
                        takesA($a);
                    }',
            ],
            'templateIsAComplexMultilineType' => [
                'code' => '<?php
                /**
                 * @template T of array{
                 *    a: string,
                 *    b: int
                 * }
                 */
                class MyContainer {
                    /** @var T */
                    private $value;
                    /** @param T $value */
                    public function __construct($value) {
                        $this->value = $value;
                    }
                    /** @return T */
                    public function getValue() {
                      return $this->value;
                    }
                }',
            ],
            'newWithoutInferredTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-template T2 of object
                     */
                    final class Foo {}

                    $f = new Foo();',
                'assertions' => [
                    '$f' => 'Foo<object>',
                ],
            ],
            'PHP80-weakmapIsGeneric' => [
                'code' => '<?php
                    /** @param WeakMap<Throwable,int> $wm */
                    function isCountable(WeakMap $wm): int {
                        return count($wm);
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     * @return array{Throwable,int}
                     */
                    function isTraverable(WeakMap $wm): array {
                        foreach ($wm as $k => $v) {
                            return [$k, $v];
                        }
                        throw new RuntimeException;
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     * @return Traversable<Throwable,int>
                     */
                    function hasAggregateIterator(WeakMap $wm): Traversable {
                        return $wm->getIterator();
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     */
                    function readsLikeAnArray(WeakMap $wm): int {
                        $ex = new Exception;
                        if (isset($wm[$ex])) {
                            return $wm[$ex];
                        }
                        throw new RuntimeException;
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     */
                    function writesLikeAnArray(WeakMap $wm): void {
                        $ex = new Exception;
                        $wm[$ex] = 42;
                    }
                ',
            ],
            'combineTwoTemplatedArrays' => [
                'code' => '<?php
                    /** @template T */
                    class Option
                    {
                        /** @param T $v */
                        public function __construct(private $v) {}

                        /**
                         * @template E
                         * @param E $else
                         * @return T|E
                         */
                        public function getOrElse($else)
                        {
                           return rand(0, 1) === 1 ? $this->v : $else;
                        }
                    }

                    $opt = new Option([1, 3]);

                    $b = $opt->getOrElse([2, 4])[0];',
                'assertions' => [
                    '$b===' => '1|2',
                ],
            ],
            'generaliseTemplatedString' => [
                'code' => '<?php
                    /** @template TData */
                    class Container {
                        /** @var TData */
                        public $data;

                        /** @param TData $data */
                        public function __construct($data) {
                            $this->data = $data;
                        }
                    }

                    /** @param Container<string> $r */
                    function takesContainer(Container $r): void {
                        $r->data = "David";
                    }

                    $me = new Container("Matthew");

                    takesContainer($me);

                    if ($me->data === "David") {}',
            ],
            'generaliseTemplatedArray' => [
                'code' => '<?php
                    /** @template TData */
                    class Container {
                        /** @var TData */
                        public $data;

                        /** @param TData $data */
                        public function __construct($data) {
                            $this->data = $data;
                        }
                    }

                    /** @param Container<array{name: string}> $r */
                    function takesContainer(Container $r): void {
                        $r->data = ["name" => "David"];
                    }

                    $me = new Container(["name" => "Matthew"]);

                    takesContainer($me);

                    if ($me->data["name"] === "David") {}',
            ],
            'allowCovariantBoundsMismatchSameContainers' => [
                'code' => '<?php
                    /**
                     * @param Collection<Dog> $c
                     * @param Collection<Cat> $d
                     */
                    function bar(Collection $c, Collection $d): Dog|Cat {
                        return foo($c, $d);
                    }

                    /** @template-covariant T of object */
                    interface Collection {
                        /** @return T */
                        public function get(): object;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param Collection<T> $c
                     * @param Collection<T> $d
                     * @return T
                     */
                    function foo(Collection $c, Collection $d): object {
                        return rand(0, 1) ? $c->get() : $d->get();
                    }',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '8.0',
            ],
            'allowCovariantBoundsMismatchDifferentContainers' => [
                'code' => '<?php
                    /**
                     * @param Collection1<Dog> $c
                     * @param Collection2<Cat> $d
                     */
                    function bar(Collection1 $c, Collection2 $d): Dog|Cat {
                        return foo($c, $d);
                    }

                    /** @template-covariant T of object */
                    interface Collection1 {
                        /** @return T */
                        public function get(): object;
                    }

                    /** @template-covariant T of object */
                    interface Collection2 {
                        /** @return T */
                        public function get(): object;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param Collection1<T> $c
                     * @param Collection2<T> $d
                     * @return T
                     */
                    function foo(Collection1 $c, Collection2 $d): object {
                        return rand(0, 1) ? $c->get() : $d->get();
                    }',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '8.0',
            ],
            'allowCovariantBoundsMismatchContainerAndObject' => [
                'code' => '<?php
                    /**
                     * @param Collection<Cat> $d
                     */
                    function bar(Dog $c, Collection $d): Dog|Cat {
                        $animal = foo($c, $d);
                        if ($animal instanceof Dog) {}
                        if ($animal instanceof Cat) {}
                        return $animal;
                    }

                    /** @template-covariant T of object */
                    interface Collection {
                        /** @return T */
                        public function get(): object;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param T $c
                     * @param Collection<T> $d
                     * @return T
                     */
                    function foo(object $c, Collection $d): object {
                        return rand(0, 1) ? $c : $d->get();
                    }',
                    'assertions' => [],
                    'ignored_issues' => [],
                    'php_version' => '8.0',
            ],
            'allowCompatibleGenerics' => [
                'code' => '<?php
                    /** @template T of object */
                    interface A {}

                    /** @template T of object */
                    interface B {}

                    /**
                     * @template T of object
                     * @param A<T> $a
                     * @param B<T> $b
                     */
                    function foo(A $a, B $b): void {}

                    /**
                     * @param A<stdClass> $a
                     * @param B<stdClass> $b
                     */
                    function bar(A $a, B $b): void {
                        foo($a, $b);
                    }',
            ],
            'templateOnDocblockMethod' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @method T get()
                     * @method void set(T $value)
                     */
                    class Container
                    {
                        public function __call(string $name, array $args) {}
                    }

                    class A {}
                    function foo(A $a): void {}

                    /** @var Container<A> $container */
                    $container = new Container();
                    $container->set(new A());
                    foo($container->get());
                ',
            ],
            'templateOnDocblockMethodParamsGlobalNamespace' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @method $this plain(TValue $v)
                     * @method $this through(callable(TValue): mixed $callback)
                     * @method $this throughClosure(\Closure(TValue): mixed $callback)
                     */
                    interface I {}

                    /** @param I<string> $p */
                    function f(I $p): void {
                        $p->plain("x");
                        $p->through(fn (string $s): int => 1);
                        $p->throughClosure(fn (string $s): int => 1);
                    }
                ',
            ],
            'templateOnDocblockMethodParamsNamespaced' => [
                'code' => '<?php
                    namespace App\Ns;

                    /**
                     * @template TValue
                     * @method $this plain(TValue $v)
                     * @method $this through(callable(TValue): mixed $callback)
                     * @method $this throughClosure(\Closure(TValue): mixed $callback)
                     * @method $this acceptNullable(?TValue $v = null)
                     * @method $this afterComparison(bool $flag = 1 < 2, ?TValue $v = null)
                     */
                    interface I {}

                    /** @param I<string> $p */
                    function f(I $p): void {
                        $p->plain("x");
                        $p->through(fn (string $s): int => 1);
                        $p->throughClosure(fn (string $s): int => 1);
                        $p->acceptNullable(null);
                        $p->afterComparison(true, "x");
                    }
                ',
            ],
            'classStringMapParamInDocblockMethodOfNamespacedClass' => [
                'code' => '<?php
                    namespace App\Ns;

                    /**
                     * @template T of \stdClass
                     * @method void put(class-string-map<T, T> $map)
                     */
                    interface I {}

                    /** @param I<\stdClass> $p */
                    function f(I $p): void {
                        echo $p::class;
                    }
                ',
            ],
            'explicitClassNamedLikeTemplateInDocblockMethodParam' => [
                'code' => '<?php
                    namespace App\Ns;

                    class TValue {}

                    /**
                     * @template TValue
                     * @method $this keep(\App\Ns\TValue $v)
                     */
                    interface I {}

                    /** @param I<string> $p */
                    function f(I $p): void {
                        $p->keep(new TValue());
                    }
                ',
            ],
            'importAliasNamedLikeClassTemplateInDocblockMethodReturn' => [
                'code' => '<?php
                    namespace Vendor {
                        class Thing {}
                    }

                    namespace App\Ns {
                        use Vendor\Thing as TValue;

                        /**
                         * @template TValue
                         * @method get(): TValue
                         */
                        interface Box {}

                        /** @param Box<int> $box */
                        function f(Box $box): \Vendor\Thing {
                            return $box->get();
                        }
                    }
                ',
            ],
            'classTemplateShadowsImportAliasInDocblockMethodParam' => [
                'code' => '<?php
                    namespace Vendor {
                        class Thing {}
                    }

                    namespace App\Ns {
                        use Vendor\Thing as TValue;

                        /**
                         * @template TValue
                         * @method void put(TValue $v)
                         */
                        interface Box {}

                        /** @param Box<int> $box */
                        function f(Box $box): void {
                            $box->put(1);
                        }
                    }
                ',
            ],
            'templateOnDocblockMethodOnInterface' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @method T get()
                     * @method void set(T $value)
                     */
                    interface Container
                    {
                    }

                    class A {}
                    function foo(A $a): void {}

                    /** @var Container<A> $container */
                    $container->set(new A());
                    foo($container->get());
                ',
            ],
            'refineTemplateTypeOfUnion' => [
                'code' => '<?php
                    /** @psalm-template T as One|Two|Three */
                    class A {
                        /** @param T $t */
                        public function __construct(
                            private object $t
                        ) {}

                        public function foo(): void {
                            if ($this->t instanceof One || $this->t instanceof Two) {}
                        }
                    }

                    final class One {}
                    final class Two {}
                    final class Three {}',
            ],
            'refineTemplateTypeOfUnionMoreComplex' => [
                'code' => '<?php
                    /** @psalm-template T as One|Two|Three */
                    class A {
                        /** @param T $t */
                        public function __construct(
                            private object $t
                        ) {}

                        public function foo(): void {
                            if ($this->t instanceof One && rand(0, 1)) {}

                            if ($this->t instanceof Two) {}
                        }
                    }

                    final class One {}
                    final class Two {}
                    final class Three {}',
            ],
            'issue7825' => [
                'code' => '<?php
                    interface Stub {}
                    interface ProxyQueryInterface {}
                    class MockObject {}
                    /** @phpstan-template T of ProxyQueryInterface */
                    interface PagerInterface {}
                    /** @phpstan-template T of ProxyQueryInterface */
                    class Datagrid
                    {
                        /** @var T */
                        private $query;

                        /** @var PagerInterface<T> */
                        private $pager;

                        /**
                         * @phpstan-param T                 $query
                         * @phpstan-param PagerInterface<T> $pager
                         */
                        public function __construct(
                            ProxyQueryInterface $query,
                            PagerInterface $pager
                        ) {
                            $this->pager = $pager;
                            $this->query = $query;
                        }
                    }
                    interface FormBuilderInterface {}
                    /** @template T of FieldDescriptionInterface */
                    class FieldDescriptionCollection {}
                    interface FieldDescriptionInterface {}
                    abstract class Test
                    {
                        /** @var Datagrid<ProxyQueryInterface&Stub> */
                        private Datagrid $datagrid;

                        /** @var PagerInterface<ProxyQueryInterface&Stub>&MockObject */
                        private $pager;

                        /** @var ProxyQueryInterface&Stub */
                        private $query;

                        /** @var FieldDescriptionCollection<FieldDescriptionInterface> */
                        private FieldDescriptionCollection $columns;

                        private FormBuilderInterface $formBuilder;

                        /**
                         * @psalm-template RealInstanceType of object
                         * @psalm-param class-string<RealInstanceType> $originalClassName
                         * @psalm-return MockObject&RealInstanceType
                         */
                        abstract protected function createMock(string $originalClassName): MockObject;

                        /**
                         * @psalm-template RealInstanceType of object
                         * @psalm-param    class-string<RealInstanceType> $originalClassName
                         * @psalm-return   Stub&RealInstanceType
                         */
                        abstract protected function createStub(string $originalClassName): Stub;

                        protected function setUp(): void
                        {
                            $this->query = $this->createStub(ProxyQueryInterface::class);
                            $this->columns = new FieldDescriptionCollection();

                            /** @var PagerInterface<ProxyQueryInterface&Stub>&MockObject $pager */
                            $pager = $this->createMock(PagerInterface::class);
                            $this->pager = $pager;
                            $this->datagrid = new Datagrid($this->query, $pager);
                        }
                    }',
            ],
            'complexTypes' => [
                'code' => '<?php

                /**
                 * @template T
                 */
                class Future {
                    /**
                     * @param T $v
                     */
                    public function __construct(private $v) {}
                    /** @return T */
                    public function get() { return $this->v; }
                }


                /**
                 * @template TTObject
                 *
                 * @extends Future<ArrayObject<int, TTObject>>
                 */
                class FutureB extends Future {
                    /** @param TTObject $data */
                    public function __construct($data) { parent::__construct(new ArrayObject([$data])); }
                }

                $a = new FutureB(123);

                $r = $a->get();',
                'assertions' => [
                    '$a===' => 'FutureB<123>',
                    '$r===' => 'ArrayObject<int, 123>',
                ],
            ],
            'return TemplatedClass<static>' => [
                'code' => '<?php

                    /**
                     * @template-covariant A
                     * @psalm-immutable
                     */
                    final class Maybe
                    {
                        /**
                         * @param null|A $value
                         */
                        public function __construct(private $value = null) {}

                        /**
                         * @template B
                         * @param B $value
                         * @return Maybe<B>
                         *
                         * @psalm-pure
                         */
                        public static function just($value): self
                        {
                            return new self($value);
                        }
                    }

                    abstract class Test
                    {
                        final private function __construct() {}

                        /** @return Maybe<static> */
                        final public static function create(): Maybe
                        {
                            return Maybe::just(new static());
                        }
                    }',
            ],
            'return list<static> created in a static method of another class' => [
                'code' => '<?php

                    final class Lister
                    {
                        /**
                         * @template B
                         * @param B $value
                         * @return list<B>
                         *
                         * @psalm-pure
                         */
                        public static function mklist($value): array
                        {
                            return [ $value ];
                        }
                    }

                    abstract class Test
                    {
                        final private function __construct() {}

                        /** @return list<static> */
                        final public static function create(): array
                        {
                            return Lister::mklist(new static());
                        }
                    }',
            ],
            'use TemplatedClass<static> as an intermediate variable inside a method' => [
                'code' => '<?php

                    /**
                     * @template-covariant A
                     * @psalm-immutable
                     */
                    final class Maybe
                    {
                        /**
                         * @param A $value
                         */
                        public function __construct(public $value) {}

                        /**
                         * @template B
                         * @param B $value
                         * @return Maybe<B>
                         *
                         * @psalm-pure
                         */
                        public static function just($value): self
                        {
                            return new self($value);
                        }
                    }

                    abstract class Test
                    {
                        final private function __construct() {}

                        final public static function create(): static
                        {
                            $maybe = Maybe::just(new static());
                            return $maybe->value;
                        }
                    }',
            ],
            'static is the return type of an analyzed static method' => [
                'code' => '<?php

                    abstract class A
                    {
                    }

                    final class B extends A
                    {
                        public static function create(): static
                        {
                            return new self();
                        }
                    }

                    final class Service
                    {
                        public function do(): void
                        {
                            $this->acceptA(B::create());
                        }

                        private function acceptA(A $_a): void
                        {
                        }
                    }',
            ],
            'undefined class in function dockblock' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress UndefinedDocblockClass
                     *
                     * @param DoesNotExist<int> $baz
                     */
                    function foobar(DoesNotExist $baz): void {}

                    /**
                     * @psalm-suppress UndefinedDocblockClass, UndefinedClass
                     * @var DoesNotExist
                     */
                    $baz = new DoesNotExist();
                    foobar($baz);',
            ],
            'promoted property with template' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class A {
                        public function __construct(
                            /** @var T */
                            public mixed $t
                        ) {}
                    }

                    $a = new A(5);
                    $t = $a->t;
                ',
                'assertions' => [
                    '$a' => 'A<int>',
                    '$t' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'template of simple type with additional comment without dot' => [
                'code' => '<?php
                    /**
                     * @psalm-template T of string
                     *
                     * lorem ipsum
                     */
                    class Foo {
                        /** @psalm-var T */
                        public string $t;

                        /** @psalm-param T $t */
                        public function __construct(string $t) {
                            $this->t = $t;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function t(): string {
                            return $this->t;
                        }
                    }
                    $t = (new Foo(\'\'))->t();
                ',
                'assertions' => [
                    '$t===' => '\'\'',
                ],
            ],
            'template of simple type with additional comment with dot' => [
                'code' => '<?php
                    /**
                     * @psalm-template T of string
                     *
                     * lorem ipsum.
                     */
                    class Foo {
                        /** @psalm-var T */
                        public string $t;

                        /** @psalm-param T $t */
                        public function __construct(string $t) {
                            $this->t = $t;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function t(): string {
                            return $this->t;
                        }
                    }
                    $t = (new Foo(\'\'))->t();
                ',
                'assertions' => [
                    '$t===' => '\'\'',
                ],
            ],
            'mixedAssignment' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Foo {
                        /** @psalm-var T */
                        protected $value;

                        /** @psalm-param T $value */
                        public function __construct($value)
                        {
                            /** @var T */
                            $value = $this->normalize($value);
                            $this->value = $value;
                        }

                        /**
                         * @psalm-param T $value
                         * @psalm-return T
                         */
                        protected function normalize($value)
                        {
                            return $value;
                        }
                    }
                ',
            ],
            'typesOrderInsideImplementsNotMatter' => [
                'code' => '<?php
                    /** @template T */
                    interface I {}

                    /**
                     * @template T
                     * @extends I<T>
                     */
                    interface ExtendedI extends I {}

                    /**
                     * @template T
                     * @implements ExtendedI<T|null>
                     */
                    final class TWithNull implements ExtendedI
                    {
                        /** @param T $_value */
                        public function __construct($_value) {}
                    }

                    /**
                     * @template T
                     * @implements ExtendedI<null|T>
                     */
                    final class NullWithT implements ExtendedI
                    {
                        /** @param T $_value */
                        public function __construct($_value) {}
                    }

                    /** @param I<null|int> $_type */
                    function nullWithInt(I $_type): void {}

                    /** @param I<int|null> $_type */
                    function intWithNull(I $_type): void {}

                    nullWithInt(new TWithNull(1));
                    nullWithInt(new NullWithT(1));
                    intWithNull(new TWithNull(1));
                    intWithNull(new NullWithT(1));',
            ],
            'intersectParentTemplateReturnWithConcreteChildReturn' => [
                'code' => '<?php
                    /**  @template T  */
                    interface Aggregator
                    {
                        /**
                         * @psalm-param T ...$values
                         * @psalm-return T
                         */
                        public function aggregate(...$values): mixed;
                    }

                    /** @implements Aggregator<int|float|null> */
                    final class AverageAggregator implements Aggregator
                    {
                        public function aggregate(...$values): null|int|float
                        {
                            if (!$values) {
                                return null;
                            }
                            return array_sum($values) / count($values);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'preventTemplatedCorrectionBeingWrittenTo' => [
                'code' => '<?php
                    namespace NS;

                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class ArrayCollection {
                        /** @var array<TKey,TValue> */
                        private $data;

                        /** @param array<TKey,TValue> $data */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @param TKey $key
                         * @param TValue $value
                         */
                        public function addItem($key, $value) : void {
                            $this->data[$key] = $value;
                        }
                    }

                    class Item {}
                    class SubItem extends Item {}
                    class OtherSubItem extends Item {}

                    /**
                     * @param ArrayCollection<int,Item> $i
                     */
                    function takesCollectionOfItems(ArrayCollection $i): void {
                       $i->addItem(10, new OtherSubItem);
                    }

                    $subitem_collection = new ArrayCollection([ new SubItem ]);

                    takesCollectionOfItems($subitem_collection);',
            ],
            'noCrashTemplatedClosure' => [
                'code' => '<?php
                    /**
                     * @template TCallback as Closure():string
                     */
                    class A {
                        /** @var TCallback */
                        private $callback;

                        /** @param TCallback $callback */
                        public function __construct(Closure $callback) {
                            $this->callback = $callback;
                        }

                        /** @param TCallback $callback */
                        public function setCallback(Closure $callback): void {
                            $this->callback = $callback;
                        }
                    }
                    $a = new A(function() { return "a";});
                    $a->setCallback(function() { return "b";});',
            ],
            'classTemplateSelfs' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var class-string<T> */
                        public $T;

                        /**
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    class E {
                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo() {
                            return new Foo(__CLASS__);
                        }

                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo2() {
                            return new Foo(self::class);
                        }

                        /**
                         * @return Foo<static>
                         */
                        public static function getFoo3() {
                            return new Foo(static::class);
                        }
                    }

                    class G extends E {}

                    $efoo = E::getFoo();
                    $efoo2 = E::getFoo2();
                    $efoo3 = E::getFoo3();

                    $gfoo = G::getFoo();
                    $gfoo2 = G::getFoo2();
                    $gfoo3 = G::getFoo3();',
            ],
            'constructorLiteralArgsGeneralize' => [
                'code' => '<?php
                    /** @template T */
                    class SomeCollection {
                        /** @param array<T> $c */
                        public function __construct(array $c) {}
                    }

                    /** @param SomeCollection<int> $c */
                    function takesInts(SomeCollection $c): void {}

                    takesInts(new SomeCollection([1, 2, 3]));',
            ],
            'nullReturnAgainstTemplatedTypeSilent' => [
                'code' => '<?php
                    /** @template T as array|object|string */
                    class Cache9 {
                        /** @var T|null */
                        private $item = null;

                        /** @return T */
                        public function getItem(bool $flag): array|object|string|null
                        {
                            if ($flag) {
                                return null;
                            }
                            return $this->item;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'partialGenericTypeFillsTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TA
                     * @template TB of string = \'pivot\'
                     */
                    abstract class Rel {
                        /** @return TA */
                        public function a(): mixed {
                            throw new Exception();
                        }

                        /** @return TB */
                        public function acc(): string {
                            throw new Exception();
                        }
                    }

                    /**
                     * @psalm-mutable
                     * @template T
                     * @template U = list<T>
                     */
                    abstract class Pair {
                        /** @return U */
                        public function u(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @psalm-immutable */
                    class Pivot {}

                    /** @psalm-immutable */
                    final class MorphPivot extends Pivot {}

                    /**
                     * @psalm-mutable
                     * @template TRelated of object
                     * @template TDeclaring of object
                     * @template-covariant TPivot of Pivot = Pivot
                     */
                    class BelongsToMany {
                        /** @return TPivot */
                        public function pivot(): object {
                            throw new Exception();
                        }
                    }

                    /**
                     * @psalm-mutable
                     * @template TRelated of object
                     * @template TDeclaring of object
                     * @template-covariant TPivot of Pivot = MorphPivot
                     * @extends BelongsToMany<TRelated, TDeclaring, TPivot>
                     */
                    class MorphToMany extends BelongsToMany {}

                    /** @psalm-mutable */
                    final class Team {
                        /** @return BelongsToMany<Team, $this> */
                        public function users(): BelongsToMany {
                            throw new Exception();
                        }

                        /** @return MorphToMany<Team, $this> */
                        public function tags(): MorphToMany {
                            throw new Exception();
                        }
                    }

                    /** @return Rel<int> */
                    function rel(): Rel {
                        throw new Exception();
                    }

                    /** @var Pair<string> $pair */
                    $pair = null;

                    $a = rel()->a();
                    $acc = rel()->acc();
                    $u = $pair->u();
                    $pivot = (new Team())->users()->pivot();
                    $morph_pivot = (new Team())->tags()->pivot();',
                'assertions' => [
                    '$a===' => 'int',
                    '$acc===' => '\'pivot\'',
                    '$u===' => 'list<string>',
                    '$pivot===' => 'Pivot',
                    '$morph_pivot===' => 'MorphPivot',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'partialGenericPropertiesAndIterationUseTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TK of array-key = int
                     * @template TV = string
                     * @implements IteratorAggregate<TK, TV>
                     * @property TV $magic
                     */
                    class Coll implements IteratorAggregate {
                        /** @var TV|null */
                        public mixed $last = null;

                        /** @return Iterator<TK, TV> */
                        public function getIterator(): Iterator {
                            throw new Exception();
                        }

                        public function __get(string $name): mixed {
                            return null;
                        }
                    }

                    /**
                     * @psalm-mutable
                     * @template TK of array-key = int
                     * @template TV = string
                     * @extends Coll<TK, TV>
                     */
                    final class ChildColl extends Coll {}

                    /** @return Coll<int> */
                    function coll(): Coll {
                        throw new Exception();
                    }

                    /** @return ChildColl<int> */
                    function childColl(): ChildColl {
                        throw new Exception();
                    }

                    $last = coll()->last;
                    $magic = coll()->magic;
                    $child_last = childColl()->last;

                    $key = null;
                    $value = null;

                    foreach (coll() as $key => $value) {}',
                'assertions' => [
                    '$last===' => 'null|string',
                    '$magic===' => 'string',
                    '$child_last===' => 'null|string',
                    '$key===' => 'int|null',
                    '$value===' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templatesInferredFromPartialGenericArgument' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TA
                     * @template TB of string = \'pivot\'
                     */
                    final class Rel {}

                    /**
                     * @template X
                     * @template Y of string
                     * @param Rel<X, Y> $r
                     * @return array{X, Y}
                     */
                    function unpackRel(Rel $r): array {
                        throw new Exception();
                    }

                    /** @return Rel<int> */
                    function rel(): Rel {
                        throw new Exception();
                    }

                    $unpacked = unpackRel(rel());',
                'assertions' => [
                    '$unpacked===' => 'list{int, \'pivot\'}',
                ],
            ],
            'newOfClassWithTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T = int
                     */
                    final class Box {}

                    /**
                     * @psalm-mutable
                     * @template T of object = stdClass
                     */
                    final class OBox {
                        /** @param T|null $t */
                        public function __construct(?object $t = null) {}
                    }

                    /**
                     * @psalm-mutable
                     * @template TA
                     * @template TB = string
                     */
                    final class Pair {
                        /** @param TA $a */
                        public function __construct(mixed $a) {}
                    }

                    /**
                     * @psalm-mutable
                     * @template T of int = int
                     * @psalm-purity-template C(pure) <= io
                     */
                    final class Gen {
                        /** @param T $t */
                        public function __construct(int $t) {}
                    }

                    $box = new Box();
                    $obox = new OBox();
                    $inferred_obox = new OBox(new Exception());
                    $pair = new Pair(1);
                    $gen = new Gen(5);',
                'assertions' => [
                    '$box===' => 'Box<int>',
                    '$obox===' => 'OBox<stdClass>',
                    '$inferred_obox===' => 'OBox<Exception>',
                    '$pair===' => 'Pair<1, string>',
                    '$gen===' => 'Gen[io]<5>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classTemplateDefaultUsingKeyOfAnEarlierTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T of array
                     * @template K of array-key = key-of<T>
                     */
                    final class Keyed {
                        /** @return K */
                        public function key(): int|string {
                            throw new Exception();
                        }
                    }

                    /** @param Keyed<array{a: int}> $keyed */
                    function keyOf(Keyed $keyed): string {
                        return $keyed->key();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'selfReferencingTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T
                     * @template TNext of Node = Node<T>
                     * @template TKey of array-key = key-of<self::MAP>
                     */
                    final class Node {
                        public const MAP = ["a" => 1];

                        /** @return TNext */
                        public function next(): Node {
                            throw new Exception();
                        }

                        /** @return T */
                        public function value(): mixed {
                            throw new Exception();
                        }
                    }

                    /** @return Node<int> */
                    function node(): Node {
                        throw new Exception();
                    }

                    $node = node();
                    $value = node()->next()->next()->value();',
                'assertions' => [
                    '$node===' => 'Node<int, Node<int>, \'a\'>',
                    '$value===' => 'int',
                ],
                'ignored_issues' => ['MissingClassConstType'],
                'php_version' => '8.0',
            ],
            'selfReferencingTemplateDefaultIsExpandedOneLevelDeep' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T
                     * @template TNext of Node = Node<T>
                     * @template TKey of string = key-of<self::MAP>
                     */
                    final class Node {
                        public const MAP = ["a" => 1];

                        /** @return TNext */
                        public function next(): Node {
                            throw new Exception();
                        }

                        /** @return TKey */
                        public function key(): string {
                            throw new Exception();
                        }
                    }

                    /** @return Node<int> */
                    function node(): Node {
                        throw new Exception();
                    }

                    $next = node()->next();
                    $key = node()->key();
                    $next_next = $next->next();
                    $next_key = $next->key();',
                'assertions' => [
                    '$next===' => 'Node<int, Node<int>, \'a\'>',
                    '$key===' => '\'a\'',
                    '$next_next===' => 'Node<int, Node<int>, \'a\'>',
                    '$next_key===' => '\'a\'',
                ],
                'ignored_issues' => ['MissingClassConstType'],
                'php_version' => '8.0',
            ],
            'partialGenericRepeatingItsClassIsFilledAtEachLevel' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T
                     * @template U = string
                     */
                    final class Pair {}

                    class Base {
                        /** @var Pair<Pair<int>, bool>|null */
                        public ?Pair $value = null;
                    }

                    final class Child extends Base {
                        /** @var Pair<Pair<int, string>, bool>|null */
                        public ?Pair $value = null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newThroughAnInheritedConstructorUsesTheDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T
                     */
                    class Base {
                        /** @param (callable(): T)|null $cb */
                        public function __construct(?callable $cb = null) {}
                    }

                    /**
                     * @psalm-immutable
                     * @template U = int
                     * @extends Base<U>
                     */
                    final class Child extends Base {}

                    $omitted = new Child();
                    $null = new Child(null);
                    $inferred = new Child(fn(): string => "x");',
                'assertions' => [
                    '$omitted===' => 'Child<int>',
                    '$null===' => 'Child<int>',
                    '$inferred===' => 'Child<\'x\'>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'partialGenericTypeGetsExpandedDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TModel
                     * @template TAcc of string = self::ACC
                     * @template TKey of array-key = key-of<self::MAP>
                     */
                    final class Rel {
                        public const ACC = "pivot";
                        public const MAP = ["x" => 1, "y" => 2];
                    }

                    /** @var Rel<int> $rel */
                    $rel = null;',
                'assertions' => [
                    '$rel===' => 'Rel<int, \'pivot\', \'x\'|\'y\'>',
                ],
                'ignored_issues' => ['MissingClassConstType'],
                'php_version' => '8.0',
            ],
            'newIgnoresWhatAnArgumentSaysAboutOtherTemplates' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T = int
                     * @template U
                     */
                    final class Box {
                        /** @param (callable(): T)|list<U> $value */
                        public function __construct(callable|array $value) {}
                    }

                    $box = new Box([true]);',
                'assertions' => [
                    '$box===' => 'Box<int, true>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newInfersFromAnArgumentThatMayBeForTheTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T = int
                     * @template U
                     */
                    final class Either {
                        /** @param (callable(): T)|U $value */
                        public function __construct(mixed $value) {}
                    }

                    /** @var callable $callable */
                    $callable = null;
                    /** @var mixed $mixed */
                    $mixed = null;
                    /** @var Closure|int $closure_or_int */
                    $closure_or_int = null;

                    $from_callable = new Either($callable);
                    $from_mixed = new Either($mixed);
                    $from_int = new Either(5);
                    $from_closure_or_int = new Either($closure_or_int);',
                'assertions' => [
                    '$from_callable===' => 'Either<mixed, callable[impure]>',
                    '$from_mixed===' => 'Either<mixed, mixed>',
                    '$from_int===' => 'Either<int, 5>',
                    '$from_closure_or_int===' => 'Either<mixed, Closure[impure]|int>',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'newLeavesATemplateWithADefaultToLaterCodeThatCanBindIt' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TValue = mixed
                     */
                    final class Promise {
                        public function __construct(?callable $wait = null) {}

                        /** @param TValue $value */
                        public function resolve(mixed $value): void {}
                    }

                    /**
                     * @template T
                     * @param callable(): T $task
                     * @return Promise<T>
                     */
                    function task(callable $task): Promise {
                        $promise = new Promise(fn() => null);
                        $promise->resolve($task());

                        return $promise;
                    }

                    /** @return Promise<string> */
                    function resolved(): Promise {
                        $promise = new Promise();
                        $promise->resolve("x");

                        return $promise;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newLeavesATemplateWithADefaultToLaterCodeAfterATemplateFreeArgument' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T = int
                     */
                    final class Lazy {
                        /** @param (callable(): T)|null $factory */
                        public function __construct(?callable $factory = null) {}

                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /** @return Lazy<string> */
                    function lazy(): Lazy {
                        $lazy = new Lazy(null);
                        $lazy->set("x");

                        return $lazy;
                    }

                    $unbound = new Lazy(null);',
                'assertions' => [
                    '$unbound===' => 'Lazy<`_0:mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newLeavesATemplateWithADefaultThatLaterCodeNeverFixesToItsBound' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TError of Throwable = RuntimeException
                     */
                    final class Result {
                        public function __construct() {}

                        /** @param TError $error */
                        public function fail(Throwable $error): void {}

                        /** @return TError|null */
                        public function error(): ?Throwable {
                            return null;
                        }
                    }

                    $error = (new Result())->error();',
                'assertions' => [
                    '$error===' => 'Throwable|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newLeavesATemplateWithADefaultNamedInAPublicPropertyToLaterCode' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T = int
                     */
                    final class Holder {
                        /** @var T|null */
                        public mixed $value = null;

                        public function __construct() {}
                    }

                    /** @return Holder<string> */
                    function holder(): Holder {
                        $holder = new Holder();
                        $holder->value = "x";

                        return $holder;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newUsesTheDefaultOfATemplateOnlyAnInheritedMethodTakes' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    abstract class Setter {
                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /**
                     * @psalm-mutable
                     * @template T = int
                     * @extends Setter<T>
                     */
                    final class Inherited extends Setter {
                        public function __construct() {}
                    }

                    /** @template T */
                    trait SetsIt {
                        /** @param T $value */
                        public function set(mixed $value): void {}
                    }

                    /**
                     * @psalm-mutable
                     * @template T = int
                     */
                    final class FromTrait {
                        /** @use SetsIt<T> */
                        use SetsIt;

                        public function __construct() {}
                    }

                    $inherited = new Inherited();
                    $from_trait = new FromTrait();',
                'assertions' => [
                    '$inherited===' => 'Inherited<int>',
                    '$from_trait===' => 'FromTrait<int>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'newWithTemplateFreeConstructorArgumentUsesTheDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T = int
                     */
                    final class Lazy {
                        /** @param (callable(): T)|null $f */
                        public function __construct(?callable $f = null) {}
                    }

                    /**
                     * @psalm-immutable
                     * @template T of object = stdClass
                     */
                    final class OBox {
                        /** @param T|null $t */
                        public function __construct(?object $t = null) {}
                    }

                    /**
                     * @psalm-immutable
                     * @template T = int
                     */
                    final class ByName {
                        /** @param class-string<T>|string|null $c */
                        public function __construct(?string $c = null) {}
                    }

                    /**
                     * @psalm-immutable
                     * @template T = int
                     */
                    final class Two {
                        /**
                         * @param (callable(): T)|null $a
                         * @param (callable(): T)|null $b
                         */
                        public function __construct(?callable $a, ?callable $b) {}
                    }

                    /** @var callable $callable */
                    $callable = null;

                    $omitted = new Lazy();
                    $null = new Lazy(null);
                    $bare_callable = new Lazy($callable);
                    $null_object = new OBox(null);
                    $name = new ByName("foo");
                    $nulls = new Two(null, null);
                    $null_then_bare = new Two(null, $callable);
                    $bare_then_null = new Two($callable, null);',
                'assertions' => [
                    '$omitted===' => 'Lazy<int>',
                    '$null===' => 'Lazy<int>',
                    '$bare_callable===' => 'Lazy<mixed>',
                    '$null_object===' => 'OBox<stdClass>',
                    '$name===' => 'ByName<int>',
                    '$nulls===' => 'Two<int>',
                    '$null_then_bare===' => 'Two<mixed>',
                    '$bare_then_null===' => 'Two<mixed>',
                ],
                'ignored_issues' => ['MixedArgumentTypeCoercion'],
                'php_version' => '8.0',
            ],
            'propertyRedeclaredWithTheDefaultsWrittenOutIsInvariant' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TModel
                     * @template TAcc of string = \'pivot\'
                     */
                    final class Rel {}

                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    abstract class Base {
                        /** @var T|null */
                        public mixed $p = null;

                        /** @var Rel<int>|null */
                        public ?Rel $q = null;
                    }

                    /**
                     * @psalm-mutable
                     * @extends Base<Rel<int>>
                     */
                    final class Sub extends Base {
                        /** @var Rel<int, \'pivot\'>|null */
                        public mixed $p = null;

                        /** @var Rel<int, \'pivot\'>|null */
                        public ?Rel $q = null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'partialGenericWithARequiredArgumentLeftOutKeepsArgumentPositions' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template A
                     * @template B
                     * @template C = int
                     */
                    final class Foo {}

                    /**
                     * @template T1
                     * @template T2
                     * @template T3
                     * @param Foo<T1, T2, T3> $f
                     * @return array{T1, T2, T3}
                     */
                    function unpackFoo(Foo $f): array {
                        throw new Exception();
                    }

                    /** @var Foo<string> $foo */
                    $foo = null;

                    $unpacked = unpackFoo($foo);',
                'assertions' => [
                    '$unpacked===' => 'list{string, mixed, mixed}',
                ],
                'ignored_issues' => ['MissingTemplateParam'],
            ],
            'emptyTemplateDefaultKeepsTheRestOfTheDocblock' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T of int =
                     * @template U = string
                     */
                    final class A {
                        /** @return T */
                        public function t(): int {
                            throw new Exception();
                        }

                        /** @return U */
                        public function u(): mixed {
                            throw new Exception();
                        }
                    }

                    $t = (new A())->t();
                    $u = (new A())->u();',
                'assertions' => [
                    '$t===' => 'int',
                    '$u===' => 'string',
                ],
                'ignored_issues' => ['InvalidDocblock'],
                'php_version' => '8.0',
            ],
            'bareGenericTypeIgnoresTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TM of object = stdClass
                     * @template TN = int
                     */
                    abstract class Res {
                        /** @return TM */
                        public function m(): object {
                            throw new Exception();
                        }

                        /** @return TN */
                        public function n(): mixed {
                            throw new Exception();
                        }
                    }

                    /**
                     * @psalm-mutable
                     * @template TAsync of bool = false
                     */
                    final class PendingRequest {
                        /** @return (TAsync is true ? int : string) */
                        public function get(): mixed {
                            throw new Exception();
                        }

                        /** @return PendingRequest<true> */
                        public function async(): PendingRequest {
                            throw new Exception();
                        }
                    }

                    /**
                     * @psalm-mutable
                     * @template T of string|null = null
                     */
                    abstract class A {
                        /** @var T */
                        public $foo;
                    }

                    function res(): Res {
                        throw new Exception();
                    }

                    function a(): A {
                        throw new Exception();
                    }

                    /** @psalm-mutable */
                    function withAuth(PendingRequest $r): int|string {
                        return $r->get();
                    }

                    $m = res()->m();
                    $n = res()->n();
                    $foo = a()->foo;
                    $plain = withAuth(new PendingRequest());
                    $async = withAuth((new PendingRequest())->async());',
                'assertions' => [
                    '$m===' => 'object',
                    '$n===' => 'mixed',
                    '$foo===' => 'null|string',
                    '$plain===' => 'int|string',
                    '$async===' => 'int|string',
                ],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'methodTemplateDefaultNamingClassTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    interface Promise {
                        /**
                         * @psalm-impure
                         * @template TResult1 = T
                         * @template TResult2 = never
                         * @param (callable(T): TResult1)|null $a
                         * @param (callable(mixed): TResult2)|null $b
                         * @return Promise<TResult1|TResult2>
                         */
                        public function then(?callable $a = null, ?callable $b = null): Promise;
                    }

                    /** @var Promise<int> $p */
                    $p = null;

                    $nulls = $p->then(null, null);
                    $omitted = $p->then();
                    $first = $p->then(fn(int $i): string => (string) $i);
                    $second = $p->then(null, fn(mixed $e): float => 1.0);',
                'assertions' => [
                    '$nulls===' => 'Promise<int>',
                    '$omitted===' => 'Promise<int>',
                    '$first===' => 'Promise<decimal-int-string>',
                    '$second===' => 'Promise<float(1)|int>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'methodTemplateDefaultNamingClassTemplateGivenANonCallableArgument' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TValue
                     */
                    final class Coll {
                        /**
                         * @template TResult = TValue
                         * @param (callable(TValue): TResult)|string|null $callback
                         * @return TResult|null
                         */
                        public function max(callable|string|null $callback = null): mixed {
                            return null;
                        }
                    }

                    /** @var Coll<int> $c */
                    $c = null;

                    $omitted = $c->max();
                    $key = $c->max("price");
                    $callback = $c->max(fn(int $i): string => "x");',
                'assertions' => [
                    '$omitted===' => 'int|null',
                    '$key===' => 'int|null',
                    '$callback===' => '\'x\'|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedMethodTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    abstract class Base {
                        /**
                         * @template R = T
                         * @param (callable(T): R)|null $cb
                         * @return list<R>
                         */
                        public function map(?callable $cb = null): array {
                            return [];
                        }
                    }

                    /** @extends Base<int> */
                    final class Child extends Base {
                        public function map(?callable $cb = null): array {
                            return [];
                        }
                    }

                    /**
                     * @template T
                     */
                    trait MapsT {
                        /**
                         * @template R = T
                         * @param (callable(T): R)|null $cb
                         * @return list<R>
                         */
                        public function tmap(?callable $cb = null): array {
                            return [];
                        }
                    }

                    /** @psalm-mutable */
                    final class UsesT {
                        /** @use MapsT<string> */
                        use MapsT;
                    }

                    $overridden = (new Child())->map();
                    $from_trait = (new UsesT())->tmap();',
                'assertions' => [
                    '$overridden===' => 'list<int>',
                    '$from_trait===' => 'list<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'methodTemplateDefaultLeavesTemplateInferredFromParamDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-mutable
                     * @template TModel of object
                     */
                    abstract class Factory {
                        /**
                         * @template TV = TModel
                         * @param TV|null $v
                         * @return TV
                         */
                        public static function pick(mixed $v = null): mixed {
                            throw new Exception();
                        }
                    }

                    /** @psalm-mutable */
                    final class Req {
                        /**
                         * @template TDefault = null
                         * @param TDefault $default
                         * @return string|TDefault
                         */
                        public function input(string $k, mixed $default = null): mixed {
                            throw new Exception();
                        }

                        /**
                         * @template TAsync of bool = false
                         * @param TAsync $async
                         * @return (TAsync is true ? int : string)
                         */
                        public function send(bool $async = false): mixed {
                            throw new Exception();
                        }
                    }

                    /** @psalm-immutable */
                    final class User {}

                    /** @extends Factory<User> */
                    final class UserFactory extends Factory {}

                    $picked = UserFactory::pick();
                    $input = (new Req())->input("x");
                    $input_with_default = (new Req())->input("x", 5);
                    $sent = (new Req())->send();
                    $sent_async = (new Req())->send(true);',
                'assertions' => [
                    '$picked===' => 'null',
                    '$input===' => 'null|string',
                    '$input_with_default===' => '5|string',
                    '$sent===' => 'string',
                    '$sent_async===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classTemplateBoundUsingTemplateRedeclaredWithAnotherTagPrefix' => [
                'code' => '<?php
                    final class Foo {}

                    /**
                     * @template T
                     * @template U of T
                     * @psalm-template T of object
                     */
                    final class DupBox {
                        /** @param U $u */
                        public function __construct(private object $u) {}

                        /** @return U */
                        public function get(): object {
                            return $this->u;
                        }
                    }

                    $u = (new DupBox(new Foo()))->get();',
                'assertions' => [
                    '$u===' => 'Foo',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'bindTemplateRegardlessOfArgumentOrderWithUnboundNullFirst' => [
                'code' => '<?php
                    final class Foo {}

                    /** @template T of object */
                    final class Pair {
                        /**
                         * @param T|null $a
                         * @param T|null $b
                         */
                        public function __construct(?object $a, ?object $b) {}
                    }

                    final class S {
                        /**
                         * @template T of object
                         * @param T|null $a
                         * @param T|null $b
                         * @return T
                         */
                        public static function s(?object $a, ?object $b): object {
                            throw new \Exception();
                        }
                    }

                    $foo = new Foo();
                    $a = new Pair(null, $foo);
                    $b = new Pair($foo, null);
                    $c = S::s(null, $foo);',
                'assertions' => [
                    '$a' => 'Pair<Foo>',
                    '$b' => 'Pair<Foo>',
                    '$c' => 'Foo',
                ],
            ],
            'bindClassStringTemplateAfterUnboundNull' => [
                'code' => '<?php
                    final class Foo {}

                    /** @template T of object */
                    final class X {
                        /**
                         * @param T|null $a
                         * @param class-string<T> $b
                         */
                        public function __construct(?object $a, string $b) {}
                    }

                    $x = new X(null, Foo::class);',
                'assertions' => [
                    '$x' => 'X<Foo>',
                ],
            ],
            'bindTemplateAfterUnboundArgInConditionalParam' => [
                'code' => '<?php
                    /** @template T of self::* */
                    final class SomeTemplatedClass {
                        public const TYPE1 = "type1";
                        public const TYPE2 = "type2";

                        /**
                         * @param (T is self::TYPE1 ? string : null) $someParam
                         * @param T $type
                         */
                        public function __construct(private ?string $someParam, private string $type) {}

                        /** @return SomeTemplatedClass<self::TYPE1> */
                        public static function create(): self {
                            return new self("some-string", self::TYPE1);
                        }
                    }

                    $r = new SomeTemplatedClass("some-string", SomeTemplatedClass::TYPE1);',
                'assertions' => [
                    '$r===' => 'SomeTemplatedClass<\'type1\'>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
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

                    /**
                     * @param Pair<int, stdClass> $concrete
                     * @param Pair<int, object> $bound
                     * @param Child<stdClass> $child
                     * @param Child<*> $starred_child
                     * @param Pair<int, *> $starred
                     * @param Relation<Model, string, int> $relation
                     */
                    function f(
                        Pair $concrete,
                        Pair $bound,
                        Child $child,
                        Child $starred_child,
                        Pair $starred,
                        Relation $relation
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
            'starProjectionReadsByPosition' => [
                'code' => '<?php
                    abstract class Model {}

                    /**
                     * @template TFirst
                     * @template TSecond of object
                     */
                    final class Pair {
                        /** @var list<TSecond> */
                        public array $seconds = [];

                        /** @var Closure(TSecond): void */
                        public Closure $callback;

                        /** @param Closure(TSecond): void $callback */
                        public function __construct(Closure $callback) {
                            $this->callback = $callback;
                        }

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

                        /** @return Closure(TSecond): void */
                        public function consumer(): Closure {
                            throw new Exception();
                        }

                        /** @return Closure(): TSecond */
                        public function producer(): Closure {
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

                    $pair = pair();
                    $first = $pair->first();
                    $second = $pair->second();
                    $seconds = $pair->seconds();
                    $seconds_property = $pair->seconds;
                    $callback_property = $pair->callback;
                    $consumer = $pair->consumer();
                    $producer = $pair->producer();
                    $consumer_of_consumer = $pair->consumerOfConsumer();
                    $self = $pair->self();
                    $model = relation()->getModel();

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
                    '$callback_property===' => 'Closure[impure](never):void',
                    '$consumer===' => 'Closure[impure](never):void',
                    '$producer===' => 'Closure[impure]():object',
                    '$consumer_of_consumer===' => 'Closure[impure](Closure[impure](object):void):void',
                    '$self===' => 'Pair<int, *>',
                    '$model===' => 'Model',
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
            'starProjectionNarrowedToAChildStaysUnknown' => [
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

                    /** @return Pair<int, *> */
                    function pair(): Pair {
                        throw new Exception();
                    }

                    $pair = pair();
                    $child = $pair instanceof Child ? $pair : null;',
                'assertions' => [
                    '$child===' => 'Child<*>|null',
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
            'arrayCallableOnAGenericObjectBindsItsTemplates' => [
                'code' => '<?php
                    /** @template T */
                    final class Coll {
                        /** @param T $value */
                        public function add(mixed $value): void {}

                        /** @return T */
                        public function first(): mixed {
                            throw new Exception();
                        }

                        /**
                         * @param T $a
                         * @param T $b
                         */
                        public function compare(mixed $a, mixed $b): int {
                            return 0;
                        }
                    }

                    /** @return Coll<int> */
                    function ints(): Coll {
                        throw new Exception();
                    }

                    $sorted = [3, 1, 2];
                    usort($sorted, [ints(), "compare"]);
                    $add = Closure::fromCallable([ints(), "add"]);
                    $first = Closure::fromCallable([ints(), "first"]);',
                'assertions' => [
                    '$add===' => 'Closure[impure](int):void',
                    '$first===' => 'Closure[impure]():int',
                ],
                'ignored_issues' => [],
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
            'composedParentArgumentPropertyIsReadWithTheChildArguments' => [
                'code' => '<?php
                    abstract class Model {}

                    final class User extends Model {}

                    /** @template T */
                    final class Box {}

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

                    /** @return Composed<User> */
                    function users(): Composed {
                        throw new Exception();
                    }

                    $box = users()->value;',
                'assertions' => [
                    '$box===' => 'Box<User>',
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
            'templateInVarDocblockOfPromotedProperty' => [
                'code' => '<?php
                    /** @template T */
                    final class Foo {
                        public function __construct(
                            /** @var T */
                            public mixed $withVar,
                        ) {}
                    }

                    /** @var Foo<int> $foo */
                    $foo = new Foo(1);
                    $x = $foo->withVar;',
                'assertions' => [
                    '$x===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'staticReturnFromInterfaceOnGenericClassKeepsTemplateParams' => [
                'code' => '<?php
                    interface CopyInterface {
                        public function copy(): static;
                    }

                    /**
                     * @template T
                     */
                    class Collection implements CopyInterface {
                        /** @var T[] */
                        private $x;

                        /** @param T[] $x */
                        public function __construct($x) {
                            $this->x = $x;
                        }

                        public function copy(): static {
                            return $this;
                        }
                    }

                    /** @param Collection<int> $x */
                    function checkCollection($x): string {
                        return get_class($x);
                    }

                    /** @var Collection<int> $intCollection */
                    $intCollection = new Collection([1]);

                    checkCollection($intCollection->copy());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templateBoundReferencesEarlierTemplateInPropertyAndParamDocblock' => [
                'code' => '<?php
                    interface Entity {}
                    final class Customer implements Entity {}

                    /** @template T */
                    interface Dao {}

                    /** @implements Dao<Customer> */
                    final class CustomerDao implements Dao {}

                    /**
                     * @template TModel of Entity
                     * @template TDao of Dao<TModel>
                     */
                    class Cache {}

                    class Test {
                        /** @var Cache<Customer, CustomerDao> */
                        public $c;

                        /** @param Cache<Customer, CustomerDao> $c */
                        public function __construct($c) {
                            $this->c = $c;
                        }
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
            'classTemplateDefaultNamingAnUndefinedClass' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T = Missing
                     */
                    final class C {}',
                'error_message' => 'UndefinedClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:33 - Class, interface or enum named Missing does not exist',
            ],
            'partialGenericNextToASelfNamingDefaultChecksItsDefaults' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template A
                     * @template B of string = "pivot"
                     */
                    final class Rel {}

                    /**
                     * @psalm-immutable
                     * @template X
                     * @template Y = Rel<int>
                     * @template Next = Outer<X>
                     */
                    final class Outer {}

                    /** @param Outer<int> $o */
                    function takes(Outer $o): void {}

                    /** @param Outer<int, Rel<int, "other">, Outer<int>> $o */
                    function badDefault(Outer $o): void {
                        takes($o);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'templateInCallableParamOfDocblockMethodOfNamespacedClassRejectsOtherType' => [
                'code' => '<?php
                    namespace App\Ns;

                    /**
                     * @template TValue
                     * @method $this through(callable(TValue): mixed $callback)
                     */
                    interface I {}

                    /** @param I<string> $p */
                    function f(I $p): void {
                        $p->through(fn (int $i): int => $i);
                    }
                ',
                'error_message' => 'Argument 1 of through expects callable[impure](string):mixed, but Closure[pure](int):int provided',
            ],
            'templateParamWithNullDefaultInDocblockMethodOfNamespacedClass' => [
                'code' => '<?php
                    namespace App\Ns;

                    /**
                     * @template TValue
                     * @method $this accept(TValue $v = null)
                     */
                    interface I {}

                    /** @param I<string> $p */
                    function f(I $p): void {
                        $p->accept(null);
                    }
                ',
                'error_message' => 'NullArgument',
            ],
            'restrictTemplateInputWithClassString' => [
                'code' => '<?php
                    /** @template T as object */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new B);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:36:31 - Type B should be a subtype of A',
            ],
            'restrictTemplateInputWithTClassBadInput' => [
                'code' => '<?php
                    /** @template T */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new B);',
                'error_message' => 'IncompatibleTypeParameters - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:36:31 - Type B should be a subtype of A',
            ],
            'templatedClosureProperty' => [
                'code' => '<?php
                    final class State
                    {}

                    interface Foo
                    {}

                    function type(string ...$_p): void {}

                    /**
                     * @template T
                     */
                    final class AlmostFooMap
                    {
                        /**
                         * @param callable(State):(T&Foo) $closure
                         */
                        public function __construct(callable $closure)
                        {
                            type($closure);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:34 - Argument 1 of type expects string, but callable[impure](State):(T:AlmostFooMap as mixed)&Foo provided',
            ],
            'templateWithNoReturn' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class A {
                        /** @return T */
                        public function foo() {}
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'templateInvalidDocblockArgument' => [
                'code' => '<?php
                    /** @template T as object */
                    class Generic {}

                    /**
                     * @template T
                     * @param T $p
                     * @return Generic<T>
                     * @psalm-suppress InvalidReturnType
                     */
                    function violate($p) {}',
                'error_message' => 'InvalidTemplateParam',
            ],
            'doublyLinkedListBadParam' => [
                'code' => '<?php
                    /** @var SplDoublyLinkedList<string> */
                    $templated_list = new SplDoublyLinkedList();
                    $templated_list->add(5, []);',
                'error_message' => 'InvalidArgument',
            ],
            'copyScopedClassInNamespacedClass' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @template Bar as DOMNode
                     */
                    class Bar {}',
                'error_message' => 'ReservedWord',
            ],
            'duplicateTemplateFunction' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Foo
                    {
                        /** @var T */
                        private $value;

                        /**
                         * @template T
                         * @param T $value
                         * @return self<T>
                         */
                        static function of($value): self
                        {
                            return new self($value);
                        }

                        /**
                         * @param T $value
                         */
                        private function __construct($value)
                        {
                            $this->value = $value;
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'preventDogCatSnafu' => [
                'code' => '<?php
                    class Animal {}
                    class Dog extends Animal {}
                    class Cat extends Animal {}

                    /**
                     * @template T
                     */
                    class Collection {
                        /**
                         * @param T $t
                         */
                        public function add($t) : void {}
                    }

                    /**
                     * @param Collection<Animal> $list
                     */
                    function addAnimal(Collection $list) : void {
                        $list->add(new Cat());
                    }

                    /**
                     * @param Collection<Dog> $list
                     */
                    function takesDogList(Collection $list) : void {
                        addAnimal($list); // this should be an error
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'templateEmptyParamCoercionChangeVariable' => [
                'code' => '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data = []) {
                            $this->data = $data;
                        }
                    }

                    /** @psalm-param Collection<string> $c */
                    function takesStringCollection(Collection $c): void {}

                    /** @psalm-param Collection<int> $c */
                    function takesIntCollection(Collection $c): void {}

                    $collection = new Collection();

                    takesStringCollection($collection);
                    takesIntCollection($collection);',
                'error_message' => 'InvalidArgument',
            ],
            'argumentExpectsFleshOutTIndexedAccess' => [
                'code' => '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class Row {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         *
                         * @return TData[K]
                         */
                        public function __get(string $property) {
                            // validation logic would go here
                            return $this->data[$property];
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         * @param TData[K] $value
                         */
                        public function __set(string $property, $value) {
                            // data updating would go here
                            $this->data[$property] = $value;
                        }
                    }

                    /** @extends Row<array{id: int, name: string, height: float}> */
                    class CharacterRow extends Row {}

                    $mario = new CharacterRow(["id" => 5, "name" => "Mario", "height" => 3.5]);

                    $mario->ame = "Luigi";',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . "somefile.php:47:29 - Argument 1 of CharacterRow::__set expects 'height'|'id'|'name', but 'ame' provided",
            ],
            'specialiseTypeBeforeReturning' => [
                'code' => '<?php
                    class Base {}
                    class Derived extends Base {}

                    /**
                     * @template T of Base
                     */
                    class Foo {
                        /**
                         * @param T $t
                         */
                        public function __construct ($t) {}
                    }

                    /**
                     * @return Foo<Base>
                     */
                    function returnFooBase() {
                        $f = new Foo(new Derived());
                        takesFooDerived($f);
                        return $f;
                    }

                    /**
                     * @param Foo<Derived> $foo
                     */
                    function takesFooDerived($foo): void {}',
                'error_message' => 'InvalidReturnStatement',
            ],
            'possiblySpecialiseTypeBeforeReturning' => [
                'code' => '<?php
                    class Base {}
                    class Derived extends Base {}

                    /**
                     * @template T of Base
                     */
                    class Foo {
                        /**
                         * @param T $t
                         */
                        public function __construct ($t) {}
                    }

                    /**
                     * @return Foo<Base>
                     */
                    function returnFooBase() {
                        $f = new Foo(new Derived());

                        if (rand(0, 1)) {
                            takesFooDerived($f);
                        }

                        return $f;
                    }

                    /**
                     * @param Foo<Derived> $foo
                     */
                    function takesFooDerived($foo): void {}',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventUseWithMoreSpecificParamInt' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @param T $elem */
                        public function add($elem): void {}
                    }

                    /**
                     * @template T
                     * @param Collection<T> $col
                     */
                    function usesCollection(Collection $col): void {
                        $col->add(456);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventUseWithMoreSpecificParamEmptyArray' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @param T $elem */
                        public function add($elem): void {}
                    }

                    /**
                     * @template T
                     * @param Collection<T> $col
                     */
                    function usesCollection(Collection $col): void {
                        $col->add([]);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'noClassTemplatesInStaticMethods' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class C {
                        /**
                         * @param T $t
                         */
                        public static function foo($t) : void {}
                    }',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'newGenericBecomesPropertyTypeInvalidArg' => [
                'code' => '<?php
                    class B {}
                    class C {}

                    class A {
                        /** @var ArrayCollection<int, B> */
                        public ArrayCollection $b_collection;

                        public function __construct() {
                            $this->b_collection = new ArrayCollection([]);
                            $this->b_collection->add(5, new C());
                        }
                    }

                    /**
                     * @psalm-template TKey
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR
                    . 'somefile.php:11:57 - Argument 2 of ArrayCollection::add expects B, but C provided',
            ],
            'preventIteratorAggregateToIterableWithDifferentTypes' => [
                'code' => '<?php
                    class Foo {}

                    class Bar {}

                    /** @param iterable<int, Foo> $foos */
                    function consume(iterable $foos): void {}

                    /** @param IteratorAggregate<int, Bar> $t */
                    function foo(IteratorAggregate $t) : void {
                        consume($t);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventPassingToBoundParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Container
                    {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t)
                        {
                            $this->t = $t;
                        }

                        /**
                         * @param T $t
                         * @return T
                         */
                        protected function makeNew($t)
                        {
                            return $t;
                        }

                        /**
                         * @template U
                         * @param U $u
                         */
                        public function map($u) : void
                        {
                            $this->makeNew($u);
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'bindRedirectedTemplate' => [
                'code' => '<?php
                    /**
                     * @template TIn
                     * @template TOut
                     */
                    final class Map
                    {
                        /** @param Closure(TIn): TOut $c */
                        public function __construct(private Closure $c) {}

                        /**
                         * @template TIn2 as list<TIn>
                         * @param TIn2 $in
                         * @return list<TOut>
                         */
                        public function __invoke(array $in) : array {
                            return array_map(
                                $this->c,
                                $in
                            );
                        }
                    }

                    $m = new Map(fn(int $num) => (string) $num);
                    $m(["a"]);',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'bindClosureParamAccurately' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface Collection {
                        /**
                         * @template T
                         * @param Closure(TValue):T $func
                         * @return Collection<TKey,T>
                         */
                        public function map(Closure $func);

                    }

                    /**
                     * @param Collection<int, string> $c
                     */
                    function f(Collection $c): void {
                        $fn = function(int $_p): bool { return true; };
                        $c->map($fn);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'limitTemplateTypeWithSameName' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    function takesA(A $a) : void {}

                    /** @param A<stdClass> $a */
                    function foo(A $a) : void {
                        takesA($a);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'limitTemplateTypeExtended' => [
                'code' => '<?php

                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    /**
                     * @extends A<stdClass>
                     */
                    class AChild extends A {}

                    function takesA(A $a) : void {}

                    $child = new AChild();
                    takesA($child);',
                'error_message' => 'InvalidArgument',
            ],
            'preventBoundsMismatchDifferentContainers' => [
                'code' => '<?php
                    /**
                     * @param Collection1<Dog> $c
                     * @param Collection2<Cat> $d
                     */
                    function bar(Collection1 $c, Collection2 $d): void {
                        foo($c, $d);
                    }

                    /** @template T of object */
                    interface Collection1 {
                        /** @param T $item */
                        public function add(object $item): void;
                    }

                    /** @template T of object */
                    interface Collection2 {
                        /** @param T $item */
                        public function add(object $item): void;

                        /** @return T */
                        public function get(): object;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param Collection1<T> $c
                     * @param Collection2<T> $d
                     */
                    function foo(Collection1 $c, Collection2 $d): void {
                        $c->add($d->get());
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventBoundsMismatchSameContainers' => [
                'code' => '<?php
                    /**
                     * @param Collection<Dog> $c
                     * @param Collection<Cat> $d
                     */
                    function bar(Collection $c, Collection $d): void {
                        foo($c, $d);
                    }

                    /** @template T of object */
                    interface Collection {
                        /** @param T $item */
                        public function add(object $item): void;

                        /** @return T */
                        public function get(): object;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param Collection<T> $c
                     * @param Collection<T> $d
                     */
                    function foo(Collection $c, Collection $d): void {
                        $c->add($d->get());
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventBoundsMismatchDifferentBoundLevels' => [
                'code' => '<?php
                    /**
                     * @param Collection<Dog> $c
                     */
                    function bar(Collection $c): void {
                        foo($c, new Cat());
                    }

                    /** @template T of object */
                    interface Collection {
                        /** @param T $item */
                        public function add(object $item): void;
                    }

                    class Cat {}
                    class Dog {}

                    /**
                     * @template T of object
                     * @param Collection<T> $c
                     * @param T $d
                     */
                    function foo(Collection $c, object $d): void {
                        $c->add($d);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'invalidTemplateArgumentOnDocblockMethod' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @method void set(T $value)
                     */
                    class Container
                    {
                        public function __call(string $name, array $args) {}
                    }

                    class A {}
                    class B {}

                    /** @var Container<A> $container */
                    $container = new Container();
                    $container->set(new B());',
                'error_message' => 'InvalidArgument',
            ],
            'refineTemplateTypeOfUnionAccurately' => [
                'code' => '<?php
                    /** @psalm-template T as One|Two|Three */
                    class A {
                        /** @param T $t */
                        public function __construct(
                            private object $t
                        ) {}

                        /** @return int */
                        public function foo() {
                            if ($this->t instanceof One || $this->t instanceof Two) {
                                return $this->t;
                            }

                            throw new \Exception();
                        }
                    }

                    final class One {}
                    final class Two {}
                    final class Three {}',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:40 - The inferred type \'T:A as One|Two\' ',
            ],
            'preventMixedNestedCoercion' => [
                'code' => '<?php
                    /** @template T */
                    class MyCollection {
                        /** @param array<T> $members */
                        public function __construct(public array $members) {}
                    }

                    /**
                     * @param MyCollection<string> $c
                     * @return MyCollection<mixed>
                     */
                    function getMixedCollection(MyCollection $c): MyCollection {
                        return $c;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'noCrashOnBrokenTemplate' => [
                'code' => <<<'PHP'
                <?php
                /**
                 * @template TValidationRule of callable>|string
                 */
                class C {}
                PHP,
                'error_message' => 'InvalidDocblock',
            ],
            'partialGenericArgumentIsCheckedAgainstTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TA
                     * @template TB of string = \'pivot\'
                     */
                    final class Rel {}

                    /** @param Rel<int> $r */
                    function takes(Rel $r): void {}

                    /** @param Rel<int, \'other\'> $r */
                    function f(Rel $r): void {
                        takes($r);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:31 - Argument 1 of takes expects Rel<int, \'pivot\'>, but Rel<int, \'other\'> provided',
            ],
            'partialGenericPropertyIsCheckedAgainstTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TA
                     * @template TB of string = \'pivot\'
                     */
                    final class Rel {}

                    /** @psalm-mutable */
                    final class Holder {
                        /** @var Rel<int> */
                        public Rel $r;

                        /** @param Rel<int, \'pivot\'> $ok */
                        public function __construct(Rel $ok) {
                            $this->r = $ok;
                        }

                        /** @param Rel<int, \'other\'> $other */
                        public function set(Rel $other): void {
                            $this->r = $other;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:40 - $this->r with declared type \'Rel<int, \'pivot\'>\' cannot be assigned type',
            ],
            'classTemplateDefaultOutsideItsBound' => [
                'code' => '<?php
                    /**
                     * @template T of int = string
                     */
                    abstract class Bad {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:36 - The default string of template T is not within its bound int',
            ],
            'classTemplateDefaultUsingLaterTemplate' => [
                'code' => '<?php
                    /**
                     * @template T = U
                     * @template U = int
                     */
                    abstract class Fwd {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:36 - The default of template T uses template U, which is not declared before it',
            ],
            'interfaceTemplateDefaultOutsideBoundNamingEarlierTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @template U of T = stdClass
                     */
                    interface Corr {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:31 - The default stdClass of template U is not within its bound T:Corr as object',
            ],
            'traitTemplateDefaultUsingItself' => [
                'code' => '<?php
                    /**
                     * @template T = list<T>
                     */
                    trait SelfRef {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 - The default of template T uses template T, which is not declared before it',
            ],
            'partialGenericArgumentIsCheckedAgainstNestedDefault' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template A
                     * @template B of string = \'pivot\'
                     */
                    final class Rel {}

                    /**
                     * @psalm-immutable
                     * @template X
                     * @template Y = Rel<int>
                     */
                    final class Outer {}

                    /** @param Outer<int> $o */
                    function takes(Outer $o): void {}

                    /** @param Outer<int, Rel<int, \'other\'>> $o */
                    function f(Outer $o): void {
                        takes($o);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:21:31 - Argument 1 of takes expects Outer<int, Rel<int, \'pivot\'>>, but Outer<int, Rel<int, \'other\'>> provided',
            ],
            'partialGenericArgumentIsCheckedAgainstComputedDefault' => [
                'code' => '<?php
                    final class Options {
                        /** @var array<string, int> */
                        public const MAP = ["x" => 1, "y" => 2];
                    }

                    /**
                     * @psalm-immutable
                     * @template T
                     * @template U of string = key-of<Options::MAP>
                     */
                    final class Opts {}

                    /** @param Opts<int> $o */
                    function takes(Opts $o): void {}

                    /** @param Opts<int, \'z\'> $o */
                    function f(Opts $o): void {
                        takes($o);
                    }',
                'error_message' => 'Argument 1 of takes expects Opts<int, \'x\'|\'y\'>, but Opts<int, \'z\'> provided',
            ],
            'ancestorBindingWithAPartialGenericIsCheckedOnPropertyAssignment' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TModel
                     * @template TAcc of string = \'pivot\'
                     */
                    final class Rel {}

                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    abstract class Base {
                        /** @var T|null */
                        public mixed $p = null;
                    }

                    /**
                     * @psalm-mutable
                     * @extends Base<Rel<int>>
                     */
                    final class Part extends Base {}

                    /** @param Rel<int, \'other\'> $other */
                    function f(Part $part, Rel $other): void {
                        $part->p = $other;
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue - src' . DIRECTORY_SEPARATOR . 'somefile.php:26:36 - $part->p with declared type \'Rel<int, \'pivot\'>|null\' cannot be assigned type',
            ],
            'ancestorBindingWithAPartialGenericIsCheckedOnOverride' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TModel
                     * @template TAcc of string = \'pivot\'
                     */
                    final class Rel {}

                    /**
                     * @psalm-mutable
                     * @template T
                     */
                    abstract class Base {
                        /** @return T */
                        abstract public function get(): mixed;
                    }

                    /**
                     * @psalm-mutable
                     * @extends Base<Rel<int>>
                     */
                    final class Part extends Base {
                        /** @return Rel<int, \'other\'> */
                        public function get(): mixed {
                            throw new Exception();
                        }
                    }',
                'error_message' => 'ImplementedReturnTypeMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:23:37 - The inherited return type \'Rel<int, \'pivot\'>\' for Base::get is different to the implemented return type for Part::get',
                'php_version' => '8.0',
            ],
            'filledTemplateDefaultIsADocblockType' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template TModel
                     * @template TAcc of string = \'pivot\'
                     */
                    final class Rel {
                        /** @return TAcc */
                        public function acc(): string {
                            throw new Exception();
                        }
                    }

                    /** @param Rel<int> $r */
                    function f(Rel $r): void {
                        if ($r->acc() === "pivot") {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'emptyTemplateDefaultIsReportedForItsTemplateOnly' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @template T of int =
                     */
                    final class A {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:33 - Missing default after = for template T in docblock for A',
            ],
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
            'starProjectionInheritedComposedArgumentIsNotConcrete' => [
                'code' => '<?php
                    /** @template T */
                    class Box {}

                    /**
                     * @template TChild of object
                     * @extends Box<list<TChild>>
                     */
                    final class ListBox extends Box {}

                    /** @param Box<list<object>> $box */
                    function takesObjects(Box $box): void {}

                    /** @param ListBox<*> $box */
                    function f(ListBox $box): void {
                        takesObjects($box);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:16:38 - Argument 1 of takesObjects expects Box<list<object>>, but ListBox<*> provided',
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
        ];
    }
}
