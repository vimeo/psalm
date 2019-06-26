<?php
namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;
use function is_array;

class TypeReconciliationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /** @var FileAnalyzer */
    protected $file_analyzer;

    /** @var StatementsAnalyzer */
    protected $statements_analyzer;

    /**
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        $this->file_analyzer = new FileAnalyzer($this->project_analyzer, 'somefile.php', 'somefile.php');
        $this->file_analyzer->context = new Context();
        $this->statements_analyzer = new StatementsAnalyzer($this->file_analyzer);
    }

    /**
     * @dataProvider providerTestReconcilation
     *
     * @param string $expected
     * @param string $type
     * @param string $string
     *
     * @return void
     */
    public function testReconcilation($expected, $type, $string)
    {
        $reconciled = Reconciler::reconcileTypes(
            $type,
            Type::parseString($string),
            null,
            $this->statements_analyzer,
            false,
            []
        );

        $this->assertSame(
            $expected,
            $reconciled->getId()
        );

        if (is_array($reconciled->getTypes())) {
            $this->assertContainsOnlyInstancesOf('Psalm\Type\Atomic', $reconciled->getTypes());
        }
    }

    /**
     * @dataProvider providerTestTypeIsContainedBy
     *
     * @param string $input
     * @param string $container
     *
     * @return void
     */
    public function testTypeIsContainedBy($input, $container)
    {
        $this->assertTrue(
            TypeAnalyzer::isContainedBy(
                $this->project_analyzer->getCodebase(),
                Type::parseString($input),
                Type::parseString($container)
            )
        );
    }

    /**
     * @return void
     */
    public function testNegateFormula()
    {
        $formula = [
            new Clause(['$a' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(2, $negated_formula);
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $negated_formula[1]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy']]),
            new Clause(['$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(1, $negated_formula);
        $this->assertSame(['$a' => ['falsy'], '$b' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['int', 'string'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertCount(3, $negated_formula);
        $this->assertSame(['$a' => ['!int']], $negated_formula[0]->possibilities);
        $this->assertSame(['$a' => ['!string']], $negated_formula[1]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $negated_formula[2]->possibilities);
    }

    /**
     * @return void
     */
    public function testContainsClause()
    {
        $this->assertTrue(
            (new Clause(
                [
                    '$a' => ['!falsy'],
                    '$b' => ['!falsy'],
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy'],
                    ]
                )
            )
        );

        $this->assertFalse(
            (new Clause(
                [
                    '$a' => ['!falsy'],
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!falsy'],
                        '$b' => ['!falsy'],
                    ]
                )
            )
        );
    }

    /**
     * @return void
     */
    public function testSimplifyCNF()
    {
        $formula = [
            new Clause(['$a' => ['!falsy']]),
            new Clause(['$a' => ['falsy'], '$b' => ['falsy']]),
        ];

        $simplified_formula = Algebra::simplifyCNF($formula);

        $this->assertCount(2, $simplified_formula);
        $this->assertSame(['$a' => ['!falsy']], $simplified_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $simplified_formula[1]->possibilities);
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public function providerTestReconcilation()
    {
        return [
            'notNullWithObject' => ['MyObject', '!null', 'MyObject'],
            'notNullWithObjectPipeNull' => ['MyObject', '!null', 'MyObject|null'],
            'notNullWithMyObjectPipeFalse' => ['false|MyObject', '!null', 'MyObject|false'],
            'notNullWithMixed' => ['mixed', '!null', 'mixed'],

            'notEmptyWithMyObject' => ['MyObject', '!falsy', 'MyObject'],
            'notEmptyWithMyObjectPipeNull' => ['MyObject', '!falsy', 'MyObject|null'],
            'notEmptyWithMyObjectPipeFalse' => ['MyObject', '!falsy', 'MyObject|false'],
            'notEmptyWithMixed' => ['non-empty-mixed', '!falsy', 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithMyObjectFalseTrue' => ['MyObject|true', '!falsy', 'MyObject|bool'],

            'nullWithMyObjectPipeNull' => ['null', 'null', 'MyObject|null'],
            'nullWithMixed' => ['null', 'null', 'mixed'],

            'falsyWithMyObject' => ['mixed', 'falsy', 'MyObject'],
            'falsyWithMyObjectPipeFalse' => ['false', 'falsy', 'MyObject|false'],
            'falsyWithMyObjectPipeBool' => ['false', 'falsy', 'MyObject|bool'],
            'falsyWithMixed' => ['empty-mixed', 'falsy', 'mixed'],
            'falsyWithBool' => ['false', 'falsy', 'bool'],
            'falsyWithStringOrNull' => ['null|string()|string(0)', 'falsy', 'string|null'],
            'falsyWithScalarOrNull' => ['empty-scalar', 'falsy', 'scalar'],

            'notMyObjectWithMyObjectPipeBool' => ['bool', '!MyObject', 'MyObject|bool'],
            'notMyObjectWithMyObjectPipeNull' => ['null', '!MyObject', 'MyObject|null'],
            'notMyObjectWithMyObjectAPipeMyObjectB' => ['MyObjectB', '!MyObjectA', 'MyObjectA|MyObjectB'],

            'myObjectWithMyObjectPipeBool' => ['MyObject', 'MyObject', 'MyObject|bool'],
            'myObjectWithMyObjectAPipeMyObjectB' => ['MyObjectA', 'MyObjectA', 'MyObjectA|MyObjectB'],

            'array' => ['array<array-key, mixed>', 'array', 'array|null'],

            '2dArray' => ['array<array-key, array<array-key, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['numeric-string', 'numeric', 'string'],

            'nullableClassString' => ['null', 'falsy', '?class-string'],
            'mixedOrNullNotFalsy' => ['non-empty-mixed', '!falsy', 'mixed|null'],
            'mixedOrNullFalsy' => ['null|empty-mixed', 'falsy', 'mixed|null'],
            'nullableClassStringFalsy' => ['null', 'falsy', 'class-string<A>|null'],
            'nullableClassStringEqualsNull' => ['null', '=null', 'class-string<A>|null'],
            'nullableClassStringTruthy' => ['class-string<A>', '!falsy', 'class-string<A>|null'],
        ];
    }

    /**
     * @return array<string,array{string,string}>
     */
    public function providerTestTypeIsContainedBy()
    {
        return [
            'arrayContainsWithArrayOfStrings' => ['array<string>', 'array'],
            'arrayContainsWithArrayOfExceptions' => ['array<Exception>', 'array'],

            'unionContainsWithstring' => ['string', 'string|false'],
            'unionContainsWithFalse' => ['false', 'string|false'],
            'objectLikeTypeWithPossiblyUndefinedToGeneric' => [
                'array{0: array{a: string}, 1: array{c: string, e: string}}',
                'array<int, array<string, string>>',
            ],
            'objectLikeTypeWithPossiblyUndefinedToEmpty' => [
                'array<empty, empty>',
                'array{a?: string, b?: string}',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'intIsMixed' => [
                '<?php
                    /** @param mixed $a */
                    function foo($a): void {
                        $b = 5;

                        if ($b === $a) { }
                    }',
            ],
            'typeResolutionFromDocblock' => [
                '<?php
                    class A { }

                    /**
                     * @param  A $a
                     * @return void
                     */
                    function fooFoo($a) {
                        if ($a instanceof A) {
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['RedundantConditionGivenDocblockType'],
            ],
            'arrayTypeResolutionFromDocblock' => [
                '<?php
                    /**
                     * @param string[] $strs
                     * @return void
                     */
                    function foo(array $strs) {
                        foreach ($strs as $str) {
                            if (is_string($str)) {}
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['RedundantConditionGivenDocblockType'],
            ],
            'typeResolutionFromDocblockInside' => [
                '<?php
                    /**
                     * @param int $length
                     * @return void
                     */
                    function foo($length) {
                        if (!is_int($length)) {
                            if (is_numeric($length)) {
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['DocblockTypeContradiction'],
            ],
            'notInstanceof' => [
                '<?php
                    class A { }

                    class B extends A { }

                    $a = new A();

                    $out = null;

                    if ($a instanceof B) {
                        // do something
                    }
                    else {
                        $out = $a;
                    }',
                'assertions' => [
                    '$out' => 'null|A',
                ],
            ],
            'notInstanceOfProperty' => [
                '<?php
                    class B { }

                    class C extends B { }

                    class A {
                        /** @var B */
                        public $foo;

                        public function __construct() {
                            $this->foo = new B();
                        }
                    }

                    $a = new A();

                    $out = null;

                    if ($a->foo instanceof C) {
                        // do something
                    }
                    else {
                        $out = $a->foo;
                    }',
                'assertions' => [
                    '$out' => 'null|B',
                ],
                'error_levels' => [],
            ],
            'notInstanceOfPropertyElseif' => [
                '<?php
                    class B { }

                    class C extends B { }

                    class A {
                        /** @var string|B */
                        public $foo = "";
                    }

                    $a = new A();

                    $out = null;

                    if (is_string($a->foo)) {

                    }
                    elseif ($a->foo instanceof C) {
                        // do something
                    }
                    else {
                        $out = $a->foo;
                    }',
                'assertions' => [
                    '$out' => 'null|B',
                ],
                'error_levels' => [],
            ],
            'typeRefinementWithIsNumericOnIntOrFalse' => [
                '<?php
                    /** @return void */
                    function fooFoo(string $a) {
                        if (is_numeric($a)) { }

                        if (is_numeric($a) && $a === "1") { }
                    }

                    $b = rand(0, 1) ? 5 : false;
                    if (is_numeric($b)) { }',
            ],
            'typeRefinementWithIsNumericAndIsString' => [
                '<?php
                    /**
                     * @param mixed $a
                     * @return void
                     */
                    function foo ($a) {
                        if (is_numeric($a)) {
                            if (is_string($a)) {
                            }
                        }
                    }',
            ],
            'typeRefinementWithIsNumericOnIntOrString' => [
                '<?php
                    $a = rand(0, 5) > 4 ? "hello" : 5;

                    if (is_numeric($a)) {
                      exit;
                    }',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'typeRefinementWithStringOrTrue' => [
                '<?php
                    $a = rand(0, 5) > 4 ? "hello" : true;

                    if (is_bool($a)) {
                      exit;
                    }',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'updateMultipleIssetVars' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}

                    $a = rand(0, 1) ? ["hello"] : null;
                    if (isset($a[0])) {
                        foo($a[0]);
                    }',
            ],
            'updateMultipleIssetVarsWithVariableOffset' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}

                    $a = rand(0, 1) ? ["hello"] : null;
                    $b = 0;
                    if (isset($a[$b])) {
                        foo($a[$b]);
                    }',
            ],
            'instanceOfSubtypes' => [
                '<?php
                    abstract class A {}
                    class B extends A {}

                    abstract class C {}
                    class D extends C {}

                    function makeA(): A {
                      return new B();
                    }

                    function makeC(): C {
                      return new D();
                    }

                    $a = rand(0, 1) ? makeA(): makeC();

                    if ($a instanceof B || $a instanceof D) { }',
            ],
            'typeReconciliationAfterIfAndReturn' => [
                '<?php
                    /**
                     * @param string|int $a
                     * @return string|int
                     */
                    function foo($a) {
                        if (is_string($a)) {
                            return $a;
                        } elseif (is_int($a)) {
                            return $a;
                        }

                        throw new \LogicException("Runtime error");
                    }',
                'assertions' => [],
                'error_levels' => ['RedundantConditionGivenDocblockType'],
            ],
            'ignoreNullCheckAndMaintainNullValue' => [
                '<?php
                    $a = null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    '$b' => 'null',
                ],
                'error_levels' => ['TypeDoesNotContainType', 'RedundantCondition'],
            ],
            'ignoreNullCheckAndMaintainNullableValue' => [
                '<?php
                    $a = rand(0, 1) ? 5 : null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    '$b' => 'int|null',
                ],
            ],
            'ternaryByRefVar' => [
                '<?php
                    function foo(): void {
                        $b = null;
                        $c = rand(0, 1) ? bar($b): null;
                        if (is_int($b)) { }
                    }
                    function bar(?int &$a): void {
                        $a = 5;
                    }',
            ],
            'ternaryByRefVarInConditional' => [
                '<?php
                    function foo(): void {
                        $b = null;
                        if (rand(0, 1) || bar($b)) {
                            if (is_int($b)) { }
                        }
                    }
                    function bar(?int &$a): void {
                        $a = 5;
                    }',
            ],
            'possibleInstanceof' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    class A
                    {
                        public function foo(): void {
                            if ($this instanceof I1 || $this instanceof I2) {}
                        }
                    }',
            ],
            'intersection' => [
                '<?php
                    interface I {
                        public function bat(): void;
                    }

                    function takesI(I $i): void {}
                    function takesA(A $a): void {}
                    /** @param A&I $a */
                    function takesAandI($a): void {}
                    /** @param I&A $a */
                    function takesIandA($a): void {}

                    class A {
                        /**
                         * @return A&I|null
                         */
                        public function foo() {
                            if ($this instanceof I) {
                                $this->bar();
                                $this->bat();

                                takesA($this);
                                takesI($this);
                                takesAandI($this);
                                takesIandA($this);
                            }
                        }

                        protected function bar(): void {}
                    }

                    class B extends A implements I {
                        public function bat(): void {}
                    }',
            ],
            'createIntersectionOfInterfaceAndClass' => [
                '<?php
                    class A {
                      public function bat() : void {}
                    }
                    interface I {
                      public function baz();
                    }

                    function foo(I $i) : void {
                      if ($i instanceof A) {
                        $i->bat();
                        $i->baz();
                      }
                    }

                    function bar(A $a) : void {
                      if ($a instanceof I) {
                        $a->bat();
                        $a->baz();
                      }
                    }

                    class B extends A implements I {
                      public function baz() : void {}
                    }

                    foo(new B);
                    bar(new B);',
            ],
            'unionOfArrayOrTraversable' => [
                '<?php
                    function foo(iterable $iterable) : void {
                        if (\is_array($iterable) || $iterable instanceof \Traversable) {}
                    }',
            ],
            'isTruthy' => [
                '<?php
                    function f(string $s = null): string {
                      if ($s == true) {
                          return $s;
                      }

                      return "backup";
                    }',
            ],
            'stringOrCallableArg' => [
                '<?php
                    /**
                     * @param string|callable $param
                     */
                    function f($param): void {}
                    f("is_array");',
            ],
            'stringOrCallableOrObjectArg' => [
                '<?php
                    /**
                     * @param string|callable|object $param
                     */
                    function f($param): void {}
                    f("is_array");',
            ],
            'intOrFloatArg' => [
                '<?php
                    /**
                     * @param int|float $param
                     */
                    function f($param): void {}
                    f(5.0);
                    f(5);',
            ],
            'nullReplacement' => [
                '<?php
                    /**
                     * @param string|null|false $a
                     * @return string|false $a
                     */
                    function foo($a) {
                      if ($a === null) {
                        if (rand(0, 4) > 2) {
                          $a = "hello";
                        } else {
                          $a = false;
                        }
                      }

                      return $a;
                    }',
            ],
            'nullableIntReplacement' => [
                '<?php
                    $a = rand(0, 1) ? 5 : null;

                    $b = (bool)rand(0, 1);

                    if ($b || $a !== null) {
                        $a = 3;
                    }',
                'assertions' => [
                    '$a' => 'int|null',
                ],
            ],
            'eraseNullAfterInequalityCheck' => [
                '<?php
                    $a = mt_rand(0, 1) ? mt_rand(-10, 10): null;

                    if ($a > 0) {
                      echo $a + 3;
                    }

                    if (0 < $a) {
                      echo $a + 3;
                    }',
            ],
            'twoWrongsDontMakeARight' => [
                '<?php
                    if (rand(0, 1)) {
                        $a = false;
                    } else {
                        $a = false;
                    }',
                'assertions' => [
                    '$a' => 'false',
                ],
            ],
            'instanceofStatic' => [
                '<?php
                    abstract class Foo {
                        /**
                         * @return static[]
                         */
                        abstract public static function getArr() : array;

                        /**
                         * @return static|null
                         */
                        public static function getOne() {
                            $one = current(static::getArr());
                            return $one instanceof static ? $one : null;
                        }
                    }',
            ],
            'isaStaticClass' => [
                '<?php
                    abstract class Foo {
                        /**
                         * @return static[]
                         */
                        abstract public static function getArr() : array;

                        /**
                         * @return static|null
                         */
                        public static function getOne() {
                            $one = current(static::getArr());
                            return is_a($one, static::class, false) ? $one : null;
                        }
                    }',
            ],
            'isAClass' => [
                '<?php
                    class A {}
                    $a_class = rand(0, 1) ? A::class : "blargle";
                    if (is_a($a_class, A::class, true)) {
                      echo "cool";
                    }',
            ],
            'specificArrayFields' => [
                '<?php
                    /**
                     * @param array{field:string} $array
                     */
                    function print_field($array) : void {
                        echo $array["field"];
                    }

                    /**
                     * @param array{field:string,otherField:string} $array
                     */
                    function has_mix_of_fields($array) : void {
                        print_field($array);
                    }',
            ],
            'numericOrStringPropertySet' => [
                '<?php
                    /**
                     * @param string|null $b
                     * @psalm-suppress DocblockTypeContradiction
                     */
                    function foo($b = null) : void {
                        if (is_numeric($b) || is_string($b)) {
                            takesNullableString($b);
                        }
                    }

                    function takesNullableString(?string $s) : void {}',
            ],
            'falsyScalar' => [
                '<?php
                    /**
                     * @param scalar|null $value
                     */
                    function Foo($value = null) : bool {
                      if (!$value) {
                        return true;
                      }
                      return false;
                    }',
            ],
            'numericStringAssertion' => [
                '<?php
                    /**
                     * @param mixed $a
                     */
                    function foo($a, string $b) : void {
                        if (is_numeric($b) && $a === $b) {
                            echo $a;
                        }
                    }',
            ],
            'reconcileNullableStringWithWeakEquality' => [
                '<?php
                    function foo(?string $s) : void {
                        if ($s == "hello" || $s == "goodbye") {
                            if ($s == "hello") {
                                echo "cool";
                            }
                            echo "cooler";
                        }
                    }',
            ],
            'reconcileNullableStringWithStrictEqualityStrings' => [
                '<?php
                    function foo(?string $s, string $a, string $b) : void {
                        if ($s === $a || $s === $b) {
                            if ($s === $a) {
                                echo "cool";
                            }
                            echo "cooler";
                        }
                    }',
            ],
            'reconcileNullableStringWithWeakEqualityStrings' => [
                '<?php
                    function foo(?string $s, string $a, string $b) : void {
                        if ($s == $a || $s == $b) {
                            if ($s == $a) {
                                echo "cool";
                            }
                            echo "cooler";
                        }
                    }',
            ],
            'allowWeakEqualityScalarType' => [
                '<?php
                    function foo(int $i) : void {
                        if ($i == "5") {}
                        if ("5" == $i) {}
                        if ($i == 5.0) {}
                        if (5.0 == $i) {}
                        if ($i == 0) {}
                        if (0 == $i) {}
                        if ($i == 0.0) {}
                        if (0.0 == $i) {}
                    }
                    function bar(float $i) : void {
                        $i = $i / 100.0;
                        if ($i == "5") {}
                        if ("5" == $i) {}
                        if ($i == 5) {}
                        if (5 == $i) {}
                        if ($i == "0") {}
                        if ("0" == $i) {}
                        if ($i == 0) {}
                        if (0 == $i) {}
                    }
                    function bat(string $i) : void {
                        if ($i == 5) {}
                        if (5 == $i) {}
                        if ($i == 5.0) {}
                        if (5.0 == $i) {}
                        if ($i == 0) {}
                        if (0 == $i) {}
                        if ($i == 0.0) {}
                        if (0.0 == $i) {}
                    }',
            ],
            'filterSubclassBasedOnParentInstanceof' => [
                '<?php
                    class A {}
                    class B extends A {
                       public function foo() : void {}
                    }

                    class C {}
                    class D extends C {}

                    $b_or_d = rand(0, 1) ? new B : new D;

                    if ($b_or_d instanceof A) {
                        $b_or_d->foo();
                    }',
            ],
            'SKIPPED-isArrayOnArrayKeyOffset' => [
                '<?php
                    /** @var array{s:array<mixed, array<int, string>|string>} */
                    $doc = [];

                    if (!is_array($doc["s"]["t"])) {
                        $doc["s"]["t"] = [$doc["s"]["t"]];
                    }',
                'assertions' => [
                    '$doc[\'s\'][\'t\']' => 'array<int, string>',
                ],
            ],
            'removeTrue' => [
                '<?php
                    $a = rand(0, 1) ? new stdClass : true;

                    if ($a === true) {
                      exit;
                    }

                    function takesStdClass(stdClass $s) : void {}
                    takesStdClass($a);',
            ],
            'noReconciliationInElseIf' => [
                '<?php
                    class A {}
                    $a = rand(0, 1) ? new A : null;

                    if (rand(0, 1)) {
                        // do nothing
                    } elseif (!$a) {
                        $a = new A();
                    }

                    if ($a) {}',
            ],
            'removeStringWithIsScalar' => [
                '<?php
                    $a = rand(0, 1) ? "hello" : null;

                    if (is_scalar($a)) {
                        exit;
                    }',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'removeNullWithIsScalar' => [
                '<?php
                    $a = rand(0, 1) ? "hello" : null;

                    if (!is_scalar($a)) {
                        exit;
                    }',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'scalarToNumeric' => [
                '<?php
                    /**
                     * @param scalar $thing
                     */
                    function Foo($thing) : void {
                        if (is_numeric($thing)) {}
                    }',
            ],
            'filterSubclassBasedOnParentNegativeInstanceof' => [
                '<?php
                    class Obj {}
                    class A extends Obj {}
                    class B extends A {}
                    class C extends Obj {}
                    class D extends C {}

                    function takesD(D $d) : void {}

                    /** @param B|D $bar */
                    function foo(Obj $bar) : void {
                        if (!$bar instanceof A) {
                            takesD($bar);
                        }
                    }',
            ],
            'dontEliminateAssignOp' => [
                '<?php
                    class Obj {}
                    class A extends Obj {}
                    class B extends A {}
                    class C extends Obj {}
                    class D extends C {}
                    class E extends C {}

                    function bar(Obj $node) : void {
                        if ($node instanceof B
                            || $node instanceof D
                            || $node instanceof E
                        ) {
                            if ($node instanceof C) {}
                            if ($node instanceof D) {}
                        }
                    }',
            ],
            'eliminateNonArrays' => [
                '<?php
                    interface I {}

                    function takesArray(array $_a): void {}

                    /** @param string|I|string[]|I[] $p */
                    function eliminatesNonArray($p): void {
                        if (is_array($p)) {
                            takesArray($p);
                        }
                    }',
            ],
            'eliminateNonIterable' => [
                '<?php
                    /**
                     * @param  iterable<string>|null $foo
                     */
                    function d(?iterable $foo): void {
                        if (is_iterable($foo)) {
                            foreach ($foo as $f) {}
                        }

                        if (!is_iterable($foo)) {

                        } else {
                            foreach ($foo as $f) {}
                        }
                    }',
            ],
            'isStringServerVar' => [
                '<?php
                    if (is_string($_SERVER["abc"])) {
                        echo substr($_SERVER["abc"], 1, 2);
                    }',
            ],
            'notObject' => [
                '<?php
                  function f(): ?object {
                        return rand(0,1) ? new stdClass : null;
                  }

                  $data = f();
                  if (!$data) {}
                  if ($data) {}',
            ],
            'reconcileWithInstanceof' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function b() : bool {
                            return (bool) rand(0, 1);
                        }
                    }

                    function bar(?A $a) : void {
                        if (!$a || ($a instanceof B && $a->b())) {}
                    }',
            ],
            'reconcileFloatToEmpty' => [
                '<?php
                    function bar(float $f) : void {
                        if (!$f) {}
                    }',
            ],
            'scalarToBool' => [
                '<?php
                    /** @param mixed $s */
                    function foo($s) : void {
                        if (!is_scalar($s)) {
                            return;
                        }

                        if (is_bool($s)) {}
                        if (!is_bool($s)) {}
                        if (is_string($s)) {}
                        if (!is_string($s)) {}
                        if (is_int($s)) {}
                        if (!is_int($s)) {}
                        if (is_float($s)) {}
                        if (!is_float($s)) {}
                    }',
            ],
            'removeFromArray' => [
                '<?php
                    /**
                     * @param array<string> $v
                     */
                    function foo(array $v) : void {
                        if (!isset($v[0])) {
                            return;
                        }

                        if ($v[0] === " ") {
                            array_shift($v);
                        }

                        if (!isset($v[0])) {}
                    }',
            ],
            'arrayEquality' => [
                '<?php
                    /**
                     * @param array<string, array<array-key, string|int>> $haystack
                     * @param array<array-key, int|string> $needle
                     */
                    function foo(array $haystack, array $needle) : void {
                        foreach ($haystack as $arr) {
                            if ($arr === $needle) {}
                        }
                    }',
            ],
            'classResolvesBackToSelfAfterComparison' => [
                '<?php
                    class A {}
                    class B extends A {}
                    function getA() : A {
                      return new A();
                    }

                    $a = getA();
                    if ($a instanceof B) {
                        $a = new B;
                    }',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'isNumericCanBeScalar' => [
                '<?php
                    /** @param scalar $val */
                    function foo($val) : void {
                        if (!is_numeric($val)) {}
                    }',
            ],
            'classStringCanBeFalsy' => [
                '<?php
                    /** @param class-string<stdClass>|null $val */
                    function foo(?string $val) : void {
                        if (!$val) {}
                        if ($val) {}
                    }',
            ],
            'allowStringToObjectReconciliation' => [
                '<?php
                    /**
                     * @param string|object $maybe
                     *
                     * @throws InvalidArgumentException but it should not
                     */
                    function foo($maybe) : string {
                        /** @psalm-suppress DocblockTypeContradiction */
                        if ( ! is_string($maybe) && ! is_object($maybe)) {
                            throw new InvalidArgumentException("bad");
                        }

                        return is_string($maybe) ? $maybe : get_class($maybe);
                    }',
            ],
            'allowObjectToStringReconciliation' => [
                '<?php
                    /**
                     * @param string|object $maybe
                     *
                     * @throws InvalidArgumentException but it should not
                     */
                    function bar($maybe) : string {
                        /** @psalm-suppress DocblockTypeContradiction */
                        if ( ! is_string($maybe) && ! is_object($maybe)) {
                            throw new InvalidArgumentException("bad");
                        }

                        return is_object($maybe) ? get_class($maybe) : $maybe;
                    }',
            ],
            'removeArrayWithIterableCheck' => [
                '<?php
                    $s = rand(0,1) ? "foo" : [1];
                    if (!is_iterable($s)) {
                        strlen($s);
                    }',
            ],
            'removeIterableWithIterableCheck' => [
                '<?php
                    /** @var string|iterable */
                    $s = rand(0,1) ? "foo" : [1];
                    if (!is_iterable($s)) {
                        strlen($s);
                    }',
            ],
            'removeArrayWithIterableCheckWithExit' => [
                '<?php
                    $a = rand(0,1) ? "foo" : [1];
                    if (is_iterable($a)) {
                        return;
                    }
                    strlen($a);',
            ],
            'removeIterableWithIterableCheckWithExit' => [
                '<?php
                    /** @var string|iterable */
                    $a = rand(0,1) ? "foo" : [1];
                    if (is_iterable($a)) {
                        return;
                    }
                    strlen($a);',
            ],
            'removeCallable' => [
                '<?php
                    $s = rand(0,1) ? "strlen" : [1];
                    if (!is_callable($s)) {
                        array_pop($s);
                    }

                    $a = rand(0, 1) ? (function(): void {}) : 1.1;
                    if (!is_callable($a)) {
                        echo $a;
                    }',
            ],
            'removeCallableWithAssertion' => [
                '<?php
                    /**
                     * @param mixed $p
                     * @psalm-assert !callable $p
                     * @throws TypeError
                     */
                    function assertIsNotCallable($p): void { if (!is_callable($p)) throw new TypeError; }

                    /** @return callable|float */
                    function f() { return rand(0,1) ? "f" : 1.1; }

                    $a = f();
                    assert(!is_callable($a));

                    $b = f();
                    assertIsNotCallable($b);

                    atan($a);
                    atan($b);',
            ],
            'PHP71-removeNonCallable' => [
                '<?php
                    $f = rand(0, 1) ? "strlen" : 1.1;
                    if (is_callable($f)) {
                        Closure::fromCallable($f);
                    }',
            ],
            'compareObjectLikeToArray' => [
                '<?php
                    /**
                     * @param array<"from"|"to", bool> $a
                     * @return array{from:bool, to: bool}
                     */
                    function foo(array $a) : array {
                        return $a;
                    }',
            ],
            'dontChangeScalar' => [
                '<?php
                    /**
                     * @param scalar|null $val
                     */
                    function foo($val) : ? bool {
                        if ("1" === $val || 1 === $val) {
                            return true;
                        } elseif ("0" === $val || 0 === $val) {
                            return false;
                        }

                        return null;
                    }',
            ],
            'emptyArrayCheck' => [
                '<?php
                    /**
                     * @param non-empty-array $x
                     */
                    function example(array $x): void {}

                    /** @var array */
                    $x = [];
                    if ($x !== []) {
                        example($x);
                    }',
            ],
            'emptyArrayCheckInverse' => [
                '<?php
                    /**
                     * @param non-empty-array $x
                     */
                    function example(array $x): void {}

                    /** @var array */
                    $x = [];
                    if ($x === []) {
                    } else {
                        example($x);
                    }',
            ],
            'allowNumericToFoldIntoType' => [
                '<?php
                    /**
                     * @param mixed $width
                     * @param mixed $height
                     *
                     * @throws RuntimeException
                     */
                    function Foo($width, $height) : void {
                        if (!is_numeric($width) || !is_numeric($height)) {
                            throw new RuntimeException("Width & Height were not numeric!");
                        }

                        echo sprintf("padding-top:%s%%;", 100 * ($height/$width));
                    }'
            ],
            'notEmptyCheckOnMixedInTernary' => [
                '<?php
                    $a = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" ? true : false;',
            ],
            'notEmptyCheckOnMixedInIf' => [
                '<?php
                    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
                        $a = true;
                    } else {
                        $a = false;
                    }',
            ],
            'dontRewriteNullableArrayAfterEmptyCheck' => [
                '<?php
                    /**
                     * @param array{x:int,y:int}|null $start_pos
                     * @return array{x:int,y:int}|null
                     */
                    function foo(?array $start_pos) : ?array {
                        if ($start_pos) {}

                        return $start_pos;
                    }',
            ],
            'falseEqualsBoolean' => [
                '<?php
                    class A {}
                    class B extends A {
                        public function foo() : void {}
                    }
                    class C extends A {
                        public function foo() : void {}
                    }
                    function bar(A $a) : void {
                        if (false === (!$a instanceof B || !$a instanceof C)) {
                            return;
                        }
                        $a->foo();
                    }
                    function baz(A $a) : void {
                        if ((!$a instanceof B || !$a instanceof C) === false) {
                            return;
                        }
                        $a->foo();
                    }',
            ],
            'selfInstanceofStatic' => [
                '<?php
                    class A {
                        public function foo(self $value): void {
                            if ($value instanceof static) {}
                        }
                    }',
            ],
            'reconcileCallable' => [
                '<?php
                    function reflectCallable(callable $callable): ReflectionFunctionAbstract {
                        if (\is_array($callable)) {
                            return new \ReflectionMethod($callable[0], $callable[1]);
                        } elseif ($callable instanceof \Closure || \is_string($callable)) {
                            return new \ReflectionFunction($callable);
                        } elseif (\is_object($callable)) {
                            return new \ReflectionMethod($callable, "__invoke");
                        } else {
                            throw new \InvalidArgumentException("Bad");
                        }
                    }'
            ],
            'noLeakyClassType' => [
                '<?php
                    class A {
                        public array $foo = [];
                        public array $bar = [];

                        public function setter() : void {
                            if ($this->foo) {
                                $this->foo = [];
                            }
                        }

                        public function iffer() : bool {
                            return $this->foo || $this->bar;
                        }
                    }'
            ],
            'noLeakyForeachType' => [
                '<?php

                    class A {
                        /** @var mixed */
                        public $_array_value;

                        private function getArrayValue() : ?array {
                            return rand(0, 1) ? [] : null;
                        }

                        public function setValue(string $var) : void {
                            $this->_array_value = $this->getArrayValue();

                            if ($this->_array_value !== null && !count($this->_array_value)) {
                                return;
                            }

                            switch ($var) {
                                case "a":
                                    foreach ($this->_array_value ?: [] as $v) {}
                                    break;

                                case "b":
                                    foreach ($this->_array_value ?: [] as $v) {}
                                    break;
                            }
                        }
                    }',
                [],
                ['MixedAssignment']
            ],
            'nonEmptyThing' => [
                '<?php
                    /** @param mixed $clips */
                    function foo($clips, bool $found, int $id) : void {
                        if ($found === false) {
                            $clips = [];
                        }

                        $i = array_search($id, $clips);

                        if ($i !== false) {
                            unset($clips[$i]);
                        }
                    }',
                [],
                ['MixedArgument', 'MixedArrayAccess', 'MixedAssignment', 'MixedArrayOffset']
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'makeNonNullableNull' => [
                '<?php
                    class A { }
                    $a = new A();
                    if ($a === null) {
                    }',
                'error_message' => 'TypeDoesNotContainNull',
            ],
            'makeInstanceOfThingInElseif' => [
                '<?php
                    class A { }
                    class B { }
                    class C { }
                    $a = rand(0, 10) > 5 ? new A(): new B();
                    if ($a instanceof A) {
                    } elseif ($a instanceof C) {
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'functionValueIsNotType' => [
                '<?php
                    if (json_last_error() === "5") { }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'stringIsNotTnt' => [
                '<?php
                    if (5 === "5") { }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'stringIsNotNull' => [
                '<?php
                    if (5 === null) { }',
                'error_message' => 'TypeDoesNotContainNull',
            ],
            'stringIsNotFalse' => [
                '<?php
                    if (5 === false) { }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'typeTransformation' => [
                '<?php
                    $a = "5";

                    if (is_numeric($a)) {
                        if (is_int($a)) {
                            echo $a;
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'dontEraseNullAfterLessThanCheck' => [
                '<?php
                    $a = mt_rand(0, 1) ? mt_rand(-10, 10): null;

                    if ($a < 0) {
                      echo $a + 3;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'dontEraseNullAfterGreaterThanCheck' => [
                '<?php
                    $a = mt_rand(0, 1) ? mt_rand(-10, 10): null;

                    if (0 > $a) {
                      echo $a + 3;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'nonRedundantConditionGivenDocblockType' => [
                '<?php
                    /** @param array[] $arr */
                    function foo(array $arr) : void {
                       if ($arr === "hello") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'lessSpecificArrayFields' => [
                '<?php
                    /**
                     * @param array{field:string, otherField:string} $array
                     */
                    function print_field($array) : void {
                        echo $array["field"] . " " . $array["otherField"];
                    }

                    print_field(["field" => "name"]);',
                'error_message' => 'InvalidArgument',
            ],
            'intersectionIncorrect' => [
                '<?php
                    interface I {
                        public function bat(): void;
                    }

                    interface C {}

                    /** @param I&C $a */
                    function takesIandC($a): void {}

                    class A {
                        public function foo(): void {
                            if ($this instanceof I) {
                                takesIandC($this);
                            }
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'catchTypeMismatchInBinaryOp' => [
                '<?php
                    /** @return array<int, string|int> */
                    function getStrings(): array {
                        return ["hello", "world", 50];
                    }

                    $a = getStrings();

                    if (is_bool($a[0]) && $a[0]) {}',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventWeakEqualityToObject' => [
                '<?php
                    function foo(int $i, stdClass $s) : void {
                        if ($i == $s) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'properReconciliationInElseIf' => [
                '<?php
                    class A {}
                    $a = rand(0, 1) ? new A : null;

                    if (rand(0, 1)) {
                        $a = new A();
                    } elseif (!$a) {
                        $a = new A();
                    }

                    if ($a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'allRemovalOfStringWithIsScalar' => [
                '<?php
                    $a = rand(0, 1) ? "hello" : "goodbye";

                    if (is_scalar($a)) {
                        exit;
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'noRemovalOfStringWithIsScalar' => [
                '<?php
                    $a = rand(0, 1) ? "hello" : "goodbye";

                    if (!is_scalar($a)) {
                        exit;
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleNullEquality' => [
                '<?php
                    $i = 5;
                    echo $i === null;',
                'error_message' => 'TypeDoesNotContainNull',
            ],
            'impossibleTrueEquality' => [
                '<?php
                    $i = 5;
                    echo $i === true;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleFalseEquality' => [
                '<?php
                    $i = 5;
                    echo $i === false;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'impossibleNumberEquality' => [
                '<?php
                    $i = 5;
                    echo $i === 3;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'SKIPPED-noIntersectionOfArrayOrTraversable' => [
                '<?php
                    function foo(iterable $iterable) : void {
                        if (\is_array($iterable) && $iterable instanceof \Traversable) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'scalarToBoolContradiction' => [
                '<?php
                    /** @param mixed $s */
                    function foo($s) : void {
                        if (!is_scalar($s)) {
                            return;
                        }

                        if (!is_bool($s)) {
                            if (is_bool($s)) {}
                        }
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'noCrashWhenCastingArray' => [
                '<?php
                    function foo() : string {
                        return (object) ["a" => 1, "b" => 2];
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventStrongEqualityScalarType' => [
                '<?php
                    function bar(float $f) : void {
                        if ($f === 0) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'preventYodaStrongEqualityScalarType' => [
                '<?php
                    function bar(float $f) : void {
                        if (0 === $f) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'classCannotNotBeSelf' => [
                '<?php
                    class A {}
                    class B extends A {}
                    function getA() : A {
                      return new A();
                    }

                    $a = getA();
                    if ($a instanceof B) {
                        $a = new B;
                    }

                    if ($a instanceof A) {}',
                'error_message' => 'RedundantCondition',
            ],
            'preventImpossibleComparisonToTrue' => [
                '<?php
                    /** @return false|string */
                    function firstChar(string $s) {
                      return empty($s) ? false : $s[0];
                    }

                    if (true === firstChar("sdf")) {}',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventAlwaysPossibleComparisonToTrue' => [
                '<?php
                    /** @return false|string */
                    function firstChar(string $s) {
                      return empty($s) ? false : $s[0];
                    }

                    if (true !== firstChar("sdf")) {}',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'preventAlwaysImpossibleComparisonToFalse' => [
                '<?php
                    function firstChar(string $s) : string { return $s; }

                    if (false === firstChar("sdf")) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'preventAlwaysPossibleComparisonToFalse' => [
                '<?php
                    function firstChar(string $s) : string { return $s; }

                    if (false !== firstChar("sdf")) {}',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
