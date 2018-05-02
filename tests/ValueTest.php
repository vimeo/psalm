<?php
namespace Psalm\Tests;

class ValueTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'whileCountUpdate' => [
                '<?php
                    $array = [1, 2, 3];
                    while (rand(1, 10) === 1) {
                        $array[] = 4;
                        $array[] = 5;
                        $array[] = 6;
                    }

                    if (count($array) === 7) {}',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'neverEqualsType' => [
                '<?php
                    if (4 === 5) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'SKIPPED-alwaysIdenticalType' => [
                '<?php
                    if (4 === 4) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysNotIdenticalType' => [
                '<?php
                    if (4 !== 5) {
                        // do something
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'SKIPPED-neverNotIdenticalType' => [
                '<?php
                    if (4 !== 4) {
                        // do something
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'phpstanPostedArrayTest' => [
                '<?php
                    $array = [1, 2, 3];
                    if (rand(1, 10) === 1) {
                        $array[] = 4;
                        $array[] = 5;
                        $array[] = 6;
                    }

                    if (count($array) === 7) {

                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
        ];
    }
}
