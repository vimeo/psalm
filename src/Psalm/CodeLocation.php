<?php
namespace Psalm;

use PhpParser;
use Psalm\Internal\Analyzer\CommentAnalyzer;

class CodeLocation
{
    /** @var string */
    public $file_path;

    /** @var string */
    public $file_name;

    /** @var int */
    protected $line_number;

    /** @var int */
    private $end_line_number = -1;

    /** @var int */
    protected $file_start;

    /** @var int */
    protected $file_end;

    /** @var bool */
    protected $single_line;

    /** @var int */
    protected $preview_start;

    /** @var int */
    private $preview_end = -1;

    /** @var int */
    private $selection_start = -1;

    /** @var int */
    private $selection_end = -1;

    /** @var int */
    private $column_from = -1;

    /** @var int */
    private $column_to = -1;

    /** @var string */
    private $snippet = '';

    /** @var null|string */
    private $text;

    /** @var int|null */
    private $docblock_start_line_number;

    /** @var int|null */
    private $docblock_line_number;

    /** @var null|int */
    private $regex_type;

    /** @var bool */
    private $have_recalculated = false;

    /** @var null|CodeLocation */
    public $previous_location;

    const VAR_TYPE = 0;
    const FUNCTION_RETURN_TYPE = 1;
    const FUNCTION_PARAM_TYPE = 2;
    const FUNCTION_PHPDOC_RETURN_TYPE = 3;
    const FUNCTION_PHPDOC_PARAM_TYPE = 4;
    const FUNCTION_PARAM_VAR = 5;
    const CATCH_VAR = 6;

    /**
     * @param bool                 $single_line
     * @param null|CodeLocation    $previous_location
     * @param null|int             $regex_type
     * @param null|string          $selected_text
     */
    public function __construct(
        FileSource $file_source,
        PhpParser\Node $stmt,
        CodeLocation $previous_location = null,
        $single_line = false,
        $regex_type = null,
        $selected_text = null
    ) {
        $this->file_start = (int)$stmt->getAttribute('startFilePos');
        $this->file_end = (int)$stmt->getAttribute('endFilePos');
        $this->file_path = $file_source->getFilePath();
        $this->file_name = $file_source->getFileName();
        $this->single_line = $single_line;
        $this->regex_type = $regex_type;
        $this->previous_location = $previous_location;
        $this->text = $selected_text;

        $doc_comment = $stmt->getDocComment();
        $this->preview_start = $doc_comment ? $doc_comment->getFilePos() : $this->file_start;
        $this->docblock_start_line_number = $doc_comment ? $doc_comment->getLine() : null;
        $this->line_number = $stmt->getLine();
    }

    /**
     * @param int $line
     *
     * @return void
     */
    public function setCommentLine($line)
    {
        $this->docblock_line_number = $line;
    }

    /**
     * @psalm-suppress MixedArrayAccess
     *
     * @return void
     */
    private function calculateRealLocation()
    {
        if ($this->have_recalculated) {
            return;
        }

        $this->have_recalculated = true;

        $this->selection_start = $this->file_start;
        $this->selection_end = $this->file_end + 1;

        $project_analyzer = Internal\Analyzer\ProjectAnalyzer::getInstance();

        $codebase = $project_analyzer->getCodebase();

        $file_contents = $codebase->getFileContents($this->file_path);

        $file_length = strlen($file_contents);

        $search_limit = $this->single_line ? $this->selection_start : $this->selection_end;

        if ($search_limit <= $file_length) {
            $preview_end = strpos(
                $file_contents,
                "\n",
                $search_limit
            );
        } else {
            $preview_end = false;
        }

        // if the string didn't contain a newline
        if ($preview_end === false) {
            $preview_end = $this->selection_end;
        }

        $this->preview_end = $preview_end;

        if ($this->docblock_line_number &&
            $this->docblock_start_line_number &&
            $this->preview_start < $this->selection_start
        ) {
            $preview_lines = explode(
                "\n",
                substr(
                    $file_contents,
                    $this->preview_start,
                    $this->selection_start - $this->preview_start - 1
                )
            );

            $preview_offset = 0;

            $comment_line_offset = $this->docblock_line_number - $this->docblock_start_line_number;

            for ($i = 0; $i < $comment_line_offset; ++$i) {
                $preview_offset += strlen($preview_lines[$i]) + 1;
            }

            $key_line = $preview_lines[$i];

            $indentation = (int)strpos($key_line, '@');

            $key_line = trim(preg_replace('@\**/\s*@', '', substr($key_line, $indentation)));

            $this->selection_start = $preview_offset + $indentation + $this->preview_start;
            $this->selection_end = $this->selection_start + strlen($key_line);
        }

        if ($this->regex_type !== null) {
            switch ($this->regex_type) {
                case self::VAR_TYPE:
                    $regex = '/@(psalm-)?var[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_RETURN_TYPE:
                    $regex = '/\\:\s+(\\??\s*[A-Za-z0-9_\\\\\[\]]+)/';
                    $match_offset = 1;
                    break;

                case self::FUNCTION_PARAM_TYPE:
                    $regex = '/^(\\??\s*[A-Za-z0-9_\\\\\[\]]+)\s/';
                    $match_offset = 1;
                    break;

                case self::FUNCTION_PHPDOC_RETURN_TYPE:
                    $regex = '/@(psalm-)?return[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_PHPDOC_PARAM_TYPE:
                    $regex = '/@(psalm-)?param[ \t]+' . CommentAnalyzer::TYPE_REGEX . '/';
                    $match_offset = 2;
                    break;

                case self::FUNCTION_PARAM_VAR:
                    $regex = '/(\$[^ ]*)/';
                    $match_offset = 1;
                    break;

                case self::CATCH_VAR:
                    $regex = '/(\$[^ ^\)]*)/';
                    $match_offset = 1;
                    break;

                default:
                    throw new \UnexpectedValueException('Unrecognised regex type ' . $this->regex_type);
            }

            $preview_snippet = substr(
                $file_contents,
                $this->selection_start,
                $this->selection_end - $this->selection_start
            );

            if ($this->text) {
                $regex = '/(' . str_replace(',', ',[ ]*', preg_quote($this->text, '/')) . ')/';
                $match_offset = 1;
            }

            if (preg_match($regex, $preview_snippet, $matches, PREG_OFFSET_CAPTURE)) {
                $this->selection_start = $this->selection_start + (int)$matches[$match_offset][1];
                $this->selection_end = $this->selection_start + strlen((string)$matches[$match_offset][0]);
            }
        }

        // reset preview start to beginning of line
        $this->preview_start = (int)strrpos(
            $file_contents,
            "\n",
            min($this->preview_start, $this->selection_start) - strlen($file_contents)
        ) + 1;

        $this->selection_start = max($this->preview_start, $this->selection_start);
        $this->selection_end = min($this->preview_end, $this->selection_end);

        if ($this->preview_end - $this->selection_end > 200) {
            $this->preview_end = (int)strrpos(
                $file_contents,
                "\n",
                $this->selection_end + 200 - strlen($file_contents)
            );

            // if the line is over 200 characters long
            if ($this->preview_end < $this->selection_end) {
                $this->preview_end = $this->selection_end + 50;
            }
        }

        $this->snippet = substr($file_contents, $this->preview_start, $this->preview_end - $this->preview_start);
        $this->text = substr($file_contents, $this->selection_start, $this->selection_end - $this->selection_start);

        // reset preview start to beginning of line
        $this->column_from = $this->selection_start -
            (int)strrpos($file_contents, "\n", $this->selection_start - strlen($file_contents));

        $newlines = substr_count($this->text, "\n");

        if ($newlines) {
            $this->column_to = $this->selection_end -
                (int)strrpos($file_contents, "\n", $this->selection_end - strlen($file_contents));
        } else {
            $this->column_to = $this->column_from + strlen($this->text);
        }

        $this->end_line_number = $this->getLineNumber() + $newlines;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->docblock_line_number ?: $this->line_number;
    }

    /**
     * @return int
     */
    public function getEndLineNumber()
    {
        $this->calculateRealLocation();

        return $this->end_line_number;
    }

    /**
     * @return string
     */
    public function getSnippet()
    {
        $this->calculateRealLocation();

        return $this->snippet;
    }

    /**
     * @return string
     */
    public function getSelectedText()
    {
        $this->calculateRealLocation();

        return (string)$this->text;
    }

    /**
     * @return int
     */
    public function getColumn()
    {
        $this->calculateRealLocation();

        return $this->column_from;
    }

    /**
     * @return int
     */
    public function getEndColumn()
    {
        $this->calculateRealLocation();

        return $this->column_to;
    }

    /**
     * @return array<int, int>
     */
    public function getSelectionBounds()
    {
        $this->calculateRealLocation();

        return [$this->selection_start, $this->selection_end];
    }

    /**
     * @return array<int, int>
     */
    public function getSnippetBounds()
    {
        $this->calculateRealLocation();

        return [$this->preview_start, $this->preview_end];
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return (string) $this->file_start;
    }
}
