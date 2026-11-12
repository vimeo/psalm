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
            'KeepCastWhenALaterDoWhilePassWidensTheType' => [
                'input' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $i = 0;
                        do {
                            (int)$test;
                            $test = $strings[$i];
                            $i++;
                        } while ($i < 3);
                    }
                ',
                'output' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $i = 0;
                        do {
                            (int)$test;
                            $test = $strings[$i];
                            $i++;
                        } while ($i < 3);
                    }
                ',
                'php_version' => '7.4',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
            'RemoveRedundantCastInDoWhileWhereEveryPassFlagsIt' => [
                'input' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $other = 1;
                        $i = 0;
                        do {
                            (int)$test;
                            (int)$other;
                            $test = $strings[$i];
                            $i++;
                        } while ($i < 3);
                    }
                ',
                'output' => '<?php
                    /** @param list<string> $strings */
                    function a(array $strings): void {
                        $test = 1;
                        $other = 1;
                        $i = 0;
                        do {
                            (int)$test;
                            $other;
                            $test = $strings[$i];
                            $i++;
                        } while ($i < 3);
                    }
                ',
                'php_version' => '7.4',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
            'RemoveRedundantCastInAttributeArgument' => [
                'input' => '<?php
                    #[Attribute]
                    final class A { public function __construct(public int $n) {} }

                    #[A((int) 5)]
                    final class B {}
                ',
                'output' => '<?php
                    #[Attribute]
                    final class A { public function __construct(public int $n) {} }

                    #[A(5)]
                    final class B {}
                ',
                'php_version' => '8.1',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
            'RemoveRedundantCastAfterALoopThatStopsTheAnalysis' => [
                'input' => '<?php
                    function a(?object $o): void {
                        for ($i = 0; $i < 3; $i = $o?->foo) {
                        }
                    }
                    function b(int $j): void {
                        (int)$j;
                    }
                ',
                'output' => '<?php
                    function a(?object $o): void {
                        for ($i = 0; $i < 3; $i = $o?->foo) {
                        }
                    }
                    function b(int $j): void {
                        $j;
                    }
                ',
                'php_version' => '7.4',
                'issues_to_fix' => ['RedundantCast'],
                'safe_types' => true,
            ],
        ];
    }
}
