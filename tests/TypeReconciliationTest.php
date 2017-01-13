<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
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

        $config = new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();

        $this->file_checker = new FileChecker('somefile.php', $this->project_checker);
        $this->file_checker->context = new Context('somefile.php');

        $this->project_checker = new ProjectChecker();
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

        $negated_formula = TypeChecker::negateFormula($formula);

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['empty']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['!empty'], '$b' => ['!empty']])
        ];

        $negated_formula = TypeChecker::negateFormula($formula);

        $this->assertSame(2, count($negated_formula));
        $this->assertSame(['$a' => ['empty']], $negated_formula[0]->possibilities);
        $this->assertSame(['$b' => ['empty']], $negated_formula[1]->possibilities);

        $formula = [
            new Clause(['$a' => ['!empty']]),
            new Clause(['$b' => ['!empty']]),
        ];

        $negated_formula = TypeChecker::negateFormula($formula);

        $this->assertSame(1, count($negated_formula));
        $this->assertSame(['$a' => ['empty'], '$b' => ['empty']], $negated_formula[0]->possibilities);

        $formula = [
            new Clause(['$a' => ['int', 'string'], '$b' => ['!empty']])
        ];

        $negated_formula = TypeChecker::negateFormula($formula);

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

        $simplified_formula = TypeChecker::simplifyCNF($formula);

        $this->assertSame(2, count($simplified_formula));
        $this->assertSame(['$a' => ['!empty']], $simplified_formula[0]->possibilities);
        $this->assertSame(['$b' => ['empty']], $simplified_formula[1]->possibilities);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
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
        $context = new Context('somefile.php');
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
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
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
        $a = new A();
        if ($a instanceof A) {
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
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
        $context = new Context('somefile.php');
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
        }

        $out = null;

        if ($a->foo instanceof C) {
            // do something
        }
        else {
            $out = $a->foo;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
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
            public $foo;
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
        $context = new Context('somefile.php');
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
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('int', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$b']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$c']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeDoesNotContainType
     * @return                   void
     */
    public function testTypeTransformation()
    {
        $this->markTestIncomplete('This currently fails');
        $stmts = self::$parser->parse('<?php
        $a = "5";

        if (is_numeric($a)) {
            if (is_int($a)) {
                echo $a;
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context('somefile.php');
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
