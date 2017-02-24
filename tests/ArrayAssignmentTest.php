<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;
use Psalm\Type;

class ArrayAssignmentTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

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
        \Psalm\Checker\FileChecker::clearCache();
        $this->project_checker = new \Psalm\Checker\ProjectChecker();
        $this->project_checker->setConfig(new TestConfig());
    }

    /**
     * @return void
     */
    public function testGenericArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        foreach ([1, 2, 3, 4, 5] as $value) {
            $out[] = 4;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGeneric2DArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        foreach ([1, 2, 3, 4, 5] as $value) {
            $out[] = [4];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, array<int, int>>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGeneric2DArrayCreationAddedInIf()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        $bits = [];

        foreach ([1, 2, 3, 4, 5] as $value) {
            if (rand(0,100) > 50) {
                $out[] = $bits;
                $bits = [];
            }

            $bits[] = 4;
        }

        if ($bits) {
            $out[] = $bits;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, array<int, int>>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGenericArrayCreationWithObjectAddedInIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {}

        $out = [];

        if (rand(0,10) === 10) {
            $out[] = new B();
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, B>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGenericArrayCreationWithElementAddedInSwitch()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        switch (rand(0,10)) {
            case 5:
                $out[] = 4;
                break;

            case 6:
                // do nothing
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGenericArrayCreationWithElementsAddedInSwitch()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        switch (rand(0,10)) {
            case 5:
                $out[] = 4;
                break;

            case 6:
                $out[] = "hello";
                break;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, int|string>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testGenericArrayCreationWithElementsAddedInSwitchWithNothing()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        switch (rand(0,10)) {
            case 5:
                $out[] = 4;
                break;

            case 6:
                $out[] = "hello";
                break;

            case 7:
                // do nothing
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, int|string>', (string) $context->vars_in_scope['$out']);
    }

    /**
     * @return void
     */
    public function testImplicitIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testImplicit2DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, array<int, string>>', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testImplicit3DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][][] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, array<int, array<int, string>>>', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testImplicit4DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][][][] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals(
            'array<int, array<int, array<int, array<int, string>>>>',
            (string) $context->vars_in_scope['$foo']
        );
    }

    /**
     * @return void
     */
    public function testImplicitIndexedIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[0] = "hello";
        $foo[1] = "hello";
        $foo[2] = "hello";

        $bar = [0, 1, 2];

        $bat = [];

        foreach ($foo as $i => $text) {
            $bat[$text] = $bar[$i];
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$bar']);
        $this->assertEquals('array<string, int>', (string) $context->vars_in_scope['$bat']);
    }

    /**
     * @return void
     */
    public function testImplicitStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:string}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\']']);
    }

    /**
     * @return void
     */
    public function testImplicit2DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"] = "hello";
        ');

        // check array access of baz on foo
        // with some extra data – if we need to create an array for type $foo["bar"],

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:array{baz:string}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\']']);
    }

    /**
     * @return void
     */
    public function testImplicit3DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:array{baz:array{bat:string}}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\'][\'bat\']']);
    }

    /**
     * @return void
     */
    public function testImplicit4DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"]["bap"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals(
            'array{bar:array{baz:array{bat:array{bap:string}}}}',
            (string) $context->vars_in_scope['$foo']
        );

        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']']);
    }

    /**
     * @return void
     */
    public function test2Step2DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:array{baz:string}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\']']);
    }

    /**
     * @return void
     */
    public function test2StepImplicit3DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:array{baz:array{bat:string}}}', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testConflictingTypes()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:array{a:string}, baz:array<int, int>}', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testImplicitObjectLikeCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => 1,
        ];
        $foo["baz"] = "a";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{bar:int, baz:string}', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testConflictingTypesWithAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        $foo["bar"]["bam"]["baz"] = "hello";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals(
            'array{bar:array{a:string, bam:array{baz:string}}, baz:array<int, int>}',
            (string) $context->vars_in_scope['$foo']
        );
    }

    /**
     * @return void
     */
    public function testConflictingTypesWithAssignment2()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"][] = "goodbye";
        $bar = $foo["a"];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{a:string, b:array<int, string>}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'a\']']);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo[\'b\']']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$bar']);
    }

    /**
     * @return void
     */
    public function testConflictingTypesWithAssignment3()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"]["c"]["d"] = "goodbye";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{a:string, b:array{c:array{d:string}}}', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testNestedObjectLikeAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"]["b"] = "hello";
        $foo["a"]["c"] = 1;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{a:array{b:string, c:int}}', (string) $context->vars_in_scope['$foo']);
    }

    /**
     * @return void
     */
    public function testConditionalObjectLikeAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $foo = ["a" => "hello"];
        if (rand(0, 10) === 5) {
            $foo["b"] = 1;
        }
        else {
            $foo["b"] = 2;
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array{a:string, b:int}', (string) $context->vars_in_scope['$foo']);
    }



    /**
     * @return void
     */
    public function testConditionalAssignment()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
                if ($b) {
                    $foo["a"] = "hello";
                }
            ')
        );

        $context = new Context();
        $context->vars_in_scope['$b'] = \Psalm\Type::getBool();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    /**
     * @return void
     */
    public function testImplementsArrayAccess()
    {
        $stmts = self::$parser->parse('<?php
        class A implements \ArrayAccess {
            public function offsetSet($offset, $value) : void {
            }

            public function offsetExists($offset) : bool {
                return true;
            }

            public function offsetUnset($offset) : void {
            }

            public function offsetGet($offset) : int {
                return 1;
            }
        }

        $a = new A();
        $a["bar"] = "cool";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
        $this->assertFalse(isset($context->vars_in_scope['$a[\'bar\']']));
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAssignment
     * @return                   void
     */
    public function testObjectAssignment()
    {
        $context = new Context();
        $stmts = self::$parser->parse('<?php
        class A {}
        (new A)["b"] = 1;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testConditionalCheck()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
                /**
                 * @param  array{b:string} $a
                 * @return null|string
                 */
                function fooFoo($a) {
                    if ($a["b"]) {
                        return $a["b"];
                    }
                }
            ')
        );

        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testArrayKey()
    {
        $file_checker = new FileChecker(
            'somefile.php',
            $this->project_checker,
            self::$parser->parse('<?php
            $a = ["foo", "bar"];
            $b = $a[0];

            $c = ["a" => "foo", "b"=> "bar"];
            $d = "a";
            $e = $a[$d];
            ')
        );

        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string', (string)$context->vars_in_scope['$b']);
        $this->assertEquals('string', (string)$context->vars_in_scope['$e']);
    }

    /**
     * @return void
     */
    public function testVariableKeyArrayCreate()
    {
        $stmts = self::$parser->parse('<?php
        $a = [];
        $b = "boop";
        $a[$b][] = "bam";

        $c = [];
        $c[$b][$b][] = "bam";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<string, array<int, string>>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<string, array<string, array<int, string>>>', (string) $context->vars_in_scope['$c']);
    }

    /**
     * @return void
     */
    public function testAssignExplicitValueToGeneric()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<string, string>> */
        $a = [];
        $a["foo"] = ["bar" => "baz"];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<string, array<string, string>>', (string) $context->vars_in_scope['$a']);
    }

    /**
     * @return void
     */
    public function testAdditionWithEmpty()
    {
        $stmts = self::$parser->parse('<?php
        $a = [];
        $a += ["bar"];

        $b = [] + ["bar"];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testAdditionDifferentType()
    {
        $stmts = self::$parser->parse('<?php
        $a = ["bar"];
        $a += [1];

        $b = ["bar"] + [1];
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<int, string|int>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<int, string|int>', (string) $context->vars_in_scope['$b']);
    }

    /**
     * @return void
     */
    public function testPreset1DArrayTypeWithVarKeys()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<int, string>> */
        $a = [];

        $foo = "foo";

        $a[$foo][] = "bat";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testPreset2DArrayTypeWithVarKeys()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<string, array<int, string>>> */
        $b = [];

        $foo = "foo";
        $bar = "bar";

        $b[$foo][$bar][] = "bat";
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAssignment
     * @return                   void
     */
    public function testInvalidArrayAccess()
    {
        $context = new Context();
        $stmts = self::$parser->parse('<?php
        $a = 5;
        $a[0] = 5;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedStringOffsetAssignment
     * @return                   void
     */
    public function testMixedStringOffsetAssignment()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = 5;
        "hello"[0] = $a;
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage TypeCoercion
     * @return                   void
     */
    public function testMixedArrayArgument()
    {
        Config::getInstance()->setCustomErrorLevel('MixedAssignment', Config::REPORT_SUPPRESS);

        $context = new Context();
        $stmts = self::$parser->parse('<?php
        /** @param array<mixed, int|string> $foo */
        function fooFoo(array $foo) : void { }

        function barBar(array $bar) : void {
            fooFoo($bar);
        }

        barBar([1, "2"]);
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @return void
     */
    public function testObjectLikeWithIntegerKeys()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array{0: string, 1: int} **/
        $a = ["hello", 5];
        $b = $a[0]; // string
        $c = $a[1]; // int
        list($d, $e) = $a; // $d is string, $e is int
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('string', (string) $context->vars_in_scope['$b']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$c']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$d']);
        $this->assertEquals('int', (string) $context->vars_in_scope['$e']);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     * @return                   void
     */
    public function testArrayPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string[] */
            public $strs = ["a", "b", "c"];

            /** @return void */
            public function bar() {
                $this->strs = [new stdClass()]; // no issue emitted
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidPropertyAssignment
     * @return                   void
     */
    public function testIncrementalArrayPropertyAssignment()
    {
        $stmts = self::$parser->parse('<?php
        class A {
            /** @var string[] */
            public $strs = ["a", "b", "c"];

            /** @return void */
            public function bar() {
                $this->strs[] = new stdClass(); // no issue emitted
            }
        }
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
