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
            'continue-outside-loop' => [
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
            'echo-after-for' => [
                '<?php
                    for ($i = 0; $i < 5; $i++);
                    echo $i;'
            ]
        ];
    }
}
