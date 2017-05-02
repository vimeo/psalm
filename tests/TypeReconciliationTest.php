<?php
namespace Psalm\Tests;

use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Clause;
use Psalm\Context;
use Psalm\Type;

class TypeReconciliationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /** @var FileChecker */
    protected $file_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->file_checker->context = new Context();
    }

    /**
     * @dataProvider providerTestReconcilation
     * @param string $expected
     * @param string $type
     * @param string $string
     * @return void
     */
    public function testReconcilation($expected, $type, $string)
    {
        $reconciled = TypeChecker::reconcileTypes(
            $type,
            Type::parseString($string),
            null,
            $this->file_checker
        );

        $this->assertEquals(
            $expected,
            (string) $reconciled
        );

        if ($reconciled && is_array($reconciled->types)) {
            foreach ($reconciled->types as $type) {
                $this->assertInstanceOf('Psalm\Type\Atomic', $type);
            }
        }
    }

    /**
     * @dataProvider providerTestTypeIsContainedBy
     * @param string $input
     * @param string $container
     * @return void
     */
    public function testTypeIsContainedBy($input, $container)
    {
        $this->assertTrue(
            TypeChecker::isContainedBy(
                Type::parseString($input),
                Type::parseString($container),
                $this->file_checker
            )
        );
    }

    /**
     * @return void
     */
    public function testNegateFormula()
    {
        $formula = [
            new Clause(['$a' => ['!empty']])
        ];

        $negated_formula = AlgebraChecker::negateFormula($formula);

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['empty']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['!empty'], '$b' => ['!empty']])
        ];

        $negated_formula = AlgebraChecker::negateFormula($formula);

        $this->assertSame(2, count($negated_formula));
        $this->assertSame(['$a' => ['empty']], $negated_formula[0]->possibilities);
        $this->assertSame(['$b' => ['empty']], $negated_formula[1]->possibilities);

        $formula = [
            new Clause(['$a' => ['!empty']]),
            new Clause(['$b' => ['!empty']]),
        ];

        $negated_formula = AlgebraChecker::negateFormula($formula);

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['empty'], '$b' => ['empty']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['int', 'string'], '$b' => ['!empty']])
        ];

        $negated_formula = AlgebraChecker::negateFormula($formula);

        $this->assertSame(3, count($negated_formula));
        $this->assertSame(['$a' => ['!int']], $negated_formula[0]->possibilities);
        $this->assertSame(['$a' => ['!string']], $negated_formula[1]->possibilities);
        $this->assertSame(['$b' => ['empty']], $negated_formula[2]->possibilities);
    }

    /**
     * @return void
     */
    public function testContainsClause()
    {
        $this->assertTrue(
            (new Clause(
                [
                    '$a' => ['!empty'],
                    '$b' => ['!empty']
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!empty']
                    ]
                )
            )
        );

        $this->assertFalse(
            (new Clause(
                [
                    '$a' => ['!empty']
                ]
            ))->contains(
                new Clause(
                    [
                        '$a' => ['!empty'],
                        '$b' => ['!empty']
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
            new Clause(['$a' => ['!empty']]),
            new Clause(['$a' => ['empty'], '$b' => ['empty']])
        ];

        $simplified_formula = AlgebraChecker::simplifyCNF($formula);

        $this->assertSame(2, count($simplified_formula));
        $this->assertSame(['$a' => ['!empty']], $simplified_formula[0]->possibilities);
        $this->assertSame(['$b' => ['empty']], $simplified_formula[1]->possibilities);
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

            'notEmptyWithMyObject' => ['MyObject', '!empty', 'MyObject'],
            'notEmptyWithMyObjectPipeNull' => ['MyObject', '!empty', 'MyObject|null'],
            'notEmptyWithMyObjectPipeFalse' => ['MyObject', '!empty', 'MyObject|false'],
            'notEmptyWithMixed' => ['mixed', '!empty', 'mixed'],
            // @todo in the future this should also work
            //'notEmptyWithMyObjectFalseTrue' => ['MyObject|true', '!empty', 'MyObject|bool'],

            'notEmptyWithMyObjectPipeNull' => ['null', 'null', 'MyObject|null'],
            'notEmptyWithMixed' => ['null', 'null', 'mixed'],

            'emptyWithMyObject' => ['null', 'empty', 'MyObject'],
            'emptyWithMyObjectPipeFalse' => ['false', 'empty', 'MyObject|false'],
            'emptyWithMyObjectPipeBool' => ['false', 'empty', 'MyObject|bool'],
            'emptyWithMixed' => ['mixed', 'empty', 'mixed'],
            'emptyWithBool' => ['false', 'empty', 'bool'],

            'notMyObjectWithMyObjectPipeBool' => ['bool', '!MyObject', 'MyObject|bool'],
            'notMyObjectWithMyObjectPipeNull' => ['null', '!MyObject', 'MyObject|null'],
            'notMyObjectWithMyObjectAPipeMyObjectB' => ['MyObjectB', '!MyObjectA', 'MyObjectA|MyObjectB'],

            'myObjectWithMyObjectPipeBool' => ['MyObject', 'MyObject', 'MyObject|bool'],
            'myObjectWithMyObjectAPipeMyObjectB' => ['MyObjectA', 'MyObjectA', 'MyObjectA|MyObjectB'],

            'array' => ['array<mixed, mixed>', 'array', 'array|null'],

            '2dArray' => ['array<mixed, array<mixed, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['string', 'numeric', 'string']
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
            'unionContainsWithFalse' => ['false', 'string|false']
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
                    function foo($a) : void {
                        $b = 5;

                        if ($b === $a) { }
                    }'
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
                    }'
            ],
            'arrayTypeResolutionFromDocblock' => [
                '<?php
                    /**
                     * @param string[] $strs
                     * @return void
                     */
                    function foo(array $strs) {
                        foreach ($strs as $str) {
                            if (is_string($str)) {} // Issue emitted here
                        }
                    }'
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
                    }'
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
                    ['null|A' => '$out']
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$a' => Type::parseString('A')
                ]
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
                    ['null|B' => '$out']
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$a' => Type::parseString('A')
                ]
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
                    ['null|B' => '$out']
                ],
                'error_levels' => [],
                'scope_vars' => [
                    '$a' => Type::parseString('A')
                ]
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
                    ['int' => '$a'],
                    ['int' => '$b'],
                    ['string' => '$c'],
                    ['string|int|float' => '$hours'],
                    ['string|int|float' => '$minutes'],
                    ['string|int|float' => '$seconds']
                ]
            ],
            'typeRefinementWithIsNumeric' => [
                '<?php
                    /** @return void */
                    function fooFoo(string $a) {
                        if (is_numeric($a)) { }
                    }

                    $b = rand(0, 1) ? 5 : false;
                    if (is_numeric($b)) { }'
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
                    }'
            ],
            'updateMultipleIssetVars' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}

                    $a = rand(0, 1) ? ["hello"] : null;
                    if (isset($a[0])) {
                        foo($a[0]);
                    }'
            ],
            'updateMultipleIssetVarsWithVariableOffset' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}

                    $a = rand(0, 1) ? ["hello"] : null;
                    $b = 0;
                    if (isset($a[$b])) {
                        foo($a[$b]);
                    }'
            ],
            'removeEmptyArray' => [
                '<?php
                    $arr_or_string = [];

                    if (rand(0, 1)) {
                      $arr_or_string = "hello";
                    }

                    /** @return void **/
                    function foo(string $s) {}

                    if (!empty($arr_or_string)) {
                        foo($arr_or_string);
                    }'
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

                    $a = rand(0, 1) ? makeA() : makeC();

                    if ($a instanceof B || $a instanceof D) { }'
            ],
            'emptyArrayReconciliationThenIf' => [
                '<?php
                    /**
                     * @param string|string[] $a
                     */
                    function foo($a) : string {
                        if (is_string($a)) {
                            return $a;
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        if (isset($a[0])) {
                            return $a[0];
                        };

                        return "not found";
                    }'
            ],
            'emptyStringReconciliationThenIf' => [
                '<?php
                    /**
                     * @param Exception|string|string[] $a
                     */
                    function foo($a) : string {
                        if (is_array($a)) {
                            return "hello";
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        if (is_string($a)) {
                            return $a;
                        };

                        return "an exception";
                    }'
            ],
            'emptyExceptionReconciliationAfterIf' => [
                '<?php
                    /**
                     * @param Exception|null $a
                     */
                    function foo($a) : string {
                        if ($a && $a->getMessage() === "hello") {
                            return "hello";
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        return $a->getMessage();
                    }'
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
                    }'
            ],
            'ignoreNullCheckAndMaintainNullValue' => [
                '<?php
                    $a = null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    ['null' => '$b']
                ],
                'error_levels' => ['FailedTypeResolution']
            ],
            'ignoreNullCheckAndMaintainNullableValue' => [
                '<?php
                    $a = rand(0, 1) ? 5 : null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    ['int|null' => '$b']
                ]
            ],
            'ternaryByRefVar' => [
                '<?php
                    function foo() : void {
                        $b = null;
                        $c = rand(0, 1) ? bar($b) : null;
                        if (is_int($b)) { }
                    }
                    /** @param ?int $a */
                    function bar(&$a) : void {
                        $a = 5;
                    }'
            ],
            'ternaryByRefVarInConditional' => [
                '<?php
                    function foo() : void {
                        $b = null;
                        if (rand(0, 1) || bar($b)) {
                            if (is_int($b)) { }
                        }
                    }
                    /** @param ?int $a */
                    function bar(&$a) : void {
                        $a = 5;
                    }'
            ],
            'possibleInstanceof' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    class A
                    {
                        public function foo() : void {
                            if ($this instanceof I1 || $this instanceof I2) {}
                        }
                    }'
            ],
            'intersection' => [
                '<?php
                    interface I {
                        public function bat() : void;
                    }

                    function takesI(I $i) : void {}
                    function takesA(A $a) : void {}

                    class A {
                        public function foo() : void {
                            if ($this instanceof I) {
                                $this->bar();
                                $this->bat();

                                takesA($this);
                                takesI($this);
                            }
                        }

                        protected function bar() : void {}
                    }

                    class B extends A implements I {
                        public function bat() : void {}
                    }'
            ]
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
                'error_message' => 'TypeDoesNotContainNull'
            ],
            'makeInstanceOfThingInElseif' => [
                '<?php
                    class A { }
                    class B { }
                    class C { }
                    $a = rand(0, 10) > 5 ? new A() : new B();
                    if ($a instanceof A) {
                    } elseif ($a instanceof C) {
                    }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'functionValueIsNotType' => [
                '<?php
                    if (json_last_error() === "5") { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'stringIsNotTnt' => [
                '<?php
                    if (5 === "5") { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'stringIsNotNull' => [
                '<?php
                    if (5 === null) { }',
                'error_message' => 'TypeDoesNotContainNull'
            ],
            'stringIsNotFalse' => [
                '<?php
                    if (5 === false) { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'failedTypeResolution' => [
                '<?php
                    class A { }

                    /**
                     * @return void
                     */
                    function fooFoo(A $a) {
                        if ($a instanceof A) {
                        }
                    }',
                'error_message' => 'FailedTypeResolution'
            ],
            'failedTypeResolutionWithDocblock' => [
                '<?php
                    class A { }

                    /**
                     * @param  A $a
                     * @return void
                     */
                    function fooFoo(A $a) {
                        if ($a instanceof A) {
                        }
                    }',
                'error_message' => 'FailedTypeResolution'
            ],
            'typeResolutionFromDocblockAndInstanceof' => [
                '<?php
                    class A { }

                    /**
                     * @param  A $a
                     * @return void
                     */
                    function fooFoo($a) {
                        if ($a instanceof A) {
                            if ($a instanceof A) {
                            }
                        }
                    }',
                'error_message' => 'FailedTypeResolution'
            ],
            'typeTransformation' => [
                '<?php
                    $a = "5";

                    if (is_numeric($a)) {
                        if (is_int($a)) {
                            echo $a;
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType'
            ]
        ];
    }
}
