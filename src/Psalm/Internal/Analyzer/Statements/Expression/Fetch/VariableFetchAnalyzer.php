<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\PossiblyUndefinedGlobalVariable;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\UndefinedGlobalVariable;
use Psalm\Issue\UndefinedVariable;
use Psalm\IssueBuffer;
use Psalm\Type;
use function is_string;

/**
 * @internal
 */
class VariableFetchAnalyzer
{
    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\Variable    $stmt
     * @param   Context                         $context
     * @param   bool                            $passed_by_reference
     * @param   Type\Union|null                 $by_ref_type
     * @param   bool                            $array_assignment
     * @param   bool                            $from_global - when used in a global keyword
     *
     * @return  false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        $passed_by_reference = false,
        Type\Union $by_ref_type = null,
        $array_assignment = false,
        $from_global = false
    ) {
        $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->name === 'this') {
            if ($statements_analyzer->isStatic()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a static context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if (!isset($context->vars_in_scope['$this'])) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Invalid reference to $this in a non-class context',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }

                $context->vars_in_scope['$this'] = Type::getMixed();
                $context->vars_possibly_in_scope['$this'] = true;

                return null;
            }

            $stmt->inferredType = clone $context->vars_in_scope['$this'];

            if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                && isset($stmt->inferredType)
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    (string) $stmt->inferredType
                );
            }

            return null;
        }

        if (!$context->check_variables) {
            if (is_string($stmt->name)) {
                $var_name = '$' . $stmt->name;

                if (!$context->hasVariable($var_name, $statements_analyzer)) {
                    $context->vars_in_scope[$var_name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $stmt->inferredType = Type::getMixed();
                } else {
                    $stmt->inferredType = clone $context->vars_in_scope[$var_name];
                }
            } else {
                $stmt->inferredType = Type::getMixed();
            }

            return null;
        }

        if (is_string($stmt->name) && $statements_analyzer->isSuperGlobal('$' . $stmt->name)) {
            $var_name = '$' . $stmt->name;

            if (isset($context->vars_in_scope[$var_name])) {
                $stmt->inferredType = clone $context->vars_in_scope[$var_name];

                return null;
            }

            $type = $statements_analyzer->getGlobalType($var_name);

            $stmt->inferredType = $type;
            $context->vars_in_scope[$var_name] = clone $type;
            $context->vars_possibly_in_scope[$var_name] = true;

            return null;
        }

        if (!is_string($stmt->name)) {
            return ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
        }

        if ($passed_by_reference && $by_ref_type) {
            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $stmt,
                $by_ref_type,
                $by_ref_type,
                $context
            );

            return null;
        }

        $var_name = '$' . $stmt->name;

        if (!$context->hasVariable($var_name, !$array_assignment ? $statements_analyzer : null)) {
            if (!isset($context->vars_possibly_in_scope[$var_name]) ||
                !$statements_analyzer->getFirstAppearance($var_name)
            ) {
                if ($array_assignment) {
                    // if we're in an array assignment, let's assign the variable
                    // because PHP allows it

                    $context->vars_in_scope[$var_name] = Type::getArray();
                    $context->vars_possibly_in_scope[$var_name] = true;

                    // it might have been defined first in another if/else branch
                    if (!$statements_analyzer->hasVariable($var_name)) {
                        $statements_analyzer->registerVariable(
                            $var_name,
                            new CodeLocation($statements_analyzer, $stmt),
                            $context->branch_point
                        );
                    }
                } elseif (!$context->inside_isset
                    || $statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                ) {
                    if ($context->is_global || $from_global) {
                        if (IssueBuffer::accepts(
                            new UndefinedGlobalVariable(
                                'Cannot find referenced variable ' . $var_name . ' in global scope',
                                new CodeLocation($statements_analyzer->getSource(), $stmt)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        $stmt->inferredType = Type::getMixed();

                        return null;
                    }

                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable ' . $var_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    $stmt->inferredType = Type::getMixed();

                    return false;
                }
            }

            $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

            if ($first_appearance && !$context->inside_isset && !$context->inside_unset) {
                if ($context->is_global) {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedGlobalVariable'])) {
                            return;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedVariable'])) {
                            return;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start . '-' . $first_appearance->raw_file_end . ':mixed'
                    );
                }

                $statements_analyzer->registerVariableUses([$first_appearance->getHash() => $first_appearance]);
            }
        } else {
            $stmt->inferredType = clone $context->vars_in_scope[$var_name];

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    (string) $stmt->inferredType
                );
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

                if ($first_appearance) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt,
                        $first_appearance->raw_file_start
                            . '-' . $first_appearance->raw_file_end
                            . ':' . $stmt->inferredType->getId()
                    );
                }
            }
        }

        return null;
    }
}
