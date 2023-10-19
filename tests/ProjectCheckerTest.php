<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\ClassLikeStorageInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\FakeFileReferenceCacheProvider;
use Psalm\Tests\Internal\Provider\FileStorageInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ParserInstanceCacheProvider;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\Progress\EchoProgress;

use function define;
use function defined;
use function get_class;
use function getcwd;
use function microtime;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function realpath;

use const DIRECTORY_SEPARATOR;

class ProjectCheckerTest extends TestCase
{
    protected static TestConfig $config;

    protected ProjectAnalyzer $project_analyzer;

    public static function setUpBeforeClass(): void
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '4.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();
    }

    private function getProjectAnalyzerWithConfig(Config $config): ProjectAnalyzer
    {
        $config->setIncludeCollector(new IncludeCollector());
        return new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new ParserInstanceCacheProvider(),
                new FileStorageInstanceCacheProvider(),
                new ClassLikeStorageInstanceCacheProvider(),
                new FakeFileReferenceCacheProvider(),
                new ProjectCacheProvider(),
            ),
            new ReportOptions(),
        );
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

    public function testAfterCodebasePopulatedIsInvoked(): void
    {
        $hook = new class implements AfterCodebasePopulatedInterface {
            public static bool $called = false;

            /**
             * @return void
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint
             */
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

        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject');
        ob_end_clean();

        $this->assertTrue($hook::$called);
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

class Bat
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
}
