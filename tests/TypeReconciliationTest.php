<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Clause;
use Psalm\Config;
use Psalm\Context;
use Psalm\Type;

class TypeReconciliationTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /** @var FileChecker */
    protected $file_checker;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();

        $this->project_checker = new ProjectChecker();
        $this->project_checker->setConfig(new TestConfig());

        $this->file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->file_checker->context = new Context();
    }

    /**
     * @return void
     */
    public function testNotNull()
    {
        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('MyObject'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('MyObject|null'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObject|false',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('MyObject|false'), null, $this->file_checker)
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('!null', Type::parseString('mixed'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function testNotEmpty()
    {
        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('MyObject'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('MyObject|null'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('MyObject|false'), null, $this->file_checker)
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('mixed'), null, $this->file_checker)
        );

        // @todo in the future this should also work
        /*
        $this->assertEquals(
            'MyObject|true',
            (string) TypeChecker::reconcileTypes('!empty', Type::parseString('MyObject|bool'))
        );
         */
    }

    /**
     * @return void
     */
    public function testNull()
    {
        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('MyObject|null'), null, $this->file_checker)
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('null', Type::parseString('mixed'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function testEmpty()
    {
        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('MyObject'), null, $this->file_checker)
        );
        $this->assertEquals(
            'false',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('MyObject|false'), null, $this->file_checker)
        );

        $this->assertEquals(
            'false',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('MyObject|bool'), null, $this->file_checker)
        );

        $this->assertEquals(
            'mixed',
            (string) TypeChecker::reconcileTypes('empty', Type::parseString('mixed'), null, $this->file_checker)
        );

        /** @var Type\Union */
        $reconciled = TypeChecker::reconcileTypes('empty', Type::parseString('bool'), null, $this->file_checker);
        $this->assertEquals('false', (string) $reconciled);
        $this->assertInstanceOf('Psalm\Type\Atomic', $reconciled->types['false']);
    }

    /**
     * @return void
     */
    public function testNotMyObject()
    {
        $this->assertEquals(
            'bool',
            (string) TypeChecker::reconcileTypes('!MyObject', Type::parseString('MyObject|bool'), null, $this->file_checker)
        );

        $this->assertEquals(
            'null',
            (string) TypeChecker::reconcileTypes('!MyObject', Type::parseString('MyObject|null'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObjectB',
            (string) TypeChecker::reconcileTypes('!MyObjectA', Type::parseString('MyObjectA|MyObjectB'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function testMyObject()
    {
        $this->assertEquals(
            'MyObject',
            (string) TypeChecker::reconcileTypes('MyObject', Type::parseString('MyObject|bool'), null, $this->file_checker)
        );

        $this->assertEquals(
            'MyObjectA',
            (string) TypeChecker::reconcileTypes('MyObjectA', Type::parseString('MyObjectA|MyObjectB'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function testArray()
    {
        $this->assertEquals(
            'array<mixed, mixed>',
            (string) TypeChecker::reconcileTypes('array', Type::parseString('array|null'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function test2DArray()
    {
        $this->assertEquals(
            'array<mixed, array<mixed, string>>',
            (string) TypeChecker::reconcileTypes('array', Type::parseString('array<array<string>>|null'), null, $this->file_checker)
        );
    }

    /**
     * @return void
     */
    public function testArrayContains()
    {
        $this->assertTrue(
            TypeChecker::isContainedBy(
                Type::parseString('array<string>'),
                Type::parseString('array'),
                $this->file_checker
            )
        );

        $this->assertTrue(
            TypeChecker::isContainedBy(
                Type::parseString('array<Exception>'),
                Type::parseString('array'),
                $this->file_checker
            )
        );
    }

    /**
     * @return void
     */
    public function testUnionContains()
    {
        $this->assertTrue(
            TypeChecker::isContainedBy(
                Type::parseString('string'),
                Type::parseString('string|false'),
                $this->file_checker
            )
        );

        $this->assertTrue(
            TypeChecker::isContainedBy(
                Type::parseString('false'),
                Type::parseString('string|false'),
                $this->file_checker
            )
        );
    }

    /**
     * @return void
     */
    public function testNumeric()
    {
        $this->assertEquals(
            'string',
            (string) TypeChecker::reconcileTypes('numeric', Type::parseString('string'), null, $this->file_checker)
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
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainNull
     * @return                   void
     */
    public function testMakeNonNullableNull()
    {
        $stmts = self::$parser->parse('<?php
        class A { }
        $a = new A();
        if ($a === null) {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testMakeInstanceOfThingInElseif()
    {
        $stmts = self::$parser->parse('<?php
        class A { }
        class B { }
        class C { }
        $a = rand(0, 10) > 5 ? new A() : new B();
        if ($a instanceof A) {
        } elseif ($a instanceof C) {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testFunctionValueIsNotType()
    {
        $stmts = self::$parser->parse('<?php
        if (json_last_error() === "5") { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testStringIsNotInt()
    {
        $stmts = self::$parser->parse('<?php
        if (5 === "5") { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainNull
     * @return                   void
     */
    public function testStringIsNotNull()
    {
        $stmts = self::$parser->parse('<?php
        if (5 === null) { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return  void
     */
    public function testIntIsMixed()
    {
        $stmts = self::$parser->parse('<?php
        function foo($a) : void {
            $b = 5;

            if ($b === $a) { }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testStringIsNotFalse()
    {
        $stmts = self::$parser->parse('<?php
        if (5 === false) { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage FailedTypeResolution
     * @return                   void
     */
    public function testFailedTypeResolution()
    {
        $stmts = self::$parser->parse('<?php
        class A { }

        /**
         * @return void
         */
        function fooFoo(A $a) {
            if ($a instanceof A) {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage FailedTypeResolution
     * @return                   void
     */
    public function testFailedTypeResolutionWithDocblock()
    {
        $stmts = self::$parser->parse('<?php
        class A { }

        /**
         * @param  A $a
         * @return void
         */
        function fooFoo(A $a) {
            if ($a instanceof A) {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTypeResolutionFromDocblock()
    {
        $stmts = self::$parser->parse('<?php
        class A { }

        /**
         * @param  A $a
         * @return void
         */
        function fooFoo($a) {
            if ($a instanceof A) {
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayTypeResolutionFromDocblock()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param string[] $strs
         * @return void
         */
        function foo(array $strs) {
            foreach ($strs as $str) {
                if (is_string($str)) {} // Issue emitted here
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTypeResolutionFromDocblockInside()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param int $length
         * @return void
         */
        function foo($length) {
            if (!is_int($length)) {
                if (is_numeric($length)) {
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage FailedTypeResolution
     * @return                   void
     */
    public function testTypeResolutionFromDocblockAndInstanceof()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testNotInstanceOf()
    {
        $stmts = self::$parser->parse('<?php
        class A { }

        class B extends A { }

        $out = null;

        if ($a instanceof B) {
            // do something
        }
        else {
            $out = $a;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $context->vars_in_scope['$a'] = Type::parseString('A');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|A', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testNotInstanceOfProperty()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $context->vars_in_scope['$a'] = Type::parseString('A');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|B', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testNotInstanceOfPropertyElseif()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $context->vars_in_scope['$a'] = Type::parseString('A');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null|B', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testTypeArguments()
    {
        $stmts = self::$parser->parse('<?php
        $a = min(0, 1);
        $b = min([0, 1]);
        $c = min("a", "b");
        $d = min(1, 2, 3, 4);
        $e = min(1, 2, 3, 4, 5);
        sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$b']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$c']);
        $this->assertEquals('string', (string)$context->vars_in_scope['$hours']);
        $this->assertEquals('string', (string)$context->vars_in_scope['$minutes']);
        $this->assertEquals('string', (string)$context->vars_in_scope['$seconds']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testTypeTransformation()
    {
        $stmts = self::$parser->parse('<?php
        $a = "5";

        if (is_numeric($a)) {
            if (is_int($a)) {
                echo $a;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testTypeRefinementWithIsNumeric()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void */
        function fooFoo(string $a) {
            if (is_numeric($a)) { }
        }

        $b = rand(0, 1) ? 5 : false;
        if (is_numeric($b)) { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testTypeRefinementWithIsNumericAndIsString()
    {
        $stmts = self::$parser->parse('<?php
        /**
         * @param mixed $a
         * @return void
         */
        function foo ($a) {
            if (is_numeric($a)) {
                if (is_string($a)) {
                }
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testUpdateMultipleIssetVars()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function foo(string $s) {}

        $a = rand(0, 1) ? ["hello"] : null;
        if (isset($a[0])) {
            foo($a[0]);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testUpdateMultipleIssetVarsWithVariableOffset()
    {
        $stmts = self::$parser->parse('<?php
        /** @return void **/
        function foo(string $s) {}

        $a = rand(0, 1) ? ["hello"] : null;
        $b = 0;
        if (isset($a[$b])) {
            foo($a[$b]);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testRemoveEmptyArray()
    {
        $stmts = self::$parser->parse('<?php
        $arr_or_string = [];

        if (rand(0, 1)) {
          $arr_or_string = "hello";
        }

        /** @return void **/
        function foo(string $s) {}

        if (!empty($arr_or_string)) {
            foo($arr_or_string);
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testInstanceOfSubtypes()
    {
        $stmts = self::$parser->parse('<?php
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

        if ($a instanceof B || $a instanceof D) { }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testEmptyArrayReconciliationThenIf()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testEmptyStringReconciliationThenIf()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testEmptyExceptionReconciliationAfterIf()
    {
        $stmts = self::$parser->parse('<?php
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
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods();
    }

    /**
     * @return void
     */
    public function testIgnoreNullCheckAndMaintainNullValue()
    {
        Config::getInstance()->setCustomErrorLevel('FailedTypeResolution', Config::REPORT_SUPPRESS);

        $stmts = self::$parser->parse('<?php
        $a = null;
        if ($a !== null) { }
        $b = $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('null', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testIgnoreNullCheckAndMaintainNullableValue()
    {
        $stmts = self::$parser->parse('<?php
        $a = rand(0, 1) ? 5 : null;
        if ($a !== null) { }
        $b = $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int|null', (string) $context->vars_in_scope['$b']);
    }
}
