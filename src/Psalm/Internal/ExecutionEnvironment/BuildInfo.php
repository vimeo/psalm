<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use Psalm\SourceControl\Git\GitInfo;

/**
 * What a CI service tells about the build, as Shepherd reads it.
 *
 * @psalm-immutable
 * @internal
 */
final class BuildInfo
{
    /**
     * @param ?Repository $repository the repository the build runs in
     * @param array<string, string> $raw the variables of the CI service that are sent as they are
     * @param ?GitInfo $git the git info of the build, when the CI service tells more than the checkout does
     */
    public function __construct(
        public readonly string $ciName,
        public readonly ?string $branch = null,
        public readonly ?Repository $repository = null,
        public readonly ?PullRequest $pullRequest = null,
        public readonly ?string $buildNumber = null,
        public readonly ?string $jobId = null,
        public readonly ?string $buildUrl = null,
        public readonly array $raw = [],
        public readonly ?GitInfo $git = null,
    ) {
    }

    /**
     * The "build" of the payload sent to Shepherd. The only place that knows its keys.
     * A value that is not known is not sent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $values = [
            'CI_REPO_OWNER' => $this->repository?->owner,
            'CI_REPO_NAME' => $this->repository?->name,
            'CI_BRANCH' => $this->branch,
            'CI_PR_NUMBER' => $this->pullRequest?->number,
            'CI_PR_REPO_OWNER' => $this->pullRequest?->head?->owner,
            'CI_PR_REPO_NAME' => $this->pullRequest?->head?->name,
            'CI_BUILD_NUMBER' => $this->buildNumber,
            'CI_JOB_ID' => $this->jobId,
            'CI_BUILD_URL' => $this->buildUrl,
        ];

        $build = ['CI_NAME' => $this->ciName];

        foreach ($values as $key => $value) {
            if ($value !== null) {
                $build[$key] = $value;
            }
        }

        $build += $this->raw;

        if ($this->git !== null) {
            $build['git'] = $this->git->toArray();
        }

        return $build;
    }
}
