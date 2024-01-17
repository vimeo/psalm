<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Config\ProjectFileFilter;
use Psalm\Internal\IncludeCollector;
use SimpleXMLElement;

use function getcwd;

use const DIRECTORY_SEPARATOR;

class TestConfig extends Config
{
    private static ?ProjectFileFilter $cached_project_files = null;

    public function __construct()
    {
        parent::__construct();

        foreach ($this->php_extensions as $ext => $_enabled) {
            $this->php_extensions[$ext] = true;
        }

        $this->throw_exception = true;
        $this->use_docblock_types = true;
        $this->level = 1;
        $this->cache_directory = null;
        $this->ignore_internal_falsable_issues = true;
        $this->ignore_internal_nullable_issues = true;

        $this->base_dir = (string) getcwd() . DIRECTORY_SEPARATOR;

        if (!self::$cached_project_files) {
            self::$cached_project_files = ProjectFileFilter::loadFromXMLElement(
                new SimpleXMLElement($this->getContents()),
                $this->base_dir,
                true,
            );
        }

        $this->project_files = self::$cached_project_files;
        $this->setIncludeCollector(new IncludeCollector());

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();
    }

    protected function getContents(): string
    {
        return '<?xml version="1.0"?>
                <projectFiles>
                    <directory name="src" />
                    <file name="tests/somefile.php" />
                    <ignoreFiles>
                        <directory name="src/Psalm/Internal/Stubs" />
                    </ignoreFiles>
                </projectFiles>';
    }

    /** @return false */
    public function getComposerFilePathForClassLike(string $fq_classlike_name): bool
    {
        return false;
    }

    public function getProjectDirectories(): array
    {
        return [];
    }
}
