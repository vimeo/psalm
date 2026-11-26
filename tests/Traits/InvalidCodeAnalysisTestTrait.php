<?php

declare(strict_types=1);

namespace Psalm\Tests\Traits;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\CodeException;
use Psalm\Tests\FileManipulation\PureAnnotationAdditionTest;
use Psalm\Tests\ImmutableAnnotationTest;
use Psalm\Tests\PureAnnotationTest;
use Psalm\Tests\PureCallableTest;

use function preg_quote;
use function str_replace;
use function strpos;
use function strtoupper;
use function substr;

use const PHP_OS;

/** @psalm-mutable */
trait InvalidCodeAnalysisTestTrait
{
    use InvalidCodeAnalysisCasesTrait;

    /**
     * @param list<string> $error_levels
     */
    #[DataProvider('providerInvalidCodeParse')]
    public function testInvalidCode(
        string $code,
        string $error_message,
        array  $error_levels = [],
        ?string $php_version = null,
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'PHP80-') !== false) {
            if ($php_version === null) {
                $php_version = '8.0';
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        if ($php_version === null) {
            $php_version = '7.4';
        }

        // sanity check - do we have a PHP tag?
        if (strpos($code, '<?php') === false) {
            $this->fail('Test case must have a <?php tag');
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $code = str_replace("\n", "\r\n", $code);
        }

        foreach ($error_levels as $error_level) {
            $issue_name = $error_level;
            $error_level = Config::REPORT_SUPPRESS;

            Config::getInstance()->setCustomErrorLevel($issue_name, $error_level);
        }
        if ($this instanceof PureAnnotationTest
            || $this instanceof PureCallableTest
            || $this instanceof PureAnnotationAdditionTest
            || $this instanceof ImmutableAnnotationTest
        ) {
            Config::getInstance()->suggest_purity_annotations = true;
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
