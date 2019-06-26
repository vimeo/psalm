<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use const DIRECTORY_SEPARATOR;

class AnnotationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testPhpStormGenericsWithValidArrayIteratorArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString(ArrayIterator $i): void {
                    $s = $i->offsetGet("a");
                    takesString($s);

                    foreach ($i as $s2) {
                        takesString($s2);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsWithValidTraversableArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param Traversable|string[] $i */
                function takesTraversableOfString(Traversable $i): void {
                    foreach ($i as $s2) {
                        takesString($s2);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsWithClassProperty()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @psalm-suppress MissingConstructor */
                class Foo {
                  /** @var \stdClass[]|\ArrayObject */
                  public $bar;

                  /**
                   * @return \stdClass[]|\ArrayObject
                   */
                  public function getBar(): \ArrayObject
                  {
                    return $this->bar;
                  }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsWithGeneratorArray()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /**
                     * @return stdClass[]|Generator
                     */
                    function getCollection(): Generator
                    {
                        yield new stdClass;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsWithValidIterableArgument()
    {
        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesString(string $s): void {}

                /** @param iterable|string[] $i */
                function takesArrayIteratorOfString(iterable $i): void {
                    foreach ($i as $s2) {
                        takesString($s2);
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsInvalidArgument()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('InvalidScalarArgument');

        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function takesInt(int $s): void {}

                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString(ArrayIterator $i): void {
                    $s = $i->offsetGet("a");
                    takesInt($s);
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testPhpStormGenericsNoTypehint()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('PossiblyInvalidMethodCall');

        Config::getInstance()->allow_phpstorm_generics = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /** @param ArrayIterator|string[] $i */
                function takesArrayIteratorOfString($i): void {
                    $s = $i->offsetGet("a");
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testInvalidParamDefault()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('InvalidParamDefault');

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $arr
                 * @return void
                 */
                function foo($arr = false) {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testInvalidParamDefaultButAllowedInConfig()
    {
        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $arr
                 * @return void
                 */
                function foo($arr = false) {}
                foo(false);
                foo(["hello"]);'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testInvalidTypehintParamDefaultButAllowedInConfig()
    {
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('InvalidParamDefault');

        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo(array $arr = false) : void {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'nopType' => [
                '<?php
                    $a = "hello";

                    /** @var int $a */',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'validDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo(): string {
                        return "boop";
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo2(): array {
                        return ["hello"];
                    }

                    /**
                     * @return array<int, string>
                     */
                    function foo3(): array {
                        return ["hello"];
                    }',
            ],
            'reassertWithIs' => [
                '<?php
                    /** @param array $a */
                    function foo($a): void {
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'checkArrayWithIs' => [
                '<?php
                    /** @param mixed $b */
                    function foo($b): void {
                        /** @var array */
                        $a = (array)$b;
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'error_level' => ['RedundantConditionGivenDocblockType'],
            ],
            'checkArrayWithIsInsideLoop' => [
                '<?php
                    /** @param array<mixed, array<mixed, mixed>> $data */
                    function foo($data): void {
                        foreach ($data as $key => $val) {
                            if (!\is_array($data)) {
                                $data = [$key => null];
                            } else {
                                $data[$key] = !empty($val);
                            }
                        }
                    }',
                'assertions' => [],
                'error_level' => ['LoopInvalidation', 'MixedArrayOffset', 'DocblockTypeContradiction'],
            ],
            'goodDocblock' => [
                '<?php
                    class A {
                        /**
                         * @param A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b): void {
                        }
                    }',
            ],
            'goodDocblockInNamespace' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @param \Foo\A $a
                         * @param bool $b
                         */
                        public function g(A $a, $b): void {
                        }
                    }',
            ],

            'ignoreNullableReturn' => [
                '<?php
                    class A {
                        /** @var int */
                        public $bar = 5;
                        public function foo(): void {}
                    }

                    /**
                     * @return ?A
                     * @psalm-ignore-nullable-return
                     */
                    function makeA() {
                        return rand(0, 1) ? new A(): null;
                    }

                    function takeA(A $a): void { }

                    $a = makeA();
                    $a->foo();
                    $a->bar = 7;
                    takeA($a);',
            ],
            'invalidDocblockParamSuppress' => [
                '<?php
                    /**
                     * @param int $bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(array $bar): void {
                    }',
            ],
            'differentDocblockParamClassSuppress' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @param B $bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(A $bar): void {
                    }',
            ],
            'varDocblock' => [
                '<?php
                    /** @var array<Exception> */
                    $a = [];

                    $a[0]->getMessage();',
            ],
            'mixedDocblockParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        /** @param mixed $a */
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'intDocblockParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        /** @param int $a */
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'varSelf' => [
                '<?php
                    class A
                    {
                        public function foo(): void {}

                        public function getMeAgain(): void {
                            /** @var self */
                            $me = $this;
                            $me->foo();
                        }
                    }',
            ],
            'psalmVar' => [
                '<?php
                    class A
                    {
                        /** @psalm-var array<int, string> */
                        public $foo = [];

                        public function updateFoo(): void {
                            $this->foo[5] = "hello";
                        }
                    }',
            ],
            'psalmParam' => [
                '<?php
                    function takesInt(int $a): void {}

                    /**
                     * @psalm-param  array<int, string> $a
                     * @param string[] $a
                     */
                    function foo(array $a): void {
                        foreach ($a as $key => $value) {
                            takesInt($key);
                        }
                    }',
            ],
            'returnDocblock' => [
                '<?php
                    function foo(int $i): int {
                        /** @var int */
                        return $i;
                    }',
            ],
            'doubleVar' => [
                '<?php
                    function foo() : array {
                        return ["hello" => new stdClass, "goodbye" => new stdClass];
                    }

                    $a = null;
                    $b = null;

                    /**
                     * @var string $key
                     * @var stdClass $value
                     */
                    foreach (foo() as $key => $value) {
                        $a = $key;
                        $b = $value;
                    }',
                'assertions' => [
                    '$a' => 'null|string',
                    '$b' => 'null|stdClass',
                ],
            ],
            'allowOptionalParamsToBeEmptyArray' => [
                '<?php
                    /** @param array{b?: int, c?: string} $a */
                    function foo(array $a = []) : void {}',
            ],
            'allowEmptyVarAnnotation' => [
                '<?php
                    /**
                     * @param $x
                     */
                    function example(array $x) : void {}',
            ],
            'allowCapitalisedNamespacedString' => [
                '<?php
                    namespace Foo;

                    /**
                     * @param String $x
                     */
                    function example(string $x) : void {}',
            ],
            'megaClosureAnnotationWithoutSpacing' => [
                '<?php
                    /** @var array{a:Closure():(array<mixed, mixed>|null), b?:Closure():array<mixed, mixed>, c?:Closure():array<mixed, mixed>, d?:Closure():array<mixed, mixed>, e?:Closure():(array{f:null|string, g:null|string, h:null|string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), p?:Closure():(array{f:null|string, g:null|string, h:null|string, q:string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), r?:Closure():(array<mixed, mixed>|null), s:array<mixed, mixed>} */
                    $arr = [];

                    $arr["a"]();',
            ],
            'megaClosureAnnotationWithSpacing' => [
                '<?php
                    /**
                     * @var array{
                     * a: Closure() : (array<mixed, mixed>|null),
                     * b?: Closure() : array<mixed, mixed>,
                     * c?: Closure() : array<mixed, mixed>,
                     * d?: Closure() : array<mixed, mixed>,
                     * e?: Closure() : (array{
                     *   f: null|string,
                     *   g: null|string,
                     *   h: null|string,
                     *   i: string,
                     *   j: mixed,
                     *   k: mixed,
                     *   l: mixed,
                     *   m: mixed,
                     *   n: bool,
                     *   o?: array{0:string}
                     * }|null),
                     * p?: Closure() : (array{
                     *   f: null|string,
                     *   g: null|string,
                     *   h: null|string,
                     *   q: string,
                     *   i: string,
                     *   j: mixed,
                     *   k: mixed,
                     *   l: mixed,
                     *   m: mixed,
                     *   n: bool,
                     *   o?: array{0:string}
                     * }|null),
                     * r?: Closure() : (array<mixed, mixed>|null),
                     * s: array<mixed, mixed>
                     * }
                     *
                     * Some text
                     */
                    $arr = [];

                    $arr["a"]();',
            ],
            'multipeLineGenericArray' => [
                '<?php
                    /**
                     * @psalm-type MiddlewareArray = array<
                     *     class-string<\Exception>,
                     *     array<int, string>
                     * >
                     *
                     * @psalm-type RuleArray = array{
                     *     rule: string,
                     *     controller?: class-string<\Exception>,
                     *     redirect?: string,
                     *     code?: int,
                     *     type?: string,
                     *     middleware?: MiddlewareArray
                     * }
                     *
                     * Foo Bar
                     */
                    class A {}',
            ],
            'slashAfter?' => [
                '<?php
                    namespace ns;

                    /** @param ?\stdClass $s */
                    function foo($s) : void {
                    }

                    foo(null);
                    foo(new \stdClass);',
            ],
            'generatorReturnType' => [
                '<?php
                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    $g = g();',
                'assertions' => [
                    '$g' => 'Generator<int, stdClass, mixed, mixed>',
                ],
            ],
            'returnTypeShouldBeNullable' => [
                '<?php
                    /**
                     * @return stdClass
                     */
                    function foo() : ?stdClass {
                        return rand(0, 1) ? new stdClass : null;
                    }

                    $f = foo();
                    if ($f) {}',
            ],
            'spreadOperatorAnnotation' => [
                '<?php
                    /** @param string[] $s */
                    function foo(string ...$s) : void {}
                    /** @param string ...$s */
                    function bar(string ...$s) : void {}
                    foo("hello", "goodbye");
                    bar("hello", "goodbye");
                    foo(...["hello", "goodbye"]);
                    bar(...["hello", "goodbye"]);',
            ],
            'spreadOperatorByRefAnnotation' => [
                '<?php
                    /** @param string &...$s */
                    function foo(&...$s) : void {}
                    /** @param string ...&$s */
                    function bar(&...$s) : void {}
                    /** @param string[] &$s */
                    function bat(&...$s) : void {}

                    $a = "hello";
                    $b = "goodbye";
                    $c = "hello again";
                    foo($a);
                    bar($b);
                    bat($c);',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'string',
                    '$c' => 'string',
                ],
            ],
            'valueReturnType' => [
                '<?php
                    /**
                     * @param "a"|"b" $_p
                     */
                    function acceptsLiteral($_p): void {}

                    /**
                     * @return "a"|"b"
                     */
                    function returnsLiteral(): string {
                        return rand(0,1) ? "a" : "b";
                    }

                    acceptsLiteral(returnsLiteral());',
            ],
            'typeAliasBeforeClass' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasBeforeFunction' => [
                '<?php
                    /**
                     * @psalm-type A_OR_B = A|B
                     * @psalm-type CoolType = A_OR_B|null
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasInSeparateBlockBeforeFunction' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */
                    /**
                     * @return CoolType
                     */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    class A {}
                    class B {}

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'almostFreeStandingTypeAlias' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B|null
                     */

                    // this breaks up the line

                    class A {}
                    class B {}

                    /** @return CoolType */
                    function foo() {
                        if (rand(0, 1)) {
                            return new A();
                        }

                        if (rand(0, 1)) {
                            return new B();
                        }

                        return null;
                    }

                    /** @param CoolType $a **/
                    function bar ($a) : void { }

                    bar(foo());',
            ],
            'typeAliasUsedTwice' => [
                '<?php
                    /** @psalm-type TA = array<int, string> */

                    class Bar {
                        public function foo() : void {
                            $bar =
                                /** @return TA */
                                function() {
                                    return ["hello"];
                            };

                            /** @var array<int, TA> */
                            $bat = [$bar(), $bar()];

                            foreach ($bat as $b) {
                                echo $b[0];
                            }
                        }
                    }

                    /**
                      * @psalm-type _A=array{elt:int}
                      * @param _A $p
                      * @return _A
                      */
                    function f($p) {
                        /** @var _A */
                        $r = $p;
                        return $r;
                    }',
            ],
            'listUnpackWithDocblock' => [
                '<?php
                    interface I {}

                    class A implements I {
                        public function bar() : void {}
                    }

                    /** @return I[] */
                    function foo() : array {
                        return [new A()];
                    }

                    /** @var A $a1 */
                    [$a1, $a2] = foo();

                    $a1->bar();',
            ],
            'spaceInType' => [
                '<?php
                    /** @return string | null */
                    function foo(string $s = null) {
                        return $s;
                    }',
            ],
            'missingReturnTypeWithBadDocblockIgnoreBoth' => [
                '<?php
                    /**
                     * @return [bad]
                     */
                    function fooBar() {
                    }',
                [],
                [
                    'InvalidDocblock' => \Psalm\Config::REPORT_INFO,
                    'MissingReturnType' => \Psalm\Config::REPORT_INFO,
                ],
            ],
            'objectWithPropertiesAnnotation' => [
                '<?php
                    /** @param object{foo:string} $o */
                    function foo(object $o) : string {
                        return $o->foo;
                    }

                    $s = new \stdClass();
                    $s->foo = "hello";
                    foo($s);

                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    foo(new A);',
            ],
            'refineTypeInNestedCall' => [
                '<?php
                    function foo(array $arr): \Generator {
                        /** @var array<string, mixed> $arr */
                        foreach (array_filter(array_keys($arr), function (string $key) : bool {
                            return strpos($key, "BAR") === 0;
                        }) as $envVar) {
                            yield $envVar => [getenv($envVar)];
                        }
                    }',
            ],
            'allowAnnotationOnServer' => [
                '<?php
                    function foo(): \Generator {
                        /** @var array<string, mixed> $_SERVER */
                        foreach (array_filter(array_keys($_SERVER), function (string $key) : bool {
                            return strpos($key, "BAR") === 0;
                        }) as $envVar) {
                            yield $envVar => [getenv($envVar)];
                        }
                    }',
            ],
            'annotationOnForeachItems' => [
                '<?php
                    function foo(array $arr) : void {
                        $item = null;

                        /** @var string $item */
                        foreach ($arr as $item) {}

                        if (is_null($item)) {}
                    }

                    function bar(array $arr) : void {
                        $item = null;

                        /** @var string $item */
                        foreach ($arr as $item => $_) {}

                        if (is_null($item)) {}
                    }

                    function bat(array $arr) : void {
                        $item = null;

                        /** @var string $item */
                        foreach ($arr as list($item)) {}

                        if (is_null($item)) {}
                    }

                    function baz(array $arr) : void {
                        $item = null;

                        /** @var string $item */
                        foreach ($arr as list($item => $_)) {}

                        if (is_null($item)) {}
                    }',
                [],
                [
                    'MixedAssignment',
                ],
            ],
            'extraneousDocblockParamName' => [
                '<?php
                    /**
                     * @param string $foo
                     * @param string[] $bar
                     * @param string[] $barb
                     */
                    function f(string $foo, array $barb): void {}',
            ],
            'nonEmptyArray' => [
                '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    foo(["a", "b", "c"]);

                    /** @param array<string> $arr */
                    function bar(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foo($arr);
                    }',
            ],
            'nonEmptyArrayInNamespace' => [
                '<?php
                    namespace ns;

                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    foo(["a", "b", "c"]);

                    /** @param array<string> $arr */
                    function bar(array $arr) : void {
                        if (!$arr) {
                            return;
                        }

                        foo($arr);
                    }',
            ],
            'inheritParentReturnDocbblock' => [
                '<?php
                    class Foo {
                        /**
                         * @return int[]
                         */
                        public function doFoo() {
                            return [1, 2, 3];
                        }
                    }

                    class Bar extends Foo {
                        public function doFoo(): array {
                            return [4, 5, 6];
                        }
                    }

                    $b = (new Bar)->doFoo();',
                [
                    '$b' => 'array<array-key, int>',
                ]
            ],
            'noExceptionOnIntersection' => [
                '<?php
                    class Foo {
                        /** @var null|\DateTime&\DateTimeImmutable */
                        private $s = null;
                    }',
            ],
            'intersectionWithSpace' => [
                '<?php
                    interface A {
                        public function foo() : void;
                    }
                    interface B {
                        public function bar() : void;
                    }

                    /** @param A & B $a */
                    function f(A $a) : void {
                        $a->foo();
                        $a->bar();
                    }'
            ],
            'allowClosingComma' => [
                '<?php
                    /**
                     * @param array{
                     *    foo: string,
                     *    bar: string,
                     *    baz: array{
                     *       a: int,
                     *    },
                     * } $foo
                     */
                    function foo(array $foo) : int {
                      return count($foo);
                    }

                    /**
                     * @var array{
                     *    foo:string,
                     *    bar:string,
                     *    baz:string,
                     * } $foo
                     */
                    $foo = ["foo" => "", "bar" => "", "baz" => ""];'
            ],
            'inheritedSelfAnnotation' => [
                '<?php
                    interface I {
                        /**
                         * @param self $i
                         * @return self
                         */
                        function foo(self $i) : self;
                    }

                    class C implements I {
                        public function foo(I $i) : I {
                            return $i;
                        }
                    }

                    function takeI(I $i) : I {
                        return (new C)->foo($i);
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidClassMethodReturn' => [
                '<?php
                    class C {
                        /**
                         * @return $thus
                         */
                        public function barBar() {
                            return $this;
                        }
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'invalidClassMethodReturnClass' => [
                '<?php
                    class C {
                        /**
                         * @return 1
                         */
                        public static function barBar() {
                            return 1;
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidClassMethodReturnBrackets' => [
                '<?php
                    class C {
                        /**
                         * @return []
                         */
                        public static function barBar() {
                            return [];
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidInterfaceMethodReturn' => [
                '<?php
                    interface I {
                        /**
                         * @return $thus
                         */
                        public static function barBar();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'invalidInterfaceMethodReturnClass' => [
                '<?php
                    interface I {
                        /**
                         * @return 1
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidInterfaceMethodReturnBrackets' => [
                '<?php
                    interface I {
                        /**
                         * @return []
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidPropertyClass' => [
                '<?php
                    class A {
                        /**
                         * @var 1
                         */
                        public $bar;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidPropertyBrackets' => [
                '<?php
                    class A {
                        /**
                         * @var []
                         */
                        public $bar;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidReturnClassWithComma' => [
                '<?php
                    interface I {
                        /**
                         * @return 1,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'returnClassWithComma' => [
                '<?php
                    interface I {
                        /**
                         * @return a,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'missingParamType' => [
                '<?php
                    /**
                     * @param string $bar
                     */
                    function fooBar(): void {
                    }

                    fooBar("hello");',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:21 - Too many arguments for method fooBar '
                    . '- expecting 0 but saw 1',
            ],
            'missingParamVar' => [
                '<?php
                    /**
                     * @param string
                     */
                    function fooBar(): void {
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:21 - Badly-formatted @param',
            ],
            'missingReturnTypeWithBadDocblock' => [
                '<?php
                    /**
                     * @return [bad]
                     */
                    function fooBar() {
                    }',
                'error_message' => 'MissingReturnType',
                [
                    'InvalidDocblock' => \Psalm\Config::REPORT_INFO,
                ],
            ],
            'invalidDocblockReturn' => [
                '<?php
                    /**
                     * @return string
                     */
                    function fooFoo(): int {
                        return 5;
                    }',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
            'intParamTypeDefinedInParent' => [
                '<?php
                    class A {
                        public function foo(int $a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
                'error_message' => 'MissingParamType',
                'error_levels' => ['MethodSignatureMismatch'],
            ],
            'psalmInvalidVar' => [
                '<?php
                    class A
                    {
                        /** @psalm-var array<int, string> */
                        public $foo = [];

                        public function updateFoo(): void {
                            $this->foo["boof"] = "hello";
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'incorrectDocblockOrder' => [
                '<?php
                    class MyClass {
                        /**
                         * Comment
                         * @var $fooPropTypo string
                         */
                        public $fooProp = "/tmp/file.txt";
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'badlyFormattedVar' => [
                '<?php
                    /**
                     * @return string[]
                     */
                    function returns_strings() {
                        /** @var array(string) $result */
                        $result = ["example"];
                        return $result;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badlyWrittenVar' => [
                '<?php
                    /** @param mixed $x */
                    function myvalue($x): void {
                        /** @var $myVar MyNS\OtherClass */
                        $myVar = $x->conn()->method();
                        $myVar->otherMethod();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'dontOverrideSameType' => [
                '<?php
                    class A {
                        /** @return ?int */
                        public function foo(): ?int {
                            if (rand(0, 1)) return 5;
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'alwaysCheckReturnType' => [
                '<?php
                    class A {}

                    /**
                     * @return A
                     * @psalm-suppress MismatchingDocblockReturnType
                     */
                    function foo(): B {
                      return new A;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'preventBadBoolean' => [
                '<?php
                    function foo(): boolean {
                        return true;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'undefinedDocblockClassCall' => [
                '<?php
                    class B {
                        /**
                         * @return A
                         * @psalm-suppress UndefinedDocblockClass
                         * @psalm-suppress InvalidReturnStatement
                         * @psalm-suppress InvalidReturnType
                         */
                        public function foo() {
                            return new stdClass();
                        }

                        public function bar() {
                            $this->foo()->bar();
                        }
                    }
                    ',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'preventBadObjectLikeFormat' => [
                '<?php
                    /**
                     * @param array{} $arr
                     */
                    function bar(array $arr): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'noPhpStormAnnotationsThankYou' => [
                '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString(ArrayIterator $i): void {}',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'noPhpStormAnnotationsPossiblyInvalid' => [
                '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString($i): void {
                        $s = $i->offsetGet("a");
                    }',
                'error_message' => 'PossiblyInvalidMethodCall',
            ],
            'badStaticVar' => [
                '<?php
                    /** @var static */
                    $a = new stdClass();',
                'error_message' => 'InvalidDocblock',
            ],
            'doubleBar' => [
                '<?php
                    /** @param PDO||Closure|numeric $a */
                    function foo($a) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'badStringVar' => [
                '<?php
                    /** @var string; */
                    $a = "hello";',
                'error_message' => 'InvalidDocblock',
            ],
            'badCallableVar' => [
                '<?php
                    /** @return Closure(int): */
                    function foo() : callable {
                        return function () : void {};
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'hyphenInType' => [
                '<?php
                    /**
                     * @return - Description
                     */
                    function example() {
                        return "placeholder";
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badAmpersand' => [
                '<?php
                    /** @return &array */
                    function foo() : array {
                        return [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidTypeAlias' => [
                '<?php
                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInObjectLike' => [
                '<?php
                    /**
                     * @psalm-type aType null|"a"|"b"|"c"|"d"
                     */

                    /** @psalm-return array{0:bool,1:aType} */
                    function f(): array {
                        return [(bool)rand(0,1), rand(0,1) ? "z" : null];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'noCrashOnHalfDoneArrayPropertyType' => [
                '<?php
                    class A {
                        /** @var array< */
                        private $foo = [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnHalfDoneObjectLikeArrayPropertyType' => [
                '<?php
                    class A {
                        /** @var array{ */
                        private $foo = [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnInvalidClassTemplateAsType' => [
                '<?php
                    /**
                     * @template T as ' . '
                     */
                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnInvalidFunctionTemplateAsType' => [
                '<?php
                    /**
                     * @template T as ' . '
                     */
                    function foo() : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'returnTypeNewLineIsIgnored' => [
                '<?php
                    /**
                     * @return
                     *     Some text
                     */
                    function foo() {}',
                'error_message' => 'MissingReturnType',
            ],
            'objectWithPropertiesAnnotationNoMatchingProperty' => [
                '<?php
                    /** @param object{foo:string} $o */
                    function foo(object $o) : string {
                        return $o->foo;
                    }

                    class A {}

                    foo(new A);',
                'error_message' => 'InvalidArgument',
            ],
            'badVar' => [
                '<?php
                    /** @var Foo */
                    $a = $_GET["foo"];',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'badPsalmType' => [
                '<?php
                    /**
                     * @psalm-type Foo = array{a:}
                     */',
                'error_message' => 'InvalidDocblock',
            ],
            'mismatchingDocblockParamName' => [
                '<?php
                    /** @param string[] $bar */
                    function f(array $barb): void {}',
                'error_message' => 'InvalidDocblockParamName - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:41',
            ],
            'nonEmptyArrayCalledWithEmpty' => [
                '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    foo([]);',
                'error_message' => 'InvalidArgument',
            ],
            'nonEmptyArrayCalledWithEmptyInNamespace' => [
                '<?php
                    namespace ns;

                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    foo([]);',
                'error_message' => 'InvalidArgument',
            ],
            'nonEmptyArrayCalledWithArray' => [
                '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    /** @param array<string> $arr */
                    function bar(array $arr) {
                        foo($arr);
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'automaticInheritDoc' => [
                '<?php
                    class Y {
                        /**
                         * @param string[] $arr
                         */
                        public function boo(array $arr) : void {}
                    }

                    class X extends Y {
                        public function boo(array $arr) : void {}
                    }

                    (new X())->boo([1, 2]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorArrayAnnotationBadArg' => [
                '<?php
                    /** @param string[] $s */
                    function foo(string ...$s) : void {}
                    foo(5);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorArrayAnnotationBadSpreadArg' => [
                '<?php
                    /** @param string[] $s */
                    function foo(string ...$s) : void {}
                    foo(...[5]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall1' => [
                '<?php
                    /** @param string &...$s */
                    function foo(&...$s) : void {}

                    $a = 1;
                    foo($a);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall2' => [
                '<?php
                    /** @param string ...&$s */
                    function foo(&...$s) : void {}

                    $b = 2;
                    foo($b);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall3' => [
                '<?php
                    /** @param string[] &$s */
                    function foo(&...$s) : void {}

                    $c = 3;
                    foo($c);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'identifyReturnType' => [
                '<?php
                    /** @return array{hello: string} */
                    function foo() {}',
                'error_message' => 'InvalidReturnType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:33',
            ],
            'invalidParamDocblockAsterisk' => [
                '<?php
                    /**
                     * @param    *   $reference
                     */
                    function f($reference) {}',
                'error_message' => 'MissingDocblockType'
            ],
        ];
    }
}
