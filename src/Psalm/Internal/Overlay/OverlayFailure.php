<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use RuntimeException;

/**
 * A request that has no answer, with the kind the protocol names
 *
 * @internal
 */
final class OverlayFailure extends RuntimeException
{
    public const INVALID_REQUEST = 'invalid_request';
    public const INVALID_OVERLAY = 'invalid_overlay';
    public const UNSUPPORTED = 'unsupported';
    public const REFERENCES_NOT_RECORDED = 'references_not_recorded';
    public const REFERENCES_STALE = 'references_stale';
    public const PROJECT_CHANGED = 'project_changed';
    public const WORKER_FAILED = 'worker_failed';
    public const TIMEOUT = 'timeout';
    public const CANCELLED = 'cancelled';
    public const CONFIG = 'config';

    /**
     * @param self::* $kind
     * @psalm-capabilities read-props
     */
    public function __construct(public readonly string $kind, string $message)
    {
        parent::__construct($message);
    }
}
