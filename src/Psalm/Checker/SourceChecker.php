<?php
namespace Psalm\Checker;

use PhpParser\Node\Stmt\Namespace_;
use PhpParser;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

abstract class SourceChecker implements StatementsSource
{
    /**
     * @var StatementsSource|null
     */
    protected $source = null;

    /**
     * @return array<string, string>
     */
    public function getAliasedClasses()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getAliasedClasses();
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
     * Gets a list of all aliased constants
     *
     * @return array<string, string>
     */
    public function getAliasedConstants()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getAliasedConstants();
    }

    /**
     * Gets a list of all aliased functions
     *
     * @return array<string, string>
     */
    public function getAliasedFunctions()
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        return $this->source->getAliasedFunctions();
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
    public function getFileChecker()
    {
        if ($this instanceof FileChecker) {
            return $this;
        }

        if ($this->source === null) {
            throw new \UnexpectedValueException('$this->source should not be null');
        }

        return $this->source->getFileChecker();
    }

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
     * @param string|null $file_name
     * @param string|null $file_path
     * @return void
     */
    public function setFileName($file_name, $file_path)
    {
        if ($this->source === null) {
            throw new \UnexpectedValueException('$source cannot be null');
        }

        $this->source->setFileName($file_name, $file_path);
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
     * @return string
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
