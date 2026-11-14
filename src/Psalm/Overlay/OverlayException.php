<?php

declare(strict_types=1);

namespace Psalm\Overlay;

use RuntimeException;

/**
 * A request without an answer. $kind is one of invalid_request, invalid_overlay, unsupported,
 * references_not_recorded, references_stale, project_changed, worker_failed, timeout, cancelled, config.
 */
final class OverlayException extends RuntimeException
{
    /**
     * @psalm-capabilities read-props
     */
    public function __construct(public readonly string $kind, string $message)
    {
        parent::__construct($message);
    }
}
