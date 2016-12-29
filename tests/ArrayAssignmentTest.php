<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Type;

class ArrayAssignmentTest extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    public static function setUpBeforeClass()
    {
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function setUp()
    {
        $config = new TestConfig();
        \Psalm\Checker\FileChecker::clearCache();
    }

    public function testGenericArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        foreach ([1, 2, 3, 4, 5] as $value) {
            $out[] = 4;
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$out']);
    }

    public function testGeneric2DArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $out = [];

        foreach ([1, 2, 3, 4, 5] as $value) {
            $out[] = [4];
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, array<int, int>>', (string) $context->vars_in_scope['$out']);
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, array<int, int>>', (string) $context->vars_in_scope['$out']);
    }

    public function testGenericArrayCreationWithObjectAddedInIf()
    {
        $stmts = self::$parser->parse('<?php
        class B {}

        $out = [];

        if (rand(0,10) === 10) {
            $out[] = new B();
        }
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, B>', (string) $context->vars_in_scope['$out']);
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$out']);
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, int|string>', (string) $context->vars_in_scope['$out']);
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, int|string>', (string) $context->vars_in_scope['$out']);
    }

    public function testImplicitIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo']);
    }

    public function testImplicit2DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, array<int, string>>', (string) $context->vars_in_scope['$foo']);
    }

    public function testImplicit3DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, array<int, array<int, string>>>', (string) $context->vars_in_scope['$foo']);
    }

    public function testImplicit4DIntArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo[][][][] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals(
            'array<int, array<int, array<int, array<int, string>>>>',
            (string) $context->vars_in_scope['$foo']
        );
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('array<int, int>', (string) $context->vars_in_scope['$bar']);
        $this->assertEquals('array<string, int>', (string) $context->vars_in_scope['$bat']);
    }

    public function testImplicitStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:string}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\']']);
    }

    public function testImplicit2DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"] = "hello";
        ');

        // check array access of baz on foo
        // with some extra data – if we need to create an array for type $foo["bar"],

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:array{baz:string}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\']']);
    }

    public function testImplicit3DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:array{baz:array{bat:string}}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\'][\'bat\']']);
    }

    public function testImplicit4DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["bar"]["baz"]["bat"]["bap"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals(
            'array{bar:array{baz:array{bat:array{bap:string}}}}',
            (string) $context->vars_in_scope['$foo']
        );

        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\'][\'bat\'][\'bap\']']);
    }

    public function test2Step2DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:array{baz:string}}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'bar\'][\'baz\']']);
    }

    public function test2StepImplicit3DStringArrayCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = ["bar" => []];
        $foo["bar"]["baz"]["bat"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:array{baz:array{bat:string}}}', (string) $context->vars_in_scope['$foo']);
    }

    public function testConflictingTypes()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:array{a:string}, baz:array<int, int>}', (string) $context->vars_in_scope['$foo']);
    }

    public function testImplicitObjectLikeCreation()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => 1,
        ];
        $foo["baz"] = "a";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{bar:int, baz:string}', (string) $context->vars_in_scope['$foo']);
    }

    public function testConflictingTypesWithAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [
            "bar" => ["a" => "b"],
            "baz" => [1]
        ];
        $foo["bar"]["bam"]["baz"] = "hello";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals(
            'array{bar:array{a:string, bam:array{baz:string}}, baz:array<int, int>}',
            (string) $context->vars_in_scope['$foo']
        );
    }

    public function testConflictingTypesWithAssignment2()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"][] = "goodbye";
        $bar = $foo["a"];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{a:string, b:array<int, string>}', (string) $context->vars_in_scope['$foo']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$foo[\'a\']']);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$foo[\'b\']']);
        $this->assertEquals('string', (string) $context->vars_in_scope['$bar']);
    }

    public function testConflictingTypesWithAssignment3()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"] = "hello";
        $foo["b"]["c"]["d"] = "goodbye";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{a:string, b:array{c:array{d:string}}}', (string) $context->vars_in_scope['$foo']);
    }

    public function testNestedObjectLikeAssignment()
    {
        $stmts = self::$parser->parse('<?php
        $foo = [];
        $foo["a"]["b"] = "hello";
        $foo["a"]["c"] = 1;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{a:array{b:string, c:int}}', (string) $context->vars_in_scope['$foo']);
    }

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

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array{a:string, b:int}', (string) $context->vars_in_scope['$foo']);
    }

    public function testIssetKeyedOffset()
    {
        $file_checker = new \Psalm\Checker\FileChecker(
            'somefile.php',
            self::$parser->parse('<?php
                if (!isset($foo["a"])) {
                    $foo["a"] = "hello";
                }
            ')
        );
        $context = new Context('somefile.php');
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->check(true, true, $context);
        $this->assertEquals('mixed', (string) $context->vars_in_scope['$foo[\'a\']']);
    }

    public function testConditionalAssignment()
    {
        $file_checker = new \Psalm\Checker\FileChecker(
            'somefile.php',
            self::$parser->parse('<?php
                if ($b) {
                    $foo["a"] = "hello";
                }
            ')
        );

        $context = new Context('somefile.php');
        $context->vars_in_scope['$b'] = \Psalm\Type::getBool();
        $context->vars_in_scope['$foo'] = \Psalm\Type::getArray();
        $file_checker->check(true, true, $context);
        $this->assertFalse(isset($context->vars_in_scope['$foo[\'a\']']));
    }

    public function testImplementsArrayAccess()
    {
        $stmts = self::$parser->parse('<?php
        class A implements \ArrayAccess { }

        $a = new A();
        $a["bar"] = "cool";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('A', (string) $context->vars_in_scope['$a']);
        $this->assertFalse(isset($context->vars_in_scope['$a[\'bar\']']));
    }

    public function testConditionalCheck()
    {
        $file_checker = new \Psalm\Checker\FileChecker(
            'somefile.php',
            self::$parser->parse('<?php
                /**
                 * @param  array{b:string} $a
                 * @return null|string
                 */
                function foo($a) {
                    if ($a["b"]) {
                        return $a["b"];
                    }
                }
            ')
        );

        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testArrayKey()
    {
        $file_checker = new \Psalm\Checker\FileChecker(
            'somefile.php',
            self::$parser->parse('<?php
            $a = ["foo", "bar"];
            $b = $a[0];

            $c = ["a" => "foo", "b"=> "bar"];
            $d = "a";
            $e = $a[$d];
            ')
        );

        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('string', (string)$context->vars_in_scope['$b']);
        $this->assertEquals('string', (string)$context->vars_in_scope['$e']);
    }

    public function testVariableKeyArrayCreate()
    {
        $stmts = self::$parser->parse('<?php
        $a = [];
        $b = "boop";
        $a[$b][] = "bam";

        $c = [];
        $c[$b][$b][] = "bam";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string, array<int, string>>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<string, array<string, array<int, string>>>', (string) $context->vars_in_scope['$c']);
    }

    public function testAssignExplicitValueToGeneric()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<string, string>> */
        $a = [];
        $a["foo"] = ["bar" => "baz"];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<string, array<string, string>>', (string) $context->vars_in_scope['$a']);
    }

    public function testAdditionWithEmpty()
    {
        $stmts = self::$parser->parse('<?php
        $a = [];
        $a += ["bar"];

        $b = [] + ["bar"];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<int, string>', (string) $context->vars_in_scope['$b']);
    }

    public function testAdditionDifferentType()
    {
        $stmts = self::$parser->parse('<?php
        $a = ["bar"];
        $a += [1];

        $b = ["bar"] + [1];
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
        $this->assertEquals('array<int, string|int>', (string) $context->vars_in_scope['$a']);
        $this->assertEquals('array<int, string|int>', (string) $context->vars_in_scope['$b']);
    }

    public function testPreset1DArrayTypeWithVarKeys()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<int, string>> */
        $a = [];

        $foo = "foo";

        $a[$foo][] = "bat";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    public function testPreset2DArrayTypeWithVarKeys()
    {
        $stmts = self::$parser->parse('<?php
        /** @var array<string, array<string, array<int, string>>> */
        $b = [];

        $foo = "foo";
        $bar = "bar";

        $b[$foo][$bar][] = "bat";
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $context = new Context('somefile.php');
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage InvalidArrayAssignment
     */
    public function testInvalidArrayAccess()
    {
        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        $a = 5;
        $a[0] = 5;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }

    /**
     * @expectedException \Psalm\Exception\CodeException
     * @expectedExceptionMessage MixedStringOffsetAssignment
     */
    public function testMixedStringOffsetAssignment()
    {
        $filter = new Config\FileFilter(false);
        $filter->addIgnoreFile('somefile.php');
        Config::getInstance()->setIssueHandler('MixedAssignment', $filter);

        $context = new Context('somefile.php');
        $stmts = self::$parser->parse('<?php
        /** @var mixed */
        $a = 5;
        "hello"[0] = $a;
        ');

        $file_checker = new \Psalm\Checker\FileChecker('somefile.php', $stmts);
        $file_checker->check(true, true, $context);
    }
}
