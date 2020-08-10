<?php
namespace Psalm\Tests;

use function define;
use function defined;
use const DIRECTORY_SEPARATOR;
use function getcwd;
use function ini_set;
use function method_exists;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Config;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
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
    public static function setUpBeforeClass() : void
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
     * @return Config
     */
    protected function makeConfig() : Config
    {
        return new TestConfig();
    }

    /**
     * @return void
     */
    public function setUp() : void
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        \Psalm\Internal\Provider\StatementsProvider::clearLexer();

        $this->file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();

        $config = $this->makeConfig();

        $providers = new Providers(
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );



        $this->project_analyzer->setPhpVersion('7.3');
    }

    public function tearDown() : void
    {
        FileAnalyzer::clearCache();
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
        $this->project_analyzer->getCodebase()->scanner->addFileToShallowScan($file_path);
    }

    /**
     * @param  string         $file_path
     * @param  \Psalm\Context $context
     *
     * @return void
     */
    public function analyzeFile($file_path, \Psalm\Context $context, bool $track_unused_suppressions = true)
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);

        $codebase->scanFiles();

        $codebase->config->visitStubFiles($codebase);

        if ($codebase->alter_code) {
            $this->project_analyzer->interpretRefactors();
        }

        $this->project_analyzer->trackUnusedSuppressions();

        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $file_path,
            $codebase->config->shortenFileName($file_path)
        );
        $file_analyzer->analyze($context);

        if ($codebase->taint) {
            $codebase->taint->connectSinksAndSources();
        }

        if ($track_unused_suppressions) {
            \Psalm\IssueBuffer::processUnusedSuppressions($codebase->file_provider);
        }
    }

    /**
     * @param  bool $withDataSet
     *
     * @return string
     */
    protected function getTestName($withDataSet = true)
    {
        $name = parent::getName($withDataSet);
        /**
         * @psalm-suppress TypeDoesNotContainNull PHPUnit 8.2 made it non-nullable again
         */
        if (null === $name) {
            throw new RuntimeException('anonymous test - shouldn\'t happen');
        }

        return $name;
    }

    /**
     * Compatibility alias
     */
    public function expectExceptionMessageRegExp(string $regexp): void
    {
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($regexp);
        } else {
            /** @psalm-suppress UndefinedMethod */
            parent::expectExceptionMessageRegExp($regexp);
        }
    }

    public static function assertRegExp(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(self::class, 'assertMatchesRegularExpression')) {
            self::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }
}
