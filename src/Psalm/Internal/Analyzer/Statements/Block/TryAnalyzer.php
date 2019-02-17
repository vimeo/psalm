<?php
namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidCatch;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @internal
 */
class TryAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Stmt\TryCatch    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\TryCatch $stmt,
        Context $context
    ) {
        $catch_actions = [];
        $all_catches_leave = true;

        $codebase = $statements_analyzer->getCodebase();

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_actions[$i] = ScopeAnalyzer::getFinalControlActions(
                $catch->stmts,
                $codebase->config->exit_functions
            );
            $all_catches_leave = $all_catches_leave && !in_array(ScopeAnalyzer::ACTION_NONE, $catch_actions[$i], true);
        }

        $existing_thrown_exceptions = $context->possibly_thrown_exceptions;

        /**
         * @var array<string, bool>
         */
        $context->possibly_thrown_exceptions = [];

        if ($all_catches_leave) {
            $try_context = $context;
        } else {
            $try_context = clone $context;

            if ($codebase->alter_code) {
                $try_context->branch_point = $try_context->branch_point ?: (int) $stmt->getAttribute('startFilePos');
            }
        }

        $assigned_var_ids = $try_context->assigned_var_ids;
        $context->assigned_var_ids = [];

        $old_unreferenced_vars = $try_context->unreferenced_vars;
        $newly_unreferenced_vars = [];
        $reassigned_vars = [];

        if ($statements_analyzer->analyze($stmt->stmts, $context) === false) {
            return false;
        }

        /** @var array<string, bool> */
        $newly_assigned_var_ids = $context->assigned_var_ids;

        $context->assigned_var_ids = array_merge(
            $assigned_var_ids,
            $newly_assigned_var_ids
        );

        if ($try_context !== $context) {
            foreach ($context->vars_in_scope as $var_id => $type) {
                if (!isset($try_context->vars_in_scope[$var_id])) {
                    $try_context->vars_in_scope[$var_id] = clone $type;
                    $try_context->vars_in_scope[$var_id]->from_docblock = true;
                    $type->possibly_undefined_from_try = true;
                } else {
                    $try_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $try_context->vars_in_scope[$var_id],
                        $type
                    );
                }
            }

            $try_context->vars_possibly_in_scope = $context->vars_possibly_in_scope;

            $context->referenced_var_ids = array_merge(
                $try_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if ($context->collect_references) {
                $newly_unreferenced_vars = array_merge(
                    $newly_unreferenced_vars,
                    array_diff_key(
                        $context->unreferenced_vars,
                        $old_unreferenced_vars
                    )
                );

                foreach ($context->unreferenced_vars as $var_id => $locations) {
                    if (isset($old_unreferenced_vars[$var_id])
                        && $old_unreferenced_vars[$var_id] !== $locations
                    ) {
                        $reassigned_vars[$var_id] = $locations;
                    }
                }
            }
        }

        $try_leaves_loop = $context->loop_scope
            && $context->loop_scope->final_actions
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true);

        if (!$all_catches_leave) {
            foreach ($newly_assigned_var_ids as $assigned_var_id => $_) {
                $context->removeVarFromConflictingClauses($assigned_var_id);
            }
        } else {
            foreach ($newly_assigned_var_ids as $assigned_var_id => $_) {
                $try_context->removeVarFromConflictingClauses($assigned_var_id);
            }
        }

        // at this point we have two contexts – $context, in which it is assumed that everything was fine,
        // and $try_context - which allows all variables to have the union of the values before and after
        // the try was applied
        $original_context = clone $try_context;

        $issues_to_suppress = [
            'RedundantCondition',
            'RedundantConditionGivenDocblockType',
            'TypeDoesNotContainNull',
            'TypeDoesNotContainType',
        ];

        /** @var int $i */
        foreach ($stmt->catches as $i => $catch) {
            $catch_context = clone $original_context;

            $fq_catch_classes = [];

            $catch_var_name = $catch->var->name;

            if (!is_string($catch_var_name)) {
                throw new \UnexpectedValueException('Catch var name must be a string');
            }

            foreach ($catch->types as $catch_type) {
                $fq_catch_class = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $catch_type,
                    $statements_analyzer->getAliases()
                );

                if ($original_context->check_classes) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_catch_class,
                        new CodeLocation($statements_analyzer->getSource(), $catch_type, $context->include_location),
                        $statements_analyzer->getSuppressedIssues(),
                        false
                    ) === false) {
                        return false;
                    }
                }

                if (($codebase->classExists($fq_catch_class)
                        && strtolower($fq_catch_class) !== 'exception'
                        && !($codebase->classExtends($fq_catch_class, 'Exception')
                            || $codebase->classImplements($fq_catch_class, 'Throwable')))
                    || ($codebase->interfaceExists($fq_catch_class)
                        && strtolower($fq_catch_class) !== 'throwable'
                        && !$codebase->interfaceExtends($fq_catch_class, 'Throwable'))
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidCatch(
                            'Class/interface ' . $fq_catch_class . ' cannot be caught',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $fq_catch_class
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                $fq_catch_classes[] = $fq_catch_class;
            }

            $potentially_caught_classes = array_flip($fq_catch_classes);

            if ($catch_context->collect_exceptions) {
                foreach ($fq_catch_classes as $fq_catch_class) {
                    $fq_catch_class_lower = strtolower($fq_catch_class);

                    foreach ($context->possibly_thrown_exceptions as $exception_fqcln => $_) {
                        $exception_fqcln_lower = strtolower($exception_fqcln);

                        if ($exception_fqcln_lower === $fq_catch_class_lower) {
                            unset($context->possibly_thrown_exceptions[$exception_fqcln]);
                            continue;
                        }

                        if ($codebase->classExists($exception_fqcln)
                            && $codebase->classExtendsOrImplements(
                                $exception_fqcln,
                                $fq_catch_class
                            )
                        ) {
                            unset($context->possibly_thrown_exceptions[$exception_fqcln]);
                            continue;
                        }

                        if ($codebase->interfaceExists($exception_fqcln)
                            && $codebase->interfaceExtends(
                                $exception_fqcln,
                                $fq_catch_class
                            )
                        ) {
                            unset($context->possibly_thrown_exceptions[$exception_fqcln]);
                            continue;
                        }
                    }
                }
            }

            $catch_var_id = '$' . $catch_var_name;

            $catch_context->vars_in_scope[$catch_var_id] = new Union(
                array_map(
                    /**
                     * @param string $fq_catch_class
                     *
                     * @return Type\Atomic
                     */
                    function ($fq_catch_class) use ($codebase) {
                        $catch_class_type = new TNamedObject($fq_catch_class);

                        if (version_compare(PHP_VERSION, '7.0.0dev', '>=')
                            && $codebase->interfaceExists($fq_catch_class)
                            && !$codebase->interfaceExtends($fq_catch_class, 'Throwable')
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

            if (!$statements_analyzer->hasVariable($catch_var_id)) {
                $location = new CodeLocation(
                    $statements_analyzer,
                    $catch->var,
                    $context->include_location
                );
                $statements_analyzer->registerVariable(
                    $catch_var_id,
                    $location,
                    $try_context->branch_point
                );
                $catch_context->unreferenced_vars[$catch_var_id] = [$location->getHash() => $location];
            }

            // this registers the variable to avoid unfair deadcode issues
            $catch_context->hasVariable($catch_var_id, $statements_analyzer);

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->addSuppressedIssues([$issue_to_suppress]);
                }
            }

            $statements_analyzer->analyze($catch->stmts, $catch_context);

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->removeSuppressedIssues([$issue_to_suppress]);
                }
            }

            $context->referenced_var_ids = array_merge(
                $catch_context->referenced_var_ids,
                $context->referenced_var_ids
            );

            if ($context->collect_references && $catch_actions[$i] !== [ScopeAnalyzer::ACTION_END]) {
                foreach ($context->unreferenced_vars as $var_id => $_) {
                    if (!isset($catch_context->unreferenced_vars[$var_id])) {
                        unset($context->unreferenced_vars[$var_id]);
                    }
                }

                $newly_unreferenced_vars = array_merge(
                    $newly_unreferenced_vars,
                    array_diff_key(
                        $catch_context->unreferenced_vars,
                        $old_unreferenced_vars
                    )
                );

                foreach ($catch_context->unreferenced_vars as $var_id => $locations) {
                    if (!isset($old_unreferenced_vars[$var_id])
                        && (isset($context->unreferenced_vars[$var_id])
                            || isset($newly_assigned_var_ids[$var_id]))
                    ) {
                        $statements_analyzer->registerVariableUses($locations);
                    } elseif (isset($old_unreferenced_vars[$var_id])
                        && $old_unreferenced_vars[$var_id] !== $locations
                    ) {
                        $statements_analyzer->registerVariableUses($locations);
                    }
                }
            }

            if ($context->collect_exceptions) {
                $potentially_caught_classes = array_diff_key(
                    $potentially_caught_classes,
                    $context->possibly_thrown_exceptions
                );
            }

            if ($catch_actions[$i] !== [ScopeAnalyzer::ACTION_END]) {
                foreach ($catch_context->vars_in_scope as $var_id => $type) {
                    if ($context->hasVariable($var_id)
                        && $context->vars_in_scope[$var_id]->getId() !== $type->getId()
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
            } elseif ($stmt->finally) {
                $context->vars_possibly_in_scope = array_merge(
                    $catch_context->vars_possibly_in_scope,
                    $context->vars_possibly_in_scope
                );
            }
        }

        if ($context->loop_scope
            && !$try_leaves_loop
            && !in_array(ScopeAnalyzer::ACTION_NONE, $context->loop_scope->final_actions, true)
        ) {
            $context->loop_scope->final_actions[] = ScopeAnalyzer::ACTION_NONE;
        }

        if ($stmt->finally) {
            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->addSuppressedIssues([$issue_to_suppress]);
                }
            }

            $statements_analyzer->analyze($stmt->finally->stmts, $context);

            foreach ($issues_to_suppress as $issue_to_suppress) {
                if (!in_array($issue_to_suppress, $suppressed_issues, true)) {
                    $statements_analyzer->removeSuppressedIssues([$issue_to_suppress]);
                }
            }
        }

        if ($context->collect_references) {
            foreach ($old_unreferenced_vars as $var_id => $locations) {
                if (isset($context->unreferenced_vars[$var_id])
                    && $context->unreferenced_vars[$var_id] !== $locations
                ) {
                    $statements_analyzer->registerVariableUses($locations);
                }
            }
        }

        $context->possibly_thrown_exceptions += $existing_thrown_exceptions;

        return null;
    }
}
