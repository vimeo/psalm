<?php
namespace Psalm\Issue;

use Psalm\CodeLocation;

abstract class FixableCodeIssue extends CodeIssue
{
    /**
     * @var ?string
     */
    protected $replacement_text;

    /**
     * @param string        $message
     * @param CodeLocation  $code_location
     * @param string|null   $replacement_text
     */
    public function __construct(
        $message,
        CodeLocation $code_location,
        $replacement_text = null
    ) {
        parent::__construct($message, $code_location);
        $this->replacement_text = $replacement_text;
    }

    /**
     * @return ?string
     */
    public function getReplacementText()
    {
        return $this->replacement_text;
    }
}
