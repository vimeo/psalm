<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Fork;

use Amp\NullCancellation;
use Amp\Sync\Channel;
use Psalm\Internal\Fork\InitScannerTask;
use Psalm\Internal\Fork\ShutdownScannerTask;
use Psalm\Internal\Scanner\UnresolvedConstant\ScalarValue;
use Psalm\Storage\FunctionStorage;
use Psalm\Tests\TestCase;
use Psalm\Type;

use function Amp\Sync\createChannelPair;
use function array_keys;

final class ScannerTasksTest extends TestCase
{
    /**
     * A forked scanner worker starts with the stubs the parent has, which the parent does not need back, so it
     * reports only the functions and constants it added itself.
     */
    public function testWorkerReportsOnlyTheGlobalsItAdded(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        /** @var Channel<void, void> $channel */
        [$channel] = createChannelPair();

        $codebase->functions->addGlobalFunction('inherited_function', new FunctionStorage());
        $codebase->addGlobalConstantType('INHERITED_CONSTANT', Type::getInt());
        $codebase->addUnresolvedGlobalConstant('INHERITED_UNRESOLVED_CONSTANT', new ScalarValue(1));

        (new InitScannerTask())->run($channel, new NullCancellation());

        $codebase->functions->addGlobalFunction('Added_Function', new FunctionStorage());
        $codebase->addGlobalConstantType('ADDED_CONSTANT', Type::getString());
        $codebase->addUnresolvedGlobalConstant('ADDED_UNRESOLVED_CONSTANT', new ScalarValue(2));

        $result = (new ShutdownScannerTask())->run($channel, new NullCancellation());

        $this->assertSame(['added_function'], array_keys($result['global_functions']));
        $this->assertSame(['ADDED_CONSTANT'], array_keys($result['global_constants']));
        $this->assertSame(['ADDED_UNRESOLVED_CONSTANT'], array_keys($result['unresolved_global_constants']));
    }

    /**
     * Merging what a worker reports into the parent leaves it with the same globals as merging all the worker has
     * (see Functions::addGlobalFunctions()), including when the worker replaced a stub it inherited.
     */
    public function testMergingWhatTheWorkerReportsLeavesTheParentWithAllOfTheWorkersGlobals(): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        /** @var Channel<void, void> $channel */
        [$channel] = createChannelPair();

        $inherited = new FunctionStorage();
        $codebase->functions->addGlobalFunction('inherited_function', $inherited);
        $inherited_constant = Type::getInt();
        $codebase->addGlobalConstantType('INHERITED_CONSTANT', $inherited_constant);

        (new InitScannerTask())->run($channel, new NullCancellation());

        $added = new FunctionStorage();
        $codebase->functions->addGlobalFunction('inherited_function', new FunctionStorage());
        $codebase->functions->addGlobalFunction('added_function', $added);
        $codebase->addGlobalConstantType('INHERITED_CONSTANT', Type::getString());

        $result = (new ShutdownScannerTask())->run($channel, new NullCancellation());

        // The parent still has what it had before the fork
        $codebase->functions->addGlobalFunction('inherited_function', $inherited);
        $codebase->addGlobalConstantType('INHERITED_CONSTANT', $inherited_constant);
        $codebase->functions->addGlobalFunctions($result['global_functions']);
        $codebase->addGlobalConstantTypes($result['global_constants']);

        $this->assertSame(
            ['inherited_function' => $inherited, 'added_function' => $added],
            $codebase->functions->getAllStubbedFunctions(),
        );
        $this->assertSame(['INHERITED_CONSTANT' => $inherited_constant], $codebase->getAllStubbedConstants());
    }
}
