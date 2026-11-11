<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\Tests\DeferredAnalyzerTestCase;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestConfig;

use function strpos;

abstract class FileManipulationTestCase extends DeferredAnalyzerTestCase
{
    protected ProjectAnalyzer $project_analyzer;

    /**
     * @param string[] $issues_to_fix
     * @param array<string, string> $other_files the contents of the files analysed along with somefile.php
     */
    #[DataProvider('providerValidCodeParse')]
    public function testValidCode(
        string $input,
        string $output,
        string $php_version,
        array $issues_to_fix,
        bool $safe_types,
        bool $allow_backwards_incompatible_changes = true,
        array $other_files = [],
    ): void {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $config = new TestConfig();

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
        );

        if (empty($issues_to_fix)) {
            $config->addPluginPath('examples/plugins/ClassUnqualifier.php');
            $config->initializePlugins($this->project_analyzer);
        }

        $context = new Context();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $input,
        );

        foreach ($other_files as $other_file_name => $other_file_contents) {
            $this->addFile(self::$src_dir_path . $other_file_name, $other_file_contents);
        }

        $this->project_analyzer->setPhpVersion($php_version, 'tests');

        $keyed_issues_to_fix = [];

        foreach ($issues_to_fix as $issue) {
            $keyed_issues_to_fix[$issue] = true;
        }

        $this->project_analyzer->setIssuesToFix($keyed_issues_to_fix);
        $this->project_analyzer->alterCodeAfterCompletion(
            false,
            $safe_types,
        );
        $this->project_analyzer->getCodebase()->allow_backwards_incompatible_changes = $allow_backwards_incompatible_changes;
        $this->project_analyzer->getConfig()->check_for_throws_docblock = true;

        if (strpos(static::class, 'Unused') || strpos(static::class, 'Unnecessary')) {
            $this->project_analyzer->getCodebase()->reportUnusedCode();
        }

        $this->analyzeFile($file_path, $context);

        $this->project_analyzer->consolidateAnalyzedData();

        $this->project_analyzer->getCodebase()->analyzer->updateFile($file_path, false);
        $this->assertSame($output, $this->project_analyzer->getCodebase()->getFileContents($file_path));
    }

    /**
     * @return array<string,array{input:string,output:string,php_version:string,issues_to_fix:array<string>,safe_types:bool,allow_backwards_incompatible_changes?:bool,other_files?:array<string, string>}>
     */
    abstract public static function providerValidCodeParse(): array;
}
