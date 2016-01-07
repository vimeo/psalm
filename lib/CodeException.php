<?php

namespace CodeInspector;

class CodeException extends \Exception
{
    const CODE_EXCEPTION = 1;
    public $line_number;

    public function __construct($message, $file_name, $line_number, $code = self::CODE_EXCEPTION, \Exception $previous = null)
    {
        $this->line_number = $line_number;
        $this->file_name = $file_name;

        parent::__construct($message, $code, $previous);
    }

    public function getSourceLine()
    {
    	return $this->line_number;
    }
}
