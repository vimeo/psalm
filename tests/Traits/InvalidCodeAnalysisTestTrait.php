<?php

declare(strict_types=1);

namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;

use function array_key_exists;
use function preg_quote;
use function str_contains;
use function str_replace;
use function strtoupper;
use function substr;

use const PHP_OS;
use const PHP_VERSION_ID;

/**
 * @psalm-type psalmConfigOptions = array{
 *     strict_binary_operands?: bool,
 * }
 * @psalm-type DeprecatedDataProviderArrayNotation = array{
 *     code: string,
 *     error_message: string,
 *     ignored_issues?: list<string>,
 *     php_version?: string,
 *     config_options?: psalmConfigOptions,
 * }
 * @psalm-type NamedArgumentsDataProviderArrayNotation = array{
 *     code: string,
 *     error_message: string,
 *     error_levels?: list<string>,
 *     php_version?: string|null,
 *     config_options?: psalmConfigOptions,
 * }
 */
trait InvalidCodeAnalysisTestTrait
{
    /**
     * @return iterable<
     *     string,
     *     DeprecatedDataProviderArrayNotation|NamedArgumentsDataProviderArrayNotation
     * >
     */
    abstract public function providerInvalidCodeParse(): iterable;

    /**
     * @dataProvider providerInvalidCodeParse
     * @small
     * @param list<string> $error_levels
     * @param psalmConfigOptions $config_options
     */
    public function testInvalidCode(
        string $code,
        string $error_message,
        array  $error_levels = [],
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

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        $config = Config::getInstance();
        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            $config->setCustomErrorLevel($issue_name, $error_level);
        }
        if (array_key_exists('strict_binary_operands', $config_options)) {
            $config->strict_binary_operands = $config_options['strict_binary_operands'];
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        // $error_message = (string) preg_replace('/ src[\/\\\\]somefile\.php/', ' src/somefile.php', $error_message);

        $this->expectException(CodeException::class);

        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }
}
