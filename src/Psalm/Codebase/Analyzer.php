<?php
namespace Psalm\Codebase;

use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\FileManipulation\FileManipulation;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\FileManipulation\FunctionDocblockManipulator;
use Psalm\IssueBuffer;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;

/**
 * @psalm-type  IssueData = array{
 *          issues: array<
 *            int,
 *            array{
 *              severity: string,
 *              line_from: int,
 *              line_to: int,
 *              type: string,
 *              message: string,
 *              file_name: string,
 *              file_path: string,
 *              snippet: string,
 *              from: int,
 *              to: int,
 *              snippet_from: int,
 *              snippet_to: int,
 *              column_from: int,
 *              column_to: int
 *            }
 *          >,
 *          file_references: array<string, array<string,bool>>,
 *          mixed_counts: array<string, array{0: int, 1: int}>,
 *          method_references: array<string, array<string,bool>>,
 *          correct_methods: array<string, array<string, bool>>
 *        }
 */

/**
 * @internal
 *
 * Called in the analysis phase of Psalm's execution
 */
class Analyzer
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var bool
     */
    private $debug_output;

    /**
     * Used to store counts of mixed vs non-mixed variables
     *
     * @var array<string, array{0: int, 1: int}
     */
    private $mixed_counts = [];

    /**
     * @var bool
     */
    private $count_mixed = true;

    /**
     * We analyze more files than we necessarily report errors in
     *
     * @var array<string, string>
     */
    private $files_to_analyze = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private $correct_methods = [];

    /**
     * @param bool $debug_output
     */
    public function __construct(
        Config $config,
        FileProvider $file_provider,
        FileStorageProvider $file_storage_provider,
        $debug_output
    ) {
        $this->config = $config;
        $this->file_provider = $file_provider;
        $this->file_storage_provider = $file_storage_provider;
        $this->debug_output = $debug_output;
    }

    /**
     * @param array<string, string> $files_to_analyze
     *
     * @return void
     */
    public function addFiles(array $files_to_analyze)
    {
        $this->files_to_analyze += $files_to_analyze;
    }

    /**
     * @param  string $file_path
     *
     * @return bool
     */
    public function canReportIssues($file_path)
    {
        return isset($this->files_to_analyze[$file_path]);
    }

    /**
     * @param  string $file_path
     * @param  array<string, string> $filetype_checkers
     *
     * @return FileChecker
     *
     * @psalm-suppress MixedOperand
     */
    private function getFileChecker(ProjectChecker $project_checker, $file_path, array $filetype_checkers)
    {
        $extension = (string)pathinfo($file_path)['extension'];

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_checkers[$extension])) {
            /** @var FileChecker */
            $file_checker = new $filetype_checkers[$extension]($project_checker, $file_path, $file_name);
        } else {
            $file_checker = new FileChecker($project_checker, $file_path, $file_name);
        }

        if ($this->debug_output) {
            echo 'Getting ' . $file_path . "\n";
        }

        return $file_checker;
    }

    /**
     * @param  ProjectChecker $project_checker
     * @param  int            $pool_size
     * @param  bool           $alter_code
     *
     * @return void
     */
    public function analyzeFiles(ProjectChecker $project_checker, $pool_size, $alter_code)
    {
        if (!$project_checker->cache_provider instanceof \Psalm\Provider\NoCache\NoParserCacheProvider
            && !$project_checker->codebase->collect_references
        ) {
            $this->correct_methods = \Psalm\Provider\FileReferenceProvider::getCorrectMethodCache($this->config);
        } else {
            $this->correct_methods = [];
        }

        $unchanged_methods = $project_checker->codebase->statements_provider->getUnchangedMembers();
        $unchanged_signature_methods = $project_checker->codebase->statements_provider->getUnchangedSignatureMembers();

        $method_map = [];

        foreach ($this->correct_methods as $file_path => $correct_methods) {
            foreach ($correct_methods as $correct_method_id => $_) {
                $method_map[$correct_method_id] = $file_path;
            }
        }

        foreach ($this->correct_methods as $file_path => $correct_methods) {
            foreach ($correct_methods as $correct_method_id => $_) {
                if (isset($unchanged_methods[$file_path])
                    && !isset($unchanged_methods[$file_path][$correct_method_id])
                ) {
                    unset($this->correct_methods[$file_path][$correct_method_id]);

                    if (!isset($unchanged_signature_methods[$file_path][$correct_method_id])) {
                        $referencing_methods = \Psalm\Provider\FileReferenceProvider::getMethodsReferencingClassMember(
                            $correct_method_id
                        );

                        foreach ($referencing_methods as $method_id => $_) {
                            if (isset($method_map[$method_id])) {
                                $method_file_path = $method_map[$method_id];
                                unset($this->correct_methods[$method_file_path][$method_id]);
                            }
                        }
                    }
                }
            }
        }

        $filetype_checkers = $this->config->getFiletypeCheckers();

        $analysis_worker =
            /**
             * @param int $_
             * @param string $file_path
             *
             * @return void
             */
            function ($_, $file_path) use ($project_checker, $filetype_checkers) {
                $file_checker = $this->getFileChecker($project_checker, $file_path, $filetype_checkers);

                if ($this->debug_output) {
                    echo 'Analyzing ' . $file_checker->getFilePath() . "\n";
                }

                $file_checker->analyze(null);
            };

        if ($pool_size > 1 && count($this->files_to_analyze) > $pool_size) {
            $process_file_paths = [];

            $i = 0;

            foreach ($this->files_to_analyze as $file_path) {
                $process_file_paths[$i % $pool_size][] = $file_path;
                ++$i;
            }

            // Run analysis one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new \Psalm\Fork\Pool(
                $process_file_paths,
                /** @return void */
                function () {
                },
                $analysis_worker,
                /** @return IssueData */
                function () {
                    $analyzer = ProjectChecker::getInstance()->codebase->analyzer;

                    return [
                        'issues' => IssueBuffer::getIssuesData(),
                        'file_references' => FileReferenceProvider::getAllFileReferences(),
                        'method_references' => FileReferenceProvider::getClassMethodReferences(),
                        'mixed_counts' => $analyzer->getMixedCounts(),
                        'correct_methods' => $analyzer->getCorrectMethods(),
                    ];
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<int, IssueData>
             */
            $forked_pool_data = $pool->wait();

            foreach ($forked_pool_data as $pool_data) {
                IssueBuffer::addIssues($pool_data['issues']);
                FileReferenceProvider::addFileReferences($pool_data['file_references']);
                FileReferenceProvider::addClassMethodReferences($pool_data['method_references']);
                $this->correct_methods = array_merge($pool_data['correct_methods'], $this->correct_methods);

                foreach ($pool_data['mixed_counts'] as $file_path => list($mixed_count, $nonmixed_count)) {
                    if (!isset($this->mixed_counts[$file_path])) {
                        $this->mixed_counts[$file_path] = [$mixed_count, $nonmixed_count];
                    } else {
                        $this->mixed_counts[$file_path][0] += $mixed_count;
                        $this->mixed_counts[$file_path][1] += $nonmixed_count;
                    }
                }
            }

            // TODO: Tell the caller that the fork pool encountered an error in another PR?
            // $did_fork_pool_have_error = $pool->didHaveError();
        } else {
            $i = 0;

            foreach ($this->files_to_analyze as $file_path => $_) {
                $analysis_worker($i, $file_path);
                ++$i;
            }
        }

        if (!$project_checker->cache_provider instanceof \Psalm\Provider\NoCache\NoParserCacheProvider) {
            \Psalm\Provider\FileReferenceProvider::setCorrectMethodCache($this->correct_methods);
        }

        if ($alter_code) {
            foreach ($this->files_to_analyze as $file_path) {
                $this->updateFile($file_path, $project_checker->dry_run, true);
            }
        }
    }

    /**
     * @param  string $file_path
     *
     * @return array{0:int, 1:int}
     */
    public function getMixedCountsForFile($file_path)
    {
        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        return $this->mixed_counts[$file_path];
    }

    /**
     * @param  string $file_path
     * @param  array{0:int, 1:int} $mixed_counts
     *
     * @return void
     */
    public function setMixedCountsForFile($file_path, array $mixed_counts)
    {
        $this->mixed_counts[$file_path] = $mixed_counts;
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function incrementMixedCount($file_path)
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][0];
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function incrementNonMixedCount($file_path)
    {
        if (!$this->count_mixed) {
            return;
        }

        if (!isset($this->mixed_counts[$file_path])) {
            $this->mixed_counts[$file_path] = [0, 0];
        }

        ++$this->mixed_counts[$file_path][1];
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public function getMixedCounts()
    {
        return $this->mixed_counts;
    }

    /**
     * @return string
     */
    public function getTypeInferenceSummary()
    {
        $mixed_count = 0;
        $nonmixed_count = 0;

        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            foreach ($this->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        foreach ($all_deep_scanned_files as $file_path => $_) {
            if (!$this->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            if (isset($this->mixed_counts[$file_path])) {
                list($path_mixed_count, $path_nonmixed_count) = $this->mixed_counts[$file_path];
                $mixed_count += $path_mixed_count;
                $nonmixed_count += $path_nonmixed_count;
            }
        }

        $total = $mixed_count + $nonmixed_count;

        $total_files = count($all_deep_scanned_files);

        if (!$total_files) {
            return 'No files analyzed';
        }

        if (!$total) {
            return 'Psalm was unable to infer types in any of '
                . $total_files . ' file' . ($total_files > 1 ? 's' : '');
        }

        return 'Psalm was able to infer types for ' . number_format(100 * $nonmixed_count / $total, 3) . '%'
            . ' of analyzed code (' . $total_files . ' file' . ($total_files > 1 ? 's' : '') . ')';
    }

    /**
     * @return string
     */
    public function getNonMixedStats()
    {
        $stats = '';

        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            if (!$this->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            foreach ($this->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        foreach ($all_deep_scanned_files as $file_path => $_) {
            if (isset($this->mixed_counts[$file_path])) {
                list($path_mixed_count, $path_nonmixed_count) = $this->mixed_counts[$file_path];
                $stats .= number_format(100 * $path_nonmixed_count / ($path_mixed_count + $path_nonmixed_count), 0)
                    . '% ' . $this->config->shortenFileName($file_path)
                    . ' (' . $path_mixed_count . ' mixed)' . "\n";
            }
        }

        return $stats;
    }

    /**
     * @return void
     */
    public function disableMixedCounts()
    {
        $this->count_mixed = false;
    }

    /**
     * @return void
     */
    public function enableMixedCounts()
    {
        $this->count_mixed = true;
    }

    /**
     * @param  string $file_path
     * @param  bool $dry_run
     * @param  bool $output_changes to console
     *
     * @return void
     */
    public function updateFile($file_path, $dry_run, $output_changes = false)
    {
        $new_return_type_manipulations = FunctionDocblockManipulator::getManipulationsForFile($file_path);

        $other_manipulations = FileManipulationBuffer::getForFile($file_path);

        $file_manipulations = array_merge($new_return_type_manipulations, $other_manipulations);

        usort(
            $file_manipulations,
            /**
             * @return int
             */
            function (FileManipulation $a, FileManipulation $b) {
                if ($a->start === $b->start) {
                    if ($b->end === $a->end) {
                        return $b->insertion_text > $a->insertion_text ? 1 : -1;
                    }

                    return $b->end > $a->end ? 1 : -1;
                }

                return $b->start > $a->start ? 1 : -1;
            }
        );

        $docblock_update_count = count($file_manipulations);

        $existing_contents = $this->file_provider->getContents($file_path);

        foreach ($file_manipulations as $manipulation) {
            $existing_contents
                = substr($existing_contents, 0, $manipulation->start)
                    . $manipulation->insertion_text
                    . substr($existing_contents, $manipulation->end);
        }

        if ($docblock_update_count) {
            if ($dry_run) {
                echo $file_path . ':' . "\n";

                $differ = new \PhpCsFixer\Diff\v2_0\Differ(
                    new \PhpCsFixer\Diff\GeckoPackages\DiffOutputBuilder\UnifiedDiffOutputBuilder([
                        'fromFile' => 'Original',
                        'toFile' => 'New',
                    ])
                );

                echo (string) $differ->diff($this->file_provider->getContents($file_path), $existing_contents);

                return;
            }

            if ($output_changes) {
                echo 'Altering ' . $file_path . "\n";
            }

            $this->file_provider->setContents($file_path, $existing_contents);
        }
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getCorrectMethods()
    {
        return $this->correct_methods;
    }

    /**
     * @param string $file_path
     * @param string $method_id
     *
     * @return void
     */
    public function setCorrectMethod($file_path, $method_id)
    {
        $this->correct_methods[$file_path][$method_id] = true;
    }

    /**
     * @param  string  $file_path
     * @param  string  $method_id
     * @return bool
     */
    public function isMethodCorrect($file_path, $method_id)
    {
        return isset($this->correct_methods[$file_path][$method_id]);
    }
}
