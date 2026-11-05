<?php

/**
 * Applies the triage changes the maintainers queued with their Telegram bot (https://github.com/danog/psalm_bot):
 * closing and reopening issues and pull requests, adding and removing labels, setting the release label of a pull
 * request, and merging pull requests.
 *
 * Usage: php bin/ci/apply-queued-changes.php [--dry-run]
 *
 * Run every half hour by .github/workflows/apply-queued-changes.yml. The bot has no access to this repository: it
 * only queues changes, which this script fetches from QUEUE_URL with QUEUE_TOKEN and makes with the workflow's token.
 * The queue is trusted no more than needed:
 *  - only the changes above can be made, to issues and pull requests of this repository, with labels it has;
 *  - a merge needs an open, ready pull request with exactly one release label, whose head is still the commit the
 *    merge was queued at.
 * What was done, and what couldn't be, is reported back to QUEUE_URL/done, which tells whoever queued it.
 *
 * Each queue entry says what an issue or pull request should become, so a change GitHub already shows is skipped:
 * an entry applied twice (if reporting back failed) changes nothing the second time.
 *
 * Changes made with the workflow's token don't trigger other workflows, so after merging into a branch, the
 * workflows a push to it runs are dispatched on it, unless they already ran on its head (as they did if a run merged
 * but failed to report it, which the next run sees as an already merged pull request); bin/ci/auto-release.php
 * counts them as the checks of its head.
 *
 * Needs the GitHub CLI, authenticated (GH_TOKEN), GITHUB_REPOSITORY (default: vimeo/psalm), QUEUE_URL, QUEUE_TOKEN
 * and MERGE_METHOD (merge, squash or rebase; default: merge).
 */

declare(strict_types=1);

// the workflows that run on a push to any branch, and those that only run on pushes to master and 6.x
$push_workflows = ['ci.yml', 'windows-ci.yml', 'macos-scan.yml', 'shepherd.yml', 'build-stubs.yml'];
$release_branch_workflows = ['build-phar.yml', 'build-docker.yml'];
$release_branches = ['master', '6.x'];
$release_prefix = 'release:';
// queue entries handled per run at most; the rest wait for the next one
$max_changes = 100;

$dry_run = in_array('--dry-run', array_slice($argv, 1), true);
$repository = getenv('GITHUB_REPOSITORY') ?: 'vimeo/psalm';
$queue_url = getenv('QUEUE_URL') ?: throw new RuntimeException('QUEUE_URL is not set');
$queue_token = getenv('QUEUE_TOKEN') ?: throw new RuntimeException('QUEUE_TOKEN is not set');
$merge_method = getenv('MERGE_METHOD') ?: 'merge';
if (!in_array($merge_method, ['merge', 'squash', 'rebase'], true)) {
    throw new RuntimeException("Unknown MERGE_METHOD $merge_method");
}

/**
 * Runs gh; if it fails, throws the error GitHub gave.
 *
 * @param non-empty-list<string> $args
 */
$gh = static function (array $args): string {
    $process = proc_open(['gh', ...$args], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if ($process === false) {
        throw new RuntimeException('Cannot run gh');
    }

    $output = (string) stream_get_contents($pipes[1]);
    $errors = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);

    if (proc_close($process) !== 0) {
        // such as "gh: Pull Request is not mergeable (HTTP 405)"
        $lines = explode("\n", $errors);
        throw new RuntimeException(preg_replace('/^gh: /', '', end($lines)) ?: 'gh ' . $args[0] . ' failed');
    }

    return $output;
};

/**
 * Calls the API of this repository; field values that are lists become arrays.
 *
 * @param array<string, string|list<string>> $fields
 */
$api = static function (string $method, string $path, array $fields = []) use ($gh, $repository): mixed {
    $args = ['api', '--method', $method, "repos/$repository/$path"];
    foreach ($fields as $name => $value) {
        foreach (is_array($value) ? $value : [$value] as $item) {
            // -f, unlike -F, never reads a value starting with @ as a file name
            $args = [...$args, '-f', (is_array($value) ? "{$name}[]" : $name) . '=' . $item];
        }
    }

    $output = $gh($args);

    return $output === '' ? null : json_decode($output, true, 512, JSON_THROW_ON_ERROR);
};

/**
 * Calls the queue's API.
 *
 * @param ?array<string, mixed> $body
 */
$queue = static function (string $method, string $url, ?array $body = null) use ($queue_token): mixed {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_PROTOCOLS_STR, 'https');
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $queue_token",
        'Accept: application/json',
        'Content-Type: application/json',
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }

    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    if (!is_string($response) || $status !== 200) {
        $error = is_string($response) ? "HTTP $status" : curl_error($curl);
        throw new RuntimeException("$method $url failed: $error");
    }

    return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
};

$repository_labels = array_filter(explode("\n", $gh([
    'api', '--paginate', "repos/$repository/labels?per_page=100", '--jq', '.[].name',
])));

$isLabel = static fn(mixed $label): bool => is_string($label) && in_array($label, $repository_labels, true);
$isLabelList = static fn(mixed $labels): bool => is_array($labels) && array_is_list($labels)
    && array_filter($labels, static fn(mixed $label): bool => !$isLabel($label)) === [];

// why a queue entry can't be applied, or null if it can
$getInvalidReason = static function (mixed $change) use ($isLabel, $isLabelList, $release_prefix): ?string {
    if (!is_array($change)) {
        return 'not an object';
    }

    return match (true) {
        !is_int($change['number'] ?? null) || $change['number'] <= 0 => 'bad number',
        !is_int($change['revision'] ?? null) => 'bad revision',
        !in_array($change['state'] ?? null, [null, 'open', 'closed'], true) => 'bad state',
        !in_array($change['state_reason'] ?? null, [null, 'completed', 'not_planned'], true) => 'bad state reason',
        !$isLabelList($change['add_labels'] ?? []) => 'a label to add is not a label of the repository',
        !$isLabelList($change['remove_labels'] ?? []) => 'a label to remove is not a label of the repository',
        ($change['release_label'] ?? null) !== null
            && (!$isLabel($change['release_label']) || !str_starts_with($change['release_label'], $release_prefix))
            => 'the release label is not a release label of the repository',
        ($change['merge_sha'] ?? null) !== null
            && (!is_string($change['merge_sha']) || preg_match('/^[0-9a-f]{40}$/D', $change['merge_sha']) !== 1)
            => 'bad merge commit',
        default => null,
    };
};

$queued = $queue('GET', $queue_url);
if (!is_array($queued) || ($queued['repo'] ?? null) !== $repository || !is_array($queued['changes'] ?? null)) {
    throw new RuntimeException("The queue is not for $repository");
}

$results = [];
// the branches merged into, by name, also by an earlier run whose results weren't reported: their push workflows
// are dispatched if they haven't run on the head of the branch
$merged_into = [];

foreach (array_slice($queued['changes'], 0, $max_changes) as $change) {
    $reason = $getInvalidReason($change);
    if ($reason !== null) {
        echo '::warning::Skipping a queue entry: ' . $reason . PHP_EOL;
        if (is_array($change) && is_int($change['number'] ?? null) && is_int($change['revision'] ?? null)) {
            $results[] = [
                'number' => $change['number'],
                'revision' => $change['revision'],
                'applied' => [],
                'errors' => ["Not applied: $reason"],
            ];
        }
        continue;
    }

    /** @var array{
     *     number: positive-int,
     *     revision: int,
     *     state?: 'open'|'closed'|null,
     *     state_reason?: 'completed'|'not_planned'|null,
     *     add_labels?: list<string>,
     *     remove_labels?: list<string>,
     *     release_label?: ?string,
     *     merge_sha?: ?string,
     * } $change */
    $number = $change['number'];
    echo "== #$number: " . json_encode($change, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $applied = [];
    $errors = [];

    // makes a change, unless running dry
    $make = static function (string $description, callable $change) use (&$applied, &$errors, $dry_run): bool {
        echo ($dry_run ? 'Would ' . lcfirst($description) : $description) . PHP_EOL;
        if ($dry_run) {
            return true;
        }

        try {
            $change();
        } catch (RuntimeException $e) {
            echo "::warning::$description: {$e->getMessage()}" . PHP_EOL;
            $errors[] = "$description: {$e->getMessage()}";

            return false;
        }

        $applied[] = $description;

        return true;
    };

    try {
        $issue = $api('GET', "issues/$number");
        $pr = isset($issue['pull_request']) ? $api('GET', "pulls/$number") : null;
    } catch (RuntimeException $e) {
        $results[] = ['number' => $number, 'revision' => $change['revision'], 'applied' => [], 'errors' => [
            "Cannot read #$number: {$e->getMessage()}",
        ]];
        continue;
    }

    $merged = $pr !== null && $pr['merged'] === true;
    // pull requests are closed and reopened through their own endpoint
    $state_path = $pr !== null ? "pulls/$number" : "issues/$number";

    if (($change['state'] ?? null) === 'open' && $issue['state'] === 'closed') {
        if ($merged) {
            $errors[] = 'Reopen: merged pull requests cannot be reopened';
        } else {
            $make('Reopen', static fn() => $api('PATCH', $state_path, ['state' => 'open']));
        }
    }

    $labels = array_column($issue['labels'], 'name');
    $add = array_values(array_diff($change['add_labels'] ?? [], $labels));
    $remove = array_values(array_intersect($change['remove_labels'] ?? [], $labels));
    $release_label = $change['release_label'] ?? null;
    if ($release_label !== null) {
        $other_release_labels = array_filter(
            $labels,
            static fn(string $label): bool => str_starts_with($label, $release_prefix) && $label !== $release_label,
        );
        $remove = array_values(array_unique([...$remove, ...$other_release_labels]));
        if (!in_array($release_label, $labels, true) && !in_array($release_label, $add, true)) {
            $add[] = $release_label;
        }
    }

    foreach ($remove as $label) {
        $make(
            "Remove label $label",
            static fn() => $api('DELETE', "issues/$number/labels/" . rawurlencode($label)),
        );
    }
    if ($add !== []) {
        $make(
            (count($add) === 1 ? 'Add label ' : 'Add labels ') . implode(', ', $add),
            static fn() => $api('POST', "issues/$number/labels", ['labels' => $add]),
        );
    }

    $merge_sha = $change['merge_sha'] ?? null;
    if ($merge_sha !== null && $merged) {
        $merged_into[(string) $pr['base']['ref']] = true;
    } elseif ($merge_sha !== null) {
        try {
            $problem = $pr === null ? "#$number is an issue" : null;
            // the release labels as they are now, after the changes above
            $release_labels = $pr === null ? [] : array_filter(
                $dry_run
                    ? [...array_diff($labels, $remove), ...$add]
                    : array_column($api('GET', "issues/$number/labels"), 'name'),
                static fn(string $label): bool => str_starts_with($label, $release_prefix),
            );
            $problem ??= match (true) {
                $pr['state'] !== 'open' => 'the pull request is closed',
                $pr['draft'] === true => 'the pull request is a draft',
                count($release_labels) !== 1 => 'the pull request needs exactly one release label',
                $pr['head']['sha'] !== $merge_sha => 'commits were pushed since the merge was queued: queue it again',
                default => null,
            };
        } catch (RuntimeException $e) {
            $problem = $e->getMessage();
        }

        if ($problem !== null) {
            echo "::warning::Not merging #$number: $problem" . PHP_EOL;
            $errors[] = "Merge: $problem";
        } else {
            $base = (string) $pr['base']['ref'];
            // the head SHA makes GitHub refuse the merge if commits were pushed meanwhile
            $was_merged = $make(
                "Merge into $base",
                static fn() => $api('PUT', "pulls/$number/merge", [
                    'merge_method' => $merge_method,
                    'sha' => $merge_sha,
                ]),
            );
            if ($was_merged) {
                $merged_into[$base] = true;
            }
        }
    }

    if (($change['state'] ?? null) === 'closed' && $issue['state'] === 'open' && $merge_sha === null) {
        $state_reason = $pr === null ? ($change['state_reason'] ?? null) : null;
        $make(
            'Close' . ($state_reason !== null ? ' as ' . str_replace('_', ' ', $state_reason) : ''),
            static fn() => $api('PATCH', $state_path, ['state' => 'closed', ...($state_reason !== null
                ? ['state_reason' => $state_reason]
                : [])]),
        );
    }

    $results[] = ['number' => $number, 'revision' => $change['revision'], 'applied' => $applied, 'errors' => $errors];
}

if ($dry_run) {
    exit(0);
}

// dispatched before reporting: once reported, the merges leave the queue, and nothing would dispatch them again
$dispatched = true;
foreach (array_keys($merged_into) as $branch) {
    $workflows = in_array($branch, $release_branches, true)
        ? [...$push_workflows, ...$release_branch_workflows]
        : $push_workflows;
    try {
        $head = $api('GET', 'branches/' . rawurlencode($branch))['commit']['sha'];
    } catch (RuntimeException $e) {
        echo "::error::Cannot read $branch: {$e->getMessage()}" . PHP_EOL;
        $dispatched = false;
        continue;
    }

    foreach ($workflows as $workflow) {
        try {
            foreach (['push', 'workflow_dispatch'] as $event) {
                $runs = $api('GET', "actions/workflows/$workflow/runs?head_sha=$head&event=$event&per_page=1");
                if ($runs['total_count'] > 0) {
                    echo "$workflow already ran on $branch at $head" . PHP_EOL;
                    continue 2;
                }
            }

            echo "Dispatching $workflow on $branch at $head" . PHP_EOL;
            $gh(['workflow', 'run', $workflow, '--repo', $repository, '--ref', $branch]);
        } catch (RuntimeException $e) {
            echo "::error::Cannot dispatch $workflow on $branch: {$e->getMessage()}" . PHP_EOL;
            $dispatched = false;
        }
    }
}

// a failed dispatch fails the run, so that the maintainers are notified; the results are reported anyway, as the
// changes were made
if ($results !== []) {
    $queue('POST', rtrim($queue_url, '/') . '/done', ['results' => $results]);
}

exit($dispatched ? 0 : 1);
