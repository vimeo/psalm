<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type\Atomic;

use function array_map;

use const DIRECTORY_SEPARATOR;

final class MixinAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'ownMixinExtendsInheritedMixins' => [
                'code' => '<?php
                    final class QueryBuilder
                    {
                        public function where(string $column): self
                        {
                            return $this;
                        }
                    }

                    /** @mixin QueryBuilder */
                    abstract class Record
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    final class Helper
                    {
                        public function marker(): int
                        {
                            return 1;
                        }
                    }

                    /** @mixin Helper */
                    final class AnnotatedRecord extends Record {}

                    $record = new AnnotatedRecord();
                    $a = $record->where("id");
                    $b = $record->marker();
                    $c = AnnotatedRecord::where("id");',
                'assertions' => [
                    '$a===' => 'QueryBuilder',
                    '$b===' => 'int',
                    '$c===' => 'QueryBuilder',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nearestOfInheritedMixinsWins' => [
                'code' => '<?php
                    final class GrandparentMixin
                    {
                        public function a(): int { return 1; }
                        public function b(): int { return 1; }
                        public function c(): int { return 1; }
                    }

                    final class ParentMixin
                    {
                        public function b(): string { return ""; }
                        public function c(): string { return ""; }
                    }

                    final class ChildMixin
                    {
                        public function c(): bool { return true; }
                    }

                    /** @mixin GrandparentMixin */
                    abstract class GrandparentHost
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin ParentMixin */
                    abstract class ParentHost extends GrandparentHost {}

                    /** @mixin ChildMixin */
                    final class ChildHost extends ParentHost {}

                    $host = new ChildHost();
                    $a = $host->a();
                    $b = $host->b();
                    $c = $host->c();',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'string',
                    '$c===' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedGenericMixinBindsTheTemplatesOfItsDeclaringClass' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Box
                    {
                        /** @return TValue */
                        public function get(): mixed
                        {
                            throw new RuntimeException();
                        }
                    }

                    final class Helper
                    {
                        public function marker(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @template T
                     * @mixin Box<T>
                     */
                    abstract class Base
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /**
                     * @extends Base<string>
                     * @mixin Helper
                     */
                    final class Child extends Base {}

                    $a = (new Child())->get();',
                'assertions' => [
                    '$a===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedStaticMixinBindsTheChildThatMixesInItsParent' => [
                'code' => '<?php
                    /** @template TModel of Model */
                    final class Builder
                    {
                        /** @return TModel */
                        public function first(): Model
                        {
                            throw new RuntimeException();
                        }
                    }

                    /** @mixin Builder<static> */
                    abstract class Model
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin Model */
                    final class Post extends Model {}

                    $a = (new Post())->first();
                    $b = Post::first();',
                'assertions' => [
                    '$a===' => 'Post',
                    '$b===' => 'Post',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'ownGenericMixinNextToInheritedTemplatedMixin' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Box
                    {
                        /** @return TValue */
                        public function get(): mixed
                        {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Proxy
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /**
                     * @template U
                     * @extends Proxy<stdClass>
                     * @mixin Box<U>
                     */
                    final class Child extends Proxy {}

                    /** @param Child<int> $child */
                    function get(Child $child): int
                    {
                        return $child->get();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'ownMixinPseudoMethodWinsOverInheritedOne' => [
                'code' => '<?php
                    /** @method int foo() */
                    final class ParentMixin
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @method string foo() */
                    final class ChildMixin
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin ParentMixin */
                    abstract class ParentHost
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin ChildMixin */
                    final class ChildHost extends ParentHost {}

                    $a = (new ChildHost())->foo();',
                'assertions' => [
                    '$a===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstDeclaredMixinWinsForAProperty' => [
                'code' => '<?php
                    final class First
                    {
                        public int $value = 1;
                    }

                    final class Second
                    {
                        public string $value = "";
                    }

                    /**
                     * @mixin First
                     * @mixin Second
                     */
                    final class Host
                    {
                        public function __get(string $name): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->value;',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pseudoMembersOfTheClassWinOverItsMixins' => [
                'code' => '<?php
                    final class Delegate
                    {
                        public string $value = "";

                        public function foo(): string
                        {
                            return "";
                        }

                        public static function bar(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @method int foo()
                     * @method static int bar()
                     * @property int $value
                     * @mixin Delegate
                     */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }

                        public function __get(string $name): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->foo();
                    $b = Host::bar();
                    $c = (new Host())->value;',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'int',
                    '$c===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinOfTheOwnMixinWinsOverTheParentMixin' => [
                'code' => '<?php
                    final class Deep
                    {
                        public function foo(): int
                        {
                            return 1;
                        }
                    }

                    final class ParentMixin
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /** @mixin Deep */
                    final class ChildMixin {}

                    /** @mixin ParentMixin */
                    abstract class Base
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin ChildMixin */
                    final class Child extends Base {}

                    $a = (new Child())->foo();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinOfATraitIsInheritedBeforeTheParentMixin' => [
                'code' => '<?php
                    final class TraitMixin
                    {
                        public function foo(): int
                        {
                            return 1;
                        }

                        public static function bar(): int
                        {
                            return 1;
                        }
                    }

                    final class ParentMixin
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /** @mixin TraitMixin */
                    trait ForwardsCalls {}

                    /** @mixin ParentMixin */
                    abstract class Base
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    final class Child extends Base
                    {
                        use ForwardsCalls;
                    }

                    $a = (new Child())->foo();
                    $b = Child::bar();',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classReachedThroughTwoMixinsIsLookedUpAtItsFirstPlace' => [
                'code' => '<?php
                    final class Shared
                    {
                        public function foo(): int
                        {
                            return 1;
                        }
                    }

                    final class Other
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /** @mixin Shared */
                    final class Left {}

                    /**
                     * @mixin Other
                     * @mixin Shared
                     */
                    final class Right {}

                    /**
                     * @mixin Left
                     * @mixin Right
                     */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->foo();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfAGenericMixinIsBound' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Box
                    {
                        /** @var TValue */
                        public mixed $value;

                        /** @param TValue $value */
                        public function __construct(mixed $value)
                        {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @template T
                     * @mixin Box<T>
                     */
                    final class Host
                    {
                        public function __get(string $name): mixed
                        {
                            return null;
                        }
                    }

                    /** @param Host<int> $host */
                    function value(Host $host): int
                    {
                        return $host->value;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinOfAGenericMixinIsFollowed' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Inner
                    {
                        /** @return TValue */
                        public function get(): mixed
                        {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T
                     * @mixin Inner<T>
                     */
                    final class Collection
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin Collection<string> */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->get();',
                'assertions' => [
                    '$a===' => 'string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedTemplatedMixinOfANonGenericChild' => [
                'code' => '<?php
                    final class Implementation
                    {
                        public function go(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Proxy
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @extends Proxy<Implementation> */
                    final class ImplementationProxy extends Proxy {}

                    $a = (new ImplementationProxy())->go();
                    $b = ImplementationProxy::go();',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticOfAGenericMixinIsTheGenericReceiver' => [
                'code' => '<?php
                    /** @template TValue */
                    final class Holder
                    {
                        /** @return TValue */
                        public function get(): mixed
                        {
                            throw new RuntimeException();
                        }
                    }

                    /**
                     * @template T
                     * @mixin Holder<static>
                     */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @var Host<string> $host */
                    $host = new Host();
                    $a = $host->get();',
                'assertions' => [
                    '$a===' => 'Host<string>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinOfASubclassOfTheParentKeepsStaticTheClass' => [
                'code' => '<?php
                    /** @template TModel */
                    final class Builder
                    {
                        /** @return TModel */
                        public function first(): mixed
                        {
                            throw new RuntimeException();
                        }
                    }

                    /** @mixin Builder<static> */
                    abstract class Model
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    class Eloquent extends Model {}

                    /** @mixin Eloquent */
                    final class User extends Model {}

                    $a = (new User())->first();',
                'assertions' => [
                    '$a===' => 'User',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pseudoMethodOfAnInterfaceWinsOverTheMixins' => [
                'code' => '<?php
                    final class Delegate
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /** @method int foo() */
                    interface HasFoo {}

                    /** @mixin Delegate */
                    final class Host implements HasFoo
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->foo();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstMixinWinsForPseudoAndRealMethods' => [
                'code' => '<?php
                    /** @method int foo() */
                    final class A
                    {
                        public function bar(): int
                        {
                            return 1;
                        }
                    }

                    /** @method string foo() */
                    final class B
                    {
                        public function bar(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @mixin A
                     * @mixin B
                     */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $foo = (new Host())->foo();
                    $bar = (new Host())->bar();',
                'assertions' => [
                    '$foo===' => 'int',
                    '$bar===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'propertyOfAMixinWithStaticIsBoundToTheClass' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository
                    {
                        /** @var T|null */
                        public mixed $current = null;
                    }

                    /** @mixin Repository<static> */
                    final class Host
                    {
                        public function __get(string $name): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Host())->current;',
                'assertions' => [
                    '$a===' => 'Host|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pseudoPropertyOfAMixinOfAnInterface' => [
                'code' => '<?php
                    final class Bar
                    {
                        public function x(): int
                        {
                            return 1;
                        }
                    }

                    /** @property Bar $not */
                    final class Foo
                    {
                        public function __get(string $name): mixed
                        {
                            return new Bar();
                        }
                    }

                    /** @mixin Foo */
                    interface HasNot {}

                    function not(HasNot $object): Bar
                    {
                        return $object->not;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classTemplateOfAPseudoMethodOfAGenericMixin' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @method T get()
                     */
                    final class Box
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @mixin Box<int> */
                    final class Holder
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $a = (new Holder())->get();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstClassCallableOfAPseudoMethodOfATemplatedMixin' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @method T get()
                     */
                    final class Box
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /**
                     * @template TMixin of object
                     * @mixin TMixin
                     */
                    final class Proxy
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    /** @param Proxy<Box<int>> $proxy */
                    function read(Proxy $proxy): int
                    {
                        $get = $proxy->get(...);

                        return $get();
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'intersectionPropertyNextToAMixinWithoutIt' => [
                'code' => '<?php
                    final class EmptyTarget {}

                    /** @mixin EmptyTarget */
                    trait ForwardsCalls {}

                    class Host
                    {
                        use ForwardsCalls;
                    }

                    class HasValue
                    {
                        public int $value = 1;
                    }

                    /** @param Host&HasValue $host */
                    function read(Host $host): int
                    {
                        return $host->value;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticPseudoMethodOfAnInterfaceWinsOverTheMixins' => [
                'code' => '<?php
                    final class Delegate
                    {
                        public static function foo(): string
                        {
                            return "";
                        }
                    }

                    /** @method static int foo() */
                    interface HasFoo {}

                    /** @mixin Delegate */
                    final class Host implements HasFoo
                    {
                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    $a = Host::foo();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pseudoMethodOfAMixinWithoutReturnTypeGoesThroughTheCallOfTheClass' => [
                'code' => '<?php
                    /** @method foo() */
                    final class Delegate
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): string
                        {
                            return "";
                        }
                    }

                    /** @mixin Delegate */
                    final class Host
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): int
                        {
                            return 1;
                        }
                    }

                    $a = (new Host())->foo();',
                'assertions' => [
                    '$a===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'staticCallThroughAMixinIsTheInstanceCall' => [
                'code' => '<?php
                    /** @template TModel of Model */
                    class Builder
                    {
                        /** @return $this */
                        public function where(): static
                        {
                            return $this;
                        }
                    }

                    /** @mixin Builder<static> */
                    abstract class Model
                    {
                        /** @param list<mixed> $args */
                        public function __call(string $name, array $args): mixed
                        {
                            return null;
                        }

                        /** @param list<mixed> $args */
                        public static function __callStatic(string $name, array $args): mixed
                        {
                            return null;
                        }
                    }

                    final class Customer extends Model {}

                    $a = Customer::where();
                    $b = (new Customer())->where();',
                'assertions' => [
                    '$a===' => 'Customer',
                    '$b===' => 'Customer',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nearestMixinWinsForAStaticMethodOfNestedMixins' => [
                'code' => '<?php
                    final class Deep
                    {
                        public static function foo(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @mixin Deep
                     */
                    final class Near
                    {
                        public static function foo(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @mixin Near
                     */
                    final class Host {}

                    $a = Host::foo();',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'staticMethodOnlyOfTheDeeperOfNestedMixins' => [
                'code' => '<?php
                    final class Deep
                    {
                        public static function deepOnly(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @mixin Deep
                     */
                    final class Near {}

                    /**
                     * @mixin Near
                     */
                    final class Host {}

                    $a = Host::deepOnly();',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'nearestMixinWinsForAMethodOfNestedMixins' => [
                'code' => '<?php
                    final class Deep
                    {
                        public function foo(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @mixin Deep
                     */
                    final class Near
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @mixin Near
                     */
                    final class Host {}

                    $a = (new Host())->foo();',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'staticMethodsOfSeveralMixins' => [
                'code' => '<?php
                    interface Mixin
                    {
                        public static function mix(): string;
                    }

                    interface OtherMixin
                    {
                        public static function otherMix(): int;
                    }

                    /**
                     * @mixin Mixin
                     * @mixin OtherMixin
                     */
                    class Bar {}

                    $a = Bar::mix();
                    $b = Bar::otherMix();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'mixinMethodChargesWhatCallDoes' => [
                'code' => '<?php
                    final class Connection {
                        public function send(string $data): int {
                            echo $data;
                            return 1;
                        }
                    }

                    /** @mixin Connection */
                    final class Wrapper {
                        /** @psalm-pure */
                        public function __call(string $name, array $args): int {
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function send(Wrapper $wrapper): int {
                        return $wrapper->send("x");
                    }',
            ],
            'validSimpleAnnotations' => [
                'code' => '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                        public static function __callStatic(string $name, array $args) {}
                    }

                    class Provider {
                        public function getString() : string {
                            return "hello";
                        }

                        public function setInteger(int $i) : void {}

                        public static function getInt() : int {
                            return 5;
                        }
                    }

                    /** @mixin Provider */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $b = $child::getInt();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'anotherSimpleExample' => [
                'code' => '<?php
                    /**
                     * @mixin B
                     */
                    class A {
                        /** @var B */
                        private $b;

                        public function __construct() {
                            $this->b = new B();
                        }

                        public function c(string $s) : void {}

                        /**
                         * @param array<mixed> $arguments
                         * @return mixed
                         */
                        public function __call(string $method, array $arguments)
                        {
                            return $this->b->$method(...$arguments);
                        }
                    }

                    class B {
                        public function b(): void {
                            echo "b";
                        }

                        public function c(int $s) : void {}
                    }

                    $a = new A();
                    $a->b();',
            ],
            'allowConstructor' => [
                'code' => '<?php
                    abstract class AParent {
                        protected int $i;

                        public function __construct() {
                            $this->i = 1;
                        }
                    }

                    class M {
                        public function __construct() {}
                    }

                    /**
                     * @mixin M
                     */
                    class A extends AParent {}',
            ],
            'implicitMixin' => [
                'code' => '<?php
                    function foo(string $dir) : void {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($dir)
                        );

                        while ($iterator->valid()) {
                            if (!$iterator->isDot() && $iterator->isLink()) {}

                            $iterator->next();
                        }
                    }',
            ],
            'wrapCustomIterator' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<1, 2>
                     */
                    class Subject implements Iterator {
                        /**
                         * the index method exists
                         *
                         * @param int $index
                         * @return bool
                         */
                        public function index($index) {
                            return true;
                        }

                        public function current() {
                            return 2;
                        }

                        public function next() {}

                        public function key() {
                            return 1;
                        }

                        public function valid() {
                            return false;
                        }

                        public function rewind() {}
                    }

                    $iter = new IteratorIterator(new Subject());
                    $b = $iter->index(0);',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'templatedMixin' => [
                'code' => '<?php

                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /** @return T */
                        abstract public function hi();
                    }

                    /**
                     * @mixin Foo<string>
                     */
                    class Bar {}

                    $bar = new Bar();
                    $b = $bar->hi();',
                'assertions' => [
                    '$b' => 'string',
                ],
            ],
            'templatedMixinSelf' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Animal {
                        /** @var T */
                        private $item;

                        /**
                         * @param T $item
                         */
                        public function __construct($item) {
                            $this->item = $item;
                        }

                        /**
                         * @return T
                         */
                        public function get() {
                            return $this->item;
                        }
                    }

                    /**
                     * @mixin Animal<self>
                     */
                    class Dog {
                        public function __construct() {}
                    }

                    function getDog(): Dog {
                        return (new Dog())->get();
                    }',
            ],
            'inheritPropertyAnnotations' => [
                'code' => '<?php
                    /**
                     * @property string $foo
                     */
                    class A {}

                    /**
                     * @mixin A
                     */
                    class B {
                        /** @return mixed */
                        public function __get(string $s) {
                            return 5;
                        }
                    }

                    function toArray(B $b) : string {
                        return $b->foo;
                    }',
            ],
            'inheritTemplatedMixinWithStatic' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     * @psalm-consistent-constructor
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }

                        public function __callStatic(string $name, array $args) {
                            return (new static)->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress PropertyNotSetInConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test2() : FooGrandChild {
                        return FooGrandChild::type();
                    }

                    function test() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }',
            ],
            'inheritTemplatedMixinWithStaticAndFinalClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return self<T>
                         */
                        public function getMixin() {
                            return $this;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    /**
                    * @psalm-return Mixin<FooGrandChild>
                    */
                    function test() : Mixin {
                        return (new FooGrandChild)->getMixin();
                    }',
            ],
            'mixinParseWithTextAfter' => [
                'code' => '<?php
                    class M {}

                    /**
                     * @mixin M
                     * Hello
                     */
                    class C {}',
            ],
            'templatedMixinWithTemplateWithStatic' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     * @mixin T
                     */
                    class Builder {
                        private $t;

                        /** @param T $t */
                        public function __construct(object $t) {
                            $this->t = $t;
                        }

                        public function __call(string $method, array $parameters) {
                            /** @psalm-suppress MixedMethodCall */
                            return $this->t->$method($parameters);
                        }
                    }

                    /**
                     * @method self active()
                     */
                    class Model {
                        /**
                         * @return Builder<static>
                         */
                        public function query(): Builder {
                            return new Builder($this);
                        }

                        public function __call(string $method, array $parameters) {
                            if ($method === "active") {
                                return new Model();
                            }
                        }
                    }

                    /** @param Builder<Model> $b */
                    function foo(Builder $b) : Model {
                        return $b->active();
                    }',
            ],
            'multipleMixins' => [
                'code' => '<?php
                    class MixinA {
                        function a(): string { return "foo"; }
                    }

                    class MixinB {
                        function b(): int { return 0; }
                    }

                    /**
                     * @mixin MixinA
                     * @mixin MixinB
                     */
                    class Test {}

                    $test = new Test();

                    $a = $test->a();
                    $b = $test->b();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'inheritMultipleTemplatedMixinsWithStatic' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T
                     */
                    class OtherMixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function other() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @template T2 as string
                     * @mixin Mixin<T>
                     * @mixin OtherMixin<T2>
                     * @psalm-consistent-constructor
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        /** @var OtherMixin<T2> */
                        public object $otherObj;

                        public function __call(string $name, array $args) {
                            if ($name === "test") {
                                return $this->obj->$name(...$args);
                            }

                            return $this->otherObj->$name(...$args);
                        }

                        public function __callStatic(string $name, array $args) {
                            if ($name === "test") {
                                return (new static)->obj->$name(...$args);
                            }

                            return (new static)->otherObj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static, string>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress PropertyNotSetInConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test() : FooGrandChild {
                        return FooGrandChild::type();
                    }

                    function testStatic() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }

                    function other() : string {
                        return FooGrandChild::other();
                    }

                    function otherStatic() : string {
                        return (new FooGrandChild)->other();
                    }',
            ],
            'multipleMixinsWithSameMethod' => [
                'code' => '<?php

                    class Mix1
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    class Mix2
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @mixin Mix1
                     * @mixin Mix2
                     */
                    class Bar
                    {

                    }

                    $bar = new Bar();

                    $bar->foo();',
            ],
            'severalMixinsDeclaringTheSameMethodBindStaticToTheHost' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /**
                     * @mixin Labels
                     * @mixin Repository<static>
                     */
                    final class Post {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /**
                     * @mixin Repository<static>
                     * @mixin Labels
                     */
                    final class Tag {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /** @mixin Repository<static> */
                    final class Comment {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    $p = (new Post)->first();
                    $t = (new Tag)->first();
                    $c = (new Comment)->first();',
                'assertions' => [
                    '$p===' => 'string',
                    '$t===' => 'Tag|null',
                    '$c===' => 'Comment|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'severalMixinsDeclaringTheSameMethodBindStaticToTheHostForAStaticCall' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /**
                     * @mixin Repository<static>
                     * @mixin Labels
                     */
                    final class Post {
                        public function __call(string $name, array $args): mixed { return null; }
                        public static function __callStatic(string $name, array $args): mixed { return null; }
                    }

                    $post = Post::first();',
                'assertions' => [
                    '$post===' => 'Post|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'mixinCallDoesNotRebindTheTemplatedMixinOfTheMixinTarget' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /** @mixin Target */
                    final class Host {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /**
                     * @template T
                     * @mixin Repository<T>
                     */
                    final class Target {
                        public function foo(): void {}
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    (new Host)->foo();

                    /** @var Target<string> $target */
                    $target = new Target;
                    $r = $target->first();',
                'assertions' => [
                    '$r===' => 'null|string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritedShadowedMixinBindsStaticToTheChildHost' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /**
                     * @mixin Repository<static>
                     * @mixin Labels
                     */
                    abstract class Model {
                        public function __call(string $name, array $args): mixed { return null; }
                        public static function __callStatic(string $name, array $args): mixed { return null; }
                    }

                    final class Child extends Model {}

                    $instance = (new Child)->first();
                    $static = Child::first();',
                'assertions' => [
                    '$instance===' => 'Child|null',
                    '$static===' => 'Child|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'shadowedMixinCalledThroughThisBindsStaticToTheHost' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /**
                     * @mixin Repository<static>
                     * @mixin Labels
                     */
                    final class Post {
                        public function __call(string $name, array $args): mixed { return null; }

                        public function run(): void {
                            $r = $this->first();
                            /** @psalm-check-type-exact $r = Post|null */
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'shadowedMixinOfATemplatedHostBindsTheTemplate' => [
                'code' => '<?php
                    /** @template T */
                    final class Repository {
                        /** @return T|null */
                        public function first(): mixed { return null; }
                    }

                    final class Labels {
                        public function first(): string { return ""; }
                    }

                    /**
                     * @template T
                     * @mixin Repository<T>
                     * @mixin Labels
                     */
                    final class Box {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /** @var Box<int> $box */
                    $box = new Box;
                    $r = $box->first();',
                'assertions' => [
                    '$r===' => 'int|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstOfSeveralTemplatedMixinsDeclaringTheMethodWins' => [
                'code' => '<?php
                    final class A {
                        public function foo(): int { return 1; }
                    }

                    final class B {
                        public function foo(): string { return ""; }
                    }

                    /**
                     * @template TA of object
                     * @template TB of object
                     * @mixin TA
                     * @mixin TB
                     */
                    final class Host {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /** @var Host<A, B> $host */
                    $host = new Host;
                    $r = $host->foo();',
                'assertions' => [
                    '$r===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'firstOfSeveralTemplatedMixinsDeclaringTheMethodAsPseudoMethodWins' => [
                'code' => '<?php
                    /** @method int foo() */
                    final class PA {}

                    /** @method string foo() */
                    final class PB {}

                    final class RA {
                        public function foo(): int { return 1; }
                    }

                    /**
                     * @template TA of object
                     * @template TB of object
                     * @mixin TA
                     * @mixin TB
                     */
                    final class H {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /** @var H<PA, PB> $h */
                    $h = new H;
                    $r = $h->foo();

                    /** @var H<RA, PB> $h2 */
                    $h2 = new H;
                    $r2 = $h2->foo();',
                'assertions' => [
                    '$r===' => 'int',
                    '$r2===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templatedMixinBindStatic' => [
                'code' => '<?php
                    /**
                     * @template-covariant TModel of Model
                     */
                    class QueryBuilder {
                        /**
                         * @return list<TModel>
                         */
                        public function getInner() {
                            return [];
                        }
                    }

                    /**
                     * @mixin QueryBuilder<static>
                     */
                    abstract class Model {}

                    class FooModel extends Model {}

                    $f = new FooModel();
                    $g = $f->getInner();',
                'assertions' => [
                    '$g' => 'list<FooModel>',
                ],
            ],
            'mixinInheritMagicMethods' => [
                'code' => '<?php
                    /**
                     * @method $this active()
                     */
                    class A {
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin A
                     */
                    class B {
                        public function __call(string $name, array $arguments) {}
                    }

                    $b = new B;
                    $c = $b->active();',
                'assertions' => [
                    '$c' => 'B',
                ],
            ],
            'nestedMixinResolvesTransitiveMethod' => [
                'code' => '<?php
                    namespace T;

                    class QueryB {
                        public function whereNull(string $c): static { return $this; }
                    }

                    /** @mixin \T\QueryB */
                    class EloquentB {}

                    /** @mixin \T\EloquentB */
                    class ClassHost {}

                    /** @mixin \T\EloquentB */
                    interface IfaceContract {}

                    function direct(EloquentB $q): void   { $q->whereNull("x"); }
                    function viaClass(ClassHost $q): void { $q->whereNull("x"); }
                    function viaIface(IfaceContract $q): void { $q->whereNull("x"); }',
            ],
            'nestedMixinResolvesReturnType' => [
                'code' => '<?php
                    namespace T;

                    class Base {
                        public function value(): int { return 1; }
                    }

                    /** @mixin \T\Base */
                    class Middle {}

                    /** @mixin \T\Middle */
                    class Top {}

                    $top = new Top();
                    $result = $top->value();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinDirectShadowsTransitive' => [
                'code' => '<?php
                    namespace T;

                    class Deep {
                        public function foo(): int { return 1; }
                    }

                    /** @mixin \T\Deep */
                    class Near {
                        public function foo(): string { return ""; }
                    }

                    /** @mixin \T\Near */
                    class Host {}

                    $host = new Host();
                    $result = $host->foo();',
                'assertions' => [
                    '$result' => 'string',
                ],
            ],
            'mutualMixinsDoNotRecurseInfinitely' => [
                'code' => '<?php
                    namespace T;

                    /** @mixin \T\Pong */
                    class Ping {
                        public function ping(): void {}
                    }

                    /** @mixin \T\Ping */
                    class Pong {
                        public function pong(): void {}
                    }

                    function test(Ping $p): void {
                        $p->ping();
                        $p->pong();
                    }',
            ],
            'nestedMixinStaticCall' => [
                'code' => '<?php
                    namespace T;

                    class StaticBase {
                        public static function make(): int { return 1; }
                    }

                    /** @mixin \T\StaticBase */
                    class StaticMiddle {}

                    /** @mixin \T\StaticMiddle */
                    class StaticTop {}

                    $result = StaticTop::make();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinPropertyFetch' => [
                'code' => '<?php
                    namespace T;

                    class PropBase {
                        public int $count = 0;
                    }

                    /** @mixin \T\PropBase */
                    class PropMiddle {}

                    /** @mixin \T\PropMiddle */
                    class PropTop {}

                    $top = new PropTop();
                    $result = $top->count;',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'templatedMixinPropertyFetchViaThisInSubclass' => [
                'code' => '<?php
                    namespace T;

                    /** @property string $headline */
                    class Post {
                        public function __get(string $name): string { return ""; }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {
                        public function __get(string $name): int { return 0; }
                    }

                    /** @extends Wrapper<Post> */
                    final class PostWrapper extends Wrapper {
                        public function title(): string {
                            $result = $this->headline;
                            /** @psalm-check-type-exact $result = string */
                            return $result;
                        }
                    }',
            ],
            'templatedMixinPropertyFetchViaGenericReceiver' => [
                'code' => '<?php
                    namespace T;

                    /** @property string $headline */
                    class Post {
                        public function __get(string $name): string { return ""; }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {
                        public function __get(string $name): int { return 0; }
                    }

                    /** @param Wrapper<Post> $w */
                    function test(Wrapper $w): string {
                        $result = $w->headline;
                        /** @psalm-check-type-exact $result = string */
                        return $result;
                    }',
            ],
            'templatedMixinPropertyFetchViaSubclassReceiver' => [
                'code' => '<?php
                    namespace T;

                    /** @property string $headline */
                    class Post {
                        public function __get(string $name): string { return ""; }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {
                        public function __get(string $name): int { return 0; }
                    }

                    /** @extends Wrapper<Post> */
                    final class PostWrapper extends Wrapper {}

                    function test(PostWrapper $w): string {
                        $result = $w->headline;
                        /** @psalm-check-type-exact $result = string */
                        return $result;
                    }',
            ],
            'templatedMixinUnresolvedKeepsIntersectionFallback' => [
                'code' => '<?php
                    namespace T;

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    class Host {}

                    class HasProp {
                        public int $views = 0;
                    }

                    /** @param Host&HasProp $x */
                    function test(Host $x): int {
                        return $x->views;
                    }',
            ],
            'templatedMixinResolvedWithoutMatchKeepsIntersectionFallback' => [
                'code' => '<?php
                    namespace T;

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    class Host {}

                    class EmptyTarget {}

                    class HasProp {
                        public int $views = 0;
                    }

                    /** @param Host<EmptyTarget>&HasProp $x */
                    function test(Host $x): int {
                        return $x->views;
                    }',
            ],
            'ownMixinOfTheChildWinsOverTheTemplatedMixinOfTheParentForAProperty' => [
                'code' => '<?php
                    namespace T;

                    class ParentTarget {
                        public int $views = 0;
                    }

                    class LocalTarget {
                        public string $views = "";
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    class Host {}

                    /**
                     * @extends Host<ParentTarget>
                     * @mixin LocalTarget
                     */
                    class Child extends Host {}

                    $child = new Child();
                    $result = $child->views;',
                'assertions' => [
                    '$result' => 'string',
                ],
            ],
            'templatedMixinPropertyFetchRealProperty' => [
                'code' => '<?php
                    namespace T;

                    class Post {
                        public int $views = 0;
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {}

                    /** @extends Wrapper<Post> */
                    final class PostWrapper extends Wrapper {}

                    $w = new PostWrapper();
                    $result = $w->views;',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinDiamondResolvesSharedTarget' => [
                'code' => '<?php
                    namespace T;

                    class Shared {
                        public function shared(): int { return 1; }
                    }

                    /** @mixin \T\Shared */
                    class LeftArm {}

                    /** @mixin \T\Shared */
                    class RightArm {}

                    /**
                     * @mixin \T\LeftArm
                     * @mixin \T\RightArm
                     */
                    class Diamond {}

                    $d = new Diamond();
                    $result = $d->shared();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinTargetInheritsMixinFromParent' => [
                'code' => '<?php
                    namespace T;

                    class GrandChildMethods {
                        public function deep(): int { return 1; }
                    }

                    /** @mixin \T\GrandChildMethods */
                    class MixinParent {}

                    class MixinChild extends MixinParent {}

                    /** @mixin \T\MixinChild */
                    class HostDeclaredFirst {}

                    $host = new HostDeclaredFirst();
                    $result = $host->deep();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinOfTheFirstSiblingWins' => [
                'code' => '<?php
                    namespace T;

                    class SibTargetA { public function val(): int { return 1; } }
                    class SibTargetB { public function val(): string { return ""; } }

                    /** @mixin \T\SibTargetA */
                    class SibLeft {}

                    /** @mixin \T\SibTargetB */
                    class SibRight {}

                    /**
                     * @mixin \T\SibLeft
                     * @mixin \T\SibRight
                     */
                    class SibHost {}

                    $host = new SibHost();
                    $result = $host->val();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinPseudoMethodViaMagicCall' => [
                'code' => '<?php
                    namespace T;

                    /** @method string pseudo() */
                    class PseudoBase {
                        public function __call(string $n, array $a) {}
                    }

                    /** @mixin \T\PseudoBase */
                    class PseudoMiddle {}

                    /** @mixin \T\PseudoMiddle */
                    class PseudoTop {
                        public function __call(string $n, array $a) {}
                    }

                    $result = (new PseudoTop())->pseudo();',
                'assertions' => [
                    '$result' => 'string',
                ],
            ],
            'PHP81-firstClassCallableStaticCallForwardedThroughMixin' => [
                'code' => '<?php
                    class Builder {
                        public function has(string $relation): self { return $this; }
                    }

                    /** @mixin Builder */
                    class Model {
                        public static function __callStatic(string $name, array $args): mixed {
                            return (new Builder())->$name(...$args);
                        }
                    }

                    $has = Model::has(...);',
                'assertions' => [
                    '$has' => 'Closure[impure](string):Builder',
                ],
            ],
            'mixinMissingMethodAnalyzesNarrowingArgumentOnce' => [
                'code' => '<?php
                    final class Inner {}

                    /** @mixin Inner */
                    final class Outer
                    {
                        public function __call(string $name, array $arguments): mixed
                        {
                            return [$name, $arguments];
                        }
                    }

                    function f(Outer $o, ?DateTimeImmutable $d): void
                    {
                        $o->missing($d ?? throw new LogicException());
                    }',
                'assertions' => [],
                'ignored_issues' => ['UndefinedMagicMethod'],
                'php_version' => '8.0',
            ],
            'unsealedMixinMissingMethodAnalyzesNarrowingArgumentOnce' => [
                'code' => '<?php
                    final class Inner {}

                    /**
                     * @mixin Inner
                     * @psalm-no-seal-methods
                     */
                    final class Outer
                    {
                        public function __call(string $name, array $arguments): mixed
                        {
                            return [$name, $arguments];
                        }
                    }

                    function f(Outer $o, ?DateTimeImmutable $d): void
                    {
                        $o->missing($d ?? throw new LogicException());
                    }',
                'assertions' => [],
                'ignored_issues' => ['UndefinedMagicMethod'],
                'php_version' => '8.0',
            ],
            'mixinMissingStaticMethodAnalyzesNarrowingArgumentOnce' => [
                'code' => '<?php
                    final class Inner {}

                    /** @mixin Inner */
                    final class Outer
                    {
                        public static function __callStatic(string $name, array $arguments): mixed
                        {
                            return [$name, $arguments];
                        }
                    }

                    function f(?DateTimeImmutable $d): void
                    {
                        Outer::missing($d ?? throw new LogicException());
                    }',
                'assertions' => [],
                'ignored_issues' => ['UndefinedMagicMethod'],
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
            'undefinedMixinOfATrait' => [
                'code' => '<?php
                    /** @mixin Missing */
                    trait ForwardsCalls {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'mixinCallDoesNotSuppressTheMixinCheckOfTheMixinTarget' => [
                'code' => '<?php
                    /** @mixin Target */
                    final class Host {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    function call(Host $host): void {
                        $host->foo();
                    }

                    /** @mixin Missing */
                    class Target {
                        public function foo(): void {}
                    }',
                'error_message' => 'UndefinedDocblockClass',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'staticMethodOfNoneOfSeveralMixins' => [
                'code' => '<?php
                    interface Mixin
                    {
                        public static function mix(): void;
                    }

                    interface OtherMixin
                    {
                        public static function otherMix(): void;
                    }

                    /**
                     * @mixin Mixin
                     * @mixin OtherMixin
                     */
                    class Bar {}

                    Bar::missing();',
                'error_message' => 'UndefinedMethod',
            ],
            'mixinMethodChargesTheCallOfTheClassUsingTheMixin' => [
                'code' => '<?php
                    final class Connection {
                        /** @psalm-pure */
                        public function send(string $data): int {
                            return strlen($data);
                        }
                    }

                    /** @mixin Connection */
                    final class Wrapper {
                        public function __call(string $name, array $args): int {
                            echo $name;
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function send(Wrapper $wrapper): int {
                        return $wrapper->send("x");
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'templatedMixinMethodChargesTheCallOfTheClassUsingTheMixin' => [
                'code' => '<?php
                    final class Connection {
                        /** @psalm-pure */
                        public function send(string $data): int {
                            return strlen($data);
                        }
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    final class Wrapper {
                        public function __call(string $name, array $args): int {
                            echo $name;
                            return 1;
                        }
                    }

                    /**
                     * @param Wrapper<Connection> $wrapper
                     * @psalm-pure
                     */
                    function send(Wrapper $wrapper): int {
                        return $wrapper->send("x");
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'undefinedMixinClass' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'undefinedMixinClassWithPropertyFetch' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyFetch_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __get(string $name): string {
                            return "";
                        }
                    }

                    (new A)->foo;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'templatedMixinPropertyFetchMissingOnResolvedMixin' => [
                'code' => '<?php
                    namespace T;

                    /** @property string $headline */
                    class Post {
                        public function __get(string $name): string { return ""; }
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {
                        public function __get(string $name): int { return 0; }
                    }

                    /** @extends Wrapper<Post> */
                    final class PostWrapper extends Wrapper {}

                    function test(PostWrapper $w): void {
                        echo $w->missing;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'templatedMixinPropertyFetchMissingOnResolvedMixinViaThis' => [
                'code' => '<?php
                    namespace T;

                    class Post {
                        public int $views = 0;
                    }

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {}

                    /** @extends Wrapper<Post> */
                    final class PostWrapper extends Wrapper {
                        public function test(): void {
                            echo $this->missing;
                        }
                    }',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'templatedMixinPropertyFetchUnionIsNotPicked' => [
                'code' => '<?php
                    namespace T;

                    /** @property string $headline */
                    class A {
                        public function __get(string $name): string { return ""; }
                    }

                    class B {}

                    /**
                     * @template T of object
                     * @mixin T
                     */
                    abstract class Wrapper {
                        public function __get(string $name): int { return 0; }
                    }

                    /** @extends Wrapper<A|B> */
                    final class AorBWrapper extends Wrapper {}

                    function test(AorBWrapper $w): void {
                        echo $w->headline;
                    }',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyAssignment' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedMixinClassWithPropertyAssignment_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __set(string $name, string $value) {}
                    }

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'undefinedMixinClassWithMethodCall' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithMethodCall_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public function __call(string $name, array $arguments) {}
                    }

                    (new A)->foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    A::foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    A::foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'inheritTemplatedMixinWithSelf' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<self>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'mixinStaticCallShouldNotPolluteContext' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Foo
                    {
                        public function foobar(): void {}
                    }

                    /**
                     * @template T
                     * @mixin Foo<T>
                     */
                    class Bar
                    {
                        public function baz(): self
                        {
                            self::foobar();
                            return $__tmp_mixin_var__;
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'unparseableMixinTypeIsAnIssue' => [
                'code' => '<?php
                    /** @mixin Foo< */
                    class A {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:27 -'
                    . ' No generic params provided for type in docblock for A',
            ],
        ];
    }

    public function testTransitiveMixinIsNotCheckedAtHostDeclaration(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                namespace T;

                /** @mixin \T\Mid */
                class Host {}

                /** @mixin \T\Deep */
                class Mid {}

                /** @deprecated */
                class Deep {
                    public function deep(): void {}
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $deprecated_class_lines = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'DeprecatedClass') {
                $deprecated_class_lines[] = $issue->line_from;
            }
        }

        // Only Mid names the deprecated class; Host merely reaches it through Mid.
        $this->assertSame([8], $deprecated_class_lines);
    }

    public function testInvalidMixinTypeDoesNotDiscardTheNextMixin(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                final class Provider
                {
                    public function hello(): string
                    {
                        return "hello";
                    }
                }

                /**
                 * @mixin Foo<
                 * @mixin Provider
                 */
                final class Host {}

                function greet(Host $host): string
                {
                    return $host->hello();
                }',
        );
        $this->analyzeFile($file_path, new Context());

        $issue_types = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $issue_types[] = $issue->type;
        }

        $this->assertSame(['InvalidDocblock'], $issue_types);
        $this->assertSame(
            ['Provider'],
            array_map(
                static fn(Atomic $mixin): string => $mixin->getId(),
                $this->project_analyzer->getCodebase()->classlike_storage_provider->get('Host')->getMixins(),
            ),
        );
    }

    public function testUnparseableMixinAloneBehavesLikeNoMixin(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @mixin Foo< */
                final class A {}',
        );
        $this->analyzeFile($file_path, new Context());

        $issue_types = [];
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $issue_types[] = $issue->type;
        }

        $this->assertSame(['InvalidDocblock'], $issue_types);
        $this->assertSame(
            [],
            $this->project_analyzer->getCodebase()->classlike_storage_provider->get('A')->getMixins(),
        );
    }
}
