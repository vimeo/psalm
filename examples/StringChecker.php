<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\Checker;
use Psalm\Context;

/**
 * Checks all strings to see if they contain references to classes
 * and, if so, checks that those classes exist
 */
class StringChecker extends \Psalm\Plugin
{
    /**
     * checks an expression
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Context              $context
     * @param  CodeLocation        $file_name
     * @param  array<string>        $suppressed_issues
     * @return null|false
     */
    public function checkExpression(
        PhpParser\Node\Expr $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if ($stmt instanceof \PhpParser\Node\Scalar\String_) {
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (preg_match($class_or_class_method, $stmt->value)) {
                $fq_class_name = preg_split('/[:]/', $stmt->value)[0];

                if (Checker\ClassChecker::checkFullyQualifiedClassLikeName(
                    $fq_class_name,
                    $code_location,
                    $suppressed_issues
                ) === false
                ) {
                    return false;
                }

                if ($fq_class_name !== $stmt->value) {
                    if (Checker\MethodChecker::checkMethodExists(
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
