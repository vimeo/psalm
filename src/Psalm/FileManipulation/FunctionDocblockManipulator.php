<?php
namespace Psalm\FileManipulation;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\Checker\CommentChecker;
use Psalm\Checker\ProjectChecker;

class FunctionDocblockManipulator
{
    /** @var array<string, array<string, FunctionDocblockManipulator>> */
    private static $manipulators = [];

    /**
     * Manipulators ordered by line number
     *
     * @var array<string, array<int, FunctionDocblockManipulator>>
     */
    private static $ordered_manipulators = [];

    /** @var Closure|Function_|ClassMethod */
    private $stmt;

    /** @var int */
    private $docblock_start;

    /** @var int */
    private $docblock_end;

    /** @var ?string */
    private $new_phpdoc_return_type;

    /** @var ?string */
    private $new_psalm_return_type;

    /** @var string */
    private $indentation;

    /**
     * @param  string $file_path
     * @param  string $function_id
     * @param  Closure|Function_|ClassMethod $stmt
     *
     * @return self
     */
    public static function getForFunction(ProjectChecker $project_checker, $file_path, $function_id, $stmt)
    {
        if (isset(self::$manipulators[$file_path][$function_id])) {
            return self::$manipulators[$file_path][$function_id];
        }

        $manipulator
            = self::$manipulators[$file_path][$function_id]
            = self::$ordered_manipulators[$file_path][$stmt->getLine()]
            = new self($file_path, $stmt, $project_checker);

        return $manipulator;
    }

    /**
     * @param string $file_path
     * @param Closure|Function_|ClassMethod $stmt
     */
    private function __construct($file_path, $stmt, ProjectChecker $project_checker)
    {
        $this->stmt = $stmt;
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = (int)$stmt->getAttribute('startFilePos');

        $file_lines = explode(PHP_EOL, $project_checker->getFileContents($file_path));
        $first_line = $file_lines[$stmt->getLine() - 1];
        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    /**
     * Sets the new docblock return type
     *
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     *
     * @return  void
     */
    public function setDocblockReturnType($new_type, $phpdoc_type)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        $this->new_phpdoc_return_type = $phpdoc_type;
        $this->new_psalm_return_type = $new_type;
    }

    /**
     * Gets a new docblock given the existing docblock, if one exists, and the updated return types
     * and/or parameters
     *
     * @return string
     */
    private function getDocblock()
    {
        $docblock = $this->stmt->getDocComment();

        if ($docblock) {
            $parsed_docblock = CommentChecker::parseDocComment((string)$docblock);
        } else {
            $parsed_docblock = ['description' => ''];
        }

        $parsed_docblock['specials']['return'] = [$this->new_phpdoc_return_type];

        if ($this->new_phpdoc_return_type !== $this->new_psalm_return_type) {
            $parsed_docblock['specials']['psalm-return'] = [$this->new_psalm_return_type];
        }

        return CommentChecker::renderDocComment($parsed_docblock, $this->indentation);
    }

    /**
     * @param  string $file_path
     *
     * @return array<int, FileManipulation>
     */
    public static function getManipulationsForFile($file_path)
    {
        if (!isset(self::$manipulators[$file_path])) {
            return [];
        }

        $file_manipulations = [];

        foreach (self::$ordered_manipulators[$file_path] as $manipulator) {
            $file_manipulations[$manipulator->docblock_start] = new FileManipulation(
                $manipulator->docblock_start,
                $manipulator->docblock_end,
                $manipulator->getDocblock()
            );
        }

        return $file_manipulations;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$manipulators = [];
        self::$ordered_manipulators = [];
    }
}
