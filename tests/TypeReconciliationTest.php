<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Clause;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Algebra;
use Psalm\Type\Reconciler;

class TypeReconciliationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /** @var FileChecker */
    protected $file_checker;

    /** @var StatementsChecker */
    protected $statements_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->file_checker = new FileChecker($this->project_checker, 'somefile.php', 'somefile.php');
        $this->file_checker->context = new Context();
        $this->statements_checker = new StatementsChecker($this->file_checker);
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
            $this->statements_checker
        );

        $this->assertSame(
            $expected,
            (string) $reconciled
        );

        if (is_array($reconciled->getTypes())) {
            foreach ($reconciled->getTypes() as $type) {
                $this->assertInstanceOf('Psalm\Type\Atomic', $type);
            }
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
            TypeChecker::isContainedBy(
                $this->project_checker->codebase,
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

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertSame(2, count($negated_formula));
        $this->assertSame(['$a' => ['falsy']], $negated_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $negated_formula[1]->possibilities);

        $formula = [
            new Clause(['$a' => ['!falsy']]),
            new Clause(['$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['falsy'], '$b' => ['falsy']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['int', 'string'], '$b' => ['!falsy']]),
        ];

        $negated_formula = Algebra::negateFormula($formula);

        $this->assertSame(3, count($negated_formula));
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

        $this->assertSame(2, count($simplified_formula));
        $this->assertSame(['$a' => ['!falsy']], $simplified_formula[0]->possibilities);
        $this->assertSame(['$b' => ['falsy']], $simplified_formula[1]->possibilities);
    }

    /**
     * @return array
     */
    public function providerTestReconcilation()
    {
        return [
            'notNullWithObject' => ['MyObject', '!null', 'MyObject'],
            'notNullWithObjectPipeNull' => ['MyObject', '!null', 'MyObject|null'],
            'notNullWithMyObjectPipeFalse' => ['MyObject|false', '!null', 'MyObject|false'],
            'notNullWithMixed' => ['mixed', '!null', 'mixed'],

            'notEmptyWithMyObject' => ['MyObject', '!falsy', 'MyObject'],
            'notEmptyWithMyObjectPipeNull' => ['MyObject', '!falsy', 'MyObject|null'],
            'notEmptyWithMyObjectPipeFalse' => ['MyObject', '!falsy', 'MyObject|false'],
            'notEmptyWithMixed' => ['mixed', '!falsy', 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithMyObjectFalseTrue' => ['MyObject|true', '!falsy', 'MyObject|bool'],

            'nullWithMyObjectPipeNull' => ['null', 'null', 'MyObject|null'],
            'nullWithMixed' => ['null', 'null', 'mixed'],

            'falsyWithMyObject' => ['mixed', 'falsy', 'MyObject'],
            'falsyWithMyObjectPipeFalse' => ['false', 'falsy', 'MyObject|false'],
            'falsyWithMyObjectPipeBool' => ['false', 'falsy', 'MyObject|bool'],
            'falsyWithMixed' => ['mixed', 'falsy', 'mixed'],
            'falsyWithBool' => ['false', 'falsy', 'bool'],
            'falsyWithStringOrNull' => ['null|string', 'falsy', 'string|null'],
            'falsyWithScalarOrNull' => ['scalar', 'falsy', 'scalar'],

            'notMyObjectWithMyObjectPipeBool' => ['bool', '!MyObject', 'MyObject|bool'],
            'notMyObjectWithMyObjectPipeNull' => ['null', '!MyObject', 'MyObject|null'],
            'notMyObjectWithMyObjectAPipeMyObjectB' => ['MyObjectB', '!MyObjectA', 'MyObjectA|MyObjectB'],

            'myObjectWithMyObjectPipeBool' => ['MyObject', 'MyObject', 'MyObject|bool'],
            'myObjectWithMyObjectAPipeMyObjectB' => ['MyObjectA', 'MyObjectA', 'MyObjectA|MyObjectB'],

            'array' => ['array<mixed, mixed>', 'array', 'array|null'],

            '2dArray' => ['array<mixed, array<mixed, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['string', 'numeric', 'string'],
        ];
    }

    /**
     * @return array
     */
    public function providerTestTypeIsContainedBy()
    {
        return [
            'arrayContainsWithArrayOfStrings' => ['array<string>', 'array'],
            'arrayContainsWithArrayOfExceptions' => ['array<Exception>', 'array'],

            'unionContainsWithstring' => ['string', 'string|false'],
            'unionContainsWithFalse' => ['false', 'string|false'],
            'objectLikeTypeWithPossiblyUndefinedToGeneric' => [
                'array{0:array{a:string}, 1:array{c:string, e:string}}',
                'array<int, array<string, string>>'
            ],
            'objectLikeTypeWithPossiblyUndefinedToEmpty' => [
                'array<empty, empty>',
                'array{a?:string, b?:string}',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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
                'error_levels' => ['RedundantConditionGivenDocblockType'],
            ],
            'notInstanceof' => [
                '<?php
                    class A { }

                    class B extends A { }

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
                'error_levels' => [],
                'scope_vars' => [
                    '$a' => Type::parseString('A'),
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
                'scope_vars' => [
                    '$a' => Type::parseString('A'),
                ],
            ],
            'notInstanceOfPropertyElseif' => [
                '<?php
                    class B { }

                    class C extends B { }

                    class A {
                        /** @var string|B */
                        public $foo = "";
                    }

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
                'scope_vars' => [
                    '$a' => Type::parseString('A'),
                ],
            ],
            'typeArguments' => [
                '<?php
                    $a = min(0, 1);
                    $b = min([0, 1]);
                    $c = min("a", "b");
                    $d = min(1, 2, 3, 4);
                    $e = min(1, 2, 3, 4, 5);
                    sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'string',
                    '$hours' => 'string|int|float',
                    '$minutes' => 'string|int|float',
                    '$seconds' => 'string|int|float',
                ],
            ],
            'typeRefinementWithIsNumeric' => [
                '<?php
                    /** @return void */
                    function fooFoo(string $a) {
                        if (is_numeric($a)) { }
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
                    '$b' => 'null|int',
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
                    }'
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
                    }
                    function bar(float $f) : void {
                      if ($f == 0) {}
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
        ];
    }
}
