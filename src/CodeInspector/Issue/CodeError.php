<?php

namespace CodeInspector\Issue;

abstract class CodeError extends CodeIssue
{
    public function getMessage()
    {
        return 'Error: ' . parent::getMessage();
    }
}
