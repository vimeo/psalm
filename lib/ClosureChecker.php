<?php

namespace CodeInspector;

use \PhpParser;

class ClosureChecker extends FunctionChecker
{
    public function check()
    {
        $use_vars = [];
        foreach ($this->_function->uses as $use) {
            $use_vars[$use->var] = true;
        }

        parent::check($use_vars);
    }
}
