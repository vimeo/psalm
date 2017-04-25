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
            'continueOutsideLoop' => [
                '<?php
                    continue;',
                'error_message' => 'ContinueOutsideLoop'
            ],
            'invalidIterator' => [
                '<?php
                    foreach (5 as $a) {
            
                    }',
                'error_message' => 'InvalidIterator'
            ]
        ];
    }
}
