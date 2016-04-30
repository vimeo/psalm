<?php

namespace CodeInspector;

use PhpParser;

class ClosureChecker extends FunctionChecker
{
    public function check($extra_scope_vars = [])
    {
        $use_vars = [];
        foreach ($this->_function->uses as $use) {
            $use_vars[$use->var] = 'mixed';
        }

        parent::check($use_vars);
    }
}
