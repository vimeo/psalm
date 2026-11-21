<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\ExecutionEnvironment;

use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\ExecutionEnvironment\BuildInfoCollector;
use Psalm\SourceControl\Git\CommitInfo;
use Psalm\SourceControl\Git\GitInfo;
use Psalm\SourceControl\Git\RemoteInfo;
use Psalm\Tests\TestCase;

use function ksort;

use const DIRECTORY_SEPARATOR;

final class BuildInfoCollectorTest extends TestCase
{
    private const HEAD_SHA = '0123456789abcdef0123456789abcdef01234567';

    public function testGithubActionsPullRequestSendsTheNumberAndTheRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request')))->collect();

        self::assertSame('github-actions', $info['CI_NAME']);
        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('example', $info['CI_REPO_NAME']);
        self::assertSame('42', $info['CI_PR_NUMBER']);
        self::assertSame('psalm', $info['CI_PR_REPO_OWNER']);
        self::assertSame('example', $info['CI_PR_REPO_NAME']);
    }

    public function testGithubActionsPullRequestSendsItsHeadCommit(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request')))->collect();

        /** @var array{head: array{id: string}} $git */
        $git = $info['git'];
        self::assertSame(self::HEAD_SHA, $git['head']['id']);
    }

    public function testGithubActionsPullRequestTargetSendsTheNumber(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request_target', 'pull_request')))->collect();

        self::assertSame('42', $info['CI_PR_NUMBER']);
    }

    public function testGithubActionsPullRequestFromAForkSendsTheForkAsTheRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request_from_fork')))->collect();

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('contributor', $info['CI_PR_REPO_OWNER']);
        self::assertSame('example', $info['CI_PR_REPO_NAME']);
    }

    public function testGithubActionsPullRequestFromADeletedForkSendsNoRepositoryItComesFrom(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request_from_deleted_fork')))
            ->collect();

        self::assertSame('42', $info['CI_PR_NUMBER']);
        self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_NAME', $info);
    }

    public function testGithubActionsPushSendsNoPullRequest(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('push', 'pull_request')))->collect();

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
        ]))->collect(self::git('(HEAD detached at 0123456)', ['origin' => 'https://gitlab-ci-token:s3cret@github.com/psalm/example.git']));

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
        ]))->collect(self::git('(no branch)', ['origin' => 'https://github.com/psalm/example']));

        self::assertSame('generic', $info['CI_NAME']);
        self::assertSame('master', $info['CI_BRANCH']);
        self::assertSame('9', $info['CI_PR_NUMBER']);
    }

    public function testGenericCiUsesTheBranchOfTheCheckoutWhenItIsNotDetached(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true', 'BITBUCKET_BRANCH' => 'other']))
            ->collect(self::git('master', ['origin' => 'git@github.com:psalm/example.git']));

        self::assertSame('master', $info['CI_BRANCH']);
        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
    }

    public function testGenericCiWithADetachedHeadAndNoBranchVariableSendsNoBranch(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))
            ->collect(self::git('(HEAD detached at 0123456)', ['origin' => 'git@github.com:psalm/example.git']));

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
            ->collect(self::git('master', ['origin' => 'git@github.com:psalm/example.git']));

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
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect(self::git('master', ['origin' => $url]));

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
        self::assertSame([], (new BuildInfoCollector(['CI' => 'true']))->collect(self::git('master', ['origin' => $url])));
    }

    public function testGenericCiPrefersTheOriginRemote(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect(self::git('master', [
            'fork' => 'https://github.com/contributor/example.git',
            'origin' => 'https://github.com/psalm/example.git',
        ]));

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
    }

    public function testGenericCiUsesTheFirstGithubRemoteWhenThereIsNoOrigin(): void
    {
        $info = (new BuildInfoCollector(['CI' => 'true']))->collect(self::git('master', [
            'gitlab' => 'git@gitlab.com:psalm/example.git',
            'upstream' => 'https://github.com/psalm/example.git',
        ]));

        self::assertSame('psalm', $info['CI_REPO_OWNER']);
    }

    public function testNothingIsCollectedOutsideACiService(): void
    {
        $git = self::git('master', ['origin' => 'git@github.com:psalm/example.git']);

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
        $info = (new BuildInfoCollector(self::githubActions('push', 'pull_request') + [
            'CI' => 'true',
            'CI_COMMIT_PULL_REQUEST' => '7',
        ]))->collect(self::git('other', ['origin' => 'git@github.com:other/other.git']));

        self::assertSame('github-actions', $info['CI_NAME']);
        self::assertSame('psalm', $info['CI_REPO_OWNER']);
        self::assertSame('example', $info['CI_REPO_NAME']);
        self::assertSame('master', $info['CI_BRANCH']);
        self::assertArrayNotHasKey('CI_PR_NUMBER', $info);
    }

    /**
     * What is sent to Shepherd for every CI service, as the whole `build` array.
     *
     * @return iterable<string, array{array<string, string>, GitInfo|null, array<string, mixed>}>
     */
    public static function provideBuildInfo(): iterable
    {
        $commit_id = self::HEAD_SHA;
        $pull_request_git = [
            'branch' => 'feature',
            'head' => [
                'id' => $commit_id,
                'author_name' => null,
                'author_email' => null,
                'committer_name' => null,
                'committer_email' => null,
                'message' => null,
                'date' => null,
            ],
            'remotes' => [],
        ];

        yield 'Travis CI push' => [
            [
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'TRAVIS_REPO_SLUG' => 'psalm/example',
                'TRAVIS_PULL_REQUEST' => 'false',
                'TRAVIS_PULL_REQUEST_SLUG' => '',
                'TRAVIS_BRANCH' => 'master',
                'TRAVIS_TAG' => '',
            ],
            null,
            [
                'CI_JOB_ID' => '123',
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'CI_NAME' => 'travis-ci',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'Travis CI pull request from a fork' => [
            [
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'TRAVIS_REPO_SLUG' => 'psalm/example',
                'TRAVIS_PULL_REQUEST' => '42',
                'TRAVIS_PULL_REQUEST_SLUG' => 'contributor/example',
                'TRAVIS_BRANCH' => 'master',
                'TRAVIS_TAG' => '',
            ],
            null,
            [
                'CI_JOB_ID' => '123',
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'CI_NAME' => 'travis-ci',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_REPO_OWNER' => 'contributor',
                'CI_PR_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'Travis CI tag' => [
            [
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'TRAVIS_REPO_SLUG' => 'psalm/example',
                'TRAVIS_PULL_REQUEST' => 'false',
                'TRAVIS_PULL_REQUEST_SLUG' => '',
                'TRAVIS_BRANCH' => 'v1.0.0',
                'TRAVIS_TAG' => 'v1.0.0',
            ],
            null,
            [
                'CI_JOB_ID' => '123',
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'CI_NAME' => 'travis-ci',
                'TRAVIS_TAG' => 'v1.0.0',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
            ],
        ];

        yield 'CircleCI push' => [
            [
                'CIRCLECI' => 'true',
                'CIRCLE_BUILD_NUM' => '77',
                'CIRCLE_PROJECT_USERNAME' => 'psalm',
                'CIRCLE_PROJECT_REPONAME' => 'example',
                'CIRCLE_BRANCH' => 'master',
            ],
            null,
            [
                'CIRCLECI' => 'true',
                'CIRCLE_BUILD_NUM' => '77',
                'CI_BUILD_NUMBER' => '77',
                'CI_NAME' => 'circleci',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'CircleCI pull request from a fork' => [
            [
                'CIRCLECI' => 'true',
                'CIRCLE_BUILD_NUM' => '77',
                'CIRCLE_PROJECT_USERNAME' => 'psalm',
                'CIRCLE_PROJECT_REPONAME' => 'example',
                'CIRCLE_BRANCH' => 'pull/42',
                'CIRCLE_PR_USERNAME' => 'contributor',
                'CIRCLE_PR_REPONAME' => 'example',
                'CIRCLE_PR_NUMBER' => '42',
            ],
            null,
            [
                'CIRCLECI' => 'true',
                'CIRCLE_BUILD_NUM' => '77',
                'CI_BUILD_NUMBER' => '77',
                'CI_NAME' => 'circleci',
                'CI_PR_REPO_OWNER' => 'contributor',
                'CI_PR_REPO_NAME' => 'example',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'CI_BRANCH' => 'pull/42',
            ],
        ];

        yield 'AppVeyor push' => [
            [
                'APPVEYOR' => 'True',
                'APPVEYOR_BUILD_NUMBER' => '5',
                'APPVEYOR_JOB_NUMBER' => '1',
                'APPVEYOR_REPO_NAME' => 'psalm/example',
                'APPVEYOR_REPO_BRANCH' => 'master',
            ],
            null,
            [
                'CI_BUILD_NUMBER' => '5',
                'CI_JOB_ID' => '1',
                'APPVEYOR' => 'True',
                'APPVEYOR_BUILD_NUMBER' => '5',
                'APPVEYOR_JOB_NUMBER' => '1',
                'APPVEYOR_REPO_BRANCH' => 'master',
                'CI_NAME' => 'AppVeyor',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'AppVeyor pull request from a fork' => [
            [
                'APPVEYOR' => 'True',
                'APPVEYOR_BUILD_NUMBER' => '5',
                'APPVEYOR_JOB_NUMBER' => '1',
                'APPVEYOR_REPO_NAME' => 'psalm/example',
                'APPVEYOR_REPO_BRANCH' => 'master',
                'APPVEYOR_PULL_REQUEST_NUMBER' => '42',
                'APPVEYOR_PULL_REQUEST_HEAD_REPO_NAME' => 'contributor/example',
                'APPVEYOR_PULL_REQUEST_HEAD_REPO_BRANCH' => 'feature',
            ],
            null,
            [
                'CI_BUILD_NUMBER' => '5',
                'CI_JOB_ID' => '1',
                'CI_PR_NUMBER' => '42',
                'APPVEYOR' => 'True',
                'APPVEYOR_BUILD_NUMBER' => '5',
                'APPVEYOR_JOB_NUMBER' => '1',
                'APPVEYOR_REPO_BRANCH' => 'master',
                'CI_NAME' => 'AppVeyor',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_REPO_OWNER' => 'contributor',
                'CI_PR_REPO_NAME' => 'example',
                'CI_BRANCH' => 'feature',
            ],
        ];

        yield 'Jenkins' => [
            ['JENKINS_URL' => 'https://ci.example.com/', 'BUILD_NUMBER' => '9'],
            null,
            [
                'CI_BUILD_NUMBER' => '9',
                'CI_BUILD_URL' => 'https://ci.example.com/',
                'BUILD_NUMBER' => '9',
                'JENKINS_URL' => 'https://ci.example.com/',
                'CI_NAME' => 'jenkins',
            ],
        ];

        yield 'Scrutinizer without a pull request' => [
            [
                'SCRUTINIZER' => 'true',
                'SCRUTINIZER_INSPECTION_UUID' => 'aaaa-bbbb',
                'SCRUTINIZER_BRANCH' => 'master',
                'SCRUTINIZER_PROJECT' => 'g/psalm/example',
            ],
            null,
            [
                'CI_JOB_ID' => 'aaaa-bbbb',
                'CI_BRANCH' => 'master',
                'CI_NAME' => 'Scrutinizer',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
            ],
        ];

        yield 'Scrutinizer with a pull request' => [
            [
                'SCRUTINIZER' => 'true',
                'SCRUTINIZER_INSPECTION_UUID' => 'aaaa-bbbb',
                'SCRUTINIZER_BRANCH' => 'master',
                'SCRUTINIZER_PROJECT' => 'g/psalm/example',
                'SCRUTINIZER_PR_NUMBER' => '42',
                'SCRUTINIZER_PR_SOURCE_BRANCH' => 'feature',
            ],
            null,
            [
                'CI_JOB_ID' => 'aaaa-bbbb',
                'CI_BRANCH' => 'master',
                'CI_PR_NUMBER' => '42',
                'CI_NAME' => 'Scrutinizer',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_REPO_OWNER' => 'psalm',
                'CI_PR_REPO_NAME' => 'example',
            ],
        ];

        yield 'GitHub Actions push' => [
            self::githubActions('push', 'push'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'CI_BRANCH' => 'master',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/heads/master',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'git' => [
                    'branch' => 'master',
                    'head' => [
                        'id' => $commit_id,
                        'author_name' => 'Jane Author',
                        'author_email' => 'jane@example.com',
                        'committer_name' => 'Joe Committer',
                        'committer_email' => 'joe@example.com',
                        'message' => 'Fix the thing',
                        'date' => 1_777_638_600,
                    ],
                    'remotes' => [],
                ],
            ],
        ];

        yield 'GitHub Actions tag' => [
            self::githubActions('push', 'push', 'refs/tags/v1.0.0'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/tags/v1.0.0',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'git' => [
                    'branch' => '',
                    'head' => [
                        'id' => $commit_id,
                        'author_name' => 'Jane Author',
                        'author_email' => 'jane@example.com',
                        'committer_name' => 'Joe Committer',
                        'committer_email' => 'joe@example.com',
                        'message' => 'Fix the thing',
                        'date' => 1_777_638_600,
                    ],
                    'remotes' => [],
                ],
            ],
        ];

        yield 'GitHub Actions pull_request' => [
            self::githubActions('pull_request', 'pull_request', 'refs/pull/42/merge', 'feature'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'CI_BRANCH' => 'feature',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/pull/42/merge',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'CI_PR_REPO_OWNER' => 'psalm',
                'CI_PR_REPO_NAME' => 'example',
                'git' => $pull_request_git,
            ],
        ];

        yield 'GitHub Actions pull_request_target' => [
            self::githubActions('pull_request_target', 'pull_request', 'refs/heads/master', 'feature'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'CI_BRANCH' => 'feature',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/heads/master',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'CI_PR_REPO_OWNER' => 'psalm',
                'CI_PR_REPO_NAME' => 'example',
                'git' => $pull_request_git,
            ],
        ];

        yield 'GitHub Actions pull_request from a fork' => [
            self::githubActions('pull_request', 'pull_request_from_fork', 'refs/pull/42/merge', 'feature'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'CI_BRANCH' => 'feature',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/pull/42/merge',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'CI_PR_REPO_OWNER' => 'contributor',
                'CI_PR_REPO_NAME' => 'example',
                'git' => $pull_request_git,
            ],
        ];

        yield 'GitHub Actions pull_request from a deleted fork' => [
            self::githubActions('pull_request', 'pull_request_from_deleted_fork', 'refs/pull/42/merge', 'feature'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/pull/42/merge',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'git' => $pull_request_git,
            ],
        ];

        yield 'GitHub Actions pull_request_target from a deleted fork' => [
            self::githubActions('pull_request_target', 'pull_request_from_deleted_fork', 'refs/heads/master', 'feature'),
            null,
            [
                'CI_NAME' => 'github-actions',
                'GITHUB_ACTIONS' => 'true',
                'CI_JOB_ID' => '9876543210',
                'GITHUB_REF' => 'refs/heads/master',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_PR_NUMBER' => '42',
                'git' => $pull_request_git,
            ],
        ];

        yield 'generic CI with a detached HEAD' => [
            ['CI' => 'true', 'CI_COMMIT_REF_NAME' => 'feature', 'CI_MERGE_REQUEST_IID' => '7'],
            self::git('(HEAD detached at 0123456)', ['origin' => 'https://github.com/psalm/example.git']),
            [
                'CI_NAME' => 'generic',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'feature',
                'CI_PR_NUMBER' => '7',
                'CI_PR_REPO_OWNER' => 'psalm',
                'CI_PR_REPO_NAME' => 'example',
            ],
        ];

        yield 'generic CI on a branch' => [
            ['TF_BUILD' => 'True', 'BUILD_SOURCEBRANCHNAME' => 'other'],
            self::git('master', ['origin' => 'git@github.com:psalm/example.git']),
            [
                'CI_NAME' => 'generic',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'generic CI with a detached HEAD and no branch variable' => [
            ['CI' => 'true'],
            self::git('', ['origin' => 'git@github.com:psalm/example.git']),
            [
                'CI_NAME' => 'generic',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
            ],
        ];

        yield 'generic CI with a pull request variable that says there is none' => [
            ['CI' => 'true', 'BUILDKITE_PULL_REQUEST' => 'false', 'DRONE_PULL_REQUEST' => '5'],
            self::git('master', ['origin' => 'git@github.com:psalm/example.git']),
            [
                'CI_NAME' => 'generic',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
                'CI_BRANCH' => 'master',
            ],
        ];

        yield 'generic CI with a remote that is not GitHub' => [
            ['CI' => 'true'],
            self::git('master', ['origin' => 'git@gitlab.com:psalm/example.git']),
            [],
        ];

        yield 'generic CI without git info' => [['CI' => 'true'], null, []];

        yield 'no CI' => [
            ['CI' => 'false', 'TF_BUILD' => ''],
            self::git('master', ['origin' => 'git@github.com:psalm/example.git']),
            [],
        ];

        yield 'no CI variable at all' => [
            [],
            self::git('master', ['origin' => 'git@github.com:psalm/example.git']),
            [],
        ];

        yield 'Travis CI and Jenkins' => [
            [
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'TRAVIS_REPO_SLUG' => 'psalm/example',
                'TRAVIS_PULL_REQUEST' => '42',
                'TRAVIS_BRANCH' => 'master',
                'JENKINS_URL' => 'https://ci.example.com/',
                'BUILD_NUMBER' => '9',
            ],
            null,
            [
                'CI_BUILD_NUMBER' => '9',
                'CI_BUILD_URL' => 'https://ci.example.com/',
                'BUILD_NUMBER' => '9',
                'JENKINS_URL' => 'https://ci.example.com/',
                'CI_NAME' => 'jenkins',
            ],
        ];

        yield 'every known CI service' => [
            [
                'GITHUB_ACTIONS' => 'true',
                'GITHUB_REF' => 'refs/heads/master',
                'GITHUB_REPOSITORY' => 'psalm/example',
                'SCRUTINIZER' => 'true',
                'SCRUTINIZER_INSPECTION_UUID' => 'aaaa-bbbb',
                'SCRUTINIZER_PROJECT' => 'g/scrutinizer/example',
                'SCRUTINIZER_PR_NUMBER' => '1',
                'JENKINS_URL' => 'https://ci.example.com/',
                'BUILD_NUMBER' => '9',
                'APPVEYOR' => 'True',
                'APPVEYOR_BUILD_NUMBER' => '5',
                'CIRCLECI' => 'true',
                'CIRCLE_BUILD_NUM' => '77',
                'TRAVIS' => 'true',
                'TRAVIS_JOB_ID' => '123',
                'CI' => 'true',
            ],
            self::git('other', ['origin' => 'git@github.com:generic/example.git']),
            [
                'CI_NAME' => 'github-actions',
                'CI_BRANCH' => 'master',
                'GITHUB_ACTIONS' => 'true',
                'GITHUB_REF' => 'refs/heads/master',
                'CI_REPO_OWNER' => 'psalm',
                'CI_REPO_NAME' => 'example',
            ],
        ];

        yield 'GitHub Actions with only its flag' => [
            ['GITHUB_ACTIONS' => 'true'],
            null,
            ['CI_NAME' => 'github-actions', 'GITHUB_ACTIONS' => 'true'],
        ];

        yield 'Travis CI with a "false" flag' => [['TRAVIS' => 'false', 'TRAVIS_JOB_ID' => '123'], null, []];
        yield 'CircleCI with a "false" flag' => [['CIRCLECI' => 'false', 'CIRCLE_BUILD_NUM' => '77'], null, []];
        yield 'AppVeyor with a "false" flag' => [['APPVEYOR' => 'False', 'APPVEYOR_BUILD_NUMBER' => '5'], null, []];
        yield 'Scrutinizer with a "false" flag' => [['SCRUTINIZER' => 'false'], null, []];
        yield 'Travis CI without a job id' => [['TRAVIS' => 'true'], null, []];
        yield 'CircleCI without a build number' => [['CIRCLECI' => 'true'], null, []];
        yield 'AppVeyor without a build number' => [['APPVEYOR' => 'True'], null, []];
        yield 'Jenkins without a build number' => [['JENKINS_URL' => 'https://ci.example.com/'], null, []];
    }

    /**
     * @param array<string, string> $env
     * @param array<string, mixed> $expected
     */
    #[DataProvider('provideBuildInfo')]
    public function testCollectsTheBuildInfoOfEveryCiService(array $env, ?GitInfo $git, array $expected): void
    {
        $actual = (new BuildInfoCollector($env))->collect($git);

        // the order of the keys of a JSON object means nothing to Shepherd
        ksort($expected);
        ksort($actual);

        self::assertSame($expected, $actual);
    }

    public function testGithubActionsWithoutARunIdSendsNoJobId(): void
    {
        $env = self::githubActions('push', 'push');
        unset($env['GITHUB_RUN_ID']);

        self::assertArrayNotHasKey('CI_JOB_ID', (new BuildInfoCollector($env))->collect());
    }

    public function testGithubActionsPullRequestWithoutAHeadRefKeepsTheRefAsTheBranch(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request', 'refs/pull/42/merge')))
            ->collect();

        self::assertSame('refs/pull/42/merge', $info['CI_BRANCH']);
    }

    /**
     * @return iterable<string, array{string, string}>
     * @psalm-pure
     */
    public static function providePullRequestEventRef(): iterable
    {
        yield 'pull_request' => ['pull_request', 'refs/pull/42/merge'];
        yield 'pull_request_target' => ['pull_request_target', 'refs/heads/master'];
    }

    #[DataProvider('providePullRequestEventRef')]
    public function testGithubActionsPullRequestFromADeletedForkSendsNoBranch(string $event_name, string $ref): void
    {
        $env = self::githubActions($event_name, 'pull_request_from_deleted_fork', $ref, 'master');

        self::assertArrayNotHasKey('CI_BRANCH', (new BuildInfoCollector($env))->collect());
    }

    public function testGithubActionsPullRequestWithAnEmptyHeadRefKeepsTheRefAsTheBranch(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('pull_request', 'pull_request', 'refs/pull/42/merge', '')))
            ->collect();

        self::assertSame('refs/pull/42/merge', $info['CI_BRANCH']);
    }

    public function testGithubActionsPushIgnoresTheHeadRef(): void
    {
        $info = (new BuildInfoCollector(self::githubActions('push', 'push', 'refs/heads/master', 'feature')))->collect();

        self::assertSame('master', $info['CI_BRANCH']);
    }

    /**
     * @return iterable<string, array{string, string}>
     * @psalm-pure
     */
    public static function provideBranchRef(): iterable
    {
        yield 'branch' => ['refs/heads/master', 'master'];
        yield 'branch with a slash' => ['refs/heads/feature/login', 'feature/login'];
        yield 'branch with "refs/heads/" in its name' => ['refs/heads/feature/refs/heads/login', 'feature/refs/heads/login'];
        yield 'branch with "refs/tags/" in its name' => ['refs/heads/feature/refs/tags/login', 'feature/refs/tags/login'];
    }

    #[DataProvider('provideBranchRef')]
    public function testGithubActionsBranchIsTheRefWithoutItsPrefix(string $ref, string $branch): void
    {
        $info = (new BuildInfoCollector(self::githubActions('push', 'push', $ref)))->collect();

        self::assertSame($branch, $info['CI_BRANCH']);
    }

    /**
     * @return iterable<string, array{string}>
     * @psalm-pure
     */
    public static function provideScrutinizerProjectThatIsNotOnGithub(): iterable
    {
        yield 'Bitbucket' => ['b/psalm/example'];
        yield 'no host' => ['psalm/example'];
        yield 'no repository' => ['g/psalm'];
    }

    #[DataProvider('provideScrutinizerProjectThatIsNotOnGithub')]
    public function testScrutinizerSendsNoRepositoryForAProjectThatIsNotOnGithub(string $project): void
    {
        foreach (['', '42'] as $pull_request_number) {
            $info = (new BuildInfoCollector([
                'SCRUTINIZER' => 'true',
                'SCRUTINIZER_BRANCH' => 'master',
                'SCRUTINIZER_PROJECT' => $project,
                'SCRUTINIZER_PR_NUMBER' => $pull_request_number,
            ]))->collect();

            self::assertSame('Scrutinizer', $info['CI_NAME']);
            self::assertArrayNotHasKey('CI_REPO_OWNER', $info);
            self::assertArrayNotHasKey('CI_REPO_NAME', $info);
            self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
            self::assertArrayNotHasKey('CI_PR_REPO_NAME', $info);
        }
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     * @psalm-pure
     */
    public static function provideEnvWithAMalformedRepositorySlug(): iterable
    {
        yield 'Travis CI' => [[
            'TRAVIS' => 'true',
            'TRAVIS_JOB_ID' => '123',
            'TRAVIS_REPO_SLUG' => 'psalm',
            'TRAVIS_PULL_REQUEST' => '42',
            'TRAVIS_PULL_REQUEST_SLUG' => 'contributor',
            'TRAVIS_BRANCH' => 'master',
        ]];

        yield 'AppVeyor' => [[
            'APPVEYOR' => 'True',
            'APPVEYOR_BUILD_NUMBER' => '5',
            'APPVEYOR_JOB_NUMBER' => '1',
            'APPVEYOR_REPO_NAME' => 'psalm',
            'APPVEYOR_REPO_BRANCH' => 'master',
            'APPVEYOR_PULL_REQUEST_NUMBER' => '42',
            'APPVEYOR_PULL_REQUEST_HEAD_REPO_NAME' => 'contributor',
        ]];

        yield 'GitHub Actions' => [[
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REF' => 'refs/heads/master',
            'GITHUB_REPOSITORY' => 'psalm',
        ]];
    }

    /**
     * @param array<string, string> $env
     */
    #[DataProvider('provideEnvWithAMalformedRepositorySlug')]
    public function testARepositorySlugThatIsNotOwnerAndNameSendsNoRepository(array $env): void
    {
        $info = (new BuildInfoCollector($env))->collect();

        self::assertArrayHasKey('CI_NAME', $info);
        self::assertArrayNotHasKey('CI_REPO_OWNER', $info);
        self::assertArrayNotHasKey('CI_REPO_NAME', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_OWNER', $info);
        self::assertArrayNotHasKey('CI_PR_REPO_NAME', $info);
    }

    /**
     * @param array<string, string> $remotes name => URL
     */
    private static function git(string $branch, array $remotes): GitInfo
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
    private static function githubActions(
        string $event_name,
        string $event_fixture,
        string $ref = 'refs/heads/master',
        ?string $head_ref = null,
    ): array {
        return [
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_RUN_ID' => '9876543210',
            'GITHUB_REF' => $ref,
            'GITHUB_REPOSITORY' => 'psalm/example',
            'GITHUB_EVENT_NAME' => $event_name,
            'GITHUB_EVENT_PATH' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'GithubEvents'
                . DIRECTORY_SEPARATOR . $event_fixture . '.json',
        ] + ($head_ref === null ? [] : ['GITHUB_HEAD_REF' => $head_ref]);
    }
}
