<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class AttributeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testStubsWithDifferentAttributes(): void
    {
        $this->addStubFile(
            'stubOne.phpstub',
            '<?php
                #[Attribute]
                class Attr {}

                #[Attr]
                class Foo {}
            ',
        );

        $this->addFile(
            'somefile.php',
            '<?php
                class Foo {}
            ',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'classAndPropertyAttributesExists' => [
                'code' => '<?php
                    namespace Foo;

                    #[\Attribute(\Attribute::TARGET_CLASS)]
                    class Table {
                        public function __construct(public string $name) {}
                    }

                    #[\Attribute(\Attribute::TARGET_PROPERTY)]
                    class Column {
                        public function __construct(public string $name) {}
                    }

                    #[Table(name: "videos")]
                    class Video {
                        #[Column(name: "id")]
                        public string $id = "";

                        #[Column(name: "title")]
                        public string $name = "";
                    }

                    #[Table(name: "users")]
                    class User {
                        public function __construct(
                            #[Column(name: "id")]
                            public string $id,

                            #[Column(name: "name")]
                            public string $name = "",
                        ) {}
                    }',
            ],
            'functionAttributeExists' => [
                'code' => '<?php
                    namespace {
                        #[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_PARAMETER)]
                        class Deprecated {}
                    }

                    namespace Foo\Bar {
                        #[\Deprecated]
                        function foo() : void {}
                    }',
            ],
            'paramAttributeExists' => [
                'code' => '<?php
                    namespace {
                        #[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_PARAMETER)]
                        class Deprecated {}
                    }

                    namespace Foo\Bar {
                        function foo(#[\Deprecated] string $foo) : void {}
                    }',
            ],
            'testReflectingClass' => [
                'code' => '<?php
                    abstract class BaseAttribute {
                        public function __construct(public string $name) {}
                    }

                    #[Attribute(Attribute::TARGET_CLASS)]
                    class Table extends BaseAttribute {}

                    /** @param class-string $s */
                    function foo(string $s) : void {
                        foreach ((new ReflectionClass($s))->getAttributes(BaseAttribute::class, 2) as $attr) {
                            $attribute = $attr->newInstance();
                            echo $attribute->name;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'testReflectingAllAttributes' => [
                'code' => '<?php
                    /** @var class-string $a */
                    $cs = stdClass::class;

                    $a = new ReflectionClass($cs);
                    $b = $a->getAttributes();
                    ',
                'assertions' => [
                    '$b' => 'list<ReflectionAttribute<object>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'convertKeyedArray' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    class Route {
                        private $methods = [];
                        /**
                         * @param string[] $methods
                         */
                        public function __construct(array $methods = []) {
                            $this->methods = $methods;
                        }
                    }
                    #[Route(methods: ["GET"])]
                    class HealthController
                    {}',
            ],
            'allowsRepeatableFlag' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_ALL|Attribute::IS_REPEATABLE)] // results in int(127)
                    class A {}
                ',
            ],
            'allowsClassString' => [
                'code' => '<?php

                    #[Attribute(Attribute::TARGET_CLASS)]
                    class Foo
                    {
                        /**
                         * @param class-string<Baz> $_className
                         */
                        public function __construct(string $_className)
                        {
                        }
                    }

                    #[Foo(_className: Baz::class)]
                    class Baz {}',
            ],
            'allowsClassStringFromDifferentNamespace' => [
                'code' => '<?php

                    namespace NamespaceOne {
                        use Attribute;

                        #[Attribute(Attribute::TARGET_CLASS)]
                        class FooAttribute
                        {
                            /** @var class-string */
                            private string $className;

                            /**
                             * @param class-string<FoobarInterface> $className
                             */
                            public function __construct(string $className)
                            {
                                $this->className = $className;
                            }
                        }

                        interface FoobarInterface {}

                        class Bar implements FoobarInterface {}
                    }

                    namespace NamespaceTwo {
                        use NamespaceOne\FooAttribute;
                        use NamespaceOne\Bar as ZZ;

                        #[FooAttribute(className: ZZ::class)]
                        class Baz {}
                    }
                ',
            ],
            'returnTypeWillChange7.1' => [
                'code' => '<?php

                    namespace Rabus\PsalmReturnTypeWillChange;

                    use EmptyIterator;
                    use IteratorAggregate;
                    use ReturnTypeWillChange;

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    final class EmptyCollection implements IteratorAggregate
                    {
                        #[ReturnTypeWillChange]
                        public function getIterator()
                        {
                            return new EmptyIterator();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.1',
            ],
            'returnTypeWillChange8.1' => [
                'code' => '<?php

                    namespace Rabus\PsalmReturnTypeWillChange;

                    use EmptyIterator;
                    use IteratorAggregate;
                    use ReturnTypeWillChange;

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    final class EmptyCollection implements IteratorAggregate
                    {
                        #[ReturnTypeWillChange]
                        public function getIterator()
                        {
                            return new EmptyIterator();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'allowDynamicProperties' => [
                'code' => '<?php

                    namespace AllowDynamicPropertiesAttribute;

                    use AllowDynamicProperties;

                    #[AllowDynamicProperties]
                    class Foo
                    {}
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'sensitiveParameter' => [
                'code' => '<?php

                    namespace SensitiveParameter;

                    use SensitiveParameter;

                    class HelloWorld {
                        public function __construct(
                            #[SensitiveParameter] string $password
                        ) {}
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'createObjectAsAttributeArg' => [
                'code' => '<?php
                    #[Attribute]
                    class B
                    {
                        public function __construct(?array $listOfB = null) {}
                    }

                    #[Attribute(Attribute::TARGET_CLASS)]
                    class A
                    {
                        /**
                         * @param B[] $listOfB
                         */
                        public function __construct(?array $listOfB = null) {}
                    }

                    #[A([new B])]
                    class C {}
                ',
            ],
            'selfInClassAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    class SomeAttr
                    {
                        /** @param class-string $class */
                        public function __construct(string $class) {}
                    }

                    #[SomeAttr(self::class)]
                    class A
                    {
                        #[SomeAttr(self::class)]
                        public const CONST = "const";

                        #[SomeAttr(self::class)]
                        public string $foo = "bar";

                        #[SomeAttr(self::class)]
                        public function baz(): void {}
                    }
                ',
            ],
            'parentInClassAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    class SomeAttr
                    {
                        /** @param class-string $class */
                        public function __construct(string $class) {}
                    }

                    class A {}

                    #[SomeAttr(parent::class)]
                    class B extends A
                    {
                        #[SomeAttr(parent::class)]
                        public const CONST = "const";

                        #[SomeAttr(parent::class)]
                        public string $foo = "bar";

                        #[SomeAttr(parent::class)]
                        public function baz(): void {}
                    }
                ',
            ],
            'selfInInterfaceAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    class SomeAttr
                    {
                        /** @param class-string $class */
                        public function __construct(string $class) {}
                    }

                    #[SomeAttr(self::class)]
                    interface C
                    {
                        #[SomeAttr(self::class)]
                        public const CONST = "const";

                        #[SomeAttr(self::class)]
                        public function baz(): void {}
                    }
                ',
            ],
            'allowBothParamAndPropertyAttributesForPromotedProperties' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PARAMETER)]
                    class Foo {}

                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Bar {}

                    class Baz
                    {
                        public function __construct(#[Foo, Bar] private int $test) {}
                    }
                ',
            ],
            'multipleAttributesInMultipleGroups' => [
                'code' => '<?php
                    #[Attribute]
                    class A {}
                    #[Attribute]
                    class B {}
                    #[Attribute]
                    class C {}
                    #[Attribute]
                    class D {}

                    #[A, B]
                    #[C, D]
                    class Foo {}
                ',
            ],
            'propertyLevelSuppression' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    class ClassAttr {}

                    class Foo
                    {
                        /** @psalm-suppress InvalidAttribute */
                        #[ClassAttr]
                        public string $bar = "baz";
                    }
                ',
            ],
            'invalidAttributeDoesntCrash' => [
                'code' => '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    #[Attribute("foobar")]
                    class Foo {}

                    #[Foo]
                    class Bar {}
                ',
            ],
            'dontCrashWhenRedefiningStubbedMethodWithFewerParams' => [
                'code' => '<?php
                    if (!class_exists(ArrayObject::class)) {
                        class ArrayObject
                        {
                            public function __construct() {}
                        }
                    }
                ',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'attributeClassHasNoAttributeAnnotation' => [
                'code' => '<?php
                    class A {}

                    #[A]
                    class B {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnClass' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    class Video {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnProperty' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    class Baz
                    {
                        #[Pure]
                        public string $foo = "bar";
                    }
                ',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:27',
            ],
            'missingAttributeOnFunction' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    function foo() : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnParam' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    function foo(#[Pure] string $str) : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:36',
            ],
            'tooFewArgumentsToAttributeConstructor' => [
                'code' => '<?php
                    namespace Foo;

                    #[\Attribute(\Attribute::TARGET_CLASS)]
                    class Table {
                        public function __construct(public string $name) {}
                    }

                    #[Table()]
                    class Video {}',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:23',
            ],
            'invalidArgument' => [
                'code' => '<?php
                    #[Attribute]
                    class Foo
                    {
                        public function __construct(int $i)
                        {
                        }
                    }

                    #[Foo("foo")]
                    class Bar{}',
                'error_message' => 'InvalidScalarArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:27',
            ],
            'classAttributeUsedOnFunction' => [
                'code' => '<?php
                    namespace Foo;

                    #[\Attribute(\Attribute::TARGET_CLASS)]
                    class Table {
                        public function __construct(public string $name) {}
                    }

                    #[Table("videos")]
                    function foo() : void {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:23',
            ],
            'interfaceCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'traitCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    trait Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'abstractClassCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    abstract class Baz {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'attributeClassCannotHavePrivateConstructor' => [
                'code' => '<?php
                    #[Attribute]
                    class Baz {
                        private function __construct() {}
                    }',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'SKIPPED-attributeInvalidTargetClassConst' => [ // Will be implemented in Psalm 5 where we have better class const analysis
                'code' => '<?php
                    class Foo {
                        #[Attribute]
                        public const BAR = "baz";
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetProperty' => [
                'code' => '<?php
                    class Foo {
                        #[Attribute]
                        public string $bar = "baz";
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetMethod' => [
                'code' => '<?php
                    class Foo {
                        #[Attribute]
                        public function bar(): void {}
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetFunction' => [
                'code' => '<?php
                    #[Attribute]
                    function foo(): void {}
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetParameter' => [
                'code' => '<?php
                    function foo(#[Attribute] string $_bar): void {}
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeTargetArgCannotBeVariable' => [
                'code' => '<?php
                    $target = 1;

                    #[Attribute($target)]
                    class Foo {}
                ',
                'error_message' => 'UndefinedVariable',
            ],
            'attributeTargetArgCannotBeSelfConst' => [
                'code' => '<?php
                    #[Attribute(self::BAR)]
                    class Foo
                    {
                        public const BAR = 1;
                    }
                ',
                'error_message' => 'NonStaticSelfCall',
            ],
            'noParentInAttributeOnClassWithoutParent' => [
                'code' => '<?php
                    #[Attribute]
                    class SomeAttr
                    {
                        /** @param class-string $class */
                        public function __construct(string $class) {}
                    }

                    #[SomeAttr(parent::class)]
                    class A {}
                ',
                'error_message' => 'ParentNotFound',
            ],
            'undefinedConstantInAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    class Foo
                    {
                        public function __construct(int $i) {}
                    }

                    #[Foo(self::BAR_CONST)]
                    class Bar {}
                ',
                'error_message' => 'UndefinedConstant',
            ],
            'getAttributesOnClassWithNonClassAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    class Foo {}

                    $r = new ReflectionClass(Foo::class);
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:39 - Attribute Attr cannot be used on a class',
            ],
            'getAttributesOnFunctionWithNonFunctionAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    function foo(): void {}

                    /** @psalm-suppress InvalidArgument */
                    $r = new ReflectionFunction("foo");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:39 - Attribute Attr cannot be used on a function',
            ],
            'getAttributesOnMethodWithNonMethodAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    class Foo
                    {
                        public function bar(): void {}
                    }

                    $r = new ReflectionMethod("Foo::bar");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:39 - Attribute Attr cannot be used on a method',
            ],
            'getAttributesOnPropertyWithNonPropertyAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    class Attr {}

                    class Foo
                    {
                        public string $bar = "baz";
                    }

                    $r = new ReflectionProperty(Foo::class, "bar");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:39 - Attribute Attr cannot be used on a property',
            ],
            'getAttributesOnClassConstantWithNonClassConstantAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    class Foo
                    {
                        public const BAR = "baz";
                    }

                    $r = new ReflectionClassConstant(Foo::class, "BAR");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:39 - Attribute Attr cannot be used on a class constant',
            ],
            'getAttributesOnParameterWithNonParameterAttribute' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    function foo(int $bar): void {}

                    $r = new ReflectionParameter("foo", "bar");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:39 - Attribute Attr cannot be used on a function/method parameter',
            ],
            'getAttributesWithNonAttribute' => [
                'code' => '<?php
                    class NonAttr {}

                    function foo(int $bar): void {}

                    $r = new ReflectionParameter("foo", "bar");
                    $r->getAttributes(NonAttr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39 - The class NonAttr doesn\'t have the Attribute attribute',
            ],
            'analyzeConstructorForNonexistentAttributes' => [
                'code' => '<?php
                    class Foo
                    {
                        public function __construct(string $_arg) {}
                    }

                    /** @psalm-suppress UndefinedAttributeClass */
                    #[AttrA(new Foo(1))]
                    class Bar {}
                ',
                'error_message' => 'InvalidScalarArgument',
            ],
            'multipleAttributesShowErrors' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_CLASS)]
                    class Foo {}

                    #[Attribute(Attribute::TARGET_PARAMETER)]
                    class Bar {}

                    #[Foo, Bar]
                    class Baz {}
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'repeatNonRepeatableAttribute' => [
                'code' => '<?php
                    #[Attribute]
                    class Foo {}

                    #[Foo, Foo]
                    class Baz {}
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:28 - Attribute Foo is not repeatable',
            ],
            'invalidAttributeConstructionWithReturningFunction' => [
                'code' => '<?php
                    enum Enumm
                    {
                        case SOME_CASE;
                    }

                    #[Attribute]
                    final class Attr
                    {
                        public function __construct(public Enumm $e) {}
                    }

                    final class SomeClass
                    {
                        #[Attr(Enumm::WRONG_CASE)]
                        public function anotherMethod(): string
                        {
                            return "";
                        }
                    }
                ',
                'error_message' => 'UndefinedConstant',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'sensitiveParameterOnMethod' => [
                'code' => '<?php

                    namespace SensitiveParameter;

                    use SensitiveParameter;

                    class HelloWorld {
                        #[SensitiveParameter]
                        public function __construct(
                            string $password
                        ) {}
                    }
                ',
                'error_message' => 'Attribute SensitiveParameter cannot be used on a method',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
        ];
    }
}
