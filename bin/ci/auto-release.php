<?php

/**
 * Releases the given branches of Psalm when they have unreleased commits and every check of their head passed.
 *
 * Usage: php bin/ci/auto-release.php [--dry-run] <branch>...
 *
 * Run hourly by .github/workflows/auto-release.yml. For each branch:
 *  - the last release is the highest version tagged on the branch (master also contains the 6.x tags);
 *  - nothing happens if the branch has no commits since, or if a workflow run or a commit status of its head
 *    is still running, failed, or the unit tests haven't run yet. A failed check is reported as a warning, and
 *    fails the run once the head has been blocked for a day, so that the maintainers are notified;
 *  - the next version keeps the readiness of the last one: 7.0.0-alpha1 is followed by 7.0.0-alpha2, likewise
 *    for betas and release candidates, and a stable 7.0.0 by 7.1.0 if a feature or deprecation was merged, else
 *    by 7.0.1. A removal needs a new major version, which is left to the maintainers;
 *  - the release notes are the ones GitHub generates from the labels of the merged pull requests
 *    (.github/release.yml);
 *  - a release made with the workflow's token doesn't trigger other workflows, so the phar and docker builds
 *    are dispatched on the new tag.
 *
 * Needs the GitHub CLI, authenticated (GH_TOKEN), and GITHUB_REPOSITORY (default: vimeo/psalm).
 */

declare(strict_types=1);

$required_workflows = ['Run unit tests', 'Run unit tests on Windows'];
$own_workflow = 'Automatic release';
$dispatched_workflows = ['build-phar.yml', 'build-docker.yml'];
$version_pattern = '/^(?<major>\d+)\.(?<minor>\d+)\.(?<patch>\d+)'
    . '(?:-(?<stage>alpha|beta|rc)(?<dot>\.?)(?<number>\d+))?$/';

$args = array_slice($argv, 1);
$dry_run = in_array('--dry-run', $args, true);
$branches = array_values(array_filter($args, static fn(string $arg): bool => $arg !== '--dry-run'));
if ($branches === []) {
    fwrite(STDERR, 'Usage: ' . $argv[0] . ' [--dry-run] <branch>...' . PHP_EOL);
    exit(1);
}

$repository = getenv('GITHUB_REPOSITORY') ?: 'vimeo/psalm';

/** @param non-empty-list<string> $command */
$run = static function (array $command): string {
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => STDERR], $pipes);
    if ($process === false) {
        throw new RuntimeException('Cannot run ' . implode(' ', $command));
    }

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    if (proc_close($process) !== 0) {
        throw new RuntimeException('Failed: ' . implode(' ', $command));
    }

    return $output;
};

$api = static fn(string $path): array => json_decode(
    $run(['gh', 'api', "repos/$repository/$path"]),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

// the highest version tagged on the branch
$getLastTag = static function (string $ref) use ($run, $version_pattern): ?string {
    $tags = array_filter(
        explode("\n", trim($run(['git', 'tag', '--merged', $ref]))),
        static fn(string $tag): bool => preg_match($version_pattern, $tag) === 1,
    );
    usort($tags, static fn(string $a, string $b): int => version_compare($b, $a));

    return $tags[0] ?? null;
};

// why the commit can't be released yet, and whether that's because a check failed rather than hasn't finished,
// or null if every workflow run and commit status of it passed
/** @return ?array{bool, string} */
$getChecksFailure = static function (string $sha) use ($api, $required_workflows, $own_workflow): ?array {
    // the latest attempt of each workflow
    $latest = [];
    foreach ($api("actions/runs?head_sha=$sha&event=push&per_page=100")['workflow_runs'] as $workflow_run) {
        $name = $workflow_run['name'];
        if ($name !== $own_workflow
            && (!isset($latest[$name]) || $workflow_run['created_at'] > $latest[$name]['created_at'])
        ) {
            $latest[$name] = $workflow_run;
        }
    }

    foreach ($required_workflows as $name) {
        if (!isset($latest[$name])) {
            return [false, "$name hasn't run"];
        }
    }

    foreach ($latest as $name => $workflow_run) {
        if ($workflow_run['status'] !== 'completed') {
            return [false, "$name is {$workflow_run['status']}"];
        }

        if (!in_array($workflow_run['conclusion'], ['success', 'skipped', 'neutral'], true)) {
            return [true, "$name: {$workflow_run['conclusion']} ({$workflow_run['html_url']})"];
        }
    }

    // the statuses of other CI services, such as CircleCI
    $status = $api("commits/$sha/status");
    if ($status['total_count'] > 0 && $status['state'] !== 'success') {
        return [$status['state'] !== 'pending', implode(', ', array_map(
            static fn(array $context): string => "{$context['context']}: {$context['state']}",
            array_filter($status['statuses'], static fn(array $context): bool => $context['state'] !== 'success'),
        ))];
    }

    return null;
};

// the version following $tag at the same readiness: the next alpha, beta or release candidate, or for a stable
// version the next minor one if $minor, else the next patch one
$getNextVersion = static function (string $tag, bool $minor) use ($version_pattern): string {
    preg_match($version_pattern, $tag, $matches);

    if (($matches['stage'] ?? '') !== '') {
        return "{$matches['major']}.{$matches['minor']}.{$matches['patch']}-{$matches['stage']}{$matches['dot']}"
            . ((int) $matches['number'] + 1);
    }

    return $minor
        ? $matches['major'] . '.' . ((int) $matches['minor'] + 1) . '.0'
        : "{$matches['major']}.{$matches['minor']}." . ((int) $matches['patch'] + 1);
};

$isPrerelease = static fn(string $version): bool => preg_match('/-(alpha|beta|rc)/', $version) === 1;

$generateNotes = static fn(string $tag, string $target, string $previous_tag): string => $run([
    'gh', 'api', "repos/$repository/releases/generate-notes",
    '-f', "tag_name=$tag",
    '-f', "target_commitish=$target",
    '-f', "previous_tag_name=$previous_tag",
    '--jq', '.body',
]);

// a branch whose head has failed checks for this long fails the run, so that the maintainers are notified
$max_blocked_seconds = 24 * 60 * 60;
$stuck = false;

foreach ($branches as $branch) {
    echo "== $branch" . PHP_EOL;

    $run(['git', 'fetch', '--quiet', '--force', '--tags', 'origin', "+refs/heads/$branch:refs/remotes/origin/$branch"]);
    $head = trim($run(['git', 'rev-parse', "origin/$branch"]));

    $last_tag = $getLastTag("origin/$branch");
    if ($last_tag === null) {
        echo "No release tag on $branch" . PHP_EOL;
        continue;
    }

    $unreleased = (int) trim($run(['git', 'rev-list', '--count', "$last_tag..$head"]));
    if ($unreleased === 0) {
        echo "Nothing to release since $last_tag" . PHP_EOL;
        continue;
    }

    $failure = $getChecksFailure($head);
    if ($failure !== null) {
        [$failed, $reason] = $failure;
        if (!$failed) {
            echo "Not releasing $head ($unreleased commits since $last_tag) yet: $reason" . PHP_EOL;
            continue;
        }

        $blocked_seconds = time() - (int) trim($run(['git', 'log', '-1', '--format=%ct', $head]));
        $level = $blocked_seconds > $max_blocked_seconds ? 'error' : 'warning';
        $stuck = $stuck || $level === 'error';
        echo "::$level::Not releasing $branch at $head ($unreleased commits since $last_tag, committed "
            . intdiv($blocked_seconds, 3600) . " hours ago): $reason" . PHP_EOL;
        continue;
    }

    $next = $getNextVersion($last_tag, false);
    $notes = $generateNotes($next, $head, $last_tag);

    if (!$isPrerelease($next)) {
        if (str_contains($notes, '### Removed')) {
            echo "::warning::$branch has removals since $last_tag: release a new major version by hand" . PHP_EOL;
            continue;
        }

        if (str_contains($notes, '### Features') || str_contains($notes, '### Deprecations')) {
            $next = $getNextVersion($last_tag, true);
            $notes = $generateNotes($next, $head, $last_tag);
        }
    }

    echo "Releasing $next at $head ($unreleased commits since $last_tag)" . PHP_EOL . $notes . PHP_EOL;
    if ($dry_run) {
        continue;
    }

    // GitHub marks as latest the highest stable release, not one of an older branch
    $latest = !$isPrerelease($next) && version_compare(
        $next,
        trim($run(['gh', 'api', "repos/$repository/releases/latest", '--jq', '.tag_name'])),
    ) > 0;

    $notes_file = tempnam(sys_get_temp_dir(), 'notes');
    file_put_contents($notes_file, $notes);

    $run([
        'gh', 'release', 'create', $next,
        '--repo', $repository,
        '--target', $head,
        '--title', $next,
        '--notes-file', $notes_file,
        '--latest=' . ($latest ? 'true' : 'false'),
        ...($isPrerelease($next) ? ['--prerelease'] : []),
    ]);

    foreach ($dispatched_workflows as $workflow) {
        $run(['gh', 'workflow', 'run', $workflow, '--repo', $repository, '--ref', $next]);
    }
}

exit($stuck ? 1 : 0);
