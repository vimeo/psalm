<?php

namespace Psalm\Tests\FileManipulation;

class RedundantCastManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'RemoveRedundantCast' => [
                '<?php
                    $test = 1;
                    (int)$test;
                ',
                '<?php
                    $test = 1;
                    $test;
                ',
                '5.6',
                ['RedundantCast'],
                true,
            ],
            'RemoveRedundantCastGivenDocblockType' => [
                '<?php
                    /** @param int $test */
                    function a($test){
                        (int)$test;
                    }

                ',
                '<?php
                    /** @param int $test */
                    function a($test){
                        $test;
                    }

                ',
                '5.6',
                ['RedundantCastGivenDocblockType'],
                true,
            ],
        ];
    }
}
