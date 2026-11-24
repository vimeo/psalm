<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\Internal\ExecutionEnvironment\Repository;
use Psalm\SourceControl\Git\GitInfo;

/**
 * CircleCI: "CIRCLECI" and "CIRCLE_BUILD_NUM" must be set.
 *
 * @psalm-immutable
 * @internal
 */
final class CircleCi implements CiProvider
{
    private const FLAG = 'CIRCLECI';
    private const BUILD_NUMBER = 'CIRCLE_BUILD_NUM';
    private const REPOSITORY_OWNER = 'CIRCLE_PROJECT_USERNAME';
    private const REPOSITORY_NAME = 'CIRCLE_PROJECT_REPONAME';
    private const PULL_REQUEST = 'CIRCLE_PR_NUMBER';
    private const PULL_REQUEST_REPOSITORY_OWNER = 'CIRCLE_PR_USERNAME';
    private const PULL_REQUEST_REPOSITORY_NAME = 'CIRCLE_PR_REPONAME';
    private const BRANCH = 'CIRCLE_BRANCH';

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if (!$env->isTruthy(self::FLAG) || $env->get(self::BUILD_NUMBER) === null) {
            return null;
        }

        return new BuildInfo(
            ciName: 'circleci',
            branch: $env->get(self::BRANCH),
            repository: Repository::fromParts(
                $env->get(self::REPOSITORY_OWNER),
                $env->get(self::REPOSITORY_NAME),
            ),
            pullRequest: PullRequest::fromNumber(
                $env->get(self::PULL_REQUEST),
                Repository::fromParts(
                    $env->get(self::PULL_REQUEST_REPOSITORY_OWNER),
                    $env->get(self::PULL_REQUEST_REPOSITORY_NAME),
                ),
            ),
            buildNumber: $env->get(self::BUILD_NUMBER),
            raw: $env->pick(self::FLAG, self::BUILD_NUMBER),
        );
    }
}
