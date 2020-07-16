<?php
namespace Psalm\Config;

use function array_filter;
use function array_map;
use function dirname;
use function in_array;
use function scandir;
use SimpleXMLElement;
use function strtolower;
use function substr;
use const SCANDIR_SORT_NONE;

class IssueHandler
{
    /**
     * @var string
     */
    private $error_level = \Psalm\Config::REPORT_ERROR;

    /**
     * @var array<ErrorLevelFileFilter>
     */
    private $custom_levels = [];

    /**
     * @param  SimpleXMLElement $e
     * @param  string           $base_dir
     *
     * @return self
     */
    public static function loadFromXMLElement(SimpleXMLElement $e, $base_dir)
    {
        $handler = new self();

        if (isset($e['errorLevel'])) {
            $handler->error_level = (string) $e['errorLevel'];

            if (!in_array($handler->error_level, \Psalm\Config::$ERROR_LEVELS, true)) {
                throw new \Psalm\Exception\ConfigException('Unexpected error level ' . $handler->error_level);
            }
        }

        /** @var \SimpleXMLElement $error_level */
        foreach ($e->errorLevel as $error_level) {
            $handler->custom_levels[] = ErrorLevelFileFilter::loadFromXMLElement($error_level, $base_dir, true);
        }

        return $handler;
    }

    /**
     * @param string $error_level
     *
     * @return void
     */
    public function setErrorLevel($error_level)
    {
        if (!in_array($error_level, \Psalm\Config::$ERROR_LEVELS, true)) {
            throw new \Psalm\Exception\ConfigException('Unexpected error level ' . $error_level);
        }

        $this->error_level = $error_level;
    }

    /**
     * @param string $file_path
     *
     * @return string
     */
    public function getReportingLevelForFile($file_path)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allows($file_path)) {
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }

    /**
     * @param string $fq_classlike_name
     *
     * @return string|null
     */
    public function getReportingLevelForClass($fq_classlike_name)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsClass($fq_classlike_name)) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @param string $method_id
     *
     * @return string|null
     */
    public function getReportingLevelForMethod($method_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($method_id))) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @return string|null
     */
    public function getReportingLevelForFunction(string $function_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @return string|null
     */
    public function getReportingLevelForArgument(string $function_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($function_id))) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @param string $property_id
     *
     * @return string|null
     */
    public function getReportingLevelForProperty($property_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsProperty($property_id)) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @param string $var_name
     *
     * @return string|null
     */
    public function getReportingLevelForVariable($var_name)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsVariable($var_name)) {
                return $custom_level->getErrorLevel();
            }
        }
    }

    /**
     * @return       string[]
     * @psalm-return array<string>
     */
    public static function getAllIssueTypes()
    {
        return array_filter(
            array_map(
                /**
                 * @param string $file_name
                 *
                 * @return string
                 */
                function ($file_name) {
                    return substr($file_name, 0, -4);
                },
                scandir(dirname(__DIR__) . '/Issue', SCANDIR_SORT_NONE)
            ),
            /**
             * @param string $issue_name
             *
             * @return bool
             */
            function ($issue_name) {
                return !empty($issue_name)
                    && $issue_name !== 'MethodIssue'
                    && $issue_name !== 'PropertyIssue'
                    && $issue_name !== 'FunctionIssue'
                    && $issue_name !== 'ArgumentIssue'
                    && $issue_name !== 'VariableIssue'
                    && $issue_name !== 'ClassIssue'
                    && $issue_name !== 'CodeIssue'
                    && $issue_name !== 'PsalmInternalError'
                    && $issue_name !== 'ParseError'
                    && $issue_name !== 'PluginIssue';
            }
        );
    }
}
