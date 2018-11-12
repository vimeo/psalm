<?php
namespace Psalm\Tests;

use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Config;
use Psalm\Context;
use Psalm\Tests\Internal\Provider;

class ProjectAnalyzerTest extends TestCase
{
    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
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
    public function setUp()
    {
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();
    }

    /**
     * @return       string[]
     * @psalm-return array<mixed, string>
     */
    public static function getAllIssues()
    {
        return array_filter(
            array_map(
                /**
                 * @param string $file_name
                 *
                 * @return string
                 */
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/src/Psalm/Issue')
            ),
            /**
             * @param string $issue_name
             *
             * @return bool
             */
            function ($issue_name) {
                return !empty($issue_name)
                    && $issue_name !== 'MethodIssue'
                    && $issue_name !== 'PropertyIssue'
                    && $issue_name !== 'ClassIssue'
                    && $issue_name !== 'CodeIssue';
            }
        );
    }

    /**
     * @param  Config $config
     *
     * @return \Psalm\Internal\Analyzer\ProjectAnalyzer
     */
    private function getProjectAnalyzerWithConfig(Config $config)
    {
        return new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\ParserInstanceCacheProvider(),
                new Provider\FileStorageInstanceCacheProvider(),
                new Provider\ClassLikeStorageInstanceCacheProvider(),
                new Provider\FakeFileReferenceCacheProvider()
            )
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        ob_start();
        $this->project_analyzer->check('tests/DummyProject');
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (2 files)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
        );
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->output_format = \Psalm\Internal\Analyzer\ProjectAnalyzer::TYPE_JSON;

        $this->project_analyzer->check('tests/DummyProject', true);
        \Psalm\IssueBuffer::finish($this->project_analyzer, true, microtime(true));

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (2 files)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
        );

        $this->project_analyzer->getCodebase()->reloadFiles($this->project_analyzer, []);

        $this->project_analyzer->check('tests/DummyProject', true);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'No files analyzed',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        $this->project_analyzer->output_format = \Psalm\Internal\Analyzer\ProjectAnalyzer::TYPE_JSON;

        $this->project_analyzer->check('tests/DummyProject', true);
        \Psalm\IssueBuffer::finish($this->project_analyzer, true, microtime(true));

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (2 files)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
        );

        $bat_file_path = getcwd() . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'DummyProject' . DIRECTORY_SEPARATOR . 'Bat.php';

        $bat_replacement_contents = '<?php

namespace Foo;

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

        $this->project_analyzer->check('tests/DummyProject', true);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (1 file)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        ob_start();
        $this->project_analyzer->checkDir('tests/DummyProject');
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (2 files)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        ob_start();
        $this->project_analyzer->checkPaths(['tests/DummyProject/Bar.php']);
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (1 file)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
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
                        <directory name="tests/DummyProject" />
                    </projectFiles>
                </psalm>'
            )
        );

        ob_start();
        $this->project_analyzer->checkFile('tests/DummyProject/Bar.php');
        $output = ob_get_clean();

        $this->assertSame('Scanning files...' . "\n" . 'Analyzing files...' . "\n", $output);

        $this->assertSame(0, \Psalm\IssueBuffer::getErrorCount());

        $this->assertSame(
            'Psalm was able to infer types for 100.000% of analyzed code (1 file)',
            $this->project_analyzer->getCodebase()->analyzer->getTypeInferenceSummary()
        );
    }
}
