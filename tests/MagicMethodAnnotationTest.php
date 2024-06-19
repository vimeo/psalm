<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class MagicMethodAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
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
                $e = $child->getCallable();',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testPhpDocMethodWhenUndefinedWithStatic(): void
    {
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method static string getString()
                 * @method static void setInteger(int $integer)
                 * @method static mixed setString(int $integer)
                 * @method static bool getBool(string $foo)
                 * @method static (string|int)[] getArray()
                 * @method static (callable() : string) getCallable()
                 */
                class Child {}

                $a = Child::getString();
                Child::setInteger(4);
                /** @psalm-suppress MixedAssignment */
                $b = Child::setString(5);
                $c = Child::getBool("hello");
                $d = Child::getArray();
                $e = Child::getCallable();',
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
                    public function find() {
                        return null;
                    }
                }

                /** @psalm-suppress MissingTemplateParam */
                class B extends A {}

                class Obj {}

                /**
                 * @method Obj|null find()
                 */
                class C extends B {}',
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

                $child->getString();',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testAnnotationWithoutCallConfigWithStatic(): void
    {
        $this->expectExceptionMessage('UndefinedMethod');
        $this->expectException(CodeException::class);
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = false;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @method static string getString()
                 */
                class Child {}

                Child::getString();',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testAnnotationWithoutCallConfigWithExtends(): void
    {
        $this->expectExceptionMessage('UndefinedMethod');
        $this->expectException(CodeException::class);
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class MyParent {}
                /**
                 * @method string getString()
                 */
                class Child extends MyParent {}

                $child = new Child();

                $child->getString();',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testAnnotationWithoutCallConfigWithExtendsWithStatic(): void
    {
        $this->expectExceptionMessage('UndefinedMethod');
        $this->expectException(CodeException::class);
        Config::getInstance()->use_phpdoc_method_without_magic_or_parent = false;

        $this->addFile(
            'somefile.php',
            '<?php
                class MyParent {}
                /**
                 * @method static string getString()
                 */
                class Child extends MyParent {}

                Child::getString();',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function testOverrideParentClassReturnType(): void
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

                $child = Child::getMe();',
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
                }',
        );

        $context = new Context();

        $this->analyzeFile('somefile.php', $context);
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'validSimpleAnnotations' => [
                'code' => '<?php
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
            'validSimpleAnnotationsWithStatic' => [
                'code' => '<?php
                    class ParentClass {
                        public function __callStatic(string $name, array $args) {}
                    }

                    /**
                     * @method static string getString() dsa sada
                     * @method static void setInteger(int $integer) dsa sada
                     * @method static mixed setString(int $integer) dsa sada
                     * @method static mixed setMixed(mixed $foo) dsa sada
                     * @method static mixed setImplicitMixed($foo) dsa sada
                     * @method static mixed setAnotherImplicitMixed( $foo, $bar,$baz) dsa sada
                     * @method static mixed setYetAnotherImplicitMixed( $foo  ,$bar,  $baz    ) dsa sada
                     * @method static bool getBool(string $foo)   dsa sada
                     * @method static (string|int)[] getArray() with some text dsa sada
                     * @method static (callable() : string) getCallable() dsa sada
                     * @method static static getInstance() dsa sada
                     */
                    class Child extends ParentClass {}

                    $a = Child::getString();
                    Child::setInteger(4);
                    /** @psalm-suppress MixedAssignment */
                    $b = Child::setString(5);
                    $c = Child::getBool("hello");
                    $d = Child::getArray();
                    $e = Child::getCallable();
                    $f = Child::getInstance();
                    Child::setMixed("hello");
                    Child::setMixed(4);
                    Child::setImplicitMixed("hello");
                    Child::setImplicitMixed(4);',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'mixed',
                    '$c' => 'bool',
                    '$d' => 'array<array-key, int|string>',
                    '$e' => 'callable():string',
                    '$f' => 'Child',
                ],
            ],
            'validAnnotationWithDefault' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @method void global() */
                    class A {
                        public function __call(string $s) {}
                    }',
            ],
            'magicMethodInternalCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'assertions' => [
                    '$a' => 'C',
                    '$b' => 'C',
                ],
            ],
            'allowMagicMethodStatic' => [
                'code' => '<?php
                    /** @method static getStatic() */
                    class C {
                        public function __call(string $c, array $args) {}
                    }

                    class D extends C {}

                    $c = (new C)->getStatic();
                    $d = (new D)->getStatic();',
                'assertions' => [
                    '$c' => 'C',
                    '$d' => 'D',
                ],
            ],
            'validSimplePsalmAnnotations' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @method boolean(int $foo) : bool
                     * @method integer(int $foo) : bool
                     */
                    class Child {
                        public function __call(string $name, array $args) {}
                    }

                    $child = new Child();

                    $child->boolean(5);
                    $child->integer(5);',
            ],
            'overrideWithSelfBeforeMethodName' => [
                'code' => '<?php
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
                    }',
            ],
            'validMethodAsAnnotation' => [
                'code' => '<?php
                    /**
                     * @method string as(string $value)
                     */
                    class Foo {}',
            ],
            'annotationWithSealedSuppressingUndefinedMagicMethod' => [
                'code' => '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method string getString()
                     */
                    class Child extends ParentClass {}

                    $child = new Child();
                    /** @psalm-suppress UndefinedMagicMethod */
                    $child->foo();',
            ],
            'allowFinalOverrider' => [
                'code' => '<?php
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
                    final class B extends A {}',
            ],
            'namespacedMethod' => [
                'code' => '<?php
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
                    }',
            ],
            'parseFloatInDefault' => [
                'code' => '<?php
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
                    }',
            ],
            'negativeInDefault' => [
                'code' => '<?php
                    /**
                     * @method void foo($a = -0.1, $b = -12)
                     */
                    class G
                    {
                        public function __call(string $method, array $attributes): void
                        {
                        }
                    }
                    (new G)->foo();',
            ],
            'namespacedNegativeInDefault' => [
                'code' => '<?php
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
                    }',
            ],
            'namespacedUnion' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @method string bar(\DateTimeInterface|\DateInterval|self $a, Cache|\Exception $e)
                     */
                    class Cache {
                        public function __call(string $method, array $args) {
                            return $method;
                        }
                    }

                    (new Cache)->bar(new \DateTime(), new Cache());',
            ],
            'magicMethodInheritance' => [
                'code' => '<?php
                    /**
                     * @method string foo()
                     */
                    interface I {}

                    /**
                     * @method int bar()
                     */
                    class A implements I {}

                    class B extends A {
                        public function __call(string $method, array $args) {}
                    }

                    $b = new B();

                    function consumeString(string $s): void {}
                    function consumeInt(int $i): void {}

                    consumeString($b->foo());
                    consumeInt($b->bar());',
            ],
            'magicMethodInheritanceOnInterface' => [
                'code' => '<?php
                    /**
                     * @method string foo()
                     */
                    interface I {}
                    interface I2 extends I {}
                    function consumeString(string $s): void {}

                    /** @var I2 $i */
                    consumeString($i->foo());',
            ],
            'magicStaticMethodInheritance' => [
                'code' => '<?php
                    /**
                     * @method static string foo()
                     */
                    interface I {}

                    /**
                     * @method static int bar()
                     */
                    class A implements I {}

                    class B extends A {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    function consumeString(string $s): void {}
                    function consumeInt(int $i): void {}

                    consumeString(B::foo());
                    consumeInt(B::bar());',
            ],
            'magicStaticMethodInheritanceWithoutCallStatic' => [
                'code' => '<?php
                    /**
                     * @method static int bar()
                     */
                    class A {}
                    class B extends A {}
                    function consumeInt(int $i): void {}

                    /** @psalm-suppress UndefinedMethod, MixedArgument */
                    consumeInt(B::bar());',
            ],
            'magicStaticMethodInheritanceWithoutCallStatic_WithReturnAndManyArgs' => [
                // This is compatible with "magicMethodInheritanceWithoutCall_WithReturnAndManyArgs"
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @method static void bar()
                     */
                    class A {}
                    class B extends A {}

                    /** @psalm-suppress UndefinedMethod, MixedAssignment */
                    $a = B::bar(123, "whatever");
                    PHP,
                'assertions' => [
                    '$a===' => 'mixed',
                ],
            ],
            'magicMethodInheritanceWithoutCall_WithReturnAndManyArgs' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @method void bar()
                     */
                    class A {}
                    class B extends A {}

                    $obj = new B();

                    /** @psalm-suppress UndefinedMethod, MixedAssignment */
                    $a = $obj->bar(123, "whatever");
                    PHP,
                'assertions' => [
                    '$a===' => 'mixed',
                ],
            ],
            'callUsingParent' => [
                'code' => '<?php
                    /**
                     * @method static create(array $data)
                     */
                    class Model {
                        public function __call(string $name, array $arguments) {
                            /** @psalm-suppress UnsafeInstantiation */
                            return new static;
                        }
                    }

                    class BlahModel extends Model {
                        /**
                         * @param mixed $input
                         * @return static
                         */
                        public function create($input): BlahModel
                        {
                            return parent::create([]);
                        }
                    }

                    class FooModel extends Model {}

                    function consumeFoo(FooModel $a): void {}
                    function consumeBlah(BlahModel $a): void {}

                    $b = new FooModel();
                    consumeFoo($b->create([]));

                    $d = new BlahModel();
                    consumeBlah($d->create([]));',
            ],
            'returnThisShouldKeepGenerics' => [
                'code' => '<?php
                    /**
                     * @template E
                     * @method $this foo()
                     */
                    class A
                    {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @template E
                     * @method $this foo()
                     */
                    interface I {}

                    class B {}

                    /** @var A<B> $a */
                    $a = new A();
                    $b = $a->foo();

                    /** @var I<B> $i */
                    $c = $i->foo();',
                'assertions' => [
                    '$b' => 'A<B>&static',
                    '$c' => 'I<B>&static',
                ],
            ],
            'genericsOfInheritedMethodsShouldBeResolved' => [
                'code' => '<?php
                    /**
                     * @template E
                     * @method E get()
                     */
                    interface I {}

                    /**
                     * @template E
                     * @implements I<E>
                     */
                    class A implements I
                    {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @template E
                     * @extends I<E>
                     */
                    interface I2 extends I {}

                    class B {}

                    /**
                     * @template E
                     * @method E get()
                     */
                    class C
                    {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @template E
                     * @extends C<E>
                     */
                    class D extends C {}

                    /** @var A<B> $a */
                    $a = new A();
                    $b = $a->get();

                    /** @var I2<B> $i */
                    $c = $i->get();

                    /** @var D<B> $d */
                    $d = new D();
                    $e = $d->get();',
                'assertions' => [
                    '$b' => 'B',
                    '$c' => 'B',
                    '$e' => 'B',
                ],
            ],
            'arrayAsMethodName' => [
                'code' => <<<'PHP'
                    <?php
                    /** @method static void array() */
                    class C {}
                    //C::array();
                    PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'annotationWithBadDocblock' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setString(int $integer)
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $child->setString("five");',
                'error_message' => 'InvalidArgument',
            ],
            'unionAnnotationInvalidArg' => [
                'code' => '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                    }

                    /**
                     * @method setBool(string $foo, string|bool $bar)  :   bool dsa sada
                     */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $b = $child->setBool("hello", 5);',
                'error_message' => 'InvalidArgument',
            ],
            'validAnnotationWithInvalidVariadicCall' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * A test class
                     *
                     * @method ClientInterface exchange-connect(array $options = [])
                     */
                    abstract class TestClassA {}',
                'error_message' => 'InvalidDocblock',
            ],
            'methodWithAmpersandAndSpace' => [
                'code' => '<?php
                    /**
                     * @method void alloc(string & $result)
                     */
                    class Foo {}',
                'error_message' => 'InvalidDocblock',
            ],
            'inheritSealedMethods' => [
                'code' => '<?php
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
            'inheritSealedMethodsWithStatic' => [
                'code' => '<?php
                    /**
                     * @psalm-seal-methods
                     */
                    class A {
                        public static function __callStatic(string $method, array $args) {}
                    }

                    class B extends A {}

                    B::foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'lonelyMethod' => [
                'code' => '<?php
                    /**
                     * @method
                     */
                    class C {}',
                'error_message' => 'InvalidDocblock',
            ],
            'magicParentCallShouldNotPolluteContext' => [
                'code' => '<?php
                    /**
                     * @method baz(): Foo
                     */
                    class Foo
                    {
                        public function __call()
                        {
                            return new self();
                        }
                    }

                    class Bar extends Foo
                    {
                        public function baz(): Foo
                        {
                            parent::baz();
                            return $__tmp_parent_var__;
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'staticInvocationWithMagicMethodFoo' => [
                'code' => '<?php
                    /**
                     * @method string foo()
                     */
                    class A {
                        // Has "magic methods"
                        public function __call(string $method, array $args) {}
                        public static function __callStatic(string $method, array $args) {}
                    }

                    A::foo();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'nonStaticSelfCallWithMagicMethodFoo' => [
                'code' => '<?php
                    /**
                     * @method string foo()
                     */
                    class A {
                        // Has "magic methods"
                        public function __call(string $method, array $args) {}
                        public static function __callStatic(string $method, array $args) {}
                    }

                    class B extends A {
                        public static function bar(): void {
                            self::foo();
                        }
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
            'staticInvocationWithInstanceMethodFoo' => [
                'code' => '<?php
                    class A {
                        public function foo(): void {}

                        // Has "magic methods"
                        public function __call(string $method, array $args) {}
                        public static function __callStatic(string $method, array $args) {}
                    }

                    A::foo();',
                'error_message' => 'InvalidStaticInvocation',
            ],
            'nonStaticSelfCallWithInstanceMethodFoo' => [
                'code' => '<?php
                    class A {
                        public function foo(): void {}

                        // Has "magic methods"
                        public function __call(string $method, array $args) {}
                        public static function __callStatic(string $method, array $args) {}
                    }

                    class B extends A {
                        public static function bar(): void {
                            self::foo();
                        }
                    }',
                'error_message' => 'NonStaticSelfCall',
            ],
            'suppressUndefinedMethodWithObjectCall_WithNotExistsFunc' => [
                'code' => <<<'PHP'
                    <?php
                    /** @method int bar() */
                    class A {}
                    class B extends A {}

                    $obj = new B();
                
                    /** @psalm-suppress UndefinedMethod */
                    $a = $obj->bar(function_does_not_exist(123));
                    PHP,
                'error_message' => 'UndefinedFunction',
            ],
            'suppressUndefinedMethodWithStaticCall_WithNotExistsFunc' => [
                'code' => <<<'PHP'
                    <?php
                    /** @method static int bar() */
                    class A {}
                    class B extends A {}
                
                    /** @psalm-suppress UndefinedMethod */
                    $a = B::bar(function_does_not_exist(123));
                    PHP,
                'error_message' => 'UndefinedFunction',
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
              ',
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithoutFooWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public static function __callStatic(string $method, array $args) {}
              }

              class B extends A {}

              B::foo();
              ',
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoSealAllMethods(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @psalm-no-seal-properties */
              class A {
                public function __call(string $method, array $args) {}
              }

              class B extends A {}

              $b = new B();
              $b->foo();
              ',
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }

    public function testNoSealAllMethodsWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @psalm-no-seal-properties */
              class A {
                public static function __callStatic(string $method, array $args) {}
              }

              class B extends A {}

              B::foo();
              ',
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
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFooWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public static function __callStatic(string $method, array $args) {}
                public static function foo(): void {}
              }

              class B extends A {}

              B::foo();
              ',
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
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFooInSubclassWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public static function __callStatic(string $method, array $args) {}
              }

              class B extends A {
                public static function foo(): void {}
              }

              B::foo();
              ',
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
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsWithFooAnnotatedWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = true;

        $this->addFile(
            'somefile.php',
            '<?php
              /** @method static int foo() */
              class A {
                public static function __callStatic(string $method, array $args) {}
              }

              class B extends A {}

              B::foo();
              ',
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
              ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testSealAllMethodsSetToFalseWithStatic(): void
    {
        Config::getInstance()->seal_all_methods = false;

        $this->addFile(
            'somefile.php',
            '<?php
              class A {
                public static function __callStatic(string $method, array $args) {}
              }

              class B extends A {}

              B::foo();
              ',
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
              ',
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
              $b->nonExistentMethod();
              ',
        );

        $error_message = 'UndefinedMagicMethod';
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage($error_message);
        $this->analyzeFile('somefile.php', new Context());
    }
}
