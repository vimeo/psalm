<?php

/**
 * Publishes the agent plugins of a Psalm release on the agent-plugins branch.
 *
 * Usage: php bin/ci/publish-agent-plugins.php [--dry-run] <version>
 *        php bin/ci/publish-agent-plugins.php --build <version> <dir>
 *
 * Run by .github/workflows/publish-agent-plugins.yml on each release tag. The branch holds the Claude Code
 * marketplace (.claude-plugin/marketplace.json) and the plugin: skills/psalm of the release under psalm/, with its
 * version, which is also the OpenCode plugin (psalm/opencode) and the opencode-psalm npm package (psalm/). Users get
 * released versions only, and a clone of the branch is small. It keeps its history, so that the clones of
 * marketplaces pull it, and a version older than the published one (a release of an older branch, or a workflow
 * run again on an old tag) is skipped. --build only writes the files of the branch to <dir>.
 *
 * Needs git, run in a checkout of the release that can push to origin when not dry-running.
 */

declare(strict_types=1);

$branch = 'agent-plugins';
$description = 'Psalm static analysis for PHP: MCP tools to check code, inspect inferred types, find references'
    . ' and explain issues, language server diagnostics after each edit, and the Psalm skill.';

/** @param list<string> $command */
$run = static function (array $command, ?string $cwd = null): string {
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot run ' . implode(' ', $command));
    }
    $output = (string) stream_get_contents($pipes[1]);
    $error = (string) stream_get_contents($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException(implode(' ', $command) . " failed:\n" . $error . $output);
    }

    return $output;
};

$copyDirectory = static function (string $from, string $to) use (&$copyDirectory): void {
    mkdir($to, 0777, true);
    foreach (new DirectoryIterator($from) as $file) {
        if ($file->isDot()) {
            continue;
        }
        $target = $to . '/' . $file->getFilename();
        if ($file->isDir()) {
            $copyDirectory($file->getPathname(), $target);
        } else {
            copy($file->getPathname(), $target);
        }
    }
};

/** @param array<string, mixed> $data */
$writeJson = static function (string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
};

// writes the files of the branch for a version to $dir, which must not exist
$build = static function (string $version, string $dir) use ($copyDirectory, $writeJson, $description): void {
    $skill = dirname(__DIR__, 2) . '/skills/psalm';
    $copyDirectory($skill, $dir . '/psalm');

    $manifest_file = $dir . '/psalm/.claude-plugin/plugin.json';
    $manifest = json_decode((string) file_get_contents($manifest_file), true, 512, JSON_THROW_ON_ERROR);
    $writeJson($manifest_file, ['name' => $manifest['name'], 'version' => $version] + $manifest);

    mkdir($dir . '/.claude-plugin');
    $writeJson($dir . '/.claude-plugin/marketplace.json', [
        'name' => 'psalm',
        'owner' => ['name' => 'Psalm', 'url' => 'https://psalm.dev'],
        'metadata' => ['description' => 'The Psalm plugin: static analysis for PHP in Claude Code.'],
        'plugins' => [[
            'name' => 'psalm',
            'source' => './psalm',
            'version' => $version,
            'description' => $description,
        ]],
    ]);

    // the package is the whole skill, which the plugin reads the skill and the launcher from
    $package_json = (string) file_get_contents($skill . '/opencode/package.json');
    $package = json_decode($package_json, true, 512, JSON_THROW_ON_ERROR);
    unset($package['private']);
    $writeJson($dir . '/psalm/package.json', [
        'name' => $package['name'],
        'version' => $version,
        'main' => 'opencode/index.js',
        'exports' => ['.' => './opencode/index.js', './server' => './opencode/index.js'],
    ] + $package);

    file_put_contents($dir . '/README.md', <<<MD
    # Psalm agent plugins

    Psalm $version, published from `skills/psalm` of [vimeo/psalm](https://github.com/vimeo/psalm) on each release.
    Don't edit this branch. See [Using Psalm with AI agents](https://psalm.dev/docs/running_psalm/ai_agents/).

    MD);
};

/** @var list<string> $argv */
$args = array_slice($argv, 1);

$build_only = ($args[0] ?? '') === '--build' && count($args) === 3;
$dry_run = ($args[0] ?? '') === '--dry-run';
$version = $args[$build_only || $dry_run ? 1 : 0] ?? '';

// a tag, not the branch a run was dispatched on
if (preg_match('/^\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.?\d+)?$/', $version) !== 1) {
    fwrite(STDERR, "Usage: php bin/ci/publish-agent-plugins.php [--dry-run] <version>, got: $version\n");
    exit(1);
}

if ($build_only) {
    $build($version, $args[2]);
    exit(0);
}

$work = sys_get_temp_dir() . '/psalm-agent-plugins-' . getmypid();
$build($version, $work . '/tree');

// a worktree of this checkout, which has its credentials
$clone = $work . '/branch';
try {
    $run(['git', 'fetch', '--quiet', 'origin', $branch]);
    $has_branch = true;
} catch (RuntimeException) {
    $has_branch = false;
}

if ($has_branch) {
    $published = json_decode($run(['git', 'show', 'FETCH_HEAD:.claude-plugin/marketplace.json']), true);
    $published_version = is_array($published) ? (string) ($published['plugins'][0]['version'] ?? '0') : '0';
    if (version_compare($version, $published_version, '<=')) {
        echo "The branch has Psalm $published_version already, skipping $version" . PHP_EOL;
        exit(0);
    }
    $run(['git', 'worktree', 'add', '--quiet', '--detach', $clone, 'FETCH_HEAD']);
} else {
    $run(['git', 'worktree', 'add', '--quiet', '--detach', $clone]);
    $run(['git', 'checkout', '--quiet', '--orphan', $branch . '-' . getmypid()], $clone);
}

foreach (new DirectoryIterator($clone) as $file) {
    if (!$file->isDot() && $file->getFilename() !== '.git') { // a file in a worktree
        $run(['rm', '-rf', $file->getPathname()]);
    }
}
foreach (new DirectoryIterator($work . '/tree') as $file) {
    if (!$file->isDot()) {
        $run(['mv', $file->getPathname(), $clone . '/' . $file->getFilename()]);
    }
}

$run(['git', 'add', '--all'], $clone);
$run([
    'git',
    '-c', 'user.name=github-actions[bot]',
    '-c', 'user.email=41898282+github-actions[bot]@users.noreply.github.com',
    'commit', '--quiet', '--message', "Psalm $version",
], $clone);

if ($dry_run) {
    echo $run(['git', 'show', '--stat', '--format=%s', 'HEAD'], $clone);
    echo "Would push this to " . $branch . PHP_EOL;
} else {
    $run(['git', 'push', '--quiet', 'origin', 'HEAD:refs/heads/' . $branch], $clone);
    echo "Published Psalm $version on " . $branch . PHP_EOL;
}

$run(['git', 'worktree', 'remove', '--force', $clone]);
