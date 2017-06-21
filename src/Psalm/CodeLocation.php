<?php
namespace Psalm;

class CodeLocation
{
    /** @var string */
    public $file_path;

    /** @var string */
    public $file_name;

    /** @var int */
    private $line_number;

    /** @var int */
    private $file_start;

    /** @var int */
    private $file_end;

    /** @var bool */
    private $single_line;

    /** @var int */
    private $preview_start;

    /** @var int */
    private $preview_end = -1;

    /** @var int */
    private $selection_start = -1;

    /** @var int */
    private $selection_end = -1;

    /** @var int */
    private $column = -1;

    /** @var string */
    private $snippet = '';

    /** @var int|null */
    private $docblock_start_line_number;

    /** @var int|null */
    private $docblock_line_number;

    /** @var string|null */
    private $regex;

    /** @var bool */
    private $have_recalculated = false;

    /** @var ?CodeLocation */
    public $previous_location;

    /**
     * @param StatementsSource $statements_source
     * @param \PhpParser\Node  $stmt
     * @param bool          $single_line
     * @param string           $regex   A regular expression to select part of the snippet
     * @param CodeLocation  $previous_location
     */
    public function __construct(
        StatementsSource $statements_source,
        \PhpParser\Node $stmt,
        CodeLocation $previous_location = null,
        $single_line = false,
        $regex = null
    ) {
        $this->file_start = (int)$stmt->getAttribute('startFilePos');
        $this->file_end = (int)$stmt->getAttribute('endFilePos');
        $this->file_path = $statements_source->getCheckedFilePath();
        $this->file_name = $statements_source->getCheckedFileName();
        $this->single_line = $single_line;
        $this->regex = $regex;
        $this->previous_location = $previous_location;

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

        $project_checker = Checker\ProjectChecker::getInstance();

        $file_contents = $project_checker->getFileContents($this->file_path);

        $preview_end = strpos(
            $file_contents,
            "\n",
            $this->single_line ? $this->selection_start : $this->selection_end
        );

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

            $i = 0;

            $comment_line_offset = $this->docblock_line_number - $this->docblock_start_line_number;

            for ($i = 0; $i < $comment_line_offset; ++$i) {
                $preview_offset += strlen($preview_lines[$i]) + 1;
            }

            $key_line = $preview_lines[$i];

            $indentation = (int)strpos($key_line, '@');

            $key_line = trim(preg_replace('@\**/\s*@', '', substr($key_line, $indentation)));

            $this->selection_start = $preview_offset + $indentation + $this->preview_start;
            $this->selection_end = $this->selection_start + strlen($key_line);
        } elseif ($this->regex) {
            $preview_snippet = substr(
                $file_contents,
                $this->selection_start,
                $this->selection_end - $this->selection_start
            );

            if (preg_match($this->regex, $preview_snippet, $matches, PREG_OFFSET_CAPTURE)) {
                $this->selection_start = $this->selection_start + (int)$matches[1][1];
                $this->selection_end = $this->selection_start + strlen((string)$matches[1][0]);
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

        // reset preview start to beginning of line
        $this->column = $this->selection_start -
            (int)strrpos($file_contents, "\n", $this->selection_start - strlen($file_contents));

        $this->snippet = substr($file_contents, $this->preview_start, $this->preview_end - $this->preview_start);
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->docblock_line_number ?: $this->line_number;
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
     * @return int
     */
    public function getColumn()
    {
        $this->calculateRealLocation();

        return $this->column;
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
}
