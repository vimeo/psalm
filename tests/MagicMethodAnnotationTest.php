<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class MagicMethodAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testPhpDocMethodWhenUndefined(): void
    {
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method string getString()
                 * @method  void setInteger(int $integer)
                 * @method setString(int $integer)
                 * @method  getBool(string $foo) : bool
                 * @method (string|int)[] getArray()
                 * @method (callable() : string) getCallable()
                 */
                class Child {}

                $child = new Child();

                $a = $child->getString();
                $child->setInteger(4);
                /** @psalm-suppress MixedAssignment */
                $b = $child->setString(5);
                $c = $child->getBool("hello");
                $d = $child->getArray();
                $e = $child->getCallable();'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocMethodWhenTemplated(): void
    {
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @template T */
                class A {
                    /** @return ?T */
                    public function find() {}
                }

                class B extends A {}

                class Obj {}

                /**
                 * @method Obj|null find()
                 */
                class C extends B {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testAnnotationWithoutCallConfig(): void
    {
        $this->expectExceptionMessage('UndefinedMethod');
        $this->expectException(CodeException::class);
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method string getString()
                 */
                class Child {}

                $child = new Child();

                $child->getString();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testOverrideParentClassRetunType(): void
    {
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class ParentClass {
                    public static function getMe() : self {
                        return new self();
                    }
                }

                /**
                 * @method getMe() : Child
                 */
                class Child extends ParentClass {}

                $child = Child::getMe();'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);

        $this->assertSame('Child', (string) $context->vars_in_scope['$child']);
    }

    public function testOverrideExceptionMethodReturn(): void
    {
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method int getCode()
                 */
                class MyException extends Exception {}

                function foo(MyException $e): int {
                    return $e->getCode();
                }'
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'validSimpleAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString() dsa sada
                     * @method  void setInteger(int $integer) dsa sada
                     * @method setString(int $integer) dsa sada
                     * @method setMixed(mixed $foo) dsa sada
                     * @method setImplicitMixed($foo) dsa sada
                     * @method setAnotherImplicitMixed( $foo, $bar,$baz) dsa sada
                     * @method setYetAnotherImplicitMixed( $foo  ,$bar,  $baz    ) dsa sada
                     * @method  getBool(string $foo)  :   bool dsa sada
                     * @method (string|int)[] getArray() with some text dsa sada
                     * @method (callable() : string) getCallable() dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);
                    /** @psalm-suppress MixedAssignment */
                    $b = $child->setString(5);
                    $c = $child->getBool("hello");
                    $d = $child->getArray();
                    $e = $child->getCallable();
                    $child->setMixed("hello");
                    $child->setMixed(4);
                    $child->setImplicitMixed("hello");
                    $child->setImplicitMixed(4);',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'mixed',
                    '$c' => 'bool',
                    '$d' => 'array<array-key, int|string>',
                    '$e' => 'callable():string',
                ],
            ],
            'validAnnotationWithDefault' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setArray(array $arr = array(), int $foo = 5) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setArray(["boo"]);
                    $child->setArray(["boo"], 8);',
            ],
            'validAnnotationWithByRefParam' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @template T
                     * @method void configure(string $string, array &$arr)
                     */
                    class Child extends ParentClass
                    {
                        /** @psalm-param T $t */
                        public function getChild($t): void {}
                    }
                    $child = new Child();

                    $array = [];
                    $child->configure("foo", $array);',
            ],
            'validAnnotationWithNonEmptyDefaultArray' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setArray(array $arr = [1, 2, 3]) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setArray(["boo"]);
                    $child->setArray(["boo"]);',
            ],
            'validAnnotationWithNonEmptyDefaultOldStyleArray' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setArray(array $arr = array(1, 2, 3)) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setArray(["boo"]);
                    $child->setArray(["boo"]);',
            ],
            'validStaticAnnotationWithDefault' => [
                '<?php
                    class ParentClass {
                        public static function __callStatic(string $name, array $args) {}
                    }

                    /**
                     * @method static string getString(int $foo) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child::getString(5);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'validAnnotationWithVariadic' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setInts(int ...$foo) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setInts(1, 2, 3, 4);',
            ],
            'validUnionAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool dsa sada
                     * @method void setAnotherArray(int[]|string[] $arr = [], int $foo = 5) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $b = $child->setBool("hello", true);
                    $c = $child->setBool("hello", "true");
                    $child->setAnotherArray(["boo"]);',
                'assertions' => [
                    '$b' => 'bool',
                    '$c' => 'bool',
                ],
            ],
            'namespacedValidAnnotations' => [
                '<?php
                    namespace Foo;

                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $c = $child->setBool("hello", true);
                    $c = $child->setBool("hello", "true");',
            ],
            'globalMethod' => [
                '<?php
                    /** @method void global() */
                    class A {
                        public function __call(string $s) {}
                    }',
            ],
            'magicMethodInternalCall' => [
                '<?php
                    /**
                     * @method I[] work()
                     */
                    class I {
                        function __call(string $method, array $args) { return [new I, new I]; }

                        function zugzug(): void {
                            echo count($this->work());
                        }
                    }',
            ],
            'magicMethodOverridesParentWithMoreSpecificType' => [
                '<?php
                    class C {}
                    class D extends C {}

                    class A {
                        public function foo(string $s) : C {
                            return new C;
                        }
                    }

                    /** @method D foo(string $s) */
                    class B extends A {}',
            ],
            'complicatedMagicMethodInheritance' => [
                '<?php
                    class BaseActiveRecord {
                        /**
                         * @param string $class
                         * @param array $link
                         * @return ActiveQueryInterface
                         */
                        public function hasMany($class, $link)
                        {
                            return new ActiveQuery();
                        }
                    }

                    /**
                     * @method ActiveQuery hasMany($class, array $link)
                     */
                    class ActiveRecord extends BaseActiveRecord {}

                    interface ActiveQueryInterface {}

                    class ActiveQuery implements ActiveQueryInterface {
                        /**
                         * @param string $tableName
                         * @param array $link
                         * @param callable $callable
                         * @return $this
                         */
                        public function viaTable($tableName, $link, callable $callable = null)
                        {
                            return $this;
                        }
                    }

                    class Boom extends ActiveRecord {
                        /**
                         * @return ActiveQuery
                         */
                        public function getUsers()
                        {
                            $query = $this->hasMany("User", ["id" => "user_id"])
                                ->viaTable("account_to_user", ["account_id" => "id"]);

                            return $query;
                        }
                    }',
            ],
            'magicMethodReturnSelf' => [
                '<?php
                    /**
                     * @method static self getSelf()
                     * @method $this getThis()
                     */
                    class C {
                        public static function __callStatic(string $c, array $args) {}
                        public function __call(string $c, array $args) {}
                    }

                    $a = C::getSelf();
                    $b = (new C)->getThis();',
                [
                    '$a' => 'C',
                    '$b' => 'C',
                ],
            ],
            'allowMagicMethodStatic' => [
                '<?php
                    /** @method static getStatic() */
                    class C {
                        public function __call(string $c, array $args) {}
                    }

                    class D extends C {}

                    $c = (new C)->getStatic();
                    $d = (new D)->getStatic();',
                [
                    '$c' => 'C',
                    '$d' => 'D',
                ],
            ],
            'validSimplePsalmAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @psalm-method string getString() dsa sada
                     * @psalm-method  void setInteger(int $integer) dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'overrideMethodAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method int getString() dsa sada
                     * @method  void setInteger(string $integer) dsa sada
                     * @psalm-method string getString() dsa sada
                     * @psalm-method  void setInteger(int $integer) dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $child->setInteger(4);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'alwaysAllowAnnotationOnInterface' => [
                '<?php
                    /**
                     * @method string sayHello()
                     */
                    interface A {}

                    function makeConcrete() : A {
                        return new class implements A {
                            function sayHello() : string {
                                return "Hello";
                            }
                        };
                    }

                    echo makeConcrete()->sayHello();',
            ],
            'inheritInterfacePseudoMethodsFromParent' => [
                '<?php
                    namespace Foo;

                    interface ClassMetadata {}
                    interface ORMClassMetadata extends ClassMetadata {}

                    interface EntityManagerInterface {
                        public function getClassMetadata() : ClassMetadata;
                    }

                    /**
                     * @method ORMClassMetadata getClassMetadata()
                     * @method int getOtherMetadata()
                     */
                    interface ORMEntityManagerInterface extends EntityManagerInterface{}

                    interface ConcreteEntityManagerInterface extends ORMEntityManagerInterface {}

                    /** @psalm-suppress InvalidReturnType */
                    function em(): ORMEntityManagerInterface {}
                    /** @psalm-suppress InvalidReturnType */
                    function concreteEm(): ConcreteEntityManagerInterface {}

                    function test(ORMClassMetadata $metadata): void {}
                    function test2(int $metadata): void {}

                    test(em()->getClassMetadata());
                    test(concreteEm()->getClassMetadata());

                    test2(em()->getOtherMetadata());
                    test2(concreteEm()->getOtherMetadata());',
            ],
            'fullyQualifiedParam' => [
                '<?php
                    namespace Foo {
                        /**
                         * @method  void setInteger(\Closure $c)
                         */
                        class Child {
                            public function __call(string $s, array $args) {}
                        }
                    }

                    namespace {
                        $child = new Foo\Child();
                        $child->setInteger(function() : void {});
                    }',
            ],
            'allowMethodsNamedBooleanAndInteger' => [
                '<?php
                    /**
                     * @method boolean(int $foo) : bool
                     * @method integer(int $foo) : bool
                     */
                    class Child {
                        public function __call(string $name, array $args) {}
                    }

                    $child = new Child();

                    $child->boolean(5);
                    $child->integer(5);'
            ],
            'overrideWithSelfBeforeMethodName' => [
                '<?php
                    class A {
                        public static function make(): self {
                            return new self();
                        }
                    }

                    /**
                     * @method static self make()
                     */
                    class B extends A {}

                    function makeB(): B {
                        return B::make();
                    }'
            ],
            'validMethodAsAnnotation' => [
                '<?php
                    /**
                     * @method string as(string $value)
                     */
                    class Foo {}'
            ],
            'annotationWithSealedSuppressingUndefinedMagicMethod' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString()
                     */
                    class Child extends ParentClass {}

                    $child = new Child();
                    /** @psalm-suppress UndefinedMagicMethod */
                    $child->foo();'
            ],
            'allowFinalOverrider' => [
                '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public static function foo()
                        {
                            return new static();
                        }

                        final public function __construct() {}
                    }

                    /**
                     * @method static B foo()
                     */
                    final class B extends A {}'
            ],
            'namespacedMethod' => [
                '<?php
                    declare(strict_types = 1);

                    namespace App;

                    interface FooInterface {}

                    /**
                     * @method \IteratorAggregate<int, FooInterface> getAll():\IteratorAggregate
                     */
                    class Foo
                    {
                        private \IteratorAggregate $items;

                        /**
                         * @psalm-suppress MixedReturnTypeCoercion
                         */
                        public function getAll(): \IteratorAggregate
                        {
                            return $this->items;
                        }

                        public function __construct(\IteratorAggregate $foos)
                        {
                            $this->items = $foos;
                        }
                    }

                    /**
                     * @psalm-suppress MixedReturnTypeCoercion
                     * @method \IteratorAggregate<int, FooInterface> getAll():\IteratorAggregate
                     */
                    class Bar
                    {
                        private \IteratorAggregate $items;

                        /**
                         * @psalm-suppress MixedReturnTypeCoercion
                         */
                        public function getAll(): \IteratorAggregate
                        {
                            return $this->items;
                        }

                        public function __construct(\IteratorAggregate $foos)
                        {
                            $this->items = $foos;
                        }
                    }'
            ],
            'parseFloatInDefault' => [
                '<?php
                    namespace Foo {
                        /**
                         * @method int randomInt()
                         * @method void takesFloat($a = 0.1)
                         */
                        class G
                        {
                            /**
                             * @param string $method
                             * @param array $attributes
                             *
                             * @return mixed
                             */
                            public function __call($method, $attributes)
                            {
                                return null;
                            }
                        }
                    }

                    namespace Bar {
                        (new \Foo\G)->randomInt();
                    }'
            ],
            'negativeInDefault' => [
                '<?php
                    /**
                     * @method void foo($a = -0.1, $b = -12)
                     */
                    class G
                    {
                        public function __call(string $method, array $attributes): void
                        {
                        }
                    }
                    (new G)->foo();'
            ],
            'namespacedNegativeInDefault' => [
                '<?php
                    namespace Foo {
                        /**
                         * @method void foo($a = -0.1, $b = -12)
                         */
                        class G
                        {
                            public function __call(string $method, array $attributes): void
                            {
                            }
                        }
                        (new G)->foo();
                    }'
            ],
            'namespacedUnion' => [
                '<?php
                    namespace Foo;

                    /**
                     * @method string bar(\DateTimeInterface|\DateInterval|self $a, Cache|\Exception $e)
                     */
                    class Cache {
                        public function __call(string $method, array $args) {
                            return $method;
                        }
                    }

                    (new Cache)->bar(new \DateTime(), new Cache());'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'annotationWithBadDocblock' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString(\)
                     */
                    class Child extends ParentClass {}',
                'error_message' => 'InvalidDocblock',
            ],
            'annotationWithByRefParam' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString(&$a)
                     */
                    class Child extends ParentClass {}',
                'error_message' => 'InvalidDocblock',
            ],
            'annotationWithSealed' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString()
                     */
                    class Child extends ParentClass {}

                    $child = new Child();
                    $child->getString();
                    $child->foo();',
                'error_message' => 'UndefinedMagicMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:29 - Magic method Child::foo does not exist',
            ],
            'annotationInvalidArg' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setString(int $integer)
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setString("five");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'unionAnnotationInvalidArg' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $b = $child->setBool("hello", 5);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'validAnnotationWithInvalidVariadicCall' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method void setInts(int ...$foo) with some more text
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setInts([1, 2, 3]);',
                'error_message' => 'InvalidArgument',
            ],
            'magicMethodOverridesParentWithDifferentReturnType' => [
                '<?php
                    class C {}
                    class D {}

                    class A {
                        public function foo(string $s) : C {
                            return new C;
                        }
                    }

                    /** @method D foo(string $s) */
                    class B extends A {}',
                'error_message' => 'ImplementedReturnTypeMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:33',
            ],
            'magicMethodOverridesParentWithDifferentParamType' => [
                '<?php
                    class C {}
                    class D extends C {}

                    class A {
                        public function foo(string $s) : C {
                            return new C;
                        }
                    }

                    /** @method D foo(int $s) */
                    class B extends A {}',
                'error_message' => 'ImplementedParamTypeMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:33',
            ],
            'parseBadMethodAnnotation' => [
                '<?php
                    /**
                     * @method aaa
                     */
                    class AAA {
                        function __call() {
                            echo $b."\n";
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'methodwithDash' => [
                '<?php
                    /**
                     * A test class
                     *
                     * @method ClientInterface exchange-connect(array $options = [])
                     */
                    abstract class TestClassA {}',
                'error_message' => 'InvalidDocblock',
            ],
            'methodWithAmpersandAndSpace' => [
                '<?php
                    /**
                     * @method void alloc(string & $result)
                     */
                    class Foo {}',
                'error_message' => 'InvalidDocblock',
            ],
            'inheritSealedMethods' => [
                '<?php
                    /**
                     * @psalm-seal-methods
                     */
                    class A {
                        public function __call(string $method, array $args) {}
                    }

                    class B extends A {}

                    $b = new B();
                    $b->foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'lonelyMethod' => [
                '<?php
                    /**
                     * @method
                     */
                    class C {}',
                'error_message' => 'InvalidDocblock',
            ],
        ];
    }

    public function testSealAllMethodsWithoutFoo(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __call(string $method, array $args) {}
              }

              class B extends A {}

              $b = new B();
              $b->foo();
              '
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFoo(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __call(string $method, array $args) {}
                public function foo(): void {}
              }

              class B extends A {}

              $b = new B();
              $b->foo();
              '
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFooInSubclass(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __call(string $method, array $args) {}
              }

              class B extends A {
                public function foo(): void {}
              }

              $b = new B();
              $b->foo();
              '
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFooAnnotated(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @method foo(): int */
              class A {
                public function __call(string $method, array $args) {}
              }

              class B extends A {}

              $b = new B();
              $b->foo();
              '
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsSetToFalse(): void
    {
        Config::getInstance()->seal_all_methods = false;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public function __call(string $method, array $args) {}
              }

              class B extends A {}

              $b = new B();
              $b->foo();
              '
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testIntersectionTypeWhenMagicMethodDoesNotExistButIsProvidedBySecondType(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
              /** @method foo(): int */
              class A {
                public function __call(string $method, array $args) {}
              }

              class B {
                public function otherMethod(): void {}
              }

              /** @var A & B $b */
              $b = new B();
              $b->otherMethod();
              '
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testIntersectionTypeWhenMethodDoesNotExistOnEither(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php
              /** @method foo(): int */
              class A {
                public function __call(string $method, array $args) {}
              }

              class B {
                public function otherMethod(): void {}
              }

              /** @var A & B $b */
              $b = new B();
              $b->nonExistantMethod();
              '
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }
}
