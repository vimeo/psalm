<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Context;
use Psalm\Exception\CodeException;

class BadFormatTest extends TestCase
{
    public function testMissingSemicolon(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:9');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @var int|null */
                    protected $hello;

                    /** @return void */
                    function foo() {
                        $this->hello = 5
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testClassMethodWithNoStmts(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:3');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function foo() : void;
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testInterfaceWithProperties(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:3');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                interface foo {
                    public static $foo = ["bar"];
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testTypingReturnType(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:5');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @return void */
                    protected function _getCollaborators(User $user, User $cur_user = null) :
                    {
                        return $a;
                    }
                }',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testOverriddenUse(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:6');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Demo;

                use A\B;

                interface B {}',
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testBadArray(): void
    {
        $this->expectExceptionMessage('ParseError - somefile.php:2');
        $this->expectException(CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                [1,,2];',
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
