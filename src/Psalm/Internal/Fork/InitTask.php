<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use AssertionError;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\VersionUtils;
use Psalm\IssueBuffer;

use const PHP_EOL;

/** @internal */
abstract class InitTask implements Task
{
    private readonly ProjectAnalyzer $analyzer;
    private readonly Config $config;
    private readonly string $memoryLimit;
    private readonly array $server;
    public static bool $ran = false;
    final public function __construct()
    {
        $this->analyzer = ProjectAnalyzer::getInstance();
        $this->config = Config::getInstance();
        $this->memoryLimit = ini_get('memory_limit');
        $this->server = IssueBuffer::getServer();
    }
    final public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        if (self::$ran) {
            throw new AssertionError("Already inited!");
        }
        self::$ran = true;

        define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        define('PHP_PARSER_VERSION', VersionUtils::getPhpParserVersion());

        CliUtils::checkRuntimeRequirements();
        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install([]);

        ini_set('memory_limit', $this->memoryLimit);

        IssueBuffer::captureServer($this->server);

        // More initialization is needed but doing just this for now...

        ProjectAnalyzer::$instance = $this->analyzer;
        Config::setInstance($this->config);

        static::init();

        return null;
    }
    abstract protected function init(): void;
}
