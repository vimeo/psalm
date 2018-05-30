<?php
namespace Psalm\Checker;

use Psalm\Aliases;
use Psalm\StatementsSource;

abstract class SourceChecker implements StatementsSource
{
    /**
     * @var StatementsSource|null
     */
    protected $source = null;

    /**
     * @return Aliases
     */
    public function getAliases()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getAliases();
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getAliasedClassesFlipped();
    }

    /**
     * @return string|null
     */
    public function getFQCLN()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getFQCLN();
    }

    /**
     * @return string|null
     */
    public function getClassName()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getClassName();
    }

    /**
     * @return FileChecker
     */
    abstract public function getFileChecker();

    /**
     * @return string|null
     */
    public function getParentFQCLN()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getParentFQCLN();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getFileName();
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getFilePath();
    }

    /**
     * @return string
     */
    public function getRootFileName()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getRootFileName();
    }

    /**
     * @return string
     */
    public function getRootFilePath()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

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
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        $this->source->setRootFilePath($file_path, $file_name);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasParentFilePath($file_path)
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->hasParentFilePath($file_path);
    }

    /**
     * @param string $file_path
     *
     * @return bool
     */
    public function hasAlreadyRequiredFilePath($file_path)
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->hasAlreadyRequiredFilePath($file_path);
    }

    /**
     * @return int
     */
    public function getRequireNesting()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getRequireNesting();
    }

    /**
     * @return StatementsSource
     */
    public function getSource()
    {
        return $this->source ?: $this;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getSuppressedIssues();
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function addSuppressedIssues(array $new_issues)
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        $this->source->addSuppressedIssues($new_issues);
    }

    /**
     * @param array<int, string> $new_issues
     *
     * @return void
     */
    public function removeSuppressedIssues(array $new_issues)
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        $this->source->removeSuppressedIssues($new_issues);
    }

    /**
     * @return null|string
     */
    public function getNamespace()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getNamespace();
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->isStatic();
    }
}
