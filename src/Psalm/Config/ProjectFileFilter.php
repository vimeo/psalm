<?php
namespace Psalm\Config;

use SimpleXMLElement;

class ProjectFileFilter extends FileFilter
{
    /**
     * @var ProjectFileFilter|null
     */
    private $file_filter = null;

    /**
     * @param  SimpleXMLElement $e
     * @param  string           $base_dir
     * @param  bool             $inclusive
     *
     * @return static
     */
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        $base_dir,
        $inclusive
    ) {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);

        if (isset($e->ignoreFiles)) {
            if (!$inclusive) {
                throw new \Psalm\Exception\ConfigException('Cannot nest ignoreFiles inside itself');
            }

            /** @var \SimpleXMLElement $e->ignoreFiles */
            $filter->file_filter = static::loadFromXMLElement($e->ignoreFiles, $base_dir, false);
        }

        return $filter;
    }

    /**
     * @param  string  $file_name
     * @param  bool $case_sensitive
     *
     * @return bool
     */
    public function allows($file_name, $case_sensitive = false)
    {
        if ($this->inclusive && $this->file_filter) {
            if (!$this->file_filter->allows($file_name, $case_sensitive)) {
                return false;
            }
        }

        return parent::allows($file_name, $case_sensitive);
    }

    /**
     * @param  string  $file_name
     * @param  bool $case_sensitive
     *
     * @return bool
     */
    public function forbids($file_name, $case_sensitive = false)
    {
        if ($this->inclusive && $this->file_filter) {
            if (!$this->file_filter->allows($file_name, $case_sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string $file_name
     * @param  bool   $case_sensitive
     *
     * @return bool
     */
    public function reportTypeStats($file_name, $case_sensitive = false)
    {
        foreach ($this->ignore_type_stats as $exclude_dir => $_) {
            if ($case_sensitive) {
                if (strpos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            } else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  string $file_name
     * @param  bool   $case_sensitive
     *
     * @return bool
     */
    public function useStrictTypes($file_name, $case_sensitive = false)
    {
        foreach ($this->declare_strict_types as $exclude_dir => $_) {
            if ($case_sensitive) {
                if (strpos($file_name, $exclude_dir) === 0) {
                    return true;
                }
            } else {
                if (stripos($file_name, $exclude_dir) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
