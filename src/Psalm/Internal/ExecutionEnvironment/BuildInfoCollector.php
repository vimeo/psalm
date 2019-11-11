<?php
namespace Psalm\Internal\ExecutionEnvironment;

use function explode;

/**
 * Environment variables collector for CI environment.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class BuildInfoCollector
{
    /**
     * Environment variables.
     *
     * Overwritten through collection process.
     *
     * @var array
     */
    protected $env;

    /**
     * Read environment variables.
     *
     * @var array
     */
    protected $readEnv = [];

    public function __construct(array $env)
    {
        $this->env = $env;
    }

    // API

    /**
     * Collect environment variables.
     */
    public function collect() : array
    {
        $this->readEnv = [];

        $this
            ->fillTravisCi()
            ->fillCircleCi()
            ->fillAppVeyor()
            ->fillJenkins()
            ->fillScrutinizer();

        return $this->readEnv;
    }

    // internal method

    /**
     * Fill Travis CI environment variables.
     *
     * "TRAVIS", "TRAVIS_JOB_ID" must be set.
     *
     * @return $this
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function fillTravisCi() : self
    {
        if (isset($this->env['TRAVIS']) && $this->env['TRAVIS'] && isset($this->env['TRAVIS_JOB_ID'])) {
            $this->readEnv['CI_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];
            $this->env['CI_NAME'] = 'travis-ci';

            // backup
            $this->readEnv['TRAVIS'] = $this->env['TRAVIS'];
            $this->readEnv['TRAVIS_JOB_ID'] = $this->env['TRAVIS_JOB_ID'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];
            $this->readEnv['TRAVIS_TAG'] = $this->env['TRAVIS_TAG'];

            $repo_slug = (string) $this->env['TRAVIS_REPO_SLUG'];

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);
                $this->readEnv['CI_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_REPO_NAME'] = $slug_parts[1];
            }

            $pr_slug = (string) ($this->env['TRAVIS_PULL_REQUEST_SLUG'] ?? '');

            if ($pr_slug) {
                $slug_parts = explode('/', $pr_slug);

                $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[1];
            }

            $this->readEnv['CI_PR_NUMBER'] = $this->env['TRAVIS_PULL_REQUEST'];
            $this->readEnv['CI_BRANCH'] = $this->env['TRAVIS_BRANCH'];
        }

        return $this;
    }

    /**
     * Fill CircleCI environment variables.
     *
     * "CIRCLECI", "CIRCLE_BUILD_NUM" must be set.
     *
     * @return $this
     */
    protected function fillCircleCi() : self
    {
        if (isset($this->env['CIRCLECI']) && $this->env['CIRCLECI'] && isset($this->env['CIRCLE_BUILD_NUM'])) {
            $this->env['CI_BUILD_NUMBER'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->env['CI_NAME'] = 'circleci';

            // backup
            $this->readEnv['CIRCLECI'] = $this->env['CIRCLECI'];
            $this->readEnv['CIRCLE_BUILD_NUM'] = $this->env['CIRCLE_BUILD_NUM'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];

            $this->readEnv['CI_PR_REPO_OWNER'] = $this->env['CIRCLE_PR_USERNAME'] ?? null;
            $this->readEnv['CI_PR_REPO_NAME'] = $this->env['CIRCLE_PR_REPONAME'] ?? null;

            $this->readEnv['CI_REPO_OWNER'] = $this->env['CIRCLE_PROJECT_USERNAME'] ?? null;
            $this->readEnv['CI_REPO_NAME'] = $this->env['CIRCLE_PROJECT_REPONAME'] ?? null;

            $this->readEnv['CI_PR_NUMBER'] = $this->env['CIRCLE_PR_NUMBER'] ?? null;

            $this->readEnv['CI_BRANCH'] = $this->env['CIRCLE_BRANCH'] ?? null;
        }

        return $this;
    }

    /**
     * Fill AppVeyor environment variables.
     *
     * "APPVEYOR", "APPVEYOR_BUILD_NUMBER" must be set.
     *
     * @return $this
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function fillAppVeyor() : self
    {
        if (isset($this->env['APPVEYOR']) && $this->env['APPVEYOR'] && isset($this->env['APPVEYOR_BUILD_NUMBER'])) {
            $this->readEnv['CI_BUILD_NUMBER'] = $this->env['APPVEYOR_BUILD_NUMBER'];
            $this->readEnv['CI_JOB_ID'] = $this->env['APPVEYOR_JOB_NUMBER'];
            $this->readEnv['CI_BRANCH'] = $this->env['APPVEYOR_REPO_BRANCH'];
            $this->readEnv['CI_PR_NUMBER'] = $this->env['APPVEYOR_PULL_REQUEST_NUMBER'] ?? '';
            $this->env['CI_NAME'] = 'AppVeyor';

            // backup
            $this->readEnv['APPVEYOR'] = $this->env['APPVEYOR'];
            $this->readEnv['APPVEYOR_BUILD_NUMBER'] = $this->env['APPVEYOR_BUILD_NUMBER'];
            $this->readEnv['APPVEYOR_JOB_NUMBER'] = $this->env['APPVEYOR_JOB_NUMBER'];
            $this->readEnv['APPVEYOR_REPO_BRANCH'] = $this->env['APPVEYOR_REPO_BRANCH'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];

            $repo_slug = (string) ($this->env['APPVEYOR_REPO_NAME'] ?? '');

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);

                $this->readEnv['CI_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_REPO_NAME'] = $slug_parts[1];
            }

            $pr_slug = (string) ($this->env['APPVEYOR_PULL_REQUEST_HEAD_REPO_NAME'] ?? '');

            if ($pr_slug) {
                $slug_parts = explode('/', $pr_slug);

                $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[0];
                $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[1];
            }

            $this->readEnv['CI_BRANCH'] = $this->env['APPVEYOR_PULL_REQUEST_HEAD_REPO_BRANCH']
                ?? $this->env['APPVEYOR_REPO_BRANCH'];
        }

        return $this;
    }

    /**
     * Fill Jenkins environment variables.
     *
     * "JENKINS_URL", "BUILD_NUMBER" must be set.
     *
     * @return $this
     */
    protected function fillJenkins() : self
    {
        if (isset($this->env['JENKINS_URL']) && isset($this->env['BUILD_NUMBER'])) {
            $this->readEnv['CI_BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->readEnv['CI_BUILD_URL'] = $this->env['JENKINS_URL'];
            $this->env['CI_NAME'] = 'jenkins';

            // backup
            $this->readEnv['BUILD_NUMBER'] = $this->env['BUILD_NUMBER'];
            $this->readEnv['JENKINS_URL'] = $this->env['JENKINS_URL'];
            $this->readEnv['CI_NAME'] = $this->env['CI_NAME'];
        }

        return $this;
    }

    /**
     * Fill Scrutinizer environment variables.
     *
     * "JENKINS_URL", "BUILD_NUMBER" must be set.
     *
     * @return $this
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    protected function fillScrutinizer() : self
    {
        if (isset($this->env['SCRUTINIZER']) && $this->env['SCRUTINIZER']) {
            $this->readEnv['CI_JOB_ID'] = $this->env['SCRUTINIZER_INSPECTION_UUID'];
            $this->readEnv['CI_BRANCH'] = $this->env['SCRUTINIZER_BRANCH'];
            $this->readEnv['CI_PR_NUMBER'] = $this->env['SCRUTINIZER_PR_NUMBER'] ?? '';

            // backup
            $this->readEnv['CI_NAME'] = 'Scrutinizer';

            $repo_slug = (string) ($this->env['SCRUTINIZER_PROJECT'] ?? '');

            if ($repo_slug) {
                $slug_parts = explode('/', $repo_slug);

                if ($this->readEnv['CI_PR_NUMBER']) {
                    $this->readEnv['CI_PR_REPO_OWNER'] = $slug_parts[1];
                    $this->readEnv['CI_PR_REPO_NAME'] = $slug_parts[2];
                } else {
                    $this->readEnv['CI_REPO_OWNER'] = $slug_parts[1];
                    $this->readEnv['CI_REPO_NAME'] = $slug_parts[2];
                }
            }
        }

        return $this;
    }
}
