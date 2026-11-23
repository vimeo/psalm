<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

/**
 * @psalm-immutable
 * @internal
 */
final class Request
{
    /**
     * @param array<string, string> $overlays absolute path of the original file => contents that replace it
     * @param positive-int|null $timeout_ms
     * @param Strategy::*|null $forced_strategy
     */
    public function __construct(
        public readonly array $overlays,
        public readonly bool $whole_project = false,
        public readonly ?int $timeout_ms = null,
        public readonly ?string $forced_strategy = null,
    ) {
    }

    /**
     * The request as the `analyze` method of the protocol takes it
     *
     * @return array{overlays: list<array{path: string, contents: string}>, whole_project: bool, timeout_ms?: int}
     */
    public function toParams(): array
    {
        $overlays = [];
        foreach ($this->overlays as $path => $contents) {
            $overlays[] = ['path' => $path, 'contents' => $contents];
        }

        $params = ['overlays' => $overlays, 'whole_project' => $this->whole_project];

        if ($this->timeout_ms !== null) {
            $params['timeout_ms'] = $this->timeout_ms;
        }

        return $params;
    }
}
