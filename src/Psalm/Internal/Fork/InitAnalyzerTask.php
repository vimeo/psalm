<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\CodeUseGraph;
use Psalm\Internal\Codebase\TaintFlowGraph;

/**
 * @internal
 * @implements Task<null, void, void>
 */
final class InitAnalyzerTask implements Task
{
    #[Override]
    public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $project_analyzer = ProjectAnalyzer::getInstance();
        $codebase = $project_analyzer->getCodebase();

        $file_reference_provider = $codebase->file_reference_provider;

        if ($codebase->taint_flow_graph) {
            $codebase->taint_flow_graph = new TaintFlowGraph();
        }

        if ($codebase->code_use_graph) {
            $codebase->code_use_graph = new CodeUseGraph();
        }

        $file_reference_provider->setReferencesToMixedMemberNames([]);
        $file_reference_provider->setMethodParamUses([]);
        return null;
    }
}
