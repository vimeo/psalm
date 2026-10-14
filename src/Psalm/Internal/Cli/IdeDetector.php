<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use function getenv;
use function is_string;
use function str_starts_with;

/**
 * Detects the current IDE from environment variables injected into integrated terminals.
 *
 * Detection is only reliable when the script runs inside the IDE's own terminal.
 * Returns null when running from an external terminal.
 *
 * @internal
 */
final class IdeDetector
{
    public const IDE_PHPSTORM = 'phpstorm';
    public const IDE_VS_CODE = 'code';
    public const IDE_VS_CODE_SERVER = 'code-server';

    /**
     * @return self::IDE_*|null
     */
    public static function detect(): ?string
    {
        // JetBrains IDEs (PhpStorm, IntelliJ IDEA, etc.) set TERMINAL_EMULATOR=JetBrains-JediTerm
        $emulator = getenv('TERMINAL_EMULATOR');
        if (is_string($emulator) && str_starts_with($emulator, 'JetBrains')) {
            return self::IDE_PHPSTORM;
        }

        // code-server (browser-based VS Code) sets VSCODE_PROXY_URI; desktop VS Code does not
        if (getenv('VSCODE_PROXY_URI') !== false) {
            return self::IDE_VS_CODE_SERVER;
        }

        // Desktop VS Code sets TERM_PROGRAM=vscode in its integrated terminal
        if (getenv('TERM_PROGRAM') === 'vscode') {
            return self::IDE_VS_CODE;
        }

        return null;
    }
}
