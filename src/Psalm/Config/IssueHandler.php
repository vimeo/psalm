<?php

declare(strict_types=1);

namespace Psalm\Config;

use Psalm\Config;
use Psalm\Exception\ConfigException;
use SimpleXMLElement;

use function array_filter;
use function array_map;
use function assert;
use function dirname;
use function in_array;
use function scandir;
use function strtolower;
use function substr;

use const SCANDIR_SORT_NONE;

/** @internal */
final class IssueHandler
{
    private string $error_level = Config::REPORT_ERROR;

    /**
     * @var list<ErrorLevelFileFilter>
     */
    private array $custom_levels = [];

    public static function loadFromXMLElement(SimpleXMLElement $e, string $base_dir): self
    {
        $handler = new self();

        if (isset($e['errorLevel'])) {
            $handler->error_level = (string) $e['errorLevel'];

            if (!in_array($handler->error_level, Config::$ERROR_LEVELS, true)) {
                throw new ConfigException('Unexpected error level ' . $handler->error_level);
            }
        }

        if (isset($e->errorLevel)) {
            foreach ($e->errorLevel as $error_level) {
                $handler->custom_levels[] = ErrorLevelFileFilter::loadFromXMLElement($error_level, $base_dir, true);
            }
        }

        return $handler;
    }

    /** @return list<ErrorLevelFileFilter> */
    public function getFilters(): array
    {
        return $this->custom_levels;
    }

    public function setCustomLevels(array $customLevels, string $base_dir): void
    {
        /** @var array $customLevel */
        foreach ($customLevels as $customLevel) {
            $this->custom_levels[] = ErrorLevelFileFilter::loadFromArray($customLevel, $base_dir, true);
        }
    }

    public function setErrorLevel(string $error_level): void
    {
        if (!in_array($error_level, Config::$ERROR_LEVELS, true)) {
            throw new ConfigException('Unexpected error level ' . $error_level);
        }

        $this->error_level = $error_level;
    }

    public function getReportingLevelForFile(string $file_path): string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allows($file_path)) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }

    public function getReportingLevelForClass(string $fq_classlike_name): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsClass($fq_classlike_name)) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForMethod(string $method_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($method_id))) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForFunction(string $function_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForArgument(string $function_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForProperty(string $property_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsProperty($property_id)) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForClassConstant(string $constant_id): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsClassConstant($constant_id)) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    public function getReportingLevelForVariable(string $var_name): ?string
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsVariable($var_name)) {
                $custom_level->suppressions++;
                return $custom_level->getErrorLevel();
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function getAllIssueTypes(): array
    {
        $scan = scandir(dirname(__DIR__) . '/Issue', SCANDIR_SORT_NONE);
        assert($scan !== false);
        return array_filter(
            array_map(
                static fn(string $file_name): string => substr($file_name, 0, -4),
                $scan,
            ),
            static fn(string $issue_name): bool => $issue_name !== ''
                && $issue_name !== 'MethodIssue'
                && $issue_name !== 'PropertyIssue'
                && $issue_name !== 'ClassConstantIssue'
                && $issue_name !== 'FunctionIssue'
                && $issue_name !== 'ArgumentIssue'
                && $issue_name !== 'VariableIssue'
                && $issue_name !== 'ClassIssue'
                && $issue_name !== 'CodeIssue'
                && $issue_name !== 'PsalmInternalError'
                && $issue_name !== 'ParseError'
                && $issue_name !== 'PluginIssue'
                && $issue_name !== 'MixedIssue'
                && $issue_name !== 'MixedIssueTrait',
        );
    }
}
