<?php
namespace Psalm\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;

class TestCase extends BaseTestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

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
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );
        $this->project_checker->setConfig(new TestConfig());
        $this->project_checker->infer_types_from_usage = true;
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->project_checker->classlike_storage_provider->deleteAll();
        $this->project_checker->file_storage_provider->deleteAll();
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
}
