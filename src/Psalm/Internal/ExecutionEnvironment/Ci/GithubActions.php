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
use function str_starts_with;
use function strtotime;
use function substr;

use const JSON_THROW_ON_ERROR;

/**
 * GitHub Actions: "GITHUB_ACTIONS" must be set.
 *
 * The event that started the workflow tells the head commit, and for a pull request the number and the
 * repository it comes from, which is told apart from the one the workflow runs in, as for the other CI
 * services: a report is only linked to the pull request of its own repository. The checkout of a pull
 * request is not a commit of it, so its head commit is sent too.
 *
 * @internal
 */
final class GithubActions implements CiProvider
{
    private const FLAG = 'GITHUB_ACTIONS';
    private const REF = 'GITHUB_REF';
    private const HEAD_REF = 'GITHUB_HEAD_REF';
    private const RUN_ID = 'GITHUB_RUN_ID';
    private const REPOSITORY = 'GITHUB_REPOSITORY';
    private const EVENT_NAME = 'GITHUB_EVENT_NAME';
    private const EVENT_PATH = 'GITHUB_EVENT_PATH';

    private const PULL_REQUEST_EVENTS = ['pull_request', 'pull_request_target'];

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if ($env->get(self::FLAG) === null) {
            return null;
        }

        $ref = $env->get(self::REF) ?? '';
        $branch = self::getBranch($ref);

        $is_pull_request_event = in_array($env->get(self::EVENT_NAME), self::PULL_REQUEST_EVENTS, true);

        $git = null;
        $pull_request = null;

        $event_path = $env->get(self::EVENT_PATH);
        if ($event_path !== null) {
            $event_json = file_get_contents($event_path);
            assert($event_json !== false);
            /** @var array */
            $event_data = json_decode($event_json, true, 512, JSON_THROW_ON_ERROR);

            $git = self::getHeadCommitGit($event_data, $branch);

            if ($is_pull_request_event) {
                $pull_request = self::getPullRequest($event_data);

                if ($pull_request !== null) {
                    $git = self::getPullRequestGit($event_data) ?? $git;

                    // GITHUB_REF is the merge commit ("pull_request") or the base branch ("pull_request_target"),
                    // so the branch the pull request comes from is sent instead. Shepherd compares the branch with the
                    // default one when no CI_PR_REPO_* is sent, so a pull request from a deleted fork sends none.
                    $branch = $pull_request->head === null ? null : ($env->get(self::HEAD_REF) ?? $branch);
                }
            }
        }

        return new BuildInfo(
            ciName: 'github-actions',
            branch: $branch,
            repository: $env->repository(self::REPOSITORY),
            pullRequest: $pull_request,
            jobId: $env->get(self::RUN_ID),
            raw: $env->pick(self::FLAG, self::REF),
            git: $git,
        );
    }

    /**
     * @psalm-pure
     */
    private static function getBranch(string $ref): ?string
    {
        // a tag is not a branch: Shepherd compares the branch with the default one of the repository
        if (str_starts_with($ref, 'refs/tags/')) {
            return null;
        }

        if (str_starts_with($ref, 'refs/heads/')) {
            $ref = substr($ref, 11);
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
        $head_owner = $head['owner']['login'] ?? null;
        $head_name = $head['name'] ?? null;

        return PullRequest::fromNumber((string) $number, Repository::fromParts($head_owner, $head_name));
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
