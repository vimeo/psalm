<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Config;
use function explode;
use function get_called_class;
use function array_pop;

abstract class CodeIssue
{
    const ERROR_LEVEL = -1;
    const SHORTCODE = 0;

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
     */
    public function __construct(
        $message,
        CodeLocation $code_location
    ) {
        $this->code_location = $code_location;
        $this->message = $message;
    }

    public function getLocation(): CodeLocation
    {
        return $this->code_location;
    }

    public function getShortLocationWithPrevious(): string
    {
        $previous_text = '';

        if ($this->code_location->previous_location) {
            $previous_location = $this->code_location->previous_location;
            $previous_text = ' from ' . $previous_location->file_name . ':' . $previous_location->getLineNumber();
        }

        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber() . $previous_text;
    }

    public function getShortLocation(): string
    {
        return $this->code_location->file_name . ':' . $this->code_location->getLineNumber();
    }

    public function getFilePath(): string
    {
        return $this->code_location->file_path;
    }

    /**
     *
     * @psalm-suppress PossiblyUnusedMethod for convenience
     */
    public function getFileName(): string
    {
        return $this->code_location->file_name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param  string          $severity
     *
     */
    public function toIssueData($severity = Config::REPORT_ERROR): \Psalm\Internal\Analyzer\IssueData
    {
        $location = $this->getLocation();
        $selection_bounds = $location->getSelectionBounds();
        $snippet_bounds = $location->getSnippetBounds();

        $fqcn_parts = explode('\\', get_called_class());
        $issue_type = array_pop($fqcn_parts);

        return new \Psalm\Internal\Analyzer\IssueData(
            $severity,
            $location->getLineNumber(),
            $location->getEndLineNumber(),
            $issue_type,
            $this->getMessage(),
            $location->file_name,
            $location->file_path,
            $location->getSnippet(),
            $location->getSelectedText(),
            $selection_bounds[0],
            $selection_bounds[1],
            $snippet_bounds[0],
            $snippet_bounds[1],
            $location->getColumn(),
            $location->getEndColumn(),
            (int) static::SHORTCODE,
            (int) static::ERROR_LEVEL,
            $this instanceof TaintedInput ? $this->getTaintTrace() : null
        );
    }
}
