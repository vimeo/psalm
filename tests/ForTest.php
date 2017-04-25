<?php
namespace Psalm\Tests;

class ForTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'continueOutsideLoop' => [
                '<?php
                    class Node {
                        /** @var Node|null */
                        public $next;
                    }
            
                    /** @return void */
                    function test(Node $head) {
                        for ($node = $head; $node; $node = $next) {
                            $next = $node->next;
                            $node->next = null;
                        }
                    }'
            ],
            'echoAfterFor' => [
                '<?php
                    for ($i = 0; $i < 5; $i++);
                    echo $i;'
            ]
        ];
    }
}
