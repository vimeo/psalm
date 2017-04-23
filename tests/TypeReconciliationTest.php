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
            'not-null.MyObject' => ['MyObject', '!null', 'MyObject'],
            'not-null.MyObject|null' => ['MyObject', '!null', 'MyObject|null'],
            'not-null.MyObject|false' => ['MyObject|false', '!null', 'MyObject|false'],
            'not-null.mixed' => ['mixed', '!null', 'mixed'],

            'not-empty.MyObject' => ['MyObject', '!empty', 'MyObject'],
            'not-empty.MyObject|null' => ['MyObject', '!empty', 'MyObject|null'],
            'not-empty.MyObject|false' => ['MyObject', '!empty', 'MyObject|false'],
            'not-empty.mixed' => ['mixed', '!empty', 'mixed'],
            // @todo in the future this should also work
            //'not-empty.MyObject|true' => ['MyObject|true', '!empty', 'MyObject|bool'],

            'not-empty.MyObject|null' => ['null', 'null', 'MyObject|null'],
            'not-empty.MyObject|null' => ['null', 'null', 'mixed'],

            'empty.MyObject' => ['null', 'empty', 'MyObject'],
            'empty.MyObject|false' => ['false', 'empty', 'MyObject|false'],
            'empty.MyObject|false' => ['false', 'empty', 'MyObject|false'],
            'empty.MyObject|bool' => ['false', 'empty', 'MyObject|bool'],
            'empty.mixed' => ['mixed', 'empty', 'mixed'],
            'empty.bool' => ['false', 'empty', 'bool'],

            'not-my-object.MyObject|bool' => ['bool', '!MyObject', 'MyObject|bool'],
            'not-my-object.MyObject|null' => ['null', '!MyObject', 'MyObject|null'],
            'not-my-object.MyObjectA|MyObjectB' => ['MyObjectB', '!MyObjectA', 'MyObjectA|MyObjectB'],

            'my-object.MyObject|bool' => ['MyObject', 'MyObject', 'MyObject|bool'],
            'my-object.MyObjectA|MyObjectB' => ['MyObjectA', 'MyObjectA', 'MyObjectA|MyObjectB'],

            'array' => ['array<mixed, mixed>', 'array', 'array|null'],

            '2d-array' => ['array<mixed, array<mixed, string>>', 'array', 'array<array<string>>|null'],

            'numeric' => ['string', 'numeric', 'string']
        ];
    }

    /**
     * @return array
     */
    public function providerTestTypeIsContainedBy()
    {
        return [
            'array-contains.array<string>' => ['array<string>', 'array'],
            'array-contains.array<Exception>' => ['array<Exception>', 'array'],

            'union-contains.string' => ['string', 'string|false'],
            'union-contains.false' => ['false', 'string|false']
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'int-is-mixed' => [
                '<?php
                    function foo($a) : void {
                        $b = 5;
            
                        if ($b === $a) { }
                    }'
            ],
            'type-resolution-from-docblock' => [
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
            'array-type-resolution-from-docblock' => [
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
            'type-resolution-from-docblock-inside' => [
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
            'not-instanceof' => [
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
            'not-instance-of-property' => [
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
            'not-instance-of-property-elseif' => [
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
            'type-arguments' => [
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
                    ['string' => '$hours'],
                    ['string' => '$minutes'],
                    ['string' => '$seconds']
                ]
            ],
            'type-refinement-with-is-numeric' => [
                '<?php
                    /** @return void */
                    function fooFoo(string $a) {
                        if (is_numeric($a)) { }
                    }
            
                    $b = rand(0, 1) ? 5 : false;
                    if (is_numeric($b)) { }'
            ],
            'type-refinement-with-is-numeric-and-is-string' => [
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
            'update-multiple-isset-vars' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}
            
                    $a = rand(0, 1) ? ["hello"] : null;
                    if (isset($a[0])) {
                        foo($a[0]);
                    }'
            ],
            'update-multiple-isset-vars-with-variable-offset' => [
                '<?php
                    /** @return void **/
                    function foo(string $s) {}
            
                    $a = rand(0, 1) ? ["hello"] : null;
                    $b = 0;
                    if (isset($a[$b])) {
                        foo($a[$b]);
                    }'
            ],
            'remove-empty-array' => [
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
            'instance-of-subtypes' => [
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
            'empty-array-reconciliation-then-if' => [
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
            'empty-string-reconciliation-then-if' => [
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
            'empty-exception-reconciliation-after-if' => [
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
            'type-reconciliation-after-if-and-return' => [
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
            'ignore-null-check-and-maintain-null-value' => [
                '<?php
                    $a = null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    ['null' => '$b']
                ],
                'error_levels' => ['FailedTypeResolution']
            ],
            'ignore-null-check-and-maintain-nullable-value' => [
                '<?php
                    $a = rand(0, 1) ? 5 : null;
                    if ($a !== null) { }
                    $b = $a;',
                'assertions' => [
                    ['int|null' => '$b']
                ]
            ],
            'ternary-by-ref-var' => [
                '<?php
                    function foo() : void {
                        $b = null;
                        $c = rand(0, 1) ? bar($b) : null;
                        if (is_int($b)) { }
                    }
                    function bar(?int &$a) : void {
                        $a = 5;
                    }'
            ],
            'ternary-by-ref-var-in-conditional' => [
                '<?php
                    function foo() : void {
                        $b = null;
                        if (rand(0, 1) || bar($b)) {
                            if (is_int($b)) { }
                        }
                    }
                    function bar(?int &$a) : void {
                        $a = 5;
                    }'
            ],
            'possible-instanceof' => [
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
            'make-non-nullable-null' => [
                '<?php
                    class A { }
                    $a = new A();
                    if ($a === null) {
                    }',
                'error_message' => 'TypeDoesNotContainNull'
            ],
            'make-instance-of-thing-in-elseif' => [
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
            'function-value-is-not-type' => [
                '<?php
                    if (json_last_error() === "5") { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'string-is-not-int' => [
                '<?php
                    if (5 === "5") { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'string-is-not-null' => [
                '<?php
                    if (5 === null) { }',
                'error_message' => 'TypeDoesNotContainNull'
            ],
            'string-is-not-false' => [
                '<?php
                    if (5 === false) { }',
                'error_message' => 'TypeDoesNotContainType'
            ],
            'failed-type-resolution' => [
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
            'failed-type-resolution-with-docblock' => [
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
            'type-resolution-from-docblock-and-instanceof' => [
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
            'type-transformation' => [
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
