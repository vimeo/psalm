<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;

class TestCase extends PHPUnit_Framework_TestCase
{
    /** @var \PhpParser\Parser */
    protected static $parser;

    /** @var TestConfig */
    protected static $config;

    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /** @var Provider\FakeFileProvider */
    protected $file_provider;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        FileChecker::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();
        $this->project_checker = new \Psalm\Checker\ProjectChecker($this->file_provider);
        $this->project_checker->setConfig(new TestConfig());
    }

    /**
     * @param string $file_path
     * @param string $contents
     * @return void
     */
    public function addFile($file_path, $contents)
    {
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_checker->queueFileForScanning($file_path);
    }
}
