<?php
namespace Psalm\Internal\Visitor;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;

class AssignmentMapVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @var array<string, array<string, bool>>
     */
    protected $assignment_map = [];

    /**
     * @var string|null
     */
    protected $this_class_name;

    /**
     * @param string|null $this_class_name
     */
    public function __construct($this_class_name)
    {
        $this->this_class_name = $this_class_name;
    }

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $left_var_id = ExpressionAnalyzer::getRootVarId($node->var, $this->this_class_name);
            $right_var_id = ExpressionAnalyzer::getRootVarId($node->expr, $this->this_class_name);

            if ($left_var_id) {
                $this->assignment_map[$left_var_id][$right_var_id ?: 'isset'] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof PhpParser\Node\Expr\PostInc
            || $node instanceof PhpParser\Node\Expr\PostDec
            || $node instanceof PhpParser\Node\Expr\PreInc
            || $node instanceof PhpParser\Node\Expr\PreDec
            || $node instanceof PhpParser\Node\Expr\AssignOp
        ) {
            $var_id = ExpressionAnalyzer::getRootVarId($node->var, $this->this_class_name);

            if ($var_id) {
                $this->assignment_map[$var_id][$var_id] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        } elseif ($node instanceof PhpParser\Node\Expr\FuncCall) {
            foreach ($node->args as $arg) {
                $arg_var_id = ExpressionAnalyzer::getRootVarId($arg->value, $this->this_class_name);

                if ($arg_var_id) {
                    $this->assignment_map[$arg_var_id][$arg_var_id] = true;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getAssignmentMap()
    {
        return $this->assignment_map;
    }
}
