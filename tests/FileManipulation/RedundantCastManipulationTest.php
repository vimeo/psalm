<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class RedundantCastManipulationTest extends FileManipulationTestCase
{
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
