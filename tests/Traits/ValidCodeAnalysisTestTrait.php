<?php
namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;

use function is_int;
use function strlen;
use function strpos;
use function substr;
use function version_compare;

use const PHP_VERSION;

trait ValidCodeAnalysisTestTrait
{
    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[],php_version?:string}>
     */
    abstract public function providerValidCodeParse(): iterable;

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string, string> $assertions
     * @param array<string|int, string> $error_levels
     *
     * @small
     */
    public function testValidCode(
        $code,
        $assertions = [],
        $error_levels = [],
        string $php_version = '7.3'
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP73-') !== false) {
            if (version_compare(PHP_VERSION, '7.3.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.3.');
            }
        } elseif (strpos($test_name, 'PHP71-') !== false) {
            if (version_compare(PHP_VERSION, '7.1.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.1.');
            }
        } elseif (strpos($test_name, 'PHP80-') !== false) {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
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

        if (\strtoupper(substr(\PHP_OS, 0, 3)) === 'WIN') {
            $code = \str_replace("\n", "\r\n", $code);
        }

        $context = new Context();

        $this->project_analyzer->setPhpVersion($php_version);

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            $exact = false;

            if ($var && strpos($var, '===') === strlen($var) - 3) {
                $var = substr($var, 0, -3);
                $exact = true;
            }

            if (isset($context->vars_in_scope[$var])) {
                if ($exact) {
                    $actual_vars[$var . '==='] = $context->vars_in_scope[$var]->getId();
                } else {
                    $actual_vars[$var] = (string)$context->vars_in_scope[$var];
                }
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }
}
