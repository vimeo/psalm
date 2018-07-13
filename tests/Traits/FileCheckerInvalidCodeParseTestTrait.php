<?php
namespace Psalm\Tests\Traits;

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
     * @small
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
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        if ($strict_mode) {
            Config::getInstance()->strict_binary_operands = true;
        }

        foreach ($error_levels as $error_level) {
            Config::getInstance()->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }
}
