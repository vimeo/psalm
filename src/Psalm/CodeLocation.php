<?php
namespace Psalm;

class CodeLocation
{
    /** @var int */
    public $file_start;

    /** @var int */
    public $file_end;

    /** @var string */
    public $file_path;

    /** @var string */
    public $file_name;

    /** @var int */
    public $line_number;

    /** @var bool */
    public $single_line;

    /** @var int */
    public $preview_start;

    /** @var int|null */
    public $comment_line_number;

    /**
     * @param StatementsSource $statements_source
     * @param \PhpParser\Node  $stmt
     * @param boolean          $single_line
     */
    public function __construct(StatementsSource $statements_source, \PhpParser\Node $stmt, $single_line = false)
    {
        $this->file_start = (int)$stmt->getAttribute('startFilePos');
        $this->file_end = (int)$stmt->getAttribute('endFilePos');
        $this->file_path = $statements_source->getCheckedFilePath();
        $this->file_name = $statements_source->getCheckedFileName();
        $this->single_line = $single_line;

        $doc_comment = $stmt->getDocComment();
        $this->preview_start = $doc_comment ? $doc_comment->getFilePos() : $this->file_start;
        $this->line_number = $doc_comment ? $doc_comment->getLine() : $stmt->getLine();
    }

    /**
     * @param int $line
     * @return void
     */
    public function setCommentLine($line) {
        $this->comment_line_number = $line;
    }
}
