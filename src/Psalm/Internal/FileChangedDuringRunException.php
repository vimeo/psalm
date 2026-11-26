<?php

declare(strict_types=1);

namespace Psalm\Internal;

use RuntimeException;

/**
 * A project file was added, removed or edited after Psalm listed or scanned it; the message is meant for the user
 *
 * @internal
 */
final class FileChangedDuringRunException extends RuntimeException
{
}
