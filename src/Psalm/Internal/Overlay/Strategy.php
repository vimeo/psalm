<?php

declare(strict_types=1);

namespace Psalm\Internal\Overlay;

use function getenv;

/**
 * How many files a request analyses, from what the overlays change for other files
 *
 * @internal
 */
final class Strategy
{
    /** Nothing another file can see changed: the overlaid files, their inheritors and the files including them */
    public const BODY = 'S0';

    /** Only the visibility of members changed: S0 and the files referencing those members */
    public const VISIBILITY = 'S1';

    /** Another change to existing declarations: S0 and the files referencing them, or every project file */
    public const SIGNATURE = 'S2';

    /** Declarations were added or removed: every project file */
    public const DECLARATIONS = 'S3';

    /** What the scan registered globally cannot be undone in a scanned process: a process that scans the overlays */
    public const FRESH = 'S3-fresh';

    /** The request asked for it: every project file, with the passes over the whole project */
    public const WHOLE_PROJECT = 'whole';

    /** S2 takes every project file, see SCOPE_ENV */
    public const SCOPE_PROJECT = 'project';

    /** S2 takes the files the reference index names, the default */
    public const SCOPE_INDEX = 'index';

    /** `project` makes S2 analyse every project file, to compare with the files the index names */
    public const SCOPE_ENV = 'PSALM_OVERLAY_SIGNATURE_SCOPE';

    /** Computes the S2 answer too for S1 requests and reports differences on STDERR */
    public const VERIFY_ENV = 'PSALM_OVERLAY_VERIFY';

    /** Prints the strategy and the files of each request on STDERR, as a JSON line */
    public const DEBUG_ENV = 'PSALM_OVERLAY_DEBUG';

    /** @return self::SCOPE_* */
    public static function getSignatureScope(): string
    {
        return getenv(self::SCOPE_ENV) === self::SCOPE_PROJECT ? self::SCOPE_PROJECT : self::SCOPE_INDEX;
    }
}
