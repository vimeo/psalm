<?php
namespace Psalm\Visitor;

use PhpParser;
use Psalm\Checker\Statements\ExpressionChecker;

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

    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr\Assign) {
            $left_var_id = ExpressionChecker::getVarId($node->var, $this->this_class_name);
            $right_var_id = ExpressionChecker::getVarId($node->expr, $this->this_class_name);

            if ($left_var_id) {
                $this->assignment_map[$left_var_id][(string)$right_var_id] = true;
            }

            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
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
