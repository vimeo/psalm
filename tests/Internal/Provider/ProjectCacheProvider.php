<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\ProjectCacheProvider as PsalmProjectCacheProvider;

final class ProjectCacheProvider extends PsalmProjectCacheProvider
{
    private int $last_run = 0;

    /** @var array{count: int, custom: array<int, string>, map: array<string, int>}|null */
    private ?array $custom_taints = null;

    /**
     * @psalm-mutation-free
     */
    public function __construct()
    {
    }

    /**
     * In-memory stand-in for the on-disk custom taint persistence, so the cache tests can exercise it
     * without a real cache directory.
     *
     * @return array{count: int, custom: array<int, string>, map: array<string, int>}|null
     * @psalm-mutation-free
     */
    #[Override]
    public function loadCustomTaints(): ?array
    {
        return $this->custom_taints;
    }

    /**
     * @param array{count: int, custom: array<int, string>, map: array<string, int>} $data
     * @psalm-external-mutation-free
     */
    #[Override]
    public function saveCustomTaints(array $data): void
    {
        $this->custom_taints = $data;
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function processSuccessfulRun(float $start_time, string $psalm_version): void
    {
        $this->last_run = (int) $start_time;
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function canDiffFiles(): bool
    {
        return $this->last_run > 0;
    }
}
