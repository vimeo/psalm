<?php
namespace Psalm\Internal\FileManipulation;

use Psalm\FileManipulation;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Psalm\DocComment;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

/**
 * @internal
 */
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

    /** @var null|int */
    private $return_typehint_colon_start;

    /** @var null|int */
    private $return_typehint_start;

    /** @var null|int */
    private $return_typehint_end;

    /** @var null|string */
    private $new_php_return_type;

    /** @var bool */
    private $return_type_is_php_compatible = false;

    /** @var null|string */
    private $new_phpdoc_return_type;

    /** @var null|string */
    private $new_psalm_return_type;

    /** @var array<string, int> */
    private $param_typehint_area_starts = [];

    /** @var array<string, int> */
    private $param_typehint_starts = [];

    /** @var array<string, int> */
    private $param_typehint_ends = [];

    /** @var array<string, string> */
    private $new_php_param_types = [];

    /** @var array<string, bool> */
    private $param_type_is_php_compatible = [];

    /** @var array<string, string> */
    private $new_phpdoc_param_types = [];

    /** @var array<string, string> */
    private $new_psalm_param_types = [];

    /** @var string */
    private $indentation;

    /** @var string|null */
    private $return_type_description;

    /**
     * @param  string $file_path
     * @param  string $function_id
     * @param  Closure|Function_|ClassMethod $stmt
     *
     * @return self
     */
    public static function getForFunction(
        ProjectAnalyzer $project_analyzer,
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
            = new self($file_path, $stmt, $project_analyzer);

        return $manipulator;
    }

    /**
     * @param string $file_path
     * @param Closure|Function_|ClassMethod $stmt
     */
    private function __construct($file_path, FunctionLike $stmt, ProjectAnalyzer $project_analyzer)
    {
        $this->stmt = $stmt;
        $docblock = $stmt->getDocComment();
        $this->docblock_start = $docblock ? $docblock->getFilePos() : (int)$stmt->getAttribute('startFilePos');
        $this->docblock_end = $function_start = (int)$stmt->getAttribute('startFilePos');
        $function_end = (int)$stmt->getAttribute('endFilePos');

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($file_path);

        $last_arg_position = $stmt->params
            ? (int) $stmt->params[count($stmt->params) - 1]->getAttribute('endFilePos') + 1
            : null;

        if ($stmt instanceof Closure && $stmt->uses) {
            $last_arg_position = (int) $stmt->uses[count($stmt->uses) - 1]->getAttribute('endFilePos') + 1;
        }

        $end_bracket_position = (int) strpos($file_contents, ')', $last_arg_position ?: $function_start);

        $this->return_typehint_area_start = $end_bracket_position + 1;

        $function_code = substr($file_contents, $function_start, $function_end);

        $function_code_after_bracket = substr($function_code, $end_bracket_position + 1 - $function_start);

        // do a little parsing here
        $chars = str_split($function_code_after_bracket);

        $in_single_line_comment = $in_multi_line_comment = false;

        for ($i = 0; $i < count($chars); ++$i) {
            $char = $chars[$i];

            switch ($char) {
                case "\n":
                    $in_single_line_comment = false;
                    continue 2;

                case ':':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    $this->return_typehint_colon_start = $i + $end_bracket_position + 1;

                    continue 2;

                case '/':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    if ($chars[$i + 1] === '*') {
                        $in_multi_line_comment = true;
                        ++$i;
                    }

                    if ($chars[$i + 1] === '/') {
                        $in_single_line_comment = true;
                        ++$i;
                    }

                    continue 2;

                case '*':
                    if ($in_single_line_comment) {
                        continue 2;
                    }

                    if ($chars[$i + 1] === '/') {
                        $in_multi_line_comment = false;
                        ++$i;
                    }

                    continue 2;

                case '{':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    break 2;

                case '?':
                    if ($in_multi_line_comment || $in_single_line_comment) {
                        continue 2;
                    }

                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                    break;
            }

            if ($in_multi_line_comment || $in_single_line_comment) {
                continue;
            }

            if ($chars[$i] === '\\' || preg_match('/\w/', $char)) {
                if ($this->return_typehint_start === null) {
                    $this->return_typehint_start = $i + $end_bracket_position + 1;
                }

                if ($chars[$i + 1] !== '\\' && !preg_match('/[\w]/', $chars[$i + 1])) {
                    $this->return_typehint_end = $i + $end_bracket_position + 2;
                    break;
                }
            }
        }

        $preceding_newline_pos = strrpos($file_contents, "\n", $this->docblock_end - strlen($file_contents));

        if ($preceding_newline_pos === false) {
            $this->indentation = '';

            return;
        }

        $first_line = substr($file_contents, $preceding_newline_pos + 1, $this->docblock_end - $preceding_newline_pos);

        $this->indentation = str_replace(ltrim($first_line), '', $first_line);
    }

    /**
     * Sets the new return type
     *
     * @param   ?string     $php_type
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     * @param   bool        $is_php_compatible
     * @param   ?string     $description
     *
     * @return  void
     */
    public function setReturnType($php_type, $new_type, $phpdoc_type, $is_php_compatible, $description)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        $this->new_php_return_type = $php_type;
        $this->new_phpdoc_return_type = $phpdoc_type;
        $this->new_psalm_return_type = $new_type;
        $this->return_type_is_php_compatible = $is_php_compatible;
        $this->return_type_description = $description;
    }

    /**
     * Sets a new param type
     *
     * @param   string      $param_name
     * @param   ?string     $php_type
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     * @param   bool        $is_php_compatible
     *
     * @return  void
     */
    public function setParamType($param_name, $php_type, $new_type, $phpdoc_type, $is_php_compatible)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        if ($php_type) {
            $this->new_php_param_types[$param_name] = $php_type;
        }
        $this->new_phpdoc_param_types[$param_name] = $phpdoc_type;
        $this->new_psalm_param_types[$param_name] = $new_type;
        $this->param_type_is_php_compatible[$param_name] = $is_php_compatible;
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
            $parsed_docblock = DocComment::parse((string)$docblock, null, true);
        } else {
            $parsed_docblock = ['description' => '', 'specials' => []];
        }

        foreach ($this->new_phpdoc_param_types as $param_name => $phpdoc_type) {
            $found_in_params = false;
            $new_param_block = $phpdoc_type . ' ' . '$' . $param_name;

            if (isset($parsed_docblock['specials']['param'])) {
                foreach ($parsed_docblock['specials']['param'] as &$param_block) {
                    $doc_parts = CommentAnalyzer::splitDocLine($param_block);

                    if ($doc_parts[1] === '$' . $param_name) {
                        $param_block = $new_param_block;
                        $found_in_params = true;
                        break;
                    }
                }
            }

            if (!$found_in_params) {
                $parsed_docblock['specials']['params'][] = $new_param_block;
            }
        }

        if ($this->new_phpdoc_return_type) {
            $parsed_docblock['specials']['return'] = [
                $this->new_phpdoc_return_type
                    . ($this->return_type_description ? (' ' . $this->return_type_description) : '')
            ];
        }

        if ($this->new_phpdoc_return_type !== $this->new_psalm_return_type && $this->new_psalm_return_type) {
            $parsed_docblock['specials']['psalm-return'] = [$this->new_psalm_return_type];
        }

        return DocComment::render($parsed_docblock, $this->indentation);
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
                        ': ' . $manipulator->new_php_return_type
                    );
                }
            } elseif ($manipulator->return_typehint_colon_start
                && $manipulator->new_phpdoc_return_type
                && $manipulator->return_typehint_start
                && $manipulator->return_typehint_end
            ) {
                $file_manipulations[$manipulator->return_typehint_start] = new FileManipulation(
                    $manipulator->return_typehint_colon_start,
                    $manipulator->return_typehint_end,
                    ''
                );
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
