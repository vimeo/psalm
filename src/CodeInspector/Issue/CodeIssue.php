<?php

namespace CodeInspector\Issue;

abstract class CodeIssue
{
    const CODE_EXCEPTION = 1;

    protected $file_name;
    protected $line_number;
    protected $message;

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
