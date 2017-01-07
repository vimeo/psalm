<?php
namespace Psalm;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FileChecker;

interface StatementsSource
{
    /**
     * @return string
     */
    public function getNamespace();

    /**
     * @return array<string, string>
     */
    public function getAliasedClasses();

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped();

    /**
     * Gets a list of all aliased constants
     *
     * @return array<string, string>
     */
    public function getAliasedConstants();

    /**
     * Gets a list of all aliased functions
     *
     * @return array<string, string>
     */
    public function getAliasedFunctions();

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
     * @return string|null
     */
    public function getIncludeFileName();

    /**
     * @return string|null
     */
    public function getIncludeFilePath();

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
    public function setIncludeFileName($file_name, $file_path);

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
}
