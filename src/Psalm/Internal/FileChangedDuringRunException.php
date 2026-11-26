<?php

declare(strict_types=1);

namespace Psalm\Internal;

use RuntimeException;

/**
 * A project file that Psalm scanned or analyses is not what it was when the run began: it was missing
 * at the scan and is there now, or has other contents. The message is meant for the user.
 *
 * @internal
 */
final class FileChangedDuringRunException extends RuntimeException
{
}
