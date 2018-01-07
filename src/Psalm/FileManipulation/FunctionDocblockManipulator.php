<?php
namespace Psalm\FileManipulation;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
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

    /** @var int */
    private $return_typehint_area_start;

    /** @var ?int */
    private $return_typehint_start;

    /** @var ?int */
    private $return_typehint_end;

    /** @var ?string */
    private $new_php_return_type;

    /** @var bool */
    private $return_type_is_php_compatible = false;

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
    public static function getForFunction(
        ProjectChecker $project_checker,
        $file_path,
        $function_id,
        FunctionLike $stmt
    ) {
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
    private function __construct($file_path, FunctionLike $stmt, ProjectChecker $project_checker)
    {
        $this->stmt = $stmt;
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = $function_start = (int)$stmt->getAttribute('startFilePos');
        $function_end = (int)$stmt->getAttribute('endFilePos');

        $file_contents = $project_checker->getFileContents($file_path);

        $last_arg_position = $stmt->params
            ? (int) $stmt->params[count($stmt->params) - 1]->getAttribute('endFilePos')
            : null;

        if ($stmt instanceof Closure && $stmt->uses) {
            $last_arg_position = (int) $stmt->uses[count($stmt->uses) - 1]->getAttribute('endFilePos');
        }

        $end_bracket_position = (int) strpos($file_contents, ')', $last_arg_position ?: $function_start);

        $this->return_typehint_area_start = $end_bracket_position + 1;

        $function_code = substr($file_contents, $function_start, $function_end);

        $function_code_after_bracket = substr($function_code, $end_bracket_position + 1 - $function_start);

        // do a little parsing here
        /** @var array<int, string> */
        $chars = str_split($function_code_after_bracket);

        foreach ($chars as $i => $char) {
            switch ($char) {
                case PHP_EOL:
                    continue;

                case ':':
                    continue;

                case '/':
                    // @todo handle comments in this area
                    throw new \UnexpectedValueException('Not expecting comments where return types should live');

                case '{':
                    break 2;

                case '?':
                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                    break;
            }

            if (preg_match('/\w/', $char)) {
                if ($this->return_typehint_start === null) {
                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                }

                if (!preg_match('/\w/', $chars[$i + 1])) {
                    $this->return_typehint_end = $i + $end_bracket_position + 2;
                    break;
                }
            }
        }

        $preceding_newline_pos = strrpos($file_contents, PHP_EOL, $this->docblock_end - strlen($file_contents));

        if ($preceding_newline_pos === false) {
            $this->indentation = '';

            return;
        }

        $first_line = substr($file_contents, $preceding_newline_pos + 1, $this->docblock_end - $preceding_newline_pos);

        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    /**
     * Sets the new docblock return type
     *
     * @param   ?string     $php_type
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     * @param   bool        $is_php_compatible
     *
     * @return  void
     */
    public function setReturnType($php_type, $new_type, $phpdoc_type, $is_php_compatible)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        $this->new_php_return_type = $php_type;
        $this->new_phpdoc_return_type = $phpdoc_type;
        $this->new_psalm_return_type = $new_type;
        $this->return_type_is_php_compatible = $is_php_compatible;
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

        if ($this->new_phpdoc_return_type) {
            $parsed_docblock['specials']['return'] = [$this->new_phpdoc_return_type];
        }

        if ($this->new_phpdoc_return_type !== $this->new_psalm_return_type && $this->new_psalm_return_type) {
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
            if ($manipulator->new_php_return_type) {
                if ($manipulator->return_typehint_start && $manipulator->return_typehint_end) {
                    $file_manipulations[$manipulator->return_typehint_start] = new FileManipulation(
                        $manipulator->return_typehint_start,
                        $manipulator->return_typehint_end,
                        $manipulator->new_php_return_type
                    );
                } else {
                    $file_manipulations[$manipulator->return_typehint_area_start] = new FileManipulation(
                        $manipulator->return_typehint_area_start,
                        $manipulator->return_typehint_area_start,
                        ' : ' . $manipulator->new_php_return_type
                    );
                }
            }

            if (!$manipulator->new_php_return_type
                || !$manipulator->return_type_is_php_compatible
                || $manipulator->docblock_start !== $manipulator->docblock_end
            ) {
                $file_manipulations[$manipulator->docblock_start] = new FileManipulation(
                    $manipulator->docblock_start,
                    $manipulator->docblock_end,
                    $manipulator->getDocblock()
                );
            }
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
