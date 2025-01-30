<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;

/**
 * @internal
 * @implements Task<null, void, void>
 */
final class InitAnalyzerTask implements Task
{
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $file_reference_provider = $codebase->file_reference_provider;

        if ($codebase->taint_flow_graph) {
            $codebase->taint_flow_graph = new TaintFlowGraph();
        }

        $file_reference_provider->setNonMethodReferencesToClasses([]);
        $file_reference_provider->setCallingMethodReferencesToClassMembers([]);
        $file_reference_provider->setCallingMethodReferencesToClassProperties([]);
        $file_reference_provider->setFileReferencesToClassMembers([]);
        $file_reference_provider->setFileReferencesToClassProperties([]);
        $file_reference_provider->setCallingMethodReferencesToMissingClassMembers([]);
        $file_reference_provider->setFileReferencesToMissingClassMembers([]);
        $file_reference_provider->setReferencesToMixedMemberNames([]);
        $file_reference_provider->setMethodParamUses([]);
        return null;
    }
}
