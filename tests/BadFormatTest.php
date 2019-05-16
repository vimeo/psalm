<?php
namespace Psalm\Tests;

use Psalm\Context;

class BadFormatTest extends TestCase
{
    /**
     *
     * @return void
     */
    public function testMissingSemicolon()
    {
        $this->expectExceptionMessage('ParseError - somefile.php:9');
        $this->expectException(\Psalm\Exception\CodeException::class);
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
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     *
     * @return void
     */
    public function testClassMethodWithNoStmts()
    {
        $this->expectExceptionMessage('ParseError - somefile.php:3');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    public function foo() : void;
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     *
     * @return void
     */
    public function testTypingReturnType()
    {
        $this->expectExceptionMessage('ParseError - somefile.php:5');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                class A {
                    /** @return void */
                    protected function _getCollaborators(User $user, User $cur_user = null) :
                    {
                        return $a;
                    }
                }'
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     *
     * @return void
     */
    public function testOverriddenUse()
    {
        $this->expectExceptionMessage('ParseError - somefile.php:6');
        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->addFile(
            'somefile.php',
            '<?php
                namespace Demo;

                use A\B;

                interface B {}'
        );

        $this->analyzeFile('somefile.php', new Context());
    }
}
