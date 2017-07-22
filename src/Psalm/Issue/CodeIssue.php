<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Config;

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
     * @return CodeLocation
     */
    public function getLocation()
    {
        return $this->code_location;
    }

    /**
     * @return string
     */
    public function getShortLocation()
    {
        $previous_text = '';

        if ($this->code_location->previous_location) {
            $previous_location = $this->code_location->previous_location;
            $previous_text = ' from ' . $previous_location->file_name . ':' . $previous_location->getLineNumber();
        }

        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber() . $previous_text;
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
        return $this->message;
    }

    /**
     * @param  string          $severity
     *
     * @return array{severity: string, line_number: string, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, from: int, to: int, snippet_from: int, snippet_to: int, column: int}
     */
    public function toArray($severity = Config::REPORT_ERROR)
    {
        $location = $this->getLocation();
        $selection_bounds = $location->getSelectionBounds();
        $snippet_bounds = $location->getSnippetBounds();

        $fqcn_parts = explode('\\', get_called_class());
        $issue_type = array_pop($fqcn_parts);

        return [
            'severity' => $severity,
            'line_number' => $location->getLineNumber(),
            'type' => $issue_type,
            'message' => $this->getMessage(),
            'file_name' => $location->file_name,
            'file_path' => $location->file_path,
            'snippet' => $location->getSnippet(),
            'from' => $selection_bounds[0],
            'to' => $selection_bounds[1],
            'snippet_from' => $snippet_bounds[0],
            'snippet_to' => $snippet_bounds[1],
            'column' => $location->getColumn(),
        ];
    }
}
