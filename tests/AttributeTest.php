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
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>}>
     */
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                'php_version' => '8.0'
            ],
            'testReflectingAllAttributes' => [
                'code' => '<?php
                    /** @var class-string $a */
                    $cs = stdClass::class;

                    $a = new ReflectionClass($cs);
                    $b = $a->getAttributes();
                    ',
                'assertions' => [
                    '$b' => 'array<array-key, ReflectionAttribute<object>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
            ],
            'allowsRepeatableFlag' => [
                'code' => '<?php
                    #[Attribute(Attribute::TARGET_ALL|Attribute::IS_REPEATABLE)] // results in int(127)
                    class A {}
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0'
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
                '
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
                'php_version' => '7.1'
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
                'php_version' => '8.1'
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
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'attributeClassHasNoAttributeAnnotation' => [
                'code' => '<?php
                    class A {}

                    #[A]
                    class B {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'missingAttributeOnClass' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    class Video {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'missingAttributeOnFunction' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    function foo() : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'missingAttributeOnParam' => [
                'code' => '<?php
                    use Foo\Bar\Pure;

                    function foo(#[Pure] string $str) : void {}',
                'error_message' => 'UndefinedAttributeClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:36',
                'ignored_issues' => [],
                'php_version' => '8.0',
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
                'ignored_issues' => [],
                'php_version' => '8.0',
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
                'ignored_issues' => [],
                'php_version' => '8.0',
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
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'interfaceCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'traitCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    interface Foo {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'abstractClassCannotBeAttributeClass' => [
                'code' => '<?php
                    #[Attribute]
                    abstract class Baz {}',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'abstractClassCannotHavePrivateConstructor' => [
                'code' => '<?php
                    #[Attribute]
                    class Baz {
                        private function __construct() {}
                    }',
                'error_message' => 'InvalidAttribute - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:23',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }
}
