<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Config;
use Psalm\Config\ProjectFileFilter;
use Psalm\Internal\IncludeCollector;
use SimpleXMLElement;

use function getcwd;

final class TestConfig extends Config
{
    public const INIT_PROJECT_FILES_NOW = true;
    private static ?ProjectFileFilter $cached_project_files = null;

    /** @var array<string, string> */
    public array $composer_files = [];

    public function __construct()
    {
        parent::__construct();

        $this->throw_exception = true;
        $this->use_docblock_types = true;
        $this->level = 1;
        $this->cache_directory = null;
        $this->ignore_internal_falsable_issues = true;
        $this->ignore_internal_nullable_issues = true;
        $this->ensure_override_attribute = false;
        $this->strict_binary_operands = false;

        $this->base_dir = (string) getcwd();

        if (!self::$cached_project_files) {
            self::$cached_project_files = ProjectFileFilter::loadFromXMLElement(
                new SimpleXMLElement($this->getContents()),
                $this->base_dir,
                true,
            );
        }

        $this->project_files = self::$cached_project_files;
        $this->setIncludeCollector(new IncludeCollector());

        // the analysed snippets depend on no package, not even the ones Psalm requires
        $this->setProjectPackages([]);

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();
    }

    /**
     * For a test of the default mode, where the callmap stands in for the stub of an extension
     *
     * @psalm-capabilities read-props|write-this-props
     */
    public function disableExtension(string $extension): void
    {
        $this->php_extensions[$extension] = false;
    }

    /**
     * @psalm-pure
     */
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

    /**
     * @psalm-capabilities read-props
     */
    #[Override]
    public function getComposerFilePathForClassLike(string $fq_classlike_name): string|bool
    {
        return $this->composer_files[$fq_classlike_name] ?? false;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getProjectDirectories(): array
    {
        return [];
    }
}
