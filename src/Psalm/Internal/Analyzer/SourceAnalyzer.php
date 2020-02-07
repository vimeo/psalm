<?php
namespace Psalm\Internal\Analyzer;

use Psalm\Aliases;
use Psalm\Codebase;
use Psalm\StatementsSource;
use Psalm\Type;

/**
 * @internal
 */
abstract class SourceAnalyzer implements StatementsSource
{
    /**
     * @var SourceAnalyzer
     */
    protected $source;

    public function __destruct()
    {
        $this->source = null;
    }

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        return $this->source->getAliases();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        return $this->source->getAliasedClassesFlipped();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlippedReplaceable()
    {
        return $this->source->getAliasedClassesFlippedReplaceable();
    }

    /**
     * @return string|null
     */
    public function getFQCLN()
    {
        return $this->source->getFQCLN();
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        return $this->source->getClassName();
    }

    /**
     * @return string|null
     */
    public function getParentFQCLN()
    {
        return $this->source->getParentFQCLN();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->source->getFileName();
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->source->getFilePath();
    }

    /**
     * @return string
     */
    public function getRootFileName()
    {
        return $this->source->getRootFileName();
    }

    /**
     * @return string
     */
    public function getRootFilePath()
    {
        return $this->source->getRootFilePath();
    }

    /**
     * @param string $file_path
     * @param string $file_name
     *
     * @return void
     */
    public function setRootFilePath($file_path, $file_name)
    {
        $this->source->setRootFilePath($file_path, $file_name);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasParentFilePath($file_path)
    {
        return $this->source->hasParentFilePath($file_path);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasAlreadyRequiredFilePath($file_path)
    {
        return $this->source->hasAlreadyRequiredFilePath($file_path);
    }

    /**
     * @return int
     */
    public function getRequireNesting()
    {
        return $this->source->getRequireNesting();
    }

    /**
     * @return StatementsSource
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->source->getSuppressedIssues();
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues)
    {
        $this->source->addSuppressedIssues($new_issues);
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
        $this->source->removeSuppressedIssues($new_issues);
    }

    /**
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->source->getNamespace();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return $this->source->isStatic();
    }

    public function getCodebase() : Codebase
    {
        return $this->source->getCodebase();
    }

    public function getProjectAnalyzer() : ProjectAnalyzer
    {
        return $this->source->getProjectAnalyzer();
    }

    public function getFileAnalyzer() : FileAnalyzer
    {
        return $this->source->getFileAnalyzer();
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>|null
     */
    public function getTemplateTypeMap()
    {
        return $this->source->getTemplateTypeMap();
    }

    public function getNodeTypeProvider() : \Psalm\NodeTypeProvider
    {
        return $this->source->getNodeTypeProvider();
    }
}
