<?php

namespace Psalm\Tests\Traits;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;

use function preg_quote;
use function str_replace;
use function strpos;
use function strtoupper;
use function substr;
use function version_compare;

use const PHP_OS;
use const PHP_VERSION;

trait InvalidCodeAnalysisTestTrait
{
    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    abstract public function providerInvalidCodeParse(): iterable;

    /**
     * @dataProvider providerInvalidCodeParse
     * @small
     *
     * @param list<string> $error_levels
     */
    public function testInvalidCode(
        string $code,
        string $error_message,
        array  $error_levels = [],
        string $php_version = '7.3'
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP80-') !== false) {
            if (version_compare(PHP_VERSION, '8.0.0', '<')) {
                $this->markTestSkipped('Test case requires PHP 8.0.');
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            Config::getInstance()->setCustomErrorLevel($issue_name, $error_level);
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $file_path = self::$src_dir_path . 'somefile.php';

        // $error_message = preg_replace('/ src[\/\\\\]somefile\.php/', ' src/somefile.php', $error_message);

        $this->expectException(CodeException::class);

        $this->expectExceptionMessageMatches('/\b' . preg_quote($error_message, '/') . '\b/');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $this->addFile($file_path, $code);
        $this->analyzeFile($file_path, new Context());
    }
}
