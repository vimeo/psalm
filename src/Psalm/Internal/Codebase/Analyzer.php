<?php
namespace Psalm\Internal\Codebase;

use PhpParser;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Config;
use Psalm\FileManipulation;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\FileManipulation\FunctionDocblockManipulator;
use Psalm\IssueBuffer;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Progress\Progress;

/**
 * @psalm-type  IssueData = array{
 *     severity: string,
 *     line_from: int,
 *     line_to: int,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     snippet: string,
 *     from: int,
 *     to: int,
 *     snippet_from: int,
 *     snippet_to: int,
 *     column_from: int,
 *     column_to: int
 * }
 *
 * @psalm-type  TaggedCodeType = array<int, array{0: int, 1: string}>
 *
 * @psalm-type  WorkerData = array{
 *     issues: array<int, IssueData>,
 *     file_references_to_classes: array<string, array<string,bool>>,
 *     file_references_to_class_members: array<string, array<string,bool>>,
 *     file_references_to_missing_class_members: array<string, array<string,bool>>,
 *     mixed_counts: array<string, array{0: int, 1: int}>,
 *     mixed_member_names: array<string, array<string, bool>>,
 *     file_manipulations: array<string, FileManipulation[]>,
 *     method_references_to_class_members: array<string, array<string,bool>>,
 *     method_references_to_missing_class_members: array<string, array<string,bool>>,
 *     method_param_uses: array<string, array<int, array<string, bool>>>,
 *     analyzed_methods: array<string, array<string, int>>,
 *     file_maps: array<
 *         string,
 *         array{0: TaggedCodeType, 1: TaggedCodeType}
 *     >,
 *     class_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *     class_method_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *     class_property_locations: array<string, array<int, \Psalm\CodeLocation>>,
 *     possible_method_param_types: array<string, array<int, \Psalm\Type\Union>>
 * }
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
     * @var Progress
     */
    private $progress;

    /**
     * Used to store counts of mixed vs non-mixed variables
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private $mixed_counts = [];

    /**
     * Used to store member names of mixed property/method access
     *
     * @var array<string, array<string, bool>>
     */
    private $mixed_member_names = [];

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
     * We may update fewer files than we analyse (i.e. for dead code detection)
     *
     * @var array<string>|null
     */
    private $files_to_update = null;

    /**
     * @var array<string, array<string, int>>
     */
    private $analyzed_methods = [];

    /**
     * @var array<string, array<int, IssueData>>
     */
    private $existing_issues = [];

    /**
     * @var array<string, array<int, array{0: int, 1: string}>>
     */
    private $reference_map = [];

    /**
     * @var array<string, array<int, array{0: int, 1: string}>>
     */
    private $type_map = [];

    /**
     * @var array<string, array<int, \Psalm\Type\Union>>
     */
    public $possible_method_param_types = [];

    public function __construct(
        Config $config,
        FileProvider $file_provider,
        FileStorageProvider $file_storage_provider,
        Progress $progress
    ) {
        $this->config = $config;
        $this->file_provider = $file_provider;
        $this->file_storage_provider = $file_storage_provider;
        $this->progress = $progress;
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
     * @param array<string> $files_to_update
     *
     * @return void
     */
    public function setFilesToUpdate(array $files_to_update)
    {
        $this->files_to_update = $files_to_update;
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
     * @param  array<string, class-string<FileAnalyzer>> $filetype_analyzers
     *
     * @return FileAnalyzer
     *
     * @psalm-suppress MixedOperand
     */
    private function getFileAnalyzer(ProjectAnalyzer $project_analyzer, $file_path, array $filetype_analyzers)
    {
        $extension = (string) (pathinfo($file_path)['extension'] ?? '');

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_analyzers[$extension])) {
            $file_analyzer = new $filetype_analyzers[$extension]($project_analyzer, $file_path, $file_name);
        } else {
            $file_analyzer = new FileAnalyzer($project_analyzer, $file_path, $file_name);
        }

        $this->progress->debug('Getting ' . $file_path . "\n");

        return $file_analyzer;
    }

    /**
     * @param  ProjectAnalyzer $project_analyzer
     * @param  int            $pool_size
     * @param  bool           $alter_code
     *
     * @return void
     */
    public function analyzeFiles(ProjectAnalyzer $project_analyzer, $pool_size, $alter_code)
    {
        $this->loadCachedResults($project_analyzer);

        $filetype_analyzers = $this->config->getFiletypeAnalyzers();
        $codebase = $project_analyzer->getCodebase();

        if ($alter_code) {
            $project_analyzer->interpretRefactors();
        }

        $analysis_worker =
            /**
             * @param int $_
             * @param string $file_path
             *
             * @return array
             */
            function ($_, $file_path) use ($project_analyzer, $filetype_analyzers) {
                $file_analyzer = $this->getFileAnalyzer($project_analyzer, $file_path, $filetype_analyzers);

                $this->progress->debug('Analyzing ' . $file_analyzer->getFilePath() . "\n");

                $file_analyzer->analyze(null);

                return $this->getFileIssues($file_path);
            };

        $this->progress->start(count($this->files_to_analyze));

        $task_done_closure =
            /**
             * @param array<IssueData> $issues
             */
            function (array $issues): void {
                $has_error = false;
                $has_info = false;

                foreach ($issues as $issue) {
                    if ($issue['severity'] === 'error') {
                        $has_error = true;
                        break;
                    }

                    if ($issue['severity'] === 'info') {
                        $has_info = true;
                    }
                }

                $this->progress->taskDone($has_error ? 2 : ($has_info ? 1 : 0));
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
            $pool = new \Psalm\Internal\Fork\Pool(
                $process_file_paths,
                /** @return void */
                function () {
                    $project_analyzer = ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();

                    $file_reference_provider = $codebase->file_reference_provider;

                    $file_reference_provider->setFileReferencesToClasses([]);
                    $file_reference_provider->setCallingMethodReferencesToClassMembers([]);
                    $file_reference_provider->setFileReferencesToClassMembers([]);
                    $file_reference_provider->setCallingMethodReferencesToMissingClassMembers([]);
                    $file_reference_provider->setFileReferencesToMissingClassMembers([]);
                    $file_reference_provider->setReferencesToMixedMemberNames([]);
                    $file_reference_provider->setMethodParamUses([]);
                },
                $analysis_worker,
                /** @return WorkerData */
                function () {
                    $project_analyzer = ProjectAnalyzer::getInstance();
                    $codebase = $project_analyzer->getCodebase();
                    $analyzer = $codebase->analyzer;
                    $file_reference_provider = $codebase->file_reference_provider;

                    $this->progress->debug('Gathering data for forked process' . "\n");

                    return [
                        'issues' => IssueBuffer::getIssuesData(),
                        'file_references_to_classes'
                            => $file_reference_provider->getAllFileReferencesToClasses(),
                        'file_references_to_class_members'
                            => $file_reference_provider->getAllFileReferencesToClassMembers(),
                        'method_references_to_class_members'
                            => $file_reference_provider->getAllMethodReferencesToClassMembers(),
                        'file_references_to_missing_class_members'
                            => $file_reference_provider->getAllFileReferencesToMissingClassMembers(),
                        'method_references_to_missing_class_members'
                            => $file_reference_provider->getAllMethodReferencesToMissingClassMembers(),
                        'method_param_uses' => $file_reference_provider->getAllMethodParamUses(),
                        'mixed_member_names' => $analyzer->getMixedMemberNames(),
                        'file_manipulations' => FileManipulationBuffer::getAll(),
                        'mixed_counts' => $analyzer->getMixedCounts(),
                        'analyzed_methods' => $analyzer->getAnalyzedMethods(),
                        'file_maps' => $analyzer->getFileMaps(),
                        'class_locations' => $file_reference_provider->getAllClassLocations(),
                        'class_method_locations' => $file_reference_provider->getAllClassMethodLocations(),
                        'class_property_locations' => $file_reference_provider->getAllClassPropertyLocations(),
                        'possible_method_param_types' => $analyzer->getPossibleMethodParamTypes(),
                    ];
                },
                $task_done_closure
            );

            $this->progress->debug('Forking analysis' . "\n");

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<int, WorkerData>
             */
            $forked_pool_data = $pool->wait();

            $this->progress->debug('Collecting forked analysis results' . "\n");

            foreach ($forked_pool_data as $pool_data) {
                IssueBuffer::addIssues($pool_data['issues']);

                foreach ($pool_data['issues'] as $issue_data) {
                    $codebase->file_reference_provider->addIssue($issue_data['file_path'], $issue_data);
                }

                $codebase->file_reference_provider->addFileReferencesToClasses(
                    $pool_data['file_references_to_classes']
                );
                $codebase->file_reference_provider->addFileReferencesToClassMembers(
                    $pool_data['file_references_to_class_members']
                );
                $codebase->file_reference_provider->addMethodReferencesToClassMembers(
                    $pool_data['method_references_to_class_members']
                );
                $codebase->file_reference_provider->addFileReferencesToMissingClassMembers(
                    $pool_data['file_references_to_missing_class_members']
                );
                $codebase->file_reference_provider->addMethodReferencesToMissingClassMembers(
                    $pool_data['method_references_to_missing_class_members']
                );
                $codebase->file_reference_provider->addMethodParamUses(
                    $pool_data['method_param_uses']
                );
                $this->addMixedMemberNames(
                    $pool_data['mixed_member_names']
                );
                $codebase->file_reference_provider->addClassLocations(
                    $pool_data['class_locations']
                );
                $codebase->file_reference_provider->addClassMethodLocations(
                    $pool_data['class_method_locations']
                );
                $codebase->file_reference_provider->addClassPropertyLocations(
                    $pool_data['class_property_locations']
                );

                $this->analyzed_methods = array_merge($pool_data['analyzed_methods'], $this->analyzed_methods);

                foreach ($pool_data['mixed_counts'] as $file_path => list($mixed_count, $nonmixed_count)) {
                    if (!isset($this->mixed_counts[$file_path])) {
                        $this->mixed_counts[$file_path] = [$mixed_count, $nonmixed_count];
                    } else {
                        $this->mixed_counts[$file_path][0] += $mixed_count;
                        $this->mixed_counts[$file_path][1] += $nonmixed_count;
                    }
                }

                foreach ($pool_data['possible_method_param_types'] as $declaring_method_id => $possible_param_types) {
                    if (!isset($this->possible_method_param_types[$declaring_method_id])) {
                        $this->possible_method_param_types[$declaring_method_id] = $possible_param_types;
                    } else {
                        foreach ($possible_param_types as $offset => $possible_param_type) {
                            if (!isset($this->possible_method_param_types[$declaring_method_id][$offset])) {
                                $this->possible_method_param_types[$declaring_method_id][$offset]
                                    = $possible_param_type;
                            } else {
                                $this->possible_method_param_types[$declaring_method_id][$offset]
                                    = \Psalm\Type::combineUnionTypes(
                                        $this->possible_method_param_types[$declaring_method_id][$offset],
                                        $possible_param_type,
                                        $codebase
                                    );
                            }
                        }
                    }
                }

                foreach ($pool_data['file_manipulations'] as $file_path => $manipulations) {
                    FileManipulationBuffer::add($file_path, $manipulations);
                }

                foreach ($pool_data['file_maps'] as $file_path => list($reference_map, $type_map)) {
                    $this->reference_map[$file_path] = $reference_map;
                    $this->type_map[$file_path] = $type_map;
                }
            }

            if ($pool->didHaveError()) {
                exit(1);
            }
        } else {
            $i = 0;

            foreach ($this->files_to_analyze as $file_path => $_) {
                $analysis_worker($i, $file_path);
                ++$i;

                $issues = $this->getFileIssues($file_path);
                $task_done_closure($issues);
            }

            foreach (IssueBuffer::getIssuesData() as $issue_data) {
                $codebase->file_reference_provider->addIssue($issue_data['file_path'], $issue_data);
            }
        }

        $this->progress->finish();

        $codebase = $project_analyzer->getCodebase();

        if ($codebase->find_unused_code
            && ($project_analyzer->full_run || $codebase->find_unused_code === 'always')
        ) {
            $project_analyzer->checkClassReferences();
        }

        $scanned_files = $codebase->scanner->getScannedFiles();
        $codebase->file_reference_provider->setAnalyzedMethods($this->analyzed_methods);
        $codebase->file_reference_provider->setFileMaps($this->getFileMaps());
        $codebase->file_reference_provider->setTypeCoverage($this->mixed_counts);
        $codebase->file_reference_provider->updateReferenceCache($codebase, $scanned_files);

        if ($codebase->diff_methods) {
            $codebase->statements_provider->resetDiffs();
        }

        if ($alter_code) {
            $this->progress->startAlteringFiles();

            $project_analyzer->prepareMigration();

            $files_to_update = $this->files_to_update !== null ? $this->files_to_update : $this->files_to_analyze;

            foreach ($files_to_update as $file_path) {
                $this->updateFile($file_path, $project_analyzer->dry_run);
            }

            $project_analyzer->migrateCode();
        }
    }

    /**
     * @return array<IssueData>
     */
    private function getFileIssues(string $file_path): array
    {
        return array_filter(
            IssueBuffer::getIssuesData(),
            function (array $issue) use ($file_path): bool {
                return $issue['file_path'] === $file_path;
            }
        );
    }

    /**
     * @return void
     */
    public function loadCachedResults(ProjectAnalyzer $project_analyzer)
    {
        $codebase = $project_analyzer->getCodebase();
        if ($codebase->diff_methods
            && (!$codebase->collect_references || $codebase->server_mode)
        ) {
            $this->analyzed_methods = $codebase->file_reference_provider->getAnalyzedMethods();
            $this->existing_issues = $codebase->file_reference_provider->getExistingIssues();
            $file_maps = $codebase->file_reference_provider->getFileMaps();

            foreach ($file_maps as $file_path => list($reference_map, $type_map)) {
                $this->reference_map[$file_path] = $reference_map;
                $this->type_map[$file_path] = $type_map;
            }
        }

        $statements_provider = $codebase->statements_provider;
        $file_reference_provider = $codebase->file_reference_provider;

        $changed_members = $statements_provider->getChangedMembers();
        $unchanged_signature_members = $statements_provider->getUnchangedSignatureMembers();

        $diff_map = $statements_provider->getDiffMap();

        $method_references_to_class_members
            = $file_reference_provider->getAllMethodReferencesToClassMembers();
        $method_references_to_missing_class_members =
            $file_reference_provider->getAllMethodReferencesToMissingClassMembers();

        $all_referencing_methods = $method_references_to_class_members + $method_references_to_missing_class_members;

        $file_references_to_classes = $file_reference_provider->getAllFileReferencesToClasses();

        $method_param_uses = $file_reference_provider->getAllMethodParamUses();

        $file_references_to_class_members
            = $file_reference_provider->getAllFileReferencesToClassMembers();
        $file_references_to_missing_class_members
            = $file_reference_provider->getAllFileReferencesToMissingClassMembers();

        $references_to_mixed_member_names = $file_reference_provider->getAllReferencesToMixedMemberNames();

        $this->mixed_counts = $file_reference_provider->getTypeCoverage();

        $classlikes = $codebase->classlikes;

        foreach ($all_referencing_methods as $member_id => $referencing_method_ids) {
            $member_class_name = preg_replace('/::.*$/', '', $member_id);

            if ($classlikes->hasFullyQualifiedClassLikeName($member_class_name)
                && !$classlikes->hasFullyQualifiedTraitName($member_class_name)
            ) {
                continue;
            }

            $member_stub = $member_class_name . '::*';

            if (!isset($all_referencing_methods[$member_stub])) {
                $all_referencing_methods[$member_stub] = $referencing_method_ids;
            } else {
                $all_referencing_methods[$member_stub] += $referencing_method_ids;
            }
        }

        $newly_invalidated_methods = [];

        foreach ($unchanged_signature_members as $file_unchanged_signature_members) {
            $newly_invalidated_methods = array_merge($newly_invalidated_methods, $file_unchanged_signature_members);

            foreach ($file_unchanged_signature_members as $unchanged_signature_member_id => $_) {
                // also check for things that might invalidate constructor property initialisation
                if (isset($all_referencing_methods[$unchanged_signature_member_id])) {
                    foreach ($all_referencing_methods[$unchanged_signature_member_id] as $referencing_method_id => $_) {
                        if (substr($referencing_method_id, -13) === '::__construct') {
                            $newly_invalidated_methods[$referencing_method_id] = true;
                        }
                    }
                }
            }
        }

        foreach ($changed_members as $file_changed_members) {
            foreach ($file_changed_members as $member_id => $_) {
                $newly_invalidated_methods[$member_id] = true;

                if (isset($all_referencing_methods[$member_id])) {
                    $newly_invalidated_methods = array_merge(
                        $all_referencing_methods[$member_id],
                        $newly_invalidated_methods
                    );
                }

                unset($method_references_to_class_members[$member_id]);
                unset($file_references_to_class_members[$member_id]);
                unset($method_references_to_missing_class_members[$member_id]);
                unset($file_references_to_missing_class_members[$member_id]);
                unset($references_to_mixed_member_names[$member_id]);
                unset($method_param_uses[$member_id]);

                $member_stub = preg_replace('/::.*$/', '::*', $member_id);

                if (strpos($member_id, '::')) {
                    $fqcln = explode('::', $member_id)[0];
                    unset($file_references_to_classes[$fqcln]);
                }

                if (isset($all_referencing_methods[$member_stub])) {
                    $newly_invalidated_methods = array_merge(
                        $all_referencing_methods[$member_stub],
                        $newly_invalidated_methods
                    );
                }
            }
        }

        foreach ($newly_invalidated_methods as $method_id => $_) {
            foreach ($method_references_to_class_members as &$referencing_method_ids) {
                unset($referencing_method_ids[$method_id]);
            }

            foreach ($method_references_to_missing_class_members as &$referencing_method_ids) {
                unset($referencing_method_ids[$method_id]);
            }

            foreach ($references_to_mixed_member_names as &$references) {
                unset($references[$method_id]);
            }

            foreach ($method_param_uses as &$references) {
                foreach ($references as &$method_refs) {
                    unset($method_refs[$method_id]);
                }
            }
        }

        foreach ($this->analyzed_methods as $file_path => $analyzed_methods) {
            foreach ($analyzed_methods as $correct_method_id => $_) {
                $trait_safe_method_id = $correct_method_id;

                $correct_method_ids = explode('&', $correct_method_id);

                $correct_method_id = $correct_method_ids[0];

                if (isset($newly_invalidated_methods[$correct_method_id])
                    || (isset($correct_method_ids[1])
                        && isset($newly_invalidated_methods[$correct_method_ids[1]]))
                ) {
                    unset($this->analyzed_methods[$file_path][$trait_safe_method_id]);
                }
            }
        }

        $this->shiftFileOffsets($diff_map);

        foreach ($this->files_to_analyze as $file_path) {
            $file_reference_provider->clearExistingIssuesForFile($file_path);
            $file_reference_provider->clearExistingFileMapsForFile($file_path);

            $this->setMixedCountsForFile($file_path, [0, 0]);

            foreach ($file_references_to_class_members as &$referencing_file_paths) {
                unset($referencing_file_paths[$file_path]);
            }

            foreach ($file_references_to_classes as &$referencing_file_paths) {
                unset($referencing_file_paths[$file_path]);
            }

            foreach ($references_to_mixed_member_names as &$references) {
                unset($references[$file_path]);
            }

            foreach ($file_references_to_missing_class_members as &$referencing_file_paths) {
                unset($referencing_file_paths[$file_path]);
            }
        }

        $method_references_to_class_members = array_filter(
            $method_references_to_class_members,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $method_references_to_missing_class_members = array_filter(
            $method_references_to_missing_class_members,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $file_references_to_class_members = array_filter(
            $file_references_to_class_members,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $file_references_to_missing_class_members = array_filter(
            $file_references_to_missing_class_members,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $references_to_mixed_member_names = array_filter(
            $references_to_mixed_member_names,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $file_references_to_classes = array_filter(
            $file_references_to_classes,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $method_param_uses = array_filter(
            $method_param_uses,
            function (array $a) : bool {
                return !!$a;
            }
        );

        $file_reference_provider->setCallingMethodReferencesToClassMembers(
            $method_references_to_class_members
        );

        $file_reference_provider->setFileReferencesToClassMembers(
            $file_references_to_class_members
        );

        $file_reference_provider->setCallingMethodReferencesToMissingClassMembers(
            $method_references_to_missing_class_members
        );

        $file_reference_provider->setFileReferencesToMissingClassMembers(
            $file_references_to_missing_class_members
        );

        $file_reference_provider->setReferencesToMixedMemberNames(
            $references_to_mixed_member_names
        );

        $file_reference_provider->setFileReferencesToClasses(
            $file_references_to_classes
        );

        $file_reference_provider->setMethodParamUses(
            $method_param_uses
        );
    }

    /**
     * @param array<string, array<int, array{int, int, int, int}>> $diff_map
     * @return void
     */
    public function shiftFileOffsets(array $diff_map)
    {
        foreach ($this->existing_issues as $file_path => &$file_issues) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->existing_issues[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($file_issues as $i => &$issue_data) {
                if ($issue_data['to'] < $first_diff_offset || $issue_data['from'] > $last_diff_offset) {
                    unset($file_issues[$i]);
                    continue;
                }

                $matched = false;

                foreach ($file_diff_map as list($from, $to, $file_offset, $line_offset)) {
                    if ($issue_data['from'] >= $from
                        && $issue_data['from'] <= $to
                        && !$matched
                    ) {
                        $issue_data['from'] += $file_offset;
                        $issue_data['to'] += $file_offset;
                        $issue_data['snippet_from'] += $file_offset;
                        $issue_data['snippet_to'] += $file_offset;
                        $issue_data['line_from'] += $line_offset;
                        $issue_data['line_to'] += $line_offset;
                        $matched = true;
                    }
                }

                if (!$matched) {
                    unset($file_issues[$i]);
                }
            }
        }

        foreach ($this->reference_map as $file_path => &$reference_map) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->reference_map[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($reference_map as $reference_from => list($reference_to, $tag)) {
                if ($reference_to < $first_diff_offset || $reference_from > $last_diff_offset) {
                    continue;
                }

                foreach ($file_diff_map as list($from, $to, $file_offset)) {
                    if ($reference_from >= $from && $reference_from <= $to) {
                        unset($reference_map[$reference_from]);
                        $reference_map[$reference_from += $file_offset] = [
                            $reference_to += $file_offset,
                            $tag
                        ];
                    }
                }
            }
        }

        foreach ($this->type_map as $file_path => &$type_map) {
            if (!isset($this->analyzed_methods[$file_path])) {
                unset($this->type_map[$file_path]);
                continue;
            }

            $file_diff_map = $diff_map[$file_path] ?? [];

            if (!$file_diff_map) {
                continue;
            }

            $first_diff_offset = $file_diff_map[0][0];
            $last_diff_offset = $file_diff_map[count($file_diff_map) - 1][1];

            foreach ($type_map as $type_from => list($type_to, $tag)) {
                if ($type_to < $first_diff_offset || $type_from > $last_diff_offset) {
                    continue;
                }


                foreach ($file_diff_map as list($from, $to, $file_offset)) {
                    if ($type_from >= $from && $type_from <= $to) {
                        unset($type_map[$type_from]);
                        $type_map[$type_from += $file_offset] = [
                            $type_to += $file_offset,
                            $tag
                        ];
                    }
                }
            }
        }
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getMixedMemberNames() : array
    {
        return $this->mixed_member_names;
    }

    /**
     * @return void
     */
    public function addMixedMemberName(string $member_id, string $reference)
    {
        $this->mixed_member_names[$member_id][$reference] = true;
    }

    public function hasMixedMemberName(string $member_id) : bool
    {
        return isset($this->mixed_member_names[$member_id]);
    }

    /**
     * @param array<string, array<string, bool>> $names
     * @return void
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function addMixedMemberNames(array $names)
    {
        $this->mixed_member_names = array_merge_recursive($this->mixed_member_names, $names);
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
        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;
        }

        return array_intersect_key($this->mixed_counts, $all_deep_scanned_files);
    }

    /**
     * @return void
     */
    public function addNodeType(
        string $file_path,
        PhpParser\Node $node,
        string $node_type,
        PhpParser\Node $parent_node = null
    ) {
        $this->type_map[$file_path][(int)$node->getAttribute('startFilePos')] = [
            ($parent_node ? (int)$parent_node->getAttribute('endFilePos') : (int)$node->getAttribute('endFilePos')) + 1,
            $node_type
        ];
    }

    /**
     * @return void
     */
    public function addNodeReference(string $file_path, PhpParser\Node $node, string $reference)
    {
        $this->reference_map[$file_path][(int)$node->getAttribute('startFilePos')] = [
            (int)$node->getAttribute('endFilePos') + 1,
            $reference
        ];
    }

    /**
     * @return void
     */
    public function addOffsetReference(string $file_path, int $start, int $end, string $reference)
    {
        $this->reference_map[$file_path][$start] = [
            $end,
            $reference
        ];
    }

    /**
     * @return array{int, int}
     */
    public function getTotalTypeCoverage(\Psalm\Codebase $codebase)
    {
        $mixed_count = 0;
        $nonmixed_count = 0;

        foreach ($codebase->file_reference_provider->getTypeCoverage() as $file_path => $counts) {
            if (!$this->config->reportTypeStatsForFile($file_path)) {
                continue;
            }

            list($path_mixed_count, $path_nonmixed_count) = $counts;

            if (isset($this->mixed_counts[$file_path])) {
                $mixed_count += $path_mixed_count;
                $nonmixed_count += $path_nonmixed_count;
            }
        }

        return [$mixed_count, $nonmixed_count];
    }

    /**
     * @return string
     */
    public function getTypeInferenceSummary(\Psalm\Codebase $codebase)
    {
        $all_deep_scanned_files = [];

        foreach ($this->files_to_analyze as $file_path => $_) {
            $all_deep_scanned_files[$file_path] = true;

            foreach ($this->file_storage_provider->get($file_path)->required_file_paths as $required_file_path) {
                $all_deep_scanned_files[$required_file_path] = true;
            }
        }

        list($mixed_count, $nonmixed_count) = $this->getTotalTypeCoverage($codebase);

        $total = $mixed_count + $nonmixed_count;

        $total_files = count($all_deep_scanned_files);

        if (!$total_files) {
            return 'No files analyzed';
        }

        if (!$total) {
            return 'Psalm was unable to infer types in the codebase';
        }

        $percentage = $nonmixed_count === $total ? '100' : number_format(100 * $nonmixed_count / $total, 4);

        return 'Psalm was able to infer types for ' . $percentage . '%'
            . ' of the codebase';
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

                if ($path_mixed_count + $path_nonmixed_count) {
                    $stats .= number_format(100 * $path_nonmixed_count / ($path_mixed_count + $path_nonmixed_count), 0)
                        . '% ' . $this->config->shortenFileName($file_path)
                        . ' (' . $path_mixed_count . ' mixed)' . "\n";
                }
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
     *
     * @return void
     */
    public function updateFile($file_path, $dry_run)
    {
        $new_return_type_manipulations = FunctionDocblockManipulator::getManipulationsForFile($file_path);

        $other_manipulations = FileManipulationBuffer::getManipulationsForFile($file_path);

        $file_manipulations = array_merge($new_return_type_manipulations, $other_manipulations);

        if (!$file_manipulations) {
            return;
        }

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

        $existing_contents = $this->file_provider->getContents($file_path);

        foreach ($file_manipulations as $manipulation) {
            $existing_contents = $manipulation->transform($existing_contents);
        }

        if ($dry_run) {
            echo $file_path . ':' . "\n";

            $differ = new \SebastianBergmann\Diff\Differ(
                new \SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder([
                    'fromFile' => $file_path,
                    'toFile' => $file_path,
                ])
            );

            echo (string) $differ->diff($this->file_provider->getContents($file_path), $existing_contents);

            return;
        }

        $this->progress->alterFileDone($file_path);

        $this->file_provider->setContents($file_path, $existing_contents);
    }

    /**
     * @param string $file_path
     * @param int $start
     * @param int $end
     *
     * @return array<int, IssueData>
     */
    public function getExistingIssuesForFile($file_path, $start, $end, ?string $issue_type = null)
    {
        if (!isset($this->existing_issues[$file_path])) {
            return [];
        }

        $applicable_issues = [];

        foreach ($this->existing_issues[$file_path] as $issue_data) {
            if ($issue_data['from'] >= $start && $issue_data['from'] <= $end) {
                if ($issue_type === null || $issue_type === $issue_data['type']) {
                    $applicable_issues[] = $issue_data;
                }
            }
        }

        return $applicable_issues;
    }

    /**
     * @param string $file_path
     * @param int $start
     * @param int $end
     *
     * @return void
     */
    public function removeExistingDataForFile($file_path, $start, $end)
    {
        if (isset($this->existing_issues[$file_path])) {
            foreach ($this->existing_issues[$file_path] as $i => $issue_data) {
                if ($issue_data['from'] >= $start && $issue_data['from'] <= $end) {
                    unset($this->existing_issues[$file_path][$i]);
                }
            }
        }

        if (isset($this->type_map[$file_path])) {
            foreach ($this->type_map[$file_path] as $map_start => $_) {
                if ($map_start >= $start && $map_start <= $end) {
                    unset($this->type_map[$file_path][$map_start]);
                }
            }
        }

        if (isset($this->reference_map[$file_path])) {
            foreach ($this->reference_map[$file_path] as $map_start => $_) {
                if ($map_start >= $start && $map_start <= $end) {
                    unset($this->reference_map[$file_path][$map_start]);
                }
            }
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getAnalyzedMethods()
    {
        return $this->analyzed_methods;
    }

    /**
     * @return array<string, array{0: TaggedCodeType, 1: TaggedCodeType}>
     */
    public function getFileMaps()
    {
        $file_maps = [];

        foreach ($this->reference_map as $file_path => $reference_map) {
            $file_maps[$file_path] = [$reference_map, []];
        }

        foreach ($this->type_map as $file_path => $type_map) {
            if (isset($file_maps[$file_path])) {
                $file_maps[$file_path][1] = $type_map;
            } else {
                $file_maps[$file_path] = [[], $type_map];
            }
        }

        return $file_maps;
    }

    /**
     * @return array{0: array<int, array{0: int, 1: string}>, 1: array<int, array{0: int, 1: string}>}
     */
    public function getMapsForFile(string $file_path)
    {
        return [
            $this->reference_map[$file_path] ?? [],
            $this->type_map[$file_path] ?? []
        ];
    }

    /**
     * @return array<string, array<int, \Psalm\Type\Union>>
     */
    public function getPossibleMethodParamTypes()
    {
        return $this->possible_method_param_types;
    }

    /**
     * @param string $file_path
     * @param string $method_id
     * @param bool $is_constructor
     *
     * @return void
     */
    public function setAnalyzedMethod($file_path, $method_id, $is_constructor = false)
    {
        $this->analyzed_methods[$file_path][$method_id] = $is_constructor ? 2 : 1;
    }

    /**
     * @param  string  $file_path
     * @param  string  $method_id
     * @param bool $is_constructor
     *
     * @return bool
     */
    public function isMethodAlreadyAnalyzed($file_path, $method_id, $is_constructor = false)
    {
        if ($is_constructor) {
            return isset($this->analyzed_methods[$file_path][$method_id])
                && $this->analyzed_methods[$file_path][$method_id] === 2;
        }

        return isset($this->analyzed_methods[$file_path][$method_id]);
    }
}
