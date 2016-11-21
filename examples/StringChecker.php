<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\Checker;

/**
 * Checks all strings to see if they contain references to classes
 * and, if so, checks that those classes exist
 */
class StringChecker extends \Psalm\Plugin
{
    /**
     * checks an expression
     * @param  PhpParser\Node\Expr  $stmt
     * @param  array<Type\Union>    &$vars_in_scope
     * @param  array                &$vars_possibly_in_scope
     * @param  array                $suppressed_issues
     * @return null|false
     */
    public function checkExpression(PhpParser\Node\Expr $stmt, \Psalm\Context $context, $file_name, array $suppressed_issues)
    {
        if ($stmt instanceof \PhpParser\Node\Scalar\String_) {
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (preg_match($class_or_class_method, $stmt->value)) {
                $fq_class_name = preg_split('/[:]/', $stmt->value)[0];

                if (Checker\ClassChecker::checkFullyQualifiedClassLikeName(
                    $fq_class_name,
                    $file_name,
                    $stmt->getLine(),
                    $suppressed_issues) === false
                ) {
                    return false;
                }

                if ($fq_class_name !== $stmt->value) {
                    if (Checker\MethodChecker::checkMethodExists(
                        $stmt->value,
                        $file_name,
                        $stmt->getLine(),
                        $suppressed_issues)
                    ) {
                        return false;
                    }
                }
            }
        }
    }
}

return new StringChecker;
