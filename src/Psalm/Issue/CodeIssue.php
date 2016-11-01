<?php

namespace Psalm\Issue;

abstract class CodeIssue
{
    const CODE_EXCEPTION = 1;

    /** @var string */
    protected $file_name;

    /** @var int */
    protected $line_number;

    /** @var string */
    protected $message;

    /**
     * @param string $message
     * @param string $file_name
     * @param int    $line_number
     */
    public function __construct($message, $file_name, $line_number)
    {
        $this->line_number = $line_number;
        $this->file_name = $file_name;
        $this->message = $message;
    }

    public function getLineNumber()
    {
        return $this->line_number;
    }

    public function getFileName()
    {
        return $this->file_name;
    }

    public function getMessage()
    {
        return $this->file_name . ':' . $this->line_number .' - ' . $this->message;
    }
}
