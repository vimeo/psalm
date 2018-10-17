<?php
namespace Vimeo\CodeAnalysis;

use PhpParser;
use Psalm\Checker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulation;

class StringChecker extends \Psalm\Plugin
{
    /**
     * Called after an expression has been checked
     *
     * @param  StatementsChecker    $statements_checker
     * @param  PhpParser\Node\Expr  $stmt
     * @param  Context              $context
     * @param  CodeLocation         $code_location
     * @param  string[]             $suppressed_issues
     * @param  FileManipulation[]   $file_replacements
     *
     * @return null|false
     */
    public static function afterExpressionCheck(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr $stmt,
        Context $context,
        CodeLocation $code_location,
        array $suppressed_issues,
        array &$file_replacements = []
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $class_or_class_method = '/^\\\?Psalm(\\\[A-Z][A-Za-z0-9]+)+(::[A-Za-z0-9]+)?$/';

            if (strpos($code_location->file_name, 'base/DefinitionManager.php') === false
                && strpos($stmt->value, 'TestController') === false
                && preg_match($class_or_class_method, $stmt->value)
            ) {
                $absolute_class = preg_split('/[:]/', $stmt->value)[0];

                if (\Psalm\IssueBuffer::accepts(
                    new \Psalm\Issue\InvalidClass(
                        'Use ::class constants when representing class names',
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $absolute_class
                    ),
                    $suppressed_issues
                )) {
                    // fall through
                }
            }
        } elseif ($stmt instanceof PhpParser\Node\Expr\BinaryOp\Concat
            && $stmt->left instanceof PhpParser\Node\Expr\ClassConstFetch
            && $stmt->left->class instanceof PhpParser\Node\Name
            && $stmt->left->name instanceof PhpParser\Node\Identifier
            && strtolower($stmt->left->name->name) === 'class'
            && !in_array(strtolower($stmt->left->class->parts[0]), ['self', 'static', 'parent'])
            && $stmt->right instanceof PhpParser\Node\Scalar\String_
            && preg_match('/^::[A-Za-z0-9]+$/', $stmt->right->value)
        ) {
            $method_id = ((string) $stmt->left->class->getAttribute('resolvedName')) . $stmt->right->value;

            $appearing_method_id = $project_checker->codebase->getAppearingMethodId($method_id);

            if (!$appearing_method_id) {
                if (\Psalm\IssueBuffer::accepts(
                    new \Psalm\Issue\UndefinedMethod(
                        'Method ' . $method_id . ' does not exist',
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $method_id
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return;
            }
        }
    }
}
