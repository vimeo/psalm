<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class CodeIssue
{
    const CODE_EXCEPTION = 1;

    /**
     * @var CodeLocation
     */
    protected $code_location;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param string        $message
     * @param CodeLocation  $code_location
     */
    public function __construct($message, CodeLocation $code_location)
    {
        $this->code_location = $code_location;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->code_location->line_number;
    }

    /**
     * @return int[]
     */
    public function getFileRange()
    {
        return [$this->code_location->file_start, $this->code_location->file_end];
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->code_location->file_path;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->code_location->file_name;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->code_location->file_name . ':' . $this->code_location->line_number .' - ' . $this->message;
    }

    /**
     * @return string
     * @psalm-suppress MixedArrayAccess
     */
    public function getFileSnippet()
    {
        $selection_start = $this->code_location->file_start;
        $selection_end = $this->code_location->file_end;

        $preview_start = $this->code_location->preview_start;

        $file_contents = (string)file_get_contents($this->code_location->file_path);

        $preview_end = (int)strpos(
            $file_contents,
            "\n",
            $this->code_location->single_line ? $selection_start : $selection_end
        );

        if ($this->code_location->comment_line_number && $preview_start < $selection_start) {
            $preview_lines = explode(
                "\n",
                substr($file_contents, $preview_start, $selection_start - $preview_start - 1)
            );

            $preview_offset = 0;

            $i = 0;

            $comment_line_offset = $this->code_location->comment_line_number - $this->code_location->line_number;

            for ($i = 0; $i < $comment_line_offset; $i++) {
                $preview_offset += strlen($preview_lines[$i]) + 1;
            }

            $preview_offset += (int)strpos($preview_lines[$i], '@');

            $selection_start = $preview_offset + $preview_start;
            $selection_end = (int)strpos($file_contents, "\n", $selection_start);
        } elseif ($this->code_location->regex) {
            $preview_snippet = substr($file_contents, $selection_start, $selection_end - $selection_start);

            if (preg_match($this->code_location->regex, $preview_snippet, $matches, PREG_OFFSET_CAPTURE)) {
                $selection_start = $selection_start + (int)$matches[1][1];
                $selection_end = $selection_start + strlen((string)$matches[1][0]);
            }
        }

        // reset preview start to beginning of line
        $preview_start = (int)strrpos(
            $file_contents,
            "\n",
            min($preview_start, $selection_start) - strlen($file_contents)
        ) + 1;

        $code_line = substr($file_contents, $preview_start, $preview_end - $preview_start);

        $code_line_error_start = $selection_start - $preview_start;
        $code_line_error_length = $selection_end - $selection_start + 1;
        return substr($code_line, 0, $code_line_error_start) .
            "\e[97;41m" . substr($code_line, $code_line_error_start, $code_line_error_length) .
            "\e[0m" . substr($code_line, $code_line_error_length + $code_line_error_start) . PHP_EOL;
    }
}
