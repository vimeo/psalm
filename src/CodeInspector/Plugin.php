<?php

namespace CodeInspector;

use PhpParser;

abstract class Plugin
{
    /**
     * checks an expression
     * @param  PhpParser\Node\Expr  $stmt
     * @param  array<Type\Union>    &$vars_in_scope
     * @param  array                &$vars_possibly_in_scope
     * @param  string               $file_name
     * @return null|false
     */
    public function checkExpression(PhpParser\Node\Expr $stmt, Context $context, $file_name)
    {
        return;
    }

    /**
     * checks a statement
     * @param  PhpParser\Node       $stmt
     * @param  array<Type\Union>    &$vars_in_scope
     * @param  array                &$vars_possibly_in_scope
     * @param  string               $file_name
     * @return null|false
     */
    public function checkStatement(PhpParser\Node $stmt, Context $context, $file_name)
    {
        return;
    }
}
