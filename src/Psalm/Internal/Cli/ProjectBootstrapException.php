<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use RuntimeException;

/**
 * The project cannot be set up as the options ask; the message is meant for the user
 *
 * @internal
 */
final class ProjectBootstrapException extends RuntimeException
{
}
