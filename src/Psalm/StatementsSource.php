<?php
namespace Psalm;

use Psalm\Checker\FileChecker;

interface StatementsSource
{
    /**
     * @return ?string
     */
    public function getNamespace();

    /**
     * @return Aliases
     */
    public function getAliases();

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped();

    /**
     * @return string|null
     */
    public function getFQCLN();

    /**
     * @return string|null
     */
    public function getClassName();

    /**
     * @return FileChecker
     */
    public function getFileChecker();

    /**
     * @return string|null
     */
    public function getParentFQCLN();

    /**
     * @return string
     */
    public function getFileName();

    /**
     * @return string
     */
    public function getFilePath();

    /**
     * @return string
     */
    public function getCheckedFileName();

    /**
     * @return string
     */
    public function getCheckedFilePath();

    /**
     * @param string|null $file_name
     * @param string|null $file_path
     */
    public function setFileName($file_name, $file_path);

    /**
     * @return bool
     */
    public function isStatic();

    /**
     * @return StatementsSource|null
     */
    public function getSource();

    /**
     * Get a list of suppressed issues
     *
     * @return array<int, string>
     */
    public function getSuppressedIssues();

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues);

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues);
}
