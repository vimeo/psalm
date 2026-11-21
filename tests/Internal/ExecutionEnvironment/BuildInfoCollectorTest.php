<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\ExecutionEnvironment;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\ExecutionEnvironment\BuildInfoCollector;
use Psalm\SourceControl\Git\CommitInfo;
use Psalm\SourceControl\Git\GitInfo;
use Psalm\SourceControl\Git\RemoteInfo;
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

    public function testGitlabCiEnvironmentWithADetachedHeadIsAGenericCi(): void
    {
        $info = (new BuildInfoCollector([
            'CI' => 'true',
            'CI_COMMIT_REF_NAME' => 'feature',
            'CI_MERGE_REQUEST_IID' => '7',
        ]))->collect($this->git('(HEAD detached at 0123456)', ['origin' => 'https://gitlab-ci-token:s3cret@github.com/psalm/example.git']));

        self::assertSame(
            [
                'CI_NAME' => 'generic',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'feature',
                'CI_PR_NUMBER' => '7',
                'CI_PR_REPO_OWNER' => 'psalm',
                'CI_PR_REPO_NAME' => 'example',
            ],
            $info,
        );
    }

    public function testAzurePipelinesEnvironmentIsAGenericCi(): void
    {
        $info = (new BuildInfoCollector([
            'TF_BUILD' => 'True',
            'BUILD_SOURCEBRANCHNAME' => 'master',
            'SYSTEM_PULLREQUEST_PULLREQUESTNUMBER' => '9',
        ]))->collect($this->git('(no branch)', ['origin' => 'https://github.com/psalm/example']));

        self::assertSame('generic', $info['CI_NAME']);
        self::assertSame('master', $info['CI_BRANCH']);
        self::assertSame('9', $info['CI_PR_NUMBER']);
    }

    public function testGenericCiUsesTheBranchOfTheCheckoutWhenItIsNotDetached(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true', 'BITBUCKET_BRANCH' => 'other']))
            ->collect($this->git('master', ['origin' => 'git@github.com:psalm/example.git']));

        self::assertSame('master', $info['CI_BRANCH']);
        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
    }

    public function testGenericCiWithADetachedHeadAndNoBranchVariableSendsNoBranch(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))
            ->collect($this->git('(HEAD detached at 0123456)', ['origin' => 'git@github.com:psalm/example.git']));

        self::assertSame('generic', $info['CI_NAME']);
        self::assertArrayNotHasKey('CI_BRANCH', $info);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function provideNoPullRequestValue(): iterable
    {
        yield 'false' => ['false'];
        yield 'zero' => ['0'];
        yield 'empty' => [''];
    }

    #[DataProvider('provideNoPullRequestValue')]
    public function testGenericCiSendsNoPullRequestWhenTheVariableSaysThereIsNone(string $value): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true', 'BUILDKITE_PULL_REQUEST' => $value]))
            ->collect($this->git('master', ['origin' => 'git@github.com:psalm/example.git']));

        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     * @psalm-pure
     */
    public static function provideGithubRemoteUrl(): iterable
    {
        yield 'scp-like' => ['git@github.com:psalm/example.git', 'psalm', 'example'];
        yield 'scp-like without .git' => ['git@github.com:psalm/example', 'psalm', 'example'];
        yield 'https' => ['https://github.com/psalm/example.git', 'psalm', 'example'];
        yield 'https without .git' => ['https://github.com/psalm/example', 'psalm', 'example'];
        yield 'https with a trailing slash' => ['https://github.com/psalm/example/', 'psalm', 'example'];
        yield 'https with a token' => ['https://x-access-token:ghs_abc@github.com/psalm/example.git', 'psalm', 'example'];
        yield 'https with a user' => ['https://user@github.com/psalm/example.git', 'psalm', 'example'];
        yield 'ssh' => ['ssh://git@github.com/psalm/example.git', 'psalm', 'example'];
        yield 'ssh with a port' => ['ssh://git@github.com:22/psalm/example', 'psalm', 'example'];
        yield 'dots and dashes' => ['https://github.com/psalm-dev/psalm.dev.git', 'psalm-dev', 'psalm.dev'];
        yield 'upper case host' => ['https://GitHub.com/psalm/example.git', 'psalm', 'example'];
    }

    #[DataProvider('provideGithubRemoteUrl')]
    public function testGenericCiReadsTheRepositoryFromTheGithubRemote(string $url, string $owner, string $name): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect($this->git('master', ['origin' => $url]));

        self::assertSame($owner, $info['CI_REPO_OWNER']);
        self::assertSame($name, $info['CI_REPO_NAME']);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function provideNonGithubRemoteUrl(): iterable
    {
        yield 'gitlab' => ['git@gitlab.com:psalm/example.git'];
        yield 'bitbucket' => ['https://bitbucket.org/psalm/example.git'];
        yield 'self-hosted' => ['https://git.example.com/psalm/example.git'];
        yield 'host that ends like github.com' => ['https://notgithub.com/psalm/example.git'];
        yield 'github.com in the path' => ['https://git.example.com/github.com/psalm/example.git'];
        yield 'github.com in the credentials' => ['https://github.com@git.example.com/psalm/example.git'];
        yield 'no repository' => ['https://github.com/psalm'];
        yield 'a local path' => ['/srv/git/example.git'];
    }

    #[DataProvider('provideNonGithubRemoteUrl')]
    public function testGenericCiNeedsAGithubRemote(string $url): void
    {
        self::assertSame([], (new BuildInfoCollector(['CI' => 'true']))->collect($this->git('master', ['origin' => $url])));
    }

    public function testGenericCiPrefersTheOriginRemote(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect($this->git('master', [
            'fork' => 'https://github.com/contributor/example.git',
            'origin' => 'https://github.com/psalm/example.git',
        ]));

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
    }

    public function testGenericCiUsesTheFirstGithubRemoteWhenThereIsNoOrigin(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect($this->git('master', [
            'gitlab' => 'git@gitlab.com:psalm/example.git',
            'upstream' => 'https://github.com/psalm/example.git',
        ]));

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
    }

    public function testNothingIsCollectedOutsideACiService(): void
    {
        $git = $this->git('master', ['origin' => 'git@github.com:psalm/example.git']);

        self::assertSame([], (new BuildInfoCollector([]))->collect($git));
        self::assertSame([], (new BuildInfoCollector(['CI' => 'false']))->collect($git));
        self::assertSame([], (new BuildInfoCollector(['CI' => '']))->collect($git));
        self::assertSame([], (new BuildInfoCollector(['CI' => '0', 'TF_BUILD' => '']))->collect($git));
    }

    public function testNothingIsCollectedWithoutGitInfo(): void
    {
        self::assertSame([], (new BuildInfoCollector(['CI' => 'true']))->collect());
    }

    public function testAKnownCiComesBeforeAGenericOne(): void
    {
        $info = (new BuildInfoCollector($this->githubActions('push', 'pull_request') + [
            'CI' => 'true',
            'CI_COMMIT_PULL_REQUEST' => '7',
        ]))->collect($this->git('other', ['origin' => 'git@github.com:other/other.git']));

        self::assertSame('github-actions', $info['CI_NAME']);
        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('example', $info['CI_REPO_NAME']);
        self::assertSame('master', $info['CI_BRANCH']);
        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
    }

    /**
     * @param array<string, string> $remotes name => URL
     */
    private function git(string $branch, array $remotes): GitInfo
    {
        $remote_infos = [];
        foreach ($remotes as $name => $url) {
            $remote_infos[] = (new RemoteInfo())->setName($name)->setUrl($url);
        }

        return new GitInfo($branch, (new CommitInfo())->setId('0123456789abcdef0123456789abcdef01234567'), $remote_infos);
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
