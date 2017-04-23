<?php
namespace Psalm\Tests;

class AssignmentTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'nested-assignment' => [
                '<?php
                    $a = $b = $c = 5;',
                'assertions' => [
                    ['int' => '$a']
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'mixed-assignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    $b = $a;',
                'error_message' => 'MixedAssignment'
            ]
        ];
    }
}
