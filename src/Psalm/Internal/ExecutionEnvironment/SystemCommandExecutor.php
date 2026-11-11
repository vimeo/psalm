<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use RuntimeException;

use function exec;
use function function_exists;
use function sprintf;

use const PHP_OS_FAMILY;

/**
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @internal
 */
final class SystemCommandExecutor
{
    /**
     * Execute command.
     *
     * @throws RuntimeException
     * @return string[]
     */
    public function execute(string $command): array
    {
        if (!function_exists('exec')) {
            throw new RuntimeException(sprintf('exec does not exist, failed to execute command: %s', $command));
        }

        // the failure is reported by the exception, not by what the command writes to stderr
        exec($command . ' 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $result, $returnValue);

        if ($returnValue === 0) {
            /** @var string[] */
            return $result;
        }

        throw new RuntimeException(sprintf('Failed to execute command: %s', $command), $returnValue);
    }
}
