<?php
namespace Psalm;

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
     * @return array<string>
     */
    public function getAliasedClassesFlipped();

    /**
     * Gets a list of all aliased constants
     *
     * @return array
     */
    public function getAliasedConstants();

    /**
     * Gets a list of all aliased functions
     *
     * @return array
     */
    public function getAliasedFunctions();

    /**
     * @return string
     */
    public function getFQCLN();

    /**
     * @return string
     */
    public function getClassName();

    /**
     * @return string
     */
    public function getClassLikeChecker();

    /**
     * @return string|null
     */
    public function getParentClass();

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
     * @return array<string>
     */
    public function getSuppressedIssues();
}
