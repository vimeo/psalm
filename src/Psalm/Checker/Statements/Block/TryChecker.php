<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ScopeChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Scope\LoopScope;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;

class TryChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Stmt\TryCatch    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context,
        LoopScope $loop_scope = null
    ) {
        $statements_checker->analyze($stmt->stmts, $context, $loop_scope);

        $try_leaves_loop = $loop_scope
            && $loop_scope->final_actions
            && !in_array(ScopeChecker::ACTION_NONE, $loop_scope->final_actions, true);

        // clone context for catches after running the try block, as
        // we optimistically assume it only failed at the very end
        $original_context = clone $context;

        foreach ($stmt->catches as $catch) {
            $catch_context = clone $original_context;

            $fq_catch_classes = [];

            foreach ($catch->types as $catch_type) {
                $fq_catch_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $catch_type,
                    $statements_checker->getAliases()
                );

                if ($context->check_classes) {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker->getFileChecker()->project_checker,
                        $fq_catch_class,
                        new CodeLocation($statements_checker->getSource(), $catch_type, $context->include_location),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }

                $fq_catch_classes[] = $fq_catch_class;
            }

            $catch_var_id = '$' . $catch->var;

            $catch_context->vars_in_scope[$catch_var_id] = new Type\Union(
                array_map(
                    /**
                     * @param string $fq_catch_class
                     *
                     * @return Type\Atomic
                     */
                    function ($fq_catch_class) {
                        return new TNamedObject($fq_catch_class);
                    },
                    $fq_catch_classes
                )
            );

            // discard all clauses because crazy stuff may have happened in try block
            $catch_context->clauses = [];

            $catch_context->vars_possibly_in_scope[$catch_var_id] = true;

            if (!$statements_checker->hasVariable($catch_var_id)) {
                $statements_checker->registerVariable(
                    $catch_var_id,
                    new CodeLocation($statements_checker, $catch, $context->include_location, true)
                );
            }

            // this registers the variable to avoid unfair deadcode issues
            $catch_context->hasVariable($catch_var_id);

            $statements_checker->analyze($catch->stmts, $catch_context, $loop_scope);

            $context->referenced_var_ids = array_merge(
                $catch_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if (ScopeChecker::getFinalControlActions($catch->stmts) !== [ScopeChecker::ACTION_END]) {
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

        if ($stmt->finally) {
            $statements_checker->analyze($stmt->finally->stmts, $context, $loop_scope);
        }

        if ($loop_scope
            && !$try_leaves_loop
            && !in_array(ScopeChecker::ACTION_NONE, $loop_scope->final_actions, true)
        ) {
            $loop_scope->final_actions[] = ScopeChecker::ACTION_NONE;
        }

        return null;
    }
}
