<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\Internal\ExecutionEnvironment\Repository;
use Psalm\SourceControl\Git\CommitInfo;
use Psalm\SourceControl\Git\GitInfo;

use function assert;
use function file_get_contents;
use function in_array;
use function json_decode;
use function str_contains;
use function str_replace;
use function strtotime;

use const JSON_THROW_ON_ERROR;

/**
 * GitHub Actions: "GITHUB_ACTIONS" must be set.
 *
 * The event that started the workflow tells the head commit, and for a pull request the number and the
 * repository it comes from, which is told apart from the one the workflow runs in, as for the other CI
 * services: a report is only linked to the pull request of its own repository. The checkout of a pull
 * request is a merge commit that is not part of it, so its head commit is sent too.
 *
 * @internal
 */
final class GithubActions implements CiProvider
{
    private const FLAG = 'GITHUB_ACTIONS';
    private const REF = 'GITHUB_REF';
    private const REPOSITORY = 'GITHUB_REPOSITORY';
    private const EVENT_NAME = 'GITHUB_EVENT_NAME';
    private const EVENT_PATH = 'GITHUB_EVENT_PATH';

    private const PULL_REQUEST_EVENTS = ['pull_request', 'pull_request_target'];

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function detects(Env $env, ?GitInfo $git_info): bool
    {
        return $env->get(self::FLAG) !== null;
    }

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): BuildInfo
    {
        $ref = $env->get(self::REF) ?? '';
        $branch = self::getBranch($ref);

        $git = null;
        $pull_request = null;

        $event_path = $env->get(self::EVENT_PATH);
        if ($event_path !== null) {
            $event_json = file_get_contents($event_path);
            assert($event_json !== false);
            /** @var array */
            $event_data = json_decode($event_json, true, 512, JSON_THROW_ON_ERROR);

            $git = self::getHeadCommitGit($event_data, $branch);

            if (in_array($env->get(self::EVENT_NAME), self::PULL_REQUEST_EVENTS, true)) {
                $pull_request = self::getPullRequest($event_data);

                if ($pull_request !== null) {
                    $git = self::getPullRequestGit($event_data) ?? $git;
                }
            }
        }

        return new BuildInfo(
            ciName: 'github-actions',
            branch: $branch,
            repository: $env->repository(self::REPOSITORY),
            pullRequest: $pull_request,
            raw: $env->pick(self::FLAG, self::REF),
            git: $git,
        );
    }

    /**
     * @psalm-pure
     */
    private static function getBranch(string $ref): ?string
    {
        if (str_contains($ref, 'refs/heads/')) {
            $ref = str_replace('refs/heads/', '', $ref);
        } elseif (str_contains($ref, 'refs/tags/')) {
            $ref = str_replace('refs/tags/', '', $ref);
        }

        return $ref === '' ? null : $ref;
    }

    /**
     * The git info of the head commit of a "push" event.
     */
    private static function getHeadCommitGit(array $event_data, ?string $branch): ?GitInfo
    {
        if (!isset($event_data['head_commit'])) {
            return null;
        }

        /**
         * @var array{
         *    id: string,
         *    author: array{name: string, email: string},
         *    committer: array{name: string, email: string},
         *    message: string,
         *    timestamp: string
         * } $head_commit_data
         */
        $head_commit_data = $event_data['head_commit'];

        return new GitInfo(
            $branch ?? '',
            (new CommitInfo())
                ->setId($head_commit_data['id'])
                ->setAuthorName($head_commit_data['author']['name'])
                ->setAuthorEmail($head_commit_data['author']['email'])
                ->setCommitterName($head_commit_data['committer']['name'])
                ->setCommitterEmail($head_commit_data['committer']['email'])
                ->setMessage($head_commit_data['message'])
                ->setDate((int) strtotime($head_commit_data['timestamp'])),
            [],
        );
    }

    /**
     * The pull request of a "pull_request" or "pull_request_target" event.
     *
     * @psalm-pure
     */
    private static function getPullRequest(array $event_data): ?PullRequest
    {
        /**
         * @var array{
         *    number?: int,
         *    pull_request?: array{
         *        number?: int,
         *        head?: array{repo?: array{name?: string, owner?: array{login?: string}}|null}
         *    }
         * } $event_data
         */
        $number = $event_data['pull_request']['number'] ?? $event_data['number'] ?? null;
        if ($number === null) {
            return null;
        }

        $head = $event_data['pull_request']['head']['repo'] ?? [];
        $head_owner = $head['owner']['login'] ?? '';
        $head_name = $head['name'] ?? '';

        return PullRequest::fromNumber(
            (string) $number,
            $head_owner !== '' && $head_name !== '' ? new Repository($head_owner, $head_name) : null,
        );
    }

    /**
     * The git info of the head commit of a pull request, which is not the checked out one.
     *
     * @psalm-pure
     */
    private static function getPullRequestGit(array $event_data): ?GitInfo
    {
        /**
         * @var array{
         *    pull_request?: array{head?: array{ref?: string, sha?: string}}
         * } $event_data
         */
        $head = $event_data['pull_request']['head'] ?? [];

        $head_sha = $head['sha'] ?? '';
        if ($head_sha === '') {
            return null;
        }

        return new GitInfo($head['ref'] ?? '', (new CommitInfo())->setId($head_sha), []);
    }
}
