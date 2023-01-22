<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli\Commands;

use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use const PHP_VERSION;

final class DisplayConfig extends Command
{
    private Config $config;
    private ProjectAnalyzer $project_analyzer;
    private string $current_dir;

    public function __construct(Config $config, ProjectAnalyzer $project_analyzer, string $current_dir)
    {
        parent::__construct();

        $this->config = $config;
        $this->project_analyzer = $project_analyzer;
        $this->current_dir = $current_dir;
    }

    public function debug(): void
    {
        $this->execute(new ArgvInput(), new ConsoleOutput());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new PsalmStyle($input, $output);

        $io->title('Psalm config');

        $io->definitionList(
            ['Psalm version' => PSALM_VERSION],
            ['PHP Version' => "{$this->config->getPhpVersion()} (inferred from {$this->project_analyzer->getCodebase()->php_version_source})"],
            ['Error level' => $this->config->level],
            new TableSeparator(),
            ['current_dir' => $this->current_dir ?: 'n/a'],
            ['Base dir' => $this->config->base_dir],
            ['cache_directory' => $this->config->cache_directory ?: 'n/a'],
            ['global_cache_directory' => $this->config->global_cache_directory ?: 'n/a'],
            ['autoloader' => $this->config->autoloader ?: 'n/a'],
            ['error_baseline' => $this->config->error_baseline ?: 'n/a'],
        );

        $io->namedListing('Plugins:', $this->config->plugin_paths);
        $io->namedListing('External stubs:', $this->config->getStubFiles());
        $io->namedListing('Internal stubs:', $this->config->internal_stubs);
        $io->namedListing('Project directories:', $this->config->getProjectDirectories());
        $io->namedListing('Project files:', $this->config->getProjectFiles());

        $io->definitionList(
            ['Parameter' => 'Value'],
            new TableSeparator(),
            ['find_unused_code' => $this->formatBool($this->config->find_unused_code)],
            ['find_unused_variables' => $this->formatBool($this->config->find_unused_variables)],
            ['find_unused_psalm_suppress' => $this->formatBool($this->config->find_unused_psalm_suppress)],
            ['run_taint_analysis' => $this->formatBool($this->config->run_taint_analysis)],
            ['use_phpstorm_meta_path' => $this->formatBool($this->config->use_phpstorm_meta_path)],
            ['use_docblock_types' => $this->formatBool($this->config->use_docblock_types)],
            ['use_docblock_property_types' => $this->formatBool($this->config->use_docblock_property_types)],
            ['hide_external_errors' => $this->formatBool($this->config->hide_external_errors)],
            ['hide_all_errors_except_passed_files' => $this->formatBool($this->config->hide_all_errors_except_passed_files)],
            ['allow_includes' => $this->formatBool($this->config->allow_includes)],
            ['show_mixed_issues' => $this->formatBool($this->config->show_mixed_issues)],
            ['strict_binary_operands' => $this->formatBool($this->config->strict_binary_operands)],
            ['remember_property_assignments_after_call' => $this->formatBool($this->config->remember_property_assignments_after_call)],
            ['use_igbinary' => $this->formatBool($this->config->use_igbinary)],
            ['allow_string_standin_for_class' => $this->formatBool($this->config->allow_string_standin_for_class)],
            ['disable_suppress_all' => $this->formatBool($this->config->disable_suppress_all)],
            ['use_phpdoc_method_without_magic_or_parent' => $this->formatBool($this->config->use_phpdoc_method_without_magic_or_parent)],
            ['use_phpdoc_property_without_magic_or_parent' => $this->formatBool($this->config->use_phpdoc_property_without_magic_or_parent)],
            ['skip_checks_on_unresolvable_includes' => $this->formatBool($this->config->skip_checks_on_unresolvable_includes)],
            ['seal_all_methods' => $this->formatBool($this->config->seal_all_methods)],
            ['seal_all_properties' => $this->formatBool($this->config->seal_all_properties)],
            ['memoize_method_calls' => $this->formatBool($this->config->memoize_method_calls)],
            ['hoist_constants' => $this->formatBool($this->config->hoist_constants)],
            ['add_param_default_to_docblock_type' => $this->formatBool($this->config->add_param_default_to_docblock_type)],
            ['disable_var_parsing' => $this->formatBool($this->config->disable_var_parsing)],
            ['check_for_throws_docblock' => $this->formatBool($this->config->check_for_throws_docblock)],
            ['check_for_throws_in_global_scope' => $this->formatBool($this->config->check_for_throws_in_global_scope)],
            ['ignore_internal_falsable_issues' => $this->formatBool($this->config->ignore_internal_falsable_issues)],
            ['ignore_internal_nullable_issues' => $this->formatBool($this->config->ignore_internal_nullable_issues)],
        );

        $io->title('Env config');
        $io->definitionList(
            ['PHP version' => PHP_VERSION],
        );

        return self::SUCCESS;
    }

    private function formatBool(?bool $boolValue): string
    {
        if ($boolValue === null) {
            return 'n/a';
        }

        return $boolValue ? 'true' : 'false';
    }
}
