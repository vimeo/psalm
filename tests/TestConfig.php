<?php
namespace Psalm\Tests;

use Psalm\Config;
use Psalm\Internal\IncludeCollector;
use SimpleXMLElement;

use function getcwd;

use const DIRECTORY_SEPARATOR;

class TestConfig extends Config
{
    /** @var Config\ProjectFileFilter|null */
    private static $cached_project_files = null;

    /**
     * @psalm-suppress PossiblyNullPropertyAssignmentValue because cache_directory isn't strictly nullable
     */
    public function __construct()
    {
        parent::__construct();

        $this->throw_exception = true;
        $this->use_docblock_types = true;
        $this->level = 1;
        $this->cache_directory = null;

        $this->base_dir = getcwd() . DIRECTORY_SEPARATOR;

        if (!self::$cached_project_files) {
            self::$cached_project_files = Config\ProjectFileFilter::loadFromXMLElement(
                new SimpleXMLElement($this->getContents()),
                $this->base_dir,
                true
            );
        }

        $this->project_files = self::$cached_project_files;
        $this->setIncludeCollector(new IncludeCollector());

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();
    }

    protected function getContents() : string
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

    /**
     * @return false
     */
    public function getComposerFilePathForClassLike(string $fq_classlike_name): bool
    {
        return false;
    }

    public function getProjectDirectories(): array
    {
        return [];
    }
}
