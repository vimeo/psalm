<?php
namespace Psalm\Scanner;

use PhpParser;
use PhpParser\NodeTraverser;
use Psalm\Codebase;
use Psalm\FileSource;
use Psalm\Storage\FileStorage;
use Psalm\Visitor\DependencyFinderVisitor;

class FileScanner implements FileSource
{
    /**
     * @var string
     */
    public $file_path;

    /**
     * @var string
     */
    public $file_name;

    /**
     * @var bool
     */
    public $will_analyze;

    /**
     * @param string $file_path
     * @param string $file_name
     * @param bool $will_analyze
     */
    public function __construct($file_path, $file_name, $will_analyze)
    {
        $this->file_path = $file_path;
        $this->file_name = $file_name;
        $this->will_analyze = $will_analyze;
    }

    /**
     * @param array<mixed, PhpParser\Node> $stmts
     *
     * @return void
     */
    public function scan(Codebase $codebase, array $stmts, FileStorage $file_storage)
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new DependencyFinderVisitor($codebase, $file_storage, $this));
        $traverser->traverse($stmts);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        return $this->file_path;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->file_name;
    }
}
