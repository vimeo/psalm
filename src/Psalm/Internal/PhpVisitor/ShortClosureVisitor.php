<?php
namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
class ShortClosureVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @var array<string, bool>
     */
    protected $used_variables = [];

    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr\Variable && \is_string($node->name)) {
            $this->used_variables['$' . $node->name] = true;
        };
    }

    /**
     * @return array<string, bool>
     */
    public function getUsedVariables()
    {
        return $this->used_variables;
    }
}
