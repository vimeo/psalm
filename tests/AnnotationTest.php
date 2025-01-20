<?php

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class AnnotationTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->reportUnusedVariables();
    }

    public function testLessSpecificImplementedReturnTypeWithDocblockOnMultipleLines(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('LessSpecificImplementedReturnType - somefile.php:16:');

        $this->addFile(
            'somefile.php',
            '<?php

                class ParentClass
                {
                    /**
                     * @return $this
                     */
                    public function execute()
                    {
                        return $this;
                    }
                }

                /**
                 * @method int test()
                 * @method self execute()
                 */
                class BreakingThings extends ParentClass { }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLessSpecificImplementedReturnTypeWithDocblockOnMultipleLinesWithMultipleClasses(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('LessSpecificImplementedReturnType - somefile.php:15:');

        $this->addFile(
            'somefile.php',
            '<?php

                class ParentClass
                {
                    /**
                     * @return $this
                     */
                    public function execute()
                    {
                        return $this;
                    }
                }

                /**
                 * @method self execute()
                 */
                class BreakingThings extends ParentClass
                {
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testLessSpecificImplementedReturnTypeWithDescription(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('LessSpecificImplementedReturnType - somefile.php:19:');

        $this->addFile(
            'somefile.php',
            '<?php

                class ParentClass
                {
                    /**
                     * @return $this
                     */
                    public function execute()
                    {
                        return $this;
                    }
                }

                /**
                 * test test test
                 * test rambling text
                 * test test text
                 *
                 * @method self execute()
                 */
                class BreakingThings extends ParentClass { }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testInvalidParamDefault(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidParamDefault');

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $arr
                 * @return void
                 */
                function foo($arr = false) {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testInvalidParamDefaultButAllowedInConfig(): void
    {
        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                /**
                 * @param array $_arr
                 * @return void
                 */
                function foo($_arr = false) {}
                foo(false);
                foo(["hello"]);',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testInvalidTypehintParamDefaultButAllowedInConfig(): void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('InvalidParamDefault');

        Config::getInstance()->add_param_default_to_docblock_type = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function foo(array $arr = false) : void {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function providerValidCodeParse(): iterable
    {
        return [
            'nopType' => [
                'code' => '<?php
                    $_a = "hello";

                    /** @var int $_a */',
                'assertions' => [
                    '$_a' => 'int',
                ],
            ],
            'validDocblockReturn' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param array $a */
                    function foo($a): void {
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantConditionGivenDocblockType'],
            ],
            'checkArrayWithIs' => [
                'code' => '<?php
                    /** @param mixed $b */
                    function foo($b): void {
                        /**
                         * @psalm-suppress UnnecessaryVarAnnotation
                         * @var array
                         */
                        $a = (array)$b;
                        if (is_array($a)) {
                            // do something
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantConditionGivenDocblockType'],
            ],
            'goodDocblock' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                        return rand(0, 1) ? new A() : null;
                    }

                    function takeA(A $_a): void { }

                    $a = makeA();
                    $a->foo();
                    $a->bar = 7;
                    takeA($a);',
            ],
            'invalidDocblockParamSuppress' => [
                'code' => '<?php
                    /**
                     * @param int $_bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(array $_bar): void {
                    }',
            ],
            'differentDocblockParamClassSuppress' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    /**
                     * @param B $_bar
                     * @psalm-suppress MismatchingDocblockParamType
                     */
                    function fooFoo(A $_bar): void {
                    }',
            ],
            'varDocblock' => [
                'code' => '<?php
                    /** @var array<Exception> */
                    $a = [];

                    echo $a[0]->getMessage();',
            ],
            'ignoreVarDocblock' => [
                'code' => '<?php
                    /**
                     * @var array<Exception>
                     * @ignore-var
                     */
                    $a = [];

                    $a[0]->getMessage();',
                'assertions' => [],
                'ignored_issues' => ['EmptyArrayAccess', 'MixedMethodCall'],
            ],
            'psalmIgnoreVarDocblock' => [
                'code' => '<?php
                    /**
                     * @var array<Exception>
                     * @psalm-ignore-var
                     */
                    $a = [];

                    $a[0]->getMessage();',
                'assertions' => [],
                'ignored_issues' => ['EmptyArrayAccess', 'MixedMethodCall'],
            ],
            'mixedDocblockParamTypeDefinedInParent' => [
                'code' => '<?php
                    class A {
                        /** @param mixed $a */
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'intDocblockParamTypeDefinedInParent' => [
                'code' => '<?php
                    class A {
                        /** @param int $a */
                        public function foo($a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
            ],
            'varSelf' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function takesInt(int $_a): void {}

                    /**
                     * @psalm-param  array<int, string> $a
                     * @param string[] $a
                     */
                    function foo(array $a): void {
                        foreach ($a as $key => $_value) {
                            takesInt($key);
                        }
                    }',
            ],
            'returnDocblock' => [
                'code' => '<?php
                    function foo(int $i): int {
                        /** @var int */
                        return $i;
                    }',
            ],
            'doubleVar' => [
                'code' => '<?php
                    function foo() : array {
                        return ["hello" => new stdClass, "goodbye" => new stdClass];
                    }

                    $_a = null;
                    $_b = null;

                    /**
                     * @var string $_key
                     * @var stdClass $_value
                     */
                    foreach (foo() as $_key => $_value) {
                        $_a = $_key;
                        $_b = $_value;
                    }',
                'assertions' => [
                    '$_a' => 'null|string',
                    '$_b' => 'null|stdClass',
                ],
            ],
            'allowOptionalParamsToBeEmptyArray' => [
                'code' => '<?php
                    /** @param array{b?: int, c?: string} $_a */
                    function foo(array $_a = []) : void {}',
            ],
            'allowEmptyVarAnnotation' => [
                'code' => '<?php
                    /**
                     * @param $_x
                     */
                    function example(array $_x) : void {}',
            ],
            'allowCapitalisedNamespacedString' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @param String $_x
                     */
                    function example(string $_x) : void {}',
            ],
            'megaClosureAnnotationWithoutSpacing' => [
                'code' => '<?php
                    /** @var array{a:Closure():(array<mixed, mixed>|null), b?:Closure():array<mixed, mixed>, c?:Closure():array<mixed, mixed>, d?:Closure():array<mixed, mixed>, e?:Closure():(array{f:null|string, g:null|string, h:null|string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), p?:Closure():(array{f:null|string, g:null|string, h:null|string, q:string, i:string, j:mixed, k:mixed, l:mixed, m:mixed, n:bool, o?:array{0:string}}|null), r?:Closure():(array<mixed, mixed>|null), s:array<mixed, mixed>} */
                    $arr = [];

                    $arr["a"]();',
            ],
            'megaClosureAnnotationWithSpacing' => [
                'code' => '<?php
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
            'multipleLineGenericArray' => [
                'code' => '<?php
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
            'multipleLineGenericArray2' => [
                'code' => '<?php
                    /**
                     * @psalm-type TRelAlternate =
                     * list<
                     *      array{
                     *          href: string,
                     *          lang: string
                     *      }
                     * >
                     */
                    class A {
                        /** @return TRelAlternate */
                        public function ret(): array { return []; }
                    }

                    $_ = (new A)->ret();
                ',
                'assertions' => [
                    '$_===' => 'list<array{href: string, lang: string}>',
                ],
            ],
            'invalidPsalmForMethodShouldNotBreakDocblock' => [
                'code' => '<?php
                    class A {
                        /**
                         * @psalm-impure
                         * @param string $arg
                         * @return non-falsy-string
                         */
                        public function foo($arg) {
                            return $arg . "bar";
                        }
                    }

                    $a = new A();
                    $_ = $a->foo("hello");
                ',
                'assertions' => [
                    '$_===' => 'non-falsy-string',
                ],
                'ignored_issues' => ['InvalidDocblock'],
            ],
            'invalidPsalmForFunctionShouldNotBreakDocblock' => [
                'code' => '<?php
                    /**
                     * @psalm-impure
                     * @param string $arg
                     * @return non-falsy-string
                     */
                    function foo($arg) {
                        return $arg . "bar";
                    }

                    $_ = foo("hello");
                ',
                'assertions' => [
                    '$_===' => 'non-falsy-string',
                ],
                'ignored_issues' => ['InvalidDocblock'],
            ],
            'builtInClassInAShape' => [
                'code' => '<?php
                    /**
                     * @return array{d:Exception}
                     * @psalm-suppress InvalidReturnType
                     */
                    function f() {}',
            ],
            'slashAfter?' => [
                'code' => '<?php
                    namespace ns;

                    /** @param ?\stdClass $_s */
                    function foo($_s) : void {
                    }

                    foo(null);
                    foo(new \stdClass);',
            ],
            'returnTypeShouldBeNullable' => [
                'code' => '<?php
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
                'code' => '<?php
                    /** @param string[] $_s */
                    function foo(string ...$_s) : void {}
                    /** @param string ...$_s */
                    function bar(string ...$_s) : void {}
                    foo("hello", "goodbye");
                    bar("hello", "goodbye");
                    foo(...["hello", "goodbye"]);
                    bar(...["hello", "goodbye"]);',
            ],
            'spreadOperatorByRefAnnotation' => [
                'code' => '<?php
                    /**
                     * @param string &...$s
                     * @psalm-suppress UnusedParam
                     */
                    function foo(&...$s) : void {}
                    /**
                     * @param string ...&$s
                     * @psalm-suppress UnusedParam
                     */
                    function bar(&...$s) : void {}
                    /**
                     * @param string[] &$s
                     * @psalm-suppress UnusedParam
                     */
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
                'code' => '<?php
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
                'code' => '<?php
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

                    /** @param CoolType $_a **/
                    function bar ($_a) : void { }

                    bar(foo());',
            ],
            'typeAliasBeforeFunction' => [
                'code' => '<?php
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

                    /** @param CoolType $_a **/
                    function bar ($_a) : void { }

                    bar(foo());',
            ],
            'typeAliasInSeparateBlockBeforeFunction' => [
                'code' => '<?php
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

                    /** @param CoolType $_a **/
                    function bar ($_a) : void { }

                    bar(foo());',
            ],
            'almostFreeStandingTypeAlias' => [
                'code' => '<?php
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

                    /** @param CoolType $_a **/
                    function bar ($_a) : void { }

                    bar(foo());',
            ],
            'typeAliasUsedTwice' => [
                'code' => '<?php
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
                        /**
                         * @psalm-suppress UnnecessaryVarAnnotation
                         * @var _A
                         */
                        $r = $p;
                        return $r;
                    }',
            ],
            'listUnpackWithDocblock' => [
                'code' => '<?php
                    interface I {}

                    class A implements I {
                        public function bar() : void {}
                    }

                    /** @return I[] */
                    function foo() : array {
                        return [new A()];
                    }

                    /** @var A $a1 */
                    [$a1, $_a2] = foo();

                    $a1->bar();',
            ],
            'spaceInType' => [
                'code' => '<?php
                    /** @return string | null */
                    function foo(string $s = null) {
                        return $s;
                    }',
            ],
            'missingReturnTypeWithBadDocblockIgnoreBoth' => [
                'code' => '<?php
                    /**
                     * @return [bad]
                     */
                    function fooBar() {
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidDocblock', 'MissingReturnType'],
            ],
            'objectWithPropertiesAnnotation' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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

                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @var string $item
                         */
                        foreach ($arr as list($item)) {}

                        if (is_null($item)) {}
                    }

                    function baz(array $arr) : void {
                        $item = null;

                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @var string $item
                         */
                        foreach ($arr as list($item => $_)) {}

                        if (is_null($item)) {}
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'extraneousDocblockParamName' => [
                'code' => '<?php
                    /**
                     * @param string $_foo
                     * @param string[] $bar
                     * @param string[] $_barb
                     */
                    function f(string $_foo, array $_barb): void {}',
            ],
            'nonEmptyArray' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'noExceptionOnIntersection' => [
                'code' => '<?php
                    class Foo {
                        /** @var null|\DateTime&\DateTimeImmutable */
                        private $s = null;
                    }',
            ],
            'intersectionWithSpace' => [
                'code' => '<?php
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
                    }',
            ],
            'allowClosingComma' => [
                'code' => '<?php
                    /**
                     * @psalm-type _Alias=array{
                     *    foo: string,
                     *    bar: string,
                     *    baz: array{
                     *       a: int,
                     *    },
                     * }
                     */
                    class Foo { }

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
                     * } $_foo
                     */
                    $_foo = ["foo" => "", "bar" => "", "baz" => ""];',
            ],
            'returnNumber' => [
                'code' => '<?php
                    class C {
                        /**
                         * @return 1
                         */
                        public static function barBar() {
                            return 1;
                        }
                    }',
            ],
            'returnNumberForInterface' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return 1
                         */
                        public static function barBar();
                    }',
            ],
            'psalmTypeAnnotationAboveReturn' => [
                'code' => '<?php
                    /**
                     * @psalm-type Person = array{name: string, age: int}
                     */

                    /**
                     * @psalm-return Person
                     */
                    function getPerson_error(): array {
                        $json = \'{"name": "John", "age": 44}\';
                        /** @psalm-var Person */
                        return json_decode($json, true);
                    }',
            ],
            'allowDocblockDefinedTKeyedArrayIntoNonEmpty' => [
                'code' => '<?php
                    /** @param non-empty-array $_bar */
                    function foo(array $_bar) : void { }

                    /** @var array{0:list<string>, 1:list<int>} */
                    $bar = [[], []];

                    foo($bar);',
            ],
            'allowResourceInList' => [
                'code' => '<?php
                    /** @param list<scalar|array|object|resource|null> $_s */
                    function foo(array $_s) : void { }',
            ],
            'possiblyUndefinedObjectProperty' => [
                'code' => '<?php
                    function consume(string $value): void {
                        echo $value;
                    }

                    /** @var object{value?: string} $data */
                    $data = json_decode("{}", false);
                    consume($data->value ?? "");',
            ],
            'throwSelf' => [
                'code' => '<?php
                    namespace Foo;

                    class MyException extends \Exception {
                        /**
                         * @throws self
                         */
                        public static function create(): void {
                            throw new self();
                        }
                    }',
            ],
            'parseTrailingCommaInReturn' => [
                'code' => '<?php
                    /**
                     * @psalm-return array{
                     *     a: int,
                     *     b: string,
                     * }
                     */
                    function foo(): array {
                        return ["a" => 1, "b" => "two"];
                    }',
            ],
            'falsableFunctionAllowedWhenBooleanExpected' => [
                'code' => '<?php

                    /** @psalm-return bool */
                    function alwaysFalse1()
                    {
                        return false;
                    }

                    function alwaysFalse2(): bool
                    {
                        return false;
                    }',
            ],
            'dontInheritDocblockReturnWhenRedeclared' => [
                'code' => '<?php
                    interface Id {}

                    class UserId implements Id {}

                    interface Entity {
                        /** @psalm-return Id */
                        function id(): Id;
                    }

                    class User implements Entity {
                        public function id(): UserId {
                            return new UserId();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'arrayWithKeySlashesAndNewline' => [
                'code' => '<?php
                    $_arr = ["foo\\bar\nbaz" => "literal"];',
                'assertions' => [
                    '$_arr' => 'array{\'foo\\\\bar\nbaz\': string}',
                ],
            ],
            'doubleSpaceBeforeAt' => [
                'code' => '<?php
                    /**
                     *  @param string $_c
                     */
                    function foo($_c) : void {}',
            ],
            'throwsAnnotationWithBarAndSpace' => [
                'code' => '<?php
                    /**
                     * @throws \Exception| \InvalidArgumentException
                     */
                    function bar() : void {}',
            ],
            'varDocblockAboveCall' => [
                'code' => '<?php

                    function example(string $s): void {
                        if (preg_match(\'{foo-(\w+)}\', $s, $m)) {
                          /** @var array{string, string} $m */
                          takesString($m[1]);
                        }
                    }

                    function takesString(string $_s): void {}',
            ],
            'noCrashWithoutAssignment' => [
                'code' => '<?php
                    /** @var DateTime $obj */
                    echo $obj->format("Y");',
            ],
            'intMaskWithClassConstants' => [
                'code' => '<?php
                    class FileFlag {
                        public const OPEN = 1;
                        public const MODIFIED = 2;
                        public const NEW = 4;
                    }

                    /**
                     * @param int-mask<FileFlag::OPEN, FileFlag::MODIFIED, FileFlag::NEW> $flags
                     */
                    function takesFlags(int $flags) : void {
                        echo $flags;
                    }

                    takesFlags(FileFlag::MODIFIED | FileFlag::NEW);',
            ],
            'intMaskWithZero' => [
                'code' => '<?php
                    /** @param int-mask<1,2> $_flags */
                    function takesFlags(int $_flags): void {}

                    takesFlags(0);
                ',
            ],
            'intMaskOfWithClassWildcard' => [
                'code' => '<?php
                    class FileFlag {
                        public const OPEN = 1;
                        public const MODIFIED = 2;
                        public const NEW = 4;
                    }

                    /**
                     * @param int-mask-of<FileFlag::*> $flags
                     */
                    function takesFlags(int $flags) : void {
                        echo $flags;
                    }

                    takesFlags(FileFlag::MODIFIED | FileFlag::NEW);',
            ],
            'intMaskOfWithZero' => [
                'code' => '<?php
                    class FileFlag {
                        public const OPEN = 1;
                        public const MODIFIED = 2;
                        public const NEW = 4;
                    }

                    /** @param int-mask-of<FileFlag::*> $_flags */
                    function takesFlags(int $_flags): void {}

                    takesFlags(0);
                ',
            ],
            'emptyStringFirst' => [
                'code' => '<?php
                    /**
                     * @param \'\'|\'a\'|\'b\' $v
                     */
                    function testBad(string $v): void {
                        echo $v;
                    }',
            ],
            'UnnecessaryVarAnnotationSuppress' => [
                'code' => '<?php
                    /** @psalm-consistent-constructor */
                    final class Foo{}
                    /**
                     * @param class-string $class
                     */
                    function foo(string $class): Foo {
                        if (!is_subclass_of($class, Foo::class)) {
                            throw new \LogicException();
                        }

                        /**
                         * @psalm-suppress UnnecessaryVarAnnotation
                         * @var Foo $instance
                         */
                        $instance = new $class();

                        return $instance;
                    }',
            ],
            'suppressNonInvariantDocblockPropertyType' => [
                'code' => '<?php
                    class Vendor
                    {
                        /**
                         * @var array
                         */
                        public array $config = [];
                        public function getConfig(): array {return $this->config;}
                    }

                    class A extends Vendor
                    {
                        /**
                         * @var string[]
                         * @psalm-suppress NonInvariantDocblockPropertyType
                         */
                        public array $config = [];
                    }
                    $a = new Vendor();
                    $_b = new A();
                    echo (string)($a->getConfig()[0]??"");',
            ],
            'promotedPropertiesDocumentationEitherForParamOrForProperty' => [
                'code' => '<?php
                    final class UserRole
                    {
                        /** @psalm-param stdClass $id */
                        public function __construct(
                            protected $id,
                            /** @psalm-var stdClass */
                            protected $id2
                        ) {
                        }
                    }

                    new UserRole(new stdClass(), new stdClass());
                    ',
            ],
            'promotedPropertiesDocumentationForPropertyAndSignature' => [
                'code' => '<?php
                    final class A
                    {
                        public function __construct(
                            /**
                             * @var iterable<string>
                             */
                            private iterable $strings,
                        ) {
                        }
                    }',
            ],
            'globalDocBlock' => [
                'code' => '<?php
                    function f(): string {
                        /** @var string $a */
                        global $a;
                        return $a;
                    }',
            ],
            'globalDocBlockInGlobalScope' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidGlobal
                     * @var string $a
                     */
                    global $a;
                    echo strlen($a);
                ',
            ],
            'multiLineArrayShapeWithComments' => [
                'code' =>
                    <<<EOT
                    <?php
                    /**
                     * @return array { // Array with comments
                     *     // Array with single quoted keys
                     *     'single quote keys': array {           // Single quoted key
                     *         'single_quote_key//1': int,        // Single quoted key with //
                     *         'single_quote_key\'//2': string,   // Single quoted key with ' and //
                     *         'single_quote_key\'//\'3': bool,   // Single quoted key with 2x ' and //
                     *         'single_quote_key"//"4': float,    // Single quoted key with 2x " and //
                     *         'single_quote_key"//\'5': array {  // Single quoted key with ', " and //
                     *             'single_quote_key//5//1': int, // Single quoted key with 2x //
                     *         },
                     *         // 'commented_out_array_element//1': int
                     *         'single_quote_key//no_whitespace':int,//Single quoted key without whitespace
                     *     },
                     *     // Array with double quoted keys
                     *     "double quote keys": array {           // Double quoted key
                     *         "double_quote_key//1": int,        // Double quoted key with //
                     *         "double_quote_key'//2": string,    // Double quoted key with ' and //
                     *         "double_quote_key\"//\"3": bool,   // Double quoted key with 2x ' and //
                     *         "double_quote_key'//'4": float,    // Double quoted key with 2x " and //
                     *         "double_quote_key\"//'5": array {  // Double quoted key with ', " and //
                     *             "double_quote_key//5//1": int, // Double quoted key with 2x //
                     *         },
                     *         // "commented_out_array_element//1": int
                     *         "double_quote_key//no_whitespace":int,//Double quoted key without whitespace
                     *     },
                     * }
                     */
                    function f(): array
                    {
                        return [
                            'single quote keys' => [
                                'single_quote_key//1' => 1,
                                'single_quote_key\'//2' => 'string',
                                'single_quote_key\'//\'3' => true,
                                'single_quote_key"//"4' => 0.1,
                                'single_quote_key"//\'5' => [
                                    'single_quote_key//5//1' => 1,
                                ],
                                'single_quote_key//no_whitespace' => 1
                            ],
                            "double quote keys" => [
                                "double_quote_key//1" => 1,
                                "double_quote_key'//2" => 'string',
                                "double_quote_key\"//\"3" => true,
                                "double_quote_key'//'4" => 0.1,
                                "double_quote_key\"//'5" => [
                                    "double_quote_key//5//1" => 1,
                                ],
                                "double_quote_key//no_whitespace" => 1
                            ],
                        ];
                    }
                    EOT,
            ],
            'validArrayKeyAlias' => [
                'code' => '<?php
                    /**
                     * @psalm-type ArrayKeyType array-key
                     */
                    class Bar {}

                    /**
                     * @psalm-import-type ArrayKeyType from Bar
                     * @psalm-type UsesArrayKeyType array<ArrayKeyType, bool>
                     */
                    class Foo {}',
                'assertions' => [],
            ],
            'sinceTagNonPhpVersion' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @since 8.9.9
                         */
                        public function bar() : void {
                        }
                    };',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidClassMethodReturn' => [
                'code' => '<?php
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
            'invalidArrayKeyType' => [
                'code' => '<?php
                    /**
                     * @param array<float, string> $arg
                     * @return void
                     */
                    function foo($arg) {}',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidClassMethodReturnBrackets' => [
                'code' => '<?php
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
                'code' => '<?php
                    interface I {
                        /**
                         * @return $thus
                         */
                        public static function barBar();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'invalidInterfaceMethodReturnBrackets' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return []
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidPropertyBrackets' => [
                'code' => '<?php
                    class A {
                        /**
                         * @var []
                         */
                        public $bar;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidReturnClassWithComma' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return 1,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'returnClassWithComma' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return a,
                         */
                        public static function barBar();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'missingParamType' => [
                'code' => '<?php
                    /**
                     * @param string $bar
                     */
                    function fooBar(): void {
                    }

                    fooBar("hello");',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:21 - Too many arguments for fooBar '
                    . '- expecting 0 but saw 1',
            ],
            'missingParamVar' => [
                'code' => '<?php
                    /**
                     * @param string
                     */
                    function fooBar(): void {
                    }',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:21 - Badly-formatted @param',
            ],
            'invalidSlashWithString' => [
                'code' => '<?php
                    /**
                     * @return \?string
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'missingReturnTypeWithBadDocblock' => [
                'code' => '<?php
                    /**
                     * @return [bad]
                     */
                    function fooBar() {
                    }',
                'error_message' => 'MissingReturnType',
                'ignored_issues' => ['InvalidDocblock'],
            ],
            'invalidDocblockReturn' => [
                'code' => '<?php
                    /**
                     * @return string
                     */
                    function fooFoo(): int {
                        return 5;
                    }',
                'error_message' => 'MismatchingDocblockReturnType',
            ],
            'intParamTypeDefinedInParent' => [
                'code' => '<?php
                    class A {
                        public function foo(int $a): void {}
                    }

                    class B extends A {
                        public function foo($a): void {}
                    }',
                'error_message' => 'MissingParamType',
                'ignored_issues' => ['MethodSignatureMismatch'],
            ],
            'psalmInvalidVar' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @param mixed $x */
                    function myvalue($x): void {
                        /** @var $myVar MyNS\OtherClass */
                        $myVar = $x->conn()->method();
                        $myVar->otherMethod();
                    }',
                'error_message' => 'MissingDocblockType',
            ],
            'dontOverrideSameType' => [
                'code' => '<?php
                    class A {
                        /** @return ?int */
                        public function foo(): ?int {
                            if (rand(0, 1)) return 5;
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'alwaysCheckReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(): boolean {
                        return true;
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'undefinedDocblockClassCall' => [
                'code' => '<?php
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
            'invalidTaintEscapeAnnotation' => [
                'code' => '<?php
                    /**
                     * @psalm-taint-escape
                     */
                    function takesInt(int $i): int {
                        return $i;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'noPhpStormAnnotationsThankYou' => [
                'code' => '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString(ArrayIterator $i): void {}',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'noPhpStormAnnotationsPossiblyInvalid' => [
                'code' => '<?php
                    /** @param ArrayIterator|string[] $i */
                    function takesArrayIteratorOfString($i): void {
                        $s = $i->offsetGet("a");
                    }',
                'error_message' => 'PossiblyInvalidMethodCall',
            ],
            'doubleBar' => [
                'code' => '<?php
                    /** @param PDO||Closure|numeric $a */
                    function foo($a) : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'badStringVar' => [
                'code' => '<?php
                    /** @var string; */
                    $a = "hello";',
                'error_message' => 'InvalidDocblock',
            ],
            'badCallableVar' => [
                'code' => '<?php
                    /** @return Closure(int): */
                    function foo() : callable {
                        return function () : void {};
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'hyphenInType' => [
                'code' => '<?php
                    /**
                     * @return - Description
                     */
                    function example() {
                        return "placeholder";
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'badAmpersand' => [
                'code' => '<?php
                    /** @return &array */
                    function foo() : array {
                        return [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'invalidTypeAlias' => [
                'code' => '<?php
                    /**
                     * @psalm-type CoolType = A|B>
                     */

                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'typeAliasInTKeyedArray' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        /** @var array< */
                        private $foo = [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnHalfDoneTKeyedArrayPropertyType' => [
                'code' => '<?php
                    class A {
                        /** @var array{ */
                        private $foo = [];
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnInvalidClassTemplateAsType' => [
                'code' => '<?php
                    /**
                     * @template T as ' . '
                     */
                    class A {}',
                'error_message' => 'InvalidDocblock',
            ],
            'noCrashOnInvalidFunctionTemplateAsType' => [
                'code' => '<?php
                    /**
                     * @template T as ' . '
                     */
                    function foo() : void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'returnTypeNewLineIsIgnored' => [
                'code' => '<?php
                    /**
                     * @return
                     *     Some text
                     */
                    function foo() {}',
                'error_message' => 'MissingReturnType',
            ],
            'objectWithPropertiesAnnotationNoMatchingProperty' => [
                'code' => '<?php
                    /** @param object{foo:string} $o */
                    function foo(object $o) : string {
                        return $o->foo;
                    }

                    class A {}

                    foo(new A);',
                'error_message' => 'InvalidArgument',
            ],
            'badVar' => [
                'code' => '<?php
                    /** @var Foo */
                    $a = $_GET["foo"];',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'badPsalmType' => [
                'code' => '<?php
                    /**
                     * @psalm-type Foo = array{a:}
                     */',
                'error_message' => 'InvalidDocblock',
            ],
            'mismatchingDocblockParamName' => [
                'code' => '<?php
                    /** @param string[] $bar */
                    function f(array $barb): void {}',
                'error_message' => 'InvalidDocblockParamName - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:41',
            ],
            'nonEmptyArrayCalledWithEmpty' => [
                'code' => '<?php
                    /** @param non-empty-array<string> $arr */
                    function foo(array $arr) : void {
                        foreach ($arr as $a) {}
                        echo $a;
                    }

                    foo([]);',
                'error_message' => 'InvalidArgument',
            ],
            'nonEmptyArrayCalledWithEmptyInNamespace' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'spreadOperatorArrayAnnotationBadArg' => [
                'code' => '<?php
                    /** @param string[] $_s */
                    function foo(string ...$_s) : void {}
                    foo(5);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorArrayAnnotationBadSpreadArg' => [
                'code' => '<?php
                    /** @param string[] $_s */
                    function foo(string ...$_s) : void {}
                    foo(...[5]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall1' => [
                'code' => '<?php
                    /**
                     * @param string &...$s
                     * @psalm-suppress UnusedParam
                     */
                    function foo(&...$s) : void {}

                    $a = 1;
                    foo($a);',
                'error_message' => 'InvalidArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall2' => [
                'code' => '<?php
                    /**
                     * @param string ...&$s
                     * @psalm-suppress UnusedParam
                     */
                    function foo(&...$s) : void {}

                    $b = 2;
                    foo($b);',
                'error_message' => 'InvalidArgument',
            ],
            'spreadOperatorByRefAnnotationBadCall3' => [
                'code' => '<?php
                    /**
                     * @param string[] &$s
                     * @psalm-suppress UnusedParam
                     */
                    function foo(&...$s) : void {}

                    $c = 3;
                    foo($c);',
                'error_message' => 'InvalidArgument',
            ],
            'identifyReturnType' => [
                'code' => '<?php
                    /** @return array{hello: string} */
                    function foo() {}',
                'error_message' => 'InvalidReturnType - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:33',
            ],
            'invalidParamDocblockAsterisk' => [
                'code' => '<?php
                    /**
                     * @param    *   $reference
                     */
                    function f($reference) {}',
                'error_message' => 'MissingDocblockType',
            ],
            'canNeverReturnDeclaredType' => [
                'code' => '<?php

                    /** @psalm-return false */
                    function alwaysFalse() : bool
                    {
                        return true;
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:32',
            ],
            'falsableWithExpectedTypeTrue' => [
                'code' => '<?php

                    /** @psalm-return true */
                    function alwaysFalse()
                    {
                        return false;
                    }',
                'error_message' => 'FalsableReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:32',
            ],
            'DuplicatedParam' => [
                'code' => '<?php
                    /**
                     * @psalm-param array $arr
                     * @psalm-param array $arr
                     */
                    function bar(array $arr): void {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - Found duplicated @param or prefixed @param tag in docblock for bar',
            ],
            'DuplicatedReturn' => [
                'code' => '<?php
                    /**
                     * @return void
                     * @return void
                     */
                    function bar(array $arr): void {}',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:21 - Found duplicated @return or prefixed @return tag in docblock for bar',
            ],
            'missingClassForTKeyedArray' => [
                'code' => '<?php
                    interface I {
                        /** @return object{id: int, a: int} */
                        public function run();
                    }

                    class C implements I {
                        /** @return X */
                        public function run() {}
                    }',
                'error_message' => 'ImplementedReturnTypeMismatch',
            ],
            'unexpectedImportType' => [
                'code' => '<?php
                    /** @psalm-import-type asd */
                    function f(): void {}
                ',
                'error_message' => 'PossiblyInvalidDocblockTag',
            ],
            'unexpectedVarOnFunction' => [
                'code' => '<?php
                    /** @var int $p */
                    function f($p): void {}
                ',
                'error_message' => 'PossiblyInvalidDocblockTag',
            ],
            'unterminatedParentheses' => [
                'code' => '<?php
                    /** @return ( */
                    function f() {}
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'emptyParentheses' => [
                'code' => '<?php
                    /** @return () */
                    function f() {}
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'unbalancedParentheses' => [
                'code' => "<?php
                    /** @return ((string) */
                    function f(): string {
                        return '';
                    }
                ",
                'error_message' => 'InvalidDocblock',
            ],
            'promotedPropertiesDocumentationFailsWhenSendingBadTypeAgainstParam' => [
                'code' => '<?php
                    final class UserRole
                    {
                        /** @psalm-param stdClass $id */
                        public function __construct(
                            protected $id
                        ) {
                        }

                    }
                    new UserRole("a");
                    ',
                'error_message' => 'InvalidArgument',
            ],
            'promotedPropertiesDocumentationFailsWhenSendingBadTypeAgainstProperty' => [
                'code' => '<?php
                    final class UserRole
                    {
                        public function __construct(
                            /** @psalm-var stdClass */
                            protected $id2
                        ) {
                        }
                    }

                    new UserRole("a");
                    ',
                'error_message' => 'InvalidArgument',
            ],
            'promotedPropertyDuplicateDoc' => [
                'code' => '<?php
                    final class UserRole
                    {
                        /** @psalm-param string $id */
                        public function __construct(
                            /** @psalm-var stdClass */
                            protected $id
                        ) {
                        }
                    }
                    ',
                'error_message' => 'InvalidDocblock',
            ],
            'promotedPropertyWithParamDocblockAndSignatureType' => [
                'code' => '<?php

                    class A
                    {
                        public function __construct(
                            /** @var "cti"|"basic"|"teams"|"" */
                            public string $licenseType = "",
                        ) {
                        }
                    }

                    $a = new A("ladida");
                    $a->licenseType = "dudidu";

                    echo $a->licenseType;',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
