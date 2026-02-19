<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Internal\VersionUtils;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Type\Union;
use Throwable;

use function array_filter;
use function count;
use function define;
use function defined;
use function getcwd;
use function implode;
use function ini_set;
use function is_string;
use function preg_match;
use function preg_quote;

use const ARRAY_FILTER_USE_KEY;
use const DIRECTORY_SEPARATOR;

class TestCase extends BaseTestCase
{
    protected static string $src_dir_path;

    /**
     * caused by phpunit using setUp() instead of __construct
     * could perhaps use psalm-plugin-phpunit once https://github.com/psalm/psalm-plugin-phpunit/issues/129
     * to remove this suppression
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected ProjectAnalyzer $project_analyzer;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected FakeFileProvider $file_provider;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected Config $testConfig;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        ini_set('memory_limit', '-1');

        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', VersionUtils::getPhpParserVersion());
        }

        parent::setUpBeforeClass();
        self::$src_dir_path = (string) getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    }

    protected function makeConfig(): Config
    {
        return new TestConfig();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $this->testConfig = $this->makeConfig();

        $providers = new Providers(
            $this->file_provider,
            new FakeParserCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $this->testConfig,
            $providers,
        );

        $this->project_analyzer->setPhpVersion('7.4', 'tests');
    }

    #[Override]
    public function tearDown(): void
    {
        unset($this->project_analyzer, $this->file_provider, $this->testConfig);
        RuntimeCaches::clearAll();
    }

    public function addFile(string $file_path, string $contents): void
    {
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_analyzer->getCodebase()->scanner->addFileToShallowScan($file_path);
    }

    public function addStubFile(string $file_path, string $contents): void
    {
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_analyzer->getConfig()->addStubFile($file_path);
    }

    public function analyzeFile(string $file_path, Context $context, bool $track_unused_suppressions = true, bool $taint_flow_tracking = false): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        if ($taint_flow_tracking) {
            $this->project_analyzer->trackTaintedInputs();
        }

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
            $codebase->config->shortenFileName($file_path),
        );
        $file_analyzer->analyze($context);

        if ($codebase->taint_flow_graph) {
            $codebase->taint_flow_graph->connectSinksAndSources($codebase->progress);
        }

        if ($track_unused_suppressions) {
            IssueBuffer::processUnusedSuppressions($codebase->file_provider);
        }
    }

    protected function getTestName(bool $withDataSet = true): string
    {
        return $this->getName($withDataSet);
    }

    public static function assertHasIssue(string $expected, string $message = ''): void
    {
        $issue_messages = [];
        $res = false;
        $issues = IssueBuffer::getIssuesData();
        foreach ($issues as $file_issues) {
            foreach ($file_issues as $issue) {
                $full_issue_message = $issue->type . ' - ' . $issue->file_name . ':' . $issue->line_from . ':' . $issue->column_from . ' - ' . $issue->message;
                $issue_messages[] = $full_issue_message;
                if (preg_match('/\b' . preg_quote($expected, '/') . '\b/', $full_issue_message)) {
                    $res = true;
                }
            }
        }
        if (!$message) {
            $message = "Failed asserting that issue with \"$expected\" was emitted.";
            if (count($issue_messages)) {
                $message .= "\n" . 'Other issues reported:' . "\n  - " . implode("\n  - ", $issue_messages);
            } else {
                $message .= ' No issues reported.';
            }
        }
        self::assertTrue($res, $message);
    }

    public static function assertArrayKeysAreStrings(array $array, string $message = ''): void
    {
        $validKeys = array_filter($array, 'is_string', ARRAY_FILTER_USE_KEY);
        self::assertTrue(count($array) === count($validKeys), $message);
    }

    public static function assertArrayKeysAreZeroOrString(array $array, string $message = ''): void
    {
        $isZeroOrString = /** @param mixed $key */ static fn($key): bool => $key === 0 || is_string($key);
        $validKeys = array_filter($array, $isZeroOrString, ARRAY_FILTER_USE_KEY);
        self::assertTrue(count($array) === count($validKeys), $message);
    }

    public static function assertArrayValuesAreArrays(array $array, string $message = ''): void
    {
        $validValues = array_filter($array, 'is_array');
        self::assertTrue(count($array) === count($validValues), $message);
    }

    public static function assertArrayValuesAreStrings(array $array, string $message = ''): void
    {
        $validValues = array_filter($array, 'is_string');
        self::assertTrue(count($array) === count($validValues), $message);
    }

    public static function assertStringIsParsableType(string $type, string $message = ''): void
    {
        if ($type === '') {
            //    Ignore empty types for now, as these are quite common for pecl libraries
            self::assertTrue(true);
        } else {
            $union = null;
            try {
                $tokens = TypeTokenizer::tokenize($type);
                $union = TypeParser::parseTokens($tokens);
            } catch (Throwable $_e) {
            }
            self::assertInstanceOf(Union::class, $union, $message);
        }
    }
}
