<?php
namespace Psalm\Tests;

class ForeachTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'continue-outside-loop' => [
                '<?php
                    continue;',
                'error_message' => 'ContinueOutsideLoop'
            ],
            'invalid-iterator' => [
                '<?php
                    foreach (5 as $a) {
            
                    }',
                'error_message' => 'InvalidIterator'
            ]
        ];
    }
}
