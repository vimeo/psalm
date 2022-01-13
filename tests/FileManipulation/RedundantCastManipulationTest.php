<?php

namespace Psalm\Tests\FileManipulation;

class RedundantCastManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{input:string,output:string,php_version:string,issues_to_fix:array<string>,safe_types:bool,allow_backwards_incompatible_changes?:bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'RemoveRedundantCast' => [
                'input' => '<?php
                    $test = 1;
                    (int)$test;
                ',
                'output' => '<?php
                    $test = 1;
                    $test;
                ',
                'php_version' => '5.6',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
            'RemoveRedundantCastGivenDocblockType' => [
                'input' => '<?php
                    /** @param int $test */
                    function a($test){
                        (int)$test;
                    }

                ',
                'output' => '<?php
                    /** @param int $test */
                    function a($test){
                        $test;
                    }

                ',
                'php_version' => '5.6',
                'issues_to_fix' => ['RedundantCastGivenDocblockType'],
                'safe_types' => true,
            ],
        ];
    }
}
