<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;
use Psalm\Config;
use Psalm\Context;

class AttributeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'missingAttributeOnClass' => [
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    class Video {}',
                'error_message' => 'UndefinedAttributeClass',
                [],
                false,
                '8.0'
            ],
            'missingAttributeOnFunction' => [
                '<?php
                    use Foo\Bar\Pure;

                    #[Pure]
                    function foo() : void {}',
                'error_message' => 'UndefinedAttributeClass',
                [],
                false,
                '8.0'
            ],
            'missingAttributeOnParam' => [
                '<?php
                    use Foo\Bar\Pure;

                    function foo(#[Pure] string $str) : void {}',
                'error_message' => 'UndefinedAttributeClass',
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
                'error_message' => 'TooFewArguments',
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
                'error_message' => 'InvalidScalarArgument',
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
                'error_message' => 'InvalidAttribute',
                [],
                false,
                '8.0'
            ],
            'testReflectingClass74' => [
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
                'error_message' => 'UndefinedMethod',
                [],
                false,
                '7.4'
            ],
        ];
    }
}
