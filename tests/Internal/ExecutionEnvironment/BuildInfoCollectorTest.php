<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\ExecutionEnvironment;

use Psalm\Internal\ExecutionEnvironment\BuildInfoCollector;
use Psalm\Tests\TestCase;

use const DIRECTORY_SEPARATOR;

final class BuildInfoCollectorTest extends TestCase
{
    private const HEAD_SHA = '0123456789abcdef0123456789abcdef01234567';

    public function testGithubActionsPullRequestSendsTheNumberAndTheRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('pull_request', 'pull_request')))->collect();

        self::assertSame('github-actions', $info['CI_NAME']);
        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('example', $info['CI_REPO_NAME']);
        self::assertSame('42', $info['CI_PR_NUMBER']);
        self::assertSame('psalm', $info['CI_PR_REPO_OWNER']);
        self::assertSame('example', $info['CI_PR_REPO_NAME']);
    }

    public function testGithubActionsPullRequestSendsItsHeadCommit(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('pull_request', 'pull_request')))->collect();

        /** @var array{head: array{id: string}} $git */
        $git = $info['git'];
        self::assertSame(self::HEAD_SHA, $git['head']['id']);
    }

    public function testGithubActionsPullRequestTargetSendsTheNumber(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('pull_request_target', 'pull_request')))->collect();

        self::assertSame('42', $info['CI_PR_NUMBER']);
    }

    public function testGithubActionsPullRequestFromAForkSendsTheForkAsTheRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('pull_request', 'pull_request_from_fork')))->collect();

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('contributor', $info['CI_PR_REPO_OWNER']);
        self::assertSame('example', $info['CI_PR_REPO_NAME']);
    }

    public function testGithubActionsPullRequestFromADeletedForkSendsNoRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('pull_request', 'pull_request_from_deleted_fork')))
            ->collect();

        self::assertSame('42', $info['CI_PR_NUMBER']);
        self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_NAME', $info);
    }

    public function testGithubActionsPushSendsNoPullRequest(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('push', 'pull_request')))->collect();

        self::assertSame('github-actions', $info['CI_NAME']);
        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
    }

    /**
     * @return array<string, string>
     * @psalm-pure
     */
    private function githubActions(string $event_name, string $event_fixture): array
    {
        return [
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REF' => 'refs/heads/master',
            'GITHUB_REPOSITORY' => 'psalm/example',
            'GITHUB_EVENT_NAME' => $event_name,
            'GITHUB_EVENT_PATH' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'GithubEvents'
                . DIRECTORY_SEPARATOR . $event_fixture . '.json',
        ];
    }
}
