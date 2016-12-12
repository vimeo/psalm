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
     * @param  CodeLocation         $code_location
     * @param  array                $suppressed_issues
     * @return null|false
     * @psalm-suppress InvalidReturnType
     */
    public function checkExpression(PhpParser\Node\Expr $stmt, Context $context, CodeLocation $code_location, array $suppressed_issues)
    {
        return null;
    }

    /**
     * Checks a statement
     *
     * @param  PhpParser\Node\Stmt|PhpParser\Node\Expr  $stmt
     * @param  Context                                  $context
     * @param  CodeLocation                             $code_location
     * @param  array                                    $suppressed_issues
     * @return null|false
     * @psalm-suppress InvalidReturnType
     */
    public function checkStatement(PhpParser\Node $stmt, Context $context, CodeLocation $code_location, array $suppressed_issues)
    {
        return null;
    }
}
