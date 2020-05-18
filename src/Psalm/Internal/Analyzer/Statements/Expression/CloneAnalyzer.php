<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidClone;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;

class CloneAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Clone_ $stmt,
        Context $context
    ) : bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        $stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($stmt_expr_type) {
            $clone_type = $stmt_expr_type;

            $immutable_cloned = false;

            foreach ($clone_type->getAtomicTypes() as $clone_type_part) {
                if (!$clone_type_part instanceof TNamedObject
                    && !$clone_type_part instanceof TObject
                    && !$clone_type_part instanceof TMixed
                    && !$clone_type_part instanceof TTemplateParam
                ) {
                    if ($clone_type_part instanceof Type\Atomic\TFalse
                        && $clone_type->ignore_falsable_issues
                    ) {
                        continue;
                    }

                    if ($clone_type_part instanceof Type\Atomic\TNull
                        && $clone_type->ignore_nullable_issues
                    ) {
                        continue;
                    }

                    if (IssueBuffer::accepts(
                        new InvalidClone(
                            'Cannot clone ' . $clone_type_part,
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return true;
                }

                if ($clone_type_part instanceof TNamedObject) {
                    $immutable_cloned = true;
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);

            if ($immutable_cloned) {
                $stmt_expr_type = clone $stmt_expr_type;
                $statements_analyzer->node_data->setType($stmt, $stmt_expr_type);
                $stmt_expr_type->reference_free = true;
                $stmt_expr_type->allow_mutations = true;
            }
        }

        return true;
    }
}
