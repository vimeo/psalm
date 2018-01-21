<?php
namespace Psalm\Tests;

use Psalm\Context;

class BadFormatTest extends TestCase
{
    /**
     * @return void
     */
    public function testMissingSemicolon()
    {
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
}
