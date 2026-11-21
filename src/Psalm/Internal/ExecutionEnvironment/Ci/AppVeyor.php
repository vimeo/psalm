<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\SourceControl\Git\GitInfo;

/**
 * AppVeyor: "APPVEYOR" and "APPVEYOR_BUILD_NUMBER" must be set.
 *
 * @psalm-immutable
 * @internal
 */
final class AppVeyor implements CiProvider
{
    private const FLAG = 'APPVEYOR';
    private const BUILD_NUMBER = 'APPVEYOR_BUILD_NUMBER';
    private const JOB_NUMBER = 'APPVEYOR_JOB_NUMBER';
    private const REPOSITORY = 'APPVEYOR_REPO_NAME';
    private const BRANCH = 'APPVEYOR_REPO_BRANCH';
    private const PULL_REQUEST = 'APPVEYOR_PULL_REQUEST_NUMBER';
    private const PULL_REQUEST_REPOSITORY = 'APPVEYOR_PULL_REQUEST_HEAD_REPO_NAME';
    private const PULL_REQUEST_BRANCH = 'APPVEYOR_PULL_REQUEST_HEAD_REPO_BRANCH';

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if (!$env->isTruthy(self::FLAG) || $env->get(self::BUILD_NUMBER) === null) {
            return null;
        }

        return new BuildInfo(
            ciName: 'AppVeyor',
            branch: $env->get(self::PULL_REQUEST_BRANCH, self::BRANCH),
            repository: $env->repository(self::REPOSITORY),
            pullRequest: PullRequest::fromNumber(
                $env->get(self::PULL_REQUEST),
                $env->repository(self::PULL_REQUEST_REPOSITORY),
            ),
            buildNumber: $env->get(self::BUILD_NUMBER),
            jobId: $env->get(self::JOB_NUMBER),
            raw: $env->pick(self::FLAG, self::BUILD_NUMBER, self::JOB_NUMBER, self::BRANCH),
        );
    }
}
