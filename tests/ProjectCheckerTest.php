<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\LanguageServer\ClientConfiguration;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\LanguageServer\PathMapper;
use Psalm\Internal\LanguageServer\Progress;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Config\Plugin\ReportabilityExpandingHook;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\LanguageServer\MockProtocolStream;
use Psalm\Tests\Progress\EchoProgress;
use Psalm\Tests\TestConfig;
use Psalm\Type\Union;

use function array_map;
use function get_class;
use function getcwd;
use function microtime;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function realpath;
use function str_replace;

use const DIRECTORY_SEPARATOR;

final class ProjectCheckerTest extends DeferredAnalyzerTestCase
{
    private const AUTOLOAD_FILE_CLASS = 'Psalm\Tests\fixtures\ScanHookAndAutoloadFiles\ReachableFromAutoloadFile';

    protected ProjectAnalyzer $project_analyzer;

    private static ?IncludeCollector $autoload_file_collector = null;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to stop Psalm seeing the phpunit arguments
        global $argv;
        $argv = [];

        new TestConfig();
    }

    private function getProjectAnalyzerWithConfig(Config $config, bool $init_files = true): ProjectAnalyzer
    {
        $config->setIncludeCollector(new IncludeCollector());
        $ret = new ProjectAnalyzer(
            $config,
            $this->getInMemoryProviders($config),
            new ReportOptions(),
        );
        if ($init_files) {
            $ret->initProjectFiles();
            $ret->initExtraFiles();
        }
        return $ret;
    }

    /**
     * The include collector of a run whose Composer autoloader required a file that is the only reference
     * to a class. A file required again is no new include, so every test shares the first collector.
     */
    private static function getIncludeCollectorWithAutoloadFile(): IncludeCollector
    {
        if (self::$autoload_file_collector === null) {
            self::$autoload_file_collector = new IncludeCollector();
            self::$autoload_file_collector->runAndCollect(static function (): void {
                require __DIR__ . '/fixtures/ScanHookAndAutoloadFiles/autoload_file.php';
            });
        }

        return self::$autoload_file_collector;
    }

    private static function getConfigWithScanHookPlugin(): Config
    {
        $config = Config::loadFromXML(
            (string) getcwd(),
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="tests/fixtures/DummyProject" />
                </projectFiles>
                <plugins>
                    <pluginClass class="Psalm\Tests\fixtures\ScanHookAndAutoloadFiles\ScanHookPlugin" />
                </plugins>
            </psalm>',
        );
        $config->setIncludeCollector(self::getIncludeCollectorWithAutoloadFile());

        return $config;
    }

    private function getProjectAnalyzerWithScanHookPlugin(?Providers $providers = null): ProjectAnalyzer
    {
        $config = self::getConfigWithScanHookPlugin();
        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers ?? $this->getInMemoryProviders($config),
            new ReportOptions(),
        );
        $project_analyzer->initProjectFiles();
        $project_analyzer->initExtraFiles();

        return $project_analyzer;
    }

    private function getInMemoryProviders(Config $config): Providers
    {
        return new Providers(
            $this->file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );
    }

    /**
     * @psalm-capabilities read-props
     */
    private static function isSeenByScanHook(ProjectAnalyzer $project_analyzer, string $fq_class_name): bool
    {
        return isset(
            $project_analyzer->getCodebase()->classlike_storage_provider->get($fq_class_name)
                ->custom_metadata['seen_by_scan_hook'],
        );
    }

    #[DataProvider('autoloadFileEntryPointProvider')]
    public function testScanHooksSeeClassesReachableOnlyFromAutoloadFiles(string $entry_point): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithScanHookPlugin();

        ob_start();
        match ($entry_point) {
            'check' => $this->project_analyzer->check('tests/fixtures/DummyProject'),
            'checkPaths' => $this->project_analyzer->checkPaths([
                (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject'),
            ]),
            'serverMode' => $this->project_analyzer->serverMode(new LanguageServer(
                new MockProtocolStream(),
                new MockProtocolStream(),
                $this->project_analyzer,
                $this->project_analyzer->getCodebase(),
                new ClientConfiguration(),
                new Progress(),
                new PathMapper((string) getcwd(), (string) getcwd()),
            )),
        };
        ob_end_clean();

        $this->assertTrue(self::isSeenByScanHook($this->project_analyzer, self::AUTOLOAD_FILE_CLASS));
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function autoloadFileEntryPointProvider(): iterable
    {
        yield 'check' => ['check'];
        yield 'checkPaths' => ['checkPaths'];
        yield 'serverMode' => ['serverMode'];
    }

    public function testFullRunDoesNotDependOnWhichRunWroteTheCache(): void
    {
        $shared_providers = $this->getInMemoryProviders(self::getConfigWithScanHookPlugin());

        ob_start();
        $this->getProjectAnalyzerWithScanHookPlugin($shared_providers)->checkPaths([
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject'),
        ]);

        // each run starts like a new process, with only the cache kept
        RuntimeCaches::clearAll();
        $project_analyzer = $this->getProjectAnalyzerWithScanHookPlugin($shared_providers);
        $project_analyzer->check('tests/fixtures/DummyProject');
        $with_cache_of_paths_run = self::isSeenByScanHook($project_analyzer, self::AUTOLOAD_FILE_CLASS);

        RuntimeCaches::clearAll();
        $project_analyzer = $this->getProjectAnalyzerWithScanHookPlugin();
        $project_analyzer->check('tests/fixtures/DummyProject');
        $with_new_cache = self::isSeenByScanHook($project_analyzer, self::AUTOLOAD_FILE_CLASS);
        ob_end_clean();

        $this->assertSame(
            ['with the cache of a run with paths' => true, 'with a new cache' => true],
            ['with the cache of a run with paths' => $with_cache_of_paths_run, 'with a new cache' => $with_new_cache],
        );
    }

    public function testDiffRunInitializesPlugins(): void
    {
        $providers = $this->getInMemoryProviders(self::getConfigWithScanHookPlugin());

        $this->project_analyzer = $this->getProjectAnalyzerWithScanHookPlugin($providers);
        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();

        $this->file_provider->registerFile(
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/Bat.php'),
            '<?php

namespace Vimeo\Test\DummyProject;

final class Bat
{
}
',
        );

        RuntimeCaches::clearAll();
        $this->project_analyzer = $this->getProjectAnalyzerWithScanHookPlugin($providers);
        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_end_clean();

        $this->assertTrue($this->project_analyzer->getCodebase()->diff_run);
        $this->assertTrue(self::isSeenByScanHook($this->project_analyzer, 'Vimeo\Test\DummyProject\Bat'));
    }

    public function testCheckFileReportsIssueWithoutInitialisedProjectFiles(): void
    {
        // Regression test: checkFile() is a single-file entry point that may run
        // without initProjectFiles() having populated $project_files. An issue
        // raised during the run reads $project_files (via canReportIssues), which
        // used to crash with "must not be accessed before initialization".
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
            false,
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $file_path = (string) getcwd()
            . DIRECTORY_SEPARATOR . 'tests'
            . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'DummyProject'
            . DIRECTORY_SEPARATOR . 'CheckFileRegression.php';

        $this->file_provider->registerFile(
            $file_path,
            '<?php

namespace Vimeo\Test\DummyProject;

function checkFileRegression(): int
{
    return "not an int";
}
',
        );

        // Must not throw; the issue in the file must still be reported.
        $this->project_analyzer->checkFile($file_path);

        $this->assertGreaterThan(0, IssueBuffer::getErrorCount());
    }

    public function testCheckDirReportsIssueWithoutInitialisedProjectFiles(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
            false,
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $dir_path = (string) getcwd()
            . DIRECTORY_SEPARATOR . 'tests'
            . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'DummyProject';

        $this->file_provider->registerFile(
            $dir_path . DIRECTORY_SEPARATOR . 'CheckDirRegression.php',
            '<?php

namespace Vimeo\Test\DummyProject;

function checkDirRegression(): int
{
    return "not an int";
}
',
        );

        // Must not throw; the issue in the file must still be reported.
        $this->project_analyzer->checkDir($dir_path);

        $this->assertGreaterThan(0, IssueBuffer::getErrorCount());
    }

    public function testCanReportIssuesWithoutCheckMethod(): void
    {
        // A tool that embeds Psalm and drives the analysis itself never calls a check method,
        // so nothing narrows the reportable files to the ones being checked.
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
            false,
        );

        $project_dir = (string) getcwd()
            . DIRECTORY_SEPARATOR . 'tests'
            . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'DummyProject';

        $this->assertTrue($this->project_analyzer->canReportIssues($project_dir . DIRECTORY_SEPARATOR . 'Bar.php'));
        $this->assertFalse($this->project_analyzer->canReportIssues((string) getcwd() . DIRECTORY_SEPARATOR . 'psalm'));
    }

    public function testReportableFilesWithoutCheckMethod(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                        <ignoreFiles>
                            <directory name="tests/fixtures/stubs" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>',
            ),
            false,
        );
        $config = $this->project_analyzer->getConfig();
        $codebase = $this->project_analyzer->getCodebase();

        $tests_dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $order = $tests_dir . 'order.blade.php';
        $invoice = $tests_dir . 'invoice.blade.php';
        $ignored = $tests_dir . 'fixtures' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'cart.blade.php';

        $codebase->addReportableFiles([$order]);
        $codebase->addReportableFiles([$invoice, $ignored]);

        $this->assertTrue($this->project_analyzer->canReportIssues($order));
        $this->assertTrue($this->project_analyzer->canReportIssues($invoice));
        $this->assertFalse($this->project_analyzer->canReportIssues($tests_dir . 'receipt.blade.php'));
        $this->assertFalse($config->reportIssueInFile('InvalidReturnType', $ignored));
    }

    public function testIssuesInAnAnalyzedFileOutsideTheProjectAreReportedOnceItIsReportable(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        // a plugin compiles templates to PHP outside the project and has them analyzed
        $tests_dir = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;
        $compiled_view = $tests_dir . 'CompiledView.php';
        $other_compiled_view = $tests_dir . 'OtherCompiledView.php';
        $this->file_provider->registerFile($compiled_view, '<?php
            function orderTotal(): int {
                return "total";
            }');
        $this->file_provider->registerFile($other_compiled_view, '<?php
            function invoiceTotal(): int {
                return "total";
            }');

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$compiled_view => $compiled_view, $other_compiled_view => $other_compiled_view]);
        $codebase->addReportableFiles([$compiled_view]);

        $this->project_analyzer->check('tests/fixtures/DummyProject');

        $this->assertSame(
            ['InvalidReturnStatement', 'InvalidReturnType'],
            array_map(
                static fn(IssueData $issue): string => $issue->type,
                IssueBuffer::getIssuesDataForFile($compiled_view),
            ),
        );
        $this->assertSame([], IssueBuffer::getIssuesDataForFile($other_compiled_view));
    }

    public function testReportableFileIsNotAnalyzed(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        // a template: issues are reported in it, but it is not PHP to analyze
        $template = (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'order.blade.php';
        $this->file_provider->registerFile($template, '<?php
            function orderTotal(): int {
                return "total";
            }');

        $this->project_analyzer->getCodebase()->addReportableFiles([$template]);

        $this->project_analyzer->check('tests/fixtures/DummyProject');

        $this->assertTrue($this->project_analyzer->canReportIssues($template));
        $this->assertFalse($this->project_analyzer->getCodebase()->file_storage_provider->has($template));
        $this->assertSame([], IssueBuffer::getIssuesDataForFile($template));
    }

    public function testCheck(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Target PHP version: 8.1 (set by tests)', $output);
        $this->assertStringContainsString('Scanning files...', $output);
        $this->assertStringContainsString('Analyzing files...', $output);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $codebase = $this->project_analyzer->getCodebase();

        $this->assertSame([0, 5], $codebase->analyzer->getTotalTypeCoverage($codebase));

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $codebase->analyzer->getTypeInferenceSummary(
                $codebase,
            ),
        );
    }

    #[DataProvider('entryPointProvider')]
    public function testAfterCodebasePopulatedIsInvoked(string $entry_point): void
    {
        $hook = new class implements AfterCodebasePopulatedInterface {
            public static bool $called = false;

            /**
             * @return void
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
             * @psalm-external-mutation-free
             */
            #[Override]
            public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event)
            {
                self::$called = true;
            }
        };

        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $hook_class = get_class($hook);

        $this->project_analyzer->getCodebase()->config->eventDispatcher->after_codebase_populated[] = $hook_class;
        $hook::$called = false;

        $file_path = (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/Bar.php');

        ob_start();
        match ($entry_point) {
            'check' => $this->project_analyzer->check('tests/fixtures/DummyProject'),
            'checkDir' => $this->project_analyzer->checkDir('tests/fixtures/DummyProject'),
            'checkFile' => $this->project_analyzer->checkFile($file_path),
            'checkPaths' => $this->project_analyzer->checkPaths([$file_path]),
        };
        ob_end_clean();

        $this->assertTrue($hook::$called);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function entryPointProvider(): iterable
    {
        yield 'check' => ['check'];
        yield 'checkDir' => ['checkDir'];
        yield 'checkFile' => ['checkFile'];
        yield 'checkPaths' => ['checkPaths'];
    }

    public function testCheckAfterNoChange(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $this->assertNotNull($this->project_analyzer->stdout_report_options);

        $this->project_analyzer->stdout_report_options->format = Report::TYPE_JSON;

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_start();
        IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );

        $this->project_analyzer->getCodebase()->reloadFiles($this->project_analyzer, []);

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $this->assertSame(
            "No files analyzed\nPsalm was able to infer types for 100% of the codebase",
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );
    }

    public function testCheckAfterFileChange(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $this->assertNotNull($this->project_analyzer->stdout_report_options);

        $this->project_analyzer->stdout_report_options->format = Report::TYPE_JSON;

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_start();
        IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );

        $bat_file_path = (string) getcwd()
            . DIRECTORY_SEPARATOR . 'tests'
            . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . 'DummyProject'
            . DIRECTORY_SEPARATOR . 'Bat.php';

        $bat_replacement_contents = '<?php

namespace Vimeo\Test\DummyProject;

final class Bat
{
    public function __construct()
    {
        $a = new Bar();
    }
}
';

        $this->file_provider->registerFile($bat_file_path, $bat_replacement_contents);

        $this->project_analyzer->getCodebase()->reloadFiles($this->project_analyzer, []);

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );
    }

    public function testCheckDir(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );
        $this->project_analyzer->initExtraFiles();
        $this->project_analyzer->initProjectFiles();

        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->checkDir('tests/fixtures/DummyProject');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Target PHP version: 8.1 (set by tests)', $output);
        $this->assertStringContainsString('Scanning files...', $output);
        $this->assertStringContainsString('Analyzing files...', $output);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );
    }

    public function testCheckPaths(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        // checkPaths expects absolute paths,
        // otherwise it's unable to match them against configured folders
        $this->project_analyzer->checkPaths([
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/Bar.php'),
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/SomeTrait.php'),
        ]);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Target PHP version: 8.1 (set by tests)', $output);
        $this->assertStringContainsString('Scanning files...', $output);
        $this->assertStringContainsString('Analyzing files...', $output);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );
    }

    public function testCheckFile(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>',
            ),
        );

        $this->project_analyzer->setPhpVersion('8.1', 'tests');

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        // checkPaths expects absolute paths,
        // otherwise it's unable to match them against configured folders
        $this->project_analyzer->checkPaths([
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/Bar.php'),
            (string) realpath((string) getcwd() . '/tests/fixtures/DummyProject/SomeTrait.php'),
        ]);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Target PHP version: 8.1 (set by tests)', $output);
        $this->assertStringContainsString('Scanning files...', $output);
        $this->assertStringContainsString('Analyzing files...', $output);

        $this->assertSame(0, IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase(),
            ),
        );
    }

    public function testCheckRecordsParamTypesOnlyOfMethodsTheReaderCanUse(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerForParamTypeInference();
        $fixable_before = IssueBuffer::getFixableIssues()['MissingParamType'] ?? 0;

        $this->project_analyzer->check('tests/fixtures/DummyProject');

        $possible_param_types = $this->project_analyzer->getCodebase()->analyzer->possible_method_param_types;
        $this->assertSame(
            "'hello'",
            $possible_param_types['vimeo\test\dummyproject\inferencetarget::project'][0]->getId(),
        );
        $this->assertArrayNotHasKey('vimeo\test\vendor\vendortarget::vendor', $possible_param_types);
        $this->assertArrayNotHasKey('vimeo\test\dummyproject\inferencetrait::fromtrait', $possible_param_types);
        $this->assertSame($fixable_before + 1, IssueBuffer::getFixableIssues()['MissingParamType'] ?? 0);
    }

    /**
     * A plugin hook can make a file of the project directories reportable (an include that the project files don't
     * list: LateTarget.inc) before or while the analysis runs, in the main process or in a worker. Which arguments
     * are recorded must not depend on it, so that the outcome is the one of a run that knew the file from the start:
     * mixedThenInt is never inferred, however its calls are spread over the expansion.
     *
     * @param 'none'|'addFilesToAnalyze'|'addFilesToShowResults'|'addReportableFiles' $api
     */
    #[DataProvider('providerReportabilityExpansions')]
    public function testRecordedParamTypesDoNotDependOnWhenAFileBecomesReportable(
        string $api,
        bool $during_analysis,
    ): void {
        [$possible_param_types, $fixed] = $this->checkLateTarget($api, $during_analysis);

        $this->assertTrue($possible_param_types['vimeo\test\dummyproject\latetarget::mixedthenint'][0]->hasMixed());
        $this->assertSame('1|2', $possible_param_types['vimeo\test\dummyproject\latetarget::intbeforeandafter'][0]->getId());
        $this->assertSame('3', $possible_param_types['vimeo\test\dummyproject\latetarget::onlyafter'][0]->getId());
        // without an expansion the include stays unreportable: its types are recorded and the reader skips them
        $this->assertSame($api === 'none' ? 0 : 2, $fixed);
    }

    /**
     * @return iterable<string, array{'none'|'addFilesToAnalyze'|'addFilesToShowResults'|'addReportableFiles', bool}>
     * @psalm-pure
     */
    public static function providerReportabilityExpansions(): iterable
    {
        yield 'no expansion' => ['none', true];

        foreach (['addFilesToAnalyze', 'addFilesToShowResults', 'addReportableFiles'] as $api) {
            yield $api . ' during the analysis' => [$api, true];
            yield $api . ' before the analysis' => [$api, false];
        }
    }

    /**
     * Checks the project, in which LateCallerA.php calls the methods of LateTarget.inc before and LateCallerB.php after
     * the hook makes LateTarget.inc reportable. The include is in a project directory but not among the project files.
     *
     * @param 'none'|'addFilesToAnalyze'|'addFilesToShowResults'|'addReportableFiles' $api
     * @param bool $during_analysis false: the hook runs once the codebase is populated, before any call is analyzed
     * @return array{array<string, array<int, Union>>, int} the usage-inferred param types, the issues to fix
     */
    private function checkLateTarget(string $api, bool $during_analysis): array
    {
        $project_dir = self::fixturePath('DummyProject');
        $late_file = $project_dir . DIRECTORY_SEPARATOR . 'LateTarget.inc';
        $first_caller = $project_dir . DIRECTORY_SEPARATOR . 'LateCallerA.php';
        $this->file_provider->registerFile(
            $first_caller,
            '<?php

namespace Vimeo\Test\DummyProject;

function callBeforeTheExpansion(LateTarget $target, mixed $m): void
{
    $target->mixedThenInt($m);
    $target->intBeforeAndAfter(1);
}
',
        );
        $this->file_provider->registerFile(
            $project_dir . DIRECTORY_SEPARATOR . 'LateCallerB.php',
            '<?php

namespace Vimeo\Test\DummyProject;

function callAfterTheExpansion(LateTarget $target): void
{
    $target->mixedThenInt(5);
    $target->intBeforeAndAfter(2);
    $target->onlyAfter(3);
}
',
        );
        $this->project_analyzer = $this->createParamTypeInferenceProjectAnalyzer();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->scanner->addFilesToDeepScan([$late_file => $late_file]);
        $this->project_analyzer->getConfig()->eventDispatcher->registerClass(ReportabilityExpandingHook::class);
        $fixable_before = IssueBuffer::getFixableIssues()['MissingParamType'] ?? 0;

        ReportabilityExpandingHook::$enabled = true;
        ReportabilityExpandingHook::$api = $api;
        ReportabilityExpandingHook::$after_file = $during_analysis ? $first_caller : '';
        ReportabilityExpandingHook::$file_to_report = $late_file;

        try {
            $this->project_analyzer->check('tests/fixtures/DummyProject');
        } finally {
            ReportabilityExpandingHook::reset();
        }

        return [
            $codebase->analyzer->possible_method_param_types,
            (IssueBuffer::getFixableIssues()['MissingParamType'] ?? 0) - $fixable_before,
        ];
    }

    public function testCheckPathsRecordsParamTypesOnlyOfMethodsTheReaderCanUse(): void
    {
        $this->project_analyzer = $this->getProjectAnalyzerForParamTypeInference();
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->infer_types_from_usage = true;

        $this->project_analyzer->checkPaths([self::fixturePath('DummyProject')]);

        $possible_param_types = $codebase->analyzer->possible_method_param_types;
        $this->assertSame(
            "'hello'",
            $possible_param_types['vimeo\test\dummyproject\inferencetarget::project'][0]->getId(),
        );
        $this->assertArrayNotHasKey('vimeo\test\vendor\vendortarget::vendor', $possible_param_types);
    }

    public function testCheckAltersParamTypesOnlyOfMethodsWhoseIssuesCanBeReported(): void
    {
        // the dry run prints the files it would change: only the project's
        $this->expectOutputRegex('~^(?!.*VendorTarget).*DummyProject[/\\\\]InferenceTarget\.php~s');
        $this->project_analyzer = $this->getProjectAnalyzerForParamTypeInference();
        $this->project_analyzer->setIssuesToFix(['MissingParamType' => true]);
        $this->project_analyzer->alterCodeAfterCompletion(false, true);
        $vendor_file = self::fixturePath('ExtraFiles/dependency/VendorTarget.php');
        $vendor_contents = $this->file_provider->getContents($vendor_file);

        $this->project_analyzer->check('tests/fixtures/DummyProject');

        $codebase = $this->project_analyzer->getCodebase();
        $this->assertStringContainsString(
            'public function project(string $a): void',
            $codebase->getFileContents(self::fixturePath('DummyProject/InferenceTarget.php')),
        );
        $this->assertSame($vendor_contents, $codebase->getFileContents($vendor_file));
    }

    /**
     * The path of a fixture as the config and the scanned files spell it: the paths of the project directories are
     * compared as prefixes, so on Windows a path with forward slashes is not in them
     */
    private static function fixturePath(string $path_in_fixtures): string
    {
        return (string) getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'fixtures'
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path_in_fixtures);
    }

    private function getProjectAnalyzerForParamTypeInference(): ProjectAnalyzer
    {
        $this->file_provider->registerFile(
            self::fixturePath('DummyProject/InferenceTarget.php'),
            '<?php

namespace Vimeo\Test\DummyProject;

use Vimeo\Test\Vendor\VendorTarget;

trait InferenceTrait
{
    public function fromTrait($a): void {}
}

final class InferenceTarget extends VendorTarget
{
    use InferenceTrait;

    public function project($a): void {}
}

function callInferenceTargets(InferenceTarget $target, VendorTarget $vendor_target): void
{
    $target->project("hello");
    $target->fromTrait("hello");
    $target->vendor("hello");
    $vendor_target->vendor("hello");
}
',
        );
        $this->file_provider->registerFile(
            self::fixturePath('ExtraFiles/dependency/VendorTarget.php'),
            '<?php

namespace Vimeo\Test\Vendor;

class VendorTarget
{
    public function vendor($a): void {}
}
',
        );

        return $this->createParamTypeInferenceProjectAnalyzer();
    }

    private function createParamTypeInferenceProjectAnalyzer(): ProjectAnalyzer
    {
        $project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                    <extraFiles>
                        <directory name="tests/fixtures/ExtraFiles/dependency" />
                    </extraFiles>
                </psalm>',
            ),
        );
        $project_analyzer->setPhpVersion('8.1', 'tests');

        return $project_analyzer;
    }
}
