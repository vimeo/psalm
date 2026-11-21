<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use Psalm\Internal\ExecutionEnvironment\Ci\AppVeyor;
use Psalm\Internal\ExecutionEnvironment\Ci\CiProvider;
use Psalm\Internal\ExecutionEnvironment\Ci\CircleCi;
use Psalm\Internal\ExecutionEnvironment\Ci\GenericCi;
use Psalm\Internal\ExecutionEnvironment\Ci\GithubActions;
use Psalm\Internal\ExecutionEnvironment\Ci\Jenkins;
use Psalm\Internal\ExecutionEnvironment\Ci\Scrutinizer;
use Psalm\Internal\ExecutionEnvironment\Ci\TravisCi;
use Psalm\SourceControl\Git\GitInfo;

/**
 * Environment variables collector for CI environment.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 * @internal
 */
final class BuildInfoCollector
{
    private readonly Env $env;

    /**
     * The first provider that matches the environment wins. The known CI services come first and the generic
     * one, which matches any CI service, last; the order of the known ones only matters when the environment
     * has the variables of several of them.
     *
     * @var list<CiProvider>
     */
    private readonly array $providers;

    /**
     * @param array<array-key, mixed> $env environment variables
     * @psalm-mutation-free
     */
    public function __construct(array $env)
    {
        $this->env = new Env($env);
        $this->providers = [
            new GithubActions(),
            new Scrutinizer(),
            new Jenkins(),
            new AppVeyor(),
            new CircleCi(),
            new TravisCi(),
            new GenericCi(),
        ];
    }

    /**
     * Collect the build info of the CI service the environment variables are the ones of, as Shepherd reads it.
     *
     * The git info of the checkout names the repository of a CI service Psalm does not know.
     *
     * @return array<string, mixed> empty when it is not a CI service
     */
    public function collect(?GitInfo $git_info = null): array
    {
        foreach ($this->providers as $provider) {
            $build_info = $provider->collect($this->env, $git_info);

            if ($build_info !== null) {
                return $build_info->toArray();
            }
        }

        return [];
    }
}
