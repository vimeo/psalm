<?php

namespace Psalm\Tests\HelperTest;

use function explode;
use function str_replace;

use const PHP_EOL;

trait HelperAssert
{
    private static function assertOutputPrettyPrintEquals(string $expected_output, string $output): void
    {
        $tokens = ["\r\n","\r","\n"];
        $asExpectedOutput = explode(PHP_EOL, $expected_output);
        $asActualOutput = $output;

        foreach ($asExpectedOutput as $line) {
            self::assertStringContainsString(
                str_replace($tokens, '\n', $line),
                str_replace($tokens, '\n', $asActualOutput),
            );
        }
    }
}
