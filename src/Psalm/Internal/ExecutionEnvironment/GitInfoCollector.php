<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use Psalm\SourceControl\Git\CommitInfo;
use Psalm\SourceControl\Git\GitInfo;
use Psalm\SourceControl\Git\RemoteInfo;
use RuntimeException;

use function array_keys;
use function array_unique;
use function count;
use function explode;
use function range;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Git repository info collector.
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 * @internal
 */
final class GitInfoCollector
{
    /**
     * Git command.
     */
    private readonly SystemCommandExecutor $executor;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->executor = new SystemCommandExecutor();
    }

    // API

    /**
     * Collect git repository info.
     */
    public function collect(): GitInfo
    {
        $branch = $this->collectBranch();
        $commit = $this->collectCommit();
        $remotes = $this->collectRemotes();

        return new GitInfo($branch, $commit, $remotes);
    }

    /**
     * Collect branch name.
     *
     * @throws RuntimeException
     */
    private function collectBranch(): string
    {
        $branchesResult = $this->executor->execute('git branch');

        foreach ($branchesResult as $result) {
            if (str_starts_with($result, '* ')) {
                $exploded = explode('* ', $result, 2);

                return $exploded[1];
            }
        }

        throw new RuntimeException();
    }

    /**
     * Collect commit info.
     *
     * @throws RuntimeException
     */
    private function collectCommit(): CommitInfo
    {
        $commitResult = $this->executor->execute('git log -1 --pretty=format:%H%n%aN%n%ae%n%cN%n%ce%n%s%n%at');

        if (count($commitResult) !== 7 || array_keys($commitResult) !== range(0, 6)) {
            throw new RuntimeException();
        }

        $commit = new CommitInfo();

        return $commit
            ->setId(trim($commitResult[0]))
            ->setAuthorName(trim($commitResult[1]))
            ->setAuthorEmail(trim($commitResult[2]))
            ->setCommitterName(trim($commitResult[3]))
            ->setCommitterEmail(trim($commitResult[4]))
            ->setMessage($commitResult[5])
            ->setDate((int) $commitResult[6]);
    }

    /**
     * Collect remotes info.
     *
     * @throws RuntimeException
     * @return list<RemoteInfo>
     */
    private function collectRemotes(): array
    {
        $remotesResult = $this->executor->execute('git remote -v');

        if (count($remotesResult) === 0) {
            throw new RuntimeException();
        }

        // parse command result
        $results = [];

        foreach ($remotesResult as $result) {
            if (str_contains($result, ' ')) {
                [$remote] = explode(' ', $result, 2);

                $results[] = $remote;
            }
        }

        // filter
        $results = array_unique($results);

        // create Remote instances
        $remotes = [];

        foreach ($results as $result) {
            if (str_contains($result, "\t")) {
                [$name, $url] = explode("\t", $result, 2);

                $remote = new RemoteInfo();
                $remotes[] = $remote->setName(trim($name))->setUrl(trim($url));
            }
        }

        return $remotes;
    }
}
