<?php

declare(strict_types=1);

namespace Psalm\Overlay;

/**
 * How {@see OverlaySession} starts Psalm
 *
 * @psalm-immutable
 */
final class OverlayOptions
{
    /**
     * @param string $root the project directory
     * @param string|null $config the Psalm config, found from $root when null
     * @param positive-int $threads requests analysed at once
     * @param float|null $timeout seconds a request may take, after which it fails with the kind "timeout"
     * @param list<string>|null $command how to start Psalm, PHP and this package's `psalm` script when null
     */
    public function __construct(
        public readonly string $root,
        public readonly ?string $config = null,
        public readonly int $threads = 1,
        public readonly ?float $timeout = null,
        public readonly ?array $command = null,
    ) {
    }
}
