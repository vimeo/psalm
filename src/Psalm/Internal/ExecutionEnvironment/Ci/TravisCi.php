<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\SourceControl\Git\GitInfo;

/**
 * Travis CI: "TRAVIS" and "TRAVIS_JOB_ID" must be set.
 *
 * @psalm-immutable
 * @internal
 */
final class TravisCi implements CiProvider
{
    private const FLAG = 'TRAVIS';
    private const JOB_ID = 'TRAVIS_JOB_ID';
    private const TAG = 'TRAVIS_TAG';
    private const REPOSITORY = 'TRAVIS_REPO_SLUG';
    private const PULL_REQUEST = 'TRAVIS_PULL_REQUEST';
    private const PULL_REQUEST_REPOSITORY = 'TRAVIS_PULL_REQUEST_SLUG';
    private const BRANCH = 'TRAVIS_BRANCH';

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if (!$env->isTruthy(self::FLAG) || $env->get(self::JOB_ID) === null) {
            return null;
        }

        return new BuildInfo(
            ciName: 'travis-ci',
            // for a tag build, Travis CI sets the branch to the name of the tag
            branch: $env->get(self::TAG) === null ? $env->get(self::BRANCH) : null,
            repository: $env->repository(self::REPOSITORY),
            pullRequest: PullRequest::fromNumber(
                $env->get(self::PULL_REQUEST),
                $env->repository(self::PULL_REQUEST_REPOSITORY),
            ),
            jobId: $env->get(self::JOB_ID),
            raw: $env->pick(self::FLAG, self::JOB_ID, self::TAG),
        );
    }
}
