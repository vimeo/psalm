<?php

namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;

use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;
use function version_compare;

use const PHP_OS;
use const PHP_VERSION;

/**
 * @psalm-require-extends \Psalm\Tests\TestCase
 */
trait ValidCodeAnalysisTestTrait
{
    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string,required_extensions?:list<value-of<Config::SUPPORTED_EXTENSIONS>>}>
     */
    abstract public function providerValidCodeParse(): iterable;

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string, string> $assertions
     * @param list<string> $error_levels
     * @param list<value-of<Config::SUPPORTED_EXTENSIONS>> $required_extensions
     *
     * @small
     */
    public function testValidCode(
        $code,
        $assertions = [],
        $error_levels = [],
        string $php_version = '7.3',
        array $required_extensions = []
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP80-') !== false) {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        foreach ($required_extensions as $ext_name) {
            $this->testConfig->enableExtension($ext_name);
        }

        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            Config::getInstance()->setCustomErrorLevel($issue_name, $error_level);
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        $context = new Context();

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

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
                $value = $context->vars_in_scope[$var]->getId($exact);
                if ($exact) {
                    $actual_vars[$var . '==='] = $value;
                } else {
                    $actual_vars[$var] = $value;
                }
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }
}
