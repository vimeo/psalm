<?php

declare(strict_types=1);

namespace Psalm\Config;

use Override;
use Psalm\Config;
use Psalm\Exception\ConfigException;
use SimpleXMLElement;

use function dom_import_simplexml;
use function in_array;

/** @internal */
final class ErrorLevelFileFilter extends FileFilter
{
    private string $error_level = '';

    public int $suppressions = 0;

    /** Line of the <errorLevel> element in the config file, 0 if the filter doesn't come from one */
    public int $line = 0;

    #[Override]
    public static function loadFromArray(
        array $config,
        string $base_dir,
        bool $inclusive,
    ): static {
        $filter = parent::loadFromArray($config, $base_dir, $inclusive);

        if (isset($config['type'])) {
            $filter->error_level = (string) $config['type'];

            if (!in_array($filter->error_level, Config::$ERROR_LEVELS, true)) {
                throw new ConfigException('Unexpected error level ' . $filter->error_level);
            }
        } else {
            throw new ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    #[Override]
    public static function loadFromXMLElement(
        SimpleXMLElement $e,
        string $base_dir,
        bool $inclusive,
    ): static {
        $filter = parent::loadFromXMLElement($e, $base_dir, $inclusive);
        $filter->line = dom_import_simplexml($e)->getLineNo();

        if (isset($e['type'])) {
            $filter->error_level = (string) $e['type'];

            if (!in_array($filter->error_level, Config::$ERROR_LEVELS, true)) {
                throw new ConfigException('Unexpected error level ' . $filter->error_level);
            }
        } else {
            throw new ConfigException('<type> element expects a level');
        }

        return $filter;
    }

    public function getErrorLevel(): string
    {
        return $this->error_level;
    }
}
