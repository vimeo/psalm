<?php
namespace Psalm\Tests;

use function define;
use function defined;
use const DIRECTORY_SEPARATOR;
use function get_class;
use function getcwd;
use function microtime;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Plugin\Hook\AfterCodebasePopulatedInterface;
use Psalm\Tests\Internal\Provider;
use Psalm\Tests\Progress\EchoProgress;

class ProjectCheckerTest extends TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public static function setUpBeforeClass() : void
    {
        self::$config = new TestConfig();

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '2.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }
    }

    /**
     * @return void
     */
    public function setUp() : void
    {
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @param  Config $config
     *
     * @return \Psalm\Internal\Analyzer\ProjectAnalyzer
     */
    private function getProjectAnalyzerWithConfig(Config $config)
    {
        $config->setIncludeCollector(new IncludeCollector());
        return new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\ParserInstanceCacheProvider(),
                new Provider\FileStorageInstanceCacheProvider(),
                new Provider\ClassLikeStorageInstanceCacheProvider(),
                new Provider\FakeFileReferenceCacheProvider(),
                new Provider\ProjectCacheProvider()
            ),
            new \Psalm\Report\ReportOptions()
        );
    }

    /**
     * @return void
     */
    public function testCheck()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject');
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $codebase = $this->project_analyzer->getCodebase();

        $this->assertSame([0, 5], $codebase->analyzer->getTotalTypeCoverage($codebase));

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $codebase->analyzer->getTypeInferenceSummary(
                $codebase
            )
        );
    }

    /**
     * @return void
     */
    public function testAfterCodebasePopulatedIsInvoked()
    {
        $hook = new class implements AfterCodebasePopulatedInterface {
            /** @var bool */
            public static $called = false;

            /** @return void */
            public static function afterCodebasePopulated(Codebase $codebase)
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
                </psalm>'
            )
        );

        $hook_class = get_class($hook);

        $this->project_analyzer->getCodebase()->config->after_codebase_populated[] = $hook_class;

        ob_start();
        $this->project_analyzer->check('tests/fixtures/DummyProject');
        ob_end_clean();

        $this->assertTrue($hook::$called);
    }

    /**
     * @return void
     */
    public function testCheckAfterNoChange()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->assertNotNull($this->project_analyzer->stdout_report_options);

        $this->project_analyzer->stdout_report_options->format = \Psalm\Report::TYPE_JSON;

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_start();
        \Psalm\IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );

        $this->project_analyzer->getCodebase()->reloadFiles($this->project_analyzer, []);

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );
    }

    /**
     * @return void
     */
    public function testCheckAfterFileChange()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->assertNotNull($this->project_analyzer->stdout_report_options);

        $this->project_analyzer->stdout_report_options->format = \Psalm\Report::TYPE_JSON;

        $this->project_analyzer->check('tests/fixtures/DummyProject', true);
        ob_start();
        \Psalm\IssueBuffer::finish($this->project_analyzer, true, microtime(true));
        ob_end_clean();

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );

        $bat_file_path = getcwd()
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

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );
    }

    /**
     * @return void
     */
    public function testCheckDir()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->checkDir('tests/fixtures/DummyProject');
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );
    }

    /**
     * @return void
     */
    public function testCheckPaths()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->checkPaths([
            'tests/fixtures/DummyProject/Bar.php',
            'tests/fixtures/DummyProject/SomeTrait.php'
        ]);
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );
    }

    /**
     * @return void
     */
    public function testCheckFile()
    {
        $this->project_analyzer = $this->getProjectAnalyzerWithConfig(
            Config::loadFromXML(
                (string)getcwd(),
                '<?xml version="1.0"?>
                <psalm>
                    <projectFiles>
                        <directory name="tests/fixtures/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->progress = new EchoProgress();

        ob_start();
        $this->project_analyzer->checkPaths([
            'tests/fixtures/DummyProject/Bar.php',
            'tests/fixtures/DummyProject/SomeTrait.php'
        ]);
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100% of the codebase',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary(
                $this->project_analyzer->getCodebase()
            )
        );
    }
}
