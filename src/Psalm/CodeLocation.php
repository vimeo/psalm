<?php
namespace Psalm;

class CodeLocation
{
    /** @var string */
    public $file_path;

    /** @var string */
    public $file_name;

    /** @var int */
    protected $line_number;

    /** @var int */
    protected $file_start;

    /** @var int */
    protected $file_end;

    /** @var bool */
    protected $single_line;

    /** @var int */
    protected $preview_start;

    /** @var int */
    protected $preview_end = -1;

    /** @var int */
    protected $selection_start = -1;

    /** @var int */
    protected $selection_end = -1;

    /** @var string */
    protected $snippet = '';

    /** @var int|null */
    protected $docblock_line_number;

    /** @var string|null */
    protected $regex;

    /** @var boolean */
    private $have_recalculated = false;

    /**
     * @param StatementsSource $statements_source
     * @param \PhpParser\Node  $stmt
     * @param boolean          $single_line
     * @param string           $regex   A regular expression to select part of the snippet
     */
    public function __construct(
        StatementsSource $statements_source,
        \PhpParser\Node $stmt,
        $single_line = false,
        $regex = null
    ) {
        $this->file_start = (int)$stmt->getAttribute('startFilePos');
        $this->file_end = (int)$stmt->getAttribute('endFilePos');
        $this->file_path = $statements_source->getCheckedFilePath();
        $this->file_name = $statements_source->getCheckedFileName();
        $this->single_line = $single_line;
        $this->regex = $regex;

        $doc_comment = $stmt->getDocComment();
        $this->preview_start = $doc_comment ? $doc_comment->getFilePos() : $this->file_start;
        $this->line_number = $doc_comment ? $doc_comment->getLine() : $stmt->getLine();
    }

    /**
     * @param int $line
     * @return void
     */
    public function setCommentLine($line)
    {
        $this->docblock_line_number = $this->line_number;
        $this->line_number = $line;
    }

    /**
     * @psalm-suppress MixedArrayAccess
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

        if ($this->docblock_line_number && $this->preview_start < $this->selection_start) {
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

            $comment_line_offset = $this->line_number - $this->docblock_line_number;

            for ($i = 0; $i < $comment_line_offset; $i++) {
                $preview_offset += strlen($preview_lines[$i]) + 1;
            }

            $preview_offset += (int)strpos($preview_lines[$i], '@');

            $this->selection_start = $preview_offset + $this->preview_start;
            $this->selection_end = (int)strpos($file_contents, "\n", $this->selection_start);
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

        $this->snippet = substr($file_contents, $this->preview_start, $this->preview_end - $this->preview_start);
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->line_number;
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
