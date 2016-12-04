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
     */
    public function getFileSnippet()
    {
        $file_start = $this->code_location->file_start;
        $file_end = $this->code_location->file_end;
        $preview_start = $this->code_location->preview_start;

        $file_contents = (string)file_get_contents($this->code_location->file_path);

        if ($this->code_location->comment_line_number && $preview_start < $file_start) {
            $preview_lines = explode("\n", substr($file_contents, $preview_start, $file_start - $preview_start - 1));

            $preview_offset = 0;

            $i = 0;

            while ($i < $this->code_location->comment_line_number - $this->code_location->line_number) {
                $preview_offset += strlen($preview_lines[$i++]) + 1;
            }

            $preview_offset += (int)strpos($preview_lines[$i], '@');

            $file_start = $preview_offset + $preview_start;
        }

        $line_beginning = (int)strrpos(
            $file_contents,
            "\n",
            min($preview_start, $file_start) - strlen($file_contents)
        ) + 1;
        $line_end = (int)strpos($file_contents, "\n", $this->code_location->single_line ? $file_start : $file_end);

        $code_line = substr($file_contents, $line_beginning, $line_end - $line_beginning);
        $code_line_error_start = $file_start - $line_beginning;
        $code_line_error_length = $file_end - $file_start + 1;
        return substr($code_line, 0, $code_line_error_start) .
            "\e[97;41m" . substr($code_line, $code_line_error_start, $code_line_error_length) .
            "\e[0m" . substr($code_line, $code_line_error_length + $code_line_error_start) . PHP_EOL;
    }
}
