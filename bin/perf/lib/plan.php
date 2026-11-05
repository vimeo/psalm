<?php

declare(strict_types=1);

const RUNS_PER_PROJECT = 4;
const JOB_SLACK_MINUTES = 5;

/**
 * @param list<string> $args
 */
function runPlan(array $args): int
{
    $options = parseOptions($args, ['corpus']);
    requireOptions($options, ['corpus']);
    echo json_encode(buildPlan(readJsonFile($options['corpus'])), JSON_UNESCAPED_SLASHES), "\n";

    return 0;
}

/** "12G" / "512M" to MiB. */
function memoryLimitMb(string $limit): int
{
    return (int) $limit * (str_ends_with(strtoupper($limit), 'G') ? 1024 : 1);
}

/**
 * Runner budget: one install plus up to four analyses per project.
 *
 * @param list<string> $members
 * @param array<mixed> $projects
 */
function jobMinutes(array $members, array $projects, int $installSeconds): int
{
    $seconds = 0;
    foreach ($members as $name) {
        $seconds += $installSeconds + RUNS_PER_PROJECT * (int) ($projects[$name]['timeout'] ?? 0);
    }

    return (int) ceil($seconds / 60) + JOB_SLACK_MINUTES;
}

/**
 * Workflow plan: matrix jobs plus what compare needs to judge crashes.
 *
 * @param array<mixed> $corpus
 * @return array<string, mixed>
 */
function buildPlan(array $corpus): array
{
    $problems = corpusProblems($corpus);
    if ($problems !== []) {
        throw new RuntimeException("invalid corpus:\n" . implode("\n", $problems));
    }

    $jobs = [];
    foreach ($corpus['jobs'] as $job => $members) {
        $jobs[] = [
            'job' => (string) $job,
            'projects' => $members,
            'timeout_minutes' => jobMinutes($members, $corpus['projects'], $corpus['install_timeout']),
        ];
    }

    return [
        'memory_limit_mb' => memoryLimitMb($corpus['memory_limit']),
        'timeouts' => array_map(static fn(array $p): int => $p['timeout'], $corpus['projects']),
        'jobs' => $jobs,
    ];
}

/**
 * Only what would silently skip a project or crash.
 *
 * @param array<mixed> $corpus
 * @return list<string>
 */
function corpusProblems(array $corpus): array
{
    $problems = is_int($corpus['install_timeout'] ?? null) && $corpus['install_timeout'] > 0
        ? []
        : ['install_timeout must be a positive integer'];
    $seen = [];
    foreach ($corpus['jobs'] as $job => $members) {
        foreach ($members as $name) {
            if (!isset($corpus['projects'][$name])) {
                $problems[] = "job {$job}: unknown project {$name}";
            }
            $seen[$name] = ($seen[$name] ?? 0) + 1;
        }
    }

    foreach ($corpus['projects'] as $name => $project) {
        if (($seen[$name] ?? 0) !== 1) {
            $problems[] = "project {$name}: must be in exactly one job";
        }
        foreach (['repo', 'ref', 'timeout'] as $key) {
            if (!isset($project[$key])) {
                $problems[] = "project {$name}: missing {$key}";
            }
        }
        if (($project['psalm']['dirs'] ?? []) === []) {
            $problems[] = "project {$name}: missing psalm.dirs";
        }
    }

    return $problems;
}
