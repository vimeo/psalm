<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\InterfaceChecker;
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
        $catch_actions = [];
        $all_catches_leave = true;

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_actions[$i] = ScopeChecker::getFinalControlActions($catch->stmts);
            $all_catches_leave = $all_catches_leave && !in_array(ScopeChecker::ACTION_NONE, $catch_actions[$i], true);
        }

        if ($all_catches_leave) {
            $try_context = $context;
        } else {
            $try_context = clone $context;
        }

        $assigned_var_ids = $context->assigned_var_ids;
        $context->assigned_var_ids = [];

        if ($statements_checker->analyze($stmt->stmts, $context, $loop_scope) === false) {
            return false;
        }

        $context->assigned_var_ids = $assigned_var_ids;

        if ($try_context !== $context) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (!isset($try_context->vars_in_scope[$var_id])) {
                    $try_context->vars_in_scope[$var_id] = clone $type;
                    $try_context->vars_in_scope[$var_id]->from_docblock = true;
                } else {
                    $try_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $try_context->vars_in_scope[$var_id],
                        $type
                    );
                }
            }

            $try_context->vars_possibly_in_scope = $context->vars_possibly_in_scope;
        }

        $try_leaves_loop = $loop_scope
            && $loop_scope->final_actions
            && !in_array(ScopeChecker::ACTION_NONE, $loop_scope->final_actions, true);

        if (!$all_catches_leave) {
            foreach ($assigned_var_ids as $assigned_var_id => $_) {
                $context->removeVarFromConflictingClauses($assigned_var_id);
            }
        } else {
            foreach ($assigned_var_ids as $assigned_var_id => $_) {
                $try_context->removeVarFromConflictingClauses($assigned_var_id);
            }
        }

        // at this point we have two contexts – $context, in which it is assumed that everything was fine,
        // and $try_context - which allows all variables to have the union of the values before and after
        // the try was applied
        $original_context = clone $try_context;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_context = clone $original_context;

            $fq_catch_classes = [];

            foreach ($catch->types as $catch_type) {
                $fq_catch_class = ClassLikeChecker::getFQCLNFromNameObject(
                    $catch_type,
                    $statements_checker->getAliases()
                );

                if ($original_context->check_classes) {
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
                    function ($fq_catch_class) use ($project_checker) {
                        $catch_class_type = new TNamedObject($fq_catch_class);

                        if (version_compare(PHP_VERSION, '7.0.0dev', '>=')
                            && InterfaceChecker::interfaceExists($project_checker, $fq_catch_class)
                            && !InterfaceChecker::interfaceExtends($project_checker, $fq_catch_class, 'Throwable')
                        ) {
                            $catch_class_type->addIntersectionType(new TNamedObject('Throwable'));
                        }

                        return $catch_class_type;
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

            $suppressed_issues = $statements_checker->getSuppressedIssues();

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_checker->addSuppressedIssues(['RedundantCondition']);
            }

            $statements_checker->analyze($catch->stmts, $catch_context, $loop_scope);

            if (!in_array('RedundantCondition', $suppressed_issues, true)) {
                $statements_checker->removeSuppressedIssues(['RedundantCondition']);
            }

            $context->referenced_var_ids = array_merge(
                $catch_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if ($catch_actions[$i] !== [ScopeChecker::ACTION_END]) {
                foreach ($catch_context->vars_in_scope as $var_id => $type) {
                    if ($catch->var !== $var_id &&
                        $context->hasVariable($var_id) &&
                        $context->vars_in_scope[$var_id]->getId() !== $type->getId()
                    ) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->vars_in_scope[$var_id],
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

        if ($loop_scope
            && !$try_leaves_loop
            && !in_array(ScopeChecker::ACTION_NONE, $loop_scope->final_actions, true)
        ) {
            $loop_scope->final_actions[] = ScopeChecker::ACTION_NONE;
        }

        if ($stmt->finally) {
            $statements_checker->analyze($stmt->finally->stmts, $context, $loop_scope);
        }

        return null;
    }
}
