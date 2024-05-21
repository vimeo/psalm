<?php

declare(strict_types=1);

namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;

use function array_key_exists;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function strtoupper;
use function substr;

use const PHP_OS;
use const PHP_VERSION_ID;

/**
 * @psalm-type psalmConfigOptions = array{
 *     strict_binary_operands?: bool,
 * }
 */
trait ValidCodeAnalysisTestTrait
{
    /**
     * @return iterable<
     *     string,
     *     array{
     *         code: string,
     *         assertions?: array<string, string>,
     *         ignored_issues?: list<string>,
     *         php_version?: string|null,
     *         config_options?: psalmConfigOptions,
     *     }
     * >
     */
    abstract public function providerValidCodeParse(): iterable;

    /**
     * @dataProvider providerValidCodeParse
     * @param array<string, string> $assertions
     * @param list<string> $ignored_issues
     * @param psalmConfigOptions $config_options
     * @small
     */
    public function testValidCode(
        string $code,
        array $assertions = [],
        array $ignored_issues = [],
        ?string $php_version = null,
        array $config_options = [],
    ): void {
        $test_name = $this->getTestName();
        if (str_contains($test_name, 'PHP80-')) {
            if (PHP_VERSION_ID < 8_00_00) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
            }

            if ($php_version === null) {
                $php_version = '8.0';
            }
        } elseif (str_contains($test_name, 'PHP81-')) {
            if (PHP_VERSION_ID < 8_01_00) {
                $this->markTestSkipped('Test case requires PHP 8.1.');
            }

            if ($php_version === null) {
                $php_version = '8.1';
            }
        } elseif (str_contains($test_name, 'SKIPPED-')) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        if ($php_version === null) {
            $php_version = '7.4';
        }

        // sanity check - do we have a PHP tag?
        if (!str_contains($code, '<?php')) {
            $this->fail('Test case must have a <?php tag');
        }

        $config = Config::getInstance();
        foreach ($ignored_issues as $issue_name) {
            $config->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }
        if (array_key_exists('strict_binary_operands', $config_options)) {
            $config->strict_binary_operands = $config_options['strict_binary_operands'];
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        $context = new Context();

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
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
