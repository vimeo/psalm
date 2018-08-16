<?php
namespace Psalm\Config;

use SimpleXMLElement;

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
                throw new \Psalm\Exception\ConfigException('Unexepected error level ' . $handler->error_level);
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
            throw new \Psalm\Exception\ConfigException('Unexepected error level ' . $error_level);
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
     * @return string
     */
    public function getReportingLevelForClass($fq_classlike_name)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsClass($fq_classlike_name)) {
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }

    /**
     * @param string $method_id
     *
     * @return string
     */
    public function getReportingLevelForMethod($method_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsMethod(strtolower($method_id))) {
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }

    /**
     * @param string $property_id
     *
     * @return string
     */
    public function getReportingLevelForProperty($property_id)
    {
        foreach ($this->custom_levels as $custom_level) {
            if ($custom_level->allowsProperty($property_id)) {
                return $custom_level->getErrorLevel();
            }
        }

        return $this->error_level;
    }
}
