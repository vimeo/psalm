<?php
namespace Psalm\Tests\Traits;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

trait FileCheckerInvalidCodeParseTestTrait
{
    /**
     * @return array
     */
    abstract public function providerFileCheckerInvalidCodeParse();

    /**
     * @dataProvider providerFileCheckerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     * @param bool $strict_mode
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message, $error_levels = [], $strict_mode = false)
    {
        if (strpos($this->getName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        if ($strict_mode) {
            Config::getInstance()->strict_binary_operands = true;
        }

        foreach ($error_levels as $error_level) {
            Config::getInstance()->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessage($error_message);

        $this->project_checker->registerFile(
            'somefile.php',
            $code
        );

        $context = new Context();

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $file_checker->visitAndAnalyzeMethods($context);
    }
}
