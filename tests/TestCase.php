<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Tests\Internal\Provider;
use RuntimeException;

class TestCase extends BaseTestCase
{
    /** @var string */
    protected static $src_dir_path;

    /** @var ProjectAnalyzer */
    protected $project_analyzer;

    /** @var Provider\FakeFileProvider */
    protected $file_provider;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        ini_set('memory_limit', '-1');

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '2.0.0');
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }

        parent::setUpBeforeClass();
        self::$src_dir_path = getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            false,
            true,
            ProjectAnalyzer::TYPE_CONSOLE,
            1,
            false
        );

        $this->project_analyzer->getCodebase()->infer_types_from_usage = true;
    }

    /**
     * @param string $file_path
     * @param string $contents
     *
     * @return void
     */
    public function addFile($file_path, $contents)
    {
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_analyzer->getCodeBase()->scanner->addFileToShallowScan($file_path);
    }

    /**
     * @param  string         $file_path
     * @param  \Psalm\Context $context
     *
     * @return void
     */
    public function analyzeFile($file_path, \Psalm\Context $context)
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);

        $codebase->scanFiles();

        $codebase->config->visitStubFiles($codebase);

        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $file_path,
            $codebase->config->shortenFileName($file_path)
        );
        $file_analyzer->analyze($context);
    }

    /**
     * @param  bool $withDataSet
     * @return string
     */
    protected function getTestName($withDataSet = true)
    {
        $name = parent::getName($withDataSet);
        /** @psalm-suppress DocblockTypeContradiction PHPUnit 7 introduced nullable name */
        if (null === $name) {
            throw new RuntimeException('anonymous test - shouldn\'t happen');
        }
        return $name;
    }
}
