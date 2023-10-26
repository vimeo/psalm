<?php

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\VersionUtils;
use Psalm\IssueBuffer;

use function cli_get_process_title;
use function define;
use function function_exists;
use function gc_collect_cycles;
use function gc_disable;
use function ini_get;
use function ini_set;

final class InitStartupTask implements Task
{
    private readonly string $memoryLimit;
    private readonly array $server;
    private readonly ?string $processTitle;
    final public function __construct(private ProjectAnalyzer $analyzer)
    {
        $this->memoryLimit = ini_get('memory_limit');
        $this->server = IssueBuffer::getServer();
        if (function_exists('cli_get_process_title')) {
            $this->processTitle = cli_get_process_title();
        } else {
            $this->processTitle = null;
        }
    }
    final public function run(Channel $channel, Cancellation $cancellation): mixed
    {
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
        Config::setInstance($this->analyzer->getConfig());

        /*if (function_exists('cli_set_process_title') && $this->processTitle !== null) {
            @cli_set_process_title($this->processTitle);
        }*/

        return null;
    }
}
