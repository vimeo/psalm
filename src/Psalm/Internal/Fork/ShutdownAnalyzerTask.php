<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\IssueBuffer;

/**
 * @internal
 * @psalm-import-type WorkerData from Analyzer
 * @implements Task<WorkerData, void, void>
 */
final class ShutdownAnalyzerTask implements Task
{
    /**
     * @return WorkerData
     */
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $project_analyzer        = ProjectAnalyzer::getInstance();
        $codebase                = $project_analyzer->getCodebase();
        $analyzer                = $codebase->analyzer;
        $file_reference_provider = $codebase->file_reference_provider;

        $project_analyzer->progress->debug('Gathering data for forked process'."\n");

        // @codingStandardsIgnoreStart
        return [
            'issues'                                     => IssueBuffer::getIssuesData(),
            'fixable_issue_counts'                       => IssueBuffer::getFixableIssues(),
            'nonmethod_references_to_classes'            => $file_reference_provider->getAllNonMethodReferencesToClasses(),
            'method_references_to_classes'               => $file_reference_provider->getAllMethodReferencesToClasses(),
            'file_references_to_class_members'           => $file_reference_provider->getAllFileReferencesToClassMembers(),
            'method_references_to_class_members'         => $file_reference_provider->getAllMethodReferencesToClassMembers(),
            'method_dependencies'                        => $file_reference_provider->getAllMethodDependencies(),
            'file_references_to_class_properties'        => $file_reference_provider->getAllFileReferencesToClassProperties(),
            'file_references_to_method_returns'          => $file_reference_provider->getAllFileReferencesToMethodReturns(),
            'method_references_to_class_properties'      => $file_reference_provider->getAllMethodReferencesToClassProperties(),
            'method_references_to_method_returns'        => $file_reference_provider->getAllMethodReferencesToMethodReturns(),
            'file_references_to_missing_class_members'   => $file_reference_provider->getAllFileReferencesToMissingClassMembers(),
            'method_references_to_missing_class_members' => $file_reference_provider->getAllMethodReferencesToMissingClassMembers(),
            'method_param_uses'                          => $file_reference_provider->getAllMethodParamUses(),
            'mixed_member_names'                         => $analyzer->getMixedMemberNames(),
            'file_manipulations'                         => FileManipulationBuffer::getAll(),
            'mixed_counts'                               => $analyzer->getMixedCounts(),
            'function_timings'                           => $analyzer->getFunctionTimings(),
            'analyzed_methods'                           => $analyzer->getAnalyzedMethods(),
            'file_maps'                                  => $analyzer->getFileMaps(),
            'class_locations'                            => $file_reference_provider->getAllClassLocations(),
            'class_method_locations'                     => $file_reference_provider->getAllClassMethodLocations(),
            'class_property_locations'                   => $file_reference_provider->getAllClassPropertyLocations(),
            'possible_method_param_types'                => $analyzer->getPossibleMethodParamTypes(),
            'taint_data'                                 => $codebase->taint_flow_graph,
            'unused_suppressions'                        => $codebase->track_unused_suppressions ? IssueBuffer::getUnusedSuppressions() : [],
            'used_suppressions'                          => $codebase->track_unused_suppressions ? IssueBuffer::getUsedSuppressions() : [],
            'function_docblock_manipulators'             => FunctionDocblockManipulator::getManipulators(),
            'mutable_classes'                            => $codebase->analyzer->mutable_classes,
            'issue_handlers'                             => $codebase->config->getIssueHandlerSuppressions()
        ];
        // @codingStandardsIgnoreEnd
    }
}
