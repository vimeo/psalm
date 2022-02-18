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
                [],
                [],
                '8.0'
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
                [],
                [],
                '8.0'
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
                [],
                [],
                '8.0'
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
                [],
                [],
                '8.0'
            ],
            'allowsRepeatableFlag' => [
                '<?php
                    #[Attribute(Attribute::TARGET_ALL|Attribute::IS_REPEATABLE)] // results in int(127)
                    class A {}
                ',
                [],
                [],
                '8.0'
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
                [],
                [],
                '8.0'
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
                [],
                false,
                '8.0'
            ],
            'missingAttributeOnClass' => [
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    class Video {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
                [],
                false,
                '8.0'
            ],
            'missingAttributeOnFunction' => [
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    function foo() : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
                [],
                false,
                '8.0'
            ],
            'missingAttributeOnParam' => [
                '<?php
                    use Foo\Bar\Pure;

                    function foo(#[Pure] string $str) : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:36',
                [],
                false,
                '8.0'
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
                [],
                false,
                '8.0'
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
                [],
                false,
                '8.0'
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
                [],
                false,
                '8.0'
            ],
            'interfaceCannotBeAttributeClass' => [
                '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                [],
                false,
                '8.0'
            ],
            'traitCannotBeAttributeClass' => [
                '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                [],
                false,
                '8.0'
            ],
            'abstractClassCannotBeAttributeClass' => [
                '<?php
                    #[Attribute]
                    abstract class Baz {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                [],
                false,
                '8.0'
            ],
            'abstractClassCannotHavePrivateConstructor' => [
                '<?php
                    #[Attribute]
                    class Baz {
                        private function __construct() {}
                    }',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                [],
                false,
                '8.0'
            ],
        ];
    }
}
