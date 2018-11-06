<?php
namespace Psalm\Tests;

class AssignmentTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'nestedAssignment' => [
                '<?php
                    $a = $b = $c = 5;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'mixedAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    $b = $a;',
                'error_message' => 'MixedAssignment',
            ],
        ];
    }
}
