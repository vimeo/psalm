<?php
namespace Psalm\Tests\FileManipulation;

use Psalm\Context;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\TestConfig;
use function strpos;

abstract class FileManipulationTestCase extends \Psalm\Tests\TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $input_code
     * @param string $output_code
     * @param string $php_version
     * @param string[] $issues_to_fix
     * @param bool $safe_types
     *
     */
    public function testValidCode(
        $input_code,
        $output_code,
        $php_version,
        array $issues_to_fix,
        $safe_types,
        bool $allow_backwards_incompatible_changes = true
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        if (empty($issues_to_fix)) {
            $config->addPluginPath('examples/plugins/ClassUnqualifier.php');
            $config->initializePlugins($this->project_analyzer);
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input_code
        );

        $this->project_analyzer->setPhpVersion($php_version);

        $keyed_issues_to_fix = [];

        foreach ($issues_to_fix as $issue) {
            $keyed_issues_to_fix[$issue] = true;
        }

        $this->project_analyzer->setIssuesToFix($keyed_issues_to_fix);
        $this->project_analyzer->alterCodeAfterCompletion(
            false,
            $safe_types
        );
        $this->project_analyzer->getCodebase()->allow_backwards_incompatible_changes = $allow_backwards_incompatible_changes;

        if (strpos(static::class, 'Unused') || strpos(static::class, 'Unnecessary')) {
            $this->project_analyzer->getCodebase()->reportUnusedCode();
        }

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->consolidateAnalyzedData();

        $this->project_analyzer->getCodebase()->analyzer->updateFile($file_path, false);
        $this->assertSame($output_code, $this->project_analyzer->getCodebase()->getFileContents($file_path));
    }

    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    abstract public function providerValidCodeParse(): array;
}
