<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class AttributeTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'classAndPropertyAttributesExists' => [
                '<?php
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
                '<?php
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
                '<?php
                    namespace {
                        #[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_PARAMETER)]
                        class Deprecated {}
                    }

                    namespace Foo\Bar {
                        function foo(#[\Deprecated] string $foo) : void {}
                    }',
            ],
            'testReflectingClass' => [
                '<?php
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
                [],
                [],
                '8.0'
            ],
            'testReflectingAllAttributes' => [
                '<?php
                    /** @var class-string $a */
                    $cs = stdClass::class;

                    $a = new ReflectionClass($cs);
                    $b = $a->getAttributes();
                    ',
                'assertions' => [
                    '$b' => 'array<array-key, ReflectionAttribute<object>>',
                ],
                [],
                '8.0'
            ],
            'convertKeyedArray' => [
                '<?php
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
                '<?php
                    #[Attribute(Attribute::TARGET_ALL|Attribute::IS_REPEATABLE)] // results in int(127)
                    class A {}
                ',
            ],
            'allowsClassString' => [
                '<?php

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
                '<?php

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
                '
            ],
            'returnTypeWillChange7.1' => [
                '<?php

                    namespace Rabus\PsalmReturnTypeWillChange;

                    use EmptyIterator;
                    use IteratorAggregate;
                    use ReturnTypeWillChange;

                    final class EmptyCollection implements IteratorAggregate
                    {
                        #[ReturnTypeWillChange]
                        public function getIterator()
                        {
                            return new EmptyIterator();
                        }
                    }',
                [],
                [],
                '7.1'
            ],
            'returnTypeWillChange8.1' => [
                '<?php

                    namespace Rabus\PsalmReturnTypeWillChange;

                    use EmptyIterator;
                    use IteratorAggregate;
                    use ReturnTypeWillChange;

                    final class EmptyCollection implements IteratorAggregate
                    {
                        #[ReturnTypeWillChange]
                        public function getIterator()
                        {
                            return new EmptyIterator();
                        }
                    }',
                [],
                [],
                '8.1'
            ],
            'createObjectAsAttributeArg' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    #[Attribute("foobar")]
                    class Foo {}

                    #[Foo]
                    class Bar {}
                ',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'attributeClassHasNoAttributeAnnotation' => [
                '<?php
                    class A {}

                    #[A]
                    class B {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnClass' => [
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    class Video {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnProperty' => [
                '<?php
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
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    function foo() : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
            ],
            'missingAttributeOnParam' => [
                '<?php
                    use Foo\Bar\Pure;

                    function foo(#[Pure] string $str) : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:36',
            ],
            'tooFewArgumentsToAttributeConstructor' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'traitCannotBeAttributeClass' => [
                '<?php
                    #[Attribute]
                    trait Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'abstractClassCannotBeAttributeClass' => [
                '<?php
                    #[Attribute]
                    abstract class Baz {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'attributeClassCannotHavePrivateConstructor' => [
                '<?php
                    #[Attribute]
                    class Baz {
                        private function __construct() {}
                    }',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
            ],
            'SKIPPED-attributeInvalidTargetClassConst' => [ // Will be implemented in Psalm 5 where we have better class const analysis
                '<?php
                    class Foo {
                        #[Attribute]
                        public const BAR = "baz";
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetProperty' => [
                '<?php
                    class Foo {
                        #[Attribute]
                        public string $bar = "baz";
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetMethod' => [
                '<?php
                    class Foo {
                        #[Attribute]
                        public function bar(): void {}
                    }
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetFunction' => [
                '<?php
                    #[Attribute]
                    function foo(): void {}
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeInvalidTargetParameter' => [
                '<?php
                    function foo(#[Attribute] string $_bar): void {}
                ',
                'error_message' => 'InvalidAttribute',
            ],
            'attributeTargetArgCannotBeVariable' => [
                '<?php
                    $target = 1;

                    #[Attribute($target)]
                    class Foo {}
                ',
                'error_message' => 'UndefinedVariable',
            ],
            'attributeTargetArgCannotBeSelfConst' => [
                '<?php
                    #[Attribute(self::BAR)]
                    class Foo
                    {
                        public const BAR = 1;
                    }
                ',
                'error_message' => 'NonStaticSelfCall',
            ],
            'noParentInAttributeOnClassWithoutParent' => [
                '<?php
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
                '<?php
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
                '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    class Foo {}

                    $r = new ReflectionClass(Foo::class);
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:39 - Attribute Attr cannot be used on a class',
            ],
            'getAttributesOnFunctionWithNonFunctionAttribute' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    #[Attribute(Attribute::TARGET_PROPERTY)]
                    class Attr {}

                    function foo(int $bar): void {}

                    $r = new ReflectionParameter("foo", "bar");
                    $r->getAttributes(Attr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:39 - Attribute Attr cannot be used on a function/method parameter',
            ],
            'getAttributesWithNonAttribute' => [
                '<?php
                    class NonAttr {}

                    function foo(int $bar): void {}

                    $r = new ReflectionParameter("foo", "bar");
                    $r->getAttributes(NonAttr::class);
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39 - The class NonAttr doesn\'t have the Attribute attribute',
            ],
            'analyzeConstructorForNonexistentAttributes' => [
                '<?php
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
                '<?php
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
                '<?php
                    #[Attribute]
                    class Foo {}

                    #[Foo, Foo]
                    class Baz {}
                ',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:28 - Attribute Foo is not repeatable',
            ],
        ];
    }
}
