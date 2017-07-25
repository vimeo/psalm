<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;

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

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods();
    }
}
