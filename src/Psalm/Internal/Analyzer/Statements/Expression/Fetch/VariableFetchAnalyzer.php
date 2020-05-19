<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
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
use function in_array;

/**
 * @internal
 */
class VariableFetchAnalyzer
{
    const SUPER_GLOBALS = [
        '$GLOBALS',
        '$_SERVER',
        '$_GET',
        '$_POST',
        '$_FILES',
        '$_COOKIE',
        '$_SESSION',
        '$_REQUEST',
        '$_ENV',
        '$http_response_header',
    ];

    /**
     * @param   StatementsAnalyzer               $statements_analyzer
     * @param   PhpParser\Node\Expr\Variable    $stmt
     * @param   Context                         $context
     * @param   bool                            $passed_by_reference
     * @param   Type\Union|null                 $by_ref_type
     * @param   bool                            $array_assignment
     * @param   bool                            $from_global - when used in a global keyword
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Variable $stmt,
        Context $context,
        $passed_by_reference = false,
        Type\Union $by_ref_type = null,
        $array_assignment = false,
        $from_global = false
    ) : bool {
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

                return true;
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

                return true;
            }

            $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope['$this']);

            if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                && ($stmt_type = $statements_analyzer->node_data->getType($stmt))
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
                );
            }

            return true;
        }

        if (!$context->check_variables) {
            if (is_string($stmt->name)) {
                $var_name = '$' . $stmt->name;

                if (!$context->hasVariable($var_name, $statements_analyzer)) {
                    $context->vars_in_scope[$var_name] = Type::getMixed();
                    $context->vars_possibly_in_scope[$var_name] = true;
                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());
                } else {
                    $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope[$var_name]);
                }
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            return true;
        }

        if (is_string($stmt->name) && self::isSuperGlobal('$' . $stmt->name)) {
            $var_name = '$' . $stmt->name;

            if (isset($context->vars_in_scope[$var_name])) {
                $statements_analyzer->node_data->setType($stmt, clone $context->vars_in_scope[$var_name]);

                return true;
            }

            $type = self::getGlobalType($var_name);

            $statements_analyzer->node_data->setType($stmt, $type);
            $context->vars_in_scope[$var_name] = clone $type;
            $context->vars_possibly_in_scope[$var_name] = true;

            return true;
        }

        if (!is_string($stmt->name)) {
            return ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
        }

        if ($passed_by_reference && $by_ref_type) {
            AssignmentAnalyzer::assignByRefParam(
                $statements_analyzer,
                $stmt,
                $by_ref_type,
                $by_ref_type,
                $context
            );

            return true;
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
                            // fall through
                        }

                        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                        return true;
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

                    $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                    return true;
                }
            }

            $first_appearance = $statements_analyzer->getFirstAppearance($var_name);

            if ($first_appearance && !$context->inside_isset && !$context->inside_unset) {
                if ($context->is_global) {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedGlobalVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
                    )) {
                        return true;
                    }
                } else {
                    if ($codebase->alter_code) {
                        if (!isset($project_analyzer->getIssuesToFix()['PossiblyUndefinedVariable'])) {
                            return true;
                        }

                        $branch_point = $statements_analyzer->getBranchPoint($var_name);

                        if ($branch_point) {
                            $statements_analyzer->addVariableInitialization($var_name, $branch_point);
                        }

                        return true;
                    }

                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ', first seen on line ' .
                                $first_appearance->getLineNumber(),
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                        (bool) $statements_analyzer->getBranchPoint($var_name)
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
            $stmt_type = clone $context->vars_in_scope[$var_name];

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if ($stmt_type->possibly_undefined_from_try && !$context->inside_isset) {
                if ($context->is_global) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedGlobalVariable(
                            'Possibly undefined global variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Possibly undefined variable ' . $var_name . ' defined in try block',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeType(
                    $statements_analyzer->getFilePath(),
                    $stmt,
                    $stmt_type->getId()
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
                            . ':' . $stmt_type->getId()
                    );
                }
            }
        }

        return true;
    }

    public static function isSuperGlobal(string $var_id) : bool
    {
        return in_array(
            $var_id,
            self::SUPER_GLOBALS,
            true
        );
    }

    public static function getGlobalType(string $var_id) : Type\Union
    {
        $config = \Psalm\Config::getInstance();

        if (isset($config->globals[$var_id])) {
            return Type::parseString($config->globals[$var_id]);
        }

        if ($var_id === '$argv') {
            return new Type\Union([
                new Type\Atomic\TArray([Type::getInt(), Type::getString()]),
            ]);
        }

        if ($var_id === '$argc') {
            return Type::getInt();
        }

        if (self::isSuperGlobal($var_id)) {
            $type = Type::getArray();

            return $type;
        }

        return Type::getMixed();
    }
}
