<?php
namespace Psalm;

use Psalm\Checker\FileChecker;

interface StatementsSource extends FileSource
{
    /**
     * @return null|string
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
     * @return array<string>
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
