<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Amp\Cancellation;
use Amp\Parallel\Worker\Task;
use Amp\Sync\Channel;
use Override;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Type\Union;

use const PHP_EOL;

/**
 * @internal
 * @implements Task<null, void, void>
 */
final class InitScannerTask implements Task
{
    /**
     * The global constants the worker inherited at fork time, which the parent has too: ShutdownScannerTask sends
     * back only the ones the worker added or changed.
     *
     * @var array<string, Union>
     */
    public static array $inherited_constants = [];

    /** @var array<string, UnresolvedConstantComponent> */
    public static array $inherited_unresolved_constants = [];

    #[Override]
    final public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $analyzer = ProjectAnalyzer::getInstance();
        $analyzer->progress->debug('Initialising forked process for scanning' . PHP_EOL);

        $codebase = $analyzer->getCodebase();
        $statements_provider = $codebase->statements_provider;

        $codebase->scanner->isForked();
        self::$inherited_constants = $codebase->getAllStubbedConstants();
        self::$inherited_unresolved_constants = $codebase->getAllUnresolvedGlobalConstants();
        $codebase->resetGlobalConstantRecords();
        FileStorageProvider::deleteAll();
        ClassLikeStorageProvider::deleteAll();

        $statements_provider->resetDiffs();

        $analyzer->progress->debug('Have initialised forked process for scanning' . PHP_EOL);

        return null;
    }
}
