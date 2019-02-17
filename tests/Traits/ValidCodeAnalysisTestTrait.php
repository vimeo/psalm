<?php
namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;

trait ValidCodeAnalysisTestTrait
{
    /**
     * @return array
     */
    abstract public function providerValidCodeParse();

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string, string> $assertions
     * @param array<string|int, string> $error_levels
     *
     * @small
     *
     * @return void
     */
    public function testValidCode(
        $code,
        $assertions = [],
        $error_levels = [],
        string $php_version = '7.3'
    ) {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP71-') !== false) {
            if (version_compare(PHP_VERSION, '7.1.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.1.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        foreach ($error_levels as $error_level_key => $error_level) {
            if (is_int($error_level_key)) {
                $issue_name = $error_level;
                $error_level = Config::REPORT_SUPPRESS;
            } else {
                $issue_name = $error_level_key;
            }

            Config::getInstance()->setCustomErrorLevel($issue_name, $error_level);
        }

        $context = new Context();

        $this->project_analyzer->setPhpVersion($php_version);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = (string)$context->vars_in_scope[$var];
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }
}
