<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Context;

class TestCase extends BaseTestCase
{
    /** @var TestConfig */
    protected static $config;

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

        $this->project_checker = new ProjectChecker(
            new TestConfig(),
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
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
        $this->project_checker->queueFileForScanning($file_path);
    }

    /**
     * @param  string  $file_path
     * @param  Context $context
     *
     * @return void
     */
    public function analyzeFile($file_path, Context $context)
    {
        $config = $this->project_checker->getConfig();
        $file_checker = new FileChecker($this->project_checker, $file_path, $config->shortenFileName($file_path));
        $this->project_checker->registerAnalyzableFile($file_path);
        $this->project_checker->scanFiles();
        $file_checker->analyze($context);
    }
}
