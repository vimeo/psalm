<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
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
            'new_file_content_hashes' => $statements_provider->parser_cache_provider
                ? $statements_provider->parser_cache_provider->getNewFileContentHashes()
                : [],
            'taint_data' => $codebase->taint_flow_graph,
            'global_constants' => $codebase->getAllStubbedConstants(),
            'global_functions' => $codebase->functions->getAllStubbedFunctions(),
        ];
    }
}
