<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\StatementsChecker;

abstract class Plugin
{
    /**
     * Checks an expression
     *
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Context              $context
     * @param  CodeLocation         $code_location
     * @param  array                $suppressed_issues
     * @return null|false
     */
    public function checkExpression(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        return null;
    }

    /**
     * Checks a statement
     *
     * @param  StatementsChecker                        $statements_checker
     * @param  PhpParser\Node\Stmt|PhpParser\Node\Expr  $stmt
     * @param  Context                                  $context
     * @param  CodeLocation                             $code_location
     * @param  array                                    $suppressed_issues
     * @return null|false
     */
    public function checkStatement(
        StatementsChecker $statements_checker,
        PhpParser\Node $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        return null;
    }
}
