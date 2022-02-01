<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Server;

use Amp\Success;
use LanguageServerProtocol\FileChangeType;
use LanguageServerProtocol\FileEvent;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\LanguageServer\LanguageServer;

/**
 * Provides method handlers for all workspace/* methods
 */
class Workspace
{
    /**
     * @var LanguageServer
     */
    protected $server;

    /**
     * @var Codebase
     */
    protected $codebase;

    /**
     * @var ProjectAnalyzer
     */
    protected $project_analyzer;

    public function __construct(
        LanguageServer $server,
        Codebase $codebase,
        ProjectAnalyzer $project_analyzer
    ) {
        $this->server = $server;
        $this->codebase = $codebase;
        $this->project_analyzer = $project_analyzer;
    }

    /**
     * The watched files notification is sent from the client to the server when the client
     * detects changes to files and folders watched by the language client (note although
     * the name suggest that only file events are sent it is about file system events
     * which include folders as well). It is recommended that servers register for these
     * file system events using the registration mechanism. In former implementations clients
     * pushed file events without the server actively asking for it.
     *
     * @param FileEvent[] $changes
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function didChangeWatchedFiles(array $changes): void
    {
        $this->server->logDebug(
            'workspace/didChangeWatchedFiles'
        );
        foreach ($changes as $change) {
            $file_path = LanguageServer::uriToPath($change->uri);

            if ($change->type === FileChangeType::DELETED) {
                $this->codebase->invalidateInformationForFile($file_path);
                continue;
            }

            if (!$this->codebase->config->isInProjectDirs($file_path)) {
                continue;
            }

            if ($this->project_analyzer->onchange_line_limit === 0) {
                continue;
            }

            //If the file is currently open then dont analyse it because its tracked by the client
            if (!$this->codebase->file_provider->isOpen($file_path)) {
                $this->server->queueFileAnalysis($file_path, $change->uri);
            }
        }
    }

    /**
     * A notification sent from the client to the server to signal the change of configuration settings.
     *
     * @param mixed $settings
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function didChangeConfiguration($settings): void
    {
        $this->server->logDebug(
            'workspace/didChangeConfiguration'
        );
        $this->server->client->refreshConfiguration();
    }

    /**
     * The workspace/executeCommand request is sent from the client to the server to
     * trigger command execution on the server.
     *
     * @param string $command
     * @param mixed $arguments
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function executeCommand($command, $arguments) {
        $this->server->logDebug(
            'workspace/executeCommand',
            [
                'command' => $command,
                'arguments' => $arguments,
            ]
        );

        switch($command) {
            case 'psalm.analyze.uri':
                $file = LanguageServer::uriToPath($arguments->uri);
                $codebase = $this->project_analyzer->getCodebase();
                $codebase->reloadFiles(
                    $this->project_analyzer,
                    [$file]
                );

                $codebase->analyzer->addFilesToAnalyze(
                    [$file => $file]
                );
                $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

                $this->server->emitVersionedIssues([$file]);
            break;
        }

        return new Success(null);
    }
}
