<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Override;
use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\SourceControl\Git\GitInfo;

/**
 * Jenkins: "JENKINS_URL" and "BUILD_NUMBER" must be set.
 *
 * @psalm-immutable
 * @internal
 */
final class Jenkins implements CiProvider
{
    private const URL = 'JENKINS_URL';
    private const BUILD_NUMBER = 'BUILD_NUMBER';

    #[Override]
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo
    {
        if ($env->get(self::URL) === null || $env->get(self::BUILD_NUMBER) === null) {
            return null;
        }

        return new BuildInfo(
            ciName: 'jenkins',
            buildNumber: $env->get(self::BUILD_NUMBER),
            buildUrl: $env->get(self::URL),
            raw: $env->pick(self::BUILD_NUMBER, self::URL),
        );
    }
}
