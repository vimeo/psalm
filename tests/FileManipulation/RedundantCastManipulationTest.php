<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use Override;

/**
 * @psalm-immutable
 */
final class RedundantCastManipulationTest extends FileManipulationTestCase
{
    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): array
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
            'KeepCastWhenALaterLoopPassWidensTheType' => [
                'input' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        foreach ($strings as $string) {
                            (int)$test;
                            $test = $string;
                        }
                    }
                ',
                'output' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        foreach ($strings as $string) {
                            (int)$test;
                            $test = $string;
                        }
                    }
                ',
                'php_version' => '7.4',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
            'RemoveRedundantCastInLoopOnlyWhereTheLastPassStillFlagsIt' => [
                'input' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $other = 1;
                        foreach ($strings as $string) {
                            (int)$test;
                            (int)$other;
                            $test = $string;
                        }
                    }
                ',
                'output' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $other = 1;
                        foreach ($strings as $string) {
                            (int)$test;
                            $other;
                            $test = $string;
                        }
                    }
                ',
                'php_version' => '7.4',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
        ];
    }
}
