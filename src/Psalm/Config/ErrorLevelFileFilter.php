<?php
namespace Psalm\Config;

use Psalm\Config;
use SimpleXMLElement;

class ErrorLevelFileFilter extends FileFilter
{
    /**
     * @var string
     */
    private $error_level = "";

    /**
     * @param  SimpleXMLElement $e
     * @param  bool             $inclusive
     * @param  string           $base_dir
     * @return self
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        $base_dir,
        $inclusive
    ) {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);

        if (isset($e['type'])) {
            $filter->error_level = (string) $e['type'];

            if (!in_array($filter->error_level, \Psalm\Config::$ERROR_LEVELS)) {
                throw new \Psalm\Exception\ConfigException('Unexepected error level ' . $filter->error_level);
            }
        } else {
            throw new \Psalm\Exception\ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    /**
     * @return string
     */
    public function getErrorLevel()
    {
        return $this->error_level;
    }
}
