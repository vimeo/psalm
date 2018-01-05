<?php
namespace Psalm;

use Psalm\Checker\FileChecker;

class TraitSource implements StatementsSource
{
    /** @var Aliases */
    private $aliases;

    /** @var FileChecker */
    private $file_checker;

    public function __construct(FileChecker $file_checker, Aliases $aliases)
    {
        $this->aliases = $aliases;
        $this->file_checker = $file_checker;
    }

    /**
     * @return ?string
     */
    public function getNamespace()
    {
        return $this->aliases->namespace;
    }

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getFQCLN()
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return null;
    }

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this->file_checker;
    }

    /**
     * @return string|null
     */
    public function getParentFQCLN()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_checker->getFileName();
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->file_checker->getFilePath();
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->file_checker->getCheckedFileName();
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        return $this->file_checker->getCheckedFilePath();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * @return FileChecker
     */
    public function getSource()
    {
        return $this->file_checker;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<int, string>
     */
    public function getSuppressedIssues()
    {
        return [];
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues)
    {
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
    }
}
