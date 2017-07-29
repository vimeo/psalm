<?php
namespace Psalm\Example\Plugin;

use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\CodeLocation;

/**
 * Checks all strings to see if they contain references to classes
 * and, if so, checks that those classes exist.
 *
 * You will need to add `"nikic/PHP-Parser": ">=3.0.2"` to your
 * composer.json.
 */
class StringChecker extends \Psalm\Plugin
{
    /**
     * Checks an expression
     *
     * @param  StatementsChecker     $statements_checker
     * @param  \PhpParser\Node\Expr  $stmt
     * @param  Context               $context
     * @param  CodeLocation          $code_location
     * @param  array<string>         $suppressed_issues
     * @return null|false
     */
    public function checkExpression(
        StatementsChecker $statements_checker,
        \PhpParser\Node\Expr $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($stmt instanceof \PhpParser\Node\Scalar\String_) {
            // Replace "Psalm" with your namespace
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (preg_match($class_or_class_method, $stmt->value)) {
                $fq_class_name = preg_split('/[:]/', $stmt->value)[0];

                $project_checker = $statements_checker->getFileChecker()->project_checker;
                if (Checker\ClassChecker::checkFullyQualifiedClassLikeName(
                    $project_checker,
                    $fq_class_name,
                    $code_location,
                    $suppressed_issues
                ) === false
                ) {
                    return false;
                }

                if ($fq_class_name !== $stmt->value) {
                    if (Checker\MethodChecker::checkMethodExists(
                        $project_checker,
                        $stmt->value,
                        $code_location,
                        $suppressed_issues
                    )
                    ) {
                        return false;
                    }
                }
            }
        }
    }
}

return new StringChecker;
