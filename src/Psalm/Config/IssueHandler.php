<?php
namespace Psalm\Config;

use Psalm\Config;
use SimpleXMLElement;

class IssueHandler
{
    /**
     * @var string
     */
    protected $error_level = \Psalm\Config::REPORT_ERROR;

    /**
     * @var array<ErrorLevelFileFilter>
     */
    protected $custom_levels = [];

    /**
     * @param  SimpleXMLElement $e
     * @param  Config           $config
     * @return self
     */
    public static function loadFromXMLElement(SimpleXMLElement $e, Config $config)
    {
        $handler = new self();

        if (isset($e['errorLevel'])) {
            $handler->error_level = (string) $e['errorLevel'];

            if (!in_array($handler->error_level, \Psalm\Config::$ERROR_LEVELS)) {
                throw new \Psalm\Exception\ConfigException('Unexepected error level ' . $handler->error_level);
            }
        }

        /** @var \SimpleXMLElement $error_level */
        foreach ($e->errorLevel as $error_level) {
            $handler->custom_levels[] = ErrorLevelFileFilter::loadFromXMLElement($error_level, $config, true);
        }

        return $handler;
    }

    /**
     * @param string $error_level
     * @return void
     */
    public function setErrorLevel($error_level)
    {
        if (!in_array($error_level, \Psalm\Config::$ERROR_LEVELS)) {
            throw new \Psalm\Exception\ConfigException('Unexepected error level ' . $error_level);
        }

        $this->error_level = $error_level;
    }

    /**
     * @param string $file_path
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
}
