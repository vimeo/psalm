<?php
namespace Psalm;

use PhpParser;

abstract class Plugin
{
    /**
     * Checks an expression
     *
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Context              $context
     * @param  string               $file_name
     * @param  array                $suppressed_issues
     * @return null|false
     * @psalm-suppress InvalidReturnType
     */
    public function checkExpression(PhpParser\Node\Expr $stmt, Context $context, $file_name, array $suppressed_issues)
    {
        return null;
    }

    /**
     * Checks a statement
     *
     * @param  PhpParser\Node       $stmt
     * @param  Context              $context
     * @param  string               $file_name
     * @param  array                $suppressed_issues
     * @return null|false
     * @psalm-suppress InvalidReturnType
     */
    public function checkStatement(PhpParser\Node $stmt, Context $context, $file_name, array $suppressed_issues)
    {
        return null;
    }
}
