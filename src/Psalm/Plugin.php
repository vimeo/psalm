<?php
namespace Psalm;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Storage\ClassLikeStorage;

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

    /**
     * @param  ClassLikeChecker $statements_checker
     * @param  ClassLikeStorage $storage
     * @param  PhpParser\Node\Stmt\ClassLike $stmt
     * @param  CodeLocation     $code_location
     * @return null|false
     */
    public function visitClassLike(
        ClassLikeChecker $statements_checker,
        PhpParser\Node\Stmt\ClassLike $stmt,
        ClassLikeStorage $storage,
        CodeLocation $code_location
    ) {
        return null;
    }
}
