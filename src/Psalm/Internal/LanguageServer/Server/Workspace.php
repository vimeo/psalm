<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Server;

use Amp\Promise;
use Amp\Success;
use InvalidArgumentException;
use LanguageServerProtocol\FileChangeType;
use LanguageServerProtocol\FileEvent;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Composer;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\Provider\FileReferenceProvider;

use function array_filter;
use function array_map;
use function in_array;
use function realpath;

/**
 * Provides method handlers for all workspace/* methods
 *
 * @internal
 */
final class Workspace
{
    protected LanguageServer $server;

    protected Codebase $codebase;

    protected ProjectAnalyzer $project_analyzer;

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
            'workspace/didChangeWatchedFiles',
        );

        $realFiles = array_filter(
            array_map(function (FileEvent $change) {
                try {
                    return $this->server->uriToPath($change->uri);
                } catch (InvalidArgumentException $e) {
                    return null;
                }
            }, $changes),
        );

        $composerLockFile = realpath(Composer::getLockFilePath($this->codebase->config->base_dir));
        if (in_array($composerLockFile, $realFiles)) {
            $this->server->logInfo('Composer.lock file changed. Reloading codebase');
            FileReferenceProvider::clearCache();
            $this->server->queueFileAnalysisWithOpenedFiles();
            return;
        }

        foreach ($changes as $change) {
            $file_path = $this->server->uriToPath($change->uri);

            if ($composerLockFile === $file_path) {
                continue;
            }

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

            //If the file is currently open then dont analize it because its tracked in didChange
            if (!$this->codebase->file_provider->isOpen($file_path)) {
                $this->server->queueClosedFileAnalysis($file_path, $change->uri);
            }
        }
    }

    /**
     * A notification sent from the client to the server to signal the change of configuration settings.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function didChangeConfiguration(): void
    {
        $this->server->logDebug(
            'workspace/didChangeConfiguration',
        );
        $this->server->client->refreshConfiguration();
    }

    /**
     * The workspace/executeCommand request is sent from the client to the server to
     * trigger command execution on the server.
     *
     * @param mixed $arguments
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function executeCommand(string $command, $arguments): Promise
    {
        $this->server->logDebug(
            'workspace/executeCommand',
            [
                'command' => $command,
                'arguments' => $arguments,
            ],
        );

        switch ($command) {
            case 'psalm.analyze.uri':
                /** @var array{uri: string} */
                $arguments = (array) $arguments;
                $file = $this->server->uriToPath($arguments['uri']);
                $this->codebase->reloadFiles(
                    $this->project_analyzer,
                    [$file],
                    true,
                );

                $this->codebase->analyzer->addFilesToAnalyze(
                    [$file => $file],
                );
                $this->codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

                $this->server->emitVersionedIssues([$file => $arguments['uri']]);
                break;
        }

        return new Success(null);
    }
}
