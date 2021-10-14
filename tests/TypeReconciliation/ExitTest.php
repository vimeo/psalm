<?php
namespace Psalm\Tests\TypeReconciliation;

class ExitTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'minInt' => [
                '<?php exit(0);',
            ],
            'maxInt' => [
                '<?php exit(254);',
            ],
            'empty-string' => [
                '<?php exit("");',
            ],
            'non-empty-string' => [
                '<?php exit("message");',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'float' => [
                '<?php exit(1.0);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'negativeInt' => [
                '<?php exit(-1);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'overMax' => [
                '<?php exit(255);',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
