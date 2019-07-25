<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Config;
use function explode;
use function get_called_class;
use function array_pop;

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
    public function __construct(
        $message,
        CodeLocation $code_location
    ) {
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
    public function getShortLocationWithPrevious()
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
    public function getShortLocation()
    {
        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber();
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
     *
     * @psalm-suppress PossiblyUnusedMethod for convenience
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
     * @return array{severity: string, line_from: int, line_to: int, type: string, message: string, file_name: string,
     *  file_path: string, snippet: string, selected_text: string, from: int, to: int, snippet_from: int,
     *  snippet_to: int, column_from: int, column_to: int}
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
            'line_from' => $location->getLineNumber(),
            'line_to' => $location->getEndLineNumber(),
            'type' => $issue_type,
            'message' => $this->getMessage(),
            'file_name' => $location->file_name,
            'file_path' => $location->file_path,
            'snippet' => $location->getSnippet(),
            'selected_text' => $location->getSelectedText(),
            'from' => $selection_bounds[0],
            'to' => $selection_bounds[1],
            'snippet_from' => $snippet_bounds[0],
            'snippet_to' => $snippet_bounds[1],
            'column_from' => $location->getColumn(),
            'column_to' => $location->getEndColumn(),
        ];
    }
}
