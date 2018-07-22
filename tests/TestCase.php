<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use RuntimeException;

class TestCase extends BaseTestCase
{
    /** @var string */
    protected static $src_dir_path;

    /** @var ProjectChecker */
    protected $project_checker;

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

        FileChecker::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $config = new TestConfig();
        $parser_cache_provider = new Provider\FakeParserCacheProvider();

        $this->project_checker = new ProjectChecker(
            $config,
            $this->file_provider,
            $parser_cache_provider,
            new \Psalm\Provider\NoCache\NoFileStorageCacheProvider(),
            new \Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider(),
            false,
            true,
            ProjectChecker::TYPE_CONSOLE,
            1,
            false
        );

        $this->project_checker->infer_types_from_usage = true;
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
        $this->project_checker->getCodeBase()->scanner->addFileToShallowScan($file_path);
    }

    /**
     * @param  string         $file_path
     * @param  \Psalm\Context $context
     *
     * @return void
     */
    public function analyzeFile($file_path, \Psalm\Context $context)
    {
        $codebase = $this->project_checker->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);

        $codebase->scanFiles();

        $codebase->config->visitStubFiles($codebase);

        $file_checker = new FileChecker(
            $this->project_checker,
            $file_path,
            $codebase->config->shortenFileName($file_path)
        );
        $file_checker->analyze($context);
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
