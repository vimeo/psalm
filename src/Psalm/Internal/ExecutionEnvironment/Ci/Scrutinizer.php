<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\Internal\ExecutionEnvironment\PullRequest;
use Psalm\Internal\ExecutionEnvironment\Repository;
use Psalm\SourceControl\Git\GitInfo;

use function str_starts_with;
use function strlen;
use function substr;

/**
 * Scrutinizer: "SCRUTINIZER" must be set.
 *
 * @psalm-immutable
 * @internal
 */
final class Scrutinizer implements CiProvider
{
    private const FLAG = 'SCRUTINIZER';
    private const JOB_ID = 'SCRUTINIZER_INSPECTION_UUID';
    private const BRANCH = 'SCRUTINIZER_BRANCH';
    private const PULL_REQUEST = 'SCRUTINIZER_PR_NUMBER';
    private const PROJECT = 'SCRUTINIZER_PROJECT';
    private const GITHUB_PROJECT_PREFIX = 'g/';

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if (!$env->isTruthy(self::FLAG)) {
            return null;
        }

        $repository = self::repository($env->get(self::PROJECT));

        // the project is the inspected one; Scrutinizer does not tell the repository a pull request comes from
        $pull_request = PullRequest::fromNumber($env->get(self::PULL_REQUEST), $repository);

        return new BuildInfo(
            ciName: 'Scrutinizer',
            branch: $env->get(self::BRANCH),
            repository: $repository,
            pullRequest: $pull_request,
            jobId: $env->get(self::JOB_ID),
        );
    }

    /**
     * The repository of a project, which is named like "g/owner/name" on GitHub and "b/owner/name" on Bitbucket.
     * Shepherd only knows GitHub.
     *
     * @psalm-pure
     */
    private static function repository(?string $project): ?Repository
    {
        if ($project === null || !str_starts_with($project, self::GITHUB_PROJECT_PREFIX)) {
            return null;
        }

        return Repository::fromSlug(substr($project, strlen(self::GITHUB_PROJECT_PREFIX)));
    }
}
