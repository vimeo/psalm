<?php
namespace Psalm\Internal\Visitor;

use PhpParser;
use function count;

/**
 * Given a list of file diffs, this scans an AST to find the sections it can replace, and parses
 * just those methods.
 */
class TraitFinder extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /** @var list<PhpParser\Node\Stmt\Trait_> */
    private $matching_trait_nodes = [];

    private $fq_trait_name;

    public function __construct(string $fq_trait_name)
    {
        $this->fq_trait_name = $fq_trait_name;
    }

    /**
     * @param  PhpParser\Node $node
     * @param  bool $traverseChildren
     *
     * @return null|int|PhpParser\Node
     */
    public function enterNode(PhpParser\Node $node, &$traverseChildren = true)
    {
        if ($node instanceof PhpParser\Node\Stmt\Trait_
            && $node->getAttribute('resolvedName') === $this->fq_trait_name
        ) {
            $this->matching_trait_nodes[] = $node;
        }

        if ($node instanceof PhpParser\Node\Stmt\ClassLike
            || $node instanceof PhpParser\Node\FunctionLike
        ) {
            return PhpParser\NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    public function getNode() : ?PhpParser\Node\Stmt\Trait_
    {
        if (!count($this->matching_trait_nodes)) {
            return null;
        }

        if (count($this->matching_trait_nodes) === 1 || !\trait_exists($this->fq_trait_name)) {
            return $this->matching_trait_nodes[0];
        }

        try {
            $reflection_trait = new \ReflectionClass($this->fq_trait_name);
        } catch (\Throwable $t) {
            return null;
        }

        foreach ($this->matching_trait_nodes as $node) {
            if ($node->getLine() === $reflection_trait->getStartLine()) {
                return $node;
            }
        }

        return null;
    }
}
