<?php

declare(strict_types=1);

const USAGE = <<<'TXT'
Usage: php server.php mcp|lsp [PROJECT_DIR]

Starts the Psalm of a PHP project as an MCP server (mcp) or a language server (lsp), on stdin and stdout.
It looks for Psalm in the project (vendor/vimeo/psalm, psalm/phar, vendor-bin/*, tools/*), in the global
Composer packages, then on the PATH. PROJECT_DIR defaults to $CLAUDE_PROJECT_DIR, then to the current directory.
Arguments after PROJECT_DIR are passed on to the server.

TXT;

/**
 * The project root: the closest directory from the start up that has a composer.json or a psalm.xml. It stays in the
 * Git repository of the start, so that the Psalm of a directory above the project never runs.
 */
function findProjectRoot(string $start): string
{
    $dir = $start;
    while (true) {
        foreach (['composer.json', 'psalm.xml', 'psalm.xml.dist'] as $file) {
            if (is_file($dir . '/' . $file)) {
                return $dir;
            }
        }

        if (file_exists($dir . '/.git') || dirname($dir) === $dir || !isInGitRepository(dirname($dir))) {
            return $start;
        }

        $dir = dirname($dir);
    }
}

function isInGitRepository(string $dir): bool
{
    for (; dirname($dir) !== $dir; $dir = dirname($dir)) {
        if (file_exists($dir . '/.git')) {
            return true;
        }
    }

    return file_exists($dir . '/.git');
}

/**
 * @return list<string> the directories that may hold a Composer installation of Psalm, best first
 */
function getVendorDirs(string $root): array
{
    $vendor_dir = 'vendor';
    $composer = is_file($root . '/composer.json')
        ? json_decode((string) file_get_contents($root . '/composer.json'), true)
        : null;
    if (is_array($composer) && is_string($composer['config']['vendor-dir'] ?? null)) {
        $vendor_dir = $composer['config']['vendor-dir'];
    }

    // only inside the project
    $project_vendor_dir = realpath($root . '/' . $vendor_dir);
    $dirs = $project_vendor_dir !== false
        && str_starts_with(strtr($project_vendor_dir, '\\', '/') . '/', strtr($root, '\\', '/') . '/')
        ? [$project_vendor_dir]
        : [];
    foreach ([...(glob($root . '/vendor-bin/*/vendor') ?: []), ...(glob($root . '/tools/*/vendor') ?: [])] as $dir) {
        $dirs[] = $dir;
    }
    $dirs[] = $root . '/tools/vendor';

    $home = getenv('COMPOSER_HOME');
    $user_home = getenv('HOME') ?: getenv('USERPROFILE');
    foreach ([
        is_string($home) && $home !== '' ? $home : null,
        is_string($user_home) ? $user_home . '/.composer' : null,
        is_string($user_home) ? $user_home . '/.config/composer' : null,
        is_string(getenv('APPDATA')) ? getenv('APPDATA') . '/Composer' : null,
    ] as $global) {
        if ($global !== null) {
            $dirs[] = $global . '/vendor';
        }
    }

    return $dirs;
}

/**
 * @return list<string>|null the command that starts the server, null when Psalm isn't installed
 */
function findServer(string $mode, string $root, ?string &$problem): ?array
{
    foreach (getVendorDirs($root) as $vendor_dir) {
        $package = $vendor_dir . '/vimeo/psalm';
        if (is_file($package . '/psalm')) {
            if ($mode === 'lsp') {
                return [PHP_BINARY, $package . '/psalm-language-server'];
            }
            if (is_file($package . '/psalm-mcp')) {
                return [PHP_BINARY, $package . '/psalm-mcp'];
            }
            $problem = 'The Psalm in ' . $package . ' has no MCP server: update it to the latest Psalm 7 with'
                . ' `composer require --dev vimeo/psalm:^7`.';
            return null;
        }

        $phar = $vendor_dir . '/psalm/phar/psalm.phar';
        if (is_file($phar)) {
            return [PHP_BINARY, $phar, $mode === 'lsp' ? '--language-server' : '--mcp'];
        }
    }

    $path = getenv('PATH');
    foreach (is_string($path) ? explode(PATH_SEPARATOR, $path) : [] as $dir) {
        foreach ($mode === 'lsp' ? ['psalm-language-server'] : ['psalm-mcp'] as $name) {
            if (is_file($dir . '/' . $name)) {
                return [$dir . '/' . $name];
            }
        }
        foreach (['psalm', 'psalm.phar'] as $name) {
            if (is_file($dir . '/' . $name)) {
                return [$dir . '/' . $name, $mode === 'lsp' ? '--language-server' : '--mcp'];
            }
        }
    }

    $problem = 'Psalm is not installed in ' . $root . '. Install it with `composer require --dev vimeo/psalm`, then'
        . ' create its config with `vendor/bin/psalm --init`, and restart the agent.';
    return null;
}

/**
 * An MCP server whose one tool says what is missing, so that the agent can tell the user
 */
function serveProblem(string $problem): never
{
    while (($line = fgets(STDIN)) !== false) {
        $message = json_decode($line, true);
        if (!is_array($message) || !isset($message['id'], $message['method'])) {
            continue;
        }

        $result = match ($message['method']) {
            'initialize' => [
                'protocolVersion' => is_string($message['params']['protocolVersion'] ?? null)
                    ? $message['params']['protocolVersion']
                    : '2025-06-18',
                'capabilities' => ['tools' => new stdClass()],
                'serverInfo' => ['name' => 'psalm', 'version' => '0'],
            ],
            'tools/list' => ['tools' => [[
                'name' => 'psalm_check',
                'description' => 'Psalm is not available in this project: call this tool to see why.',
                'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
            ]]],
            'tools/call' => ['content' => [['type' => 'text', 'text' => $problem]], 'isError' => true],
            default => new stdClass(),
        };

        $response = ['jsonrpc' => '2.0', 'id' => $message['id'], 'result' => $result];
        echo json_encode($response, JSON_UNESCAPED_SLASHES), "\n";
    }

    exit(0);
}

/** @var list<string> $argv */
$mode = $argv[1] ?? '';
if (!in_array($mode, ['mcp', 'lsp'], true)) {
    fwrite(in_array($mode, ['-h', '--help'], true) ? STDOUT : STDERR, USAGE);
    exit($mode === '-h' || $mode === '--help' ? 0 : 1);
}

$start = $argv[2] ?? (getenv('CLAUDE_PROJECT_DIR') ?: getcwd());
$start = is_string($start) ? realpath($start) : false;
if ($start === false) {
    fwrite(STDERR, "The project directory does not exist\n");
    exit(1);
}

$root = findProjectRoot($start);
$problem = null;
$command = findServer($mode, $root, $problem);

if ($command === null) {
    if ($mode === 'mcp') {
        serveProblem((string) $problem);
    }
    fwrite(STDERR, $problem . "\n");
    exit(1);
}

$process = proc_open([...$command, ...array_slice($argv, 3)], [STDIN, STDOUT, STDERR], $pipes, $root);
if (!is_resource($process)) {
    fwrite(STDERR, 'Could not run ' . implode(' ', $command) . "\n");
    exit(1);
}

exit(proc_close($process));
