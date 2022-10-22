<?php

namespace Psalm\Type;

abstract class TypeVisitor
{
    public const STOP_TRAVERSAL = 1;
    public const DONT_TRAVERSE_CHILDREN = 2;

    /**
     * @internal Can only be called by a TypeNode
     *
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    abstract protected function enterNode(TypeNode $type): ?int;

    public function traverse(TypeNode $node): bool
    {
        $result = $this->enterNode($node);

        if ($result === self::DONT_TRAVERSE_CHILDREN) {
            return true;
        }

        if ($result === self::STOP_TRAVERSAL) {
            return false;
        }

        return $node->visit($this);
    }

    /**
     * @param non-empty-array<TypeNode> $nodes
     */
    public function traverseArray(array $nodes): void
    {
        foreach ($nodes as $node) {
            if ($this->traverse($node) === false) {
                return;
            }
        }
    }
}
