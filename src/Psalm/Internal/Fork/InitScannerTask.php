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

use function array_diff_key;
use function array_fill_keys;
use function array_keys;

use const PHP_EOL;

/**
 * @internal
 * @implements Task<null, void, void>
 */
final class InitScannerTask implements Task
{
    /**
     * The global functions, constants and unresolved constants the worker inherited when it was forked. The parent
     * process has them already (and keeps its own when merging, see Functions::addGlobalFunctions()), so the worker
     * reports only the ones it added: ShutdownScannerTask would otherwise send every stub back once per worker.
     *
     * @var array{
     *     functions: array<string, true>,
     *     constants: array<string, true>,
     *     unresolved_constants: array<string, true>,
     * }
     */
    private static array $inherited = ['functions' => [], 'constants' => [], 'unresolved_constants' => []];

    #[Override]
    final public function run(Channel $channel, Cancellation $cancellation): mixed
    {
        $analyzer = ProjectAnalyzer::getInstance();
        $analyzer->progress->debug('Initialising forked process for scanning' . PHP_EOL);

        $codebase = $analyzer->getCodebase();
        $statements_provider = $codebase->statements_provider;

        $codebase->scanner->isForked();
        self::$inherited = [
            'functions' => array_fill_keys(array_keys($codebase->functions->getAllStubbedFunctions()), true),
            'constants' => array_fill_keys(array_keys($codebase->getAllStubbedConstants()), true),
            'unresolved_constants' => array_fill_keys(array_keys($codebase->getAllUnresolvedGlobalConstants()), true),
        ];
        FileStorageProvider::deleteAll();
        ClassLikeStorageProvider::deleteAll();

        $statements_provider->resetDiffs();

        $analyzer->progress->debug('Have initialised forked process for scanning' . PHP_EOL);

        return null;
    }

    /**
     * @template TKey of string
     * @template T
     * @param array<TKey, T> $all
     * @param 'functions'|'constants'|'unresolved_constants' $kind
     * @return array<TKey, T>
     */
    public static function withoutInherited(string $kind, array $all): array
    {
        return array_diff_key($all, self::$inherited[$kind]);
    }
}
