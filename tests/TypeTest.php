<?php

namespace CodeInspector\Tests;

use PhpParser;
use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;

class TypeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException CodeInspector\CodeException
     */
    public function testNullableMethodCall()
    {
        $code = '<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                $a->foo();
            }
        }';

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }

    public function testNullableMethodWithGuard()
    {
        $code = '<?php
        class A {
            public function foo() {}
        }

        class B {
            public function bar(A $a = null) {
                if ($a) {
                    $a->foo();
                }
            }
        }';

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        $file_checker = new \CodeInspector\FileChecker('somefile.php', $stmts);
        $file_checker->check();
    }
}
