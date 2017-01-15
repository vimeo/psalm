<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

class TryChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\TryCatch    $stmt
     * @param   Context                         $context
     * @param   Context|null                    $loop_context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context,
        Context $loop_context = null
    ) {
        $statements_checker->analyze($stmt->stmts, $context, $loop_context);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            $catch_class = ClassLikeChecker::getFQCLNFromNameObject(
                $catch->type,
                $statements_checker
            );

            if ($context->check_classes) {
                $fq_class_name = $catch_class;

                if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                    $fq_class_name,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $catch, $context->include_location),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            $catch_var_id = '$' . $catch->var;

            $catch_context->vars_in_scope[$catch_var_id] = new Type\Union([
                new TNamedObject($catch_class)
            ]);

            $catch_context->vars_possibly_in_scope[$catch_var_id] = true;

            if (!$statements_checker->hasVariable($catch_var_id)) {
                $statements_checker->registerVariable(
                    $catch_var_id,
                    new CodeLocation($statements_checker, $catch, $context->include_location, true)
                );
            }

            // this registers the variable to avoid unfair deadcode issues
            $catch_context->hasVariable($catch_var_id);

            $statements_checker->analyze($catch->stmts, $catch_context, $loop_context);

            if ($context->collect_references) {
                $context->referenced_vars = array_merge(
                    $catch_context->referenced_vars,
                    $context->referenced_vars
                );
            }

            if (!ScopeChecker::doesAlwaysReturnOrThrow($catch->stmts)) {
                foreach ($catch_context->vars_in_scope as $catch_var => $type) {
                    if ($catch->var !== $catch_var &&
                        $context->hasVariable($catch_var) &&
                        (string) $context->vars_in_scope[$catch_var] !== (string) $type
                    ) {
                        $context->vars_in_scope[$catch_var] = Type::combineUnionTypes(
                            $context->vars_in_scope[$catch_var],
                            $type
                        );
                    }
                }

                $context->vars_possibly_in_scope = array_merge(
                    $catch_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            }
        }

        if ($stmt->finallyStmts) {
            $statements_checker->analyze($stmt->finallyStmts, $context, $loop_context);
        }

        return null;
    }
}
