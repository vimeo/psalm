<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\Internal\ExecutionEnvironment\Repository;
use Psalm\SourceControl\Git\GitInfo;

use function array_unshift;
use function preg_match;

/**
 * A CI service Psalm does not know, when the environment says it is CI ("CI" or "TF_BUILD" is set) and
 * the checkout has a GitHub remote, which tells the repository. The providers of the services Psalm
 * knows come first.
 *
 * The branch is the one of the checkout, or, when its HEAD is detached, the one the common CI services
 * put in their variables. The pull request number is read the same way, when there is one.
 *
 * @psalm-immutable
 * @internal
 */
final class GenericCi implements CiProvider
{
    private const CI_FLAG = 'CI';
    private const AZURE_PIPELINES_FLAG = 'TF_BUILD';
    private const BRANCH = [
        'CI_COMMIT_BRANCH',
        'CI_COMMIT_REF_NAME',
        'BITBUCKET_BRANCH',
        'BUILDKITE_BRANCH',
        'BUILD_SOURCEBRANCHNAME',
        'DRONE_BRANCH',
    ];
    private const PULL_REQUEST = [
        'CI_MERGE_REQUEST_IID',
        'BITBUCKET_PR_ID',
        'BUILDKITE_PULL_REQUEST',
        'SYSTEM_PULLREQUEST_PULLREQUESTNUMBER',
        'CI_COMMIT_PULL_REQUEST',
        'DRONE_PULL_REQUEST',
    ];

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if ((!$env->isTruthy(self::CI_FLAG) && $env->get(self::AZURE_PIPELINES_FLAG) === null) || $git_info === null) {
            return null;
        }

        $repository = self::getGithubRepository($git_info);
        if ($repository === null) {
            return null;
        }

        $branch = $git_info->getBranch();
        if ($branch === '' || $branch[0] === '(') {
            $branch = $env->get(...self::BRANCH);
        }

        return new BuildInfo(
            ciName: 'generic',
            branch: $branch,
            repository: $repository,
            pullRequest: PullRequest::fromNumber($env->get(...self::PULL_REQUEST), $repository),
        );
    }

    /**
     * The owner and the name of the GitHub repository of the "origin" remote, or of the first remote
     * that is one: github.com is the only host Shepherd knows.
     *
     * @psalm-mutation-free
     */
    private static function getGithubRepository(GitInfo $git_info): ?Repository
    {
        $urls = [];
        foreach ($git_info->getRemotes() as $remote) {
            $url = $remote->getUrl();

            if ($url === null) {
                continue;
            }

            if ($remote->getName() === 'origin') {
                array_unshift($urls, $url);
            } else {
                $urls[] = $url;
            }
        }

        foreach ($urls as $url) {
            if (preg_match(
                '#^(?:(?:https?|ssh|git)://(?:[^/]*@)?github\.com(?::\d+)?/|(?:[^@/:]+@)?github\.com:)'
                . '([\w.-]+)/([\w.-]+?)(?:\.git)?/?$#i',
                $url,
                $matches,
            ) === 1) {
                return new Repository($matches[1], $matches[2]);
            }
        }

        return null;
    }
}
