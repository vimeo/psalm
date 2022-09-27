<?php

namespace Psalm\Type;

use function is_array;

abstract class ImmutableTypeVisitor
{
    public const STOP_TRAVERSAL = 1;
    public const DONT_TRAVERSE_CHILDREN = 2;

    /**
     * @return self::STOP_TRAVERSAL|self::DONT_TRAVERSE_CHILDREN|null
     */
    abstract protected function enterNode(TypeNode $type): ?int;

    /**
     * @return bool - true if we want to continue traversal, false otherwise
     */
    public function traverse(TypeNode $node): bool
    {
        $visitor_result = $this->enterNode($node);

        if ($visitor_result === self::DONT_TRAVERSE_CHILDREN) {
            return true;
        }

        if ($visitor_result === self::STOP_TRAVERSAL) {
            return false;
        }

        foreach ($node->getChildNodeKeys() as $key) {
            if ($node instanceof Union || $node instanceof MutableUnion) {
                $child_node = $node->getAtomicTypes();
            } else {
                $child_node = $node->{$key};
            }
            if ($child_node === null) {
                continue;
            }
            if (is_array($child_node)) {
                $visitor_result = $this->traverseArray($child_node);
            } else {
                $visitor_result = $this->traverse($child_node);
            }
            if ($visitor_result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<TypeNode> $nodes
     */
    public function traverseArray(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($this->traverse($node) === false) {
                return false;
            }
        }
        return true;
    }
}
