<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment\Ci;

use Psalm\Internal\ExecutionEnvironment\BuildInfo;
use Psalm\Internal\ExecutionEnvironment\Env;
use Psalm\SourceControl\Git\GitInfo;

/**
 * A CI service that tells its build info in environment variables.
 *
 * @psalm-mutable
 * @internal
 */
interface CiProvider
{
    /**
     * The build info of the CI service, or null when the environment is not the one of this CI service.
     *
     * @param ?GitInfo $git_info the git info of the checkout
     */
    public function collect(Env $env, ?GitInfo $git_info): ?BuildInfo;
}
