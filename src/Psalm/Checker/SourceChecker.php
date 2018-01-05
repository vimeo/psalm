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
    public function getCheckedFileName()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getCheckedFileName();
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getCheckedFilePath();
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
     * @return ?string
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
