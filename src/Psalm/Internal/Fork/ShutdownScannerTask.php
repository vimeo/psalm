<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Scanner;
use Psalm\IssueBuffer;

use const PHP_EOL;

/**
 * @internal
 * @psalm-import-type PoolData from Scanner
 * @implements Task<PoolData, void, void>
 */
final class ShutdownScannerTask implements Task
{
    /**
     * @return PoolData
     */
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $project_analyzer->progress->debug('Collecting data from forked scanner process' . PHP_EOL);

        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();
        $statements_provider = $codebase->statements_provider;

        return [
            'classlikes_data' => $codebase->classlikes->getThreadData(),
            'scanner_data' => $codebase->scanner->getThreadData(),
            'issues' => IssueBuffer::getIssuesData(),
            'changed_members' => $statements_provider->getChangedMembers(),
            'unchanged_signature_members' => $statements_provider->getUnchangedSignatureMembers(),
            'diff_map' => $statements_provider->getDiffMap(),
            'deletion_ranges' => $statements_provider->getDeletionRanges(),
            'errors' => $statements_provider->getErrors(),
            'classlike_storage' => $codebase->classlike_storage_provider->getAll(),
            'file_storage' => $codebase->file_storage_provider->getAll(),
            'taint_data' => $codebase->taint_flow_graph,
            'global_constants' => self::getChanged(
                $codebase->getAllStubbedConstants(),
                InitScannerTask::$inherited_constants,
            ),
            'unresolved_global_constants' => self::getChanged(
                $codebase->getAllUnresolvedGlobalConstants(),
                InitScannerTask::$inherited_unresolved_constants,
            ),
            'global_constant_definitions' => $codebase->getGlobalConstantDefinitions(),
            'unresolved_global_constant_definitions' => $codebase->getUnresolvedGlobalConstantDefinitions(),
            'missed_global_constants' => $codebase->getMissedGlobalConstants(),
            'files_to_cache' => $codebase->scanner->getFilesToCache(),
            // the parent registers the functions of autoloaded files from their file storage, see Scanner
            'global_functions' => $codebase->register_autoload_files
                ? []
                : $codebase->functions->getAllStubbedFunctions(),
        ];
    }

    /**
     * @template T of object
     * @param array<string, T> $current
     * @param array<string, T> $inherited
     * @return array<string, T>
     * @psalm-pure
     */
    private static function getChanged(array $current, array $inherited): array
    {
        $changed = [];

        foreach ($current as $name => $value) {
            if (($inherited[$name] ?? null) !== $value) {
                $changed[$name] = $value;
            }
        }

        return $changed;
    }
}
