<?php

namespace Vimeo\CodeInspector;

use \PhpParser;

class ClosureChecker extends FunctionChecker
{
    public function check()
    {
        foreach ($this->_function->uses as $use) {
            $this->_declared_variables[$use->var] = 1;
        }

        parent::check();
    }
}
