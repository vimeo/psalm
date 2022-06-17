<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Internal\DataFlow\Path;

use function array_filter;
use function array_diff_key;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function explode;
use function in_array;
use function preg_match;
use function preg_quote;
use function sprintf;

use const ARRAY_FILTER_USE_KEY;

class OrphanForwardEdgesWorker
{
    private const TYPE_ESCAPED = 'conditionally-escaped';
    private const TYPE_ARGUMENT = 'arg';

    /** @var array<string, array<string, Path>> */
    private array $forwardEdges;
    /** @var array<string, array<string, Path>> */
    private array $orphanForwardEdges;
    /** @var array<string, array<string, Path>> */
    private array $potentialForwardEdges;

    /**
     * @param array<string, array<string, Path>> $forwardEdges
     */
    public function __construct(array $forwardEdges)
    {
        $this->forwardEdges = $forwardEdges;
        $this->prepare();
        $this->process();
    }

    private function prepare(): void
    {
        $originNames = array_keys($this->forwardEdges);
        $this->orphanForwardEdges = array_filter(array_map(
            fn(array $origin): array => array_filter(
                $origin,
                fn(string $destinationName): bool => !in_array($destinationName, $originNames),
                ARRAY_FILTER_USE_KEY
            ),
            $this->forwardEdges
        ));
        $this->potentialForwardEdges = array_filter(array_map(
            fn(array $origin): array => array_filter(
                $origin,
                fn(Path $desination): bool => $desination->type === self::TYPE_ESCAPED
            ),
            $this->forwardEdges
        ));
    }

    private function process(): void
    {
        // filter out potential forward edges using same origin keys
        $orphanForwardEdges = array_diff_key($this->orphanForwardEdges, $this->potentialForwardEdges);
        foreach ($orphanForwardEdges as $orphanOriginName => $orphanDestinations) {
            foreach ($orphanDestinations as $orphanDestinationName => $orphanDestination) {
                if ($orphanDestination->type !== self::TYPE_ARGUMENT) {
                    continue;
                }
                $orphanDestinationParts = explode('-', $orphanDestinationName, 2);
                if (empty($orphanDestinationParts[1])) {
                    continue;
                }
                [$identifier, $locationTrace] = $orphanDestinationParts;
                if (preg_match('/^(?P<ref>.+)#(?P<argPos>\d+)$/', $identifier, $matches)) {
                    $modification = $this->resolvePotentialModification($matches['ref'], $locationTrace);
                    if ($modification !== null) {
                        // cave: since `Path` instances are not cloned in constructor, changes are applied directly
                        $this->applyModification($modification, $orphanDestination);
                    }
                }
            }
        }
    }

    /**
     * @param string $reference corresponding call reference, e.g. `unserialize` (without argPos `#123`)
     * @param string $locationTrace corresponding call location trace, e.g. `src/somefile.php:95`
     * @return Path|null
     */
    private function resolvePotentialModification(string $reference, string $locationTrace): ?Path
    {
        $unescapedTaints = [];
        $escapedTaints = [];

        // matching given reference, any alternative location trace (e.g. stubs) and the given location trace
        $pattern = sprintf(
            '/^%s-(?:escaped)(?:-[^:]+:\d+(-\d+)?)-%s$/',
            preg_quote($reference, '/'),
            preg_quote($locationTrace, '/')
        );
        // exact match (basically stripped of `argPos`)
        $exactMatch = $reference . '-' . $locationTrace;
        foreach ($this->potentialForwardEdges as $potentialOriginName => $potentialDestinations) {
            if ($potentialOriginName !== $exactMatch) {
                continue;
            }
            foreach ($potentialDestinations as $potentialDestinationName => $potentialDestination) {
                if (!preg_match($pattern, $potentialDestinationName)) {
                    continue;
                }
                if (!empty($potentialDestination->unescaped_taints)) {
                    $unescapedTaints = array_merge($unescapedTaints, $potentialDestination->unescaped_taints);
                }
                if (!empty($potentialDestination->escaped_taints)) {
                    $escapedTaints = array_merge($escapedTaints, $potentialDestination->escaped_taints);
                }
            }
        }

        $unescapedTaints = array_unique($unescapedTaints);
        $escapedTaints = array_unique($escapedTaints);

        if ($unescapedTaints === [] && $escapedTaints === []) {
            return null;
        }
        return new Path('temp', 0, $unescapedTaints, $escapedTaints);
    }

    private function applyModification(Path $modification, Path $to): void
    {
        $to->unescaped_taints = array_unique(array_merge(
            $target->unescaped_taints ?? [],
            $modification->unescaped_taints ?? []
        ));
        $to->escaped_taints = array_unique(array_merge(
            $target->escaped_taints ?? [],
            $modification->escaped_taints ?? []
        ));
    }
}
